<?php
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(array('error' => 'GET is required.'));
    exit;
}
session_name('smartlimserp');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (empty($_SESSION['DatabaseName'])) {
    http_response_code(401);
    echo json_encode(array('error' => 'POS session expired. Please sign in again.'));
    exit;
}
$barcode = isset($_GET['barcode']) ? trim((string)$_GET['barcode']) : '';
if ($barcode === '' || strlen($barcode) > 128) {
    http_response_code(400);
    echo json_encode(array('error' => 'A valid barcode is required.'));
    exit;
}
include('../config.php');
$conn = new mysqli($host, $DBUser, $DBPassword, $_SESSION['DatabaseName']);
if ($conn->connect_error) {
    error_log('POS barcode lookup connection failed: ' . $conn->connect_error);
    http_response_code(500);
    echo json_encode(array('error' => 'Could not connect to the company database.'));
    exit;
}
$sql = "SELECT stockmaster.barcode, stockmaster.descrip AS description,
               IFNULL(vatcategory.vat, 0) AS vat,
               IFNULL(stockmaster.sellingprice, 0) AS unit_price
        FROM stockmaster
        LEFT JOIN inventorypostinggroup ON stockmaster.postinggroup = inventorypostinggroup.code
        LEFT JOIN vatcategory ON inventorypostinggroup.vatcategory = vatcategory.vatc
        WHERE stockmaster.barcode = ? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('POS barcode lookup prepare failed: ' . $conn->error);
    http_response_code(500);
    echo json_encode(array('error' => 'Could not prepare the product lookup.'));
    $conn->close();
    exit;
}
$stmt->bind_param('s', $barcode);
if (!$stmt->execute()) {
    error_log('POS barcode lookup execute failed: ' . $stmt->error);
    http_response_code(500);
    echo json_encode(array('error' => 'Could not complete the product lookup.'));
    $stmt->close();
    $conn->close();
    exit;
}
$result = $stmt->get_result();
$product = $result ? $result->fetch_assoc() : null;
echo json_encode($product ? array($product) : array());
$stmt->close();
$conn->close();
?>