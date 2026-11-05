<?php
// Horizontal ERP navigation. Module keys, not captions, provide unique menu identifiers.
$posAppPath = str_replace('\\', '/', dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/'));
$posAppPath = rtrim($posAppPath, '/');
$posTerminalUrl = ($posAppPath === '' || $posAppPath === '.') ? '/posindex.php' : $posAppPath . '/posindex.php';

$hadDefaultDisplayRecordsMax = isset($_SESSION['DefaultDisplayRecordsMax']);
if (!$hadDefaultDisplayRecordsMax) {
    $_SESSION['DefaultDisplayRecordsMax'] = 10;
}
include_once(dirname(__FILE__) . '/MainMenuLinksArray.php');
if (!$hadDefaultDisplayRecordsMax) {
    unset($_SESSION['DefaultDisplayRecordsMax']);
}

$erpModuleLabels = array(
    'GeneralLedger' => _('General Ledger'),
    'AccountsReceivable' => _('Accounts Receivable'),
    'AccountsPayable' => _('Accounts Payable'),
    'Purchases' => _('Purchases'),
    'Inventory' => _('Inventory')
);
$erpMenuSections = array('Transactions', 'Reports', 'Maintenance');
?>

<nav id="erpTopMenu" class="navbar navbar-expand-lg navbar-light bg-light border-bottom shadow-sm" aria-label="ERP module navigation">
    <div class="container-fluid">
        <a class="navbar-brand d-lg-none" href="<?php echo htmlspecialchars($posTerminalUrl, ENT_QUOTES, 'UTF-8'); ?>">ERP Menu</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#erpModuleNavigation" aria-controls="erpModuleNavigation" aria-expanded="false" aria-label="Toggle ERP navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="erpModuleNavigation">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link fw-semibold" href="<?php echo htmlspecialchars($posTerminalUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Point of Sale (Touch)</a>
                </li>
<?php foreach ($erpModuleLabels as $moduleKey => $moduleLabel): ?>
<?php
    if (!isset($MenuItems[$moduleKey]) || !is_array($MenuItems[$moduleKey])) {
        continue;
    }
    $moduleSections = array();
    foreach ($erpMenuSections as $sectionKey) {
        if (!isset($MenuItems[$moduleKey][$sectionKey]['Caption']) ||
            !isset($MenuItems[$moduleKey][$sectionKey]['URL'])) {
            continue;
        }
        $captions = $MenuItems[$moduleKey][$sectionKey]['Caption'];
        $urls = $MenuItems[$moduleKey][$sectionKey]['URL'];
        $sectionItems = array();
        $itemCount = min(count($captions), count($urls));
        for ($i = 0; $i < $itemCount; $i++) {
            $rawUrl = $urls[$i];
            $scriptPath = parse_url($rawUrl, PHP_URL_PATH);
            $scriptFile = basename($scriptPath);
            if ($scriptFile === '') {
                continue;
            }
            $pageName = preg_replace('/\\.php$/i', '', $scriptFile);
            $queryString = parse_url($rawUrl, PHP_URL_QUERY);
            $queryParams = array();
            if (!empty($queryString)) {
                parse_str($queryString, $queryParams);
            }
            $queryParams = array_merge(array('link' => $pageName), $queryParams);
            $sectionItems[] = array(
                'caption' => $captions[$i],
                'href' => 'index.php?' . http_build_query($queryParams)
            );
        }
        if (count($sectionItems) > 0) {
            $moduleSections[$sectionKey] = $sectionItems;
        }
    }
    if (count($moduleSections) === 0) {
        continue;
    }
    $moduleMenuId = 'erpModule' . preg_replace('/[^A-Za-z0-9_-]/', '', $moduleKey);
?>
                <li class="nav-item dropdown">
                    <button class="nav-link dropdown-toggle btn btn-link text-decoration-none" id="<?php echo htmlspecialchars($moduleMenuId, ENT_QUOTES, 'UTF-8'); ?>" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo htmlspecialchars($moduleLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="<?php echo htmlspecialchars($moduleMenuId, ENT_QUOTES, 'UTF-8'); ?>">
<?php foreach ($moduleSections as $sectionLabel => $sectionItems): ?>
                        <li><h6 class="dropdown-header"><?php echo htmlspecialchars(_($sectionLabel), ENT_QUOTES, 'UTF-8'); ?></h6></li>
<?php foreach ($sectionItems as $item): ?>
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($item['caption'], ENT_QUOTES, 'UTF-8'); ?></a></li>
<?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
<?php endforeach; ?>
                    </ul>
                </li>
<?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>
