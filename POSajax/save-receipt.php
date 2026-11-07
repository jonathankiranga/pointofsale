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
include_once('../Ajax/DatafunctionsForAjax.inc');
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
$appointmentId = isset($receiptData['appointmentId']) ? (int)$receiptData['appointmentId'] : 0;
if ($appointmentId < 0) { posSaveResponse(false, 'Invalid appointment reference.', 400); }
$appointmentContext = null;
if ($appointmentId > 0) {
    $appointmentContextStmt = $db->prepare("SELECT a.appointment_id,a.appointment_status,a.customer_name,l.service_id,l.hairdresser_id,l.agreed_price,
            s.pos_itemcode,s.pos_barcode
        FROM salon_appointments a
        INNER JOIN salon_appointment_lines l ON l.appointment_id=a.appointment_id
        INNER JOIN salon_services s ON s.service_id=l.service_id
        WHERE a.appointment_id=?");
    if (!$appointmentContextStmt) { posSaveResponse(false, 'Could not validate the linked appointment.', 500); }
    $appointmentContextStmt->bind_param('i', $appointmentId);
    if (!$appointmentContextStmt->execute()) {
        $appointmentContextStmt->close();
        posSaveResponse(false, 'Could not validate the linked appointment.', 500);
    }
    $appointmentContextResult = $appointmentContextStmt->get_result();
    $appointmentContextRows = array();
    while ($appointmentContextRow = $appointmentContextResult->fetch_assoc()) { $appointmentContextRows[] = $appointmentContextRow; }
    $appointmentContextStmt->close();
    if (count($appointmentContextRows) !== 1 || !in_array($appointmentContextRows[0]['appointment_status'], array('in_service','completed'), true)) {
        posSaveResponse(false, 'The linked appointment is not in service or does not contain exactly one booked service.', 409);
    }
    $appointmentContext = $appointmentContextRows[0];
}
$paymentMethod = isset($receiptData['paymentMethod']) ? strtoupper(trim((string)$receiptData['paymentMethod'])) : '';
if (!in_array($paymentMethod, array('CASH', 'MPESA'), true)) {
    posSaveResponse(false, 'A valid POS payment method is required.', 400);
}
$mpesaPhone = isset($receiptData['mpesaPhone']) ? trim((string)$receiptData['mpesaPhone']) : '';
$mpesaReference = isset($receiptData['mpesaReference']) ? strtoupper(trim((string)$receiptData['mpesaReference'])) : '';
if ($paymentMethod === 'MPESA') {
    $cash = 0;
    $change = 0;
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
    ('POS_DefaultCustomer','POS_DefaultStore','POS_DefaultCurrency','POS_DefaultPostingGroup','POS_CashBankAccount','POS_MPesaBankAccount')");
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

$customerStmt = $db->prepare("SELECT d.customer,d.customerposting,p.debtorsaccount
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
    posSaveResponse(false, 'The configured POS customer has no valid debtor posting group/account. Correct the debtor master before selling.', 409);
}
if (trim((string)$customerRow['customerposting']) !== trim((string)$postingGroup)) {
    posSaveResponse(false, 'The POS default posting group must match the configured POS customer posting group.', 409);
}
$groupStmt = $db->prepare("SELECT VATinclusive FROM postinggroups WHERE code=? LIMIT 1");
if (!$groupStmt) { posSaveResponse(false, 'Could not prepare the POS posting group validation query.', 500); }
$groupStmt->bind_param('s', $postingGroup);
if (!$groupStmt->execute()) {
    $groupError = $groupStmt->error;
    $groupStmt->close();
    posSaveResponse(false, 'Could not validate the POS posting group: ' . $groupError, 500);
}
$groupRow = $groupStmt->get_result()->fetch_assoc();
$groupStmt->close();
if (!$groupRow || $groupRow['VATinclusive'] === null ||
    !in_array((string)$groupRow['VATinclusive'], array('0','1'), true)) {
    posSaveResponse(false, 'The configured POS posting group has a NULL or invalid VAT-inclusive setting.', 409);
}
$vatInclusive = (int)$groupRow['VATinclusive'];

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
if (!$bankStmt) { posSaveResponse(false, 'Could not validate the POS payment bank account.', 500); }
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
$appointmentServiceLineCount = 0;
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
$salonServiceStmt = null;
$salonAssignmentStmt = null;
$salonMappedItemStmt = null;
$salonMappingColumns = $db->query("SELECT COUNT(*) AS column_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='salon_services'
      AND COLUMN_NAME IN ('pos_itemcode','pos_barcode')");
$salonMappingColumnRow = $salonMappingColumns ? $salonMappingColumns->fetch_assoc() : null;
if ($salonMappingColumnRow && (int)$salonMappingColumnRow['column_count'] === 2) {
    $salonMappedItemStmt = $db->prepare("SELECT service_id FROM salon_services
        WHERE pos_itemcode=? AND pos_barcode=? LIMIT 1");
    if (!$salonMappedItemStmt) {
        $productStmt->close();
        posSaveResponse(false, 'Could not validate salon service item mappings.', 500);
    }
}

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
    $salonServiceId = isset($item['salonServiceId']) ? (int)$item['salonServiceId'] : 0;
    $hairdresserId = isset($item['hairdresserId']) ? (int)$item['hairdresserId'] : 0;
    if ($salonMappedItemStmt) {
        $salonMappedItemStmt->bind_param('ss', $itemcode, $barcode);
        if (!$salonMappedItemStmt->execute()) {
            $productStmt->close();
            $salonMappedItemStmt->close();
            posSaveResponse(false, 'Could not verify whether this item is a salon service.', 500);
        }
        $mappedSalonService = $salonMappedItemStmt->get_result()->fetch_assoc();
        if ($mappedSalonService && ((int)$mappedSalonService['service_id'] !== $salonServiceId || $hairdresserId < 1)) {
            $productStmt->close();
            $salonMappedItemStmt->close();
            posSaveResponse(false, 'This item is configured as a salon service. Add it through the Salon POS and assign a hairdresser.', 409);
        }
    }
    $unitPrice = (float)$product['sellingprice'];
    $saleDescription = $product['descrip'];
    if ($salonServiceId > 0 || $hairdresserId > 0) {
        if ($salonServiceId < 1 || $hairdresserId < 1) {
            $productStmt->close();
            if ($salonServiceStmt) { $salonServiceStmt->close(); }
            if ($salonAssignmentStmt) { $salonAssignmentStmt->close(); }
            posSaveResponse(false, 'Every salon service line must have an assigned active hairdresser.', 400);
        }
        if (!$salonServiceStmt) {
            $salonServiceStmt = $db->prepare("SELECT service_id,service_name,standard_price,pos_itemcode,pos_barcode
                FROM salon_services WHERE service_id=? AND is_active=1 LIMIT 1");
            $salonAssignmentStmt = $db->prepare("SELECT COUNT(*) AS assigned
                FROM salon_hairdresser_services hs
                INNER JOIN salon_hairdressers h ON h.hairdresser_id=hs.hairdresser_id
                WHERE hs.service_id=? AND hs.hairdresser_id=? AND hs.is_active=1 AND h.is_active=1");
            if (!$salonServiceStmt || !$salonAssignmentStmt) {
                $productStmt->close();
                posSaveResponse(false, 'Salon POS setup is incomplete. Apply the salon operations and item mapping migrations.', 409);
            }
        }
        $salonServiceStmt->bind_param('i', $salonServiceId);
        if (!$salonServiceStmt->execute()) {
            $productStmt->close();
            $salonServiceStmt->close();
            $salonAssignmentStmt->close();
            posSaveResponse(false, 'Could not validate the salon service.', 500);
        }
        $salonService = $salonServiceStmt->get_result()->fetch_assoc();
        if (!$salonService || trim((string)$salonService['pos_itemcode']) !== $itemcode ||
            trim((string)$salonService['pos_barcode']) !== $barcode ||
            !is_numeric($salonService['standard_price']) || (float)$salonService['standard_price'] < 0) {
            $productStmt->close();
            $salonServiceStmt->close();
            $salonAssignmentStmt->close();
            posSaveResponse(false, 'The salon service is no longer mapped to this ERP POS item. Refresh the salon terminal.', 409);
        }
        $salonAssignmentStmt->bind_param('ii', $salonServiceId, $hairdresserId);
        if (!$salonAssignmentStmt->execute()) {
            $productStmt->close();
            $salonServiceStmt->close();
            $salonAssignmentStmt->close();
            posSaveResponse(false, 'Could not validate the hairdresser assignment.', 500);
        }
        $assignment = $salonAssignmentStmt->get_result()->fetch_assoc();
        if (!$assignment || (int)$assignment['assigned'] !== 1) {
            $productStmt->close();
            $salonServiceStmt->close();
            $salonAssignmentStmt->close();
            posSaveResponse(false, 'The selected hairdresser is not actively assigned to this service.', 409);
        }
        $unitPrice = (float)$salonService['standard_price'];
        if ($appointmentContext && $salonServiceId === (int)$appointmentContext['service_id']) {
            if ($hairdresserId !== (int)$appointmentContext['hairdresser_id'] ||
                $itemcode !== trim((string)$appointmentContext['pos_itemcode']) ||
                $barcode !== trim((string)$appointmentContext['pos_barcode'])) {
                $productStmt->close();
                $salonServiceStmt->close();
                $salonAssignmentStmt->close();
                posSaveResponse(false, 'The booked appointment service and hairdresser must remain unchanged in its POS transaction.', 409);
            }
            $unitPrice = (float)$appointmentContext['agreed_price'];
            $appointmentServiceLineCount++;
        }
        $saleDescription = mb_substr((string)$salonService['service_name'], 0, 100, 'UTF-8');
    }
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
        'description' => $saleDescription,
        'units' => $product['units'],
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'vat_rate' => $vatRate,
        'vat_amount' => $vatAmount,
        'line_total' => $lineTotal,
        'tax_category' => $product['tax_category'],
        'salon_service_id' => $salonServiceId,
        'hairdresser_id' => $hairdresserId
    );
    $calculatedTotal += $lineTotal;
    $calculatedVat += $vatAmount;
}
if ($appointmentContext && $appointmentServiceLineCount !== 1) {
    $productStmt->close();
    if ($salonServiceStmt) { $salonServiceStmt->close(); }
    if ($salonAssignmentStmt) { $salonAssignmentStmt->close(); }
    if ($salonMappedItemStmt) { $salonMappedItemStmt->close(); }
    posSaveResponse(false, 'The linked appointment service must appear exactly once in the checkout basket.', 409);
}
$productStmt->close();
if ($salonServiceStmt) { $salonServiceStmt->close(); }
if ($salonAssignmentStmt) { $salonAssignmentStmt->close(); }
if ($salonMappedItemStmt) { $salonMappedItemStmt->close(); }
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

    if ($appointmentId > 0) {
        $appointmentLock = $db->prepare("SELECT appointment_status FROM salon_appointments WHERE appointment_id=? FOR UPDATE");
        if (!$appointmentLock) { throw new Exception('Could not lock the linked salon appointment: '.$db->error); }
        $appointmentLock->bind_param('i', $appointmentId);
        if (!$appointmentLock->execute()) { throw new Exception('Could not lock the linked salon appointment: '.$appointmentLock->error); }
        $lockedAppointment = $appointmentLock->get_result()->fetch_assoc();
        $appointmentLock->close();
        if (!$lockedAppointment || !in_array($lockedAppointment['appointment_status'], array('in_service','completed'), true)) {
            throw new Exception('The linked appointment is not available for checkout.');
        }
        $appointmentLinkCheck = $db->prepare("SELECT document_no FROM salon_appointment_transactions WHERE appointment_id=? LIMIT 1");
        if (!$appointmentLinkCheck) { throw new Exception('Could not check the appointment checkout lock: '.$db->error); }
        $appointmentLinkCheck->bind_param('i', $appointmentId);
        if (!$appointmentLinkCheck->execute()) { throw new Exception('Could not check the appointment checkout lock: '.$appointmentLinkCheck->error); }
        $appointmentLink = $appointmentLinkCheck->get_result()->fetch_assoc();
        $appointmentLinkCheck->close();
        if ($appointmentLink && $appointmentLink['document_no'] !== $docno) {
            throw new Exception('This appointment has already been checked out on POS receipt '.$appointmentLink['document_no'].'.');
        }
        if ($lockedAppointment['appointment_status'] === 'completed' && !$appointmentLink) {
            throw new Exception('The appointment is completed but has no matching POS receipt link. Contact an administrator.');
        }
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
    $check = $db->prepare("SELECT documentno,total,total_vat FROM SalesHeader
        WHERE documenttype=1 AND documentno=? LIMIT 1 FOR UPDATE");
    if (!$check) { throw new Exception('Could not check for an existing POS receipt: ' . $db->error); }
    $check->bind_param('s', $docno);
    if (!$check->execute()) { throw new Exception('Could not check for an existing POS receipt: ' . $check->error); }
    $existing = $check->get_result()->fetch_assoc();
    $check->close();
    if ($existing) {
        if ($appointmentId > 0) {
            $existingAppointment = $db->prepare("SELECT document_no FROM salon_appointment_transactions WHERE appointment_id=? AND document_no=? LIMIT 1");
            if (!$existingAppointment) { throw new Exception('Could not verify the existing appointment receipt link: '.$db->error); }
            $existingAppointment->bind_param('is', $appointmentId, $docno);
            if (!$existingAppointment->execute()) { throw new Exception('Could not verify the existing appointment receipt link: '.$existingAppointment->error); }
            $existingAppointmentRow = $existingAppointment->get_result()->fetch_assoc();
            $existingAppointment->close();
            if (!$existingAppointmentRow) {
                throw new Exception('This receipt number already belongs to another transaction and cannot be linked to this appointment.');
            }
        }
        $invoiceCheck = $db->prepare("SELECT documentno FROM SalesHeader
            WHERE documenttype=10 AND externaldocumentno=? LIMIT 1");
        $invoiceCheck->bind_param('s', $docno);
        $invoiceCheck->execute();
        $invoiceRow = $invoiceCheck->get_result()->fetch_assoc();
        $invoiceCheck->close();

        $complete = false;
        if ($invoiceRow) {
            $completeSql = "SELECT
                (SELECT COUNT(*) FROM SalesHeader WHERE documenttype=10 AND externaldocumentno=?) AS invoice_count,
                (SELECT COUNT(*) FROM CustomerStatement WHERE Documentno=? AND Documenttype=10) AS invoice_statement_count,
                (SELECT COUNT(*) FROM GeneralLedger WHERE DocumentNo=? AND DocumentType=10) AS invoice_gl_count,
                (SELECT COUNT(*) FROM debtorsledger WHERE invref=? AND systypes_1=10) AS invoice_debtor_count,
                (SELECT COUNT(*) FROM receiptheader WHERE docno=?) AS receipt_count,
                (SELECT COUNT(*) FROM BankTransactions WHERE DocumentNo=? AND doctype=12
                    AND cleared IS NULL AND reconciled IS NULL) AS bank_count,
                (SELECT COUNT(*) FROM debtorsledger WHERE invref=? AND systypes_1=12) AS debtor_count,
                (SELECT COUNT(*) FROM GeneralLedger WHERE DocumentNo=? AND DocumentType=12) AS gl_count,
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
            FROM SalesLine l
            LEFT JOIN stockmaster sm ON sm.code=l.code
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

    if (!$existing && $appointmentId > 0 && $appointmentLink) {
        throw new Exception('The appointment receipt link exists, but its POS receipt is missing. Contact an administrator before retrying checkout.');
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

    $entryResult = $db->query("SELECT COALESCE(MAX(entryno),0)+1 AS next_entry FROM SalesHeader");
    if (!$entryResult) { throw new Exception('Could not determine the next sales entry number: ' . $db->error); }
    $entryRow = $entryResult->fetch_assoc();
    if (!$entryRow || !isset($entryRow['next_entry']) || (int)$entryRow['next_entry'] < 1) {
        throw new Exception('The next sales entry number is invalid.');
    }
    $entryNo = (int)$entryRow['next_entry'];

    $header = $db->prepare("INSERT INTO SalesHeader
        (documenttype,documentno,docdate,customercode,customername,locationcode,postinggroup,currencycode,
         status,userid,period,entryno,vatinclusive,total,cash,change_amount,pos_shift_id,payment_method,mpesa_phone,total_vat)
        VALUES (1,?,?,?,?,?,?,?,1,?,?,?,?,?,?,?,?,?,?,?)");
    if (!$header) { throw new Exception('Could not prepare sales header: ' . $db->error); }
    $customerName = $appointmentContext ? (string)$appointmentContext['customer_name'] : (string)$customerRow['customer'];
    $userName = isset($_SESSION['UsersRealName']) ? (string)$_SESSION['UsersRealName'] : $userId;
    $periodString = (int)$periodNo;
    $header->bind_param('ssssssssiiidddissd',
        $docno, $currentDate, $customerCode, $customerName, $storeCode, $postingGroup, $currencyCode,
        $userId, $periodString, $entryNo, $vatInclusive, $calculatedTotal, $cash, $change,
        $shiftId, $paymentMethod, $mpesaPhone, $calculatedVat);
    if (!$header->execute()) { throw new Exception('Could not save sales header: ' . $header->error); }
    $header->close();

    $lineStmt = $db->prepare("INSERT INTO SalesLine
        (entryno,documenttype,docdate,documentno,locationcode,code,description,unitofmeasure,
         Quantity,Quantity_toinvoice,Qunatity_delivered,UnitPrice,vatamount,invoiceamount,
         completed,printed,vatrate,inclusive,pos_tax_category)
        VALUES (?,1,?,?,?,?,?,?,?,?,?,?,?,?,1,1,?,?,?)");
    if (!$lineStmt) { throw new Exception('Could not prepare sales lines: ' . $db->error); }
    $saleLineSequence = 0;
    $commissionRuleStmt = null;
    $commissionInsertStmt = null;
    foreach ($items as $saleLine) {
        $saleLineSequence++;
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

        if (!empty($saleLine['salon_service_id']) && !empty($saleLine['hairdresser_id'])) {
            $serviceId = (int)$saleLine['salon_service_id'];
            $hairdresserId = (int)$saleLine['hairdresser_id'];
            if (!$commissionRuleStmt) {
                $commissionRuleStmt = $db->prepare("SELECT commission_type,commission_value
                    FROM salon_commission_rules
                    WHERE is_active=1 AND effective_from<=CURDATE()
                      AND (effective_to IS NULL OR effective_to>=CURDATE())
                      AND (hairdresser_id=? OR hairdresser_id IS NULL)
                      AND (service_id=? OR service_id IS NULL)
                    ORDER BY ((hairdresser_id IS NOT NULL) + (service_id IS NOT NULL)) DESC,
                             effective_from DESC,commission_rule_id DESC
                    LIMIT 1");
                $commissionInsertStmt = $db->prepare("INSERT INTO salon_service_commissions
                    (source_document_type,source_document_no,source_line_entry_no,service_id,hairdresser_id,
                     quantity,eligible_service_amount,commission_type,commission_value,commission_amount,commission_status)
                    VALUES (1,?,?,?,?,?,?,?,?,?,'pending')");
                if (!$commissionRuleStmt || !$commissionInsertStmt) {
                    throw new Exception('Could not prepare salon commission calculation. Apply the salon foundation migration.');
                }
            }
            $eligibleAmount = round(max(0, (float)$saleLine['line_total'] - (float)$saleLine['vat_amount']), 2);
            $quantity = (float)$saleLine['quantity'];
            $commissionRuleStmt->bind_param('ii', $hairdresserId, $serviceId);
            if (!$commissionRuleStmt->execute()) {
                throw new Exception('Could not resolve the salon commission rule: ' . $commissionRuleStmt->error);
            }
            $commissionRule = $commissionRuleStmt->get_result()->fetch_assoc();
            if (!$commissionRule) {
                throw new Exception('No active commission rule applies to ' . $saleLine['description'] . ' for the selected hairdresser. Configure a rule before completing this service sale.');
            }
            if ($commissionRule) {
                $commissionType = $commissionRule['commission_type'];
                $commissionValue = (float)$commissionRule['commission_value'];
                $commissionAmount = $commissionType === 'percentage'
                    ? round($eligibleAmount * $commissionValue / 100, 2)
                    : round($commissionValue * $quantity, 2);
                $commissionLineSequence = $saleLineSequence;
                $commissionInsertStmt->bind_param('siiiddsdd', $docno, $commissionLineSequence,
                    $serviceId, $hairdresserId, $quantity, $eligibleAmount, $commissionType,
                    $commissionValue, $commissionAmount);
                if (!$commissionInsertStmt->execute()) {
                    throw new Exception('Could not record salon commission: ' . $commissionInsertStmt->error);
                }
            }
        }
    }
    if ($commissionRuleStmt) { $commissionRuleStmt->close(); }
    if ($commissionInsertStmt) { $commissionInsertStmt->close(); }
    $lineStmt->close();

    // The sales order, sales invoice, receipt, allocation, bank transaction
    // and all ledger entries must use this same MySQL connection/transaction.
    $_POST['DimensionOne'] = '';
    $_POST['DimensionTwo'] = '';
    $invoicePosting = PostPOSInvoice($docno, $periodNo);

    $invoiceStatementStmt = $db->prepare("SELECT COUNT(*) AS row_count,
            COALESCE(SUM(Grossamount),0) AS invoice_amount,
            (SELECT COALESCE(SUM(amount),0) FROM debtorsledger
                WHERE invref=? AND systypes_1=10) AS debtor_amount
        FROM CustomerStatement
        WHERE JournalNo=? AND Documenttype=10 AND Accountno=?");
    if (!$invoiceStatementStmt) {
        throw new Exception('Could not verify the posted POS invoice customer statement: ' . $db->error);
    }
    $invoiceJournal = (string)$invoicePosting['journal'];
    $invoiceNumber = (string)$invoicePosting['documentno'];
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
        !preg_match('/^[A-Za-z0-9._\\/-]+$/', (string)$receipt->Receipt_Journal)) {
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
        (SELECT COUNT(*) FROM BankTransactions bt
            INNER JOIN BankAccounts ba ON ba.accountcode=bt.bankcode
            WHERE bt.DocumentNo=? AND bt.doctype=12
              AND bt.cleared IS NULL AND bt.reconciled IS NULL
              AND bt.exchangerate IS NOT NULL AND bt.exchangerate>0) AS bank_count,
        (SELECT COALESCE(SUM(amount),0) FROM BankTransactions
            WHERE DocumentNo=? AND doctype=12) AS bank_amount,
        (SELECT COALESCE(SUM(amount),0) FROM receiptheader WHERE docno=?) AS receipt_amount,
        (SELECT COUNT(*) FROM debtorsledger WHERE invref=? AND systypes_1=12) AS debtor_count,
        (SELECT COALESCE(SUM(amount),0) FROM debtorsledger WHERE invref=? AND systypes_1=12) AS debtor_amount,
        (SELECT COUNT(*) FROM GeneralLedger WHERE DocumentNo=? AND DocumentType=12) AS gl_count,
        (SELECT COALESCE(SUM(amount),0) FROM GeneralLedger WHERE DocumentNo=? AND DocumentType=12) AS gl_amount,
        (SELECT COUNT(*) FROM receiptsallocation WHERE receiptno=?) AS allocation_count,
        (SELECT COALESCE(SUM(amount),0) FROM receiptsallocation WHERE receiptno=?) AS allocation_amount,
        (SELECT COUNT(*) FROM CustomerStatement WHERE Documentno=? AND Documenttype=12) AS statement_count,
        (SELECT COALESCE(SUM(Grossamount),0) FROM CustomerStatement WHERE Documentno=? AND Documenttype=12) AS statement_amount";
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
    if ($appointmentId > 0 && !$appointmentLink) {
        $appointmentLinkInsert = $db->prepare("INSERT INTO salon_appointment_transactions
            (appointment_id,document_type,document_no,sales_entry_no) VALUES (?,1,?,?)");
        if (!$appointmentLinkInsert) { throw new Exception('Could not prepare the appointment receipt link: '.$db->error); }
        $appointmentLinkInsert->bind_param('isi', $appointmentId, $docno, $entryNo);
        if (!$appointmentLinkInsert->execute()) {
            $linkError = $appointmentLinkInsert->error;
            $appointmentLinkInsert->close();
            throw new Exception('Could not link the POS receipt to the appointment: '.$linkError);
        }
        $appointmentLinkInsert->close();
        $appointmentComplete = $db->prepare("UPDATE salon_appointments SET appointment_status='completed' WHERE appointment_id=? AND appointment_status='in_service'");
        if (!$appointmentComplete) { throw new Exception('Could not prepare appointment completion: '.$db->error); }
        $appointmentComplete->bind_param('i', $appointmentId);
        if (!$appointmentComplete->execute() || $appointmentComplete->affected_rows !== 1) {
            $appointmentComplete->close();
            throw new Exception('The appointment could not be marked completed. The POS transaction was rolled back.');
        }
        $appointmentComplete->close();
        $appointmentLineComplete = $db->prepare("UPDATE salon_appointment_lines SET line_status='completed' WHERE appointment_id=? AND line_status='in_service'");
        if (!$appointmentLineComplete) { throw new Exception('Could not prepare appointment service completion: '.$db->error); }
        $appointmentLineComplete->bind_param('i', $appointmentId);
        if (!$appointmentLineComplete->execute() || $appointmentLineComplete->affected_rows < 1) {
            $appointmentLineComplete->close();
            throw new Exception('The appointment service line could not be marked completed. The POS transaction was rolled back.');
        }
        $appointmentLineComplete->close();
    }
    if (!$db->commit()) {
        throw new Exception('Could not commit the POS sale: ' . $db->error);
    }
    if ($entryLockAcquired) {
        $db->query("SELECT RELEASE_LOCK('pos_sales_entryno')");
        $entryLockAcquired = false;
    }
    posSaveResponse(true, 'Receipt saved successfully.', 200, array(
        'documentno' => $docno, 'total' => $calculatedTotal, 'total_vat' => $calculatedVat,
        'appointment_id' => $appointmentId, 'items' => $printItems
    ));
} catch (Throwable $e) {
    $db->rollback();
    if ($entryLockAcquired) {
        $db->query("SELECT RELEASE_LOCK('pos_sales_entryno')");
    }
    error_log('POS receipt save failed: ' . $e->getMessage());
    posSaveResponse(false, 'The receipt could not be saved. Check the server error log.', 500);
}
?>
