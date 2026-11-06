<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Inventory Maintenace');

$mbflag = array();
$mbflag[1]='Stock type';
$mbflag[2]='Consumable Non-stock';
$mbflag[3]='Phantom';
$mbflag[4]='Assemble';
$mbflag[5]='Kit';
$mbflag[6]='Container';
$thispage = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;

if(isset($_POST['importexcel']) && !(isset($_GET['downloadtemplate']) && $_GET['downloadtemplate'] == '1')){
    require_once 'vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
    require_once 'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php';
    require_once 'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Reader/Xlsx.php';
    require_once 'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Reader/Xls.php';
    require_once 'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Writer/Xlsx.php';
}

if(isset($_GET['downloadtemplate']) && $_GET['downloadtemplate'] == '1'){
    try{
        require_once 'vendor/autoload.php';
        $spreadsheet = new Spreadsheet();
        $inventory = $spreadsheet->getActiveSheet();
        $inventory->setTitle('Inventory List');
        $inventory->fromArray(array(array('Barcode','StockName','RetailPrice','GroupCode','PartsPerUnit','FullUnitCode','LooseUnitCode','CategoryID','ContainerItemCode')),NULL,'A1');
        $inventory->getStyle('A1:I1')->applyFromArray(array(
            'font'=>array('bold'=>true,'color'=>array('rgb'=>'FFFFFF')),
            'fill'=>array('fillType'=>'solid','startColor'=>array('rgb'=>'1F4E78')),
            'alignment'=>array('horizontal'=>'center','vertical'=>'center','wrapText'=>true),
            'borders'=>array('bottom'=>array('style'=>'medium','color'=>array('rgb'=>'17365D')))
        ));
        $inventory->getRowDimension(1)->setRowHeight(30);
        $widths = array('A'=>18,'B'=>34,'C'=>15,'D'=>17,'E'=>15,'F'=>17,'G'=>17,'H'=>14,'I'=>22);
        foreach($widths as $column=>$width){ $inventory->getColumnDimension($column)->setWidth($width); }
        for($row=2;$row<=101;$row++){
            if($row % 2 === 0){
                $inventory->getStyle('A'.$row.':I'.$row)->getFill()->setFillType('solid')->getStartColor()->setRGB('F2F6FA');
            }
        }
        $inventory->getStyle('C2:C101')->getNumberFormat()->setFormatCode('#,##0.00');
        $inventory->getStyle('E2:E101')->getNumberFormat()->setFormatCode('0.####');
        $inventory->freezePane('A2');
        $inventory->setAutoFilter('A1:I101');
        $inventory->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $inventory->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $inventory->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
        $inventory->getPageSetup()->setFitToPage(true);
        $inventory->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1,1);
        $inventory->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.25)->setRight(0.25);
        $inventory->getHeaderFooter()->setOddFooter('&CInventory List - Page &P of &N');
        $inventory->getPageSetup()->setPrintArea('A1:I101');

        $codes = $spreadsheet->createSheet();
        $codes->setTitle('Code Reference');
        $codes->setShowGridlines(false);
        $codes->mergeCells('A1:F1');
        $codes->setCellValue('A1','LIVE ERP CODE REFERENCE');
        $codes->getStyle('A1:F1')->applyFromArray(array(
            'font'=>array('bold'=>true,'size'=>16,'color'=>array('rgb'=>'FFFFFF')),
            'fill'=>array('fillType'=>'solid','startColor'=>array('rgb'=>'1F4E78')),
            'alignment'=>array('horizontal'=>'center','vertical'=>'center')
        ));
        $codes->getRowDimension(1)->setRowHeight(30);
        $codes->mergeCells('A2:F2');
        $codes->setCellValue('A2','Generated from live ERP records at download time. Enter the exact code in the matching column on the Inventory List sheet.');
        $codes->getStyle('A2:F2')->getAlignment()->setWrapText(true);
        $codes->getRowDimension(2)->setRowHeight(32);

        $codes->mergeCells('A3:F3');
        $codes->setCellValue('A3','GROUP CODE identifies the inventory posting and accounting setup. CATEGORY ID classifies the item for inventory reporting. They are different values and must not be interchanged.');
        $codes->getStyle('A3:F3')->getFont()->setBold(true);
        $codes->getStyle('A3:F3')->getAlignment()->setWrapText(true);
        $codes->getRowDimension(3)->setRowHeight(34);

        $sectionStyle=array('font'=>array('bold'=>true,'color'=>array('rgb'=>'FFFFFF')),'fill'=>array('fillType'=>'solid','startColor'=>array('rgb'=>'1F4E78')),'alignment'=>array('horizontal'=>'left','vertical'=>'center'));
        $tableHeaderStyle=array('font'=>array('bold'=>true,'color'=>array('rgb'=>'FFFFFF')),'fill'=>array('fillType'=>'solid','startColor'=>array('rgb'=>'4472C4')),'alignment'=>array('horizontal'=>'center','vertical'=>'center','wrapText'=>true));

        $codes->fromArray(array(
            array('FIELD','WHAT TO ENTER'),
            array('GroupCode','Use an existing inventory posting group code configured in the back office. This controls the inventory, sales, purchase and VAT account mappings.'),
            array('CategoryID','Use the existing stock category ID. This classifies the item for inventory reporting; it is not an accounting posting group.'),
            array('FullUnitCode','Use an existing unit-of-measure code for the full selling or purchasing unit.'),
            array('LooseUnitCode','Use an existing unit-of-measure code for the base or loose unit.'),
            array('PartsPerUnit','Enter how many loose/base units make up one full unit. Leave blank for a single-unit item; the importer defaults it to 1.'),
            array('ContainerItemCode','For container items, enter the item code of an existing stock item configured as a container. Otherwise leave blank.'),
            array('Important','This template is generated without querying reference tables. Use the codes already configured in the application. The import preview validates the entered codes before anything is imported.')
        ),NULL,'A4');
        $codes->getStyle('A4:B4')->applyFromArray($tableHeaderStyle);
        $codes->getStyle('A5:B'.($codes->getHighestRow()))->getAlignment()->setVertical('top')->setWrapText(true);
        $codes->getStyle('A11:B11')->getFont()->setBold(true);
        $codes->getRowDimension(5)->setRowHeight(42);
        $codes->getRowDimension(6)->setRowHeight(42);
        $codes->getRowDimension(7)->setRowHeight(32);
        $codes->getRowDimension(8)->setRowHeight(32);
        $codes->getRowDimension(9)->setRowHeight(42);
        $codes->getRowDimension(10)->setRowHeight(42);
        $codes->getRowDimension(11)->setRowHeight(50);

        $codeWidths=array('A'=>24,'B'=>34,'C'=>34,'D'=>34,'E'=>34,'F'=>16);
        foreach($codeWidths as $column=>$width){ $codes->getColumnDimension($column)->setWidth($width); }
        $codes->getStyle('A4:B'.max(4,$codes->getHighestRow()))->getAlignment()->setVertical('top')->setWrapText(true);
        $codes->freezePane('A5');
        $codes->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $codes->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $codes->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
        $codes->getPageSetup()->setFitToPage(true);
        $codes->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1,4);
        $codes->getPageMargins()->setTop(0.45)->setBottom(0.45)->setLeft(0.35)->setRight(0.35);
        $codes->getHeaderFooter()->setOddFooter('&CLive ERP Code Reference - Page &P of &N');
        $codes->setPrintArea('A1:B'.max(4,$codes->getHighestRow()));
        $spreadsheet->setActiveSheetIndex(0);
        if(ob_get_length()){ ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="stock_catalogue_import.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }catch(\Throwable $e){
        error_log('Inventory XLSX template generation failed: '.$e->getMessage());
        if(!headers_sent()){
            while(ob_get_level() > 0){ ob_end_clean(); }
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
        }
        die('The Excel workbook could not be generated. Check the PHP error log for the underlying error.');

    }
}

include('includes/header.inc');

if(!function_exists('Triger_stockmaster')){
    function Triger_stockmaster($descript){
        global $db;
        $description = trim((string)$descript);
        $prefix = substr($description,0,2);
        if($prefix === ''){
            $prefix = 'ST';
        }

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
    unset($_SESSION['stock_import_preview']);
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
                $reader = ($extension === 'xls') ? new Xls() : new Xlsx();
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($fileTmp);
                $worksheet = $spreadsheet->getActiveSheet();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($worksheet->getHighestDataColumn());
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
                        if(implode('',array_values($values)) === ''){
                            continue;
                        }

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
                                $check->bind_param("s",$postingGroup);
                                $check->execute();
                                $check->store_result();
                                if($check->num_rows < 1){ $errors[] = 'Inventory posting group does not exist.'; }
                                $check->close();
                            }else{
                                $errors[] = 'Could not validate inventory posting group.';
                            }
                        }
                        foreach(array($fullUnit,$looseUnit) as $unitCode){
                            if($unitCode !== '0'){
                                $check = $mysqli->prepare("SELECT code FROM unit WHERE code=? LIMIT 1");
                                if($check){
                                    $check->bind_param("s",$unitCode);
                                    $check->execute();
                                    $check->store_result();
                                    if($check->num_rows < 1){ $errors[] = 'Unit code '.$unitCode.' does not exist.'; }
                                    $check->close();
                                }else{
                                    $errors[] = 'Could not validate unit codes.';
                                }
                            }
                        }
                        if($category !== ''){
                            $check = $mysqli->prepare("SELECT categoryid FROM stockcategory WHERE categoryid=? LIMIT 1");
                            if($check){
                                $check->bind_param("s",$category);
                                $check->execute();
                                $check->store_result();
                                if($check->num_rows < 1){ $errors[] = 'Category ID does not exist.'; }
                                $check->close();
                            }else{
                                $errors[] = 'Could not validate category.';
                            }
                        }
                        if($container !== '0'){
                            $check = $mysqli->prepare("SELECT itemcode FROM stockmaster WHERE itemcode=? AND isstock='6' LIMIT 1");
                            if($check){
                                $check->bind_param("s",$container);
                                $check->execute();
                                $check->store_result();
                                if($check->num_rows < 1){ $errors[] = 'Container item code is not a valid container inventory item.'; }
                                $check->close();
                            }else{
                                $errors[] = 'Could not validate container item.';
                            }
                        }
                        if($barcode !== ''){
                            if(isset($seenBarcodes[$barcode])){
                                $errors[] = 'Barcode is duplicated in this Excel file.';
                            }else{
                                $seenBarcodes[$barcode] = true;
                                $check = $mysqli->prepare("SELECT itemcode FROM stockmaster WHERE barcode=? LIMIT 1");
                                if($check){
                                    $check->bind_param("s",$barcode);
                                    $check->execute();
                                    $check->store_result();
                                    if($check->num_rows > 0){ $errors[] = 'Barcode already exists in the inventory catalogue.'; }
                                    $check->close();
                                }else{
                                    $errors[] = 'Could not check for an existing barcode.';
                                }
                            }
                        }

                        $preview[] = array(
                            'barcode'=>$barcode,
                            'description'=>$description,
                            'price'=>(float)$priceText,
                            'postinggroup'=>$postingGroup,
                            'partperunit'=>(float)$partPerUnitText,
                            'units'=>$fullUnit,
                            'subunits'=>$looseUnit,
                            'category'=>$category,
                            'container'=>$container,
                            'errors'=>$errors
                        );
                    }
                    if(count($preview) < 1){
                        $importError = 'No inventory rows were found in the Excel file.';
                    }else{
                        $_SESSION['stock_import_preview'] = $preview;
                    }
                }
            }catch(\Exception $e){
                $importError = 'The Excel file could not be read: '.$e->getMessage();
            }
        }
    }
    if($importError !== ''){
        prnMsg($importError,'warn');
    }
}

if(isset($_POST['confirmimport'])){
    $preview = isset($_SESSION['stock_import_preview']) ? $_SESSION['stock_import_preview'] : array();
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
                if(count($row['errors']) > 0){
                    $failed[] = $row['barcode'].': '.implode(' ',$row['errors']);
                    continue;
                }
                $check = $mysqli->prepare("SELECT itemcode FROM stockmaster WHERE barcode=? LIMIT 1");
                if(!$check){
                    $failed[] = $row['barcode'].': could not re-check duplicate barcode.';
                    continue;
                }
                $check->bind_param("s",$row['barcode']);
                $check->execute();
                $check->store_result();
                $duplicate = ($check->num_rows > 0);
                $check->close();
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
                if($insert->execute()){
                    $inserted++;
                }else{
                    $failed[] = $row['barcode'].': '.$insert->error;
                }
            }
            $insert->close();
            if($inserted > 0){
                prnMsg($inserted.' inventory item(s) imported successfully.');
            }
            if(count($failed) > 0){
                prnMsg('Some rows were not imported: '.htmlspecialchars(implode(' | ',$failed),ENT_QUOTES,'UTF-8'),'warn');
            }
            unset($_SESSION['stock_import_preview']);
        }
        }
    }
}

if(isset($_POST['submit'])){
    $requiredFields = [
        'descrip' => 'Enter Desription',
        'partperunit' => 'select parts in a unit ',
        'postinggroup' => 'Select Posting Group'
    ];
     
    $ERROR = 0;
    foreach ($requiredFields as $field => $errorMsg) {
        if (!isset($_POST[$field]) || mb_strlen($_POST[$field]) == 0) {
            echo "$errorMsg</br>";
            $ERROR++;
        }
    }
      
   if($ERROR ==0){
        if($_POST['submit']=='Add New Inventory'){

            $ID = Triger_stockmaster($_POST['descrip']);

            $sql=sprintf("INSERT INTO [stockmaster]
                ([itemcode],[isstock],[barcode],[descrip] ,[postinggroup]
                ,[averagestock],[partperunit] ,[reorderlevel]
                ,[eoq] ,[category] ,[units] ,[inactive],[nextserialno]
                ,[container],[subunits],[sellingprice])
          VALUES
                ('%s' ,'%s' ,'%s' ,'%s' ,'%s',0,'%s' ,%f ,%f ,'%s' ,'%s',%s ,1,'%s','%s','%s')",
                $ID, $_POST['isstock'],$_POST['barcode'] ,$_POST['descrip'] ,$_POST['postinggroup']
                ,(($_POST['isstock']==5)?1:$_POST['partperunit'])
                ,$_POST['reorderlevel'],$_POST['eoq'] ,$_POST['category'] ,$_POST['units'] ,$_POST['inactive']
                ,$_POST['container'] ,$_POST['subunits'] ,$_POST['sellingprice']);


        }

        if($_POST['submit']=='Update Inventory'){

          $sql=sprintf("UPDATE [stockmaster]
             SET [isstock] = '%s'
                ,[barcode] = '%s'
                ,[descrip] = '%s'
                ,[postinggroup] = '%s'
                ,[reorderlevel] = %f
                ,[eoq] = %f
                ,[category] = '%s'
                ,[units] = '%s'
                ,[inactive] = '%s'
                ,[container]='%s'
                ,[subunits]='%s'
                ,[sellingprice]= %f
            WHERE [itemcode] = '%s' ",
                   $_POST['isstock'] ,$_POST['barcode'],$_POST['descrip'] ,$_POST['postinggroup'] ,$_POST['reorderlevel'],$_POST['eoq'] 
                  ,$_POST['category'],$_POST['units'] ,$_POST['inactive'],$_POST['container'],$_POST['subunits'],$_POST['sellingprice']
                  ,$_POST['StockID']);
        }

        if(!isset($_POST['postinggroup'])){
            prnMsg('You cannot save this inventory because you have not selected an "Inventory Posting Group"');
        }else{
            $ErrMsg = _('The inventory alterations could not be processed because');
            $DbgMsg = _('The SQL that was used to update the inventory and failed was');
            $result = DB_query($sql,$db, $ErrMsg, $DbgMsg);
        }
        unset($_POST);
  }
  
}

if(isset($_POST['delete'])){
    
    $sql=sprintf("select * from stockledger where itemcode='%s' ",$_POST['StockID']);
    $ResultIndex=DB_query($sql,$db);
    if(DB_num_rows($ResultIndex)==0){
        DB_query("Delete from stockmaster where itemcode='".$_POST['StockID']."'", $db);
    }else{
         prnMsg('You cannot delete this inventory because it has entries');
     }
}


if (isset($_GET['StockID'])) {
    $sql=sprintf("select [isstock]
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
      ,[container]
      ,[subunits]
      ,[sellingprice]
  FROM [stockmaster] where itemcode='%s' ",$_GET['StockID']);
    $ResultIndex=DB_query($sql,$db);
    while($rowsk=DB_fetch_array($ResultIndex)){
        $_POST['isstock']=$rowsk['isstock'];
        $_POST['barcode']=$rowsk['barcode'];
        $_POST['itemcode']=$rowsk['itemcode'];
        $_POST['descrip']=$rowsk['descrip'];
        $_POST['postinggroup']=$rowsk['postinggroup'];
        $_POST['averagestock']=$rowsk['averagestock'];
        $_POST['partperunit']=$rowsk['partperunit'];
        $_POST['reorderlevel']=$rowsk['reorderlevel'];
        $_POST['eoq']=$rowsk['eoq'];
        $_POST['category']=$rowsk['category'];
        $_POST['units']=$rowsk['units'];
        $_POST['inactive']=$rowsk['inactive'];
        $_POST['container']=$rowsk['container'];
        $_POST['subunits']=$rowsk['subunits'];
        $_POST['sellingprice']=$rowsk['sellingprice'];
         
    }
    
}

echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . _('Inventory Maintenance') . '" alt="" />' . ' ' . _('Inventory Maintenance') . '</p>';

echo '<div class="alert alert-info" style="margin:12px 0;">Excel columns: A Barcode, B StockName, C RetailPrice, D GroupCode, E PartsPerUnit, F FullUnitCode, G LooseUnitCode, H CategoryID, I ContainerItemCode. Columns E-I are optional for single-unit items; PartsPerUnit defaults to 1. For multi-pack items, provide the pack size and both unit codes.</div>';

if(isset($_SESSION['stock_import_preview']) && is_array($_SESSION['stock_import_preview'])){
    $previewRows = $_SESSION['stock_import_preview'];
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
echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"  enctype="multipart/form-data" id="inventory">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if (isset($_GET['StockID'])) {
    echo '<input type="hidden" name="StockID" value="' . $_GET['StockID'] . '" />';
}

echo '<table class="table table-bordered"><tr><td valign="top">'
    . '<table class="table-bordered">';

echo '<tr><td>' . _('Barcode').'</td><td><input type="text" name="barcode" maxlength="20"  value="'.$_POST['barcode'].'"/></td></tr>';
echo '<tr><td>' . _('Name').'</td><td><input type="text" name="descrip" maxlength="100"  value="'.$_POST['descrip'].'"/></td></tr>';
  if (!isset($_GET['StockID'])) {
   echo '<tr><td>' . _('Parts In a unit(Can not be altered later)').'</td><td><input type="text" name="partperunit" maxlength="10" class="integer"  value="'.$_POST['partperunit'].'"/></td></tr>';
  }else{
 echo '<tr><td>' . _('Parts In a unit').'</td><td><input type="text" name="partperunit" readonly="readonly" class="integer"  value="'.$_POST['partperunit'].'"/></td></tr>';
  }
  
echo '<tr><td>' . _('Retail Price').'</td><td><input type="text" name="sellingprice" maxlength="10" class="integer"  value="'.$_POST['sellingprice'].'"/></td></tr>';

echo '<tr><td>' . _('Reorder Level in units').'</td><td><input type="text" name="reorderlevel" maxlength="10" class="integer"  value="'.$_POST['reorderlevel'].'"/></td></tr>';
echo '<tr><td>' . _('EOQ in units').'</td><td><input type="text" name="eoq" maxlength="10" class="integer"  value="'.$_POST['eoq'].'"/></td></tr>';
echo '<tr><td>'._('Units of measure').'</td><td>';
      
    $result=DB_query("SELECT [code],[descrip] FROM unit",$db);
  	if (DB_num_rows($result)==0){
        echo '<tr><td colspan="2">' .
                '<p>'. _('There are no Units of measure currently defined <br /> go to the setup tab of the main menu and set at least one up first').'</p></td>
                </tr>';
	} else {
        echo '<select name="units"><option value="0">Not Applicable</option>';
        while ($myrow = DB_fetch_array($result)) {
                    echo '<option value="'.$myrow['code'].'" ' . ($myrow['code']==$_POST['units']?'selected="selected"':''). '>' . $myrow['descrip'] . '</option>';
		} //end while loop
        echo '</select>' ;
        }      
     echo  '</td></tr>';   
echo '<tr><td>'._('Sub Units of measure').'</td><td>';
      
    $result=DB_query("SELECT [code],[descrip] FROM unit",$db);
  	if (DB_num_rows($result)==0){
        echo '<tr><td colspan="2">' .
                '<p>'. _('There are no Units of measure currently defined <br /> go to the setup tab of the main menu and set at least one up first').'</p></td>
                </tr>';
	} else {
        echo '<select name="subunits"><option value="0">Not Applicable</option>';
        while ($myrow = DB_fetch_array($result)) {
                    echo '<option value="'.$myrow['code'].'" ' . ($myrow['code']==$_POST['subunits']?'selected="selected"':''). '>' . $myrow['descrip'] . '</option>';
		} //end while loop
        echo '</select>' ;
        }      
     echo  '</td></tr>';        
echo  '</table></td><td><table class="table-bordered">';

 echo '<tr><td>' . _('Classification').'</td><td>';
 
    
  echo '<select name="isstock">';
        foreach ($mbflag as $key => $value) {
        	echo '<option value="'.$key.'" '. ($key==$_POST['isstock']?'selected="selected"':''). '>' . $value . '</option>';
	} //end while loop
echo '</select>' ;


echo  '</td></tr>';

  echo '<tr><td>' . _('Obsolete').'</td><td>';
        echo '<select name="inactive">'
                . '<option  '.($_POST['inactive']==0?'selected="selected"':'').' value="0">No</option>'
                . '<option  '.($_POST['inactive']==1?'selected="selected"':'').' value="1">Yes</option>'
                . '</select>';
        echo  '</td></tr>';
  
        echo '<tr><td>'._('Inventory Posting Group').'</td><td>';
      
    $result=DB_query("SELECT code FROM inventorypostinggroup",$db);
  	if (DB_num_rows($result)==0){
		echo '<tr><td colspan="2">' .
                        '<p>'. _('There are no posting groups currently defined <br /> go to the setup tab of the main menu and set at least one up first').'</p></td>
			</tr>';
	} else {
        echo '<select name="postinggroup">';
        while ($myrow = DB_fetch_array($result)) {
		echo '<option value="'.$myrow['code'].'" '. ($myrow['code']==$_POST['postinggroup']?'selected="selected"':''). '>' . $myrow['code'] . '</option>';
		} //end while loop
        echo '</select>' ;
        }      
     echo  '</td></tr>';

     
  echo '<tr><td>'._('Category').'</td><td>';
      
    $result=DB_query("SELECT [categoryid],[categorydescription] FROM stockcategory",$db);
  	if (DB_num_rows($result)==0){
		echo '<tr><td colspan="2">' .
                        '<p>'. _('There are no Categories currently defined <br /> go to the setup tab of the main menu and set at least one up first').'</p></td>
			</tr>';
	} else {
        echo '<select name="category">';
        while ($myrow = DB_fetch_array($result)) {
		echo '<option value="'.$myrow['categoryid'].'" ' . ($myrow['categoryid']==$_POST['category']?'selected="selected"':''). '>' . $myrow['categorydescription'] . '</option>';
		} //end while loop
        echo '</select>' ;
        }      
     echo  '</td></tr>';   
     
echo '</table></td></tr>'
     . '<tr><td><table class="table table-bordered"><tr><td>' . _('Is this inventory packed').'</td></tr>';

echo '<tr><td>'._('Select the Package for Item if any').'</td><td>';
      
    $result=DB_query("SELECT itemcode,descrip FROM stockmaster where isstock='6'",$db);
        echo '<select name="container"><option value="0">Not Applicable</option>';
        while ($myrow = DB_fetch_array($result)) {
		echo '<option value="'.$myrow['itemcode'].'" '. ($myrow['itemcode']==$_POST['container']?'selected="selected"':''). '>' . $myrow['descrip'] . '</option>';
		} //end while loop
        echo '</select>' ;
        
     echo  '</td></tr>'
        . '</table><td>';
     
       if (!isset($_GET['StockID'])) {
        echo  '<div class="centre">'
            . '<label>Choose Excel File</label><input type="file" name="importfile"  accept=".xls,.xlsx">'
            . '<input type="submit" name="importexcel"  value="'._('Import New Stock list from Excel').'"/>'
            . '<a class="btn btn-default" style="margin-left:8px;" href="index.php?Stocks=1&amp;downloadtemplate=1">Download Excel Inventory Template</a>'
            . '</div>';
          }
     
    echo '</td></td></tr></table>';

	
if (isset($_GET['StockID'])) {
   echo '<div class="centre">
            <input type="submit" name="submit" value="' . _('Update Inventory') . '" />&nbsp;
            <input type="submit" name="delete" value="' . _('Delete Inventory') . '" onclick="return confirm(\'' . _('Are You Sure You Want To Delete?') . '\');" />
        </div>';
}else{
        echo  '<input type="submit" name="submit" value="' . _('Add New Inventory') . '" />
                <input type="Reset" name="Reset" value="' . _('Reset') . '" />';
 }
        
echo '</div></form>' ;
include('includes/footer.inc');

?>