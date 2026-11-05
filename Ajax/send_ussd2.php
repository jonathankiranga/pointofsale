<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
$comPort = "COM15"; // Change to the actual COM port

// Additional error handling to catch fopen issues
$serial = @fopen($comPort, "w+");
if (!$serial) {
    die("Failed to open port $comPort: " . error_get_last()['message']);
}
// Capture the USSD code from the AJAX request
$ussdCode = $_POST['ussdCode'] ?? '';

if ($ussdCode) {
 
    // Open the serial port
    $serial = fopen($comPort, "w+");
    if (!$serial) {
        die("Failed to open port $comPort");
    }

    // Set the COM port configuration
    exec("mode $comPort: BAUD=9600 PARITY=N data=8 stop=1 xon=off");

    // Send the USSD command to the modem
    fwrite($serial, "AT+CUSD=1,\"$ussdCode\",15\r");
    usleep(500000); // Small delay to wait for a response

    // Read and store the response in a temporary file
    $response = fread($serial, 128);
    file_put_contents('modem_response.txt', $response);

    // Close the serial connection
    fclose($serial);

    // Send response back to the front end
    echo "Command sent: $ussdCode";
}
?>
