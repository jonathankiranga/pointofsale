<?php
session_name('smartlimserp');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
$adminToken = isset($_SESSION['PageSecurityArray']['CompanyPreferences.php'])
    ? $_SESSION['PageSecurityArray']['CompanyPreferences.php'] : null;
if (empty($_SESSION['DatabaseName'])) {
    http_response_code(401);
    echo 'Your session has expired. Sign in again.';
    exit;
}
if ($adminToken === null || !in_array($adminToken, $_SESSION['AllowedPageSecurityTokens'])) {
    http_response_code(403);
    echo 'You do not have permission to view POS administration reports.';
    exit;
}
$PageSecurity = $adminToken;
$PathPrefix = '';
include('includes/globalsession.inc');
global $mysqli;
if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
    die('Could not connect to the company database.');
}

function posReportEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function posReportMoney($value) {
    return number_format((float)$value, 2);
}
function posReportBind($stmt, $types, $params) {
    $values = array_merge(array($types), $params);
    $refs = array();
    foreach ($values as $key => $value) { $refs[$key] = &$values[$key]; }
    call_user_func_array(array($stmt, 'bind_param'), $refs);
}
$from = isset($_GET['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from']) ? $_GET['from'] : date('Y-m-01');
$to = isset($_GET['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to']) ? $_GET['to'] : date('Y-m-d');
$cashier = isset($_GET['cashier']) ? trim((string)$_GET['cashier']) : '';

$where = "h.documenttype=1 AND h.pos_shift_id IS NOT NULL AND COALESCE(h.status,1)=1 AND h.docdate >= ? AND h.docdate < DATE_ADD(?, INTERVAL 1 DAY)";
$params = array($from, $to);
$types = 'ss';
if ($cashier !== '') {
    $where .= ' AND h.userid=?';
    $params[] = $cashier;
    $types .= 's';
}

$sql = "SELECT COUNT(*) AS receipts, COALESCE(SUM(h.total),0) AS sales,
        COALESCE(SUM(h.total_vat),0) AS vat,
        COALESCE(SUM(CASE WHEN h.payment_method='CASH' THEN h.total ELSE 0 END),0) AS cash_sales,
        COALESCE(SUM(CASE WHEN h.payment_method='MPESA' THEN h.total ELSE 0 END),0) AS mpesa_sales
        FROM SalesHeader h WHERE " . $where;
$stmt = $mysqli->prepare($sql);
posReportBind($stmt, $types, $params);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

$cashiers = array();
$r = $mysqli->query("SELECT userid, realname FROM www_users ORDER BY realname, userid");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $cashiers[] = $row;
    }
}

$paymentSql = "SELECT COALESCE(payment_method,'UNCLASSIFIED') AS payment_method,
                      COUNT(*) AS receipts, COALESCE(SUM(total),0) AS amount
               FROM SalesHeader h WHERE " . $where . " GROUP BY payment_method ORDER BY payment_method";
$stmt = $mysqli->prepare($paymentSql);
posReportBind($stmt, $types, $params);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$itemSql = "SELECT l.code AS itemcode, l.description, SUM(l.Quantity) AS quantity,
                   SUM(l.invoiceamount) AS sales_value, SUM(l.vatamount) AS vat_value
            FROM SalesLine l INNER JOIN SalesHeader h
              ON h.documenttype=l.documenttype AND h.documentno=l.documentno
            WHERE " . $where . "
            GROUP BY l.code,l.description ORDER BY sales_value DESC";
$stmt = $mysqli->prepare($itemSql);
posReportBind($stmt, $types, $params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$taxSql = "SELECT COALESCE(l.pos_tax_category,'UNCLASSIFIED') AS tax_category,
                       l.vatrate AS vat_rate,
                       SUM(l.invoiceamount-l.vatamount) AS net_value,
                       SUM(l.vatamount) AS vat_value,
                       SUM(l.invoiceamount) AS gross_value
                FROM SalesLine l INNER JOIN SalesHeader h
                  ON h.documenttype=l.documenttype AND h.documentno=l.documentno
                WHERE " . $where . "
                GROUP BY l.pos_tax_category,l.vatrate ORDER BY l.vatrate DESC";
$stmt = $mysqli->prepare($taxSql);
posReportBind($stmt, $types, $params);
$stmt->execute();
$taxBreakdown = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$shiftSql = "SELECT id,z_report_number,cashier_id,cashier_name,opened_at,closed_at,opening_float,
                         expected_cash,cash_counted,cash_variance,total_sales,total_vat,cash_sales,mpesa_sales,status
                  FROM pos_shifts WHERE opened_at >= ? AND opened_at < DATE_ADD(?, INTERVAL 1 DAY)";
$shiftParams = array($from, $to);
$shiftTypes = 'ss';
if ($cashier !== '') { $shiftSql .= ' AND cashier_id=?'; $shiftParams[] = $cashier; $shiftTypes .= 's'; }
$shiftSql .= ' ORDER BY opened_at DESC';
$stmt = $mysqli->prepare($shiftSql);
posReportBind($stmt, $shiftTypes, $shiftParams);
$stmt->execute();
$shifts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


$selectedShift = null;
$shiftPayments = array();
$shiftProducts = array();
$shiftReceipts = array();
$selectedShiftId = isset($_GET['shift_id']) ? (int)$_GET['shift_id'] : 0;
if ($selectedShiftId > 0) {
    $detailStmt = $mysqli->prepare("SELECT * FROM pos_shifts WHERE id=? LIMIT 1");
    $detailStmt->bind_param('i', $selectedShiftId);
    $detailStmt->execute();
    $selectedShift = $detailStmt->get_result()->fetch_assoc();
    $detailStmt->close();
    if ($selectedShift) {
        $detailStmt = $mysqli->prepare("SELECT COALESCE(payment_method,'UNCLASSIFIED') AS payment_method, COUNT(*) AS receipts, COALESCE(SUM(total),0) AS amount FROM SalesHeader WHERE pos_shift_id=? AND documenttype=1 AND COALESCE(status,1)=1 GROUP BY payment_method ORDER BY payment_method");
        $detailStmt->bind_param('i', $selectedShiftId);
        $detailStmt->execute();
        $shiftPayments = $detailStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $detailStmt->close();

        $detailStmt = $mysqli->prepare("SELECT l.code AS itemcode,l.description,SUM(l.Quantity) AS quantity,SUM(l.invoiceamount) AS sales_value,SUM(l.vatamount) AS vat_value FROM SalesLine l INNER JOIN SalesHeader h ON h.documenttype=l.documenttype AND h.documentno=l.documentno WHERE h.pos_shift_id=? AND h.documenttype=1 AND COALESCE(h.status,1)=1 GROUP BY l.code,l.description ORDER BY sales_value DESC");
        $detailStmt->bind_param('i', $selectedShiftId);
        $detailStmt->execute();
        $shiftProducts = $detailStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $detailStmt->close();

        $detailStmt = $mysqli->prepare("SELECT documentno,docdate,userid,payment_method,total,total_vat FROM SalesHeader WHERE pos_shift_id=? AND documenttype=1 AND COALESCE(status,1)=1 ORDER BY docdate,documentno");
        $detailStmt->bind_param('i', $selectedShiftId);
        $detailStmt->execute();
        $shiftReceipts = $detailStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $detailStmt->close();
    }
}

$transactionSql = "SELECT h.documentno,h.docdate,h.userid,h.payment_method,h.total,h.total_vat
                   FROM SalesHeader h WHERE " . $where . " ORDER BY h.docdate DESC,h.documentno DESC LIMIT 500";
$stmt = $mysqli->prepare($transactionSql);
posReportBind($stmt, $types, $params);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$Title = 'POS Administration Reports';
include('includes/header.inc');
?>
<style>
@media print {
  .pos-report-filters, .pos-admin-summary, .pos-shift-history, .pos-other-reports,
  .pos-shift-history + .table-responsive, .pos-other-reports + .table-responsive,
  .container-fluid > .row:first-child, .pos-z-detail .panel-heading button { display:none !important; }
  .pos-z-detail { display:block !important; border:0 !important; }
  .pos-z-detail .panel-heading { background:#fff !important; color:#000 !important; border:0 !important; font-size:18px; }
  .pos-z-detail .panel-body { padding:0 !important; }
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-sm-8"><h2>POS Administration Reports</h2><p>Sales, payment and product performance from posted POS receipts.</p></div>
        <div class="col-sm-4 text-right"><a class="btn btn-default" href="posindex.php">Return to POS</a></div>
    </div>
    <form method="get" class="form-inline pos-report-filters" style="margin:15px 0;">
        <label>From <input class="form-control" type="date" name="from" value="<?php echo posReportEscape($from); ?>"></label>
        <label style="margin-left:10px;">To <input class="form-control" type="date" name="to" value="<?php echo posReportEscape($to); ?>"></label>
        <label style="margin-left:10px;">Cashier <select class="form-control" name="cashier"><option value="">All cashiers</option>
            <?php foreach ($cashiers as $cashierUser) {
                $cashierId = (string)$cashierUser['userid'];
                $cashierName = trim((string)$cashierUser['realname']);
                if ($cashierName === '') { $cashierName = $cashierId; }
            ?><option value="<?php echo posReportEscape($cashierId); ?>"<?php echo $cashierId === $cashier ? ' selected' : ''; ?>><?php echo posReportEscape($cashierName . ' (' . $cashierId . ')'); ?></option><?php } ?>
        </select></label>
        <button class="btn btn-primary" type="submit">Run report</button>
        <button class="btn btn-default" type="button" onclick="window.print()">Print</button>
    </form>

    <div class="row pos-admin-summary">
        <div class="col-sm-3"><div class="panel panel-default"><div class="panel-body"><small>RECEIPTS</small><h3><?php echo (int)$summary['receipts']; ?></h3></div></div></div>
        <div class="col-sm-3"><div class="panel panel-default"><div class="panel-body"><small>GROSS SALES (KES)</small><h3><?php echo posReportMoney($summary['sales']); ?></h3></div></div></div>
        <div class="col-sm-3"><div class="panel panel-default"><div class="panel-body"><small>VAT RECORDED (KES)</small><h3><?php echo posReportMoney($summary['vat']); ?></h3></div></div></div>
        <div class="col-sm-3"><div class="panel panel-default"><div class="panel-body"><small>CASH / M-PESA (KES)</small><h3><?php echo posReportMoney($summary['cash_sales']); ?> / <?php echo posReportMoney($summary['mpesa_sales']); ?></h3></div></div></div>
    </div>


    <?php if ($selectedShift) { ?>
    <section class="panel panel-primary pos-z-detail" id="pos-z-detail">
        <div class="panel-heading">
            <strong><?php echo posReportEscape($selectedShift['z_report_number'] ? $selectedShift['z_report_number'] : 'Shift #' . $selectedShift['id']); ?> — Full shift report</strong>
            <button type="button" class="btn btn-default btn-sm pull-right" onclick="window.print()">Print Z report</button>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-sm-3"><strong>Cashier</strong><br><?php echo posReportEscape($selectedShift['cashier_name']); ?></div>
                <div class="col-sm-3"><strong>Opened</strong><br><?php echo posReportEscape($selectedShift['opened_at']); ?></div>
                <div class="col-sm-3"><strong>Closed</strong><br><?php echo posReportEscape($selectedShift['closed_at'] ? $selectedShift['closed_at'] : 'Not closed'); ?></div>
                <div class="col-sm-3"><strong>Status</strong><br><?php echo posReportEscape($selectedShift['status']); ?></div>
            </div>
            <hr>
            <div class="row">
                <div class="col-sm-3"><strong>Gross sales</strong><br>KES <?php echo posReportMoney($selectedShift['total_sales']); ?></div>
                <div class="col-sm-3"><strong>VAT</strong><br>KES <?php echo posReportMoney($selectedShift['total_vat']); ?></div>
                <div class="col-sm-3"><strong>Cash sales</strong><br>KES <?php echo posReportMoney($selectedShift['cash_sales']); ?></div>
                <div class="col-sm-3"><strong>M-Pesa sales</strong><br>KES <?php echo posReportMoney($selectedShift['mpesa_sales']); ?></div>
            </div>
            <hr>
            <div class="row">
                <div class="col-sm-3"><strong>Opening float</strong><br>KES <?php echo posReportMoney($selectedShift['opening_float']); ?></div>
                <div class="col-sm-3"><strong>Expected cash</strong><br>KES <?php echo $selectedShift['expected_cash'] === null ? '—' : posReportMoney($selectedShift['expected_cash']); ?></div>
                <div class="col-sm-3"><strong>Counted cash</strong><br>KES <?php echo $selectedShift['cash_counted'] === null ? '—' : posReportMoney($selectedShift['cash_counted']); ?></div>
                <div class="col-sm-3"><strong>Cash variance</strong><br>KES <?php echo $selectedShift['cash_variance'] === null ? '—' : posReportMoney($selectedShift['cash_variance']); ?></div>
            </div>
            <h4>Payment breakdown</h4>
            <div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr><th>Method</th><th>Receipts</th><th class="text-right">Amount (KES)</th></tr></thead><tbody>
            <?php foreach ($shiftPayments as $payment) { ?><tr><td><?php echo posReportEscape($payment['payment_method']); ?></td><td><?php echo (int)$payment['receipts']; ?></td><td class="text-right"><?php echo posReportMoney($payment['amount']); ?></td></tr><?php } ?>
            <?php if (!$shiftPayments) { ?><tr><td colspan="3">No posted payments recorded.</td></tr><?php } ?>
            </tbody></table></div>
            <h4>Products sold</h4>
            <div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr><th>Item code</th><th>Description</th><th class="text-right">Quantity</th><th class="text-right">Sales (KES)</th><th class="text-right">VAT (KES)</th></tr></thead><tbody>
            <?php foreach ($shiftProducts as $item) { ?><tr><td><?php echo posReportEscape($item['itemcode']); ?></td><td><?php echo posReportEscape($item['description']); ?></td><td class="text-right"><?php echo posReportMoney($item['quantity']); ?></td><td class="text-right"><?php echo posReportMoney($item['sales_value']); ?></td><td class="text-right"><?php echo posReportMoney($item['vat_value']); ?></td></tr><?php } ?>
            <?php if (!$shiftProducts) { ?><tr><td colspan="5">No product lines recorded.</td></tr><?php } ?>
            </tbody></table></div>
            <h4>Receipts in this shift</h4>
            <div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr><th>Receipt</th><th>Date</th><th>Cashier</th><th>Payment</th><th class="text-right">Gross (KES)</th><th class="text-right">VAT (KES)</th></tr></thead><tbody>
            <?php foreach ($shiftReceipts as $receipt) { ?><tr><td><?php echo posReportEscape($receipt['documentno']); ?></td><td><?php echo posReportEscape($receipt['docdate']); ?></td><td><?php echo posReportEscape($receipt['userid']); ?></td><td><?php echo posReportEscape($receipt['payment_method']); ?></td><td class="text-right"><?php echo posReportMoney($receipt['total']); ?></td><td class="text-right"><?php echo posReportMoney($receipt['total_vat']); ?></td></tr><?php } ?>
            <?php if (!$shiftReceipts) { ?><tr><td colspan="6">No receipts recorded for this shift.</td></tr><?php } ?>
            </tbody></table></div>
        </div>
    </section>
    <?php } elseif ($selectedShiftId > 0) { ?>
        <div class="alert alert-warning">The requested shift could not be found.</div>
    <?php } ?>

    <h3 class="pos-shift-history">Cashier shifts and Z reports</h3>
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>Z report</th><th>Cashier</th><th>Opened</th><th>Closed</th><th>Status</th><th class="text-right">Opening float</th><th class="text-right">Expected cash</th><th class="text-right">Counted cash</th><th class="text-right">Variance</th><th class="text-right">Gross sales</th></tr></thead><tbody>
        <?php foreach ($shifts as $shift) { ?><tr><td><?php if ($shift['z_report_number']) { ?><a href="POSReports.php?from=<?php echo rawurlencode($from); ?>&amp;to=<?php echo rawurlencode($to); ?>&amp;cashier=<?php echo rawurlencode($cashier); ?>&amp;shift_id=<?php echo (int)$shift['id']; ?>#pos-z-detail"><?php echo posReportEscape($shift['z_report_number']); ?></a><?php } else { echo '—'; } ?></td><td><?php echo posReportEscape($shift['cashier_name']); ?></td><td><?php echo posReportEscape($shift['opened_at']); ?></td><td><?php echo posReportEscape($shift['closed_at'] ? $shift['closed_at'] : '—'); ?></td><td><?php echo posReportEscape($shift['status']); ?></td><td class="text-right"><?php echo posReportMoney($shift['opening_float']); ?></td><td class="text-right"><?php echo $shift['expected_cash'] === null ? '—' : posReportMoney($shift['expected_cash']); ?></td><td class="text-right"><?php echo $shift['cash_counted'] === null ? '—' : posReportMoney($shift['cash_counted']); ?></td><td class="text-right"><?php echo $shift['cash_variance'] === null ? '—' : posReportMoney($shift['cash_variance']); ?></td><td class="text-right"><?php echo $shift['total_sales'] === null ? '—' : posReportMoney($shift['total_sales']); ?></td></tr><?php } ?>
        <?php if (!$shifts) { ?><tr><td colspan="10">No cashier shifts found for this period.</td></tr><?php } ?>
    </tbody></table></div>

    <h3 class="pos-other-reports">Payment reconciliation</h3>
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>Payment method</th><th>Receipts</th><th class="text-right">Amount (KES)</th></tr></thead><tbody>
        <?php foreach ($payments as $payment) { ?><tr><td><?php echo posReportEscape($payment['payment_method']); ?></td><td><?php echo (int)$payment['receipts']; ?></td><td class="text-right"><?php echo posReportMoney($payment['amount']); ?></td></tr><?php } ?>
    </tbody></table></div>

    <h3 class="pos-other-reports">Tax breakdown by ERP VAT category</h3>
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>ERP VAT category</th><th class="text-right">VAT rate (%)</th><th class="text-right">Net value (KES)</th><th class="text-right">VAT (KES)</th><th class="text-right">Gross value (KES)</th></tr></thead><tbody>
        <?php foreach ($taxBreakdown as $tax) { ?><tr><td><?php echo posReportEscape($tax['tax_category']); ?></td><td class="text-right"><?php echo posReportMoney($tax['vat_rate']); ?></td><td class="text-right"><?php echo posReportMoney($tax['net_value']); ?></td><td class="text-right"><?php echo posReportMoney($tax['vat_value']); ?></td><td class="text-right"><?php echo posReportMoney($tax['gross_value']); ?></td></tr><?php } ?>
        <?php if (!$taxBreakdown) { ?><tr><td colspan="5">No tax data found for this period.</td></tr><?php } ?>
    </tbody></table></div>

    <h3 class="pos-other-reports">Products sold</h3>
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>Item code</th><th>Description</th><th class="text-right">Quantity</th><th class="text-right">Sales (KES)</th><th class="text-right">VAT (KES)</th></tr></thead><tbody>
        <?php foreach ($items as $item) { ?><tr><td><?php echo posReportEscape($item['itemcode']); ?></td><td><?php echo posReportEscape($item['description']); ?></td><td class="text-right"><?php echo posReportMoney($item['quantity']); ?></td><td class="text-right"><?php echo posReportMoney($item['sales_value']); ?></td><td class="text-right"><?php echo posReportMoney($item['vat_value']); ?></td></tr><?php } ?>
        <?php if (!$items) { ?><tr><td colspan="5">No POS sales found for this period.</td></tr><?php } ?>
    </tbody></table></div>

    <h3 class="pos-other-reports">Receipts</h3>
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>Receipt</th><th>Date</th><th>Cashier</th><th>Payment</th><th class="text-right">Gross (KES)</th><th class="text-right">VAT (KES)</th></tr></thead><tbody>
        <?php foreach ($transactions as $transaction) { ?><tr><td><?php echo posReportEscape($transaction['documentno']); ?></td><td><?php echo posReportEscape($transaction['docdate']); ?></td><td><?php echo posReportEscape($transaction['userid']); ?></td><td><?php echo posReportEscape($transaction['payment_method']); ?></td><td class="text-right"><?php echo posReportMoney($transaction['total']); ?></td><td class="text-right"><?php echo posReportMoney($transaction['total_vat']); ?></td></tr><?php } ?>
        <?php if (!$transactions) { ?><tr><td colspan="6">No POS receipts found for this period.</td></tr><?php } ?>
    </tbody></table></div>
    <p class="text-muted">These are internal POS reconciliation reports. They do not replace KRA eTIMS fiscalisation or statutory tax invoice records.</p>
</div>
<?php include('includes/footer.inc'); ?>
