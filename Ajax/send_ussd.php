<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ussd_code'])) {
    $comPort = "COM15"; // Adjust to your modem’s COM port
    $ussdCode = $_POST['ussd_code'];

    // Open the serial port
    $serial = fopen($comPort, "w+");
    if (!$serial) {
        die("Error: Could not open port $comPort");
    }

    // Configure the COM port
    exec("mode $comPort: BAUD=9600 PARITY=N data=8 stop=1 xon=off");

    // Send the AT command to initiate communication
    fwrite($serial, "AT\r");
    usleep(500000);  // Delay for modem response

    // Send the USSD command
    fwrite($serial, "AT+CUSD=1,\"$ussdCode\",15\r");
    usleep(500000);  // Delay to receive the response

    // Read the response
    $response = fread($serial, 128);
    
    // Close the port
    fclose($serial);

    // Send the response back to the AJAX call
    echo $response;
}
?>
