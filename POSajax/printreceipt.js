function collectReceiptData(paymentMethod, mpesaPhone, mpesaReference) {
    const items = [];

    $('#sales-table tbody tr').each(function() {
        const $row = $(this);
        const barcode = $row.find('td').eq(0).text().trim();
        const itemcode = String($row.attr('data-itemcode') || '').trim();
        const description = $row.find('td').eq(1).text().trim();

        if (barcode && itemcode && description && !$row.hasClass('blank-row')) {
            const quantity = parseFloat($row.find('.quantity').text()) || 0;
            const unitPrice = parseFloat($row.find('.unit-price').text()) || 0;
            const vatRate = parseFloat($row.find('.vat-per').text()) || 0;
            const vatAmount = parseFloat($row.find('.VAT-total').text()) || 0;
            const lineTotal = parseFloat($row.find('.line-total').text()) || 0;

            if (!Number.isFinite(quantity) || quantity <= 0 || !Number.isFinite(unitPrice) ||
                unitPrice < 0 || !Number.isFinite(lineTotal) || lineTotal < 0 ||
                !Number.isFinite(vatAmount) || vatAmount < 0) {
                throw new Error('One or more sales lines contain invalid quantities or prices.');
            }

            items.push({
                barcode: barcode,
                itemcode: itemcode,
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
        throw new Error('There are no sale items to complete.');
    }

    const total = parseFloat($('#total-amount').text()) || 0;
    const totalVAT = parseFloat($('#tax-amount').text()) || 0;
    const cashValue = parseFloat($('#customer-cash').val());
    const changeValue = parseFloat($('#customer-change').text());

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
        mpesaReference: mpesaReference || '',
        printerId: window.POS_PRINTER_ID || '',
        printedAt: new Date().toISOString()
    };
}

function printReceipt(paymentMethod, mpesaPhone, mpesaReference) {
    if (window.POS_PRINT_IN_PROGRESS) {
        showCustomDialog('A sale is already being saved. Please wait.');
        return;
    }

    let receiptData;
    try {
        receiptData = collectReceiptData(paymentMethod, mpesaPhone, mpesaReference);
    } catch (error) {
        showCustomDialog(error.message);
        return;
    }

    window.POS_PRINT_IN_PROGRESS = true;

    // Saving the sale is the transaction. Printing is queued by PHP after
    // the accounting transaction commits; the local agent polls over HTTPS.
    SaveReceipt(receiptData, function(saveResponse) {
        receiptData.total = Number(saveResponse.total);
        receiptData.totalVAT = Number(saveResponse.total_vat);
        if (Array.isArray(saveResponse.items)) { receiptData.items = saveResponse.items; }
        completeSaleSave(receiptData);
        window.POS_PRINT_IN_PROGRESS = false;

        if (saveResponse.print_queued) {
            showCustomDialog('Sale completed and saved. Receipt queued for the local printer.');
        } else {
            showCustomDialog('Sale completed and saved. The print queue is not available. Please check the POS print-agent setup.');
        }
    });
}

function SaveReceipt(receiptData, onSaved) {
    $.ajax({
        url: 'POSajax/save-receipt.php',
        type: 'POST',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        headers: { 'X-POS-FormID': window.POS_FORM_ID || '' },
        data: JSON.stringify(receiptData),
        timeout: 20000
    }).done(function(response) {
        if (response && response.success) {
            if (typeof onSaved === 'function') {
                onSaved(response);
            }
        } else {
            window.POS_PRINT_IN_PROGRESS = false;
            showCustomDialog((response && response.message)
                ? response.message
                : 'The sale could not be saved. No receipt was queued for printing.');
        }
    }).fail(function(jqXHR) {
        window.POS_PRINT_IN_PROGRESS = false;
        let message = 'The sale could not be saved. No receipt was queued for printing.';
        if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
            message += ' ' + jqXHR.responseJSON.message;
        }
        showCustomDialog(message);
    });
}

function completeSaleSave(receiptData) {
    clearTable();
    $('#customer-cash').val('');
    $('#customer-change').text('0.00');
    $('#customer-mpesa-no').val('');
    $('#customer-mpesa-reference').val('');
    $('#cashsaleno').text('CS-' + Date.now());
    closeModal();
}
