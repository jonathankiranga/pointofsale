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
        $lines = array();
        $lineSql = "SELECT l.code AS itemcode, l.description, SUM(l.Quantity) AS quantity,
                           SUM(l.invoiceamount) AS sales_value, SUM(l.vatamount) AS vat_value
                    FROM SalesLine l INNER JOIN SalesHeader h
                      ON h.documenttype=l.documenttype AND h.documentno=l.documentno
                    WHERE h.pos_shift_id=? AND h.documenttype=1 AND COALESCE(h.status,1)=1
                    GROUP BY l.code, l.description ORDER BY l.description";
        $lineStmt = $mysqli->prepare($lineSql);
        $lineStmt->bind_param('i', $shift['id']);
        $lineStmt->execute();
        $res = $lineStmt->get_result();
        while ($line = $res->fetch_assoc()) { $lines[] = $line; }
        $lineStmt->close();
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
        $expected = round((float)$shift['opening_float'] + (float)$totals['cash_sales'], 2);
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
            'mpesa_sales' => $mpesaSales, 'opening_float' => (float)$shift['opening_float'],
            'expected_cash' => $expected, 'cash_counted' => $counted, 'cash_variance' => $variance
        ));
    }
    posShiftResponse(false, 'Unknown shift action.', 400);
} catch (Exception $e) {
    if ($mysqli instanceof mysqli) { $mysqli->rollback(); }
    error_log('POS shift operation failed: ' . $e->getMessage());
    posShiftResponse(false, 'The shift operation failed. Check the server error log.', 500);
}
?>
