<?php

define ('LIKE','LIKE');
/*There is a variable called lower_case_table_names in my.cnf file which can be set as lower_case_table_names=1 to make the mysql table incasesensitive.

To do this in Linux below are the steps:

Open terminal and edit /etc/mysql/my.cnf

sudo nano /etc/mysql/my.cnf Underneath the [mysqld] section.add if not present in the cnf file:

lower_case_table_names = 1

Restart mysql*/
if (!isset($mysqlport)){
	$mysqlport = 3306;
}
global $db,$mysqli;
// Make sure it IS global, regardless of our context
       
$db = mysqli_connect($host , $DBUser, $DBPassword,$_SESSION['DatabaseName'], $mysqlport);

$mysqli = new mysqli($host , $DBUser, $DBPassword,$_SESSION['DatabaseName']);

$result=DB_query('SET sql_mode = ANSI', $db);
//this statement sets the charset to be used for sending data to and from the db server
//if not set, both mysqli server and mysqli client/library may assume otherwise
mysqli_set_charset($db, 'utf8');

/* check connection */
if (mysqli_connect_errno()) {
	printf("Connect failed: %s\n", mysqli_connect_error());
	session_unset();
	session_destroy();
	echo '<p>' . _('Click') . ' ' . '<a href="index.php">' . _('here') . '</a>' . ' '  ._('to try logging in again') . '</p>';
	exit();
}

if ( !$db ) {
	echo '<br />' . _('The configuration in the file config.php for the database user name and password do not provide the information required to connect to the database server');
	exit;
}

/* Update to allow RecurringSalesOrdersProcess.php to run via cron */
if (isset($DatabaseName)) {
	if (!mysqli_select_db($db,$DatabaseName)) {
		echo '<br />' . _('The company name entered does not correspond to a database on the database server specified in the config.php configuration file. Try logging in with a different company name');
		echo '<br /><a href="index.php">' . _('Back to login page') . '</A>';
		unset ($DatabaseName);
		exit;
	}
} else {
	if (!mysqli_select_db($db,$_SESSION['DatabaseName'])) {
		echo '<br />' . _('The company name entered does not correspond to a database on the database server specified in the config.php configuration file. Try logging in with a different company name');
		echo '<br /><a href="index.php">' . _('Back to login page') . '</A>';

		unset ($_SESSION['DatabaseName']);
		exit;
        }
}

require_once ($PathPrefix .'includes/MiscFunctions.php');

//DB wrapper functions to change only once for whole application

function DB_query ($SQL, $Conn, $ErrorMessage='', $DebugMessage= '', $Transaction=false, $TrapErrors=true){

	global $debug;
	global $PathPrefix;
        
        $SQL = RemoveBox($SQL);
    //  $filename= 'sql/'.basename($_SERVER['SCRIPT_NAME'],'.php').'.SQL';
    //  file_put_contents($filename,$SQL."\r\n\r\n",FILE_APPEND);

	$result= mysqli_query($Conn,$SQL);

	$_SESSION['LastInsertId'] = mysqli_insert_id($Conn);

	if ($DebugMessage == '') {
		$DebugMessage = _('The SQL that failed was');
	}

	if (DB_error_no($Conn) != 0 AND $TrapErrors==true){
		if ($TrapErrors){
			require_once($PathPrefix . 'includes/header.inc');
		}
		prnMsg($ErrorMessage . '<br />' . DB_error_msg($Conn),'error', _('Database Error'). ' ' .DB_error_no($Conn));
		if ($debug==1){
			prnMsg($DebugMessage. '<br />' . $SQL . '<br />','error',_('Database SQL Failure'));
		}
		if ($Transaction){
			$SQL = 'rollback';
			$Result = DB_query($SQL,$Conn);
			if (DB_error_no($Conn) !=0){
				prnMsg(_('Error Rolling Back Transaction'), 'error', _('Database Rollback Error'). ' ' .DB_error_no($Conn) );
			}else{
				prnMsg(_('Rolling Back Transaction OK'), 'error', _('Database Rollback Due to Error Above'));
			}
		}
		if ($TrapErrors){
			include($PathPrefix . 'includes/footer.inc');
			exit;
		}
	} elseif (isset($_SESSION['Monthsaudittrail']) and (DB_error_no($Conn)==0 AND $_SESSION['Monthsaudittrail']>0) AND (DB_affected_rows($result)>0)){

		$SQLArray = explode(' ', $SQL);

		if (($SQLArray[0] == 'INSERT')
			OR ($SQLArray[0] == 'UPDATE')
			OR ($SQLArray[0] == 'DELETE')) {

			if ($SQLArray[2]!='audittrail'){ // to ensure the auto delete of audit trail history is not logged
				$AuditSQL = "INSERT INTO audittrail (transactiondate,userid,querystring)
						VALUES('" . Date('Y-m-d H:i:s') . "',
							'" . trim($_SESSION['UserID']) . "',
							'" . htmlentities($SQL) . "')";

				$AuditResult = mysqli_query($Conn, $AuditSQL);
			}
		}
	}
	return $result;

}

function DB_System ($SQL, $Conn, $ErrorMessage='', $DebugMessage= '', $Transaction=false, $TrapErrors=true){
    global $debug;
    global $PathPrefix;
        
        $SQL = RemoveBox($SQL);
        
    //      $filename= 'sql/'.basename($_SERVER['SCRIPT_NAME'],'.php').'.SQL';
    //      file_put_contents($filename,$SQL."\r\n\r\n",FILE_APPEND);

        
	$result= mysqli_query($Conn,$SQL);

	$_SESSION['LastInsertId'] = mysqli_insert_id($Conn);

	if ($DebugMessage == '') {
	    $DebugMessage = _('The SQL that failed was');
	}

        if (DB_error_no($Conn) != 0 AND $TrapErrors==true){
		if ($TrapErrors){
			require_once($PathPrefix . 'includes/header.inc');
		}
		prnMsg($ErrorMessage . '<br />' . DB_error_msg($Conn),'error', _('Database Error'). ' ' .DB_error_no($Conn));
		if ($debug==1){
			prnMsg($DebugMessage. '<br />' . $SQL . '<br />','error',_('Database SQL Failure'));
		}
		if ($Transaction){
			$SQL = 'rollback';
			$Result = DB_query($SQL,$Conn);
			if (DB_error_no($Conn) !=0){
				prnMsg(_('Error Rolling Back Transaction'), 'error', _('Database Rollback Error'). ' ' .DB_error_no($Conn) );
			}else{
				prnMsg(_('Rolling Back Transaction OK'), 'error', _('Database Rollback Due to Error Above'));
			}
		}
		if ($TrapErrors){
			include($PathPrefix . 'includes/footer.inc');
			exit;
		}
	}
	
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
    return  mysqli_escape_string($db,$String);;
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

	$Result = DB_query("UPDATE config SET confvalue='" . Date('Y-m-d') . "' WHERE confname='DB_Maintenance_LastRun'",$Conn);
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
    $leftbrace = str_replace('[','`',$sql);
    $allbraces = str_replace(']','`',$leftbrace);

    return $allbraces;
}
 

FUNCTION IssqldateGreater($PriodClosedDate,$CurrentDate){
    Global $db;
   
    $sql= sprintf("TO_DAYS('%s') - TO_DAYS('%s')",$CurrentDate,$PriodClosedDate);
    $ResultIndex = DB_System($sql,$db); 
    $rows = DB_fetch_row($ResultIndex);
   	
    if($rows[0] <0){ $return =0; }else{ $return =1; }
    
    return $return;
}


 $data = array();
 $index = array();
 $DIMENSIONARRY = array();
 
function mysqlclass($id){
       global $db,$data,$index,$DIMENSIONARRY;
        
        $sql=sprintf("select PARENT,LEVEL,Code,Dimension FROM dimensions where id='%s'",$id);
        $ResultIndex = DB_System($sql,$db); 
        while ($row = DB_fetch_array($ResultIndex)) {
            $id =$row["Code"];
            $parent_id =$row["PARENT"];
            $data[$id]= $row;
            $index[$parent_id][]=$id;
        }
        
        display_tree("",0);
        return $DIMENSIONARRY;
}

function display_tree($parent_id, $level){
    global $data, $index,$DIMENSIONARRY;
    if (isset($index[$parent_id])) {
        foreach ($index[$parent_id] as $id) {
            $DIMENSIONARRY[]=array(
                "sort_seq"=>($level*2),
                "levels"=>$level,
                "Code"=>$data[$id]["Code"],
                "Dimension"=>$data[$id]["Dimension"]);
            display_tree($id, $level + 1);
        }
    }
    
    return $DIMENSIONARRY;
}

Function vendorstatements($accountno,$yearindex){
    global $db,$Balance;
      
$SQL="select min(start_date) ,max(end_date) from financialperiods where periodno='".$yearindex."'";
$ResultIndex=DB_query($SQL,$db);
$data=DB_fetch_row($ResultIndex);
$startdate=$data[0] ; $enddate=$data[1] ; 

$SQL="select sum(Grossamount) from supplierstatement where date <'".$startdate."' and Accountno='".$accountno."'";
$ResultIndex=DB_query($SQL,$db);
$data=DB_fetch_row($ResultIndex);
$openbal=(float)$data[0]; 
$Balance=$openbal;

$Supp[]=array("date"=>$startdate , "openbal" =>$openbal);


$SQL="SELECT 
	   supplierstatement.Date
           ,supplierstatement.Documentno
           ,Accountno
           ,Grossamount
           ,(Grossamount-(SELECT sum(amount) FROM paymentsallocation 
	where itemcode=supplierstatement.Accountno and date=supplierstatement.date 
        and invoiceno=supplierstatement.Documentno and journalno=supplierstatement.JournalNo)) as pamount
	   ,(select systypes_1.typename from systypes_1 where systypes_1.typeid=supplierstatement.Documenttype) as doctypes
	   ,enterbillheaders.narration
  FROM supplierstatement  left join enterbillheaders on enterbillheaders.journalno=supplierstatement.JournalNo 
  where supplierstatement.Date between '".$startdate."' and sysdate() and Accountno='".$accountno."' order by Date";
        $ResultIndex=DB_System($SQL,$db);
        while($row= DB_fetch_array($ResultIndex)){
            $Supp[]=array("date"=>$row['Date'],
                "Documentno"=>$row['Documentno'] ,
                "Accountno"=>$row['Accountno'] , 
                "Grossamount"=>$row['Grossamount'] , 
                "pamount"=>$row['pamount'] , 
                "doctypes"=>$row['doctypes'] , 
                "narration"=>$row['narration'] );

        }

        return $Supp;
        
}

Function customerstatements($accountno,$yearindex){
    global $db,$Balance;
    
$SQL="select min(start_date) ,max(end_date) from financialperiods where periodno='".$yearindex."'";
$ResultIndex=DB_query($SQL,$db);
$data=DB_fetch_row($ResultIndex);
$startdate=$data[0] ; $enddate=$data[1] ; 

$SQL="select sum(Grossamount) from customerstatement where date <'".$startdate."' and Accountno='".$accountno."'";
$ResultIndex=DB_query($SQL,$db);
$data=DB_fetch_row($ResultIndex);
$openbal=(float)$data[0]; 
$Balance=$openbal;

$Cust[]=array("date"=>$startdate ,"Documentno"=>'Bal',"Grossamount"=>$openbal);


$SQL="SELECT 
       date
      ,Documentno
      ,Accountno
      ,Grossamount
      ,(Grossamount-(SELECT sum(amount) FROM receiptsallocation 
         where itemcode=customerstatement.Accountno
          and invoiceno=customerstatement.Documentno 
          and journalno=customerstatement.JournalNo)) as pamount
	  ,(select systypes_1.typename from systypes_1 where systypes_1.typeid=customerstatement.Documenttype)  as doctypes
	FROM customerstatement where date  between '".$startdate."' and sysdate() and Accountno='".$accountno."'  order by date asc";
        $ResultIndex=DB_query($SQL,$db);
        while($row= DB_fetch_array($ResultIndex)){
            $Cust[]=array("date"=>$row['date'],
                "Documentno"=>$row['Documentno'] ,
                "Accountno"=>$row['Accountno'] , 
                "Grossamount"=>$row['Grossamount'] , 
                "pamount"=>$row['pamount'] , 
                "doctypes"=>$row['doctypes'] );

        }

        return $Cust;
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

Function Save_allocation($Receiptdate,$Receiptno,$amountreceipt,$journalreceipt,$journalnoinv){
    global $db;
        
     $sql=SPRINTF("insert into receiptsallocation (itemcode,date,invoiceno,journalno,doctype,receiptno,amount,receiptjournal) 
    (Select Accountno,'%s',Documentno,JournalNo,Documenttype,'%s',%f,'%s' from customerstatement where JournalNo='%s') ",
     $Receiptdate,$Receiptno,$amountreceipt,$journalreceipt,$journalnoinv); 
     
    DB_System($sql,$db);
}

function ReceiptAllocations($journal ,$Accountno){
    global $db;
    
   $ResultIndex = DB_query(sprintf("select sum(amount) FROM  receiptsallocation  
   where  receiptjournal ='%s' and  itemcode ='%s'",$journal,$Accountno), $db);
   $recAlloc = DB_fetch_row($ResultIndex);
    
    return $recAlloc[0];
}

function PaymentsAllocations($journal ,$Accountno){
    global $db;
    
   $ResultIndex = DB_query(sprintf("select sum(amount) FROM paymentsallocation 
   where receiptjournal='%s' and itemcode='%s'",$journal,$Accountno), $db);
   $recAlloc = DB_fetch_row($ResultIndex);
    
    return $recAlloc[0];
}

Function ageingSuppliers($account){
     global $db;
   $date=sprintf("date_add(sysdate(), interval  %f  day)",-30);

	//Declare Agecurrent cursor for
	$CurrentAge="SELECT Date,Grossamount
          ,(SELECT sum(amount) FROM paymentsallocation 
          where itemcode=supplierstatement.Accountno 
          and invoiceno=supplierstatement.Documentno
          and journalno=supplierstatement.JournalNo) as Pamount,JournalNo
      FROM supplierstatement 
      where supplierstatement.Accountno=".$account." 
	      and supplierstatement.Grossamount<0
              and supplierstatement.date between
              ".$date." and sysdate()";
        
$TotalCurrent=0;
       $ResultIndex = DB_query($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $TotalCurrent+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }

//calculate 30 day
        $date=sprintf("date_add(sysdate(), interval  %f  day)",-60);
	$date1=sprintf("date_add(sysdate(), interval  %f  day)",-30);

              $SQL="SELECT Date,Grossamount
          ,(SELECT sum(amount) FROM paymentsallocation 
          where itemcode=supplierstatement.Accountno 
          and invoiceno=supplierstatement.Documentno
          and journalno=supplierstatement.JournalNo) as Pamount,JournalNo
      FROM supplierstatement 
      where supplierstatement.Accountno=".$account." 
	      and supplierstatement.Grossamount<0
              and supplierstatement.date between ".$date." and ".$date1;

        $days30=0;
        $ResultIndex = DB_System($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $days30+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }


//calculate 60 day
      $date=sprintf("date_add(sysdate(), interval  %f  day)",-90);
	$date1=sprintf("date_add(sysdate(), interval  %f  day)",-60);

$SQL="SELECT Date,Grossamount
          ,(SELECT sum(amount) FROM paymentsallocation 
          where itemcode=supplierstatement.Accountno 
          and invoiceno=supplierstatement.Documentno
          and journalno=supplierstatement.JournalNo) as Pamount,JournalNo
      FROM supplierstatement 
      where supplierstatement.Accountno=".$account." 
	      and supplierstatement.Grossamount<0
              and supplierstatement.date between ".$date." and ".$date1;
       $days60=0;
       $ResultIndex = DB_System($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $days60+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }


  //calculate 90 day
$date=sprintf("date_add(sysdate(), interval  %f  day)",-120);
$date1=sprintf("date_add(sysdate(), interval  %f  day)",-90);

        $SQL="SELECT Date,Grossamount
          ,(SELECT sum(amount) FROM paymentsallocation 
          where itemcode=supplierstatement.Accountno 
          and invoiceno=supplierstatement.Documentno
          and journalno=supplierstatement.JournalNo) as Pamount,JournalNo
      FROM supplierstatement 
      where supplierstatement.Accountno=".$account." 
	      and supplierstatement.Grossamount<0
              and supplierstatement.date between ".$date." and ".$date1;
       $days90=0;
       $ResultIndex = DB_System($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $days90+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }


 //calculate over 90 day
$date=sprintf("date_add(sysdate(), interval  %f  day)",-120);
$date1=sprintf("date_add(sysdate(), interval  %f  day)",-90);

 $SQL="SELECT Date,Grossamount
          ,(SELECT sum(amount) FROM paymentsallocation 
          where itemcode=supplierstatement.Accountno 
          and invoiceno=supplierstatement.Documentno
          and journalno=supplierstatement.JournalNo) as Pamount,JournalNo
      FROM supplierstatement 
      where supplierstatement.Accountno=".$account." 
	      and supplierstatement.Grossamount<0
              and supplierstatement.date < ".$date;
       $daysover90=0;
       $ResultIndex = DB_query($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $daysover90+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }


return  array("TotalCurrent"=>$TotalCurrent,
	"30days"=>$days30 ,
	"60days"=>$days60 ,
	"90days"=>$days90 ,
	"over90days"=>$daysover90); 

}

function ageingSuppliersReport($account,$Month){
       global $db;
    
    $ResultIndex = DB_query("select lastdate_in_period from periods where periodno=".$Month, $db);
    $rows = DB_fetch_row($ResultIndex);
    $lastdate_in_period = $rows[0];
    $date = sprintf("date_add('%s',interval -30 day)",$lastdate_in_period);
 
	//Declare Agecurrent cursor for
	$SQL="SELECT Date
      ,Grossamount
      ,(SELECT sum(amount) FROM paymentsallocation 
         where itemcode=supplierstatement.Accountno 
          and invoiceno=supplierstatement.Documentno 
          and journalno=supplierstatement.JournalNo) as Pamount ,JournalNo
      FROM supplierstatement 
      where supplierstatement.Accountno='".$account."' 
	      and supplierstatement.Grossamount<0
          and supplierstatement.date between  ".$date." and '$lastdate_in_period'";
        
$TotalCurrent=0;
       $ResultIndex = DB_query($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $TotalCurrent+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }

//calculate 30 day
        $date2 = sprintf("date_add('%s',interval -30 day)",$lastdate_in_period);
        $date1 = sprintf("date_add('%s',interval -60 day)",$lastdate_in_period);
        
              $SQL="SELECT Date
      ,Grossamount
      ,(SELECT sum(amount) FROM paymentsallocation 
         where itemcode=supplierstatement.Accountno 
          and invoiceno=supplierstatement.Documentno 
          and journalno=supplierstatement.JournalNo) as Pamount ,JournalNo
      FROM supplierstatement 
      where supplierstatement.Accountno='".$account."' 
	      and supplierstatement.Grossamount<0
          and supplierstatement.date between  ".$date1." and  ".$date2;

        $days30=0;
        $ResultIndex = DB_query($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $days30+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }


//calculate 60 day
        $date2 = sprintf("date_add('%s',interval -60 day)",$lastdate_in_period);
        $date1 = sprintf("date_add('%s',interval -90 day)",$lastdate_in_period);
  
		  $SQL="SELECT Date
      ,Grossamount
      ,(SELECT sum(amount) FROM paymentsallocation 
         where itemcode=supplierstatement.Accountno 
          and invoiceno=supplierstatement.Documentno 
          and journalno=supplierstatement.JournalNo) as Pamount ,JournalNo
      FROM supplierstatement 
      where supplierstatement.Accountno='".$account."' 
	      and supplierstatement.Grossamount<0
          and supplierstatement.date between   ".$date1." and ".$date2;
       $days60=0;
       $ResultIndex = DB_query($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $days60+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }


  //calculate 90 day
        $date2 = sprintf("date_add('%s',interval -90 day)",$lastdate_in_period);
        $date1 = sprintf("date_add('%s',interval -120 day)",$lastdate_in_period);
  
        $SQL="SELECT Date
      ,Grossamount
      ,(SELECT sum(amount) FROM paymentsallocation 
         where itemcode=supplierstatement.Accountno 
          and invoiceno=supplierstatement.Documentno 
          and journalno=supplierstatement.JournalNo) as Pamount ,JournalNo
      FROM supplierstatement 
      where supplierstatement.Accountno='".$account."' 
	      and supplierstatement.Grossamount<0
          and supplierstatement.date between  ".$date1." and ".$date2;
       $days90=0;
       $ResultIndex = DB_query($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $days90+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }


 //calculate over 90 day
        $date1 = sprintf("date_add('%s',interval -120 day)",$lastdate_in_period);
  
 $SQL="SELECT Date
      ,Grossamount
      ,(SELECT sum(amount) FROM paymentsallocation 
         where itemcode=supplierstatement.Accountno 
          and invoiceno=supplierstatement.Documentno 
          and journalno=supplierstatement.JournalNo) as Pamount ,JournalNo
      FROM supplierstatement 
      where supplierstatement.Accountno='".$account."' 
	      and supplierstatement.Grossamount<0
          and supplierstatement.date < ".$date1;
       $daysover90=0;
       $ResultIndex = DB_query($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $daysover90+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }


return  array("TotalCurrent"=>$TotalCurrent,
	"30days"=>$days30 ,
	"60days"=>$days60 ,
	"90days"=>$days90 ,
	"over90days"=>$daysover90); 

}

Function ageingCustomers($account){
  global $db;
	//Declare Agecurrent cursor for
        $date=sprintf("date_add(sysdate(), interval  %f  day)",-30);
	$SQL="SELECT 
                    Date,
                    Grossamount ,
                    (SELECT sum(amount) FROM receiptsallocation 
                 where itemcode=customerstatement.Accountno 
                  and invoiceno=customerstatement.Documentno 
                  and journalno=customerstatement.JournalNo) as Pamount,
                  JournalNo
              FROM customerstatement 
              where customerstatement.Accountno='".$account."' 
                  and customerstatement.Grossamount>0
                  and customerstatement.date between  ".$date." and sysdate()";
        
$TotalCurrent=0;
       $ResultIndex = DB_query($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $TotalCurrent+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }

//calculate 30 day
        $date=sprintf("date_add(sysdate(), interval  %f  day)",-60);
	$date1=sprintf("date_add(sysdate(), interval  %f  day)",-30);
	
              $SQL="SELECT 
                    Date,
                    Grossamount ,
                    (SELECT sum(amount) FROM receiptsallocation 
                 where itemcode=customerstatement.Accountno 
                  and invoiceno=customerstatement.Documentno 
                  and journalno=customerstatement.JournalNo) as Pamount,
                  JournalNo
              FROM customerstatement 
              where customerstatement.Accountno='".$account."' 
                  and customerstatement.Grossamount>0
                  and customerstatement.date between  ".$date." and ".$date1;

        $days30=0;
        $ResultIndex = DB_query($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $days30+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }


//calculate 60 day
        $date=sprintf("date_add(sysdate(), interval  %f  day)",-90);
	$date1=sprintf("date_add(sysdate(), interval  %f  day)",-60);

		  $SQL="SELECT 
                    Date,
                    Grossamount ,
                    (SELECT sum(amount) FROM receiptsallocation 
                 where itemcode=customerstatement.Accountno 
                  and invoiceno=customerstatement.Documentno 
                  and journalno=customerstatement.JournalNo) as Pamount,
                  JournalNo
              FROM customerstatement 
              where customerstatement.Accountno='".$account."' 
                  and customerstatement.Grossamount>0
                  and customerstatement.date between  ".$date." and ".$date1;
       $days60=0;
       $ResultIndex = DB_query($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $days60+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }


  //calculate 90 day
    $date=sprintf("date_add(sysdate(), interval  %f  day)",-120);
	$date1=sprintf("date_add(sysdate(), interval  %f  day)",-90);

        $SQL="SELECT 
                    Date,
                    Grossamount ,
                    (SELECT sum(amount) FROM receiptsallocation 
                 where itemcode=customerstatement.Accountno 
                  and invoiceno=customerstatement.Documentno 
                  and journalno=customerstatement.JournalNo) as Pamount,
                  JournalNo
              FROM customerstatement 
              where customerstatement.Accountno='".$account."' 
                  and customerstatement.Grossamount>0
                  and customerstatement.date between ".$date." and ".$date1;
       $days90=0;
       $ResultIndex = DB_query($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $days90+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }


 //calculate over 90 day
 $date=sprintf("date_add(sysdate(), interval  %f  day)",-120);

            $SQL="SELECT 
                    Date,
                    Grossamount,
                    (SELECT sum(amount) FROM receiptsallocation 
                 where itemcode=customerstatement.Accountno 
                  and invoiceno=customerstatement.Documentno 
                  and journalno=customerstatement.JournalNo) as Pamount,
                  JournalNo
              FROM customerstatement 
              where customerstatement.Accountno='".$account."' 
                  and customerstatement.Grossamount>0
                  and customerstatement.date < ".$date;
            
            
            
       $daysover90=0;
       $ResultIndex = DB_query($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $daysover90+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }


return  array("TotalCurrent"=>$TotalCurrent,
	"30days"=>$days30 ,
	"60days"=>$days60 ,
	"90days"=>$days90 ,
	"over90days"=>$daysover90); 
}
 
Function ageingCustomersReport($account,$Month){
    global $db;
    
    $ResultIndex = DB_query("select lastdate_in_period from periods where periodno=".$Month, $db);
 $rows = DB_fetch_row($ResultIndex);
 $lastdate_in_period = $rows[0];
 $date = sprintf("date_add('%s',interval -30 day)",$lastdate_in_period);
 
	//Declare Agecurrent cursor for
	$SQL="SELECT 
                    Date,
                    Grossamount,
                    (SELECT sum(amount) FROM receiptsallocation 
                 where itemcode=customerstatement.Accountno 
                  and invoiceno=customerstatement.Documentno 
                  and journalno=customerstatement.JournalNo) as Pamount,
                  JournalNo
              FROM customerstatement 
              where customerstatement.Accountno='".$account."' 
                  and customerstatement.Grossamount>0
                  and customerstatement.date between  ".$date." and '$lastdate_in_period'";
        
$TotalCurrent=0;
       $ResultIndex = DB_query($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $TotalCurrent+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }

//calculate 30 day
        $date2 = sprintf("date_add('%s',interval -30 day)",$lastdate_in_period);
        $date1 = sprintf("date_add('%s',interval -60 day)",$lastdate_in_period);
        
              $SQL="SELECT 
                    Date,
                    Grossamount,
                    (SELECT sum(amount) FROM receiptsallocation 
                 where itemcode=customerstatement.Accountno 
                  and invoiceno=customerstatement.Documentno 
                  and journalno=customerstatement.JournalNo) as Pamount,
                  JournalNo
              FROM customerstatement 
              where customerstatement.Accountno='".$account."' 
                  and customerstatement.Grossamount>0
                  and customerstatement.date between  ".$date1." and  ".$date2;

        $days30=0;
        $ResultIndex = DB_query($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $days30+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }


//calculate 60 day
        $date2 = sprintf("date_add('%s',interval -60 day)",$lastdate_in_period);
        $date1 = sprintf("date_add('%s',interval -90 day)",$lastdate_in_period);
  
		  $SQL="SELECT 
                    Date,
                    Grossamount,
                    (SELECT sum(amount) FROM receiptsallocation 
                 where itemcode=customerstatement.Accountno 
                  and invoiceno=customerstatement.Documentno 
                  and journalno=customerstatement.JournalNo) as Pamount,
                  JournalNo
              FROM customerstatement 
              where customerstatement.Accountno='".$account."' 
                  and customerstatement.Grossamount>0
                  and customerstatement.date between   ".$date1." and ".$date2;
       $days60=0;
       $ResultIndex = DB_query($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $days60+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }


  //calculate 90 day
        $date2 = sprintf("date_add('%s',interval -90 day)",$lastdate_in_period);
        $date1 = sprintf("date_add('%s',interval -120 day)",$lastdate_in_period);
  
        $SQL="SELECT 
                    Date,
                    Grossamount,
                    (SELECT sum(amount) FROM receiptsallocation 
                 where itemcode=customerstatement.Accountno 
                  and invoiceno=customerstatement.Documentno 
                  and journalno=customerstatement.JournalNo) as Pamount,
                  JournalNo
              FROM customerstatement 
              where customerstatement.Accountno='".$account."' 
                  and customerstatement.Grossamount>0
                  and customerstatement.date between  ".$date1." and ".$date2;
       $days90=0;
       $ResultIndex = DB_query($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $days90+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }


 //calculate over 90 day
        $date1 = sprintf("date_add('%s',interval -120 day)",$lastdate_in_period);
  
 $SQL="SELECT 
                    Date,
                    Grossamount,
                    (SELECT sum(amount) FROM receiptsallocation 
                 where itemcode=customerstatement.Accountno 
                 and invoiceno=customerstatement.Documentno 
                  and journalno=customerstatement.JournalNo) as Pamount,
                  JournalNo
              FROM customerstatement 
              where customerstatement.Accountno='".$account."' 
                  and customerstatement.Grossamount>0
                  and customerstatement.date <   ".$date1;
       $daysover90=0;
       $ResultIndex = DB_query($SQL, $db);
       while($row= DB_fetch_array($ResultIndex)){
           $daysover90+=(float)$row['Grossamount']+(float)$row['Pamount'];
       }


return  array("TotalCurrent"=>$TotalCurrent,
	"30days"=>$days30 ,
	"60days"=>$days60 ,
	"90days"=>$days90 ,
	"over90days"=>$daysover90); 

}

Function Triger_getaccountno($accdesc){
    global $db;
   
   $ResultIndex = DB_System('Select count(*) from acct',$db);
   $pkey = DB_fetch_row($ResultIndex);
   $int  =(int) $pkey[0];
   $pad_length = (6-count($int));
   $replicate = str_repeat('0', $pad_length);
   $code = substr($accdesc,1,2) . $replicate .$int;
 
 return $code;
}

function Triger_bankaccounts(){
    global $db;
     
   $ResultIndex = DB_System('Select count(*) from bankaccounts',$db);
   $pkey = DB_fetch_row($ResultIndex);
   $int  =(int) $pkey[0];
   $pad_length = (6-count($int));
   $replicate = str_repeat('0', $pad_length);
   $code = 'BK' . $replicate .$int;
   
 return $code;
}

Function Triger_creditors($descript){
      global $db;
      
   $ResultIndex = DB_System('Select count(*) from creditors',$db);
   $pkey = DB_fetch_row($ResultIndex);
   $int  =(int) $pkey[0];
   $pad_length = (4-count($int));
   $replicate = str_repeat('0', $pad_length);
   $code = 'cr' .substr($descript, 0, 2) . $replicate .$int;
 
 return $code;
}

Function Triger_debtors($descript){
      global $db;
      
   $ResultIndex = DB_System('Select count(*) from debtors',$db);
   $pkey = DB_fetch_row($ResultIndex);
   $int  =(int) $pkey[0];
   $pad_length = (4-count($int));
   $replicate = str_repeat('0', $pad_length);
   $code = 'Dr' .substr($descript, 0, 2) . $replicate .$int;
 
 return $code;
}

Function Triger_stockmaster($descript){
      global $db;
      
   $ResultIndex = DB_System('Select count(*) from stockmaster',$db);
   $pkey = DB_fetch_row($ResultIndex);
   $int  =(int) $pkey[0];
   $pad_length = (6-count($int));
   $replicate = str_repeat('0', $pad_length);
   $code = substr($descript, 0, 2) . $replicate .$int;
 
 return $code;
}

Function Triger_unit(){
      global $db;
      
   $ResultIndex = DB_System('Select count(*) from unit',$db);
   $pkey = DB_fetch_row($ResultIndex);
   $int  =(int) $pkey[0];
   $pad_length = (2-count($int));
   $replicate = str_repeat('0', $pad_length);
   $code = 'u' . $replicate .$int;
 
 return $code;
}

