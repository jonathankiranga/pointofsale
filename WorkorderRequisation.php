<?php
include('includes/session.inc');
$Title = _('Work Order Requisation');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('transactions/stockbalance.inc');
DB_query("[update_tank_balance]",$db);


if($_POST['Maxunit']>0){
    
    if(isset($_POST['Trnasfer'])  and $_POST['units']>0){
        
   DB_Txn_Begin($db);
     
   $Home_Currency = $_SESSION['CompanyRecord']['currencydefault'];
   $journal = GetNextTransNo(0,$db);
   $PeriodNo = GetPeriod($_POST['Date'],$db,false);
   $sql = array();
    
   $sql[]=sprintf("INSERT INTO [tanktrans]
           ([tankname]
           ,[units]
           ,[uom]
           ,[date]
           ,[batchno]
           ,[variance]
           ,[doctype]
           ,[itemcode])
     VALUES
           ('%s'
           ,%f
           ,'%s'
           ,'%s'
           ,'%s'
           ,0
           ,26
           ,'%s')",
           $_POST['wotank'],
           $_POST['units'],
           $_POST['UOM']['R'],
           FormatDateForSQL($_POST['Date']),
           $_POST['docno'],
           $_POST['Prod_itemcode']);
   
   $sql[]="INSERT [stockledger]
                    ([date]
                    ,[stname]
                    ,[doctyp]
                    ,[itemcode]
                    ,[invref]
                    ,[netamt]
                    ,[vat]
                    ,[amount]
                    ,fulqty
                    ,[loosqty]
                    ,[price]
                    ,[store]
                    ,[journal]
                    ,[period])
                    (SELECT 
                        [tanktrans].[date]
                       ,[tanktrans].[tankname]
                       ,'26'
                       ,'".$_POST['Prod_itemcode']."'
                       ,[tanktrans].[batchno]
                       ,0
                       ,0
                       ,0
                       ,case [tanktrans].UOM when 'fulqty'  
                       then ([tanktrans].[units]*-1) 
                       else 0 
                       end as fulqty
                       ,case [tanktrans].UOM when 'loosqty' 
                       then ([tanktrans].[units]*-1) 
                       else 0 
                       end as loosqty
                       ,0
                       ,'".$_POST['location']['R']."'
                       ,'".$journal."'
                       ,'".$PeriodNo."'
                    FROM [tanktrans]  where 
                    [batchno]='".$_POST['docno']."')";
   
        
                    if($_POST['averagecost']>0){ 
                        $mssql="SELECT [itemcode],[balance],[tankname] FROM [ProductionUnit]  where [tankname]='".$_POST['wotank']."'";
                        $ResultIndex = DB_query($mssql,$db);  $row = DB_fetch_row($ResultIndex);  $Tank_stock_ID = $row[0];
                            
                        $sql[] = "Update [stockmaster] set [averagestock]=".$_POST['averagecost']."  where itemcode='".$Tank_stock_ID."'";

                        $sql[] = " INSERT INTO [StockRegister] ([itemcode],[StockIn],[cost],[StockOut],[journal],[GRN])  VALUES  ('".$Tank_stock_ID."', ".$_POST['units']." ,".$_POST['averagecost'].", 0 , '".$journal."' ,'".$_POST['docno']."')";
                     }
   
                    foreach ($sql as $value) {
                      DB_query($value,$db);
                    }
   
  
                    if(DB_error_no($db)>0){
                        DB_Txn_Rollback($db);
                    }else{
                        DB_Txn_Commit($db);
                        prnMsg('You have successfuly tranfered');
                        
                        DB_query("[update_tank_balance]",$db);

                        unset($_POST);
                    }
   
   
}
}

if(isset($_GET['new'])){
    $_POST['docno'] = GetNextTransNo(26, $db);
}

if(!isset($_POST['Date'])){
    $ResultIndex = db_query("select sysdate() as date",$db);
    $row = DB_fetch_row($ResultIndex);
    $_POST['Date']= ConvertSQLDate($row[0]);
}

echo '<p class="page_title_text">' . '<img src="css/'.$Theme.'/images/inventory.png" title="' . _('Work Order Requisation') .'" alt="" />' . ' ' . _('Work Order Requisation') . '</p>';

echo '<form action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="table table-bordered">';
echo '<tr><td colspan="4"><a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?new=1">Click here to Create New Work Order Number Refence</a></td></tr>';

echo '<tr><td>Work order Requisation No</td><td colspan="3"><input type="text" name="docno" value="'.$_POST['docno'].'" required="required" size="11" maxlength="10"/></td></tr>';
echo '<tr><td>Date</td><td colspan="3"><input type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="Date" value="'.$_POST['Date'].'" required="required" size="11" maxlength="10"/></td></tr>';
echo '<tr><td>Select Product</td>'
   . '<td><select name="Prod_itemcode" required="required" onchange="ReloadForm(salesform.submit)">';
     GetProductOption();
echo '</select></td></tr>';

 $sql="SELECT [averagestock] FROM [stockmaster]  where [itemcode]='".$_POST['Prod_itemcode']."'";
       $ResultIndex = DB_query($sql,$db);
       $row = DB_fetch_row($ResultIndex);
       $averagecost = $row[0];

echo '<tr><td>Select Issuing Store</td><td>';
echo createstores('R');
echo '</td><td>Select Receiving Tank</td>'
    . '<td><select name="wotank" required="required" onchange="ReloadForm(salesform.submit)">';
     GetwoTank();
echo '</select></td></tr>';


if(isset($_POST['wotank'])){
    
$sql="SELECT [itemcode],[balance],[tankname] FROM [ProductionUnit] where [tankname]='".$_POST['wotank']."'";
$ResultIndex = DB_query($sql,$db);
$row = DB_fetch_row($ResultIndex);
$Tank_stock_ID = $row[0];

echo '<tr><td>Select UOM</td><td>';
echo CreatepageUnits($_POST['Prod_itemcode'],'R');
echo '</td></tr>';

 if(isset($_POST['UOM']['R'])){
        $storebalance = getbalance($_POST['Prod_itemcode'],$_POST['location']['R'],$_POST['UOM']['R']);

        echo '<tr><td>Stock Balance in the issueing store</td><td class="number" colspan="3">'.$storebalance .'</td></tr>';
        echo '<input type="hidden" name="ItemCode" value="' .$Tank_stock_ID. '" />';

            $sql="SELECT 
                [ProductionUnit].[itemcode], 
                [ProductionUnit].[balance], 
                [ProductionUnit].[tankname], 
                [unit].[descrip],
                [stockmaster].[partperunit],
                [ProductionUnit].[UOM] 
             FROM [ProductionUnit] 
             join stockmaster on ProductionUnit.itemcode=stockmaster.itemcode 
             left join [unit] on 
             (case 
             ProductionUnit.UOM when 'fulqty' 
                then (stockmaster.units) 
                else (stockmaster.subunits) 
             end) = unit.code  where [tankname]='".$_POST['wotank']."'";
            $ResultIndex = DB_query($sql,$db);
            $row = DB_fetch_row($ResultIndex);
            $number =($row[1]);
 

            if($_POST['UOM']['R']=='fulqty' and trim($row[5])=='fulqty'){
                 echo '<tr><td>The Capacity Available in the receiving tank is </td><td class="number" colspan="3">'.$number.'</td></tr>';

             }elseif($_POST['UOM']['R']=='fulqty' and trim($row[5])=='loosqty'){
              $number =($row[1] * $row[4]);
                 echo '<tr><td>The Capacity Available in the receiving tank is </td><td class="number" colspan="3">'.$number.'</td></tr>';

             }elseif($_POST['UOM']['R']=='loosqty' and trim($row[5])=='loosqty'){
                echo '<tr><td>The Capacity Available in the receiving tank is </td><td class="number" colspan="3">'.$number.'</td></tr>';

             }elseif($_POST['UOM']['R']=='loosqty' and trim($row[5])=='fulqty'){
                 $number =($row[1] * $row[4]);
                  echo '<tr><td>The Capacity Available in the receiving tank is </td><td class="number" colspan="3">'.$number.'</td></tr>';
            }

            echo '<input type="hidden" name="Maxunit" value="'.$number.'" /><input type="hidden" name="averagecost" value="'.$averagecost.'" />';
            echo '<tr><td>Issue these number of Units</td><td colspan="3"><input type="number" class="number" name="units" value="'.$_POST['units'].'" max="'.$number.'" min="0" size="5"/>'
                 . ' : Cost :<input type="text"  value="'.$averagecost.'" readonly="readonly" /></td></tr>';
}


}

if(!isset($_POST['location']['R']) or !isset($_POST['UOM']['R'])){
   echo '<tr><td colspan="3"><div><input type="submit" name="submit" value="Next"/></div>';
}else{
  echo '<tr><td colspan="3"><div><input type="submit" name="submit" value="Refresh"/></div>';
}

if($storebalance >0){
   echo '<div><input type="submit" name="Trnasfer" value="Transfer Units to the Receiving Store"/></div>';
}

echo '</td></tr></table>';
echo '</div></form>';

include('includes/footer.inc');



?>