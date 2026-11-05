<?php
   /* $Id: ConnectDB_mssql.inc 6310 2014-08-06 14:41:50Z Jonathan Kiranga $ */
define ('LIKE','LIKE');
session_write_close(); //in case a previous session is not closed
session_name('smartlimserp');
session_start();

include('../config.php');
include('../includes/DateFunctions.inc');
require_once('../includes/MiscFunctions.php');

if (!isset($mysqlport)){
	$mysqlport = 3306;
}
global $db,$mysqli;
// Make sure it IS global, regardless of our context
       
$mysqli = new mysqli($host , $DBUser, $DBPassword,$_SESSION['DatabaseName']);

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


   if(isset($_GET['Customerfind'])){
       getcustomers($_GET['offset'],$_GET['height'],$_GET['Customerfind']);
   }elseif(isset($_POST['Customerfind'])){
       getcustomers($_POST['offset'],$_POST['height'],$_POST['Customerfind']);
   }
   
   function getcustomers($offset,$height,$value){
     Global $db;
   
          
           $SQL = "SELECT itemcode,customer,[curr_cod],IFNULL([salesman],'') as salesman 
                     from debtors where inactive=0 order by customer";
     
        
    $left = $offset['left'];
    
    $return= '<div id="findcustomer" class="finderheader" style="top:20vh; left:'. $left .'px;">'
           . '<div class="form-control"><label>Customer search</label></div>'
            . '<div class="finder"><table id="mycustomersTable" class="finder table-collapsed">';
    
            $ResultIndex = DB_query($SQL, $db);
            while($row=DB_fetch_array($ResultIndex)){
               
                $return .= sprintf('<tr><td onclick="selectcustomer(\'%s\',\'%s\',\'%s\',\'%s\')"><label>%s</label></td></tr>', 
                trim($row['itemcode']),trim($row['customer']),trim($row['curr_cod']),trim($row['salesman']),trim($row['customer'])) ;
            }
            
            $return .= '</table></div>'
          . '<input type="text" tabindex="1" class="myInput" id="mycustomersInput" onkeyup="mycustomersFunction()" autofocus="autofocus" placeholder="Search for names..">'
          . '<button onclick="selectcustomer(\'\',\'\',\'\',\'not\')">Cancel</button>'
          . '</div>';
                   
        echo $return;
   }
    
   if(isset($_GET['Vendorfind'])){
       GetVendors($_GET['offset'],$_GET['height'],$_GET['Vendorfind']);
   }elseif(isset($_POST['Vendorfind'])){
       GetVendors($_POST['offset'],$_POST['height'],$_POST['Vendorfind']);
   }
   
   function GetVendors($offset,$height,$value){
        Global $db;
   
   $ResultIndex = DB_query("SELECT itemcode,customer,[curr_cod] from creditors where inactive=0 order by customer", $db);
  
    $left = $offset['left'];

    
    $return = '<div id="findVendor" class="finderheader" style="top:20vh; left:'. $left .'px;">
            <div>Vendor Search</div><div class="finder"><table id="myVendorTable" class="finder table-collapsed">';
            while($row=DB_fetch_array($ResultIndex)){
                $return .= sprintf('<tr><td onclick="selectvendor(\'%s\',\'%s\',\'%s\')"><label>%s</label></td></tr>', 
                trim($row['itemcode']),trim($row['customer']),trim($row['curr_cod']),trim($row['customer'])) ;
            }
            $return .= '</table></div>'
                    . '<input type="text" class="myInput"  id="myVendorInput" onkeyup="myVendorFunction()" autofocus="autofocus" placeholder="Search for names..">'
          . '<button class="finder" onclick="selectvendor(\'\',\'\',\'\')">Cancel</button></div>';
        echo $return;
   }
   
   if(isset($_GET['stocktransferitemcode'])) {
      ShowStockUOM($_GET['stocktransferitemcode']);
  }elseif(isset($_POST['stocktransferitemcode'])){
      ShowStockUOM($_POST['stocktransferitemcode']);
  }
  
   
   if(isset($_GET['Jobfind'])){
      Jobfind($_GET['offset'],$_GET['height'],$_GET['Jobfind']);
   }elseif(isset($_POST['Jobfind'])){
      Jobfind($_POST['offset'],$_POST['height'],$_POST['Jobfind']);
   }
   
   function Jobfind($offset,$height,$value){
 Global $db;
         
    $ResultIndex = DB_query("SELECT itemcode,descrip from stockmaster where inactive=0 and (isstock=3) order by descrip", $db);
     
    $left = $offset['left'];
     
    $return= '<div id="findJob" class="finderheader"  style="top:20vh; left:'. $left .'px;">'
            . '<div><label>List Services we Provide</label></div>'
            . '<div class="finder"><table id="myStockTable" class="finder table-collapsed">';
            
    while($row=DB_fetch_array($ResultIndex)){
    $return .= sprintf('<tr><td onclick="selectservice(\'%s\',\'%s\');CalculateForm(salesform.submit)"><div>%s</div></td></tr>', 
    trim($row['itemcode']),trim($row['descrip']),trim($row['descrip'])) ;
    }
    
   $return .= '</table></div>'
                . '<input type="text" tabindex="1" class="myInput" id="myStockInput" onkeyup="myStockFunction()"  autofocus="autofocus" placeholder="Search for names..">'
                . '<button onclick="selectservice(\'\',\'\')">Cancel</button></div>';
 
   echo $return;
 }
   
   if(isset($_GET['Stockfind'])){
       Stockfind($_GET['offset'],$_GET['height'],$_GET['Stockfind']);
   }elseif(isset($_POST['Stockfind'])){
       Stockfind($_POST['offset'],$_POST['height'],$_POST['Stockfind']);
   }
   
   function Stockfind($offset,$height,$value){
 Global $db;
         
    $ResultIndex = DB_query("SELECT itemcode,descrip from stockmaster where inactive=0 order by descrip", $db);
     
    $left = $offset['left'];
    
    $return= '<div id="findStock" class="finderheader" style="top:20vh; left:'. $left .'px;">'
            . '<div><label>Inventory Search</label></div>'
            . '<div  class="finder"><table id="myStockTable" class="finder table-collapsed">';
            
    while($row=DB_fetch_array($ResultIndex)){
    $return .= sprintf('<tr><td onclick="selectInventory(\'%s\',\'%s\');CalculateForm(salesform.submit)"><div>%s</div></td></tr>', 
    trim($row['itemcode']),trim($row['descrip']),trim($row['descrip'])) ;
    }
    
   $return .= '</table></div>'
           . '<input type="text" tabindex="1" class="myInput"  id="myStockInput" onkeyup="myStockFunction()" autofocus="autofocus" placeholder="Search for names..">'
           . '<button onclick="selectInventory(\'\',\'\')">Cancel</button></div>';
 
   echo $return;
 }
   
   if(isset($_GET['Assetfind'])){
       Assetfind($_GET['offset'],$_GET['height'],$_GET['Assetfind']);
   }elseif(isset($_POST['Assetfind'])){
       Assetfind($_POST['offset'],$_POST['height'],$_POST['Assetfind']);
   }
   
   function Assetfind($offset,$height,$value){
        Global $db;
     
    $ResultIndex = DB_query("SELECT [assetid],[description],[longdescription]  from fixedassets  order by description", $db);
    $left = $offset['left'];
    
        $return= '<div id="AssetStock"  class="finderheader"  style="top:20vh; left:'. $left .'px;">'
                . '<div><label>Assets Search</label></div>'
                . '<div class="finder"><table id="myAssetTable"  class="finder table-collapsed">';
       
            
            while($row=DB_fetch_array($ResultIndex)){
            $return .= sprintf('<tr><td onclick="selectFixedassets(\'%s\',\'%s\');CalculateForm(salesform.submit)"><div>%s</div></td></tr>', 
            trim($row['assetid']),trim($row['description']),trim($row['description'])) ;
            }
           $return .= '</table></div>'
                . '<input type="text" tabindex="1" class="myInput"  id="myAssetInput" onkeyup="myAssetFunction()" autofocus="autofocus" placeholder="Search for names..">'
                . '<button style="float:right;" onclick="selectFixedassets(\'\',\'\')">Cancel</button></th>'
                . '</div>';
      
        echo $return;
   }
   
  if(isset($_GET['EmployeeNamefind'])){
       EmployeeNamefind($_GET['offset'],$_GET['height'],$_GET['EmployeeNamefind']);
   }elseif(isset($_POST['EmployeeNamefind'])){
       EmployeeNamefind($_POST['offset'],$_POST['height'],$_POST['EmployeeNamefind']);
   }
   
   function EmployeeNamefind($offset,$height,$value){
 Global $db;
         
    $ResultIndex = DB_query("SELECT code,salesman from productionEmployee", $db);
    $left = $offset['left'];
    
    $return= '<div id="EmployeeNamefind" class="finderheader"  style="top:20vh; left:'. $left .'px;">'
           . '<div><label>Employee Search</label></div><div class="finder">'
            . '<table id="myEmployeeTable" class="finder table-collapsed">';
    while($row=DB_fetch_array($ResultIndex)){
    $return .= sprintf('<tr><td onclick="selectemployee(\'%s\',\'%s\');CalculateForm(salesform.submit)">'
            . '<div>%s</div></td></tr>', trim($row['code']),trim($row['salesman']),trim($row['salesman'])) ;
    }
    $return .= '</table></div>'
            . '<input type="text" tabindex="1" class="myInput" id="myEmployeeInput" onkeyup="myEmployeeFunction()" autofocus="autofocus" placeholder="Search for names..">'
            . '<button onclick="selectemployee(\'\',\'\')">Cancel</button></div>';
 
   echo $return;
 }
 
   if(isset($_GET['Samplefind'])){
       Samplefind($_GET['offset'],$_GET['height'],$_GET['Samplefind']);
   }elseif(isset($_POST['Samplefind'])){
       Samplefind($_POST['offset'],$_POST['height'],$_POST['Samplefind']);
   }
   
   function Samplefind($offset,$height,$value){
    Global $db;
         
    $ResultIndex = DB_query("select [sampletypeid],[sampletypeName] FROM [SampleTypes] order by sampletypeName", $db);
    $top  =  $offset['top'];
    $left = $offset['left'];
    
    $return= '<div id="findStock" class="finderheader"  style="top:20vh; left:'. $left .'px;">'
        . '<div><label>Sample Types</label></div><div class="finder">'
            . '<table id="myStockTable" class="finder table-collapsed">';
            
    while($row=DB_fetch_array($ResultIndex)){
    $return .= sprintf('<tr>'
            . '<td onclick="selectInventory(\'%s\',\'%s\');CalculateForm(salesform.submit)"><div>%s</div></td></tr>', 
            trim($row['sampletypeid']),trim($row['sampletypeName']),trim($row['sampletypeName'])) ;
    }
    
   $return .= '</table></div>'
        . '<input type="text" tabindex="1" class="myInput"  id="myStockInput" onkeyup="myStockFunction()" autofocus="autofocus" placeholder="Search for names..">'
        . '<button onclick="selectInventory(\'\',\'\')">Cancel</button></div>';
 
   echo $return;
 }
   
 
 
  if(isset($_GET['Chartfind'])){
       Chartfind($_GET['offset'],$_GET['height'],$_GET['Chartfind']);
   }elseif(isset($_POST['Chartfind'])){
       Chartfind($_POST['offset'],$_POST['height'],$_POST['Chartfind']);
   }
   
   function Chartfind($offset,$height,$value){
    Global $db;
    
    $AccountType=array();
    $AccountType[0]="Posting";
    $AccountType[1]="Heading";
    $AccountType[2]="Total";
    $AccountType[3]="Begin-Total";
    $AccountType[4]="End-Total";
    
    $BalanceSheet=array();
    $BalanceSheet[0]="Balance Sheet";
    $BalanceSheet[1]="Profit and Loss";
               
     if($value=='reload'){
         $java ='ReloadForm(Journal.update);';
             $ResultIndex=DB_query("Select accno,accdesc,ReportCode,ReportStyle,[balance_income] 
              from acct join GLpostinggroup on GLpostinggroup.code=acct.postinggroup
              order by ReportCode,accdesc",$db);
     }else{
         $java='';
         $ResultIndex=DB_query("Select accno,accdesc,ReportCode,ReportStyle,[balance_income] from acct order by ReportCode,accdesc",$db);
     }
     
    $top  =  $offset['top'];
    $left = $offset['left'];
    
    $return= '<div class="finderheader" id="findSchart" style="top:top:20vh; left:350px;">'
             . '<b>Search for Account:</b><div class="finder">'
            . '<table id="myAccountTable" class="finder table-collapsed">';
    
            while($row=DB_fetch_array($ResultIndex)){
               $accdesc= trim($row['accdesc']);
                if($row['ReportStyle']==0){
                    $toclick=sprintf('<td onclick="selectaccount(\'%s\',\'%s\');%s">%s</td>',trim($row['accno']),$accdesc,$java,$accdesc);
                }else{
                   $toclick=sprintf('<td onclick="alert(\'This Account is not a posting account\');">%s</td>',$accdesc);
                }
             $return .= sprintf('<tr>%s<td>%s</td><td>%s</td></tr>',$toclick,$BalanceSheet[$row['balance_income']],$AccountType[$row['ReportStyle']]) ;
           }
           
    $return .= '</table></div>'
    . '<input type="text" tabindex="1" class="myInput" id="myAccountInput" onkeyup="myAccountFunction()"  autofocus="autofocus" placeholder="Search for account..">'
    . '<input type="button" onclick="findSchart.setAttribute(\'style\',\'visibility:hidden;display:none\');" value="Cancel"/>
   </div>';
 
   echo $return;
 }
   function ShowStockUOM($stockcode){
      global $db;
      
        $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty  "
                . "FROM [stockmaster] left join [unit] f on [stockmaster].[units]=f.code "
                . "  left join [unit] l on [stockmaster].[subunits]=l.code "
                . " where itemcode= '".$stockcode."'", $db);
        
        $rows= DB_fetch_row($REsults);
        $UOMline = '<option value="fulqty" >'.$rows[0].'</option><option value="loosqty" >'.$rows[1].'</option>';
        
        echo $UOMline;
        
  }
  
  
  if(isset($_POST['LabPrefix'])){
    CheckLabPrefix($_POST['LabPrefix']);
  }elseif(isset($_GET['LabPrefix'])){
    CheckLabPrefix($_GET['LabPrefix']);
 }
  
Function CheckLabPrefix($Lab){
    global $db;
    
    $SQL = SPRINTF("UPDATE systypes_1 SET typeno = 0,prefix='%s' WHERE typeid = '%s' ",trim($Lab),'10');
    $UpdTransNoResult = DB_query($SQL,$db);
 
    echo 'Done';
}
