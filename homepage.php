<?php
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
$userName = isset($_SESSION['UsersRealName']) ? $_SESSION['UsersRealName'] : 'User';

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
        .salon-banner{height:58px;min-height:58px;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:0 22px;background:#172b4d;color:#fff;box-shadow:0 2px 8px rgba(15,23,42,.15);z-index:20}
        .salon-brand{display:flex;align-items:center;gap:12px;font-size:16px;font-weight:700;white-space:nowrap}
        .salon-brand-mark{width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:9px;background:#2b6695;color:#fff}
        .salon-banner-meta{display:flex;align-items:center;gap:17px;color:#dbe7f3;font-size:12px}
        .salon-banner a{color:#fff;text-decoration:none}
        .salon-banner a:hover{text-decoration:underline}
        .salon-content{flex:1;min-height:0;padding:12px 14px;background:#eef2f7}
        .salon-content iframe{width:100%;height:100%;display:block;border:1px solid #dfe6ee;border-radius:9px;background:#fff;box-shadow:0 4px 16px rgba(15,23,42,.06)}
        .salon-footer{height:30px;min-height:30px;display:flex;align-items:center;gap:18px;padding:0 16px;background:#fff;border-top:1px solid #dce4ed;color:#64748b;font-size:11px}
        .salon-footer .status{margin-left:auto;color:#28734d}
        @media(max-width:700px){.salon-banner{padding:0 11px}.salon-banner-meta .user-name{display:none}.salon-content{padding:5px}.salon-brand{font-size:14px}}
    </style>
</head>
<body>
<div class="salon-shell">
    <header class="salon-banner">
        <div class="salon-brand"><span class="salon-brand-mark"><i class="fa fa-scissors"></i></span><span>Salon POS</span></div>
        <div class="salon-banner-meta">
            <span class="user-name"><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span>
            <a href="SalonPOS.php" target="_blank" rel="noopener"><i class="fa fa-cash-register"></i> Open Salon Terminal</a>
            <a href="Logout.php"><i class="fa fa-sign-out-alt"></i> Log out</a>
        </div>
    </header>
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
