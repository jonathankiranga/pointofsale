<?php
  /* $Id: ConnectDB_mssql.inc 6310 2014-08-06 14:41:50Z Jonathan Kiranga $ */
define ('LIKE','LIKE');
session_write_close(); //in case a previous session is not closed
session_name('smartlimserp');
session_start();
include('../config.php');


if (!isset($mysqlport)){
	$mysqlport = 3306;
}
global $db;
// Make sure it IS global, regardless of our context
       
$db = mysqli_connect($host , $DBUser, $DBPassword,$_SESSION['DatabaseName'], $mysqlport);
$result=DB_query('SET sql_mode = ANSI', $db);
//this statement sets the charset to be used for sending data to and from the db server
//if not set, both mysqli server and mysqli client/library may assume otherwise
mysqli_set_charset($db, 'utf8');

include('DateFunctions.inc');

require_once ('MiscFunctions.php');

//DB wrapper functions to change only once for whole application

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


$sql="SELECT [coycode],[coyname],[PIN],[vat] ,[regoffice1],[regoffice2] ,[regoffice3]  ,[regoffice4] ,[regoffice5] ,[regoffice6] ,[telephone],[fax] ,[email],[currencydefault], currencies.decimalplaces
FROM companies  INNER JOIN currencies ON companies.currencydefault=currencies.currabrev  WHERE coycode=1";
$ReadCoyResult = DB_query($sql,$db);
if (DB_num_rows($ReadCoyResult)>0) {
    $_SESSION['CompanyRecord'] =  DB_fetch_array($ReadCoyResult);
}

$sql = "SELECT confname, confvalue FROM config";
$ConfigResult = DB_query($sql,$db);
while($myrow = DB_fetch_array($ConfigResult)) {
        if (is_numeric($myrow['confvalue']) AND $myrow['confname']!='DefaultPriceList' AND $myrow['confname']!='VersionNumber'){
                //the variable name is given by $myrow[0]
                $_SESSION[$myrow['confname']] = (double) $myrow['confvalue'];
        } else {
                $_SESSION[$myrow['confname']] =  $myrow['confvalue'];
        }
} 



function myjavascript($DEBIT='DEBIT',$amount=0){
    
    if($DEBIT=='DEBIT'){
         echo '<script type="text/javascript"> ';
         echo "var TotalCleared=0,Difference=0,Balbfwd=0, CRamount=0,bankendbalance=0, DBamount=0;
              document.getElementById('clearedDB').value =Math.round( ".$amount.",1) ;
              Balbfwd   = document.getElementById('lastreconbalance').value;
              CRamount  = document.getElementById('clearedCR').value;
              bankendbalance = document.getElementById('bankendbalance').value;
              TotalCleared = (((+Balbfwd) + (+ ".$amount." ))- (+CRamount));
              Difference= (+bankendbalance)- (+TotalCleared);
              document.getElementById('cleared').value = Math.round(TotalCleared,1);
            </script>";
    }else{
        echo '<script type="text/javascript"> ';
        echo "var TotalCleared=0,Difference=0,Balbfwd=0,CRamount=0,bankendbalance=0,DBamount=0;
            document.getElementById('clearedCR').value = Math.round(".$amount.",1) ;
            Balbfwd = document.getElementById('lastreconbalance').value;
            DBamount =  document.getElementById('clearedDB').value;
            bankendbalance = document.getElementById('bankendbalance').value;
            TotalCleared = (((+Balbfwd) + (+DBamount))- (+".$amount."));
            Difference= (+bankendbalance)- (+TotalCleared);
            document.getElementById('cleared').value =Math.round(TotalCleared,1);";
        echo "</script>";
    }
}

function CreateRow($D,$N,$M,$M2,$A,$Journal,$cleared){
    Global $DRamount,$db;
    $D = ConvertSQLDate($D);
    
     $currentStatus = (($cleared==1)?'checked="checked"':'unchecked="unchecked"');
     if($cleared==1){
        $DRamount += $A ;
     }
    
   return SprintF('<tr>'
            . '<td>%s</td>'
            . '<td>%s<input type="hidden" name="Cheq[%s]" value="%s"/></td>' 
            . '<td>%s</td>'
            . '<td>%s</td>'
            . '<td class="number"><input type="text" class="number" name="Amount[%s]" value="%s" size="10" readonly="readonly"/></td>'
            . '<td><input type="checkbox" name="check[%s]" %s  onchange="GetDBCleared(%s,this)"/></td>'
            . '</tr>',$D,$N,$Journal,$N,$M,$M2,$Journal,number_format(abs($A),2),$Journal,$currentStatus,$A);
}

function CreateRow2($D,$N,$M,$M2,$A,$Journal,$cleared){
    Global $CRamount,$db;
    $D = ConvertSQLDate($D);
   
    $currentStatus = (($cleared==1)?'checked="checked"':'unchecked="unchecked"');
     if($cleared==1){
        $CRamount += $A ;
     }
  
     return SprintF('<tr>'
            . '<td>%s</td>'
            . '<td>%s<input type="hidden"  name="Cheq[%s]" value="%s"/></td>' 
            . '<td>%s</td>'
            . '<td>%s</td>'
            . '<td class="number"><input type="text" class="number" name="Amount[%s]" value="%s" size="10" readonly="readonly"/></td>'
            . '<td><input type="checkbox" name="check2[%s]" %s  onchange="GetCRCleared(%s,this)" /></td>'
            . '</tr>', $D,$N,$Journal,$N,$M,$M2,$Journal,number_format(abs($A),2),$Journal,$currentStatus,$A);
}

Global $db,$tableObject,$PostingGroup;
Global $CRamount,$DRamount,$Uncleared,$Difference;
Global $banklist;

$DRamount=0;$Uncleared=0;$Difference=0;$CRamount=0;

$banklist=array();

$resultindex=DB_query("SELECT [accountcode],[bankName],[currency],[lastreconcileddate],[AccountNo],[BranchCode],[BranchName],[lastreconbalance],[lastChequeno],[PostingGroup]   FROM [BankAccounts]", $db);
while($row=DB_fetch_array($resultindex)){
    $banklist[trim($row['accountcode'])]=$row['bankName'].' '.$row['BranchName'].' '.$row['currency'];
}

    $SQL="SELECT 
       [bankcode]
      ,[DocDate]
      ,[systypes_1].[typename]
      ,[DocumentNo]
      ,(case TransType 
	   when 'DR' then (select customer from debtors where debtors.itemcode=banktransactions.itemcode)  
	   when 'CR' then (select customer from creditors where creditors.itemcode=banktransactions.itemcode)  
	   else (select customer from debtors where debtors.itemcode=banktransactions.itemcode) end) as Payee
      ,[journal]
      ,[amount]
      ,[narrative]
      ,[exchangerate]
      ,IFNULL([cleared],0) AS [cleared]
      ,[BankTransactions].[reconciled]
  FROM [BankTransactions] 
  left join [systypes_1] on [BankTransactions].[doctype]=[systypes_1].typeid 
  where ([bankcode]='".mysqli_real_escape_string($db, trim((string)$_POST['Bank_Code']))."')";
   
  $statementStart = isset($_POST['statementstartdate']) ? trim((string)$_POST['statementstartdate']) : '';
  $statementEnd = isset($_POST['enddate']) ? trim((string)$_POST['enddate']) : '';
  if ($statementStart !== '' && $statementEnd !== '') {
        $SQL .= " and (([BankTransactions].[DocDate] between '".mysqli_real_escape_string($db, $statementStart)."' and '".mysqli_real_escape_string($db, $statementEnd)."')"
           . " or ([BankTransactions].[reconciled] is NULL))";
  } elseif ($statementStart !== '') {
        $SQL .= " and (([BankTransactions].[DocDate] > '".mysqli_real_escape_string($db, $statementStart)."') or ([BankTransactions].[reconciled] is NULL))";
  }
    
$SQL .=" order by [DocDate] Asc" ;
 $ResultIndex=DB_query($SQL,$db);
   
$tableObject='<div id="filtereddata"><div class="centre">'
           . '<input type="submit" name="refresh" value="Update"/>'
           . '<input type="submit" name="SaveRecon" value="Save Bank Reconciliation"/>'
           . '</div>';

$tableObject.= '<p><table class="table-bordered"><tr><td valign="top">'
        . '<table id="cashbookdebit" class="display table-bordered"><thead><tr><th>Date</th><th>Cheque No</th>'
        . '<th>Narration</th><th>Narration 2</th><th>Debit</th><th>Clear</th></tr></thead><tbody>';
   
while($row =DB_fetch_array($ResultIndex)){
        $amounttocheck=$row['amount'];
            if($amounttocheck>0){
                $tableObject .=  CreateRow($row['DocDate']
                         ,$row['DocumentNo']
                         ,trim($row['narrative'])
                         ,trim($row['typename']).'  '.$row['Payee']
                         ,$row['amount']
                         ,$row['journal']
                         ,$row['cleared']);
            }
    }
 
     myjavascript('DEBIT',$DRamount);
   
    
  $tableObject .= '</tbody><tfoot><tr><th>Date</th><th>Cheque No</th>'
        . '<th>Narration</th><th>Narration 2</th><th>Debit</th><th>Clear</th></tr>'
          . '</tfoot></table></td><td valign="top">';
      
  $tableObject .= '<table  id="cashbookcredit"  class="display table-bordered"><thead><tr><th>Date</th><th>Cheque No</th>'
        . '<th>Narration</th><th>Narration 2</th><th>Debit</th><th>Clear</th></tr></thead><tbody>';

 $ResultIndex=DB_query($SQL,$db);
    while($row =DB_fetch_array($ResultIndex)){
        $amounttocheck = $row['amount'];
            if($amounttocheck<0){
                $tableObject .=  CreateRow2($row['DocDate']
                         ,$row['DocumentNo']
                         ,trim($row['narrative'])
                         ,trim($row['typename']).'  '.$row['Payee']
                         ,$row['amount']
                         ,$row['journal']
                         ,$row['cleared']);
            }
    }
  myjavascript('CREDIT',$CRamount);
        
 
  $tableObject .= '</tbody><tfoot><tr><th>Date</th><th>Cheque No</th>'
        . '<th>Narration</th><th>Narration 2</th><th>Debit</th><th>Clear</th></tr>'
          . '</tfoot></table></td>'
          . '</tr></table></div>';
         
echo $tableObject;
   
