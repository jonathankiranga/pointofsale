<?php
header('Content-Type: application/json; charset=utf-8');

function posSaveResponse($success, $message, $httpCode = 200, $extra = array()) {
    http_response_code($httpCode);
    echo json_encode(array_merge(array('success' => $success, 'message' => $message), $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    posSaveResponse(false, 'POST is required.', 405);
}
$receiptData = json_decode(file_get_contents('php://input'), true);
if (!is_array($receiptData) || empty($receiptData['items']) || empty($receiptData['docno'])) {
    posSaveResponse(false, 'Receipt number and at least one sale item are required.', 400);
}

$PathPrefix = '../';
include('../includes/globalsession.inc');
include_once('../includes/DateFunctions.inc');
include_once('../includes/SQL_CommonFunctions.inc');
include_once('../Ajax/DatafunctionsForAjax.inc');

if (empty($_SESSION['DatabaseName']) || empty($_SESSION['UserID'])) {
    posSaveResponse(false, 'Your POS session has expired. Sign in again.', 401);
}
global $mysqli, $db;
if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
    posSaveResponse(false, 'Could not connect to the company database.', 500);
}
$token = isset($_SERVER['HTTP_X_POS_FORMID']) ? (string)$_SERVER['HTTP_X_POS_FORMID'] : '';
if (empty($_SESSION['FormID']) || !hash_equals((string)$_SESSION['FormID'], $token)) {
    posSaveResponse(false, 'The POS security token has expired. Reload the terminal.', 403);
}

$docno = trim((string)$receiptData['docno']);
$paymentMethod = isset($receiptData['paymentMethod']) ? strtoupper(trim((string)$receiptData['paymentMethod'])) : 'CASH';
if (!in_array($paymentMethod, array('CASH', 'MPESA'), true)) {
    posSaveResponse(false, 'Unsupported POS payment method.', 400);
}
$cash = isset($receiptData['cash']) ? (float)$receiptData['cash'] : 0;
$change = isset($receiptData['change']) ? (float)$receiptData['change'] : 0;
$mpesaPhone = isset($receiptData['mpesaPhone']) ? trim((string)$receiptData['mpesaPhone']) : '';
if ($paymentMethod === 'MPESA') {
    $cash = 0;
    $change = 0;
    if (!preg_match('/^254[17][0-9]{8}$/', $mpesaPhone)) {
        posSaveResponse(false, 'A verified M-Pesa phone number is required.', 400);
    }
}

$config = array();
$configResult = $mysqli->query("SELECT confname, confvalue FROM config WHERE confname IN
    ('POS_DefaultCustomer','POS_DefaultStore','POS_DefaultCurrency','POS_DefaultPostingGroup')");
if ($configResult) {
    while ($row = $configResult->fetch_assoc()) { $config[$row['confname']] = $row['confvalue']; }
}
$requiredConfig = array('POS_DefaultCustomer','POS_DefaultStore','POS_DefaultCurrency','POS_DefaultPostingGroup');
foreach ($requiredConfig as $key) {
    if (empty($config[$key])) {
        posSaveResponse(false, 'POS configuration is incomplete. Set the default customer, store, currency and posting group in POS Configuration.', 409);
    }
}
$customerCode = $config['POS_DefaultCustomer'];
$storeCode = $config['POS_DefaultStore'];
$currencyCode = $config['POS_DefaultCurrency'];
$postingGroup = $config['POS_DefaultPostingGroup'];

$customerStmt = $mysqli->prepare("SELECT customer FROM debtors WHERE itemcode=? LIMIT 1");
$customerStmt->bind_param('s', $customerCode);
$customerStmt->execute();
$customerRow = $customerStmt->get_result()->fetch_assoc();
$customerStmt->close();
if (!$customerRow) { posSaveResponse(false, 'The configured POS customer no longer exists in the debtor master.', 409); }
$groupStmt = $mysqli->prepare("SELECT VATinclusive FROM postinggroups WHERE code=? LIMIT 1");
$groupStmt->bind_param('s', $postingGroup);
$groupStmt->execute();
$groupRow = $groupStmt->get_result()->fetch_assoc();
$groupStmt->close();
if (!$groupRow) { posSaveResponse(false, 'The configured POS posting group no longer exists.', 409); }
$vatInclusive = (int)$groupRow['VATinclusive'];

$shiftStmt = $mysqli->prepare("SELECT id FROM pos_shifts WHERE cashier_id=? AND status='OPEN' ORDER BY id DESC LIMIT 1");
$userId = (string)$_SESSION['UserID'];
$shiftStmt->bind_param('s', $userId);
$shiftStmt->execute();
$shiftRow = $shiftStmt->get_result()->fetch_assoc();
$shiftStmt->close();
if (!$shiftRow) {
    posSaveResponse(false, 'Open a cashier shift before completing a sale.', 409);
}
$shiftId = (int)$shiftRow['id'];

$items = array();
$calculatedTotal = 0.00;
$calculatedVat = 0.00;
$productStmt = $mysqli->prepare("SELECT sm.barcode, sm.itemcode, sm.descrip, sm.units, sm.sellingprice,
        IFNULL(vc.vat,0) AS vat_rate, ipg.vatcategory AS tax_category
    FROM stockmaster sm
    LEFT JOIN inventorypostinggroup ipg ON sm.postinggroup=ipg.code
    LEFT JOIN vatcategory vc ON ipg.vatcategory=vc.vatc
    WHERE sm.barcode=? AND sm.itemcode=? LIMIT 1");
if (!$productStmt) { posSaveResponse(false, 'Could not prepare ERP product validation.', 500); }

foreach ($receiptData['items'] as $item) {
    $barcode = isset($item['barcode']) ? trim((string)$item['barcode']) : '';
    $itemcode = isset($item['itemcode']) ? trim((string)$item['itemcode']) : '';
    $quantity = isset($item['quantity']) ? (float)$item['quantity'] : 0;
    if ($barcode === '' || $itemcode === '' || !is_finite($quantity) || $quantity <= 0) {
        $productStmt->close();
        posSaveResponse(false, 'A sale line contains an invalid product or quantity.', 400);
    }
    $productStmt->bind_param('ss', $barcode, $itemcode);
    if (!$productStmt->execute()) {
        $productStmt->close();
        posSaveResponse(false, 'Could not validate a sale item against the ERP product master.', 500);
    }
    $product = $productStmt->get_result()->fetch_assoc();
    if (!$product) {
        $productStmt->close();
        posSaveResponse(false, 'A product in this sale is no longer available in the ERP item master. Refresh the basket.', 409);
    }
    $unitPrice = (float)$product['sellingprice'];
    $vatRate = (float)$product['vat_rate'];
    $lineTotal = round($unitPrice * $quantity, 2);
    $vatAmount = $vatInclusive && $vatRate > 0
        ? round($lineTotal - ($lineTotal / (1 + ($vatRate / 100))), 2)
        : ($vatRate > 0 ? round($lineTotal * ($vatRate / 100), 2) : 0.00);
    if (!$vatInclusive) { $lineTotal = round($lineTotal + $vatAmount, 2); }
    $items[] = array(
        'barcode' => $product['barcode'],
        'itemcode' => $product['itemcode'],
        'description' => $product['descrip'],
        'units' => $product['units'],
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'vat_rate' => $vatRate,
        'vat_amount' => $vatAmount,
        'line_total' => $lineTotal,
        'tax_category' => $product['tax_category']
    );
    $calculatedTotal += $lineTotal;
    $calculatedVat += $vatAmount;
}
$productStmt->close();
$calculatedTotal = round($calculatedTotal, 2);
$calculatedVat = round($calculatedVat, 2);
if ($paymentMethod === 'CASH' && ($cash < $calculatedTotal || $change < 0 || $cash < 0)) {
    posSaveResponse(false, 'Cash received is insufficient or invalid.', 400);
}
if ($paymentMethod === 'MPESA') { $cash = $calculatedTotal; }

try {
    $mysqli->begin_transaction();

    // Lock the cashier shift while posting this receipt so it cannot be
    // closed between validation and insertion.
    $lockedShift = $mysqli->prepare("SELECT id FROM pos_shifts WHERE id=? AND cashier_id=? AND status='OPEN' FOR UPDATE");
    $lockedShift->bind_param('is', $shiftId, $userId);
    $lockedShift->execute();
    $stillOpen = $lockedShift->get_result()->fetch_assoc();
    $lockedShift->close();
    if (!$stillOpen) {
        $mysqli->rollback();
        posSaveResponse(false, 'This cashier shift has been closed. Open a new shift before completing the sale.', 409);
    }

    $check = $mysqli->prepare("SELECT documentno FROM SalesHeader WHERE documentno=? LIMIT 1 FOR UPDATE");
    $check->bind_param('s', $docno);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    $check->close();
    if ($existing) {
        $mysqli->rollback();
        posSaveResponse(true, 'This receipt has already been saved.', 200, array('documentno' => $docno, 'already_saved' => true));
    }

    $dateResult = $mysqli->query("SELECT NOW() AS transaction_date");
    $dateRow = $dateResult->fetch_assoc();
    $currentDate = $dateRow['transaction_date'];
    $_POST['date'] = ConvertSQLDate($currentDate);
    $periodNo = GetPeriod($_POST['date'], $db, true);
    if (!$periodNo) { throw new Exception('The current financial period could not be determined.'); }

    $lockResult = $mysqli->query("SELECT GET_LOCK('pos_sales_entryno',10) AS lock_ok");
    $lockRow = $lockResult ? $lockResult->fetch_assoc() : null;
    if (!$lockRow || (int)$lockRow['lock_ok'] !== 1) { throw new Exception('Could not lock the sales entry sequence.'); }
    $entryResult = $mysqli->query("SELECT COALESCE(MAX(entryno),0)+1 AS next_entry FROM SalesHeader");
    $entryRow = $entryResult->fetch_assoc();
    $entryNo = (int)$entryRow['next_entry'];

    $header = $mysqli->prepare("INSERT INTO SalesHeader
        (documenttype,documentno,docdate,customercode,customername,locationcode,postinggroup,currencycode,
         status,userid,period,entryno,vatinclusive,total,cash,change_amount,pos_shift_id,payment_method,mpesa_phone,total_vat)
        VALUES (1,?,?,?,?,?,?,?,1,?,?,?,?,?,?,?,?,?,?,?)");
    if (!$header) { throw new Exception('Could not prepare sales header: ' . $mysqli->error); }
    $customerName = (string)$customerRow['customer'];
    $userName = isset($_SESSION['UsersRealName']) ? (string)$_SESSION['UsersRealName'] : $userId;
    $periodString = (int)$periodNo;
    $header->bind_param('ssssssssiiidddissd',
        $docno, $currentDate, $customerCode, $customerName, $storeCode, $postingGroup, $currencyCode,
        $userId, $periodString, $entryNo, $vatInclusive, $calculatedTotal, $cash, $change,
        $shiftId, $paymentMethod, $mpesaPhone, $calculatedVat);
    if (!$header->execute()) { throw new Exception('Could not save sales header: ' . $header->error); }
    $header->close();

    $lineStmt = $mysqli->prepare("INSERT INTO SalesLine
        (entryno,documenttype,docdate,documentno,locationcode,code,description,unitofmeasure,
         Quantity,Quantity_toinvoice,Qunatity_delivered,UnitPrice,vatamount,invoiceamount,
         completed,printed,vatrate,inclusive,pos_tax_category)
        VALUES (?,1,?,?,?,?,?,?,?,?,?,?,?,?,1,1,?,?,?)");
    if (!$lineStmt) { throw new Exception('Could not prepare sales lines: ' . $mysqli->error); }
    foreach ($items as $saleLine) {
        $lineEntry = $entryNo;
        $lineLocation = $storeCode;
        $code = $saleLine['itemcode'];
        $description = $saleLine['description'];
        $unit = $saleLine['units'];
        $qty = $saleLine['quantity'];
        $unitPrice = $saleLine['unit_price'];
        $vatAmount = $saleLine['vat_amount'];
        $lineTotal = $saleLine['line_total'];
        $rate = $saleLine['vat_rate'];
        $taxCategory = $saleLine['tax_category'];
        $lineStmt->bind_param('issssssdddddddis', $lineEntry, $currentDate, $docno, $lineLocation,
            $code, $description, $unit, $qty, $qty, $qty, $unitPrice, $vatAmount, $lineTotal, $rate, $vatInclusive, $taxCategory);
        if (!$lineStmt->execute()) { throw new Exception('Could not save a sales line: ' . $lineStmt->error); }
    }
    $lineStmt->close();
    $mysqli->query("SELECT RELEASE_LOCK('pos_sales_entryno')");

    $printItems = array();
    foreach ($items as $saleLine) {
        $printItems[] = array(
            'barcode' => $saleLine['barcode'], 'itemcode' => $saleLine['itemcode'],
            'description' => $saleLine['description'], 'quantity' => $saleLine['quantity'],
            'unitPrice' => $saleLine['unit_price'], 'vatRate' => $saleLine['vat_rate'],
            'vatAmount' => $saleLine['vat_amount'], 'lineTotal' => $saleLine['line_total']
        );
    }
    $mysqli->commit();
    posSaveResponse(true, 'Receipt saved successfully.', 200, array(
        'documentno' => $docno, 'total' => $calculatedTotal, 'total_vat' => $calculatedVat,
        'items' => $printItems
    ));
} catch (Exception $e) {
    $mysqli->rollback();
    $mysqli->query("SELECT RELEASE_LOCK('pos_sales_entryno')");
    error_log('POS receipt save failed: ' . $e->getMessage());
    posSaveResponse(false, 'The receipt could not be saved. Check the server error log.', 500);
}
?>
