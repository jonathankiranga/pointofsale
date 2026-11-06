<?php
/*
 * Inventory report page header styled consistently with POS X/Z reports.
 * This include is reused for every page of the inventory PDF.
 */
$PageNumber++;
if ($PageNumber > 1) {
    $pdf->newPage();
}

$company = isset($_SESSION['CompanyRecord']) ? $_SESSION['CompanyRecord'] : array();
$companyName = isset($company['coyname']) ? $company['coyname'] : '';
$companyAddress = trim(
    (isset($company['regoffice1']) ? $company['regoffice1'] . ' ' : '') .
    (isset($company['regoffice2']) ? $company['regoffice2'] . ' ' : '') .
    (isset($company['regoffice3']) ? $company['regoffice3'] . ' ' : '') .
    (isset($company['regoffice4']) ? $company['regoffice4'] . ' ' : '') .
    (isset($company['regoffice5']) ? $company['regoffice5'] : '')
);
$companyContact = trim(
    (isset($company['telephone']) ? $company['telephone'] : '') .
    (isset($company['email']) ? '  |  ' . $company['email'] : '')
);

$pdf->SetFont('helvetica', 'B', 15);
$pdf->SetTextColor(23, 43, 77);
$pdf->addText(46, 552, 15, $companyName);
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(82, 97, 118);
if ($companyAddress !== '') {
    $pdf->addText(46, 539, 8, $companyAddress);
}
if ($companyContact !== '') {
    $pdf->addText(46, 527, 8, $companyContact);
}

$pdf->SetFillColor(23, 43, 77);
$pdf->Rect(46, 493, 744, 25, 'F');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(255, 255, 255);
$pdf->addText(56, 501, 12, _('INVENTORY REPORT'));
$pdf->SetFont('helvetica', '', 8);
$pdf->addTextWrap(520, 501, 260, 8, _('Page') . ' ' . $PageNumber, 'right');

$pdf->SetTextColor(36, 50, 71);
$pdf->addText(46, 480, 8, _('Store') . ': ' . $store_name);
$pdf->addText(300, 480, 8, _('Period') . ': ' . $_POST['fromdate'] . '  -  ' . $_POST['todate']);
$pdf->addTextWrap(570, 480, 220, 8, _('Generated') . ': ' . date('d/m/Y H:i:s'), 'right');
$pdf->line(46, 471, 790, 471);

/* Column group headings */
$YPos = 451;
$pdf->SetFillColor(226, 235, 245);
$pdf->Rect(46, $YPos - 5, 744, 24, 'F');
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetTextColor(23, 43, 77);
$pdf->addTextWrap(50, $YPos, 100, 8, _('Stock Name'), 'left');
$pdf->addTextWrap(150, $YPos, 80, 8, _('UOM / SUB UOM'), 'left');
$pdf->addTextWrap(230, $YPos, 80, 8, _('Opening Stock'), 'center');
$pdf->addTextWrap(310, $YPos, 80, 8, _('Purchases'), 'center');
$pdf->addTextWrap(390, $YPos, 80, 8, _('Production'), 'center');
$pdf->addTextWrap(470, $YPos, 80, 8, _('Available'), 'center');
$pdf->addTextWrap(550, $YPos, 80, 8, _('WO Requests'), 'center');
$pdf->addTextWrap(630, $YPos, 80, 8, _('Sales Orders'), 'center');
$pdf->addTextWrap(710, $YPos, 80, 8, _('Closing Stock'), 'center');
$pdf->SetTextColor(0, 0, 0);
$pdf->line(46, $YPos - 9, 790, $YPos - 9);

$firstrowpos = $YPos - 27;
$lastrow = $Bottom_Margin + ($line_height * 2);
$YPos = $firstrowpos;
$FontSize = 8;
?>