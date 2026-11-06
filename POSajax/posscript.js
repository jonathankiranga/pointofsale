
$(document).ready(function() {
    // One delegated handler supports dynamically added barcode cells.
    $('#sales-table tbody').on('keydown', '.barcode[contenteditable="true"]', function(event) {
        if (event.key !== 'Enter') { return; }
        event.preventDefault();
        const barcode = $(this).text().trim();
        if (barcode) { fetchProduct(barcode); }
    });
     
       // Detect barcode input from scanner (usually appends Enter key after scan)
    $(document).keydown(function(event) {
    // Check if the active element is contenteditable
        if (event.target.isContentEditable && event.key === 'Enter') {
            event.preventDefault(); // Prevent Enter from adding a new line
        }
        
        if (event.ctrlKey && event.key === 'F1') {
            event.preventDefault();
            openCheckoutModal();
        }
        
        if (event.ctrlKey && event.key === 'F2') {
            event.preventDefault();
            showReceiptModal();
            handleCashPayment();
        }
        
        if (event.ctrlKey && event.key === 'F3') {
            event.preventDefault();
            showReceiptModal();
            handleMpesaPayment();
        }
        
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
        if (!/^254[17][0-9]{8}$/.test(phone)) {
            showCustomDialog('Enter a valid Kenyan M-Pesa number in 2547XXXXXXXX or 2541XXXXXXXX format.');
            return;
        }
        if (!window.confirm('Have you received and verified this M-Pesa payment in your M-Pesa confirmation message or statement?')) {
            return;
        }
        printReceipt('MPESA', phone);
    });
    
    $('#show-bill-link').on('click', function(event) {
        event.preventDefault(); // Prevent the default link behavior
        $('#checkout-modal').show(); // Show the bill modal
    });



    $(document).on('input', '.quantity', function() {
    const $row = $(this).closest('tr');
    const unitPrice = parseFloat($row.find('.unit-price').text());
    const quantity = parseInt($(this).text()) || 0; // Ensure quantity is a number or default to 0
    const lineTotal = unitPrice * quantity;
    const vatper = parseFloat($row.find('.vat-per').text()) || 0; // Ensure vatper is a number or default to 0

    // Respect the configured ERP sales posting group's VAT-inclusive setting.
    const vatInclusive = Number(window.POS_VAT_INCLUSIVE) === 1;
    const vatAmount = vatInclusive
        ? lineTotal - (lineTotal / (1 + (vatper / 100)))
        : lineTotal * (vatper / 100);
    const grossTotal = vatInclusive ? lineTotal : lineTotal + vatAmount;

    $row.find('.VAT-total').text(vatAmount.toFixed(2));
    $row.find('.line-total').text(grossTotal.toFixed(2));

    // Call updateLineTotal to recalculate totals across the entire table
    updateLineTotal();    
});

   
$(document).on('click', '#delete-selected-link', function() {
    $('#sales-table tbody tr.selected').remove();
    updateLineTotal(); // Update the total after item is removed
});


$(document).on('click', '#sales-table tbody tr', function() {
    // Check if the clicked row does not have the 'blank-row' class
    if (!$(this).hasClass('blank-row')) {
        // Remove the 'selected' class from all rows
        $('#sales-table tbody tr').removeClass('selected');
      // Add the 'selected' class to the clicked row
        $(this).addClass('selected');
    }
       updateLineTotal(); // Update the total after item is removed
});

  
  
$(document).on('input', '#customer-cash', function() {
    const cash = parseFloat($(this).val());
    const total = parseFloat($('#total-amount').text());
    const change = (cash - total).toFixed(2);
    $('#customer-change').text(change >= 0 ? change : 'Insufficient Cash');
});

$('#find-items-link').on('click', function(event) {
    event.preventDefault(); // Prevent the default link behavior
    $('#search-modal').show(); // Show the search modal
});


// Event listener for closing the modal when the "X" is clicked
$('.checkout-modal-close').on('click', function(event) {
    event.preventDefault(); // Prevent the default link behavior
    $('.modal').hide();
});


 $('#save-transaction-link').on('click', function(event) {
    event.preventDefault(); // Prevent the default link behavior
    saveSalesDataWithReference();
 });
     
     
      // Open modal function
    function openModal() {
        $('#recall-modal').show();
    }

    // Close modal function
    function closeModal() {
        $('#recall-modal').hide();
    }

    // Show the modal when needed (example: a button click)
    $('#recall-transaction-link').on('click', function() {
        openModal();
    });
    
    
     // Handle recall action
    $('#recall-button').on('click', function() {
        const selectedId = $('#reference-list').val();
        if (selectedId) {
            recallSalesDataByReference(selectedId);
            closeModal(); // Close the modal after recalling
        } else {
            showCustomDialog('Please select a reference to recall.');
        }
    });
     
    
    $('#dialog-ok-btn').on('click', function(event) {
        event.preventDefault(); // Prevent the default link behavior
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
    $('#custom-dialog').fadeOut();         // Hide the dialog
}
// Function to list change in currency denominations
function listChangeDenominations(change) {
    const denominations = [1000, 500, 200, 100, 50, 20, 10, 5, 1];
    let remainingChange = Math.floor(change); // Convert to whole number for denomination calculation
    let denominationCount = {};

    denominations.forEach(denomination => {
        if (remainingChange >= denomination) {
            denominationCount[denomination] = Math.floor(remainingChange / denomination);
            remainingChange -= denominationCount[denomination] * denomination;
        }
    });

    // Generate and display the denomination list
    let denominationHtml = '<ul>';
    for (const [denomination, count] of Object.entries(denominationCount)) {
        denominationHtml += `<li>${count} x KES ${denomination}</li>`;
    }
    denominationHtml += '</ul>';

    // Display the list of denominations
    $('#denomination-list').html(denominationHtml);
}

function restoreSalesData() {
    const savedData = localStorage.getItem('salesData');
    if (savedData) {
        const receiptData = JSON.parse(savedData);

        // Restore total, cash, and change
        $('#total-amount').text(receiptData.total);
        $('#customer-cash').val(receiptData.cash);
        $('#customer-change').text(receiptData.change);
     // Clear existing rows in the table
        const tableBody = document.querySelector('#sales-table tbody');
        tableBody.innerHTML = ''; // Clear all rows
          
        // Restore each item row
        receiptData.items.forEach(function(item) {
            const newRow = `<tr class="selectable" data-barcode="${item.barcode}" data-itemcode="${item.itemcode || ''}">
                    <td>${item.barcode}</td>
                    <td>${item.description}</td>
                    <td contenteditable="true" class="quantity">${item.quantity}</td>
                    <td class="unit-price">${item.unitPrice}</td>
                    <td class="vat-per">${item.vatper}</td>
                    <td class="VAT-total">${item.VATtotal}</td>
                    <td class="line-total">${item.lineTotal}</td>
                </tr>`;
             
             tableBody.insertAdjacentHTML('beforeend', newRow);
             updateLineTotal(); // Update the total after item is removed
        });
         
         localStorage.removeItem('salesData');
        // Add a new blank row ONLY IF there are no other blank rows
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

    const receiptData = {
        items: items,
        total: total,
        cash: cash,
        change: change
    };
    
    localStorage.removeItem('salesData');
    // Save to localStorage
    localStorage.setItem('salesData', JSON.stringify(receiptData));
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

    const receiptData = {
        items: items,
        total: total,
        cash: cash,
        change: change
    };

    // Generate a unique reference number (timestamp as an example)
    const referenceNumber = `REF-${Date.now()}`;
    
    // Save to localStorage with the reference number as the key
    let savedTransactions = JSON.parse(localStorage.getItem('savedTransactions')) || {};
    savedTransactions[referenceNumber] = receiptData;
    localStorage.setItem('savedTransactions', JSON.stringify(savedTransactions));

    // Optionally, update a UI list with the reference number
    $('#reference-list').append(`<option value="${referenceNumber}">${referenceNumber}</option>`);

   
     showCustomDialog(`Data saved with reference: ${referenceNumber}`);
     clearTable(); // Call this to clear the existing table and create editable row
}

function recallSalesDataByReference(referenceNumber) {
    const savedTransactions = JSON.parse(localStorage.getItem('savedTransactions')) || {};
    const receiptData = savedTransactions[referenceNumber];

    if (receiptData) {
        // Restore total, cash, and change
        $('#total-amount').text(receiptData.total);
        $('#customer-cash').val(receiptData.cash);
        $('#customer-change').text(receiptData.change);
        // Clear existing rows in the table
        
        const tableBody = document.querySelector('#sales-table tbody');
        tableBody.innerHTML = ''; // Clear all rows
       
        // Restore each item row
        receiptData.items.forEach(function(item) {
            const newRow = `
                <tr class="selectable" data-barcode="${item.barcode}" data-itemcode="${item.itemcode || ''}">
                    <td>${item.barcode}</td>
                    <td>${item.description}</td>
                    <td contenteditable="true" class="quantity">${item.quantity}</td>
                    <td class="unit-price">${item.unitPrice}</td>
                    <td class="vat-per">${item.vatper}</td>
                    <td class="VAT-total">${item.VATtotal}</td>
                    <td class="line-total">${item.lineTotal}</td>
                </tr>`;
            
            tableBody.insertAdjacentHTML('beforeend', newRow);
             updateLineTotal(); // Update the total after item is remove
        });
        
        delete savedTransactions[referenceNumber]; // Remove the transaction
        // Update the savedTransactions in localStorage
        localStorage.setItem('savedTransactions', JSON.stringify(savedTransactions));

        
         showCustomDialog(`Data for reference ${referenceNumber} loaded successfully!`);
    } else {
        
        showCustomDialog('No data found for the selected reference.');
    }
    
    // Add a new blank row ONLY IF there are no other blank rows
        if (document.querySelector('#sales-table tbody tr.blank-row') === null) {
            addNewBlankRow();
        }
}

function openCheckoutModal() {
    const totalAmount = $('#total-amount').text();
    $('#checkout-total').text(totalAmount);  // Set the total amount in the modal
    $('#checkout-modal').show();  // Show the modal
}
// Function to add a new blank row to the sales table
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
    // Clear all existing rows except for the editable row
    const tableBody = document.querySelector('#sales-table tbody');
    tableBody.innerHTML = ''; // Clear all rows
    
    addNewBlankRow();
}
// Function to simulate fetching data from the database
function fetchProduct(barcode) {
  $.ajax({
    url: 'POSajax/fetch_product.php', // Update with your actual endpoint
    type: 'GET', // or 'POST' based on your API setup
    data: { barcode: barcode },
    success: function(response) {
        // Assuming the response is a JSON object with the product details
        const product = response[0];
        if (product) {
            //  const blankRow = document.querySelector('#sales-table tbody tr.blank-row'); 
            // Select the current blank row
              const blankRow = $('#sales-table tbody tr.blank-row');
              if (!blankRow.length) { addNewBlankRow(); }
              const targetRow = $('#sales-table tbody tr.blank-row').first();
              const vatper = parseFloat(product.vat) || 0;
              const unitPrice = parseFloat(product.unit_price) || 0;
              const vatInclusive = Number(window.POS_VAT_INCLUSIVE) === 1;
              const netAmount = unitPrice;
              const vatamount = (vatInclusive
                  ? netAmount - (netAmount / (1 + (vatper / 100)))
                  : netAmount * (vatper / 100)).toFixed(2);
              const initialTotal = vatInclusive ? netAmount : netAmount + parseFloat(vatamount);
               // Replace the blank row with the product data
                targetRow.attr('data-itemcode', product.itemcode || '');
                targetRow.html(`
                    <td class="barcode">${product.barcode}</td>
                    <td>${product.description}</td>
                    <td contenteditable="true" class="quantity">1</td>
                    <td class="unit-price">${product.unit_price}</td>
                    <td class="vat-per">${product.vat}</td>
                    <td class="VAT-total">${vatamount}</td>
                    <td class="line-total">${initialTotal.toFixed(2)}</td>
                `).removeClass('blank-row'); // Remove 'blank-row' class

                // Remove the contenteditable attribute from the barcode cell
                targetRow.find('.barcode').removeAttr('contenteditable');
             updateLineTotal();
            // Add a new blank row ONLY IF there are no other blank rows
         //   if (document.querySelector('#sales-table tbody tr.blank-row') === null) {
             if ($('#sales-table tbody tr.blank-row').length === 0) {
                addNewBlankRow();
            }
             // Remove the 'blank-row' class since this row is no longer blank
        } else {
              showCustomDialog('Product not found');
        }
    },
    error: function() {
        
         showCustomDialog('Error fetching product details');
    }
});

}
// Update the total sales amount
function updateLineTotal() {
    let total = 0; 
    let vattotal = 0;
    
    // Loop through each .line-total and add the value if valid
    $('#sales-table tbody tr').each(function() {
        let lineValue = parseFloat($(this).find('.line-total').text()) || 0; // Fallback to 0 if invalid
        total += lineValue;
    
        let vatValue = parseFloat($(this).find('.VAT-total').text()) || 0; // Fallback to 0 if invalid
        vattotal += vatValue;
    });
    
    // Update the total and VAT amounts in the UI
    $('#total-amount').text(total.toFixed(2));
    $('#checkout-total').text(total.toFixed(2));  // Corrected the selector
    $('#tax-amount').text(vattotal.toFixed(2));
}

function fillAnySavedDataOnLoading() {
   const referencelist = document.querySelector('#reference-list');
   referencelist.innerHTML = ''; // Clear the list
   $('#reference-list').append(`<option value="">Select a Reference</option>`);
   // Retrieve saved transactions, or initialize an empty array if none exist
   let savedTransactions = JSON.parse(localStorage.getItem('savedTransactions')) || [];
  // Loop through the keys of the savedTransactions object (reference numbers)
    Object.keys(savedTransactions).forEach(function(referenceNumber) {
        // Append each reference number to the reference-list select element
        $('#reference-list').append(`<option value="${referenceNumber}">${referenceNumber}</option>`);
    });
}

function showReceiptModal() {
    // Kept for compatibility with any existing callers; opening this preview
    // must not trigger a print or save operation.
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
}

function closeModal() {
    $('.modal').hide();
}

// Cash drawer opening is handled by the Node.js ESC/POS printer service
// after the receipt has been printed and the sale has been saved.

function viewInventoryReport() {
    $('#search-modal').show(); // Show the receipt modal
}

function viewPaymentsReport() {
    alert('Showing Payments Report...');
    // Add logic to display the payments report
}

function viewDailyReport() {
    alert('Showing Daily Summary...');
    // Add logic to display the daily summary
}

function viewMonthlyReport() {
    alert('Showing Monthly Summary...');
    // Add logic to display the monthly summary
}

// Call this function whenever the data changes (e.g., after adding a product)
setInterval(saveSalesData,5000);  // Save every 5 seconds
clearTable(); // Call this to clear the existing table and create editable row
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
    return 'body{margin:0;padding:28px;background:#eef2f7;color:#172033;font:14px/1.45 Arial,sans-serif}' +
        '.pos-shift-report{max-width:900px;margin:0 auto;padding:34px;background:#fff;border:1px solid #dfe5ee}' +
        '.psr-head{display:flex;justify-content:space-between;gap:20px;border-bottom:3px solid #172b4d;padding-bottom:20px}' +
        '.psr-brand{font-size:12px;font-weight:800;letter-spacing:2px;color:#52627a}.psr-title{font-size:28px;font-weight:800;margin:5px 0}' +
        '.psr-sub{color:#667085}.psr-stamp{align-self:flex-start;padding:9px 14px;background:#eaf2ff;color:#145cc5;font-weight:800;letter-spacing:1px}' +
        '.psr-meta{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:20px 0}.psr-meta div,.psr-metric{padding:13px;background:#f5f7fa;border:1px solid #e6ebf2}' +
        '.psr-label{display:block;color:#667085;font-size:10px;font-weight:800;letter-spacing:.8px;text-transform:uppercase}.psr-value{display:block;margin-top:5px;font-weight:700}' +
        '.psr-total{background:#10233f;color:#fff;padding:20px;margin:16px 0}.psr-total .psr-label{color:#c7d4e7}.psr-total strong{display:block;font-size:30px;margin-top:4px}' +
        '.psr-section{margin-top:24px}.psr-section h3{font-size:13px;letter-spacing:1px;text-transform:uppercase;border-bottom:1px solid #dfe5ee;padding-bottom:9px}' +
        '.psr-metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.psr-metric strong{display:block;margin-top:6px;font-size:16px}' +
        '.psr-table{width:100%;border-collapse:collapse;margin-top:10px}.psr-table th{background:#f1f4f8;text-align:left;font-size:10px;text-transform:uppercase;color:#52627a}' +
        '.psr-table th,.psr-table td{padding:10px;border-bottom:1px solid #e7ebf1}.psr-table .num{text-align:right;font-variant-numeric:tabular-nums}' +
        '.psr-empty{text-align:center;color:#667085;padding:22px!important}.psr-footer{margin-top:28px;padding-top:12px;border-top:1px solid #dfe5ee;color:#667085;font-size:10px;display:flex;justify-content:space-between}' +
        '@page{size:A4;margin:12mm}@media print{body{padding:0;background:#fff}.pos-shift-report{border:0;padding:0;max-width:none}.psr-total,.psr-stamp{-webkit-print-color-adjust:exact;print-color-adjust:exact}}';
}

function showPosShiftReport(type, shift, items) {
    var isZ = type === 'Z';
    var reportNo = isZ ? (shift.z_report_number || 'Z REPORT') : ('SHIFT #' + shift.id);
    var cashier = shift.cashier_name || '—';
    var opened = shift.opened_at || '—';
    var closed = shift.closed_at || (isZ ? '—' : 'Shift still open');
    var rows = '';
    var totalQty = 0;
    (items || []).forEach(function(item, index) {
        var qty = Number(item.quantity) || 0;
        totalQty += qty;
        rows += '<tr><td>' + (index + 1) + '</td><td>' + posReportEscape(item.itemcode || '') +
            '</td><td>' + posReportEscape(item.description || '') + '</td><td class="num">' +
            qty.toLocaleString('en-KE', {maximumFractionDigits: 2}) + '</td><td class="num">' +
            posReportMoney(item.sales_value) + '</td></tr>';
    });
    if (!rows) {
        rows = '<tr><td colspan="5" class="psr-empty">No sales have been recorded for this shift.</td></tr>';
    }
    var html = '<article class="pos-shift-report">' +
        '<header class="psr-head"><div><div class="psr-brand">SMARTERPLIMS · POINT OF SALE</div><div class="psr-title">' +
        (isZ ? 'Shift Closing Report' : 'Shift Trading Report') + '</div><div class="psr-sub">Cashier shift reconciliation</div></div>' +
        '<div class="psr-stamp">' + (isZ ? 'Z REPORT' : 'X REPORT') + '</div></header>' +
        '<section class="psr-meta"><div><span class="psr-label">Report reference</span><span class="psr-value">' + posReportEscape(reportNo) +
        '</span></div><div><span class="psr-label">Cashier</span><span class="psr-value">' + posReportEscape(cashier) +
        '</span></div><div><span class="psr-label">Shift number</span><span class="psr-value">#' + posReportEscape(shift.id || '—') +
        '</span></div><div><span class="psr-label">Opened</span><span class="psr-value">' + posReportEscape(opened) +
        '</span></div><div><span class="psr-label">Closed</span><span class="psr-value">' + posReportEscape(closed) +
        '</span></div><div><span class="psr-label">Receipts</span><span class="psr-value">' + posReportEscape(shift.receipt_count || 0) +
        '</span></div></section>' +
        '<div class="psr-total"><span class="psr-label">Gross sales</span><strong>' + posReportMoney(shift.total_sales) + '</strong></div>' +
        '<section class="psr-section"><h3>Sales and payment summary</h3><div class="psr-metrics">' +
        '<div class="psr-metric"><span class="psr-label">VAT</span><strong>' + posReportMoney(shift.total_vat) + '</strong></div>' +
        '<div class="psr-metric"><span class="psr-label">Cash sales</span><strong>' + posReportMoney(shift.cash_sales) + '</strong></div>' +
        '<div class="psr-metric"><span class="psr-label">M-Pesa sales</span><strong>' + posReportMoney(shift.mpesa_sales) + '</strong></div>' +
        '</div></section>' +
        '<section class="psr-section"><h3>Cash drawer reconciliation</h3><div class="psr-metrics">' +
        '<div class="psr-metric"><span class="psr-label">Opening float</span><strong>' + posReportMoney(shift.opening_float) + '</strong></div>' +
        '<div class="psr-metric"><span class="psr-label">Expected cash</span><strong>' + posReportMoney(shift.expected_cash) + '</strong></div>' +
        (isZ ? '<div class="psr-metric"><span class="psr-label">Actual cash counted</span><strong>' + posReportMoney(shift.cash_counted) +
        '</strong></div><div class="psr-metric"><span class="psr-label">Cash variance</span><strong>' + posReportMoney(shift.cash_variance) +
        '</strong></div>' : '') + '</div></section>' +
        '<section class="psr-section"><h3>Products sold</h3><table class="psr-table"><thead><tr><th>#</th><th>Item code</th><th>Description</th>' +
        '<th class="num">Quantity</th><th class="num">Sales value</th></tr></thead><tbody>' + rows +
        '</tbody><tfoot><tr><th colspan="3">Total quantity</th><th class="num">' +
        totalQty.toLocaleString('en-KE', {maximumFractionDigits: 2}) + '</th><th></th></tr></tfoot></table></section>' +
        '<footer class="psr-footer"><span>SmartERPlims POS · ' + (isZ ? 'End of shift' : 'Interim snapshot') +
        '</span><span>Generated ' + posReportEscape(new Date().toLocaleString()) + '</span></footer></article>';
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
            $('#pos-shift-label').text('No open shift');
            $('#pos-shift-open').prop('disabled', false);
            $('#pos-x-report, #pos-z-close').prop('disabled', true);
            $('#pay-cash-btn, #pay-mpesa-btn').prop('disabled', true);
            return;
        }
        $('#pos-shift-label').text('Shift #' + shift.id + ' · Opened ' + shift.opened_at);
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
        reportWindow.document.write('<!doctype html><html><head><meta charset="utf-8"><title>SmartERPlims POS Shift Report</title><style>' + posShiftReportPrintCss() + '</style></head><body>' + window.POS_LAST_SHIFT_REPORT + '<script>window.onload=function(){window.print();};<\/script></body></html>');
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