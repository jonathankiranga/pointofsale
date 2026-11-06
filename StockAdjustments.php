<?php
include('includes/session.inc');
$Title = _('Stock Adjustments');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');

$thispage = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

function AdjustStockRegisterForCount($itemcode,$baseDifference,$averageCost,$journalno,$documentNo){
    global $db;

    $itemcode = str_replace("'","''",$itemcode);
    $journalno = str_replace("'","''",$journalno);
    $documentNo = str_replace("'","''",$documentNo);
    $difference = (float)$baseDifference;

    if(abs($difference) < 0.0000001){
        return 0.0;
    }

    if($difference > 0){
        DB_System("INSERT INTO stockregister
            (itemcode,StockIn,cost,StockOut,journal,GRN)
            VALUES ('".$itemcode."',".sprintf('%.6F',$difference).",
                ".sprintf('%.6F',(float)$averageCost).",0,'".$journalno."','".$documentNo."')",$db);
        return $difference * (float)$averageCost;
    }

    $remaining = abs($difference);
    $layers = array();
    $available = 0.0;
    $result = DB_query("SELECT rowid,cost,StockBalance
        FROM stockregister
        WHERE itemcode='".$itemcode."' AND StockBalance > 0
        ORDER BY rowid ASC",$db);
    while($row = DB_fetch_array($result)){
        $layers[] = $row;
        $available += max(0,(float)$row['StockBalance']);
    }
    DB_free_result($result);

    if($available + 0.0000001 < $remaining){
        return false;
    }

    $totalCost = 0.0;
    foreach($layers as $layer){
        if($remaining <= 0.0000001){
            break;
        }
        $take = min($remaining,max(0,(float)$layer['StockBalance']));
        if($take <= 0){
            continue;
        }
        DB_System(sprintf(
            "UPDATE stockregister
             SET StockOut=COALESCE(StockOut,0)+%.6F
             WHERE rowid='%s'",
            $take,
            str_replace("'","''",$layer['rowid'])
        ),$db);
        $totalCost += $take * (float)$layer['cost'];
        $remaining -= $take;
    }

    return ($remaining <= 0.0000001) ? $totalCost : false;
}

if(isset($_POST['savecount'])){
    $itemcode = isset($_POST['StockID']) ? str_replace("'", "''", trim($_POST['StockID'])) : '';
    $store = isset($_POST['location']) ? str_replace("'", "''", trim($_POST['location'])) : '';
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
                    $journalno = GetNextTransNo(0,$db);
                    $documentNo = isset($_POST['DocNo']) ? str_replace("'", "''", $_POST['DocNo']) : GetNextTransNo(17,$db);
                    $periodNo = GetPeriod(Today(),$db);
                    $currency = str_replace("'", "''", $_SESSION['CompanyRecord']['currencydefault']);
                    $narration = str_replace("'","''","Counted ".$fullCountInput." full + ".$looseCountInput." loose; system ".$systemUnits."; variance ".$differenceSql);

                    DB_Txn_Begin($db);
                    DB_query("INSERT INTO stockledger
                        (date,stname,doctyp,itemcode,invref,fulqty,loosqty,price,store,journal,period)
                        VALUES (SYSDATE(),'Stock Take Adjustment','17','".$itemcode."','".$documentNo."',
                            0,'".$differenceSql."','".$averageCost."','".$store."','".$journalno."','".$periodNo."')",$db);

                    $fifoValue = AdjustStockRegisterForCount($itemcode,$difference,$averageCost,$journalno,$documentNo);
                    if($fifoValue === false){
                        DB_Txn_Rollback($db);
                        prnMsg('The FIFO register does not contain enough costed stock to post this shortage. Reconcile the FIFO layers before posting the stock take.','warn');
                    }else{
                        $amountSql = sprintf('%.6F',abs($fifoValue));
                        DB_query("INSERT INTO Generalledger
                            (journalno,Docdate,period,DocumentNo,DocumentType,accountcode,balaccountcode,
                             VATaccountcode,amount,VATamount,currencycode,ExchangeRate,narration)
                            SELECT '".$journalno."',SYSDATE(),'".$periodNo."','".$documentNo."',17,
                                CASE WHEN ".$differenceSql." > 0
                                     THEN inventorypostinggroup.balancesheet
                                     ELSE inventorypostinggroup.stockvariance END,
                                CASE WHEN ".$differenceSql." > 0
                                     THEN inventorypostinggroup.stockvariance
                                     ELSE inventorypostinggroup.balancesheet END,
                                '',".$amountSql.",0,'".$currency."',1,
                                CONCAT(RTRIM(stockmaster.descrip),' - ".$narration."')
                            FROM stockmaster
                            JOIN inventorypostinggroup ON inventorypostinggroup.code=stockmaster.postinggroup
                            WHERE stockmaster.itemcode='".$itemcode."'",$db);

                        if(DB_error_no($db)>0){
                            DB_Txn_Rollback($db);
                            prnMsg('The stock take adjustment could not be posted. No stock change was saved.','warn');
                        }else{
                            DB_Txn_Commit($db);
                            GetStockRegister($itemcode);
                            prnMsg('Stock take posted. System stock has been adjusted to the physical count.');
                            unset($_POST['count_full'],$_POST['count_loose']);
                            $_POST['DocNo'] = GetNextTransNo(17,$db);
                        }
                    }
                }
            }
        }
    }
}

if(isset($_POST['saveadjustment'])){
    $itemcode = isset($_POST['StockID']) ? str_replace("'", "''", trim($_POST['StockID'])) : '';
    $store = isset($_POST['location']) ? str_replace("'", "''", trim($_POST['location'])) : '';
    $uom = isset($_POST['UOM']) ? $_POST['UOM'] : '';
    $qtyInput = isset($_POST['stockbalance']) ? str_replace(',', '', trim($_POST['stockbalance'])) : '';
    $averageCostInput = isset($_POST['averagecost']) ? str_replace(',', '', trim($_POST['averagecost'])) : '';

    if($itemcode === '' || $store === '' || !is_numeric($qtyInput)
        || ($uom !== 'fulqty' && $uom !== 'loosqty')
        || ($averageCostInput !== '' && (!is_numeric($averageCostInput) || (float)$averageCostInput < 0))){
        prnMsg('Enter a valid adjustment quantity, unit of measure, store and average cost.','warn');
    }else{
        $itemResult = DB_query("SELECT partperunit,averagestock FROM stockmaster WHERE itemcode='".$itemcode."'",$db);
        if(DB_num_rows($itemResult) < 1){
            prnMsg('The selected inventory item could not be found.','warn');
        }else{
            $itemData = DB_fetch_array($itemResult);
            $partPerUnit = (float)$itemData['partperunit'];
            $quantity = (float)$qtyInput;
            if($uom === 'fulqty' && $partPerUnit <= 0){
                prnMsg('This item has an invalid pack size. Set a valid Parts In a Unit value before adjusting full units.','warn');
            }else{
                $baseAdjustment = ($uom === 'fulqty') ? ($quantity * $partPerUnit) : $quantity;
                $averageCost = ($averageCostInput !== '') ? (float)$averageCostInput : (float)$itemData['averagestock'];
                $journalno = GetNextTransNo(0,$db);
                $documentNo = isset($_POST['DocNo']) ? str_replace("'", "''", $_POST['DocNo']) : GetNextTransNo(17,$db);
                $periodNo = GetPeriod(Today(),$db);
                $currency = str_replace("'", "''", $_SESSION['CompanyRecord']['currencydefault']);

                DB_Txn_Begin($db);

                if($averageCostInput !== '' && (float)$averageCostInput > 0){
                    DB_query("UPDATE stockmaster SET averagestock='".sprintf('%.6F',$averageCost)."' WHERE itemcode='".$itemcode."'",$db);
                }

                DB_query("INSERT INTO stockledger
                    (date,stname,doctyp,itemcode,invref,fulqty,loosqty,price,store,journal,period)
                    VALUES (SYSDATE(),'Stock Adjust','17','".$itemcode."','".$documentNo."',
                    ".sprintf('%.6F',($uom === 'fulqty' ? $quantity : 0)).",
                    ".sprintf('%.6F',($uom === 'loosqty' ? $quantity : 0)).",
                    ".sprintf('%.6F',$averageCost).",'".$store."','".$journalno."','".$periodNo."')",$db);

                $fifoValue = AdjustStockRegisterForCount($itemcode,$baseAdjustment,$averageCost,$journalno,$documentNo);
                if($fifoValue === false){
                    DB_Txn_Rollback($db);
                    prnMsg('The FIFO register does not contain enough costed stock to post this shortage. Reconcile the FIFO layers before posting the adjustment.','warn');
                }else{
                    $amountSql = sprintf('%.6F',abs($fifoValue));
                    DB_query("INSERT INTO Generalledger
                        (journalno,Docdate,period,DocumentNo,DocumentType,accountcode,balaccountcode,VATaccountcode,
                         amount,VATamount,currencycode,ExchangeRate,narration)
                        SELECT '".$journalno."',SYSDATE(),'".$periodNo."','".$documentNo."',17,
                            CASE WHEN ".sprintf('%.6F',$baseAdjustment)." > 0
                                 THEN inventorypostinggroup.balancesheet
                                 ELSE inventorypostinggroup.stockvariance END,
                            CASE WHEN ".sprintf('%.6F',$baseAdjustment)." > 0
                                 THEN inventorypostinggroup.stockvariance
                                 ELSE inventorypostinggroup.balancesheet END,
                            '',".$amountSql.",0,'".$currency."',1,
                            CONCAT(RTRIM(stockmaster.descrip),' - Manual adjustment ',
                                '".str_replace("'","''",$qtyInput)."',' ',
                                '".($uom === 'fulqty' ? 'full units' : 'loose units')."')
                        FROM stockmaster
                        JOIN inventorypostinggroup ON inventorypostinggroup.code=stockmaster.postinggroup
                        WHERE stockmaster.itemcode='".$itemcode."'",$db);

                    if(DB_error_no($db)>0){
                        DB_Txn_Rollback($db);
                        prnMsg('The stock adjustment could not be posted. No stock change was saved.','warn');
                    }else{
                        DB_Txn_Commit($db);
                        GetStockRegister($itemcode);
                        prnMsg('The stock adjustment has been posted.');
                        $_POST['DocNo'] = GetNextTransNo(17,$db);
                    }
                }
            }
        }
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