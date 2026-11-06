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

$SQL="Select  
       PurchaseHeader.[documentno]
      ,[PurchaseHeader].[docdate]
      ,[PurchaseHeader].[oderdate]
      ,[PurchaseHeader].[duedate]
      ,[PurchaseHeader].[vendorcode]
      ,[PurchaseHeader].[vendorname]
      ,[PurchaseHeader].[currencycode]
      ,[PurchaseHeader].[status]
      ,[PurchaseHeader].[userid] 
      from PurchaseHeader 
      join [PurchaseLine] on [PurchaseHeader].documentno=PurchaseLine.[documentno] 
      where  [PurchaseHeader].[documenttype]='20' and [PurchaseHeader].[released]=1 
     order by  PurchaseHeader.[documentno]
      ,[PurchaseHeader].[docdate]
      ,[PurchaseHeader].[oderdate]
      ,[PurchaseHeader].[duedate]
      ,[PurchaseHeader].[vendorcode]
      ,[PurchaseHeader].[vendorname]
      ,[PurchaseHeader].[currencycode]
      ,[PurchaseHeader].[status]
      ,[PurchaseHeader].[userid] desc";
    $Result=DB_query($SQL,$db);
           
    echo '<div class="table-responsive service-lpo-list"><table class="table table-bordered table-hover"><thead><tr>'
             . '<th>Supplier Invoice<br /> for GRN</th>'
             . '<th>Purchase <br /> Order <br /> Document<br /> date</th>'
             . '<th>Purchase <br /> Order <br />Due <br />Date</th>'
             . '<th>Vendor <br />ID</th>'
             . '<th>Vendor<br /> Name</th>'
             . '<th>Purchase <br />Order<br /> Value</th>'
             . '<th>Currency</th>'
             . '<th>Authorisation<br /> Status</th>'
              . '<th>Created<br /> By</th>'
             . '</tr></thead><tbody>';
    
 
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
        echo sprintf('<td><a href="%s?No=%s">Invoice:%s</a></td>',htmlspecialchars('SupplierServiceInvoice.php',ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',is_null($row['duedate'])?'': ConvertSQLDate($row['duedate']));
        echo sprintf('<td>%s</td>',$row['vendorcode']);
        echo sprintf('<td>%s</td>',$row['vendorname']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
        echo '</tr>';
  }
        
    echo '</tbody></table></div><br />';
    
     
include('includes/footer.inc');


?>