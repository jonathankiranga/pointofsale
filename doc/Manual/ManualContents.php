<?php
/*
 * POS and Back-office User Manual
 * This page intentionally contains the application help content so that the
 * manual is available from one entry point without relying on legacy webERP topics.
 */
$manualTitle = 'POS & Back-office User Manual';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($manualTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" type="text/css" href="style/manual.css">
    <style>
        :root { color-scheme: light; }
        body { margin:0 auto; padding:24px; max-width:1180px; color:#243247; background:#f5f7fa; font:15px/1.65 Arial,Helvetica,sans-serif; }
        #pagetitle { padding:22px 26px; margin-bottom:22px; color:#fff; background:#17365d; border-radius:8px; font-size:25px; font-weight:700; }
        .manual-layout { display:grid; grid-template-columns:260px minmax(0,1fr); gap:22px; align-items:start; }
        .manual-nav,.manual-content { background:#fff; border:1px solid #dce3eb; border-radius:8px; }
        .manual-nav { position:sticky; top:16px; padding:18px; }
        .manual-nav strong { display:block; margin-bottom:10px; color:#17365d; font-size:16px; }
        .manual-nav a { display:block; padding:5px 7px; color:#24558a; text-decoration:none; border-radius:4px; }
        .manual-nav a:hover { background:#edf3f9; text-decoration:underline; }
        .manual-content { padding:28px 32px; min-width:0; }
        h1 { margin-top:0; color:#17365d; font-size:26px; }
        h2 { margin:34px 0 12px; padding-bottom:7px; color:#17365d; border-bottom:1px solid #dce3eb; font-size:21px; }
        h3 { margin:22px 0 8px; color:#24558a; font-size:17px; }
        p { margin:8px 0 13px; }
        li { margin:5px 0; }
        table { width:100%; border-collapse:collapse; margin:14px 0 20px; }
        th,td { padding:9px 11px; border:1px solid #dce3eb; text-align:left; vertical-align:top; }
        th { color:#17365d; background:#edf3f9; }
        code { padding:2px 5px; background:#f0f3f7; border-radius:3px; }
        .note { margin:14px 0; padding:12px 15px; border-left:4px solid #3d78ae; background:#f0f6fb; }
        .warning { border-left-color:#c58b2b; background:#fff8e9; }
        .manual-top { text-align:right; margin-top:28px; font-size:13px; }
        .manual-top a { color:#24558a; }
        @media (max-width:800px) {
            body { padding:12px; }
            .manual-layout { grid-template-columns:1fr; }
            .manual-nav { position:static; }
            .manual-content { padding:20px; }
        }
        @media print {
            body { max-width:none; padding:0; background:#fff; }
            #pagetitle { color:#000; background:#fff; border-bottom:2px solid #17365d; border-radius:0; padding:8px 0; }
            .manual-layout { display:block; }
            .manual-nav { display:none; }
            .manual-content { border:0; padding:0; }
            h2 { break-after:avoid; }
            table { break-inside:avoid; }
        }
    </style>
</head>
<body>
<div id="top"></div>
<div id="pagetitle">POS &amp; Back-office User Manual</div>

<div class="manual-layout">
    <nav class="manual-nav" aria-label="Manual contents">
        <strong>Contents</strong>
        <a href="#overview">1. System overview</a>
        <a href="#access">2. Access and navigation</a>
        <a href="#pos-terminal">3. POS terminal</a>
        <a href="#sell">4. Processing a sale</a>
        <a href="#payments">5. Taking payment</a>
        <a href="#saved-sales">6. Save and recall a sale</a>
        <a href="#shifts">7. Cashier shifts</a>
        <a href="#x-report">8. X report</a>
        <a href="#z-report">9. Z close</a>
        <a href="#admin-reports">10. POS administration reports</a>
        <a href="#inventory">11. Inventory catalogue</a>
        <a href="#excel">12. Excel catalogue import</a>
        <a href="#codes">13. Group Code and Category ID</a>
        <a href="#units">14. Full units and loose units</a>
        <a href="#stock-control">15. Stock control</a>
        <a href="#back-office">16. Back-office operations</a>
        <a href="#salon-foundation">17. Hair salon operations (foundation)</a>
        <a href="#security">18. Security and good practice</a>
        <a href="#troubleshooting">19. Troubleshooting</a>
    </nav>

    <main class="manual-content">
        <h1 id="overview">1. System overview</h1>
        <p>The application is an ERP system with an integrated touch-friendly point-of-sale terminal. The POS is used by cashiers to identify products, build a sale, collect payment and issue a receipt. The back office maintains the product catalogue, customers, suppliers, inventory, financial setup and management reports.</p>
        <p>The POS uses the ERP company and cashier session. Product descriptions, barcodes, prices, VAT information and posting configuration are sourced from the ERP database; the terminal is not a separate product database.</p>
        <div class="note"><strong>Currency:</strong> The POS payment summary and receipts are presented in KES. VAT treatment follows the VAT-inclusive setting of the configured default ERP sales posting group.</div>

        <h2 id="access">2. Access and navigation</h2>
        <ol>
            <li>Sign in through the ERP login page using your assigned user account.</li>
            <li>Open the POS terminal from the ERP navigation menu. The terminal displays the company name and the signed-in cashier.</li>
            <li>Use the back-office menu to maintain customers, suppliers, products, stores, posting groups and other ERP records for which your account has permission.</li>
            <li>Use <strong>Log out</strong> on the POS terminal when your cashier session is finished.</li>
        </ol>
        <p>Menu entries and administration actions depend on the security permissions assigned to the user. For example, access to POS administration reports is restricted by the ERP Company Preferences security permission.</p>

        <h2 id="pos-terminal">3. POS terminal</h2>
        <p>The terminal is organised into three working areas:</p>
        <ul>
            <li><strong>Action bar:</strong> Find product, Save sale, Recall sale, Remove line, Open shift, X report and Z close. Admin reports appear only for authorised users.</li>
            <li><strong>Sale items grid:</strong> Lists each barcode, description, quantity, unit price, VAT rate, VAT amount and line total.</li>
            <li><strong>Payment summary:</strong> Shows the amount due, VAT amount and the Proceed to payment action.</li>
        </ul>
        <p>A barcode scanner can be used where configured as a keyboard-input scanner. The terminal also provides product search when a barcode is unavailable or the item cannot be scanned.</p>

        <h2 id="sell">4. Processing a sale</h2>
        <ol>
            <li>Start a cashier shift using <strong>Open shift</strong> before trading. Enter the opening cash float when prompted.</li>
            <li>Scan the product barcode. The terminal looks up the product and adds it to the sale grid.</li>
            <li>Alternatively, select <strong>Find product</strong>, search by barcode or product description, and select the required product from the results.</li>
            <li>Repeat for each item. The terminal creates a new blank row after a product is selected.</li>
            <li>Change the quantity in the quantity cell or use its plus/minus controls. The quantity must remain at least one.</li>
            <li>Review the unit prices, VAT rates and line totals. Select a row and use <strong>Remove line</strong> to remove an item from the current, unposted sale.</li>
            <li>Check the amount due and choose <strong>Proceed to payment</strong>.</li>
        </ol>
        <div class="note warning"><strong>Before accepting payment:</strong> Check the products and quantities carefully. Removing a line only changes the current sale; it does not reverse a receipt that has already been completed.</div>
        <h3>VAT calculation</h3>
        <p>The POS reads the VAT-inclusive flag from the ERP default posting group. Where prices are VAT-inclusive, the VAT component is extracted from the gross line amount. Where prices are VAT-exclusive, VAT is added to the net line amount. Do not manually add VAT to a price that is already configured as VAT-inclusive.</p>

        <h2 id="payments">5. Taking payment</h2>
        <h3>Cash</h3>
        <ol>
            <li>Open the payment window and enter the cash received in KES.</li>
            <li>Check the displayed change. The terminal will indicate insufficient cash if the amount received is below the amount due.</li>
            <li>Select <strong>Cash</strong> to complete the cash payment and print the receipt.</li>
        </ol>
        <p>The change display can show a suggested breakdown using common Kenyan shilling denominations. This is a cash-handling aid; count the physical cash before handing change to the customer.</p>
        <h3>M-Pesa</h3>
        <ol>
            <li>Select <strong>M-Pesa</strong> in the payment window.</li>
            <li>Enter the customer's Kenyan M-Pesa number in the format <code>2547XXXXXXXX</code> or <code>2541XXXXXXXX</code>.</li>
            <li>Verify that the payment has actually been received using the M-Pesa confirmation message or statement.</li>
            <li>Confirm the verification prompt only after checking the payment, then complete the sale and print the receipt.</li>
        </ol>
        <div class="note warning"><strong>Important:</strong> The current POS workflow asks the cashier to verify M-Pesa payment manually. Entering a phone number is not proof of payment and does not itself initiate or confirm an STK push.</div>

        <h2 id="saved-sales">6. Save and recall a sale</h2>
        <p><strong>Save sale</strong> stores the current uncompleted sale in the browser's local storage and assigns it a reference. The sale grid is cleared so another transaction can be started. Use <strong>Recall sale</strong> to select a saved reference and restore its items.</p>
        <div class="note warning">Saved sales are browser-local working data, not posted ERP receipts. They are not a substitute for completing payment. Because the saved references are held in that browser's local storage, they may not be available on another device or browser profile. Do not clear browser data while saved sales are still needed.</div>

        <h2 id="shifts">7. Cashier shifts</h2>
        <p>A shift records the cashier's trading session and groups completed POS receipts for reporting and cash reconciliation.</p>
        <h3>Opening a shift</h3>
        <ol>
            <li>Select <strong>Open shift</strong>.</li>
            <li>Enter the actual opening cash float in KES.</li>
            <li>Confirm the opening. A cashier cannot open a second shift while that cashier already has an open shift.</li>
        </ol>
        <p>The terminal shows the shift state. If it displays <strong>No open shift</strong>, open a shift before processing sales.</p>

        <h2 id="x-report">8. X report — view without closing</h2>
        <p>The <strong>X report</strong> displays the current open shift's figures without closing the shift. It is intended for an interim check during trading.</p>
        <p>The shift summary includes receipt count, total sales, VAT, cash sales, M-Pesa sales, expected cash and a breakdown of products sold with quantities and values. Expected cash is calculated as:</p>
        <p><code>Opening float + cash sales</code></p>
        <p>An X report does not close the shift or create the final Z-close record. The cashier can continue serving customers after reviewing it.</p>

        <h2 id="z-report">9. Z close — close and reconcile</h2>
        <p><strong>Z close</strong> is the end-of-shift operation. It records the cashier's cash count, compares it with expected cash, saves the shift totals and closes the shift.</p>
        <ol>
            <li>Finish or account for all current transactions before closing.</li>
            <li>Select <strong>Z close</strong> and review the displayed shift summary.</li>
            <li>Count the physical cash in the drawer, including the opening float, and enter the counted amount when prompted.</li>
            <li>Review the expected cash and cash variance. Investigate any difference before confirming.</li>
            <li>Confirm the close. The system saves the Z report number, closing time, totals and cash variance, and marks the shift as closed.</li>
        </ol>
        <p>A Z close is a final shift operation, not an interim report. Do not use it merely to preview sales. Once closed, start a new shift for the next trading session.</p>
        <div class="note"><strong>Cash variance:</strong> Counted cash minus expected cash. A positive variance means the counted cash is higher than expected; a negative variance means it is short. M-Pesa sales are reported separately and are not included in expected drawer cash.</div>

        <h2 id="admin-reports">10. POS administration reports</h2>
        <p>Authorised users can open <strong>Admin reports</strong> from the POS action bar. The report is intended for management and tax reconciliation across a selected reporting period and cashier scope.</p>
        <p>The report includes:</p>
        <ul>
            <li>Sales summary: receipt count, gross sales, VAT, cash sales and M-Pesa sales.</li>
            <li>Payment reconciliation by payment method.</li>
            <li>VAT breakdown by ERP VAT category and rate, including net, VAT and gross values.</li>
            <li>Products sold, with item code, description, quantity, sales value and VAT.</li>
            <li>Receipt listing with receipt number, date, cashier, payment method, gross value and VAT. The receipt listing is limited to 500 records in the report.</li>
            <li>Cashier shift information, including shift and Z-close details where available.</li>
        </ul>
        <p>Use the report period and cashier selection to narrow the results. Reconcile the totals with the cashier's Z report and the corresponding cash and M-Pesa records.</p>

        <h2 id="inventory">11. Inventory catalogue</h2>
        <p>The back-office <strong>Stocks</strong> page maintains the item catalogue used by the POS and inventory functions. A stock item should have a unique item code and the correct barcode, description, retail price, inventory posting group, unit setup and category.</p>
        <p>Before importing or creating items, ensure the required posting groups, VAT categories, units of measure, inventory categories and stores have been configured. Incorrect posting setup can affect inventory valuation and accounting postings.</p>

        <h2 id="excel">12. Excel catalogue import</h2>
        <p>On the Stocks page, use <strong>Download Excel Inventory Template</strong> to download the workbook. The workbook has two worksheets:</p>
        <ul>
            <li><strong>Inventory List:</strong> Enter the new or updated catalogue rows using the prescribed columns.</li>
            <li><strong>Code Reference:</strong> Shows the live ERP codes available when the workbook was downloaded, including posting groups and their account mappings, categories, units and container items.</li>
        </ul>
        <table>
            <thead><tr><th>Column</th><th>Field</th><th>How to fill it</th></tr></thead>
            <tbody>
                <tr><td>A</td><td>Barcode</td><td>Required. Enter the barcode used to identify the item at the till; maximum 20 characters.</td></tr>
                <tr><td>B</td><td>StockName</td><td>Required. Enter the product description; maximum 100 characters.</td></tr>
                <tr><td>C</td><td>RetailPrice</td><td>Required. Enter a non-negative numeric selling price.</td></tr>
                <tr><td>D</td><td>GroupCode</td><td>Required. Enter a valid inventory posting group code from the Code Reference worksheet.</td></tr>
                <tr><td>E</td><td>PartsPerUnit</td><td>Optional for single-unit items; defaults to 1. For a pack, enter the number of loose/base units in one full pack.</td></tr>
                <tr><td>F</td><td>FullUnitCode</td><td>Optional for single-unit items. For packs, enter the unit code representing the full pack.</td></tr>
                <tr><td>G</td><td>LooseUnitCode</td><td>Optional for single-unit items. For packs, enter the unit code representing one loose/base unit.</td></tr>
                <tr><td>H</td><td>CategoryID</td><td>Optional classification code from the Inventory Categories section of Code Reference.</td></tr>
                <tr><td>I</td><td>ContainerItemCode</td><td>Optional. Use a valid container item code only when the item is associated with a container.</td></tr>
            </tbody>
        </table>
        <ol>
            <li>Download a fresh template so that its Code Reference sheet reflects current ERP setup.</li>
            <li>Enter catalogue data on the Inventory List worksheet. Keep the column order and headings unchanged.</li>
            <li>Use exact codes from the Code Reference worksheet; do not type descriptions in code fields.</li>
            <li>Save the workbook as <code>.xlsx</code> or <code>.xls</code>.</li>
            <li>On the Stocks page, choose the file and run the Excel import. Review any validation errors and correct the workbook before importing again.</li>
        </ol>
        <div class="note warning"><strong>Catalogue import is not a stock take.</strong> It maintains item master data; it must not be used to enter physical quantities or change stock balances. Use the stock adjustment/physical count process for inventory quantity corrections.</div>

        <h2 id="codes">13. Group Code and Category ID — what is the difference?</h2>
        <table>
            <thead><tr><th>Group Code</th><th>Category ID</th></tr></thead>
            <tbody>
                <tr><td>Controls the inventory posting and accounting setup associated with the item, including inventory, sales, purchase and VAT account mappings.</td><td>Classifies the item for catalogue organisation and inventory reporting.</td></tr>
                <tr><td>Choose it from the Group Code section at the top of the Code Reference worksheet. Check the linked accounts and VAT category to identify the correct group.</td><td>Choose it from the Inventory Categories section. Match the category description to the item type.</td></tr>
                <tr><td>Required during import.</td><td>Optional during import.</td></tr>
            </tbody>
        </table>
        <p>For a printed worksheet, locate the code in the left-hand code column and read its description or account mapping on the same row. Copy the code itself into the matching Excel field. Never substitute a Category ID for a Group Code, or vice versa.</p>

        <h2 id="units">14. Full units and loose units</h2>
        <p>For a multi-pack item, <strong>PartsPerUnit</strong> defines how many loose/base units are contained in one full unit. For example, if one carton contains 24 bottles, set PartsPerUnit to <code>24</code>, FullUnitCode to the carton unit code and LooseUnitCode to the bottle unit code.</p>
        <p>The stock balance is represented in loose/base units:</p>
        <p><code>Base quantity = (full quantity × PartsPerUnit) + loose quantity</code></p>
        <p>Example: 10 cartons received at 24 bottles each add 240 bottles. If 5 bottles and then 1 carton are sold, the balance reduces by 5 and 24 respectively. The remaining balance is 211 bottles, equivalent to 8 cartons and 19 bottles.</p>
        <div class="note">For a single-unit item, leave the optional pack fields blank where appropriate. The importer defaults PartsPerUnit to 1. Do not enter a pack size of 24 unless the item really contains 24 base units per full unit.</div>

        <h2 id="stock-control">15. Stock control</h2>
        <h3>Stores</h3>
        <p>Stock is associated with stores/locations. Maintain the store records before processing store-based stock movements or physical counts. Select the correct store when entering an adjustment or transfer.</p>
        <h3>Stock adjustments and physical counts</h3>
        <p>Use Stock Adjustments to review an item's system balance for a selected store. The physical stock take area displays the current system quantity and accepts the counted full-unit and loose-unit quantities. The posting operation adjusts the ledger to the physical count. The manual adjustment area is for a known quantity correction in the selected unit of measure.</p>
        <p>Count carefully and verify the selected item, store, unit and quantity before posting. A posted adjustment changes inventory records and can create the corresponding inventory variance accounting entry.</p>
        <h3>Stock transfers</h3>
        <p>Use Stock Transfer for movement between stores. Confirm the source and destination stores, item and quantity. For pack items, compare quantities on a common loose/base-unit basis so that loose sales or issues are not overlooked when checking availability.</p>
        <h3>Stock issues and movements</h3>
        <p>Use the relevant stock issue or movement function for operational stock movements. Keep supporting documents and ensure the movement is recorded against the correct item and store.</p>

        <h2 id="back-office">16. Back-office operations</h2>
        <p>The ERP menu provides the operational and financial records that support POS and inventory processing. Availability depends on the user's assigned permissions. Common work areas include:</p>
        <ul>
            <li><strong>Customers:</strong> Maintain customer master records and customer posting groups used by sales transactions.</li>
            <li><strong>Suppliers:</strong> Maintain supplier records and supplier posting groups used by purchasing.</li>
            <li><strong>Items and inventory:</strong> Maintain stock items, categories, units, stores, posting groups, adjustments and transfers.</li>
            <li><strong>Purchasing:</strong> Manage purchase orders, goods received notes, supplier invoices and supplier returns using the relevant purchasing workflow.</li>
            <li><strong>Sales:</strong> Manage sales orders, quotations, deliveries, invoices and credit notes where those workflows are enabled.</li>
            <li><strong>Cash and banking:</strong> Record receipts, payments, bank transactions and reconciliations using the appropriate cash or bank function.</li>
            <li><strong>General ledger:</strong> Maintain chart of accounts, journals, financial periods, posting groups and ledger reports.</li>
            <li><strong>Reports:</strong> Use operational and financial reports to review sales, purchases, stock, cash, VAT and ledger activity.</li>
        </ul>
        <p>Use the dedicated transaction page for each business process. Do not edit posted database records directly to correct an operational transaction; use the supported reversal, credit, adjustment or correction workflow.</p>

        <h2 id="salon-foundation">17. Hair salon operations — foundation and rollout status</h2>
        <p>The <code>feature/hair-salon-pos</code> branch adds a dedicated salon service terminal alongside the standard retail POS. The salon terminal uses the existing POS receipt, payment and accounting workflow; each salon service is mapped to an ERP POS item so its revenue and VAT use the configured ERP posting setup.</p>
        <div class="note"><strong>Current operational scope:</strong> Salon staff and service setup, staff-to-service assignments, commission rules, appointment booking/status tracking, service checkout and commission payout tracking are available on this branch. The appointment schedule is a list-based operational view, not a drag-and-drop calendar. Appointment bookings are not yet automatically converted into a checkout basket. Commission payout marking records a payout reference in the salon commission ledger; it does not create a bank payment voucher or GL posting.</div>
        <h3>What the foundation stores</h3>
        <ul>
            <li><strong>Hairdressers:</strong> A salon-specific roster with a unique staff code, name, contact details and active/inactive status. This is separate from the existing production employee records.</li>
            <li><strong>Services:</strong> A service catalogue with a unique service code, name, category, description, standard duration and standard price. Examples include haircuts, braiding, washing, colouring and treatments.</li>
            <li><strong>Hairdresser skills:</strong> A mapping of which active hairdressers are assigned or qualified to perform each service.</li>
            <li><strong>Commission rules:</strong> Percentage or fixed-amount rules. Rules can apply globally, to a service, to a hairdresser, or to a specific hairdresser/service combination. The future checkout integration must select the most specific applicable active rule.</li>
            <li><strong>Commission ledger:</strong> A transaction-level snapshot containing the source sale and line, service, hairdresser, eligible amount, rule type/value, calculated amount and pending/paid/reversed status.</li>
        </ul>
        <h3>Database installation</h3>
        <ol>
            <li>Back up the relevant POS company database before applying a schema migration.</li>
            <li>Run <code>SQL/20261107_hair_salon_pos_foundation.sql</code> once against that company database.</li>
            <li>Run <code>SQL/20261107_hair_salon_pos_operations.sql</code> to create appointment and appointment-line tables and register the salon pages in the ERP security script list.</li>
            <li>Run <code>SQL/20261107_hair_salon_pos_item_mapping.sql</code> to add the ERP item code and barcode mapping fields to the salon service catalogue.</li>
            <li>Confirm that the five foundation tables and the two appointment tables exist. The migrations do not alter existing POS sales, inventory, accounting or production employee tables.</li>
        </ol>
        <h3>Set up a sellable salon service</h3>
        <ol>
            <li>Create the service's ERP item in the Stocks catalogue. Configure the correct sales and VAT posting groups and ensure the item is suitable for service revenue rather than a physical stock item.</li>
            <li>In Salon setup, add the service code, name, category, duration and salon selling price.</li>
            <li>Enter the matching ERP POS item code and barcode on the salon service record. The mapping must match an existing stockmaster item exactly.</li>
            <li>Register the hairdresser and assign the hairdresser to the service under <strong>Staff skills</strong>.</li>
            <li>Create an active commission rule with an effective date. A checkout is rejected if no applicable rule exists for the selected hairdresser and service.</li>
        </ol>
        <h3>Run a salon service sale</h3>
        <ol>
            <li>Open <code>SalonPOS.php</code> from the POS terminal using <strong>Salon POS</strong>.</li>
            <li>Choose a service card, select an assigned hairdresser and tap <strong>Add service</strong>. The service appears in the sale basket with its mapped ERP item and salon price.</li>
            <li>Add further services as required. Each service line retains its selected hairdresser.</li>
            <li>Proceed to payment and complete the normal POS cash or verified M-Pesa workflow. The backend revalidates the service, ERP item mapping, hairdresser assignment and commission rule; browser-supplied prices are not trusted.</li>
            <li>After the POS receipt, invoice, receipt allocation and accounting postings commit successfully, the system records the commission snapshot as pending. A failed or uncommitted payment does not earn commission.</li>
        </ol>
        <h3>Commission calculation and payout tracking</h3>
        <ul>
            <li>The most specific active rule is selected: hairdresser plus service, then a single-scope rule, then a global rule. The eligible service amount excludes VAT; percentage rules apply to that amount and fixed rules apply per service quantity.</li>
            <li>The ledger stores the source receipt, service, hairdresser, quantity, eligible amount, rule type/value and calculated commission. Later rule changes do not recalculate historic entries.</li>
            <li>Use <strong>Commission payouts</strong> to review pending balances by hairdresser. Enter the actual payment/voucher reference to mark that hairdresser's pending commission records as paid.</li>
            <li>The payout screen is a commission ledger control and report. It does not itself create a cashbook payment, bank transaction or general-ledger voucher. Those accounting payment steps remain separate.</li>
        </ul>
        <div class="note warning"><strong>Operational controls:</strong> Do not map a salon service to a physical stock item that should be decremented on sale. Configure its ERP item, VAT and sales posting setup correctly before checkout. The salon appointment schedule currently records bookings and status changes; it does not automatically create service sales or enforce appointment capacity.</div>

        <h2 id="security">18. Security and good practice</h2>
        <ul>
            <li>Use your own ERP login. Never share cashier credentials or leave an active terminal unattended.</li>
            <li>Open and close shifts under the correct cashier account.</li>
            <li>Verify M-Pesa receipts independently before confirming the payment in the POS.</li>
            <li>Reconcile cash against the Z report and investigate variances promptly.</li>
            <li>Use the latest downloaded Excel Code Reference when importing catalogue records.</li>
            <li>Do not change posting groups, VAT setup, units or pack sizes without approval from the responsible system administrator.</li>
            <li>Do not use catalogue import to adjust stock quantities.</li>
            <li>Log out when finished, especially on shared tills and back-office computers.</li>
        </ul>

        <h2 id="troubleshooting">19. Troubleshooting</h2>
        <table>
            <thead><tr><th>Problem</th><th>What to check</th></tr></thead>
            <tbody>
                <tr><td>Product is not found by barcode</td><td>Check that the barcode is assigned to the correct stock item, is entered without extra spaces, and the item is available in the catalogue.</td></tr>
                <tr><td>Product search does not load</td><td>Reload the POS terminal and check the ERP connection. If the problem continues, ask the system administrator to check the product search request and server logs.</td></tr>
                <tr><td>Price or VAT is unexpected</td><td>Check the stock item's retail price, VAT category and the VAT-inclusive setting of the default POS posting group. Do not change the price during checkout to compensate for incorrect setup.</td></tr>
                <tr><td>Cannot open a shift</td><td>Check whether the cashier already has an open shift. Review the shift status and contact an authorised supervisor if the previous shift was not closed correctly.</td></tr>
                <tr><td>Cash payment is insufficient</td><td>Enter the actual cash received and compare it with the amount due. Do not complete the cash payment until sufficient cash has been received.</td></tr>
                <tr><td>M-Pesa payment cannot be confirmed</td><td>Check the customer's payment confirmation or statement. A phone number alone does not confirm payment.</td></tr>
                <tr><td>Excel import rejects a row</td><td>Check required fields, numeric RetailPrice, valid GroupCode, barcode length, item description length and pack unit codes. Use the Code Reference worksheet for exact codes.</td></tr>
                <tr><td>Stock balance differs from physical count</td><td>Confirm the selected store, full/loose unit conversion and recent receipts, issues, sales, transfers and adjustments before posting a count.</td></tr>
                <tr><td>Admin reports are not visible</td><td>Ask the ERP administrator to review the user's Company Preferences security permission.</td></tr>
            </tbody>
        </table>

        <div class="note"><strong>Need assistance?</strong> When reporting a problem, provide the page name, item or receipt reference, store, cashier, date/time and the exact error message. Do not send passwords or payment credentials.</div>
        <p class="manual-top"><a href="#top">Back to top ↑</a></p>
    </main>
</div>
</body>
</html>
