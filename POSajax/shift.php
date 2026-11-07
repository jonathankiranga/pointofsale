<?php
header('Content-Type: application/json; charset=utf-8');

function posShiftResponse($success, $message, $code = 200, $data = array()) {
    http_response_code($code);
    echo json_encode(array_merge(array('success' => $success, 'message' => $message), $data));
    exit;
}

$PathPrefix = '../';
include('../includes/globalsession.inc');
global $mysqli;

if (empty($_SESSION['DatabaseName']) || empty($_SESSION['UserID']) || !($mysqli instanceof mysqli)) {
    posShiftResponse(false, 'Your POS session has expired. Sign in again.', 401);
}
$userId = (string)$_SESSION['UserID'];
$userName = isset($_SESSION['UsersRealName']) ? (string)$_SESSION['UsersRealName'] : $userId;
$method = $_SERVER['REQUEST_METHOD'];
$input = array();
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        posShiftResponse(false, 'Invalid request body.', 400);
    }
    $token = isset($_SERVER['HTTP_X_POS_FORMID']) ? (string)$_SERVER['HTTP_X_POS_FORMID'] : '';
    if (empty($_SESSION['FormID']) || !hash_equals((string)$_SESSION['FormID'], $token)) {
        posShiftResponse(false, 'The POS security token has expired. Reload the terminal.', 403);
    }
}
$action = $method === 'GET' ? (isset($_GET['action']) ? $_GET['action'] : 'status') : (isset($input['action']) ? $input['action'] : '');

function posShiftTotals($mysqli, $shiftId) {
    $sql = "SELECT COUNT(*) AS receipt_count,
                   COALESCE(SUM(total),0) AS total_sales,
                   COALESCE(SUM(total_vat),0) AS total_vat,
                   COALESCE(SUM(CASE WHEN payment_method='CASH' THEN COALESCE(cash,0)-COALESCE(change_amount,0) ELSE 0 END),0) AS cash_sales,
                   COALESCE(SUM(CASE WHEN payment_method='MPESA' THEN total ELSE 0 END),0) AS mpesa_sales
            FROM SalesHeader
            WHERE pos_shift_id=? AND documenttype=1 AND COALESCE(status,1)=1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) { throw new Exception($mysqli->error); }
    $stmt->bind_param('i', $shiftId);
    if (!$stmt->execute()) { throw new Exception($stmt->error); }
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

function posShiftItems($mysqli, $shiftId) {
    $items = array();
    $sql = "SELECT l.code AS itemcode, l.description, SUM(l.Quantity) AS quantity,
                   SUM(l.invoiceamount) AS sales_value, SUM(l.vatamount) AS vat_value
            FROM SalesLine l INNER JOIN SalesHeader h
              ON h.documenttype=l.documenttype AND h.documentno=l.documentno
            WHERE h.pos_shift_id=? AND h.documenttype=1 AND COALESCE(h.status,1)=1
            GROUP BY l.code, l.description ORDER BY l.description";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) { throw new Exception($mysqli->error); }
    $stmt->bind_param('i', $shiftId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) { $items[] = $row; }
    $stmt->close();
    return $items;
}

function posShiftAccountingIntegrity($mysqli, $shiftId) {
    $sql = "SELECT h.documentno,h.total,h.payment_method,
        (SELECT COUNT(*) FROM SalesHeader ih
            WHERE ih.documenttype=10 AND ih.externaldocumentno=h.documentno) AS invoice_count,
        (SELECT COUNT(*) FROM CustomerStatement cs
            WHERE cs.Documentno=(SELECT ih.documentno FROM SalesHeader ih
                WHERE ih.documenttype=10 AND ih.externaldocumentno=h.documentno LIMIT 1)
              AND cs.Documenttype=10) AS invoice_statement_count,
        (SELECT COUNT(*) FROM GeneralLedger gl
            WHERE gl.DocumentNo=(SELECT ih.documentno FROM SalesHeader ih
                WHERE ih.documenttype=10 AND ih.externaldocumentno=h.documentno LIMIT 1)
              AND gl.DocumentType=10) AS invoice_gl_count,
        (SELECT COUNT(*) FROM debtorsledger dl
            WHERE dl.invref=(SELECT ih.documentno FROM SalesHeader ih
                WHERE ih.documenttype=10 AND ih.externaldocumentno=h.documentno LIMIT 1)
              AND dl.systypes_1=10) AS invoice_debtor_count,
        (SELECT COALESCE(SUM(cs.Grossamount),0) FROM CustomerStatement cs
            WHERE cs.Documentno=(SELECT ih.documentno FROM SalesHeader ih
                WHERE ih.documenttype=10 AND ih.externaldocumentno=h.documentno LIMIT 1)
              AND cs.Documenttype=10) AS invoice_amount,
        (SELECT COUNT(*) FROM receiptheader rh WHERE rh.docno=h.documentno) AS receipt_count,
        (SELECT externalref FROM receiptheader rh WHERE rh.docno=h.documentno LIMIT 1) AS receipt_reference,
        (SELECT COUNT(*) FROM BankTransactions bt
            WHERE bt.DocumentNo=h.documentno AND bt.doctype=12) AS bank_count,
        (SELECT COUNT(*) FROM BankTransactions bt
            INNER JOIN BankAccounts ba ON ba.accountcode=bt.bankcode
            WHERE bt.DocumentNo=h.documentno AND bt.doctype=12
              AND bt.exchangerate IS NOT NULL AND bt.exchangerate>0) AS bank_account_count,
        (SELECT COALESCE(SUM(bt.amount),0) FROM BankTransactions bt
            WHERE bt.DocumentNo=h.documentno AND bt.doctype=12) AS bank_amount,
        (SELECT COALESCE(SUM(rh.amount),0) FROM receiptheader rh
            WHERE rh.docno=h.documentno) AS receipt_amount,
        (SELECT COUNT(*) FROM debtorsledger dl
            WHERE dl.invref=h.documentno AND dl.systypes_1=12) AS receipt_debtor_count,
        (SELECT COUNT(*) FROM GeneralLedger gl
            WHERE gl.DocumentNo=h.documentno AND gl.DocumentType=12) AS receipt_gl_count,
        (SELECT COUNT(*) FROM receiptsallocation ra
            WHERE ra.receiptno=h.documentno) AS allocation_count,
        (SELECT COALESCE(SUM(ra.amount),0) FROM receiptsallocation ra
            WHERE ra.receiptno=h.documentno) AS allocation_amount,
        (SELECT COALESCE(SUM(cs.Grossamount),0) FROM CustomerStatement cs
            WHERE cs.Documentno=h.documentno AND cs.Documenttype=12) AS receipt_statement_amount
        FROM SalesHeader h
        WHERE h.pos_shift_id=? AND h.documenttype=1 AND COALESCE(h.status,1)=1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) { throw new Exception('Could not prepare POS accounting integrity check: ' . $mysqli->error); }
    $stmt->bind_param('i', $shiftId);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception('Could not verify POS accounting records: ' . $error);
    }

    $incomplete = array();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $total = ($row['total'] !== null && is_numeric($row['total'])) ? (float)$row['total'] : null;
        $reference = trim((string)$row['receipt_reference']);
        $referenceValid = ($row['payment_method'] === 'MPESA')
            ? (strpos($reference, 'MPESA ') === 0)
            : (($row['payment_method'] === 'CASH') && (strpos($reference, 'POS CASH ') === 0));

        $valid = $total !== null && is_finite($total) && $total > 0 &&
            (int)$row['invoice_count'] === 1 &&
            (int)$row['invoice_statement_count'] === 1 &&
            (int)$row['invoice_gl_count'] > 0 &&
            (int)$row['invoice_debtor_count'] > 0 &&
            abs((float)$row['invoice_amount'] - $total) < 0.005 &&
            (int)$row['receipt_count'] === 1 &&
            $referenceValid &&
            (int)$row['bank_count'] === 1 &&
            (int)$row['bank_account_count'] === 1 &&
            abs((float)$row['bank_amount'] - $total) < 0.005 &&
            abs((float)$row['receipt_amount'] - $total) < 0.005 &&
            (int)$row['receipt_debtor_count'] === 1 &&
            (int)$row['receipt_gl_count'] === 1 &&
            (int)$row['allocation_count'] === 1 &&
            abs((float)$row['allocation_amount'] + $total) < 0.005 &&
            abs((float)$row['receipt_statement_amount'] + $total) < 0.005;

        if (!$valid) {
            $incomplete[] = (string)$row['documentno'];
        }
    }
    $stmt->close();
    return $incomplete;
}

try {
    if ($action === 'status' || $action === 'x-report') {
        $stmt = $mysqli->prepare("SELECT * FROM pos_shifts WHERE cashier_id=? AND status='OPEN' ORDER BY id DESC LIMIT 1");
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $shift = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$shift) { posShiftResponse(true, 'No open cashier shift.', 200, array('shift' => null)); }
        $totals = posShiftTotals($mysqli, (int)$shift['id']);
        $shift['receipt_count'] = (int)$totals['receipt_count'];
        $shift['total_sales'] = (float)$totals['total_sales'];
        $shift['total_vat'] = (float)$totals['total_vat'];
        $shift['cash_sales'] = (float)$totals['cash_sales'];
        $shift['mpesa_sales'] = (float)$totals['mpesa_sales'];
        $shift['expected_cash'] = (float)$shift['opening_float'] + $shift['cash_sales'];
        $lines = posShiftItems($mysqli, (int)$shift['id']);
        posShiftResponse(true, 'Shift summary loaded.', 200, array('shift' => $shift, 'items' => $lines, 'report_type' => 'X'));
    }

    if ($method !== 'POST') { posShiftResponse(false, 'Unsupported request.', 405); }

    if ($action === 'open') {
        $openingFloat = isset($input['opening_float']) ? (float)$input['opening_float'] : -1;
        if (!is_finite($openingFloat) || $openingFloat < 0) {
            posShiftResponse(false, 'Enter a valid opening cash float.', 400);
        }
        $mysqli->begin_transaction();
        $check = $mysqli->prepare("SELECT id FROM pos_shifts WHERE cashier_id=? AND status='OPEN' FOR UPDATE");
        $check->bind_param('s', $userId);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();
        if ($existing) {
            $mysqli->rollback();
            posShiftResponse(false, 'You already have an open cashier shift.', 409, array('shift_id' => (int)$existing['id']));
        }
        $insert = $mysqli->prepare("INSERT INTO pos_shifts (cashier_id,cashier_name,opened_at,opening_float,status) VALUES (?,?,NOW(),?,'OPEN')");
        $insert->bind_param('ssd', $userId, $userName, $openingFloat);
        if (!$insert->execute()) { throw new Exception($insert->error); }
        $id = $mysqli->insert_id;
        $insert->close();
        $mysqli->commit();
        posShiftResponse(true, 'Cashier shift opened.', 200, array('shift_id' => $id));
    }

    if ($action === 'close') {
        $counted = isset($input['cash_counted']) ? (float)$input['cash_counted'] : -1;
        if (!is_finite($counted) || $counted < 0) {
            posShiftResponse(false, 'Enter the actual cash counted in the drawer.', 400);
        }
        $mysqli->begin_transaction();
        $stmt = $mysqli->prepare("SELECT * FROM pos_shifts WHERE cashier_id=? AND status='OPEN' ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $shift = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$shift) {
            $mysqli->rollback();
            posShiftResponse(false, 'There is no open shift to close.', 409);
        }
        $totals = posShiftTotals($mysqli, (int)$shift['id']);
        $incompleteReceipts = posShiftAccountingIntegrity($mysqli, (int)$shift['id']);
        if ($incompleteReceipts) {
            $mysqli->rollback();
            $displayReceipts = array_slice($incompleteReceipts, 0, 8);
            posShiftResponse(false,
                'Shift cannot close because these POS receipts are missing or have unbalanced accounting postings: ' . implode(', ', $displayReceipts),
                409,
                array('incomplete_receipts' => $incompleteReceipts)
            );
        }
        $expected = round((float)$shift['opening_float'] + (float)$totals['cash_sales'], 2);
        $items = posShiftItems($mysqli, (int)$shift['id']);
        $variance = round($counted - $expected, 2);
        $zNumber = 'Z-' . str_pad((string)$shift['id'], 8, '0', STR_PAD_LEFT);
        $update = $mysqli->prepare("UPDATE pos_shifts
            SET closed_at=NOW(),cash_counted=?,expected_cash=?,cash_variance=?,
                total_sales=?,total_vat=?,cash_sales=?,mpesa_sales=?,z_report_number=?,status='CLOSED'
            WHERE id=? AND status='OPEN'");
        $totalSales = (float)$totals['total_sales'];
        $totalVat = (float)$totals['total_vat'];
        $cashSales = (float)$totals['cash_sales'];
        $mpesaSales = (float)$totals['mpesa_sales'];
        $shiftId = (int)$shift['id'];
        $update->bind_param('dddddddsi', $counted, $expected, $variance, $totalSales, $totalVat, $cashSales, $mpesaSales, $zNumber, $shiftId);
        if (!$update->execute() || $update->affected_rows !== 1) { throw new Exception($update->error ? $update->error : 'The shift was already closed.'); }
        $update->close();
        $mysqli->commit();
        posShiftResponse(true, 'Z report closed and saved.', 200, array(
            'z_report_number' => $zNumber, 'receipt_count' => (int)$totals['receipt_count'],
            'total_sales' => $totalSales, 'total_vat' => $totalVat, 'cash_sales' => $cashSales,
            'mpesa_sales' => $mpesaSales, 'opening_float' => (float)$shift['opening_float'], 'items' => $items,
            'expected_cash' => $expected, 'cash_counted' => $counted, 'cash_variance' => $variance
        ));
    }
    posShiftResponse(false, 'Unknown shift action.', 400);
} catch (Exception $e) {
    if ($mysqli instanceof mysqli) { $mysqli->rollback(); }
    if ((int)$e->getCode() === 1062) {
        posShiftResponse(false, 'You already have an open cashier shift.', 409);
    }
    error_log('POS shift operation failed: ' . $e->getMessage());
    posShiftResponse(false, 'The shift operation failed. Check the server error log.', 500);
}
?>
