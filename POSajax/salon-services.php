<?php
header('Content-Type: application/json; charset=utf-8');
$PathPrefix = '../';
include('../includes/globalsession.inc');

if (empty($_SESSION['DatabaseName']) || empty($_SESSION['UserID'])) {
    http_response_code(401);
    echo json_encode(array('success'=>false,'message'=>'Your session has expired.'));
    exit;
}
$token = isset($_SERVER['HTTP_X_POS_FORMID']) ? (string)$_SERVER['HTTP_X_POS_FORMID'] : '';
if (empty($_SESSION['FormID']) || !hash_equals((string)$_SESSION['FormID'], $token)) {
    http_response_code(403);
    echo json_encode(array('success'=>false,'message'=>'The POS security token has expired. Reload the terminal.'));
    exit;
}
if (!isset($db) || !($db instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(array('success'=>false,'message'=>'The company database connection is unavailable.'));
    exit;
}

$sql = "SELECT s.service_id,s.service_code,s.service_name,s.service_category,
        s.duration_minutes,s.standard_price,sm.itemcode AS pos_itemcode,sm.barcode AS pos_barcode,
        sm.units,vc.vat AS vat_rate,ipg.vatcategory AS tax_category,
        ipg.defaultgl_sales AS sales_account,ipg.defaultgl_vat AS vat_account
    FROM salon_services s
    INNER JOIN stockmaster sm ON sm.pkey=s.stockmaster_pkey
    INNER JOIN inventorypostinggroup ipg ON ipg.code=sm.postinggroup
    INNER JOIN vatcategory vc ON vc.vatc=ipg.vatcategory
    WHERE s.is_active=1 AND sm.itemcode IS NOT NULL AND sm.itemcode<>''
      AND sm.barcode IS NOT NULL AND sm.barcode<>''
    ORDER BY s.service_category,s.service_name";
$result = $db->query($sql);
if (!$result) {
    http_response_code(500);
    echo json_encode(array('success'=>false,'message'=>'Could not load salon services. Apply the salon POS item mapping and stockmaster referential-integrity migrations and verify ERP item setup.'));
    exit;
}
$services = array();
$staffStmt = $db->prepare("SELECT h.hairdresser_id,h.hairdresser_code,h.full_name
    FROM salon_hairdresser_services hs
    INNER JOIN salon_hairdressers h ON h.hairdresser_id=hs.hairdresser_id
    WHERE hs.service_id=? AND hs.is_active=1 AND h.is_active=1
    ORDER BY h.full_name");
if (!$staffStmt) {
    http_response_code(500);
    echo json_encode(array('success'=>false,'message'=>'Could not load salon staff assignments.'));
    exit;
}
while ($row = $result->fetch_assoc()) {
    $serviceId = (int)$row['service_id'];
    $staffStmt->bind_param('i', $serviceId);
    if (!$staffStmt->execute()) {
        $staffStmt->close();
        http_response_code(500);
        echo json_encode(array('success'=>false,'message'=>'Could not load assigned hairdressers.'));
        exit;
    }
    $staffResult = $staffStmt->get_result();
    $staff = array();
    while ($person = $staffResult->fetch_assoc()) {
        $staff[] = array(
            'id'=>(int)$person['hairdresser_id'],
            'code'=>$person['hairdresser_code'],
            'name'=>$person['full_name']
        );
    }
    if (!$staff) { continue; }
    if ($row['sales_account'] === null || $row['vat_account'] === null ||
        $row['tax_category'] === null || $row['units'] === null ||
        !is_numeric($row['vat_rate']) || !is_numeric($row['standard_price'])) {
        continue;
    }
    $services[] = array(
        'id'=>$serviceId,
        'code'=>$row['service_code'],
        'name'=>$row['service_name'],
        'category'=>$row['service_category'],
        'duration'=>(int)$row['duration_minutes'],
        'price'=>(float)$row['standard_price'],
        'barcode'=>$row['pos_barcode'],
        'itemcode'=>$row['pos_itemcode'],
        'units'=>$row['units'],
        'vat_rate'=>(float)$row['vat_rate'],
        'staff'=>$staff
    );
}
$staffStmt->close();
echo json_encode(array('success'=>true,'services'=>$services));
?>
