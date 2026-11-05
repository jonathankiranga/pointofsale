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

/* Reuse the POS application session, database connection and user context. */
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

$docno = trim((string)$receiptData['docno']);
$total = isset($receiptData['total']) ? (float)$receiptData['total'] : 0;
$cash = isset($receiptData['cash']) ? (float)$receiptData['cash'] : $total;
$change = isset($receiptData['change']) ? (float)$receiptData['change'] : 0;

if ($total < 0 || $cash < 0 || $change < 0 || $cash + 0.00001 < $total) {
    posSaveResponse(false, 'Invalid payment amounts or insufficient cash.', 400);
}

$items = array();
$calculatedTotal = 0;
foreach ($receiptData['items'] as $item) {
    $barcode = isset($item['barcode']) ? trim((string)$item['barcode']) : '';
    $description = isset($item['description']) ? trim((string)$item['description']) : '';
    $quantity = isset($item['quantity']) ? (float)$item['quantity'] : 0;
    $unitPrice = isset($item['unitPrice']) ? (float)$item['unitPrice'] : 0;
    $vatAmount = isset($item['vatAmount']) ? (float)$item['vatAmount'] : 0;
    $lineTotal = isset($item['lineTotal']) ? (float)$item['lineTotal'] : 0;

    if ($barcode === '' || $description === '' || $quantity <= 0 || $unitPrice < 0 || $lineTotal < 0 || $vatAmount < 0) {
        posSaveResponse(false, 'A sale line contains invalid product, quantity or price data.', 400);
    }

    $items[] = array($barcode, $description, $quantity, $unitPrice, $vatAmount, $lineTotal);
    $calculatedTotal += $lineTotal;
}

$calculatedTotal = round($calculatedTotal, 2);
if (abs($calculatedTotal - round($total, 2)) > 0.02) {
    posSaveResponse(false, 'The receipt total does not match the sum of its sale lines. Refresh and recalculate the basket.', 400);
}

try {
    $mysqli->begin_transaction();

    /* Prevent a browser retry from creating the same receipt twice. */
    $check = $mysqli->prepare("SELECT documentno FROM SalesHeader WHERE documenttype='1' AND documentno=? LIMIT 1");
    if (!$check) {
        throw new Exception('Could not prepare duplicate receipt check: ' . $mysqli->error);
    }
    $check->bind_param('s', $docno);
    $check->execute();
    $existing = $check->get_result();
    if ($existing && $existing->num_rows > 0) {
        $check->close();
        $mysqli->rollback();
        posSaveResponse(true, 'This receipt has already been saved.', 200, array('documentno' => $docno, 'already_saved' => true));
    }
    $check->close();

    $dateResult = $mysqli->query("SELECT SYSDATE() AS date");
    if (!$dateResult) {
        throw new Exception('Could not retrieve the transaction date: ' . $mysqli->error);
    }
    $dateRow = $dateResult->fetch_assoc();
    $currentDate = $dateRow['date'];
    $_POST['date'] = ConvertSQLDate($currentDate);
    $periodno = GetPeriod($_POST['date'], $db, true);
    if (!$periodno) {
        throw new Exception('The current financial period could not be determined.');
    }

    $header = $mysqli->prepare("INSERT INTO SalesHeader
        (documenttype, documentno, docdate, status, userid, period, total, cash, change_amount)
        VALUES ('1', ?, ?, 1, ?, ?, ?, ?, ?)");
    if (!$header) {
        throw new Exception('Could not prepare receipt header: ' . $mysqli->error);
    }
    $userId = (string)$_SESSION['UserID'];
    $periodString = (string)$periodno;
    $header->bind_param('ssssddd', $docno, $currentDate, $userId, $periodString, $total, $cash, $change);
    if (!$header->execute()) {
        throw new Exception('Could not save receipt header: ' . $header->error);
    }
    $header->close();

    $line = $mysqli->prepare("INSERT INTO SalesLine
        (documenttype, documentno, barcode, description, Quantity, UnitPrice, vatamount, invoiceamount)
        VALUES ('1', ?, ?, ?, ?, ?, ?, ?)");
    if (!$line) {
        throw new Exception('Could not prepare receipt lines: ' . $mysqli->error);
    }

    foreach ($items as $item) {
        list($barcode, $description, $quantity, $unitPrice, $vatAmount, $lineTotal) = $item;
        $line->bind_param('sssdddd', $docno, $barcode, $description, $quantity, $unitPrice, $vatAmount, $lineTotal);
        if (!$line->execute()) {
            throw new Exception('Could not save a receipt line: ' . $line->error);
        }
    }
    $line->close();

    $mysqli->commit();
    posSaveResponse(true, 'Receipt saved successfully.', 200, array('documentno' => $docno));
} catch (Exception $e) {
    if ($mysqli instanceof mysqli) {
        $mysqli->rollback();
    }
    error_log('POS receipt save failed: ' . $e->getMessage());
    posSaveResponse(false, 'The receipt could not be saved. Check the server error log.', 500);
}
?>
