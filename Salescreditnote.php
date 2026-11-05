<?php
$Title = _('Sales Credit Note');
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/DimensionClass.inc');
include('transactions/stockbalance.inc');
$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

include('includes/header.inc');   
echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/sales.png" title="' . _('Sales Credit Note') .'" alt="" />' . ' ' . _('Sales Credit Note') . '</p>';

if(isset($_POST['confirm'])){
    if($_SESSION['Grossamounttotal']>0){
          include('transactions/SavecreditNote.inc');
    }else{
        prnMsg('This credit note is invalid');
    }
}

if(isset($_GET['ref'])){
    $_POST['documentno'] = $_GET['ref'];
    $_SESSION['DocumentPosted']=false;
    $_SESSION['DocumentPicking']=false;
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

echo '<table class="table-bordered" cellspacing="4"><tr><td valign="top">'
        . '<table class="table-bordered"><caption>Sales Invoice Header Details</caption>';

echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Date of order</td><td><input tabindex="2" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="Salesoderdate" size="11" maxlength="10"  readonly="readonly" value="' .$_POST['Salesoderdate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Date Due</td><td><input tabindex="3" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="datedue" size="11" maxlength="10" readonly="readonly"  value="' .$_POST['datedue']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';

echo '<tr><td>Credit Note For</td>'
        . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  size="5" readonly="readonly"/></td>'
        . '<td>Your Reference</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td></tr>';

echo '<tr><td>Customer ID</td>'
        . '<td><input tabindex="4" type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"/>'
        . '</td></tr><tr>'
        . '<td>Customer Name</td>'
        . '<td colspan="3"><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  readonly="readonly"/></td></tr>';

echo '<tr><td>Currency Code</td><td>'
. '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';

echo '<td>Sales Rep</td><td><select tabindex="7" name="salespersoncode" id="salespersoncode"><option value="not">Not selected</option>';

$ResultIndex=DB_query("SELECT [code],[salesman],[commission],[inactive] FROM [salesrepsinfo] where [inactive] is null or [inactive]=0 ", $db);

while($row=DB_fetch_array($ResultIndex)){
        echo sprintf('<option value="%s" %s>%s</option>',$row['code'], ($_POST['salespersoncode']==$row['code']?'selected="selected"':''),$row['salesman']);
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


  
$Slaqry ="SELECT [entryno],[documenttype],[docdate],[documentno],[locationcode]
      ,[stocktype],[code],[description],[unitofmeasure],[Quantity]
      ,[Quantity_toinvoice],[Qunatity_delivered],[UnitPrice],[vatamount],[invoiceamount]
      ,[completed],[printed] ,[containerprice],[containersunits] 
      ,[totalchargedcontainers],[containercode],[vatrate] ,[inclusive],[UOM],[sampleID]
  FROM [SalesLine] where   [documentno]='".$_POST['documentno']."' and [documenttype]=10
      group by  
       [entryno],[documenttype],[docdate],[documentno]
      ,[locationcode],[stocktype],[code] ,[description]
      ,[unitofmeasure],[Quantity],[Quantity_toinvoice] ,[Qunatity_delivered]
      ,[UnitPrice] ,[vatamount] ,[invoiceamount],[completed] ,[printed]
      ,[containerprice],[containersunits] ,[totalchargedcontainers] ,[containercode]
      ,[vatrate] ,[inclusive] ,[UOM],[sampleID]";


 
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
$existingCRNS =sumallcrnts($_POST['documentno']);
while($stocklist=DB_fetch_array($ResultIndex)){
    $emptycost=0; $totalemptycost=0; $cvatamount =0; 
    $cnetamount=0; $cgrossamount=0; $emptyunits=0;$Shipping=0;
    $salesprice=0;
    $Sampleidrefno = trim($stocklist['sampleID']);
    $itemcode = trim($stocklist['entryno']);
    $stkcode = trim($stocklist['code']); 
    $rate    = ($IsTaxed==0)?0: $stocklist['vatrate']; 
    $qty     = $stocklist['Qunatity_delivered']; 
    $StockDescription =  html_entity_decode($stocklist['description']).' Sample ID :'.$Sampleidrefno ;
        
    if(isset($_POST['salesprice'][$itemcode])){
        $salesprice = $_POST['salesprice'][$itemcode];
    }
    
    $baseamount = $salesprice ;
    $percentageDiscount = $discount * 0.01;
    if($VATinclusive==true){
        $netamount   = $baseamount  * (1- round(($rate/(100+$rate)),2));
        $vatamount   = $baseamount  * round(($rate/(100+$rate)),2);
        $grossamount = $baseamount  ;
    }else{
        $vatamount   = $baseamount  * ($rate * 0.01);
        $grossamount = $baseamount + $vatamount ;
        $netamount   = $baseamount ;
    }
       
    $runningnettotal    += $netamount;
    $runningvattotal    += $vatamount;
    $runninggrosstotal  += $grossamount ;
    
    echo '<tr>'
         .'<td><input type="hidden" name="code['.$itemcode.']" value="'.$stkcode.'" size="4" readonly="readonly"/>'
         .trim($StockDescription).'<input type="hidden" class="integer" name="subunits['.$itemcode.']" value="1" /></td>'
         .'<td class="number"><input type="text" class="table number" name="salesprice['.$itemcode.']" value="'.$salesprice.'" /></td>'
         .'<td class="number"><input type="text" class="table number" name="netamount['.$itemcode.']" value="'.$netamount.'" readonly="readonly" /></td>'
         .'<td class="number"><input type="text" class="table number" name="vatamount['.$itemcode.']" value="'.$vatamount.'" readonly="readonly" /></td>'
         .'<td class="number"><input type="text" class="table number" name="grossamount['.$itemcode.']" value="'.$grossamount.'" readonly="readonly" /></td>'
         .'</tr>';
}
    
 
echo sprintf('<tfoot><tr>'
              . '<td></td>'
              . '<td>TOTAL</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr></tfoot>', 
                number_format($runningnettotal,2),
                number_format($runningvattotal,2),
                number_format($runninggrosstotal,2));

$_SESSION['Grossamounttotal']=$runninggrosstotal;

echo '</table>';
if(($runninggrosstotal>$existingCRNS)){
    prnMsg("Your Total credit notes on this invoice have depleted this balance $existingCRNS.<br> you cannot generate this credit note"); 
    echo '<div class="centre">
	<input type="submit" name="submit" value="' . _('Re-Calculate') . '" />
</div>';
}else{
echo '<div class="centre">
	<input type="submit" name="submit" value="' . _('Re-Calculate') . '" />
	<input type="submit" name="confirm" value="' . _('Enter Credit Note Details and Confirm') . '"
            onclick="return confirm(\''._('Are you sure you wish to create this Credit Note ?').'\');" />
</div>';
}
echo '</td></tr></table></div></form>';
 
include('includes/footer.inc');


function sumallcrnts($doc){
    global $db;
    $sql =sprintf("select sum(invoiceamount) from SalesLine "
            . " where SalesLine.[documentno]='%s'",$doc);
   $ResultIndex=DB_query($sql,$db);
   $myrow = DB_fetch_row($ResultIndex);
   $Sales= $myrow[0];
    
   $sql =sprintf("select sum(invoiceamount) from SalesLine "
           . "join [SalesHeader] on [SalesHeader].[documentno]=SalesLine.[documentno] "
           . " where [externaldocumentno]='%s'",$doc);
   $ResultIndex=DB_query($sql,$db);
   $myrow = DB_fetch_row($ResultIndex);
   $CRN= $myrow[0];
   
   return $Sales-$CRN;
   
}