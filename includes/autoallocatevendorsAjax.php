<?php
   /* $Id: ConnectDB_mssql.inc 6310 2014-08-06 14:41:50Z Jonathan Kiranga $ */
define ('LIKE','LIKE');
session_write_close(); //in case a previous session is not closed
session_name('smartlimserp');
session_start();
include( '../config.php');

global $db,$database;
// Make sure it IS global, regardless of our context
      
$db = mysqli_connect($host , $DBUser, $DBPassword,$_SESSION['DatabaseName'], $mysqlport);
$result=DB_query('SET sql_mode = ANSI', $db);
//this statement sets the charset to be used for sending data to and from the db server
//if not set, both mysqli server and mysqli client/library may assume otherwise
mysqli_set_charset($db, 'utf8');



function DB_query ($SQL, $Conn, $ErrorMessage='', $DebugMessage= '', $Transaction=false, $TrapErrors=true){

	global $debug;
	global $PathPrefix;
        
        $SQL = RemoveBox($SQL);
   	$result= mysqli_query($Conn,$SQL);


	return $result;

}

function DB_System ($SQL, $Conn, $ErrorMessage='', $DebugMessage= '', $Transaction=false, $TrapErrors=true){
    global $debug;
    global $PathPrefix;
        
        $SQL = RemoveBox($SQL);
  	$result= mysqli_query($Conn,$SQL);

	
  return $result;

}

function DB_prepare($SQL, $Conn){
    $SQL = RemoveBox($SQL);
    $stmt = $Conn -> prepare($SQL);
  return $stmt -> execute();

}

function DB_fetch_row ($ResultIndex) {

	$RowPointer=mysqli_fetch_row($ResultIndex);
	Return $RowPointer;
}

function DB_fetch_assoc ($ResultIndex) {

	$RowPointer=mysqli_fetch_assoc($ResultIndex);
	Return $RowPointer;
}

function DB_fetch_array ($ResultIndex) {

	$RowPointer=mysqli_fetch_array($ResultIndex);
	Return $RowPointer;
}

function DB_data_seek ($ResultIndex,$Record) {
	mysqli_data_seek($ResultIndex,$Record);
}

function DB_free_result ($ResultIndex){
    if (is_resource($ResultIndex)) {
    	mysqli_free_result($ResultIndex);
    }
}

function DB_num_rows ($ResultIndex){
	return mysqli_num_rows($ResultIndex);
}

function DB_affected_rows($ResultIndex){

	global $db;
	return mysqli_affected_rows($db);

}

function DB_error_no ($Conn){
	return mysqli_errno($Conn);
}

function DB_error_msg($Conn){
	return mysqli_error($Conn);
}

function DB_Last_Insert_ID($Conn, $Table, $FieldName){
 
	if (isset($_SESSION['LastInsertId'])) {
		$Last_Insert_ID = $_SESSION['LastInsertId'];
	} else {
		$Last_Insert_ID = 0;
	}
 
	return $Last_Insert_ID;
}

function DB_escape_string($String){
	global $db;
	return mysqli_real_escape_string($db, htmlspecialchars($String, ENT_COMPAT,'utf-8', false));
}

function DB_show_tables($Conn){
	$Result = DB_query('SHOW TABLES',$Conn);
	Return $Result;
}

function DB_show_fields($TableName, $Conn){
	$Result = DB_query("DESCRIBE $TableName",$Conn);
	Return $Result;
}

function interval( $val, $Inter ){
		global $dbtype;
		return "\n".'interval ' . $val . ' '. $Inter."\n";
}

function DB_Maintenance($Conn){

	prnMsg(_('The system has just run the regular database administration and optimisation routine.'),'info');

	$TablesResult = DB_query('SHOW TABLES',$Conn);
	while ($myrow = DB_fetch_row($TablesResult)){
		$Result = DB_query('OPTIMIZE TABLE ' . $myrow[0],$Conn);
	}

	$Result = DB_query("UPDATE config
				SET confvalue='" . Date('Y-m-d') . "'
				WHERE confname='DB_Maintenance_LastRun'",
				$Conn);
}

function DB_Txn_Begin($Conn){
	mysqli_query($Conn,'SET autocommit=0');
	mysqli_query($Conn,'START TRANSACTION');
}

function DB_Txn_Commit($Conn){
	mysqli_query($Conn,'COMMIT');
	mysqli_query($Conn,'SET autocommit=1');
}

function DB_Txn_Rollback($Conn){
	mysqli_query($Conn,'ROLLBACK');
}

function DB_IgnoreForeignKeys($Conn){
	mysqli_query($Conn,'SET FOREIGN_KEY_CHECKS=0');
}

function DB_ReinstateForeignKeys($Conn){
	mysqli_query($Conn, 'SET FOREIGN_KEY_CHECKS=1');
}

Function RemoveBox($sql){
    $leftbrace = str_replace('[',' ',$sql);
    $allbraces = str_replace(']',' ',$leftbrace);

    return $allbraces;
}
 

   

$sql="SELECT [coycode],[coyname],[PIN],[vat] ,[regoffice1],[regoffice2] ,[regoffice3]  ,[regoffice4] ,[regoffice5] ,[regoffice6] ,[telephone],[fax] ,[email],[currencydefault], currencies.decimalplaces
FROM companies  INNER JOIN currencies ON companies.currencydefault=currencies.currabrev  WHERE coycode=1";
$ReadCoyResult = DB_query($sql,$db);
if (DB_num_rows($ReadCoyResult)>0) {
    $_SESSION['CompanyRecord'] =  DB_fetch_array($ReadCoyResult);
}

if(isset($_GET['autoallocatevendors'])){
    autoallocatevendors($_GET['autoallocatevendors']);
    echo 'Vendors payment now being allocated';
}elseif(isset($_POST['autoallocatevendors'])){
    autoallocatevendors($_POST['autoallocatevendors']);
    echo 'Vendors payment now being allocated';
}


      
if(isset($_GET['autoallocatedebtors'])){
       echo 'Debtors payment now being allocated';
    autoallocatedebtors($_GET['autoallocatedebtors']);
}elseif(isset($_POST['autoallocatedebtors'])){
       echo 'Debtors payment now being allocated';
    autoallocatedebtors($_POST['autoallocatedebtors']);
}


function autoallocatevendors($accountno){
    global $db;
    $SQL="SELECT (supplierstatement.Grossamount-ifnull((select sum(amount) FROM 
        paymentsallocation where receiptjournal=supplierstatement.JournalNo 
        and itemcode=supplierstatement.Accountno),0)) as AMOUNT2, JournalNo,  Documentno  
          FROM supplierstatement 
          where Accountno='".$accountno."' 
           and (supplierstatement.Grossamount-ifnull((select sum(amount) FROM 
           paymentsallocation where receiptjournal=supplierstatement.JournalNo 
           and itemcode=supplierstatement.Accountno),0))>0
           Order by Date asc";
    $ResultIndex=DB_query($SQL,$db);
   while($rows=DB_fetch_array($ResultIndex)){
       $receipts_cursor[$rows['JournalNo']]=array(
           "amountreceipt"=>$rows['AMOUNT2'],
           "journalreceipt"=>$rows['JournalNo'],
           "Receiptno"=>$rows['Documentno']);
   }
   
   
   $SQL="SELECT (supplierstatement.Grossamount+ifnull((SELECT sum(amount) FROM 
           paymentsallocation where journalno=supplierstatement.JournalNo 
           and itemcode=supplierstatement.Accountno),0)) as amount2,JournalNo
	   FROM supplierstatement where Accountno='".$accountno."'
	   and (supplierstatement.Grossamount+ifnull((SELECT sum(amount) 
           FROM paymentsallocation where journalno=supplierstatement.JournalNo 
           and itemcode=supplierstatement.Accountno),0))<0
	   Order by Date asc";
    $ResultIndex=DB_query($SQL,$db);
   while($rows=DB_fetch_array($ResultIndex)){
       $invoices_cursor[$rows['JournalNo']]=array(
           "amountinvoice"=>$rows['amount2'],
           "journalnoinv"=>$rows['JournalNo']);
   }
   
           
   $amountleft=0; $amountreceipt=0;
   
   if(is_array($receipts_cursor)){
        foreach ($receipts_cursor as $key=>$receipts) {
            $amountreceipt=(float)$receipts['amountreceipt'];

            if(is_array($invoices_cursor)){
                foreach ($invoices_cursor as $key => $invoices) {
                   if($amountreceipt >0){
                    $amountinvoice=(float)$invoices['amountinvoice'];
                        if(($amountreceipt+$amountinvoice)>0){ 
			    $sql=SPRINTF("insert into paymentsallocation
                             (itemcode,date,invoiceno,journalno,doctype,receiptno,amount,receiptjournal) 
                             (Select trim(Accountno),Date,trim(Documentno),trim(JournalNo),Documenttype,'%s',(%f * -1),'%s' 
                             from supplierstatement where JournalNo='%s') ", $receipts['Receiptno'],$invoices['amountinvoice'],
                             $receipts['journalreceipt'],$invoices['journalnoinv']);
                            DB_System($sql,$db);
                             
                             $invoices_cursor[$key]['amountinvoice']+=(0-$invoices['amountinvoice']);
                             $amountleft = $amountreceipt+$amountinvoice;
                             $amountreceipt = $amountleft;
			   }else {
				 
                            $sql=SPRINTF("insert into paymentsallocation
                            (itemcode,date,invoiceno,journalno,doctype,receiptno,amount,receiptjournal) 
                            (Select trim(Accountno),Date,trim(Documentno),trim(JournalNo),Documenttype,'%s',%f,'%s'
                            from supplierstatement where JournalNo='%s') ", $receipts['Receiptno'],$receipts['amountreceipt'],
                            $receipts['journalreceipt'],$invoices['journalnoinv']); 
                            DB_System($sql,$db);
                            
                            $invoices_cursor[$key]['amountinvoice']+=$receipts['amountreceipt'];
                            $amountreceipt=0;
                        }
                     }
                   
	          }
            }

        }
   }
   
   
}

function autoallocatedebtors($accountno){
    global $db;
     
   $SQL="SELECT  
   (grossamount-IFNULL((select sum(amount) FROM receiptsallocation where receiptjournal = customerstatement.JournalNo and itemcode=customerstatement.Accountno),0)) as AMOUNT2, 
   JournalNo,
   Documentno,
   Date
   FROM customerstatement 
    where Accountno='".$accountno."' 
    and (grossamount-IFNULL((select sum(amount) FROM receiptsallocation where receiptjournal = customerstatement.JournalNo and itemcode=customerstatement.Accountno),0))<0
    Order by Date asc";
   
   unset($receipts_cursor);
   
   $ResultIndex=DB_query($SQL,$db);
   while($rows=DB_fetch_array($ResultIndex)){
       $receipts_cursor[$rows['JournalNo']]=array(
           "amountreceipt"=>(float)$rows['AMOUNT2'],
           "journalreceipt"=>$rows['JournalNo'],
           "Receiptno"=>$rows['Documentno'],
           "Receiptdate"=>$rows['Date']);
   }
     DB_free_result($ResultIndex);
     
     $SQL="SELECT (grossamount+IFNULL((select sum(amount) FROM receiptsallocation where journalno = customerstatement.JournalNo and itemcode=customerstatement.Accountno),0) ) as amount2, 
                JournalNo, grossamount FROM  customerstatement where Accountno='".$accountno."'
                and (grossamount+IFNULL((select sum(amount) FROM receiptsallocation where journalno = customerstatement.JournalNo and itemcode=customerstatement.Accountno),0) )>0 
                order by Date";
            unset($invoices_cursor);
  
            $ResultIndex=DB_query($SQL,$db);
            while($rows=DB_fetch_array($ResultIndex)){
                 $invoices_cursor[$rows['JournalNo']]=array(
                    "amountinvoice"=>(float)$rows['amount2'],
                    "journalnoinv"=>$rows['JournalNo'],
                    "passamount"=>$rows['grossamount']);
                  
            }
            DB_free_result($ResultIndex);
            
    $amountleft=0; $amountreceipt=0;
   
   if(is_array($receipts_cursor)){
        foreach ($receipts_cursor as $key =>$receipts) {
            $amountreceipt=(float)$receipts['amountreceipt'];
            // negative 
                        
            if(is_array($invoices_cursor)){
                foreach ($invoices_cursor as $key => $invoices) {
                   if($amountreceipt <0){
                    $amount_invoice = (float)$invoices['amountinvoice'];
                    $Receiptdate    = $receipts['Receiptdate'] ;
                    $Receiptno      = $receipts['Receiptno'];
                    $journalreceipt = $receipts['journalreceipt'];
                    $journalnoinv   = $invoices['journalnoinv'];
             //positive
                    if(($amountreceipt + $amount_invoice)<=0){ 
                        Save_allocation($Receiptdate,$Receiptno,(0-$amount_invoice),$journalreceipt,$journalnoinv);
                        $invoices_cursor[$key]['amountinvoice']+=(0-$amount_invoice);
                        $amountreceipt = ($amountreceipt + $amount_invoice);
                    }else{
                       Save_allocation($Receiptdate,$Receiptno,$amountreceipt,$journalreceipt,$journalnoinv);
                       $invoices_cursor[$key]['amountinvoice']+=$amountreceipt;
                       $amountreceipt=0;
                    }
                      
                    CheckInvoiceWhenpaid($accountno,$invoices['journalnoinv'],$invoices['passamount']);
                     
                     
                    }
               }
              
                 
            }

        }
   }
   
   
   
}

Function CheckInvoiceWhenpaid($accountno,$journalno,$grossamount=0){
    global $db;
      
    $SQL=sprintf("SELECT sum(amount) FROM receiptsallocation where itemcode='%s' and journalno='%s'",
            $accountno,$journalno);
    $ResultIndex = DB_query($SQL,$db);
    $row = DB_fetch_row($ResultIndex);
    $Balance =(float)$row[0];
    
    if($grossamount+$Balance==0){
        $SQL=sprintf("select date  FROM receiptsallocation where journalno='%s' and itemcode='%s' limit 1",
                $journalno,$accountno);
        $ResultIndex = DB_query($SQL,$db);
        $row = DB_fetch_row($ResultIndex);
        $DATEPID =$row[0];
    
		 $SQL=sprintf("Update customerstatement set Datewhenpaid=(
		 select case
                     when (customerstatement.Date > '%s') 
                    then (select customerstatement.Date) 
                    else (select '%s') 
                    end where JournalNo='%s') 
                    where JournalNo='%s' and Accountno='%s'",
                         $DATEPID,$DATEPID,$journalno,$journalno,$accountno);
                 $ResultIndex = DB_System($SQL,$db);
                 
    }

}




   
?>