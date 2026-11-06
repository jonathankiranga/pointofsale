<?php
include('includes/session.inc');
$Title = _('Stock Transfer');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/inventory.png" title="' . _('Stock Transfer') . '" alt="" />' . ' ' . _('Stock Transfer') . '</p>';

$thispage = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

if(isset($_POST['transfer'])){
  $SQL=array();
    
  $transferQty = isset($_POST['qty']) ? (float)str_replace(',', '', $_POST['qty']) : 0;
  $transferUOM = isset($_POST['UOM']) ? $_POST['UOM'] : '';
  $partPerUnit = getpartperunit($_POST['itemcode']);
  $availableBaseUnits = getbalance($_POST['itemcode'],$_POST['location']);
  $requestedBaseUnits = ($transferUOM === 'fulqty') ? $transferQty * $partPerUnit : $transferQty;

  if($transferQty > 0 && $partPerUnit > 0
      && ($transferUOM === 'fulqty' || $transferUOM === 'loosqty')
      && $availableBaseUnits + 0.0000001 >= $requestedBaseUnits){
        $journalno = GetNextTransNo(16, $db);
        $Periodno  = GetPeriod($_POST['date'], $db);
   
      $SQL[]="INSERT INTO [stockledger]
           ([date],[stname],[doctyp],[itemcode],[invref],[fulqty],[loosqty],[price],[store],[journal],[period])
            VALUES ('".FormatDateForSQL($_POST['date'])."'
                ,'Stock Transfer' 
                ,'16'
                ,'".$_POST['itemcode']."'"
               . ",'".$journalno."'"
               . ",'".(($_POST['UOM']=='fulqty')?$_POST['qty']* -1:'0')."'"
               . ",'".(($_POST['UOM']=='loosqty')?$_POST['qty']* -1:'0')."'"
               . ",".(0).""
               . ",'".$_POST['location']."'"
               . ",'".$journalno."'"
               . ",'".$Periodno."'"
               . ")";
      
     $SQL[]="INSERT INTO [stockledger]
           ([date],[stname],[doctyp],[itemcode],[invref],[fulqty],[loosqty],[price],[store],[journal],[period])
             VALUES ('".FormatDateForSQL($_POST['date'])."'
                ,'Stock Transfer' 
                ,'16'
                ,'".$_POST['itemcode']."'"
               . ",'".$journalno."'"
               . ",'".(($_POST['UOM']=='fulqty')?$_POST['qty']:'0')."'"
               . ",'".(($_POST['UOM']=='loosqty')?$_POST['qty']:'0')."'"
               . ",".(0).""
               . ",'".$_POST['locationTo']."'"
               . ",'".$journalno."'"
               . ",'".$Periodno."'"
               . ")"; 
     
     
       DB_Txn_Begin($db);
       foreach($SQL as $value) {
           DB_query($value,$db);
       }
       
       if(DB_error_no($db)>0){
           DB_Txn_Rollback($db);
       }else{
           DB_Txn_Commit($db);
           prnMsg('You have created transfer Document '.$journalno .' successfuly Units:'.$_POST['qty'],'succes');
           unset($_POST['qty']);
      }
      
  }else{
      prnMsg('You do not have enough stock available in the selected unit of measure. Check the full and loose unit balance.','warn');
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