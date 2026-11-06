<?php
/*
 * Use the ERP session and standard page-security bootstrap.
 * POS administration reports require the same permission as Company Preferences.
 */
session_name('smartlimserp');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$PageSecurity = isset($_SESSION['PageSecurityArray']['CompanyPreferences.php'])
    ? $_SESSION['PageSecurityArray']['CompanyPreferences.php'] : -1;
$PathPrefix = '';
include('includes/globalsession.inc');
global $mysqli;
if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
    die('Could not connect to the company database.');
}

function posReportEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function posReportMoney($value) {
    return number_format((float)$value, 2);
}
function posReportBind($stmt, $types, $params) {
    $values = array_merge(array($types), $params);
    $refs = array();
    foreach ($values as $key => $value) { $refs[$key] = &$values[$key]; }
    call_user_func_array(array($stmt, 'bind_param'), $refs);
}
$from = isset($_GET['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from']) ? $_GET['from'] : date('Y-m-01');
$to = isset($_GET['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to']) ? $_GET['to'] : date('Y-m-d');
$cashier = isset($_GET['cashier']) ? trim((string)$_GET['cashier']) : '';

$where = "h.documenttype=1 AND h.pos_shift_id IS NOT NULL AND COALESCE(h.status,1)=1 AND h.docdate >= ? AND h.docdate < DATE_ADD(?, INTERVAL 1 DAY)";
$params = array($from, $to);
$types = 'ss';
if ($cashier !== '') {
    $where .= ' AND h.userid=?';
    $params[] = $cashier;
    $types .= 's';
}

$sql = "SELECT COUNT(*) AS receipts, COALESCE(SUM(h.total),0) AS sales,
        COALESCE(SUM(h.total_vat),0) AS vat,
        COALESCE(SUM(CASE WHEN h.payment_method='CASH' THEN h.total ELSE 0 END),0) AS cash_sales,
        COALESCE(SUM(CASE WHEN h.payment_method='MPESA' THEN h.total ELSE 0 END),0) AS mpesa_sales
        FROM SalesHeader h WHERE " . $where;
$stmt = $mysqli->prepare($sql);
posReportBind($stmt, $types, $params);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

$cashiers = array();
$r = $mysqli->query("SELECT userid, realname FROM www_users ORDER BY realname, userid");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $cashiers[] = $row;
    }
}

$paymentSql = "SELECT COALESCE(payment_method,'UNCLASSIFIED') AS payment_method,
                      COUNT(*) AS receipts, COALESCE(SUM(total),0) AS amount
               FROM SalesHeader h WHERE " . $where . " GROUP BY payment_method ORDER BY payment_method";
$stmt = $mysqli->prepare($paymentSql);
posReportBind($stmt, $types, $params);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$itemSql = "SELECT l.code AS itemcode, l.description, SUM(l.Quantity) AS quantity,
                   SUM(l.invoiceamount) AS sales_value, SUM(l.vatamount) AS vat_value
            FROM SalesLine l INNER JOIN SalesHeader h
              ON h.documenttype=l.documenttype AND h.documentno=l.documentno
            WHERE " . $where . "
            GROUP BY l.code,l.description ORDER BY sales_value DESC";
$stmt = $mysqli->prepare($itemSql);
posReportBind($stmt, $types, $params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$taxSql = "SELECT COALESCE(l.pos_tax_category,'UNCLASSIFIED') AS tax_category,
                       l.vatrate AS vat_rate,
                       SUM(l.invoiceamount-l.vatamount) AS net_value,
                       SUM(l.vatamount) AS vat_value,
                       SUM(l.invoiceamount) AS gross_value
                FROM SalesLine l INNER JOIN SalesHeader h
                  ON h.documenttype=l.documenttype AND h.documentno=l.documentno
                WHERE " . $where . "
                GROUP BY l.pos_tax_category,l.vatrate ORDER BY l.vatrate DESC";
$stmt = $mysqli->prepare($taxSql);
posReportBind($stmt, $types, $params);
$stmt->execute();
$taxBreakdown = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$shiftSql = "SELECT id,z_report_number,cashier_id,cashier_name,opened_at,closed_at,opening_float,
                         expected_cash,cash_counted,cash_variance,total_sales,total_vat,cash_sales,mpesa_sales,status
                  FROM pos_shifts WHERE opened_at >= ? AND opened_at < DATE_ADD(?, INTERVAL 1 DAY)";
$shiftParams = array($from, $to);
$shiftTypes = 'ss';
if ($cashier !== '') { $shiftSql .= ' AND cashier_id=?'; $shiftParams[] = $cashier; $shiftTypes .= 's'; }
$shiftSql .= ' ORDER BY opened_at DESC';
$stmt = $mysqli->prepare($shiftSql);
posReportBind($stmt, $shiftTypes, $shiftParams);
$stmt->execute();
$shifts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


$selectedShift = null;
$shiftPayments = array();
$shiftProducts = array();
$shiftReceipts = array();
$selectedShiftId = isset($_GET['shift_id']) ? (int)$_GET['shift_id'] : 0;
if ($selectedShiftId > 0) {
    $detailStmt = $mysqli->prepare("SELECT * FROM pos_shifts WHERE id=? LIMIT 1");
    $detailStmt->bind_param('i', $selectedShiftId);
    $detailStmt->execute();
    $selectedShift = $detailStmt->get_result()->fetch_assoc();
    $detailStmt->close();
    if ($selectedShift) {
        $detailStmt = $mysqli->prepare("SELECT COALESCE(payment_method,'UNCLASSIFIED') AS payment_method, COUNT(*) AS receipts, COALESCE(SUM(total),0) AS amount FROM SalesHeader WHERE pos_shift_id=? AND documenttype=1 AND COALESCE(status,1)=1 GROUP BY payment_method ORDER BY payment_method");
        $detailStmt->bind_param('i', $selectedShiftId);
        $detailStmt->execute();
        $shiftPayments = $detailStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $detailStmt->close();

        $detailStmt = $mysqli->prepare("SELECT l.code AS itemcode,l.description,SUM(l.Quantity) AS quantity,SUM(l.invoiceamount) AS sales_value,SUM(l.vatamount) AS vat_value FROM SalesLine l INNER JOIN SalesHeader h ON h.documenttype=l.documenttype AND h.documentno=l.documentno WHERE h.pos_shift_id=? AND h.documenttype=1 AND COALESCE(h.status,1)=1 GROUP BY l.code,l.description ORDER BY sales_value DESC");
        $detailStmt->bind_param('i', $selectedShiftId);
        $detailStmt->execute();
        $shiftProducts = $detailStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $detailStmt->close();

        $detailStmt = $mysqli->prepare("SELECT documentno,docdate,userid,payment_method,total,total_vat FROM SalesHeader WHERE pos_shift_id=? AND documenttype=1 AND COALESCE(status,1)=1 ORDER BY docdate,documentno");
        $detailStmt->bind_param('i', $selectedShiftId);
        $detailStmt->execute();
        $shiftReceipts = $detailStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $detailStmt->close();
    }
}

$transactionSql = "SELECT h.documentno,h.docdate,h.userid,h.payment_method,h.total,h.total_vat
                   FROM SalesHeader h WHERE " . $where . " ORDER BY h.docdate DESC,h.documentno DESC LIMIT 500";
$stmt = $mysqli->prepare($transactionSql);
posReportBind($stmt, $types, $params);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


/* Generate a real PDF using the ERP's bundled TCPDF wrapper. */
if (isset($_GET['output']) && $_GET['output'] === 'pdf') {
    $PaperSize = 'A4_Landscape';
    include('includes/PDFStarter.php');
    $pdf->addInfo('Title', 'POS Administration Report');
    $pdf->addInfo('Subject', 'POS sales and cashier reconciliation');
    $pdf->addInfo('Creator', 'SmartERP POS');

    $pageNumber = 0;
    $left = 36;
    $usableWidth = 770;
    $y = 0;
    $company = isset($_SESSION['CompanyRecord']) ? $_SESSION['CompanyRecord'] : array();
    $companyName = isset($company['coyname']) ? $company['coyname'] : 'Company';
    $companyAddress = trim(
        (isset($company['regoffice1']) ? $company['regoffice1'] . ' ' : '') .
        (isset($company['regoffice2']) ? $company['regoffice2'] . ' ' : '') .
        (isset($company['regoffice3']) ? $company['regoffice3'] . ' ' : '') .
        (isset($company['regoffice4']) ? $company['regoffice4'] . ' ' : '') .
        (isset($company['regoffice5']) ? $company['regoffice5'] : '')
    );
    $cashierLabel = 'All cashiers';
    if ($cashier !== '') {
        foreach ($cashiers as $cashierUser) {
            if ((string)$cashierUser['userid'] === $cashier) {
                $cashierLabel = trim((string)$cashierUser['realname']);
                if ($cashierLabel === '') { $cashierLabel = $cashier; }
                break;
            }
        }
        if ($cashierLabel === 'All cashiers') { $cashierLabel = $cashier; }
    }

    $drawPageHeader = function () use (&$pdf, &$pageNumber, &$y, $left, $usableWidth, $companyName, $companyAddress, $from, $to, $cashierLabel) {
        if ($pageNumber > 0) { $pdf->newPage(); }
        $pageNumber++;
        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->addText($left, 552, 15, $companyName);
        $pdf->SetFont('helvetica', '', 8);
        if ($companyAddress !== '') { $pdf->addText($left, 538, 8, $companyAddress); }
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->addText($left, 512, 12, 'POS ADMINISTRATION REPORT');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->addText($left, 498, 8, 'Period: ' . $from . ' to ' . $to . '    Cashier: ' . $cashierLabel);
        $pdf->addText($left + $usableWidth - 55, 498, 8, 'Page ' . $pageNumber);
        $pdf->line($left, 488, $left + $usableWidth, 488);
        $y = 470;
    };

    $renderTable = function ($title, $headers, $rows, $widths) use (&$pdf, &$y, &$drawPageHeader, $left, $usableWidth) {
        if ($y < 65) { $drawPageHeader(); }
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->addText($left, $y, 10, $title);
        $y -= 17;
        $headerHeight = 18;
        $pdf->SetFillColor(226, 235, 245);
        $pdf->Rect($left, $y - 4, $usableWidth, $headerHeight, 'F');
        $x = $left + 5;
        $pdf->SetFont('helvetica', 'B', 7);
        foreach ($headers as $i => $heading) {
            $pdf->addTextWrap($x, $y, $widths[$i] - 8, 7, $heading, 'left');
            $x += $widths[$i];
        }
        $y -= $headerHeight;
        $pdf->SetFont('helvetica', '', 7);
        foreach ($rows as $row) {
            if ($y < 48) {
                $drawPageHeader();
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->addText($left, $y, 10, $title . ' (continued)');
                $y -= 17;
                $pdf->SetFillColor(226, 235, 245);
                $pdf->Rect($left, $y - 4, $usableWidth, $headerHeight, 'F');
                $x = $left + 5;
                $pdf->SetFont('helvetica', 'B', 7);
                foreach ($headers as $i => $heading) {
                    $pdf->addTextWrap($x, $y, $widths[$i] - 8, 7, $heading, 'left');
                    $x += $widths[$i];
                }
                $y -= $headerHeight;
                $pdf->SetFont('helvetica', '', 7);
            }
            $x = $left + 5;
            foreach ($row as $i => $value) {
                $align = ($i > 0 && preg_match('/KES|Amount|Sales|VAT|Gross|Net|Qty|Quantity|Rate|Receipts|Variance|Float|Cash/i', $headers[$i])) ? 'right' : 'left';
                $pdf->addTextWrap($x, $y, $widths[$i] - 8, 7, (string)$value, $align);
                $x += $widths[$i];
            }
            $pdf->line($left, $y - 4, $left + $usableWidth, $y - 4);
            $y -= 14;
        }
        if (!$rows) {
            $pdf->SetFont('helvetica', 'I', 7);
            $pdf->addText($left + 5, $y, 7, 'No records found.');
            $y -= 15;
        }
        $y -= 12;
    };

    $drawPageHeader();
    $renderTable('Sales summary',
        array('Receipts', 'Gross sales (KES)', 'VAT (KES)', 'Cash sales (KES)', 'M-Pesa sales (KES)'),
        array(array((string)(int)$summary['receipts'], posReportMoney($summary['sales']), posReportMoney($summary['vat']), posReportMoney($summary['cash_sales']), posReportMoney($summary['mpesa_sales']))),
        array(105, 165, 145, 165, 190)
    );

    $paymentRows = array();
    foreach ($payments as $payment) { $paymentRows[] = array($payment['payment_method'], (string)(int)$payment['receipts'], posReportMoney($payment['amount'])); }
    $renderTable('Payment reconciliation', array('Payment method', 'Receipts', 'Amount (KES)'), $paymentRows, array(400, 150, 220));

    $taxRows = array();
    foreach ($taxBreakdown as $tax) {
        $taxRows[] = array($tax['tax_category'], posReportMoney($tax['vat_rate']), posReportMoney($tax['net_value']), posReportMoney($tax['vat_value']), posReportMoney($tax['gross_value']));
    }
    $renderTable('Tax breakdown by ERP VAT category', array('VAT category', 'VAT rate (%)', 'Net value (KES)', 'VAT (KES)', 'Gross value (KES)'), $taxRows, array(220, 105, 150, 130, 165));

    $itemRows = array();
    foreach ($items as $item) { $itemRows[] = array($item['itemcode'], $item['description'], posReportMoney($item['quantity']), posReportMoney($item['sales_value']), posReportMoney($item['vat_value'])); }
    $renderTable('Products sold', array('Item code', 'Description', 'Quantity', 'Sales (KES)', 'VAT (KES)'), $itemRows, array(115, 300, 100, 145, 110));

    $receiptRows = array();
    foreach ($transactions as $transaction) {
        $receiptRows[] = array($transaction['documentno'], $transaction['docdate'], $transaction['userid'], $transaction['payment_method'], posReportMoney($transaction['total']), posReportMoney($transaction['total_vat']));
    }
    $renderTable('Receipts (maximum 500 records)', array('Receipt', 'Date', 'Cashier', 'Payment', 'Gross (KES)', 'VAT (KES)'), $receiptRows, array(125, 145, 125, 125, 125, 130));

    $shiftRows = array();
    foreach ($shifts as $shift) {
        $shiftRows[] = array($shift['z_report_number'] ? $shift['z_report_number'] : ('Shift #' . $shift['id']), $shift['cashier_name'], $shift['opened_at'], $shift['closed_at'] ? $shift['closed_at'] : 'Open', $shift['status'], posReportMoney($shift['total_sales']));
    }
    $renderTable('Cashier shifts and Z reports', array('Z report / shift', 'Cashier', 'Opened', 'Closed', 'Status', 'Gross sales (KES)'), $shiftRows, array(140, 150, 150, 150, 90, 95));

    if ($selectedShift) {
        $renderTable('Selected shift: ' . ($selectedShift['z_report_number'] ? $selectedShift['z_report_number'] : ('Shift #' . $selectedShift['id'])),
            array('Cashier', 'Opened', 'Closed', 'Status', 'Gross sales (KES)'),
            array(array($selectedShift['cashier_name'], $selectedShift['opened_at'], $selectedShift['closed_at'] ? $selectedShift['closed_at'] : 'Not closed', $selectedShift['status'], posReportMoney($selectedShift['total_sales']))),
            array(170, 180, 180, 120, 150)
        );
        $shiftPaymentRows = array();
        foreach ($shiftPayments as $payment) { $shiftPaymentRows[] = array($payment['payment_method'], (string)(int)$payment['receipts'], posReportMoney($payment['amount'])); }
        $renderTable('Selected shift payment breakdown', array('Payment method', 'Receipts', 'Amount (KES)'), $shiftPaymentRows, array(400, 150, 220));

        $shiftProductRows = array();
        foreach ($shiftProducts as $item) { $shiftProductRows[] = array($item['itemcode'], $item['description'], posReportMoney($item['quantity']), posReportMoney($item['sales_value']), posReportMoney($item['vat_value'])); }
        $renderTable('Selected shift products sold', array('Item code', 'Description', 'Quantity', 'Sales (KES)', 'VAT (KES)'), $shiftProductRows, array(115, 300, 100, 145, 110));

        $shiftReceiptRows = array();
        foreach ($shiftReceipts as $receipt) { $shiftReceiptRows[] = array($receipt['documentno'], $receipt['docdate'], $receipt['userid'], $receipt['payment_method'], posReportMoney($receipt['total']), posReportMoney($receipt['total_vat'])); }
        $renderTable('Selected shift receipts', array('Receipt', 'Date', 'Cashier', 'Payment', 'Gross (KES)', 'VAT (KES)'), $shiftReceiptRows, array(125, 145, 125, 125, 125, 130));
    }

    $pdf->SetFont('helvetica', 'I', 7);
    $pdf->addText($left, 28, 7, 'Internal POS reconciliation report. This report does not replace KRA eTIMS fiscalisation or statutory tax invoice records.');
    $pdf->OutputD('POS-Administration-Report-' . $from . '-to-' . $to . '.pdf');
    $pdf->__destruct();
    exit;
}

$Title = 'POS Administration Reports';
include('includes/header.inc');
?>
<style>
.pos-reports-page {
    padding: 24px 28px 40px;
    color: #243247;
    background: #f4f7fb;
    min-height: calc(100vh - 72px);
}
.pos-reports-page .pos-report-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    margin: 0 0 22px;
    padding: 22px 26px;
    color: #fff;
    background: linear-gradient(120deg, #172b4d, #245b86);
    border-radius: 14px;
    box-shadow: 0 8px 24px rgba(23,43,77,.12);
}
.pos-reports-page .pos-report-heading h2 {
    margin: 0 0 6px;
    font-size: 25px;
    font-weight: 700;
    letter-spacing: -.35px;
}
.pos-reports-page .pos-report-heading p {
    margin: 0;
    color: rgba(255,255,255,.78);
    font-size: 13px;
}
.pos-reports-page .pos-report-heading .btn {
    padding: 10px 16px;
    border: 1px solid rgba(255,255,255,.55);
    border-radius: 8px;
    color: #fff;
    background: rgba(255,255,255,.10);
    font-weight: 600;
}
.pos-reports-page .pos-report-filters {
    display: flex;
    align-items: flex-end;
    flex-wrap: wrap;
    gap: 12px;
    margin: 0 0 22px !important;
    padding: 17px 20px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(15,23,42,.035);
}
.pos-reports-page .pos-report-filters label {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin: 0 !important;
    color: #526176;
    font-size: 12px;
    font-weight: 700;
}
.pos-reports-page .pos-report-filters .form-control {
    min-width: 165px;
    height: 40px;
    border: 1px solid #d5deea;
    border-radius: 7px;
    box-shadow: none;
}
.pos-reports-page .pos-report-filters .btn {
    min-height: 40px;
    padding: 8px 16px;
    border-radius: 7px;
    font-weight: 600;
}
.pos-reports-page .pos-admin-summary {
    margin: 0 -8px 16px;
}
.pos-reports-page .pos-admin-summary > [class*="col-"] {
    padding: 0 8px;
    margin-bottom: 14px;
}
.pos-reports-page .pos-admin-summary .panel {
    min-height: 118px;
    margin: 0;
    overflow: hidden;
    border: 1px solid #e3eaf2;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 4px 14px rgba(15,23,42,.045);
}
.pos-reports-page .pos-admin-summary .panel-body {
    padding: 19px 21px;
}
.pos-reports-page .pos-admin-summary small {
    color: #718096;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .8px;
}
.pos-reports-page .pos-admin-summary h3 {
    margin: 9px 0 0;
    color: #172b4d;
    font-size: 25px;
    font-weight: 750;
    line-height: 1.2;
}
.pos-reports-page h3.pos-shift-history,
.pos-reports-page h3.pos-other-reports {
    margin: 27px 0 12px;
    color: #243b53;
    font-size: 17px;
    font-weight: 750;
}
.pos-reports-page .table-responsive {
    margin-bottom: 22px;
    overflow-x: auto;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 3px 12px rgba(15,23,42,.035);
}
.pos-reports-page .table {
    margin: 0;
    color: #344256;
    font-size: 12px;
}
.pos-reports-page .table > thead > tr > th {
    padding: 12px 13px;
    color: #526176;
    background: #f1f5f9;
    border-bottom: 1px solid #dce5ef;
    border-top: 0;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .45px;
    text-transform: uppercase;
    white-space: nowrap;
}
.pos-reports-page .table > tbody > tr > td {
    padding: 11px 13px;
    vertical-align: middle;
    border-top-color: #edf1f6;
}
.pos-reports-page .table-striped > tbody > tr:nth-of-type(odd) {
    background: #fafbfd;
}
.pos-reports-page .table tbody tr:hover {
    background: #f1f7fd;
}
.pos-reports-page .table a {
    color: #1769aa;
    font-weight: 700;
}
.pos-reports-page .pos-z-detail {
    margin: 0 0 25px;
    overflow: hidden;
    border: 1px solid #d8e3ef;
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(15,23,42,.06);
}
.pos-reports-page .pos-z-detail .panel-heading {
    padding: 15px 19px;
    color: #fff;
    background: #203f63;
    border: 0;
    font-size: 15px;
}
.pos-reports-page .pos-z-detail .panel-heading .btn {
    margin-top: -5px;
    border-radius: 7px;
    font-weight: 600;
}
.pos-reports-page .pos-z-detail .panel-body {
    padding: 22px;
    background: #fff;
}
.pos-reports-page .pos-z-detail .panel-body h4 {
    margin: 22px 0 10px;
    color: #243b53;
    font-size: 14px;
    font-weight: 750;
}
.pos-reports-page .pos-z-detail .panel-body hr {
    border-color: #e8edf3;
}
.pos-reports-page > p.text-muted {
    margin: 12px 0 0;
    padding: 12px 15px;
    color: #718096;
    background: #edf2f7;
    border-radius: 8px;
    font-size: 11px;
}
@media (max-width: 767px) {
    .pos-reports-page { padding: 12px 10px 28px; }
    .pos-reports-page .pos-report-heading {
        align-items: flex-start;
        flex-direction: column;
        padding: 18px;
    }
    .pos-reports-page .pos-report-heading h2 { font-size: 21px; }
    .pos-reports-page .pos-report-filters { align-items: stretch; }
    .pos-reports-page .pos-report-filters label,
    .pos-reports-page .pos-report-filters .form-control,
    .pos-reports-page .pos-report-filters .btn { width: 100%; }
    .pos-reports-page .pos-admin-summary h3 { font-size: 21px; }
    .pos-reports-page .pos-z-detail .panel-body { padding: 14px; }
}
@media print {
    body { background: #fff !important; }
    .pos-report-filters, .pos-admin-summary, .pos-shift-history, .pos-other-reports,
    .pos-shift-history + .table-responsive, .pos-other-reports + .table-responsive,
    .pos-report-heading, .pos-z-detail .panel-heading button { display:none !important; }
    .pos-reports-page { min-height: 0; padding: 0; background: #fff; }
    .pos-z-detail { display:block !important; border:0 !important; box-shadow:none !important; }
    .pos-z-detail .panel-heading { background:#fff !important; color:#000 !important; border:0 !important; font-size:18px; }
    .pos-z-detail .panel-body { padding:0 !important; }
    .pos-reports-page .table-responsive { overflow: visible; border:0; box-shadow:none; }
}
</style>

<div class="container-fluid pos-reports-page">
    <div class="pos-report-heading">
        <div><h2>POS Administration Reports</h2><p>Sales, payment and product performance from posted POS receipts.</p></div>
        <a class="btn" href="posindex.php">Return to POS</a>
    </div>
    <form method="get" class="form-inline pos-report-filters" style="margin:15px 0;">
        <label>From <input class="form-control" type="date" name="from" value="<?php echo posReportEscape($from); ?>"></label>
        <label style="margin-left:10px;">To <input class="form-control" type="date" name="to" value="<?php echo posReportEscape($to); ?>"></label>
        <label style="margin-left:10px;">Cashier <select class="form-control" name="cashier"><option value="">All cashiers</option>
            <?php foreach ($cashiers as $cashierUser) {
                $cashierId = (string)$cashierUser['userid'];
                $cashierName = trim((string)$cashierUser['realname']);
                if ($cashierName === '') { $cashierName = $cashierId; }
            ?><option value="<?php echo posReportEscape($cashierId); ?>"<?php echo $cashierId === $cashier ? ' selected' : ''; ?>><?php echo posReportEscape($cashierName . ' (' . $cashierId . ')'); ?></option><?php } ?>
        </select></label>
        <button class="btn btn-primary" type="submit">Run report</button>
        <button class="btn btn-success" type="submit" name="output" value="pdf"><i class="fa fa-file-pdf-o"></i> Generate PDF</button>
    </form>

    <div class="row pos-admin-summary">
        <div class="col-sm-3"><div class="panel panel-default"><div class="panel-body"><small>RECEIPTS</small><h3><?php echo (int)$summary['receipts']; ?></h3></div></div></div>
        <div class="col-sm-3"><div class="panel panel-default"><div class="panel-body"><small>GROSS SALES (KES)</small><h3><?php echo posReportMoney($summary['sales']); ?></h3></div></div></div>
        <div class="col-sm-3"><div class="panel panel-default"><div class="panel-body"><small>VAT RECORDED (KES)</small><h3><?php echo posReportMoney($summary['vat']); ?></h3></div></div></div>
        <div class="col-sm-3"><div class="panel panel-default"><div class="panel-body"><small>CASH / M-PESA (KES)</small><h3><?php echo posReportMoney($summary['cash_sales']); ?> / <?php echo posReportMoney($summary['mpesa_sales']); ?></h3></div></div></div>
    </div>


    <?php if ($selectedShift) { ?>
    <section class="panel panel-primary pos-z-detail" id="pos-z-detail">
        <div class="panel-heading">
            <strong><?php echo posReportEscape($selectedShift['z_report_number'] ? $selectedShift['z_report_number'] : 'Shift #' . $selectedShift['id']); ?> — Full shift report</strong>
            <a class="btn btn-default btn-sm pull-right" href="POSReports.php?from=<?php echo rawurlencode($from); ?>&amp;to=<?php echo rawurlencode($to); ?>&amp;cashier=<?php echo rawurlencode($cashier); ?>&amp;shift_id=<?php echo (int)$selectedShift['id']; ?>&amp;output=pdf">Download Z report PDF</a>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-sm-3"><strong>Cashier</strong><br><?php echo posReportEscape($selectedShift['cashier_name']); ?></div>
                <div class="col-sm-3"><strong>Opened</strong><br><?php echo posReportEscape($selectedShift['opened_at']); ?></div>
                <div class="col-sm-3"><strong>Closed</strong><br><?php echo posReportEscape($selectedShift['closed_at'] ? $selectedShift['closed_at'] : 'Not closed'); ?></div>
                <div class="col-sm-3"><strong>Status</strong><br><?php echo posReportEscape($selectedShift['status']); ?></div>
            </div>
            <hr>
            <div class="row">
                <div class="col-sm-3"><strong>Gross sales</strong><br>KES <?php echo posReportMoney($selectedShift['total_sales']); ?></div>
                <div class="col-sm-3"><strong>VAT</strong><br>KES <?php echo posReportMoney($selectedShift['total_vat']); ?></div>
                <div class="col-sm-3"><strong>Cash sales</strong><br>KES <?php echo posReportMoney($selectedShift['cash_sales']); ?></div>
                <div class="col-sm-3"><strong>M-Pesa sales</strong><br>KES <?php echo posReportMoney($selectedShift['mpesa_sales']); ?></div>
            </div>
            <hr>
            <div class="row">
                <div class="col-sm-3"><strong>Opening float</strong><br>KES <?php echo posReportMoney($selectedShift['opening_float']); ?></div>
                <div class="col-sm-3"><strong>Expected cash</strong><br>KES <?php echo $selectedShift['expected_cash'] === null ? '—' : posReportMoney($selectedShift['expected_cash']); ?></div>
                <div class="col-sm-3"><strong>Counted cash</strong><br>KES <?php echo $selectedShift['cash_counted'] === null ? '—' : posReportMoney($selectedShift['cash_counted']); ?></div>
                <div class="col-sm-3"><strong>Cash variance</strong><br>KES <?php echo $selectedShift['cash_variance'] === null ? '—' : posReportMoney($selectedShift['cash_variance']); ?></div>
            </div>
            <h4>Payment breakdown</h4>
            <div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr><th>Method</th><th>Receipts</th><th class="text-right">Amount (KES)</th></tr></thead><tbody>
            <?php foreach ($shiftPayments as $payment) { ?><tr><td><?php echo posReportEscape($payment['payment_method']); ?></td><td><?php echo (int)$payment['receipts']; ?></td><td class="text-right"><?php echo posReportMoney($payment['amount']); ?></td></tr><?php } ?>
            <?php if (!$shiftPayments) { ?><tr><td colspan="3">No posted payments recorded.</td></tr><?php } ?>
            </tbody></table></div>
            <h4>Products sold</h4>
            <div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr><th>Item code</th><th>Description</th><th class="text-right">Quantity</th><th class="text-right">Sales (KES)</th><th class="text-right">VAT (KES)</th></tr></thead><tbody>
            <?php foreach ($shiftProducts as $item) { ?><tr><td><?php echo posReportEscape($item['itemcode']); ?></td><td><?php echo posReportEscape($item['description']); ?></td><td class="text-right"><?php echo posReportMoney($item['quantity']); ?></td><td class="text-right"><?php echo posReportMoney($item['sales_value']); ?></td><td class="text-right"><?php echo posReportMoney($item['vat_value']); ?></td></tr><?php } ?>
            <?php if (!$shiftProducts) { ?><tr><td colspan="5">No product lines recorded.</td></tr><?php } ?>
            </tbody></table></div>
            <h4>Receipts in this shift</h4>
            <div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr><th>Receipt</th><th>Date</th><th>Cashier</th><th>Payment</th><th class="text-right">Gross (KES)</th><th class="text-right">VAT (KES)</th></tr></thead><tbody>
            <?php foreach ($shiftReceipts as $receipt) { ?><tr><td><?php echo posReportEscape($receipt['documentno']); ?></td><td><?php echo posReportEscape($receipt['docdate']); ?></td><td><?php echo posReportEscape($receipt['userid']); ?></td><td><?php echo posReportEscape($receipt['payment_method']); ?></td><td class="text-right"><?php echo posReportMoney($receipt['total']); ?></td><td class="text-right"><?php echo posReportMoney($receipt['total_vat']); ?></td></tr><?php } ?>
            <?php if (!$shiftReceipts) { ?><tr><td colspan="6">No receipts recorded for this shift.</td></tr><?php } ?>
            </tbody></table></div>
        </div>
    </section>
    <?php } elseif ($selectedShiftId > 0) { ?>
        <div class="alert alert-warning">The requested shift could not be found.</div>
    <?php } ?>

    <h3 class="pos-shift-history">Cashier shifts and Z reports</h3>
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>Z report</th><th>Cashier</th><th>Opened</th><th>Closed</th><th>Status</th><th class="text-right">Opening float</th><th class="text-right">Expected cash</th><th class="text-right">Counted cash</th><th class="text-right">Variance</th><th class="text-right">Gross sales</th></tr></thead><tbody>
        <?php foreach ($shifts as $shift) { ?><tr><td><?php if ($shift['z_report_number']) { ?><a href="POSReports.php?from=<?php echo rawurlencode($from); ?>&amp;to=<?php echo rawurlencode($to); ?>&amp;cashier=<?php echo rawurlencode($cashier); ?>&amp;shift_id=<?php echo (int)$shift['id']; ?>#pos-z-detail"><?php echo posReportEscape($shift['z_report_number']); ?></a><?php } else { echo '—'; } ?></td><td><?php echo posReportEscape($shift['cashier_name']); ?></td><td><?php echo posReportEscape($shift['opened_at']); ?></td><td><?php echo posReportEscape($shift['closed_at'] ? $shift['closed_at'] : '—'); ?></td><td><?php echo posReportEscape($shift['status']); ?></td><td class="text-right"><?php echo posReportMoney($shift['opening_float']); ?></td><td class="text-right"><?php echo $shift['expected_cash'] === null ? '—' : posReportMoney($shift['expected_cash']); ?></td><td class="text-right"><?php echo $shift['cash_counted'] === null ? '—' : posReportMoney($shift['cash_counted']); ?></td><td class="text-right"><?php echo $shift['cash_variance'] === null ? '—' : posReportMoney($shift['cash_variance']); ?></td><td class="text-right"><?php echo $shift['total_sales'] === null ? '—' : posReportMoney($shift['total_sales']); ?></td></tr><?php } ?>
        <?php if (!$shifts) { ?><tr><td colspan="10">No cashier shifts found for this period.</td></tr><?php } ?>
    </tbody></table></div>

    <h3 class="pos-other-reports">Payment reconciliation</h3>
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>Payment method</th><th>Receipts</th><th class="text-right">Amount (KES)</th></tr></thead><tbody>
        <?php foreach ($payments as $payment) { ?><tr><td><?php echo posReportEscape($payment['payment_method']); ?></td><td><?php echo (int)$payment['receipts']; ?></td><td class="text-right"><?php echo posReportMoney($payment['amount']); ?></td></tr><?php } ?>
    </tbody></table></div>

    <h3 class="pos-other-reports">Tax breakdown by ERP VAT category</h3>
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>ERP VAT category</th><th class="text-right">VAT rate (%)</th><th class="text-right">Net value (KES)</th><th class="text-right">VAT (KES)</th><th class="text-right">Gross value (KES)</th></tr></thead><tbody>
        <?php foreach ($taxBreakdown as $tax) { ?><tr><td><?php echo posReportEscape($tax['tax_category']); ?></td><td class="text-right"><?php echo posReportMoney($tax['vat_rate']); ?></td><td class="text-right"><?php echo posReportMoney($tax['net_value']); ?></td><td class="text-right"><?php echo posReportMoney($tax['vat_value']); ?></td><td class="text-right"><?php echo posReportMoney($tax['gross_value']); ?></td></tr><?php } ?>
        <?php if (!$taxBreakdown) { ?><tr><td colspan="5">No tax data found for this period.</td></tr><?php } ?>
    </tbody></table></div>

    <h3 class="pos-other-reports">Products sold</h3>
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>Item code</th><th>Description</th><th class="text-right">Quantity</th><th class="text-right">Sales (KES)</th><th class="text-right">VAT (KES)</th></tr></thead><tbody>
        <?php foreach ($items as $item) { ?><tr><td><?php echo posReportEscape($item['itemcode']); ?></td><td><?php echo posReportEscape($item['description']); ?></td><td class="text-right"><?php echo posReportMoney($item['quantity']); ?></td><td class="text-right"><?php echo posReportMoney($item['sales_value']); ?></td><td class="text-right"><?php echo posReportMoney($item['vat_value']); ?></td></tr><?php } ?>
        <?php if (!$items) { ?><tr><td colspan="5">No POS sales found for this period.</td></tr><?php } ?>
    </tbody></table></div>

    <h3 class="pos-other-reports">Receipts</h3>
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>Receipt</th><th>Date</th><th>Cashier</th><th>Payment</th><th class="text-right">Gross (KES)</th><th class="text-right">VAT (KES)</th></tr></thead><tbody>
        <?php foreach ($transactions as $transaction) { ?><tr><td><?php echo posReportEscape($transaction['documentno']); ?></td><td><?php echo posReportEscape($transaction['docdate']); ?></td><td><?php echo posReportEscape($transaction['userid']); ?></td><td><?php echo posReportEscape($transaction['payment_method']); ?></td><td class="text-right"><?php echo posReportMoney($transaction['total']); ?></td><td class="text-right"><?php echo posReportMoney($transaction['total_vat']); ?></td></tr><?php } ?>
        <?php if (!$transactions) { ?><tr><td colspan="6">No POS receipts found for this period.</td></tr><?php } ?>
    </tbody></table></div>
    <p class="text-muted">These are internal POS reconciliation reports. They do not replace KRA eTIMS fiscalisation or statutory tax invoice records.</p>
</div>
<?php include('includes/footer.inc'); ?>
