<?php
header('Content-Type: application/json; charset=utf-8');
$PathPrefix = '../';
include('../includes/globalsession.inc');

function salonAppointmentResponse($success, $message, $code = 200, $extra = array()) {
    http_response_code($code);
    echo json_encode(array_merge(array('success'=>$success,'message'=>$message), $extra));
    exit;
}
if (empty($_SESSION['DatabaseName']) || empty($_SESSION['UserID'])) {
    salonAppointmentResponse(false, 'Your session has expired. Sign in again.', 401);
}
$token = isset($_SERVER['HTTP_X_POS_FORMID']) ? (string)$_SERVER['HTTP_X_POS_FORMID'] : '';
if (empty($_SESSION['FormID']) || !hash_equals((string)$_SESSION['FormID'], $token)) {
    salonAppointmentResponse(false, 'The POS security token has expired. Reload the terminal.', 403);
}
if (!isset($db) || !($db instanceof mysqli)) {
    salonAppointmentResponse(false, 'The company database connection is unavailable.', 500);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { salonAppointmentResponse(false, 'POST is required.', 405); }
$id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
if ($id < 1) { salonAppointmentResponse(false, 'A valid appointment ID is required.', 400); }

try {
    $db->begin_transaction();
    $lock = $db->prepare("SELECT a.appointment_id,a.appointment_reference,a.customer_name,a.customer_phone,
            a.appointment_start,a.appointment_status,l.appointment_line_id,l.service_id,l.hairdresser_id,
            l.agreed_price,l.duration_minutes,sm.descrip AS service_name,s.pos_itemcode,s.pos_barcode,s.is_active,sm.units,vc.vat AS vat_rate,
            h.full_name,h.is_active AS hairdresser_active,
            (SELECT COUNT(*) FROM salon_appointment_lines x WHERE x.appointment_id=a.appointment_id) AS line_count
        FROM salon_appointments a
        INNER JOIN salon_appointment_lines l ON l.appointment_id=a.appointment_id
        INNER JOIN salon_services s ON s.service_id=l.service_id
        INNER JOIN stockmaster sm ON sm.itemcode=s.pos_itemcode AND sm.barcode=s.pos_barcode
        INNER JOIN inventorypostinggroup ipg ON ipg.code=sm.postinggroup
        INNER JOIN vatcategory vc ON vc.vatc=ipg.vatcategory
        LEFT JOIN salon_hairdressers h ON h.hairdresser_id=l.hairdresser_id
        WHERE a.appointment_id=? FOR UPDATE");
    if (!$lock) { throw new Exception('Could not prepare appointment lookup: '.$db->error); }
    $lock->bind_param('i', $id);
    if (!$lock->execute()) { throw new Exception('Could not load appointment: '.$lock->error); }
    $result = $lock->get_result();
    $appointment = $result->fetch_assoc();
    $lock->close();
    if (!$appointment) { throw new Exception('Appointment not found or it has no service line.'); }
    if (!in_array($appointment['appointment_status'], array('arrived','in_service'), true)) {
        throw new Exception('This appointment is '.$appointment['appointment_status'].' and cannot be started.');
    }
    if ((int)$appointment['line_count'] !== 1) {
        throw new Exception('This appointment has multiple service lines. It must be reviewed before starting checkout.');
    }
    if (empty($appointment['is_active']) || empty($appointment['hairdresser_id']) ||
        empty($appointment['hairdresser_active']) || empty($appointment['pos_itemcode']) ||
        empty($appointment['pos_barcode']) || !is_numeric($appointment['agreed_price']) || !is_numeric($appointment['vat_rate'])) {
        throw new Exception('The booked service needs an active service, mapped ERP POS item and assigned active hairdresser.');
    }
    $linked = $db->prepare("SELECT document_no FROM salon_appointment_transactions WHERE appointment_id=? LIMIT 1");
    if (!$linked) { throw new Exception('Could not check for an existing POS sale: '.$db->error); }
    $linked->bind_param('i', $id);
    $linked->execute();
    $existing = $linked->get_result()->fetch_assoc();
    $linked->close();
    if ($existing) { throw new Exception('This appointment has already been checked out on POS receipt '.$existing['document_no'].'.'); }

    $assignment = $db->prepare("SELECT COUNT(*) AS assigned FROM salon_hairdresser_services
        WHERE service_id=? AND hairdresser_id=? AND is_active=1");
    if (!$assignment) { throw new Exception('Could not validate the hairdresser service assignment: '.$db->error); }
    $serviceId = (int)$appointment['service_id'];
    $hairdresserId = (int)$appointment['hairdresser_id'];
    $assignment->bind_param('ii', $serviceId, $hairdresserId);
    if (!$assignment->execute()) { throw new Exception('Could not validate the hairdresser service assignment: '.$assignment->error); }
    $assigned = $assignment->get_result()->fetch_assoc();
    $assignment->close();
    if (!$assigned || (int)$assigned['assigned'] !== 1) {
        throw new Exception('The assigned hairdresser is no longer active for this service.');
    }
    if ($appointment['appointment_status'] !== 'in_service') {
        $update = $db->prepare("UPDATE salon_appointments SET appointment_status='in_service' WHERE appointment_id=?");
        $update->bind_param('i', $id);
        if (!$update->execute()) { throw new Exception('Could not start the appointment: '.$update->error); }
        $update->close();
        $update = $db->prepare("UPDATE salon_appointment_lines SET line_status='in_service' WHERE appointment_id=?");
        $update->bind_param('i', $id);
        if (!$update->execute()) { throw new Exception('Could not update the appointment service status: '.$update->error); }
        $update->close();
    }
    $db->commit();
    salonAppointmentResponse(true, 'Appointment loaded into Salon POS.', 200, array(
        'appointment'=>array(
            'id'=>(int)$appointment['appointment_id'],
            'reference'=>$appointment['appointment_reference'],
            'customer'=>$appointment['customer_name'],
            'phone'=>$appointment['customer_phone'],
            'start'=>$appointment['appointment_start'],
            'serviceId'=>(int)$appointment['service_id'],
            'service'=>$appointment['service_name'],
            'hairdresserId'=>(int)$appointment['hairdresser_id'],
            'hairdresser'=>$appointment['full_name'],
            'price'=>(float)$appointment['agreed_price'],
            'vatRate'=>(float)$appointment['vat_rate'],
            'itemcode'=>$appointment['pos_itemcode'],
            'barcode'=>$appointment['pos_barcode']
        )
    ));
} catch (Throwable $e) {
    $db->rollback();
    error_log('Salon appointment POS load failed: '.$e->getMessage());
    salonAppointmentResponse(false, $e->getMessage(), 409);
}
?>