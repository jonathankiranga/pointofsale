<?php
define('LIKE','LIKE');
session_write_close(); //in case a previous session is not closed
session_name('smartlimserp');
session_start();

header('Content-Type: application/json; charset=utf-8');
if (empty($_SESSION['DatabaseName'])) {
    http_response_code(401);
    echo json_encode(array('error' => 'POS session expired. Sign in again.'));
    exit;
}

include('../config.php');
// Create connection
$conn = new mysqli($host , $DBUser, $DBPassword,$_SESSION['DatabaseName']);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(array('error' => 'Database connection failed.'));
    exit;
}

$barcode = isset($_GET['barcode']) ? trim((string)$_GET['barcode']) : '';
if ($barcode === '') {
    http_response_code(400);
    echo json_encode(array('error' => 'A barcode is required.'));
    $conn->close();
    exit;
}
$sql = "SELECT 
         `stockmaster`.`barcode`,
         `stockmaster`.`descrip` as description,
         IFNULL(`vatcategory`.`vat`,0) as vat,
         IFNULL(`stockmaster`.`sellingprice`,0) as unit_price
     FROM `stockmaster` 
     left join `inventorypostinggroup` on `stockmaster`.`postinggroup`=`inventorypostinggroup`.`code`
     left join `vatcategory` on `inventorypostinggroup`.`vatcategory`=`vatcategory`.`vatc` 
      where `stockmaster`.`barcode` = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(array('error' => 'Could not prepare the product lookup.'));
    $conn->close();
    exit;
}
$stmt->bind_param("s", $barcode);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(array('error' => 'Could not execute the product lookup.'));
    $stmt->close();
    $conn->close();
    exit;
}
$result = $stmt->get_result();

$product=array();
if ($result->num_rows > 0) {
    $product[] = $result->fetch_assoc();
} 

echo json_encode($product);

$stmt->close();
$conn->close();
?>

