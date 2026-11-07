<?php

Function subquery($YearPeriod,$codefrom,$codeto){
     
    if(empty(trim($codefrom))){
        $codefrom='0';
    }
    if(empty(trim($codeto))){
        $codeto='1';
    }
    
    $procodedure = "
	SELECT 
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
        case when acct.[balance_income]=0 then 
           (select sum(generalledger.amount * generalledger.[ExchangeRate]) 
            from generalledger where accountcode=acct.accno 
            and Docdate <=(select max([end_date]) from financialperiods  where periodno=".$YearPeriod."))
        else
           (select sum(generalledger.amount * generalledger.[ExchangeRate]) 
           from generalledger where accountcode=acct.accno and Docdate between (select min([start_date]) 
           from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date]) 
           from financialperiods  where periodno=".$YearPeriod."))

           end as debit,
            case when acct.[balance_income]=0 then 
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) 
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date]) 
                from financialperiods  where periodno=".$YearPeriod."))
            else 
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger 
                where balaccountcode=acct.accno and Docdate between (select min([start_date]) 
                from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date]) 
                from financialperiods  where periodno=".$YearPeriod."))
            
         end as credit,
         
            case when acct.[balance_income]=0 then 
                 (select sum(generalledger.amount * generalledger.[ExchangeRate]) 
                  from generalledger where accountcode=acct.accno 
                  and Docdate <=(select max([end_date])  from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                 (select  sum(generalledger.amount * generalledger.[ExchangeRate]) 
                  from generalledger where accountcode=acct.accno and Docdate between (select min([start_date]) 
                  from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date]) 
                  from financialperiods  where periodno=".($YearPeriod-12)."))
          
           end as debit_last,
            case when acct.[balance_income]=0 then 
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) 
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date]) 
                from financialperiods  where periodno=".($YearPeriod-12)."))
            else 
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger 
                where balaccountcode=acct.accno and Docdate between (select min([start_date]) 
                from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date]) 
                from financialperiods  where periodno=".($YearPeriod-12)."))
            
         end as credit_last,
        [Calculation]
	from acct   
	where [ReportCode] between ".$codefrom." and ".$codeto."
	group by 
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]  
	ORDER BY 
	[ReportCode]";
    
    return $procodedure;
}

class FundsFlowForYear {
    var $Calc = array();
    var $Calc_LastYear = array();
    Var $Amount = 0;
    Var $Amount2 = 0;
    Var $Amount2_lastyear = 0;
    Var $Amount_lastyear = 0;
    var $begintotal = 0;
    var $begintotal_lastyear = 0;
    var $starttotaling = true;
    Var $ResultDataSet = array();
    var $DebitSide = 0;
    var $CreditSide = 0;
    var $DebitSide_Last = 0;
    var $CreditSide_Last = 0;
    Var $Profit = 0;
    Var $Profit_last = 0;
    var $Resource;
    
    Var $NETAssets = 0;
    Var $NETinventory = 0;
    Var $NETdebtors = 0;
    Var $NETcreditors = 0;
    Var $NETloans = 0;
    Var $NETcapital = 0;
    Var $NONcashprofit = 0;
    var $Cashcfwd = 0;
    var $CashClosing = 0;
    
    Function __construct() {
        $_SESSION['BalanceSheed']['id']=100000;
    }
    
    Function GetID(){
       return $_SESSION['BalanceSheed']['id']++;
    }
    
    function Get($rows=array()){
        
       if($rows['ReportStyle']==3){
             $_SESSION['chart_table']['starttotaling'] = true;
             $_SESSION['chart_table']['begintotal'] = 0;
       }
             
       if(($rows['ReportStyle'] == 0 || $rows['ReportStyle'] == 5)
           and ($_SESSION['chart_table']['starttotaling'] == true)){
                
               $_SESSION['chart_table']['begintotal']  += ($rows['amount']);
           
       }
              
       if($rows['ReportStyle']==4){
               $_SESSION['chart_table']['starttotaling'] = false;
               $this->Amount2 = $_SESSION['chart_table']['begintotal'];
               $_SESSION['chart_table']['begintotal'] = 0;
        } else {
              
             $this->Amount2 = ($rows['amount']);
           
        }
       
        $this->savetotal($rows['ReportCode'],$this->Amount2);
        
        if((mb_strlen($rows['Calculation'])>0) and ($rows['ReportStyle']==2)){
            $this->Amount2 = $this->GetTotal($rows['Calculation']);
        }
      
        return $this->Amount2;
        
    }
    
    function Get_last($rows=array()){
        
       if($rows['ReportStyle']==3){
             $_SESSION['chart_table_Last']['starttotaling'] = true;
             $_SESSION['chart_table_Last']['begintotal'] = 0;
       }
             
       if(($rows['ReportStyle'] == 0) and ($_SESSION['chart_table_Last']['starttotaling'] == true)){
               $_SESSION['chart_table_Last']['begintotal']  += ($rows['Amount_lastyear']);
       }
              
       if($rows['ReportStyle']==4){
               $_SESSION['chart_table_Last']['starttotaling'] = false;
               $this->Amount2_lastyear = $_SESSION['chart_table_Last']['begintotal'];
               $_SESSION['chart_table_Last']['begintotal'] = 0;
        } else {
             $this->Amount2_lastyear = ($rows['Amount_lastyear']);
        }
       
        $this->savetotal_last($rows['ReportCode'],$this->Amount2);
        
        if((mb_strlen($rows['Calculation'])>0) and ($rows['ReportStyle']==2)){
            $this->Amount2_lastyear = $this->GetTotal_last($rows['Calculation']);
        }
      
        return $this->Amount2_lastyear;
        
    }
    
    Function Reset(){
        Unset($_SESSION['chart_table']);
        $_SESSION['chart_table']['starttotaling'] = false;
        $_SESSION['chart_table']['Amount'] = 0;
        $_SESSION['chart_table']['begintotal'] = 0;
        $_SESSION['chart_table']['Calc'] = array();
        
         Unset($_SESSION['chart_table_Last']);
        $_SESSION['chart_table_Last']['starttotaling'] = false;
        $_SESSION['chart_table_Last']['Amount'] = 0;
        $_SESSION['chart_table_Last']['begintotal'] = 0;
        $_SESSION['chart_table_Last']['Calc'] = array();
        
        $this->ResultDataSet=array();
    }
        
    Function GetTotal_last($formular){
        $this->Amount_lastyear=0;
        $accounts=explode('+',$formular);
        foreach ($accounts as $code) {
           $this->Amount_lastyear = $this->AddTotals_last($code);
        }
        return $this->Amount_lastyear;
    }
    
    function AddTotals($account){
         $_SESSION['chart_table']['Amount'] += $_SESSION['chart_table']['Calc'][$account];
         return $_SESSION['chart_table']['Amount'];
    }
        
    Function savetotal_last($reportcode,$amount=0){
        $_SESSION['chart_table_Last']['Calc'][$reportcode] = $amount ;
    }
        
    Function GetTotal($formular){
        $this->Amount=0;
        $accounts=explode('+',$formular);
        foreach ($accounts as $code) {
           $this->Amount = $this->AddTotals($code);
        }
        return $this->Amount;
    }
           
    function AddTotals_last($account){
         $_SESSION['chart_table_Last']['Amount'] += $_SESSION['chart_table_Last']['Calc'][$account];
         return $_SESSION['chart_table_Last']['Amount'];
    }
           
    Function savetotal($reportcode,$amount=0){
        $_SESSION['chart_table']['Calc'][$reportcode] = $amount ;
    }
           
    Function CalYearlydata(){
        $this->Reset();
        $this->Profit = 0;
        $this->Profit_last = 0;
       
        $this->DebitSide = 0;
        $this->CreditSide = 0;
        
        $this->DebitSide_Last = 0;
        $this->CreditSide_Last = 0;
        $this->begintotal=0;
        
        $this->ResultDataSet['AccFund']['Description'] =  _('Profit(loss) For the year');
        $this->ResultDataSet['AccFund']['amount'] = 0;  
                   
         $this->DB_FF_FixedAssets();
         $this->DB_FF_Inventory();
         $this->DB_FF_Debtors();
         $this->DB_FF_Bank();
         $this->DB_FF_CurrentLiability();
         $this->DB_FF_LongLiability();
         $this->DB_FF_Capital();
         $this->DB_FF_BankAnalysis();
         
         
         return $this->ResultDataSet;
    }
    
Function DB_FF_FixedAssets(){
        global $db,$YearPeriod;
        $YearPeriod = $_POST['Financial_Periods'];    

       $procodedure = "select fixedassetsFrom ,fixedassetsTo from accountsetup";
       $ResultIndex = DB_query($procodedure,$db);
       $rows = DB_fetch_row($ResultIndex);
       $codefrom=$rows[0];
       $codeto=$rows[1];
       
       $ResultsX = DB_query(subquery($YearPeriod,$codefrom,$codeto), $db);
       while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT_1 = ($Row['debit'] - $Row['credit']);
           $this->DebitSide += $AMOUNT_1;
           $AMOUNT = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->NETAssets += $AMOUNT_1 - $AMOUNT;
           $this->DebitSide_Last += $AMOUNT;
       }

        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['Description'] = _('Net Change in Fixed Assets(increase)');
        $this->ResultDataSet[$AccountCode]['amount'] =  $this->NETAssets * -1;
        $this->begintotal += $this->NETAssets * -1;

}

    
Function DB_FF_Inventory(){
        
 

global $db,$YearPeriod;
$YearPeriod = $_POST['Financial_Periods'];    

       $procodedure = "select inventoryFrom ,inventoryTo from accountsetup";
       $ResultIndex = DB_query($procodedure,$db);
       $rows = DB_fetch_row($ResultIndex);
       $codefrom=$rows[0];
       $codeto=$rows[1];
       
       $ResultsX = DB_query(subquery($YearPeriod,$codefrom,$codeto), $db);
       while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->DebitSide += $AMOUNT;
           $AMOUNT_0 = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->NETinventory += $AMOUNT - $AMOUNT_0;
           $this->DebitSide_Last += $AMOUNT_0;
           }

        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['Description'] = _('Net Change in Inventory(Increase)');
        $this->ResultDataSet[$AccountCode]['amount'] =  $this->NETinventory *-1 ;
        $this->begintotal += $this->NETinventory * -1;

}
 
    
Function DB_FF_Debtors(){
        
global $db,$YearPeriod;
$YearPeriod = $_POST['Financial_Periods'];    

$procodedure = "select DebtorsFrom,DebtorsTo from accountsetup";
       $ResultIndex = DB_query($procodedure,$db);
       $rows = DB_fetch_row($ResultIndex);
       $codefrom=$rows[0];
       $codeto=$rows[1];
       
       $ResultsX = DB_query(subquery($YearPeriod,$codefrom,$codeto), $db);
       while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->DebitSide += $AMOUNT;
           $AMOUNT_0 = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->NETdebtors += $AMOUNT - $AMOUNT_0;
           $this->DebitSide_Last += $AMOUNT_0;
      }

        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['Description'] = _('Net Change in Debtors (Increase)');
        $this->ResultDataSet[$AccountCode]['amount'] =  $this->NETdebtors * -1 ;
        $this->begintotal += $this->NETdebtors  * -1;
}
  
    
Function DB_FF_Bank(){
         global $db,$YearPeriod;
$YearPeriod = $_POST['Financial_Periods'];    
$procodedure = "select BankFrom,BankTo from accountsetup";
$ResultIndex = DB_query($procodedure,$db);
$rows = DB_fetch_row($ResultIndex);
$codefrom=$rows[0];
$codeto=$rows[1];

$ResultsX = DB_query(subquery($YearPeriod,$codefrom,$codeto), $db);
       while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->CashClosing += $AMOUNT;
           $this->DebitSide += $AMOUNT;
           $AMOUNT_0 = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->Cashcfwd += $AMOUNT_0;
           $this->DebitSide_Last += $AMOUNT_0;
           
      }
      

}

    
Function DB_FF_CurrentLiability(){
        
global $db,$YearPeriod;
$YearPeriod = $_POST['Financial_Periods'];    
$procodedure = "select CurLiabFrom,CurLiabTo from accountsetup";
$ResultIndex = DB_query($procodedure,$db);
$rows = DB_fetch_row($ResultIndex);
$codefrom=$rows[0];
$codeto=$rows[1];

$ResultsX = DB_query(subquery($YearPeriod,$codefrom,$codeto), $db);
       while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->CreditSide += $AMOUNT;
           $AMOUNT_0 = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->NETcreditors += $AMOUNT - $AMOUNT_0;
           $this->CreditSide_Last += $AMOUNT_0;
       }
       
        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['Description'] = _('Net Change in creditors(Decrease)');
        $this->ResultDataSet[$AccountCode]['amount'] =  $this->NETcreditors * -1;
        $this->begintotal += $this->NETcreditors  * -1;
}
  
    
Function DB_FF_LongLiability(){
        
global $db,$YearPeriod;
$YearPeriod = $_POST['Financial_Periods'];    
$procodedure = "select LongLiabFrom,LongLiabTo from accountsetup";
$ResultIndex = DB_query($procodedure,$db);
$rows = DB_fetch_row($ResultIndex);
$codefrom=$rows[0];
$codeto=$rows[1];

$ResultsX = DB_query(subquery($YearPeriod,$codefrom,$codeto), $db);
       while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->CreditSide += $AMOUNT;
           $AMOUNT_0 = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->NETloans += $AMOUNT - $AMOUNT_0;
           $this->CreditSide_Last += $AMOUNT_0;
       }
       
        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['Description'] = _('Net Change in Loans (Decrease)');
        $this->ResultDataSet[$AccountCode]['amount'] =  $this->NETloans * -1;
        $this->begintotal += $this->NETloans  * -1;

}
  
    
Function DB_FF_Capital(){
    
global $db,$YearPeriod;
    
$procodedure = "select CapitalFrom,CapitalTo from accountsetup";
$ResultIndex = DB_query($procodedure,$db);
$rows = DB_fetch_row($ResultIndex);
$codefrom=$rows[0];
$codeto=$rows[1];

$ResultsX = DB_query(subquery($YearPeriod,$codefrom,$codeto), $db);
       while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->CreditSide += $AMOUNT;
           $AMOUNT_0 = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->NETcapital += $AMOUNT - $AMOUNT_0;
           $this->CreditSide_Last += $AMOUNT_0;
       }
           $this->begintotal +=  $this->NETcapital  * -1;
       
        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['Description'] = _('Net Change in Capital (Decrease)');
        $this->ResultDataSet[$AccountCode]['amount'] =  $this->NETcapital* -1 ;
        
           
           $this->Profit = $this->DebitSide + $this->CreditSide  ; 
           $this->Profit_last = $this->DebitSide_Last + $this->CreditSide_Last  ; 
        
           $this->ResultDataSet['AccFund']['Description'] = _('Profit (loss) For the year');
           $this->ResultDataSet['AccFund']['amount'] = ($this->Profit)-($this->Profit_last) ;
           $this->begintotal += ($this->Profit)-($this->Profit_last) ;
          
       }
         
    
       
Function DB_FF_BankAnalysis(){
        
global $db,$YearPeriod;
$YearPeriod = $_POST['Financial_Periods'];    
$procodedure = "select BankFrom,BankTo from accountsetup";
$ResultIndex = DB_query($procodedure,$db);
$rows = DB_fetch_row($ResultIndex);
$codefrom=$rows[0];
$codeto=$rows[1];
   
        $this->DebitSide=0;
        $this->DebitSide_Last=0;
        
       $ResultsX = DB_query(subquery($YearPeriod,$codefrom,$codeto), $db);
       while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->DebitSide += $AMOUNT;
           $AMOUNT_0 = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->DebitSide_Last += $AMOUNT_0;
      }
       
         $AccountCode = $this->GetID();
         $this->ResultDataSet[$AccountCode]['Description'] = "-----------------------------------------";  
         $this->ResultDataSet[$AccountCode]['amount']='';
    
        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['Description'] = _('========================================');
         $this->ResultDataSet[$AccountCode]['amount']='';
         
        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['Description'] = _('Net Cash Generated');
        $this->ResultDataSet[$AccountCode]['amount'] = $this->begintotal  ;
        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['Description'] = _('========================================');
        $this->ResultDataSet[$AccountCode]['amount']='';
        
        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['Description'] = _('Bank Start Balance');
        $this->ResultDataSet[$AccountCode]['amount'] = $this->Cashcfwd  ;
       
        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['Description'] = _('Cash Flow');
        $this->ResultDataSet[$AccountCode]['amount'] = $this->begintotal  ;
               
        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['Description'] = _('Bank End Balance');
        $this->ResultDataSet[$AccountCode]['amount'] =  $this->CashClosing ;
 
    }

}
   
Class ShowBankAccounts {
    var $BankObject = '';
    var $BankArray = array();

    function __construct(){
        Global $db;

        $this->BankObject='<tr><td>Select Bank:</td>'
                . '<td><Select name="Bank_Code" required="required">';
        $mpesa=$_SESSION['MpesaBank'];
        $resultindex=DB_query("SELECT [accountcode],
            [bankName] ,[currency],[lastreconcileddate],
            [AccountNo],[BranchCode] ,[BranchName],[lastreconbalance] ,[lastChequeno],[PostingGroup]
                                    FROM [bankaccounts] where accountcode!='".$mpesa."'", $db);
        while($row=DB_fetch_array($resultindex)){
            if(Isset($_POST['Bank_Code'])){
                $this->BankObject .= '<option value="'.$row['accountcode'].'"  '.((trim($_POST['Bank_Code'])==trim($row['accountcode']))?'selected="selected"':'').'>'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }else{
                $this->BankObject .= '<option value="'.$row['accountcode'].'">'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }
            }

        $this->BankObject .='</select></td></tr>';
    }

    function Get(){
        ECHO  $this->BankObject;
    }

    function GetBankDetails($accountCode){
         Global $db;
        $resultindex=DB_query("SELECT [accountcode],[bankName],[bankaccounts].[currency],[lastreconcileddate]
                                     ,[AccountNo],[BranchCode],[BranchName],[lastreconbalance]
                                     ,[lastChequeno],[PostingGroup],[currencies].[rate],[StatementNo]
                                    FROM [bankaccounts] join [currencies] on [bankaccounts].[currency]=[currencies].[currabrev]
                                    where [accountcode]='".$accountCode."'", $db);

        $row=DB_fetch_row($resultindex);

        $this->BankArray['accountcode']=$row[0];
        $this->BankArray['bankName']=$row[1];
        $this->BankArray['currency']=$row[2];
        $this->BankArray['lastreconcileddate']=$row[3];
        $this->BankArray['AccountNo']=$row[4];
        $this->BankArray['BranchCode']=$row[5];
        $this->BankArray['BranchName']=$row[6];
        $this->BankArray['lastreconbalance']=$row[7];
        $this->BankArray['lastChequeno']=$row[8];
        $this->BankArray['PostingGroup']=$row[9];
        $this->BankArray['rate']=$row[10];
        $this->BankArray['StatementNo']=$row[11];

        return $this->BankArray;
    }
}

class Calculator {
    var $Calc = array();
    var $Calc_LastYear = array();
    var $Resource;
    Var $Amount=0;
    Var $Amount2=0;
    Var $Amount2_lastyear=0;
    Var $Amount_lastyear=0;
    var $begintotal=0;
    var $begintotal_lastyear=0;
    var $starttotaling=true;

    function Get($rows=array()){

       if($rows['ReportStyle']==3){
             $_SESSION['chart_table']['starttotaling'] = true;
             $_SESSION['chart_table']['begintotal'] = 0;
             $_SESSION['chart_table']['begintotal_lastyear'] = 0;
        }

       if(($rows['ReportStyle'] == 0 || $rows['ReportStyle'] == 5)
           and ($_SESSION['chart_table']['starttotaling'] == true)){
             $_SESSION['chart_table']['begintotal']  += ($rows['debit'] - $rows['credit']);
               $_SESSION['chart_table']['begintotal_lastyear']  += ($rows['debit_Last'] - $rows['credit_Last']);
    }

       if($rows['ReportStyle']==4){
             $_SESSION['chart_table']['starttotaling'] = false;
               $this->Amount2 = $_SESSION['chart_table']['begintotal'];
               $this->Amount2_lastyear = $_SESSION['chart_table']['begintotal_lastyear'];
      } else {
             $this->Amount2 = ($rows['debit'] - $rows['credit']);
             $this->Amount2_lastyear = ($rows['debit_Last'] - $rows['credit_Last']);
      }

        $this->savetotal($rows['ReportCode'],$this->Amount2);
        $this->savetotal_lastyear($rows['ReportCode'],$this->Amount2_lastyear);

        if((mb_strlen($rows['Calculation'])>0) and ($rows['ReportStyle']==2)){
            $this->Amount2 = $this->GetTotal($rows['Calculation']);
            $this->Amount2_lastyear = $this->GetTotal_lastyear($rows['Calculation']);
        }

        return $this->Amount2;

    }

    Function GetTotal_lastyear($formular){
         $this->Amount_lastyear=0;
         $accounts=explode('+',$formular);
        foreach ($accounts as $code) {
           $this->Amount_lastyear = $this->AddTotals_lastyear($code);
        }
        return $this->Amount_lastyear;
    }

    Function Reset(){
        Unset($_SESSION['chart_table']);
        $_SESSION['chart_table']['starttotaling'] = false;

        $_SESSION['chart_table']['Amount_lastyear'] = 0;
        $_SESSION['chart_table']['Amount'] = 0;

        $_SESSION['chart_table']['begintotal'] = 0;
        $_SESSION['chart_table']['begintotal_lastyear'] = 0;

        $_SESSION['chart_table']['Calc'] = array();
        $_SESSION['chart_table']['Calc_LastYear'] = array();
    }

    Function GetTotal($formular){
        $this->Amount=0;
        $accounts=explode('+',$formular);
        foreach ($accounts as $code) {
           $this->Amount = $this->AddTotals($code);
        }
        return $this->Amount;
    }

    function AddTotals($account){
         $_SESSION['chart_table']['Amount'] += $_SESSION['chart_table']['Calc'][$account];
         return $_SESSION['chart_table']['Amount'];
    }

    function AddTotals_lastyear($account){
         $_SESSION['chart_table']['Amount_lastyear'] += $_SESSION['chart_table']['Calc_LastYear'][$account];
         return $_SESSION['chart_table']['Amount_lastyear'];
    }

    Function savetotal($reportcode,$amount=0){
        $_SESSION['chart_table']['Calc'][$reportcode] = $amount ;
    }

    Function savetotal_lastyear($reportcode,$amount=0){
        $_SESSION['chart_table']['Calc_LastYear'][$reportcode] = $amount ;
    }

    function Get_lastyear(){
        return  $this->Amount2_lastyear;
    }



}

class TradingAccountbyMonth {
    var $Calc = array();
    var $Calc_LastYear = array();
    var $Resource;
    Var $Amount = 0;
    Var $Amount2 = 0;
    Var $Amount2_lastyear = 0;
    Var $Amount_lastyear = 0;
    var $begintotal = 0;
    var $begintotal_lastyear = 0;
    var $starttotaling = true;
    Var $ResultDataSet = array();
    var $COST_OF_SALES=0;
    Var $Profit=0;

    function Get($rows=array()){

       if($rows['ReportStyle']==3){
          $_SESSION['chart_table']['starttotaling'] = true;
          $_SESSION['chart_table']['begintotal'] = 0;
       }

       if(($rows['ReportStyle'] == 0) and ($_SESSION['chart_table']['starttotaling'] == true)){
          $_SESSION['chart_table']['begintotal']  += ($rows['amount']);
       }

       if($rows['ReportStyle']==4){
               $_SESSION['chart_table']['starttotaling'] = false;
               $this->Amount2 = $_SESSION['chart_table']['begintotal'];
               $_SESSION['chart_table']['begintotal'] = 0;
        } else {
             $this->Amount2 = ($rows['amount']);
        }

        $this->savetotal($rows['ReportCode'],$this->Amount2);

        if((mb_strlen($rows['Calculation'])>0) and ($rows['ReportStyle']==2)){
            $this->Amount2 = $this->GetTotal($rows['Calculation']);
        }

        return $this->Amount2;

    }

    Function Reset(){
        Unset($_SESSION['chart_table']);
        $_SESSION['chart_table']['starttotaling'] = false;

        $_SESSION['chart_table']['Amount_lastyear'] = 0;
        $_SESSION['chart_table']['Amount'] = 0;

        $_SESSION['chart_table']['begintotal'] = 0;
        $_SESSION['chart_table']['begintotal_lastyear'] = 0;

        $_SESSION['chart_table']['Calc'] = array();
        $_SESSION['chart_table']['Calc_LastYear'] = array();
    }

    Function GetTotal($formular){
        $this->Amount=0;
        $accounts=explode('+',$formular);
        foreach ($accounts as $code) {
           $this->Amount = $this->AddTotals($code);
        }
        return $this->Amount;
    }

    function AddTotals($account){
         $_SESSION['chart_table']['Amount'] += $_SESSION['chart_table']['Calc'][$account];
         return $_SESSION['chart_table']['Amount'];
    }

    Function savetotal($reportcode,$amount=0){
        $_SESSION['chart_table']['Calc'][$reportcode] = $amount ;
    }

    /*
    salesfrom,salesto,stockfrom,stockto,purchasesfrom,purchasesto,expensefrom,expenseto
    */


Function Calmonthlydata(){
        $this->Reset();
        $this->COST_OF_SALES=0;
        $this->Profit=0;

        $this->DB_Profit_loss_by_month_sales();
        $this->DB_Profit_loss_by_month_Purchase();
        $this->DB_Profit_loss_by_month_expenses();

         return $this->ResultDataSet;
    }



Function DB_Profit_loss_by_month_sales(){


global $db,$periodno;


$procodedure = "
	SELECT
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
        case acct.[balance_income] when 0 then
            (select sum(generalledger.amount * generalledger.[ExchangeRate])
            from generalledger where accountcode=acct.accno and period <='".$periodno."')
	else
           (select sum(generalledger.amount * generalledger.[ExchangeRate])
            from generalledger where accountcode=acct.accno and period='". $periodno ."')
        end
        as debit,

       case when acct.[balance_income]=0 then
            (select sum(generalledger.amount * generalledger.[ExchangeRate])
            from generalledger where balaccountcode=acct.accno and period <='".$periodno."')
	else
            (select sum(generalledger.amount * generalledger.[ExchangeRate])
            from generalledger where balaccountcode=acct.accno and period='".$periodno."')

        end
        as credit,
        [Calculation]
	from acct
	where [ReportCode] between (select salesfrom  from accountsetup limit 1) and (select salesto from accountsetup limit 1)
	group by
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]
	ORDER BY
	[ReportCode]";
  
       $ResultsX = DB_query($procodedure, $db);
       while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->ResultDataSet[$Row['ReportCode']]['ReportCode'] = $Row['ReportCode'];
           $this->ResultDataSet[$Row['ReportCode']]['accdesc'] = $Row['accdesc'];
           $this->ResultDataSet[$Row['ReportCode']]['accno'] = $Row['accno'];
           $this->ResultDataSet[$Row['ReportCode']]['ReportStyle'] = $Row['ReportStyle'];
           $this->ResultDataSet[$Row['ReportCode']]['amount'] = $AMOUNT;
           $this->Profit += $AMOUNT;
       }



}

Function DB_Profit_loss_by_month_Purchase(){

global $db,$periodno;

$procodedure = "
	SELECT
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
        case acct.[balance_income] when 0 then
            (select sum(generalledger.amount * generalledger.[ExchangeRate])  from generalledger where accountcode=acct.accno and period <='".$periodno."')
	else
           (select sum(generalledger.amount * generalledger.[ExchangeRate])  from generalledger where accountcode=acct.accno and period='". $periodno ."')
        end
        as debit,

       case when acct.[balance_income]=0 then
            (select sum(generalledger.amount * generalledger.[ExchangeRate])  from generalledger where balaccountcode=acct.accno and period <='".$periodno."')
	else
            (select sum(generalledger.amount * generalledger.[ExchangeRate])  from generalledger where balaccountcode=acct.accno and period='".$periodno."')
        end
        as credit,
        [Calculation]
	from acct
	where [ReportCode]
        between (select purchasesfrom from accountsetup limit 1) and (select purchasesto from accountsetup limit 1)
	group by
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]
	ORDER BY
	[ReportCode]
";

  
$ResultsX = DB_query($procodedure, $db);
while($Row=DB_fetch_array($ResultsX)){
     $AMOUNT = ($Row['debit'] - $Row['credit']);
    $this->ResultDataSet[$Row['ReportCode']]['ReportCode'] = $Row['ReportCode'];
    $this->ResultDataSet[$Row['ReportCode']]['accdesc'] = $Row['accdesc'];
    $this->ResultDataSet[$Row['ReportCode']]['accno'] = $Row['accno'];
    $this->ResultDataSet[$Row['ReportCode']]['ReportStyle'] = $Row['ReportStyle'];
    $this->ResultDataSet[$Row['ReportCode']]['amount'] = $AMOUNT;
     $this->COST_OF_SALES += $AMOUNT;
}
       
$this->ResultDataSet['cos']['ReportCode'] =  _('COS');
$this->ResultDataSet['cos']['accdesc'] = _('Cost Of Sales');
$this->ResultDataSet['cos']['ReportStyle'] = 2;
$this->ResultDataSet['cos']['amount'] = $this->COST_OF_SALES ;
}

Function DB_Profit_loss_by_month_expenses(){


global $db,$periodno;

$procodedure = "
	SELECT
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
        case acct.[balance_income] when 0 then
            (select sum(generalledger.amount * generalledger.[ExchangeRate])
            from generalledger where accountcode=acct.accno and period <='".$periodno."')
	else
           (select sum(generalledger.amount * generalledger.[ExchangeRate])
            from generalledger where accountcode=acct.accno and period='". $periodno ."')
        end
        as debit,

       case when acct.[balance_income]=0 then
            (select sum(generalledger.amount * generalledger.[ExchangeRate])
            from generalledger where balaccountcode=acct.accno and period <='".$periodno."')
	else
            (select sum(generalledger.amount * generalledger.[ExchangeRate])
            from generalledger where balaccountcode=acct.accno and period='".$periodno."')

        end
        as credit,
        [Calculation]
	from acct
	where [ReportCode]   between (select expensefrom  from accountsetup limit 1) and (select expenseto from accountsetup limit 1)
	group by
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]
	ORDER BY
	[ReportCode]
";


  $ResultsX=DB_query($procodedure,$db);
       while($Row=DB_fetch_array($ResultsX)){
            $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->ResultDataSet[$Row['ReportCode']]['ReportCode'] = $Row['ReportCode'];
           $this->ResultDataSet[$Row['ReportCode']]['accdesc'] = $Row['accdesc'];
           $this->ResultDataSet[$Row['ReportCode']]['accno'] = $Row['accno'];
           $this->ResultDataSet[$Row['ReportCode']]['ReportStyle'] = $Row['ReportStyle'];
           $this->ResultDataSet[$Row['ReportCode']]['amount'] = $AMOUNT;
           $this->Profit += $AMOUNT;
       }

           $this->ResultDataSet['netprofit']['ReportCode'] =  _('netprofit');
           $this->ResultDataSet['netprofit']['accdesc'] = _('Net Profit');
           $this->ResultDataSet['netprofit']['ReportStyle'] = 2;
           $this->ResultDataSet['netprofit']['amount'] =$this->Profit + $this->COST_OF_SALES ;
}


}

class TradingAccountForYear {
    var $Calc = array();
    var $Calc_LastYear = array();
    var $Resource;
    Var $Amount = 0;
    Var $Amount2 = 0;
    Var $Amount2_lastyear = 0;
    Var $Amount_lastyear = 0;
    var $begintotal = 0;
    var $begintotal_lastyear = 0;
    var $starttotaling = true;
    Var $ResultDataSet = array();
    var $COST_OF_SALES=0;
    Var $Profit=0;
    var $COST_OF_SALES_last=0;
    Var $Profit_last=0;


    function Get($rows=array()){

       if($rows['ReportStyle']==3){
             $_SESSION['chart_table']['starttotaling'] = true;
             $_SESSION['chart_table']['begintotal'] = 0;
       }

       if(($rows['ReportStyle'] == 0 || $rows['ReportStyle'] == 5)
           and ($_SESSION['chart_table']['starttotaling'] == true)){

               $_SESSION['chart_table']['begintotal']  += ($rows['amount']);

       }

       if($rows['ReportStyle']==4){
               $_SESSION['chart_table']['starttotaling'] = false;
               $this->Amount2 = $_SESSION['chart_table']['begintotal'];
               $_SESSION['chart_table']['begintotal'] = 0;
        } else {

             $this->Amount2 = ($rows['amount']);

        }

        $this->savetotal($rows['ReportCode'],$this->Amount2);

        if((mb_strlen($rows['Calculation'])>0) and ($rows['ReportStyle']==2)){
            $this->Amount2 = $this->GetTotal($rows['Calculation']);
        }

        return $this->Amount2;

    }

    function Get_last($rows=array()){

       if($rows['ReportStyle']==3){
             $_SESSION['chart_table_Last']['starttotaling'] = true;
             $_SESSION['chart_table_Last']['begintotal'] = 0;
       }

       if(($rows['ReportStyle'] == 0) and ($_SESSION['chart_table_Last']['starttotaling'] == true)){
               $_SESSION['chart_table_Last']['begintotal']  += ($rows['Amount_lastyear']);
       }

       if($rows['ReportStyle']==4){
               $_SESSION['chart_table_Last']['starttotaling'] = false;
               $this->Amount2_lastyear = $_SESSION['chart_table_Last']['begintotal'];
               $_SESSION['chart_table_Last']['begintotal'] = 0;
        } else {
             $this->Amount2_lastyear = ($rows['Amount_lastyear']);
        }

        $this->savetotal_last($rows['ReportCode'],$this->Amount2_lastyear);

        if((mb_strlen($rows['Calculation'])>0) and ($rows['ReportStyle']==2)){
            $this->Amount2_lastyear = $this->GetTotal_last($rows['Calculation']);
        }

        return $this->Amount2_lastyear;

    }

    Function Reset(){
        Unset($_SESSION['chart_table']);
        $_SESSION['chart_table']['starttotaling'] = false;
        $_SESSION['chart_table']['Amount'] = 0;
        $_SESSION['chart_table']['begintotal'] = 0;
        $_SESSION['chart_table']['Calc'] = array();

         Unset($_SESSION['chart_table_Last']);
        $_SESSION['chart_table_Last']['starttotaling'] = false;
        $_SESSION['chart_table_Last']['Amount'] = 0;
        $_SESSION['chart_table_Last']['begintotal'] = 0;
        $_SESSION['chart_table_Last']['Calc'] = array();


    }

    Function GetTotal_last($formular){
        $this->Amount_lastyear=0;
        $accounts=explode('+',$formular);
        foreach ($accounts as $code) {
           $this->Amount_lastyear = $this->AddTotals_last($code);
        }
        return $this->Amount_lastyear;
    }

    function AddTotals($account){
         $_SESSION['chart_table']['Amount'] += $_SESSION['chart_table']['Calc'][$account];
         return $_SESSION['chart_table']['Amount'];
    }

    Function savetotal_last($reportcode,$amount=0){
        $_SESSION['chart_table_Last']['Calc'][$reportcode] = $amount ;
    }

    Function GetTotal($formular){
        $this->Amount=0;
        $accounts=explode('+',$formular);
        foreach ($accounts as $code) {
           $this->Amount = $this->AddTotals($code);
        }
        return $this->Amount;
    }

    function AddTotals_last($account){
         $_SESSION['chart_table_Last']['Amount'] += $_SESSION['chart_table_Last']['Calc'][$account];
         return $_SESSION['chart_table_Last']['Amount'];
    }

    Function savetotal($reportcode,$amount=0){
        $_SESSION['chart_table']['Calc'][$reportcode] = $amount ;
    }



Function CalYearlydata(){
        $this->Reset();
        $this->COST_OF_SALES=0;
        $this->Profit=0;

         $this->DB_Profit_loss_sales();
         $this->DB_Profit_loss_Purchase();
         $this->DB_Profit_loss_expenses();

         return $this->ResultDataSet;
    }


Function DB_Profit_loss_sales(){

global $db,$YearPeriod;
$YearPeriod = $_POST['Financial_Periods'];

$procodedure = "
	SELECT
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
            case when acct.[balance_income]=0 then
                    (select
                    sum(generalledger.amount * generalledger.[ExchangeRate])
                    from generalledger where accountcode=acct.accno
                    and Docdate <=(select max([end_date])  from financialperiods  where periodno=".$YearPeriod."))
            else
                    (select  sum(generalledger.amount * generalledger.[ExchangeRate])
                    from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
                    from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date])
                   from financialperiods  where periodno=".$YearPeriod."))

           end as debit,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".$YearPeriod."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date])
                from financialperiods  where periodno=".$YearPeriod."))

         end as credit,

            case when acct.[balance_income]=0 then
                 (select sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno
                  and Docdate <=(select max([end_date])  from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                 (select  sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
                  from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                  from financialperiods  where periodno=".($YearPeriod-12)."))

           end as debit_last,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))

         end as credit_last,
        [Calculation]
	from acct
	where [ReportCode] between (select salesfrom  from accountsetup limit 1) and (select salesto from accountsetup limit 1)
	group by
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]
	ORDER BY
	[ReportCode]
";


       $ResultsX = DB_query($procodedure, $db);
       while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->ResultDataSet[$Row['ReportCode']]['ReportCode'] = $Row['ReportCode'];
           $this->ResultDataSet[$Row['ReportCode']]['accdesc'] = $Row['accdesc'];
           $this->ResultDataSet[$Row['ReportCode']]['accno'] = $Row['accno'];
           $this->ResultDataSet[$Row['ReportCode']]['ReportStyle'] = $Row['ReportStyle'];
           $this->ResultDataSet[$Row['ReportCode']]['Calculation'] = $Row['Calculation'];
           $this->ResultDataSet[$Row['ReportCode']]['amount'] = $AMOUNT;
           $this->Profit += $AMOUNT;

           $AMOUNT = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->ResultDataSet[$Row['ReportCode']]['Amount_lastyear'] = $AMOUNT;
           $this->Profit_last += $AMOUNT;
       }



}

Function DB_Profit_loss_Purchase(){


global $db,$YearPeriod;

$procodedure = "
	SELECT
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
            case when acct.[balance_income]=0 then
                    (select sum(generalledger.amount * generalledger.[ExchangeRate])
                    from generalledger where accountcode=acct.accno and Docdate <=(select max([end_date])
                    from financialperiods  where periodno=".$YearPeriod."))
            else
                    (select  sum(generalledger.amount * generalledger.[ExchangeRate])
                    from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
                    from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date]) "
                  . "from financialperiods  where periodno=".$YearPeriod."))

           end as debit,

    case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".$YearPeriod."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date])
               from financialperiods  where periodno=".$YearPeriod."))

         end as credit,

            case when acct.[balance_income]=0 then
                 (select sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno
                  and Docdate <=(select max([end_date])  from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                 (select  sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
                  from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                  from financialperiods  where periodno=".($YearPeriod-12)."))

           end as debit_Last,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))

         end as credit_Last,
        [Calculation]
	from acct
	where [ReportCode]
        between (select purchasesfrom  from accountsetup limit 1) and (select purchasesto from accountsetup limit 1)
	group by
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]
	ORDER BY
	[ReportCode]";

 
       $ResultsX = DB_query($procodedure, $db);
       while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->ResultDataSet[$Row['ReportCode']]['ReportCode'] = $Row['ReportCode'];
           $this->ResultDataSet[$Row['ReportCode']]['accdesc'] = $Row['accdesc'];
           $this->ResultDataSet[$Row['ReportCode']]['accno'] = $Row['accno'];
           $this->ResultDataSet[$Row['ReportCode']]['ReportStyle'] = $Row['ReportStyle'];
           $this->ResultDataSet[$Row['ReportCode']]['Calculation'] = $Row['Calculation'];
           $this->ResultDataSet[$Row['ReportCode']]['amount'] = $AMOUNT;
           $this->COST_OF_SALES += $AMOUNT;

           $AMOUNT = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->ResultDataSet[$Row['ReportCode']]['Amount_lastyear'] = $AMOUNT;
           $this->COST_OF_SALES_last += $AMOUNT;
       }
       
    $this->ResultDataSet['cos']['ReportCode'] = _('COS');
    $this->ResultDataSet['cos']['accdesc'] = _('Cost Of Sales');
    $this->ResultDataSet['cos']['ReportStyle'] = 2;
    $this->ResultDataSet['cos']['amount'] = $this->COST_OF_SALES ;
    $this->ResultDataSet['cos']['Amount_lastyear']=$this->COST_OF_SALES_last;
       
}

Function DB_Profit_loss_expenses(){


global $db,$YearPeriod;

$procodedure = "
	SELECT
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
            case when acct.[balance_income]=0 then
                    (select sum(generalledger.amount * generalledger.[ExchangeRate])
                    from generalledger where accountcode=acct.accno and Docdate <=(select max([end_date])
                    from financialperiods  where periodno=".$YearPeriod.") )
            else
                    (select  sum(generalledger.amount * generalledger.[ExchangeRate])
                    from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
                    from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date])
                   from financialperiods  where periodno=".$YearPeriod."))

           end as debit,

    case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate <=(select max([end_date]) from financialperiods
                where periodno=".$YearPeriod."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date])
               from financialperiods  where periodno=".$YearPeriod."))

         end as credit,

            case when acct.[balance_income]=0 then
                 (select sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno
                  and Docdate <=(select max([end_date])  from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                 (select  sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
                  from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                  from financialperiods  where periodno=".($YearPeriod-12)."))

           end as debit_Last,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))

         end as credit_Last,
        [Calculation]
	from acct
	where [ReportCode]   between (select expensefrom  from accountsetup limit 1) and   (select expenseto from accountsetup limit 1)
	group by
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]
	ORDER BY
	[ReportCode]
";


       $ResultsX = DB_query($procodedure, $db);
       while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->ResultDataSet[$Row['ReportCode']]['ReportCode'] = $Row['ReportCode'];
           $this->ResultDataSet[$Row['ReportCode']]['accdesc'] = $Row['accdesc'];
           $this->ResultDataSet[$Row['ReportCode']]['accno'] = $Row['accno'];
           $this->ResultDataSet[$Row['ReportCode']]['ReportStyle'] = $Row['ReportStyle'];
           $this->ResultDataSet[$Row['ReportCode']]['Calculation'] = $Row['Calculation'];
           $this->ResultDataSet[$Row['ReportCode']]['amount'] = $AMOUNT;
           $this->Profit += $AMOUNT;

           $AMOUNT = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->ResultDataSet[$Row['ReportCode']]['Amount_lastyear'] = $AMOUNT;
           $this->Profit_last += $AMOUNT ;
       }

           $this->ResultDataSet['netprofit']['ReportCode'] =  _('netprofit');
           $this->ResultDataSet['netprofit']['accdesc'] = _('Net Profit');
           $this->ResultDataSet['netprofit']['ReportStyle'] = 2;
           $this->ResultDataSet['netprofit']['amount'] =$this->Profit + $this->COST_OF_SALES ;
           $this->ResultDataSet['netprofit']['Amount_lastyear'] =$this->Profit_last + $this->COST_OF_SALES_last ;
}


}

class BalancesheetForYear {
    var $Calc = array();
    var $Calc_LastYear = array();
    Var $Amount = 0;
    Var $Amount2 = 0;
    Var $Amount2_lastyear = 0;
    Var $Amount_lastyear = 0;
    var $begintotal = 0;
    var $begintotal_lastyear = 0;
    var $starttotaling = true;
    Var $ResultDataSet = array();
    var $DebitSide = 0;
    var $CreditSide = 0;
    var $DebitSide_Last = 0;
    var $CreditSide_Last = 0;
    Var $Profit = 0;
    Var $Profit_last = 0;
    var $Resource;
    var $Closingstock=0;
    Var $Openningstock=0;
    Var $CurrentAssets=0;
    Var $CurrentAssets_Last=0;

    Function __construct() {
        $_SESSION['BalanceSheed']['id']=100000;
    }

    Function GetID(){
       return $_SESSION['BalanceSheed']['id']++;
    }

    function Get($rows=array()){

       if($rows['ReportStyle']==3){
             $_SESSION['chart_table']['starttotaling'] = true;
             $_SESSION['chart_table']['begintotal'] = 0;
       }

       if(($rows['ReportStyle'] == 0 || $rows['ReportStyle'] == 5)
           and ($_SESSION['chart_table']['starttotaling'] == true)){
            $_SESSION['chart_table']['begintotal']  += ($rows['amount']);
      }

       if($rows['ReportStyle']==4){
               $_SESSION['chart_table']['starttotaling'] = false;
               $this->Amount2 = $_SESSION['chart_table']['begintotal'];
               $_SESSION['chart_table']['begintotal'] = 0;
        } else {
           $this->Amount2 = ($rows['amount']);
       }

        $this->savetotal($rows['ReportCode'],$this->Amount2);

        if((mb_strlen($rows['Calculation'])>0) and ($rows['ReportStyle']==2)){
            $this->Amount2 = $this->GetTotal($rows['Calculation']);
        }

        return $this->Amount2;

    }

    function Get_last($rows=array()){

       if($rows['ReportStyle']==3){
             $_SESSION['chart_table_Last']['starttotaling'] = true;
             $_SESSION['chart_table_Last']['begintotal'] = 0;
       }

       if(($rows['ReportStyle'] == 0) and ($_SESSION['chart_table_Last']['starttotaling'] == true)){
               $_SESSION['chart_table_Last']['begintotal']  += ($rows['Amount_lastyear']);
       }

       if($rows['ReportStyle']==4){
               $_SESSION['chart_table_Last']['starttotaling'] = false;
               $this->Amount2_lastyear = $_SESSION['chart_table_Last']['begintotal'];
               $_SESSION['chart_table_Last']['begintotal'] = 0;
        } else {
             $this->Amount2_lastyear = ($rows['Amount_lastyear']);
        }

        $this->savetotal_last($rows['ReportCode'],$this->Amount2_lastyear);

        if((mb_strlen($rows['Calculation'])>0) and ($rows['ReportStyle']==2)){
            $this->Amount2_lastyear = $this->GetTotal_last($rows['Calculation']);
        }

        return $this->Amount2_lastyear;

    }

    Function Reset(){
        Unset($_SESSION['chart_table']);
        $_SESSION['chart_table']['starttotaling'] = false;
        $_SESSION['chart_table']['Amount'] = 0;
        $_SESSION['chart_table']['begintotal'] = 0;
        $_SESSION['chart_table']['Calc'] = array();

         Unset($_SESSION['chart_table_Last']);
        $_SESSION['chart_table_Last']['starttotaling'] = false;
        $_SESSION['chart_table_Last']['Amount'] = 0;
        $_SESSION['chart_table_Last']['begintotal'] = 0;
        $_SESSION['chart_table_Last']['Calc'] = array();

        $this->ResultDataSet=array();
    }
    
    Function savetotal_last($reportcode,$amount=0){
        $_SESSION['chart_table_Last']['Calc'][$reportcode] = $amount ;
    }
    
    Function GetTotal_last($formular){
        $this->Amount_lastyear=0;
        $accounts=explode('+',$formular);
        foreach ($accounts as $code) {
           $this->Amount_lastyear = $this->AddTotals_last($code);
        }
        return $this->Amount_lastyear;
    }

    Function GetTotal($formular){
        $this->Amount=0;
        $accounts=explode('+',$formular);
        foreach ($accounts as $code) {
           $this->Amount = $this->AddTotals($code);
        }
        return $this->Amount;
    }

    function AddTotals($account){
         $_SESSION['chart_table']['Amount'] += $_SESSION['chart_table']['Calc'][$account];
         return $_SESSION['chart_table']['Amount'];
    }

    function AddTotals_last($account){
         $_SESSION['chart_table_Last']['Amount'] += $_SESSION['chart_table_Last']['Calc'][$account];
         return $_SESSION['chart_table_Last']['Amount'];
    }

    Function savetotal($reportcode,$amount=0){
        $_SESSION['chart_table']['Calc'][$reportcode] = $amount ;
    }

    Function CalYearlydata(){
        $this->Reset();

        $this->Profit = 0;
        $this->Profit_last = 0;

        $this->DebitSide = 0;
        $this->CreditSide = 0;

        $this->DebitSide_Last = 0;
        $this->CreditSide_Last = 0;

         $this->CurrentAssets=0;
         $this->CurrentAssets_Last=0;

         $this->DB_FixedAssets();
         $this->DB_Inventory();
         $this->DB_Debtors();
         $this->DB_Bank();
         $this->DB_CurrentLiability();
         $this->DB_LongLiability();
         $this->DB_Capital();

         return $this->ResultDataSet;
    }

Function DB_FixedAssets(){
 
global $db,$YearPeriod;
$YearPeriod = $_POST['Financial_Periods'];

$procodedure = "
	SELECT
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
        case when acct.[balance_income]=0 then
           (select sum(generalledger.amount * generalledger.[ExchangeRate])
            from generalledger where accountcode=acct.accno and Docdate <=(select max([end_date]) from financialperiods 
            where periodno=".$YearPeriod."))
        else
           (select sum(generalledger.amount * generalledger.[ExchangeRate])
           from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
           from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date])
           from financialperiods  where periodno=".$YearPeriod."))

           end as debit,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".$YearPeriod."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date])
                from financialperiods  where periodno=".$YearPeriod."))

         end as credit,

            case when acct.[balance_income]=0 then
                 (select sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno
                  and Docdate <=(select max([end_date])  from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                 (select  sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
                  from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                  from financialperiods  where periodno=".($YearPeriod-12)."))

           end as debit_last,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))

         end as credit_last,
        [Calculation]
	from acct
	where [ReportCode] between (select fixedassetsFrom from accountsetup limit 1) and (select fixedassetsTo from accountsetup limit 1)
	group by
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]
	ORDER BY
	[ReportCode]
";


       $ResultsX = DB_query($procodedure, $db);
       while($Row=DB_fetch_array($ResultsX)){
           $AccountCode= $this->GetID();
           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->ResultDataSet[$AccountCode]['ReportCode'] = $Row['ReportCode'];
           $this->ResultDataSet[$AccountCode]['accdesc'] = $Row['accdesc'];
           $this->ResultDataSet[$AccountCode]['accno'] = $Row['accno'];
           $this->ResultDataSet[$AccountCode]['ReportStyle'] = $Row['ReportStyle'];
           $this->ResultDataSet[$AccountCode]['Calculation'] = $Row['Calculation'];
           $this->ResultDataSet[$AccountCode]['amount'] = $AMOUNT;
           $this->DebitSide += $AMOUNT;
           
           $PAMOUNT = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = $PAMOUNT;
           $this->DebitSide_Last += $PAMOUNT;
       }



}

Function DB_Inventory(){


global $db,$YearPeriod;
$YearPeriod = $_POST['Financial_Periods'];

$procodedure = "
	SELECT
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
        case when acct.[balance_income]=0 then
           (select sum(generalledger.amount * generalledger.[ExchangeRate])
            from generalledger where accountcode=acct.accno
            and Docdate <=(select max([end_date]) from financialperiods  where periodno=".$YearPeriod."))
        else
           (select sum(generalledger.amount * generalledger.[ExchangeRate])
           from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
           from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date])
           from financialperiods  where periodno=".$YearPeriod."))

           end as debit,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".$YearPeriod."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date])
                from financialperiods  where periodno=".$YearPeriod."))

         end as credit,

            case when acct.[balance_income]=0 then
                 (select sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno
                  and Docdate <=(select max([end_date])  from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                 (select  sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
                  from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                  from financialperiods  where periodno=".($YearPeriod-12)."))

           end as debit_last,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))

         end as credit_last,
        [Calculation]
	from acct
	where [ReportCode] between (select inventoryFrom  from accountsetup limit 1) and (select inventoryTo from accountsetup limit 1)
	group by
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]
	ORDER BY
	[ReportCode]
";


       $ResultsX = DB_query($procodedure, $db);
       while($Row=DB_fetch_array($ResultsX)){
              $AccountCode= $this->GetID();

           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->ResultDataSet[$AccountCode]['ReportCode'] = $Row['ReportCode'];
           $this->ResultDataSet[$AccountCode]['accdesc'] = $Row['accdesc'];
           $this->ResultDataSet[$AccountCode]['accno'] = $Row['accno'];
           $this->ResultDataSet[$AccountCode]['ReportStyle'] = $Row['ReportStyle'];
           $this->ResultDataSet[$AccountCode]['Calculation'] = $Row['Calculation'];
           $this->ResultDataSet[$AccountCode]['amount'] = $AMOUNT;
           $this->CurrentAssets += $AMOUNT;
           $AMOUNT = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = $AMOUNT;
            $this->CurrentAssets_Last += $AMOUNT;
       }



}

Function DB_Debtors(){

global $db,$YearPeriod;
$YearPeriod = $_POST['Financial_Periods'];

$procodedure = "
	SELECT
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
        case when acct.[balance_income]=0 then
           (select sum(generalledger.amount * generalledger.[ExchangeRate])
            from generalledger where accountcode=acct.accno
            and Docdate <=(select max([end_date]) from financialperiods  where periodno=".$YearPeriod."))
        else
           (select sum(generalledger.amount * generalledger.[ExchangeRate])
           from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
           from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date])
           from financialperiods  where periodno=".$YearPeriod."))

           end as debit,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".$YearPeriod."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date])
                from financialperiods  where periodno=".$YearPeriod."))

         end as credit,

            case when acct.[balance_income]=0 then
                 (select sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno
                  and Docdate <=(select max([end_date])  from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                 (select  sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
                  from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                  from financialperiods  where periodno=".($YearPeriod-12)."))

           end as debit_last,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))

         end as credit_last,
        [Calculation]
	from acct
	where [ReportCode] between (select DebtorsFrom  from accountsetup limit 1) and (select DebtorsTo from accountsetup limit 1)
	group by
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]
	ORDER BY
	[ReportCode]";


       $ResultsX = DB_query($procodedure, $db);
       while($Row=DB_fetch_array($ResultsX)){
             $AccountCode= $this->GetID();
           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->ResultDataSet[$AccountCode]['ReportCode'] = $Row['ReportCode'];
           $this->ResultDataSet[$AccountCode]['accdesc'] = $Row['accdesc'];
           $this->ResultDataSet[$AccountCode]['accno'] = $Row['accno'];
           $this->ResultDataSet[$AccountCode]['ReportStyle'] = $Row['ReportStyle'];
           $this->ResultDataSet[$AccountCode]['Calculation'] = $Row['Calculation'];
           $this->ResultDataSet[$AccountCode]['amount'] = $AMOUNT;
           $this->CurrentAssets += $AMOUNT;
           $AMOUNT = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = $AMOUNT;
           $this->CurrentAssets_Last += $AMOUNT;

       }
}

Function DB_Bank(){

global $db,$YearPeriod;
$YearPeriod = $_POST['Financial_Periods'];

$procodedure = "
	SELECT
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
        case when acct.[balance_income]=0 then
           (select sum(generalledger.amount * generalledger.[ExchangeRate])
            from generalledger where accountcode=acct.accno
            and Docdate <=(select max([end_date]) from financialperiods  where periodno=".$YearPeriod."))
        else
           (select sum(generalledger.amount * generalledger.[ExchangeRate])
           from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
           from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date])
           from financialperiods  where periodno=".$YearPeriod."))

           end as debit,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".$YearPeriod."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date])
                from financialperiods  where periodno=".$YearPeriod."))

         end as credit,

            case when acct.[balance_income]=0 then
                 (select sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno
                  and Docdate <=(select max([end_date])  from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                 (select  sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
                  from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                  from financialperiods  where periodno=".($YearPeriod-12)."))

           end as debit_last,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))

         end as credit_last,
        [Calculation]
	from acct
	where [ReportCode] between (select BankFrom from accountsetup limit 1) and (select BankTo from accountsetup limit 1)
	group by
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]
	ORDER BY
	[ReportCode]";

       $ResultsX = DB_query($procodedure, $db);
       while($Row=DB_fetch_array($ResultsX)){
              $AccountCode= $this->GetID();

           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->ResultDataSet[$AccountCode]['ReportCode'] = $Row['ReportCode'];
           $this->ResultDataSet[$AccountCode]['accdesc'] = $Row['accdesc'];
           $this->ResultDataSet[$AccountCode]['accno'] = $Row['accno'];
           $this->ResultDataSet[$AccountCode]['ReportStyle'] = $Row['ReportStyle'];
           $this->ResultDataSet[$AccountCode]['Calculation'] = $Row['Calculation'];
           $this->ResultDataSet[$AccountCode]['amount'] = $AMOUNT;
           $this->CurrentAssets += $AMOUNT;
           $AMOUNT = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = $AMOUNT;
           $this->CurrentAssets_Last += $AMOUNT;
       }

}

Function DB_CurrentLiability(){


global $db,$YearPeriod;
$YearPeriod = $_POST['Financial_Periods'];

$procodedure = "

	SELECT
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
        case when acct.[balance_income]=0 then
           (select sum(generalledger.amount * generalledger.[ExchangeRate])
            from generalledger where accountcode=acct.accno
            and Docdate <=(select max([end_date]) from financialperiods  where periodno=".$YearPeriod."))
        else
           (select sum(generalledger.amount * generalledger.[ExchangeRate])
           from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
           from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date])
           from financialperiods  where periodno=".$YearPeriod."))

           end as debit,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".$YearPeriod."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date])
                from financialperiods  where periodno=".$YearPeriod."))

         end as credit,

            case when acct.[balance_income]=0 then
                 (select sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno
                  and Docdate <=(select max([end_date])  from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                 (select  sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
                  from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                  from financialperiods  where periodno=".($YearPeriod-12)."))

           end as debit_last,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))

         end as credit_last,
        [Calculation]
	from acct
	where [ReportCode] between (select CurLiabFrom from accountsetup limit 1) and (select CurLiabTo from accountsetup limit 1)
	group by
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]
	ORDER BY
	[ReportCode]

";


       $ResultsX = DB_query($procodedure, $db);
       while($Row=DB_fetch_array($ResultsX)){
             $AccountCode= $this->GetID();

           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->ResultDataSet[$AccountCode]['ReportCode'] = $Row['ReportCode'];
           $this->ResultDataSet[$AccountCode]['accdesc'] = $Row['accdesc'];
           $this->ResultDataSet[$AccountCode]['accno'] = $Row['accno'];
           $this->ResultDataSet[$AccountCode]['ReportStyle'] = $Row['ReportStyle'];
           $this->ResultDataSet[$AccountCode]['Calculation'] = $Row['Calculation'];
           $this->ResultDataSet[$AccountCode]['amount'] = $AMOUNT * -1;
           $this->CurrentAssets += $AMOUNT;
           $AMOUNT = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = $AMOUNT * -1;
           $this->CurrentAssets_Last += $AMOUNT;
       }

           $AccountCode= $this->GetID();
           $this->ResultDataSet[$AccountCode]['ReportCode'] = _('NetAssets');
           $this->ResultDataSet[$AccountCode]['accdesc'] =  _('Net Current Assets');
           $this->ResultDataSet[$AccountCode]['ReportStyle'] = 2;
           $this->ResultDataSet[$AccountCode]['amount'] = $this->CurrentAssets ;
           $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = $this->CurrentAssets_Last ;

           $this->DebitSide += $this->CurrentAssets ;
           $this->DebitSide_Last += $this->CurrentAssets_Last ;

           $AccountCode= $this->GetID();
           $this->ResultDataSet[$AccountCode]['ReportCode'] = _('Assets');
           $this->ResultDataSet[$AccountCode]['accdesc'] =  _('Total Assets');
           $this->ResultDataSet[$AccountCode]['ReportStyle'] = 2;
           $this->ResultDataSet[$AccountCode]['amount'] = $this->DebitSide ;
           $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = $this->DebitSide_Last;

           $AccountCode= $this->GetID();
           $this->ResultDataSet[$AccountCode]['ReportCode'] =  _('Capital');
           $this->ResultDataSet[$AccountCode]['accdesc'] = _('Capital Employed');
           $this->ResultDataSet[$AccountCode]['ReportStyle'] = 4;

}

Function DB_LongLiability(){

global $db,$YearPeriod;
$YearPeriod = $_POST['Financial_Periods'];

$procodedure = "
	SELECT
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
        case when acct.[balance_income]=0 then
           (select sum(generalledger.amount * generalledger.[ExchangeRate])
            from generalledger where accountcode=acct.accno
            and Docdate <=(select max([end_date]) from financialperiods  where periodno=".$YearPeriod."))
        else
           (select sum(generalledger.amount * generalledger.[ExchangeRate])
           from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
           from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date])
           from financialperiods  where periodno=".$YearPeriod."))

           end as debit,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".$YearPeriod."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date])
                from financialperiods  where periodno=".$YearPeriod."))

         end as credit,

            case when acct.[balance_income]=0 then
                 (select sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno
                  and Docdate <=(select max([end_date])  from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                 (select  sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
                  from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                  from financialperiods  where periodno=".($YearPeriod-12)."))

           end as debit_last,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))

         end as credit_last,
        [Calculation]
	from acct
	where [ReportCode] between (select LongLiabFrom from accountsetup limit 1) and (select LongLiabTo from accountsetup limit 1)
	group by
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]
	ORDER BY
	[ReportCode]";

       $ResultsX = DB_query($procodedure, $db);
       while($Row=DB_fetch_array($ResultsX)){
             $AccountCode= $this->GetID();

           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->ResultDataSet[$AccountCode]['ReportCode'] = $Row['ReportCode'];
           $this->ResultDataSet[$AccountCode]['accdesc'] = $Row['accdesc'];
           $this->ResultDataSet[$AccountCode]['accno'] = $Row['accno'];
           $this->ResultDataSet[$AccountCode]['ReportStyle'] = $Row['ReportStyle'];
           $this->ResultDataSet[$AccountCode]['Calculation'] = $Row['Calculation'];
           $this->ResultDataSet[$AccountCode]['amount'] = $AMOUNT * -1;
           $this->CreditSide += $AMOUNT;
           $AMOUNT = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = $AMOUNT * -1;
           $this->CreditSide_Last += $AMOUNT;
       }



}

Function DB_Capital(){ 

global $db,$YearPeriod;

$procodedure = "SELECT
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
            case when acct.[balance_income]=0 then
                    (select sum(generalledger.amount * generalledger.[ExchangeRate])
                    from generalledger where accountcode=acct.accno and Docdate <=(select max([end_date])
                    from financialperiods  where periodno=".$YearPeriod.") )
            else
                    (select  sum(generalledger.amount * generalledger.[ExchangeRate])
                    from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
                    from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date])
                   from financialperiods  where periodno=".$YearPeriod."))

           end as debit,

    case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate <=(select max([end_date]) from financialperiods
                where periodno=".$YearPeriod."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".$YearPeriod.") and (select max([end_date])
               from financialperiods  where periodno=".$YearPeriod."))

         end as credit,

            case when acct.[balance_income]=0 then
                 (select sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno
                  and Docdate <=(select max([end_date])  from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                 (select  sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno and Docdate between (select min([start_date])
                  from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                  from financialperiods  where periodno=".($YearPeriod-12)."))

           end as debit_Last,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and Docdate between (select min([start_date])
                from financialperiods  where periodno=".($YearPeriod-12).") and (select max([end_date])
                from financialperiods  where periodno=".($YearPeriod-12)."))

         end as credit_Last,
        [Calculation]
	from acct
	where [ReportCode]   between (select CapitalFrom from accountsetup limit 1) and (select CapitalTo from accountsetup limit 1)
	group by
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]
	ORDER BY
	[ReportCode]";

      
       $ResultsX = DB_query($procodedure, $db);
       while($Row=DB_fetch_array($ResultsX)){
           $AccountCode= $this->GetID();

           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->ResultDataSet[$AccountCode]['ReportCode'] = $Row['ReportCode'];
           $this->ResultDataSet[$AccountCode]['accdesc'] = $Row['accdesc'];
           $this->ResultDataSet[$AccountCode]['accno'] = $Row['accno'];
           $this->ResultDataSet[$AccountCode]['ReportStyle'] = $Row['ReportStyle'];
           $this->ResultDataSet[$AccountCode]['Calculation'] = $Row['Calculation'];
           $this->ResultDataSet[$AccountCode]['amount'] = $AMOUNT * -1 ;
           $this->CreditSide += $AMOUNT;
           $AMOUNT = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = $AMOUNT * -1 ;
           $this->CreditSide_Last += $AMOUNT;
        }


           $this->Profit = $this->DebitSide + $this->CreditSide  ;
           $this->Profit_last = $this->DebitSide_Last + $this->CreditSide_Last  ;

           $AccountCode= $this->GetID();
           $this->ResultDataSet[$AccountCode]['ReportCode'] =  _('AccFund');
           $this->ResultDataSet[$AccountCode]['accdesc'] = _('Retained Earnings B/F');
           $this->ResultDataSet[$AccountCode]['ReportStyle'] = 2;
           $this->ResultDataSet[$AccountCode]['amount'] =  $this->Profit   ;
           $this->ResultDataSet[$AccountCode]['Amount_lastyear'] =  $this->Profit_last  ;

           $AccountCode= $this->GetID();
           $this->ResultDataSet[$AccountCode]['ReportCode'] =  _('Capital');
           $this->ResultDataSet[$AccountCode]['accdesc'] = _('Capital Employed');
           $this->ResultDataSet[$AccountCode]['ReportStyle'] = 2;
           $this->ResultDataSet[$AccountCode]['amount'] = ($this->CreditSide - $this->Profit) * -1 ;
           $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = ($this->CreditSide_Last - $this->Profit_last) * -1  ;

}

}

class TradingAccountByProjectForYear {
    var $Calc = array();
    var $Calc_LastYear = array();
    var $Resource;
    Var $Amount = 0;
    Var $Amount2 = 0;
    Var $Amount2_lastyear = 0;
    Var $Amount_lastyear = 0;
    var $begintotal = 0;
    var $begintotal_lastyear = 0;
    var $starttotaling = true;
    Var $ResultDataSet = array();
    var $COST_OF_SALES=0;
    Var $Profit=0;
    var $COST_OF_SALES_last=0;
    Var $Profit_last=0;
    var $YearPeriod = 0;
    var $ProjectSelected='';

    function Get($rows=array()){

       if($rows['ReportStyle']==3){
             $_SESSION['chart_table']['starttotaling'] = true;
             $_SESSION['chart_table']['begintotal'] = 0;
       }

       if(($rows['ReportStyle'] == 0 || $rows['ReportStyle'] == 5)
           and ($_SESSION['chart_table']['starttotaling'] == true)){

               $_SESSION['chart_table']['begintotal']  += ($rows['amount']);

       }

       if($rows['ReportStyle']==4){
               $_SESSION['chart_table']['starttotaling'] = false;
               $this->Amount2 = $_SESSION['chart_table']['begintotal'];
               $_SESSION['chart_table']['begintotal'] = 0;
        } else {

             $this->Amount2 = ($rows['amount']);

        }

        $this->savetotal($rows['ReportCode'],$this->Amount2);

        if((mb_strlen($rows['Calculation'])>0) and ($rows['ReportStyle']==2)){
            $this->Amount2 = $this->GetTotal($rows['Calculation']);
        }

        return $this->Amount2;

    }

    function Get_last($rows=array()){

       if($rows['ReportStyle']==3){
             $_SESSION['chart_table_Last']['starttotaling'] = true;
             $_SESSION['chart_table_Last']['begintotal'] = 0;
       }

       if(($rows['ReportStyle'] == 0) and ($_SESSION['chart_table_Last']['starttotaling'] == true)){
               $_SESSION['chart_table_Last']['begintotal']  += ($rows['Amount_lastyear']);
       }

       if($rows['ReportStyle']==4){
               $_SESSION['chart_table_Last']['starttotaling'] = false;
               $this->Amount2_lastyear = $_SESSION['chart_table_Last']['begintotal'];
               $_SESSION['chart_table_Last']['begintotal'] = 0;
        } else {
             $this->Amount2_lastyear = ($rows['Amount_lastyear']);
        }

        $this->savetotal_last($rows['ReportCode'],$this->Amount2);

        if((mb_strlen($rows['Calculation'])>0) and ($rows['ReportStyle']==2)){
            $this->Amount2_lastyear = $this->GetTotal_last($rows['Calculation']);
        }

        return $this->Amount2_lastyear;

    }

    Function Reset(){
        Unset($_SESSION['chart_table']);
        $_SESSION['chart_table']['starttotaling'] = false;
        $_SESSION['chart_table']['Amount'] = 0;
        $_SESSION['chart_table']['begintotal'] = 0;
        $_SESSION['chart_table']['Calc'] = array();

         Unset($_SESSION['chart_table_Last']);
        $_SESSION['chart_table_Last']['starttotaling'] = false;
        $_SESSION['chart_table_Last']['Amount'] = 0;
        $_SESSION['chart_table_Last']['begintotal'] = 0;
        $_SESSION['chart_table_Last']['Calc'] = array();


    }

    Function GetTotal_last($formular){
        $this->Amount_lastyear=0;
        $accounts=explode('+',$formular);
        foreach ($accounts as $code) {
           $this->Amount_lastyear = $this->AddTotals_last($code);
        }
        return $this->Amount_lastyear;
    }

    function AddTotals($account){
         $_SESSION['chart_table']['Amount'] += $_SESSION['chart_table']['Calc'][$account];
         return $_SESSION['chart_table']['Amount'];
    }

    Function savetotal_last($reportcode,$amount=0){
        $_SESSION['chart_table_Last']['Calc'][$reportcode] = $amount ;
    }

    Function GetTotal($formular){
        $this->Amount=0;
        $accounts=explode('+',$formular);
        foreach ($accounts as $code) {
           $this->Amount = $this->AddTotals($code);
        }
        return $this->Amount;
    }

    function AddTotals_last($account){
         $_SESSION['chart_table_Last']['Amount'] += $_SESSION['chart_table_Last']['Calc'][$account];
         return $_SESSION['chart_table_Last']['Amount'];
    }

    Function savetotal($reportcode,$amount=0){
        $_SESSION['chart_table']['Calc'][$reportcode] = $amount ;
    }



Function CalYearlydata(){
        $this->Reset();
        $this->COST_OF_SALES=0;
        $this->Profit=0;
        $this->YearPeriod = $_POST['Financial_Periods'];
        $this->ProjectSelected = $_POST['DimensionTwo'];

         $this->DB_Profit_loss_sales();
         $this->DB_Profit_loss_Purchase();
         $this->DB_Profit_loss_expenses();

         return $this->ResultDataSet;
    }



Function DB_Profit_loss_sales(){


global $db;

$procodedure = "
	SELECT
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where accountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' 
                and Docdate <=(select max([end_date])  from financialperiods  where periodno=".$this->YearPeriod."))
            else
               (select  sum(generalledger.amount * generalledger.[ExchangeRate])
               from generalledger where accountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' 
               and Docdate between (select min([start_date]) from financialperiods where periodno=".$this->YearPeriod.")
               and (select max([end_date]) from financialperiods where periodno=".$this->YearPeriod."))

           end as debit,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".$this->YearPeriod."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' and Docdate between (select min([start_date])
                from financialperiods  where periodno=".$this->YearPeriod.") and (select max([end_date])
                from financialperiods  where periodno=".$this->YearPeriod."))

         end as credit,

            case when acct.[balance_income]=0 then
                 (select sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' 
                  and Docdate <=(select max([end_date]) from financialperiods  where periodno=".($this->YearPeriod-12)."))
            else
                 (select  sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' and Docdate between (select min([start_date])
                  from financialperiods  where periodno=".($this->YearPeriod-12).") and (select max([end_date])
                  from financialperiods  where periodno=".($this->YearPeriod-12)."))

           end as debit_last,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".($this->YearPeriod-12)."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' and Docdate between (select min([start_date])
                from financialperiods  where periodno=".($this->YearPeriod-12).") and (select max([end_date])
                from financialperiods  where periodno=".($this->YearPeriod-12)."))

         end as credit_last,
        [Calculation]
	from acct
	where [ReportCode] between (select salesfrom from accountsetup limit 1) and (select salesto from accountsetup limit 1)
	group by
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]
	ORDER BY
	[ReportCode]";


       $ResultsX = DB_query($procodedure, $db);
       while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->ResultDataSet[$Row['ReportCode']]['ReportCode'] = $Row['ReportCode'];
           $this->ResultDataSet[$Row['ReportCode']]['accdesc'] = $Row['accdesc'];
           $this->ResultDataSet[$Row['ReportCode']]['accno'] = $Row['accno'];
           $this->ResultDataSet[$Row['ReportCode']]['ReportStyle'] = $Row['ReportStyle'];
           $this->ResultDataSet[$Row['ReportCode']]['Calculation'] = $Row['Calculation'];
           $this->ResultDataSet[$Row['ReportCode']]['amount'] = $AMOUNT;
           $this->Profit += $AMOUNT;

           $AMOUNT = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->ResultDataSet[$Row['ReportCode']]['Amount_lastyear'] = $AMOUNT;
           $this->Profit_last += $AMOUNT;
       }



}


Function DB_Profit_loss_Purchase(){


global $db;

$procodedure = "
	SELECT
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
            case when acct.[balance_income]=0 then
                    (select sum(generalledger.amount * generalledger.[ExchangeRate])
                    from generalledger where accountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' and Docdate <=(select max([end_date])
                    from financialperiods  where periodno=".$this->YearPeriod."))
            else
                    (select  sum(generalledger.amount * generalledger.[ExchangeRate])
                    from generalledger where accountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' and Docdate between (select min([start_date])
                    from financialperiods  where periodno=".$this->YearPeriod.") and (select max([end_date]) "
                  . "from financialperiods  where periodno=".$this->YearPeriod."))

           end as debit,

    case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".$this->YearPeriod."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' and Docdate between (select min([start_date])
                from financialperiods  where periodno=".$this->YearPeriod.") and (select max([end_date])
               from financialperiods  where periodno=".$this->YearPeriod."))

         end as credit,

            case when acct.[balance_income]=0 then
                 (select sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno and [dimension2]='".$this->ProjectSelected."'
                  and Docdate <=(select max([end_date])  from financialperiods  where periodno=".($this->YearPeriod-12)."))
            else
                 (select  sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' and Docdate between (select min([start_date])
                  from financialperiods  where periodno=".($this->YearPeriod-12).") and (select max([end_date])
                  from financialperiods  where periodno=".($this->YearPeriod-12)."))

           end as debit_Last,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".($this->YearPeriod-12)."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' and Docdate between (select min([start_date])
                from financialperiods  where periodno=".($this->YearPeriod-12).") and (select max([end_date])
                from financialperiods  where periodno=".($this->YearPeriod-12)."))

         end as credit_Last,
        [Calculation]
	from acct
	where [ReportCode]
        between (select purchasesfrom from accountsetup limit 1) and (select purchasesto from accountsetup limit 1)
	group by
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]
	ORDER BY
	[ReportCode]
";

  
       $ResultsX = DB_query($procodedure, $db);
       while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->ResultDataSet[$Row['ReportCode']]['ReportCode'] = $Row['ReportCode'];
           $this->ResultDataSet[$Row['ReportCode']]['accdesc'] = $Row['accdesc'];
           $this->ResultDataSet[$Row['ReportCode']]['accno'] = $Row['accno'];
           $this->ResultDataSet[$Row['ReportCode']]['ReportStyle'] = $Row['ReportStyle'];
           $this->ResultDataSet[$Row['ReportCode']]['Calculation'] = $Row['Calculation'];
           $this->ResultDataSet[$Row['ReportCode']]['amount'] = $AMOUNT;
           $this->COST_OF_SALES += $AMOUNT;

           $AMOUNT = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->ResultDataSet[$Row['ReportCode']]['Amount_lastyear'] = $AMOUNT;
           $this->COST_OF_SALES_last += $AMOUNT;
       }
       
           $this->ResultDataSet['cos']['ReportCode'] =  _('COS');
           $this->ResultDataSet['cos']['accdesc'] = _('Cost Of Sales');
           $this->ResultDataSet['cos']['ReportStyle'] = 2;
           $this->ResultDataSet['cos']['amount'] = $this->COST_OF_SALES ;
           $this->ResultDataSet['cos']['Amount_lastyear']=$this->COST_OF_SALES_last;
}



Function DB_Profit_loss_expenses(){


global $db;

$procodedure = "
	SELECT
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
            case when acct.[balance_income]=0 then
                    (select sum(generalledger.amount * generalledger.[ExchangeRate])
                    from generalledger where accountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' and Docdate <=(select max([end_date])
                    from financialperiods  where periodno=".$this->YearPeriod.") )
            else
                    (select  sum(generalledger.amount * generalledger.[ExchangeRate])
                    from generalledger where accountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' and Docdate between (select min([start_date])
                    from financialperiods  where periodno=".$this->YearPeriod.") and (select max([end_date])
                   from financialperiods  where periodno=".$this->YearPeriod."))

           end as debit,

    case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' and Docdate <=(select max([end_date]) from financialperiods
                where periodno=".$this->YearPeriod."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' and Docdate between (select min([start_date])
                from financialperiods  where periodno=".$this->YearPeriod.") and (select max([end_date])
               from financialperiods  where periodno=".$this->YearPeriod."))

         end as credit,

            case when acct.[balance_income]=0 then
                 (select sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno and [dimension2]='".$this->ProjectSelected."'
                  and Docdate <=(select max([end_date])  from financialperiods  where periodno=".($this->YearPeriod-12)."))
            else
                 (select  sum(generalledger.amount * generalledger.[ExchangeRate])
                  from generalledger where accountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' and Docdate between (select min([start_date])
                  from financialperiods  where periodno=".($this->YearPeriod-12).") and (select max([end_date])
                  from financialperiods  where periodno=".($this->YearPeriod-12)."))

           end as debit_Last,
            case when acct.[balance_income]=0 then
                (select sum(generalledger.amount * generalledger.[ExchangeRate])
                from generalledger where balaccountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' and Docdate <=(select max([end_date])
                from financialperiods  where periodno=".($this->YearPeriod-12)."))
            else
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger
                where balaccountcode=acct.accno and [dimension2]='".$this->ProjectSelected."' and Docdate between (select min([start_date])
                from financialperiods  where periodno=".($this->YearPeriod-12).") and (select max([end_date])
                from financialperiods  where periodno=".($this->YearPeriod-12)."))

         end as credit_Last,
        [Calculation]
	from acct
	where [ReportCode] between (select expensefrom from accountsetup limit 1) and (select expenseto from accountsetup limit 1)
	group by
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]
	ORDER BY
	[ReportCode]";


       $ResultsX = DB_query($procodedure, $db);
       while($Row=DB_fetch_array($ResultsX)){
            $AMOUNT = ($Row['debit'] - $Row['credit']);
           $this->ResultDataSet[$Row['ReportCode']]['ReportCode'] = $Row['ReportCode'];
           $this->ResultDataSet[$Row['ReportCode']]['accdesc'] = $Row['accdesc'];
           $this->ResultDataSet[$Row['ReportCode']]['accno'] = $Row['accno'];
           $this->ResultDataSet[$Row['ReportCode']]['ReportStyle'] = $Row['ReportStyle'];
           $this->ResultDataSet[$Row['ReportCode']]['Calculation'] = $Row['Calculation'];
           $this->ResultDataSet[$Row['ReportCode']]['amount'] = $AMOUNT;
           $this->Profit += $AMOUNT;

           $AMOUNT = ($Row['debit_Last'] - $Row['credit_Last']);
           $this->ResultDataSet[$Row['ReportCode']]['Amount_lastyear'] = $AMOUNT;
           $this->Profit_last += $AMOUNT ;
       }

           $this->ResultDataSet['netprofit']['ReportCode'] =  _('netprofit');
           $this->ResultDataSet['netprofit']['accdesc'] = _('Net Profit');
           $this->ResultDataSet['netprofit']['ReportStyle'] = 2;
           $this->ResultDataSet['netprofit']['amount'] =$this->Profit + $this->COST_OF_SALES ;
           $this->ResultDataSet['netprofit']['Amount_lastyear'] =$this->Profit_last + $this->COST_OF_SALES_last ;
}


}

class FinancialPeriods {

    var $SelectObject='';

    Function __construct() {
        Global $db;

        $this->SelectObject='<tr><td>Select Year</td><td><select name="Financial_Periods">';
        $ResultIndex=DB_query("Select [periodno],MAX([end_date]) as year from [financialperiods] Group by [periodno] order by [periodno] desc", $db);
        while($row=DB_fetch_array($ResultIndex)){

            if(isset($_POST['Financial_Periods'])){
                 $this->SelectObject .='<option value="'.$row['periodno'].'" '.(($_POST['Financial_Periods']==trim($row['periodno']))?'selected="selected"':'').'  >'.$row['year'].'</option>';
            }else{
                  $this->SelectObject .='<option value="'.$row['periodno'].'"   >'.$row['year'].'</option>';
            }
        }

        $this->SelectObject .='</select></td></tr>';
    }

    function Get(){
        echo $this->SelectObject;
    }

}

class MonthlyPeriods {

    var $SelectObject='';

        Function __construct() {
                Global $db;
                $this->SelectObject='<tr><td>Select Ending Month</td><td><select id="TaskActivityDate" name="Financial_Periods"><option></option>';
                $ResultIndex=DB_System("Select [periodno],[lastdate_in_period] as year "
                        . "from [periods]where [lastdate_in_period] < DATE_ADD(sysdate(),interval 1 MONTH)  "
                        . " Group by [periodno],[lastdate_in_period]"
                        . " order by [periodno] desc", $db);
                while($row=DB_fetch_array($ResultIndex)){

                    if(isset($_POST['Financial_Periods'])){
                        $this->SelectObject .='<option value="'.$row['periodno'].'" '.(($_POST['Financial_Periods']==trim($row['periodno']))?'selected="selected"':'').'  >'.ConvertSQLDate($row['year']).'</option>';
                    } else {
                        $this->SelectObject .='<option value="'.$row['periodno'].'">'.ConvertSQLDate($row['year']).'</option>';
                    }

                }
        
                $this->SelectObject .='</select></td></tr>';
             }
            function Get(){
                echo $this->SelectObject;
            }
        

    }

class SaveReceipt extends Showbankaccounts {

    Var $bankcode;
    Var $FormVariables=array();
    var $periodNo;
    var $sqlarray=array();
    var $journal;
    var $invref;
    var $acctfolio;
    Var $Receipt_Journal;
    Var $JournalArray=array();
    Var $date;
    var $documentno;
    var $reference;
    var $CustomerID;
    var $CustomerName;
    Var $currencycode;
    var $totalamount;
    Var $postinggroups;
    Var $checkbalance=0;

    function GetForm(){
        Global $db;

            $this->FormVariables= $_POST;
            foreach ($this->FormVariables as $key=>$value) {

                if($key=='date'){
                    $this->date = ConvertSQLDate($value);
                    $this->periodNo= GetPeriod($value,$db);
                }
                if($key=='Bank_Code'){
                    $this->bankcode=$value;$this->GetBankDetails($this->bankcode); }

                if($key=='documentno'){ $this->documentno = $value;}
                if($key=='reference'){ $this->reference = $value;}
                if($key=='CustomerID'){ $this->CustomerID = $value; $this->GetCustomer($this->CustomerID);}
                if($key=='CustomerName'){ $this->CustomerName = $value; }
                if($key=='currencycode'){ $this->currencycode = $value; }
                if($key=='totalamount'){ $this->totalamount = $value;}

                if($key=='topay'){
                    $this->checkbalance=0;
                    foreach ($value as $journal => $amount) {$this->GetPaidinvoices($journal,$amount);}}}

            $this->GetCustomer($this->CustomerID);
            $this->PostToAccounts();
    }

    function GetPaidinvoices($journal,$amount=0){
        if($amount>0){
                $this->checkbalance += $amount;
                $this->JournalArray[]=SPRINTF("insert into [receiptsallocation]
                        ([itemcode],[date],[invoiceno],[journalno],[doctype],[receiptno],[amount],[receiptjournal])
                         (Select [Accountno],[date],[Documentno],[JournalNo],[Documenttype],'%s',%f,'%s'
                         from [customerstatement] where [JournalNo]='%s') ",
                        $this->documentno,
                        (0-$amount),
                        $this->Receipt_Journal,
                        $journal);

        }
    }

    function GetCustomer($itemcode){
        global $db;

        $ResultIndex = DB_query("select [postinggroups].[debtorsaccount],[customerposting] FROM "
                . "[debtors] join [postinggroups] on [customerposting]=[postinggroups].[code] "
                . "where itemcode='".$itemcode."'", $db);
        $rows = DB_fetch_row($ResultIndex);
        $this->postinggroups = $rows[0];
    }

    function PostToAccounts(){
        $this->totalamount= $this->checkbalance;

          $this->JournalArray[] =sprintf("INSERT INTO [customerstatement]
                                ([Date]
                                ,[Documentno]
                                ,[Documenttype]
                                ,[Accountno]
                                ,[Grossamount]
                                ,[JournalNo]
                                ,[Currency])
                              VALUES
                                   ('%s'
                                    ,'%s'
                                    ,'%s'
                                    ,'%s'
                                    ,%f
                                    ,'%s'
                                    ,'%s'
                                    ) "
                                    ,$this->date
                                    ,$this->documentno
                                    ,'12'
                                    ,$this->CustomerID
                                    ,0-$this->totalamount
                                    ,$this->Receipt_Journal
                                    ,$this->BankArray['currency']);


        $this->JournalArray[]=sprintf("INSERT INTO [receiptheader]
                                        ([docno]
                                        ,[date]
                                        ,[itemcode]
                                        ,[externalref]
                                        ,[narrative]
                                        ,[amount]
                                        ,[journal]
                                        ,currency)
                                  VALUES
                                        ('%s'
                                        ,'%s'
                                        ,'%s'
                                        ,'%s'
                                        ,'%s'
                                        ,%f
                                        ,'%s'
                                        ,'%s')",
                            $this->documentno,
                            $this->date,
                            $this->CustomerID,
                            $this->reference,
                            'Paid in '.$this->BankArray['currency'] ,
                            $this->totalamount,
                            $this->Receipt_Journal,
                            $this->BankArray['currency']);


        $this->JournalArray[]=SPRINTF("INSERT INTO [banktransactions]
                                    ([bankcode]
                                    ,[DocDate]
                                    ,[doctype]
                                    ,[DocumentNo]
                                    ,[TransType]
                                    ,[itemcode]
                                    ,[journal]
                                    ,[amount]
                                    ,[narrative]
                                    ,[exchangerate]
                                    ,[cleared]
                                    ,[reconciled])
                              VALUES
                                    ('%s'
                                    ,'%s'
                                    ,12
                                    ,'%s'
                                    ,'DR'
                                    ,'%s'
                                    ,'%s'
                                    ,%f
                                    ,'%s'
                                    ,%f
                                    ,NULL
                                    ,NULL
                                    ) ",$this->bankcode,
                                        $this->date,
                                        $this->documentno,
                                        $this->CustomerID,
                                        $this->Receipt_Journal,
                                        $this->totalamount,
                                        (trim($this->CustomerName).'  '.trim($this->reference)),
                                        $this->BankArray['rate']);



        $this->JournalArray[]= sprintf("Insert into [debtorsledger]
                ([date]
               ,[details]
               ,[flag]
               ,[invref]
               ,[acctfolio]
               ,[amount]
               ,[pamount]
               ,[curr_cod]
               ,[i_n_t]
               ,[journal]
               ,[typ]
               ,[vatamt]
               ,[systypes_1]
               ,[ledger]
               ,period)
               values
               ('%s','%s','%s','%s','%s',%f,%f,'%s','%s','%s','%s',%f,'%s','%s','%s')",
                $this->date,
                ('Receipt  '.trim($this->reference)),
                "DR",
                $this->documentno,
                $this->CustomerID,
                (0-$this->totalamount),
                0,
                $this->currencycode,
                "R",
                $this->Receipt_Journal,
                "_W",
                0,
                12,
                $this->postinggroups,
                $this->periodNo);



             $this->JournalArray[]=SPRINTF("Insert into [generalledger]
                                    ([journalno]
                                    ,[Docdate]
                                    ,[period]
                                    ,[DocumentNo]
                                    ,[DocumentType]
                                    ,[accountcode]
                                    ,[balaccountcode]
                                    ,[VATaccountcode]
                                    ,[amount]
                                    ,[VATamount]
                                    ,[currencycode]
                                    ,[ExchangeRate]
                                    ,[cutomercode]
                                    ,[narration])
                                    values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s') ",
              $this->Receipt_Journal,$this->date,$this->periodNo,$this->documentno,12,
              $this->BankArray['PostingGroup'],$this->postinggroups,"",
              $this->totalamount,0,$this->currencycode,$this->BankArray['rate'],
              $this->CustomerID,(trim($this->CustomerName).'  '.trim($this->reference)));

    }

}

class SavePrepaymentReceipt extends Showbankaccounts{
    Var $bankcode;
    Var $FormVariables=array();
    var $periodNo;
    var $sqlarray=array();
    var $journal;
    var $invref;
    var $acctfolio;
    Var $Receipt_Journal;
    Var $JournalArray=array();
    Var $date;
    var $documentno;
    var $reference;
    var $CustomerID;
    var $CustomerName;
    Var $currencycode;
    var $totalamount;
    Var $postinggroups;
    Var $checkbalance=0;
    var $FunctionalCurrency;
    Var $FromRate;
    var $Torate;


    function __construct() {
        $this->FunctionalCurrency = $_SESSION['CompanyRecord']['currencydefault'];
     }

    function getrates($fromcurrency,$tocurrency){

        $this->FromRate =$this->ExtractRate($fromcurrency);
        $this->Torate =$this->ExtractRate($tocurrency);

        return ($this->Torate/$this->FromRate);
    }

    function ExtractRate($currbrieve){
        global $db;

        $SQLOldRate = "SELECT rate FROM currencies
        WHERE currabrev = '" .$currbrieve. "'";
        $ResultOldRate = DB_query($SQLOldRate, $db);
        $myrow = DB_fetch_row($ResultOldRate);
        $OldRate = $myrow[0];

        return $OldRate;
    }

    function GetForm(){
        Global $db;

            $this->Receipt_Journal= GetNextTransNo(0,$db);

            $this->FormVariables= $_POST;
            foreach ($this->FormVariables as $key=>$value) {

                if($key=='date'){
                    $this->date = ConvertSQLDate($value);
                    $this->periodNo= GetPeriod($value,$db);
                }
                if($key=='Bank_Code'){
                    $this->bankcode=$value;
                    $this->GetBankDetails($this->bankcode);
                }

                if($key=='documentno'){
                $this->documentno = $value;

                }
                if($key=='reference'){
                $this->reference = $value;

                }
                if($key=='CustomerID'){
                $this->CustomerID = $value;
                $this->GetCustomer($this->CustomerID);
                }
                if($key=='CustomerName'){
                $this->CustomerName = $value;

                }
                if($key=='currencycode'){
                $this->currencycode = $value;

                }
                if($key=='totalamount'){
                $this->totalamount = $value;

                }

            }

            $this->GetCustomer($this->CustomerID);
            $this->PostToAccounts();
    }

    function GetCustomer($itemcode){
        global $db;

        $ResultIndex = DB_query("select [postinggroups].[debtorsaccount],[customerposting] FROM "
                . "[debtors] join [postinggroups] on [customerposting]=[postinggroups].[code] "
                . " where itemcode='".$itemcode."'", $db);
        $rows = DB_fetch_row($ResultIndex);
        $this->postinggroups = $rows[0];
    }

    function PostToAccounts(){


          $this->JournalArray[] =sprintf("INSERT INTO [customerstatement]
                                ([Date]
                                ,[Documentno]
                                ,[Documenttype]
                                ,[Accountno]
                                ,[Grossamount]
                                ,[JournalNo]
                                ,[Currency])
                              VALUES
                                   ('%s'
                                    ,'%s'
                                    ,'%s'
                                    ,'%s'
                                    ,%f
                                    ,'%s'
                                    ,'%s'
                                    ) "
                                    ,$this->date
                                    ,$this->documentno
                                    ,'12'
                                    ,$this->CustomerID
                                    ,0-$this->totalamount
                                    ,$this->Receipt_Journal
                                    ,$this->BankArray['currency']);

        $this->JournalArray[]=sprintf("INSERT INTO [receiptheader]
                                        ([docno]
                                        ,[date]
                                        ,[itemcode]
                                        ,[externalref]
                                        ,[narrative]
                                        ,[amount]
                                        ,[journal]
                                        ,currency)
                                  VALUES
                                        ('%s'
                                        ,'%s'
                                        ,'%s'
                                        ,'%s'
                                        ,'%s'
                                        ,%f
                                        ,'%s'
                                        ,'%s')",
                                        $this->documentno,
                                        $this->date,
                                        $this->CustomerID,
                                        $this->reference,
                                        'Pain in :'.$this->BankArray['currency'] ,
                                        $this->totalamount,
                                        $this->Receipt_Journal,
                                        $this->BankArray['currency']);



        $this->JournalArray[]=SPRINTF("INSERT INTO [banktransactions]
                                    ([bankcode]
                                    ,[DocDate]
                                    ,[doctype]
                                    ,[DocumentNo]
                                    ,[TransType]
                                    ,[itemcode]
                                    ,[journal]
                                    ,[amount]
                                    ,[narrative]
                                    ,[exchangerate])
                              VALUES
                                    ('%s'
                                    ,'%s'
                                    ,12
                                    ,'%s'
                                    ,'DR'
                                    ,'%s'
                                    ,'%s'
                                    ,%f
                                    ,'%s'
                                    ,%f
                                    ) ",$this->bankcode,
                                        $this->date,
                                        $this->documentno,
                                        $this->CustomerID,
                                        $this->Receipt_Journal,
                                        $this->totalamount,
                                        (trim($this->CustomerName).'  '.trim($this->reference)),
                                        $this->BankArray['rate']);



        $this->JournalArray[]= sprintf("Insert into [debtorsledger]
                ([date]
               ,[details]
               ,[flag]
               ,[invref]
               ,[acctfolio]
               ,[amount]
               ,[pamount]
               ,[curr_cod]
               ,[i_n_t]
               ,[journal]
               ,[typ]
               ,[vatamt]
               ,[systypes_1]
               ,[ledger]
               ,period)
               values
               ('%s','%s','%s','%s','%s',%f,%f,'%s','%s','%s','%s',%f,'%s','%s','%s')",
                $this->date,
                ('Receipt  '.trim($this->reference)),
                "DR",
                $this->documentno,
                $this->CustomerID,
                ((0-$this->totalamount) * $this->getrates($this->currencycode,$this->BankArray['currency'])),
                0,
                $this->BankArray['currency'],
                "R",
                $this->Receipt_Journal,
                "_W",
                0,
                12,
                $this->postinggroups,
                $this->periodNo);



             $this->JournalArray[]=SPRINTF("Insert into [generalledger]
                                    ([journalno]
                                    ,[Docdate]
                                    ,[period]
                                    ,[DocumentNo]
                                    ,[DocumentType]
                                    ,[accountcode]
                                    ,[balaccountcode]
                                    ,[VATaccountcode]
                                    ,[amount]
                                    ,[VATamount]
                                    ,[currencycode]
                                    ,[ExchangeRate]
                                    ,[cutomercode]
                                    ,[narration])
                                    values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s') ",
              $this->Receipt_Journal,$this->date,$this->periodNo,$this->documentno,12,
              $this->BankArray['PostingGroup'],$this->postinggroups,"",
              $this->totalamount,0,$this->currencycode,$this->BankArray['rate'],
              $this->CustomerID,(trim($this->CustomerName).'  '.trim($this->reference)));

    }


}

class CalculateExchange{
    var $FunctionalCurrency;
    Var $FromRate;
    var $Torate;

    function __construct() {
       $this->FunctionalCurrency = $_SESSION['CompanyRecord']['currencydefault'];
     }

    function get($fromcurrency,$tocurrency){
        $this->FromRate =$this->ExtractRate($fromcurrency);
        $this->Torate =$this->ExtractRate($tocurrency);

        return ($this->FromRate/$this->Torate);

    }


    function ExtractRate($currbrieve){
        global $db;

        $SQLOldRate = "SELECT rate FROM currencies
        WHERE currabrev = '" .$currbrieve. "'";
        $ResultOldRate = DB_query($SQLOldRate, $db);
        $myrow = DB_fetch_row($ResultOldRate);
        $OldRate = $myrow[0];

        return $OldRate;
    }
}

class Savepaymentvoucher {
    Var $bankcode;
    Var $FormVariables=array();
    var $periodNo;
    var $sqlarray=array();
    var $journal;
    var $invref;
    var $acctfolio;
    Var $Receipt_Journal;
    Var $JournalArray= array();
    Var $date;
    var $documentno;
    var $reference;
    var $CustomerID;
    var $CustomerName;
    Var $currencycode;
    var $totalamount;
    Var $postinggroups;
    Var $checkbalance=0;
    Var $DimensionsBudget;
    VAR $DocNo=0;
    var $BankArray = array();

    function __construct() {
        $this->DimensionsBudget = new DimensionsBudget();
    }

    function GetFormUpdate(){
        $this->JournalArray[]="Delete from [paymentvoucherline] where [journal]='".$_POST['edit']."'";
        $this->JournalArray[]="Delete from [paymentvoucherheader] where [journal]='".$_POST['edit']."'";
        $this->GetForm();
    }


    function GetBankDetails($accountCode){
         Global $db;
        $resultindex=DB_query("SELECT
            [accountcode],[bankName],[bankaccounts].[currency],[lastreconcileddate]
           ,[AccountNo],[BranchCode],[BranchName],[lastreconbalance]
           ,[lastChequeno],[PostingGroup],[currencies].[rate],[StatementNo]
            FROM [bankaccounts]
            join [currencies] on [bankaccounts].[currency]=[currencies].[currabrev]
            where [accountcode]='".$accountCode."'", $db);

        $row=DB_fetch_row($resultindex);
        $this->BankArray['accountcode'] = $row[0];
        $this->BankArray['bankName']    = $row[1];
        $this->BankArray['currency']    = $row[2];
        $this->BankArray['lastreconcileddate'] = $row[3];
        $this->BankArray['AccountNo'] = $row[4];
        $this->BankArray['BranchCode']= $row[5];
        $this->BankArray['BranchName']= $row[6];
        $this->BankArray['lastreconbalance'] = $row[7];
        $this->BankArray['lastChequeno'] = $row[8];
        $this->BankArray['PostingGroup'] = $row[9];
        $this->BankArray['rate'] = $row[10];
        $this->BankArray['StatementNo'] = $row[11];
        return $this->BankArray;
    }



    function GetForm(){
        Global $db;

            $this->FormVariables = $_POST;
            foreach ($this->FormVariables as $key=>$value) {

                if($key=='date'){
                    $this->date = ConvertSQLDate($value);
                    $this->periodNo= GetPeriod($value,$db);
                }

                if($key=='Bank_Code'){
                    $this->bankcode=$value;
                    $this->GetBankDetails($this->bankcode);
                }

                if($key=='documentno'){
                $this->documentno = $value;

                }
                if($key=='reference'){
                $this->reference = $value;

                }

                if($key=='VendorID'){
                    $this->CustomerID = $value;
                }

                if($key=='VendorName'){
                     $this->CustomerName = $value;

                }
                if($key=='currencycode'){
                     $this->currencycode = $value;

                }

                if($key=='totalamount'){
                   $this->totalamount = $value;
                }


                if($key=='topay'){
                         foreach ($value as $journalno => $amountval) {

                                if($amountval>0){
                                     $this->GetBudgetsForAll($journalno,$amountval);
                                  }

                          }

                   }



            }

            $this->PVheader();

    }



    function PVheader(){

            $this->JournalArray[] = sprintf("INSERT INTO [paymentvoucherheader]
                                        ([docno]
                                        ,[date]
                                        ,[itemcode]
                                        ,[externalref]
                                        ,[narrative]
                                        ,[amount]
                                        ,[journal]
                                        ,[currency]
                                        ,[status])
                                  VALUES
                                        ('%s'
                                        ,'%s'
                                        ,'%s'
                                        ,'%s'
                                        ,'%s'
                                        ,%f
                                        ,'%s'
                                        ,'%s'
                                        ,0)",
                                        $this->documentno ,
                                        $this->date,
                                        $this->CustomerID,
                                        $this->reference,
                                        'Paid in '.$this->BankArray['currency'] ,
                                        $this->totalamount,
                                        $this->Receipt_Journal,
                                        $this->BankArray['currency']);

    }



    function GetBudgetsForAll($journal,$amount1){
        global $db;

        $SQL = SPRINTF("SELECT
            [Grossamount],
            [Dimension_One] ,
            [Dimension_Two] ,
            [currencies].[rate]
            FROM [supplierstatement] left join [currencies]
            on [currencies].[currabrev]=[supplierstatement].Currency
            where [Accountno]='%s' and [JournalNo]='%s' ",  $this->CustomerID,$journal);

        $ResultIndex = DB_query($SQL,$db);
        $INVOICES = DB_fetch_row($ResultIndex);

        $amount = ($amount1 * ($INVOICES[3]/$this->BankArray['rate']));

        $VOTEARRAY = $this->DimensionsBudget->Calculate($INVOICES[1]);
        $budgetbalance =($VOTEARRAY['Budget'] - ($VOTEARRAY['Committement']+ $VOTEARRAY['Expensed'] + $amount));


        $this->JournalArray[] = sprintf("INSERT INTO [paymentvoucherline]
                                        ([docno]
                                        ,[itemcode]
                                        ,[narrative]
                                        ,[amount]
                                        ,[journal]
                                        ,[invoice_journal]
                                        ,[Budget]
                                        ,[Committed]
                                        ,[Expensed]
                                        ,[Balance]
                                        ,[Dimension_1]
                                        ,[Dimension_2])
                                  VALUES
                ('%s','%s','%s',%f,'%s','%s',%f,%f,%f,%f,'%s','%s')",
                                        $this->documentno ,
                                        $this->CustomerID,
                                        'Paid in '.$this->BankArray['currency'] ,
                                        $amount,
                                        $this->Receipt_Journal,
                                        $journal,
                                        $VOTEARRAY['Budget'],
                                        $VOTEARRAY['Committement'],
                                        $VOTEARRAY['Expensed'],
                                        $budgetbalance,
                                        $INVOICES[1],
                                        $INVOICES[2]);





    }


}


?>
