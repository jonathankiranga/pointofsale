<?php
include('includes/session.inc');
$Title = _('Stock Transfer');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/inventory.png" title="' . _('Stock Transfer') . '" alt="" />' . ' ' . _('Stock Transfer') . '</p>';

$thispage = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

if(isset($_POST['transfer'])){
  $SQL=array();
    
  if(getbalance($_POST['itemcode'],$_POST['location'],$_POST['UOM']) >= $_POST['qty']){
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
      prnMsg('You do not have enough units to transfer','warn');
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

 function getbalance($stcode,$location,$UOM=''){
        global $db;
        
       $sql="SELECT  
            sum(fulqty) as funits , 
            sum(loosqty) as lunits ,
            stockmaster.partperunit 
            from stockledger
            join stockmaster on stockledger.itemcode=stockmaster.itemcode 
            where stockledger.itemcode='".$stcode."' "
                . "and stockledger.store='".$location."' "
                . "group by partperunit";        
        
       $ResultIndex=DB_query($sql,$db);
       $rows = DB_fetch_row($ResultIndex);
       if($UOM=='fulqty'){
            $ans = $rows[0];
       }else{
            $ans = ($rows[0] * $rows[2]) + $rows[1];
       }
      
       return number_format($ans,0);
    }
  
?>