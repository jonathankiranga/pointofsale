// Example AJAX request to fetch and display categories and products
function loadCategories() {
    $.get('/categories', function(data) {
        data.forEach(function(category) {
            $('#category-list').append(`<option value="${category.id}">${category.category_name}</option>`);
        });
    });
}


function loadProducts(categoryId) {
    $.get(`/products?category_id=${categoryId}`, function(data) {
        data.forEach(function(product) {
            $('#product-list').append(`<li>${product.product_name} - ${product.price}</li>`);
        });
    });
}


function getSelectedProducts() {
    const selectedProducts = [];
    // Iterate over each checkbox with class 'select-product'
    $('#product-table .select-product:checked').each(function() {
        const product = {
            id: $(this).data('id'),
            name: $(this).data('name'),
            barcode: $(this).data('barcode'),
            price: $(this).data('price')
        };
        // Push the selected product to the array
        selectedProducts.push(product);
    });
    
    return selectedProducts;
}


function importSelectedProducts() {
    const selectedProducts = getSelectedProducts();  // Assume a function that gathers selected products
    $.post('/import-products', { products: selectedProducts }, function(response) {
        if (response.success) {
            alert('Products imported successfully!');
        } else {
            alert('Failed to import products.');
        }
    });
}
