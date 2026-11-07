(function ($) {
    'use strict';

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[character];
        });
    }

    function calculateLine(price, quantity, vatRate) {
        var net = Math.round(price * quantity * 100) / 100;
        var vat = Number(window.POS_VAT_INCLUSIVE) === 1
            ? (vatRate > 0 ? net - (net / (1 + vatRate / 100)) : 0)
            : net * vatRate / 100;
        var gross = Number(window.POS_VAT_INCLUSIVE) === 1 ? net : net + vat;
        return {vat: Math.round(vat * 100) / 100, gross: Math.round(gross * 100) / 100};
    }

    function addSalonService(service, hairdresser) {
        var table = document.querySelector('#sales-table tbody');
        if (!table) { return; }
        var empty = table.querySelector('.blank-row');
        if (empty) { empty.remove(); }
        var line = calculateLine(service.price, 1, service.vat_rate);
        var row = document.createElement('tr');
        row.className = 'selectable salon-service-line';
        row.setAttribute('data-itemcode', service.itemcode);
        row.setAttribute('data-barcode', service.barcode);
        row.setAttribute('data-salon-service-id', service.id);
        row.setAttribute('data-hairdresser-id', hairdresser.id);
        row.innerHTML =
            '<td>' + escapeHtml(service.barcode) + '</td>' +
            '<td>' + escapeHtml(service.name) + ' <small>— ' + escapeHtml(hairdresser.name) + '</small></td>' +
            '<td contenteditable="true" class="quantity">1</td>' +
            '<td class="unit-price">' + Number(service.price).toFixed(2) + '</td>' +
            '<td class="vat-per">' + Number(service.vat_rate).toFixed(2) + '</td>' +
            '<td class="VAT-total">' + line.vat.toFixed(2) + '</td>' +
            '<td class="line-total">' + line.gross.toFixed(2) + '</td>';
        table.appendChild(row);
        if (typeof updateLineTotal === 'function') { updateLineTotal(); }
        $('#checkout-total').text($('#total-amount').text());
    }

    function renderServices(services) {
        var root = document.getElementById('salon-service-catalogue');
        if (!root) { return; }
        if (!services.length) {
            root.innerHTML = '<div class="alert alert-warning mb-0">No sellable salon services are configured. Map each service to an ERP POS item and assign at least one active hairdresser.</div>';
            return;
        }
        var html = '';
        services.forEach(function (service) {
            html += '<article class="salon-service-card">' +
                '<div><strong>' + escapeHtml(service.name) + '</strong><small>' +
                escapeHtml(service.category || 'Salon service') + ' · ' + service.duration + ' min</small></div>' +
                '<div class="salon-service-price">KES ' + Number(service.price).toLocaleString('en-KE', {minimumFractionDigits:2,maximumFractionDigits:2}) + '</div>' +
                '<label for="salon-staff-' + service.id + '">Hairdresser</label>' +
                '<select id="salon-staff-' + service.id + '" class="form-select salon-staff-select">';
            service.staff.forEach(function (person) {
                html += '<option value="' + person.id + '">' + escapeHtml(person.name) + '</option>';
            });
            html += '</select><button type="button" class="pos-primary-button salon-add-service" data-service-id="' + service.id + '">Add service</button></article>';
        });
        root.innerHTML = html;
        root._salonServices = services;
    }

    $(function () {
        if (!document.body.classList.contains('salon-pos-mode')) { return; }
        $.ajax({
            url: 'POSajax/salon-services.php',
            type: 'GET',
            dataType: 'json',
            headers: {'X-POS-FormID': window.POS_FORM_ID || ''}
        }).done(function (response) {
            if (response && response.success && Array.isArray(response.services)) {
                renderServices(response.services);
            } else {
                $('#salon-service-catalogue').html('<div class="alert alert-danger">Salon services could not be loaded.</div>');
            }
        }).fail(function () {
            $('#salon-service-catalogue').html('<div class="alert alert-danger">Salon services could not be loaded. Check the database migration and page permissions.</div>');
        });

        $(document).on('click', '.salon-add-service', function () {
            var root = document.getElementById('salon-service-catalogue');
            var serviceId = Number($(this).attr('data-service-id'));
            var service = (root && root._salonServices ? root._salonServices : []).find(function (item) {
                return Number(item.id) === serviceId;
            });
            if (!service) { return; }
            var staffId = Number($('#salon-staff-' + serviceId).val());
            var hairdresser = service.staff.find(function (person) { return Number(person.id) === staffId; });
            if (!hairdresser) {
                showCustomDialog('Select an assigned hairdresser for this service.');
                return;
            }
            addSalonService(service, hairdresser);
        });

        $('#sales-table tbody').on('input', '.salon-service-line .quantity', function () {
            var row = $(this).closest('tr');
            var price = Number(row.find('.unit-price').text()) || 0;
            var quantity = Number($(this).text()) || 0;
            var vatRate = Number(row.find('.vat-per').text()) || 0;
            var totals = calculateLine(price, quantity, vatRate);
            row.find('.VAT-total').text(totals.vat.toFixed(2));
            row.find('.line-total').text(totals.gross.toFixed(2));
        });
    });
})(jQuery);
