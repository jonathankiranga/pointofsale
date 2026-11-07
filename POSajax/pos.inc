<?php
$POSVatInclusive = 0;
$POSCanAdminReports = false;
$posAdminToken = isset($_SESSION['PageSecurityArray']['CompanyPreferences.php']) ? $_SESSION['PageSecurityArray']['CompanyPreferences.php'] : null;
if ($posAdminToken !== null && isset($_SESSION['AllowedPageSecurityTokens']) && in_array($posAdminToken, $_SESSION['AllowedPageSecurityTokens'])) {
    $POSCanAdminReports = true;
}
if (isset($mysqli) && $mysqli instanceof mysqli) {
    $posPostingResult = $mysqli->query("SELECT confvalue FROM config WHERE confname='POS_DefaultPostingGroup' LIMIT 1");
    if ($posPostingResult && ($posPostingRow = $posPostingResult->fetch_assoc())) {
        $posGroupStmt = $mysqli->prepare("SELECT VATinclusive FROM postinggroups WHERE code=? LIMIT 1");
        $posGroupStmt->bind_param('s', $posPostingRow['confvalue']);
        $posGroupStmt->execute();
        $posGroupRow = $posGroupStmt->get_result()->fetch_assoc();
        if ($posGroupRow) { $POSVatInclusive = (int)$posGroupRow['VATinclusive']; }
        $posGroupStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#101828">
    <title>SmartERPlims POS Terminal</title>
    <link rel="stylesheet" href="POScss/completecss.css">
    <link rel="stylesheet" href="POScss/posstyle.css">
    <link rel="stylesheet" href="POScss/pos-touch.css">
    <?php if (!empty($POSIsSalonTerminal)) { ?><link rel="stylesheet" href="POScss/salon-pos.css"><?php } ?>
    <script src="javascripts/3.5.1/jquery.min.js"></script>
</head>
<body class="pos-touch-mode <?php echo !empty($POSIsSalonTerminal) ? 'salon-pos-mode' : ''; ?>">
    <div class="pos-terminal">
        <header class="pos-topbar">
            <div class="pos-brand">
                <span class="pos-brand-mark"><svg width="27" height="27" aria-hidden="true"><use xlink:href="POScss/svg.svg#home"/></svg></span>
                <div>
                    <strong>Smarternow POS</strong>
                    <small id="company"><?php echo htmlspecialchars(stripslashes($_SESSION['CompanyRecord']['coyname']), ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
            </div>
            <div class="pos-session">
                <span class="pos-status-dot"></span>
                <span id="userlog">Cashier: <?php echo htmlspecialchars(stripslashes($_SESSION['UsersRealName']), ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="pos-session-divider"></span>
                <span id="cashsaleno" class="pos-sale-number"></span>
                <script>
                    document.getElementById('cashsaleno').textContent = 'CS-' + Date.now();
                </script>
                <a class="pos-logout" href="PosLogout.php" aria-label="Log out">
                    <svg width="21" height="21" aria-hidden="true"><use xlink:href="POScss/svg.svg#logout"/></svg>
                    <span>Log out</span>
                </a>
            </div>
        </header>

        <nav class="pos-actionbar" aria-label="Point of sale actions">
            <button type="button" class="pos-action" id="find-items-link">
                <svg width="23" height="23" aria-hidden="true"><use xlink:href="POScss/svg.svg#barcode"/></svg>
                <span>Find product</span>
            </button>
            <button type="button" class="pos-action" id="save-transaction-link">
                <svg width="23" height="23" aria-hidden="true"><use xlink:href="POScss/svg.svg#memory"/></svg>
                <span>Save sale</span>
            </button>
            <button type="button" class="pos-action" id="recall-transaction-link">
                <svg width="23" height="23" aria-hidden="true"><use xlink:href="POScss/svg.svg#recall"/></svg>
                <span>Recall sale</span>
            </button>
            <button type="button" class="pos-action pos-action-danger" id="delete-selected-link">
                <svg width="23" height="23" aria-hidden="true"><use xlink:href="POScss/svg.svg#trash"/></svg>
                <span>Remove line</span>
            </button>
            <a class="pos-action" href="SalonPOS.php" id="salon-terminal-link"><span>Salon POS</span></a>
            <a class="pos-action" href="index.php?link=SalonManagement" id="salon-management-link"><span>Salon setup</span></a>
            <button type="button" class="pos-action" id="pos-shift-open"><span>Open shift</span></button>
            <button type="button" class="pos-action" id="pos-x-report"><span>X report</span></button>
            <button type="button" class="pos-action pos-action-danger" id="pos-z-close"><span>Z close</span></button>
            <?php if ($POSCanAdminReports) { ?><a class="pos-action" href="POSReports.php" id="pos-admin-reports"><span>Admin reports</span></a><?php } ?>
            <span id="pos-shift-label" class="pos-scan-hint">No open shift</span>
            <div class="pos-scan-hint">
                <span class="pos-scan-indicator"></span>
                Scan barcode or use Find product
            </div>
        </nav>

        <?php if (!empty($POSIsSalonTerminal)) { ?>
        <section class="salon-service-picker" aria-label="Salon service catalogue">
            <div class="salon-service-picker-heading">
                <div><span class="pos-eyebrow">SALON SERVICE MENU</span><h1>Choose a service and hairdresser</h1></div>
                <span class="pos-scan-hint">Services are charged through the standard POS payment workflow</span>
            </div>
            <div id="salon-service-catalogue" class="salon-service-grid">
                <div class="pos-empty-hint">Loading salon services...</div>
            </div>
        </section>
        <?php } ?>
        <main class="pos-main">
            <section class="pos-cart-card" aria-label="Current sale">
                <div class="pos-section-heading">
                    <div>
                        <span class="pos-eyebrow">CURRENT TRANSACTION</span>
                        <h1>Sale items</h1>
                    </div>
                    <span class="pos-cart-count" id="pos-cart-label">Cart</span>
                </div>
                <div class="pos-table-wrap">
                    <table id="sales-table">
                        <thead>
                            <tr>
                                <th>Barcode</th>
                                <th>Description</th>
                                <th>Quantity</th>
                                <th>Unit price</th>
                                <th>VAT rate</th>
                                <th>VAT</th>
                                <th class="pos-line-total-heading">Line total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Existing POS JavaScript creates and updates sale rows here. -->
                        </tbody>
                    </table>
                    <div class="pos-empty-hint" aria-hidden="true">
                        <span class="pos-empty-icon">＋</span>
                        <strong>Ready for your first item</strong>
                        <span>Scan a product barcode or tap Find product</span>
                    </div>
                </div>
            </section>

            <aside id="total-amount-container" class="pos-summary-card" aria-label="Sale totals">
                <div class="pos-summary-top">
                    <span class="pos-eyebrow">PAYMENT SUMMARY</span>
                    <span class="pos-summary-currency">KES</span>
                </div>
                <div class="pos-total-label">Amount due</div>
                <div class="pos-grand-total"><span id="total-amount">0.00</span></div>
                <div class="pos-tax-line">
                    <span>VAT included</span>
                    <strong>KES <span id="tax-amount">0.00</span></strong>
                </div>
                <div class="pos-summary-divider"></div>
                <button type="button" id="show-bill-link" class="pos-checkout-button">
                    <span class="pos-checkout-icon"><svg width="25" height="25" aria-hidden="true"><use xlink:href="POScss/svg.svg#money"/></svg></span>
                    <span>Proceed to payment</span>
                    <span class="pos-checkout-arrow">›</span>
                </button>
                <div class="pos-payment-shortcuts">
                    <span>Keyboard shortcuts</span>
                    <kbd>Ctrl</kbd> + <kbd>F1</kbd>
                    <span>Checkout</span>
                </div>
            </aside>
        </main>
    </div>

    <div id="modal-overlay"></div>

    <div id="receipt-modal" style="display:none;">
        <div id="receipt-content">
            <h3>Sales Receipt</h3>
            <pre id="receipt-text"></pre>
        </div>
    </div>

    <div id="search-modal" class="modal" style="display:none;" role="dialog" aria-modal="true" aria-label="Find product">
        <div class="modal-content pos-dialog">
            <div class="pos-dialog-header">
                <div><span class="pos-eyebrow">PRODUCT CATALOGUE</span><h2>Find a product</h2></div>
                <button type="button" class="checkout-modal-close pos-close-button" aria-label="Close">&times;</button>
            </div>
            <input type="text" id="search-input" placeholder="Search barcode or product description..." autocomplete="off">
            <div id="search-results"><!-- Existing searchstock.js populates results. --></div>
        </div>
    </div>

    <div id="checkout-modal" class="modal" style="display:none;" role="dialog" aria-modal="true" aria-label="Checkout">
        <div class="modal-content pos-dialog pos-checkout-dialog">
            <div class="pos-dialog-header">
                <div><span class="pos-eyebrow">COMPLETE SALE</span><h2>Payment</h2></div>
                <button type="button" class="checkout-modal-close pos-close-button" aria-label="Close">&times;</button>
            </div>
            <div class="pos-payment-due">
                <span>Total to pay</span>
                <strong>KES <span id="checkout-total">0.00</span></strong>
            </div>
            <label for="customer-cash">Cash received (KES)</label>
            <input type="number" inputmode="decimal" id="customer-cash" placeholder="Enter amount received" min="0" step="0.01">
            <div class="pos-change-display"><span>Change</span><strong id="customer-change">0.00</strong></div>
            <div id="denomination-list-container" style="display:none;">
                <h3>Change in denominations</h3>
                <div id="denomination-list"></div>
            </div>
            <h3 class="pos-payment-method-heading">Choose payment method</h3>
            <div class="pos-payment-methods">
                <button type="button" id="pay-cash-btn" class="pos-payment-choice pos-cash-choice">
                    <svg width="30" height="30" aria-hidden="true"><use xlink:href="POScss/svg.svg#print"/></svg>
                    <strong>Cash</strong><span>Print receipt</span>
                </button>
                <button type="button" id="pay-mpesa-btn" class="pos-payment-choice pos-mpesa-choice">
                    <svg width="30" height="30" aria-hidden="true"><use xlink:href="POScss/svg.svg#simcard"/></svg>
                    <strong>M-Pesa</strong><span>Verify payment</span>
                </button>
            </div>
            <p class="pos-shortcut-note">Keyboard: Ctrl + F2 for cash · Ctrl + F3 for M-Pesa</p>
        </div>
    </div>

    <div id="mpesa-modal" class="modal" style="display:none;" role="dialog" aria-modal="true" aria-label="M-Pesa payment">
        <div class="modal-content-small pos-dialog pos-small-dialog">
            <div class="pos-dialog-header">
                <div><span class="pos-eyebrow">M-PESA PAYMENT</span><h2>Customer number</h2></div>
                <button type="button" class="checkout-modal-close pos-close-button" aria-label="Close">&times;</button>
            </div>
            <label for="customer-mpesa-no">M-Pesa phone number</label>
            <input type="tel" inputmode="numeric" id="customer-mpesa-no" placeholder="2547XXXXXXXX" autocomplete="tel">
            <label for="customer-mpesa-reference">M-Pesa transaction code</label>
            <input type="text" id="customer-mpesa-reference" placeholder="Enter the code from the confirmation SMS" maxlength="20" autocomplete="off">
            <button type="button" id="process-mpesa-btn" class="pos-primary-button">Confirm and print receipt</button>
        </div>
    </div>

    <div id="recall-modal" class="modal" style="display:none;" role="dialog" aria-modal="true" aria-label="Recall saved sale">
        <div class="modal-content-small pos-dialog pos-small-dialog">
            <div class="pos-dialog-header">
                <div><span class="pos-eyebrow">SAVED TRANSACTIONS</span><h2>Recall sale</h2></div>
                <button type="button" class="checkout-modal-close pos-close-button" aria-label="Close">&times;</button>
            </div>
            <label for="reference-list">Saved reference</label>
            <select id="reference-list"><option value="">Select a reference</option></select>
            <button type="button" id="recall-button" class="pos-primary-button">Recall selected sale</button>
        </div>
    </div>

    <div id="custom-dialog" class="modal" style="display:none;" role="dialog" aria-modal="true" aria-label="POS message">
        <div class="modal-content pos-dialog pos-message-dialog">
            <div class="pos-dialog-header">
                <span class="pos-eyebrow">POS MESSAGE</span>
                <button type="button" class="dialog-close pos-close-button" aria-label="Close">&times;</button>
            </div>
            <div id="dialog-message"></div>
            <button type="button" id="dialog-print-btn" class="pos-primary-button" style="display:none;">Print report</button>
            <button type="button" id="dialog-ok-btn" class="pos-primary-button">OK</button>
        </div>
    </div>

    <script src="POSajax/posscript.js"></script>
    <script src="POSajax/searchstock.js"></script>
    <?php if (!empty($POSIsSalonTerminal)) { ?><script src="POSajax/salon-pos.js"></script><?php } ?>
    <script>
        window.POS_VAT_INCLUSIVE = <?php echo (int)$POSVatInclusive; ?>;
        window.POS_FORM_ID = <?php echo json_encode($_SESSION['FormID'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        window.POS_PRINT_URL = <?php echo json_encode($POSPrintUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        window.POS_PRINT_TOKEN = <?php echo json_encode($POSPrintToken, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        window.POS_PRINTER_ID = <?php echo json_encode(isset($POSPrinterId) ? $POSPrinterId : '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        window.POS_COMPANY_DETAILS = <?php
            $posCompany = isset($_SESSION['CompanyRecord']) && is_array($_SESSION['CompanyRecord']) ? $_SESSION['CompanyRecord'] : array();
            $posCompanyAddress = array();
            for ($posAddressIndex = 1; $posAddressIndex <= 6; $posAddressIndex++) {
                $posAddressKey = 'regoffice' . $posAddressIndex;
                if (!empty($posCompany[$posAddressKey])) { $posCompanyAddress[] = $posCompany[$posAddressKey]; }
            }
            echo json_encode(array(
                'name' => isset($posCompany['coyname']) ? $posCompany['coyname'] : '',
                'address' => $posCompanyAddress,
                'telephone' => isset($posCompany['telephone']) ? $posCompany['telephone'] : '',
                'email' => isset($posCompany['email']) ? $posCompany['email'] : ''
            ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        ?>;
    </script>
    <script src="POSajax/printreceipt.js"></script>
</body>
</html>
