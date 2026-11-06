<?php
include('includes/session.inc');
$Title = _('Stock Adjustments');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

$thispage = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

if(isset($_POST['savecount'])){
    $itemcode = isset($_POST['StockID']) ? str_replace("'", "''", $_POST['StockID']) : '';
    $store = isset($_POST['location']) ? str_replace("'", "''", $_POST['location']) : '';
    $fullCountInput = isset($_POST['count_full']) ? trim($_POST['count_full']) : '';
    $looseCountInput = isset($_POST['count_loose']) ? trim($_POST['count_loose']) : '';

    if($itemcode === '' || $store === '' || $fullCountInput === '' || $looseCountInput === ''
        || !is_numeric($fullCountInput) || !is_numeric($looseCountInput)
        || (float)$fullCountInput < 0 || (float)$looseCountInput < 0){
        prnMsg('Enter valid non-negative full-unit and loose-unit physical counts, and select a store.','warn');
    }else{
        $countResult = DB_query("SELECT
                COALESCE(SUM(COALESCE(stockledger.fulqty,0) * COALESCE(stockmaster.partperunit,0)),0)
                + COALESCE(SUM(COALESCE(stockledger.loosqty,0)),0) AS systemunits,
                COALESCE(stockmaster.partperunit,0) AS partperunit,
                COALESCE(stockmaster.averagestock,0) AS averagecost
            FROM stockmaster
            LEFT JOIN stockledger
                ON stockledger.itemcode=stockmaster.itemcode
                AND stockledger.store='".$store."'
            WHERE stockmaster.itemcode='".$itemcode."'
            GROUP BY stockmaster.partperunit,stockmaster.averagestock",$db);

        if(DB_num_rows($countResult) < 1){
            prnMsg('The selected inventory item could not be found.','warn');
        }else{
            $countData = DB_fetch_array($countResult);
            $partPerUnit = (float)$countData['partperunit'];
            if($partPerUnit <= 0){
                prnMsg('This item has an invalid pack size. Set a valid Parts In a Unit value before taking stock.','warn');
            }else{
                $systemUnits = (float)$countData['systemunits'];
                $physicalUnits = ((float)$fullCountInput * $partPerUnit) + (float)$looseCountInput;
                $difference = $physicalUnits - $systemUnits;

                if(abs($difference) < 0.00005){
                    prnMsg('Physical count matches the system balance. No adjustment was posted.');
                }else{
                    $differenceSql = sprintf('%.4F',$difference);
                    $averageCost = (float)$countData['averagecost'];
                    $amountSql = sprintf('%.4F',abs($difference * $averageCost));
                    $journalno = GetNextTransNo(0,$db);
                    $documentNo = isset($_POST['DocNo']) ? str_replace("'", "''", $_POST['DocNo']) : GetNextTransNo(17,$db);
                    $periodNo = GetPeriod(Today(),$db);
                    $currency = $_SESSION['CompanyRecord']['currencydefault'];

                    $SQL = array();
                    $SQL[] = "INSERT INTO [stockledger]
                        ([date],[stname],[doctyp],[itemcode],[invref],[fulqty],[loosqty],[price],[store],[journal],[period])
                        VALUES (sysdate(),'Stock Take Adjustment','17','".$itemcode."','".$documentNo."',0,'".$differenceSql."','".$averageCost."','".$store."','".$journalno."','".$periodNo."')";

                    $SQL[] = "INSERT INTO [Generalledger]
                        ([journalno],[Docdate],[period],[DocumentNo],[DocumentType],[accountcode],[balaccountcode],[VATaccountcode],
                         [amount],[VATamount],[currencycode],[ExchangeRate],[narration])
                        SELECT
                            '".$journalno."',[stockledger].[date],'".$periodNo."','".$documentNo."',17,
                            CASE WHEN ".$differenceSql." > 0 THEN [inventorypostinggroup].[balancesheet] ELSE [inventorypostinggroup].[stockvariance] END,
                            CASE WHEN ".$differenceSql." > 0 THEN [inventorypostinggroup].[stockvariance] ELSE [inventorypostinggroup].[balancesheet] END,
                            '',
                            ".$amountSql.",0,'".$currency."',1,
                            CONCAT(RTRIM([stockmaster].[descrip]),' - Counted ".$fullCountInput." full + ".$looseCountInput." loose; system ".$systemUnits."; variance ".$differenceSql."')
                        FROM [stockledger]
                        JOIN [stockmaster] ON [stockledger].[itemcode]=[stockmaster].[itemcode]
                        JOIN [inventorypostinggroup] ON [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
                        WHERE [stockledger].[journal]='".$journalno."'";

                    DB_Txn_Begin($db);
                    foreach($SQL as $statement){
                        DB_query($statement,$db);
                    }
                    if(DB_error_no($db)>0){
                        DB_Txn_Rollback($db);
                        prnMsg('The stock take adjustment could not be posted. No stock change was saved.','warn');
                    }else{
                        DB_Txn_Commit($db);
                        prnMsg('Stock take posted. System stock has been adjusted to the physical count.');
                        unset($_POST['count_full'],$_POST['count_loose']);
                        $_POST['DocNo'] = GetNextTransNo(17,$db);
                    }
                }
            }
        }
    }
}

if(isset($_POST['saveadjustment'])){
    
   $SQL=array();
   
   if(isset($_POST['averagecost']) and $_POST['averagecost']>0){
   $SQL[]="Update stockmaster set [averagestock]='".$_POST['averagecost']."' where itemcode='".$_POST['StockID']."'";
   }
   
   if($_POST['stockbalance']!=0){
       
        $journalno = GetNextTransNo(0, $db);
        $Periodno = GetPeriod(Today(), $db);
   
       $SQL[]="INSERT INTO [stockledger]
           ([date],[stname],[doctyp],[itemcode],[invref],[fulqty],[loosqty],[price],[store],[journal],[period])
         VALUES (sysdate() ,'Stock Adjust' ,'17','".$_POST['StockID']."'"
               . ",'".$_POST['DocNo']."'"
               . ",'".(($_POST['UOM']=='fulqty')?$_POST['stockbalance']*1:'0')."'"
               . ",'".(($_POST['UOM']=='loosqty')?$_POST['stockbalance']*1:'0')."'"
               . ",".($_POST['averagecost']*1).""
               . ",'".$_POST['location']."'"
               . ",'".$journalno."'"
               . ",'".$Periodno."'"
               . ")";
       
       if($_POST['stockbalance']>0){
       
                        $SQL[]=SPRINTF("Insert into [Generalledger]
                        ([journalno],[Docdate] ,[period],[DocumentNo],[DocumentType],[accountcode],[balaccountcode],[VATaccountcode]
                        ,[amount],[VATamount],[currencycode],[ExchangeRate],[narration])
                        (select
                         '%s'
                        ,[stockledger].[date]
                        ,'%s'
                        ,[stockledger].invref
                        ,17
                        ,[inventorypostinggroup].[balancesheet]
                        ,[inventorypostinggroup].[stockvariance]
                        ,' '
                        ,[stockmaster].[averagestock] * ".$_POST['stockbalance']."
                        ,0
                        ,".$_SESSION['CompanyRecord']['currencydefault']."
                        ,1
                        ,(rtrim([stockmaster].[descrip]) +' X ".$_POST['stockbalance']."' )  as narration
                        from [stockledger] 
                        join [stockmaster] on [stockledger].[itemcode]=[stockmaster].itemcode
                        join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
                        where stockledger.journal='%s' 
                        )",$journalno,$Periodno,$journalno);
       }elseif($_POST['stockbalance']<0){
             $SQL[]=SPRINTF("Insert into [Generalledger]
                        ([journalno],[Docdate] ,[period],[DocumentNo],[DocumentType],[accountcode],[balaccountcode],[VATaccountcode]
                        ,[amount],[VATamount],[currencycode],[ExchangeRate],[narration])
                        (select
                         '%s'
                        ,[stockledger].[date]
                        ,'%s'
                        ,[stockledger].invref
                        ,17
                        ,[inventorypostinggroup].[stockvariance]
                        ,[inventorypostinggroup].[balancesheet]
                        ,' '
                        ,[stockmaster].[averagestock] * ".$_POST['stockbalance']."
                        ,0
                        ,".$_SESSION['CompanyRecord']['currencydefault']."
                        ,1
                       ,(rtrim([stockmaster].[descrip]) +' X ".$_POST['stockbalance']."' )  as narration
                        from [stockledger] 
                        join [stockmaster] on [stockledger].[itemcode]=[stockmaster].itemcode
                        join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
                        where stockledger.journal='%s' 
                        )",$journalno,$Periodno,$journalno);
       }
       
                   
 
   }
   
                    DB_Txn_Begin($db);
                    foreach ($SQL as $value) {
                        DB_query($value,$db);
                    }
                    if(DB_error_no($db)>0){
                        DB_Txn_Rollback($db);
                    }else{
                        DB_Txn_Commit($db);
                        prnMsg('This transaction has been posted');
                    }
   
    
}


echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . _('Stock Adjustments') . '" alt="" />' . ' ' . _('Stock Adjustments') . '</p>';
echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" id="inventory">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if(isset($_GET['StockID']) || isset($_POST['StockID'])){
    $selectedStockID = isset($_GET['StockID']) ? $_GET['StockID'] : $_POST['StockID'];
    
        $results=DB_query("Select [itemcode],[descrip],[inactive],[partperunit],[averagestock],[postinggroup]  FROM [stockmaster] where itemcode='".$selectedStockID."'", $db);
        $rowsstock=DB_fetch_row($results);
        $DESCRIPTION=$rowsstock[1];
        $AVERAGESTOCK=$rowsstock[4];        
       
       $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty  FROM [stockmaster] left join [unit] f on [stockmaster].[units]=f.code   left join [unit] l on [stockmaster].[subunits]=l.code  where itemcode= '".$selectedStockID."'", $db);
       $UOMline ='<select name="UOM" required="required">';
       $rows=  DB_fetch_row($REsults);
        $fullUnitName = isset($rows[0]) && $rows[0] !== '' ? $rows[0] : 'Full units';
        $looseUnitName = isset($rows[1]) && $rows[1] !== '' ? $rows[1] : 'Loose units';
        $UOMline .=sprintf('<option value="fulqty" %s >'.$rows[0].'</option>'
                   . '<option value="loosqty" %s >'.$rows[1].'</option>'
                   ,$_POST['UOM']=='fulqty'?'selected="selected"':'',
                   $_POST['UOM']=='loosqty'?'selected="selected"':'');
       $UOMline .= '</select>';
  
       
       $selectedStore = isset($_POST['location']) ? $_POST['location'] : '';
       if($selectedStore === ''){
           $firstStore = DB_query('SELECT [code] FROM [Stores] ORDER BY [code] LIMIT 1',$db);
           $firstStoreRow = DB_fetch_row($firstStore);
           $selectedStore = isset($firstStoreRow[0]) ? $firstStoreRow[0] : '';
       }
       $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
       $STOREline ='<select name="location" required="required">';
       while($rows=DB_fetch_array($REsults)){
           $STOREline .= sprintf('<option value="%s" %s >%s</option>',
                   $rows['code'],$selectedStore==$rows['code']?'selected="selected"':'',$rows['Storename']);
       }
       $STOREline .= '</select>';
       
    echo '<p class="page_title_text">'. $DESCRIPTION. '</p>';
    echo '<input type="hidden" name="StockID" value="' .$selectedStockID .'"/>';
   
    if(!isset($_POST['DocNo']) || $_POST['DocNo']===''){ $_POST['DocNo'] = GetNextTransNo(17, $db); }
    
    $balanceResult = DB_query("SELECT
            COALESCE(SUM(COALESCE(stockledger.fulqty,0) * COALESCE(stockmaster.partperunit,0)),0)
            + COALESCE(SUM(COALESCE(stockledger.loosqty,0)),0) AS systemunits,
            COALESCE(stockmaster.partperunit,0) AS partperunit
        FROM stockmaster
        LEFT JOIN stockledger ON stockledger.itemcode=stockmaster.itemcode AND stockledger.store='".str_replace("'", "''", $selectedStore)."'
        WHERE stockmaster.itemcode='".str_replace("'", "''", $selectedStockID)."'
        GROUP BY stockmaster.partperunit",$db);
    $balanceData = DB_fetch_array($balanceResult);
    $systemUnits = isset($balanceData['systemunits']) ? (float)$balanceData['systemunits'] : 0;
    $partPerUnit = isset($balanceData['partperunit']) ? (float)$balanceData['partperunit'] : 0;
    $systemFull = ($partPerUnit > 0) ? (int)($systemUnits / $partPerUnit) : 0;
    $systemLoose = $systemUnits - ($systemFull * $partPerUnit);

    echo '<table class="table table-bordered">'
    . '<tr><td>Document No</td><td><input type="text" name="DocNo" value="'.$_POST['DocNo'].'" size="10" readonly="readonly"/></td></tr>'
            . '<tr><td>Average Cost</td><td><input type="text" name="averagecost" class="number" maxlength="10" size="10" value="'.$AVERAGESTOCK.'"/></td></tr>';
    echo '<tr><td>Store</td><td>';
    echo  $STOREline;
    echo '</td></tr>';
    echo '<tr><td colspan="2"><strong>Physical Stock Take</strong></td></tr>';
    echo '<tr><td>Current system balance</td><td>'.number_format($systemFull,0).' '.$fullUnitName.' + '.number_format($systemLoose,2).' '.$looseUnitName.' ('.number_format($systemUnits,2).' '.$looseUnitName.' equivalent)</td></tr>';
    echo '<tr><td>Physical full units counted</td><td><input type="number" min="0" step="any" name="count_full" value="'.(isset($_POST['count_full'])?htmlspecialchars($_POST['count_full'],ENT_QUOTES,'UTF-8'):'').'"/> '.$fullUnitName.'</td></tr>';
    echo '<tr><td>Physical loose units counted</td><td><input type="number" min="0" step="any" name="count_loose" value="'.(isset($_POST['count_loose'])?htmlspecialchars($_POST['count_loose'],ENT_QUOTES,'UTF-8'):'').'"/> '.$looseUnitName.'</td></tr>';
    echo '<tr><td colspan="2"><input type="submit" name="savecount" value="Post Stock Take" onclick="return confirm(\'Post the physical count and adjust stock to match?\');"/></td></tr>';
    echo '<tr><td colspan="2"><strong>Manual Adjustment</strong></td></tr>';
    echo '<tr><td>Adjust by QTY</td><td>';
    echo  $UOMline ;
    echo  '<input type="text" name="stockbalance" class="number" maxlength="10" size="10"/></td></tr>';
    echo '</table><br />';
    echo '<div><input type="submit" name="saveadjustment" value="Save Adjustment"/>';
    echo '<input type="submit" name="cancel" value="Cancel"/></div>';

}else{
 
echo '<table  class="table table-bordered">'
        . '<thead><TR>'
        . '<th>Stock Code</th>'
        . '<th>Inventory Name</th>'
        . '<th>Is Obsolete</th>'
        . '<th>Pan Zize</th>'
        . '<th>Ave Cost</th>'
        . '<th>Posting Group</th></tr></thead><tbody>';
      
       $results=DB_query('Select [itemcode],[descrip],'
               . '[inactive],[partperunit],[averagestock],[postinggroup]  '
               . 'FROM [stockmaster] where isstock<>3 order by [descrip]', $db);
       
       $k=0;
       while($rows=DB_fetch_array($results)){
           echo sprintf('<tr><td><a href="%s?StockID=%s">%s</a></td>', $thispage, trim($rows['itemcode']),trim($rows['itemcode']));
           echo '<td>'.$rows['descrip'].'</td>';
           echo '<td>'.($rows['inactive']==true?'YES':"NO").'</td>';
           echo '<td>'. number_format($rows['partperunit'],0).'</td>';
           echo '<td>'. number_format($rows['averagestock'],2).'</td>';
           echo '<td>'.trim($rows['postinggroup']).'</td></tr>';
            
       }     
       
       echo '<table>';
}

echo '</div></form>' ;
include('includes/footer.inc');

?>