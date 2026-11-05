<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/DimensionClass.inc');

$Title = _('Sales Invoice');
include('includes/header.inc');   
include('transactions/stockbalance.inc');  


$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
    
echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/sales.png" title="' . _('Sales Invoice') .'" alt="" />' . ' ' . _('Service Invoice') . '</p>';

if(isset($_GET['ref'])){
    $_POST['documentno'] = $_GET['ref'];
    $_SESSION['DocumentPosted']=false;
    $_SESSION['DocumentPicking']=false;
}
    
if(isset($_POST['submit']) and $_POST['submit']=='Confirm Invoice'){
    include('transactions/Saveserviceinvoice.inc');  
}

echo '<a href="'.htmlspecialchars('ConfirmServiceInvoice.php',ENT_QUOTES,'UTF-8').'">Go to Service orders list</a>';
        
$filter="SELECT 
            [documenttype]
           ,[documentno]
           ,[docdate]
           ,[oderdate]
           ,[duedate]
           ,[postingdate]
           ,[customercode]
           ,[customername]
           ,[yourreference]
           ,[externaldocumentno]
           ,[locationcode]
           ,[paymentterms]
           ,[postinggroup]
           ,[currencycode]
           ,[salespersoncode]
           ,[vatinclusive]
       FROM [SalesHeader] 
       where [documentno]='".$_POST['documentno']."'";
$ResultIndex= DB_query($filter, $db);
$rowresults = DB_fetch_row($ResultIndex);
    
    $_POST['date'] = is_null($rowresults[2])?'': ConvertSQLDate($rowresults[2]);
    if(!isset($_POST['Salesoderdate'])){
        $_POST['Salesoderdate']= is_null($rowresults[3])?'': ConvertSQLDate($rowresults[3]);
    }
  
    $_POST['datedue'] = is_null($rowresults[4])?'': ConvertSQLDate($rowresults[4]);
    
    if(!isset($_POST['reference'])){
        $_POST['reference'] = $rowresults[8];
    }
    
    $_POST['CustomerID'] = $rowresults[6];
    $_POST['CustomerName']= $rowresults[7];
    $_POST['currencycode']= $rowresults[13];
    
    if(!isset($_POST['salespersoncode'])){
        $_POST['salespersoncode']= $rowresults[14];
    }
    
    $_POST['documentno'] = $rowresults[1];
  
echo '<form action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

echo '<table class="table table-bordered" cellspacing="4"><tr><td valign="top">'
        . '<table class="table table-bordered"><caption>Sales Invoice Header Details</caption>';

echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Date of order</td><td><input tabindex="2" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="Salesoderdate" size="11" maxlength="10"  readonly="readonly" value="' .$_POST['Salesoderdate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Date Due</td><td><input tabindex="3" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="datedue" size="11" maxlength="10" readonly="readonly"  value="' .$_POST['datedue']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';

echo '<tr><td>Document No</td>'
        . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  size="5" readonly="readonly"/></td>'
        . '<td>Your Reference</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td></tr>';

echo '<tr><td>Customer ID</td>'
        . '<td><input tabindex="4" type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"  readonly="readonly"/>'
        . '<input type="button" id="searchcustomer" value="Search Customer"/></td>'
        . '<td>Customer Name</td>'
        . '<td colspan="3"><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  required="required" /></td></tr>';

echo '<tr><td>Currency Code</td><td>'
. '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';

echo '<td>Sales Rep</td><td><select tabindex="7" name="salespersoncode" id="salespersoncode">'
. '<option value="not">Not selected</option>';

$ResultIndex=DB_query("SELECT [code],[salesman],[commission],[inactive] FROM [salesrepsinfo] where [inactive] is null or [inactive]=0 ", $db);

while($row=DB_fetch_array($ResultIndex)){
        echo sprintf('<option value="%s"  %s >%s</option>',$row['code'], ($_POST['salespersoncode']==$row['code']?'selected="selected"':''),$row['salesman']);
}
    
echo '</select></td></tr>';
echo $_SESSION['SelectObject']['dimensionone'];
echo $_SESSION['SelectObject']['dimensiontwo'];
echo '</table></td></tr><tr><td>';

$runningnettotal = 0;
$runningvattotal = 0;
$runninggrosstotal = 0;
    
$sqldebtors=DB_query("SELECT [itemcode] ,[creditlimit],[customer]
      ,[phone] ,[email] ,[city] ,[country],[curr_cod],[customerposting],[salesman],[VATinclusive],[IsTaxed]
       FROM [debtors] join postinggroups on code=[customerposting] where itemcode='".$_POST['CustomerID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[8];
$VATinclusive = $debtorsrow[10];
$IsTaxed = $debtorsrow[11];

if(isset($_GET['line'])) {
   $results = DB_query("Delete from [SalesLine] where [entryno]='".$_GET['line']."'",$db);
}
      
$Slaqry ="SELECT [entryno],[documenttype],[docdate] ,[documentno] ,[locationcode]
      ,[stocktype],[code] ,[description],[unitofmeasure],[Quantity],[UnitPrice],[vatamount],[invoiceamount]
      ,[completed],[printed] ,[vatrate] ,[inclusive],stockmaster.[partperunit]
      ,stockmaster.[averagestock],[UOM]
  FROM [SalesLine] join stockmaster on [SalesLine].[code]=stockmaster.itemcode
 where [documentno]='".$_POST['documentno']."' 
     group by  
       [entryno],[documenttype],[docdate],[documentno]
      ,[locationcode],[stocktype],[code] ,[description]
      ,[unitofmeasure],[Quantity],[UnitPrice] ,[vatamount] ,[invoiceamount],[completed] ,[printed]
      ,[vatrate] ,[inclusive] ,[partperunit] ,[averagestock],[UOM]";
 $ResultIndex = DB_query($Slaqry,$db);

echo '<table  class="table table-bordered"><thead><tr>'
        . '<th class="number">Stock ID</th>'
        . '<th class="number">Stock Description</th>'
        . '<th class="number">UOM</th>'
        . '<th class="number">Qty to<br />Invoice</th>'
        . '<th class="number">Rate</th>'
        . '<th class="number">Net Amount</th>'
        . '<th class="number">VAT Amount</th>'
        . '<th class="number">Gross Amount</th></tr></thead>';

$runningnettotal = 0;
$runningvattotal  = 0;
$runninggrosstotal  = 0;

while($stocklist=DB_fetch_array($ResultIndex)){
    $itemcode = trim($stocklist['entryno']);
    $stkcode = trim($stocklist['code']);
    $rate = (($IsTaxed==0)? 0 : $stocklist['vatrate']);
    $location = $_POST['location'][$itemcode];
    
     if(isset($_POST['UOM'][$itemcode])){
         $UOM=$_POST['UOM'][$itemcode];
     }else{
        $UOM = $stocklist['UOM'];
    }
       
    if(isset($_POST['subunits'][$itemcode])){
         $qty = $_POST['subunits'][$itemcode];
    }else{
         $qty = $stocklist['Quantity'];
    }
    
    
    if(isset($_POST['salesprice'][$itemcode])){
        $salesprice = $_POST['salesprice'][$itemcode];
    }else{
         $salesprice = $stocklist['UnitPrice'];
    }
       
           if($UOM=='fulqty'){
                  $baseamount = ($salesprice * ($qty * $stocklist['partperunit']));
            }else{
                  $baseamount = ($salesprice * $qty);
            }
        
    
    if($stocklist['inclusive']==true){
        $netamount =$baseamount * (1- round(($rate/(100+$rate)),2));
        $vatamount = $netamount * round(($rate/(100+$rate)),2);
        $grossamount=$baseamount ;
    }else{
        $vatamount  = $baseamount * ($rate/100);
        $grossamount= $baseamount + $vatamount;
        $netamount  = $baseamount;
    }
       
    
    $runningnettotal += $netamount;
    $runningvattotal += $vatamount;
    $runninggrosstotal += $grossamount;
   
    $Controlbox= GetUnits($stkcode);
    echo sprintf('<tr>'
         .'<td><input type="text" name="code['.$itemcode.']" value="'.$stkcode.'" size="4" readonly="readonly"/></td>'
         .'<td>%s</td>'
         .'<td>%s</td>'
         .'<td><input type="text" class="integer" name="subunits['.$itemcode.']" value="'.$qty.'" size="5"/></td>'
         .'<td><input type="text" class="number" name="salesprice['.$itemcode.']" value="'.$salesprice.'" size="5"/></td>'
         .'<td><input type="text" class="number" name="netamount['.$itemcode.']" value="'.$netamount.'" readonly="readonly" size="5"/></td>'
         .'<td><input type="text" class="number" name="vatamount['.$itemcode.']" value="'.$vatamount.'" readonly="readonly" size="5"/></td>'
         .'<td><input type="text" class="number" name="grossamount['.$itemcode.']" value="'.$grossamount.'" readonly="readonly" size="5"/></td>'
         .'<td><a href="'.$pge.'?line='.$itemcode.'&ref='.$_POST['documentno'].'"  onclick="return confirm(\''._('Are you sure you wish to remove '.trim($stocklist['description']).' from this invoice?').'\');">Remove Line</a></td>'
         .'</tr>',trim($stocklist['description']),
                 $Controlbox[$UOM],
                 number_format($stocklist['partperunit'],0));
}
   
echo sprintf('<tfoot><tr>'
        . '<td colspan="4"></td>'
              . '<td >TOTAL</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr></tfoot>', 
                number_format($runningnettotal,2),
                number_format($runningvattotal,2),
                number_format($runninggrosstotal,2));

$_SESSION['Grossamounttotal']=$runninggrosstotal;

echo '</table>';


echo '<div class="centre">
	<input type="submit" name="submit" value="' . _('Re-Calculate') . '" />
	<input type="submit" name="submit" value="' . _('Confirm Invoice') . '"
            onclick="return confirm(\''._('Are you sure you wish to Close this Invoice ?').'\');" />
</div>';

echo '</td></tr></table></div></form>';
 
include('includes/footer.inc');



?>