<?php
include('../Mailer/PHPMailerAutoload.php');
include('../config.php');

if(!file_exists("../chats/EmailConfig.php")){
    header('Location:../EmailConfigurator.php');
}else{
   include('../chats/EmailConfig.php');
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

function Resetpassword($email,$password){
   Global $db;
   $SQL = "update [www_users] set [password]='".$password."' WHERE [email]='" .$email. "'";
   $ResultIndex=DB_query($SQL,$db);
}

Function GetCredentials($email){
 Global $db;
  $arr = array();
   $jsonData = '{"results":[';
  
   $SQL = "SELECT [userid],[password],[realname],[phone],[email] FROM [www_users]  WHERE [email]='" .$email. "'";
   
   $ResultIndex=DB_query($SQL,$db);
   $line = new stdClass;
      while ($rows = odbc_fetch_array($ResultIndex)) {
        $line->membershipNo = $rows['phone'];
        $line->userid = $rows['userid'];
        $line->userpassword = $rows['password'];
        $line->names = $rows['realname'];
        $arr[] = json_encode($line);
      }
   
   $jsonData .= implode(",", $arr);
      $jsonData .= ']}';
      return $jsonData;
}


$callbackJSONData=GetCredentials($_POST['getemail']);
$callbackData=json_decode($callbackJSONData);

if(count($callbackData->results)==1){
    
$pf_no=$callbackData->results[0]->membershipNo;
$userid=$callbackData->results[0]->userid;
$userpassword=$callbackData->results[0]->userpassword;
$names=$callbackData->results[0]->names;
  
$mail = new PHPMailer(true);
class phpmailerAppException extends phpmailerException {}

$ConHost=constant("HOST");
$ConPORT=constant("PORT");
$Conusername=constant("username");
$Conpassword=constant("password");

$mail->SMTPOptions = array( 'ssl' => array('verify_peer' => false,'verify_peer_name' => false,'allow_self_signed' => true));
$mail->isSMTP();

//Enable SMTP debugging
// 0 = off (for production use)
// 1 = client messages
// 2 = client and server messages
$mail->SMTPDebug =0;
//Ask for HTML-friendly debug output
$mail->Debugoutput = 'html';
//Set the hostname of the mail server
$mail->Host = $ConHost ;
// if your network does not support SMTP over IPv6
//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
$mail->Port = $ConPORT;
//Set the encryption system to use - ssl (deprecated) or tls
$mail->SMTPSecure = 'tls';
//Whether to use SMTP authentication
$mail->SMTPAuth = true;
//Username to use for SMTP authentication - use full email address for gmail
$mail->Username = $Conusername;
//Password to use for SMTP authentication
$mail->Password = trim(decrypt($Conpassword));
//Set who the message is to be sent from
$mail->setFrom($Conusername,'SmartERP');
//Set an alternative reply-to address
//$mail->addReplyTo('No-Reply','SmartERP');
//Set who the message is to be sent to
$mail->addAddress($_POST['getemail'],$names);
//Set the subject line

$ConfirmationURL = $_SERVER["SERVER_NAME"].':'.$_SERVER["SERVER_PORT"] ;
$ConfirmationURL = htmlspecialchars($ConfirmationURL.'/index.php?link=UserSettings',ENT_QUOTES,'UTF-8');

$mail->Subject = 'Your ERP,  Login Credentials';

$mailbody="Dear ".$names."<br/>"
        . "Mobile No=".$pf_no."<br/>"
        . "Once you Login please Change your password.<br/>"
        . "Your user ID: ".$userid ."<br/>"
        . "Your password is : ". trim($userpassword)."<br/>"
        . "Use this URL https://".$ConfirmationURL ."<br/>";
//Replace the plain text body with one created manually

$mail->WordWrap = 78;
$mail->msgHTML($mailbody, dirname(__FILE__), true); //Create message bodies and embed images
//$mail->AltBody = $mailbody;
//send the message, check for errors
if (!$mail->send()) {
    
   echo "Mailer Error: " . $mail->ErrorInfo;
} else {
    
    $password = rand(999,9856245);
    Resetpassword($_POST['getemail'],$password);
    echo "Message sent!";
}

}else{
     echo 'The system cannot find this email address';
}



function encrypt($data){
 // Store cipher method
$ciphering = "BF-CBC";
 
$options = 0;
// Use random_bytes() function which gives
// randomly 16 digit values
$encryption_iv = "12345678";
// Alternatively, we can use any 16 digit
// characters or numeric for iv
$encryption_key = "12345678";
// Encryption of string process starts
$encryption = openssl_encrypt($data, $ciphering,$encryption_key, $options, $encryption_iv);

return $encryption;
}


function decrypt($data){
// Store cipher method
$ciphering = "BF-CBC";

$options = 0;

$decryption_iv ="12345678" ;
// Store the decryption key
$decryption_key = "12345678";
// Descrypt the string
$decryption = openssl_decrypt ($data, $ciphering,$decryption_key, $options,$decryption_iv);

return $decryption;
}
