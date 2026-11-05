
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

    // Calculate the base price and VAT amount
    const basePrice = lineTotal / (1 + (vatper / 100));
    const vatAmount = lineTotal - basePrice;

    // Update the VAT and line total fields in the current row
    $row.find('.VAT-total').text(vatAmount.toFixed(2)); // Corrected variable name to 'vatAmount'
    $row.find('.line-total').text(lineTotal.toFixed(2));

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
    $('#dialog-message').text(message);    // Set the message
    $('#custom-dialog').fadeIn();          // Show the dialog
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
            const newRow = `<tr class="selectable" data-barcode="${item.barcode}">
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
        const description = $(this).find('td').eq(1).text().trim();
        const quantity = $(this).find('.quantity').text().trim();
        const unitPrice = $(this).find('.unit-price').text().trim();
        const vatper = $(this).find('.vat-per').text().trim();
        const VATtotal = $(this).find('.VAT-total').text().trim();
        const lineTotal = $(this).find('.line-total').text().trim();

 
        if (barcode !== '' && description !== '') {
            items.push({
                barcode: barcode,
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
        const description = $(this).find('td').eq(1).text().trim();
        const quantity = $(this).find('.quantity').text().trim();
        const unitPrice = $(this).find('.unit-price').text().trim();
        const vatper = $(this).find('.vat-per').text().trim();
        const VATtotal = $(this).find('.VAT-total').text().trim();
        const lineTotal = $(this).find('.line-total').text().trim();

 
        if (barcode !== '' && description !== '') {
            items.push({
                barcode: barcode,
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
                <tr class="selectable">
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
              const vatamount = (unitPrice * vatper * 0.01).toFixed(2);
               // Replace the blank row with the product data
                targetRow.html(`
                    <td class="barcode">${product.barcode}</td>
                    <td>${product.description}</td>
                    <td contenteditable="true" class="quantity">1</td>
                    <td class="unit-price">${product.unit_price}</td>
                    <td class="vat-per">${product.vat}</td>
                    <td class="VAT-total">${vatamount}</td>
                    <td class="line-total">${(product.unit_price * 1).toFixed(2)}</td>
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