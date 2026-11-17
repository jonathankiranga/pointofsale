$(document).ready(function() {
    // Auto-focus the top barcode/search bar on load for instant supermarket scanning.
    $('#pos-barcode-scan').focus();

    function submitTopBarcodeScan() {
        const raw = $('#pos-barcode-scan').val().trim();
        if (!raw) { return; }
        fetchProduct(raw);
        $('#pos-barcode-scan').val('').focus();
    }

    $('#pos-barcode-scan').on('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            submitTopBarcodeScan();
        }
    });

    $('#pos-barcode-add').on('click', function() {
        submitTopBarcodeScan();
    });

    // One delegated handler supports dynamically added barcode cells.
    $('#sales-table tbody').on('keydown', '.barcode[contenteditable="true"]', function(event) {
        if (event.key !== 'Enter') { return; }
        event.preventDefault();
        const barcode = $(this).text().trim();
        if (barcode) { fetchProduct(barcode); }
    });

    // Supermarket keyboard shortcuts
    $(document).keydown(function(event) {
        if (event.target.isContentEditable && event.key === 'Enter') {
            event.preventDefault();
        }

        if (event.key === 'F2' && !event.ctrlKey) {
            event.preventDefault();
            $('#pos-barcode-scan').focus().select();
        }

        if (event.ctrlKey && event.key === 'F1') {
            event.preventDefault();
            openCheckoutModal();
        }

        if (event.ctrlKey && event.key === 'F2') {
            event.preventDefault();
            openCheckoutModal();
            handleCashPayment();
        }

        if (event.ctrlKey && event.key === 'F3') {
            event.preventDefault();
            openCheckoutModal();
            handleMpesaPayment();
        }

        if (event.key === 'Delete' && !$(event.target).is('input, textarea, [contenteditable="true"]')) {
            $('#sales-table tbody tr.selected').remove();
            updateLineTotal();
        }
    });

    function refreshTenderStatus() {
        const total = parseFloat($('#total-amount').text()) || 0;
        const cash = parseFloat($('#customer-cash').val());
        if (Number.isFinite(cash) && cash > 0) {
            const diff = cash - total;
            const statusText = diff >= 0
                ? 'Tendered: KES ' + cash.toFixed(2) + ' · Change: KES ' + diff.toFixed(2)
                : 'Tendered: KES ' + cash.toFixed(2) + ' · Balance: KES ' + Math.abs(diff).toFixed(2);
            $('#pos-tender-status').text(statusText).css('color', diff >= 0 ? '#12845b' : '#174ea6').show();
            $('#pos-tender-clear').show();
        } else {
            $('#pos-tender-status').hide().text('');
            $('#pos-tender-clear').hide();
        }
    }

    // Quick cash tender buttons add together so bills over 1,000 can use multiple notes.
    $(document).on('click', '.pos-tender-btn', function() {
        const total = parseFloat($('#total-amount').text()) || 0;
        if (total <= 0) {
            showCustomDialog('Add at least one product before tendering cash.');
            return;
        }
        const tender = $(this).attr('data-tender');
        const currentCash = parseFloat($('#customer-cash').val()) || 0;
        const cashAmount = (tender === 'exact') ? total : (currentCash + (parseFloat(tender) || 0));
        $('#customer-cash').val(cashAmount.toFixed(2)).trigger('input');
        refreshTenderStatus();
        if (cashAmount >= total) {
            openCheckoutModal();
        }
    });

    $(document).on('click', '#pos-tender-clear', function() {
        $('#customer-cash').val('').trigger('input');
        refreshTenderStatus();
    });

    // Cash payment is validated before the receipt is printed and saved.
    $('#pay-cash-btn').click(function() {
        handleCashPayment();
    });

    // M-Pesa requires the cashier to verify payment before printing.
    $('#pay-mpesa-btn').click(function() {
        handleMpesaPayment();
    });

    $('#process-mpesa-btn').click(function(event) {
        event.preventDefault();
        const phone = $('#customer-mpesa-no').val().trim();
        const reference = $('#customer-mpesa-reference').val().trim().toUpperCase();
        if (!/^254[17][0-9]{8}$/.test(phone)) {
            showCustomDialog('Enter a valid Kenyan M-Pesa number in 2547XXXXXXXX or 2541XXXXXXXX format.');
            return;
        }
        if (!/^[A-Z0-9]{8,20}$/.test(reference)) {
            showCustomDialog('Enter the M-Pesa transaction code from the payment confirmation message.');
            return;
        }
        if (!window.confirm('Have you received and verified this M-Pesa payment in your M-Pesa confirmation message or statement?')) {
            return;
        }
        printReceipt('MPESA', phone, reference);
    });

    $('#show-bill-link').on('click', function(event) {
        event.preventDefault();
        openCheckoutModal();
    });

    $(document).on('input', '.quantity', function() {
        const $row = $(this).closest('tr');
        const unitPrice = parseFloat($row.find('.unit-price').text()) || 0;
        const quantity = parseFloat($(this).text()) || 0;
        const lineTotal = unitPrice * quantity;
        const vatper = parseFloat($row.find('.vat-per').text()) || 0;

        // Respect the configured ERP sales posting group's VAT-inclusive setting.
        const vatInclusive = Number(window.POS_VAT_INCLUSIVE) === 1;
        const vatAmount = vatInclusive
            ? (vatper > 0 ? lineTotal - (lineTotal / (1 + (vatper / 100))) : 0)
            : lineTotal * (vatper / 100);
        const grossTotal = vatInclusive ? lineTotal : lineTotal + vatAmount;

        $row.find('.VAT-total').text(vatAmount.toFixed(2));
        $row.find('.line-total').text(grossTotal.toFixed(2));

        updateLineTotal();
    });

    $(document).on('click', '#delete-selected-link', function() {
        $('#sales-table tbody tr.selected').remove();
        updateLineTotal();
    });

    $(document).on('click', '#sales-table tbody tr', function() {
        if (!$(this).hasClass('blank-row')) {
            $('#sales-table tbody tr').removeClass('selected');
            $(this).addClass('selected');
        }
        updateLineTotal();
    });

    $(document).on('input', '#customer-cash', function() {
        const cash = parseFloat($(this).val());
        const total = parseFloat($('#total-amount').text()) || 0;
        const change = (cash - total).toFixed(2);
        $('#customer-change').text(Number.isFinite(cash) && cash >= total ? change : 'Insufficient Cash');
        if (Number.isFinite(cash) && cash >= total) {
            listChangeDenominations(cash - total);
            $('#denomination-list-container').show();
        } else {
            $('#denomination-list').empty();
            $('#denomination-list-container').hide();
        }
    });

    $('#find-items-link').on('click', function(event) {
        event.preventDefault();
        $('#search-modal').show();
        $('#search-input').focus();
    });

    $('.checkout-modal-close').on('click', function(event) {
        event.preventDefault();
        $('.modal').hide();
        $('#pos-barcode-scan').focus();
    });

    $('#save-transaction-link').on('click', function(event) {
        event.preventDefault();
        saveSalesDataWithReference();
    });

    $('#recall-transaction-link').on('click', function() {
        $('#recall-modal').show();
    });

    $('#recall-button').on('click', function() {
        const selectedId = $('#reference-list').val();
        if (selectedId) {
            recallSalesDataByReference(selectedId);
            $('#recall-modal').hide();
        } else {
            showCustomDialog('Please select a reference to recall.');
        }
    });

    $('#dialog-ok-btn').on('click', function(event) {
        event.preventDefault();
        hideCustomDialog();
    });
});

function showCustomDialog(message) {
    window.POS_LAST_SHIFT_REPORT = '';
    $('#dialog-print-btn').hide();
    $('#dialog-message').text(message);
    $('#custom-dialog').fadeIn();
}

function hideCustomDialog() {
    $('#custom-dialog').fadeOut();
    $('#pos-barcode-scan').focus();
}

function listChangeDenominations(change) {
    const denominations = [1000, 500, 200, 100, 50, 20, 10, 5, 1];
    let remainingChange = Math.floor(change);
    let denominationCount = {};

    denominations.forEach(denomination => {
        if (remainingChange >= denomination) {
            denominationCount[denomination] = Math.floor(remainingChange / denomination);
            remainingChange -= denominationCount[denomination] * denomination;
        }
    });

    let denominationHtml = '<ul>';
    for (const [denomination, count] of Object.entries(denominationCount)) {
        denominationHtml += `<li>${count} x KES ${denomination}</li>`;
    }
    denominationHtml += '</ul>';
    $('#denomination-list').html(denominationHtml);
}

function restoreSalesData() {
    const savedData = localStorage.getItem('salesData');
    if (savedData) {
        const receiptData = JSON.parse(savedData);
        $('#total-amount').text(receiptData.total);
        $('#customer-cash').val(receiptData.cash);
        $('#customer-change').text(receiptData.change);
        const tableBody = document.querySelector('#sales-table tbody');
        tableBody.innerHTML = '';

        receiptData.items.forEach(function(item) {
            const newRow = `<tr class="selectable" data-barcode="${item.barcode}" data-itemcode="${item.itemcode || ''}">
                <td class="barcode">${item.barcode}</td>
                <td class="description">${item.description}</td>
                <td contenteditable="true" class="quantity">${item.quantity}</td>
                <td class="unit-price">${item.unitPrice}</td>
                <td class="vat-per">${item.vatper}</td>
                <td class="VAT-total">${item.VATtotal}</td>
                <td class="line-total">${item.lineTotal}</td>
            </tr>`;
            tableBody.insertAdjacentHTML('beforeend', newRow);
        });
        updateLineTotal();

        localStorage.removeItem('salesData');
        if (document.querySelector('#sales-table tbody tr.blank-row') === null) {
            addNewBlankRow();
        }
    }
}

function saveSalesData() {
    const total = $('#total-amount').text();
    const cash = $('#customer-cash').val();
    const change = $('#customer-change').text();
    const items = [];
    $('#sales-table tbody tr').each(function() {
        const barcode = $(this).find('td').eq(0).text().trim();
        const itemcode = String($(this).attr('data-itemcode') || '').trim();
        const description = $(this).find('td').eq(1).text().trim();
        const quantity = $(this).find('.quantity').text().trim();
        const unitPrice = $(this).find('.unit-price').text().trim();
        const vatper = $(this).find('.vat-per').text().trim();
        const VATtotal = $(this).find('.VAT-total').text().trim();
        const lineTotal = $(this).find('.line-total').text().trim();

        if (barcode !== '' && description !== '') {
            items.push({
                barcode: barcode,
                itemcode: itemcode,
                description: description,
                quantity: quantity,
                unitPrice: unitPrice,
                vatper: vatper,
                VATtotal: VATtotal,
                lineTotal: lineTotal
            });
        }
    });

    localStorage.setItem('salesData', JSON.stringify({
        items: items,
        total: total,
        cash: cash,
        change: change
    }));
}

function saveSalesDataWithReference() {
    const total = $('#total-amount').text();
    const cash = $('#customer-cash').val();
    const change = $('#customer-change').text();
    const items = [];

    $('#sales-table tbody tr').each(function() {
        const barcode = $(this).find('td').eq(0).text().trim();
        const itemcode = String($(this).attr('data-itemcode') || '').trim();
        const description = $(this).find('td').eq(1).text().trim();
        const quantity = $(this).find('.quantity').text().trim();
        const unitPrice = $(this).find('.unit-price').text().trim();
        const vatper = $(this).find('.vat-per').text().trim();
        const VATtotal = $(this).find('.VAT-total').text().trim();
        const lineTotal = $(this).find('.line-total').text().trim();

        if (barcode !== '' && description !== '') {
            items.push({
                barcode: barcode,
                itemcode: itemcode,
                description: description,
                quantity: quantity,
                unitPrice: unitPrice,
                vatper: vatper,
                VATtotal: VATtotal,
                lineTotal: lineTotal
            });
        }
    });

    if (!items.length) {
        showCustomDialog('Add at least one item before holding a sale.');
        return;
    }

    const referenceNumber = `REF-${Date.now()}`;
    let savedTransactions = JSON.parse(localStorage.getItem('savedTransactions')) || {};
    savedTransactions[referenceNumber] = { items: items, total: total, cash: cash, change: change };
    localStorage.setItem('savedTransactions', JSON.stringify(savedTransactions));

    $('#reference-list').append(`<option value="${referenceNumber}">${referenceNumber}</option>`);
    showCustomDialog(`Sale held with reference: ${referenceNumber}`);
    clearTable();
}

function recallSalesDataByReference(referenceNumber) {
    const savedTransactions = JSON.parse(localStorage.getItem('savedTransactions')) || {};
    const receiptData = savedTransactions[referenceNumber];

    if (receiptData) {
        $('#total-amount').text(receiptData.total);
        $('#customer-cash').val(receiptData.cash);
        $('#customer-change').text(receiptData.change);
        const tableBody = document.querySelector('#sales-table tbody');
        tableBody.innerHTML = '';

        receiptData.items.forEach(function(item) {
            const newRow = `
                <tr class="selectable" data-barcode="${item.barcode}" data-itemcode="${item.itemcode || ''}">
                    <td class="barcode">${item.barcode}</td>
                    <td class="description">${item.description}</td>
                    <td contenteditable="true" class="quantity">${item.quantity}</td>
                    <td class="unit-price">${item.unitPrice}</td>
                    <td class="vat-per">${item.vatper}</td>
                    <td class="VAT-total">${item.VATtotal}</td>
                    <td class="line-total">${item.lineTotal}</td>
                </tr>`;
            tableBody.insertAdjacentHTML('beforeend', newRow);
        });
        updateLineTotal();

        delete savedTransactions[referenceNumber];
        localStorage.setItem('savedTransactions', JSON.stringify(savedTransactions));
        fillAnySavedDataOnLoading();
        showCustomDialog(`Data for reference ${referenceNumber} loaded successfully!`);
    } else {
        showCustomDialog('No data found for the selected reference.');
    }

    if (document.querySelector('#sales-table tbody tr.blank-row') === null) {
        addNewBlankRow();
    }
}

function openCheckoutModal() {
    const totalAmount = $('#total-amount').text();
    $('#checkout-total').text(totalAmount);
    $('#checkout-modal').show();
    $('#customer-cash').focus();
}

function addNewBlankRow() {
    const newBlankRow = `<tr class="blank-row">
        <td class="barcode" contenteditable="true"></td>
        <td class="description"></td>
        <td class="quantity" contenteditable="true"></td>
        <td class="unit-price">0.00</td>
        <td class="vat-per"></td>
        <td class="VAT-total">0.00</td>
        <td class="line-total">0.00</td>
    </tr>`;

    const tableBody = document.querySelector('#sales-table tbody');
    tableBody.insertAdjacentHTML('beforeend', newBlankRow);
}

function clearTable() {
    const tableBody = document.querySelector('#sales-table tbody');
    tableBody.innerHTML = '';
    $('#customer-cash').val('');
    $('#customer-change').text('0.00');
    $('#pos-tender-status').hide().text('');
    $('#pos-tender-clear').hide();
    addNewBlankRow();
    updateLineTotal();
}

// Supermarket behavior: if the scanned item is already in the cart, increment quantity by 1.
function addOrIncrementProductRow(product) {
    if (!product || !product.itemcode) { return; }
    const existingRow = $('#sales-table tbody tr.selectable[data-itemcode="' + product.itemcode + '"]').first();
    if (existingRow.length) {
        const $qty = existingRow.find('.quantity');
        const nextQty = (parseFloat($qty.text()) || 0) + 1;
        $qty.text(nextQty).trigger('input');
        $('#sales-table tbody tr').removeClass('selected');
        existingRow.addClass('selected');
        return;
    }

    const blankRow = $('#sales-table tbody tr.blank-row');
    if (!blankRow.length) { addNewBlankRow(); }
    const targetRow = $('#sales-table tbody tr.blank-row').first();
    const vatper = parseFloat(product.vat) || 0;
    const unitPrice = parseFloat(product.unit_price) || 0;
    const vatInclusive = Number(window.POS_VAT_INCLUSIVE) === 1;
    const vatamount = (vatInclusive
        ? (vatper > 0 ? unitPrice - (unitPrice / (1 + (vatper / 100))) : 0)
        : unitPrice * (vatper / 100)).toFixed(2);
    const initialTotal = vatInclusive ? unitPrice : unitPrice + parseFloat(vatamount);

    targetRow.attr('data-itemcode', product.itemcode || '');
    targetRow.addClass('selectable').removeClass('blank-row');
    targetRow.html(`
        <td class="barcode">${product.barcode}</td>
        <td class="description">${product.description}</td>
        <td contenteditable="true" class="quantity">1</td>
        <td class="unit-price">${unitPrice.toFixed(2)}</td>
        <td class="vat-per">${vatper}</td>
        <td class="VAT-total">${vatamount}</td>
        <td class="line-total">${initialTotal.toFixed(2)}</td>
    `);
    targetRow.find('.barcode').removeAttr('contenteditable');
    $('#sales-table tbody tr').removeClass('selected');
    targetRow.addClass('selected');

    updateLineTotal();
    if ($('#sales-table tbody tr.blank-row').length === 0) {
        addNewBlankRow();
    }
}

function fetchProduct(barcode) {
    $.ajax({
        url: 'POSajax/fetch_product.php',
        type: 'GET',
        data: { barcode: barcode },
        success: function(response) {
            const product = Array.isArray(response) ? response[0] : null;
            if (product) {
                addOrIncrementProductRow(product);
            } else {
                showCustomDialog('Product not found for barcode: ' + barcode);
            }
        },
        error: function() {
            showCustomDialog('Error fetching product details');
        }
    });
}

function updateLineTotal() {
    let total = 0;
    let vattotal = 0;
    let itemCount = 0;

    $('#sales-table tbody tr:not(.blank-row)').each(function() {
        let lineValue = parseFloat($(this).find('.line-total').text()) || 0;
        total += lineValue;

        let vatValue = parseFloat($(this).find('.VAT-total').text()) || 0;
        vattotal += vatValue;

        let qtyValue = parseFloat($(this).find('.quantity').text()) || 0;
        itemCount += qtyValue;
    });

    $('#total-amount').text(total.toFixed(2));
    $('#checkout-total').text(total.toFixed(2));
    $('#tax-amount').text(vattotal.toFixed(2));
    $('#pos-cart-label').text(itemCount + (itemCount === 1 ? ' item' : ' items'));
}

function fillAnySavedDataOnLoading() {
    const referencelist = document.querySelector('#reference-list');
    referencelist.innerHTML = '';
    $('#reference-list').append(`<option value="">Select a Reference</option>`);
    let savedTransactions = JSON.parse(localStorage.getItem('savedTransactions')) || {};
    Object.keys(savedTransactions).forEach(function(referenceNumber) {
        $('#reference-list').append(`<option value="${referenceNumber}">${referenceNumber}</option>`);
    });
}

function showReceiptModal() {
    $('#receipt-modal').show();
}

function handleCashPayment() {
    const total = parseFloat($('#total-amount').text()) || 0;
    const cash = parseFloat($('#customer-cash').val());

    if (total <= 0) {
        showCustomDialog('Add at least one product before completing the sale.');
        return;
    }
    if (!Number.isFinite(cash) || cash < total) {
        showCustomDialog('Enter sufficient cash before printing the receipt.');
        return;
    }

    printReceipt('CASH', '');
}

function handleMpesaPayment() {
    const total = parseFloat($('#total-amount').text()) || 0;
    if (total <= 0) {
        showCustomDialog('Add at least one product before completing the sale.');
        return;
    }

    $('#customer-cash').val(total.toFixed(2));
    $('#customer-change').text('0.00');
    $('#denomination-list').empty();
    $('#denomination-list-container').hide();
    $('#checkout-modal').hide();
    $('#mpesa-modal').show();
    $('#customer-mpesa-no').focus();
}

function closeModal() {
    $('.modal').hide();
    $('#pos-barcode-scan').focus();
}

setInterval(saveSalesData, 5000);
clearTable();
restoreSalesData();
fillAnySavedDataOnLoading();

function posReportEscape(value) {
    return String(value === null || typeof value === 'undefined' ? '' : value)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function posReportMoney(value) {
    var amount = Number(value);
    if (!Number.isFinite(amount)) { amount = 0; }
    return 'KES ' + amount.toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function posShiftReportPrintCss() {
    return 'body{margin:0;padding:18px;background:#eef2f7;color:#1c2940;font:12px/1.5 Segoe UI,Arial,sans-serif}' +
        '.pos-shift-report{box-sizing:border-box;max-width:1080px;margin:0 auto;padding:34px 38px;background:#fff;border:1px solid #d9e1eb;border-top:5px solid #17365d;border-radius:8px}' +
        '.psr-company-head{min-height:92px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:0 12px 20px;border-bottom:1px solid #cbd5e1}' +
        '.psr-company-name{color:#17365d;font-size:25px;line-height:1.2;font-weight:800;letter-spacing:1.1px;text-transform:uppercase}.psr-company-address{margin-top:5px;color:#617187;font-size:11px}' +
        '.psr-report-heading{display:flex;align-items:center;justify-content:space-between;gap:20px;margin:22px 0 16px;padding-bottom:13px;border-bottom:3px solid #17365d}' +
        '.psr-report-heading h2{margin:0;color:#17365d;font-size:18px;font-weight:800;letter-spacing:.65px}.psr-report-heading span{display:block;margin-top:4px;color:#77869a;font-size:10px}' +
        '.psr-report-heading strong{min-width:135px;padding:10px 15px;border:1px solid #b8c6d8;border-radius:4px;background:#f2f6fb;color:#17365d;text-align:center;font-size:12px}' +
        '.psr-parties,.psr-table,.psr-summary{width:100%;margin:0 0 22px;border-collapse:separate;border-spacing:0;table-layout:fixed}' +
        '.psr-parties{border:1px solid #d8e0ea}.psr-parties th,.psr-parties td,.psr-table th,.psr-table td,.psr-summary th,.psr-summary td{padding:10px 12px;border-right:1px solid #d8e0ea;border-bottom:1px solid #d8e0ea}' +
        '.psr-parties th{width:16%;background:#f2f5f9;color:#596b82;text-align:left;font-size:9px;font-weight:800;text-transform:uppercase}.psr-parties td{width:34%;font-weight:650}' +
        '.psr-table thead th{background:#17365d;color:#fff;text-align:left;font-size:9px;font-weight:800;text-transform:uppercase;border-color:#365578}' +
        '.psr-table thead .psr-table-title{background:#e9eff6!important;color:#17365d!important;text-align:center!important;font-size:10px!important;letter-spacing:1.2px}' +
        '.psr-table tbody tr:nth-child(even){background:#f7f9fc}.psr-table .num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}.psr-table tfoot th{background:#e9eff6;color:#17365d;font-weight:800;border-top:2px solid #9aabc0}' +
        '.psr-empty{padding:24px!important;color:#718096;text-align:center}.psr-summary{width:72%;margin:0 auto 22px;border:1px solid #cbd5e1}' +
        '.psr-summary th{background:#e9eff6;color:#17365d;text-align:center;font-size:10px;font-weight:800;letter-spacing:1px}.psr-summary td{width:50%}' +
        '.psr-summary td:first-child{background:#f7f9fc;color:#596b82;font-weight:650}.psr-summary td:last-child{background:#fff;color:#1c2940;text-align:right;font-weight:750;font-variant-numeric:tabular-nums}' +
        '.psr-footer{display:flex;justify-content:center;margin-top:24px;padding-top:12px;border-top:1px solid #cbd5e1;color:#7b899b;text-align:center;font-size:9px;letter-spacing:.25px}' +
        '@page{size:A4 portrait;margin:12mm}@media print{body{padding:0;background:#fff}.pos-shift-report{max-width:none;padding:24px 26px;border-radius:0;box-shadow:none}.psr-table{page-break-inside:auto}.psr-table tr{page-break-inside:avoid;page-break-after:auto}}';
}

function showPosShiftReport(type, shift, items) {
    var isZ = type === 'Z';
    var reportNo = isZ ? (shift.z_report_number || 'Z REPORT') : ('SHIFT #' + shift.id);
    var company = window.POS_COMPANY_DETAILS || {};
    var companyName = company.name || 'Company name not configured';
    var companyAddress = (company.address || []).filter(function(value) { return value; }).join(', ');
    var companyContacts = [company.telephone, company.email].filter(function(value) { return value; }).join(' · ');
    var generated = new Date();
    var reportDate = generated.toLocaleDateString('en-GB');
    var reportTime = generated.toLocaleTimeString('en-GB', {hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:false});
    var rows = '';
    var totalQty = 0, totalVat = 0, totalSales = 0;
    (items || []).forEach(function(item, index) {
        var qty = Number(item.quantity) || 0;
        var vat = Number(item.vat_value) || 0;
        var sales = Number(item.sales_value) || 0;
        totalQty += qty; totalVat += vat; totalSales += sales;
        rows += '<tr><td class="num">' + (index + 1) + '</td><td>' + posReportEscape(item.itemcode || '') +
            '</td><td>' + posReportEscape(item.description || '') + '</td><td class="num">' +
            qty.toLocaleString('en-KE', {minimumFractionDigits:2, maximumFractionDigits:2}) +
            '</td><td class="num">' + posReportMoney(sales) + '</td><td class="num">' +
            posReportMoney(vat) + '</td><td class="num">' + posReportMoney(sales + vat) + '</td></tr>';
    });
    if (!rows) {
        rows = '<tr><td colspan="7" class="psr-empty">No sales have been recorded for this shift.</td></tr>';
    }
    var html = '<article class="pos-shift-report">' +
        '<header class="psr-company-head"><div class="psr-company-name">' + posReportEscape(companyName) +
        '</div><div class="psr-company-address">' + posReportEscape(companyAddress) + '</div>' +
        '<div class="psr-company-address">' + posReportEscape(companyContacts) + '</div></header>' +
        '<div class="psr-report-heading"><div><h2>' + (isZ ? 'Z REPORT - SHIFT CLOSING' : 'X REPORT - SHIFT SUMMARY') +
        '</h2><span>Point of Sale Shift Report</span></div><strong>' + posReportEscape(reportNo) + '</strong></div>' +
        '<table class="psr-parties"><tbody><tr><th>Report date</th><td>' + reportDate +
        '</td><th>Report time</th><td>' + reportTime + '</td></tr><tr><th>Cashier</th><td>' +
        posReportEscape(shift.cashier_name || '-') + '</td><th>Shift number</th><td>#' +
        posReportEscape(shift.id || '-') + '</td></tr><tr><th>Shift opened</th><td>' +
        posReportEscape(shift.opened_at || '-') + '</td><th>Shift closed</th><td>' +
        posReportEscape(shift.closed_at || (isZ ? '-' : 'OPEN')) + '</td></tr><tr><th>Receipts</th><td>' +
        posReportEscape(shift.receipt_count || 0) + '</td><th>Report type</th><td>' +
        (isZ ? 'Final / Z' : 'Interim / X') + '</td></tr></tbody></table>' +
        '<table class="psr-table"><thead><tr><th colspan="7" class="psr-table-title">PRODUCT SALES ANALYSIS</th></tr><tr>' +
        '<th class="num">#</th><th>Item code</th><th>Description</th><th class="num">Quantity</th>' +
        '<th class="num">Net amount</th><th class="num">VAT</th><th class="num">Gross amount</th></tr></thead><tbody>' + rows +
        '</tbody><tfoot><tr><th colspan="3">TOTAL</th><th class="num">' +
        totalQty.toLocaleString('en-KE', {minimumFractionDigits:2, maximumFractionDigits:2}) +
        '</th><th class="num">' + posReportMoney(totalSales) + '</th><th class="num">' +
        posReportMoney(totalVat) + '</th><th class="num">' + posReportMoney(totalSales+totalVat) +
        '</th></tr></tfoot></table>' +
        '<table class="psr-summary"><tbody><tr><th colspan="2">SHIFT FINANCIAL SUMMARY</th></tr>' +
        '<tr><td>Gross sales</td><td>' + posReportMoney(shift.total_sales) + '</td></tr>' +
        '<tr><td>VAT</td><td>' + posReportMoney(shift.total_vat) + '</td></tr>' +
        '<tr><td>Cash sales</td><td>' + posReportMoney(shift.cash_sales) + '</td></tr>' +
        '<tr><td>M-Pesa sales</td><td>' + posReportMoney(shift.mpesa_sales) + '</td></tr>' +
        '<tr><td>Opening float</td><td>' + posReportMoney(shift.opening_float) + '</td></tr>' +
        '<tr><td>Expected drawer cash</td><td>' + posReportMoney(shift.expected_cash) + '</td></tr>' +
        (isZ ? '<tr><td>Actual cash counted</td><td>' + posReportMoney(shift.cash_counted) +
        '</td></tr><tr><td>Cash variance</td><td>' + posReportMoney(shift.cash_variance) + '</td></tr>' : '') +
        '</tbody></table><footer class="psr-footer">SmartERPlims POS · ' +
        (isZ ? 'End of shift report' : 'Interim shift report') + ' · Generated ' +
        reportDate + ' ' + reportTime + '</footer></article>';
    window.POS_LAST_SHIFT_REPORT = html;
    $('#dialog-message').html(html);
    $('#dialog-print-btn').show();
    $('#custom-dialog').fadeIn();
}

function posShiftRequest(action, data, onSuccess) {
    $.ajax({
        url: 'POSajax/shift.php' + (data ? '' : '?action=' + encodeURIComponent(action)),
        type: data ? 'POST' : 'GET',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        headers: data ? { 'X-POS-FormID': window.POS_FORM_ID || '' } : {},
        data: data ? JSON.stringify($.extend({ action: action }, data)) : undefined
    }).done(function(response) {
        if (!response || !response.success) {
            showCustomDialog(response && response.message ? response.message : 'The shift request failed.');
            return;
        }
        if (typeof onSuccess === 'function') { onSuccess(response); }
    }).fail(function(xhr) {
        var message = xhr.responseJSON && xhr.responseJSON.message
            ? xhr.responseJSON.message : 'Could not communicate with the POS shift service.';
        showCustomDialog(message);
    });
}

function posRefreshShift() {
    posShiftRequest('status', null, function(response) {
        var shift = response.shift;
        if (!shift) {
            $('#pos-shift-label').text('No open shift').removeClass('pos-shift-open');
            $('#pos-shift-open').prop('disabled', false);
            $('#pos-x-report, #pos-z-close').prop('disabled', true);
            $('#pay-cash-btn, #pay-mpesa-btn').prop('disabled', true);
            return;
        }
        $('#pos-shift-label').text('Shift #' + shift.id + ' · ' + shift.opened_at).addClass('pos-shift-open');
        $('#pos-shift-open').prop('disabled', true);
        $('#pos-x-report, #pos-z-close').prop('disabled', false);
        $('#pay-cash-btn, #pay-mpesa-btn').prop('disabled', false);
    });
}

$(document).ready(function() {
    $('#dialog-print-btn').on('click', function() {
        if (!window.POS_LAST_SHIFT_REPORT) { return; }
        var reportWindow = window.open('', '_blank');
        if (!reportWindow) {
            showCustomDialog('Allow pop-ups to print the shift report.');
            return;
        }
        reportWindow.document.write('<!doctype html><html><head><meta charset="utf-8"><title>SmartERPlims POS Shift Report</title><style>' +
posShiftReportPrintCss() + '</style></head><body>' + window.POS_LAST_SHIFT_REPORT + '<script>window.onload=function(){window.print();};<\/script></body></html>');
        reportWindow.document.close();
    });
    posRefreshShift();

    $('#pos-shift-open').on('click', function() {
        var openingFloat = window.prompt('Enter the opening cash float (KES):', '0.00');
        if (openingFloat === null) { return; }
        openingFloat = parseFloat(openingFloat);
        if (!Number.isFinite(openingFloat) || openingFloat < 0) {
            showCustomDialog('Enter a valid opening cash float.');
            return;
        }
        posShiftRequest('open', { opening_float: openingFloat }, function(response) {
            showCustomDialog(response.message + ' Shift #' + response.shift_id);
            posRefreshShift();
        });
    });

    $('#pos-x-report').on('click', function() {
        posShiftRequest('x-report', null, function(response) {
            showPosShiftReport('X', response.shift, response.items || []);
        });
    });

    $('#pos-z-close').on('click', function() {
        var counted = window.prompt('Count the actual cash in the drawer and enter the amount (KES):', '');
        if (counted === null) { return; }
        counted = parseFloat(counted);
        if (!Number.isFinite(counted) || counted < 0) {
            showCustomDialog('Enter the actual cash counted in the drawer.');
            return;
        }
        if (!window.confirm('Close this cashier shift using the cash count of KES ' + counted.toFixed(2) + '? The Z report cannot be reopened.')) {
            return;
        }
        posShiftRequest('close', { cash_counted: counted }, function(response) {
            showPosShiftReport('Z', response, response.items || []);
            posRefreshShift();
        });
    });
});