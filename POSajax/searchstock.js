let stockData = [];
let activeStockCategory = 'all';

// Load the product catalogue once; searching and category filtering happen locally
// for both the homepage quick-pick grid and the full catalogue modal.
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
            renderCatalogueTargets();
        },
        error: function() {
            showCustomDialog('Error loading stock data.');
        }
    });

    $('#search-input').on('input', function() {
        displaySearchResults(getFilteredStock($('#search-input').val()));
    });

    $('#pos-inline-filter').on('input', function() {
        displayInlineCatalogue(getFilteredStock($('#pos-inline-filter').val()));
    });

    $(document).on('click', '.search-result-item', function() {
        const selectedProduct = stockData[Number($(this).attr('data-stock-index'))];
        if (!selectedProduct) {
            showCustomDialog('The selected product is no longer available in the loaded catalogue. Search again.');
            return;
        }

        if (typeof addOrIncrementProductRow === 'function') {
            addOrIncrementProductRow(selectedProduct);
        }

        $('#search-modal').hide();
        $('#search-input').val('');
        $('#pos-barcode-scan').focus();
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

function getFilteredStock(rawQuery) {
    const query = String(rawQuery || '').toLowerCase().trim();
    return stockData.filter(function(item) {
        const barcode = String(item.barcode || '').toLowerCase();
        const description = String(item.description || '').toLowerCase();
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

function buildCategoryBarHtml() {
    const categories = [];
    stockData.forEach(function(product) {
        const category = String(product.category || '').trim();
        if (category && categories.indexOf(category) === -1) {
            categories.push(category);
        }
    });
    categories.sort(function(a, b) { return a.localeCompare(b); });

    let html = '<div class="pos-category-bar" aria-label="Product categories">';
    html += '<button type="button" class="pos-category-chip' + (activeStockCategory === 'all' ? ' active' : '') + '" data-category="all">All</button>';
    categories.forEach(function(category) {
        html += '<button type="button" class="pos-category-chip' + (activeStockCategory === category ? ' active' : '') + '" data-category="' + escapeStockHtml(category) + '">' + escapeStockHtml(category) + '</button>';
    });
    html += '</div>';
    return html;
}

function buildProductGridHtml(results, limit) {
    let html = '<div class="pos-product-grid" aria-live="polite">';
    const list = (typeof limit === 'number' && limit > 0) ? results.slice(0, limit) : results;
    if (list.length) {
        list.forEach(function(product) {
            const index = stockData.indexOf(product);
            html += '<button type="button" class="search-result-item pos-product-tile" data-stock-index="' + index + '">';
            html += '<span class="pos-product-category">' + escapeStockHtml(product.category || 'General') + '</span>';
            html += '<strong class="pos-product-name">' + escapeStockHtml(product.description || 'Unnamed product') + '</strong>';
            html += '<span class="pos-product-barcode">' + escapeStockHtml(product.barcode || '') + '</span>';
            html += '<span class="pos-product-price">KES ' + escapeStockHtml((parseFloat(product.unit_price) || 0).toFixed(2)) + '</span>';
            html += '</button>';
        });
    } else {
        html += '<p class="pos-no-products">No products match this search or category.</p>';
    }
    html += '</div>';
    return html;
}

function displaySearchResults(results) {
    $('#search-results').html(buildCategoryBarHtml() + buildProductGridHtml(results, 0));
}

function displayInlineCatalogue(results) {
    const $target = $('#pos-inline-catalogue');
    if (!$target.length) { return; }
    $target.html(buildCategoryBarHtml() + buildProductGridHtml(results, 60));
}

function renderCatalogueTargets() {
    displaySearchResults(getFilteredStock($('#search-input').val()));
    displayInlineCatalogue(getFilteredStock($('#pos-inline-filter').val()));
}

$(document).on('click', '.pos-category-chip', function() {
    activeStockCategory = String($(this).attr('data-category') || 'all');
    renderCatalogueTargets();
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
        cell.innerHTML = '<button type="button" class="pos-qty-step pos-qty-decrease" aria-label="Decrease quantity">-</button>' +
            '<span class="quantity" contenteditable="true" inputmode="numeric" role="spinbutton" aria-label="Quantity" aria-valuemin="1">' +
            escapeStockHtml(initialValue) + '</span>' +
            '<button type="button" class="pos-qty-step pos-qty-increase" aria-label="Increase quantity">+</button>';
    });
}
