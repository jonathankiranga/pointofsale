<?php
// Remove stale cache validators.
header_remove('ETag');
header_remove('Pragma');
header_remove('Cache-Control');
header_remove('Last-Modified');
header_remove('Expires');

// Prevent cached application pages.
header('Expires: Thu, 1 Jan 1970 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

include('config.php');
if (isset($SessionSavePath)) {
    session_save_path($SessionSavePath);
}

if (!isset($SysAdminEmail)) {
    $SysAdminEmail = '';
}

if (!isset($_SESSION)) {
    ini_set('session.gc_maxlifetime', $SessionLifeTime);

    if (!ini_get('safe_mode')) {
        set_time_limit($MaximumExecutionTime);
        ini_set('max_execution_time', $MaximumExecutionTime);
    }

    session_write_close();
    session_name('smartlimserp');
    session_start();
}

/*
 * index.php is the back-office router. Do not redirect to SalonPOS.php
 * merely because a request has no "link" parameter. Ordinary anchors such
 * as href="index.php" must remain in the back office.
 *
 * Only use the route explicitly supplied in this request. A previous
 * SCRIPT_NAME_BASE session value must never determine the next page.
 */
$route = isset($_GET['link']) ? trim((string) $_GET['link']) : '';

if (isset($_GET['Stocks']) && $_GET['Stocks'] == '1') {
    include('Stocks.php');
} elseif ($route === 'Logout' || $route === 'Logout.php') {
    unset($_SESSION['SCRIPT_NAME_BASE']);
    session_unset();
    session_destroy();

    $PageSecurity = 0;
    include('includes/session.inc');
    $Title = _('index');
    include('includes/bootstrapheader.inc');
    echo '<main id="view-panel" class="container">';
    include('includes/boostrapfooter.inc');
} elseif ($route !== '') {
    // Accept only a simple page name and resolve it inside the application
    // root. This prevents path traversal and arbitrary include paths.
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $route)) {
        http_response_code(400);
        exit('Invalid page request.');
    }

    $pageFile = $route . '.php';
    $pagePath = __DIR__ . DIRECTORY_SEPARATOR . $pageFile;

    if (!is_file($pagePath)) {
        http_response_code(404);
        exit('The requested page could not be found.');
    }

    $_SESSION['SCRIPT_NAME_BASE'] = $pageFile;
    include($pagePath);
} else {
    // No explicit destination was requested. Render the normal back-office
    // landing shell; do not silently open the salon terminal or a stale page.
    unset($_SESSION['SCRIPT_NAME_BASE']);
    $PageSecurity = 0;
    include('includes/session.inc');
    $Title = _('index');
    include('includes/bootstrapheader.inc');
    echo '<main id="view-panel" class="container">';
    include('includes/boostrapfooter.inc');
}
