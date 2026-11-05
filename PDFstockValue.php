<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.
$Title = _('Print Stock');
include('includes/PostStockCost.inc');
$stockClass = new StockBalance();
    
if(isset($_POST['trailbalance'])){
    if(Date1GreaterThanDate2($_POST['fromdate'],$_POST['todate'])==TRUE){
     unset($_POST['trailbalance']);
     $errorExists=1;
    }
}

if(isset($_POST['trailbalance'])){
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    
    $pdf->addInfo('Title',_('Inventory Reports'));
    $pdf->addInfo('Subject',_('inventory'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 15;
    $PageNumber = 0;
    $line_height = 12;
        
    Getheader();
     $FontSize = 8;
     $YPos = $firstrowpos;
     $Balance = 0;
     
     $ResultsP = $stockClass->EXECUTE();
     foreach ($ResultsP as $rows) {

         $totalline = ($rows['Close_l'] * $rows['averagestock'])+($rows['Close_f'] * ($rows['partperunit'] * $rows['averagestock']));
         $Balance += $totalline;
         
         $LeftOvers = $pdf->addTextWrap(50, $YPos,100, $FontSize, ucfirst($rows['stockname']),'left');
         $LeftOvers = $pdf->addTextWrap(150, $YPos,50, $FontSize, trim($rows['fulqtyName']).'/'.$rows['LoosqtyName'],'right');
         $LeftOvers = $pdf->addTextWrap(230, $YPos,30, $FontSize, number_format($rows['averagestock'],0),'right');
         $LeftOvers = $pdf->addTextWrap(310, $YPos,30, $FontSize, number_format($rows['partperunit'],0),'right');
         $LeftOvers = $pdf->addTextWrap(400, $YPos,30, $FontSize, number_format($rows['Close_f'],0),'right');
         $LeftOvers = $pdf->addTextWrap(450, $YPos,30, $FontSize, number_format($rows['Close_l'],0),'right');
         $LeftOvers = $pdf->addTextWrap(500, $YPos,50, $FontSize, number_format($totalline,2),'right');
           
         $YPos -= $line_height ;
         if($YPos < ($lastrow + ($line_height * 3))){
             Getheader();
             $YPos=$firstrowpos;
             $FontSize = 8;
         }
      }
          
    
$pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('Inventory'). '_' . date('Y-m-d').'.pdf');
$pdf->__destruct();

    
} else {
    
  $Title = _('Print Inventory');
  include('includes/header.inc');
  
  if(isset($errorExists)){
     prnMsg('You have selected an invalid date range','warn');
  }
  
  echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Inventory Report') .'" alt="" />' . _('Inventory Report') . '</p>';
  echo '<form  action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><div>';
  echo '<input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'"/>';
  echo '<table style="height:200px"><tr><td valign="top"><table class="table table-bordered"><tr>'
    . '<td>From Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="fromdate" size="11" maxlength="10" readonly="readonly" value="' .$_POST['fromdate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>
       <td>To Date</td><td><input tabindex="2" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="todate" size="11" maxlength="10" readonly="readonly" value="' .$_POST['todate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';
 echo '<tr><td>Select Store</td><td><select name="store"><option value="AllStores">All</option>';
 
   $sql="SELECT [code],[Storename] FROM [stores]";
   $ResultsP = DB_query($sql,$db);
    while($rows = DB_fetch_array($ResultsP)){
          echo '<option value="'.$rows['code'].'">'.$rows['Storename'].'</option>' ;
   }
   
  echo '</select></td></tr></table></td></tr>'
  . '<tr><td colspan="2"><input type="submit" name="trailbalance" value="Print Inventory"/></td></tr></table>';
  echo '</div></form>';
  
  include('includes/footer.inc');
  
  
}
 
  
class StockBalance {
      var $procedure;
      var $Fromdate;
      var $Todate;
      var $StoreCode;
      
      function __construct() {
        
          $this->Fromdate = ConvertSQLDate($_POST['fromdate']);
          $this->Todate = ConvertSQLDate($_POST['todate']);
          $this->StoreCode = $_POST['store'];
      }
            
    function EXECUTE(){
          Global $db;
          $Results= InventoryReport($this->Fromdate,$this->Todate,$this->StoreCode);
          Return  $Results;
      }  
  }
  
  
function Getheader(){
    Global $PageNumber,$pdf,$XPos,$YPos,$Page_Width,$Right_Margin,$FontSize,$firstrowpos,$lastrow,$Bottom_Margin;
    Global $Page_Height,$Top_Margin,$line_height;
// The $PageNumber variable is initialised in 0 by PDFStarter.php
// This page header increments variable $PageNumber before printing it.
$PageNumber++;
// Inserts a page break if it is not the first page
if ($PageNumber>1) {
    $pdf->newPage();
}
// Print 'Quotation' title
// $XPos = 361; 
$XPos=46;
$pdf->addTextWrap($Page_Width-$Right_Margin-220, $Page_Height-$Top_Margin-$FontSize * 1, 200, $FontSize, _('Page').': '.$PageNumber, 'right');

// Print company info
$XPos = 60;
$YPos = ($Page_Height-$Top_Margin-$FontSize * 2)-30;
$FontSize=12;
$pdf->addText($XPos, $YPos, $FontSize, htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$FontSize =10;
$pdf->addText($XPos, $YPos-12, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($XPos, $YPos-21, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($XPos, $YPos-30, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
$pdf->addText($XPos, $YPos-39, $FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
$pdf->addText($XPos, $YPos-48, $FontSize, $_SESSION['CompanyRecord']['email']);
$pdf->addText($XPos, $YPos-57, $FontSize, _('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);

$pdf->addText(($Page_Width/2)- $Right_Margin ,$YPos-70,12,_('Stock Balance Report') );
$pdf->addText(($Page_Width/2)- $Right_Margin ,$YPos-90,8,_('From :'). $_POST['fromdate']._(' To :'). $_POST['todate'] );
$pdf->line($Page_Width-$Right_Margin,$YPos-100,$Left_Margin,$YPos-100);

// Print 'Delivery To' info
$XPos = 46;
$YPos -= 120;
$FontSize=12;
$FontSize=10;


$LeftOvers = $pdf->addTextWrap(50, $YPos,100, $FontSize,_('Stock Name'),'left');
$LeftOvers = $pdf->addTextWrap(150, $YPos,80, $FontSize,_('UOM/SUB UOM'),'left');
$LeftOvers = $pdf->addTextWrap(230, $YPos,80, $FontSize,_('Average Cost'),'center');
$LeftOvers = $pdf->addTextWrap(310, $YPos,80, $FontSize,_('Kit Size'),'center');
$LeftOvers = $pdf->addTextWrap(400, $YPos,80, $FontSize,_('Closing Stock'),'center');
$LeftOvers = $pdf->addTextWrap(490, $YPos,80, $FontSize ,_('Stock Value'),'center');

$pdf->line($Page_Width-$Right_Margin,($YPos - $line_height),$Left_Margin,($YPos - $line_height) );

$firstrowpos = ($YPos - $line_height) -20 ;
$lastrow = $Bottom_Margin + ($line_height * 2);

$YPos -= (2 * $line_height);

}
    
?>