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

    function addSalonService(service, hairdresser, appointmentLine) {
        var table = document.querySelector('#sales-table tbody');
        if (!table) { return; }
        var empty = table.querySelector('.blank-row');
        if (empty) { empty.remove(); }
        var line = calculateLine(service.price, 1, service.vat_rate);
        var row = document.createElement('tr');
        row.className = 'selectable salon-service-line' + (appointmentLine ? ' appointment-service-line' : '');
        row.setAttribute('data-itemcode', service.itemcode);
        row.setAttribute('data-barcode', service.barcode);
        row.setAttribute('data-salon-service-id', service.id);
        row.setAttribute('data-hairdresser-id', hairdresser.id);
        if (appointmentLine) { row.setAttribute('data-appointment-line', '1'); }
        row.innerHTML =
            '<td>' + escapeHtml(service.barcode) + '</td>' +
            '<td>' + escapeHtml(service.name) + ' <small>— ' + escapeHtml(hairdresser.name) + '</small></td>' +
            '<td ' + (appointmentLine ? '' : 'contenteditable="true"') + ' class="quantity">1</td>' +
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

    function loadAppointment(appointmentId) {
        if (!appointmentId) { return; }
        $('#salon-appointment-summary').show().html('<strong>Loading appointment...</strong>');
        $.ajax({
            url: 'POSajax/salon-appointment.php',
            type: 'POST',
            dataType: 'json',
            data: {appointment_id: appointmentId},
            headers: {'X-POS-FormID': window.POS_FORM_ID || ''}
        }).done(function (response) {
            if (!response || !response.success || !response.appointment) {
                $('#salon-appointment-summary').html('<div class="alert alert-danger mb-0">' + escapeHtml(response && response.message ? response.message : 'Appointment could not be loaded.') + '</div>');
                $('.salon-service-picker').hide();
                return;
            }
            var appointment = response.appointment;
            $('#salon-appointment-summary').html(
                '<div class="salon-appointment-banner"><div><span class="pos-eyebrow">APPOINTMENT ' + escapeHtml(appointment.reference) + '</span>' +
                '<strong>' + escapeHtml(appointment.customer) + '</strong><small>' + escapeHtml(appointment.phone || '') + '</small></div>' +
                '<div><span class="pos-eyebrow">BOOKED SERVICE</span><strong>' + escapeHtml(appointment.service) + '</strong><small>' +
                escapeHtml(appointment.hairdresser) + '</small></div><div><span class="pos-eyebrow">STATUS</span><strong>In Service</strong></div></div>'
            );
            var service = {
                id: appointment.serviceId,
                name: appointment.service,
                price: appointment.price,
                itemcode: appointment.itemcode,
                barcode: appointment.barcode,
                vat_rate: appointment.vatRate
            };
                        var hairdresser = {id: appointment.hairdresserId, name: appointment.hairdresser};
            addSalonService(service, hairdresser, true);
            $('#sales-table').attr('data-appointment-id', appointment.id);
            $('.salon-add-service[data-service-id="' + appointment.serviceId + '"]').prop('disabled', true).text('Booked in appointment');
            $('#salon-service-catalogue').prepend('<div class="alert alert-info w-100">The booked service is already in the Sale Items grid. Add only extra services or retail products below.</div>');
        }).fail(function (xhr) {
            var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Appointment could not be loaded.';
            $('#salon-appointment-summary').html('<div class="alert alert-danger mb-0">' + escapeHtml(message) + '</div>');
            $('.salon-service-picker').hide();
        });
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
        }).always(function () {
            if (Number(window.POS_APPOINTMENT_ID) > 0) { loadAppointment(Number(window.POS_APPOINTMENT_ID)); }
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
