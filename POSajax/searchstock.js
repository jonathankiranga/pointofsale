let stockData = [];
let activeStockCategory = 'all';

// Load the product catalogue once; searching and category filtering happen locally.
$(document).ready(function() {
    $.ajax({
        url: 'POSajax/search_stock.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (!Array.isArray(response)) {
                showCustomDialog((response && response.error) ? response.error : 'The product catalogue returned an invalid response.');
                return;
            }
            stockData = response;
            displaySearchResults(stockData);
        },
        error: function() {
            showCustomDialog('Error loading stock data.');
        }
    });

    $('#search-input').on('input', function() {
        displaySearchResults(getFilteredStock());
    });

    // Keep the existing product-selection workflow and row structure.
    $(document).on('click', '.search-result-item', function() {
        const selectedProduct = stockData[Number($(this).attr('data-stock-index'))];
        if (!selectedProduct) {
            showCustomDialog('The selected product is no longer available in the loaded catalogue. Search again.');
            return;
        }

        const blankRow = document.querySelector('#sales-table tbody tr.blank-row');
        if (!blankRow) {
            showCustomDialog('The sale grid is not ready for another item. Please try again.');
            return;
        }

        const vatRate = parseFloat(selectedProduct.vat) || 0;
        const unitPrice = (parseFloat(selectedProduct.unit_price) || 0).toFixed(2);
        const lineTotal = (parseFloat(unitPrice) * 1).toFixed(2);
        const vatAmount = (parseFloat(lineTotal) * vatRate / (100 + vatRate)).toFixed(2);

        blankRow.classList.add('selectable');
        blankRow.classList.remove('blank-row');
        blankRow.setAttribute('data-itemcode', selectedProduct.itemcode || '');
        blankRow.querySelector('.barcode').textContent = selectedProduct.barcode || '';
        blankRow.querySelector('.description').textContent = selectedProduct.description || '';
        blankRow.querySelector('.unit-price').textContent = unitPrice;
        blankRow.querySelector('.line-total').textContent = lineTotal;
        blankRow.querySelector('.vat-per').textContent = vatRate;
        blankRow.querySelector('.VAT-total').textContent = vatAmount;
        blankRow.querySelector('.barcode').removeAttribute('contenteditable');

        updateLineTotal();
        addNewBlankRow();
        $('#search-modal').hide();
        $('#search-input').val('');
        activeStockCategory = 'all';
        displaySearchResults(stockData);
    });

    // Add large +/- controls without changing the .quantity selector used by existing totals and receipt code.
    const salesBody = document.querySelector('#sales-table tbody');
    if (salesBody && window.MutationObserver) {
        const quantityObserver = new MutationObserver(function() {
            enhanceQuantityControls(salesBody);
        });
        quantityObserver.observe(salesBody, { childList: true, subtree: true });
        enhanceQuantityControls(salesBody);
    }

    $(document).on('click', '.pos-qty-step', function(event) {
        event.preventDefault();
        event.stopPropagation();
        const $quantity = $(this).siblings('.quantity');
        let quantity = parseInt($quantity.text(), 10) || 1;
        quantity += $(this).hasClass('pos-qty-increase') ? 1 : -1;
        quantity = Math.max(1, quantity);
        $quantity.text(quantity).trigger('input');
    });
});

function getFilteredStock() {
    const query = String($('#search-input').val() || '').toLowerCase().trim();
    return stockData.filter(function(item) {
        const barcode = String(item.barcode || '').toLowerCase();
        const description = String(item.description || '').toLowerCase();
        const category = String(item.category || '').toLowerCase();
        const matchesQuery = !query || barcode.includes(query) || description.includes(query);
        const matchesCategory = activeStockCategory === 'all' || String(item.category || '') === activeStockCategory;
        return matchesQuery && matchesCategory;
    });
}

function escapeStockHtml(value) {
    return String(value === null || value === undefined ? '' : value).replace(/[&<>"']/g, function(character) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[character];
    });
}

function displaySearchResults(results) {
    const categories = [];
    stockData.forEach(function(product) {
        const category = String(product.category || '').trim();
        if (category && categories.indexOf(category) === -1) {
            categories.push(category);
        }
    });
    categories.sort(function(a, b) { return a.localeCompare(b); });

    let htmlContent = '<div class="pos-category-bar" aria-label="Product categories">';
    htmlContent += '<button type="button" class="pos-category-chip' + (activeStockCategory === 'all' ? ' active' : '') + '" data-category="all">All products</button>';
    categories.forEach(function(category) {
        htmlContent += '<button type="button" class="pos-category-chip' + (activeStockCategory === category ? ' active' : '') + '" data-category="' + escapeStockHtml(category) + '">' + escapeStockHtml(category) + '</button>';
    });
    htmlContent += '</div>';
    htmlContent += '<div class="pos-product-grid" aria-live="polite">';

    if (results.length) {
        results.forEach(function(product) {
            const index = stockData.indexOf(product);
            htmlContent += '<button type="button" class="search-result-item pos-product-tile" data-stock-index="' + index + '">';
            htmlContent += '<span class="pos-product-category">' + escapeStockHtml(product.category || 'Uncategorised') + '</span>';
            htmlContent += '<strong class="pos-product-name">' + escapeStockHtml(product.description || 'Unnamed product') + '</strong>';
            htmlContent += '<span class="pos-product-barcode">' + escapeStockHtml(product.barcode || '') + '</span>';
            htmlContent += '<span class="pos-product-price">KES ' + escapeStockHtml((parseFloat(product.unit_price) || 0).toFixed(2)) + '</span>';
            htmlContent += '</button>';
        });
    } else {
        htmlContent += '<p class="pos-no-products">No products match this search or category.</p>';
    }

    htmlContent += '</div>';
    $('#search-results').html(htmlContent);
}

$(document).on('click', '.pos-category-chip', function() {
    activeStockCategory = String($(this).attr('data-category') || 'all');
    displaySearchResults(getFilteredStock());
});

function enhanceQuantityControls(tableBody) {
    $(tableBody).find('tr:not(.blank-row) td.quantity').each(function() {
        const cell = this;
        if (cell.getAttribute('data-pos-quantity-enhanced') === '1') {
            return;
        }

        const initialValue = String($(cell).text()).trim() || '1';
        cell.removeAttribute('contenteditable');
        cell.classList.remove('quantity');
        cell.classList.add('pos-quantity-cell');
        cell.setAttribute('data-pos-quantity-enhanced', '1');
        cell.innerHTML = '<button type="button" class="pos-qty-step pos-qty-decrease" aria-label="Decrease quantity">−</button>' +
            '<span class="quantity" contenteditable="true" inputmode="numeric" role="spinbutton" aria-label="Quantity" aria-valuemin="1">' +
            escapeStockHtml(initialValue) + '</span>' +
            '<button type="button" class="pos-qty-step pos-qty-increase" aria-label="Increase quantity">+</button>';
    });
}
