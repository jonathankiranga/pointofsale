<?php
// Decode the incoming JSON data
$receiptData = json_decode($_POST['receiptData'], true);

// Prepare cURL request to send the receipt data to the printing server
$ch = curl_init('http://localhost:3000/print-receipt');  // Endpoint for printing receipt
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen(json_encode($receiptData))
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($receiptData));

// Execute cURL and get the response
$response = curl_exec($ch);

if ($response === false) {
    // If cURL fails, send back a failure response
    echo json_encode(['success' => false, 'message' => 'Error printing receipt via cURL.']);
} else {
    // If cURL succeeds, return success to the JavaScript
    $responseDecoded = json_decode($response, true);
    if (isset($responseDecoded['success']) && $responseDecoded['success']) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error printing receipt.']);
    }
}

// Close cURL session
curl_close($ch);
?>
