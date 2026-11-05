<?php
header('Content-Type: application/json; charset=utf-8');
session_name('smartlimserp');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (empty($_SESSION['DatabaseName'])) {
    http_response_code(401);
    echo json_encode(array('error' => 'POS session expired. Please sign in again.'));
    exit;
}
include('../config.php');
$conn = new mysqli($host, $DBUser, $DBPassword, $_SESSION['DatabaseName']);
if ($conn->connect_error) {
    error_log('POS product catalogue connection failed: ' . $conn->connect_error);
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
        ORDER BY stockmaster.descrip";
$stmt = $conn->prepare($sql);
if (!$stmt || !$stmt->execute()) {
    error_log('POS product catalogue query failed: ' . $conn->error);
    http_response_code(500);
    echo json_encode(array('error' => 'Could not load the product catalogue.'));
    if ($stmt) { $stmt->close(); }
    $conn->close();
    exit;
}
$result = $stmt->get_result();
$products = $result ? $result->fetch_all(MYSQLI_ASSOC) : array();
echo json_encode($products);
$stmt->close();
$conn->close();
?>