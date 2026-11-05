<?php
$_SESSION['DocumentNo']=$_POST['documentno'] ;
$_SESSION['invoicerrors']=0;
$_SESSION['somedetails']=0;

foreach ($_POST['subunits'] as $key => $value) {
    $location = $_POST['location'][$key];
    $stcode   = $_POST['code'][$key] ;
    $qty      = $_POST['subunits'][$key] ;
    $dbbalance = $_POST['balance'][$key];
   
    if($qty > 0){
        $_SESSION['invoicerrors']++;
    }
       
    if($dbbalance < 0){
        $_SESSION['somedetails']++;
    } 
       
}
   
if($_SESSION['somedetails'] > 0) {
        prnMsg('You cannot Receive more than was ordered','warn');
}
    
 if($_SESSION['invoicerrors'] == 0){
       prnMsg('You have not entred stock units to receive','warn');
}   
    
if($_SESSION['DocumentPicking']==true){
      prnMsg('Create a new GRN no','warn');
}

if($_SESSION['somedetails'] == 0 AND $_SESSION['DocumentPicking']==false){
    
    $ResultIndex = DB_query('Select sysdate() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['posingdate'] = ConvertSQLDate($rowdate[0]);
       
    $sqldebtors=DB_query("SELECT [itemcode],[customer],[phone],
    [email],[city],[country],[curr_cod],[supplierposting],[VATinclusive]
    FROM [creditors] join arpostinggroups on code=[supplierposting] 
    where itemcode='".$_POST['VendorID']."'", $db);
    
    $debtorsrow = DB_fetch_row($sqldebtors);
    $customerposting = $debtorsrow[7];
    $VATinclusive    = $debtorsrow[8];
    $PeriodNo        = GetPeriod($_POST['posingdate'],$db,false);
    $TransBuffer     = DB_Txn_Begin($db);
    $INVOICENO       = GetNextTransNo(30,$db);
    $journal         = GetNextTransNo(0,$db);
    
    $sql=array();
    
    RecordGRN($INVOICENO);
    
    foreach ($_POST['subunits'] as $key => $value) {
         if($value>0){

               $location = $_POST['location'][$key];
               $stcode = $_POST['code'][$key];
               $qty = $_POST['subunits'][$key];       
               $salesoderno = $_SESSION['DocumentNo'];
            
               $sql[]= sprintf("Update [PurchaseLine] set
                    [Qunatity_delivered]= IFNULL([Qunatity_delivered],0)+ %f,
                    [Quantity_toinvoice]= [Quantity]-[Qunatity_delivered]
                    where [documentno]='%s' and [documenttype]='%s' 
                    and [entryno]=%s",$qty,$salesoderno,18,$key);
               
               
               
                $sql[]="INSERT [stockledger]
                    ([date]
                    ,[stname]
                    ,[batch]
                    ,[doctyp]
                    ,[itemcode]
                    ,[invref]
                    ,[netamt]
                    ,[vat]
                    ,[amount]
                    ,fulqty
                    ,[loosqty]
                    ,[price]
                    ,[store]
                    ,[journal]
                    ,[period]
                    ,jobcard)
                    (SELECT 
                        [PurchaseLine].[docdate]
                       ,substring([PurchaseLine].[description],0,30)
                       ,'".$key."'
                       ,'30'
                       ,[PurchaseLine].[code]
                       ,[PurchaseLine].[documentno]
                       ,[PurchaseLine].[invoiceamount]
                       ,[PurchaseLine].[vatamount]
                       ,[PurchaseLine].[invoiceamount]
                       ,case [PurchaseLine].UOM when 'fulqty' then ".$qty."  else 0 end as fulqty
                       ,case [PurchaseLine].UOM when 'loosqty' then ".$qty."  else 0 end as loosqty
                       ,[PurchaseLine].[UnitPrice]
                       ,'".$location."'
                       ,'".$journal."'
                       ,'".$PeriodNo."'
                       ,'".$INVOICENO ."' 
                       FROM [PurchaseLine] 
                       join stockmaster on [PurchaseLine].[code] = stockmaster.itemcode
                       where documentno='".$salesoderno."' "
                        . " and code='".$stcode."' "
                        . " and [entryno]='".$key."' "
                        . " and stockmaster.isstock <> 3)";
                

               $results=PostStockRegister($qty,$key,$journal,$PeriodNo);
              
         }
    }

    
    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('There was an Error , Contact your administrator','warn');
      
    } 
    else {
        DB_Txn_Commit($db);
        prnMsg('This GRN document '.$INVOICENO.' has been created'.'<a href="PDFPrintgrn.php?No='.$INVOICENO.'">GRN Reports</a>');
         $_SESSION['DocumentPicking']=true;
         $_SESSION['DocumentNo']=true;
        unset($_POST);

    }
    
}




Function RecordGRN($DOCNO){
 Global $db,$sql;
 
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
      (select '30'
           ,'".$DOCNO."'
           ,'".FormatDateForSQL($_POST['date'])."'
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
           ,[freight] from [PurchaseHeader] where [documentno]='".$_POST['documentno']."')" ;



            foreach ($_POST['subunits'] as $key => $value) {

            if($value>0){

                $itemcode = $_POST['code'][$key];
                    
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
                       (select 
                       30,
                        [docdate]
                       ,'".$DOCNO."'
                       ,[code]
                       ,[description]
                       ,[unitofmeasure]
                       ,%f
                       ,[UnitPrice]
                       ,[vatamount]
                       ,[invoiceamount]
                       ,vatrate
                       ,inclusive
                       ,UOM 
                       from [PurchaseLine]   where  [documentno]='".$_POST['documentno']."' and code='".$itemcode."')"
                       ,$value);
                    }


            }

Return $sql;

}


?>