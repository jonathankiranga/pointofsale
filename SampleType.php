<?php
include('includes/session.inc');
$Title = _('Sample Types');
include('includes/header.inc');

echo  '<div>FILTER CRITERIA:<select id="filter-field">
    <option></option>
    <option value="sampletypeid">RowID</option>
    <option value="StandardCode">Standard Code</option>
    <option value="sampletypeName">Standard Name</option>
     <option value="ControlParameter">Control Sample</option>
  </select>

  OPERAND:<select id="filter-type">
    <option value="like">like</option>
    <option value="=">=</option>
    <option value="<"><</option>
    <option value="<="><=</option>
    <option value=">">></option>
    <option value=">=">>=</option>
    <option value="!=">!=</option>
  </select>

<input id="filter-value" type="text" placeholder="value to filter">
<button id="filter-clear">Clear Filter</button>
<button id="add-std">Add New Standard</button><button id="refr-std">Refresh</button></div>
<div id="Tabulator-table"></div>';

?>
<script type="text/javascript">
  

document.addEventListener("DOMContentLoaded", function () {
  
//Define variables for input elements
var fieldEl = document.getElementById("filter-field");
var typeEl = document.getElementById("filter-type");
var valueEl = document.getElementById("filter-value");

var isLoading = true; // Assuming this variable indicates whether the object is loading

//Trigger setFilter function with correct parameters
function updateFilter(){
  var filterVal = fieldEl.options[fieldEl.selectedIndex].value;
  var typeVal = typeEl.options[typeEl.selectedIndex].value;
  if(filterVal){
    tableTabulator.setFilter(filterVal,typeVal, valueEl.value);
  }
}

//Update filters on value change
document.getElementById("filter-field").addEventListener("change", updateFilter);
document.getElementById("filter-type").addEventListener("change", updateFilter);
document.getElementById("filter-value").addEventListener("keyup", updateFilter);
//Clear filters on "Clear Filters" button click
document.getElementById("filter-clear").addEventListener("click", function(){
  fieldEl.value = "";
  typeEl.value = "=";
  valueEl.value = "";

  tableTabulator.clearFilter();
});

 document.getElementById("add-std").addEventListener("click", function(){
      var url = 'QuickAddSamples.php'; // Replace with your desired URL
          var width = 800; // Replace with the desired width
          var height = 400; // Replace with the desired height
          openResizedWindow(url, width, height);
});

document.getElementById("refr-std").addEventListener("click", function(){
      tableTabulator.clearData();
      tableTabulator.setData("Ajax/SampleStandardsAjax.php");
});
  
  
toastr.options = {
    "positionClass": "toast-top-right",
    "preventDuplicates": true,
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "5000",
    "extendedTimeOut": "1000",
    "tapToDismiss": true,
    "closeButton": false
  }

// Debounce function to limit the rate at which a function can fire
function debounce(func, wait) {
  let timeout;
  return function (...args) {
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(this, args), wait);
  };
}

// Lock to prevent concurrent operations
let selectionLock = false;

function toggleRowSelection(row) {
  if (!selectionLock) {
    selectionLock = true;
    const data = row.getData();
    data.select = !data.select;
    row.update(data);
    selectionLock = false;
  }
}

var tableTabulator = new Tabulator("#Tabulator-table", {
  height: "90%",
  ajaxURL: "Ajax/SampleStandardsAjax.php",
  layout: "fitColumns",
  columnDefaults: {
    resizable: true,
  },
  placeholder: "No Data Set",
  groupBy: "StandardCode",
  groupStartOpen: false,
  pagination: "remote",
  paginationSize: 20,
  paginationSizeSelector: [20, 30, 60, 120, 240, 480, 960],
  paginationCounter: "rows",
  columns: [
    // Define the column structure for both parent and child rows
    { title: "ID", field: "sampletypeid"},
    { title: "Standard Code", field: "StandardCode" },
    { title: "Standard Name", field: "sampletypeName" },
    { title: "Pakage Price", field: "PakagePrice" },
    { title: "Notes", field: "Summary" },
    { title: "Sales Group", field: "salesgroup", visible: false },
    { title: "Is CS", field: "ControlParameter", formatter: "tickCross", headerSort: false },
   ],
  groupHeader: function (value, count, data, group) {
    var groupRows = group.getRows();
    var selectedRowsCount = groupRows.filter(function (row) {
      return row.getData().select;
    }).length;
    var selectAllChecked = selectedRowsCount === groupRows.length;

    var checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.checked = selectAllChecked;

    checkbox.addEventListener("change", debounce(function () {
      if (!selectionLock) {
        selectionLock = true;
        var selected = checkbox.checked;
        groupRows.forEach(function (row) {
          var data = row.getData();
          data.select = selected;
          row.update(data);
        });
        selectionLock = false;
      }
    }, 100));

    var label = document.createElement("label");
    label.appendChild(checkbox);
    label.appendChild(document.createTextNode(value));

    checkbox.addEventListener("click", function (e) {
      e.stopPropagation(); // Prevent event propagation to the group header cell
    });

    return label;
  },
});

// Attach event listener to capture button outside of Tabulator's config
    // Add a double-click event listener to the table element
 tableTabulator.on("rowDblClick", function(e, row) {
  // Get the data of the double-clicked row
  var rowData = row.getData();
  
   var url = 'QuickAddSamples.php?sampletypeid='+rowData.sampletypeid; // Replace with your desired URL
          var width = 800; // Replace with the desired width
          var height = 400; // Replace with the desired height
          openResizedWindow(url, width, height);
   
         
});
   
    
function openResizedWindow(url, width, height) {
   var left = (window.innerWidth - width) / 2;
  var top = (window.innerHeight - height) / 2;
  var options = 'width=' + width + ',height=' + height + ',top=' + top + ',left=' + left + ',location=no';
  window.open(url, 'lims', options);
}

    
    
});

</script>


<?php
include('includes/footer.inc');
 
     
Function GetFooterText($code){
  global $db;
      
    $narration="";

    $sql = Sprintf("Select [Narration] from [SampleTypeLine] where [sampletypeid]='%s'",$code);
    $ResultIndex = DB_query($sql,$db);
    while($row = DB_fetch_array($ResultIndex)){
        $narration .=$row['Narration'];
    }

   return html_entity_decode($narration);
    
    
}

Function ShowSampleTypes(){
    Global $db;
    //set it 209
    $TransType=15;
    
    $SQL = SPRINTF( "UPDATE systypes_1 SET typeno = typeno + 1  WHERE typeid = '%s' ",$TransType);
    $ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The transaction number could not be incremented');
    $DbgMsg =  _('The following SQL to increment the transaction number was used');
    $UpdTransNoResult = DB_query($SQL,$db,$ErrMsg,$DbgMsg);

    $SQL = SPRINTF("SELECT IFNULL(prefix,'') as prefix , typeno FROM systypes_1 WHERE typeid='%s'",$TransType);
    $ErrMsg =  _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': <BR>' . _('The next transaction number could not be retrieved from the database because');
    $DbgMsg =  _('The following SQL to retrieve the transaction number was used');
    $GetTransNoResult = DB_query($SQL,$db,$ErrMsg,$DbgMsg);
    $myrow = DB_fetch_row($GetTransNoResult);

   $prefix = $myrow[0];
   $nexno  = padR($myrow[1]);
   
    return $prefix . $nexno ;
    
}

?>
 
