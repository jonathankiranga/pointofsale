<?php
/*
 * End the current Salon POS login session and return to the login screen.
 * Do not include session.inc here: that bootstrap performs normal page setup
 * and is not needed when logging out.
 */

if (is_file('config.php')) {
    include_once('config.php');
}

if (isset($SessionSavePath) && $SessionSavePath !== '') {
    session_save_path($SessionSavePath);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('smartlimserp');
    session_start();
}

// Remove all server-side session data.
$_SESSION = array();

// Expire the browser's session cookie using the same cookie settings.
if (ini_get('session.use_cookies')) {
    $cookieParams = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $cookieParams['path'],
        $cookieParams['domain'],
        $cookieParams['secure'],
        $cookieParams['httponly']
    );
}

session_destroy();

// Return to the application entry point. It will render the login form
// because the authenticated session has been removed.
$rootPath = str_replace('\\\\', '/', dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/Logout.php'));
$rootPath = rtrim($rootPath, '/');
if ($rootPath === '.' || $rootPath === '/') {
    $rootPath = '';
}

header('Location: ' . $rootPath . '/index.php');
exit;
