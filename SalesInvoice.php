<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

$Title = _('Sales Invoice');
include('includes/header.inc');   

echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/sales.png" title="' . _('Sales Invoice') .'" alt="" />' . ' ' . _('Sales Invoice') . '</p>';

$SQL="select 
       [salesheader].[documentno]
      ,[salesheader].[docdate]
      ,[salesheader].[oderdate]
      ,[salesheader].[duedate]
      ,[salesheader].[customercode]
      ,[salesheader].[customername]
      ,[salesheader].[currencycode]
      ,[salesheader].[salespersoncode]
      ,[salesheader].[status]
      ,[salesheader].[userid] ,
      sum(salesline.[invoiceamount]) as OrderValue
      ,[salesheader].[BatchNO]
      from [salesheader] 
      join salesline on [salesheader].[documentno]=salesline.[documentno] 
      where [salesheader].[documenttype]='1' and [released]=1 
      group by 
       [salesheader].[documentno]
      ,[salesheader].[docdate]
      ,[salesheader].[oderdate]
      ,[salesheader].[duedate]
      ,[salesheader].[customercode]
      ,[salesheader].[customername]
      ,[salesheader].[currencycode]
      ,[salesheader].[salespersoncode]
      ,[salesheader].[status]
      ,[salesheader].[userid]
      ,[salesheader].[BatchNO] ";
    $Result=DB_query($SQL,$db);
       
    Echo '<div class="table-responsive sales-invoice-list-wrap">'
       . '<table class="table table-bordered table-hover sales-invoice-list">'
       . '<thead><tr>'
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
       . '</tr></thead><tbody>';

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
        
    echo '</tbody></table></div><br />';
    
     
include('includes/footer.inc');


?>