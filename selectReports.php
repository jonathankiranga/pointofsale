<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.
include('includes/SQL_CommonFunctions.inc');
include('includes/AccountBalance.inc');
include('includes/GetBalance_withfilter.inc');

if(!isset($_POST['Accountcode'])){
                
        $Title = _('Ledger Reports ');
        include('includes/header.inc');

        echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/sales.png" title="' . _('General Ledger') .'" alt="" />' . ' ' . _('General Ledger') . '</p>';

        echo '<form action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post">';
        echo '<div>';
        echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
        echo '<table class="table table-bordered">';
        echo '<tr><td>Select Account</td><td>'
        . '<select name="Accountcode" required="required"><option></option>';
          
     $result=DB_query("Select accno,accdesc from acct order by accdesc",$db);
    while ($myrow = DB_fetch_array($result)) {
        if ($_POST['Accountcode']==$myrow['accno']){
                echo '<option selected="selected" value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        } else {
                echo '<option value="'. $myrow['accno'] .'">' . $myrow['accdesc'] . '</option>';
        }
    }  

    echo '</select></td></tr>';
        
        echo '<tr><td>From Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="fromdate" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['fromdate']. '" onchange="isDate(this,this.value,'."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
        echo '<td>To Date</td><td><input tabindex="2" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="Todate" size="11" maxlength="10"  required="required" value="' .$_POST['Todate']. '" onchange="isDate(this, this.value,'."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';
        echo '<tr><td>Select Report Output</td><td><select name="reportoutput">'
                  . '<option value="1">HTML</option>'
                 . '<option value="2">PDF</option></select></td></tr>';
        echo '</table>' ;
        echo '<input type="submit" name="submitreport" value="Show Report"/></div><form/>';

        include('includes/footer.inc');
        
}elseif(isset($_POST['Accountcode'])) {
   if($_POST['reportoutput']==1){
       ShowHTML();
   }else{
       ShowPDF();
   }
}
    
Function LedgerHeader(){
    
 Global $pdf,$FontSize,$PageNumber,$line_height,$YPos,$XPos ;
 Global $Page_Width,$Right_Margin,$Left_Margin,$Page_Height ;
 Global $Bottom_Margin,$Top_Margin,$Balance,$finacialdates,$AccountName ;
 Global $firstrowpos,$lastrow ;
    
 $FontSize = 12;
 $PageNumber++;
// Inserts a page break if it is not the first page
if ($PageNumber>1) {
  $pdf->newPage();
}
    
$XPos=46;
$pdf->addTextWrap($Page_Width-$Right_Margin-220, $Page_Height-$Top_Margin-$FontSize * 2, 200, $FontSize, _('Page').': '.$PageNumber, 'right');
$topRow = $Page_Height-$Top_Margin-$FontSize * 2;
$pdf->addText($XPos,$topRow,$FontSize,$_SESSION['CompanyRecord']['coyname']);

// Print company info
$XPos = 60;
$YPos = ($Page_Height-$Top_Margin-$FontSize * 3)-30;
$FontSize=12;
$FontSize =10;

$pdf->addText(($Page_Width/2)-$Right_Margin-$Left_Margin,$YPos,12,$AccountName);
$pdf->addText(($Page_Width/2)-$Right_Margin-$Left_Margin,$YPos-20,8,_('From :'). $_POST['fromdate']._(' To :'). $_POST['Todate']) ;
$pdf->line($Page_Width-$Right_Margin,$YPos-30,$Left_Margin,$YPos-30);

// Print 'Delivery To' info
$XPos = 46;
$YPos -= 40;
$FontSize=12;
// Draw a rectangle with rounded corners around 'Delivery To' info
$FontSize=10;
$LeftOvers = $pdf->addTextWrap(42, $YPos,50, $FontSize, _('Date'),'left');
$LeftOvers = $pdf->addTextWrap(90, $YPos,50, $FontSize, _('Doc No'),'left');
$LeftOvers = $pdf->addTextWrap(145, $YPos,100, $FontSize, _('Doc Type'),'left');
$LeftOvers = $pdf->addTextWrap(245, $YPos,120, $FontSize, _('Narrative'),'left');
$LeftOvers = $pdf->addTextWrap(360, $YPos, 85, $FontSize, _('Debit'),'right');
$LeftOvers = $pdf->addTextWrap(420, $YPos, 85, $FontSize, _('Credit'),'right');
$LeftOvers = $pdf->addTextWrap(480, $YPos, 85, $FontSize, _('Balance'),'right');
$pdf->line($Page_Width-$Right_Margin,$YPos - $line_height,$Left_Margin,$YPos - $line_height);
$LeftOvers = $pdf->addTextWrap(480,($YPos-($line_height * 2)),85,$FontSize,number_format($Balance,2),'right');

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
           . " left join [BankAccounts] on [bankcode] = BankAccounts.accountcode"
           . " where  [journalno]='".$journal."'";
   
   $ResultIndex = DB_query($sql,$db);
   $Row_Memo = DB_fetch_row($ResultIndex);
   
   return  $Row_Memo[0].''.$Row_Memo[1].''.$Row_Memo[2];
}

Function ShowHTML(){
    Global $db;
    
    $SQL="SELECT [accdesc],[balance_income] FROM [acct] where [accno]='".$_POST['Accountcode']."'";
    $ResultIndex = DB_query($SQL,$db);
    $row = DB_fetch_row($ResultIndex);
    $AccountName = $row[0];
    $balance_income = $row[1];
    DB_free_result($ResultIndex);
    
    include('includes/header.inc');
    include('includes/Getdrilldownstatement.inc');
    
    echo '<Div class="centre">'.$AccountName.'</DIV>'
       . '<Div class="centre">'._('From :'). $_POST['fromdate']._(' To :'). $_POST['Todate'].'</DIV>'
       . '<table class="table table-bordered" id="GL"><tr>'
       . '<th>Date</th><th>Doc No</th><th>Doc Type</th>'
       . '<th>Narrative</th><th>Debit</th>'
       . '<th>Credit</th><th>Balance</th></tr>';
    
    $ResultDrill=DB_query($SQLDrilldown,$db);
     $OpnBal=DB_fetch_row($ResultDrill);
     $Balance = $openbal ;
     $ResultDrill=DB_query($SQLDrilldown,$db);
     
        
               echo '<tr><td>'. $_POST['fromdate'] .'</td>';
               echo '<td>Bal</td>';
               echo '<td></td>';
               echo '<td>Openning Balance</td>';
               echo '<td class="number"></td>';
               echo '<td class="number"></td>';
               echo '<td class="number">'.number_format($Balance,2) .'</td></tr>';
     
       while($row=DB_fetch_array($ResultDrill)){
           if($row['AMOUNT']!=0){
               
                $debit = 0;$credit = 0; $Balance += $row['AMOUNT'];
                $namecontanct = GetAccount($row['journalno']);

                if($row['AMOUNT']>0){
                   $debit = $row['AMOUNT'];
                }else{
                   $credit = $row['AMOUNT'] * -1;
                }
                
               echo '<tr><td>'. ConvertSQLDate($row['Docdate']) .'</td>';
               echo '<td>'. $row['DocumentNo'] .'</td>';
               echo '<td>'.$Doctypes[$row['DocumentType']] .'</td>';
               echo '<td>'.$namecontanct." ".$row['narration'] .'</td>';
               echo '<td class="number">'.number_format($debit,2) .'</td>';
               echo '<td class="number">'.number_format($credit,2) .'</td>';
               echo '<td class="number">'.number_format($Balance,2) .'</td></tr>';
                
           }
       }
       echo '</table>';
       echo '<input type="button" onclick="tableToExcel(\'GL\',\'General Ledger\')" value="Export to Excel">';

        include('includes/footer.inc');
}

FUNCTION ShowPDF(){
     Global $db;
     
    $Doctypes=array();
    
    $Results = DB_query("Select [typeid],[typename] from [systypes_1]",$db);
    while($roe = DB_fetch_array($Results)){
        $Doctypes[$roe['typeid']] = $roe['typename'];
    }
    
      
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    
    $pdf->addInfo('Title',_('Financial Reports'));
    $pdf->addInfo('Subject',_('Accounts'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 15;
    $PageNumber = 0;
    $line_height = 12;
    $Balance = 0;
    $firstrowpos = 0;
    $lastrow = 0;
    
    $SQL="SELECT [accdesc],[balance_income] FROM [acct] where [accno]='".$_POST['Accountcode']."'";
    $ResultIndex = DB_query($SQL,$db);
    $row = DB_fetch_row($ResultIndex);
    $AccountName = $row[0];
    $balance_income = $row[1];
    DB_free_result($ResultIndex);
     
        
    include('includes/Getdrilldownstatement.inc');
    
     $ResultDrill=DB_query($SQLDrilldown,$db);
     $OpnBal=DB_fetch_row($ResultDrill);
     $Balance = $openbal;
    //Balcfwd
    LedgerHeader();
    $YPos=$firstrowpos;
    
       $ResultDrill=DB_query($SQLDrilldown,$db);
       while($row=DB_fetch_array($ResultDrill)){
           if($row['AMOUNT']!=0){
               
                $debit = 0;$credit = 0; $Balance += $row['AMOUNT'];
                $namecontanct = GetAccount($row['journalno']);

                if($row['AMOUNT']>0){
                   $debit = $row['AMOUNT'];
                }else{
                   $credit = $row['AMOUNT'] * -1;
                }

                $LeftOvers = $pdf->addTextWrap(42, $YPos,50, 8,ConvertSQLDate($row['Docdate']),'left');
                $LeftOvers = $pdf->addTextWrap(100, $YPos,50, 8,$row['DocumentNo'],'left');
                $LeftOvers = $pdf->addTextWrap(145, $YPos,100,8,$Doctypes[$row['DocumentType']],'left');
                $LeftOvers = $pdf->addTextWrap(245, $YPos,120, 8,$namecontanct." ".$row['narration'],'left');
                $LeftOvers = $pdf->addTextWrap(360, $YPos, 85,8,number_format($debit,2),'right');
                $LeftOvers = $pdf->addTextWrap(420, $YPos, 85, 8,number_format($credit,2),'right');
                $LeftOvers = $pdf->addTextWrap(480, $YPos, 85, 8,number_format($Balance,2),'right');

                $YPos -= $line_height;
           }
           
        if($lastrow > $YPos){
            LedgerHeader();
            $YPos=$firstrowpos;
        }
        
    }
     
    $pdf->OutputI($_SESSION['DatabaseName'].'_' ._('LedgerAccount').'_'.$_POST['Accountcode'].'_'. date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
    unset($_POST['Accountcode']);
}




?>
