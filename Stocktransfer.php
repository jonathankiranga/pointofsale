<?php
include('includes/session.inc');
$Title = _('Stock Transfer');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/inventory.png" title="' . _('Stock Transfer') . '" alt="" />' . ' ' . _('Stock Transfer') . '</p>';

$thispage = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

if(isset($_POST['transfer'])){
    $transferQty = isset($_POST['qty']) ? (float)str_replace(',', '', $_POST['qty']) : 0;
    $transferUOM = isset($_POST['UOM']) ? $_POST['UOM'] : '';
    $itemcode = isset($_POST['itemcode']) ? str_replace("'", "''", trim($_POST['itemcode'])) : '';
    $fromStore = isset($_POST['location']) ? str_replace("'", "''", trim($_POST['location'])) : '';
    $toStore = isset($_POST['locationTo']) ? str_replace("'", "''", trim($_POST['locationTo'])) : '';
    $partPerUnit = getpartperunit($itemcode);
    $availableBaseUnits = getbalance($itemcode,$fromStore);
    $requestedBaseUnits = ($transferUOM === 'fulqty') ? $transferQty * $partPerUnit : $transferQty;

    if($transferQty > 0 && $partPerUnit > 0
        && ($transferUOM === 'fulqty' || $transferUOM === 'loosqty')
        && $fromStore !== '' && $toStore !== ''
        && $fromStore !== $toStore
        && $availableBaseUnits + 0.0000001 >= $requestedBaseUnits){

        $qtySql = sprintf('%.4F',$transferQty);
        $journalno = GetNextTransNo(16,$db);
        $Periodno = GetPeriod($_POST['date'],$db);
        $dateSql = FormatDateForSQL($_POST['date']);
        $fullQty = ($transferUOM === 'fulqty') ? $qtySql : '0';
        $looseQty = ($transferUOM === 'loosqty') ? $qtySql : '0';

        $SQL = array();
        $SQL[] = "INSERT INTO stockledger
            (date,stname,doctyp,itemcode,invref,fulqty,loosqty,price,store,journal,period)
            VALUES ('".$dateSql."','Stock Transfer','16','".$itemcode."','".$journalno."',
                '".(-1 * (float)$fullQty)."','".(-1 * (float)$looseQty)."',0,
                '".$fromStore."','".$journalno."','".$Periodno."')";

        $SQL[] = "INSERT INTO stockledger
            (date,stname,doctyp,itemcode,invref,fulqty,loosqty,price,store,journal,period)
            VALUES ('".$dateSql."','Stock Transfer','16','".$itemcode."','".$journalno."',
                '".$fullQty."','".$looseQty."',0,
                '".$toStore."','".$journalno."','".$Periodno."')";

        DB_Txn_Begin($db);
        foreach($SQL as $value){
            DB_query($value,$db);
        }

        if(DB_error_no($db)>0){
            DB_Txn_Rollback($db);
            prnMsg('The stock transfer could not be posted. No stock movement was saved.','warn');
        }else{
            DB_Txn_Commit($db);
            prnMsg('You have created transfer Document '.$journalno.' successfully. Quantity: '.$transferQty,'success');
            unset($_POST['qty']);
        }
    }else{
        prnMsg('Select different source and destination stores, a valid unit and quantity, and ensure the source store has enough stock.','warn');
    }
}

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';
Echo '<table class="table table-bordered" id="testTable">';
echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '</td></tr><tr><td>SELECT PRODUCT</td>';

  $results=DB_query('Select [itemcode],[descrip] FROM [stockmaster] where (isstock <>3) and [inactive]=0 ', $db);
  echo '<td  colspan="4"><select name="itemcode" required="required" id="stocktransferitemcode">';
  while($rows=DB_fetch_array($results)){
      $selected=($_POST['itemcode']==trim($rows['itemcode']))?'selected="selected"':'';
     echo sprintf('<option value="%s"  '.$selected.'>%s</option>', trim($rows['itemcode']),trim($rows['descrip']));
  } 
  
  echo  '</select></td></tr><tr><td></td><td>From Store';

  $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
       $STOREline ='<select name="location">';
       while($rows=  DB_fetch_array($REsults)){
         $STOREline .= sprintf('<option value="%s" %s >%s</option>',
                   $rows['code'],$_POST['location']==$rows['code']?'selected="selected"':'',$rows['Storename']);
       }
        $STOREline .= '</select>';
        echo $STOREline;
        echo '</td><td>From To';

       $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
       $STOREline ='<select name="locationTo">';
       while($rows=  DB_fetch_array($REsults)){
         $STOREline .= sprintf('<option value="%s" %s >%s</option>',
                   $rows['code'],$_POST['locationTo']==$rows['code']?'selected="selected"':'',$rows['Storename']);
       }
        $STOREline .= '</select>';
        echo $STOREline;
        echo '</td></tr>'; 
       
       $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty "
               . " FROM [stockmaster] left join [unit] f on [stockmaster].[units]=f.code "
               . " left join [unit] l on [stockmaster].[subunits]=l.code "
               . " where itemcode= '".$_POST['itemcode']."'", $db);
       
       $UOMline ='<tr><td>Select UOM</td>'
               . '<td><select name="UOM" required="required" id="UOMstocktransfer">';
        $rows= DB_fetch_row($REsults);
        $UOMline .= sprintf('<option value="fulqty" %s >'.$rows[0].'</option>'
                   . '<option value="loosqty" %s >'.$rows[1].'</option>'
                   ,$_POST['UOM']=='fulqty'?'selected="selected"':'',
                   $_POST['UOM']=='loosqty'?'selected="selected"':'');
       $UOMline .= '</select></td>'
                . '<td><input type="text" class="number" name="qty" value="'.$_POST['qty'].'"   required="required" /></td>';
      echo $UOMline;
       
echo '<tr><td><input type="submit" name="transfer"  value="'._('Transfer Inventory').'"/></td></tr></table>';

echo '</div></form>' ;
   
include('includes/footer.inc');

 function getpartperunit($stcode){
        global $db;
        $sql = "SELECT partperunit FROM stockmaster WHERE itemcode='".str_replace("'", "''", $stcode)."'";
        $result = DB_query($sql,$db);
        if(DB_num_rows($result) < 1){
            return 0;
        }
        $row = DB_fetch_row($result);
        return (float)$row[0];
    }

    function getbalance($stcode,$location,$UOM=''){
        global $db;
        $itemcode = str_replace("'", "''", $stcode);
        $store = str_replace("'", "''", $location);
        $sql = "SELECT
                    COALESCE(SUM(COALESCE(stockledger.fulqty,0) * stockmaster.partperunit),0)
                    + COALESCE(SUM(COALESCE(stockledger.loosqty,0)),0) AS baseunits
                FROM stockmaster
                LEFT JOIN stockledger
                    ON stockledger.itemcode = stockmaster.itemcode
                    AND stockledger.store = '".$store."'
                WHERE stockmaster.itemcode = '".$itemcode."'
                GROUP BY stockmaster.partperunit";
        $result = DB_query($sql,$db);
        if(DB_num_rows($result) < 1){
            return 0.0;
        }
        $row = DB_fetch_row($result);
        return (float)$row[0];
    }
  
?>