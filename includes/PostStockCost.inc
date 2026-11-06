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

    $lineKey = (int)$key;
    $receivedQty = (float)$Quantity;
    if($receivedQty <= 0){
        return false;
    }

    $SQL = "SELECT
                purchaseheader.documentno,
                purchaseheader.docdate,
                purchaseheader.currencycode,
                purchaseline.code,
                (purchaseline.UnitPrice * currencies.rate) AS UnitPrice,
                purchaseline.PartPerUnit,
                purchaseline.Quantity,
                (purchaseheader.freight * currencies.rate) AS freight,
                purchaseline.UOM,
                (IFNULL(purchaseline.discount,0) * currencies.rate) AS discount,
                purchaseline.description,
                purchaseline.unitofmeasure
            FROM purchaseheader
            JOIN purchaseline ON purchaseline.documentno=purchaseheader.documentno
            JOIN currencies ON currencies.currabrev=purchaseheader.currencycode
            WHERE purchaseline.entryno=".$lineKey."
            LIMIT 1";

    $RESULTS = DB_query($SQL,$db);
    if(DB_num_rows($RESULTS) < 1){
        return false;
    }

    $row = DB_fetch_array($RESULTS);
    DB_free_result($RESULTS);

    $GRN = str_replace("'","''",trim($row['documentno']));
    $itemcode = str_replace("'","''",trim($row['code']));
    $partPerUnit = (float)$row['PartPerUnit'];
    $uom = isset($row['UOM']) ? $row['UOM'] : 'loosqty';
    $orderedQty = (float)$row['Quantity'];
    if($uom === 'fulqty' && $partPerUnit <= 0){
        return false;
    }

    $orderedBaseQty = ($uom === 'fulqty') ? ($orderedQty * $partPerUnit) : $orderedQty;
    $receivedBaseQty = ($uom === 'fulqty') ? ($receivedQty * $partPerUnit) : $receivedQty;
    if($orderedBaseQty <= 0 || $receivedBaseQty <= 0){
        return false;
    }

    $unitCost = (float)$row['UnitPrice'] - (float)$row['discount'];
    $freightPerBaseUnit = ((float)$row['freight'] > 0)
        ? ((float)$row['freight'] / $orderedBaseQty)
        : 0;
    $unitCost += $freightPerBaseUnit;
    if($unitCost < 0){
        return false;
    }

    $amount = $unitCost * $receivedBaseQty;
    $journalSql = str_replace("'","''",$journal);
    $periodSql = str_replace("'","''",$PeriodNo);
    $docDate = str_replace("'","''",$row['docdate']);
    $description = str_replace("'","''",trim($row['description']));
    $unitName = str_replace("'","''",trim($row['unitofmeasure']));
    $currency = str_replace("'","''",$_SESSION['CompanyRecord']['currencydefault']);

    $insertRegister = "INSERT INTO stockregister
        (itemcode,StockIn,cost,StockOut,journal,GRN)
        VALUES
        ('".$itemcode."',".sprintf('%.6F',$receivedBaseQty).",
         ".sprintf('%.6F',$unitCost).",0,'".$journalSql."','".$GRN."')";
    DB_System($insertRegister,$db);

    $insertGL = "INSERT INTO generalledger
        (journalno,Docdate,period,DocumentNo,DocumentType,accountcode,balaccountcode,
         VATaccountcode,amount,VATamount,currencycode,ExchangeRate,cutomercode,narration)
        SELECT
            '".$journalSql."',purchaseheader.docdate,'".$periodSql."',
            purchaseheader.documentno,purchaseheader.documenttype,
            inventorypostinggroup.balancesheet,inventorypostinggroup.stockvariance,'',
            ".sprintf('%.6F',$amount).",0,'".$currency."',1,purchaseheader.vendorcode,
            CONCAT('".$description." X ', '".sprintf('%.6F',$receivedQty)."', ' ', '".$unitName."')
        FROM purchaseheader
        JOIN purchaseline ON purchaseline.documentno=purchaseheader.documentno
        JOIN stockmaster ON purchaseline.code=stockmaster.itemcode
        JOIN inventorypostinggroup ON inventorypostinggroup.code=stockmaster.postinggroup
        WHERE purchaseline.entryno=".$lineKey."
          AND purchaseline.code='".$itemcode."'";
    DB_System($insertGL,$db);

    GetStockRegister($itemcode);
    return true;
}

Function GetStockRegister($itemcode){
    global $db;

    $itemcode = str_replace("'", "''", $itemcode);
    $SQL = "UPDATE stockmaster
            SET averagestock = (
                SELECT cost
                FROM stockregister
                WHERE itemcode='".$itemcode."'
                  AND StockBalance > 0
                ORDER BY rowid ASC
                LIMIT 1
            )
            WHERE itemcode='".$itemcode."'";
    return DB_System($SQL,$db);
}

Function GetFIFO($journal,$PeriodNo,$salesoderno,$stcode,$qty,$key){
    global $db;

    $itemcode = str_replace("'", "''", $stcode);
    $documentNo = str_replace("'", "''", $salesoderno);
    $lineKey = (int)$key;
    $requestedQty = (float)$qty;
    if($requestedQty <= 0){
        return false;
    }

    $SQL = "SELECT salesline.unitofmeasure, salesline.UOM, salesline.PartPerUnit
            FROM salesline
            WHERE salesline.entryno=".$lineKey."
              AND salesline.documentno='".$documentNo."'
              AND salesline.code='".$itemcode."'
            LIMIT 1";
    $RESULTS = DB_query($SQL,$db);
    if(DB_num_rows($RESULTS) < 1){
        return false;
    }
    $line = DB_fetch_array($RESULTS);
    DB_free_result($RESULTS);

    $unitofmeasure = isset($line['unitofmeasure']) ? $line['unitofmeasure'] : '';
    $uom = isset($line['UOM']) ? $line['UOM'] : 'loosqty';
    $partPerUnit = isset($line['PartPerUnit']) ? (float)$line['PartPerUnit'] : 0;
    if($uom === 'fulqty' && $partPerUnit <= 0){
        return false;
    }

    $remainingQty = ($uom === 'fulqty') ? ($requestedQty * $partPerUnit) : $requestedQty;
    $SQL = "SELECT cost, StockBalance, rowid
            FROM stockregister
            WHERE itemcode='".$itemcode."' AND StockBalance > 0
            ORDER BY rowid ASC";
    $RESULTS = DB_query($SQL,$db);
    $stockregister = array();
    $availableQty = 0.0;
    while($row = DB_fetch_array($RESULTS)){
        $stockregister[] = $row;
        $availableQty += max(0,(float)$row['StockBalance']);
    }
    DB_free_result($RESULTS);

    if($availableQty + 0.0000001 < $remainingQty){
        return false;
    }

    foreach($stockregister as $Register){
        if($remainingQty <= 0.0000001){
            break;
        }

        $layerBalance = max(0,(float)$Register['StockBalance']);
        $useBaseQty = min($remainingQty,$layerBalance);
        if($useBaseQty <= 0){
            continue;
        }

        $rowid = str_replace("'", "''", $Register['rowid']);
        $updateSQL = sprintf(
            "UPDATE stockregister
             SET StockOut=COALESCE(StockOut,0)+%.6F
             WHERE rowid='%s'",
            $useBaseQty,
            $rowid
        );
        DB_System($updateSQL,$db);

        $layerCost = (float)$Register['cost'];
        $displayQty = ($uom === 'fulqty') ? ($useBaseQty / $partPerUnit) : $useBaseQty;
        $displayCost = ($uom === 'fulqty') ? ($layerCost * $partPerUnit) : $layerCost;
        $amount = $layerCost * $useBaseQty;

        $displayCostSql = sprintf('%.6F',$displayCost);
        $displayQtySql = sprintf('%.6F',$displayQty);
        $amountSql = sprintf('%.6F',$amount);
        $currency = str_replace("'", "''", $_SESSION['CompanyRecord']['currencydefault']);
        $GLSQL = "INSERT INTO generalledger
            (journalno,Docdate,period,DocumentNo,DocumentType,accountcode,balaccountcode,
             VATaccountcode,amount,VATamount,currencycode,ExchangeRate,cutomercode,narration)
            SELECT
                '".$journal."',salesheader.docdate,'".$PeriodNo."',
                salesheader.documentno,salesheader.documenttype,
                inventorypostinggroup.stockvariance,inventorypostinggroup.balancesheet,'',
                ".$amountSql.",0,
                '".$currency."',1,
                salesheader.customercode,
                CONCAT(salesline.description,'@',".$displayCostSql.",' X ',".$displayQtySql.",' ',salesline.unitofmeasure)
            FROM salesheader
            JOIN salesline ON salesline.documentno=salesheader.documentno
            JOIN stockmaster ON salesline.code=stockmaster.itemcode
            JOIN inventorypostinggroup ON inventorypostinggroup.code=stockmaster.postinggroup
            WHERE salesheader.documentno='".$documentNo."'
              AND salesline.code='".$itemcode."'
              AND salesline.entryno=".$lineKey;
        DB_System($GLSQL,$db);

        $remainingQty -= $useBaseQty;
    }

    GetStockRegister($itemcode);
    return ($remainingQty <= 0.0000001);
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

    $itemcode = str_replace("'", "''", $stcode);
    $requestedQty = (float)$qty;
    if($requestedQty <= 0){
        return false;
    }

    $partResult = DB_query("SELECT partperunit FROM stockmaster WHERE itemcode='".$itemcode."'",$db);
    if(DB_num_rows($partResult) < 1){
        return false;
    }
    $partRow = DB_fetch_row($partResult);
    $partPerUnit = (float)$partRow[0];
    if($partPerUnit <= 0){
        return false;
    }

    $remainingQty = $partPerUnit * $requestedQty;
    $SQL = "SELECT cost,StockBalance,rowid
            FROM stockregister
            WHERE itemcode='".$itemcode."' AND StockBalance > 0
            ORDER BY rowid ASC";
    $RESULTS = DB_query($SQL,$db);
    $stockregister = array();
    $availableQty = 0.0;
    while($Register = DB_fetch_array($RESULTS)){
        $stockregister[] = $Register;
        $availableQty += max(0,(float)$Register['StockBalance']);
    }
    DB_free_result($RESULTS);

    if($availableQty + 0.0000001 < $remainingQty){
        return false;
    }

    $docDate = isset($key['docdate']) ? str_replace("'","''",$key['docdate']) : '';
    $description = isset($key['description']) ? str_replace("'","''",$key['description']) : $itemcode;
    $unit = isset($key['unitofmeasure']) ? str_replace("'","''",$key['unitofmeasure']) : '';
    $customer = isset($_POST['CustomerID']) ? str_replace("'","''",$_POST['CustomerID']) : '';
    $dimensionOne = isset($_POST['DimensionOne']) ? str_replace("'","''",$_POST['DimensionOne']) : '';
    $dimensionTwo = isset($_POST['DimensionTwo']) ? str_replace("'","''",$_POST['DimensionTwo']) : '';

    foreach($stockregister as $Register){
        if($remainingQty <= 0.0000001){
            break;
        }

        $layerBalance = max(0,(float)$Register['StockBalance']);
        $useBaseQty = min($remainingQty,$layerBalance);
        if($useBaseQty <= 0){
            continue;
        }

        $rowid = str_replace("'","''",$Register['rowid']);
        DB_System(sprintf(
            "UPDATE stockregister
             SET StockOut=COALESCE(StockOut,0)+%.6F
             WHERE rowid='%s'",
            $useBaseQty,
            $rowid
        ),$db);

        $amount = (float)$Register['cost'] * $useBaseQty;
        $narration = $description." @ ".((float)$Register['cost'] * $partPerUnit)." X ".($useBaseQty / $partPerUnit)." ".$unit;
        $narration = str_replace("'","''",$narration);
        $GLSQL = "INSERT INTO generalledger
            (journalno,Docdate,period,DocumentNo,DocumentType,accountcode,balaccountcode,
             VATaccountcode,amount,VATamount,currencycode,ExchangeRate,cutomercode,narration,dimension,dimension2)
            SELECT '".$journal."','".$docDate."','".$PeriodNo."','".$salesoderno."','".$DocumentType."',
                inventorypostinggroup.productionexpense,inventorypostinggroup.balancesheet,'',
                ".sprintf('%.6F',$amount).",0,
                '".$_SESSION['CompanyRecord']['currencydefault']."',1,
                '".$customer."','".$narration."','".$dimensionOne."','".$dimensionTwo."'
            FROM stockmaster
            JOIN inventorypostinggroup ON inventorypostinggroup.code=stockmaster.postinggroup
            WHERE stockmaster.itemcode='".$itemcode."'";
        DB_System($GLSQL,$db);

        $remainingQty -= $useBaseQty;
    }

    GetStockRegister($itemcode);
    return ($remainingQty <= 0.0000001);
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

    $itemcode = str_replace("'", "''", $itemcode);
    $startdate = str_replace("'", "''", $startdate);
    $enddate = str_replace("'", "''", $enddate);
    $doctype = str_replace("'", "''", $Doctypes);

    $SQL = "SELECT
                COALESCE(SUM(COALESCE(stockledger.fulqty,0) * COALESCE(stockmaster.partperunit,0)),0)
                + COALESCE(SUM(COALESCE(stockledger.loosqty,0)),0) AS totalunits
            FROM stockledger
            JOIN stockmaster ON stockmaster.itemcode = stockledger.itemcode
            WHERE stockledger.itemcode='".$itemcode."'
              AND stockledger.date BETWEEN '".$startdate."' AND '".$enddate."'
              AND stockledger.doctyp='".$doctype."'";

    if($STORECODE !== 'AllStores'){
        $store = str_replace("'", "''", $STORECODE);
        $SQL .= " AND stockledger.store='".$store."'";
    }

    $ResultIndex = DB_query($SQL,$db);
    $row = DB_fetch_row($ResultIndex);
    return array("loosqty" => (float)$row[0]);
}

Function StockBalance($itemcode,$startdate,$STORECODE='AllStores'){
    global $db;

    $itemcode = str_replace("'", "''", $itemcode);
    $startdate = str_replace("'", "''", $startdate);

    $SQL = "SELECT
                COALESCE(SUM(COALESCE(stockledger.fulqty,0) * COALESCE(stockmaster.partperunit,0)),0)
                + COALESCE(SUM(COALESCE(stockledger.loosqty,0)),0) AS totalunits
            FROM stockledger
            JOIN stockmaster ON stockmaster.itemcode = stockledger.itemcode
            WHERE stockledger.itemcode='".$itemcode."'
              AND stockledger.date < '".$startdate."'";

    if($STORECODE !== 'AllStores'){
        $store = str_replace("'", "''", $STORECODE);
        $SQL .= " AND stockledger.store='".$store."'";
    }

    $ResultIndex = DB_query($SQL,$db);
    $row = DB_fetch_row($ResultIndex);
    return array("loosqty" => (float)$row[0]);
}
?>