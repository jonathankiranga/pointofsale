<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('transactions/stockbalance.inc');
include('transactions/poscart.inc');
 
$POSclass = new Pos();

$Title = _('Sales Order');
include('includes/header.inc');   

echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/sales.png" title="' . _('Sales Order') .'" alt="" />' . ' ' . _('Sales Order') . '</p>';
echo '<form action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />'
        . '<div class="centre">';


if($_POST['submit']=="Enter Delivery Details and Confirm Order"){
    if($_SESSION['Grossamounttotal']==0){
       $_POST['submit']="Empty";
    }
}
    
if(isset($_POST['confirm'])){
            
    DB_query("UPDATE [SalesHeader]  SET [released] = 1 where documentno='".$_SESSION['CompleteDocument']."'", $db);
        
    DB_query("UPDATE [SalesLine]  SET [completed] = 1 where documentno='".$_SESSION['CompleteDocument']."'", $db);
    
    prnMsg('Sales order :'.$_SESSION['CompleteDocument'].' has been saved.'
            . ' Click Here to Print'.'<a href="PDFPrintSalesOrder.php?No='.$_SESSION['CompleteDocument'].'">Print order '.$_SESSION['CompleteDocument'].'</a>');
       
    unset($_POST);
}

if(isset($_POST['Delete'])){
    DB_query("Delete from [SalesLine] where documentno='".$_SESSION['CompleteDocument']."' and [documenttype]='1' ", $db);
          
    DB_query("Delete from [SalesHeader] where documentno='".$_SESSION['CompleteDocument']."' and [documenttype]='1' ", $db);
            
    prnMsg('Sales order :'.$_SESSION['CompleteDocument'].' has been Deleted');
          
    unset($_POST);
}

if($_POST['submit']=='Add New Line'){
     
    unset($_POST['stockitemcode']);
    unset($_POST['stockname']);
     $POSclass->Nextorder();
}

if($_POST['submit']=="Enter Delivery Details and Confirm Order"){
        include('transactions/customerreadonly.inc');
} else {
    
    if(isset($_GET['new'])){
         $_SESSION['locked'] = true;
         $_POST['documentno'] = GetNextTransNo(1,$db);
         prnMsg('Sales order :'.$_POST['documentno'].' has been created');
         $POSclass->neworder();
    }
    
    $ResultIndex = DB_query('Select sysdate() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);

    if(!isset($_POST['date'])){
        $_POST['date']= ConvertSQLDate($rowdate[0]);
    }

$ResultIndex = DB_query('Select sysdate()+1 as date ',$db);
$rowdate = DB_fetch_row($ResultIndex);

if(!isset($_POST['datedue'])){
    $_POST['datedue']= ConvertSQLDate($rowdate[0]);
}
echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?new=1">Click here to Create New Sales Order Number Refence</a>';

echo '</div><div class="container-fluid">'
        . '<table class="table table-bordered"><caption>Sales Order Header Details</caption>';

echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Date of order</td><td><input tabindex="2" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="Salesoderdate" size="11" maxlength="10"   value="' .$_POST['Salesoderdate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Date Due</td><td><input tabindex="3" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="datedue" size="11" maxlength="10" required="required"  value="' .$_POST['datedue']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';

echo '<tr><td>Document No</td>'
        . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  size="5" required="required" /></td>'
        . '<td>Your Reference</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td></tr>';

echo '<tr><td>Customer ID</td>'
        . '<td><input tabindex="4" type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"  required="required" />'
        . '<input type="button" id="searchcustomer" value="Search Customer"/></td>'
        . '<td>Customer Name</td>'
        . '<td colspan="3"><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="20"  required="required" /></td></tr>';

echo '<tr><td>Currency Code</td><td>'
   . '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode"  value="'.$_POST['currencycode'].'" readonly="readonly"/></td></tr>';

echo '<tr><td>Sales Rep</td><td><select tabindex="7" name="salespersoncode" id="salespersoncode">'
   . '<option value="not">Not selected</option>';

$ResultIndex=DB_query("SELECT [code],[salesman],[commission],[inactive] "
        . " FROM [salesrepsinfo] where [inactive] is null or [inactive]=0 ", $db);

while($row=DB_fetch_array($ResultIndex)){
   echo sprintf('<option value="%s" %s >%s</option>',
           $row['code'],($_POST['salespersoncode']==$row['code']?'selected="selected"':''),
           $row['salesman']);
}
    
echo '</select></td></tr>';

echo '<tr><td>Find Stock</td>'
    . '<td colspan="3"><input type="hidden" id="stockitemcode" name="stockitemcode" value="'.$_POST['stockitemcode'].'" />'
    . '<input type="text" id="stockname" size="20" name="stockname" value="'.$_POST['stockname'].'" />'
    . '<input type="button" id="searchstock" value="Search stock"/></td></tr>';

echo '</table>';

include('transactions/stocklist.inc');

echo '</DIV>';
}

echo '</div></form>' ;

include('includes/footer.inc');

?>