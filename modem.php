<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>USSD Command Interface</title>
    <script src="javascripts/3.5.1/jquery.min.js"></script>
</head>
<body>

<h2>Send USSD Command</h2>
<form id="ussdForm">
    <label for="ussd_code">USSD Code:</label>
    <input type="text" id="ussd_code" name="ussd_code" placeholder="e.g., *123#" required>
    <button type="submit">Send</button>
</form>

<div id="response">
    <!-- This div will display the modem's response -->
</div>

<script>
$(document).ready(function(){
    $('#ussdForm').submit(function(event){
        event.preventDefault();
        var ussd_code = $('#ussd_code').val();

        $.ajax({
            url: 'Ajax/send_ussd.php',  // PHP file that sends the command
            type: 'POST',
            data: { ussd_code: ussd_code },
            success: function(response) {
                $('#response').html("<strong>Modem Response:</strong><br>" + response);
            },
            error: function(xhr, status, error) {
                $('#response').html("Error: " + error);
            }
        });
    });
});
</script>

</body>
</html>
