<?php
if (!isset($RootPath)){
        $RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF']));
         if ($RootPath == '/' OR $RootPath == "\\") {
                $RootPath = '';
        }
}

$ViewTopic = isset($ViewTopic)?'?ViewTopic=' . $ViewTopic : '';
$BookMark  = isset($BookMark)? '#' . $BookMark : '';
$Title = "LIMS ERP";

echo '<html xmlns="http://www.w3.org/1999/xhtml"><head><title>LIMS ERP</title>';
echo '<meta charset="utf-8"/><meta http-equiv="X-UA-Compatible" content="IE=edge" SameSite="None"/>'
. '<meta name="viewport" content="width=device-width,initial-scale=1"/>';
echo '<link rel="shortcut icon" href="favicon.ico" /><link rel="icon" href="favicon.ico" />';
echo '<input type="hidden" name="Lang" id="Lang" value="'.$Lang.'" />';
echo '<link rel="stylesheet" href="css/assets/dist/css/bootstrap.min.css" type="text/css"/>';

echo '<link rel="stylesheet" href="css/assets/DataTables/datatables.min.css" type="text/css"/>'
   . '<link rel="stylesheet" href="css/dashboard.css" type="text/css"/>';
echo '<link rel="stylesheet" href="css/headers.css" type="text/css"/>';
echo '<link rel="stylesheet" href="css/index.css" type="text/css"/>'
   . '<link rel="stylesheet" href="css/assets/css/style.css" type="text/css"/>';
echo '<link rel="stylesheet" href="css/tailwind.css" type="text/css"/>';
echo '<script type="text/javascript" src = "javascripts/jquery.min.js"></script>
<script type="text/javascript" src ="css/assets/DataTables/datatables.min.js"></script>
<script type="text/javascript" src ="javascripts/jquery.tablesorter.pager.js"></script>
<script type="text/javascript" src ="javascripts/jquery.dataTables.min.js"></script>
<script type="text/javascript" src ="javascripts/menu/dmenu.js"></script>
<script type="text/javascript" src ="javascripts/menu/dmenu_key.js"></script>
<script type="text/javascript" src ="javascripts/menu/data-clear-2.js"></script>'; 
include('includes/svg.inc');
echo '</head>';
echo '<body>';


echo '<header id="headerid" class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
   <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="#">';
echo '<img src="css/xenos/images/company.png" title="'._('Company').'" alt="'._('Company').'"/>' . stripslashes($_SESSION['CompanyRecord']['coyname']);
echo '</a>';
 
echo ' <ul class="nav col-12 col-lg-auto my-2 justify-content-center my-md-0 text-small">';
echo '<li><a class="nav-link text-white" href="posindex.php"><svg class="bi d-block mx-auto mb-1" width="24" height="24"><use xlink:href="#basket"/></svg>' . _('Point of Sale') . '</a></li>';
echo '<li><a class="nav-link text-white" href="index.php?link=UserSettings"><svg class="bi d-block mx-auto mb-1" width="24" height="24"><use xlink:href="#table"/></svg>User Logged : ' . stripslashes($_SESSION['UsersRealName']).'</a></li>';
		
if (isset($Title)) {
		if (count($_SESSION['AllowedPageSecurityTokens'])>1){
			echo '<li><a class="nav-link text-white" href="index.php?link=SelectCustomer&newsearch=yes"><svg class="bi d-block mx-auto mb-1" width="24" height="24"><use xlink:href="#people-circle"/></svg>' . _('Customers') .'</a></li>';
			echo '<li><a class="nav-link text-white" href="index.php?link=SelectSupplier&newsearch=yes"><svg class="bi d-block mx-auto mb-1" width="24" height="24"><use xlink:href="#head"/></svg>' . _('Suppliers') .'</a></li>';
                        echo '<li><a class="nav-link text-white" href="index.php?link=SelectProduct&newsearch=yes"><svg class="bi d-block mx-auto mb-1" width="24" height="24"><use xlink:href="#basket"/></svg>' ._('Items') .'</a></li>';
                        echo '<li><a class="nav-link text-white" href="index.php?link=bankregister"><svg class="bi d-block mx-auto mb-1" width="24" height="24"><use xlink:href="#basket"/></svg>' ._('Cash Book') .'</a></li>';
                        
                        
			
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
if (!function_exists('GetTempLabrefNo')) {
    include('includes/SQL_CommonFunctions.inc');
 }
 
 $documentno = GetTempLabrefNo(10,$db);
echo '<input class="form-control form-control-dark w-100" type="text" placeholder="Next Batch No is '.$documentno.'" aria-label="Search">
  <div class="navbar-nav">
    <div class="nav-item text-nowrap">
 	<a class="nav-link text-white" href="index.php?link=Logout" onclick="return confirm(\''._('Are you sure you wish to logout?').'\');"><svg class="bi d-block mx-auto mb-1" width="24" height="24"><use xlink:href="#logout"/></svg>' . _('Logout') . '</a>
   </div>
  </div>
</header><script>
(function () {
    var header = document.getElementById("headerid");
    if (!header) {
        return;
    }

    var updateHeaderHeight = function () {
        document.documentElement.style.setProperty(
            "--app-header-height",
            header.getBoundingClientRect().height + "px"
        );
    };

    updateHeaderHeight();

    if (typeof ResizeObserver !== "undefined") {
        var headerObserver = new ResizeObserver(updateHeaderHeight);
        headerObserver.observe(header);
    } else {
        window.addEventListener("resize", updateHeaderHeight);
    }

    /*
     * Mark legacy HTML form-layout tables so the shared stylesheet can size
     * them to their controls. Real data grids and DataTables are left alone.
     */
    var markCompactFormTables = function (root) {
        var scope = root && root.querySelectorAll ? root : document;
        var tables = [];

        if (scope.matches && scope.matches("table")) {
            tables.push(scope);
        }

        Array.prototype.forEach.call(
            scope.querySelectorAll("form table"),
            function (table) {
                tables.push(table);
            }
        );

        tables.forEach(function (table) {
            if (!table.closest("form") ||
                table.classList.contains("dataTable") ||
                table.closest(".dataTables_wrapper, .table-responsive") ||
                table.querySelector(":scope > thead") ||
                table.parentElement.closest("table")) {
                return;
            }

            if (table.querySelector(
                "input:not([type=hidden]):not([type=submit]):not([type=button]), select, textarea"
            )) {
                table.classList.add("bo-compact-form-table");
            }
        });
    };

    /*
     * Bootstrap's form-control/form-select classes impose large default
     * padding, height and full-width sizing on legacy ERP controls. Keep the
     * Bootstrap grid, navigation and button classes, but remove those two
     * sizing classes from back-office form fields only.
     */
    var removeOversizedBootstrapControlClasses = function (root) {
        var panel = document.getElementById("view-panel");
        if (!panel) {
            return;
        }

        var scope = root && root.querySelectorAll ? root : panel;
        var controls = [];

        if (scope.matches && scope.matches(
            "main#view-panel input, main#view-panel select, main#view-panel textarea"
        )) {
            controls.push(scope);
        }

        Array.prototype.forEach.call(
            scope.querySelectorAll(
                "main#view-panel input, main#view-panel select, main#view-panel textarea, " +
                "#view-panel input, #view-panel select, #view-panel textarea"
            ),
            function (control) {
                controls.push(control);
            }
        );

        controls.forEach(function (control) {
            control.classList.remove("form-control", "form-select");
        });
    };


    /*
     * Remove Bootstrap's visual table treatment from ordinary HTML tables.
     * Keep it on actual data grids (DataTables, tablesorter, or explicitly
     * marked grid tables/containers).
     */
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

    markCompactFormTables(document);
    removeOversizedBootstrapControlClasses(document);
    updateBootstrapTableClasses(document);

    if (document.body && typeof MutationObserver !== "undefined") {
        var formTableObserver = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                Array.prototype.forEach.call(mutation.addedNodes, function (node) {
                    if (node.nodeType === 1) {
                        markCompactFormTables(node);
                        removeOversizedBootstrapControlClasses(node);
                        updateBootstrapTableClasses(node);
                    }
                });
            });
        });

        formTableObserver.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ["class", "role", "data-grid"]
        });
    }
})();
</script>';

?>
