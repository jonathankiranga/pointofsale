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
    
echo '<div class="centre"><p class="page_title_text"><img src="css/'.$Theme.'/images/sales.png" title="' . _('Sales Invoice') .'" alt="" />' . ' ' . _('Sales Invoice') . '</p>';

if(isset($_GET['ref'])){
    $_POST['documentno'] = $_GET['ref'];
    $_SESSION['DocumentPosted']=false;
    $_SESSION['DocumentPicking']=false;
    $_SESSION['Grossamounttotal']=0;
    unset($_SESSION['bybatchno']);
    unset($_SESSION['batchnoref']);
}

if(isset($_GET['bybatchno'])){
    $_SESSION['bybatchno']=$_GET['bybatchno'];
}
    
if(isset($_POST['submits'])){
    if($_SESSION['Grossamounttotal']>0){
    include('transactions/Saveinvoice.inc');  
    }
}
     
$ResultIndex = DB_query('Select sysdate() as date ',$db);
$rowdate = DB_fetch_row($ResultIndex);
if(!isset($_POST['date'])){
    $_POST['date']= ConvertSQLDate($rowdate[0]);
}
    
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
           ,[lpo]
           ,BatchNO
       FROM [SalesHeader] 
       where [documentno]='".$_POST['documentno']."'";

$ResultIndex= DB_query($filter, $db);
$rowresults = DB_fetch_row($ResultIndex);
    
if(!isset($_POST['Salesoderdate'])){
    $_POST['Salesoderdate']= is_null($rowresults[3])?'': ConvertSQLDate($rowresults[3]);
}

$_POST['datedue'] = is_null($rowresults[4])?'': ConvertSQLDate($rowresults[4]);
if(!isset($_POST['reference'])){
    $_POST['reference'] = $rowresults[16];
}

 if(!isset($_POST['salespersoncode'])){
    $_POST['salespersoncode']= $rowresults[14];
}
    
 $_POST['CustomerID']   = $rowresults[6];
 $_POST['CustomerName'] = $rowresults[7];
 $_POST['currencycode'] = $rowresults[13];
 $_POST['documentno']   = $rowresults[1];
 $_POST['batchno']      = $rowresults[17];
 $_SESSION['batchnoref']= $rowresults[17];
  
echo '<form action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
echo '<div class="card"><table class="table-bordered">';
echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Payment Terms</td>'
     . '<td><input tabindex="5" type="text" name="paymentterms" value="'.$_POST['paymentterms'].'"  size="10"  maxlength="10"/></td>';
    
echo '</tr><tr><td>Document No</td>'
        . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  size="10" readonly="readonly"/></td>'
        . '<td>Your Reference</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="10" /></td>'
        . '</tr>';
echo '<tr><td>Customer ID</td>'
        . '<td><input tabindex="4" type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"/>'
        . '</td>'
        . '<td>Customer Name</td>'
        . '<td colspan="3"><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  required="required" /></td></tr>';
echo '<tr><td>Currency Code</td><td>'
. '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode"  value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';
echo '<td>Sales Rep</td><td><select tabindex="7" name="salespersoncode" id="salespersoncode">'
. '<option value="not">Not selected</option>';
$ResultIndex=DB_query("SELECT [code],[salesman],[commission],[inactive] FROM [salesrepsinfo] where [inactive] is null or [inactive]=0 ", $db);
while($row=DB_fetch_array($ResultIndex)){
   echo sprintf('<option value="%s"  %s >%s</option>',$row['code'], ($_POST['salespersoncode']==$row['code']?'selected="selected"':''),$row['salesman']);
}
    
echo '</select></td></tr>';
echo '</table>';

$runningnettotal = 0;$runningvattotal = 0; $runninggrosstotal = 0; $runningshipping = 0;
    
$sqldebtors=DB_query("SELECT [itemcode] ,[creditlimit],[customer],[phone] ,[email] ,[city] ,[country],[curr_cod],[customerposting],[salesman],[VATinclusive],[IsTaxed]
       FROM [debtors] join postinggroups on code=[customerposting] where itemcode='".$_POST['CustomerID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$discountDefault = $debtorsrow[1];
$customerposting = $debtorsrow[8];
$VATinclusive = $debtorsrow[10];
$IsTaxed = $debtorsrow[11];
 
if($_SESSION['bybatchno']=='true'){
    $Slaqry ="SELECT [entryno],[documenttype],[docdate],[documentno],[locationcode]
      ,[stocktype],[code],[description],[unitofmeasure],[Quantity]
      ,[Quantity_toinvoice],[Qunatity_delivered],[UnitPrice],[vatamount],[invoiceamount]
      ,[completed],[printed] ,[containerprice],[containersunits] 
      ,[totalchargedcontainers],[containercode],[vatrate] ,[inclusive],[UOM]
  FROM [SalesLine] where ([completed] is null) and [BatchNO]='".$_POST['batchno']."' and [documenttype]=1
      group by  
       [entryno],[documenttype],[docdate],[documentno]
      ,[locationcode],[stocktype],[code] ,[description]
      ,[unitofmeasure],[Quantity],[Quantity_toinvoice] ,[Qunatity_delivered]
      ,[UnitPrice] ,[vatamount] ,[invoiceamount],[completed] ,[printed]
      ,[containerprice],[containersunits] ,[totalchargedcontainers] ,[containercode]
      ,[vatrate] ,[inclusive] ,[UOM]";
}else{
      
$Slaqry ="SELECT [entryno],[documenttype],[docdate],[documentno],[locationcode]
      ,[stocktype],[code],[description],[unitofmeasure],[Quantity]
      ,[Quantity_toinvoice],[Qunatity_delivered],[UnitPrice],[vatamount],[invoiceamount]
      ,[completed],[printed] ,[containerprice],[containersunits] 
      ,[totalchargedcontainers],[containercode],[vatrate] ,[inclusive],[UOM]
  FROM [SalesLine] where ([completed] is null) and  [documentno]='".$_POST['documentno']."' and [documenttype]=1
      group by  
       [entryno],[documenttype],[docdate],[documentno]
      ,[locationcode],[stocktype],[code] ,[description]
      ,[unitofmeasure],[Quantity],[Quantity_toinvoice] ,[Qunatity_delivered]
      ,[UnitPrice] ,[vatamount] ,[invoiceamount],[completed] ,[printed]
      ,[containerprice],[containersunits] ,[totalchargedcontainers] ,[containercode]
      ,[vatrate] ,[inclusive] ,[UOM]";
}

 
 $ResultIndex = DB_query($Slaqry,$db);
 echo '<table class="table-bordered"><thead><tr>'
        . '<th class="number">Lab Test Parameter</th>'
         . '<th class="number">Price</th>'
        . '<th class="number">Net Amount</th>'
        . '<th class="number">VAT Amount</th>'
        . '<th class="number">Gross Amount</th></tr></thead>';

$runningnettotal = 0;
$runningvattotal  = 0;
$runninggrosstotal  = 0;
$runningshipping   = 0;
$runningVATdiscount = 0;
$runningdiscount = 0;
while($stocklist=DB_fetch_array($ResultIndex)){
    $emptycost=0; $totalemptycost=0; $cvatamount =0; $cnetamount=0; $cgrossamount=0; $emptyunits=0;$Shipping=0;
   
    $itemcode = trim($stocklist['entryno']);
    $stkcode = trim($stocklist['code']); 
    $rate    = ($IsTaxed==0)?0: $stocklist['vatrate']; 
    $qty     = $stocklist['Qunatity_delivered']; 
    $StockDescription =  html_entity_decode($stocklist['description']);
    $discount =(($_POST['discount']>0)?$_POST['discount']:$discountDefault); 
       
    if(isset($_POST['salesprice'][$itemcode])){
        $salesprice = $_POST['salesprice'][$itemcode];
    }else{
        $salesprice = $stocklist['UnitPrice'];
    }
    
    $baseamount = $salesprice ;
    $percentageDiscount= $discount * 0.01;
    if($VATinclusive==true){
        $netamount   = $baseamount  * (1- round(($rate/(100+$rate)),2));
        $vatamount   = $baseamount  * round(($rate/(100+$rate)),2);
        $grossamount = $baseamount  ;
        $vatDiscount = $vatamount * $percentageDiscount;
        $DISCOUNTNET= ($netamount *  $percentageDiscount);
        $DISCOUNTGROSS = ($grossamount *  $percentageDiscount);
    }else{
        $vatamount   = $baseamount  * ($rate * 0.01);
        $grossamount = $baseamount + $vatamount ;
        $netamount   = $baseamount ;
        $vatDiscount = $vatamount * $percentageDiscount;
        $DISCOUNTNET= ($netamount *  $percentageDiscount);
        $DISCOUNTGROSS = ($grossamount *  $percentageDiscount);
    }
       
    $runningnetdiscount += $DISCOUNTNET;
    $runningdiscount    += $DISCOUNTGROSS;
    $runningnettotal    += $netamount;
    $runningvattotal    += $vatamount;
    $runninggrosstotal  += $grossamount ;
    $runningVATdiscount += $vatDiscount;
    
    echo '<tr>'
         .'<td><input type="hidden" name="code['.$itemcode.']" value="'.$stkcode.'" size="4" readonly="readonly"/>'
         .trim($StockDescription).'<input type="hidden" class="integer" name="subunits['.$itemcode.']" value="1" /></td>'
         .'<td class="number"><input type="text" class="table number" name="salesprice['.$itemcode.']" value="'.$salesprice.'" readonly="readonly" /></td>'
         .'<td class="number"><input type="text" class="table number" name="netamount['.$itemcode.']" value="'.$netamount.'" readonly="readonly" /></td>'
         .'<td class="number"><input type="text" class="table number" name="vatamount['.$itemcode.']" value="'.$vatamount.'" readonly="readonly" /></td>'
         .'<td class="number"><input type="text" class="table number" name="grossamount['.$itemcode.']" value="'.$grossamount.'" readonly="readonly" /></td>'
         .'</tr>';
}
     echo sprintf('<tr>'
           . '<td>Discount Rate</td>'
           . '<td class="number"><input type="number" id="invoicediscountid" size="4" name="discount"  value="'.$discount.'"   min=0  max=99/></td>'
           . '<td class="number">%s</td>'
           . '<td class="number">%s</td>'
           . '<td class="number">%s</td></tr>', 
                number_format($runningnetdiscount,2),
                number_format($runningVATdiscount,2),
                number_format($runningdiscount,2));
 
echo sprintf('<tfoot><tr>'
              . '<td></td>'
              . '<td>TOTAL</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr></tfoot>', 
                number_format($runningnettotal-$runningnetdiscount,2),
                number_format($runningvattotal-$runningVATdiscount,2),
                number_format($runninggrosstotal-$runningdiscount,2));

$_SESSION['Grossamounttotal']=$runninggrosstotal-$runningdiscount;

echo '</table>';


echo '<div class="centre">
	<input type="submit" id="refreshid" name="submit" value="' . _('Re-Calculate') . '" />
	<input type="submit" id="saveid" name="submits" value="' . _('Process Invoice') . '"
            onclick="return confirm(\''._('Are you sure you wish to Close this Invoice ?').'\');" />
</div>';

echo '</td></tr></table></div></form>';
 
include('includes/footer.inc');

 
?>
<script>         
      $("#invoicediscountid").mouseup(function(){
         $("#refreshid").click();
    }); 
</script>