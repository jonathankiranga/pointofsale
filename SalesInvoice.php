<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

$Title = _('Sales Invoice');
include('includes/header.inc');   

echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/sales.png" title="' . _('Sales Invoice') .'" alt="" />' . ' ' . _('Sales Invoice') . '</p>';

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
      ,[SalesHeader].[BatchNO]
      from [SalesHeader] 
      join SalesLine on [SalesHeader].[documentno]=SalesLine.[documentno] 
      where [SalesHeader].[documenttype]='1' and [released]=1 
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
      ,[SalesHeader].[userid]
      ,[SalesHeader].[BatchNO] ";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table"><tr>'
       . '<th>Generate & Invoice <br />Lab Test<br /> By Sample ID</th>' 
      . '<th>Generate & Invoice <br />Lab Test<br /> By Batch No</th>' 
       . '<th>Sales <br /> Order <br /> Document<br /> date</th>'
       . '<th>Sales <br /> Order <br />Due <br />Date</th>'
       . '<th>Customer <br />ID</th>'
       . '<th>Customer<br /> Name</th>'
       . '<th>Sales <br />Order<br /> Value</th>'
       . '<th>Currency</th>'
       . '<th>Sales<br /> Person</th>'
       . '<th>Authorisation<br /> Status</th>'
       . '<th>Created<br /> By</th>'
       . '</tr>';
    
    $pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
        echo sprintf('<td><a href="%s?link=SalesInvoicefromorders&ref=%s">Order:%s</a></td>',
        $pge,$row['documentno'],$row['documentno']);
        echo sprintf('<td><a href="%s?link=SalesInvoicefromorders&ref=%s&bybatchno=true">Order:%s</a></td>',
        $pge,$row['documentno'],$row['BatchNO']);
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
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