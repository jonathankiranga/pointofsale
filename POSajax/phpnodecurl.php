<?php
error_reporting(E_ALL);
// Receipt data
$data = [
    "items" => [
        ["barcode" => "123456", "description" => "Product 1", "quantity" => 2, "unitPrice" => 50, "lineTotal" => 100],
        ["barcode" => "789012", "description" => "Product 2", "quantity" => 1, "unitPrice" => 150, "lineTotal" => 150]
    ],
    "total" => 250,
    "cash" => 300,
    "change" => 50
];

// Convert data to JSON
$jsonData = json_encode($data);

// Send a POST request to the Node.js server
$ch = curl_init('http://localhost:3000/print-receipt');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 

// Execute request and get response
$response = curl_exec($ch);
curl_close($ch);

// Handle the response
echo $response;
?>
