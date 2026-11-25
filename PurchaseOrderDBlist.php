<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

$Title = _('Open Purchase Orders List');
include('includes/header.inc');   

echo '<p class="page_title_text">'
. '<img src="css/'.$Theme.'/images/sales.png" title="' . _('Purchase Orders List') .'" alt="" />' 
        . ' ' . _('Purchase Orders List') . '</p>';

echo '<form action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"  id="salesform">';
echo '<div><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] .'"/>';

echo '<div class="container"><table class="table table-bordered"><tr>'
    . '<td><input type="button" id="searchvendor" value="Search for Vendor"/>'
    . '<input type="hidden" name="VendorID" id="VendorID"/>'
    . '<input type="hidden" name="currencycode" id="currencycode"/></td>'
    . '<td>Supplier Name</td><td><input tabindex="5" type="text" name="VendorName" id="VendorName"/>'
    . '<input type="submit" value="GO"/></td></tr></table></div>';

$SQL="select 
       [purchaseheader].[documentno]
      ,[purchaseheader].[docdate]
      ,[purchaseheader].[oderdate]
      ,[purchaseheader].[duedate]
      ,[purchaseheader].[vendorcode]
      ,[purchaseheader].[vendorname]
      ,[purchaseheader].[currencycode]
      ,[purchaseheader].[status]
      ,[purchaseheader].[userid] 
      ,sum(purchaseline.[invoiceamount]) as OrderValue
      ,[purchaseheader].printed
      from [purchaseheader] join purchaseline on 
      [purchaseheader].[documentno]=purchaseline.[documentno] 
      where [purchaseheader].[documenttype]='20' ". 
        (isset($_POST['VendorID'])?" and [purchaseheader].[vendorcode]='".$_POST['VendorID']."'":'')."
       group by 
       [purchaseheader].[documentno]
      ,[purchaseheader].[docdate]
      ,[purchaseheader].[oderdate]
      ,[purchaseheader].[duedate]
      ,[purchaseheader].[vendorcode]
      ,[purchaseheader].[vendorname]
      ,[purchaseheader].[currencycode]
      ,[purchaseheader].[status]
      ,[purchaseheader].[userid]
      ,[purchaseheader].printed order by [purchaseheader].[docdate] desc ";
    $Result=DB_query($SQL,$db);
       
    
    Echo '<Table class="table table-bordered"><tr>'
             . '<th>Goods Returned</th>'
             . '<th>Purchase <br /> Order <br /> Document<br /> date</th>'
             . '<th>Vendor <br />ID</th>'
             . '<th>Vendor<br /> Name</th>'
             . '<th>Purchase <br />Order<br /> Value</th>'
             . '<th>Currency</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
             . '<th>Print<br />Status</th>'
             . '</tr>';
    
 
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
        echo sprintf('<td><a href="%s?ref=%s">Return Stock:%s</a></td>',htmlspecialchars('Goodsreturnedtovendors.php',ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',$row['vendorcode']);
        echo sprintf('<td>%s</td>',$row['vendorname']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
        echo sprintf('<td>%s</td>',$row['printed']==1?'Has Been Printed':'Not Yet Printed');
        echo '</tr>';
  }
        
    echo '</table><br />';
  
    echo '</div></form>';  
     
include('includes/footer.inc');


?>