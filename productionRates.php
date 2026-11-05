<?php

$PageSecurity=0;

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Production Pricing');
include('includes/header.inc');

$mbflag = array();
$mbflag[1]='Stock type';
$mbflag[2]='Consumable';
$mbflag[3]='Service type';
$mbflag[4]='Assemble';
$mbflag[5]='Kit';
$mbflag[6]='Container';
$thispage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');

if($_GET['StaffID']){
    $StaffID=$_GET['StaffID'];
}elseif($_POST['StaffID']){
    $StaffID=$_POST['StaffID'];
}

if(isset($_POST['cancel'])){
    unset($StaffID);
    unset($_POST['submit']);
}

If(isset($_POST['submit'])){
    $InsertSQL=array();
     
    DB_Txn_Begin($db);
    
    $InsertSQL[] =sprintf("Delete from [EmployeeProductionRates] where [StaffID]='%s'",$_POST['StaffID']);
   
   
    foreach ($_POST['Produce'] as $key => $value) {
        if($value>0){
            $InsertSQL[] = sprintf("INSERT INTO [EmployeeProductionRates]
           ([StaffID],[StockID],[Rate],[UOM]) VALUES ('%s','%s',%f,'%s')",
            $_POST['StaffID'],$key,$_POST['Produce'][$key],'looseqty');
         }
    
    }
           
    foreach ($InsertSQL as $SQL) {
      DB_query($SQL, $db);
    }
   
    if(DB_error_no($Conn)) {
        DB_Txn_Rollback($db);
    } else{
        DB_Txn_Commit($db);
    }      
            
}
    
if(isset($StaffID)){

$result = DB_query("Select * from productionEmployee where code='".$StaffID."'", $db);
if(DB_num_rows($result)==0){
    Die('This code is not correct');
}else{
    $myrows= DB_fetch_array($result);
    $_POST['customer']=trim($myrows['salesman']);
}
    
echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/Inventory.png" title="' . _('Production Rates for:').'<B>'.$_POST['customer']. '</B>'.
	'" alt="" />' . ' ' . _('Production Rates for:') .'<B>'.$_POST['customer']. '</B></p>';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
      <input type="hidden" name="StaffID" value="' . $StaffID . '" />
     <div class="container"><table id="myStockTable"  class="table table-striped table-bordered"><thead>
     <tr><th class="ascending">Stock Code</th>'
        . '<th><label>Inventory Name</label><input type="text" tabindex="1" class="myInput" id="myStockInput" onkeyup="myRatesFunction()" placeholder="Search for names..">'
        . '</th>'
        . '<th>Type</th>'
        . '<th>Is Obsolete</th>'
        . '<th>Price</th>'
        . '</tr></thead>';

    $results=DB_query("select 
    stockmaster.isstock,stockmaster.itemcode, stockmaster.descrip, stockmaster.inactive,EmployeeProductionRates.Rate as 'averagestock',
    stockmaster.partperunit, stockmaster.[postinggroup], unitfull.descrip as Fulqty,unitloos.descrip as Loosqty
    from stockmaster 
    left join EmployeeProductionRates on stockmaster.itemcode=StockID and EmployeeProductionRates.StaffID='".$StaffID."' 
    left join unit unitfull on stockmaster.units=unitfull.code 
    left join unit unitloos on stockmaster.subunits=unitloos.code
    order by descrip asc", $db);
       
  
       while($rows=DB_fetch_array($results)){
           echo  '<tr>';
           
           echo sprintf('<td>%s</td>', trim($rows['itemcode']));      
           if($rows['isstock']==3){
               echo '<td><b>'.$rows['descrip'].'</b></td>';
           }else{
               echo '<td><b>'.$rows['descrip'].'</b><i>('.number_format($rows['partperunit'],0).' '.$rows['Loosqty'].' = 1 '.$rows['Fulqty'].')</i></td>';
            }
           echo '<td>'. $mbflag[$rows['isstock']].'</td>';
           echo '<td>'. ($rows['inactive']==true?'YES':"NO").'</td>';
           echo '<td><input type="text" class="number"  name="Produce['.trim($rows['itemcode']).']" value="'. number_format($rows['averagestock'],2).'"/></td>'
           . '</tr>';
            
       }        

echo '</tbody></table></div><br /><div class="centre">
        <input type="submit" name="submit" value="' . _('Update') . '" />
        <input type="submit" name="cancel" value="' . _('CANCEL') . '" />
    </div></form>';
}else{
    
echo '<div class="container"><table class="table table-bordered">'
        . '<thead><TR><th>Account Code</th>'
        . '<th>Name</th>'
        . '<th>Telephone No</th>'
        . '<th>Email</th>'
        . '<th>Target P/M</th>'
        . '<th>Supervised By</th>'
        . '</tr></thead><tbody>';
      
       $results=DB_query('Select productionEmployee.code,productionEmployee.salesman,'
               . 'productionEmployee.phone,productionEmployee.email,productionEmployee.target,'
               . 'productionManager.salesman as manager'
               . ' from productionEmployee left join productionManager on productionEmployee.manager=productionManager.code', $db);
       $k=0;
       while($rows=DB_fetch_array($results)){
           
           echo sprintf('<tr><td><a href="productionEmployee.php?Modify=%s">%s</a></td>',trim($rows['code']),trim($rows['code']));
            echo '<td>'.$rows['salesman'].'</td>';
            echo '<td>'.trim($rows['phone']).'</td>';
            echo '<td>'.trim($rows['email']).'</td>'
               . '<td>'.number_format($rows['target'],2).'</td>'
               . '<td>'.$rows['manager'].'</td>';
           echo sprintf('<td><a href="'.$thispage.'?StaffID=%s">Modify Rates</a></td>',trim($rows['code']));
               echo '</tr>';
            
       }        

echo '</tbody></table></div>';


}

include('includes/footer.inc');


?>
