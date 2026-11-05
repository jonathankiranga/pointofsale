<nav id="mainsidebar" class="d-flex flex-column flex-shrink-0 p-3 bg-light">
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
<?php
// Resolve the POS URL from the application's current installation path.
$posAppPath = str_replace('\\', '/', dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/'));
$posAppPath = rtrim($posAppPath, '/');
$posTerminalUrl = ($posAppPath === '' || $posAppPath === '.') ? '/posindex.php' : $posAppPath . '/posindex.php';
?>
      <li class="nav-item">
        <a href="<?php echo htmlspecialchars($posTerminalUrl, ENT_QUOTES, 'UTF-8'); ?>" id="PointOfSaleNav" class="nav-link link-dark" target="_blank" rel="noopener">
          <svg class="bi me-2" width="16" height="16"><use xlink:href="#basket"/></svg>
          Point of Sale <span class="text-muted">(Touch)</span>
        </a>
      </li>

<?php
// Restore the original ERP module navigation from the repository's menu definitions.
// Missing legacy targets are skipped so the menu never points to non-existent PHP files.
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

foreach ($erpModuleLabels as $moduleKey => $moduleLabel) {
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

            // Keep the menu definition as the source of truth. These legacy URLs may
            // be routed by index.php and must not be hidden by filesystem checks.
            if ($scriptFile === '') {
                continue;
            }

            // Route through index.php to retain the existing homepage/session/security flow.
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

    // TEMPORARY DIAGNOSTIC: test the first four Purchases transaction links only.
    // Reports and Maintenance remain hidden until the Transactions list is verified.
    if ($moduleKey === 'Purchases') {
        $firstFourTransactions = isset($moduleSections['Transactions'])
            ? array_slice($moduleSections['Transactions'], 0, 4)
            : array();
        $moduleSections = count($firstFourTransactions) > 0
            ? array('Transactions' => $firstFourTransactions)
            : array();
    }

    if (count($moduleSections) === 0) {
        continue;
    }

    echo '<li class="nav-item mb-1"><details class="erp-module-menu" data-module-key="' .
         htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8') . '">';
    echo '<summary class="nav-link link-dark fw-semibold" style="cursor:pointer;">' .
         htmlspecialchars($moduleLabel, ENT_QUOTES, 'UTF-8') . '</summary>';
    echo '<div class="ms-3">';

    foreach ($moduleSections as $sectionLabel => $sectionItems) {
        echo '<div class="small text-uppercase text-muted fw-bold mt-2 mb-1">' .
             htmlspecialchars(_($sectionLabel), ENT_QUOTES, 'UTF-8') . '</div>';
        echo '<ul class="nav flex-column">';
        foreach ($sectionItems as $item) {
            echo '<li class="nav-item"><a class="nav-link link-dark py-1" href="' .
                 htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') . '">' .
                 htmlspecialchars($item['caption'], ENT_QUOTES, 'UTF-8') . '</a></li>';
        }
        echo '</ul>';
    }

    echo '</div></details></li>';
}
?>

    </ul>
    <hr>
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" aria-current="page" href="https://www.team-software.africa">
                <?php echo 'POS ERP  © 2012 - ' . Date('Y');?>
            </a>
        </li>
        <li class="nav-item"><?php echo DisplayDateTime(); ?></li>
    </ul>
</nav>

<script>
(function () {
    var sidebar = document.getElementById("mainsidebar");
    if (!sidebar) return;

    var menus = Array.prototype.slice.call(
        sidebar.querySelectorAll("details.erp-module-menu[data-module-key]")
    );
    var storageKey = "posERP.sidebar.openModule:" + window.location.pathname;

    function saveOpenMenu(key) {
        try {
            if (key) localStorage.setItem(storageKey, key);
            else localStorage.removeItem(storageKey);
        } catch (e) {}
    }

    // Restore previously open module
    var saved = null;
    try { saved = localStorage.getItem(storageKey); } catch (e) {}
    menus.forEach(function (menu) {
        menu.open = (saved && menu.getAttribute("data-module-key") === saved);
    });

    // Exclusive accordion — capture phase so we win the race with the browser
    menus.forEach(function (menu) {
        var summary = menu.querySelector(":scope > summary");
        if (!summary) return;

        summary.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();

            var willOpen = !menu.open;

            menus.forEach(function (other) {
                other.open = false;
            });

            if (willOpen) {
                menu.open = true;
                saveOpenMenu(menu.getAttribute("data-module-key"));
            } else {
                saveOpenMenu(null);
            }
        }, true); // capture = true
    });
})();
</script>
