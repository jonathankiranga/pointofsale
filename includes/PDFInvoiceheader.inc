<?php
/* $Id: PDFQuotationPageHeader.inc 6540 2014-01-15 02:18:07Z rchacon $*/
/* pdf-php by R&OS code to set up a new sales order page */

// The $PageNumber variable is initialised in 0 by PDFStarter.php
// This page header increments variable $PageNumber before printing it.
$PageNumber++;
// Inserts a page break if it is not the first page
if ($PageNumber>1) {
	$pdf->newPage();
}

// Print 'Quotation' title
$pdf->addText(361,500,10, $headerName );


// Print company logo
$pdf->addJpegFromFile($_SESSION['LogoFile'], 361, 505, 0, 40);

// Print company info
$XPos = 361;
$YPos = 482;
$FontSize=10;
$pdf->addText($XPos, $YPos, $FontSize, $_SESSION['CompanyRecord']['coyname']);
$FontSize =8;
$pdf->addText($XPos, $YPos-12, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($XPos, $YPos-21, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($XPos, $YPos-30, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
$pdf->addText($XPos, $YPos-39, $FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
$pdf->addText($XPos, $YPos-48, $FontSize, $_SESSION['CompanyRecord']['email']);
$pdf->addText($XPos, $YPos-57, $FontSize,  _('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);

// Print 'Quotation' info
$pdf->addTextWrap($Page_Width-$Right_Margin-200,$YPos, 200, $FontSize,($headerName=='ORDER')?_('Order Due:'):''. ($headerName=='ORDER'? ConvertSQLDate($myrow[3]):''), 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200,$YPos-15, 200, $FontSize, _('Date').': '. ConvertSQLDate($myrow[1]), 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200,$YPos-30, 200, $FontSize, _('Page').': '.$PageNumber, 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200,$YPos-45, 200, $FontSize, _('Your LPO Ref '). ': '.$myrow[16], 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200,$YPos-60, 200, $FontSize, _($headerName.' No'). ': '.$_GET['No'], 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200,$YPos-75, 200, $FontSize, _(' Terms'). ':'.$myrow[21], 'right');

// Print 'Delivery To' info
$XPos = 46;
//$YPos = 566;
$FontSize=8;

// Print 'Quotation For' info
$pdf->addText($XPos, $YPos, $FontSize, _($headerName.' For').':');
$pdf->addText($XPos, $YPos-15, $FontSize, htmlspecialcharsLocal_decode($myrow[5]));
$pdf->addText($XPos, $YPos-30, $FontSize, $myrow[12]);
$pdf->addText($XPos, $YPos-45, $FontSize, $myrow[13]);
$pdf->addText($XPos, $YPos-60, $FontSize, $myrow[15]);
$YPos -= 80;
// Draw a rectangle with rounded corners around 'Delivery To' info
$XPos  = 50;
$YPos += 12;
$pdf->RoundRectangle($XPos-10, $YPos+60+10, 225+10+10, 60+10+10, 10, 10);// Function RoundRectangle from includes/class.pdf.php
//----$YPos -= 82;
// Draw a rectangle with rounded corners around 'Quotation For' info
// Print the currency name
$pdf->addText($Page_Width/2-60, $YPos-5, $FontSize, _('Currency') . ' - ' . $myrow[6] . ' ' . $CurrencyName[$myrow[6]]);
// Print table header
$XPos = 40;
$YPos -= 37;
$FontSize=10;

$firstrowpos = $YPos-$line_height ;
$lastrow  = $Bottom_Margin+200;

$LeftOvers = $pdf->addTextWrap(42, $YPos,250, $FontSize, _('Item Description'),'left');
$LeftOvers = $pdf->addTextWrap(480, $YPos, 85, $FontSize, _('Price'),'right');
$LeftOvers = $pdf->addTextWrap(590, $YPos, 55, $FontSize, _('VAT Rate'),'right');
$LeftOvers = $pdf->addTextWrap(650, $YPos, 70, $FontSize, _('VAT Amount'),'right');  
$LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100, $YPos, 90, $FontSize, _('Total Amount'),'right');
/*Column lines*/
//  added 360 +100
$pdf->line(520, $Bottom_Margin+360,520, $Bottom_Margin+120+100);
$pdf->line(580, $Bottom_Margin+360,580, $Bottom_Margin+100);
$pdf->line(650, $Bottom_Margin+360,650, $Bottom_Margin+120+100);
$pdf->line(720, $Bottom_Margin+360,720, $Bottom_Margin+100);
/*put totals*/
$SummaryYpos = $Bottom_Margin+115+100;
$pdf->line($Left_Margin, $Bottom_Margin+220,$Page_Width-$Right_Margin, $Bottom_Margin+220);
$LeftOvers = $pdf->addTextWrap(500,$SummaryYpos-10,200, 10, _('SUB TOTAL'),'right');
$LeftOvers = $pdf->addTextWrap(500,$SummaryYpos -=$line_height *2.5,200,10, _('DISCOUNT '),'right');
$LeftOvers = $pdf->addTextWrap(500,$SummaryYpos -=$line_height *2.5,200,10, _('VAT AMOUNT'),'right');
$LeftOvers = $pdf->addTextWrap(500,$SummaryYpos -=$line_height *2.5,200,10, _('TOTAL INVOICE'),'right');
// under line sub total
$pdf->line(580, $Bottom_Margin+100+100,$Page_Width-$Right_Margin, $Bottom_Margin+100+100);
// under line sub total
$pdf->line(580, $Bottom_Margin+75+100,$Page_Width-$Right_Margin, $Bottom_Margin+75+100);
// under line vat
$pdf->line(580, $Bottom_Margin+50+100,$Page_Width-$Right_Margin, $Bottom_Margin+50+100);
/*draw a box with nice round corner for entering line items */

/*90 degree arc at top right of box 0 degrees starts a bottom */
$pdf->partEllipse($Page_Width-$Right_Margin-10, $Bottom_Margin+350,0,90,10,10);
/*line to the top   */
$pdf->line($Page_Width-$Right_Margin-10, $Bottom_Margin+360,$Left_Margin+10, $Bottom_Margin+360);
/*line under headings to top   */
  $pdf->line($Page_Width-$Right_Margin, $Bottom_Margin+335,$Left_Margin, $Bottom_Margin+335);
/*Dow top left corner */
$pdf->partEllipse($Left_Margin+10, $Bottom_Margin+350,90,180,10,10);
/*Do a line to the Side left corner */
$pdf->line($Left_Margin, $Bottom_Margin+350,$Left_Margin, $Bottom_Margin+10+100);
/*Now do the bottom left corner 180 - 270 coming back west*/
$pdf->partEllipse($Left_Margin+10, $Bottom_Margin+10+100,180,270,10,10);
/*Now a line to the bottom margine */
 $pdf->line($Left_Margin+10, $Bottom_Margin+100,$Page_Width-$Right_Margin-10, $Bottom_Margin+100);
///////////////left////////////top/////////////////////left///////////////////top
/*Now do the bottom right corner */
$pdf->partEllipse($Page_Width-$Right_Margin-10, $Bottom_Margin+10+100,270,350,10,10);
/*Finally join up to the  right corner where started */
$pdf->line($Page_Width-$Right_Margin, $Bottom_Margin+10+100,$Page_Width-$Right_Margin, $Bottom_Margin+350);

$YPos -= (2 * $line_height);
?>
