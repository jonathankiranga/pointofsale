<?php
  
$sqldebtors=DB_query("SELECT [itemcode] ,[creditlimit],[customer]
      ,[phone] ,[email] ,[city] ,[country],[curr_cod],
      [customerposting],[salesman],[VATinclusive]
       FROM [debtors] join postinggroups on code=[customerposting]
       where itemcode='".$_POST['CustomerID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[8];
$VATinclusive    = $debtorsrow[10];

$transstart = DB_Txn_Begin($db);
$PeriodNo = GetPeriod($_POST['date'],$db,false);

$sql=array();

$sql[]="INSERT INTO [SalesHeader]
           ([documenttype]
           ,[documentno]
           ,[docdate]
           ".(Is_date($_POST['Salesoderdate'])?',[oderdate]':'')."
           ".(Is_date($_POST['datedue'])?',[duedate]':'')."
           ,[customercode]
           ,[customername]
           ,[yourreference]
           ,[locationcode]
           ,[postinggroup]
           ,[currencycode]
           ,[salespersoncode]
           ,[status]
           ,[userid]
           ,[period]
           ,[vatinclusive])
     VALUES
           ('54'
           ,'".$_POST['documentno']."'
           ,'".FormatDateForSQL($_POST['date'])."'
           ".(Is_date($_POST['Salesoderdate'])?",'".FormatDateForSQL($_POST['Salesoderdate'])."'":'')."
           ".(Is_date($_POST['datedue'])?",'".FormatDateForSQL($_POST['datedue'])."'":'') ."
           ,'".$_POST['CustomerID']."'
           ,'".$_POST['CustomerName']."'
           ,'".$_POST['reference']."'
           ,'".$_POST['locationcode']."'
           ,'".$customerposting ."'
           ,'".$_POST['currencycode']."'
           ,'".$_POST['salespersoncode']."'
           ,'1'
           ,'".$_SESSION['UserID']."'
           ,'".$PeriodNo."'
           ,'".$VATinclusive."')" ;



foreach ($_SESSION['sales_orders'] as $lineno => $value) {
    
   if($_POST['grossamount'][$lineno]>0){
        
     $stockcode   = $value['itemcode']; 
     $si          = $_POST['UOM'][$lineno]; 
     $qty         = $value['quantity'];
     $salesprice  = $_POST['salesprice'][$lineno] ;
     $grossamount = $_POST['grossamount'][$lineno] ;
     $vatamount   = $_POST['vatamount'][$lineno];
     $vatrate     = $value['CVAT'];
     $stockcode   = $value['itemcode'];
     $stockname   = $value['stockname'];
    
    $sql[] =  sprintf("INSERT INTO [SalesLine]
           ([documenttype]
           ,[docdate]
           ,[documentno]
           ,[code]
           ,[description]
           ,[unitofmeasure]
           ,[Quantity]
           ,[UnitPrice]
           ,[vatamount]
           ,[invoiceamount]
           ,[vatrate]
           ,[inclusive]
           ,[containerprice]
           ,[containersunits]
           ,[totalchargedcontainers]
           ,[containercode]
           ,UOM)
     VALUES
           ('%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,%f
           ,%f
           ,%f
           ,'%f'
           ,'%s'
           ,%f
           ,%f
           ,%f
           ,'%s'
           ,'%s')"
            ,"54"
           ,FormatDateForSQL($_POST['date'])
           ,$_POST['documentno']
           ,$stockcode
           ,$stockname
           ,$si
           ,$qty
           ,$salesprice
           ,$vatamount
           ,$grossamount
           ,$vatrate
           ,$VATinclusive
           ,0
           ,0
           ,0
           ,0
           ,'fulqty');
        }
        
        
}


if( $_SESSION['locked'] == true){
    
    foreach ($sql as $value) {
        
        $ResultIndex=DB_query($value,$db);
    }

    $_SESSION['savedsuccessfuly']=false;

    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('Failed to save sales order :'.$_POST['documentno'],'warn');
        $_SESSION['savedsuccessfuly'] = false;
        $_SESSION['locked'] = true;
    } else {
        DB_Txn_Commit($db);
        prnMsg('Sales order :'.$_POST['documentno'].' has been created','info');
        $_SESSION['savedsuccessfuly'] = true;
        $_SESSION['locked'] = false;
    }
    
}

if($_SESSION['savedsuccessfuly']==true){
    
    $SQL="select 
       [documentno],[docdate],[oderdate] ,[duedate] ,[postingdate] ,[customercode] ,[customername]
      ,[yourreference],[externaldocumentno] ,[locationcode]  ,[paymentterms],[postinggroup]
      ,[currencycode] ,[salespersoncode] ,[printed] ,[released],[status]
      ,[userid] from [SalesHeader] where [documenttype]='54' 
      and  [documentno]='".$_POST['documentno']."'";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table"><tr><th>Parameter</th><th>Value</th></tr>';
    $row=DB_fetch_row($Result);
        
    echo sprintf("<tr><td>Sales Quote No</td><td>%s</td></tr>",$_POST['documentno']);
    echo sprintf("<tr><td>Sales Quote Document date</td><td>%s</td></tr>",Is_null($row[1])?'': ConvertSQLDate($row[1]));
    echo sprintf("<tr><td>Customer ID</td><td>%s</td></tr>", $row[5]);
    echo sprintf("<tr><td>Customer Name</td><td>%s</td></tr>",$row[6]);
    echo '<tr><td colspan="2">Line Details</td></tr>';
    
    
    $SQL="select 
            [documenttype] ,[docdate] ,[documentno] ,[code],[description]
           ,(case [SalesLine].UOM when 'fulqty' then (select rtrim([unit].[descrip]) from [unit] join stockmaster on stockmaster.units=[unit].code    and stockmaster.itemcode=SalesLine.code) else (select rtrim([unit].[descrip])  from [unit] join stockmaster on stockmaster.subunits=[unit].code and stockmaster.itemcode=SalesLine.code) end) as [unitofmeasure],
           [Quantity],[UnitPrice],[totalchargedcontainers] ,[vatamount] ,[invoiceamount] ,UOM 
           from [SalesLine] where [documenttype]=54 and [documentno]='".$_POST['documentno']."'";
    echo '<tr><td colspan="2">';
    
    echo '<table  class="table table-bordered"><thead><tr>'
            . '<th>Product<br /> Code</th>'
            . '<th>Product Name</th>'
            . '<th>Unit Of<br /> Measure</th>'
            . '<th class="number">Quantity</th>'
            . '<th class="number">Sales Price</th>'
            . '<th class="number">VAT Amount</th>'
            . '<th class="number">Gross Amount</th>'
            . '</tr></thead>';
    
    $VATAMOUNT=0;
    $INVOICEAMOUNT=0;
    
    $ResultIndex=DB_query($SQL,$db);
    while($myrow=DB_fetch_array($ResultIndex)){
         echo sprintf('<tr>'
                 . '<td>%s</td>'
                 . '<td>%s</td>'
                 . '<td>%s</td>'
                 . '<td>%s</td>'
                 . '<td class="number">%s</td>'
                 . '<td class="number">%s</td>'
                 . '<td class="number">%s</td></tr>',
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
    
    
     echo sprintf('<tfoot><tr><td colspan="5">%s</td><td  class="number">%s</td>'
             . '<td class="number">%s</td></tr></tfoot>','Sales Quote Totals',
             number_format($VATAMOUNT,2),number_format($INVOICEAMOUNT,2));

    echo '</table></td></tr>';
    echo '</table>';
    
}

$_SESSION['Grossamounttotal']=0;
$_SESSION['CompleteDocument']=$_POST['documentno'];

echo '<div class="rightside"><input type="submit" name="confirm" value="'._('Confirm Sales Quote').'"/>';
echo '<input type="submit" name="Delete" value="'._('Cancel Quote').'"/></div>';
?>
