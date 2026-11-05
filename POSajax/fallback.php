<?php
header('Content-Type: application/json; charset=utf-8');

function posPrintResponse($success, $message, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array('success' => $success, 'message' => $message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    posPrintResponse(false, 'POST is required.', 405);
}

$receiptData = json_decode(file_get_contents('php://input'), true);
if (!is_array($receiptData) || empty($receiptData['items'])) {
    posPrintResponse(false, 'Invalid receipt data.', 400);
}

$PathPrefix = '../';
include('../config.php');

if (empty($POSPrintUrl)) {
    posPrintResponse(false, 'The POS printing URL is not configured.', 500);
}
if (!function_exists('curl_init')) {
    posPrintResponse(false, 'PHP cURL is not installed. Enable the PHP cURL extension.', 500);
}

$payload = json_encode($receiptData);
$ch = curl_init($POSPrintUrl);
curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'X-Print-Token: ' . (isset($POSPrintToken) ? $POSPrintToken : '')
    ),
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 20
));

$response = curl_exec($ch);
$curlError = curl_error($ch);
$statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    posPrintResponse(false, 'Could not connect to the Node.js printer service: ' . $curlError, 502);
}

$decoded = json_decode($response, true);
if ($statusCode >= 200 && $statusCode < 300 && is_array($decoded) && !empty($decoded['success'])) {
    posPrintResponse(true, 'Receipt printed successfully.');
}

$message = (is_array($decoded) && !empty($decoded['message']))
    ? $decoded['message']
    : 'The Node.js printer service returned an unsuccessful response.';
posPrintResponse(false, $message, 502);
?>
