<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>USSD Command Interface</title>
    <script src="javascripts/3.5.1/jquery.min.js"></script>
</head>
<body>
    <h1>Send USSD Command</h1>
    <form id="ussdForm">
        <label for="ussdCode">USSD Code:</label>
        <input type="text" id="ussdCode" name="ussdCode" placeholder="*123#">
        <button type="submit">Send</button>
    </form>
    <div id="response">
        <h2>Modem Response:</h2>
        <pre id="responseData"></pre>
    </div>

    <script>
        $(document).ready(function() {
            // Listen for form submission
            $('#ussdForm').on('submit', function(e) {
                e.preventDefault();
                let ussdCode = $('#ussdCode').val();

                // Send the USSD command via AJAX
                $.ajax({
                    url: 'Ajax/send_ussd2.php',
                    type: 'POST',
                    data: { ussdCode: ussdCode },
                    success: function(data) {
                        // Display the response
                        $('#responseData').text(data);
                    }
                });
            });

            // Poll the modem response
            function pollModem() {
                $.ajax({
                    url: 'Ajax/poll_modem.php',
                    type: 'GET',
                    success: function(data) {
                        $('#responseData').text(data);
                    },
                    complete: function() {
                        // Set up the next poll
                        setTimeout(pollModem, 2000);
                    }
                });
            }

            // Start polling the modem for responses
            pollModem();
        });
    </script>
</body>
</html>
