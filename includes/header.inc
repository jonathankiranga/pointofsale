<?php
if (!isset($RootPath)){
       $RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF']));
       if ($RootPath == '/' OR $RootPath == "\\") {
           $RootPath = '';
       }
   }
       
echo '<html xmlns="http://www.w3.org/1999/xhtml"><head><title>LIMS ERP</title>';
echo '<meta charset="utf-8"/><meta http-equiv="X-UA-Compatible" content="IE=edge" SameSite="None"/>'
   . '<meta name="viewport" content="width=device-width,initial-scale=1"/>';
echo '<link rel="shortcut icon" href="favicon.ico" /><link rel="icon" href="favicon.ico" />';
echo '<input type="hidden" name="Lang" id="Lang" value="'.$Lang.'" />';
echo '<link rel="stylesheet" href="javascripts/dist/css/tabulator.min.css">';
echo '<link rel="stylesheet" href="css/assets/dist/css/bootstrap.min.css" type="text/css"/>';
echo '<link rel="stylesheet" href="css/tailwind.css" type="text/css"/>';
echo '<link rel="stylesheet" href="css/assets/DataTables/datatables.min.css" type="text/css"/>';
echo '<link rel="stylesheet" href="css/trumbowyg.min.css" type="text/css"/>'
   . '<link rel="stylesheet" href="css/assets/css/style.css" type="text/css"/>';
echo '<link rel="stylesheet" href="css/dashboard.css" type="text/css"/>';
echo '<link rel="stylesheet" href="css/headers.css" type="text/css"/>';
echo '<link rel="stylesheet" href="css/index.css" type="text/css"/>
      <link rel="stylesheet" href="css/toastr/toastr.min.css">';
            
 echo '<script type="text/javascript" src = "javascripts/3.5.1/jquery.min.js"></script>
      <script type="text/javascript" src ="https://code.jquery.com/jquery-3.6.0.min.js"></script>
      <script type="text/javascript" src = "css/assets/dist/js/bootstrap.bundle.min.js" /></script>
      <script type="text/javascript" src = "css/assets/DataTables/datatables.min.js"></script>
      <script type="text/javascript" src = "javascripts/jquery.tablesorter.pager.js"></script>
      <script type="text/javascript" src = "javascripts/jquery.dataTables.min.js"></script>
      <script type="text/javascript" src = "javascripts/nicEdit-latest.js"></script>
      <script type="text/javascript" src = "javascripts/trumbowyg.js"></script>
      <script type="text/javascript" src = "javascripts/MiscFunctions.js"></script> 
      <script type="text/javascript" src = "javascripts/JQueryclases.js"></script>
      <script type="text/javascript" src = "javascripts/dist/js/tabulator.min.js"></script>
      <script type="text/javascript" src = "javascripts/xlsx.full.min.js"></script>';
 
 echo '<script type="text/javascript" src="javascripts/menu/dmenu.js"></script>
      <script type="text/javascript" src="javascripts/menu/dmenu_key.js"></script>
      <script type="text/javascript" src="javascripts/menu/data-clear-2.js"></script>
      <script type="text/javascript" src="css/toastr/toastr.min.js"></script>
      <script type="text/javascript" src="css/toastr/confirmtoastr.js"></script>'; 
?>
<style>
  .modal-dialog.large {
    width: 80% !important;
    max-width: unset;
  }
  .modal-dialog.mid-large {
    width: 50% !important;
    max-width: unset;
  }
 </style>
<?php
include("css/htmlpurifier/library/HTMLPurifier.auto.php");
include('includes/svg.inc');

$ViewTopic = isset($ViewTopic) ? $ViewTopic : '';
$BookMark = isset($BookMark) ? $BookMark : '';
echo '</head><body>';
echo '<div id="page"></div>';
echo '<script>
(function () {
    var updateBootstrapTableClasses = function (root) {
        var scope = root && root.querySelectorAll ? root : document;
        var tables = [];

        if (scope.matches && scope.matches("table")) {
            tables.push(scope);
        }

        Array.prototype.forEach.call(scope.querySelectorAll("table"), function (table) {
            tables.push(table);
        });

        tables.forEach(function (table) {
            var isGrid =
                table.classList.contains("dataTable") ||
                table.classList.contains("tablesorter") ||
                table.classList.contains("table-grid") ||
                table.classList.contains("grid-table") ||
                table.hasAttribute("data-grid") ||
                table.getAttribute("role") === "grid" ||
                !!table.closest(
                    ".dataTables_wrapper, .tablesorter, .grid-table, .table-grid, " +
                    "[data-grid], [role=grid]"
                );

            if (!isGrid && table.classList.contains("table")) {
                table.classList.remove("table");
            }
        });
    };

    updateBootstrapTableClasses(document);

    if (document.body && typeof MutationObserver !== "undefined") {
        var tableClassObserver = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === "childList") {
                    Array.prototype.forEach.call(mutation.addedNodes, function (node) {
                        if (node.nodeType === 1) {
                            updateBootstrapTableClasses(node);
                        }
                    });
                } else if (mutation.type === "attributes") {
                    updateBootstrapTableClasses(
                        mutation.target.matches("table") ?
                            mutation.target : mutation.target
                    );
                }
            });
        });

        tableClassObserver.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ["class", "role", "data-grid"]
        });
    }
})();
</script>';
echo '<header id="headerid" class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
   <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="#">';
echo '<img src="css/xenos/images/company.png" title="'._('Company').'" alt="'._('Company').'"/>' . stripslashes($_SESSION['CompanyRecord']['coyname']);
echo '</a>';
 
echo ' <ul class="nav col-12 col-lg-auto my-2 justify-content-center my-md-0 text-small">';
echo '<li><a class="nav-link text-white" href="index.php?link=UserSettings">'
. '<svg class="bi d-block mx-auto mb-1" width="24" height="24"><use xlink:href="#table"/></svg>User Logged : ' . stripslashes($_SESSION['UsersRealName']).'</a></li>';
		
	if (isset($Title)) {
		if (count($_SESSION['AllowedPageSecurityTokens'])>1){
			echo '<li><a  class="nav-link text-white"   href="index.php?link=SelectCustomer&newsearch=yes"><svg class="bi d-block mx-auto mb-1" width="24" height="24"><use xlink:href="#people-circle"/></svg>' . _('Customers') .'</a></li>';
			echo '<li><a  class="nav-link text-white"   href="index.php?link=SelectSupplier&newsearch=yes"><svg class="bi d-block mx-auto mb-1" width="24" height="24"><use xlink:href="#head"/></svg>' . _('Suppliers') .'</a></li>';
                        echo '<li><a  class="nav-link text-white"   href="index.php?link=SelectProduct&newsearch=yes"><svg class="bi d-block mx-auto mb-1" width="24" height="24"><use xlink:href="#basket"/></svg>' ._('Items') .'</a></li>';
			    echo '<li><a class="nav-link text-white" href="index.php?link=bankregister"><svg class="bi d-block mx-auto mb-1" width="24" height="24"><use xlink:href="#table"/></svg>' ._('Cash Book') .'</a></li>';
                        
                   
			$DefaultManualLink = '<li><a class="nav-link text-white" rel="external" accesskey="8" href="doc/Manual/ManualContents.php'. $ViewTopic . $BookMark. '"><svg class="bi d-block mx-auto mb-1" width="24" height="24"><use xlink:href="#whatsapp"/></svg>' . _('Manual') . '</a></li>';

			if (mb_substr($_SESSION['Language'],0,2) != 'en'){
				if (file_exists('locale/'.$_SESSION['Language'].'/Manual/ManualContents.php')){
				  echo '<li><a class="nav-link text-white" target="_blank" href="locale/'.$_SESSION['Language'].'/Manual/ManualContents.php'. $ViewTopic . $BookMark. '"><svg class="bi d-block mx-auto mb-1" width="24" height="24"><use xlink:href="#whatsapp"/></svg>' . _('Manual') . '</a></li>';
				} else {
				  echo $DefaultManualLink;
				}
			} else {
                            echo $DefaultManualLink;
			}
		}
	}
        
echo '</ul>';

echo '<button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="navbar-nav">
    <div class="nav-item text-nowrap">
 	<a class="nav-link text-white" href="index.php?link=Logout" onclick="return confirm(\''._('Are you sure you wish to logout?').'\');"><svg class="bi d-block mx-auto mb-1" width="24" height="24"><use xlink:href="#logout"/></svg>' . _('Logout') . '</a>
   </div>
  </div>
</header><main>';
 
 echo '<main id="view-panel" class="container">'
         . '<div class="responsive">'
         . '<div class="container-fluid">';