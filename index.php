<?php
/*
 * Central application entry point.
 *
 * Page selection is URL-based. No page name is stored in the session.
 */

header_remove('ETag');
header_remove('Pragma');
header_remove('Cache-Control');
header_remove('Last-Modified');
header_remove('Expires');

header('Expires: Thu, 1 Jan 1970 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$PathPrefix = '';
$RequestedPage = '';

/*
 * Keep the existing Stocks shortcut working.
 * Stocks.php performs its own session and security initialization.
 */
if (isset($_GET['Stocks']) && $_GET['Stocks'] === '1') {
    $RequestedPage = 'Stocks';
    include 'Stocks.php';
    exit;
}

/*
 * Logout must destroy the current session and then start a clean request.
 * Do not render an empty page after destroying the session.
 */
if (isset($_GET['link']) && $_GET['link'] === 'Logout') {
    include 'Logout.php';
    header('Location: index.php');
    exit;
}

/*
 * Existing menu links use index.php?link=PageName.
 * Resolve that URL parameter directly on this request; never persist it.
 */
if (isset($_GET['link']) && $_GET['link'] !== '') {
    $RequestedPage = trim($_GET['link']);

    if (!preg_match('/^[A-Za-z0-9_-]+$/D', $RequestedPage)) {
        http_response_code(404);
        echo 'Page not found.';
        exit;
    }

    $PageFile = $RequestedPage . '.php';
    $PagePath = $PageFile;

    if (!is_file($PagePath)) {
        http_response_code(404);
        echo 'Page not found.';
        exit;
    }

    include $PagePath;
    exit;
}

/*
 * Default homepage. session.inc handles login and initializes the
 * application security context. PageSecurity 0 is the public landing page.
 */
$PageSecurity = 0;
include 'includes/session.inc';

$Title = _('Home');
include 'includes/bootstrapheader.inc';

echo '<main id="view-panel" class="container">';
echo '<section class="py-4">';
echo '<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">';
echo '<div>';
echo '<h1 class="h3 mb-1">' . _('Welcome') . '</h1>';
echo '<p class="text-muted mb-0">' . _('Select a workspace to continue.') . '</p>';
echo '</div>';
echo '<a class="btn btn-primary btn-lg" href="posindex.php">' . _('Open Point of Sale') . '</a>';
echo '</div>';

echo '<div class="row g-3">';
echo '<div class="col-12 col-md-6 col-xl-3">';
echo '<a class="card h-100 text-decoration-none" href="posindex.php"><div class="card-body">';
echo '<h2 class="h5">' . _('Point of Sale') . '</h2>';
echo '<p class="text-muted mb-0">' . _('Open the touch point-of-sale terminal.') . '</p>';
echo '</div></a></div>';

echo '<div class="col-12 col-md-6 col-xl-3">';
echo '<a class="card h-100 text-decoration-none" href="index.php?link=SelectCustomer&amp;newsearch=yes"><div class="card-body">';
echo '<h2 class="h5">' . _('Customers') . '</h2>';
echo '<p class="text-muted mb-0">' . _('Find and manage customer accounts.') . '</p>';
echo '</div></a></div>';

echo '<div class="col-12 col-md-6 col-xl-3">';
echo '<a class="card h-100 text-decoration-none" href="index.php?link=SelectSupplier&amp;newsearch=yes"><div class="card-body">';
echo '<h2 class="h5">' . _('Suppliers') . '</h2>';
echo '<p class="text-muted mb-0">' . _('Find and manage supplier accounts.') . '</p>';
echo '</div></a></div>';

echo '<div class="col-12 col-md-6 col-xl-3">';
echo '<a class="card h-100 text-decoration-none" href="index.php?link=SelectProduct&amp;newsearch=yes"><div class="card-body">';
echo '<h2 class="h5">' . _('Inventory') . '</h2>';
echo '<p class="text-muted mb-0">' . _('Find inventory items.') . '</p>';
echo '</div></a></div>';
echo '</div>';
echo '</section>';
include 'includes/boostrapfooter.inc';
