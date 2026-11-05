<?php
$PathPrefix = '';
include('includes/globalsession.inc');

$adminToken = isset($_SESSION['PageSecurityArray']['CompanyPreferences.php'])
    ? $_SESSION['PageSecurityArray']['CompanyPreferences.php'] : null;
if ($adminToken === null || !in_array($adminToken, $_SESSION['AllowedPageSecurityTokens'])) {
    http_response_code(403);
    echo 'You do not have permission to change POS configuration.';
    exit;
}

global $mysqli;
if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
    die('Could not connect to the company database.');
}

function posConfigEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$configKeys = array(
    'POS_DefaultCustomer' => 'Default POS customer',
    'POS_DefaultStore' => 'Default POS store',
    'POS_DefaultCurrency' => 'Default POS currency',
    'POS_CashBankAccount' => 'Cash bank account',
    'POS_MPesaBankAccount' => 'M-Pesa bank account'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['FormID']) || !hash_equals((string)$_SESSION['FormID'], (string)$_POST['FormID'])) {
        http_response_code(400);
        die('The form security token is invalid. Reload the page and try again.');
    }

    $values = array();
    foreach ($configKeys as $key => $label) {
        $values[$key] = isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
    }

    $validations = array(
        'POS_DefaultCustomer' => array('SELECT itemcode FROM debtors WHERE itemcode = ? LIMIT 1', 's'),
        'POS_DefaultStore' => array('SELECT code FROM stores WHERE code = ? LIMIT 1', 's'),
        'POS_DefaultCurrency' => array('SELECT currabrev FROM currencies WHERE currabrev = ? LIMIT 1', 's'),
        'POS_CashBankAccount' => array('SELECT accountcode FROM bankaccounts WHERE accountcode = ? AND COALESCE(Makeinactive,0)=0 LIMIT 1', 's'),
        'POS_MPesaBankAccount' => array('SELECT accountcode FROM bankaccounts WHERE accountcode = ? AND COALESCE(Makeinactive,0)=0 LIMIT 1', 's')
    );

    foreach ($values as $key => $value) {
        if ($value === '') {
            continue;
        }
        $stmt = $mysqli->prepare($validations[$key][0]);
        if (!$stmt) {
            die('Could not validate POS configuration: ' . posConfigEscape($mysqli->error));
        }
        $stmt->bind_param('s', $value);
        $stmt->execute();
        $result = $stmt->get_result();
        $valid = $result && $result->num_rows === 1;
        $stmt->close();
        if (!$valid) {
            http_response_code(400);
            die('Invalid selection for ' . posConfigEscape($configKeys[$key]) . '. Reload and select an available master record.');
        }
    }

    $mysqli->begin_transaction();
    try {
        $save = $mysqli->prepare('INSERT INTO config (confname, confvalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE confvalue = VALUES(confvalue)');
        if (!$save) {
            throw new Exception($mysqli->error);
        }
        foreach ($values as $key => $value) {
            $save->bind_param('ss', $key, $value);
            if (!$save->execute()) {
                throw new Exception($save->error);
            }
        }
        $save->close();
        $mysqli->commit();

        foreach ($values as $key => $value) {
            $_SESSION[$key] = $value;
        }
        $message = 'POS configuration saved.';
    } catch (Exception $e) {
        $mysqli->rollback();
        error_log('POS configuration save failed: ' . $e->getMessage());
        $message = 'The POS configuration could not be saved. Check the server error log.';
    }
}

$current = array();
foreach ($configKeys as $key => $label) {
    $current[$key] = isset($_SESSION[$key]) ? (string)$_SESSION[$key] : '';
}
$result = $mysqli->query('SELECT itemcode, customer FROM debtors WHERE itemcode IS NOT NULL AND itemcode <> \'\' ORDER BY customer, itemcode');
$customers = $result ? $result->fetch_all(MYSQLI_ASSOC) : array();
$result = $mysqli->query('SELECT code, Storename FROM stores ORDER BY Storename, code');
$stores = $result ? $result->fetch_all(MYSQLI_ASSOC) : array();
$result = $mysqli->query('SELECT currabrev, currency FROM currencies ORDER BY currency');
$currencies = $result ? $result->fetch_all(MYSQLI_ASSOC) : array();
$result = $mysqli->query("SELECT accountcode, bankName, AcctName, currency FROM bankaccounts WHERE accountcode IS NOT NULL AND accountcode <> '' AND COALESCE(Makeinactive,0)=0 ORDER BY bankName, accountcode");
$banks = $result ? $result->fetch_all(MYSQLI_ASSOC) : array();

$Title = 'POS Configuration';
include('includes/header.inc');

function posConfigSelect($name, $label, $rows, $valueField, $textCallback, $selected) {
    echo '<tr><td>' . posConfigEscape($label) . '</td><td><select class="form-control" name="' . posConfigEscape($name) . '">';
    echo '<option value="">-- Not configured --</option>';
    foreach ($rows as $row) {
        $value = (string)$row[$valueField];
        $text = call_user_func($textCallback, $row);
        echo '<option value="' . posConfigEscape($value) . '"' . ($value === $selected ? ' selected="selected"' : '') . '>' . posConfigEscape($text) . '</option>';
    }
    echo '</select></td></tr>';
}

echo '<div class="container-fluid"><h2>POS Configuration</h2>';
if (isset($message)) {
    prnMsg($message, strpos($message, 'could not') === false ? 'success' : 'error');
}
echo '<p>Choose the ERP master records used as defaults by the POS terminal. Values are stored in the existing config table. Leave a field unconfigured if the related master record is not ready.</p>';
echo '<form method="post" action="' . posConfigEscape($_SERVER['PHP_SELF']) . '">';
echo '<input type="hidden" name="FormID" value="' . posConfigEscape($_SESSION['FormID']) . '">';
echo '<table class="table table-bordered table-striped">';
posConfigSelect('POS_DefaultCustomer', 'Default POS customer', $customers, 'itemcode', function($r) { return $r['customer'] . ' (' . $r['itemcode'] . ')'; }, $current['POS_DefaultCustomer']);
posConfigSelect('POS_DefaultStore', 'Default POS store', $stores, 'code', function($r) { return $r['Storename'] . ' (' . $r['code'] . ')'; }, $current['POS_DefaultStore']);
posConfigSelect('POS_DefaultCurrency', 'Default POS currency', $currencies, 'currabrev', function($r) { return $r['currency'] . ' (' . $r['currabrev'] . ')'; }, $current['POS_DefaultCurrency']);
$bankLabel = function($r) { return ($r['AcctName'] ? $r['AcctName'] . ' - ' : '') . $r['bankName'] . ' (' . $r['accountcode'] . ', ' . $r['currency'] . ')'; };
posConfigSelect('POS_CashBankAccount', 'Cash bank account', $banks, 'accountcode', $bankLabel, $current['POS_CashBankAccount']);
posConfigSelect('POS_MPesaBankAccount', 'M-Pesa bank account', $banks, 'accountcode', $bankLabel, $current['POS_MPesaBankAccount']);
echo '</table><button type="submit" class="btn btn-primary" name="SavePOSConfig" value="1">Save POS configuration</button> ';
echo '<a class="btn btn-default" href="posindex.php">Return to POS</a></form></div>';
include('includes/footer.inc');
?>
