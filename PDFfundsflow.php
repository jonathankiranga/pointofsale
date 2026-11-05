<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/AccountBalance.inc');
include('includes/chartbalancing.inc');

$Title = _('Funds Flow');
$reportnames = array();
$reportnames['EXEC [Balancesheet]']="Funds Flow";

if(isset($_POST['trailbalance'])){
  if($_POST['output']=='1'){
            CustomizedBalanceSheet();
        }else{
            Showhtml();
        }
} else {
    
  include('includes/header.inc');
  $FR = new FinancialPeriods();
  echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/reports.png" title="' 
          . _('Cash Flow Report') .'" alt="" />' . _('Cash Flow Report') . '</p>';
  
  echo '<form  action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><div>';
  echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>';
  echo '<table  class="table-bordered table"><tr><td>Reporting Period</td></tr>';
        $FR->Get();
  echo '<tr><td>Select Report Output</td><td>'
            . '<select name="output">'
            . '<option value="1">PDF</option>'
            . '<option value="2">HTML/EXCEL</option>'
            . '</select>'
            . '</td></tr>';
  echo '<tr><td colspan="2"><input type="submit" name="trailbalance" value="Print Funds Flow"/>'
       . '</td></tr></table>';
  echo '</div></form>';
   
include('includes/footer.inc');
    
}

    
Function Addline(){
    global $Page_Width,
            $Right_Margin,
            $YPos,$pdf,
            $Left_Margin,
            $line_height,
            $lastrow,$firstrowpos;
    
    $YPos -= $line_height ;
         if($YPos < ($lastrow+$line_height)){
            $pdf->line($Page_Width-$Right_Margin,$YPos,$Left_Margin,$YPos);
            include('includes/PDFfundsflowheader.inc');
            $YPos=$firstrowpos;
         }
}    

Function CustomizedBalanceSheet(){
          
    global $db,$YearPeriod,$reportnames;   
         
     $headerName = $reportnames['EXEC [Balancesheet]'];
    $PaperSize='A4';
    include('includes/PDFStarter.php');
      
    $Result=DB_query("Select min(B.[start_date]),max(B.End_date)
    from [FinancialPeriods] B where B.periodno='".$_POST['Financial_Periods']."'",$db);
    $finacialdates=DB_fetch_row($Result);   
    
    $pdf->addInfo('Title',_('Financial Reports'));
    $pdf->addInfo('Subject',_('Accounts'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 15;  $PageNumber = 0; $line_height = 13;
        
    include('includes/PDFfundsflowheader.inc');
    
    $YPos = $firstrowpos;
    $FundsFlowForYear = new FundsFlowForYear();
    $FontSize = 11; $amount   = 0; $lastYear = 0;
  
    $YearDataArray = $FundsFlowForYear->CalYearlydata();
    foreach ($YearDataArray as $rowspl) {
        
       $AMOUNT = $rowspl['amount'];
       $Diplay = ($AMOUNT==0)?'': number_format($AMOUNT,0) ;
       $accdesc = ucfirst($rowspl['Description']);
       $FontSize=10;
       
        $LeftOvers = $pdf->addTextWrap(65,$YPos,250, $FontSize,$accdesc,'left');
        $LeftOvers = $pdf->addTextWrap(420,$YPos,100, $FontSize,$Diplay,'right');
        $YPos -= $line_height * 2  ;
         
         if($YPos < ($lastrow + $line_height)){
           include('includes/PDFfundsflowheader.inc');
          $YPos = $firstrowpos;
         }
     }
                         
   $pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('Cashflow_Year'). '_' . date('Y-m-d').'.pdf');
   $pdf->__destruct();
  
  
    }
   
Function Showhtml(){
     Global $db;
    
    include('includes/header.inc');
        
    $Result=DB_query("Select min(B.[start_date]),max(B.End_date)
    from [FinancialPeriods] B where B.periodno='".$_POST['Financial_Periods']."'",$db);
    $finacialdates=DB_fetch_row($Result);   
    
    echo '<Div class="centre">Funds Flow statemet </DIV>'
       . '<Div class="centre">'._('From :'). ConvertSQLDate($finacialdates[0])._(' To :'). ConvertSQLDate($finacialdates[1]) .'</DIV>'
       . '<div class="container"><table class="table table-bordered" id="GL"><tr>'
       . '<th>DESECRIPTION</th><th>AMOUNT</th></tr>';
 
    $FundsFlowForYear = new FundsFlowForYear();
    $YearDataArray = $FundsFlowForYear->CalYearlydata();
   foreach ($YearDataArray as $rowspl) {
     
       $AMOUNT = $rowspl['amount'];
       $Diplay = ($AMOUNT==0)?'': number_format($AMOUNT,0) ;
       $accdesc = ucfirst($rowspl['Description']);

       echo '<tr><td>'. $accdesc .'</td><td class="number">'.$Diplay.'</td></tr>';
   
     }
     echo '</table></div>';
       
     echo '<input type="button" onclick="tableToExcel(\'GL\',\'CashFlow\')" value="Export to Excel">';

   include('includes/footer.inc');
}

?>