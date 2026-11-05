<?php
define('LIKE','LIKE');
session_write_close(); //in case a previous session is not closed
session_name('smartlimserp');
session_start();

include('../config.php');
include('DatafunctionsForAjax.inc');

 //$mysqli = new mysqli($host,$DBUser,$DBPassword,$_SESSION['DatabaseName']);

$dsn = "mysql:host=$host;dbname=$DefaultDatabase;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $DBUser, $DBPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_GET['query'])) {
        $query = $_GET['query'];

        $stmt = $pdo->prepare("Select itemcode as code,customer as name,curr_cod as currency,IFNULL(salesman,'') as  salespersoncode from debtors WHERE customer LIKE ? LIMIT 10");
        $stmt->execute(['%' . $query . '%']);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($customers);
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(['message' => 'Query parameter is required']);
    }
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
}

