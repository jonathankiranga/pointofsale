function collectReceiptData(paymentMethod, mpesaPhone) {
    const items = [];

    $('#sales-table tbody tr').each(function() {
        const $row = $(this);
        const barcode = $row.find('td').eq(0).text().trim();
        const description = $row.find('td').eq(1).text().trim();

        if (barcode && description && !$row.hasClass('blank-row')) {
            const quantity = parseFloat($row.find('.quantity').text()) || 0;
            const unitPrice = parseFloat($row.find('.unit-price').text()) || 0;
            const vatRate = parseFloat($row.find('.vat-per').text()) || 0;
            const vatAmount = parseFloat($row.find('.VAT-total').text()) || 0;
            const lineTotal = parseFloat($row.find('.line-total').text()) || 0;

            if (quantity <= 0 || unitPrice < 0 || lineTotal < 0) {
                throw new Error('One or more sales lines contain invalid quantities or prices.');
            }

            items.push({
                barcode: barcode,
                description: description,
                quantity: quantity,
                unitPrice: unitPrice,
                vatRate: vatRate,
                vatAmount: vatAmount,
                lineTotal: lineTotal
            });
        }
    });

    if (!items.length) {
        throw new Error('There are no sale items to print.');
    }

    const total = parseFloat($('#total-amount').text()) || 0;
    const totalVAT = parseFloat($('#tax-amount').text()) || 0;
    const cashValue = parseFloat($('#customer-cash').val());
    const changeText = $('#customer-change').text().trim();
    const changeValue = parseFloat(changeText);

    return {
        company: $('#company').text().trim(),
        userlog: $('#userlog').text().trim(),
        docno: $('#cashsaleno').text().trim(),
        items: items,
        total: total,
        totalVAT: totalVAT,
        cash: Number.isFinite(cashValue) ? cashValue : total,
        change: Number.isFinite(changeValue) ? changeValue : 0,
        currency: 'KES',
        paymentMethod: paymentMethod || 'CASH',
        mpesaPhone: mpesaPhone || '',
        printedAt: new Date().toISOString()
    };
}

function printReceipt(paymentMethod, mpesaPhone) {
    if (window.POS_PRINT_IN_PROGRESS) {
        showCustomDialog('A receipt operation is already in progress. Please wait.');
        return;
    }

    let receiptData;

    try {
        receiptData = collectReceiptData(paymentMethod, mpesaPhone);
    } catch (error) {
        showCustomDialog(error.message);
        return;
    }

    const printUrl = window.POS_PRINT_URL;
    if (!printUrl) {
        showCustomDialog('Receipt printing service URL is not configured in config.php.');
        return;
    }

    window.POS_PRINT_IN_PROGRESS = true;

    $.ajax({
        url: printUrl,
        type: 'POST',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        headers: {
            'X-Print-Token': window.POS_PRINT_TOKEN || ''
        },
        data: JSON.stringify(receiptData),
        timeout: 20000
    }).done(function(response) {
        if (response && response.success) {
            SaveReceipt(receiptData);
        } else {
            window.POS_PRINT_IN_PROGRESS = false;
            showCustomDialog((response && response.message) ? response.message : 'The printer did not confirm the receipt.');
        }
    }).fail(function(jqXHR) {
        // Do not automatically retry through PHP: a timeout can occur after
        // the printer has already received the job, and a retry could print twice.
        const detail = jqXHR.responseJSON && jqXHR.responseJSON.message
            ? ' ' + jqXHR.responseJSON.message
            : '';
        showCustomDialog('The printer service did not confirm the result. Check the printer before retrying to avoid a duplicate receipt.' + detail);
    });
}

function fallbackToCurl(receiptData, browserError) {
    $.ajax({
        url: 'POSajax/fallback.php',
        type: 'POST',
        dataType: 'json',
        contentType: 'application/json; charset=utf-8',
        data: JSON.stringify(receiptData),
        timeout: 25000
    }).done(function(response) {
        if (response && response.success) {
            SaveReceipt(receiptData);
        } else {
            showCustomDialog((response && response.message) ? response.message : 'Receipt printing failed. Check that the Node.js service and printer are running.');
        }
    }).fail(function() {
        showCustomDialog('Could not contact the Node.js printing service. Browser error: ' + browserError + '. Check the printer service address and network connection.');
    });
}

function SaveReceipt(receiptData) {
    // Save only after the printer has confirmed the print operation.
    // Include the document number so PHP can identify the receipt.
    $.ajax({
        url: 'POSajax/save-receipt.php',
        type: 'POST',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        data: JSON.stringify(receiptData),
        timeout: 20000
    }).done(function(response) {
        if (response && response.success) {
            clearTable();
            $('#customer-cash').val('');
            $('#customer-change').text('0.00');
            $('#cashsaleno').text('CS-' + Date.now());
            closeModal();
            if (receiptData.paymentMethod === 'CASH') {
                openCashDrawer();
            }
            window.POS_PRINT_IN_PROGRESS = false;
            showCustomDialog('Receipt printed and sale saved successfully. Receipt: ' + response.documentno);
        } else {
            showCustomDialog((response && response.message) ? response.message : 'Receipt printed, but the sale could not be saved. Do not print again; contact the administrator.');
        }
    }).fail(function(jqXHR) {
        let message = 'Receipt printed, but saving the sale failed. Do not print again until the sale is checked.';
        if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
            message += ' ' + jqXHR.responseJSON.message;
        }
        showCustomDialog(message);
    });
}

function openCashDrawer() {
    const drawerUrl = String(window.POS_PRINT_URL || '').replace('/print-receipt', '/open-drawer');
    if (!drawerUrl || drawerUrl === window.POS_PRINT_URL) {
        return;
    }

    $.ajax({
        url: drawerUrl,
        type: 'POST',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        headers: { 'X-Print-Token': window.POS_PRINT_TOKEN || '' },
        data: JSON.stringify({}),
        timeout: 10000
    }).fail(function() {
        console.warn('The receipt was saved, but the cash drawer command could not be sent.');
    });
}
