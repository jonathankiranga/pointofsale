<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('purchases/stockbalance.inc');
include('purchases/puchasescart.inc');
include('includes/DimensionClass.inc');

$POSclass = new Pos();

$Title = _('Purcahse Order');
include('includes/header.inc');   

echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/supplier.png" title="' . _('Purchase Order') .'" alt="" />' . ' ' . _('Purchase Order') . '</p>';
echo '<form action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"  id="salesform">';
echo '<div class="card">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

if($_POST['submit']=="Enter Delivery Details and Confirm Order"){
    if($_SESSION['Grossamounttotal']==0){
      $_POST['submit']="Empty";
    }
}

if(isset($_POST['confirmservice'])){
    DB_query("UPDATE [PurchaseHeader] SET [released] = 1,[documenttype]=20 where documentno='".$_SESSION['CompleteDocument']."'", $db);
    DB_query("UPDATE [PurchaseLine] SET [completed] = 1,[documenttype]=20 where documentno='".$_SESSION['CompleteDocument']."'", $db);
    prnMsg('Purchase order :'.$_SESSION['CompleteDocument'].' has been saved.'
         . ' Click Here to Print'.'<div class="centre">'
            . '<a href="PDFPrintPurchaseOrder.php?No='.$_SESSION['CompleteDocument'].'">Print Purchase order '.$_SESSION['CompleteDocument'].'</a></div>');
    unset($_POST);

}
    
if(isset($_POST['confirm'])){
    DB_query("UPDATE [PurchaseHeader] SET [released] = 1 where documentno='".$_SESSION['CompleteDocument']."'", $db);
    DB_query("UPDATE [PurchaseLine] SET [completed] = 1 where documentno='".$_SESSION['CompleteDocument']."'", $db);
    prnMsg('Purchase order :'.$_SESSION['CompleteDocument'].' has been saved.'
         . ' Click Here to Print'.'<div class="centre">'
            . '<a href="PDFPrintPurchaseOrder.php?No='.$_SESSION['CompleteDocument'].'">Print Purchase order '.$_SESSION['CompleteDocument'].'</a></div>');
    unset($_POST);
}

if(isset($_POST['Delete'])){
    DB_query("Delete from [PurchaseLine] where documentno='".$_SESSION['CompleteDocument']."' and [documenttype]='18'", $db);
    DB_query("delete from [PurchaseHeader] where documentno='".$_SESSION['CompleteDocument']."' and [documenttype]='18'", $db);
    prnMsg('Purchase order :'.$_SESSION['CompleteDocument'].' has been Deleted.');
    unset($_POST);
}

if($_POST['submit']=='Add New Line'){
    unset($_POST['stockitemcode']);
    unset($_POST['stockname']);
   $POSclass->Nextorder();
  
}


       
if(!isset($_POST['documentno'])){
    $_SESSION['locked'] = true;
    $_POST['documentno'] = GetTempLabrefNo(18,$db);
    $POSclass->neworder();
}


if($_POST['submit']=="Enter Delivery Details and Confirm Order"){
        include('purchases/vendorreadonly.inc');
        $POSclass->neworder();
 } else {
          
$ResultIndex = DB_query('Select sysdate() as date ',$db);
$rowdate = DB_fetch_row($ResultIndex);
if(!isset($_POST['date'])){
    $_POST['date']= ConvertSQLDate($rowdate[0]);
}
##DATE_ADD("2017-06-15", INTERVAL 10 DAY); 
$ResultIndex = DB_query('Select Date_add(sysdate(),INTERVAL 1 DAY) as date ',$db);
$rowdate = DB_fetch_row($ResultIndex);
if(!isset($_POST['datedue'])){
    $_POST['datedue']= ConvertSQLDate($rowdate[0]);
    $_POST['Purchaseoderdate']= $_POST['datedue'];
}

echo '</div><div class="card-body">'
 . '<table class="table-bordered"><caption>Purchase Order Header Details</caption>';
echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Date of order</td><td><input tabindex="2" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="Purchaseoderdate" size="11" maxlength="10"   value="' .$_POST['Purchaseoderdate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Date Due</td><td><input tabindex="3" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="datedue" size="11" maxlength="10"   value="' .$_POST['datedue']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';

echo '<tr><td>Document No</td>'
        . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  size="5" required="required"/></td>'
        . '<td>Your Reference</td>'
        . '<td colspan="3"><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td></tr>';

echo '<tr><td>Supplier ID</td>'
        . '<td><input tabindex="4" type="text" name="VendorID" id="VendorID" value="'.$_POST['VendorID'].'"  size="5" readonly="readonly"  required="required" />'
        . '<input type="button" id="searchvendor" value="Search for Vendor"/></td>'
        . '<td>Supplier Name</td>'
        . '<td colspan="3"><input tabindex="5" type="text" name="VendorName" id="VendorName" value="'.$_POST['VendorName'].'"  size="50"  required="required" /></td></tr>';

echo '<tr><td>Supplier Currency</td><td colspan="5">'
. '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td></tr>';

echo '<tr>'.$_SESSION['SelectObject']['dimensionone'];
echo $_SESSION['SelectObject']['dimensiontwo'].'<td colspan="2"></td></tr>';

echo '<tr><td>Find Stock</td>'
    . '<td colspan="5"><input type="hidden" id="stockitemcode" size="5" name="stockitemcode" value="'.$_POST['stockitemcode'].'" />'
    . '<input type="text" id="stockname" size="30" name="stockname" value="'.$_POST['stockname'].'" />'
    . '<input type="button" id="searchstock" value="Search stock"/></td></tr>';
 

echo '<table class="table-bordered"><thead><tr>'
        . '<th>Stock Code</th>'
        . '<th>Stock Description</th>'
        . '<th>Unit Of Measure (UOM)</th>'
        . '<th>Part per Unit(@ X 1)</th>'
        . '<th class="number">Quantity to Order</th>'
        . '<th class="number">Price for the selected UOM</th>'
        . '<th class="number">Net Amount</th>'
        . '<th class="number">VAT Amount</th>'
        . '<th class="number">Gross Amount</th></tr></thead>';

$itemcode = $_POST['stockitemcode'];
$POSclass->Getitems($_POST['stockitemcode'],$_POST['quantity'][$itemcode],$_POST['salesprice'][$itemcode]);

echo '</table>';
echo '<div class="rightside">
	<input type="submit" name="submit" value="' . _('Re-Calculate') . '" />
        <input type="submit" name="submit" value="' . _('Add New Line') . '" />
	<input type="submit" name="submit" value="' . _('Enter Delivery Details and Confirm Order') . '"  />
</div>';

echo '</td></tr>'
. '</table>';

}

echo '</div></form>' ;

include('includes/footer.inc');
 
?>