<?php
$PageSecurity=0;
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.
include('includes/SQL_CommonFunctions.inc');
include('includes/AccountBalance.inc');
include('includes/GetBalance_withfilter.inc');
$PaperSize='A4';

include('includes/PDFStarter.php');

if(isset($_GET['Accountcode'])){
    $_POST['Accountcode']=$_GET['Accountcode'];
    $_POST['fromdate']=ConvertSQLDate($_GET['fromdate']);
    $_POST['Todate']=ConvertSQLDate($_GET['Todate']);
    $_POST['reportoutput']=2;
}

    $Doctypes=array();
    $Results = DB_query("Select [typeid],[typename] from [systypes_1]",$db);
    while($roe = DB_fetch_array($Results)){
        $Doctypes[$roe['typeid']] = $roe['typename'];
    }

if(!isset($_POST['Accountcode'])){
    include('includes/header.inc');
    ?>
    <script type="text/javascript">
        $(document).ready(function() {
           function loadinit(){ 
           var a = document.createElement('a');
               a.href='index.php?link=CashFlowReport';
               document.body.appendChild(a);
               a.click();   
            }  

           loadinit();
        })
       </script>
   <?php
   
}else{
    ShowPDF();
 }
    
 
 function SearchmyBankArray($accountcode){
    
    $return=false;
    foreach ($_SESSION['bankaccounts'] as $item) {
    if (trim($item) == $accountcode) {
        $return=true;
        break;
    }
  }
  
 return  $return;
}

Function LedgerHeader(){
    
 Global $pdf,$FontSize,$PageNumber,$line_height,$YPos,$XPos ;
 Global $Page_Width,$Right_Margin,$Left_Margin,$Page_Height ;
 Global $Bottom_Margin,$Top_Margin,$Balance,$finacialdates,$AccountName ;
 Global $firstrowpos,$lastrow ;
 
$AccountName='Cash Flow Quick Audit Report';
    
 $FontSize = 12;
 $PageNumber++;
// Inserts a page break if it is not the first page
if ($PageNumber>1) {
  $pdf->newPage();
}
    
$XPos=46;
$pdf->addTextWrap($Page_Width-$Right_Margin-220, $Page_Height-$Top_Margin-$FontSize * 2, 200, $FontSize, _('Page').': '.$PageNumber, 'right');
$topRow = $Page_Height-$Top_Margin-$FontSize * 2;
$pdf->addText(($Page_Width/2)-$Right_Margin-$Left_Margin,$topRow,$FontSize,$_SESSION['CompanyRecord']['coyname']);

// Print company info
$XPos = 60;
$YPos = ($Page_Height-$Top_Margin-$FontSize * 3)-30;
$FontSize=12;
$FontSize =10;

$pdf->addText(($Page_Width/2)-$Right_Margin-$Left_Margin,$YPos,12,$AccountName);
$pdf->addText(($Page_Width/2)-$Right_Margin-$Left_Margin,$YPos-15,10,_('From :'). $_POST['fromdate']._(' To :'). $_POST['Todate']) ;
$pdf->line($Page_Width-10,$YPos-30,$Left_Margin-20,$YPos-30);

// Print 'Delivery To' info
$XPos = 46;
$YPos -= 40;
$FontSize=12;
// Draw a rectangle with rounded corners around 'Delivery To' info
$FontSize=10;
$LeftOvers = $pdf->addTextWrap(42, $YPos,50, $FontSize, _('Date'),'left');
$LeftOvers = $pdf->addTextWrap(90, $YPos,50, $FontSize, _('Doc No'),'left');
$LeftOvers = $pdf->addTextWrap(145, $YPos,100, $FontSize, _('Doc Type'),'left');
$LeftOvers = $pdf->addTextWrap(200, $YPos,100, $FontSize, _('Narrative'),'left');
$LeftOvers = $pdf->addTextWrap(300, $YPos, 85, $FontSize, _('Debit Account'),'left');
$LeftOvers = $pdf->addTextWrap(400, $YPos, 85, $FontSize, _('Credit Account'),'left');
$LeftOvers = $pdf->addTextWrap(450, $YPos, 85, $FontSize, _('Amount'),'right');
$LeftOvers = $pdf->addTextWrap(500, $YPos, 85, $FontSize, _('CB Balance'),'right');
$pdf->line($Page_Width-10,$YPos - $line_height,$Left_Margin-20,$YPos - $line_height);

$firstrowpos =($YPos - ($line_height * 3))  ;
$lastrow     = $Bottom_Margin + ($line_height * 2);
$YPos -= (2 * $line_height);
}  

Function GetAccount($journal){
    global $db;
    
   $sql="select "
           . " IFNULL(Creditors.customer,''),"
           . " IFNULL(debtors.customer,''),"
           . " IFNULL(BankAccounts.bankName,'')"
           . " from  [Generalledger] "
           . " left join creditors on [suppliercode] = creditors.itemcode"
           . " left join debtors on [cutomercode] = debtors.itemcode"
           . " left join [BankAccounts] on Generalledger.[bankcode] = BankAccounts.accountcode"
           . " where  [journalno]='".$journal."'";
   
   $ResultIndex = DB_query($sql,$db);
   $Row_Memo = DB_fetch_row($ResultIndex);
   
   return  $Row_Memo[0].''.$Row_Memo[1].''.$Row_Memo[2];
}

FUNCTION ShowPDF(){
  
 Global $pdf,$FontSize,$PageNumber,$line_height,$YPos,$XPos ;
 Global $Page_Width,$Right_Margin,$Left_Margin,$Page_Height ;
 Global $Bottom_Margin,$Top_Margin,$Balance,$finacialdates,$AccountName ;
 Global $firstrowpos,$lastrow,$Doctypes,$db ;
    
    $pdf->addInfo('Title',_('Financial Reports'));
    $pdf->addInfo('Subject',_('Accounts'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 15;
    $PageNumber = 0;
    $line_height = 12;
    $Balance = 0;
    $firstrowpos = 0;
    $lastrow  = 0;
   
    
    $SQL="SELECT [accno],[accdesc],[balance_income] FROM [acct]";
    $ResultIndex = DB_query($SQL,$db);
    while($row = DB_fetch_array($ResultIndex)){
        $ac=trim($row['accno']);
        $AccountList[$ac]=$row;
    }
   
    DB_free_result($ResultIndex);
    LedgerHeader();
           
    $SQLDrilldown ="SELECT 
    [journalno],
    [Docdate] ,
    [period] ,
    [DocumentNo],
    [DocumentType],
    [accountcode] ,
    [balaccountcode],
    ([amount] * [ExchangeRate]) as AMOUNT ,
      [currencycode] ,
      [ExchangeRate],
      [cutomercode] ,
      [suppliercode] ,
      [bankcode],
      [reconcilled] ,
      [narration] ,
      [ExchangeRateDiff],
      [VATaccountcode],
      [VATamount] ,
      [dimension] ,
      [dimension2]   
    from  [Generalledger] 
    where  Generalledger.Docdate between '".FormatDateForSQL($_POST['fromdate'])."' "
        . " and '".FormatDateForSQL($_POST['Todate'])."'"
        . " order by [Docdate]";
    
    $Cashbookbalance=0;
       $YPos=$firstrowpos;
       $YPos -= $line_height;
       $ResultDrill=DB_query($SQLDrilldown,$db);
       while($row=DB_fetch_array($ResultDrill)){
           $drac=trim($row['accountcode']);
           $crac=trim($row['balaccountcode']);
           
            $namecontanct = GetAccount($row['journalno']);
            $debitaccount = $AccountList[$drac]['accdesc'];
            $creditaccount = $AccountList[$crac]['accdesc'];
if($_POST['Accountcode']=='income'){
            if((SearchmyBankArray($drac)==true and SearchmyBankArray($crac)==false)) {
            if(SearchmyBankArray($drac)==true){
                $Cashbookbalance +=$row['AMOUNT'];
            } else {
                $Cashbookbalance -=$row['AMOUNT'];
            }

            $LeftOvers = $pdf->addTextWrap(42, $YPos,50, 8,ConvertSQLDate($row['Docdate']),'left');
            $LeftOvers = $pdf->addTextWrap(100, $YPos,50, 8,$row['DocumentNo'],'left');
            $LeftOvers = $pdf->addTextWrap(145, $YPos,50,8,$Doctypes[$row['DocumentType']],'left');
            $LeftOvers = $pdf->addTextWrap(200, $YPos,80, 8,$namecontanct." ".$row['narration'],'left');
            $LeftOvers = $pdf->addTextWrap(300, $YPos,80, 8,$debitaccount,'left');
            $LeftOvers = $pdf->addTextWrap(400, $YPos,80, 8,$creditaccount,'left');
            $LeftOvers = $pdf->addTextWrap(450, $YPos, 85, 8,number_format($row['AMOUNT'],2),'right');
            $LeftOvers = $pdf->addTextWrap(500, $YPos, 85, 8,number_format($Cashbookbalance,2),'right');

            $YPos -= $line_height;
            
         }
     }else{
         
     if((SearchmyBankArray($drac)==false and SearchmyBankArray($crac)==true)) {
                 
            if(SearchmyBankArray($drac)==true){
                $Cashbookbalance +=$row['AMOUNT'];
            } else {
                $Cashbookbalance -=$row['AMOUNT'];
            }

            $LeftOvers = $pdf->addTextWrap(42, $YPos,50, 8,ConvertSQLDate($row['Docdate']),'left');
            $LeftOvers = $pdf->addTextWrap(100, $YPos,50, 8,$row['DocumentNo'],'left');
            $LeftOvers = $pdf->addTextWrap(145, $YPos,50,8,$Doctypes[$row['DocumentType']],'left');
            $LeftOvers = $pdf->addTextWrap(200, $YPos,80, 8,$namecontanct." ".$row['narration'],'left');
            $LeftOvers = $pdf->addTextWrap(300, $YPos,80, 8,$debitaccount,'left');
            $LeftOvers = $pdf->addTextWrap(400, $YPos,80, 8,$creditaccount,'left');
            $LeftOvers = $pdf->addTextWrap(450, $YPos, 85, 8,number_format($row['AMOUNT'],2),'right');
            $LeftOvers = $pdf->addTextWrap(500, $YPos, 85, 8,number_format($Cashbookbalance,2),'right');

            $YPos -= $line_height;
            
         }
    }

        if($lastrow > $YPos){
            LedgerHeader();
            $YPos=$firstrowpos;
        }
        
    }
     
$pdf->OutputI($_SESSION['DatabaseName'].'_' ._('CashFlowAudit').'_'. date('Y-m-d').'.pdf');
$pdf->__destruct();
  
}




?>
