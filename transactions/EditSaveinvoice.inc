<?php

$_SESSION['DocumentNo']=$_POST['salesorder'] ;
if($_SESSION['DocumentPosted']==true){
    prnMsg('You cannot repost this invoice','warn');
}else{
       
    $ResultIndex = DB_query('Select sysdate() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['posingdate'] = ConvertSQLDate($rowdate[0]);
    
    $sqldebtors=DB_query("SELECT [itemcode],[creditlimit],[customer],[phone],
    [email],[city],[country],[curr_cod],[customerposting],[salesman],[VATinclusive]
    FROM [debtors] join postinggroups on code=[customerposting] 
    where itemcode='".$_POST['CustomerID']."'", $db);
    
    $debtorsrow      = DB_fetch_row($sqldebtors);
    $customerposting = $debtorsrow[8];
    $salesrep        = $debtorsrow[9];
    $VATinclusive    = $debtorsrow[10];
    $PeriodNo        = GetPeriod($_POST['date'],$db,false);
    $discount        = ($_POST['discount'] * 0.01);
    $INVOICENO       = trim($_POST['documentno']) ; // shoulb be 10 but 10 is Lab batch
    
    $sql = array();
    $TransBuffer  = DB_Txn_Begin($db);
    
    $sql[] ="delete from customerstatement where Documentno='".$INVOICENO."' and Documenttype='10'";
    $sql[] ="delete from Generalledger where DocumentNo='".$INVOICENO."' and DocumentType='10'";
    $sql[] ="delete from debtorsledger where invref='".$INVOICENO."' and [systypes_1]='10'";
    $sql[] ="delete from salesline where documentno='".$INVOICENO."' and [documenttype]='10'";
    $sql[] ="delete from salesheader where documentno='".$INVOICENO."' and [documenttype]='10'";
    
    
    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }
    
     if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
     }else{
       DB_Txn_Commit($db);
    }
    
    $sql=array();
    
    $sql[] ="insert into [salesheader] 
      ([documenttype]
      ,[documentno]
      ,[docdate]
      ,[postingdate]
      ,[customercode]
      ,[customername]
      ,[lpo]
      ,[externaldocumentno]
      ,[postinggroup]
      ,[currencycode]
      ,[salespersoncode]
      ,[printed]
      ,[released]
      ,[status]
      ,[userid]
      ,[period]
      ,[vatinclusive]
      ,[discount]
      ,BatchNO) 
      (select 10
      ,'".$INVOICENO."'
      ,'".FormatDateForSQL($_POST['date'])."'
      ,'".FormatDateForSQL($_POST['posingdate'])."'
      ,'".$_POST['CustomerID']."'
      ,'".$_POST['CustomerName']."'
      ,'".$_POST['reference']."'
      ,'".$_SESSION['DocumentNo']."'
      ,'".$customerposting."'
      ,'".$_POST['currencycode']."'
      ,'".$salesrep."'
      ,0
      ,0
      ,1
      ,'".$_SESSION['UserID']."'
      ,'".$PeriodNo."'
      ,'".$VATinclusive ."'
      ,'".$_POST['discount'] ."'
      ,'".$_SESSION['batchnoref']."'
      from [salesheader] 
      where documentno='".$_SESSION['DocumentNo']."') ";
    
    foreach ($_POST['code'] as $key => $value) {
       
    if(mb_strlen($value)>0){
        
    $location    = $_POST['location'][$key];
    $stcode      = $_POST['code'][$key];
    $qty         = $_POST['subunits'][$key];        
    $salesprice  = $_POST['salesprice'][$key];
    $Shipping    = $_POST['Shipping'][$key] ; 
    $netamount   = $_POST['netamount'][$key];
    $vatamount   = $_POST['vatamount'][$key];
    $grossamount = $_POST['grossamount'][$key];
   
    $journal = GetNextTransNo(0,$db);
     
     
    $sql[] =  sprintf("INSERT INTO [salesline]
           ([documenttype],[docdate],[documentno],[code],[description],[unitofmeasure]
           ,[Quantity],[UnitPrice],[vatamount],[invoiceamount],[vatrate],[inclusive],[locationcode]
           ,UOM,[shipping],BatchNO,Itemlist,sampleID) 
           (select '10','%s','%s',[code],[description],[unitofmeasure],[Qunatity_delivered],
           %f ,%f ,%f ,[vatrate],[inclusive],'%s','fulqty',%f,'%s',Itemlist,sampleID
           from salesline where [entryno]='".$key."')"
           ,FormatDateForSQL($_POST['date'])
           ,$INVOICENO
           ,$salesprice
           ,$vatamount 
           ,$grossamount
           ,$location
           ,$Shipping
           ,$_SESSION['batchnoref']);
    
    }
  
    }
    
    if($discount==0)$discount=1;
    $journal = GetNextTransNo(0,$db);
    $sql[]=PostGL($PeriodNo,$journal,$INVOICENO,$discount);
    $sql[]=PostDebtors($PeriodNo,$journal,$INVOICENO,$discount);
    $sql[]=VATPostGL($PeriodNo,$journal,$INVOICENO,$discount);
    $sql[]=CustomerStatemet($journal,$INVOICENO,$discount);
     
    foreach ($sql as $value) {
       $ResultIndex=DB_query($value,$db);
    }
   
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('There was an Error Posting the invoice, Contact your administrator','warn');
    }else{
        prnMsg('Click here to print invoice No :<a href="PDFPrintSalesInvoice.php?No='.$INVOICENO.'">'.$INVOICENO.'</a>');
        DB_Txn_Commit($db);
        prnMsg('This Sale has successfully been Edited in the General Ledger');
        
        $_SESSION['DocumentPosted']=true;
        unset($_SESSION['bybatchno']);
        unset($_SESSION['batchnoref']);
        unset($_SESSION['DocumentNo']);
        unset($_POST);
    }
    
}

function PostDebtors($period,$journal,$doc,$discount){
   $SQL= sprintf("Insert into [debtorsledger]
       ([date]
      ,[details]
      ,[flag]
      ,[invref]
      ,[acctfolio]
      ,[amount]
      ,[pamount]
      ,[curr_cod]
      ,[i_n_t]
      ,[journal]
      ,[typ]
      ,[vatamt]
      ,[systypes_1]
      ,[ledger]
      ,period)
  (SELECT 
      salesheader.[docdate]
      ,rtrim(salesline.sampleID)  as narration,'I'
      ,salesheader.[documentno]
      ,salesheader.[customercode]
      ,SUM(salesline.invoiceamount) * ".$discount." as Value
      ,0.00
      ,salesheader.[currencycode]
      ,'I'
      ,'%s'
      ,'r'
      ,SUM(salesline.vatamount) * ".$discount." as VAT
      ,salesheader.[documenttype]
      ,salesheader.[postinggroup]
      ,'%s'
  FROM [salesheader] join salesline 
        on salesheader.documentno=salesline.documentno 
        and salesheader.documenttype=salesline.documenttype 
        where salesheader.documentno='%s' 
        group by
       salesheader.[documenttype]
      ,salesheader.[documentno]
      ,salesheader.[docdate]
      ,salesheader.[customercode]
      ,salesheader.[externaldocumentno]
      ,salesheader.[postinggroup]
      ,salesheader.[currencycode]
      ,salesline.[Quantity]
      ,salesline.sampleID
      ,salesline.[code]
      ,salesline.[UOM])", $journal,$period,$doc);
   
   return $SQL;
}

function PostGL($period,$journal,$doc,$discount){
    $sql=SPRINTF("Insert into [Generalledger]
      ([journalno]
      ,[Docdate]
      ,[period]
      ,[DocumentNo]
      ,[DocumentType]
      ,[accountcode]
      ,[balaccountcode]
      ,[VATaccountcode]
      ,[amount]
      ,[VATamount]
      ,[currencycode]
      ,[ExchangeRate]
      ,[cutomercode]
      ,[narration]
      ,[dimension]
      ,[dimension2])
      (select
       '%s'
      ,salesheader.[docdate]
      ,'%s'
      ,salesheader.documentno
      ,salesheader.documenttype
      ,[postinggroups].[debtorsaccount]
      ,[inventorypostinggroup].[defaultgl_sales]
      ,[inventorypostinggroup].[defaultgl_vat]
      ,salesline.invoiceamount * ".$discount."
      ,IFNULL(salesline.vatamount * ".$discount.",0) as vatamount
      ,salesheader.[currencycode]
      ,[currencies].[rate]
      ,salesheader.[customercode]
      ,(rtrim(salesline.sampleID))  as narration
       ,'".$_POST['DimensionOne']."' 
       ,'".$_POST['DimensionTwo']."'
      from salesheader join [postinggroups] on [salesheader].[postinggroup]=[postinggroups].[code]
      join salesline on [salesline].[documentno] = [salesheader].[documentno] 
      join [stockmaster] on [salesline].[code]=[stockmaster].itemcode
      join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
      join [currencies] on [salesheader].[currencycode]=[currencies].[currabrev]
      where salesheader.documentno='%s'
      )",$journal,$period,$doc);
    
    return $sql;
    
}

function VATPostGL($period,$journal,$doc,$discount){
    $sql=SPRINTF("Insert into [Generalledger]
      ([journalno]
      ,[Docdate]
      ,[period]
      ,[DocumentNo]
      ,[DocumentType]
      ,[accountcode]
      ,[balaccountcode]
      ,[VATaccountcode]
      ,[amount]
      ,[VATamount]
      ,[currencycode]
      ,[ExchangeRate]
      ,[cutomercode]
      ,[narration])
      (select
       '%s'
      ,salesheader.[docdate]
      ,'%s'
      ,salesheader.documentno
      ,salesheader.documenttype
      ,[inventorypostinggroup].[defaultgl_sales]
      ,[inventorypostinggroup].[defaultgl_vat]
      ,''
      ,salesline.vatamount * ".$discount."
      ,0
      ,salesheader.[currencycode]
      ,[currencies].[rate]
      ,salesheader.[customercode]
      ,(rtrim(salesline.sampleID))  as narration
      from salesheader join [postinggroups] on [salesheader].[postinggroup]=[postinggroups].[code]
      join salesline on [salesline].[documentno] = [salesheader].[documentno] 
      join [stockmaster] on [salesline].[code]=[stockmaster].itemcode
      join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
      join [currencies] on [salesheader].[currencycode]=[currencies].[currabrev]
      where salesheader.documentno='%s'
      )",$journal,$period,$doc);
    
    return $sql;
    
}

Function CustomerStatemet($journal,$doc,$discount){
    $sql=sprintf("INSERT INTO [customerstatement]
           ([Date]
           ,[Documentno]
           ,[Documenttype]
           ,[Accountno]
           ,[Grossamount]
           ,[JournalNo]
           ,[Dimension_One]
           ,[Dimension_Two]
           ,[Currency])
      (select
              salesheader.[docdate]
             ,salesheader.documentno
             ,salesheader.documenttype
             ,salesheader.customercode
             ,sum(salesline.invoiceamount) * ".$discount."
             ,'%s'
             ,'".$_POST['DimensionOne']."'
             ,'".$_POST['DimensionTwo']."' 
             ,salesheader.[currencycode]    
             from salesheader
             join salesline on [salesline].[documentno] = [salesheader].[documentno] 
           where salesheader.documentno='%s' 
           Group by 
             salesheader.[docdate]
             ,salesheader.documentno
             ,salesheader.documenttype
             ,salesheader.customercode
             ,salesheader.[currencycode]
             ) ",
            $journal,$doc);
    
    return $sql;
}

?>