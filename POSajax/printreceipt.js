function printReceipt() {
    const company  = $('#company').text();
    const userlog  = $('#userlog').text();
    const cashsaleno = $('cashsaleno').text();
    const totalVAT = $('#tax-amount').text();
    const total   = $('#total-amount').text();
    const cash    = $('#customer-cash').val();
    const change  = $('#customer-change').text();
    const reciept = $('#cashsaleno').text();
    const items = [];

    $('#sales-table tbody tr').each(function() {
        // Fetch values from each row
        const barcode = $(this).find('td').eq(0).text().trim();
        const description = $(this).find('td').eq(1).text().trim();
        const quantity = $(this).find('.quantity').text().trim();
        const unitPrice = $(this).find('.unit-price').text().trim();
        const lineTotal = $(this).find('.line-total').text().trim();
        const linevat   = $(this).find('.vat-per').text().trim();

        if (barcode !== '' && description !== '') {
            // Add product details to the items array
            items.push({
                barcode: barcode,
                description: description,
                quantity: quantity,
                unitPrice: unitPrice,
                linevat:linevat,
                lineTotal: lineTotal
            });
     
        }
    });

    // Send receipt data to the server for printing
    const receiptData = {
        company:company,
        cashsaleno:cashsaleno,
        userlog:userlog,
        docno: reciept,
        items: items,
        total: total,
        totalVAT:totalVAT,
        cash: cash,
        change: change
    };

    
    // First attempt with $.post
    const printUrl = window.POS_PRINT_URL;
    if (!printUrl) {
        alert('Receipt printing service URL is not configured. Set $POSPrintUrl in config.php.');
        return;
    }

    $.post(printUrl, receiptData, function(response) {
        if (response.success) {
            SaveReceipt();
        } else {
            alert('Error printing receipt.');
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.log("JavaScript POST failed, falling back to PHP cURL. Error:", textStatus, errorThrown);
        // If $.post fails, fall back to PHP using cURL
        fallbackToCurl(receiptData);
    });
    
    
    
}

function fallbackToCurl(receiptData) {
    $.ajax({
        url: 'fallback.php',  // PHP file that will handle the cURL request
        type: 'POST',
        data: { receiptData: JSON.stringify(receiptData) },
        success: function(response) {
            if (response.success) {
                SaveReceipt();  // Success, call SaveReceipt
            } else {
                alert('Error printing receipt via PHP cURL.');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            alert('Error during cURL fallback request: ' + textStatus);
        }
    });
}

function SaveReceipt() {
    const total = $('#total-amount').text();
    const cash = $('#customer-cash').val();
    const change = $('#customer-change').text();
     const items = [];

    $('#sales-table tbody tr').each(function() {
        // Fetch values from each row
        const barcode = $(this).find('td').eq(0).text().trim();
        const description = $(this).find('td').eq(1).text().trim();
        const quantity  = $(this).find('.quantity').text().trim();
        const unitPrice = $(this).find('.unit-price').text().trim();
        const lineTotal = $(this).find('.line-total').text().trim();
        const linevat   = $(this).find('.vat-per').text().trim();

        if (barcode !== '' && description !== '') {
            // Add product details to the items array
            items.push({
                barcode: barcode,
                description: description,
                quantity: quantity,
                unitPrice: unitPrice,
                linevat:linevat,
                lineTotal: lineTotal
            });
        }
    });

    // Prepare receipt data
    const receiptData = {
        items: items,
        total: parseFloat(total.replace('$', '')),
        cash: parseFloat(cash),
        change: parseFloat(change.replace('$', ''))
    };

    // Send receipt data to the server via AJAX to save in MySQL
    $.ajax({
        url: 'save-receipt.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(receiptData),
        success: function(response) {
            if (response.success) {
                clearTable();
            } else {
                alert('Error saving receipt.');
            }
        },
        error: function() {
            alert('Error connecting to server.');
        }
    });
}
