<?php
$receiptData = json_decode(file_get_contents('php://input'), true);


define('LIKE','LIKE');
session_write_close(); //in case a previous session is not closed
session_name('smartlimserp');
session_start();

include('../config.php');
include('../includes/DateFunctions.inc');
include('../includes/MiscFunctions.php');
include('../includes/SQL_CommonFunctions.inc');
include('../Ajax/DatafunctionsForAjax.inc');
 

// Extract data
$items = $receiptData['items'];
$total = $receiptData['total'];
$cash  = $receiptData['cash'];
$change = $receiptData['change'];
$docno = $receiptData['docno'];

$query = "SELECT SYSDATE() AS date";
$result = $mysqli->query($query);
$row = $result->fetch_assoc();
$currentDate = $row['date'];

$_POST['date'] = ConvertSQLDate($currentDate);
$periodno = GetPeriod($_POST['date'],$db,true);
$JOURNAL  = GetNextTransNo(0,$db);

$stmt = $mysqli->prepare("INSERT INTO `SalesHeader`
    (`documenttype`,`documentno`,`docdate`,`status`,`userid`,`period`,`total`,`cash`,`change_amount`)
     VALUES ('1',? ,? ,1 ,? ,? ,?, ?, ?)") ;

// Insert into 'sales_receipts' table
$stmt->bind_param("ssssddd",$docno,$currentDate,$_SESSION['UserID'],$periodno, $total, $cash, $change);
$stmt->execute();
//$receipt_id = $stmt->insert_id; // Get the last inserted receipt ID
$stmt->close();

// Insert each item into 'sales_items' table
$stmt = $mysqli->prepare("INSERT INTO SalesLine (`documenttype`,`documentno`, `barcode`,"
        . " `description`, `Quantity`, `UnitPrice`,`vatamount`, `invoiceamount`) "
        . "VALUES ('1',?, ?, ?, ?, ?, ?, ?)");
foreach ($items as $item) {
    $stmt->bind_param("sssiddd",$docno, $item['barcode'],$item['description'], $item['quantity'], $item['unitPrice'], $item['linevat'], $item['lineTotal']);
    $stmt->execute();
}
$stmt->close();


$response = array("success" => true, "message" => "Receipt saved successfully");
echo json_encode($response);
?>
