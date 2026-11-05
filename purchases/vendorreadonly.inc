<?php
  
$sqldebtors=DB_query("SELECT [itemcode],[customer],[phone] ,[email] ,
                             [city] ,[country],[curr_cod],
                             [supplierposting],[VATinclusive]
       FROM [creditors] join arpostinggroups on code=[supplierposting] 
       where itemcode='".$_POST['VendorID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[7];
$VATinclusive = $debtorsrow[8];


$transstart=DB_Txn_Begin($db);
$PeriodNo = GetPeriod($_POST['date'],$db,false);
$_POST['documentno'] = GetNextTransNo(18,$db);
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
           ,[vatinclusive]
           ,[Dimension_1]
           ,[Dimension_2]
           ,[freight])
     VALUES
           ('18'
           ,'".$_POST['documentno']."'
           ,'".FormatDateForSQL($_POST['date'])."'
           ".(Is_date($_POST['Salesoderdate'])?",'".FormatDateForSQL($_POST['Salesoderdate'])."'":'')."
           ".(Is_date($_POST['datedue'])?",'".FormatDateForSQL($_POST['datedue'])."'":'') ."
           ,'".$_POST['VendorID']."'
           ,'".$_POST['VendorName']."'
           ,'".$_POST['reference']."'
           ,'".$_POST['locationcode']."'
           ,'".$customerposting ."'
           ,'".$_POST['currencycode']."'
           ,'1'
           ,'".$_SESSION['UserID']."'
           ,'".$PeriodNo."'
           ,'".$VATinclusive."'
           ,'".$_POST['DimensionOne']."'
           ,'".$_POST['DimensionTwo']."'
           ,'".$_POST['FREIGHT']."')" ;



foreach ($_POST['grossamount'] as $itemcode => $value ) {
 
    if($value>0){
        
        $stockcode =$_POST['itemcode'][$itemcode];
        
        $SqL="SELECT 
        [stockmaster].[barcode],
        [stockmaster].[itemcode],
        [stockmaster].[descrip] as [stockname],
        [unit].[descrip] as [si],
        [stockmaster].[averagestock],
        [stockmaster].[partperunit],
        [vatcategory].[vat]
  FROM [stockmaster] 
  left join [unit] on [stockmaster].[units]=[unit].[code]
  left join [inventorypostinggroup] on [stockmaster].[postinggroup]=[inventorypostinggroup].[code]
  left join [vatcategory] on [inventorypostinggroup].[vatcategory]=[vatcategory].[vatc]
 where [stockmaster].inactive=0 and [itemcode]='".$stockcode ."'";
    
   $ResultIndex = DB_query($SqL, $db);
   $stkmaster = DB_fetch_row($ResultIndex);
   $stockname = $stkmaster[2];
   $si        = $stkmaster[3];
   $parts     = $stkmaster[5];
   $vatrate   = $stkmaster[6];       
   
    $UOM = $_POST['UOM'][$itemcode];
            
   if(isset($_POST['quantity'][$itemcode])){
        $qty = $_POST['quantity'][$itemcode];
    }
           
    if(isset($_POST['salesprice'][$itemcode])){
        $salesprice = $_POST['salesprice'][$itemcode] ;
    }
    
    if(isset($_POST['grossamount'][$itemcode])){
        $grossamount = $_POST['grossamount'][$itemcode] ;
    }
    
    if(isset($_POST['vatamount'][$itemcode])){
       $vatamount = $_POST['vatamount'][$itemcode];
    }
    
    $sql[] =  sprintf("INSERT INTO [PurchaseLine]
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
           ,vatrate
           ,inclusive
           ,UOM)
     VALUES
           ('%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s')"
            ,"18"
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
           ,$UOM);
        }
        
        
}


if( $_SESSION['locked'] == true){
     foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }

    $_SESSION['savedsuccessfuly']=false;

    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('Failed to save Purchase order :'.$_POST['documentno'],'warn');
    }else{
        DB_Txn_Commit($db);
        prnMsg('new Purchase order :'.$_POST['documentno']);
        $_SESSION['savedsuccessfuly'] = true;
        $_SESSION['locked'] = false;
    }
    
}

if($_SESSION['savedsuccessfuly']==true){
    
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
      from [PurchaseHeader]  where [documenttype]='18' and [documentno]='".$_POST['documentno']."'";
    $Result=DB_query($SQL,$db);
       
    prnMsg('Please Click Confirm if this is correct and select if You want an LPO or LSO');
 
    Echo '<div class="card"><table class="table table-bordered"><tr><th>Parameter</th><th>Value</th></tr>';
    $row=DB_fetch_row($Result);
        
    echo sprintf("<tr><td>Purchase Order No</td><td>%s</td></tr>",$_POST['documentno']);
    echo sprintf("<tr><td>Purchase Order Document date</td><td>%s</td></tr>",Is_null($row[1])?'': ConvertSQLDate($row[1]));
    echo sprintf("<tr><td>Purchase Order date</td><td>%s</td></tr>", Is_null($row[2])? '':ConvertSQLDate($row[2]));
    echo sprintf("<tr><td>Purchase Order Due Date</td><td>%s</td></tr>", Is_null($row[3])?'': ConvertSQLDate($row[3]));
    echo sprintf("<tr><td>Supplier ID</td><td>%s</td></tr>", $row[5]);
    echo sprintf("<tr><td>Supplier Name</td><td>%s</td></tr>",$row[6]);
    echo sprintf("<tr><td>Freight Cost</td><td>%s</td></tr>",number_format($row[17],2) );
    echo '<tr><td colspan="2">Line Details</td></tr>';
   
    echo '<tr><td colspan="2">';
    
    echo '<table class="table table-bordered"><tr><th>Stck Code</th>'
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
           where [documenttype]=18 and [documentno]='".$_POST['documentno']."'";
   
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
    
    echo '<tr><td><input type="submit" name="confirm" value="'._('Click if LPO').'"/>'
            . '<input type="submit" name="confirmservice" value="'._('Click if LSO').'"/>'
            . '<input type="submit" name="Delete" value="'._('Delete This Document').'"/></td></tr>';
    echo '</table></div>';
    
}
    $_SESSION['Grossamounttotal']=0;
    $_SESSION['CompleteDocument']=$_POST['documentno'];
    