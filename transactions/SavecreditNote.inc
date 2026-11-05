<?php

$_SESSION['DocumentNo']=$_POST['documentno'] ;

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
    $VATinclusive    = $debtorsrow[10];
    $PeriodNo        = GetPeriod($_POST['posingdate'],$db,false);
    $INVOICENO       = GetNextTransNo(13,$db);
 
    $TransBuffer     = DB_Txn_Begin($db);
    $sql=array();
    
    $sql[] ="insert into [SalesHeader] 
      ([documenttype]
      ,[documentno]
      ,[docdate]
       ".(Is_date($_POST['Salesoderdate'])?',[oderdate]':'')."
       ".(Is_date($_POST['datedue'])?',[duedate]':'')."
      ,[postingdate]
      ,[customercode]
      ,[customername]
      ,[yourreference]
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
      ,sampleID) 
      (select 13
      ,'".$INVOICENO."'
      ,'".FormatDateForSQL($_POST['date'])."'
       ".(Is_date($_POST['Salesoderdate'])?",'".FormatDateForSQL($_POST['Salesoderdate'])."'":'')."
       ".(Is_date($_POST['datedue'])?",'".FormatDateForSQL($_POST['datedue'])."'":'') ."
       ,'".FormatDateForSQL($_POST['posingdate'])."'
       ,'".$_POST['CustomerID']."'
       ,'".$_POST['CustomerName']."'
      ,'".$_POST['reference']."'
      ,'".$_SESSION['DocumentNo']."'
      ,'".$customerposting."'
      ,'".$_POST['currencycode']."'
      ,'".$_POST['salespersoncode']."'
      ,0
      ,0
      ,1
      ,'".$_SESSION['UserID']."'
      ,'".$PeriodNo."'
      ,'".$VATinclusive ."'
      ,SalesHeader.sampleID
      from [SalesHeader] 
      where documentno='".$_SESSION['DocumentNo']."') ";
    
    foreach ($_POST['code'] as $key => $value) {
       
        if(mb_strlen($value)>0){
        
            $location = $_POST['location'][$key];
            $stcode = $_POST['code'][$key];
            $qty = $_POST['subunits'][$key];        
            $salesprice = $_POST['salesprice'][$key];
            $netamount = $_POST['netamount'][$key];
            $vatamount = $_POST['vatamount'][$key];
            $grossamount = $_POST['grossamount'][$key];
            
   
            $sql[] = sprintf("INSERT INTO [SalesLine] 
           ([documenttype],[docdate],[documentno],[code],[description],[unitofmeasure],[Quantity],[UnitPrice],[vatamount],[invoiceamount],[vatrate],[inclusive],[locationcode],[UOM],sampleID) 
           (select '13','%s','%s',[code],[description],[unitofmeasure],%f,%f,%f,%f,[vatrate],[inclusive],'%s',[UOM],sampleID from SalesLine where [entryno]='%s')"
           ,FormatDateForSQL($_POST['date']),$INVOICENO,$qty,$salesprice,$vatamount,$grossamount ,$location ,$key);
    
         }
  
    }
    
     $journal=GetNextTransNo(0,$db);
     $sql[]=CustomerStatemet($journal,$INVOICENO);
     $sql[]=PostDebtors($PeriodNo,$journal,$INVOICENO);
     $sql[]=PostGL($PeriodNo,$journal,$INVOICENO);
     $sql[]=VATPostGL($PeriodNo,$journal,$INVOICENO);
     
    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }
    
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('There was an Error Posting the Credit Note, Contact your administrator','warn');
    } else {
        prnMsg('Click here to Print Credit Note :<a href="PDFPrintcreditnote.php?No='.$INVOICENO.'">'.$INVOICENO.'</a>');
  
        DB_query("Update SalesHeader set [released]=3 where documentno='".$_SESSION['DocumentNo']."'",$db);
        DB_Txn_Commit($db);
        prnMsg('This Sale has successfully been posted in the General Ledger');
        
        $_SESSION['DocumentPosted']=true;
        unset($_SESSION['DocumentNo']);
        unset($_POST);
    }
    
}

function PostDebtors($period,$journal,$doc){
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
      SalesHeader.[postingdate]
      ,SalesLine.[sampleID] as narration,'I'
      ,SalesHeader.[documentno]
      ,SalesHeader.[customercode]
      ,0-SUM(SalesLine.invoiceamount) as Value
      ,0.00
      ,SalesHeader.[currencycode]
      ,'I'
      ,'%s'
      ,'r'
      ,0-SUM(SalesLine.vatamount) as VAT
      ,SalesHeader.[documenttype]
      ,SalesHeader.[postinggroup]
      ,'%s'
  FROM [SalesHeader] 
       join SalesLine on SalesHeader.documentno=SalesLine.documentno and SalesHeader.documenttype=SalesLine.documenttype 
      where SalesLine.[documentno]='%s' and SalesLine.documenttype =13
        group by
       SalesHeader.[documenttype]
      ,SalesHeader.[documentno]
      ,SalesHeader.[postingdate]
      ,SalesHeader.[customercode]
      ,SalesHeader.[externaldocumentno]
      ,SalesHeader.[postinggroup]
      ,SalesHeader.[currencycode]
      ,SalesLine.[sampleID]
      ,SalesLine.[Quantity]
       ,SalesLine.[code]
      ,SalesLine.[UOM])", $journal,$period,$doc);
   
    
   return $SQL;
}

function PostGL($period,$journal,$doc){
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
      ,SalesHeader.[postingdate]
      ,'%s'
      ,SalesHeader.documentno
      ,SalesHeader.documenttype
      ,[inventorypostinggroup].[defaultgl_sales]
      ,[postinggroups].[debtorsaccount]
      ,[inventorypostinggroup].[defaultgl_vat]
      ,SalesLine.invoiceamount
      ,IFNULL(SalesLine.vatamount,0) as vatamount
      ,SalesHeader.[currencycode]
      ,[currencies].[rate]
      ,SalesHeader.[customercode]
      ,SalesHeader.[sampleID] as narration
       ,'".$_POST['DimensionOne']."' 
       ,'".$_POST['DimensionTwo']."'
      from SalesHeader 
      join [postinggroups] on [SalesHeader].[postinggroup]=[postinggroups].[code]
      join SalesLine on [SalesLine].[documentno] = [SalesHeader].[documentno] 
      join LaboratoryStandards on LaboratoryStandards.ParameterID=salesline.code
      join inventorypostinggroup on inventorypostinggroup.code=LaboratoryStandards.category
      join [currencies] on [SalesHeader].[currencycode]=[currencies].[currabrev]
      where SalesLine.[documentno]='%s' and SalesLine.documenttype =13
      )",$journal,$period,$doc);
    
    return $sql;
    
}

function VATPostGL($period,$journal,$doc){
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
      ,SalesHeader.[postingdate]
      ,'%s'
      ,SalesHeader.documentno
      ,SalesHeader.documenttype
      ,[inventorypostinggroup].[defaultgl_vat]
       ,[inventorypostinggroup].[defaultgl_sales]
      ,''
      ,SalesLine.vatamount
      ,0
      ,SalesHeader.[currencycode]
      ,[currencies].[rate]
      ,SalesHeader.[customercode]
      ,SalesLine.[sampleID] as narration
      from SalesHeader join [postinggroups] on [SalesHeader].[postinggroup]=[postinggroups].[code]
      join SalesLine on [SalesLine].[documentno] = [SalesHeader].[documentno] 
      join LaboratoryStandards on LaboratoryStandards.ParameterID=salesline.code
      join inventorypostinggroup on inventorypostinggroup.code=LaboratoryStandards.category
      join [currencies] on [SalesHeader].[currencycode]=[currencies].[currabrev]
      where SalesLine.[documentno]='%s' and SalesLine.documenttype =13
      )",$journal,$period,$doc);
    
    return $sql;
    
}


Function CustomerStatemet($journal,$doc){
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
              SalesHeader.[postingdate]
             ,SalesHeader.documentno
             ,SalesHeader.documenttype
             ,SalesHeader.customercode
             ,0-sum(SalesLine.invoiceamount)
             ,'%s'
             ,'".$_POST['DimensionOne']."'
             ,'".$_POST['DimensionTwo']."' 
             ,SalesHeader.[currencycode]    
             from SalesHeader
             join SalesLine  on [SalesLine].[documentno] = [SalesHeader].[documentno] 
           where SalesLine.[documentno]='%s' and SalesLine.documenttype =13
           Group by 
             SalesHeader.[postingdate]
             ,SalesHeader.documentno
             ,SalesHeader.documenttype
             ,SalesHeader.customercode
             ,SalesHeader.[currencycode]
             ) ",
            $journal,$doc);
    
    return $sql;
}






?>