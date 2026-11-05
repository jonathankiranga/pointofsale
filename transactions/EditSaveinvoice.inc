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
    
    $sql[] ="delete from CustomerStatement where Documentno='".$INVOICENO."' and Documenttype='10'";
    $sql[] ="delete from Generalledger where DocumentNo='".$INVOICENO."' and DocumentType='10'";
    $sql[] ="delete from debtorsledger where invref='".$INVOICENO."' and [systypes_1]='10'";
    $sql[] ="delete from SalesLine where documentno='".$INVOICENO."' and [documenttype]='10'";
    $sql[] ="delete from SalesHeader where documentno='".$INVOICENO."' and [documenttype]='10'";
    
    
    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }
    
     if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
     }else{
       DB_Txn_Commit($db);
    }
    
    $sql=array();
    
    $sql[] ="insert into [SalesHeader] 
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
      from [SalesHeader] 
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
     
     
    $sql[] =  sprintf("INSERT INTO [SalesLine]
           ([documenttype],[docdate],[documentno],[code],[description],[unitofmeasure]
           ,[Quantity],[UnitPrice],[vatamount],[invoiceamount],[vatrate],[inclusive],[locationcode]
           ,UOM,[shipping],BatchNO,Itemlist,sampleID) 
           (select '10','%s','%s',[code],[description],[unitofmeasure],[Qunatity_delivered],
           %f ,%f ,%f ,[vatrate],[inclusive],'%s','fulqty',%f,'%s',Itemlist,sampleID
           from SalesLine where [entryno]='".$key."')"
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
      SalesHeader.[docdate]
      ,rtrim(SalesLine.sampleID)  as narration,'I'
      ,SalesHeader.[documentno]
      ,SalesHeader.[customercode]
      ,SUM(SalesLine.invoiceamount) * ".$discount." as Value
      ,0.00
      ,SalesHeader.[currencycode]
      ,'I'
      ,'%s'
      ,'r'
      ,SUM(SalesLine.vatamount) * ".$discount." as VAT
      ,SalesHeader.[documenttype]
      ,SalesHeader.[postinggroup]
      ,'%s'
  FROM [SalesHeader] join SalesLine 
        on SalesHeader.documentno=SalesLine.documentno 
        and SalesHeader.documenttype=SalesLine.documenttype 
        where SalesHeader.documentno='%s' 
        group by
       SalesHeader.[documenttype]
      ,SalesHeader.[documentno]
      ,SalesHeader.[docdate]
      ,SalesHeader.[customercode]
      ,SalesHeader.[externaldocumentno]
      ,SalesHeader.[postinggroup]
      ,SalesHeader.[currencycode]
      ,SalesLine.[Quantity]
      ,SalesLine.sampleID
      ,SalesLine.[code]
      ,SalesLine.[UOM])", $journal,$period,$doc);
   
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
      ,SalesHeader.[docdate]
      ,'%s'
      ,SalesHeader.documentno
      ,SalesHeader.documenttype
      ,[postinggroups].[debtorsaccount]
      ,[inventorypostinggroup].[defaultgl_sales]
      ,[inventorypostinggroup].[defaultgl_vat]
      ,SalesLine.invoiceamount * ".$discount."
      ,IFNULL(SalesLine.vatamount * ".$discount.",0) as vatamount
      ,SalesHeader.[currencycode]
      ,[currencies].[rate]
      ,SalesHeader.[customercode]
      ,(rtrim(SalesLine.sampleID))  as narration
       ,'".$_POST['DimensionOne']."' 
       ,'".$_POST['DimensionTwo']."'
      from SalesHeader join [postinggroups] on [SalesHeader].[postinggroup]=[postinggroups].[code]
      join SalesLine on [SalesLine].[documentno] = [SalesHeader].[documentno] 
      join [stockmaster] on [SalesLine].[code]=[stockmaster].itemcode
      join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
      join [currencies] on [SalesHeader].[currencycode]=[currencies].[currabrev]
      where SalesHeader.documentno='%s'
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
      ,SalesHeader.[docdate]
      ,'%s'
      ,SalesHeader.documentno
      ,SalesHeader.documenttype
      ,[inventorypostinggroup].[defaultgl_sales]
      ,[inventorypostinggroup].[defaultgl_vat]
      ,''
      ,SalesLine.vatamount * ".$discount."
      ,0
      ,SalesHeader.[currencycode]
      ,[currencies].[rate]
      ,SalesHeader.[customercode]
      ,(rtrim(SalesLine.sampleID))  as narration
      from SalesHeader join [postinggroups] on [SalesHeader].[postinggroup]=[postinggroups].[code]
      join SalesLine on [SalesLine].[documentno] = [SalesHeader].[documentno] 
      join [stockmaster] on [SalesLine].[code]=[stockmaster].itemcode
      join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
      join [currencies] on [SalesHeader].[currencycode]=[currencies].[currabrev]
      where SalesHeader.documentno='%s'
      )",$journal,$period,$doc);
    
    return $sql;
    
}

Function CustomerStatemet($journal,$doc,$discount){
    $sql=sprintf("INSERT INTO [CustomerStatement]
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
              SalesHeader.[docdate]
             ,SalesHeader.documentno
             ,SalesHeader.documenttype
             ,SalesHeader.customercode
             ,sum(SalesLine.invoiceamount) * ".$discount."
             ,'%s'
             ,'".$_POST['DimensionOne']."'
             ,'".$_POST['DimensionTwo']."' 
             ,SalesHeader.[currencycode]    
             from SalesHeader
             join SalesLine on [SalesLine].[documentno] = [SalesHeader].[documentno] 
           where SalesHeader.documentno='%s' 
           Group by 
             SalesHeader.[docdate]
             ,SalesHeader.documentno
             ,SalesHeader.documenttype
             ,SalesHeader.customercode
             ,SalesHeader.[currencycode]
             ) ",
            $journal,$doc);
    
    return $sql;
}

?>