<?php

$_SESSION['DocumentNo']=$_POST['documentno'] ;
$_SESSION['invoicerrors']=0;
$_SESSION['somedetails']=0;

foreach ($_POST['subunits'] as $key => $value) {
       
    $location = $_POST['location'][$key];
    $stcode = $_POST['code'][$key];
    $qty = $_POST['subunits'][$key]  ;
    $ordered = $_POST['Quantity_toinvoice'][$key] ;
    
    if($ordered < 0){
        $_SESSION['somedetails']++;
    } 
    
}


if($_SESSION['somedetails']>0){
    prnMsg('You cannot dispatch more than was ordered','warn');
    unset($_SESSION['somedetails']);
}


if($_SESSION['DocumentPicking']==true){
     prnMsg('You cannot post this document more than once','warn');
  }

if($_SESSION['somedetails']==0 AND $_SESSION['DocumentPicking']==false ){
                    
    $ResultIndex = DB_query('Select sysdate() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['posingdate'] = ConvertSQLDate($rowdate[0]);
    
    
    $sqldebtors=DB_query("SELECT [itemcode],[creditlimit],[customer],[phone],
    [email],[city],[country],[curr_cod],[customerposting],[salesman],[VATinclusive]
    FROM [debtors] join postinggroups on code=[customerposting] 
    where itemcode='".$_POST['CustomerID']."'", $db);
    
    $debtorsrow = DB_fetch_row($sqldebtors);

    $customerposting = $debtorsrow[8];
    $VATinclusive    = $debtorsrow[10];
    $PeriodNo        = GetPeriod($_POST['posingdate'],$db,false);
    $TransBuffer     = DB_Txn_Begin($db);
    $INVOICENO       = GetNextTransNo(11,$db);
    $journal         = GetNextTransNo(0,$db);
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
      ,[vatinclusive]) 
      (select 11
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
      from [SalesHeader] 
      where documentno='".$_SESSION['DocumentNo']."') ";
   
    foreach ($_POST['subunits'] as $key => $value) {
    if($value>0){

               $location = $_POST['location'][$key];
               $stcode = $_POST['code'][$key];
               $qty = $_POST['subunits'][$key];   
               $qty_ordered=$_POST['ordered'][$key];  
               $emptyunits = $_POST['emptyunits'][$key];
               $emptycode = $_POST['emptycode'][$key];
               $salesoderno = $_SESSION['DocumentNo'];
                
                $sql[] =sprintf("Update [SalesLine] set
                [Quantity]=%f,[Qunatity_delivered] = IFNULL([Qunatity_delivered],0)- %f,
                [Quantity_toinvoice] = [Quantity]-[Qunatity_delivered],
                [containersunits] = IFNULL([containersunits],0)+ %f
                where [documentno]='%s' and [documenttype]='%s' 
                and [entryno]=%s", $qty_ordered,$qty,$emptyunits,$salesoderno,10,$key);
                    
                    
                $sql[] = sprintf("INSERT INTO [SalesLine] ([documenttype],[docdate],[documentno],[code],[description],[unitofmeasure],[Quantity],[UnitPrice],[vatamount],[invoiceamount],[vatrate],[inclusive],[locationcode],[UOM]) 
                  (select '%s','%s','%s',[code],[description],[unitofmeasure],%f,%f,%f,%f,[vatrate],[inclusive],'%s',[UOM] from SalesLine where [entryno]='%s')"
                   ,11,FormatDateForSQL($_POST['date']),$INVOICENO,$qty,$salesprice,$vatamount,$grossamount ,$location ,$key);

               
               
               
                $sql[]="INSERT [stockledger]
                    ([date]
                    ,[stname]
                    ,[doctyp]
                    ,[itemcode]
                    ,[invref]
                    ,[netamt]
                    ,[vat]
                    ,[amount]
                    ,[fulqty]
                    ,[loosqty]
                    ,[price]
                    ,[store]
                    ,[journal]
                    ,[period]
                    ,jobcard)
                    (SELECT 
                        [SalesLine].[docdate]
                        ,substring([SalesLine].[description],0,30)
                       ,'11'
                       ,[SalesLine].[code]
                       ,[SalesLine].[documentno]
                       ,[SalesLine].[invoiceamount]
                       ,[SalesLine].[vatamount]
                       ,[SalesLine].[invoiceamount]
                       ,case [SalesLine].UOM when 'fulqty' then ".($qty)." else 0 end as fulqty
                       ,case [SalesLine].UOM when 'loosqty' then ".($qty)."  else 0 end as loosqty
                       ,[SalesLine].[UnitPrice]
                       ,'".$location."'
                       ,'".$journal."'
                       ,'".$PeriodNo."'
                       ,'".$INVOICENO ."' 
            FROM [SalesLine] where [entryno]='".$key."')";
                
               
    
                        $sql[]=SPRINTF("Insert into [Generalledger]
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
                                        ,[inventorypostinggroup].[balancesheet]
                                        ,[inventorypostinggroup].[stockvariance]
                                         ,''
                                        ,[stockmaster].[averagestock] * ".$qty."
                                        ,0
                                        ,SalesHeader.[currencycode]
                                        ,[currencies].[rate]
                                        ,SalesHeader.[customercode]
                                        ,(rtrim(SalesLine.[description]) +' X ".$qty." '+
                                            (case [SalesLine].UOM when 'fulqty' 
                                        then (select rtrim([unit].[descrip]) from [unit] join stockmaster on stockmaster.units=[unit].code 
                                        and stockmaster.itemcode=SalesLine.code) 
                                            else (select rtrim([unit].[descrip]) from [unit] join stockmaster on stockmaster.subunits=[unit].code 
                                            and stockmaster.itemcode=SalesLine.code)
                                            end)) as narration
                                        from SalesHeader 
                                        join SalesLine on [SalesLine].[documentno] = [SalesHeader].[documentno] 
                                        join [stockmaster] on [SalesLine].[code]=[stockmaster].itemcode
                                        join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
                                        join [currencies] on [SalesHeader].[currencycode]=[currencies].[currabrev]
                                        where [SalesLine].[entryno]='%s'
                                        )", $journal,$PeriodNo,$key);
    
                                             
                             
                
     if($emptyunits>0){ 
                
                $sql[]="INSERT [stockledger]
                    ([date]
                    ,[stname]
                    ,[doctyp]
                    ,[itemcode]
                    ,[invref]
                    ,[netamt]
                    ,[vat]
                    ,[amount]
                    ,[fulqty]
                    ,[loosqty]
                    ,[price]
                    ,[store]
                    ,[journal]
                    ,[period]
                    ,jobcard)
                    (SELECT 
                        [SalesLine].[docdate]
                       ,substring([SalesLine].[description],0,30)
                       ,'11'
                       ,[SalesLine].[containercode]
                       ,[SalesLine].[documentno]
                       ,[SalesLine].[invoiceamount]
                       ,[SalesLine].[vatamount]
                       ,[SalesLine].[invoiceamount]
                       ,".($emptyunits)."
                       ,0,[SalesLine].[UnitPrice]
                       ,'".$location."'
                       ,'".$journal."'
                       ,'".$PeriodNo."'
                       ,'".$INVOICENO ."' 
                FROM [SalesLine] 
                where [entryno]='".$key."' and code='".$emptycode."')";
                
                $sql[]=SPRINTF("Insert into [Generalledger]
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
                    ,[inventorypostinggroup].[balancesheet]
                    ,[inventorypostinggroup].[stockvariance]
                    ,''
                    ,([stockmaster].[averagestock] * ".$emptyunits.")
                    ,0
                    ,SalesHeader.[currencycode]
                    ,[currencies].[rate]
                    ,SalesHeader.[customercode]
                    ,(rtrim(stockmaster.[descrip]) +' X ".$emptyunits." '+
                     (select rtrim([unit].[descrip]) from [unit] join stockmaster on stockmaster.units=[unit].code 
                     and stockmaster.itemcode=[SalesLine].[containercode]))  as narration
                    from SalesHeader 
                    join SalesLine on [SalesLine].[documentno]=[SalesHeader].[documentno] 
                    join [stockmaster] on [SalesLine].[containercode]=[stockmaster].itemcode
                    join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
                    join [currencies] on [SalesHeader].[currencycode]=[currencies].[currabrev]
                    where [SalesLine].[entryno]='%s' and [SalesLine].[containercode]='".$emptycode."'
                    )", $journal,$PeriodNo,$key);
                }
              
            }
    }
    
   
   
    foreach ($sql as $value) {
       $ResultIndex=DB_query($value,$db);
    }
    
    
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('There was an Error , Contact your administrator','warn');
            
    }else{
        DB_Txn_Commit($db);
        prnMsg('This Returns Inwards document '.$INVOICENO.' has been created  <a href="PDFPrintReturnsList.php?No='.$INVOICENO.'">Print Returns</a>');
         $_SESSION['DocumentPicking']=true;
         $_SESSION['DocumentNo']=true;
        unset($_POST);

    }
    
    

}
?>