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
if (!is_array($receiptData) || !isset($receiptData['items']) || !is_array($receiptData['items']) || count($receiptData['items']) < 1 || !isset($receiptData['docno'])) {
    posSaveResponse(false, 'Receipt number and at least one sale item are required.', 400);
}

$PathPrefix = '../';
include('../includes/globalsession.inc');
include_once('../includes/DateFunctions.inc');
include_once('../includes/SQL_CommonFunctions.inc');
include_once('../includes/chartbalancing.inc');
if (!defined('POS_ACCOUNTING_HELPERS_ONLY')) {
    define('POS_ACCOUNTING_HELPERS_ONLY', true);
}
include_once('../transactions/Saveinvoice.inc');

if (empty($_SESSION['DatabaseName']) || empty($_SESSION['UserID'])) {
    posSaveResponse(false, 'Your POS session has expired. Sign in again.', 401);
}
global $db;
if (!($db instanceof mysqli) || $db->connect_errno) {
    posSaveResponse(false, 'Could not connect to the company database.', 500);
}
$token = isset($_SERVER['HTTP_X_POS_FORMID']) ? (string)$_SERVER['HTTP_X_POS_FORMID'] : '';
if (empty($_SESSION['FormID']) || !hash_equals((string)$_SESSION['FormID'], $token)) {
    posSaveResponse(false, 'The POS security token has expired. Reload the terminal.', 403);
}

$docno = trim((string)$receiptData['docno']);
if ($docno === '' || strlen($docno) > 20 || !preg_match('/^[A-Za-z0-9._-]+$/', $docno)) {
    posSaveResponse(false, 'The receipt number is missing or exceeds the allowed length.', 400);
}
$paymentMethod = isset($receiptData['paymentMethod']) ? strtoupper(trim((string)$receiptData['paymentMethod'])) : '';
if (!in_array($paymentMethod, array('CASH', 'MPESA'), true)) {
    posSaveResponse(false, 'A valid POS payment method is required.', 400);
}
$mpesaPhone = isset($receiptData['mpesaPhone']) ? trim((string)$receiptData['mpesaPhone']) : '';
$mpesaReference = isset($receiptData['mpesaReference']) ? strtoupper(trim((string)$receiptData['mpesaReference'])) : '';
if ($paymentMethod === 'MPESA') {
    $cash = 0.00;
    $change = 0.00;
    if (!preg_match('/^254[17][0-9]{8}$/', $mpesaPhone)) {
        posSaveResponse(false, 'A valid M-Pesa phone number is required.', 400);
    }
    if (!preg_match('/^[A-Z0-9]{8,20}$/', $mpesaReference)) {
        posSaveResponse(false, 'Enter the M-Pesa transaction code from the payment confirmation message.', 400);
    }
} else {
    $mpesaReference = '';
    $cashValue = isset($receiptData['cash']) ? $receiptData['cash'] : null;
    $changeValue = isset($receiptData['change']) ? $receiptData['change'] : null;
    if (!is_numeric($cashValue) || !is_numeric($changeValue) ||
        !is_finite((float)$cashValue) || !is_finite((float)$changeValue)) {
        posSaveResponse(false, 'Cash received and change must both be valid amounts.', 400);
    }
    $cash = round((float)$cashValue, 2);
    $change = round((float)$changeValue, 2);
}

$receiptReference = ($paymentMethod === 'MPESA')
    ? 'MPESA ' . $mpesaReference
    : 'POS CASH ' . $docno;

$config = array();
$configResult = $db->query("SELECT confname, confvalue FROM config WHERE confname IN
    ('POS_DefaultCustomer','POS_DefaultStore','POS_DefaultCurrency','POS_CashBankAccount','POS_MPesaBankAccount')");
if ($configResult) {
    while ($row = $configResult->fetch_assoc()) { $config[$row['confname']] = $row['confvalue']; }
}
$requiredConfig = array('POS_DefaultCustomer','POS_DefaultStore','POS_DefaultCurrency');
foreach ($requiredConfig as $key) {
    if (empty($config[$key])) {
        posSaveResponse(false, 'POS configuration is incomplete. Set the default customer, store and currency in POS Configuration.', 409);
    }
}
$customerCode = $config['POS_DefaultCustomer'];
$storeCode = $config['POS_DefaultStore'];
$currencyCode = $config['POS_DefaultCurrency'];
$postingGroup = $config['POS_DefaultPostingGroup'];

$customerStmt = $db->prepare("SELECT d.customer,d.customerposting,p.debtorsaccount,p.VATinclusive
    FROM debtors d
    INNER JOIN postinggroups p ON p.code=d.customerposting
    WHERE d.itemcode=? LIMIT 1");
if (!$customerStmt) { posSaveResponse(false, 'Could not prepare the POS customer validation query.', 500); }
$customerStmt->bind_param('s', $customerCode);
if (!$customerStmt->execute()) {
    $customerError = $customerStmt->error;
    $customerStmt->close();
    posSaveResponse(false, 'Could not validate the configured POS customer: ' . $customerError, 500);
}
$customerRow = $customerStmt->get_result()->fetch_assoc();
$customerStmt->close();
if (!$customerRow || empty($customerRow['customer']) || trim((string)$customerRow['customer']) === '' ||
    empty($customerRow['customerposting']) || empty($customerRow['debtorsaccount'])) {
    posSaveResponse(false, 'The configured POS customer has no valid debtor posting group/account. Correct the debtor master and its posting-group setup before selling.', 409);
}
if ($customerRow['VATinclusive'] === null ||
    !in_array((string)$customerRow['VATinclusive'], array('0','1'), true)) {
    posSaveResponse(false, 'The configured POS customer posting group has a NULL or invalid VAT-inclusive setting.', 409);
}
$vatInclusive = (int)$customerRow['VATinclusive'];

$shiftStmt = $db->prepare("SELECT id FROM pos_shifts WHERE cashier_id=? AND status='OPEN' ORDER BY id DESC LIMIT 1");
if (!$shiftStmt) { posSaveResponse(false, 'Could not prepare the cashier shift validation query.', 500); }
$userId = (string)$_SESSION['UserID'];
$shiftStmt->bind_param('s', $userId);
if (!$shiftStmt->execute()) {
    $shiftError = $shiftStmt->error;
    $shiftStmt->close();
    posSaveResponse(false, 'Could not validate the cashier shift: ' . $shiftError, 500);
}
$shiftRow = $shiftStmt->get_result()->fetch_assoc();
$shiftStmt->close();
if (!$shiftRow) {
    posSaveResponse(false, 'Open a cashier shift before completing a sale.', 409);
}
$shiftId = (int)$shiftRow['id'];

$bankConfigKey = ($paymentMethod === 'CASH') ? 'POS_CashBankAccount' : 'POS_MPesaBankAccount';
$bankAccountCode = isset($config[$bankConfigKey]) ? trim((string)$config[$bankConfigKey]) : '';
if ($bankAccountCode === '') {
    posSaveResponse(false, 'Configure the POS cash and M-Pesa bank accounts before taking payments.', 409);
}
$bankStmt = $db->prepare("SELECT ba.accountcode,ba.currency,ba.PostingGroup,c.rate
    FROM bankaccounts ba
    INNER JOIN currencies c ON c.currabrev=ba.currency
    WHERE ba.accountcode=? LIMIT 1");
if (!$bankStmt) { posSaveResponse(false, 'Could not prepare the POS payment bank account.', 500); }
$bankStmt->bind_param('s', $bankAccountCode);
if (!$bankStmt->execute()) {
    $bankError = $bankStmt->error;
    $bankStmt->close();
    posSaveResponse(false, 'Could not validate the POS payment bank account: ' . $bankError, 500);
}
$bankRow = $bankStmt->get_result()->fetch_assoc();
$bankStmt->close();
if (!$bankRow || trim((string)$bankRow['currency']) !== trim((string)$currencyCode) ||
    empty($bankRow['PostingGroup']) || !is_numeric($bankRow['rate']) ||
    !is_finite((float)$bankRow['rate']) || (float)$bankRow['rate'] <= 0) {
    posSaveResponse(false, 'The configured POS payment bank account is missing, has no posting group, or uses a different currency.', 409);
}

$items = array();
$calculatedTotal = 0.00;
$calculatedVat = 0.00;
$productStmt = $db->prepare("SELECT sm.barcode, sm.itemcode, sm.descrip, sm.units, sm.sellingprice,
        vc.vat AS vat_rate, ipg.vatcategory AS tax_category, ipg.code AS product_posting_group,
        ipg.defaultgl_sales AS sales_account, ipg.defaultgl_vat AS vat_account
    FROM stockmaster sm
    LEFT JOIN inventorypostinggroup ipg ON sm.postinggroup=ipg.code
    LEFT JOIN vatcategory vc ON ipg.vatcategory=vc.vatc
    WHERE sm.barcode=? AND sm.itemcode=? LIMIT 1");
if (!$productStmt) { posSaveResponse(false, 'Could not prepare ERP product validation.', 500); }

foreach ($receiptData['items'] as $item) {
    $barcode = isset($item['barcode']) ? trim((string)$item['barcode']) : '';
    $itemcode = isset($item['itemcode']) ? trim((string)$item['itemcode']) : '';
    $quantityValue = isset($item['quantity']) ? $item['quantity'] : null;
    $quantity = is_numeric($quantityValue) ? (float)$quantityValue : 0;
    if ($barcode === '' || $itemcode === '' || !is_numeric($quantityValue) ||
        !is_finite($quantity) || $quantity <= 0) {
        $productStmt->close();
        posSaveResponse(false, 'A sale line contains an invalid product or quantity.', 400);
    }
    $productStmt->bind_param('ss', $barcode, $itemcode);
    if (!$productStmt->execute()) {
        $productStmt->close();
        posSaveResponse(false, 'Could not validate a sale item against the ERP product master.', 500);
    }
    $productResult = $productStmt->get_result();
    if (!$productResult) {
        $productStmt->close();
        posSaveResponse(false, 'Could not read the ERP product validation result.', 500);
    }
    $product = $productResult->fetch_assoc();
    if (!$product) {
        $productStmt->close();
        posSaveResponse(false, 'A product in this sale is no longer available in the ERP item master. Refresh the basket.', 409);
    }
    if ($product['sellingprice'] === null || !is_numeric($product['sellingprice']) ||
        $product['product_posting_group'] === null || trim((string)$product['product_posting_group']) === '' ||
        $product['tax_category'] === null || trim((string)$product['tax_category']) === '' ||
        $product['sales_account'] === null || trim((string)$product['sales_account']) === '' ||
        $product['vat_account'] === null || trim((string)$product['vat_account']) === '' ||
        $product['vat_rate'] === null || !is_numeric($product['vat_rate']) ||
        $product['descrip'] === null || trim((string)$product['descrip']) === '' ||
        $product['units'] === null || trim((string)$product['units']) === '') {
        $productStmt->close();
        posSaveResponse(false, 'A sale item has a missing selling price, inventory posting group or VAT mapping. Correct the item setup before selling it.', 409);
    }
    $unitPrice = (float)$product['sellingprice'];
    $vatRate = (float)$product['vat_rate'];
    if (!is_finite($unitPrice) || $unitPrice < 0 || !is_finite($vatRate) || $vatRate < 0) {
        $productStmt->close();
        posSaveResponse(false, 'A sale item has an invalid selling price or VAT rate.', 409);
    }
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
if ($calculatedTotal <= 0) {
    posSaveResponse(false, 'The sale total must be greater than zero before payment can be posted.', 400);
}
if ($paymentMethod === 'CASH') {
    $expectedChange = round($cash - $calculatedTotal, 2);
    if ($cash < $calculatedTotal || $cash < 0 || $change < 0 ||
        $change > $cash || abs($change - $expectedChange) > 0.001) {
        posSaveResponse(false, 'Cash received and change do not balance to the sale total.', 400);
    }
}

$entryLockAcquired = false;
try {
    if (!$db->begin_transaction()) {
        throw new Exception('Could not start the POS sale transaction: ' . $db->error);
    }

    // Lock the cashier shift while posting this receipt so it cannot be
    // closed between validation and insertion.
    $lockedShift = $db->prepare("SELECT id FROM pos_shifts WHERE id=? AND cashier_id=? AND status='OPEN' FOR UPDATE");
    if (!$lockedShift) { throw new Exception('Could not lock the cashier shift: ' . $db->error); }
    $lockedShift->bind_param('is', $shiftId, $userId);
    if (!$lockedShift->execute()) { throw new Exception('Could not verify the cashier shift: ' . $lockedShift->error); }
    $stillOpen = $lockedShift->get_result()->fetch_assoc();
    $lockedShift->close();
    if (!$stillOpen) {
        $db->rollback();
        posSaveResponse(false, 'This cashier shift has been closed. Open a new shift before completing the sale.', 409);
    }

    $dateResult = $db->query("SELECT NOW() AS transaction_date");
    if (!$dateResult) { throw new Exception('Could not obtain the POS transaction date: ' . $db->error); }
    $dateRow = $dateResult->fetch_assoc();
    if (!$dateRow || empty($dateRow['transaction_date'])) {
        throw new Exception('The database returned a NULL POS transaction date.');
    }
    $currentDate = $dateRow['transaction_date'];
    $_POST['date'] = ConvertSQLDate($currentDate);
    $periodNo = GetPeriod($_POST['date'], $db, true);
    if ($periodNo === null || $periodNo === false || !is_numeric($periodNo) || (int)$periodNo < 0) {
        throw new Exception('The current financial period could not be determined.');
    }

    $lockResult = $db->query("SELECT GET_LOCK('pos_sales_entryno',10) AS lock_ok");
    $lockRow = $lockResult ? $lockResult->fetch_assoc() : null;
    if (!$lockRow || (int)$lockRow['lock_ok'] !== 1) { throw new Exception('Could not lock the sales entry sequence.'); }
    $entryLockAcquired = true;

    // Serialize the duplicate check with the entry-number allocation. A retry
    // after a committed request must not create a second invoice or receipt.
    $check = $db->prepare("SELECT documentno,total,total_vat FROM salesheader
        WHERE documenttype=1 AND documentno=? LIMIT 1 FOR UPDATE");
    if (!$check) { throw new Exception('Could not check for an existing POS receipt: ' . $db->error); }
    $check->bind_param('s', $docno);
    if (!$check->execute()) { throw new Exception('Could not check for an existing POS receipt: ' . $check->error); }
    $existing = $check->get_result()->fetch_assoc();
    $check->close();
    if ($existing) {
        $invoiceCheck = $db->prepare("SELECT documentno FROM salesheader
            WHERE documenttype=10 AND externaldocumentno=? LIMIT 1");
        $invoiceCheck->bind_param('s', $docno);
        $invoiceCheck->execute();
        $invoiceRow = $invoiceCheck->get_result()->fetch_assoc();
        $invoiceCheck->close();

        $complete = false;
        if ($invoiceRow) {
            $completeSql = "SELECT
                (SELECT COUNT(*) FROM salesheader WHERE documenttype=10 AND externaldocumentno=?) AS invoice_count,
                (SELECT COUNT(*) FROM customerstatement WHERE Documentno=? AND Documenttype=10) AS invoice_statement_count,
                (SELECT COUNT(*) FROM generalledger WHERE DocumentNo=? AND DocumentType=10) AS invoice_gl_count,
                (SELECT COUNT(*) FROM debtorsledger WHERE invref=? AND systypes_1=10) AS invoice_debtor_count,
                (SELECT COUNT(*) FROM receiptheader WHERE docno=?) AS receipt_count,
                (SELECT COUNT(*) FROM banktransactions WHERE DocumentNo=? AND doctype=12
                    AND cleared IS NULL AND reconciled IS NULL) AS bank_count,
                (SELECT COUNT(*) FROM debtorsledger WHERE invref=? AND systypes_1=12) AS debtor_count,
                (SELECT COUNT(*) FROM generalledger WHERE DocumentNo=? AND DocumentType=12) AS gl_count,
                (SELECT COUNT(*) FROM receiptsallocation WHERE receiptno=?) AS allocation_count";
            $completeStmt = $db->prepare($completeSql);
            $completeStmt->bind_param('sssssssss', $docno, $invoiceRow['documentno'], $invoiceRow['documentno'],
                $invoiceRow['documentno'], $docno, $docno, $docno, $docno, $docno);
            $completeStmt->execute();
            $completeRow = $completeStmt->get_result()->fetch_assoc();
            $completeStmt->close();
            $complete = $completeRow &&
                (int)$completeRow['invoice_count'] === 1 &&
                (int)$completeRow['invoice_statement_count'] === 1 &&
                (int)$completeRow['invoice_gl_count'] > 0 &&
                (int)$completeRow['invoice_debtor_count'] > 0 &&
                (int)$completeRow['receipt_count'] === 1 &&
                (int)$completeRow['bank_count'] === 1 &&
                (int)$completeRow['debtor_count'] === 1 &&
                (int)$completeRow['gl_count'] === 1 &&
                (int)$completeRow['allocation_count'] === 1;
        }

        if (!$complete) {
            $db->rollback();
            if ($entryLockAcquired) { $db->query("SELECT RELEASE_LOCK('pos_sales_entryno')"); $entryLockAcquired = false; }
            posSaveResponse(false, 'This POS receipt number already exists, but its accounting postings are incomplete. Do not retry it; it requires a controlled accounting repair.', 409);
        }

        $savedItems = array();
        $savedLineStmt = $db->prepare("SELECT sm.barcode,l.code AS itemcode,l.description,
                l.Quantity AS quantity,l.UnitPrice AS unit_price,l.vatrate AS vat_rate,
                l.vatamount AS vat_amount,l.invoiceamount AS line_total
            FROM salesline l
            LEFT JOIN stockmaster sm ON sm.itemcode=l.code
            WHERE l.documenttype=1 AND l.documentno=?");
        $savedLineStmt->bind_param('s', $docno);
        $savedLineStmt->execute();
        $savedLineResult = $savedLineStmt->get_result();
        while ($savedLine = $savedLineResult->fetch_assoc()) {
            $savedItems[] = array(
                'barcode' => $savedLine['barcode'], 'itemcode' => $savedLine['itemcode'],
                'description' => $savedLine['description'], 'quantity' => (float)$savedLine['quantity'],
                'unitPrice' => (float)$savedLine['unit_price'], 'vatRate' => (float)$savedLine['vat_rate'],
                'vatAmount' => (float)$savedLine['vat_amount'], 'lineTotal' => (float)$savedLine['line_total']
            );
        }
        $savedLineStmt->close();
        $db->rollback();
        if ($entryLockAcquired) { $db->query("SELECT RELEASE_LOCK('pos_sales_entryno')"); $entryLockAcquired = false; }
        posSaveResponse(true, 'This receipt has already been saved and posted.', 200, array(
            'documentno' => $docno, 'already_saved' => true,
            'total' => (float)$existing['total'], 'total_vat' => (float)$existing['total_vat'],
            'items' => $savedItems
        ));
    }

    if ($paymentMethod === 'MPESA') {
        $referenceCheck = $db->prepare("SELECT docno FROM receiptheader WHERE externalref=? LIMIT 1 FOR UPDATE");
        if (!$referenceCheck) { throw new Exception('Could not validate the M-Pesa transaction reference: ' . $db->error); }
        $referenceCheck->bind_param('s', $receiptReference);
        if (!$referenceCheck->execute()) { throw new Exception('Could not validate the M-Pesa transaction reference: ' . $referenceCheck->error); }
        $usedReference = $referenceCheck->get_result()->fetch_assoc();
        $referenceCheck->close();
        if ($usedReference) {
            $db->rollback();
            if ($entryLockAcquired) { $db->query("SELECT RELEASE_LOCK('pos_sales_entryno')"); $entryLockAcquired = false; }
            posSaveResponse(false, 'This M-Pesa transaction code has already been used for another receipt.', 409);
        }
    }

    $entryResult = $db->query("SELECT COALESCE(MAX(entryno),0)+1 AS next_entry FROM salesheader");
    if (!$entryResult) { throw new Exception('Could not determine the next sales entry number: ' . $db->error); }
    $entryRow = $entryResult->fetch_assoc();
    if (!$entryRow || !isset($entryRow['next_entry']) || (int)$entryRow['next_entry'] < 1) {
        throw new Exception('The next sales entry number is invalid.');
    }
    $entryNo = (int)$entryRow['next_entry'];

    // Explicitly pass integer 0/1 for all boolean/flag columns (printed, released, status, vatinclusive, Bybatch)
    // so strict MySQL mode never receives NULL or string 'false'/'true'.
    $header = $db->prepare("INSERT INTO salesheader
        (documenttype,documentno,docdate,customercode,customername,locationcode,postinggroup,currencycode,
         printed,released,status,userid,period,entryno,vatinclusive,discount,Bybatch,total,cash,change_amount,
         pos_shift_id,payment_method,mpesa_phone,total_vat)
        VALUES (1,?,?,?,?,?,?,?,0,0,1,?,?,?,?,0,0,?,?,?,?,?,?,?)");
    if (!$header) { throw new Exception('Could not prepare sales header: ' . $db->error); }
    $customerName = (string)$customerRow['customer'];
    $periodInt = (int)$periodNo;
    $vatIncInt = (int)$vatInclusive;
    $header->bind_param('sssssssiidddissd',
        $docno, $currentDate, $customerCode, $customerName, $storeCode, $postingGroup, $currencyCode,
        $userId, $periodInt, $entryNo, $vatIncInt, $calculatedTotal, $cash, $change,
        $shiftId, $paymentMethod, $mpesaPhone, $calculatedVat);
    if (!$header->execute()) { throw new Exception('Could not save sales header: ' . $header->error); }
    $header->close();

    // 16 placeholders: entryno(i), docdate(s), documentno(s), locationcode(s), code(s),
    // description(s), unitofmeasure(s), Quantity(d), Quantity_toinvoice(d), Qunatity_delivered(d),
    // UnitPrice(d), vatamount(d), invoiceamount(d), vatrate(d), inclusive(i), pos_tax_category(s)
    $lineStmt = $db->prepare("INSERT INTO salesline
        (entryno,documenttype,docdate,documentno,locationcode,code,description,unitofmeasure,
         Quantity,Quantity_toinvoice,Qunatity_delivered,UnitPrice,vatamount,invoiceamount,
         completed,printed,vatrate,inclusive,pos_tax_category)
        VALUES (?,1,?,?,?,?,?,?,?,?,?,?,?,?,1,1,?,?,?)");
    if (!$lineStmt) { throw new Exception('Could not prepare sales lines: ' . $db->error); }
    foreach ($items as $saleLine) {
        $lineEntry = $entryNo;
        $lineLocation = $storeCode;
        $code = $saleLine['itemcode'];
        $description = $saleLine['description'];
        $unit = $saleLine['units'];
        $qty = (float)$saleLine['quantity'];
        $unitPrice = (float)$saleLine['unit_price'];
        $vatAmount = (float)$saleLine['vat_amount'];
        $lineTotal = (float)$saleLine['line_total'];
        $rate = (float)$saleLine['vat_rate'];
        $taxCategory = $saleLine['tax_category'];
        $lineStmt->bind_param('issssssdddddddis',
            $lineEntry, $currentDate, $docno, $lineLocation,
            $code, $description, $unit,
            $qty, $qty, $qty, $unitPrice, $vatAmount, $lineTotal,
            $rate, $vatIncInt, $taxCategory);
        if (!$lineStmt->execute()) { throw new Exception('Could not save a sales line: ' . $lineStmt->error); }
    }
    $lineStmt->close();

    // Post the ERP Sales Invoice (salesheader/salesline doctype=10, generalledger, debtorsledger, customerstatement)
    $_POST['DimensionOne'] = '';
    $_POST['DimensionTwo'] = '';
    $invoicePosting = PostPOSInvoice($docno, $periodNo);
    $invoiceJournal = (string)$invoicePosting['journal'];
    $invoiceNumber = (string)$invoicePosting['documentno'];

    // Deduct sold stock from stockledger and stockregister (FIFO) within the same transaction.
    $pkeyResult = $db->query("SELECT COALESCE(MAX(pkey),0) AS max_pkey FROM stockledger");
    if (!$pkeyResult) { throw new Exception('Could not read stockledger sequence: ' . $db->error); }
    $pkeyRow = $pkeyResult->fetch_assoc();
    $nextPkey = (int)$pkeyRow['max_pkey'];

    $stockLedgerStmt = $db->prepare("INSERT INTO stockledger
        (date, stname, doctyp, itemcode, invref, netamt, vat, amount,
         fulqty, loosqty, price, curr_rat, stockvalue, store, journal, curr_cod, period, factory, jobcard, pkey)
        VALUES (?, ?, '10', ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)");
    if (!$stockLedgerStmt) { throw new Exception('Could not prepare stockledger deduction: ' . $db->error); }

    $fifoSelectStmt = $db->prepare("SELECT rowid, (COALESCE(StockIn,0) - COALESCE(StockOut,0)) AS avail
        FROM stockregister
        WHERE itemcode=? AND (COALESCE(StockIn,0) - COALESCE(StockOut,0)) > 0
        ORDER BY rowid ASC FOR UPDATE");
    $fifoUpdateStmt = $db->prepare("UPDATE stockregister SET StockOut = COALESCE(StockOut,0) + ? WHERE rowid=?");
    $fifoInsertOutStmt = $db->prepare("INSERT INTO stockregister (itemcode, StockIn, cost, StockOut, journal, GRN) VALUES (?, 0, 0, ?, ?, ?)");
    if (!$fifoSelectStmt || !$fifoUpdateStmt || !$fifoInsertOutStmt) {
        throw new Exception('Could not prepare FIFO stockregister deduction: ' . $db->error);
    }

    $currencyRate = (float)$bankRow['rate'];
    foreach ($items as $saleLine) {
        $nextPkey++;
        $stName = substr((string)$saleLine['description'], 0, 30);
        $itemCode = (string)$saleLine['itemcode'];
        $qty = (float)$saleLine['quantity'];
        $negQty = 0.0 - $qty;
        $lineGross = (float)$saleLine['line_total'];
        $lineVat = (float)$saleLine['vat_amount'];
        $lineNet = round($lineGross - $lineVat, 2);
        $unitPrice = (float)$saleLine['unit_price'];

        $stockLedgerStmt->bind_param('ssssdddddddsssisi',
            $currentDate, $stName, $itemCode, $invoiceNumber,
            $lineNet, $lineVat, $lineGross,
            $negQty, $unitPrice, $currencyRate, $lineNet,
            $storeCode, $invoiceJournal, $currencyCode, $periodInt, $docno, $nextPkey);
        if (!$stockLedgerStmt->execute()) {
            throw new Exception('Could not deduct stock from stockledger: ' . $stockLedgerStmt->error);
        }

        // Allocate StockOut across oldest FIFO layers in stockregister
        $remaining = $qty;
        $fifoSelectStmt->bind_param('s', $itemCode);
        if (!$fifoSelectStmt->execute()) {
            throw new Exception('Could not read FIFO layers from stockregister: ' . $fifoSelectStmt->error);
        }
        $fifoRes = $fifoSelectStmt->get_result();
        while ($remaining > 0.0001 && ($layer = $fifoRes->fetch_assoc())) {
            $avail = (float)$layer['avail'];
            if ($avail <= 0) { continue; }
            $take = min($remaining, $avail);
            $layerId = (int)$layer['rowid'];
            $fifoUpdateStmt->bind_param('di', $take, $layerId);
            if (!$fifoUpdateStmt->execute()) {
                throw new Exception('Could not update FIFO stockregister layer: ' . $fifoUpdateStmt->error);
            }
            $remaining = round($remaining - $take, 4);
        }
        if ($remaining > 0.0001) {
            // Record any remaining StockOut if no open GRN layer covered it
            $fifoInsertOutStmt->bind_param('sdss', $itemCode, $remaining, $invoiceJournal, $invoiceNumber);
            if (!$fifoInsertOutStmt->execute()) {
                throw new Exception('Could not record remaining StockOut in stockregister: ' . $fifoInsertOutStmt->error);
            }
        }
    }
    $stockLedgerStmt->close();
    $fifoSelectStmt->close();
    $fifoUpdateStmt->close();
    $fifoInsertOutStmt->close();

    $invoiceStatementStmt = $db->prepare("SELECT COUNT(*) AS row_count,
            COALESCE(SUM(Grossamount),0) AS invoice_amount,
            (SELECT COALESCE(SUM(amount),0) FROM debtorsledger
                WHERE invref=? AND systypes_1=10) AS debtor_amount
        FROM customerstatement
        WHERE JournalNo=? AND Documenttype=10 AND Accountno=?");
    if (!$invoiceStatementStmt) {
        throw new Exception('Could not verify the posted POS invoice customer statement: ' . $db->error);
    }
    $invoiceStatementStmt->bind_param('sss', $invoiceNumber, $invoiceJournal, $customerCode);
    if (!$invoiceStatementStmt->execute()) {
        $invoiceStatementError = $invoiceStatementStmt->error;
        $invoiceStatementStmt->close();
        throw new Exception('Could not verify the posted POS invoice customer statement: ' . $invoiceStatementError);
    }
    $invoiceStatementRow = $invoiceStatementStmt->get_result()->fetch_assoc();
    $invoiceStatementStmt->close();
    if (!$invoiceStatementRow || (int)$invoiceStatementRow['row_count'] !== 1 ||
        abs((float)$invoiceStatementRow['invoice_amount'] - $calculatedTotal) > 0.005 ||
        abs((float)$invoiceStatementRow['debtor_amount'] - $calculatedTotal) > 0.005) {
        throw new Exception('The posted POS invoice customer statement does not equal the POS sale total.');
    }

    $receipt = new SaveReceipt();
    $receipt->bankcode = mysqli_real_escape_string($db, $bankAccountCode);
    $receipt->date = substr($currentDate, 0, 10);
    $receipt->periodNo = $periodNo;
    $receipt->documentno = mysqli_real_escape_string($db, $docno);
    $receipt->reference = mysqli_real_escape_string($db, $receiptReference);
    $receipt->CustomerID = mysqli_real_escape_string($db, $customerCode);
    $receipt->CustomerName = mysqli_real_escape_string($db, $customerName);
    $receipt->currencycode = mysqli_real_escape_string($db, $currencyCode);
    $receipt->Receipt_Journal = GetNextTransNo(0, $db);
    if ($receipt->Receipt_Journal === null || trim((string)$receipt->Receipt_Journal) === '' ||
        !preg_match('/^[A-Za-z0-9._\\\\/-]+$/', (string)$receipt->Receipt_Journal)) {
        throw new Exception('Could not allocate a valid POS receipt journal number.');
    }
    $receipt->GetBankDetails($receipt->bankcode);
    $receipt->GetCustomer($receipt->CustomerID);
    if (empty($receipt->BankArray['accountcode']) || empty($receipt->BankArray['PostingGroup']) ||
        empty($receipt->postinggroups) || trim((string)$receipt->BankArray['currency']) !== trim((string)$currencyCode) ||
        !isset($receipt->BankArray['rate']) || !is_numeric($receipt->BankArray['rate']) ||
        !is_finite((float)$receipt->BankArray['rate']) || (float)$receipt->BankArray['rate'] <= 0) {
        throw new Exception('The POS receipt bank or debtor posting configuration is incomplete.');
    }
    $receipt->GetPaidinvoices($invoicePosting['journal'], $calculatedTotal);
    if (abs((float)$receipt->checkbalance - $calculatedTotal) > 0.005) {
        throw new Exception('The POS receipt allocation does not equal the invoice total.');
    }
    $receipt->PostToAccounts();
    if (count($receipt->JournalArray) < 6) {
        throw new Exception('The POS receipt did not generate all required accounting entries.');
    }
    foreach ($receipt->JournalArray as $index => $sql) {
        $result = DB_query($sql, $db, '', '', false, false);
        if (!$result || $db->errno !== 0 || $db->affected_rows < 1) {
            throw new Exception('A POS receipt accounting posting failed (posting step ' . ($index + 1) . '): ' . $db->error);
        }
    }

    $receiptVerifySql = "SELECT
        (SELECT COUNT(*) FROM receiptheader WHERE docno=?) AS receipt_count,
        (SELECT COUNT(*) FROM banktransactions bt
            INNER JOIN bankaccounts ba ON ba.accountcode=bt.bankcode
            WHERE bt.DocumentNo=? AND bt.doctype=12
                AND COALESCE(bt.cleared,0)=0 AND COALESCE(bt.reconciled,0)=0
                AND bt.exchangerate IS NOT NULL AND bt.exchangerate>0) AS bank_count,
        (SELECT COALESCE(SUM(amount),0) FROM banktransactions
            WHERE DocumentNo=? AND doctype=12) AS bank_amount,
        (SELECT COALESCE(SUM(amount),0) FROM receiptheader WHERE docno=?) AS receipt_amount,
        (SELECT COUNT(*) FROM debtorsledger WHERE invref=? AND systypes_1=12) AS debtor_count,
        (SELECT COALESCE(SUM(amount),0) FROM debtorsledger WHERE invref=? AND systypes_1=12) AS debtor_amount,
        (SELECT COUNT(*) FROM generalledger WHERE DocumentNo=? AND DocumentType=12) AS gl_count,
        (SELECT COALESCE(SUM(amount),0) FROM generalledger WHERE DocumentNo=? AND DocumentType=12) AS gl_amount,
        (SELECT COUNT(*) FROM receiptsallocation WHERE receiptno=?) AS allocation_count,
        (SELECT COALESCE(SUM(amount),0) FROM receiptsallocation WHERE receiptno=?) AS allocation_amount,
        (SELECT COUNT(*) FROM customerstatement WHERE Documentno=? AND Documenttype=12) AS statement_count,
        (SELECT COALESCE(SUM(Grossamount),0) FROM customerstatement WHERE Documentno=? AND Documenttype=12) AS statement_amount";
    $receiptVerifyStmt = $db->prepare($receiptVerifySql);
    if (!$receiptVerifyStmt) {
        throw new Exception('Could not prepare the POS receipt posting verification: ' . $db->error);
    }
    $receiptVerifyStmt->bind_param('ssssssssssss',
        $docno,$docno,$docno,$docno,$docno,$docno,$docno,$docno,$docno,$docno,$docno,$docno);
    if (!$receiptVerifyStmt->execute()) {
        $receiptVerifyError = $receiptVerifyStmt->error;
        $receiptVerifyStmt->close();
        throw new Exception('Could not verify the POS receipt postings: ' . $receiptVerifyError);
    }
    $receiptVerify = $receiptVerifyStmt->get_result()->fetch_assoc();
    $receiptVerifyStmt->close();
    if (!$receiptVerify ||
        (int)$receiptVerify['receipt_count'] !== 1 ||
        (int)$receiptVerify['bank_count'] !== 1 ||
        abs((float)$receiptVerify['bank_amount'] - $calculatedTotal) > 0.005 ||
        abs((float)$receiptVerify['receipt_amount'] - $calculatedTotal) > 0.005 ||
        (int)$receiptVerify['debtor_count'] !== 1 ||
        abs((float)$receiptVerify['debtor_amount'] + $calculatedTotal) > 0.005 ||
        (int)$receiptVerify['gl_count'] !== 1 ||
        abs((float)$receiptVerify['gl_amount'] - $calculatedTotal) > 0.005 ||
        (int)$receiptVerify['allocation_count'] !== 1 ||
        abs((float)$receiptVerify['allocation_amount'] + $calculatedTotal) > 0.005 ||
        (int)$receiptVerify['statement_count'] !== 1 ||
        abs((float)$receiptVerify['statement_amount'] + $calculatedTotal) > 0.005) {
        throw new Exception('The POS receipt, bank, debtor, general ledger or allocation postings failed their amount and record-count verification.');
    }

    $printItems = array();
    foreach ($items as $saleLine) {
        $printItems[] = array(
            'barcode' => $saleLine['barcode'], 'itemcode' => $saleLine['itemcode'],
            'description' => $saleLine['description'], 'quantity' => $saleLine['quantity'],
            'unitPrice' => $saleLine['unit_price'], 'vatRate' => $saleLine['vat_rate'],
            'vatAmount' => $saleLine['vat_amount'], 'lineTotal' => $saleLine['line_total']
        );
    }
    if (!$db->commit()) {
        throw new Exception('Could not commit the POS sale: ' . $db->error);
    }
    if ($entryLockAcquired) {
        $db->query("SELECT RELEASE_LOCK('pos_sales_entryno')");
        $entryLockAcquired = false;
    }

    // Printing is a separate delivery task. Never roll back a committed sale
    // because the print queue or local printer is unavailable.
    $printPayload = array(
        'company' => isset($_SESSION['CompanyRecord']['coyname']) ? $_SESSION['CompanyRecord']['coyname'] : '',
        'userlog' => isset($_SESSION['UsersRealName']) ? $_SESSION['UsersRealName'] : '',
        'docno' => $docno,
        'items' => $printItems,
        'totalVAT' => $calculatedVat,
        'total' => $calculatedTotal,
        'cash' => $cash,
        'change' => $change,
        'currency' => 'KES',
        'paymentMethod' => $paymentMethod,
        'mpesaPhone' => $mpesaPhone,
        'printedAt' => date('c'),
        'printerId' => isset($receiptData['printerId']) ? trim((string)$receiptData['printerId']) : ''
    );
    $printQueued = false;
    $printQueueStmt = $db->prepare("INSERT INTO pos_print_jobs (document_no, payload, status, created_at) VALUES (?, ?, 'queued', NOW())");
    if ($printQueueStmt) {
        $printPayloadJson = json_encode($printPayload);
        $printQueueStmt->bind_param('ss', $docno, $printPayloadJson);
        $printQueued = $printQueueStmt->execute();
        if (!$printQueued) {
            error_log('POS receipt saved but print queue insert failed: ' . $printQueueStmt->error);
        }
        $printQueueStmt->close();
    } else {
        error_log('POS receipt saved but print queue is unavailable. Apply SQL/pos_print_queue.sql: ' . $db->error);
    }

    posSaveResponse(true, 'Receipt saved successfully.', 200, array(
        'documentno' => $docno, 'total' => $calculatedTotal, 'total_vat' => $calculatedVat,
        'items' => $printItems, 'print_queued' => $printQueued
    ));
} catch (Throwable $e) {
    $db->rollback();
    if ($entryLockAcquired) {
        $db->query("SELECT RELEASE_LOCK('pos_sales_entryno')");
    }
    error_log('POS receipt save failed: ' . $e->getMessage());
    posSaveResponse(false, 'The receipt could not be saved: ' . $e->getMessage(), 500);
}
?>
