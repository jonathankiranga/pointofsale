<?php
// The $PageNumber variable is initialised in 0 by PDFStarter.php
// This page header increments variable $PageNumber before printing it.
$PageNumber++;
// Inserts a page break if it is not the first page
if ($PageNumber>1) {
   $pdf->newPage();
}
$pdf->SetFont('','B',8);
//$FontSize=10;
$CPos = ($Page_Height-$Top_Margin-50);
// Print 'Quotation' info
$pdf->addTextWrap($Page_Width-$Right_Margin-170, $CPos,170, 8, _('Client').' : '. htmlspecialcharsLocal_decode($HeaderRow[10]));
$pdf->addTextWrap($Page_Width-$Right_Margin-170, $CPos-=8,170, 8, _('Phone').' : '. $HeaderRow[11]);
$pdf->addTextWrap($Page_Width-$Right_Margin-170, $CPos-=8,170, 8, _('Email').' : '. $HeaderRow[15]);
$pdf->addTextWrap($Page_Width-$Right_Margin-170, $CPos-=8,170, 8, _('Town').' : '. $HeaderRow[16]);
$pdf->addTextWrap($Page_Width-$Right_Margin-170, $CPos-=8,170, 8, _('Country').' : '. $HeaderRow[17]);
$pdf->addTextWrap($Page_Width-$Right_Margin-50, $CPos-=8,50, 8, _('Page').' :'. "$PageNumber  of $NOFPAGES" );
$pdf->addJpegFromFile($_SESSION['LABCODE'],$Page_Width-$Right_Margin-50,$Page_Height-$Top_Margin-10,0,10);
$pdf->line($Page_Width/2,$Page_Height-$Top_Margin,$Page_Width/2,$Page_Height-$Top_Margin-100);
$XPos = 42;
// Print company logo
$FontSize=8;
$pdf->addJpegFromFile($_SESSION['LogoFile'],42,$Page_Height-$Top_Margin-40,0,40);
// Print company info
$YPos = ($Page_Height-$Top_Margin-40);

$pdf->RoundRectangle(38,$Page_Height-$Top_Margin,$Page_Width-$Right_Margin-$Left_Margin,100.5, 1,1);
$pdf->addText($XPos, $YPos, $FontSize, $_SESSION['CompanyRecord']['coyname']);
$pdf->addText($XPos, $YPos-8, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($XPos, $YPos-16, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($XPos, $YPos-24, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
$pdf->addText($XPos, $YPos-32, $FontSize, _('Phone') . ': ' . $_SESSION['CompanyRecord']['telephone']);
$pdf->addText($XPos, $YPos-40, $FontSize, _('Email') . ': ' .$_SESSION['CompanyRecord']['email']);
// Print 'Delivery To' info
// Draw a rectangle with rounded corners around 'Delivery To' info
$YPos = ($Page_Height-$Top_Margin-48);
$YPos-=70;
$FontSize=10;
$pdf->SetFont('','N',12);
$headlength = (strlen(trim($headerName)) * $FontSize)/2 ;
$pdf->addText((($Page_Width-$Right_Margin-$Left_Margin-$headlength )/2),$YPos+15,12, $headerName );
if(file($barcodeurl)){
$pdf->addJpegFromFile($barcodeurl,(($Page_Width-$Right_Margin-$Left_Margin-$headlength )/1.8),$YPos-20,0,15);
}
$FontSize=8;
// Print 'Quotation For' info
$pdf->addText($XPos, $YPos,$FontSize, _('Date Received').' : '. ConvertSQLDate($HeaderRow[1]));
$pdf->addText($XPos, $YPos-10,$FontSize, _('Date Started').' : '. ConvertSQLDate($HeaderRow[2]), 'right');
$pdf->addText($XPos, $YPos-20,$FontSize, _('Date Completed').' : '. ConvertSQLDate($HeaderRow[3]), 'right');
$pdf->addText($XPos, $YPos-30,$FontSize, _('External Sample ID').' : '. $HeaderRow[4], 'right');
$pdf->addText($XPos, $YPos-40,$FontSize, _('Date Printed').' : '. date('l jS F Y'), 'right');


$pdf->addTextWrap($Page_Width-$Right_Margin-170,$YPos-6,170,$FontSize, _('Batch No'). ' : '.$HeaderRow[0], 'left');
$pdf->addTextWrap($Page_Width-$Right_Margin-170,$YPos-17,170,$FontSize, _('Sample Ref'). ': '.$documentno, 'left');
$pdf->addTextWrap($Page_Width-$Right_Margin-170,$YPos-28,170,$FontSize, _('Sampled By').':'.$HeaderRow[19], 'left');
$pdf->addTextWrap($Page_Width-$Right_Margin-170,$YPos-39,170,$FontSize, _('Sampled Method').':'.$HeaderRow[20], 'left');
$pdf->addTextWrap($Page_Width-$Right_Margin-170,$YPos-48,170,$FontSize, _('Sampled Date').':'.ConvertSQLDate($HeaderRow[21]), 'left');
$pdf->RoundRectangle(38,$YPos+17,$Page_Width-$Right_Margin-$Left_Margin,70, 1, 1);

$YPos -= 54; 
$FontSize  = 10;
$pdf->SetFillColor(135, 206, 235);
$pdf->Rect(38,200,$Page_Width-$Right_Margin-$Left_Margin,$line_height * 2.1, 'DF');

$LeftOvers = $pdf->addTextWrap(42, $YPos- $line_height,200, $FontSize, _('PARAMETER'),'left');
$LeftOvers = $pdf->addTextWrap(245,$YPos- $line_height, 85, $FontSize, _('METHOD'),'left');
$LeftOvers = $pdf->addTextWrap(330,$YPos- $line_height, 85, $FontSize, _('RESULTS'),'left');
$LeftOvers = $pdf->addTextWrap(405,$YPos- $line_height, 20, $FontSize, _('Low'),'left');
$LeftOvers = $pdf->addTextWrap(435,$YPos- $line_height, 30, $FontSize, _('Opt.'),'left');
$LeftOvers = $pdf->addTextWrap(470,$YPos- $line_height, 20, $FontSize, _('High'),'left');
$LeftOvers = $pdf->addTextWrap(503,$YPos- $line_height, 50, $FontSize, _('Standard'),'center');
$LeftOvers = $pdf->addTextWrap(503,$YPos- $line_height * 1.5,50,8, _('Limits'),'center');
//Rect(float x, float y, float w, float h [, string style])
$firstrowpos = $YPos - $line_height ;
$lastrow     = $Bottom_Margin + ($line_height * 8) ;
$ULinePos    = $Bottom_Margin + 300 ;
$thisline    = ($firstrowpos - $line_height * 2);
$endoftestresults = $Bottom_Margin + 310;

// here goes the footer
$XPos = 46;
$pdf->line($Page_Width-$Right_Margin-2,$YPos-($line_height *2) ,$Left_Margin,$YPos-($line_height *2));
$pdf->line($Page_Width-$Right_Margin-2,$thisline+$line_height,$Left_Margin,$thisline+$line_height);

