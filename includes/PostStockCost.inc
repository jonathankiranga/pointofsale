<?php

$_SESSION['PostRow'] = array();
$_SESSION['records'] = array();
    
class ExchangeRates {
    var $FunctionalCurrency;
    Var $FromRate;
    var $Torate;
        
    function __construct() {
       $this->FunctionalCurrency = $_SESSION['CompanyRecord']['currencydefault'];
     }
    
    function get($fromcurrency){
        $this->FromRate =$this->ExtractRate($fromcurrency);
        $this->Torate =$this->ExtractRate($this->FunctionalCurrency);
        return ($this->FromRate/$this->Torate);
    }
    
    function ExtractRate($currbrieve){
        global $db;
        
        $SQLOldRate = "SELECT rate FROM currencies  WHERE currabrev = '" .$currbrieve. "'";
        $ResultOldRate = DB_query($SQLOldRate, $db);
        $myrow = DB_fetch_row($ResultOldRate);
        $OldRate = $myrow[0];
                
        return $OldRate;
    }
}

function update_tank_balance(){
      global $db;
      
        $ResultIndex=DB_query("select tankname FROM productionunit",$db);
        while($row=DB_fetch_array($ResultIndex)){
            $mytanks[]=$row;
        }

        if(is_array($mytanks)){
            foreach ($mytanks as $key => $row) {
                DB_System(sprintf("update productionunit 
               set units= (select sum(tanktrans.units * (case tanktrans.uom 
                  when 'fulqty' then stockmaster.partperunit else 1 end)) from tanktrans 
                   join stockmaster on tanktrans.itemcode=stockmaster.itemcode 
                  where tanktrans.tankname= '%s') 
               where productionunit.tankname= '%s' ",$row['tankname'] ,$row['tankname']),$db);


            }
        }
  
  
}

Function AverageCost($itemcode){
      Global $db; 
   
   $SQL ="Select  
        ([purchaseline].[UnitPrice] * currencies.rate) as UnitPrice,
        [purchaseheader].[documentno],
        [purchaseheader].[docdate], 
        [purchaseheader].[currencycode],
        [purchaseline].[code], 
        [purchaseline].[PartPerUnit]
     from purchaseheader 
     join purchaseline on [purchaseline].[documentno] = [purchaseheader].[documentno]
     join currencies on currencies.currabrev = [purchaseheader].[currencycode]
     where [purchaseline].[code]='". trim($itemcode) ."' 
     order by [purchaseheader].[docdate] DESC limit 1";
   
  $ResultIndex=DB_query($SQL, $db);
  $rows = DB_fetch_row($ResultIndex);
  return ($rows[5]==1)?($rows[0]/$rows[5]):$rows[0];
}

function FindJournal($invref){
    Global $db;
    $SQL="SELECT [journal] FROM [stockledger] where invref='".trim($invref)."'";
    $ResultIndex=DB_query($SQL, $db);
   $rows = DB_fetch_row($ResultIndex);
   
    return $rows[0];
}

function PostDebitRegister($Quantity,$key,$journal,$PeriodNo){
  global $db;
  
    $SQL ="select  
        purchaseheader.[docdate], 
        [purchaseheader].[currencycode],
        [purchaseline].[code], 
        [purchaseline].[UnitPrice],
        [purchaseline].[PartPerUnit]
     from purchaseheader 
     join purchaseline on [purchaseline].[documentno] = [purchaseheader].[documentno] 
     where [purchaseline].[entryno]=".$key;
    
    $RESULTS = DB_query($SQL,$db);
    $rows = DB_fetch_row($RESULTS);
    
    $ExchRates = New ExchangeRates();
    $R = $ExchRates->get($rows[1]); 
    $Price = $rows[3];
    $cost = ($Price  * $R) ;
    $itemcode = $rows[2];
    $PartPerUnit = $rows[4];
    DB_free_result($RESULTS);
    
    $SQL ="Select [averagestock] from stockmaster where itemcode='".$itemcode."'";
    $r = DB_query($SQL,$db);
    $row = DB_fetch_row($ResultIndex);
    $averagestock = $row[0];
    $quantity= (($PartPerUnit>1)?($PartPerUnit * $Quantity):$Quantity);
   
	 
    $SQL= "Insert into [generalledger]
    ([journalno] ,[Docdate],[period],[DocumentNo],[DocumentType] ,[accountcode]
    ,[balaccountcode],[VATaccountcode],[amount],[VATamount],[currencycode],[ExchangeRate],[cutomercode],[narration])
    (select '".$journal."',purchaseheader.[docdate],'".$PeriodNo."',purchaseheader.documentno
     ,purchaseheader.documenttype,[inventorypostinggroup].[stockvariance] ,[inventorypostinggroup].[balancesheet] ,''
     ,(".$averagestock * $quantity." * [purchaseline].[PartPerUnit]),0,'".$_SESSION['CompanyRecord']['currencydefault']."',1,purchaseheader.[vendorcode]
     ,concat(purchaseline.[description],' X ".$quantity."' ,purchaseline.[unitofmeasure]) as narration
     from purchaseheader 
     join purchaseline on [purchaseline].[documentno] = [purchaseheader].[documentno] 
     join [stockmaster] on [purchaseline].[code]=[stockmaster].itemcode
     join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
     join [currencies] on [purchaseheader].[currencycode]=[currencies].[currabrev]
     where [purchaseline].[entryno]=".$key.")";

     $r=DB_System($SQL,$db);
     
     GetStockRegister();
}

Function GetConsumables($journal,$PeriodNo,$salesoderno,$stcode,$qty,$key){
 global $db;
 
 $SQL ="select  
        [salesline].PartPerUnit,
        [salesheader].[documentno] 
     from salesheader
     join salesline on [salesline].[documentno] = [salesheader].[documentno] 
     where [salesline].[entryno]=".$key;
    
    $RESULTS = DB_query($SQL,$db);
    $rows = DB_fetch_row($RESULTS);
    $PartPerUnit = trim($rows[0]);
    DB_free_result($RESULTS);
    
     
    $SQL ="select 
        stockregister.[cost],
        stockregister.[StockBalance],
        stockregister.[rowid] 
        from [stockregister] 
        where [stockregister].[itemcode]='".$containercode."' and [StockBalance]>0 
        order by stockregister.rowid asc";
    $RESULTS = DB_query($SQL,$db);
    while($rows = DB_fetch_row($RESULTS)){
        $stockregister[]=$rows;
    }
     DB_free_result($RESULTS);


     if(is_array($stockregister)){
         foreach ($stockregister as $key => $Register) {
             $quantity = (($PartPerUnit>1)?($PartPerUnit* $qty):$qty);
             if($quantity>$Register['stockbalance']) {
               
                   DB_System(sprintf("Update [stockregister] set [StockOut]=([StockOut] + %f) where rowid='%s'",
                   $Register['stockbalance'], $Register['rowid']),$db);
                   $usequantity = $Register['stockbalance'];
                   $quantity    = ($quantity-$usequantity);
            
                } else{
                     DB_System(sprintf("Update [stockregister] set [StockOut]=([StockOut] + %f) where rowid='%s'",
                     $quantity, $Register['rowid']),$db);
                     $usequantity = $quantity;
                     $quantity    = 0;
                }
            
            $usequantity=(($PartPerUnit==1)?($usequantity / $PartPerUnit):$usequantity);
            $usecost=(($PartPerUnit>1)?($Register['cost'] * $PartPerUnit):$Register['cost']);
            if($usequantity>0){      
               
              $SQL="Insert into [generalledger] ([journalno],[Docdate],[period],[DocumentNo],[DocumentType],[accountcode],[balaccountcode]
                 ,[VATaccountcode] ,[amount],[VATamount],[currencycode],[ExchangeRate] ,[cutomercode],[narration])
                  (select '".$journal."',salesheader.[docdate],'".$PeriodNo."' , salesheader.documentno,salesheader.documenttype,
                 [inventorypostinggroup].[productionexpense],[inventorypostinggroup].[balancesheet],''
                 ,".$usecost * $usequantity.", 0 ,'".$_SESSION['CompanyRecord']['currencydefault']."', 1 ,
                 salesheader.[customercode] ,
                 concat(salesline.[description],'@ ".$usecost." X ".$usequantity."' ,[salesline].[unitofmeasure]) as narration
               from salesheader 
               join salesline on [salesline].[documentno] = [salesheader].[documentno] 
               join [stockmaster] on [salesline].[code]=[stockmaster].itemcode
               join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
               join [currencies] on [salesheader].[currencycode]=[currencies].[currabrev] where salesheader.documentno='".$salesoderno."' 
               and [salesline].[code]='".$stcode."' and [salesline].[entryno]='".$key."')";
              
            }
    
        }
     
    }
 $RESULTS = DB_System($SQL,$db); 
   GetStockRegister();
}

function PostStockRegister($Quantity,$key,$journal,$PeriodNo){
    global $db;
    
    $SQL ="Select  
        [purchaseheader].[documentno],
        [purchaseheader].[docdate], 
        [purchaseheader].[currencycode],
        [purchaseline].[code], 
        ([purchaseline].[UnitPrice] * currencies.rate) as UnitPrice,
        [purchaseline].[PartPerUnit],
        ([purchaseline].[invoiceamount] * currencies.rate) as invoiceamount,
        ([purchaseheader].[freight] * currencies.rate) as freight,
        [purchaseline].[Quantity],
        ([purchaseline].[PartPerUnit]*[purchaseline].[Quantity]) as Totunits,
        ifnull([purchaseline].[discount],0) * currencies.rate as discount
     from purchaseheader 
     join purchaseline on [purchaseline].[documentno] = [purchaseheader].[documentno]
     join currencies on currencies.currabrev = [purchaseheader].[currencycode]
     and  [purchaseline].[entryno]=".$key;
    
        $RESULTS  = DB_query($SQL,$db);
        $rows     = DB_fetch_row($RESULTS);

        $GRN      = (string)trim($rows[0]);
        $Price    = (float)$rows[4]-(float)$rows[10];
        $itemcode = (string)trim($rows[3]);
        $partperunit = (int)$rows[5];
        $freight  = (float)$rows[7];
        $Unitstodevide = (int)$rows[9];

        DB_free_result($RESULTS);

        $AMOUNT   = $Price * $Quantity   ;
       If(mb_strlen($itemcode)>0){

            if($freight>0){
                $freight=($freight/$Unitstodevide);
                $Price += $freight; 
            }

            $SQL=sprintf("INSERT INTO [stockregister] ([itemcode],[StockIn],[cost],[StockOut],[journal],[GRN],PartPerUnit) 
                VALUES  ('%s','%s','%s',0,'%s','%s','%s')",$itemcode,$Quantity,$Price,$journal,$GRN,$partperunit);
             $RESULTS  = DB_System($SQL,$db);

            $SQL= " Insert into [generalledger] ([journalno],[Docdate],[period],[DocumentNo],[DocumentType] ,[accountcode]
                    ,[balaccountcode],[VATaccountcode],[amount],[VATamount],[currencycode],[ExchangeRate],[cutomercode],[narration])
                    (select  '".$journal."',purchaseheader.[docdate],'".$PeriodNo."',purchaseheader.documentno,purchaseheader.documenttype,[inventorypostinggroup].[balancesheet] ,[inventorypostinggroup].[stockvariance] ,''
                     ,(".$AMOUNT."),0,'".$_SESSION['CompanyRecord']['currencydefault']."',1,purchaseheader.[vendorcode]
                     ,concat(purchaseline.[description],' X', ".$Quantity.",purchaseline.[unitofmeasure])  as narration
                     from purchaseheader 
                     join purchaseline on [purchaseline].[documentno] = [purchaseheader].[documentno] 
                     join [stockmaster] on [purchaseline].[code]=[stockmaster].itemcode
                     join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
                     join [currencies] on [purchaseheader].[currencycode]=[currencies].[currabrev]
                     where [purchaseline].[entryno]='".$key."')";
            $RESULTS  = DB_System($SQL,$db);
    }
            
     GetStockRegister($itemcode);
}

Function GetStockRegister($itemcode){
global $db;
              
$SQL=Sprintf("Update [stockmaster] set 
[averagestock]=(select   cost from [stockregister] where [itemcode]='%s' and ([StockBalance]>0 limit 1) 
order by rowid asc) where itemcode='%s'",$itemcode,$itemcode);
 $RESULTS  = DB_System($SQL,$db);
        
}

Function GetFIFO($journal,$PeriodNo,$salesoderno,$stcode,$qty,$key){
    global $db;
    
    
 $SQL ="select  
        [salesline].[unitofmeasure],
        [salesline].PartPerUnit,
        [salesheader].[documentno] 
     from salesheader
     join salesline on [salesline].[documentno] = [salesheader].[documentno] 
     where [salesline].[entryno]=".$key;
    
    $RESULTS = DB_query($SQL,$db);
    $rows = DB_fetch_row($RESULTS);
    $unitofmeasure = trim($rows[0]);
    $PartPerUnit = trim($rows[1]);
    DB_free_result($RESULTS);
    
    
    $SQL ="select 
        stockregister.[cost],
        stockregister.[StockBalance],
        stockregister.[rowid] 
        from [stockregister] 
        where [stockregister].[itemcode]='".$stcode."' and [StockBalance]>0 
         order by stockregister.rowid asc";
    $RESULTS = DB_query($SQL,$db);
    while($rows = DB_fetch_array($RESULTS)){
        $stockregister[]=$rows;
    }
     DB_free_result($RESULTS);
    
     if(is_array($stockregister)){
         foreach ($stockregister as $key => $Register) {
              $quantity = (($PartPerUnit>1)?($PartPerUnit * $qty):$qty);
              if($quantity>$Register['stockbalance']) {
               
                   DB_System(sprintf("Update [stockregister] set [StockOut]=([StockOut] + %f) where rowid='%s'",
                   $Register['stockbalance'], $Register['rowid']),$db);
                   $usequantity = $Register['stockbalance'];
                   $quantity    = ($quantity-$usequantity);
            
                } else{
                     DB_System(sprintf("Update [stockregister] set [StockOut]=([StockOut] + %f) where rowid='%s'",
                     $quantity, $Register['rowid']),$db);
                     $usequantity = $quantity;
                     $quantity    = 0;
                }
            
            $usequantity = (($PartPerUnit>1)?($usequantity / $PartPerUnit):$usequantity);
            $usecost = (($PartPerUnit>1)?($Register['cost'] * $PartPerUnit):$Register['cost']);
            if($usequantity>0){ 

               $SQL= "Insert into [generalledger] ([journalno],[Docdate],[period],[DocumentNo],[DocumentType],[accountcode],[balaccountcode]
                 ,[VATaccountcode] ,[amount],[VATamount],[currencycode],[ExchangeRate] ,[cutomercode],[narration])
                  (select '".$journal."',
                    salesheader.[docdate],'".$PeriodNo."' ,
                    salesheader.documentno,salesheader.documenttype,
                    [inventorypostinggroup].[stockvariance],
                    [inventorypostinggroup].[balancesheet],''
                 ,(".$usecost * $usequantity."), 0 ,'".$_SESSION['CompanyRecord']['currencydefault']."', 1 ,
                 salesheader.[customercode] ,
                 concat(salesline.[description],'@".$usecost."X".$usequantity." ".$unitofmeasure."')  as narration
               from salesheader 
               join salesline on [salesline].[documentno] = [salesheader].[documentno] 
               join [stockmaster] on [salesline].[code]=[stockmaster].itemcode
               join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
               join [currencies] on [salesheader].[currencycode]=[currencies].[currabrev]
               where salesheader.documentno='".$salesoderno."' and [salesline].[code]='".$stcode."' and [salesline].[entryno]='".$key."')";
               $RESULTS = DB_System($SQL,$db);
            }
      
         }
     }
     
      GetStockRegister($stcode);
}

Function GetEmptiesFIFO($journal,$PeriodNo,$salesoderno,$stcode,$qty,$key){
    global $db;
      
 $SQL ="select  
        [salesline].[containercode],
        [salesline].[unitofmeasure],
        [salesline].PartPerUnit,
        [salesheader].[documentno] 
     from salesheader
     join salesline on [salesline].[documentno] = [salesheader].[documentno] 
     where [salesline].[entryno]=".$key;
    
    $RESULTS = DB_query($SQL,$db);
    $rows = DB_fetch_row($RESULTS);
    $containercode = trim($rows[0]);
    $unitofmeasure = trim($rows[1]);
    $PartPerUnit   = trim($rows[2]);
    DB_free_result($RESULTS);
    
    
    $SQL ="select 
        stockregister.[cost],
        stockregister.[StockBalance],
        stockregister.[rowid] 
        from [stockregister] 
        where [stockregister].[itemcode]='".$containercode."' and [StockBalance]>0 
        order by stockregister.rowid asc";
    $RESULTS = DB_query($SQL,$db);
    while($rows = DB_fetch_row($RESULTS)){
        $stockregister[]=$rows;
    }
     DB_free_result($RESULTS);
    
     if(is_array($stockregister)){
         foreach ($stockregister as $key => $Register) {
              $quantity = (($PartPerUnit>1)?($PartPerUnit * $qty):$qty);
              if($quantity>$Register['stockbalance']) {
               
                   DB_System(sprintf("Update [stockregister] set [StockOut]=([StockOut] + %f) where rowid='%s'",
                   $Register['stockbalance'], $Register['rowid']),$db);
                   $usequantity = $Register['stockbalance'];
                   $quantity    = ($quantity-$usequantity);
            
                } else{
                     DB_System(sprintf("Update [stockregister] set [StockOut]=([StockOut] + %f) where rowid='%s'",
                     $quantity, $Register['rowid']),$db);
                     $usequantity = $quantity;
                     $quantity    = 0;
                }
            
            $usequantity=(($PartPerUnit>1)?($usequantity / $PartPerUnit):$usequantity);
            $usecost=(($PartPerUnit>1)?($Register['cost'] * $PartPerUnit):$Register['cost']);
            if($usequantity>0){ 

               $SQL= "Insert into [generalledger] ([journalno],[Docdate],[period],[DocumentNo],[DocumentType],[accountcode],[balaccountcode]
                 ,[VATaccountcode] ,[amount],[VATamount],[currencycode],[ExchangeRate] ,[cutomercode],[narration])
                  (select '".$journal."',
                    salesheader.[docdate],'".$PeriodNo."' ,
                    salesheader.documentno,salesheader.documenttype,
                    [inventorypostinggroup].[stockvariance],
                    [inventorypostinggroup].[balancesheet],''
                 ,(".$usecost * $usequantity."), 0 ,'".$_SESSION['CompanyRecord']['currencydefault']."', 1 ,
                 salesheader.[customercode] ,
                 concat(salesline.[description],'@".$usecost."X".$usequantity." ".$unitofmeasure."') as narration
               from salesheader 
               join salesline on [salesline].[documentno] = [salesheader].[documentno] 
               join [stockmaster] on [salesline].[containercode]=[stockmaster].itemcode
               join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
               join [currencies] on [salesheader].[currencycode]=[currencies].[currabrev]
               where salesheader.documentno='".$salesoderno."' 
               and [salesline].[containercode]='".$containercode."' and [salesline].[entryno]='".$key."' )";
               $RESULTS = DB_System($SQL,$db);
            }
      
         }
     }
     
      
}

Function GetFIFOissues($journal,$PeriodNo,$salesoderno,$stcode,$qty,$key=Array(),$DocumentType='40'){
 global $db;
 
 $SQL ="select 
        stockregister.[cost],
        stockregister.[StockBalance],
        stockregister.[rowid] ,
        stockregister.PartPerUnit
        from [stockregister] 
        where [stockregister].[itemcode]='".$stcode."' and [StockBalance]>0 
        order by stockregister.rowid asc";
    $RESULTS = DB_query($SQL,$db);
    while($rows = DB_fetch_row($RESULTS)){
        $stockregister[]=$rows;
    }
    
    
    if(is_array($stockregister)){
         foreach ($stockregister as $key => $Register) {
             
              $PartPerUnit = $key['PartPerUnit'];
              $quantity = (($PartPerUnit>1)?($PartPerUnit* $qty):$qty);
              
              if($quantity>$Register['stockbalance']) {
                   DB_System(sprintf("Update [stockregister] set [StockOut]=([StockOut] + %f) where rowid='%s'",$Register['stockbalance'], $Register['rowid']),$db);
                   $usequantity =(int) $Register['stockbalance'];
                   $quantity    =(int) ($quantity-$usequantity);
            
                } else{
                     DB_System(sprintf("Update [stockregister] set [StockOut]=([StockOut] + %f) where rowid='%s'",$quantity, $Register['rowid']),$db);
                     $usequantity = $quantity;
                     $quantity    = 0;
                }
         
            
            $usecost = (($PartPerUnit>1)?($Register['cost'] * $PartPerUnit):$Register['cost']);
            if($usequantity>0){ 

            $SQL="Insert into [generalledger] ([journalno],[Docdate],[period],[DocumentNo],[DocumentType],[accountcode],[balaccountcode]
            ,[VATaccountcode] ,[amount],[VATamount],[currencycode],[ExchangeRate] ,[cutomercode],[narration],[dimension]
            ,[dimension2]) (select '".$journal."','".$key['docdate']."','".$PeriodNo."' ,'".$salesoderno."','".$DocumentType."',
             [inventorypostinggroup].[productionexpense],[inventorypostinggroup].[balancesheet],'',
            ".($usecost * $usequantity).", 0 ,'".$_SESSION['CompanyRecord']['currencydefault']."', 1 ,
            '".$_POST['CustomerID']."','".$key['description']."@".$usecost."X".$usequantity.$key['unitofmeasure']."' as narration,
            '".trim($_POST['DimensionOne'])."', '".trim($_POST['DimensionTwo'])."'    
            from  [stockmaster]  join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
            where [stockmaster].[itemcode]='".$stcode."')";
                        
            $RESULTS = DB_System($SQL,$db);
            }
      
         }
     }
     
    
     DB_free_result($RESULTS);
       

}

Function InventoryReport($Fromdate,$Todate,$StoreCode='AllStores'){
    global $db;
    
   $SQL=sprintf("select stockmaster.itemcode,stockmaster.descrip,stockmaster.averagestock,stockmaster.units,
(select  descrip from unit where unit.code=stockmaster.units limit 1) as fulqtyName,(select  descrip from unit where unit.code=stockmaster.subunits limit 1) as LoosqtyName
from stockmaster where isstock not in (3)");
    
            $ResultIndex = DB_query($SQL,$db);
            while($row= DB_fetch_array($ResultIndex)){
                 $Sstock[$row['itemcode']]=$row;
            }
     
            if(is_array($Sstock)){
                foreach ($Sstock as $itemcode => $row) {
                    $array=StockBalance($itemcode,$Fromdate,$StoreCode);
                    $Openfqty=0;
                    $openlsqty=$array["loosqty"];
                    
                    $array=StockRange($itemcode,$Fromdate,$Todate,$StoreCode,$Doctypes='30');
                    $Purchasesfqty=0;
                    $purchaselsqty=$array["loosqty"];
	
                    $array=StockRange($itemcode,$Fromdate,$Todate,$StoreCode,$Doctypes='40');
                    $Prodfqty=0;
                    $Prodlsqty=$array["loosqty"];
   
                    $array=StockRange($itemcode,$Fromdate,$Todate,$StoreCode,$Doctypes='26');
                    $Worderqty=0;
                    $Worklsqty=$array["loosqty"];
              
                    $array=StockRange($itemcode,$Fromdate,$Todate,$StoreCode,$Doctypes='19');
                    $Salesfqty=0;
                    $Saleslsqty=$array["loosqty"];
                    
                    $total=($openlsqty+$purchaselsqty+$Prodlsqty);
                    $Totallsqty=(int)$total;
                    $Totalfqty =0; 
                   
                                       
                    $array = StockBalance($itemcode,$Todate,$StoreCode);
                    $Closingfqty =0;
                    $Closinglsqty=$array["loosqty"];
                    
                    $inventory[$row['itemcode']]=array("itemcode"=>$itemcode,"stockname"=>$row['descrip'],"averagestock"=>$row['averagestock'],"partperunit"=>'',"fulqtyName"=>$row['fulqtyName'],"LoosqtyName"=>$row['LoosqtyName'],
                        "openstock_f"=>$Openfqty,"openstock_l"=>$openlsqty, "Purchases_f"=>$Purchasesfqty, "Purchases_l"=>$purchaselsqty,
                        "Prod_f"=>$Prodfqty,"Prod_l"=>$Prodlsqty,"Total_f"=>$Totalfqty,"total_l"=>$Totallsqty, "Sales_f"=>$Salesfqty,
                        "Sales_l"=>$Saleslsqty, "Work_f"=>$Worderqty,"Work_l"=>$Worklsqty, "Close_f"=>$Closingfqty,"Close_l"=>$Closinglsqty);
                }
            }
	
     $RETURN=FALSE;
   if(is_array($inventory)){
       $RETURN=$inventory;
   }
   
   return $RETURN;
}

Function StockRange($itemcode,$startdate,$enddate,$STORECODE='AllStores',$Doctypes=''){
    global $db;
    
    if($STORECODE=='AllStores'){
          $SQL=sprintf("select sum(fulqty * partperunit),sum(loosqty)  
                   from stockledger where stockledger.itemcode='%s' and stockledger.date between '%s' and '%s'  
                   and stockledger.doctyp='%s'",$itemcode,$startdate,$enddate,$Doctypes); 
       }else{
          $SQL=sprintf("select sum(fulqty * partperunit),sum(loosqty)  
                   from stockledger  where stockledger.itemcode='%s' and stockledger.date between '%s' and '%s'  
                   and stockledger.store='%s' and stockledger.doctyp='%s'",$itemcode,$startdate,$enddate,$STORECODE,$Doctypes); 
        }
        
        $ResultIndex=DB_query($SQL,$db);
        $row=DB_fetch_row($ResultIndex);
        $fulqty = (int) $row[0]; 
        $loosqty=(int) $row[1]; 
        $total  = $fulqty+$loosqty;
       
     return array("loosqty"=>$total);   
}

Function StockBalance($itemcode,$startdate,$STORECODE='AllStores'){
    global $db;
    
       if($STORECODE=='AllStores'){
          $SQL=sprintf("select sum(fulqty * partperunit),sum(loosqty) from stockledger  
                   where stockledger.itemcode='%s' and stockledger.date <'%s'",$itemcode,$startdate); 
       }else{
          $SQL=sprintf("select sum(fulqty * partperunit),sum(loosqty) from stockledger  
                  where  stockledger.itemcode='%s' and stockledger.date < '%s' and stockledger.store='%s'",$itemcode,$startdate,$STORECODE); 
        }
         
        
        $ResultIndex=DB_query($SQL,$db);
        $row=DB_fetch_row($ResultIndex);
        $fulqty = (int) $row[0]; 
        $loosqty= (int) $row[1]; 
        $total  = $fulqty+$loosqty;
         
        
     return array("loosqty"=>$total);   
}

?>