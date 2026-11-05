<?php

$Perdno  = GetPeriod($_POST['fromdate'], $db);

 $SQL =db_GetBalance_withfilter($Perdno,$_POST['Accountcode']);
 $REsults = DB_query($SQL,$db);
$row     = DB_fetch_row($REsults);
$openbal = ($row[0] - $row[1]);

$SQLDrilldown ="SELECT 
    [journalno],
    [Docdate] ,
    [period] ,
    [DocumentNo],
    [DocumentType],
    [accountcode] ,
    [balaccountcode],
    (case 
    if([accountcode]='".$_POST['Accountcode']."',1,0) 
    when 1 then  ([amount] * [ExchangeRate] )
    else (0-[amount]) * [ExchangeRate]  end) as AMOUNT ,
      [currencycode] ,
      [ExchangeRate],
      [cutomercode] ,
      [suppliercode] ,
      [bankcode],
      [reconcilled] ,
      [narration] ,
      [ExchangeRateDiff],
      [VATaccountcode],
      [VATamount] ,
      [dimension] ,
      [dimension2],
      ".$balance_income." as [balance_income],
      '".$openbal."' as balcfwd    
    from  [Generalledger] 
    where (Generalledger.accountcode='".$_POST['Accountcode']."' "
        . " or  Generalledger.balaccountcode='".$_POST['Accountcode']."')"
        . " and Generalledger.Docdate between '".FormatDateForSQL($_POST['fromdate'])."' "
        . " and '".FormatDateForSQL($_POST['Todate'])."'"
        . " order by [Docdate]"
     

?>