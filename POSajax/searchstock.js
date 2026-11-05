let stockData = [];
$(document).ready(function() {
    $.ajax({
        url: 'POSajax/search_stock.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (!Array.isArray(response)) {
                showCustomDialog(response && response.error ? response.error : 'The product catalogue response was invalid.');
                return;
            }
            stockData = response;
            displaySearchResults(stockData);
        },
        error: function(xhr) {
            const message = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Error loading stock data.';
            showCustomDialog(message);
        }
    });
    $('#search-input').on('input', function() {
        const query = String($(this).val() || '').toLowerCase();
        displaySearchResults(stockData.filter(function(item) {
            return String(item.barcode || '').toLowerCase().includes(query) ||
                String(item.description || '').toLowerCase().includes(query);
        }));
    });
    $(document).on('click', '.search-result-item', function() {
        const selectedProduct = $(this).data('product');
        const blankRow = document.querySelector('#sales-table tbody tr.blank-row');
        if (!blankRow || !selectedProduct) {
            showCustomDialog('The sales grid is not ready for another product.');
            return;
        }
        const unitPrice = parseFloat(selectedProduct.unit_price);
        const vatRate = parseFloat(selectedProduct.vat) || 0;
        if (!Number.isFinite(unitPrice) || unitPrice < 0) {
            showCustomDialog('The selected product has an invalid selling price.');
            return;
        }
        // Preserve current VAT treatment until sellingprice inclusion is confirmed.
        const lineTotal = unitPrice.toFixed(2);
        const vatAmount = (unitPrice * vatRate / 100).toFixed(2);
        blankRow.classList.add('selectable');
        blankRow.classList.remove('blank-row');
        blankRow.querySelector('.barcode').textContent = selectedProduct.barcode || '';
        blankRow.querySelector('.description').textContent = selectedProduct.description || '';
        blankRow.querySelector('.quantity').textContent = '1';
        blankRow.querySelector('.unit-price').textContent = lineTotal;
        blankRow.querySelector('.line-total').textContent = lineTotal;
        blankRow.querySelector('.vat-per').textContent = vatRate;
        blankRow.querySelector('.VAT-total').textContent = vatAmount;
        blankRow.querySelector('.barcode').removeAttribute('contenteditable');
        addNewBlankRow();
        updateLineTotal();
        $('#search-modal').hide();
    });
});
function displaySearchResults(results) {
    let htmlContent = '<div class="search-result-header"><div class="feild">Bar Code</div><div class="feild">Description</div><div class="feild">Price</div></div>';
    if (results.length) {
        results.forEach(function(product) {
            const safeProduct = $('<div>').text(JSON.stringify(product)).html();
            const barcode = $('<div>').text(product.barcode || '').html();
            const description = $('<div>').text(product.description || '').html();
            const price = $('<div>').text(product.unit_price || '0.00').html();
            htmlContent += '<div class="search-result-item" data-product="' + safeProduct + '">' +
                '<div class="feild"><strong>' + barcode + '</strong></div>' +
                '<div class="feild">' + description + '</div><div class="feild">' + price + '</div></div>';
        });
    } else { htmlContent = '<p>No products found</p>'; }
    $('#search-results').html(htmlContent);
}