<?php
// Windows users must enable php_printer.dll inside of php.ini in order to use these functions

    //$handle  =  printer_open("Send To OneNote 2007"); ///This Works
    /*
    $handle = printer_open('\\\\192.168.0.8\\Canon MF4320-4350'); 
    printer_set_option($handle, PRINTER_MODE, "RAW");
    printer_write($handle, "TEXT To print");
    printer_close($handle);
    */

function GetPrinters(){
$getprt = printer_list(PRINTER_ENUM_LOCAL| PRINTER_ENUM_SHARED );
$printers = serialize($getprt);
$printers = unserialize($printers);

echo '<select name="printers">';
    foreach ($printers as $PrintDest){
     $PrinterDesc=explode(",",$PrintDest["DESCRIPTION"]) ;
        
     echo "<option value='".$PrintDest["NAME"]."'>".$PrinterDesc[1]."</option>";
    
    }
    echo '</select>';
    
}

//GetPrinters();

?>


<?php
$filename = "test.php";
///////
ob_start();
include $filename;
$contents = ob_get_contents();
ob_end_clean();
///////
$handle = printer_open("SAVIN4045ePCL6");
printer_set_option($handle, PRINTER_MODE, "raw"); 
printer_write($handle,$contents);
printer_close($handle);
?>


<?php
$xml=simplexml_load_file("note.xml") or die("Error: Cannot create object");
echo $xml->to . "<br>";
echo $xml->from . "<br>";
echo $xml->heading . "<br>";
echo $xml->body;
?>

Example explained:

    Initialize the XML parser with the xml_parser_create() function
    Create functions to use with the different event handlers
    Add the xml_set_element_handler() function to specify which function will be executed when the parser encounters the opening and closing tags
    Add the xml_set_character_data_handler() function to specify which function will execute when the parser encounters character data
    Parse the file "note.xml" with the xml_parse() function
    In case of an error, add xml_error_string() function to convert an XML error to a textual description
    Call the xml_parser_free() function to release the memory allocated with the xml_parser_create() function


 <?php
// Initialize the XML parser
$parser=xml_parser_create();

// Function to use at the start of an element
function start($parser,$element_name,$element_attrs) {
  switch($element_name) {
    case "NOTE":
    echo "-- Note --<br>";
    break;
    case "TO":
    echo "To: ";
    break;
    case "FROM":
    echo "From: ";
    break;
    case "HEADING":
    echo "Heading: ";
    break;
    case "BODY":
    echo "Message: ";
  }
}

// Function to use at the end of an element
function stop($parser,$element_name) {
  echo "<br>";
}

// Function to use when finding character data
function char($parser,$data) {
  echo $data;
}

// Specify element handler
xml_set_element_handler($parser,"start","stop");

// Specify data handler
xml_set_character_data_handler($parser,"char");

// Open XML file
$fp=fopen("note.xml","r");

// Read data
while ($data=fread($fp,4096)) {
  xml_parse($parser,$data,feof($fp)) or
  die (sprintf("XML Error: %s at line %d",
  xml_error_string(xml_get_error_code($parser)),
  xml_get_current_line_number($parser)));
}

// Free the XML parser
xml_parser_free($parser);
?> 
    
    
    <?php
$xmlDoc = new DOMDocument();
$xmlDoc->load("note.xml");

$x = $xmlDoc->documentElement;
foreach ($x->childNodes AS $item) {
  print $item->nodeName . " = " . $item->nodeValue . "<br>";
}
?> 