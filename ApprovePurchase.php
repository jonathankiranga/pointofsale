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

   


if(isset($_POST['confirm'])){
    DB_query("UPDATE [PurchaseHeader] SET [released] = 1 where documentno='".$_POST['documentno']."'", $db);
    DB_query("UPDATE [PurchaseLine] SET [completed] = 1 where documentno='".$_POST['documentno']."'", $db);
    prnMsg('Purchase order :'.$_POST['documentno'].' has been saved.'
         . ' Click Here to Print'.'<div class="centre">'
            . '<a href="PDFPrintPurchaseOrder.php?No='.$_POST['documentno'].'">Print Purchase order '.$_POST['documentno'].'</a></div>');
    unset($_POST);
}

if(isset($_POST['Delete'])){
    DB_query("Delete from [PurchaseLine] where documentno='".$_POST['documentno']."' and [documenttype]='18'", $db);
    DB_query("delete from [PurchaseHeader] where documentno='".$_POST['documentno']."' and [documenttype]='18'", $db);
    prnMsg('Purchase order :'.$_POST['documentno'].' has been Deleted.');
    unset($_POST);
}


if(isset($_GET['No'])){
    $documentno=$_GET['No'];
}elseif(isset($_POST['documentno'])){
    $documentno=$_POST['documentno'];
}

if(isset($documentno)){
      
echo '<form action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"  id="salesform">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
echo '<input type="hidden" name="documentno" value="'. $documentno.'" />';


    $SQL="select 
       [documentno]
      ,[docdate]
      ,[oderdate]
      ,[duedate]
      ,[postingdate]
      ,[vendorcode]
      ,[vendorname]
      ,[yourreference]
      ,[externaldocumentno]
      ,[locationcode]
      ,[paymentterms]
      ,[postinggroup]
      ,[currencycode]
      ,[printed]
      ,[released]
      ,[status]
      ,[userid]
      ,[freight] 
      from [PurchaseHeader]  where [documenttype]='18' and [documentno]='".$documentno."'";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table table-bordered"><tr><th>Parameter</th><th>Value</th></tr>';
    $row=DB_fetch_row($Result);
        
    echo sprintf("<tr><td>Purchase Order No</td><td>%s</td></tr>",$documentno);
    echo sprintf("<tr><td>Purchase Order Document date</td><td>%s</td></tr>",Is_null($row[1])?'': ConvertSQLDate($row[1]));
    echo sprintf("<tr><td>Purchase Order date</td><td>%s</td></tr>", Is_null($row[2])? '':ConvertSQLDate($row[2]));
    echo sprintf("<tr><td>Purchase Order Due Date</td><td>%s</td></tr>", Is_null($row[3])?'': ConvertSQLDate($row[3]));
    echo sprintf("<tr><td>Supplier ID</td><td>%s</td></tr>", $row[5]);
    echo sprintf("<tr><td>Supplier Name</td><td>%s</td></tr>",$row[6]);
    echo sprintf("<tr><td>Freight Cost</td><td>%s</td></tr>",number_format($row[17],2) );
    echo '<tr><td colspan="2">Line Details</td></tr>';
   
    echo '<tr><td colspan="2">';
    
    echo '<table class="table"><tr><th>Stock Code</th>'
            . '<th>Name</th>'
            . '<th>Unit Of Measure</th>'
            . '<th>Quantity</th>'
            . '<th>Purcahse Price</th>'
            . '<th>vat</th>'
            . '<th>Gross</th>'
            . '</tr>';
    
    $VATAMOUNT=0;
    $INVOICEAMOUNT=0;
    
    $SQL="select 
           [documenttype]
           ,[docdate]
           ,[documentno]
           ,[code]
           ,[description]
           ,[unitofmeasure]
           ,[Quantity]
           ,[UnitPrice]
           ,[vatamount]
           ,[invoiceamount] from [PurchaseLine] 
           where [documenttype]=18 and [documentno]='".$documentno."'";
   
    $ResultIndex=DB_query($SQL,$db);
    while($myrow=DB_fetch_array($ResultIndex)){
         echo sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                 $myrow['code'],
                 $myrow['description'],
                 $myrow['unitofmeasure'],
                 $myrow['Quantity'],
                 number_format($myrow['UnitPrice'],2),
                 number_format($myrow['vatamount'],2),
                 number_format($myrow['invoiceamount'],2));
     
                $VATAMOUNT += $myrow['vatamount'];
                $INVOICEAMOUNT  +=$myrow['invoiceamount'];
    }     
    echo sprintf('<tfoot><tr><td colspan="5">%s</td><td>%s</td><td>%s</td></tr></tfoot>','Purchase Order Totals',number_format($VATAMOUNT,2),number_format($INVOICEAMOUNT,2));
    echo '</table></td></tr>';
    echo '<tr><td colspan="2">';
    echo '<div><input type="submit" name="confirm" value="'._('Approve Purchase Order').'"/>';
    echo '<input type="submit" name="Delete" value="'._('Reject and Delete').'"/></div>';
    echo '</td></tr>';
    echo '</table>';
        
   
    echo '</div></form>' ;

}else{

$SQL="Select  
       [PurchaseHeader].[documentno]
      ,[PurchaseHeader].[docdate]
      ,[PurchaseHeader].[oderdate]
      ,[PurchaseHeader].[duedate]
      ,[PurchaseHeader].[vendorcode]
      ,[PurchaseHeader].[vendorname]
      ,[PurchaseHeader].[currencycode]
      ,[PurchaseHeader].[status]
      ,[PurchaseHeader].[userid] 
      from [PurchaseHeader] 
    
      where [PurchaseHeader].[documenttype]='18' and [PurchaseHeader].[released] IS NULL 
     order by [PurchaseHeader].[docdate] desc";
    $Result=DB_query($SQL,$db);
           
    Echo '<DIV class="container">'
        . '<Table class="table table-bordered"><tr>'
        . '<th>Supplier Invoice<br /> for GRN</th>'
        . '<th>Purchase <br /> Order <br /> Document<br /> date</th>'
        . '<th>Purchase <br /> Order <br />Due <br />Date</th>'
        . '<th>Vendor <br />ID</th>'
        . '<th>Vendor<br /> Name</th>'
        . '<th>Currency</th>'
        . '<th>Authorisation<br /> Status</th>'
        . '<th>Created<br /> By</th>'
        . '</tr>';
    
 
  while($row=DB_fetch_array($Result)){
        echo '<tr>';
        echo sprintf('<td><a href="%s?No=%s">Select :%s</a></td>',htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',is_null($row['duedate'])?'': ConvertSQLDate($row['duedate']));
        echo sprintf('<td>%s</td>',$row['vendorcode']);
        echo sprintf('<td>%s</td>',$row['vendorname']);
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['status']==1?'Pending Approval':'');
        echo sprintf('<td>%s</td>',$row['userid']);
        echo '</tr>';
  }
        
    echo '</table></DIV>';
}   
     
include('includes/footer.inc');


?>