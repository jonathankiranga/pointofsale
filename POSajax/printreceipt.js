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

            if (!Number.isFinite(quantity) || quantity <= 0 || !Number.isFinite(unitPrice) ||
                unitPrice < 0 || !Number.isFinite(lineTotal) || lineTotal < 0 ||
                !Number.isFinite(vatAmount) || vatAmount < 0) {
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
        printerId: window.POS_PRINTER_ID || '',
        printedAt: new Date().toISOString()
    };
}

function printReceipt(paymentMethod, mpesaPhone) {
    if (window.POS_PRINT_IN_PROGRESS) {
        showCustomDialog('A sale is already being saved. Please wait.');
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

    window.POS_PRINT_IN_PROGRESS = true;

    // Saving the sale is the transaction. Printing is an optional follow-up
    // and must never prevent the sale from being committed.
    SaveReceipt(receiptData, function(saveResponse) {
        completeSaleSave(receiptData);
        window.POS_PRINT_IN_PROGRESS = false;

        if (!printUrl) {
            showCustomDialog('Sale completed and saved. Receipt printing is not configured.');
            return;
        }

        // A printer outage, missing printer or print-service error must not
        // reverse or block the sale that has already been saved.
        $.ajax({
            url: printUrl,
            type: 'POST',
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            headers: { 'X-Print-Token': window.POS_PRINT_TOKEN || '' },
            data: JSON.stringify(receiptData),
            timeout: 20000
        }).done(function(response) {
            if (response && response.success) {
                if (receiptData.paymentMethod === 'CASH') {
                    openCashDrawer();
                }
                const documentNo = response.documentno || receiptData.docno;
                showCustomDialog('Sale completed and saved. Receipt printed successfully: ' + documentNo);
            } else {
                showCustomDialog('Sale completed and saved. Receipt could not be printed: ' +
                    ((response && response.message) ? response.message : 'Printer did not confirm the receipt.'));
            }
        }).fail(function() {
            showCustomDialog('Sale completed and saved. The printer is unavailable; you can print the receipt later.');
        });
    });
}

function SaveReceipt(receiptData, onSaved) {
    $.ajax({
        url: 'POSajax/save-receipt.php',
        type: 'POST',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
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
                : 'The sale could not be saved. No receipt was sent to the printer.');
        }
    }).fail(function(jqXHR) {
        window.POS_PRINT_IN_PROGRESS = false;
        let message = 'The sale could not be saved. No receipt was sent to the printer.';
        if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
            message += ' ' + jqXHR.responseJSON.message;
        }
        showCustomDialog(message);
    });
}

function completeSaleSave(receiptData) {
    // Reset the terminal immediately after the database confirms the sale.
    // Printer availability is deliberately not part of this completion step.
    clearTable();
    $('#customer-cash').val('');
    $('#customer-change').text('0.00');
    $('#customer-mpesa-no').val('');
    $('#cashsaleno').text('CS-' + Date.now());
    closeModal();
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
        console.warn('The receipt was saved and printed, but the cash drawer command could not be sent.');
    });
}
