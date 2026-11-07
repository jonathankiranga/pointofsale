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
            ["||Customer Posting Group Setup","index.php?link=CustomersPostingGroups", "", "", "", "", "", "", "", "", "", ],
            ["||New Customer","index.php?link=Customer", "", "", "", "", "", "", "", "", "", ],
            
       ["|POST","", "icon_xp3_3.gif", "icon_xp3_3o.gif", "", "", "", "", "", "", "", ],
            ["||Cash Customer Receipts","index.php?link=receiptsprepayment", "", "", "", "", "", "", "", "", "", ],
            ["||Credit Notes","index.php?link=SalesDeliveryList", "", "", "", "", "", "", "", "", "", ],
            ["||Invoice Income Not From Samples","index.php?link=EnterSalesBills", "", "", "", "", "", "", "", "", "", ],
       ["|Print","", "icon_xp3_9.gif", "icon_xp3_9o.gif", "", "", "", "", "", "", "", ],
            ["||Customer Statements","index.php?link=PrintCustStatements", "", "", "", "", "", "", "", "", "", ],
            ["||Customer Ageing","index.php?link=PrintCustAgeing", "", "", "", "", "", "", "", "", "", ],
            ["||Print Customer Receipts","index.php?link=PDFprintreceipt", "", "", "", "", "", "", "", "", "", ],
            ["||VAT Sales Summury","index.php?link=PDFPrintDailySales", "", "", "", "", "", "", "", "", "", ],
            ["||VAT Purchases Summury","index.php?link=PDFPrintDailyPurchases", "", "", "", "", "", "", "", "", "", ],
            ["||Credit Notes VAT Sales Summury","index.php?link=PDFPrintDailyCreditnotes", "", "", "", "", "", "", "", "", "", ],
            ["||Debit Notes VAT Purchases Summury","index.php?link=PDFPurchaseCreditnotes", "", "", "", "", "", "", "", "", "", ],
        ["Suppliers","", "supplier.gif", "supplier.gif", "", "", "", "", "", "", "", ],
        ["|New","", "icon_xp3_3.gif", "icon_xp3_3o.gif", "", "", "", "", "", "", "", ],
            ["||New Supplier","index.php?link=Supplier", "", "", "", "", "", "", "", "", "", ],
            ["||Supplier Posting Group","index.php?link=SupplierPostingGroups", "", "", "", "", "", "", "", "", "", ],
        ["|Post Items","", "icon_xp3_wa.gif", "icon_xp3_wao.gif", "", "", "", "", "", "", "", ],
            ["||Create Services LPO","index.php?link=PurchaseOder", "", "", "", "", "", "", "", "", "", ],
            ["||Invoice LPO For Services","index.php?link=ServiceLPO", "", "", "", "", "", "", "", "", "", ],
            ["||Invoice Vendors Expenses that you dont Order","index.php?link=EnterBills", "", "", "", "", "", "", "", "", "", ],
         ["|Print","", "icon_xp3_9.gif", "icon_xp3_9o.gif", "", "", "", "", "", "", "", ],
            ["||Print Expense","index.php?link=PDFenterbills", "", "", "", "", "", "", "", "", "", ],
            ["||List of GRN","index.php?link=PDFPrintgrnNONStock", "", "", "", "", "", "", "", "", "", ],
            ["||List of LPO","index.php?link=PDFPrintPurchaseOrder", "", "", "", "", "", "", "", "", "", ],
            ["||Suppliers Statement","index.php?link=PrintvendorStatements", "", "", "", "", "", "", "", "", "", ],
     ["Cash Accounts","", "bank.gif", "bank.gif", "", "", "", "", "", "", "", ],
        ["|New","", "icon_xp3_3.gif", "icon_xp3_3o.gif", "", "", "", "", "", "", "", ],
            ["||New Bank/Cash Account","index.php?link=CreateBankAccount", "", "", "", "", "", "", "", "", "", ],
            ["||New Currency","index.php?link=Currencies", "", "", "", "", "", "", "", "", "", ],
            ["||Day Close for Petty Cash Reports","index.php?link=PettyCashUsers", "", "", "", "", "", "", "", "", "", ],
            
        ["|Post items","", "icon_xp3_wa.gif", "icon_xp3_wao.gif", "", "", "", "", "", "", "", ],
            ["||Receipts Allocation","index.php?link=ReceitsAllocation", "", "", "", "", "", "", "", "", "", ],
            ["||vendors Allocation","index.php?link=PaymentsAllocation", "", "", "", "", "", "", "", "", "", ],
            ["||Petty Cash/Imprest Module","index.php?link=PetteyCash&New=1", "", "", "", "", "", "", "", "", "", ],
            ["||Create Payment Voucher","index.php?link=PaymentVoucher", "", "", "", "", "", "", "", "", "", ],
            ["||Pay Approved Payment Vouchers","index.php?link=WriteCheque", "", "", "", "", "", "", "", "", "", ],
            ["||Debtors Reciepts","index.php?link=receiptsprepayment", "", "", "", "", "", "", "", "", "", ],
            ["||Bank Reconciliation","index.php?link=BankReconciliation&new=Yes", "", "", "", "", "", "", "", "", "", ],
         ["|Print","", "icon_xp3_9.gif", "icon_xp3_9o.gif", "", "", "", "", "", "", "", ],
            ["||Petty Cash Summary","index.php?link=PDFimprestReport", "", "", "", "", "", "", "", "", "", ],
            ["||List of Payment Vouchers","index.php?link=PDFpaymentvoucher", "", "", "", "", "", "", "", "", "", ],
     ["Items and Products","", "icon_mac_2.gif", "icon_mac_2o.gif", "", "", "", "", "", "", "", ],
            ["|New","", "icon_xp3_3.gif", "icon_xp3_3o.gif", "", "", "", "", "", "", "", ],
                ["||New Product and Items","index.php?link=Stocks", "", "", "", "", "", "", "", "", "", ],
                ["||New Units of Measure","index.php?link=unitsofmesure", "", "", "", "", "", "", "", "", "", ],
                ["||New Store or Location","index.php?link=stockstores", "", "", "", "", "", "", "", "", "", ],
                ["||New Posting Groups","index.php?link=InventoryPostingGroups", "", "", "", "", "", "", "", "", "", ],
                ["||New Category","index.php?link=stockitemcategories", "", "", "", "", "", "", "", "", "", ],
       
    ["|Post items","", "icon_xp3_wa.gif", "icon_xp3_wao.gif", "", "", "", "", "", "", "", ],
                ["||Create Purchase order","index.php?link=PurchaseOder", "", "", "", "", "", "", "", "", "", ],
                ["||Enter items Received For LPO","index.php?link=PurchaseOrderList", "", "", "", "", "", "", "", "", "", ],
                ["||Invoice Items From LPO","index.php?link=GoodsReceivedNote", "", "", "", "", "", "", "", "", "", ],
   
    ["|Print","", "icon_xp3_9.gif", "icon_xp3_9o.gif", "", "", "", "", "", "", "", ],
                ["||Stock Balance","index.php?link=PDFstockbalance", "", "", "", "", "", "", "", "", "", ],
                ["||Stock Movement Summary","index.php?link=StockMovements", "", "", "", "", "", "", "", "", "", ],
                
       ["General Ledger","", "transactions.gif", "transactions.gif", "", "", "", "", "", "", "", ],
            ["|New","", "icon_xp3_3.gif", "icon_xp3_3o.gif", "", "", "", "", "", "", "", ],
                ["||New General Ledger Item","index.php?link=ChartofAccounts", "", "", "", "", "", "", "", "", "", ],
                ["||Company Details","index.php?link=CompanyPreferences", "", "", "", "", "", "", "", "", "", ],
                ["||System Settings","index.php?link=SystemParameters", "", "", "", "", "", "", "", "", "", ],
                ["||Maintain Dimension","index.php?link=DimensionTypes", "", "", "", "", "", "", "", "", "", ],
                ["||General Ledger Posting Groups","index.php?link=GLPostingGroups", "", "", "", "", "", "", "", "", "", ],
                ["||VAT Categories","index.php?link=vatcategory", "", "", "", "", "", "", "", "", "", ],
                 ["||System Settings","", "", "", "", "", "", "", "", "", "", ],
                 ["|||New User","index.php?link=WWW_Users", "", "", "", "", "", "", "", "", "", ],
                 ["|||User Roles","index.php?link=WWW_Access", "", "", "", "", "", "", "", "", "", ],
                 ["|||Create Page Security Tokens","index.php?link=SecurityTokens", "", "", "", "", "", "", "", "", "", ],
                 ["|||Page Security Settings","index.php?link=PageSecurity", "", "", "", "", "", "", "", "", "", ],
                 ["|||View Audit Trail","index.php?link=AuditTrail", "", "", "", "", "", "", "", "", "", ],
                 ["|||Manage Discrepancy","index.php?link=fellowgroups", "", "", "", "", "", "", "", "", "", ],
    
    ["|Transactions","", "icon_xp3_wa.gif", "icon_xp3_wao.gif", "", "", "", "", "", "", "", ],
                ["||Create Budget","index.php?link=Budgets", "", "", "", "", "", "", "", "", "", ],
                ["||Post General Journal","index.php?link=Journal&new=1", "", "", "", "", "", "", "", "", "", ],
                ["||Post Cash Journal","index.php?link=cashJournal", "", "", "", "", "", "", "", "", "", ],
                ["||Edit Ledger Account Setting","index.php?link=Generalledgerist", "", "", "", "", "", "", "", "", "", ],
                ["||Close Financial Periods","index.php?link=FinancialPeriods&new=1", "", "", "", "", "", "", "", "", "", ],
 
    ["|Reports","", "icon_xp3_9.gif", "icon_xp3_9o.gif", "", "", "", "", "", "", "", ],
                ["||Ledger Quick Report","index.php?link=selectReports", "", "", "", "", "", "", "", "", "", ],
                ["||Trial Balance","index.php?link=PDFFinacials", "", "", "", "", "", "", "", "", "", ],
                ["||Project Income statement","index.php?link=PDFprofitlossByProject", "", "", "", "", "", "", "", "", "", ],
                ["||Consolidated Income statement","index.php?link=PDFprofitloss", "", "", "", "", "", "", "", "", "", ],
                ["||Monthly Consolidated Income statement","index.php?link=PDFprofitlossbymonth", "", "", "", "", "", "", "", "", "", ],
                ["||Balance Sheet","index.php?link=PDFbalanceSheet", "", "", "", "", "", "", "", "", "", ],
                ["||Funds Flow statement","index.php?link=PDFfundsflow", "", "", "", "", "", "", "", "", "", ],
                ["||Cash Flow Report","index.php?link=CashFlowReport", "", "", "", "", "", "", "", "", "", ],
    ["Fixed Assets","", "transactions.gif", "transactions.gif", "", "", "", "", "", "", "", ],
      ["|New","", "icon_xp3_3.gif", "icon_xp3_3o.gif", "", "", "", "", "", "", "", ],
            ["||LPO for Assets","index.php?link=PurchaseOderAssets", "", "", "", "", "", "", "", "", "", ],
            ["||GRN for Assets","index.php?link=PurchaseAssetsList", "", "", "", "", "", "", "", "", "", ],
            ["||Fixed Assets Purchase Invoice","index.php?link=FassetsReceivedNote", "", "", "", "", "", "", "", "", "", ],
            ["||Fixed Assets Depreciation Journal","index.php?link=FixedAssetDepreciation", "", "", "", "", "", "", "", "", "", ],
       ["|Report","", "icon_xp3_wa.gif", "icon_xp3_wao.gif", "", "", "", "", "", "", "", ],
                ["||Asset Register","index.php?link=FixedAssetRegister", "", "", "", "", "", "", "", "", "", ],
        ["|Transactions","", "icon_xp3_wa.gif", "icon_xp3_wao.gif", "", "", "", "", "", "", "", ],
                ["||Add or Maintain Asset Categories","index.php?link=FixedAssetCategories", "", "", "", "", "", "", "", "", "", ],
                ["||Add or Maintain Asset Locations","index.php?link=FixedAssetLocations", "", "", "", "", "", "", "", "", "", ],
                ["||Add or Maintain Asset Item","index.php?link=FixedAssetItems", "", "", "", "", "", "", "", "", "", ],
                ["||Select an Asset item","index.php?link=SelectAsset", "", "", "", "", "", "", "", "", "", ],
                ["||Change Asset Location","index.php?link=FixedAssetTransfer", "", "", "", "", "", "", "", "", "", ],
     
    
];

dm_init();