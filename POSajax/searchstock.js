let stockData = []; // Store all stock data after AJAX load

// Load all stock data on page load and display in the search results
$(document).ready(function() {
    $.ajax({
        url: 'POSajax/search_stock.php', // Change to your endpoint that returns all stock data
        type: 'GET',
        success: function(response) {
            stockData = response; // Parse and store the data
            displaySearchResults(stockData); // Display all stock data initially
        },
        error: function() {
            showCustomDialog('Error loading stock data.');
        }
    });
   // Search input keyup event to filter stockData
    $('#search-input').on('keyup', function() {
        const query = $(this).val().toLowerCase();
        const filteredData = stockData.filter(item => {
            return item.barcode.toLowerCase().includes(query) || item.description.toLowerCase().includes(query);
        });

        displaySearchResults(filteredData); // Display the filtered results
    });
   // Handle selecting a product from the search results
    $(document).on('click', '.search-result-item', function() {
        const selectedProduct = $(this).data('product');
        
        // Select the current blank row
        const blankRow = document.querySelector('#sales-table tbody tr.blank-row');
         
        // Fill in the selected product's details in the blank row
        if (blankRow) {
            const vatper      = selectedProduct.vat * 0.01;
            const vatamount   = (selectedProduct.unit_price * vatper).toFixed(2);
            const unitPrice   = parseFloat(selectedProduct.unit_price).toFixed(2);
            const quantity    = 1; // Assume 1 as default quantity
            const lineTotal   = (unitPrice * quantity).toFixed(2);
         
            blankRow.classList.add('selectable');
            blankRow.classList.remove('blank-row');
            blankRow.querySelector('.barcode').textContent = selectedProduct.barcode;
            blankRow.querySelector('.description').textContent = selectedProduct.description;
            blankRow.querySelector('.unit-price').textContent = unitPrice;
            blankRow.querySelector('.line-total').textContent = lineTotal; 
            blankRow.querySelector('.vat-per').textContent  = selectedProduct.vat;
            blankRow.querySelector('.VAT-total').textContent= vatamount;
           // Remove 'blank-row' class from the updated row so it doesn't get overwritten again
             
            updateLineTotal()
            // Add a new blank row ONLY IF there are no other blank rows
            if (document.querySelector('#sales-table tbody tr.blank-row') === null) {
                blankRow.querySelector('.barcode').removeAttribute('contenteditable');

                addNewBlankRow();
            }
        }

        $('#search-modal').hide(); // Close the search modal
    });
    
    
    
});

// Function to display search results in the modal
function displaySearchResults(results) {
    let htmlContent = `<div class="search-result-header"><div class="feild">Bar Code</div>
                            <div class="feild">Description</div>
                            <div class="feild">Price</div></div>`;

    if (results.length > 0) {
        results.forEach(product => {
            htmlContent += `<div class="search-result-item" data-product='${JSON.stringify(product)}'>
                            <div class="feild"><strong>${product.barcode}</strong></div>
                            <div class="feild">${product.description}</div>
                            <div class="feild"> ${product.unit_price}</div>
                            </div>`;
        });
    } else {
        htmlContent = '<p>No products found</p>';
    }

    $('#search-results').html(htmlContent); // Update the search results
}




// Update the totals when the quantity changes
$(document).on('input', '.quantity', function() {
    const $row = $(this).closest('tr');
    const unitPrice = parseFloat($row.find('.unit-price').text());
    const quantity = parseInt($(this).text()) || 1; // Default to 1 if empty or invalid
    const lineTotal = (unitPrice * quantity).toFixed(2);
    const vatper = parseFloat($row.find('.vat-per').text()) || 0;
    const vatamount = (lineTotal * vatper * 0.01).toFixed(2);

    $row.find('.VAT-total').text(vatamount);
    $row.find('.line-total').text(lineTotal);
});
