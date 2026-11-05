<?php

require_once 'vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
require_once 'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php';
require_once 'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Reader/Xlsx.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

 if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Check if a file was uploaded
        if (isset($_FILES['importfile']) && $_FILES['importfile']['error'] == UPLOAD_ERR_OK) {
            $file = $_FILES['importfile'];
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];

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
                if ($highestColumnIndex < 4) {
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
                    // Prepare SQL Insert statement
                        $sql = "INSERT INTO stockmaster (barcode, descrip, sellingprice, vat) VALUES (?, ?, ?, ?)";
                        $stmt = $mysqli->prepare($sql);

                        // Check if statement preparation was successful
                        if ($stmt === false) {
                            die("Error preparing statement: " . $mysqli->error);
                        }

                        // Bind parameters to the statement
                        $stmt->bind_param("ssdd", $barcode, $description, $unit_price, $PostingGroup);

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
   

?>
