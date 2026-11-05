<?php

$_SESSION['DocumentNo']=$_POST['documentno'] ;
$_SESSION['invoicerrors']=0;
$_SESSION['somedetails']=0;

foreach ($_POST['subunits'] as $key => $value) {
       
    $location = $_POST['location'][$key];
    $stcode = $_POST['code'][$key];
    $qty = $_POST['subunits'][$key]  ;
    $ordered = $_POST['Quantity_toinvoice'][$key] ;
    
    $sqlstmt="SELECT sum((fulqty * stockmaster.partperunit) + loosqty) from stockledger
    join stockmaster on stockledger.itemcode=stockmaster.itemcode  
    where stockledger.itemcode='".$stcode."' and stockledger.store='".$location."'";

    $ResultIndex = DB_query($sqlstmt,$db);
    $checkbalance = DB_fetch_row($ResultIndex);
    $balance = $checkbalance[0];
    
    if($qty>0){
        if(($balance + ($qty * -1))<0){
           $_SESSION['invoicerrors']++;
        }
    }
    
    if($ordered < 0){
        $_SESSION['somedetails']++;
    } 
    
}


if($_SESSION['somedetails']>0){
    prnMsg('You cannot dispatch more than was ordered','warn');
}

if($_SESSION['invoicerrors']>0){
   prnMsg('You do not have enough stock units to invoice','warn');
}

if($_SESSION['DocumentPicking']==true){
     prnMsg('You cannot post this document more than once','warn');
  }

if($_SESSION['somedetails']==0 
        AND $_SESSION['DocumentPicking']==false 
        and $_SESSION['invoicerrors']==0){
                    
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
    $INVOICENO       = GetNextTransNo(19,$db);
    $journal         = GetNextTransNo(0,$db);
    $sql=array();
    
    
    foreach ($_POST['subunits'] as $key => $value) {
             if($value>0){

               $location = $_POST['location'][$key];
               $stcode = $_POST['code'][$key];
               $qty = $_POST['subunits'][$key];       
               $emptyunits = $_POST['emptyunits'][$key];
               $emptycode = $_POST['emptycode'][$key];
               $salesoderno= $_SESSION['DocumentNo'];
            
               $sql[]= sprintf("Update [SalesLine] set
                    [Qunatity_delivered]= IFNULL([Qunatity_delivered],0)+ %f,
                    [Quantity_toinvoice]= [Quantity]-[Qunatity_delivered],
                    [containersunits]=IFNULL([containersunits],0)+ %f
                    where [documentno]='%s' and [documenttype]='%s' 
                    and [entryno]=%s",$qty,$emptyunits,$salesoderno,1,$key);
                  
               
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
                       ,'19'
                       ,[SalesLine].[code]
                       ,[SalesLine].[documentno]
                       ,[SalesLine].[invoiceamount]
                       ,[SalesLine].[vatamount]
                       ,[SalesLine].[invoiceamount]
                       ,case [SalesLine].UOM when 'fulqty' then ".(0-$qty)." else 0 end as fulqty
                       ,case [SalesLine].UOM when 'loosqty' then ".(0-$qty)."  else 0 end as loosqty
                       ,[SalesLine].[UnitPrice]
                       ,'".$location."'
                       ,'".$journal."'
                       ,'".$PeriodNo."'
                       ,'".$INVOICENO ."' 
                     FROM [SalesLine] 
                     where documentno='".$salesoderno."' "
                 . " and code='".$stcode."' and [entryno]='".$key."')";
                
                 GetFIFO($journal,$PeriodNo,$salesoderno,$stcode,$qty,$key);  
                
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
               ,'19'
               ,[SalesLine].[containercode]
               ,[SalesLine].[documentno]
               ,[SalesLine].[invoiceamount]
               ,[SalesLine].[vatamount]
               ,[SalesLine].[invoiceamount]
               ,".(0-$emptyunits)."
               ,0,[SalesLine].[UnitPrice]
               ,'".$location."'
               ,'".$journal."'
               ,'".$PeriodNo."'
               ,'".$INVOICENO ."' 
            FROM [SalesLine]  where documentno='".$salesoderno."' "
         . " and [containercode]='".$emptycode."')";
                
 GetEmptiesFIFO($journal,$PeriodNo,$salesoderno,$emptycode,$emptyunits,$key);
                
                }
              
                
            }
    }
    
   
   
    foreach ($sql as $value) {
          $ResultIndex=DB_query($value,$db);
    }
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('There was an Error , Contact your administrator','warn');
       
    } else {
        DB_Txn_Commit($db);
        prnMsg('This picking list document '.$INVOICENO.' has been created  <a href="PDFPrintPickingList.php?No='.$INVOICENO.'">Print Pick List</a>');
         $_SESSION['DocumentPicking']=true;
         $_SESSION['DocumentNo']=true;
        unset($_POST);

    }
    
    

}


?>