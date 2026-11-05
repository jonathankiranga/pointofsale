<?php

if(isset($_POST)){
$_SESSION['__FormQuote']=$_POST;
}


if($_SESSION['locked'] == true){
    
$sqldebtors=DB_query("SELECT [itemcode],[creditlimit],[customer]
,[phone] ,[email] ,[city] ,[country],[curr_cod],[customerposting],[salesman],[VATinclusive],[IsTaxed]
FROM [debtors] join postinggroups on code=[customerposting]
where itemcode='".$_POST['CustomerID']."'", $db);

$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[8];
$VATinclusive    =(bool) $debtorsrow[10];
$IsTaxed         =(bool) $debtorsrow[11];
$PeriodNo        = GetPeriod($_SESSION['__FormQuote']['date'],$db,false);

$transstart = DB_Txn_Begin($db);
$SQLArray=array();

$SQLArray[]="INSERT INTO [SalesHeader]
           ([documenttype],[documentno],[docdate]
           ,[customercode],[customername],[yourreference] ,[externaldocumentno],[postinggroup]
           ,[currencycode],[salespersoncode],[status],[userid] ,[period],[vatinclusive]
           ,paymentterms)
     VALUES
           ('54'
           ,'".$_SESSION['__FormQuote']['documentno']."'
           ,'".FormatDateForSQL($_SESSION['__FormQuote']['date'])."'
           ,'".$_SESSION['__FormQuote']['CustomerID']."'
           ,'".$_SESSION['__FormQuote']['CustomerName']."'
           ,'".$_SESSION['__FormQuote']['Bank_Code2']."'
           ,'".$_SESSION['__FormQuote']['Bank_Code']."'
           ,'".$customerposting ."'
           ,'".$_SESSION['__FormQuote']['currencycode']."'
           ,'".$_SESSION['__FormQuote']['salespersoncode']."'
           ,'1'
           ,'".$_SESSION['UserID']."'
           ,'".$PeriodNo."'
           ,'".$VATinclusive."'
           ,'".$_POST['terms']."')" ;

 
    
for($index = 1; $index <= $_SESSION['__FormQuote']['numberoflines']; $index++) {
    $salesprice = 0;
  
    $SampleType = trim($_SESSION['__FormQuote']["sampletype"][$index]);
    $TestingSelection = $_SESSION['__FormQuote']["Pricing"][$index];
    
    $sampletypeName = trim($_SESSION['_SampleTypes'][$SampleType]['sampletypeName']);
    $salesgroup     = trim($_SESSION['_SampleTypes'][$SampleType]['salesgroup']);
    $rate           = (float) $_SESSION['_inventorypostinggroup'][$salesgroup]['vat'];
    $rate           = ($IsTaxed==false)?0: $rate;
    
    $itemlist = "";
    foreach ($_SESSION['__FormQuote']["TEST"][$index] as $parameterid => $parametercode){
        $salesprice += $_SESSION['__FormQuote']["AMOUNT"][$index][$parametercode];
        
        $itemlist .= $_SESSION['_LaboratoryStandards'][$SampleType][$parametercode]['Parameter'].' ,';
    } 
     
   $baseamount=$salesprice;
      if($VATinclusive==true){
        $netamount = ($baseamount ) * (1- round(($rate/(100+$rate)),2));
        $vatamount = ($baseamount ) * round(($rate/(100+$rate)),2);
        $grossamount = ($baseamount ) ;
    }else{
        $vatamount  = ($baseamount ) * ($rate/100);
        $grossamount = $baseamount + $vatamount ;
        $netamount = $baseamount;
    }
    
    $SQLArray[] =  sprintf("INSERT INTO [SalesLine]
    ([documenttype],[docdate],[documentno]
    ,[code],[description],[unitofmeasure],[Quantity],[UnitPrice],[vatamount],[invoiceamount],[vatrate],[inclusive],UOM,Itemlist)
    VALUES ('%s','%s' ,'%s','%s','%s' ,'%s' ,%f ,%f ,%f ,%f ,%f,%f ,'%s','%s')"
     ,"54",FormatDateForSQL($_SESSION['__FormQuote']['date']),$_SESSION['__FormQuote']['documentno']
    ,$SampleType,$sampletypeName,'u1',1,$salesprice,$vatamount,$grossamount,$rate,$VATinclusive,'fulqty',$itemlist);
    
  }

 
foreach ($SQLArray as $sql) {
     DB_query($sql,$db);
}

if(DB_error_no($db)==0){
    DB_Txn_Commit($db);
     $_SESSION['locked'] = false;
     $_SESSION['savedsuccessfuly']=true;
      
}else{
    DB_Txn_Rollback($db);
}


} else {
    prnMsg('Error submitting this page','info');
}

 