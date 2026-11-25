<?php
/* Salon back-office application shell. The legacy ERP menu remains the sole navigation. */
session_name('smartlimserp');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$PageSecurity = isset($_SESSION['PageSecurityArray']['CompanyPreferences.php'])
    ? $_SESSION['PageSecurityArray']['CompanyPreferences.php'] : -1;
$PathPrefix = '';
include('includes/globalsession.inc');

$RootPath = isset($RootPath) ? $RootPath : '';
if ($RootPath === '') {
    $RootPath = dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/homepage.php');
}
$RootPath = rtrim($RootPath, '/\\');
if ($RootPath === '.' || $RootPath === '/') {
    $RootPath = '';
}
$companyName = isset($_SESSION['CompanyRecord']['coyname'])
    ? $_SESSION['CompanyRecord']['coyname'] : 'Salon POS';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Salon POS</title>
    <link rel="icon" href="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/favicon.ico">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/css/bootstrap5.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/css/fontawesome6.4.0.all.min.css">
    <style>
        *{box-sizing:border-box}
        html,body{height:100%;margin:0;overflow:hidden;font-family:Arial,sans-serif;background:#eef2f7;color:#203047}
        .salon-shell{height:100vh;display:flex;flex-direction:column}
        .salon-content{flex:1;min-height:0;padding:12px 14px;background:#eef2f7}
        .salon-content iframe{width:100%;height:100%;display:block;border:1px solid #dfe6ee;border-radius:9px;background:#fff;box-shadow:0 4px 16px rgba(15,23,42,.06)}
        .salon-footer{height:30px;min-height:30px;display:flex;align-items:center;gap:18px;padding:0 16px;background:#fff;border-top:1px solid #dce4ed;color:#64748b;font-size:11px}
        .salon-footer .status{margin-left:auto;color:#28734d}
        @media(max-width:700px){.salon-content{padding:5px}}
    </style>
</head>
<body>
<div class="salon-shell">
    <main class="salon-content">
        <iframe id="salonContentFrame" name="salonContentFrame"
            src="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/SalonDashboard.php"
            title="Salon back-office workspace"></iframe>
    </main>
    <footer class="salon-footer">
        <span><?php echo htmlspecialchars(date('l, j F Y'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="status" id="salonWorkspaceStatus">Ready</span>
        <span><?php echo htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?></span>
    </footer>
</div>

</body>
</html>
