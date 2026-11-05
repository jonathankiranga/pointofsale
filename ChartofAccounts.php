<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Setting Up Company Accounts');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/chartbalancing.inc');
include('includes/AccountBalance.inc');

echo '<p class="page_title_text">'
. '<img src="css/'.$Theme.'/images/maintenance.png" title="' . _('General ledger') .'" alt="" />'
. ' ' . _('General ledger') . '</p>';

function Newaccount(){
    Global $db;
        
    $PostingG=array();
    $PostingG[]="";
    $ResultIndex=DB_query("SELECT [code],[defaultgl_vat],[vatcategory] FROM [GLpostinggroup]",$db);
    while($row=DB_fetch_array($ResultIndex)){
        $PostingG[$row['code']]=$row['code'];
    }


    $Sale_Purchase_Neither=array();
    $Sale_Purchase_Neither[0]="";
    $Sale_Purchase_Neither[1]="Bank";
    
    $BalanceSheet=array();
    $BalanceSheet[0]="Balance Sheet";
    $BalanceSheet[1]="Profit and Loss";
    $BalanceSheet[2]="Control";
    
    $AccountType=array();
    $AccountType[0]="Posting";
    $AccountType[1]="Heading";
    $AccountType[2]="Total";
    $AccountType[3]="Begin-Total";
    $AccountType[4]="End-Total";
       
    $DirectPosting=array();
    $DirectPosting[0]="No";
    $DirectPosting[1]="Yes";
    
    $Blocked=array();
    $Blocked[0]="No";
    $Blocked[1]="Yes";
    
    echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">'
            . '<div class="container">';
    echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
    echo '<input type="hidden" name="ACCNO" value="' . $acccountno . '" />';
    echo '<table class="table"><tr><td>';
    echo '<table class="table-bordered">'
        . '<tr><td><label>Account Code</label></td><td><input type="text" size="8" name="ReportCode" required="required" value="'.$_POST['ReportCode'].'"/></td></tr>'
        . '<tr><td><label>Account Name</label></td><td><input type="text" size="30" name="accdesc" required="required"  value="'.$_POST['accdesc'].'"/></td></tr>';
    echo '<tr><td><label>Income/Balance</label></td><td><select name="BalanceSheet">';
        foreach ($BalanceSheet as $key => $value) {
            echo sprintf('<option value="%s" %s>%s</option>',$key,($key==$_POST['BalanceSheet']?'selected="selected"':''),$value);
        }
    echo '</select></td>';
    echo '<td><label>Account Type</label></td><td><select name="AccountType">';
        foreach ($AccountType as $key => $value) {
            echo sprintf('<option value="%s" %s>%s</option>',$key,($key==$_POST['AccountType']?'selected="selected"':''),$value);
        }
    echo '</select></td></tr>';
    echo '<tr><td><label>Direct Posting Allowed</label></td><td><select name="DirectPosting">';
        foreach ($DirectPosting as $key => $value) {
            echo sprintf('<option value="%s" %s>%s</option>',$key,($key==$_POST['DirectPosting']?'selected="selected"':''),$value);
        }
    echo '</select></td>';     
    echo '<td><label>Blocked</label></td><td><select name="Blocked">';
        foreach ($Blocked as $key => $value) {
            echo sprintf('<option value="%s" %s>%s</option>',$key,($key==$_POST['Blocked']?'selected="selected"':''),$value);
        }
    echo '</select></td></tr>';
    
    echo '<tr><td><label>Reconciliation</label></td><td><select name="Sale_Purchase_Neither">';
        foreach ($Sale_Purchase_Neither as $key => $value) {
            echo sprintf('<option value="%s" %s>%s</option>',$key,($key==$_POST['Sale_Purchase_Neither']?'selected="selected"':''),$value);
        }
    echo '</select></td></TR>';
    
    
     echo '<tr><td><label>Select A Posting Group</label></td><td><select name="postinggroup"><option></option>';
        foreach ($PostingG as $key => $value) {
             echo sprintf('<option value="%s" %s>%s</option>',$key,($key==$_POST['postinggroup']?'selected="selected"':''),$value);
        }
    echo '</select></td></TR>';
    
    ECHO '<tr><td><label>Formular use Add(+) only</label></td><td colspan="4"><input type="text" size="50" name="Calculation"  value="'.$_POST['Calculation'].'"/>eg: +[accountno]+[account~1]</td>'
            . '</tr>';
    echo '<tr><td colspan="4">'
            . '<input type="submit" name="submit" value="Refresh"/>'
            . '<input type="submit" name="Newaccounts" value="New Account Head"/></td>';
    echo '</table>';
    
    echo '</th></tr></table>';
      
    echo '</div></form>' ;
    
}

function Showaccount($acccountno=''){
    Global $db;
    
    $PostingG=array();
   
    $ResultIndex=DB_query("SELECT [code],[defaultgl_vat],[vatcategory] FROM [GLpostinggroup]",$db);
    while($row=DB_fetch_array($ResultIndex)){
        $PostingG[$row['code']]=$row['code'];
    }
    
     $REsults=DB_query("SELECT 
       [ReportCode],[accdesc],[balance_income],[ReportStyle],
       [direct],[inactive],[Sale_Purchase_Neither],[Calculation],[postinggroup]
       [accno]  FROM [acct] where accno='".$acccountno."'", $db);
     
    $GeneralRow = DB_fetch_row($REsults);
    $_POST['ReportCode']=trim($GeneralRow[0]);
    $_POST['accdesc']=trim($GeneralRow[1]);
    $_POST['BalanceSheet']=trim($GeneralRow[2]);
    $_POST['AccountType']=trim($GeneralRow[3]);
    $_POST['DirectPosting']=trim($GeneralRow[4]);
    $_POST['Blocked']=trim($GeneralRow[5]);
    $_POST['Sale_Purchase_Neither']=trim($GeneralRow[6]);
    $_POST['Calculation']=trim($GeneralRow[7]);
    $_POST['postinggroup']=trim($GeneralRow[8]);
          
    $Sale_Purchase_Neither=array();
    $Sale_Purchase_Neither[0]="";
    $Sale_Purchase_Neither[1]="Bank";
       
    $BalanceSheet=array();
    $BalanceSheet[0]="Balance Sheet";
    $BalanceSheet[1]="Profit and Loss";
    $BalanceSheet[2]="Control";
    
    $AccountType=array();
    $AccountType[0]="Posting";
    $AccountType[1]="Heading";
    $AccountType[2]="Total";
    $AccountType[3]="Begin-Total";
    $AccountType[4]="End-Total";
     
    $DirectPosting=array();
    $DirectPosting[0]="No";
    $DirectPosting[1]="Yes";
    
    $Blocked=array();
    $Blocked[0]="No";
    $Blocked[1]="Yes";
    
    echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">'
            . '<div class="container">';
    echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
    echo '<input type="hidden" name="ACCNO" value="' . $acccountno . '" />';
    echo '<table class="table"><tr><td>';
    echo '<table class="table-bordered">'
        . '<tr><td><label>Account Code</label></td><td><input type="text" size="8" name="ReportCode" required="required" value="'.$_POST['ReportCode'].'"/></td></tr>'
        . '<tr><td><label>Account Name</label></td><td><input type="text" size="30" name="accdesc" required="required"  value="'.$_POST['accdesc'].'"/></td></tr>';
    echo '<tr><td><label>Income/Balance</label></td><td><select name="BalanceSheet">';
     
    foreach ($BalanceSheet as $key => $value) {
        echo sprintf('<option value="%s" %s>%s</option>',$key,($key==$_POST['BalanceSheet']?'selected="selected"':''),$value);
    }
        
    echo '</select></td>';
    
    echo '<td><label>Account Type</label></td><td><select name="AccountType">';
        foreach ($AccountType as $key => $value) {
            echo sprintf('<option value="%s" %s>%s</option>',$key,($key==$_POST['AccountType']?'selected="selected"':''),$value);
        }
    echo '</select></td></tr>';
    
    
    echo '<tr><td><label>Direct Posting Allowed</label></td><td><select name="DirectPosting">';
        foreach ($DirectPosting as $key => $value) {
            echo sprintf('<option value="%s" %s>%s</option>',$key,($key==$_POST['DirectPosting']?'selected="selected"':''),$value);
        }
    echo '</select></td>';
       
     echo '<td><label>Blocked</label></td><td><select name="Blocked">';
        foreach ($Blocked as $key => $value) {
            echo sprintf('<option value="%s" %s>%s</option>',$key,($key==$_POST['Blocked']?'selected="selected"':''),$value);
        }
    echo '</select></td></tr>';
    
    echo '<tr><td><label>Reconciliation Type</label></td><td><select name="Sale_Purchase_Neither">';
        foreach ($Sale_Purchase_Neither as $key => $value) {
            echo sprintf('<option value="%s" %s>%s</option>',$key,($key==$_POST['Sale_Purchase_Neither']?'selected="selected"':''),$value);
        }
    echo '</select></td></TR>';
    
     echo '<tr><td><label>Select A Posting Group</label></td><td><select name="postinggroup"><option></option>';
        foreach ($PostingG as $key => $value) {
            echo sprintf('<option value="%s" %s>%s</option>',$key,($key==$_POST['postinggroup']?'selected="selected"':''),$value);
        }
    echo '</select></td></TR>';
    
    ECHO '<tr><td><label>Formular use Add(+)only</label></td><td colspan="4">'
    . '<input type="text" size="50" name="Calculation"  value="'.$_POST['Calculation'].'"/>eg: +[accountno]+[account~1]</td>'
            . '</tr>';
    echo '<tr><td colspan="4">'
            . '<input type="submit" name="submit" value="Refresh"/>'
            . '<input type="submit" name="submitaccounts" value="Edit Account Head"/></td>';
    echo '</table>';
    
    echo '</td></tr></table>';
       echo '</div></form>' ;
    
}

function CreateNewAccount(){
    global $db;
    $Errors=0;
        
    if(isset($_POST['Calculation'])){
        if(mb_strlen($_POST['Calculation'])>0){
            $Array1=explode('+',$_POST['Calculation']);
            foreach ($Array1 as $value) {
                $ResultIndex=DB_query("Select accno from Acct where ReportCode='".$value."'",$db);
                if(DB_num_rows($ResultIndex)==0){
                    prnMsg("This formular has an invalid account :'".$value."'",'warn');
                    $Errors++;
                }
            }
            $Array1=explode('-',$_POST['Calculation']);
            foreach ($Array1 as $value) {
                $ResultIndex=DB_query("Select accno from Acct where ReportCode='".$value."'",$db);
                if(DB_num_rows($ResultIndex)==0){
                    prnMsg("This formular has an invalid account :'".$value."'",'warn');
                     $Errors++;
                }
            }
         }
     }
    
    $ResultIndex=DB_query("Select accno from Acct where ReportCode='".$_POST['ReportCode']."'",$db);
    if(DB_num_rows($ResultIndex)==0  and  $Errors==0){

        if($_POST['BalanceSheet']==0){
            $_POST['DirectPosting']=0;
        }
        
       $ID= Triger_getaccountno($_POST['accdesc']);

      $SQL=sprintf("INSERT INTO [acct] (accno , [ReportCode],[accdesc],[balance_income],[ReportStyle],
       [direct],[inactive],[Sale_Purchase_Neither],[Calculation],postinggroup) values
       ('%s','%s','%s',%s,%s,%s,%s,%s,'%s','%s')",$ID,$_POST['ReportCode'],$_POST['accdesc'],$_POST['BalanceSheet'],
       $_POST['AccountType'],$_POST['DirectPosting'],$_POST['Blocked'],
       $_POST['Sale_Purchase_Neither'],$_POST['Calculation'],$_POST['postinggroup']);
    
       $results=DB_query($SQL,$db);
       if(DB_error_no($db)>0){
           prnMsg(DB_error_msg($db));
       }else{
           unset($_POST);
       }

    }else{
        prnMsg('You may have selected the same code on another account <b>'.$_POST['ReportCode'].'</b>','warn');
        prnMsg('You may have entered an invalid formular <b>'.$_POST['Calculation'].'</b>','warn');
    }
    
}

function UpdateAccount(){
    global $db;
    $Errors=0;
        
    if(isset($_POST['Calculation'])){
                
      if(mb_strlen($_POST['Calculation'])>0){
                
           if(strpos($_POST['Calculation'],'+') >0 ){  
            $Array1=explode('+',$_POST['Calculation']);
            foreach ($Array1 as $value) {
                $findsql="Select accno from Acct where ReportCode='".$value."'";
                $ResultIndex=DB_query($findsql,$db);
                if(DB_num_rows($ResultIndex)==0  and mb_strlen($value)>0){
                   prnMsg("This formular + has an invalid account :'".$value."'",'warn');
                   $Errors++;
                }
               } 
            }
            
           if(strpos($_POST['Calculation'],'-') >0 ){  
               
                $Array2=explode('-',$_POST['Calculation']);
                foreach ($Array2 as $value) {               
                $findsqlm="Select accno from Acct where ReportCode='".$value."'";
                $ResultIndex=DB_query($findsqlm,$db);

                if(DB_num_rows($ResultIndex)==0 and mb_strlen($value)>0){
                   prnMsg("This formular - has an invalid account :'".$value."'",'warn');
                   $Errors++;
                }
                
              }
           }
                       
         }
     }
    
    $chsql="Select accno from Acct where ReportCode='".$_POST['ReportCode']."' and accno !='".$_POST['ACCNO']."'";
    $ResultIndex=DB_query($chsql,$db);
       
    if(DB_num_rows($ResultIndex)==0  and  $Errors==0 ){

      $SQL=sprintf("Update [acct] set 
          [ReportCode]='%s', 
          [accdesc]='%s', 
          [balance_income]=%s,
          [ReportStyle]=%s,
          [direct]=%s, 
          [inactive]=%s,
          [Sale_Purchase_Neither]=%s,
          [Calculation]='%s',[postinggroup]='%s' where accno='%s'",
       $_POST['ReportCode'],$_POST['accdesc'],$_POST['BalanceSheet'],
       $_POST['AccountType'],$_POST['DirectPosting'],$_POST['Blocked'],
       $_POST['Sale_Purchase_Neither'],$_POST['Calculation'],
       $_POST['postinggroup'],$_POST['ACCNO']);
     
       $results=DB_query($SQL,$db);
       if(DB_error_no($db)>0){
           prnMsg(DB_error_msg($db));
       }else{
           unset($_POST);
       }

    }else{
        prnMsg('You may have selected the same code on another account <b>'.$_POST['ReportCode'].'</b>','warn');
        prnMsg('You may have entered an invalid formular <b>'.$_POST['Calculation'].'</b>','warn');
    }
    
}

if(isset($_POST['Newaccounts'])){
    CreateNewAccount();
}

if(isset($_POST['submitaccounts'])){
    UpdateAccount();
}

if(isset($_GET['AKD'])){
    $account_no = $_GET['AKD'];
}elseif($_POST['ACCNO']){
    $account_no = $_POST['ACCNO'];
}

echo '<table style="width:80%">';
if(isset($account_no)){
    echo '<tr><td>';
         Showaccount($account_no);
    echo '</td></tr>';
}else{
     echo '<tr><td>';
         Newaccount();
     echo '</td></tr>';
}
echo '</table>';

 $ClassCalc = new Calculator();
    $ClassCalc->Reset();

    $BalanceSheet=array();
    $BalanceSheet[0]="Balance Sheet";
    $BalanceSheet[1]="Profit and Loss";
    $BalanceSheet[2]="Control";
    
    $AccountType=array();
    $AccountType[0]="Posting";
    $AccountType[1]="Heading";
    $AccountType[2]="Total";
    $AccountType[3]="Begin-Total";
    $AccountType[4]="End-Total";
       
       
    $REsults = DB_query('Select 
        min(B.[start_date]),
        max(B.End_date),
        periodno 
        from [FinancialPeriods] B  
        where B.closed=0 
        Group by periodno',$db);
    
       $financialPeriods = DB_fetch_row($REsults);
       $StartDate = is_null($financialPeriods[0])?0: $financialPeriods[0];
       $Enddate   = is_null($financialPeriods[1])?0: $financialPeriods[1];
       $YearPeriod= is_null($financialPeriods[2])?0: $financialPeriods[2];
       
       
    echo '<table class="table-striped table-bordered"><tr><td>PERIOD REPORTING <div> From :<b>'.
            ConvertSQLDate($StartDate).'</b> To <b>'.ConvertSQLDate($Enddate). '</b></div></td></tr>'
      . '<tr><td><input type="text" name="filter"  class="myInput" id="myAccountInput" onkeyup="mysetAccountFunction()"/></td></tr>'
      . '</table>';
            
    echo '<DIV id="container" >'
            . '<table class="table-striped table-bordered" id="myAccountTable">'
            . '<thead><tr>'
            . '<th>ACCOUNT NO</th>'
            . '<th>ACCOUNT NAME</th>'
            . '<th>BALANCE/INCOME</th>'
            . '<th>Formula</th>'
            . '<th>TYPE</th>'
            . '</thead></tr>';
   
    $k =1; 
    $URL = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

    
$REsults = DB_query( DB_AccountBalance(),$db);
while($rows=DB_fetch_array($REsults)){
 
    echo '<tr><td><a href="ChartofAccounts.php?AKD='.trim($rows['accno']).'"> Code:'.$rows['ReportCode'].' </a></td>';
    if($rows['ReportStyle']==0){
      echo '<td><a href="LedgerReports.php?Drill='.trim($rows['accno']).'">'.$rows['accdesc'].'</a></td>';
    }else{
      echo '<td>'.$rows['accdesc'].'</a></td>';
    }
    echo '<td>'.$BalanceSheet[$rows['balance_income']].'</td>';
    echo '<td>'.$rows['Calculation'].'</td>';
    echo '<td>'.$AccountType[$rows['ReportStyle']].'</td>';
    echo '</tr>' ;

}

echo '</table></DIV>';
include('includes/footer.inc');

?>