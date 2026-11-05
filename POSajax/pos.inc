
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retail POS System</title>
    <link rel="stylesheet" href="POScss/completecss.css">
    <link rel="stylesheet" href="POScss/posstyle.css">
    <script type="text/javascript" src = "javascripts/3.5.1/jquery.min.js"></script>
   </head>
<body>
    <!-- POS Ribbon Menu -->
    <div class="pos-ribbon">
       <ul>
       <li id="company"><a><svg width="24" height="24"><use xlink:href="POScss/svg.svg#home"/></svg><?php echo stripslashes($_SESSION['CompanyRecord']['coyname']);?></a></li>
       <li id="userlog"><a><svg width="24" height="24"><use xlink:href="POScss/svg.svg#people-circle"/></svg>User:<?php echo stripslashes($_SESSION['UsersRealName']); ?></a></li>
       <li><a href="PosLogout.php"><svg width="24" height="24"><use xlink:href="POScss/svg.svg#logout"/></svg>Log OUT</a></li>
       <li><a href="#" id="delete-selected-link"><svg width="24" height="24"><use xlink:href="POScss/svg.svg#trash"/></svg>Delete</a></li> 
       <li><a href="#" id="save-transaction-link"><svg  width="24" height="24"><use xlink:href="POScss/svg.svg#memory"/></svg>MS</a></li>
       <li><a href="#" id="recall-transaction-link"><svg  width="24" height="24"><use xlink:href="POScss/svg.svg#recall"/></svg>MR</a></li>
       <li><a href="#" id="show-bill-link"><svg  width="24" height="24"><use xlink:href="POScss/svg.svg#print"/></svg>CheckOut</a></li>
       <li><a href="#" id="find-items-link"><svg  width="24" height="24"><use xlink:href="POScss/svg.svg#barcode"/></svg>Search Stock</a></li>
      </ul>
    </div>
    <div id="homePageNav">
        <span id="cashsaleno"></span>
        <script>
        const cashsalenoInput = document.getElementById('cashsaleno');
        cashsalenoInput.textContent = `CS-${Date.now()}`;
    </script>
        <h2>Smarternow POS</h2>
        <table id="sales-table">
            <thead>
                <tr>
                    <th>Barcode</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>VAT Rate</th>
                    <th>VAT</th>
                    <th>Line Total</th>
                </tr>
            </thead>
            <tbody>
                <!-- Rows will be dynamically added here -->
            </tbody>
        </table>
      </div> 
    <div id="total-amount-container">
        <p>Total Amount: <span id="total-amount">0.00</span></p>
        <p>Tax (VAT): <span id="tax-amount">0.00</span></p>
       <br/><span>Press "crtl+F1" Checkout 
           <svg  width="36" height="36"><use xlink:href="POScss/svg.svg#money"/></svg>
           <svg  width="36" height="36"><use xlink:href="POScss/svg.svg#simcard"/></svg>
       </span>
    </div>
    
      
<div id="modal-overlay"></div>
<!-- Receipt Modal -->
<div id="receipt-modal" style="display:none;">
    <div id="receipt-content">
        <h3>Sales Receipt</h3>
        <pre id="receipt-text"></pre> 
    </div>
</div>
    

<div id="search-modal" class="modal"  style="display:none;">
    <div class="modal-content">
        <span class="checkout-modal-close">&times;</span>
        <div id="search-results">
            <!-- Search results will be populated here -->
        </div>
        <input type="text" id="search-input" placeholder="Search by barcode, description, or price...">
    </div>
</div>

<div id="checkout-modal" class="modal"  style="display:none;">
    <div class="modal-content">
        <span class="checkout-modal-close">&times;</span>
        <h2>Total Amount: <span id="checkout-total">0.00</span></h2>
        <br/>
        <label for="customer-cash">Customer Cash:</label>
        <input type="number" id="customer-cash" placeholder="Enter cash amount">
        <p>Change: <span id="customer-change">0.00</span></p>
        <div id="denomination-list-container" style="display:none;">
        <h3>Change in Denominations:</h3>
        <div id="denomination-list"></div>
        </div>
        <span>Press "crtl+F3" M-Pesa</span>
        <h4>Select Payment Method:</h4>
        <button id="pay-mpesa-btn"><svg  width="36" height="36"><use xlink:href="POScss/svg.svg#simcard"/></svg>M-Pesa</button>
        <button id="pay-cash-btn"><svg  width="36" height="36"><use xlink:href="POScss/svg.svg#print"/></svg>Complete Cash Sale</button>
    </div>
</div>

<div id="mpesa-modal" class="modal"  style="display:none;">
    <div class="modal-content-small">
        <span class="checkout-modal-close">&times;</span>
        <label for="customer-mpesa-no">Mpesa:</label>
        <input type="number" id="customer-mpesa-no" placeholder="254xxxxxx">
         <button id="process-mpesa-btn"><svg  width="36" height="36"><use xlink:href="POScss/svg.svg#print"/></svg>Confirm Payment & Complete Sale</button>
    </div>
</div>

<div id="recall-modal" class="modal"  style="display:none;">
    <div class="modal-content-small">
        <span class="checkout-modal-close">&times;</span>
        <button id="recall-button">Recall Data</button> 
        <select id="reference-list">
            <option value="">Select Reference to Recall</option>
        </select>
        
    </div>
</div>
    
    
 <!-- Custom Dialog Box -->
<div id="custom-dialog" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="dialog-close">&times;</span>
        <p id="dialog-message"></p>
        <button id="dialog-ok-btn">OK</button>
    </div>
</div>


<script type="text/javascript" src = "POSajax/posscript.js"></script>
<script type="text/javascript" src = "POSajax/searchstock.js"></script>
<script>
    window.POS_PRINT_URL = <?php echo json_encode($POSPrintUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    window.POS_PRINT_TOKEN = <?php echo json_encode($POSPrintToken, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    window.POS_PRINTER_ID = <?php echo json_encode(isset($POSPrinterId) ? $POSPrinterId : '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
</script>
<script type="text/javascript" src = "POSajax/printreceipt.js"></script>
</body>
</html>
