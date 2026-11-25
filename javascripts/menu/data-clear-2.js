/*
   Deluxe Menu Data File
   Created by Deluxe Tuner v3.2
   http://deluxe-menu.com
*/


// -- Deluxe Tuner Style Names
var itemStylesNames=[];
var menuStylesNames=[];
// -- End of Deluxe Tuner Style Names

//--- Common
var isHorizontal=1;
var smColumns=1;
var smOrientation=0;
var dmRTL=0;
var pressedItem=-2;
var itemCursor="pointer";
var itemTarget="";
var statusString="link";
var blankImage="images/blank.gif";
var pathPrefix_img="javascripts/menu/images/";
var pathPrefix_link="";

//--- Dimensions
var menuWidth="250px";
var menuHeight="";
var smWidth="";
var smHeight="";

//--- Positioning
var absolutePos=0;
var posX="1px";
var posY="1px";
var topDX=0;
var topDY=0;
var DX=0;
var DY=0;
var subMenuAlign="left";
var subMenuVAlign="top";

//--- Font
var fontStyle=["normal 14px Tahoma","normal 14px Tahoma"];
var fontColor=["#000000","#FFFFFF"];
var fontDecoration=["none","none"];
var fontColorDisabled="#AAAAAA";

//--- Appearance
var menuBackColor="#FCFCFC";
var menuBackImage="";
var menuBackRepeat="repeat";
var menuBorderColor="#55A1FF";
var menuBorderWidth=0;
var menuBorderStyle="solid";

//--- Item Appearance
var itemBackColor=["transparent","#1665CB"];
var itemBackImage=["sm_back_xp.gif","sm_back_xp2.gif"];
var beforeItemImage=["",""];
var afterItemImage=["",""];
var beforeItemImageW="";
var afterItemImageW="";
var beforeItemImageH="";
var afterItemImageH="";
var itemBorderWidth=0;
var itemBorderColor=["#FCEEB0","#4C99AB"];
var itemBorderStyle=["solid","solid"];
var itemSpacing=0;
var itemPadding="4px 4px 4px 5px";
var itemAlignTop="left";
var itemAlign="left";

//--- Icons
var iconTopWidth=16;
var iconTopHeight=16;
var iconWidth=16;
var iconHeight=16;
var arrowWidth=7;
var arrowHeight=7;
var arrowImageMain=["arr_black_2.gif","arr_white_2.gif"];
var arrowWidthSub=0;
var arrowHeightSub=0;
var arrowImageSub=["arr_black_2.gif","arr_white_2.gif"];

//--- Separators
var separatorImage="sep_xp.gif";
var separatorWidth="90%";
var separatorHeight="3px";
var separatorAlignment="center";
var separatorVImage="";
var separatorVWidth="3px";
var separatorVHeight="100%";
var separatorPadding="5px";

//--- Floatable Menu
var floatable=0;
var floatIterations=6;
var floatableX=1;
var floatableY=1;
var floatableDX=15;
var floatableDY=15;

//--- Movable Menu
var movable=0;
var moveWidth=12;
var moveHeight=20;
var moveColor="#DECA9A";
var moveImage="";
var moveCursor="move";
var smMovable=0;
var closeBtnW=15;
var closeBtnH=15;
var closeBtn="";

//--- Transitional Effects & Filters
var transparency="100";
var transition=24;
var transOptions="";
var transDuration=350;
var transDuration2=200;
var shadowLen=4;
var shadowColor="#B1B1B1";
var shadowTop=1;

//--- CSS Support (CSS-based Menu)
var cssStyle=0;
var cssSubmenu="";
var cssItem=["",""];
var cssItemText=["",""];

//--- Advanced
var dmObjectsCheck=0;
var saveNavigationPath=1;
var showByClick=0;
var noWrap=1;
var smShowPause=200;
var smHidePause=1000;
var smSmartScroll=1;
var topSmartScroll=0;
var smHideOnClick=1;
var dm_writeAll=0;
var useIFRAME=0;
var dmSearch=0;

//--- AJAX-like Technology
var dmAJAX=0;
var dmAJAXCount=0;
var ajaxReload=0;

//--- Dynamic Menu
var dynamic=0;

//--- Popup Menu
var popupMode=0;

//--- Keystrokes Support
var keystrokes=1;
var dm_focus=1;
var dm_actKey=113;

//--- Sound
var onOverSnd="";
var onClickSnd="";

var itemStyles = [
];
var menuStyles = [
];

var menuItems = [
       ["Salon Point of Sale","SalonPOS.php", "bank.gif", "bank.gif", "", "", "", "", "", "", "", ],
       ["Customers","", "sales.gif", "sales.gif", "", "", "", "", "", "", "", ],
        ["|New","", "icon_xp3_3.gif", "icon_xp3_3o.gif", "", "", "", "", "", "", "", ],
            ["||Customer Posting Group Setup","CustomersPostingGroups.php", "", "", "", "", "", "", "", "", "", ],
            ["||New Customer","Customer.php", "", "", "", "", "", "", "", "", "", ],
            
       ["|POST","", "icon_xp3_3.gif", "icon_xp3_3o.gif", "", "", "", "", "", "", "", ],
            ["||Cash Customer Receipts","receiptsprepayment.php", "", "", "", "", "", "", "", "", "", ],
            ["||Credit Notes","SalesDeliveryList.php", "", "", "", "", "", "", "", "", "", ],
            ["||Invoice Income Not From Samples","EnterSalesBills.php", "", "", "", "", "", "", "", "", "", ],
       ["|Print","", "icon_xp3_9.gif", "icon_xp3_9o.gif", "", "", "", "", "", "", "", ],
            ["||Customer Statements","PrintCustStatements.php", "", "", "", "", "", "", "", "", "", ],
            ["||Customer Ageing","PrintCustAgeing.php", "", "", "", "", "", "", "", "", "", ],
            ["||Print Customer Receipts","PDFprintreceipt.php", "", "", "", "", "", "", "", "", "", ],
            ["||VAT Sales Summury","PDFPrintDailySales.php", "", "", "", "", "", "", "", "", "", ],
            ["||VAT Purchases Summury","PDFPrintDailyPurchases.php", "", "", "", "", "", "", "", "", "", ],
            ["||Credit Notes VAT Sales Summury","PDFPrintDailyCreditnotes.php", "", "", "", "", "", "", "", "", "", ],
            ["||Debit Notes VAT Purchases Summury","PDFPurchaseCreditnotes.php", "", "", "", "", "", "", "", "", "", ],
        ["Suppliers","", "supplier.gif", "supplier.gif", "", "", "", "", "", "", "", ],
        ["|New","", "icon_xp3_3.gif", "icon_xp3_3o.gif", "", "", "", "", "", "", "", ],
            ["||New Supplier","Supplier.php", "", "", "", "", "", "", "", "", "", ],
            ["||Supplier Posting Group","SupplierPostingGroups.php", "", "", "", "", "", "", "", "", "", ],
        ["|Post Items","", "icon_xp3_wa.gif", "icon_xp3_wao.gif", "", "", "", "", "", "", "", ],
            ["||Create Services LPO","PurchaseOder.php", "", "", "", "", "", "", "", "", "", ],
            ["||Invoice LPO For Services","ServiceLPO.php", "", "", "", "", "", "", "", "", "", ],
            ["||Invoice Vendors Expenses that you dont Order","EnterBills.php", "", "", "", "", "", "", "", "", "", ],
         ["|Print","", "icon_xp3_9.gif", "icon_xp3_9o.gif", "", "", "", "", "", "", "", ],
            ["||Print Expense","PDFenterbills.php", "", "", "", "", "", "", "", "", "", ],
            ["||List of GRN","PDFPrintgrnNONStock.php", "", "", "", "", "", "", "", "", "", ],
            ["||List of LPO","PDFPrintPurchaseOrder.php", "", "", "", "", "", "", "", "", "", ],
            ["||Suppliers Statement","PrintvendorStatements.php", "", "", "", "", "", "", "", "", "", ],
     ["Cash Accounts","", "bank.gif", "bank.gif", "", "", "", "", "", "", "", ],
        ["|New","", "icon_xp3_3.gif", "icon_xp3_3o.gif", "", "", "", "", "", "", "", ],
            ["||New Bank/Cash Account","CreateBankAccount.php", "", "", "", "", "", "", "", "", "", ],
            ["||New Currency","Currencies.php", "", "", "", "", "", "", "", "", "", ],
            ["||Day Close for Petty Cash Reports","PettyCashUsers.php", "", "", "", "", "", "", "", "", "", ],
            
        ["|Post items","", "icon_xp3_wa.gif", "icon_xp3_wao.gif", "", "", "", "", "", "", "", ],
            ["||Receipts Allocation","ReceitsAllocation.php", "", "", "", "", "", "", "", "", "", ],
            ["||vendors Allocation","PaymentsAllocation.php", "", "", "", "", "", "", "", "", "", ],
            ["||Petty Cash/Imprest Module","PetteyCash.php&New=1", "", "", "", "", "", "", "", "", "", ],
            ["||Create Payment Voucher","PaymentVoucher.php", "", "", "", "", "", "", "", "", "", ],
            ["||Pay Approved Payment Vouchers","WriteCheque.php", "", "", "", "", "", "", "", "", "", ],
            ["||Debtors Reciepts","receiptsprepayment.php", "", "", "", "", "", "", "", "", "", ],
            ["||Bank Reconciliation","BankReconciliation.php&new=Yes", "", "", "", "", "", "", "", "", "", ],
         ["|Print","", "icon_xp3_9.gif", "icon_xp3_9o.gif", "", "", "", "", "", "", "", ],
            ["||Petty Cash Summary","PDFimprestReport.php", "", "", "", "", "", "", "", "", "", ],
            ["||List of Payment Vouchers","PDFpaymentvoucher.php", "", "", "", "", "", "", "", "", "", ],
     ["Items and Products","", "icon_mac_2.gif", "icon_mac_2o.gif", "", "", "", "", "", "", "", ],
            ["|New","", "icon_xp3_3.gif", "icon_xp3_3o.gif", "", "", "", "", "", "", "", ],
                ["||New Product and Items","Stocks.php", "", "", "", "", "", "", "", "", "", ],
                ["||New Units of Measure","unitsofmesure.php", "", "", "", "", "", "", "", "", "", ],
                ["||New Store or Location","stockstores.php", "", "", "", "", "", "", "", "", "", ],
                ["||New Posting Groups","InventoryPostingGroups.php", "", "", "", "", "", "", "", "", "", ],
                ["||New Category","stockitemcategories.php", "", "", "", "", "", "", "", "", "", ],
       
    ["|Post items","", "icon_xp3_wa.gif", "icon_xp3_wao.gif", "", "", "", "", "", "", "", ],
                ["||Create Purchase order","PurchaseOder.php", "", "", "", "", "", "", "", "", "", ],
                ["||Enter items Received For LPO","PurchaseOrderList.php", "", "", "", "", "", "", "", "", "", ],
                ["||Invoice Items From LPO","GoodsReceivedNote.php", "", "", "", "", "", "", "", "", "", ],
   
    ["|Print","", "icon_xp3_9.gif", "icon_xp3_9o.gif", "", "", "", "", "", "", "", ],
                ["||Stock Balance","PDFstockbalance.php", "", "", "", "", "", "", "", "", "", ],
                ["||Stock Movement Summary","StockMovements.php", "", "", "", "", "", "", "", "", "", ],
                
       ["General Ledger","", "transactions.gif", "transactions.gif", "", "", "", "", "", "", "", ],
            ["|New","", "icon_xp3_3.gif", "icon_xp3_3o.gif", "", "", "", "", "", "", "", ],
                ["||New General Ledger Item","ChartofAccounts.php", "", "", "", "", "", "", "", "", "", ],
                ["||Company Details","CompanyPreferences.php", "", "", "", "", "", "", "", "", "", ],
                ["||System Settings","SystemParameters.php", "", "", "", "", "", "", "", "", "", ],
                ["||Maintain Dimension","DimensionTypes.php", "", "", "", "", "", "", "", "", "", ],
                ["||General Ledger Posting Groups","GLPostingGroups.php", "", "", "", "", "", "", "", "", "", ],
                ["||VAT Categories","vatcategory.php", "", "", "", "", "", "", "", "", "", ],
                 ["||System Settings","", "", "", "", "", "", "", "", "", "", ],
                 ["|||New User","WWW_Users.php", "", "", "", "", "", "", "", "", "", ],
                 ["|||User Roles","WWW_Access.php", "", "", "", "", "", "", "", "", "", ],
                 ["|||Create Page Security Tokens","SecurityTokens.php", "", "", "", "", "", "", "", "", "", ],
                 ["|||Page Security Settings","PageSecurity.php", "", "", "", "", "", "", "", "", "", ],
                 ["|||View Audit Trail","AuditTrail.php", "", "", "", "", "", "", "", "", "", ],
                 ["|||Manage Discrepancy","fellowgroups.php", "", "", "", "", "", "", "", "", "", ],
    
    ["|Transactions","", "icon_xp3_wa.gif", "icon_xp3_wao.gif", "", "", "", "", "", "", "", ],
                ["||Create Budget","Budgets.php", "", "", "", "", "", "", "", "", "", ],
                ["||Post General Journal","Journal.php&new=1", "", "", "", "", "", "", "", "", "", ],
                ["||Post Cash Journal","cashJournal.php", "", "", "", "", "", "", "", "", "", ],
                ["||Edit Ledger Account Setting","Generalledgerist.php", "", "", "", "", "", "", "", "", "", ],
                ["||Close Financial Periods","FinancialPeriods.php&new=1", "", "", "", "", "", "", "", "", "", ],
 
    ["|Reports","", "icon_xp3_9.gif", "icon_xp3_9o.gif", "", "", "", "", "", "", "", ],
                ["||Ledger Quick Report","selectReports.php", "", "", "", "", "", "", "", "", "", ],
                ["||Trial Balance","PDFFinacials.php", "", "", "", "", "", "", "", "", "", ],
                ["||Project Budget Report","PDFprojects.php", "", "", "", "", "", "", "", "", "", ],
                ["||Consolidated Income statement","PDFprofitloss.php", "", "", "", "", "", "", "", "", "", ],
                ["||Monthly Consolidated Income statement","PDFprofitlossbymonth.php", "", "", "", "", "", "", "", "", "", ],
                ["||Balance Sheet","PDFbalanceSheet.php", "", "", "", "", "", "", "", "", "", ],
                ["||Funds Flow statement","PDFfundsflow.php", "", "", "", "", "", "", "", "", "", ],
                ["||Cash Flow Report","CashFlowReport.php", "", "", "", "", "", "", "", "", "", ],
    ["Fixed Assets","", "transactions.gif", "transactions.gif", "", "", "", "", "", "", "", ],
      ["|New","", "icon_xp3_3.gif", "icon_xp3_3o.gif", "", "", "", "", "", "", "", ],
            ["||LPO for Assets","PurchaseOderAssets.php", "", "", "", "", "", "", "", "", "", ],
            ["||GRN for Assets","PurchaseAssetsList.php", "", "", "", "", "", "", "", "", "", ],
            ["||Fixed Assets Purchase Invoice","FassetsReceivedNote.php", "", "", "", "", "", "", "", "", "", ],
            ["||Fixed Assets Depreciation Journal","FixedAssetDepreciation.php", "", "", "", "", "", "", "", "", "", ],
       ["|Report","", "icon_xp3_wa.gif", "icon_xp3_wao.gif", "", "", "", "", "", "", "", ],
                ["||Asset Register","FixedAssetRegister.php", "", "", "", "", "", "", "", "", "", ],
        ["|Transactions","", "icon_xp3_wa.gif", "icon_xp3_wao.gif", "", "", "", "", "", "", "", ],
                ["||Add or Maintain Asset Categories","FixedAssetCategories.php", "", "", "", "", "", "", "", "", "", ],
                ["||Add or Maintain Asset Locations","FixedAssetLocations.php", "", "", "", "", "", "", "", "", "", ],
                ["||Add or Maintain Asset Item","FixedAssetItems.php", "", "", "", "", "", "", "", "", "", ],
                ["||Select an Asset item","SelectAsset.php", "", "", "", "", "", "", "", "", "", ],
                ["||Change Asset Location","FixedAssetTransfer.php", "", "", "", "", "", "", "", "", "", ],
     
    
];

dm_init();