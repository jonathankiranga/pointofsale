<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
require_once 'vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
require_once 'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php';
require_once 'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Reader/Xlsx.php';
$Title = _('Inventory Maintenace');
include('includes/header.inc');

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


 if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Check if a file was uploaded
        if (isset($_FILES['importfile']) && $_FILES['importfile']['error'] == UPLOAD_ERR_OK) {
            $file = $_FILES['importfile'];
            $file_name = $file['name'];
            $file_tmp  = $file['tmp_name'];
           // Check if the file is an Excel file
            $allowed_extensions = array('xls', 'xlsx');
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            if (in_array($file_extension, $allowed_extensions)) {
                // Use a library like PhpSpreadsheet to read the Excel file
                $reader = new Xlsx();
                $spreadsheet = $reader->load($file_tmp);
                $worksheet = $spreadsheet->getActiveSheet();
               // Get the highest column letter
                $highestColumn = $worksheet->getHighestColumn();
        // Convert the highest column letter to a column index number
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        // Check if the column index is less than 4 (for 'D' column which is the 4th column)
                if ($highestColumnIndex < 5) {
                    echo "The Excel file must have exactly 4 columns. Found only ($highestColumnIndex) columns.";
                    echo   "</br>A BarCode"
                         . "</br>B StockName"
                         . "</br>C RetailPrice"
                         . "</br>D GroupCode" ;
                    exit;
                }
               // Continue processing the file if the column count is valid (at least 'D')
               // Process the data from the Excel file
               
                foreach ($worksheet->getRowIterator() as $row) {
                        $rowData = [];
                        foreach ($row->getCellIterator() as $cell) {
                            $rowData[] = $cell->getValue();
                        }
                    
                        $barcode =(string) sprintf("%s",$rowData[0]);
                        $description =(string) sprintf("%s",$rowData[1]);
                        $unit_price =(int) sprintf("%s",$rowData[2]);
                        $PostingGroup =(string) sprintf("%s",$rowData[3]);
                        $ID = Triger_stockmaster($description);
                     // Prepare SQL Insert statement
                        $sql = "INSERT INTO stockmaster (barcode, descrip, sellingprice, postinggroup, subunits ,itemcode,isstock) VALUES (?, ?, ?, ?, 1, ? ,1)";
                        $stmt = $mysqli->prepare($sql);
                     // Check if statement preparation was successful
                        if ($stmt === false) {
                            die("Error preparing statement: " . $mysqli->error);
                        }
                     // Bind parameters to the statement
                        $stmt->bind_param("ssdss", $barcode, $description, $unit_price, $PostingGroup, $ID);
                     // Execute the statement
                        if (!$stmt->execute()) {
                            echo "Error executing statement for barcode $barcode: " . $stmt->error . "<br>";
                        } else {
                            echo "Imported barcode $barcode successfully.<br>";
                        }
                     // Close the statement
                        $stmt->close();
           
                }
 
            } else {
                echo "Invalid file type. Please upload an Excel file.";
            }
        } else {
            echo "No file was uploaded.";
        }
        echo "Data import completed!";
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