<?php
$PageSecurity=0;

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc'); 

$Title = _('Quality Control');
include('includes/header.inc');   
include('purchases/stockbalance.inc');   

$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/sales.png" title="' . _('Quality Control') .'" alt="" />' . ' ' . _('Quality Control') . '</p>';

if(isset($_POST['submit']) and 
         $_POST['submit']=='Accept Goods'){
    
    $_POST['reference'] = GetNextTransNo(30,$db);
    
    if($_SESSION['Grossamounttotal']==0){
       $_POST['submit']="Empty";
    }
}

if(isset($_POST['submit']) and 
         $_POST['submit']=='Accept Goods'){
    
   SaveQC();  
}


if(isset($_GET['ref'])){
    $_POST['documentno'] = $_GET['ref'];
    $_SESSION['DocumentPicking']=false;
}
        
$filter="SELECT 
            [documenttype],[documentno] ,[docdate] ,[oderdate]
           ,[duedate],[postingdate] ,[vendorcode],[vendorname]
           ,[yourreference] ,[externaldocumentno] ,[locationcode]
           ,[paymentterms],[postinggroup] ,[currencycode] ,[vatinclusive]
       FROM [PurchaseHeader] 
       where [documentno]='".$_POST['documentno']."'";
$ResultIndex= DB_query($filter, $db);
$rowresults = DB_fetch_row($ResultIndex);
    
    $_POST['date'] = is_null($rowresults[2])?'': ConvertSQLDate($rowresults[2]);
    if(!isset($_POST['Salesoderdate'])){
        $_POST['Salesoderdate']= is_null($rowresults[3])?'': ConvertSQLDate($rowresults[3]);
    }
  
    $_POST['datedue'] = is_null($rowresults[4])?'': ConvertSQLDate($rowresults[4]);
    $_POST['VendorID'] = $rowresults[6];
    $_POST['VendorName']= $rowresults[7];
    $_POST['currencycode'] = $rowresults[13];
    
    if(!isset($_POST['salespersoncode'])){
        $_POST['salespersoncode']= $rowresults[14];
    }
    
    $_POST['documentno'] = $rowresults[1];
  
echo '<form action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

echo '<table class="table table-bordered" cellspacing="4"><tr><td valign="top">'
   . '<table class="table table-bordered"><caption>Purchase Order Header </caption>';

echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Date of order</td><td><input tabindex="2" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="Salesoderdate" size="11" maxlength="10"  readonly="readonly" value="' .$_POST['Salesoderdate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Date Due</td><td><input tabindex="3" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="datedue" size="11" maxlength="10" readonly="readonly"  value="' .$_POST['datedue']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';

echo '<tr><td>Work Order No</td>'
        . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  size="5" readonly="readonly"/></td>'
        . '<td>QC No</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" readonly="readonly"/></td></tr>';

echo '<tr><td>Employee ID</td>'
        . '<td><input tabindex="4" type="text" name="VendorID" id="VendorID" value="'.$_POST['VendorID'].'"  size="5" readonly="readonly"  readonly="readonly"/>'
        . '</td>'
    . '<td>Employee Name</td>'
        . '<td colspan="3"><input type="hidden" name="VendorName" id="VendorName" value="'.$_POST['VendorName'].'"  size="50"  readonly="readonly"/>'.$_POST['VendorName'].'</td></tr>';

echo '<tr><td>Currency Code</td><td>'
. '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';


echo '</table></td></tr><tr><td>';

$runningnettotal = 0;
$runningvattotal = 0;
$runninggrosstotal = 0;
 

      


$SQL="SELECT [entryno],[documenttype]
      ,[docdate],[documentno],[locationcode],[stocktype],[code],[description],[unitofmeasure],[Quantity],
      IFNULL([Quantity_toinvoice],0) as Quantity_toinvoice,
      IFNULL([Qunatity_delivered],0) as Qunatity_delivered,
      [UnitPrice],[vatamount],[invoiceamount],[completed],[printed],[vatrate],[inclusive],[partperunit],[averagestock],[UOM]
  FROM [PurchaseLine] join [stockmaster] 
  on [PurchaseLine].code=[stockmaster].itemcode  where [documentno]='".$_POST['documentno']."'";


$ResultIndex = DB_query($SQL, $db);
echo '<table  class="table table-bordered"><thead><tr>'
        . '<th class="number">Stock ID</th>'
        . '<th class="number">Stock Description</th>'
        . '<th class="number">Packed In</th>'
        . '<th class="number">Kit Size</th>'
        . '<th class="number">Rate<br /> per part</th>'
        . '<th class="number">Qty to<br />Produced</th>'
        . '<th class="number">Accepted Quantity</th>'
        . '<th class="number">Rejected</th>'
        . '<th class="number">Store</th>'
        . '<th class="number">Gross Amount</th>'
        . '</tr></thead>';

$runningnettotal = 0;
$runningvattotal  = 0;
$runninggrosstotal  = 0;


while($stocklist=DB_fetch_array($ResultIndex)){
    $itemcode = trim($stocklist['entryno']);
    $stkcode = trim($stocklist['code']);
    $UOM = trim($stocklist['UOM']);
    
    
    if(isset($_POST['ordered'][$itemcode])){
        $qtyorderd = $_POST['ordered'][$itemcode];
    }else{
        $qtyorderd = $stocklist['Quantity'];
    }
    
    if(isset($_POST['subunits'][$itemcode])){
        $qty = $_POST['subunits'][$itemcode];
    }else{
        $qty = $stocklist['Quantity_toinvoice'];
    }
    
    $qtytoreceive =($qtyorderd-($stocklist['Qunatity_delivered']+$qty));
     
    if(isset($_POST['salesprice'][$itemcode])){
        $salesprice = $_POST['salesprice'][$itemcode];
    }else{
         $salesprice = $stocklist['UnitPrice'];
    }
       
    $location = $_POST['location'][$itemcode];
    $grossamount = ($qty * $salesprice);
    $runninggrosstotal += $grossamount;
    
    echo '<tr><td><input type="text" name="code['.$itemcode.']" value="'.$stkcode.'" size="4" readonly="readonly"/></td>'
         .'<td>'.trim($stocklist['description']).'</td>'
         .'<td><input type="text" value="'.trim($stocklist['unitofmeasure']).'" readonly="readonly" size="10"/></td>'
         .'<td><input type="text" value="'.number_format($stocklist['partperunit'],0).'" readonly="readonly" size="10"/></td>'
         .'<td><input type="text" class="number" name="salesprice['.$itemcode.']" value="'.$salesprice.'" size="5"/></td>'
         .'<td><input type="text" class="integer" name="ordered['.$itemcode.']" value="'.$qtyorderd.'" readonly="readonly" size="10"/></td>'
         .'<td><input type="text" class="integer" name="subunits['.$itemcode.']" value="'.$qty.'" autofocus="autofocus" size="10"/></td>'
         .'<td><input type="text" class="integer" name="balance['.$itemcode.']" value="'.$qtytoreceive.'" readonly="readonly" size="10"/></td>'
         .'<td>'.createstores($itemcode).'</td>'
         .'<td><input type="text" class="number" name="grossamount['.$itemcode.']" value="'.$grossamount.'" readonly="readonly" size="10"/></td>'
         .'</tr>';
}
   
echo sprintf('<tfoot><tr><td colspan="9">TOTAL</td><td class="number">%s</td></tr></tfoot>',number_format($runninggrosstotal,2));

$_SESSION['Grossamounttotal'] = $runninggrosstotal;

echo '</table>';
echo '<div class="centre">'
        . '<input  type="submit" name="submit" value="' . _('Re-Calculate') . '" />'
        . '<input  type="submit" name="submit" value="' . _('Accept Goods') . '" /></div>';
echo '</td></tr></table></div></form>';
 
include('includes/footer.inc');



Function SaveQC(){
    Global $db;
  
$_SESSION['DocumentNo'] = $_POST['documentno'] ;
$_SESSION['invoicerrors'] = 0;
$_SESSION['somedetails'] = 0;

foreach ($_POST['subunits'] as $key => $value) {
    $location = $_POST['location'][$key];
    $stcode   = $_POST['code'][$key] ;
    $qty      = $_POST['subunits'][$key] ;
     
    if($qty > 0){
      $_SESSION['invoicerrors']++;
    }
    
    if($dbbalance < 0){
      $_SESSION['somedetails']++;
    } 
    
}

 if($_SESSION['somedetails'] > 0) {
    prnMsg('You cannot Receive more than was Produced','warn');
 }
    
 if($_SESSION['invoicerrors'] == 0){
    prnMsg('You have not selected stock units to receive','warn');
  }   
    
if($_SESSION['DocumentPicking']==true){
    prnMsg('This session has an invalid ID please reload this page from the List','warn');
}

if($_SESSION['somedetails'] == 0 AND $_SESSION['DocumentPicking']==false){
    
    $ResultIndex = DB_query('Select sysdate() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['posingdate'] = ConvertSQLDate($rowdate[0]);
 
    $sqldebtors=DB_query("SELECT  [curr_cod],[commissionposting]
      FROM [productionEmployee] where [code]='".$_POST['VendorID']."'", $db);
    $debtorsrow = DB_fetch_row($sqldebtors);
    $postinggroup = $debtorsrow[1];
    $curr_code = $debtorsrow[0];
   
    $customerposting = $debtorsrow[1];
    $VATinclusive    = 0;
    $PeriodNo        = GetPeriod($_POST['posingdate'],$db,false);
    $TransBuffer     = DB_Txn_Begin($db);
    $INVOICENO       = $_POST['reference'];
    $journal         = GetNextTransNo(0,$db);
    
    $sql=array();
    
    $sql[]="INSERT INTO [PurchaseHeader]
           ([documenttype]
           ,[documentno]
           ,[docdate]
           ".(Is_date($_POST['Salesoderdate'])?',[oderdate]':'')."
           ".(Is_date($_POST['datedue'])?',[duedate]':'')."
           ,[vendorcode]
           ,[vendorname]
           ,[yourreference]
           ,[locationcode]
           ,[postinggroup]
           ,[currencycode]
           ,[status]
           ,[userid]
           ,[period]
           ,[vatinclusive])
     VALUES
           ('30'
           ,'".$_POST['reference']."'
           ,'".FormatDateForSQL($_POST['date'])."'
           ".(Is_date($_POST['Salesoderdate'])?",'".FormatDateForSQL($_POST['Salesoderdate'])."'":'')."
           ".(Is_date($_POST['datedue'])?",'".FormatDateForSQL($_POST['datedue'])."'":'') ."
           ,'".$_POST['VendorID']."'
           ,'".$_POST['VendorName']."'
           ,'".$_SESSION['DocumentNo']."'
           ,'".$_POST['locationcode']."'
           ,'". $postinggroup ."'
           ,'". $curr_code . "'
           ,'1'
           ,'".$_SESSION['UserID']."'
           ,'".$PeriodNo."'
           ,'0')" ;
    
    foreach ($_POST['subunits'] as $key => $value) {
       if($value>0){

               $location = $_POST['location'][$key];
               $stcode = $_POST['code'][$key];
               $qty = $_POST['subunits'][$key];     
               $ProductionCost = $_POST['salesprice'][$key];
               $salesoderno = $_SESSION['DocumentNo'];
            
               $sql[]= sprintf("Update [PurchaseLine] set
                    [Qunatity_delivered]= IFNULL([Qunatity_delivered],0)+ %f,[Quantity_toinvoice]= [Quantity]-[Qunatity_delivered]
                    where [documentno]='%s' and [documenttype]='%s'  and [entryno]=%s",$qty,$salesoderno,40,$key);
                
           $sql[]="INSERT [stockledger]
               ([date] ,[stname] ,[batch] ,[doctyp],[itemcode] ,[invref],[netamt] ,[vat] ,[amount] ,[fulqty] ,[loosqty] ,[price] ,[store] ,[journal] ,[period] ,[jobcard] )
            (SELECT 
                [PurchaseLine].[docdate] , substring([PurchaseLine].[description],0,30),'".$key."','30'
               ,[PurchaseLine].[code]
               ,[PurchaseLine].[documentno]
               ,[PurchaseLine].[invoiceamount]
               ,[PurchaseLine].[vatamount]
               ,[PurchaseLine].[invoiceamount]
               ,case [PurchaseLine].UOM when 'fulqty' then ".$qty."  else 0 end as fulqty
               ,case [PurchaseLine].UOM when 'loosqty' then ".$qty."  else 0 end as loosqty
               ,[PurchaseLine].[UnitPrice] ,'".$location."' ,'".$journal."' ,'".$PeriodNo."' ,'".$INVOICENO ."' 
               FROM [PurchaseLine] 
               where documentno='".$salesoderno."' and code='".$stcode."' and [entryno]='".$key."')";
                
         $sql[]="INSERT INTO [PurchaseLine]
                ([documenttype],[docdate],[documentno],[code],[description],[unitofmeasure],[Quantity],[UnitPrice],[vatamount],[invoiceamount],[vatrate],[inclusive],[UOM]) 
                (select 30,[docdate],'".$INVOICENO."',[code],[description],[unitofmeasure],".$qty.",[UnitPrice],[vatamount],[invoiceamount],[vatrate],[inclusive],[UOM]
                from [PurchaseLine] where documentno='".$salesoderno."' and code='".$stcode."'  and [entryno]='".$key."')" ;

           if($ProductionCost>0){ 
            $sql[] = " Update [stockmaster] set [averagestock]=".$ProductionCost." where itemcode='".$stcode."'";
           
            $sql[] = " INSERT INTO [StockRegister] ([itemcode],[StockIn],[cost],[StockOut],[journal],[GRN]) 
            VALUES  ('".$stcode."',".$qty.",".$ProductionCost.",0,'".$journal."','".$INVOICENO."')";
           }
              
         }
    }
 
    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('There was an Error , Contact your administrator','warn');
    } else {
        DB_Txn_Commit($db);
        prnMsg('This QC document '.$INVOICENO.' has been created'.'<a href="PDFPrintQCquantities.php?No='.$INVOICENO.'">QC Reports</a>');
         $_SESSION['DocumentPicking']=true;
         $_SESSION['DocumentNo']=true;
        unset($_POST);
   }
    
    

}
}

?>