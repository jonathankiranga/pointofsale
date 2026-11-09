<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function posAgentResponse($success, $message, $status = 200, $extra = array()) {
    http_response_code($status);
    echo json_encode(array_merge(array('success' => $success, 'message' => $message), $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    posAgentResponse(false, 'POST is required.', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || empty($input['action'])) {
    posAgentResponse(false, 'A valid print-agent action is required.', 400);
}

require_once('../config.php');
if (!isset($DefaultDatabase) || !preg_match('/^[A-Za-z0-9_]+$/', $DefaultDatabase)) {
    posAgentResponse(false, 'The default POS database is not configured.', 500);
}

$db = new mysqli($host, $DBUser, $DBPassword, $DefaultDatabase);
if ($db->connect_errno) {
    error_log('POS print-agent database connection failed: ' . $db->connect_error);
    posAgentResponse(false, 'Could not connect to the POS database.', 500);
}
$db->set_charset('utf8mb4');

$action = (string)$input['action'];
$deviceId = isset($input['device_id']) ? strtolower(trim((string)$input['device_id'])) : '';
$secret = isset($_SERVER['HTTP_AUTHORIZATION']) ? trim((string)$_SERVER['HTTP_AUTHORIZATION']) : '';
if (strpos($secret, 'Bearer ') === 0) {
    $secret = substr($secret, 7);
} else {
    $secret = '';
}

if ($action === 'register') {
    $deviceSecret = isset($input['device_secret']) ? (string)$input['device_secret'] : '';
    $deviceName = isset($input['device_name']) ? trim((string)$input['device_name']) : 'POS printer';
    if (!preg_match('/^[a-f0-9]{32}$/', $deviceId) || !preg_match('/^[a-f0-9]{64}$/', $deviceSecret)) {
        posAgentResponse(false, 'The generated printer identity is invalid.', 400);
    }
    $secretHash = hash('sha256', $deviceSecret);
    $stmt = $db->prepare('SELECT secret_hash FROM pos_print_devices WHERE device_id=? LIMIT 1');
    if (!$stmt) { posAgentResponse(false, 'Could not prepare printer registration.', 500); }
    $stmt->bind_param('s', $deviceId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        if (!hash_equals((string)$existing['secret_hash'], $secretHash)) {
            posAgentResponse(false, 'This printer identity is already registered with a different installation.', 409);
        }
        $stmt = $db->prepare('UPDATE pos_print_devices SET is_active=1, device_name=?, last_seen=NOW() WHERE device_id=?');
        $stmt->bind_param('ss', $deviceName, $deviceId);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $db->prepare('INSERT INTO pos_print_devices (device_id, secret_hash, device_name, is_active, last_seen) VALUES (?, ?, ?, 1, NOW())');
        if (!$stmt) { posAgentResponse(false, 'Could not prepare printer registration.', 500); }
        $stmt->bind_param('sss', $deviceId, $secretHash, $deviceName);
        if (!$stmt->execute()) {
            $stmt->close();
            posAgentResponse(false, 'Could not register the local printer.', 500);
        }
        $stmt->close();
    }
    posAgentResponse(true, 'Printer registered.');
}

if (!preg_match('/^[a-f0-9]{32}$/', $deviceId) || !preg_match('/^[a-f0-9]{64}$/', $secret)) {
    posAgentResponse(false, 'Printer authentication is missing.', 401);
}
$secretHash = hash('sha256', $secret);
$stmt = $db->prepare('SELECT secret_hash FROM pos_print_devices WHERE device_id=? AND is_active=1 LIMIT 1');
if (!$stmt) { posAgentResponse(false, 'Could not verify the printer identity.', 500); }
$stmt->bind_param('s', $deviceId);
$stmt->execute();
$device = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$device || !hash_equals((string)$device['secret_hash'], $secretHash)) {
    posAgentResponse(false, 'Printer identity was not recognised.', 401);
}
$stmt = $db->prepare('UPDATE pos_print_devices SET last_seen=NOW() WHERE device_id=?');
$stmt->bind_param('s', $deviceId);
$stmt->execute();
$stmt->close();

if ($action === 'poll') {
    $db->begin_transaction();
    $result = $db->query("SELECT id, document_no, payload, attempts FROM pos_print_jobs
        WHERE status='queued' OR (status='printing' AND locked_until < NOW())
        ORDER BY id ASC LIMIT 1 FOR UPDATE");
    if (!$result) {
        $db->rollback();
        posAgentResponse(false, 'Could not read the print queue. Apply the POS print queue SQL migration.', 500);
    }
    $job = $result->fetch_assoc();
    $result->free();
    if (!$job) {
        $db->commit();
        posAgentResponse(true, 'No print jobs are waiting.', 200, array('job' => null));
    }
    $jobId = (int)$job['id'];
    $stmt = $db->prepare("UPDATE pos_print_jobs SET status='printing', device_id=?, attempts=attempts+1,
        locked_until=DATE_ADD(NOW(), INTERVAL 90 SECOND), last_error=NULL WHERE id=?");
    $stmt->bind_param('si', $deviceId, $jobId);
    if (!$stmt->execute()) {
        $stmt->close();
        $db->rollback();
        posAgentResponse(false, 'Could not claim the print job.', 500);
    }
    $stmt->close();
    $db->commit();
    $payload = json_decode($job['payload'], true);
    if (!is_array($payload)) {
        posAgentResponse(false, 'The queued receipt data is invalid.', 500);
    }
    posAgentResponse(true, 'Print job ready.', 200, array('job' => array(
        'id' => $jobId,
        'documentno' => $job['document_no'],
        'payload' => $payload
    )));
}

if ($action === 'ack') {
    $jobId = isset($input['job_id']) ? (int)$input['job_id'] : 0;
    $printed = !empty($input['printed']);
    $error = isset($input['error']) ? substr(trim((string)$input['error']), 0, 500) : '';
    if ($jobId < 1) { posAgentResponse(false, 'A valid print job ID is required.', 400); }

    if ($printed) {
        $stmt = $db->prepare("UPDATE pos_print_jobs SET status='printed', printed_at=NOW(), locked_until=NULL, last_error=NULL
            WHERE id=? AND device_id=? AND status='printing'");
        $stmt->bind_param('is', $jobId, $deviceId);
    } else {
        $stmt = $db->prepare("UPDATE pos_print_jobs SET status=IF(attempts>=5,'failed','queued'),
            locked_until=NULL, last_error=? WHERE id=? AND device_id=? AND status='printing'");
        $stmt->bind_param('sis', $error, $jobId, $deviceId);
    }
    if (!$stmt || !$stmt->execute()) {
        if ($stmt) { $stmt->close(); }
        posAgentResponse(false, 'Could not update print job status.', 500);
    }
    $changed = $stmt->affected_rows;
    $stmt->close();
    if ($changed !== 1) { posAgentResponse(false, 'Print job was already acknowledged or is not assigned to this printer.', 409); }
    posAgentResponse(true, $printed ? 'Print completed.' : 'Print failure recorded.');
}

posAgentResponse(false, 'Unknown print-agent action.', 400);
