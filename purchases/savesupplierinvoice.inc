<?php

$_SESSION['DocumentNo']=$_POST['LpoNo'] ;

if($_SESSION['DocumentPosted']==true){
    prnMsg('You cannot repost this invoice','warn');
}else{
       $sql="SELECT [itemcode],[customer],[phone],[email],[city],[country],[curr_cod],[supplierposting],[VATinclusive],[IsTaxed]
       FROM [creditors] join arpostinggroups on code=[supplierposting]  where itemcode='".$_POST['CustomerID']."'";
    
    $res = DB_query($sql, $db);
    $debtorsrow = DB_fetch_row($res);
    $customerposting = $debtorsrow[7];
    $VATinclusive    = $debtorsrow[8];
    $PeriodNo        = GetPeriod($_POST['posingdate'],$db,false);
    $journal         = GetNextTransNo(0,$db);
    $TransBuffer     = DB_Txn_Begin($db);
    $INVOICENO       = GetNextTransNo(20,$db);
    $sql=array();
    
    $sql[] ="insert into [purchaseheader] 
      ([documenttype]
      ,[documentno]
      ,[docdate]
      ,[postingdate]
      ,[vendorcode]
      ,[vendorname]
      ,[yourreference]
      ,[externaldocumentno]
      ,[postinggroup]
      ,[currencycode]
      ,[printed]
      ,[released]
      ,[status]
      ,[userid]
      ,[period]
      ,[vatinclusive]) 
      (select 
      20
      ,'".$INVOICENO."'
      ,'".FormatDateForSQL($_POST['date'])."'
       ,'".FormatDateForSQL($_POST['posingdate'])."'
       ,'".$_POST['CustomerID']."'
       ,'".$_POST['CustomerName']."'
      ,'".$_POST['reference']."'
      ,'".$_SESSION['DocumentNo']."'
      ,'".$customerposting."'
      ,'".$_POST['currencycode']."'
      ,0
      ,0
      ,1
      ,'".$_SESSION['UserID']."'
      ,'".$PeriodNo."'
      ,'".$VATinclusive ."'
      from [purchaseheader] 
      where documentno='".$_SESSION['DocumentNo']."') ";
    
    foreach ($_POST['subunits'] as $key => $value) {
       
    if($value>0){
        
        $location = $_POST['location'][$key];
        $stcode = $_POST['code'][$key];
        $qty = $_POST['subunits'][$key];        
        $salesprice = $_POST['salesprice'][$key];
        $netamount = $_POST['netamount'][$key];
        $vatamount = $_POST['vatamount'][$key];
        $grossamount = $_POST['grossamount'][$key];

  
           $sql[] =  sprintf("INSERT INTO [purchaseline]
           ([documenttype],[docdate],[documentno]
           ,[code],[description],[unitofmeasure]
           ,[Quantity],[UnitPrice],[vatamount]
           ,[invoiceamount],[vatrate]
           ,[inclusive],[locationcode],UOM) 
           (select '%s','%s' ,'%s' ,[code],[description],[unitofmeasure]
           ,[Qunatity_delivered],%f,%f,%f,[vatrate],[inclusive],'%s',[UOM]
           from purchaseline where [entryno]='".$key."')"
           ,20
           ,FormatDateForSQL($_POST['date'])
           ,$INVOICENO
           ,$salesprice
           ,$vatamount
           ,$grossamount
           ,$location);
    
                  
         
        
    }
    
    
  
    }
    
    $sql[]=PostCreditors($PeriodNo,$journal,$INVOICENO);
    $sql[]=VATPostGL($PeriodNo,$journal,$INVOICENO) ;  
    $sql[]=SupplierStatemet($journal,$INVOICENO);
    $sql[]=PostGL($PeriodNo,$journal,$INVOICENO);
 
    foreach ($sql as $value) {
       $ResultIndex=DB_query($value,$db);
    }
    
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('There was an Error Posting the invoice, Contact your administrator','warn');
    }else{
        DB_Txn_Commit($db);
        prnMsg('This Purchase has successfully been posted in the General Ledger');
        DB_query("Update purchaseheader set [released]=2 where documentno='".$_SESSION['DocumentNo']."'",$db);
 
        $_SESSION['DocumentPosted']=true;
        unset($_SESSION['DocumentNo']);
        unset($_POST);
    }
    
}

function PostCreditors($period,$journal,$doc){
   $SQL= sprintf("Insert into [creditorsledger]
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
      purchaseheader.[postingdate]
      ,(rtrim(purchaseline.[description]) +' X '+CAST(purchaseline.[Quantity] as nvarchar(10)))+' '+
       (case [purchaseline].UOM when 'fulqty' 
      then (select rtrim([unit].[descrip]) from [unit] join stockmaster on stockmaster.units=[unit].code  and stockmaster.itemcode=purchaseline.code) 
	  else (select rtrim([unit].[descrip]) from [unit] join stockmaster on stockmaster.subunits=[unit].code  and stockmaster.itemcode=purchaseline.code)
	  end)  as narration,'I'
      ,purchaseheader.[externaldocumentno]
      ,purchaseheader.[vendorcode]
      ,SUM(purchaseline.invoiceamount *-1) as Value
      ,0.00
      ,purchaseheader.[currencycode]
      ,'I'
      ,'%s'
      ,'I'
      ,SUM(purchaseline.vatamount *-1) as VAT
      ,purchaseheader.[documenttype]
      ,purchaseheader.[postinggroup]
      ,'%s'
  FROM [purchaseheader] join purchaseline 
        on purchaseheader.documentno=purchaseline.documentno 
        and purchaseheader.documenttype=purchaseline.documenttype 
        where purchaseheader.documentno='%s' 
        group by
       purchaseheader.[documenttype]
      ,purchaseheader.[documentno]
      ,purchaseheader.[postingdate]
      ,purchaseheader.[vendorcode]
      ,purchaseheader.[externaldocumentno]
      ,purchaseheader.[postinggroup]
      ,purchaseheader.[currencycode]
      ,purchaseline.[Quantity]
      ,purchaseline.[description]
      ,purchaseline.[code]
      ,purchaseline.[UOM])", $journal,$period,$doc);
   
   return $SQL;
}

function PostGL($period,$journal,$doc){
    $sql=SPRINTF("Insert into [generalledger]
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
      ,[suppliercode]
      ,[narration]
      ,[dimension]
      ,[dimension2])
      (select
       '%s'
      ,purchaseheader.[postingdate]
      ,'%s'
      ,purchaseheader.[externaldocumentno]
      ,purchaseheader.documenttype
      ,[inventorypostinggroup].[defaultgl_purch]
      ,[arpostinggroups].[creditorsaccount]
      ,[inventorypostinggroup].[defaultgl_vat]
      ,purchaseline.invoiceamount
      ,IFNULL(purchaseline.vatamount,0) as vatamount
      ,purchaseheader.[currencycode]
      ,[currencies].[rate]
      ,purchaseheader.[vendorcode]
      ,(rtrim(purchaseline.[description]) +'X'+CAST(purchaseline.[Quantity] as nvarchar(10)))+
	  (case [purchaseline].UOM when 'fulqty' 
          then (select rtrim([unit].[descrip]) from [unit] join stockmaster on stockmaster.units=[unit].code  and stockmaster.itemcode=purchaseline.code) 
	  else (select rtrim([unit].[descrip]) from [unit] join stockmaster on stockmaster.subunits=[unit].code  and stockmaster.itemcode=purchaseline.code)
	  end) as narration
      ,'".trim($_POST['DimensionOne'])."'
      ,'".trim($_POST['DimensionTwo'])."'    
      from purchaseheader 
      join [arpostinggroups] on [purchaseheader].[postinggroup]=[arpostinggroups].[code]
      join purchaseline on [purchaseline].[documentno] = [purchaseheader].[documentno] 
      join [stockmaster] on [purchaseline].[code]=[stockmaster].itemcode
      join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
      join [currencies] on [purchaseheader].[currencycode]=[currencies].[currabrev]
      where purchaseheader.documentno='%s'
      )",$journal,$period,$doc);
    
    return $sql;
    
}

function VATPostGL($period,$journal,$doc){
    $sql=SPRINTF("Insert into [generalledger]
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
      ,[suppliercode]
      ,[narration])
      (select
       '%s'
      ,purchaseheader.[postingdate]
      ,'%s'
      ,purchaseheader.[externaldocumentno]
      ,purchaseheader.documenttype
      ,[inventorypostinggroup].[defaultgl_vat]
      ,[inventorypostinggroup].[defaultgl_purch]
      ,''
      ,purchaseline.vatamount
      ,0
      ,purchaseheader.[currencycode]
      ,[currencies].[rate]
      ,[inventorypostinggroup].[defaultgl_vat]
      ,(rtrim(purchaseline.[description]) +' X '+CAST(purchaseline.[Quantity] as nvarchar(10)))+' '+
	  (case [purchaseline].UOM when 'fulqty' 
      then (select rtrim([unit].[descrip]) from [unit] join stockmaster on stockmaster.units=[unit].code  and stockmaster.itemcode=purchaseline.code) 
	  else (select rtrim([unit].[descrip]) from [unit] join stockmaster on stockmaster.subunits=[unit].code  and stockmaster.itemcode=purchaseline.code)
	  end)  as narration
      from purchaseheader 
      join [arpostinggroups] on [purchaseheader].[postinggroup]=[arpostinggroups].[code]
      join purchaseline on [purchaseline].[documentno] = [purchaseheader].[documentno] 
      join [stockmaster] on [purchaseline].[code]=[stockmaster].itemcode
      join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
      join [currencies] on [purchaseheader].[currencycode]=[currencies].[currabrev]
      where purchaseheader.documentno='%s'
      )",$journal,$period,$doc);
    
    return $sql;
    
}

Function SupplierStatemet($journal,$doc){
    $sql=sprintf("INSERT INTO [supplierstatement]
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
              purchaseheader.[docdate]
             ,purchaseheader.[externaldocumentno]
             ,purchaseheader.documenttype
             ,purchaseheader.[vendorcode]
             ,sum(purchaseline.invoiceamount * -1)
             ,'%s'
             ,'".$_POST['DimensionOne']."'
             ,'".$_POST['DimensionTwo']."'  
             ,purchaseheader.[currencycode]   
             From purchaseheader
             join purchaseline on [purchaseline].[documentno] = [purchaseheader].[documentno] 
             where purchaseheader.documentno='%s' 
             Group By purchaseheader.[docdate]
             ,purchaseheader.documentno
             ,purchaseheader.documenttype
             ,purchaseheader.[vendorcode]
             ,purchaseheader.[currencycode],
             purchaseheader.[externaldocumentno] 
             ) ", $journal,$doc);
    
    return $sql;
}

?>