<?php
// Slim index: ensure login, then send the user to the back-office homepage.
$PageSecurity = 0;
include('includes/session.inc');

// If login is required, session.inc renders Login.php and exits.

$RootPath = isset($RootPath) ? $RootPath : '.';
header('Location: ' . $RootPath . '/homepage.php');
exit;
