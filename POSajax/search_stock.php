<?php
define('LIKE','LIKE');
session_write_close(); //in case a previous session is not closed
session_name('smartlimserp');
session_start();

include('../config.php');
// Create connection
$conn = new mysqli($host , $DBUser, $DBPassword,$_SESSION['DatabaseName']);
// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed']));
}

$sql = "SELECT 
         `stockmaster`.`barcode`,
         `stockmaster`.`descrip` as description,
          IFNULL(`vatcategory`.`vat`,0) as vat,
          IFNULL(`stockmaster`.`sellingprice`,0) as unit_price
     FROM `stockmaster` 
     left join `inventorypostinggroup` on `stockmaster`.`postinggroup`=`inventorypostinggroup`.`code`
     left join `vatcategory` on `inventorypostinggroup`.`vatcategory`=`vatcategory`.`vatc`";


$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

header('Content-Type: application/json');
$product = array();
while ($row = $result->fetch_assoc()) {
    $product[] = $row;
}

echo json_encode($product);
$stmt->close();
$conn->close();
?>

