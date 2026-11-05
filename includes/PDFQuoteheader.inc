<?php
/* pdf-php by R&OS code to set up a new sales order page */
// The $PageNumber variable is initialised in 0 by PDFStarter.php
// This page header increments variable $PageNumber before printing it.
$PageNumber++;
// Inserts a page break if it is not the first page
if ($PageNumber>1) {
    $pdf->newPage();
}
$YPos = $Page_Height-$Top_Margin-$FontSize*5;
$XPos = 361;
$FontSize=10;
// Print 'Quotation' info
$pdf->Rect(12,10,$Page_Width-$Left_Margin+12,$Page_Height-$Top_Margin+10,"D");
//$XPos, $YPos, $Width, $Height, $Text, $Align='J', $border=0, $fill=0
$pdf->addTextWrap($Left_Margin, $Page_Height-$Top_Margin-$FontSize*2, 200, 10, _('Date').': '. ConvertSQLDate($myrow[1]), 'left');
$pdf->addTextWrap($Left_Margin, $Page_Height-$Top_Margin-$FontSize*3, 200, 10, _('Page').': '.$PageNumber, 'left');
$pdf->addTextWrap($Left_Margin, $Page_Height-$Top_Margin-$FontSize*4, 200, 10, _('Terms'). ': '.$myrow[18], 'left');
$pdf->addTextWrap($Left_Margin, $Page_Height-$Top_Margin-$FontSize*5, 200, 10, _($headerName.' No'). ': '.$_GET['No'], 'left');
// le detail des totaux, demarre a 221 après le cadre des totaux
$pdf->SetLineWidth(0.1); 
// line($x1,$y1,$x2,$y2,$style=array())
$Middlepage = ($Page_Width/2);
$pdf->line($Middlepage,$Page_Height-$Top_Margin-155,$Middlepage,$Page_Height-$Top_Margin+20);
// Print company info
$XPos = 361;
// Print company logo
$pdf->addJpegFromFile($_SESSION['LogoFile'],$Page_Width-$Right_Margin-140,$Page_Height-$Top_Margin*2.2,0,50);
$pdf->addText($Page_Width-$Right_Margin-250, $YPos,10,htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$pdf->addText($Page_Width-$Right_Margin-250, $YPos-12, 10,_('Address 1'). ': '. $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($Page_Width-$Right_Margin-250, $YPos-21, 10,_('Address 2'). ': '. $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($Page_Width-$Right_Margin-250, $YPos-30, 10,_('Address 3'). ': '. $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] );
$pdf->addText($Page_Width-$Right_Margin-250, $YPos-39, 10,_('Tel'). ': ' . $_SESSION['CompanyRecord']['telephone']);
$pdf->addText($Page_Width-$Right_Margin-250, $YPos-48, 10,_('PIN') . ': ' .$_SESSION['CompanyRecord']['PIN']);
$pdf->addText($Page_Width-$Right_Margin-250, $YPos-57, 10,_('Sales Representative') . ': ' .$myrow[7]);
$pdf->addText($Page_Width-$Right_Margin-250, $YPos-69, 10,_('Email'). ': '. $myrow[8]);
$pdf->addText($Page_Width-$Right_Margin-250, $YPos-81, 10, _('Currency') . ' - ' . $myrow[6] . ' ' . $CurrencyName[$myrow[6]]);

// Print 'Delivery To' info
$XPos = 46;
$YPos = $Page_Height-$Top_Margin-80;
$FontSize=10;
$pdf->addText($Left_Margin, $YPos, $FontSize, _('Customer').':' );
$pdf->addText($Left_Margin, $YPos-15, $FontSize,htmlspecialcharsLocal_decode($myrow[5]));
$pdf->addText($Left_Margin, $YPos-27, $FontSize,_('Email'). ': '. $myrow[10]);
$pdf->addText($Left_Margin, $YPos-39, $FontSize,_('Tel'). ': '. $myrow[14]);
$pdf->addText($Left_Margin, $YPos-51, $FontSize,_('Contact'). ': '.$myrow[15]);

// Draw a rectangle with rounded corners around 'Delivery To' info
$XPos= 50;
$YPos = $YPos-72;

// Print table header
$XPos = 40;
$YPos -= 20;
$FontSize = 10;
// header bottomline
$pdf->line(12,$YPos+$line_height+6,$Page_Width-$Right_Margin+12,$YPos+$line_height+6);

$LeftOvers = $pdf->addTextWrap($Left_Margin, $YPos,40, $FontSize, _('Item Description'),'right');
$LeftOvers = $pdf->addTextWrap(90, $YPos,80, $FontSize, _('Test Parameters'),'left');
$LeftOvers = $pdf->addTextWrap(380, $YPos, 50, $FontSize, _('Price'),'right');
$LeftOvers = $pdf->addTextWrap(440, $YPos, 50, $FontSize, _('VAT'),'right');
$LeftOvers = $pdf->addTextWrap(490, $YPos, 90, $FontSize, _('Total Amount'),'right');

// 630
$firstrowpos = $YPos-$line_height ;
// Calculate space required
$Bottom_Row  = ($Bottom_Margin+($line_height*40));
   
if($Bottom_Row > $lastrow ){
    $lastrow = $Bottom_Row;
}

$columtop = $YPos+($line_height * 1.5);

$pdf->line(12,$YPos-5,$Page_Width-$Right_Margin+12,$YPos-5);
/*Column lines*/
$SummaryYpos = $lastrow-($line_height*4);
 //right first column
$pdf->line(85, $columtop,85, $lastrow);
$pdf->line(360, $columtop,360, $lastrow);

$SummaryYpos = $lastrow-$line_height*1.2;

$PermanetPosition = $lastrow-$line_height*1.2;
/*put totals*/
$pdf->line(12,$SummaryYpos+($line_height*1.2),$Page_Width-$Right_Margin+12,$SummaryYpos+($line_height*1.2));
$SavedYpos=$SummaryYpos;
$LeftOvers = $pdf->addTextWrap(400,$SummaryYpos,90,10, _('Sub Total'),'right');
$LeftOvers = $pdf->addTextWrap(400,$SummaryYpos -= ($line_height * 1.2) ,90,10, _('Discount'),'right');
$LeftOvers = $pdf->addTextWrap(400,$SummaryYpos -= ($line_height * 1.2) ,90,10, _('VAT'),'right');
$LeftOvers = $pdf->addTextWrap(400,$SummaryYpos -= ($line_height * 1.2) ,90,10, _('Total'),'right');

$pdf->line(440, $columtop,440, $SummaryYpos-10);
$pdf->line(490, $columtop,490, $SummaryYpos-10);

// bottom horizontal line

$pdf->line(12,$SummaryYpos-10,$Page_Width-$Right_Margin+12,$SummaryYpos-10);

$YPos -= (2*$line_height);