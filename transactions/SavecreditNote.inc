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
    
    $sql[] ="insert into [salesheader] 
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
      ,salesheader.sampleID
      from [salesheader] 
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
            
   
            $sql[] = sprintf("INSERT INTO [salesline] 
           ([documenttype],[docdate],[documentno],[code],[description],[unitofmeasure],[Quantity],[UnitPrice],[vatamount],[invoiceamount],[vatrate],[inclusive],[locationcode],[UOM],sampleID) 
           (select '13','%s','%s',[code],[description],[unitofmeasure],%f,%f,%f,%f,[vatrate],[inclusive],'%s',[UOM],sampleID from salesline where [entryno]='%s')"
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
  
        DB_query("Update salesheader set [released]=3 where documentno='".$_SESSION['DocumentNo']."'",$db);
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
      salesheader.[postingdate]
      ,salesline.[sampleID] as narration,'I'
      ,salesheader.[documentno]
      ,salesheader.[customercode]
      ,0-SUM(salesline.invoiceamount) as Value
      ,0.00
      ,salesheader.[currencycode]
      ,'I'
      ,'%s'
      ,'r'
      ,0-SUM(salesline.vatamount) as VAT
      ,salesheader.[documenttype]
      ,salesheader.[postinggroup]
      ,'%s'
  FROM [salesheader] 
       join salesline on salesheader.documentno=salesline.documentno and salesheader.documenttype=salesline.documenttype 
      where salesline.[documentno]='%s' and salesline.documenttype =13
        group by
       salesheader.[documenttype]
      ,salesheader.[documentno]
      ,salesheader.[postingdate]
      ,salesheader.[customercode]
      ,salesheader.[externaldocumentno]
      ,salesheader.[postinggroup]
      ,salesheader.[currencycode]
      ,salesline.[sampleID]
      ,salesline.[Quantity]
       ,salesline.[code]
      ,salesline.[UOM])", $journal,$period,$doc);
   
    
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
      ,salesheader.[postingdate]
      ,'%s'
      ,salesheader.documentno
      ,salesheader.documenttype
      ,[inventorypostinggroup].[defaultgl_sales]
      ,[postinggroups].[debtorsaccount]
      ,[inventorypostinggroup].[defaultgl_vat]
      ,salesline.invoiceamount
      ,IFNULL(salesline.vatamount,0) as vatamount
      ,salesheader.[currencycode]
      ,[currencies].[rate]
      ,salesheader.[customercode]
      ,salesheader.[sampleID] as narration
       ,'".$_POST['DimensionOne']."' 
       ,'".$_POST['DimensionTwo']."'
      from salesheader 
      join [postinggroups] on [salesheader].[postinggroup]=[postinggroups].[code]
      join salesline on [salesline].[documentno] = [salesheader].[documentno] 
      join sampletypes on sampletypes.sampletypeid=salesline.code
      join inventorypostinggroup on inventorypostinggroup.code=sampletypes.salesgroup
      join [currencies] on [salesheader].[currencycode]=[currencies].[currabrev]
      where salesline.[documentno]='%s' and salesline.documenttype =13
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
      ,salesheader.[postingdate]
      ,'%s'
      ,salesheader.documentno
      ,salesheader.documenttype
      ,[inventorypostinggroup].[defaultgl_vat]
       ,[inventorypostinggroup].[defaultgl_sales]
      ,''
      ,salesline.vatamount
      ,0
      ,salesheader.[currencycode]
      ,[currencies].[rate]
      ,salesheader.[customercode]
      ,salesline.[sampleID] as narration
      from salesheader join [postinggroups] on [salesheader].[postinggroup]=[postinggroups].[code]
      join salesline on [salesline].[documentno] = [salesheader].[documentno] 
      join sampletypes on sampletypes.sampletypeid=salesline.code
      join inventorypostinggroup on inventorypostinggroup.code=sampletypes.salesgroup
      join [currencies] on [salesheader].[currencycode]=[currencies].[currabrev]
      where salesline.[documentno]='%s' and salesline.documenttype =13
      )",$journal,$period,$doc);
    
    return $sql;
    
}


Function CustomerStatemet($journal,$doc){
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
              salesheader.[postingdate]
             ,salesheader.documentno
             ,salesheader.documenttype
             ,salesheader.customercode
             ,0-sum(salesline.invoiceamount)
             ,'%s'
             ,'".$_POST['DimensionOne']."'
             ,'".$_POST['DimensionTwo']."' 
             ,salesheader.[currencycode]    
             from salesheader
             join salesline  on [salesline].[documentno] = [salesheader].[documentno] 
           where salesline.[documentno]='%s' and salesline.documenttype =13
           Group by 
             salesheader.[postingdate]
             ,salesheader.documentno
             ,salesheader.documenttype
             ,salesheader.customercode
             ,salesheader.[currencycode]
             ) ",
            $journal,$doc);
    
    return $sql;
}






?>