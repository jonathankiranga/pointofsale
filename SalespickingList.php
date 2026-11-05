<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');   

$Title = _('Sales Picking List');
include('includes/header.inc');   
include('transactions/stockbalance.inc');   

$pge=htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
    
echo '<div class="centre"><p class="page_title_text">'
. '<img src="css/'.$Theme.'/images/sales.png" title="' . _('Sales Picking List') .'" alt="" />' . ' ' . _('Sales Picking List') . '</p>';

if(isset($_POST['submit']) and $_POST['submit']=='Confirm Picking Details'){
    include('transactions/Savepickinglist.inc');  
}

if(isset($_GET['ref'])){
    $_POST['documentno'] = $_GET['ref'];
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

echo '</div><div class="container-fluid">'
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
        . '<td>Customer Name</td>'
        . '<td colspan="3"><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  readonly="readonly"/></td></tr>';

echo '<tr><td>Currency Code</td><td>'
. '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';

echo '<td>Sales Rep</td><td><select tabindex="7" name="salespersoncode" id="salespersoncode">'
. '<option value="not">Not selected</option>';

$ResultIndex=DB_query("SELECT [code],[salesman],[commission],[inactive] FROM [salesrepsinfo] where [inactive] is null or [inactive]=0 ", $db);

while($row=DB_fetch_array($ResultIndex)){
        echo sprintf('<option value="%s"  %s >%s</option>',$row['code'], ($_POST['salespersoncode']==$row['code']?'selected="selected"':''),$row['salesman']);
}
    
echo '</select></td></tr>';
echo '</table></td></tr><tr><td>';

$runningnettotal = 0;
$runningvattotal = 0;
$runninggrosstotal = 0;
    
$sqldebtors=DB_query("SELECT [itemcode] ,[creditlimit],[customer]
      ,[phone] ,[email] ,[city] ,[country],[curr_cod],[customerposting],[salesman],[VATinclusive]
       FROM [debtors] join postinggroups on code=[customerposting] where itemcode='".$_POST['CustomerID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[8];
$VATinclusive = $debtorsrow[10];

if(isset($_GET['line'])) {
   $results = DB_query("Delete from [SalesLine] where [entryno]='".$_GET['line']."'",$db);
}
      


$ResultIndex = DB_query("SELECT
       [entryno]
      ,[documenttype]
      ,[docdate]
      ,[documentno]
      ,[locationcode]
      ,[stocktype]
      ,[code]
      ,[description]
      ,[unitofmeasure]
      ,[Quantity]
      ,[Quantity_toinvoice]
      ,[Qunatity_delivered]
      ,[UnitPrice]
      ,[vatamount]
      ,[invoiceamount]
      ,[completed]
      ,[printed]
      ,[containerprice]
      ,[containersunits]
      ,[totalchargedcontainers]
      ,[containercode]
      ,[vatrate]
      ,[inclusive]
      ,[partperunit]
      ,[averagestock]
      ,UOM
  FROM [SalesLine] join [stockmaster] 
  on [SalesLine].code=[stockmaster].itemcode
 where [documentno]='".$_POST['documentno']."'", $db);


echo '<table  class="table table-bordered"><thead><tr>'
        . '<th class="number">Stock ID</th>'
        . '<th class="number">Stock Description</th>'
        . '<th class="number">Packed In</th>'
        . '<th class="number">Parts</th>'
        . '<th class="number">Qty<br />Ordered</th>'
        . '<th class="number">Store<br />Picked</th>'
        . '<th class="number">Store<br />Balance</th>'
        . '<th class="number">Qty To<br />Pick</th>'
        . '<th class="number">Qty<br />Not<br />Ccollected</th>'
        . '<th class="number">Empties<br />Issued</th>
      </thead>';

$runningnettotal = 0;
$runningvattotal  = 0;
$runninggrosstotal  = 0;


while($stocklist=DB_fetch_array($ResultIndex)){
    $itemcode = trim($stocklist['entryno']);
    $stkcode  = trim($stocklist['code']);
   
    $UOM = trim($stocklist['UOM']);
    $LOOSNAME = GetUnits($stkcode);
    $packedas = $LOOSNAME[$UOM];
     
    $InfRowContainers = ContainerInfo($stkcode);
    $containercode = $InfRowContainers[0];
     
    $emptycost=0; $totalemptycost=0; $cvatamount =0;
    $cnetamount=0; $cgrossamount=0; $emptyunits=0;
      
    $qtyorderd = $stocklist['Quantity'];
    $qtyreceived = $stocklist['Qunatity_delivered'];
        
    if(isset($_POST['subunits'][$itemcode])){
        $qty = $_POST['subunits'][$itemcode];
    }else{
        $qty=0;
    }
    
    if(isset($qty)){
        $qtytoinvoice = $qtyorderd-($qty+$qtyreceived);
    }else{
        $qtytoinvoice = $stocklist['Quantity_toinvoice'];
    }
    
    if(isset($qty)){
       $emptyunits = $_POST['emptyunits'][$itemcode];
    }else{
       $emptyunits = .0;
    }
    
    
    $location = $_POST['location'][$itemcode];
        
    echo sprintf('<tr>'
         .'<td><input type="text" name="code['.$itemcode.']" value="'.$stkcode.'" size="4" readonly="readonly"/></td>'
         .'<td>%s</td>'
         .'<td>%s</td>'
         .'<td>%s</td>'
          .'<td><input type="text" class="integer" name="ordered['.$itemcode.']" value="'.$qtyorderd.'" readonly="readonly" size="5"/></td>'
         .'<td>%s</td>'
         .'<td>%s</td>'
         .'<td><input type="text" class="integer" name="subunits['.$itemcode.']" value="'.$qty.'" autofocus="autofocus" required="required" size="10"/></td>'
         .'<td><input type="text" class="integer" name="Quantity_toinvoice['.$itemcode.']" value="'.$qtytoinvoice.'" readonly="readonly" size="5"/></td>'
        . '<td><input type="hidden" name="emptycode['.$itemcode.']" value="'.$containercode.'" size="0"/>'
        . '<input type="text" class="number" name="emptyunits['.$itemcode.']" value="'.$emptyunits.'" size="5"/></td>'
        .'</tr>',trim($stocklist['description'])
                ,$packedas
                ,number_format($stocklist['partperunit'],1)
                ,createstores($itemcode) 
                ,getbalance($stkcode,$location));
}
   
echo '<tfoot><tr>'
              . '<td colspan="6"></td>'
              . '<td ></td>'
              . '<td class="number"></td>'
              . '<td class="number"></td>'
              . '<td class="number"></td>'
              . '</tr></tfoot>';


echo '</table>';



echo '<div class="centre">
	<input type="submit" name="submit" value="' . _('Re-Calculate') . '" />
	<input type="submit" name="submit" value="' . _('Confirm Picking Details') . '"'
        . ' onclick="return confirm(\''._('Are you sure you wish to Dispath  this Picking List ?').'\');" />
</div>';



echo '</div></div></form>';
 
include('includes/footer.inc');


function ContainerInfo($itemcode){
    global $db;
    
    $ResultIndex = DB_query("SELECT 
        [stockmaster].[container],
        [c].[itemcode] ,
        [c].[descrip] as [packname],
        IFNULL([cv].[vat],0) as CVAT
  FROM [stockmaster] 
  left join [stockmaster] c on [stockmaster].[container]=[c].[itemcode]
  left join [inventorypostinggroup] ci on [c].[postinggroup]=[ci].[code]
  left join [vatcategory] cv on [ci].[vatcategory]=[cv].[vatc]
  where [stockmaster].[itemcode]='".$itemcode."'", $db);
    
   $stkmaster = DB_fetch_row($ResultIndex);
   return $stkmaster;
}
?>