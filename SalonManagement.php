<?php
include('includes/session.inc');
$Title = _('Salon Management');
include('includes/header.inc');

if (!isset($db) || !($db instanceof mysqli)) {
    prnMsg('The company database connection is unavailable.', 'error');
    include('includes/footer.inc');
    exit;
}
$db->set_charset('utf8mb4');

function salon_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function salon_post($key, $default = '') {
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}
function salon_message($message, $type) {
    echo '<div class="alert alert-' . salon_h($type) . '" role="alert">' . salon_h($message) . '</div>';
}
function salon_exec($db, $sql, $types, $values) {
    $stmt = $db->prepare($sql);
    if (!$stmt) { throw new Exception($db->error); }
    if ($types !== '') {
        $bind = array($types);
        foreach ($values as $key => $value) { $bind[] = &$values[$key]; }
        call_user_func_array(array($stmt, 'bind_param'), $bind);
    }
    if (!$stmt->execute()) { $error = $stmt->error; $stmt->close(); throw new Exception($error); }
    return $stmt;
}

$view = isset($_GET['view']) ? (string)$_GET['view'] : 'hairdressers';
$allowedViews = array('hairdressers','services','assignments','commissions','appointments','payouts');
if (!in_array($view, $allowedViews, true)) { $view = 'hairdressers'; }
$notice = '';
$noticeType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['FormID']) || !isset($_POST['FormID']) || !hash_equals((string)$_SESSION['FormID'], (string)$_POST['FormID'])) {
        $notice = 'The form security token has expired. Reload the page and try again.';
        $noticeType = 'danger';
    } else {
        try {
            $action = salon_post('action');
            if ($action === 'save_hairdresser') {
                $id = (int)salon_post('hairdresser_id', '0');
                $code = strtoupper(salon_post('hairdresser_code'));
                $name = salon_post('full_name');
                $phone = salon_post('phone');
                $email = salon_post('email');
                $active = salon_post('is_active', '1') === '1' ? 1 : 0;
                $notes = salon_post('notes');
                if ($code === '' || $name === '') { throw new Exception('Hairdresser code and full name are required.'); }
                if ($id > 0) {
                    $stmt = salon_exec($db, "UPDATE salon_hairdressers SET hairdresser_code=?,full_name=?,phone=?,email=?,is_active=?,notes=? WHERE hairdresser_id=?", 'ssssisi', array($code,$name,$phone,$email,$active,$notes,$id));
                } else {
                    $stmt = salon_exec($db, "INSERT INTO salon_hairdressers (hairdresser_code,full_name,phone,email,is_active,notes) VALUES (?,?,?,?,?,?)", 'ssssis', array($code,$name,$phone,$email,$active,$notes));
                }
                $stmt->close();
                $notice = 'Hairdresser record saved.';
                $view = 'hairdressers';
            } elseif ($action === 'save_service') {
                $id = (int)salon_post('service_id', '0');
                $code = strtoupper(salon_post('service_code'));
                $name = salon_post('service_name');
                $category = salon_post('service_category');
                $description = salon_post('description');
                $duration = (int)salon_post('duration_minutes', '30');
                $price = salon_post('standard_price', '0');
                $stockmasterPkey = (int)salon_post('stockmaster_pkey', '0');
                $active = salon_post('is_active', '1') === '1' ? 1 : 0;
                if ($code === '' || $name === '') { throw new Exception('Service code and service name are required.'); }
                if ($stockmasterPkey < 1) { throw new Exception('Select an ERP POS item code and barcode.'); }
                if ($duration < 1 || !is_numeric($price) || (float)$price < 0) { throw new Exception('Enter a positive service duration and valid non-negative salon selling price.'); }

                $mappingStmt = salon_exec($db, "SELECT pkey FROM stockmaster
                    WHERE pkey=? AND itemcode IS NOT NULL AND itemcode<>'' AND barcode IS NOT NULL AND barcode<>''
                      AND (inactive IS NULL OR inactive=0) LIMIT 1", 'i', array($stockmasterPkey));
                $mapping = $mappingStmt->get_result()->fetch_assoc();
                $mappingStmt->close();
                if (!$mapping) { throw new Exception('The selected ERP stock item is no longer active or available. Refresh the service form and select it again.'); }

                if ($id > 0) {
                    $stmt = salon_exec($db, "UPDATE salon_services SET service_code=?,service_name=?,service_category=?,description=?,duration_minutes=?,standard_price=?,stockmaster_pkey=?,is_active=? WHERE service_id=?", 'ssssidiii', array($code,$name,$category,$description,$duration,(float)$price,$stockmasterPkey,$active,$id));
                    $stmt->close();
                } else {
                    $stmt = salon_exec($db, "INSERT INTO salon_services (service_code,service_name,service_category,description,duration_minutes,standard_price,stockmaster_pkey,is_active) VALUES (?,?,?,?,?,?,?,?)", 'ssssidis', array($code,$name,$category,$description,$duration,(float)$price,$stockmasterPkey,$active));
                    $stmt->close();
                }
                $notice = 'Salon service saved.';
                $view = 'services';
            } elseif ($action === 'save_assignment') {
                $hairdresserId = (int)salon_post('hairdresser_id', '0');
                $serviceId = (int)salon_post('service_id', '0');
                $active = salon_post('is_active', '1') === '1' ? 1 : 0;
                if ($hairdresserId < 1 || $serviceId < 1) { throw new Exception('Select a hairdresser and a service.'); }
                $stmt = salon_exec($db, "INSERT INTO salon_hairdresser_services (hairdresser_id,service_id,is_active) VALUES (?,?,?) ON DUPLICATE KEY UPDATE is_active=VALUES(is_active)", 'iii', array($hairdresserId,$serviceId,$active));
                $stmt->close();
                $notice = 'Service assignment saved.';
                $view = 'assignments';
            } elseif ($action === 'save_rule') {
                $hairdresserId = (int)salon_post('hairdresser_id', '0');
                $serviceId = (int)salon_post('service_id', '0');
                $hairdresserValue = $hairdresserId > 0 ? $hairdresserId : null;
                $serviceValue = $serviceId > 0 ? $serviceId : null;
                $ruleId = (int)salon_post('commission_rule_id', '0');
                $type = salon_post('commission_type');
                $value = salon_post('commission_value');
                $from = salon_post('effective_from');
                $to = salon_post('effective_to');
                $active = salon_post('is_active', '1') === '1' ? 1 : 0;
                if (!in_array($type, array('percentage','fixed'), true) || !is_numeric($value) || (float)$value < 0 || $from === '') { throw new Exception('Enter a valid commission type, value and effective date.'); }
                if ($type === 'percentage' && (float)$value > 100) { throw new Exception('Percentage commission cannot exceed 100%.'); }
                $fromDate = DateTime::createFromFormat('Y-m-d', $from);
                $toDate = $to !== '' ? DateTime::createFromFormat('Y-m-d', $to) : null;
                if (!$fromDate || $fromDate->format('Y-m-d') !== $from || ($to !== '' && (!$toDate || $toDate->format('Y-m-d') !== $to))) { throw new Exception('Enter valid commission effective dates.'); }
                if ($to !== '' && $to < $from) { throw new Exception('The end date cannot be earlier than the effective date.'); }
                if ($ruleId > 0) {
                    $stmt = salon_exec($db, "UPDATE salon_commission_rules SET hairdresser_id=?,service_id=?,commission_type=?,commission_value=?,effective_from=?,effective_to=?,is_active=? WHERE commission_rule_id=?", 'iisdssii', array($hairdresserValue,$serviceValue,$type,(float)$value,$from,$to !== '' ? $to : null,$active,$ruleId));
                } else {
                    $stmt = salon_exec($db, "INSERT INTO salon_commission_rules (hairdresser_id,service_id,commission_type,commission_value,effective_from,effective_to,is_active) VALUES (?,?,?,?,?,?,?)", 'iisdssi', array($hairdresserValue,$serviceValue,$type,(float)$value,$from,$to !== '' ? $to : null,$active));
                }
                $stmt->close();
                $notice = 'Commission rule saved. More specific rules take precedence over general rules.';
                $view = 'commissions';
            } elseif ($action === 'save_appointment') {
                $customer = salon_post('customer_name');
                $phone = salon_post('customer_phone');
                $start = salon_post('appointment_start');
                $serviceId = (int)salon_post('service_id', '0');
                $hairdresserId = (int)salon_post('hairdresser_id', '0');
                $notes = salon_post('notes');
                if ($customer === '' || $start === '' || $serviceId < 1 || $hairdresserId < 1) { throw new Exception('Customer, appointment date/time, service and hairdresser are required.'); }
                $parsedStart = DateTime::createFromFormat('Y-m-d\\TH:i', $start);
                if (!$parsedStart || $parsedStart->format('Y-m-d\\TH:i') !== $start) { throw new Exception('Enter a valid appointment date and time.'); }
                $startDate = $parsedStart->format('Y-m-d H:i:s');
                $serviceStmt = salon_exec($db, "SELECT standard_price,duration_minutes FROM salon_services WHERE service_id=? AND is_active=1", 'i', array($serviceId));
                $service = $serviceStmt->get_result()->fetch_assoc();
                $serviceStmt->close();
                if (!$service) { throw new Exception('The selected service is unavailable.'); }
                if ($hairdresserId > 0) {
                    $assignmentStmt = salon_exec($db, "SELECT COUNT(*) AS assigned FROM salon_hairdresser_services hs INNER JOIN salon_hairdressers h ON h.hairdresser_id=hs.hairdresser_id WHERE hs.service_id=? AND hs.hairdresser_id=? AND hs.is_active=1 AND h.is_active=1", 'ii', array($serviceId,$hairdresserId));
                    $assignment = $assignmentStmt->get_result()->fetch_assoc();
                    $assignmentStmt->close();
                    if (!$assignment || (int)$assignment['assigned'] !== 1) { throw new Exception('The selected hairdresser is not assigned to this service.'); }
                }
                $endDate = date('Y-m-d H:i:s', strtotime($startDate) + ((int)$service['duration_minutes'] * 60));
                $reference = 'SAL-' . date('ymd') . '-' . strtoupper(substr(sha1(uniqid('', true)), 0, 6));
                $db->begin_transaction();
                $stmt = salon_exec($db, "INSERT INTO salon_appointments (appointment_reference,customer_name,customer_phone,appointment_start,appointment_end,notes,created_by) VALUES (?,?,?,?,?,?,?)", 'sssssss', array($reference,$customer,$phone,$startDate,$endDate,$notes,(string)$_SESSION['UserID']));
                $appointmentId = $stmt->insert_id;
                $stmt->close();
                $hairdresserValue = $hairdresserId > 0 ? $hairdresserId : null;
                $stmt = salon_exec($db, "INSERT INTO salon_appointment_lines (appointment_id,service_id,hairdresser_id,agreed_price,duration_minutes) VALUES (?,?,?,?,?)", 'iiidi', array($appointmentId,$serviceId,$hairdresserValue,(float)$service['standard_price'],(int)$service['duration_minutes']));
                $stmt->close();
                $db->commit();
                $notice = 'Appointment ' . $reference . ' booked.';
                $view = 'appointments';
            } elseif ($action === 'appointment_status') {
                $id = (int)salon_post('appointment_id', '0');
                $status = salon_post('appointment_status');
                if ($id < 1 || !in_array($status, array('arrived','cancelled','no_show'), true)) {
                    throw new Exception('Invalid appointment status. Start Service and checkout control the in-service and completed statuses.');
                }
                $db->begin_transaction();
                $lock = salon_exec($db, "SELECT appointment_status FROM salon_appointments WHERE appointment_id=? FOR UPDATE", 'i', array($id));
                $current = $lock->get_result()->fetch_assoc();
                $lock->close();
                if (!$current) { throw new Exception('Appointment not found.'); }
                $linked = salon_exec($db, "SELECT appointment_id FROM salon_appointment_transactions WHERE appointment_id=? LIMIT 1", 'i', array($id));
                $hasSale = (bool)$linked->get_result()->fetch_assoc();
                $linked->close();
                if ($hasSale || in_array($current['appointment_status'], array('in_service','completed'), true)) {
                    throw new Exception('An appointment that has started or has a POS transaction cannot be cancelled or marked as a no-show.');
                }
                if (in_array($current['appointment_status'], array('cancelled','no_show'), true)) {
                    throw new Exception('A cancelled or no-show appointment cannot be reopened. Create a new appointment instead.');
                }
                $stmt = salon_exec($db, "UPDATE salon_appointments SET appointment_status=? WHERE appointment_id=?", 'si', array($status,$id));
                $stmt->close();
                $lineStatus = $status === 'arrived' ? 'arrived' : 'cancelled';
                $stmt = salon_exec($db, "UPDATE salon_appointment_lines SET line_status=? WHERE appointment_id=?", 'si', array($lineStatus,$id));
                $stmt->close();
                $db->commit();
                $notice = 'Appointment status updated.';
                $view = 'appointments';
            } elseif ($action === 'pay_commission') {
                $hairdresserId = (int)salon_post('hairdresser_id', '0');
                $reference = strtoupper(salon_post('payout_reference'));
                if ($hairdresserId < 1 || $reference === '') { throw new Exception('Select a hairdresser and enter the payout reference.'); }
                $db->begin_transaction();
                $stmt = salon_exec($db, "UPDATE salon_service_commissions SET commission_status='paid',payout_reference=?,payout_by=?,payout_date=NOW() WHERE hairdresser_id=? AND commission_status='pending'", 'ssi', array($reference,(string)$_SESSION['UserID'],$hairdresserId));
                $count = $stmt->affected_rows;
                $stmt->close();
                $db->commit();
                $notice = $count . ' commission record(s) marked as paid. Reference: ' . $reference;
                $view = 'payouts';
            } else {
                throw new Exception('Unsupported salon operation.');
            }
        } catch (Exception $e) {
            if ($db->errno || $db->connect_errno) { /* connection state is handled by the database layer */ }
            if ($db->thread_id) { @$db->rollback(); }
            $notice = $e->getMessage();
            $noticeType = 'danger';
        }
    }
}

echo '<div class="container-fluid" style="max-width:1500px;padding:18px 8px;">';
echo '<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:18px;"><div><h2 style="margin:0;">Salon operations</h2><div class="text-muted">Staff, services, appointments and commission control</div></div><div class="d-flex gap-2"><a class="btn btn-primary" href="SalonPOS.php">Open Salon POS</a><a class="btn btn-outline-secondary" href="posindex.php">Retail POS</a></div></div>';
if ($notice !== '') { salon_message($notice, $noticeType); }
echo '<nav class="nav nav-pills flex-wrap" style="gap:6px;margin-bottom:20px;">';
$labels = array('hairdressers'=>'Hairdressers','services'=>'Services','assignments'=>'Staff skills','commissions'=>'Commission rules','appointments'=>'Appointments','payouts'=>'Commission payouts');
foreach ($labels as $key=>$label) {
    echo '<a class="nav-link ' . ($view === $key ? 'active' : '') . '" href="index.php?link=SalonManagement&view=' . $key . '">' . salon_h($label) . '</a>';
}
echo '</nav>';

function salon_rows($db, $sql) {
    $result = $db->query($sql);
    if (!$result) { throw new Exception($db->error); }
    $rows = array();
    while ($row = $result->fetch_assoc()) { $rows[] = $row; }
    return $rows;
}
$hairdressers = salon_rows($db, "SELECT hairdresser_id,hairdresser_code,full_name,phone,email,is_active,notes FROM salon_hairdressers ORDER BY full_name");
$services = salon_rows($db, "SELECT s.service_id,s.service_code,s.service_name,s.service_category,s.description,s.duration_minutes,s.standard_price,s.stockmaster_pkey,sm.itemcode AS pos_itemcode,sm.barcode AS pos_barcode,s.is_active FROM salon_services s LEFT JOIN stockmaster sm ON sm.pkey=s.stockmaster_pkey ORDER BY s.service_name");
$stockItems = salon_rows($db, "SELECT sm.pkey,sm.itemcode,sm.barcode,sm.descrip,sc.categorydescription FROM stockmaster sm LEFT JOIN stockcategory sc ON sc.categoryid=sm.category WHERE sm.barcode IS NOT NULL AND sm.barcode <> '' AND sm.itemcode IS NOT NULL AND sm.itemcode <> '' AND (sm.inactive IS NULL OR sm.inactive=0) ORDER BY sm.itemcode");
$editingHairdresser = null;
if (isset($_GET['edit_hairdresser'])) {
    foreach ($hairdressers as $candidate) { if ((int)$candidate['hairdresser_id'] === (int)$_GET['edit_hairdresser']) { $editingHairdresser = $candidate; break; } }
}
$editingService = null;
if (isset($_GET['edit_service'])) {
    foreach ($services as $candidate) { if ((int)$candidate['service_id'] === (int)$_GET['edit_service']) { $editingService = $candidate; break; } }
}

if ($view === 'hairdressers') {
    echo '<div class="row g-3"><div class="col-lg-4"><div class="card"><div class="card-header">Register hairdresser</div><div class="card-body"><form method="post"><input type="hidden" name="FormID" value="' . salon_h($_SESSION['FormID']) . '"><input type="hidden" name="action" value="save_hairdresser"><input type="hidden" name="hairdresser_id" value="' . (int)($editingHairdresser ? $editingHairdresser['hairdresser_id'] : 0) . '"><div class="mb-2"><label class="form-label">Staff code</label><input class="form-control" name="hairdresser_code" maxlength="20" value="' . salon_h($editingHairdresser ? $editingHairdresser['hairdresser_code'] : '') . '" required></div><div class="mb-2"><label class="form-label">Full name</label><input class="form-control" name="full_name" maxlength="100" value="' . salon_h($editingHairdresser ? $editingHairdresser['full_name'] : '') . '" required></div><div class="mb-2"><label class="form-label">Phone</label><input class="form-control" name="phone" maxlength="30" value="' . salon_h($editingHairdresser ? $editingHairdresser['phone'] : '') . '"></div><div class="mb-2"><label class="form-label">Email</label><input class="form-control" type="email" name="email" maxlength="120" value="' . salon_h($editingHairdresser ? $editingHairdresser['email'] : '') . '"></div><div class="mb-2"><label class="form-label">Status</label><select class="form-select" name="is_active"><option value="1" ' . (!$editingHairdresser || $editingHairdresser['is_active'] ? 'selected' : '') . '>Active</option><option value="0" ' . ($editingHairdresser && !$editingHairdresser['is_active'] ? 'selected' : '') . '>Inactive</option></select></div><div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2">' . salon_h($editingHairdresser ? $editingHairdresser['notes'] : '') . '</textarea></div><button class="btn btn-primary">' . ($editingHairdresser ? 'Update hairdresser' : 'Save hairdresser') . '</button></form></div></div></div><div class="col-lg-8"><div class="card"><div class="card-header">Hairdresser roster</div><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Code</th><th>Name</th><th>Phone</th><th>Email</th><th>Status</th><th>Action</th></tr></thead><tbody>';
    foreach ($hairdressers as $row) { echo '<tr><td>' . salon_h($row['hairdresser_code']) . '</td><td>' . salon_h($row['full_name']) . '</td><td>' . salon_h($row['phone']) . '</td><td>' . salon_h($row['email']) . '</td><td>' . ($row['is_active'] ? 'Active' : 'Inactive') . '</td><td><a href="index.php?link=SalonManagement&view=hairdressers&edit_hairdresser=' . (int)$row['hairdresser_id'] . '">Edit</a></td></tr>'; }
    if (!$hairdressers) { echo '<tr><td colspan="6" class="text-muted">No hairdressers registered.</td></tr>'; }
    echo '</tbody></table></div></div></div></div>';
} elseif ($view === 'services') {
    echo '<div class="row g-3"><div class="col-lg-4"><div class="card"><div class="card-header">Add salon service</div><div class="card-body"><form method="post"><input type="hidden" name="FormID" value="' . salon_h($_SESSION['FormID']) . '"><input type="hidden" name="action" value="save_service"><input type="hidden" name="service_id" value="' . (int)($editingService ? $editingService['service_id'] : 0) . '"><div class="mb-2"><label class="form-label">Service code</label><input class="form-control" name="service_code" maxlength="30" value="' . salon_h($editingService ? $editingService['service_code'] : '') . '" required></div><div class="mb-2"><label class="form-label">Service name</label><input class="form-control" name="service_name" maxlength="120" value="' . salon_h($editingService ? $editingService['service_name'] : '') . '" required></div><div class="mb-2"><label class="form-label">Category</label><input class="form-control" name="service_category" maxlength="60" value="' . salon_h($editingService ? $editingService['service_category'] : '') . '" placeholder="Hair, nails, treatment..."></div><div class="mb-2"><label class="form-label">Duration (minutes)</label><input class="form-control" type="number" name="duration_minutes" min="1" value="' . (int)($editingService ? $editingService['duration_minutes'] : 30) . '" required></div><div class="mb-2"><label class="form-label">Standard price (KES)</label><input class="form-control" type="number" name="standard_price" min="0" step="0.01" value="' . number_format((float)($editingService ? $editingService['standard_price'] : 0),2,'.','') . '" required></div><div class="mb-2"><label class="form-label">ERP POS item code</label><select class="form-select" id="salon-pos-itemcode" onchange="salonSyncPosMapping(this)"><option value="">Select ERP POS item</option>'; 
    foreach ($stockItems as $item) { echo '<option value="' . (int)$item['pkey'] . '" ' . ($editingService && (int)$editingService['stockmaster_pkey'] === (int)$item['pkey'] ? 'selected' : '') . '>' . salon_h($item['itemcode'] . ' — ' . $item['descrip'] . ' — ' . $item['barcode']) . '</option>'; }
    echo '</select></div><div class="mb-2"><label class="form-label">ERP POS barcode</label><select class="form-select" id="salon-pos-barcode" onchange="salonSyncPosMapping(this)"><option value="">Select ERP POS barcode</option>'; 
    foreach ($stockItems as $item) { echo '<option value="' . (int)$item['pkey'] . '" ' . ($editingService && (int)$editingService['stockmaster_pkey'] === (int)$item['pkey'] ? 'selected' : '') . '>' . salon_h($item['barcode'] . ' — ' . $item['itemcode'] . ' — ' . $item['descrip']) . '</option>'; }
    echo '</select></div><input type="hidden" id="salon-stockmaster-pkey" name="stockmaster_pkey" value="' . (int)($editingService ? $editingService['stockmaster_pkey'] : 0) . '"><script>function salonSyncPosMapping(control){var item=document.getElementById("salon-pos-itemcode");var barcode=document.getElementById("salon-pos-barcode");var pkey=document.getElementById("salon-stockmaster-pkey");if(!item||!barcode||!pkey||!control)return;var selected;if(control.id==="salon-pos-itemcode"){selected=item.options[item.selectedIndex];barcode.value=selected?selected.value:"";}else{selected=barcode.options[barcode.selectedIndex];item.value=selected?selected.value:"";}pkey.value=selected?selected.value:"";}document.addEventListener("DOMContentLoaded",function(){var item=document.getElementById("salon-pos-itemcode");if(item&&item.value){salonSyncPosMapping(item);}});</script><div class="mb-2"><label class="form-label">Status</label><select class="form-select" name="is_active"><option value="1" ' . (!$editingService || $editingService['is_active'] ? 'selected' : '') . '>Active</option><option value="0" ' . ($editingService && !$editingService['is_active'] ? 'selected' : '') . '>Inactive</option></select></div><div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2">' . salon_h($editingService ? $editingService['description'] : '') . '</textarea></div><button class="btn btn-primary">' . ($editingService ? 'Update service' : 'Save service') . '</button></form></div></div></div><div class="col-lg-8"><div class="card"><div class="card-header">Service catalogue</div><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Code</th><th>Service</th><th>Category</th><th>Duration</th><th>Price (KES)</th><th>ERP item</th><th>Barcode</th><th>Status</th><th>Action</th></tr></thead><tbody>';
    foreach ($services as $row) { echo '<tr><td>' . salon_h($row['service_code']) . '</td><td>' . salon_h($row['service_name']) . '</td><td>' . salon_h($row['service_category']) . '</td><td>' . (int)$row['duration_minutes'] . ' min</td><td>' . number_format((float)$row['standard_price'],2) . '</td><td>' . salon_h($row['pos_itemcode']) . '</td><td>' . salon_h($row['pos_barcode']) . '</td><td>' . ($row['is_active'] ? 'Active' : 'Inactive') . '</td><td><a href="index.php?link=SalonManagement&view=services&edit_service=' . (int)$row['service_id'] . '">Edit</a></td></tr>'; }
    if (!$services) { echo '<tr><td colspan="9" class="text-muted">No services configured.</td></tr>'; }
    echo '</tbody></table></div></div></div></div>';
} elseif ($view === 'assignments') {
    $rows = salon_rows($db, "SELECT hs.is_active,h.hairdresser_code,h.full_name,s.service_code,s.service_name FROM salon_hairdresser_services hs JOIN salon_hairdressers h ON h.hairdresser_id=hs.hairdresser_id JOIN salon_services s ON s.service_id=hs.service_id ORDER BY h.full_name,s.service_name");
    echo '<div class="card mb-3"><div class="card-header">Assign a service to a hairdresser</div><div class="card-body"><form method="post" class="row g-2 align-items-end"><input type="hidden" name="FormID" value="' . salon_h($_SESSION['FormID']) . '"><input type="hidden" name="action" value="save_assignment"><div class="col-md-4"><label class="form-label">Hairdresser</label><select class="form-select" name="hairdresser_id" required><option value="">Select staff</option>';
    foreach ($hairdressers as $row) { if ($row['is_active']) echo '<option value="' . (int)$row['hairdresser_id'] . '">' . salon_h($row['full_name']) . '</option>'; }
    echo '</select></div><div class="col-md-4"><label class="form-label">Service</label><select class="form-select" name="service_id" required><option value="">Select service</option>';
    foreach ($services as $row) { if ($row['is_active']) echo '<option value="' . (int)$row['service_id'] . '">' . salon_h($row['service_name']) . '</option>'; }
    echo '</select></div><div class="col-md-2"><label class="form-label">Assignment</label><select class="form-select" name="is_active"><option value="1">Enabled</option><option value="0">Disabled</option></select></div><div class="col-md-2"><button class="btn btn-primary w-100">Save</button></div></form></div></div><div class="card"><div class="card-header">Staff service permissions</div><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Hairdresser</th><th>Service</th><th>Status</th></tr></thead><tbody>';
    foreach ($rows as $row) echo '<tr><td>' . salon_h($row['hairdresser_code'] . ' — ' . $row['full_name']) . '</td><td>' . salon_h($row['service_code'] . ' — ' . $row['service_name']) . '</td><td>' . ($row['is_active'] ? 'Enabled' : 'Disabled') . '</td></tr>';
    if (!$rows) echo '<tr><td colspan="3" class="text-muted">No staff/service assignments configured.</td></tr>';
    echo '</tbody></table></div></div>';
} elseif ($view === 'commissions') {
    $rules = salon_rows($db, "SELECT r.*,h.full_name,s.service_name FROM salon_commission_rules r LEFT JOIN salon_hairdressers h ON h.hairdresser_id=r.hairdresser_id LEFT JOIN salon_services s ON s.service_id=r.service_id ORDER BY r.effective_from DESC,r.commission_rule_id DESC");
    $editingRule = null;
    if (isset($_GET['edit_rule_id'])) { foreach ($rules as $candidate) { if ((int)$candidate['commission_rule_id'] === (int)$_GET['edit_rule_id']) { $editingRule = $candidate; break; } } }
    echo '<div class="card mb-3"><div class="card-header">Configure commission rule</div><div class="card-body"><form method="post" class="row g-2 align-items-end"><input type="hidden" name="FormID" value="' . salon_h($_SESSION['FormID']) . '"><input type="hidden" name="action" value="save_rule"><input type="hidden" name="commission_rule_id" value="' . (int)($editingRule ? $editingRule['commission_rule_id'] : 0) . '"><div class="col-md-3"><label class="form-label">Hairdresser (blank = all)</label><select class="form-select" name="hairdresser_id"><option value="0" ' . (!$editingRule || !$editingRule['hairdresser_id'] ? 'selected' : '') . '>All hairdressers</option>';
    foreach ($hairdressers as $row) echo '<option value="' . (int)$row['hairdresser_id'] . '" ' . ($editingRule && (int)$editingRule['hairdresser_id'] === (int)$row['hairdresser_id'] ? 'selected' : '') . '>' . salon_h($row['full_name']) . '</option>';
    echo '</select></div><div class="col-md-3"><label class="form-label">Service (blank = all)</label><select class="form-select" name="service_id"><option value="0" ' . (!$editingRule || !$editingRule['service_id'] ? 'selected' : '') . '>All services</option>';
    foreach ($services as $row) echo '<option value="' . (int)$row['service_id'] . '" ' . ($editingRule && (int)$editingRule['service_id'] === (int)$row['service_id'] ? 'selected' : '') . '>' . salon_h($row['service_name']) . '</option>';
    echo '</select></div><div class="col-md-2"><label class="form-label">Rule type</label><select class="form-select" name="commission_type"><option value="percentage" ' . (!$editingRule || $editingRule['commission_type'] === 'percentage' ? 'selected' : '') . '>Percentage</option><option value="fixed" ' . ($editingRule && $editingRule['commission_type'] === 'fixed' ? 'selected' : '') . '>Fixed KES per service unit</option></select></div><div class="col-md-1"><label class="form-label">Value</label><input class="form-control" type="number" step="0.0001" min="0" name="commission_value" value="' . salon_h($editingRule ? $editingRule['commission_value'] : '') . '" required></div><div class="col-md-1"><label class="form-label">From</label><input class="form-control" type="date" name="effective_from" value="' . salon_h($editingRule ? $editingRule['effective_from'] : date('Y-m-d')) . '" required></div><div class="col-md-1"><label class="form-label">To</label><input class="form-control" type="date" name="effective_to" value="' . salon_h($editingRule ? $editingRule['effective_to'] : '') . '"></div><div class="col-md-1"><label class="form-label">Active</label><select class="form-select" name="is_active"><option value="1" ' . (!$editingRule || $editingRule['is_active'] ? 'selected' : '') . '>Yes</option><option value="0" ' . ($editingRule && !$editingRule['is_active'] ? 'selected' : '') . '>No</option></select></div><div class="col-12"><button class="btn btn-primary">' . ($editingRule ? 'Update commission rule' : 'Save commission rule') . '</button></div></form></div></div><div class="card"><div class="card-header">Commission rules</div><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Hairdresser</th><th>Service</th><th>Type</th><th>Value</th><th>Effective</th><th>Status</th><th>Action</th></tr></thead><tbody>';
    foreach ($rules as $row) echo '<tr><td>' . salon_h($row['full_name'] ? $row['full_name'] : 'All hairdressers') . '</td><td>' . salon_h($row['service_name'] ? $row['service_name'] : 'All services') . '</td><td>' . salon_h($row['commission_type']) . '</td><td>' . number_format((float)$row['commission_value'],4) . ($row['commission_type'] === 'percentage' ? '%' : ' KES') . '</td><td>' . salon_h($row['effective_from']) . ' – ' . salon_h($row['effective_to'] ? $row['effective_to'] : 'Open') . '</td><td>' . ($row['is_active'] ? 'Active' : 'Inactive') . '</td><td><a href="index.php?link=SalonManagement&view=commissions&edit_rule_id=' . (int)$row['commission_rule_id'] . '">Edit</a></td></tr>';
    if (!$rules) echo '<tr><td colspan="7" class="text-muted">No commission rules configured.</td></tr>';
    echo '</tbody></table></div></div>';
} elseif ($view === 'appointments') {
    $appointments = salon_rows($db, "SELECT a.appointment_id,a.appointment_reference,a.customer_name,a.customer_phone,a.appointment_start,a.appointment_status,s.service_name,h.full_name,t.document_no FROM salon_appointments a JOIN salon_appointment_lines l ON l.appointment_id=a.appointment_id JOIN salon_services s ON s.service_id=l.service_id LEFT JOIN salon_hairdressers h ON h.hairdresser_id=l.hairdresser_id LEFT JOIN salon_appointment_transactions t ON t.appointment_id=a.appointment_id WHERE a.appointment_start >= DATE_SUB(CURDATE(),INTERVAL 1 DAY) ORDER BY a.appointment_start LIMIT 250");
    echo '<div class="card mb-3"><div class="card-header">Book appointment</div><div class="card-body"><form method="post" class="row g-2 align-items-end"><input type="hidden" name="FormID" value="' . salon_h($_SESSION['FormID']) . '"><input type="hidden" name="action" value="save_appointment"><div class="col-md-3"><label class="form-label">Customer name</label><input class="form-control" name="customer_name" maxlength="100" required></div><div class="col-md-2"><label class="form-label">Phone</label><input class="form-control" name="customer_phone" maxlength="30"></div><div class="col-md-2"><label class="form-label">Date and time</label><input class="form-control" type="datetime-local" name="appointment_start" required></div><div class="col-md-2"><label class="form-label">Service</label><select class="form-select" name="service_id" required><option value="">Select service</option>';
    foreach ($services as $row) if ($row['is_active']) echo '<option value="' . (int)$row['service_id'] . '">' . salon_h($row['service_name']) . '</option>';
    echo '</select></div><div class="col-md-2"><label class="form-label">Hairdresser</label><select class="form-select" name="hairdresser_id" required><option value="">Select hairdresser</option>';
    foreach ($hairdressers as $row) if ($row['is_active']) echo '<option value="' . (int)$row['hairdresser_id'] . '">' . salon_h($row['full_name']) . '</option>';
    echo '</select></div><div class="col-md-1"><button class="btn btn-primary w-100">Book</button></div><div class="col-12"><label class="form-label">Notes</label><input class="form-control" name="notes" maxlength="1000"></div></form></div></div><div class="card"><div class="card-header">Appointment schedule</div><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Reference</th><th>Date/time</th><th>Customer</th><th>Phone</th><th>Service</th><th>Hairdresser</th><th>Status</th><th>Update</th></tr></thead><tbody>';
    foreach ($appointments as $row) {
        echo '<tr><td>' . salon_h($row['appointment_reference']) . '</td><td>' . salon_h($row['appointment_start']) . '</td><td>' . salon_h($row['customer_name']) . '</td><td>' . salon_h($row['customer_phone']) . '</td><td>' . salon_h($row['service_name']) . '</td><td>' . salon_h($row['full_name'] ? $row['full_name'] : 'Unassigned') . '</td><td>' . salon_h($row['appointment_status']) . '</td><td>';
        if (!empty($row['document_no'])) {
            echo 'POS receipt: ' . salon_h($row['document_no']);
        } elseif ($row['appointment_status'] === 'booked') {
            echo '<form method="post" class="d-inline-flex gap-1"><input type="hidden" name="FormID" value="' . salon_h($_SESSION['FormID']) . '"><input type="hidden" name="action" value="appointment_status"><input type="hidden" name="appointment_id" value="' . (int)$row['appointment_id'] . '"><select class="form-select form-select-sm" name="appointment_status"><option value="arrived">Check In / Arrived</option><option value="cancelled">Cancelled</option><option value="no_show">No show</option></select><button class="btn btn-sm btn-outline-primary">Update</button></form>';
        } elseif ($row['appointment_status'] === 'arrived') {
            echo '<a class="btn btn-sm btn-primary" href="SalonPOS.php?appointment_id=' . (int)$row['appointment_id'] . '">Start Service</a> ';
            echo '<form method="post" class="d-inline-flex gap-1"><input type="hidden" name="FormID" value="' . salon_h($_SESSION['FormID']) . '"><input type="hidden" name="action" value="appointment_status"><input type="hidden" name="appointment_id" value="' . (int)$row['appointment_id'] . '"><select class="form-select form-select-sm" name="appointment_status"><option value="arrived">Arrived</option><option value="cancelled">Cancelled</option><option value="no_show">No show</option></select><button class="btn btn-sm btn-outline-primary">Update</button></form>';
        } elseif ($row['appointment_status'] === 'in_service') {
            echo '<a class="btn btn-sm btn-outline-primary" href="SalonPOS.php?appointment_id=' . (int)$row['appointment_id'] . '">Resume POS</a>';
        } else {
            echo '—';
        }
        echo '</td></tr>';
    }
    if (!$appointments) echo '<tr><td colspan="8" class="text-muted">No appointments found.</td></tr>';
    echo '</tbody></table></div></div>';
} elseif ($view === 'payouts') {
    $pending = salon_rows($db, "SELECT h.hairdresser_id,h.hairdresser_code,h.full_name,COUNT(c.commission_id) AS records,SUM(c.commission_amount) AS amount FROM salon_hairdressers h JOIN salon_service_commissions c ON c.hairdresser_id=h.hairdresser_id WHERE c.commission_status='pending' GROUP BY h.hairdresser_id,h.hairdresser_code,h.full_name ORDER BY h.full_name");
    $history = salon_rows($db, "SELECT h.full_name,c.payout_reference,c.payout_by,c.payout_date,COUNT(*) AS records,SUM(c.commission_amount) AS amount FROM salon_service_commissions c JOIN salon_hairdressers h ON h.hairdresser_id=c.hairdresser_id WHERE c.commission_status='paid' GROUP BY h.full_name,c.payout_reference,c.payout_date ORDER BY c.payout_date DESC LIMIT 100");
    echo '<div class="card mb-3"><div class="card-header">Pending commission balances</div><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Hairdresser</th><th>Entries</th><th>Amount due (KES)</th><th>Record payout</th></tr></thead><tbody>';
    foreach ($pending as $row) echo '<tr><td>' . salon_h($row['hairdresser_code'] . ' — ' . $row['full_name']) . '</td><td>' . (int)$row['records'] . '</td><td>' . number_format((float)$row['amount'],2) . '</td><td><form method="post" class="d-flex gap-1"><input type="hidden" name="FormID" value="' . salon_h($_SESSION['FormID']) . '"><input type="hidden" name="action" value="pay_commission"><input type="hidden" name="hairdresser_id" value="' . (int)$row['hairdresser_id'] . '"><input class="form-control form-control-sm" name="payout_reference" placeholder="M-Pesa / voucher ref" required><button class="btn btn-sm btn-primary">Mark paid</button></form></td></tr>';
    if (!$pending) echo '<tr><td colspan="4" class="text-muted">No pending commission balances.</td></tr>';
    echo '</tbody></table></div></div><div class="card"><div class="card-header">Payout history</div><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Hairdresser</th><th>Reference</th><th>Recorded by</th><th>Paid date</th><th>Entries</th><th>Total (KES)</th></tr></thead><tbody>';
    foreach ($history as $row) echo '<tr><td>' . salon_h($row['full_name']) . '</td><td>' . salon_h($row['payout_reference']) . '</td><td>' . salon_h($row['payout_by']) . '</td><td>' . salon_h($row['payout_date']) . '</td><td>' . (int)$row['records'] . '</td><td>' . number_format((float)$row['amount'],2) . '</td></tr>';
    if (!$history) echo '<tr><td colspan="6" class="text-muted">No payouts have been recorded.</td></tr>';
    echo '</tbody></table></div></div>';
}
echo '</div>';
include('includes/footer.inc');
?>
