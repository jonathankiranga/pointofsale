<?php
/* $Id: ConnectDB_mssql.inc 6310 2014-08-06 14:41:50Z Jonathan Kiranga $ */
define ('LIKE','LIKE');
session_write_close(); //in case a previous session is not closed
    session_name('smartlimserp');
    session_start();
include('../config.php');
include('DateFunctions.inc');
require_once ('MiscFunctions.php');

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

 
 
function getaccount($code){
    global $db;
    $result=DB_query("Select accno,accdesc from acct where accno='".$code."'",$db);
    $rows= DB_fetch_row($result);
    
    return $rows[1];
}

$Doctypes=array();
    
    $Results = DB_query("Select [typeid],[typename] from [systypes_1]",$db);
    while($roe = DB_fetch_array($Results)){
        $Doctypes[$roe['typeid']] = $roe['typename'];
    }
    


$getsql ="SELECT 
    [rowid],
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
      from [Generalledger] where 
     (Generalledger.Docdate between '".FormatDateForSQL($_POST['journaldate'])."'  and '".FormatDateForSQL($_POST['journaldate'])."')";
    if(mb_strlen($_POST['journalfind'])>0){
      $getsql .=" and Generalledger.DocumentNo='".$_POST['journalfind']."'" ;
    } 
     $getsql .="order by [Docdate],[rowid] asc ";
 
      $object ='<table class="statement display"><thead><tr>'
       . '<th>Date</th><th>Doc No</th><th>Doc Type</th>'
       . '<th>Debit Account</th><th>Credit Account</th>'
       . '<th>Narrative</th><th>Project</th><th>AMOUNT</th></tr></thead><tbody>';
      
       $key = '123';
       $ResultDrill = DB_query($getsql, $db);
       while($row=DB_fetch_array($ResultDrill)){
           if($row['AMOUNT']!=0){
               
              $debitanct = GetAccount($row['balaccountcode']);
              $creditanct = GetAccount($row['accountcode']);
                
               $object .= '<tr><td>'. ConvertSQLDate($row['Docdate']) .'</td>';
               $object .= sprintf('<td><a href="?DocumentNo=%s&Docdate=%s">%s</a></td>',$row['DocumentNo'],$row['Docdate'],$row['DocumentNo'] );
               $object .= '<td>'.$Doctypes[$row['DocumentType']] .'</td>';
               $object .= '<td>'.$debitanct .'</td>';
               $object .= '<td>'.$creditanct.'</td>';
               $object .= '<td>'.$row['narration'].'</td>';
               $object .= '<td>'.$row['dimension2'].'</td>';
               $object .= '<td class="number">'.number_format($row['AMOUNT'],2) .'</td></tr>';
                
           }
       }
       
$object .= '</tbody><tfoot><th>Date</th><th>Doc No</th><th>Doc Type</th>'
. '<th>Debit Account</th><th>Credit Account</th>'
. '<th>Narrative</th><th>Project</th><th>Amount</th></tfoot></table>';

echo $object;

 class AjaxUnsafeCrypto{
    const METHOD = 'aes-256-ctr';

    
    public static function encrypt($message, $key, $encode = false)
    {
        $nonceSize = openssl_cipher_iv_length(self::METHOD);
        $nonce = openssl_random_pseudo_bytes($nonceSize);

        $ciphertext = openssl_encrypt(
            $message,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $nonce
        );

        // Now let's pack the IV and the ciphertext together
        // Naively, we can just concatenate
        if ($encode) {
            return base64_encode($nonce.$ciphertext);
        }
        return $nonce.$ciphertext;
    }

   
    public static function decrypt($message, $key, $encoded = false)
    {
        if ($encoded) {
            $message = base64_decode($message, true);
            if ($message === false) {
                throw new Exception('Encryption failure');
            }
        }

        $nonceSize = openssl_cipher_iv_length(self::METHOD);
        $nonce = mb_substr($message, 0, $nonceSize, '8bit');
        $ciphertext = mb_substr($message, $nonceSize, null, '8bit');

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $nonce
        );

        return $plaintext;
    }
}
 
?>
