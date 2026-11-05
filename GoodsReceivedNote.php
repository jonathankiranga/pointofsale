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

$SQL="Select Distinct
       stockledger.jobcard
      ,PurchaseLine.[documentno]
      ,[PurchaseHeader].[docdate]
      ,[PurchaseHeader].[oderdate]
      ,[PurchaseHeader].[duedate]
      ,[PurchaseHeader].[vendorcode]
      ,[PurchaseHeader].[vendorname]
      ,[PurchaseHeader].[currencycode]
      ,[PurchaseHeader].[status]
      ,[PurchaseHeader].[userid] 
      from stockledger join PurchaseLine on 
      stockledger.invref=PurchaseLine.[documentno] 
      join [PurchaseHeader] on [PurchaseHeader].documentno=PurchaseLine.[documentno] 
      and  [PurchaseHeader].[documenttype]='18' and [PurchaseHeader].[released]=1 
       order by stockledger.jobcard
      ,PurchaseLine.[documentno]
      ,[PurchaseHeader].[docdate]
      ,[PurchaseHeader].[oderdate]
      ,[PurchaseHeader].[duedate]
      ,[PurchaseHeader].[vendorcode]
      ,[PurchaseHeader].[vendorname]
      ,[PurchaseHeader].[currencycode]
      ,[PurchaseHeader].[status]
      ,[PurchaseHeader].[userid] desc";
    $Result=DB_query($SQL,$db);
           
    Echo '<Table class="table table-bordered"><tr>'
             . '<th>Supplier Invoice<br /> for GRN</th>'
             . '<th>Purchase <br /> Order <br /> Document<br /> date</th>'
             . '<th>Purchase <br /> Order <br />Due <br />Date</th>'
             . '<th>Vendor <br />ID</th>'
             . '<th>Vendor<br /> Name</th>'
             . '<th>Purchase <br />Order<br /> Value</th>'
             . '<th>Currency</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
             . '</tr>';
    
 
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
        echo sprintf('<td><a href="%s?No=%s">Invoice:%s</a></td>',htmlspecialchars('SupplierInvoice.php',ENT_QUOTES,'UTF-8'),$row['jobcard'],$row['jobcard']);
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
        
    echo '</table><br />';
    
     
include('includes/footer.inc');


?>