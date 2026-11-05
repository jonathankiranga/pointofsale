<?php
include('includes/session.inc');
$Title = _('Stock Adjustments');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

$thispage = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

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

if(isset($_GET['StockID'])){
    
        $results=DB_query("Select [itemcode],[descrip],[inactive],[partperunit],[averagestock],[postinggroup]  FROM [stockmaster] where itemcode='".$_GET['StockID']."'", $db);
        $rowsstock=DB_fetch_row($results);
        $DESCRIPTION=$rowsstock[1];
        $AVERAGESTOCK=$rowsstock[4];        
       
       $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty  FROM [stockmaster] left join [unit] f on [stockmaster].[units]=f.code   left join [unit] l on [stockmaster].[subunits]=l.code  where itemcode= '".$_GET['StockID']."'", $db);
       $UOMline ='<select name="UOM" required="required">';
       $rows=  DB_fetch_row($REsults);
        $UOMline .=sprintf('<option value="fulqty" %s >'.$rows[0].'</option>'
                   . '<option value="loosqty" %s >'.$rows[1].'</option>'
                   ,$_POST['UOM'][$pkey]=='fulqty'?'selected="selected"':'',
                   $_POST['UOM'][$pkey]=='loosqty'?'selected="selected"':'');
       $UOMline .= '</select>';
  
       
       $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
       $STOREline ='<select name="location">';
       while($rows=  DB_fetch_array($REsults)){
         $STOREline .= sprintf('<option value="%s" %s >%s</option>',
                   $rows['code'],$_POST['location']==$rows['code']?'selected="selected"':'',$rows['Storename']);
       }
        $STOREline .= '</select>';
       
    echo '<p class="page_title_text">'. $DESCRIPTION. '</p>';
    echo '<input type="hidden" name="StockID" value="' .$_GET['StockID'] .'"/>';
   
    $_POST['DocNo'] = GetNextTransNo(17, $db);
    
    echo '<table class="table table-bordered">'
    . '<tr><td>Document No</td><td><input type="text" name="DocNo" value="'.$_POST['DocNo'].'" size="10" readonly="readonly"/></td></tr>'
            . '<tr><td>Average Cost</td><td><input type="text" name="averagecost" class="number" maxlength="10" size="10" value="'.$AVERAGESTOCK.'"/></td></tr>';
    echo '<tr><td>Store</td><td>';
    echo  $STOREline;
    echo '</td></tr>';
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