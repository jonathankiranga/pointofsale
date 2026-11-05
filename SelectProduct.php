<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Inventory List');
include('includes/header.inc');

$mbflag = array();
$mbflag[1]='Stock type';
$mbflag[2]='Consumable';
$mbflag[3]='Service type';
$mbflag[4]='Assemble';
$mbflag[5]='Kit';
$mbflag[6]='Container';
$thispage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');


if(isset($_GET['StockID'])){
    $_SESSION['StockID']=$_GET['StockID'];
}

if(isset($_GET['newsearch'])){
    unset($_SESSION['StockID']);
}

if(isset($_SESSION['StockID'])){
    $StockID = $_SESSION['StockID'];
}

if(isset($_SESSION['StockID'])){
    
        $ErrMsg = _('The Item name requested cannot be retrieved because');
	$result = DB_query("Select [isstock]
                                    ,[barcode]
                                    ,[itemcode]
                                    ,[descrip]
                                    ,[postinggroup]
                                    ,[averagestock]
                                    ,[partperunit]
                                    ,[reorderlevel]
                                    ,[eoq]
                                    ,[category]
                                    ,[units]
                                    ,[inactive]
                                    ,[nextserialno]
  FROM [stockmaster] where [itemcode]='".$_SESSION['StockID']."'", $db, $ErrMsg);
       
	if ($myrow = DB_fetch_array($result)) {
		$StockName = htmlspecialchars($myrow['descrip'],ENT_QUOTES,'UTF-8',false);
		$ISSTOCK = $mbflag[$myrow['isstock']];
                
	} 
    
    echo '<p class="page_title_text"><img src="css/' . $Theme . '/images/inventory.png" title="' . _('Inventory') . '" alt="" />' . ' ' . _('You have selected Inventory') . ' : ' . $_SESSION['StockID'] . ' - ' . $StockName . ' - ' . $ISSTOCK . '</p>';
    echo '<div class="page_help_text">' . _('Select a menu option to operate using this Inventory') . '.</div><br />';
    echo '<table cellpadding="4" width="90%" class="table table-bordered">
                <tr>
                        <th style="width:33%">' . _('Inventory Inquiries') . '</th>
                        <th style="width:33%">' . _('Inventory Transactions') . '</th>
                        <th style="width:33%">' . _('Inventory Maintenance') . '</th>
                </tr>';
echo '<tr><td valign="top" class="select">';
/*Stock Inquiry Options */
        echo '<a href="StockMovements.php?StockID=' . $StockID . '">' . _('Show Stock Movements') . '</a><br />';
        echo '<a href="PDFPrintSalesOrder.php">' . _('Search Outstanding Sales Orders') . '</a><br />';
     	echo '<a href="Billofmaterial.php?StockID=' . $StockID . '">' . _('View Costed Bill Of Material') . '</a><br />';



echo '</td><td valign="top" class="select">';
/* Stock Transactions */
	echo '<a href="StockAdjustments.php?StockID=' . $StockID . '">' . _('Quantity Adjustments') . '</a><br />';
        
 /* end of ($Its_A_Kitset_Assembly_Or_Dummy == False) */
echo '</td><td valign="top" class="select">';
/* Stock Maintenance Options */
        echo '<a href="Stocks.php">' . _('Insert New Item') . '</a><br />';
        echo '<a href="Stocks.php?StockID=' . $StockID . '">' . _('Modify Item Details') . '</a><br />';
	echo '</td>';
	echo '</tr></table><br />';

    
} else {
    
echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/Inventory.png" title="' . _('Inventory') .
	'" alt="" />' . ' ' . _('Inventory List') . '</p>';

echo '<form><div class="card">
    <table class="table table-bordered">
    <thead>'
        . '<TR>'
        . '<th class="ascending">Stock Code</th>'
        . '<th class="ascending">Inventory Name</th>'
        . '<th>Type</th>'
        . '<th>Is Obsolete</th>'
        . '<th>Pan Zize</th>'
        . '<th>Ave Cost</th>'
        . '<th>Posting Group</th></tr></thead>';
     
$results=DB_query('select 
    stockmaster.isstock,
    stockmaster.itemcode,
    stockmaster.descrip,
    stockmaster.inactive,
    stockmaster.averagestock,
    stockmaster.partperunit,
    stockmaster.[postinggroup],
    unitfull.descrip as Fulqty,
    unitloos.descrip as Loosqty
        from stockmaster 
        left join unit unitfull on stockmaster.units=unitfull.code 
        left join unit unitloos on stockmaster.subunits=unitloos.code order by descrip asc', $db);
       
       $k=0;
       while($rows=DB_fetch_array($results)){
           echo  '<tr>';
           echo sprintf('<td><a href="%s?StockID=%s">%s</a></td>', $thispage, trim($rows['itemcode']),trim($rows['itemcode']));
         
           if($rows['isstock']==3){
             echo '<td><b>'.$rows['descrip'].'</b></td>';
           }else{
             echo '<td><b>'.$rows['descrip'].'</b><i>('.number_format($rows['partperunit'],0).' '.$rows['Loosqty'].' = 1 '.$rows['Fulqty'].')</i></td>';
            }
           
           echo '<td>'. $mbflag[$rows['isstock']].'</td>';
           echo '<td>'. ($rows['inactive']==true?'YES':"NO").'</td>';
           echo '<td>'. number_format($rows['partperunit'],0).'</td>';
           echo '<td>'. number_format($rows['averagestock'],2).'</td>';
           echo '<td>'. trim($rows['postinggroup']).'</td></tr>';
            
       }        

echo '</tbody></table></div></form>';
}

include('includes/footer.inc');


?>
