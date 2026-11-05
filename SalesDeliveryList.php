<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

$Title = _('Sales Picking List');
include('includes/header.inc');   

echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/sales.png" title="' . _('Sales Picking List') .'" alt="" />' . ' ' . _('Sales Picking List') . '</p>';

$SQL="select 
       [SalesHeader].[documentno]
      ,[SalesHeader].[docdate]
      ,[SalesHeader].[oderdate]
      ,[SalesHeader].[duedate]
      ,[SalesHeader].[customercode]
      ,[SalesHeader].[customername]
      ,[SalesHeader].[currencycode]
      ,[SalesHeader].[salespersoncode]
      ,[SalesHeader].[status]
      ,[SalesHeader].[userid] ,
      sum(SalesLine.[invoiceamount]) as OrderValue
      from [SalesHeader] join SalesLine on 
      [SalesHeader].[documentno]=SalesLine.[documentno] 
      where [SalesHeader].[documenttype]='10' 
       group by 
       [SalesHeader].[documentno]
      ,[SalesHeader].[docdate]
      ,[SalesHeader].[oderdate]
      ,[SalesHeader].[duedate]
      ,[SalesHeader].[customercode]
      ,[SalesHeader].[customername]
      ,[SalesHeader].[currencycode]
      ,[SalesHeader].[salespersoncode]
      ,[SalesHeader].[status]
      ,[SalesHeader].[userid] ";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table table-bordered"><tr>'
        . '<th>Post Credit Note<br /> on Sales Invoice</th>'
        . '<th>Edit Sales Invoice</th>'
        . '<th>Sales <br /> Order <br />Due <br />Date</th>'
        . '<th>Customer <br />ID</th>'
        . '<th>Customer <br /> Name</th>'
        . '<th>Sales <br />Order<br /> Value</th>'
        . '<th>Currency </th>'
        . '<th>Sales <br /> Person</th>'
        . '<th>Authorisation <br /> Status</th>'
        . '<th>Created <br /> By</th>'
        . '</tr>';
    
  while($row=DB_fetch_array($Result)){
        echo '<tr>';
        echo sprintf('<td><a href="%s&ref=%s">Credit Note:%s :</a></td>',
        'index.php?link=Salescreditnote',$row['documentno'],$row['documentno']);
        echo sprintf('<td><a href="%s&ref=%s">Edit Invoice:%s </a></td>',
        'index.php?link=EditPostedSalesinvoice',$row['documentno'],$row['documentno']);
        echo sprintf('<td>%s</td>',is_null($row['duedate'])?'': ConvertSQLDate($row['duedate']));
        echo sprintf('<td>%s</td>',$row['customercode']);
        echo sprintf('<td>%s</td>',$row['customername']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['salespersoncode']);
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
        echo '</tr>';
  }
        
    echo '</table><br />';
    
     
include('includes/footer.inc');


?>