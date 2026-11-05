<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

$Title = _('Create Journal');
include('includes/header.inc');

  echo '<div class="centre">';
if(isset($_GET['new'])){
        $JouClass = new journalentries();
        $JouClass->NewJournal();
        $ResultIndex = DB_query('Select sysdate() as date',$db);
        $rowdate = DB_fetch_row($ResultIndex);
         if(!isset($_POST['date'])){
           $_POST['date'] = ConvertSQLDate($rowdate[0]);
        }
        $_POST['JournalNo'] = GetTempNextNo(3); 
        $_POST['currency'] = $_SESSION['CompanyRecord']['currencydefault'];
        $_SESSION['JournalID']=date('U');
   
    } else {
         $JouClass = new journalentries();
    }
   
$account    = $_POST['account'.$JouClass->I];
$acctype    = $_POST['acctype'.$JouClass->I];
$itemcode   = $_POST['itemcode'.$JouClass->I];
$amount     = $_POST['amount'.$JouClass->I];
$JouClass->GLentries($account,$acctype,$itemcode,$amount);
    
if(isset($_POST['savejournal'])){
$errors=0;
$ob = new journalentries();
     
if($_POST['account'.$ob->I]==$_POST['itemcode'.$ob->I]){
    prnMsg('The Credit account should not be the same as the Debit account','warn');
    $errors++;
} 
                  
if($_POST['JournalID']!=$_SESSION['JournalID']){
        prnMsg('This journal has already been posted','warn');
        $errors++;
    }
    
if(mb_strlen($_POST['comments'])==0){
     prnMsg('This journal has must have a narration','warn');
     $errors++;
}    
           
if($errors==0){ 

        $SQL= $ob->SaveJournal();
        DB_Txn_Begin($db);
        foreach ($SQL as $sql) {
           DB_query($sql,$db);
        }
        
        if(DB_error_no($db)>0){
            DB_Txn_Rollback($db);
        } else {
            DB_Txn_Commit($db);
            
            $JouClass = new journalentries();
            $JouClass->NewJournal();

            $_POST['currency']  = $_SESSION['CompanyRecord']['currencydefault'];
            
            prnMsg('The Journal has been saved');
             $_SESSION['JournalID']=date('U');
        }
    }              

}
 
if(isset($_POST['cancel'])){
    $JouClass->NewJournal();
    $JouClass = new journalentries();
}

if(isset($_POST['addline'])){
         $JouClass = new journalentries();
         
         if($_POST['account'.$JouClass->I]==$_POST['itemcode'.$JouClass->I]){
              prnMsg('The Credit account should not be the same as the Debit account','warn');
          } else {
              
                $JouClass->NextJournal();
            
          }
        
     }
   
echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/customer.png" title="' . _('Create Journal') .'" alt="" />' . ' ' . _('Create Journal') . '</p>';
echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?new=1">To Create A new Journal Voucher click here</a>';
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"  id="Journal">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';
echo '<input type="hidden" name="JournalID" value="'. $_SESSION['JournalID'] .'"/>';
echo '<div class="card"><table class="table-bordered">';
echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr><tr>';
echo '<td>Journal No</td><td><input tabindex="2" type="text" name="JournalNo" value="'.$_POST['JournalNo'].'" size="11" maxlength="10" readonly="readonly"/></td>';

$sql = "SELECT currabrev,country,hundredsname,rate,decimalplaces FROM currencies";
$result = DB_query($sql,$db);
echo '<td>Currency</td><td><select tabindex="3" name="currency">';
while ($myrow = DB_fetch_array($result)) {
    echo '<option value="'.$myrow['currabrev'].'" '.((trim($myrow['currabrev'])==$_POST['currency'])?'selected="selected"':'').'  >'.$myrow['currabrev']."</option>";
}

echo '</select></td></tr>';
echo '<tr>'. $_SESSION['SelectObject']['dimensionone'];
echo  $_SESSION['SelectObject']['dimensiontwo'].'</tr>';

echo '<tr><td>Narration</td><td><input type="text" name="comments" size="50" value="'.$_POST['comments'].'" maxlenth="100" /></td></tr>';
  
echo '<tr><td colspan="5">'
        . '<table class="table table-bordered">'
        . '<tr>'
        . '<th>No</th>'
        . '<th>DR:Account</th>'
        . '<th>Account Description</th>'
        . '<th>CR:Account Type</th>'
        . '<th>Name</th>'
        . '<th>Amount</th>'
        . '</tr>';

$JouClass->ShowTable();

echo '</table></td></tr><tr><td colspan="5">Enter -ve to Reverse the journal Effect';
echo '<div><input type="submit" name="update" value="Update"/>  '
. '  <input type="submit" name="addline" value="Add Line"  onclick="return confirm(\''._('Do you want to add a new line ?').'\');" />'
. '  <input type="submit" name="cancel" value="Cancel Entries"  onclick="return confirm(\''._('Do you want to cancel these entries ?').'\');" />'
. '  <input type="submit" name="savejournal" value="Save Journal"  onclick="return confirm(\''._('Do you want to save this journal ?').'\');" />'
. '  </div></td></tr></table></div></form>';
 
include('includes/footer.inc');

class journalentries {
    var $I; 
    var $JOURNAL;
    var $JournalArray=array();
    
    function __construct() {
          $this->I = $_SESSION['JournalEntryIndexNo'];
    }
    
    function getrates($fromcurrency,$tocurrency){
        
        $FromRate =$this->ExtractRate($fromcurrency);
        $Torate =$this->ExtractRate($tocurrency);
        
        return ($Torate/$FromRate);
    }
    
    function ExtractRate($currbrieve){
        global $db;
        
        $SQLOldRate = "SELECT rate FROM currencies WHERE currabrev = '" .$currbrieve. "'";
        $ResultOldRate = DB_query($SQLOldRate, $db);
        $myrow = DB_fetch_row($ResultOldRate);
        $OldRate = $myrow[0];
                
        return $OldRate;
    }
       
    function GetBankDetails($accountCode){
         Global $db;
         
        $resultindex=DB_query("SELECT `BankAccounts`.`currency`,`currencies`.`rate`
        FROM `BankAccounts` join `currencies` on `BankAccounts`.`currency`=`currencies`.`currabrev` where `accountcode`='".$accountCode."'", $db);
        
        $row=DB_fetch_row($resultindex);
        $BankArray['currency']=$row[0];
        $BankArray['rate']=$row[1];
        
        return $BankArray;
    }
       
    function GetCustomerCurrency($itemcode){
        global $db;
        
        $ResultIndex = DB_query("select `curr_cod` FROM `debtors` where itemcode='".$itemcode."'", $db);
        $rows = DB_fetch_row($ResultIndex);
        $postingGroups['curr_cod'] = $rows[0];
        
        return $postingGroups;
    }
       
    function GetSupplierCurrency($itemcode){
        global $db;
        
        $ResultIndex = DB_query("select `curr_cod` FROM `creditors` where itemcode='".$itemcode."'", $db);
        $rows = DB_fetch_row($ResultIndex);
        $postingGroups['curr_cod'] = $rows[0];
        
        return $postingGroups;
    }
       
    function NextJournal() {
        $_SESSION['JournalEntryIndexNo']++;
        $this->I = $_SESSION['JournalEntryIndexNo'];
 
        $_SESSION['JournalEntryDetails'][$_SESSION['JournalEntryIndexNo']]= 
                    array('account'=>"",
                         'accountdescription'=>"",
                         'acctype'=>"",
                         'itemcode'=>"",
                         'amount'=>0.00);
        
        $this->JOURNAL=$_SESSION['JournalEntryDetails'];
              
        return $this->JOURNAL ;
    }
    
    Function NewJournal(){
        $_SESSION['JournalEntryDetails']=array();
        $_SESSION['JournalEntryIndexNo']= 0;
        $this->I = $_SESSION['JournalEntryIndexNo'];
          
        $_SESSION['JournalEntryDetails'][$this->I] =
                    array('account'=>"",
                          'accountdescription'=>"",
                          'acctype'=>"",
                          'itemcode'=>"",
                          'amount'=>0.00);
        
        $this->JOURNAL=$_SESSION['JournalEntryDetails'];
        
        return $this->JOURNAL;
    }
    
    Function GetGL($account,$db){
        $array= array();
        
        $ResultIndex = DB_query("SELECT `accno`,`accdesc` FROM `acct` where `accno`='".$account."' ", $db);
        $row = DB_fetch_row($ResultIndex);
        
        $array['accno']=$row[0];
        $array['accdesc']=$row[1];
        
        
        return $array;
    }
        
    function PersonalAccounts($itemcode,$type,$db){
        $array=array();
        
        if($type=='debtors'){
               $sql="SELECT `itemcode`,`customer` FROM `debtors` where `itemcode`='".$itemcode."'";
               $ResultIndex = DB_query($sql,$db);
               $row = DB_fetch_row($ResultIndex);
               $array['accno']=$row[0];
               $array['accdesc']=$row[1];
        } 
        
        if($type=='creditors'){
               $sql="SELECT `itemcode`,`customer` FROM `creditors` where (IsEmployee is null) and `itemcode`='".$itemcode."'";
               $ResultIndex = DB_query($sql,$db);
               $row = DB_fetch_row($ResultIndex);
               $array['accno']=$row[0];
               $array['accdesc']=$row[1];
        }
        
        if($type=='employee'){
            $sql="SELECT `itemcode`,`customer` FROM `creditors` where IsEmployee=1 and `itemcode`='".$itemcode."'";
            $ResultIndex = DB_query($sql,$db);
               $row = DB_fetch_row($ResultIndex);
               $array['accno']=$row[0];
               $array['accdesc']=$row[1];
    
        }
        
        if($type=='bank'){
               $sql="SELECT `accountcode`,`bankName` FROM `BankAccounts` where `accountcode`='".$itemcode."'";
               $ResultIndex = DB_query($sql,$db);
               $row = DB_fetch_row($ResultIndex);
               $array['accno']=$row[0];
               $array['accdesc']=$row[1];
        }
        
        if($type=='GL'){
               $sql="SELECT `accno`,`accdesc` FROM `acct` where `accno`='".$itemcode."' ";
               $ResultIndex = DB_query($sql,$db);
               $row = DB_fetch_row($ResultIndex);
               $array['accno']=$row[0];
               $array['accdesc']=$row[1];
        }
       return $array;
    }
        
    Function GLentries($account,$acctype,$itemcode,$amount){
        Global $db;
        
        $gl = $this->GetGL($account,$db);
        $pacct = $this->PersonalAccounts($itemcode,$acctype,$db);
        
         $_SESSION['JournalEntryDetails'][$this->I] = 
            array('account'=>$gl['accno'],
                  'accountdescription'=>$gl['accdesc'],
                  'acctype'=>$acctype,
                  'itemcode'=>$pacct['accno'],
                  'amount'=>$amount);
        
         $this->set();
         
         $this->JOURNAL = $_SESSION['JournalEntryDetails'];
    }
       
    Function cmbGL($objname,$value){
        Global $db;
        
        $BalanceSheet=array();
        $BalanceSheet[0]=":BS";
        $BalanceSheet[1]=":P&L";
        $BalanceSheet[2]=":Suspense";
    
        $array='<select name="'.$objname.'" onchange="ReloadForm(Journal.update)"><option></option>';
               
        $ResultIndex = DB_query("Select accno,TRIM(`accdesc`) as accdesc,ReportCode,ReportStyle,`balance_income` FROM `acct` "
                . "where `ReportStyle`=0 and `direct`=1 and `inactive`=0 order by accdesc,ReportCode asc", $db);
        while($row = DB_fetch_array($ResultIndex)){
            $array .='<option value="'.$row['accno'].'" '.(trim($row['accno'])==trim($value)?'selected="selected"':'').'>'.$row['accdesc'].' ('.$BalanceSheet[$row['balance_income']].')</option>';
        }
        
        $array .='</select>';
    
        return $array;
    }
        
    function cmbPersonal($type,$objname,$value){
        Global $db;
        
        $BalanceSheet=array();
        $BalanceSheet[0]="Balance Sheet";
        $BalanceSheet[1]="Profit and Loss";
    
        $array ='<select name="'.$objname.'"  onchange="ReloadForm(Journal.update)">';
        
        if($type=='debtors'){
            $ResultIndex = DB_query("SELECT `itemcode`,`customer` FROM `debtors` order by customer", $db);
            while($row = DB_fetch_array($ResultIndex)){
             $selected=(trim($row['itemcode']) == trim($value))?'selected="selected"':'';       
               
               $array .='<option value="'.$row['itemcode'].'" '.$selected.'>'.$row['customer'].'</option>';
            }
        
        }
            if($type=='creditors'){
            $ResultIndex = DB_query("SELECT `itemcode`,`customer` FROM `creditors`  where IsEmployee is null order by customer", $db);
             while($row = DB_fetch_array($ResultIndex)){
                $selected=(trim($row['itemcode']) == trim($value))?'selected="selected"':'';
                $array .='<option value="'.$row['itemcode'].'" '.$selected.'>'.$row['customer'].'</option>';
            }
        } 
        
        if($type=='employee'){
            $ResultIndex = DB_query("SELECT `itemcode`,`customer` FROM `creditors` where IsEmployee=1  order by customer asc", $db);
             while($row = DB_fetch_array($ResultIndex)){
                $selected=(trim($row['itemcode']) == trim($value))?'selected="selected"':'';
                $array .='<option value="'.$row['itemcode'].'" '.$selected.'>'.$row['customer'].'</option>';
            }
          } 
         
          if($type=='bank'){
                $ResultIndex = DB_query("SELECT `accountcode`,`bankName` FROM `BankAccounts`", $db);
                 while($row = DB_fetch_array($ResultIndex)){
                    $selected=(trim($row['accountcode']) == trim($value))?'selected="selected"':'';
                   $array .='<option value="'.$row['accountcode'].'" '.$selected.'>'.$row['bankName'].'</option>';
                }
           }
        
        
        if($type=='GL'){
                $ResultIndex = DB_query("Select accno,accdesc,ReportCode,ReportStyle,`balance_income` FROM `acct` where `ReportStyle`=0 and `direct`=1 and `inactive`=0 order by ReportCode,accdesc asc", $db);
                while($row = DB_fetch_array($ResultIndex)){
                    $selected= (trim($row['accno'])==trim($value)?'selected="selected"':'');
                    $array .='<option value="'.$row['accno'].'" '.$selected.'>'.$row['accdesc'].' "'.$BalanceSheet[$row['balance_income']].'"</option>';
                }
        }
        
          $array .='</select>';
                     
        
        return $array;
    }
       
    function types($objname,$value){
        
        $array = '<select name="'.$objname.'" onchange="ReloadForm(Journal.update)"><option></option>'
               . '<option value="debtors" '.($value=='debtors'?'selected="selected"':'').'>Accounts Receivable</option>'
               . '<option value="creditors" '.($value=='creditors'?'selected="selected"':'').'>Accounts Payable</option>'
               . '<option value="employee" '.($value=='employee'?'selected="selected"':'').'>Employee</option>'
               . '<option value="bank" '.($value=='bank'?'selected="selected"':'').'>Bank Account</option>'
               . '<option value="GL" '.($value=='GL'?'selected="selected"':'').'>General Ledger</option>'
               . '</select>';
     
       return $array;
    }
    
    function set(){
        $this->cmbGL('account'.$this->I,$_POST['account'.$this->I]);
        $this->types('acctype'.$this->I,$_POST['acctype'.$this->I]);
        $this->cmbPersonal($_POST['acctype'.$this->I],'itemcode'.$this->I,$_POST['itemcode'.$this->I]);
  
    }
       
    function ShowTable(){
        if(isset($this->JOURNAL)){
                foreach ($this->JOURNAL as $key => $value) {
                    $NewKey=$key+1;
                    echo sprintf('<tr>'
                        . '<td>%s</td>'
                        . '<td>%s</td>'
                        . '<td>%s</td>'
                        . '<td>%s</td>'
                        . '<td>%s</td>'
                        . '<td><input type="text" class="number" name="amount'.$key.'" value="%s" size="10" maxlength="11"/></td></tr>',
                        $NewKey,
                        $this->cmbGL('account'.$key,$value['account']),
                        $value['accountdescription'],
                        $this->types('acctype'.$key,$value['acctype']),
                        $this->cmbPersonal($value['acctype'],'itemcode'.$key,$value['itemcode']),
                        $value['amount']);

       }
        }
    }
       
    Function PostingGroup($flag,$acount){
        global $db;
        
        switch ($flag) {
            case 1:
                // debtors
                 $SQL="SELECT
                    `postinggroups`.`debtorsaccount`
                    FROM `debtors` 
                    join `postinggroups` on `debtors`.`customerposting`=`postinggroups`.`code`
                    where `itemcode`='".$acount."'";

                break;
            case 2: 
                // creditors
               $SQL="SELECT
                    `arpostinggroups`.creditorsaccount
                    FROM `creditors` 
                    join `arpostinggroups` on `creditors`.`supplierposting`=`arpostinggroups`.`code`
                    where `itemcode`='".$acount."'";

                break;
            case 3: 
                // bank
                $SQL="SELECT `PostingGroup` FROM `BankAccounts` where accountcode='".$acount."'";

                break;
            case 4: 
                // bank
                $SQL="SELECT `accno` FROM `acct` where `accno`='".$acount."' ";
               break;
            default:
                break;
        } 
        
        $ResultIndex=DB_query($SQL,$db);
        $pgrow=DB_fetch_row($ResultIndex);
        
        return $pgrow[0];
        
    }
    
    
    Function SaveJournal(){
        Global $db;
        
         if(($_SESSION['ManualNumber']==0)){
         $_POST['JournalNo'] = GetNextTransNo(3,$db);
         }
         $periodno = GetPeriod($_POST['date'],$db,true);
         $JOURNAL  = GetNextTransNo(0,$db);
         $Conrate = $this->ExtractRate($_POST['currency']);
       
       $this->JOURNAL=$_SESSION['JournalEntryDetails'];
       foreach ($this->JOURNAL as $key => $row)  {
           
          $this->JournalArray[]=  sprintf("INSERT INTO `JournalEntries`
          (`Docdate`,`JournalNo`,`Currency`,`Dimension_1`,`Dimension_2`,`Account`,`Balaccount`,`transtype`,`itemcode`,`narration`,`amount`)
           VALUES ('%s','%s','%s','%s','%s','%s' ,'%s','%s','%s','%s',%f)", FormatDateForSQL($_POST['date']),$_POST['JournalNo'],$_POST['currency'],$_POST['DimensionOne'],$_POST['DimensionTwo'],
           $row['account'],$row['account'], $row['acctype'],$row['itemcode'],$_POST['comments'],$row['amount']);
           
          if($row['acctype']=='GL'){
           $this->JournalArray[]= sprintf("INSERT INTO `Generalledger`
           (`journalno`,`Docdate`,`period`,`DocumentNo`,`DocumentType`,`accountcode`,
           `balaccountcode`,`amount`,`currencycode`,`ExchangeRate`,`cutomercode`,`suppliercode` ,
           `bankcode`,`reconcilled`,`narration`,`ExchangeRateDiff`,`VATaccountcode`,`VATamount`,
           `dimension`,`dimension2`) VALUES ('%s','%s','%s','%s','%s','%s','%s',%f,'%s',%f,'%s','%s','%s',
            '%s','%s','%s','%s','%s','%s','%s')", $JOURNAL,FormatDateForSQL($_POST['date']),$periodno,
            $_POST['JournalNo'],'0',$row['account'],$row['itemcode'],abs($row['amount']),
            $_POST['currency'],$Conrate,(($row['acctype']=='debtors')?$row['itemcode']:''),
            (($row['acctype']=='creditors')?$row['itemcode']:''),(($row['acctype']=='bank')?$row['itemcode']:''),0,
            $_POST['comments'],'0','','0',$_POST['DimensionOne'],$_POST['DimensionTwo'] );
          }
          
          
          if($row['acctype']=='bank'){
            $BankD = $this->GetBankDetails($row['itemcode']);
            $Conrate = $this->getrates($BankD['currency'],$_POST['currency']) ;
            $bankpostingaccount=$this->PostingGroup(3,$row['itemcode']);
            
            if($row['amount']>0){
                $debit  = $row['account'] ;
                $credit = $bankpostingaccount ;
            }else{
                $debit  = $bankpostingaccount ;
                $credit = $row['account'];
            }
            
            $this->JournalArray[]= sprintf("INSERT INTO `Generalledger`
           (`journalno`,`Docdate`,`period`,`DocumentNo`,`DocumentType`,`accountcode`,
           `balaccountcode`,`amount`,`currencycode`,`ExchangeRate`,`cutomercode`,`suppliercode` ,
           `bankcode`,`reconcilled`,`narration`,`ExchangeRateDiff`,`VATaccountcode`,`VATamount`,
           `dimension`,`dimension2`) VALUES ('%s','%s','%s','%s','%s','%s','%s',%f,'%s',%f,'%s','%s','%s',
            '%s','%s','%s','%s','%s','%s','%s')", $JOURNAL,FormatDateForSQL($_POST['date']),$periodno,
            $_POST['JournalNo'],'0',$debit,$credit,abs($row['amount']),$_POST['currency'],$Conrate,(($row['acctype']=='debtors')?$row['itemcode']:''),
            (($row['acctype']=='creditors')?$row['itemcode']:''),(($row['acctype']=='bank')?$row['itemcode']:''),0,
            $_POST['comments'],'0','','0',$_POST['DimensionOne'],$_POST['DimensionTwo'] );
            
            
            $this->JournalArray[]=  sprintf("INSERT INTO `BankTransactions`
           (`bankcode`,`DocDate`,`doctype`,`DocumentNo`,`TransType`,`journal`,
           `amount`,`narrative` ,`exchangerate`,`itemcode`) values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s') ",
            $row['itemcode'],FormatDateForSQL($_POST['date']),'0',$_POST['JournalNo'],$row['acctype'],$JOURNAL,
            ($row['amount'] * $Conrate)* -1,$_POST['comments'] ,$Conrate ,$row['account']);
         }
        
          if($row['acctype']=='debtors'){
                 $Cust = $this->GetCustomerCurrency($row['itemcode']);
                 $Conrate = $this->getrates($Cust['curr_cod'],$_POST['currency']) ;
                 $bankpostingaccount=$this->PostingGroup(1,$row['itemcode']);
            
                 if($row['amount']>0){
                       $debit  = $row['account'] ;
                       $credit = $bankpostingaccount ;
                 }else{
                     $debit  = $bankpostingaccount ;
                     $credit = $row['account'];
                 }
            
                $this->JournalArray[]= sprintf("INSERT INTO `Generalledger`
               (`journalno`,`Docdate`,`period`,`DocumentNo`,`DocumentType`,`accountcode`,`balaccountcode`,`amount`,`currencycode`,`ExchangeRate`,`cutomercode`,`suppliercode` ,
               `bankcode`,`reconcilled`,`narration`,`ExchangeRateDiff`,`VATaccountcode`,`VATamount`,`dimension`,`dimension2`) VALUES ('%s','%s','%s','%s','%s','%s','%s',%f,'%s',%f,'%s','%s','%s',
                '%s','%s','%s','%s','%s','%s','%s')", $JOURNAL,FormatDateForSQL($_POST['date']),$periodno,
                $_POST['JournalNo'],'0',$debit,$credit,abs($row['amount']),$_POST['currency'],$Conrate,(($row['acctype']=='debtors')?$row['itemcode']:''),
                (($row['acctype']=='creditors')?$row['itemcode']:''),(($row['acctype']=='bank')?$row['itemcode']:''),0,
                $_POST['comments'],'0','','0',$_POST['DimensionOne'],$_POST['DimensionTwo'] );

                 
                 $this->JournalArray[]=  sprintf("INSERT INTO `CustomerStatement`
                (`Date`,`Documentno` ,`Documenttype` ,`Accountno`,`Grossamount` ,`JournalNo`,`Dimension_One`,`Dimension_Two`,`Currency`) VALUES ('%s','%s','%s' ,'%s' ,%f ,'%s' ,'%s' ,'%s' ,'%s')",
                FormatDateForSQL($_POST['date']),$_POST['JournalNo'],'0',$row['itemcode'],($row['amount'] * $Conrate)* -1,
                $JOURNAL,$_POST['DimensionOne'],$_POST['DimensionTwo'],$_POST['currency']);
                 
                 
                $this->JournalArray[]=sprintf("INSERT INTO `debtorsledger`
                (`date`,`details`,`flag`,`invref` ,`acctfolio`,`amount`,`type`,`curr_cod`,`curr_rat`,`i_n_t`,`period`,`journal`,`typ`,`systypes_1`,`ledger`)
                 VALUES ('%s','%s','%s','%s','%s',%f,'%s','%s',%f,'%s','%s','%s','%s','%s','%s')",FormatDateForSQL($_POST['date']),
                 $_POST['comments'],'DR',$_POST['JournalNo'],$row['itemcode'],($row['amount'] * $Conrate)* -1,'J',$_POST['currency'],
                 $Conrate,'J',$periodno,$JOURNAL,'J','0', $row['account'] );
              
          }
          
          if($row['acctype']=='creditors' || $row['acctype']=='employee'){
                 $Cust = $this->GetSupplierCurrency($row['itemcode']);
                 $Conrate = $this->getrates($Cust['curr_cod'],$_POST['currency']) ;
                 $bankpostingaccount=$this->PostingGroup(2,$row['itemcode']);
            
                if($row['amount']>0){
                   $debit  = $row['account'] ;
                   $credit = $bankpostingaccount ;
                 }else{
                    $debit  = $bankpostingaccount ;
                    $credit = $row['account'];
                }
            
                $this->JournalArray[]= sprintf("INSERT INTO `Generalledger`
               (`journalno`,`Docdate`,`period`,`DocumentNo`,`DocumentType`,`accountcode`,`balaccountcode`,`amount`,`currencycode`,`ExchangeRate`,`cutomercode`,`suppliercode` ,`bankcode`,`reconcilled`,`narration`,`ExchangeRateDiff`,`VATaccountcode`,`VATamount`,
               `dimension`,`dimension2`) VALUES ('%s','%s','%s','%s','%s','%s','%s',%f,'%s',%f,'%s','%s','%s',
                '%s','%s','%s','%s','%s','%s','%s')", $JOURNAL,FormatDateForSQL($_POST['date']),$periodno, $_POST['JournalNo'],'0',$debit,$credit,abs($row['amount']),$_POST['currency'],$Conrate,(($row['acctype']=='debtors')?$row['itemcode']:''),
                (($row['acctype']=='creditors')?$row['itemcode']:''),(($row['acctype']=='bank')?$row['itemcode']:''),0,
                $_POST['comments'],'0','','0',$_POST['DimensionOne'],$_POST['DimensionTwo'] );

                 
                 
      
               $this->JournalArray[] = sprintf("INSERT INTO `SupplierStatement`
                (`Date`,`Documentno` ,`Documenttype` ,`Accountno`,`Grossamount` ,`JournalNo`,`Dimension_One`,`Dimension_Two`,`Currency`) VALUES
                ('%s','%s','%s' ,'%s' ,%f ,'%s' ,'%s' ,'%s' ,'%s')", FormatDateForSQL($_POST['date']),$_POST['JournalNo'],'0',$row['itemcode'],($row['amount'] * $Conrate)* -1,
                $JOURNAL,$_POST['DimensionOne'],$_POST['DimensionTwo'],$_POST['currency']);
                 
                 
                $this->JournalArray[] = sprintf("INSERT INTO `creditorsledger`
                (`date`,`details`,`flag`,`invref` ,`acctfolio`,`amount`,`type`,`curr_cod`,`curr_rat`,`i_n_t`,`period`,`journal`,`typ`,`systypes_1`,`ledger`)
                 VALUES ('%s','%s','%s','%s','%s',%f,'%s','%s',%f,'%s','%s','%s','%s','%s','%s')",FormatDateForSQL($_POST['date']),
                 $_POST['comments'],'DR',$_POST['JournalNo'],$row['itemcode'],($row['amount'] * $Conrate)* -1,'J',$_POST['currency'],
                 $Conrate,'J',$periodno,$JOURNAL,'J','0', $row['account'] );
          }
 
       }
        
        return  $this->JournalArray;
    }
    
         
}

?>
