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



/* Excel inventory import - shared format and validation rules with Stocks.php. */
if(isset($_POST['importexcel'])){
    require_once 'vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
    require_once 'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php';
    require_once 'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Reader/Xlsx.php';
    require_once 'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Reader/Xls.php';
}
if(!function_exists('Triger_stockmaster')){
    function Triger_stockmaster($descript){
        global $db;
        $description = trim((string)$descript);
        $prefix = substr($description,0,2);
        if($prefix === ''){ $prefix = 'ST'; }
        $result = DB_query('SELECT COUNT(*) FROM stockmaster',$db);
        $row = DB_fetch_row($result);
        $nextNumber = isset($row[0]) ? (int)$row[0] : 0;
        do{
            $code = $prefix.str_pad((string)$nextNumber,6,'0',STR_PAD_LEFT);
            $escapedCode = str_replace("'", "''", $code);
            $check = DB_query("SELECT itemcode FROM stockmaster WHERE itemcode='".$escapedCode."'",$db);
            $exists = DB_num_rows($check) > 0;
            $nextNumber++;
        }while($exists);
        return $code;
    }
}

if(isset($_POST['importexcel'])){
    unset($_SESSION['selectproduct_import_preview']);
    $importError = '';
    if(!isset($_FILES['importfile']) || $_FILES['importfile']['error'] !== UPLOAD_ERR_OK){
        $importError = 'Choose an Excel file before importing.';
    }else{
        $fileName = $_FILES['importfile']['name'];
        $fileTmp = $_FILES['importfile']['tmp_name'];
        $extension = strtolower(pathinfo($fileName,PATHINFO_EXTENSION));
        if(!in_array($extension,array('xls','xlsx'),true)){
            $importError = 'Only .xls and .xlsx files are supported.';
        }else{
            try{
                $reader = ($extension === 'xls')
                    ? new \\PhpOffice\\PhpSpreadsheet\\Reader\\Xls()
                    : new \\PhpOffice\\PhpSpreadsheet\\Reader\\Xlsx();
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($fileTmp);
                $worksheet = $spreadsheet->getActiveSheet();
                $highestColumnIndex = \\PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate::columnIndexFromString($worksheet->getHighestDataColumn());
                if($highestColumnIndex < 4){
                    $importError = 'The Excel file must contain at least columns A-D: Barcode, StockName, RetailPrice and GroupCode.';
                }else{
                    $preview = array();
                    $seenBarcodes = array();
                    foreach($worksheet->getRowIterator() as $row){
                        $rowNumber = $row->getRowIndex();
                        $values = array();
                        foreach(range('A','I') as $column){
                            $values[$column] = trim((string)$worksheet->getCell($column.$rowNumber)->getFormattedValue());
                        }
                        if($rowNumber === 1 && (strtolower($values['A']) === 'barcode' || strtolower($values['B']) === 'stockname')){
                            continue;
                        }
                        if(implode('',array_values($values)) === ''){ continue; }

                        $barcode = $values['A'];
                        $description = $values['B'];
                        $priceText = str_replace(',','',$values['C']);
                        $postingGroup = $values['D'];
                        $partPerUnitText = ($values['E'] === '') ? '1' : $values['E'];
                        $fullUnit = ($values['F'] === '') ? '0' : $values['F'];
                        $looseUnit = ($values['G'] === '') ? '0' : $values['G'];
                        $category = $values['H'];
                        $container = ($values['I'] === '') ? '0' : $values['I'];
                        $errors = array();

                        if($barcode === '' || strlen($barcode) > 20){ $errors[] = 'Barcode is required and must not exceed 20 characters.'; }
                        if($description === '' || strlen($description) > 100){ $errors[] = 'Stock name is required and must not exceed 100 characters.'; }
                        if($priceText === '' || !is_numeric($priceText) || (float)$priceText < 0){ $errors[] = 'Retail price must be a valid non-negative number.'; }
                        if($postingGroup === ''){ $errors[] = 'Inventory posting group is required.'; }
                        if($partPerUnitText === '' || !is_numeric($partPerUnitText) || (float)$partPerUnitText <= 0){ $errors[] = 'PartsPerUnit must be greater than zero.'; }
                        if(is_numeric($partPerUnitText) && (float)$partPerUnitText > 1 && ($fullUnit === '0' || $looseUnit === '0')){
                            $errors[] = 'Pack sizes above 1 require FullUnitCode and LooseUnitCode.';
                        }

                        if($postingGroup !== ''){
                            $check = $mysqli->prepare("SELECT code FROM inventorypostinggroup WHERE code=? LIMIT 1");
                            if($check){
                                $check->bind_param("s",$postingGroup); $check->execute(); $check->store_result();
                                if($check->num_rows < 1){ $errors[] = 'Inventory posting group does not exist.'; }
                                $check->close();
                            }else{ $errors[] = 'Could not validate inventory posting group.'; }
                        }
                        foreach(array($fullUnit,$looseUnit) as $unitCode){
                            if($unitCode !== '0'){
                                $check = $mysqli->prepare("SELECT code FROM unit WHERE code=? LIMIT 1");
                                if($check){
                                    $check->bind_param("s",$unitCode); $check->execute(); $check->store_result();
                                    if($check->num_rows < 1){ $errors[] = 'Unit code '.$unitCode.' does not exist.'; }
                                    $check->close();
                                }else{ $errors[] = 'Could not validate unit codes.'; }
                            }
                        }
                        if($category !== ''){
                            $check = $mysqli->prepare("SELECT categoryid FROM stockcategory WHERE categoryid=? LIMIT 1");
                            if($check){
                                $check->bind_param("s",$category); $check->execute(); $check->store_result();
                                if($check->num_rows < 1){ $errors[] = 'Category ID does not exist.'; }
                                $check->close();
                            }else{ $errors[] = 'Could not validate category.'; }
                        }
                        if($container !== '0'){
                            $check = $mysqli->prepare("SELECT itemcode FROM stockmaster WHERE itemcode=? AND isstock='6' LIMIT 1");
                            if($check){
                                $check->bind_param("s",$container); $check->execute(); $check->store_result();
                                if($check->num_rows < 1){ $errors[] = 'Container item code is not a valid container inventory item.'; }
                                $check->close();
                            }else{ $errors[] = 'Could not validate container item.'; }
                        }
                        if($barcode !== ''){
                            if(isset($seenBarcodes[$barcode])){
                                $errors[] = 'Barcode is duplicated in this Excel file.';
                            }else{
                                $seenBarcodes[$barcode] = true;
                                $check = $mysqli->prepare("SELECT itemcode FROM stockmaster WHERE barcode=? LIMIT 1");
                                if($check){
                                    $check->bind_param("s",$barcode); $check->execute(); $check->store_result();
                                    if($check->num_rows > 0){ $errors[] = 'Barcode already exists in the inventory catalogue.'; }
                                    $check->close();
                                }else{ $errors[] = 'Could not check for an existing barcode.'; }
                            }
                        }
                        $preview[] = array(
                            'barcode'=>$barcode,'description'=>$description,'price'=>(float)$priceText,
                            'postinggroup'=>$postingGroup,'partperunit'=>(float)$partPerUnitText,
                            'units'=>$fullUnit,'subunits'=>$looseUnit,'category'=>$category,
                            'container'=>$container,'errors'=>$errors
                        );
                    }
                    if(count($preview) < 1){
                        $importError = 'No inventory rows were found in the Excel file.';
                    }else{
                        $_SESSION['selectproduct_import_preview'] = $preview;
                    }
                }
            }catch(\\Exception $e){
                $importError = 'The Excel file could not be read: '.$e->getMessage();
            }
        }
    }
    if($importError !== ''){ prnMsg($importError,'warn'); }
}

if(isset($_POST['confirmimport'])){
    $preview = isset($_SESSION['selectproduct_import_preview']) ? $_SESSION['selectproduct_import_preview'] : array();
    if(count($preview) < 1){
        prnMsg('There is no validated Excel import waiting for confirmation. Upload the file again.','warn');
    }else{
        $hasPreviewErrors = false;
        foreach($preview as $previewRow){
            if(count($previewRow['errors']) > 0){ $hasPreviewErrors = true; break; }
        }
        if($hasPreviewErrors){
            prnMsg('The preview contains validation errors. Correct the Excel file and upload it again; no rows were imported.','warn');
        }else{
            $inserted = 0;
            $failed = array();
            $insert = $mysqli->prepare("INSERT INTO stockmaster
                (barcode,descrip,sellingprice,postinggroup,partperunit,units,subunits,category,container,itemcode,isstock,inactive,nextserialno)
                VALUES (?,?,?,?,?,?,?,?,?,?,1,0,1)");
            if(!$insert){
                prnMsg('The inventory import could not be prepared: '.$mysqli->error,'warn');
            }else{
                foreach($preview as $row){
                    $check = $mysqli->prepare("SELECT itemcode FROM stockmaster WHERE barcode=? LIMIT 1");
                    if(!$check){
                        $failed[] = $row['barcode'].': could not re-check duplicate barcode.';
                        continue;
                    }
                    $check->bind_param("s",$row['barcode']); $check->execute(); $check->store_result();
                    $duplicate = ($check->num_rows > 0); $check->close();
                    if($duplicate){
                        $failed[] = $row['barcode'].': barcode already exists; row was not imported.';
                        continue;
                    }
                    $itemcode = Triger_stockmaster($row['description']);
                    if(!$itemcode){
                        $failed[] = $row['barcode'].': item code could not be generated.';
                        continue;
                    }
                    $insert->bind_param("ssdsdsssss",
                        $row['barcode'],$row['description'],$row['price'],$row['postinggroup'],
                        $row['partperunit'],$row['units'],$row['subunits'],$row['category'],
                        $row['container'],$itemcode
                    );
                    if($insert->execute()){ $inserted++; }
                    else{ $failed[] = $row['barcode'].': '.$insert->error; }
                }
                $insert->close();
                if($inserted > 0){ prnMsg($inserted.' inventory item(s) imported successfully.'); }
                if(count($failed) > 0){ prnMsg('Some rows were not imported: '.htmlspecialchars(implode(' | ',$failed),ENT_QUOTES,'UTF-8'),'warn'); }
                unset($_SESSION['selectproduct_import_preview']);
            }
        }
    }
}

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

if(isset($_SESSION['selectproduct_import_preview']) && is_array($_SESSION['selectproduct_import_preview'])){
    $previewRows = $_SESSION['selectproduct_import_preview'];
    $previewHasErrors = false;
    echo '<div class="container"><h3>Excel Import Preview</h3><p>'.count($previewRows).' row(s) found. Review the data before confirming the import.</p>';
    echo '<div style="overflow-x:auto;"><table class="table table-bordered"><thead><tr><th>Barcode</th><th>Stock Name</th><th>Retail Price</th><th>Posting Group</th><th>Parts Per Unit</th><th>Full Unit</th><th>Loose Unit</th><th>Validation</th></tr></thead><tbody>';
    foreach($previewRows as $previewRow){
        $rowHasErrors = count($previewRow['errors']) > 0;
        if($rowHasErrors){ $previewHasErrors = true; }
        echo '<tr><td>'.htmlspecialchars($previewRow['barcode'],ENT_QUOTES,'UTF-8').'</td>';
        echo '<td>'.htmlspecialchars($previewRow['description'],ENT_QUOTES,'UTF-8').'</td>';
        echo '<td>'.number_format($previewRow['price'],2).'</td>';
        echo '<td>'.htmlspecialchars($previewRow['postinggroup'],ENT_QUOTES,'UTF-8').'</td>';
        echo '<td>'.number_format($previewRow['partperunit'],2).'</td>';
        echo '<td>'.htmlspecialchars($previewRow['units'],ENT_QUOTES,'UTF-8').'</td>';
        echo '<td>'.htmlspecialchars($previewRow['subunits'],ENT_QUOTES,'UTF-8').'</td>';
        echo '<td>'.($rowHasErrors ? '<span style="color:#b42318;">'.htmlspecialchars(implode(' ',$previewRow['errors']),ENT_QUOTES,'UTF-8').'</span>' : '<span style="color:#067647;">Ready</span>').'</td></tr>';
    }
    echo '</tbody></table></div>';
    if(!$previewHasErrors){
        echo '<form method="post" action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'">';
        echo '<input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'"/>';
        echo '<button type="submit" name="confirmimport" value="1" class="btn btn-success" onclick="return confirm(\'Import these validated inventory items?\');">Confirm and Import Items</button>';
        echo '</form>';
    }else{
        echo '<p style="color:#b42318;">Correct the errors in the Excel file and upload it again. No rows have been imported.</p>';
    }
    echo '</div>';
}
echo '<div class="alert alert-info" style="margin:12px 0;">Excel columns: A Barcode, B StockName, C RetailPrice, D GroupCode, E PartsPerUnit, F FullUnitCode, G LooseUnitCode, H CategoryID, I ContainerItemCode. Columns E-I are optional for single-unit items; PartsPerUnit defaults to 1. For multi-pack items, provide the pack size and both unit codes.</div>';
echo '<form action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post" enctype="multipart/form-data">';
echo '<input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'"/>';
echo '<label>Choose Excel File</label> <input type="file" name="importfile" accept=".xls,.xlsx" required/> ';
echo '<button type="submit" name="importexcel" value="1">Import New Stock List from Excel</button> ';
echo '<a class="btn btn-default" href="index.php?Stocks=1&amp;downloadtemplate=1">Download Excel Inventory Template</a>';
echo '</form>';
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
