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
  where ([bankcode]='".mysqli_real_escape_string($db, trim((string)$_POST['Bank_Code']))."')
    and [BankTransactions].[reconciled] IS NULL";

  $statementEnd = isset($_POST['enddate']) ? trim((string)$_POST['enddate']) : '';
  if ($statementEnd !== '') {
      $statementEnd = FormatDateForSQL($statementEnd);
      $SQL .= " and [BankTransactions].[DocDate] <= '".mysqli_real_escape_string($db, $statementEnd)."'";
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
   
