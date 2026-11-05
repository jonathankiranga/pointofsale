<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.
include('includes/AccountBalance.inc');

$Title = _('Print Trial Balance');

$reportnames = array();
$reportnames['EXEC [AccountBalance]']="Trail Balance";
$reportnames['EXEC [Profit_loss]']="Trading Profit and Loss";
$reportnames['EXEC [Balancesheet]']="Balance Sheet";
    
If(isset($_GET['type'])){
    switch ($_GET['type']) {
    case 1:
        $Report_to_execute ='EXEC [AccountBalance]';
        break;
    case 2:
        $Report_to_execute ='EXEC [Profit_loss]';
        break;
    default:
        $Report_to_execute ='EXEC [Balancesheet]';
        break;
   }
}

if(isset($_POST['trailbalance'])){
    
    $headerName = $reportnames['EXEC [AccountBalance]'];
    
    $Result=DB_query("Select min(B.[start_date]),max(B.End_date)
    from [FinancialPeriods] B where B.periodno='".$_POST['Financial_Periods']."'",$db);
    $finacialdates=DB_fetch_row($Result);   
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    
    $pdf->addInfo('Title',_('Financial Reports'));
    $pdf->addInfo('Subject',_('Accounts'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 15;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFfinancialsheader.inc');
     
     $YPos = $firstrowpos;
     $Calc = new Calculator();
     $Calc->Reset();
     
     $debit= 0;
     $credit = 0;
     $FontSize = 11;
     
     //DB_query("EXEC [AccountBalance] ",$db);
     $YearPeriod=$_POST['Financial_Periods'];
     $ResultsP =DB_AccountBalance();
     
     while($rows = DB_fetch_array($ResultsP)){
         $Calc = new Calculator();
         $amount = $Calc->Get($rows);
       
    if($rows['ReportStyle']==0 || $rows['ReportStyle']==2 || $rows['ReportStyle']==4){
 
        $LeftOvers = $pdf->addTextWrap(42,$YPos,250,8,ucfirst($rows['accdesc']),'left');
        if($amount>0){
              $debit += $amount;
              $LeftOvers = $pdf->addTextWrap(360, $YPos,100,8, number_format($amount,0),'right');
        } elseif($amount<0){
              $credit += $amount * -1;
               $LeftOvers = $pdf->addTextWrap(420, $YPos,100,8, number_format($amount *-1,0),'right');
        }
         
    }else{
           $LeftOvers = $pdf->addTextWrap(42,$YPos,250,10,$accdesc,'left');
           $YPos -= $line_height ;
    }               
        
        
         $YPos -= $line_height ;
         if($YPos < ($lastrow+($line_height * 2))){
             $pdf->line($Page_Width-$Right_Margin,$YPos,$Left_Margin,$YPos);
             include('includes/PDFfinancialsheader.inc');
             $YPos=$firstrowpos;
         }
       
     }
     
     $YPos -= $line_height ;   
     $pdf->line($Page_Width-$Right_Margin,$YPos,$Left_Margin,$YPos);
     $YPos -= $line_height ;   
     
     $LeftOvers = $pdf->addTextWrap(145, $YPos,250,10,'Totals','left');
     $LeftOvers = $pdf->addTextWrap(360, $YPos, 85,8, number_format($debit,0),'right');
     $LeftOvers = $pdf->addTextWrap(420, $YPos, 85,8, number_format($credit,0),'right');
    
             
    $pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('TrailBalance'). '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
}else{
  include('includes/header.inc');
  $FR = new FinancialPeriods();
  
  echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/maintenance.png" title="' . _('General ledger') .'" alt="" />' . _('General ledger') . '</p>';
  echo '<form  action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><div>';
  echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>';
  echo '<table  class="table table-bordered"><tr><td>Reporting Period</td></tr>';
  
  $FR->Get();
  
  echo '<tr><td colspan="2"><input type="submit" name="trailbalance" value="Print Trail Balance"/></td></tr></table>';
  echo '</div></form>';
  
  
  
  
  
    $REsults = DB_query('Select 
        min(B.[start_date]),
        max(B.End_date),
        periodno 
        from [FinancialPeriods] B  
        where B.closed=0 
        Group by periodno',$db);
    
       $financialPeriods = DB_fetch_row($REsults);
       $StartDate = $financialPeriods[0];
       $Enddate = $financialPeriods[1];
       $YearPeriod = $financialPeriods[2];
       
       
    echo '<table class="table"><tr><td>PERIOD REPORTING <div> From :<b>'.
            ConvertSQLDate($StartDate).'</b> To <b>'.ConvertSQLDate($Enddate). '</b></div></td>'
            . '</tr></table>';
            
    echo '<DIV>'
            . '<table class="table table-bordered">'
            . '<thead><tr>'
            . '<th>ACCOUNT NO</th>'
            . '<th>ACCOUNT NAME</th>'
            . '<th>BALANCE/INCOME</th>'
            . '<th>Formula</th>'
            . '<th>TYPE</th>'
            . '<th>BALANCE</th>'
            . '</thead></tr>';
 
    $ClassCalc = new Calculator();
    $ClassCalc->Reset();
    
    $k =1; 
   $URL = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
  
    $YearPeriod = $_POST['Financial_Periods'];
 #  DB_query("EXEC [AccountBalance] ",$db);
 $REsults =DB_AccountBalance();
while($rows=DB_fetch_array($REsults)){
    if($k==1){
      $k=0;
    }else{
      $k=1;
    }
    
    $ClassCalc = new Calculator();
    $amount = $ClassCalc->Get($rows);

    echo '<tr '.($k==1?'class="OddTableRows"':'class="EvenTableRows"').'>'
    . '<td><a href="ChartofAccounts.php?AKD='.trim($rows['accno']).'"> Code:'.$rows['ReportCode'].' </a></td>';
    echo '<td><a href="LedgerReports.php?Drill='.trim($rows['accno']).'">'.$rows['accdesc'].'</a></td>';
    echo '<td>'.$BalanceSheet[$rows['balance_income']].'</td>';
    echo '<td>'.$rows['Calculation'].'</td>';
    echo '<td>'.$AccountType[$rows['ReportStyle']].'</td>';
    echo '<td class="number">'.number_format($amount,2).'</td>';
    echo '</tr>' ;

}

echo '</table></DIV>';
include('includes/footer.inc');
    
}


?>