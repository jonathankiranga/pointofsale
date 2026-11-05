<?php
// Read the latest modem response from the file
$response = file_get_contents('modem_response.txt');

// Return the response to the AJAX call
echo $response;
