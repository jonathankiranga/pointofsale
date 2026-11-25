<?php
/*
 * Main back-office landing page.
 * Uses the standard ERP session bootstrap and the same visual language as
 * POSReports.php, but presents operational shortcuts and live daily figures.
 */
session_name('smartlimserp');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$PageSecurity = isset($_SESSION['PageSecurityArray']['CompanyPreferences.php'])
    ? $_SESSION['PageSecurityArray']['CompanyPreferences.php'] : -1;
$PathPrefix = '';
include('includes/globalsession.inc');

$Title = 'Home';
include('includes/header.inc');

if (!isset($db) || !($db instanceof mysqli)) {
    echo '<div class="alert alert-danger">The company database connection is unavailable.</div>';
    include('includes/footer.inc');
    exit;
}

$db->set_charset('utf8mb4');

function posHomeEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function posHomeMoney($value) {
    return number_format((float)$value, 2);
}

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$todaySummary = array('receipts'=>0,'sales'=>0,'vat'=>0,'cash_sales'=>0,'mpesa_sales'=>0);
$monthSummary = array('receipts'=>0,'sales'=>0);
$activeShift = null;
$recentReceipts = array();
$dashboardWarnings = array();

$summarySql = "SELECT COUNT(*) AS receipts,
                      COALESCE(SUM(total),0) AS sales,
                      COALESCE(SUM(total_vat),0) AS vat,
                      COALESCE(SUM(CASE WHEN payment_method='CASH' THEN total ELSE 0 END),0) AS cash_sales,
                      COALESCE(SUM(CASE WHEN payment_method='MPESA' THEN total ELSE 0 END),0) AS mpesa_sales
               FROM SalesHeader
               WHERE documenttype=1 AND COALESCE(status,1)=1
                 AND docdate >= ? AND docdate < DATE_ADD(?, INTERVAL 1 DAY)";
$stmt = $db->prepare($summarySql);
if ($stmt) {
    $stmt->bind_param('ss', $today, $today);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result) { $todaySummary = array_merge($todaySummary, (array)$result->fetch_assoc()); }
    } else {
        $dashboardWarnings[] = 'Today\'s sales summary could not be loaded.';
    }
    $stmt->close();
} else {
    $dashboardWarnings[] = 'Today\'s sales summary could not be loaded.';
}

$monthSql = "SELECT COUNT(*) AS receipts, COALESCE(SUM(total),0) AS sales
             FROM SalesHeader
             WHERE documenttype=1 AND COALESCE(status,1)=1
               AND docdate >= ? AND docdate < DATE_ADD(?, INTERVAL 1 DAY)";
$stmt = $db->prepare($monthSql);
if ($stmt) {
    $monthEnd = $today;
    $stmt->bind_param('ss', $monthStart, $monthEnd);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result) { $monthSummary = array_merge($monthSummary, (array)$result->fetch_assoc()); }
    }
    $stmt->close();
}

$shiftSql = "SELECT id, z_report_number, cashier_name, opened_at, opening_float,
                    total_sales, cash_sales, mpesa_sales, status
             FROM pos_shifts
             WHERE LOWER(status)='open'
             ORDER BY opened_at DESC LIMIT 1";
$shiftResult = $db->query($shiftSql);
if ($shiftResult) {
    $activeShift = $shiftResult->fetch_assoc();
} else {
    $dashboardWarnings[] = 'The current cashier shift could not be checked.';
}

$recentSql = "SELECT documentno, docdate, userid, payment_method, total
              FROM SalesHeader
              WHERE documenttype=1 AND COALESCE(status,1)=1
              ORDER BY docdate DESC, documentno DESC LIMIT 8";
$recentResult = $db->query($recentSql);
if ($recentResult) {
    while ($row = $recentResult->fetch_assoc()) { $recentReceipts[] = $row; }
} else {
    $dashboardWarnings[] = 'Recent receipts could not be loaded.';
}
?>
<style>
.pos-home-page{padding:24px 28px 40px;color:#243247;background:#f4f7fb;min-height:calc(100vh - 72px)}
.pos-home-hero{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:22px;padding:26px 30px;color:#fff;background:linear-gradient(120deg,#172b4d,#245b86);border-radius:14px;box-shadow:0 8px 24px rgba(23,43,77,.12)}
.pos-home-hero h1{margin:0 0 7px;font-size:27px;font-weight:750;letter-spacing:-.4px}
.pos-home-hero p{margin:0;color:rgba(255,255,255,.8);font-size:13px}
.pos-home-date{font-size:13px;color:rgba(255,255,255,.85);text-align:right}
.pos-home-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:22px}
.pos-home-stat{padding:20px 22px;background:#fff;border:1px solid #e3eaf2;border-radius:12px;box-shadow:0 4px 14px rgba(15,23,42,.045)}
.pos-home-stat span{display:block;color:#718096;font-size:10px;font-weight:800;letter-spacing:.75px;text-transform:uppercase}
.pos-home-stat strong{display:block;margin-top:9px;color:#172b4d;font-size:25px;font-weight:750;line-height:1.2}
.pos-home-section-title{margin:25px 0 12px;color:#243b53;font-size:17px;font-weight:750}
.pos-home-actions{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.pos-home-action{display:block;min-height:116px;padding:19px 20px;color:#243247!important;text-decoration:none!important;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 3px 12px rgba(15,23,42,.035);transition:transform .15s ease,box-shadow .15s ease}
.pos-home-action:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(15,23,42,.09);border-color:#c8d8e8}
.pos-home-action .action-icon{display:flex;align-items:center;justify-content:center;width:38px;height:38px;margin-bottom:12px;color:#1769aa;background:#eaf3fb;border-radius:10px;font-size:17px}
.pos-home-action strong{display:block;margin-bottom:5px;color:#203f63;font-size:14px;font-weight:750}
.pos-home-action small{display:block;color:#718096;font-size:12px;line-height:1.45}
.pos-home-lower{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(260px,1fr);gap:16px}
.pos-home-panel{overflow:hidden;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 3px 12px rgba(15,23,42,.035)}
.pos-home-panel-heading{padding:15px 19px;color:#243b53;background:#f8fafc;border-bottom:1px solid #e8edf3;font-size:14px;font-weight:750}
.pos-home-panel-body{padding:18px 20px}
.pos-home-table{width:100%;border-collapse:collapse;font-size:12px}
.pos-home-table th{padding:10px 9px;text-align:left;color:#64748b;background:#f1f5f9;font-size:10px;text-transform:uppercase;letter-spacing:.4px}
.pos-home-table td{padding:11px 9px;border-bottom:1px solid #edf1f6;color:#344256}
.pos-home-table tr:last-child td{border-bottom:0}
.pos-home-table .amount{text-align:right;white-space:nowrap;font-weight:700}
.pos-home-shift-status{display:inline-block;padding:5px 9px;color:#166534;background:#dcfce7;border-radius:20px;font-size:10px;font-weight:800;text-transform:uppercase}
.pos-home-shift-status.closed{color:#64748b;background:#e2e8f0}
.pos-home-shift-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:15px}
.pos-home-shift-grid span{display:block;color:#718096;font-size:11px}
.pos-home-shift-grid strong{display:block;margin-top:4px;color:#203f63;font-size:15px}
.pos-home-empty{padding:18px;color:#718096;text-align:center;font-size:12px}
.pos-home-warnings{margin-bottom:15px}
@media(max-width:900px){.pos-home-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.pos-home-actions{grid-template-columns:repeat(2,minmax(0,1fr))}.pos-home-lower{grid-template-columns:1fr}}
@media(max-width:600px){.pos-home-page{padding:12px 10px 28px}.pos-home-hero{align-items:flex-start;flex-direction:column;padding:20px}.pos-home-date{text-align:left}.pos-home-hero h1{font-size:22px}.pos-home-stats{gap:9px}.pos-home-stat{padding:15px}.pos-home-stat strong{font-size:20px}.pos-home-actions{grid-template-columns:1fr;gap:10px}.pos-home-action{min-height:0}.pos-home-lower{gap:12px}}
</style>

<div class="container-fluid pos-home-page">
    <header class="pos-home-hero">
        <div>
            <h1>Good <?php echo ((int)date('G') < 12 ? 'morning' : ((int)date('G') < 17 ? 'afternoon' : 'evening')); ?></h1>
            <p>Your business overview and everyday work areas.</p>
        </div>
        <div class="pos-home-date"><?php echo posHomeEscape(date('l, j F Y')); ?></div>
    </header>

    <?php if ($dashboardWarnings) { ?>
        <div class="alert alert-warning pos-home-warnings"><?php echo posHomeEscape(implode(' ', $dashboardWarnings)); ?></div>
    <?php } ?>

    <div class="pos-home-stats">
        <div class="pos-home-stat"><span>Today's receipts</span><strong><?php echo (int)$todaySummary['receipts']; ?></strong></div>
        <div class="pos-home-stat"><span>Today's sales · KES</span><strong><?php echo posHomeMoney($todaySummary['sales']); ?></strong></div>
        <div class="pos-home-stat"><span>Cash received · KES</span><strong><?php echo posHomeMoney($todaySummary['cash_sales']); ?></strong></div>
        <div class="pos-home-stat"><span>M-Pesa received · KES</span><strong><?php echo posHomeMoney($todaySummary['mpesa_sales']); ?></strong></div>
    </div>

    <h2 class="pos-home-section-title">Quick access</h2>
    <div class="pos-home-actions">
        <a class="pos-home-action" href="SalonPOS.php"><span class="action-icon"><i class="fa fa-scissors"></i></span><strong>Salon terminal</strong><small>Take a salon booking, select services and complete payment.</small></a>
        <a class="pos-home-action" href="SalonManagement.php?view=appointments"><span class="action-icon"><i class="fa fa-calendar"></i></span><strong>Appointments</strong><small>View and manage salon appointments and schedules.</small></a>
        <a class="pos-home-action" href="SalonManagement.php?view=services"><span class="action-icon"><i class="fa fa-list"></i></span><strong>Salon services</strong><small>Maintain service details, prices and service images.</small></a>
        <a class="pos-home-action" href="SalonManagement.php?view=hairdressers"><span class="action-icon"><i class="fa fa-users"></i></span><strong>Hairdressers</strong><small>Manage salon staff and their active status.</small></a>
        <a class="pos-home-action" href="POSReports.php"><span class="action-icon"><i class="fa fa-bar-chart"></i></span><strong>Sales and Z reports</strong><small>Review receipts, payment reconciliation and cashier shifts.</small></a>
        <a class="pos-home-action" href="Customer.php"><span class="action-icon"><i class="fa fa-user"></i></span><strong>Customers</strong><small>Open the customer records and account details.</small></a>
    </div>

    <h2 class="pos-home-section-title">Today's activity</h2>
    <div class="pos-home-lower">
        <section class="pos-home-panel">
            <div class="pos-home-panel-heading">Recent POS receipts</div>
            <?php if ($recentReceipts) { ?>
            <div style="overflow-x:auto">
                <table class="pos-home-table">
                    <thead><tr><th>Receipt</th><th>Date</th><th>Cashier</th><th>Payment</th><th class="amount">Total (KES)</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentReceipts as $receipt) { ?>
                        <tr>
                            <td><?php echo posHomeEscape($receipt['documentno']); ?></td>
                            <td><?php echo posHomeEscape($receipt['docdate']); ?></td>
                            <td><?php echo posHomeEscape($receipt['userid']); ?></td>
                            <td><?php echo posHomeEscape($receipt['payment_method']); ?></td>
                            <td class="amount"><?php echo posHomeMoney($receipt['total']); ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php } else { ?><div class="pos-home-empty">No posted POS receipts have been recorded yet.</div><?php } ?>
        </section>

        <section class="pos-home-panel">
            <div class="pos-home-panel-heading">Cashier shift</div>
            <div class="pos-home-panel-body">
                <?php if ($activeShift) { ?>
                    <span class="pos-home-shift-status">Open</span>
                    <div class="pos-home-shift-grid">
                        <div><span>Cashier</span><strong><?php echo posHomeEscape($activeShift['cashier_name']); ?></strong></div>
                        <div><span>Opened</span><strong><?php echo posHomeEscape($activeShift['opened_at']); ?></strong></div>
                        <div><span>Opening float</span><strong>KES <?php echo posHomeMoney($activeShift['opening_float']); ?></strong></div>
                        <div><span>Shift sales</span><strong>KES <?php echo posHomeMoney($activeShift['total_sales']); ?></strong></div>
                    </div>
                    <p style="margin:17px 0 0"><a href="POSReports.php?shift_id=<?php echo (int)$activeShift['id']; ?>">View shift details</a></p>
                <?php } else { ?>
                    <span class="pos-home-shift-status closed">No open shift</span>
                    <p style="margin:14px 0 0;color:#718096;font-size:12px">Open a cashier shift from the salon terminal before processing sales.</p>
                    <p style="margin:13px 0 0"><a href="SalonPOS.php">Open salon terminal</a></p>
                <?php } ?>
                <hr>
                <div><span style="color:#718096;font-size:11px">Month-to-date sales</span><strong style="display:block;margin-top:5px;color:#203f63;font-size:20px">KES <?php echo posHomeMoney($monthSummary['sales']); ?></strong><small style="color:#718096"><?php echo (int)$monthSummary['receipts']; ?> posted receipts since <?php echo posHomeEscape(date('j M Y', strtotime($monthStart))); ?></small></div>
            </div>
        </section>
    </div>
</div>
<?php include('includes/footer.inc'); ?>
