<?php
/* $Id: MainMenuLinksArray.php 6190 2013-08-12 02:12:02Z rchacon $*/
/* webERP menus with Captions and URLs. */
$ModuleLink = array('Sales',
    'AccountsReceivable',
    'Purchases',
    'AccountsPayable',
    'CashManagement',
    'GeneralLedger',
    'Inventory',
    'Manufacturing',
    'FixedAssets',
    'system',
    'Approval',
    'cRM');

$ReportList = array('Sales'=>'so',
    'AccountsReceivable'=>'ar',
    'Purchases'=>'po',
    'AccountsPayable'=>'ap',
    'CashManagement'=>'cm',
    'GeneralLedger'=>'GL',
    'Inventory'=>'in',
    'Manufacturing'=>'ma',
    'FixedAssets'=>'fa',
    'system'=>'sys',
    'Approval'=>'apo',
    'cRM'=>'CRM');

/*The headings showing on the tabs accross the main index used also in WWW_Users for defining what should be visible to the user */
$ModuleList = array(
    _('Sample Registration'),
    _('Accounts Receivable'),
    _('Purchases'),
    _('Accounts Payable'),
    _('Cash Management'),
    _('General Ledger'),
    _('Inventory'),
    _('Manufacturing'),
    _('Fixed Assets'),
    _('Administrator'),
    _('Document Authorisation'),
    _('Customer Relations') );




//*******//
$MenuItems['FixedAssets']['Transactions']['Caption'] = array(_('LPO for Assets'),
                                                             _('GRN for Assets'),
                                                             _('Fixed Assets Enter Bill'),
                                                             _('Depreciation Journal'),
                                                             _('Request Hire for Equipment/Assets'),
                                                             _('Issue Equipment/Assets for Hire'));

$MenuItems['FixedAssets']['Transactions']['URL'] = array('/PurchaseOderAssets.php',
                                                         '/PurchaseAssetsList.php',
                                                         '/FassetsReceivedNote.php',
                                                         '/FixedAssetDepreciation.php',
                                                         '/HireAssets.php',
                                                         '/HireOutAssetsList.php' );

$MenuItems['FixedAssets']['Reports']['Caption'] = array(_('Asset Register'),
                                                        _('Maintenance Schedule'),
                                                        _('Send Emails : Maintenance Reminder '));

$MenuItems['FixedAssets']['Reports']['URL'] = array('/FixedAssetRegister.php',
                                                    '/MaintenanceUserSchedule.php',
                                                    '/MaintenanceReminders.php');

$MenuItems['FixedAssets']['Maintenance']['Caption'] = array(_('Add or Maintain Asset Categories'),
                                                            _('Add or Maintain Asset Locations'),
                                                            _('Register Asset Maintenance Tasks'),
                                                            _('Add or Maintain Asset Item'),
                                                            _('Select an Asset item'),
                                                            _('Change Asset Location'));

$MenuItems['FixedAssets']['Maintenance']['URL'] = array('/FixedAssetCategories.php',
                                                        '/FixedAssetLocations.php',
                                                        '/MaintenanceTasks.php',
                                                        '/FixedAssetItems.php',
                                                        '/SelectAsset.php',
                                                        '/FixedAssetTransfer.php',);

//******CRM***************//
$MenuItems['cRM']['Transactions']['Caption'] = array(_('Create Targets'),
                                                     _('Create Appointment'),
                                                     _('Send status Reports'));


$MenuItems['cRM']['Transactions']['URL'] = array('/SelectSalesRep.php',
                                                '/crmclientsTasks.php',
                                                '/customerReminders.php');



$MenuItems['cRM']['Reports']['Caption'] = array(_('Sales Reps Timetable'),
                                                _('Sales Reps Debts collection Visits'),
                                                _('Sales Reps Target Report by Month'));


$MenuItems['cRM']['Reports']['URL'] = array('/SalesFollowups.php',
                                           '/debtsFollowups.php',
                                           '/SalesRepTarget.php');


$MenuItems['cRM']['Maintenance']['Caption'] = array(_('Add Customer'),
                                                    _('Add Lead Status'),
                                                    _('Add Campaign Methods'));

$MenuItems['cRM']['Maintenance']['URL'] = array('/Customer.php','/','/');


//********************//
$MenuItems['CashManagement']['Transactions']['Caption'] = array(_('Cash Journal'),
                                                                _('Payment Voucher Preparation'),
                                                                _('Write Cheques'),
                                                                _('Print Cheques Remittance'),
                                                                _('Receive Customer Deposits'),
                                                                _('Petty Cash'),
                                                                _('Bank Reconciliation'));

$MenuItems['CashManagement']['Transactions']['URL'] = array('/cashJournal.php',
                                                            '/PaymentVoucher.php',
                                                            '/WriteCheque.php', 
                                                            '/PDFcheque.php',
                                                            '/receiptsprepayment.php',
                                                            '/PetteyCash.php?New=1',
                                                            '/BankReconciliation.php?new=Yes');


$MenuItems['CashManagement']['Reports']['Caption'] = array(_('Print Customer Receipts' ),
                                                           _('Print Payment Vouchers'),
                                                           _('Print Cheque Remittance'),
                                                           _('Print Bank Reconciliation Reports'));

$MenuItems['CashManagement']['Reports']['URL'] = array('/PDFprintreceipt.php',
                                                        '/PDFpaymentvoucher.php',
                                                        '/PDFcheque.php',
                                                        '/PDFbankreconciliation.php');

$MenuItems['CashManagement']['Maintenance']['Caption'] = array(_('Create Banks'),
                                                               _('View Currency Trends'),
                                                               _('Exchange Rates'));


$MenuItems['CashManagement']['Maintenance']['URL'] = array('/CreateBankAccount.php',
                                                           '/ExchangeRateTrend.php',
                                                           '/Currencies.php');

//Budgets.php

//*********************//

$MenuItems['Approval']['Transactions']['Caption'] = array(_('Approve Inventory trade-in'),
                                                          _('FAM Approve Payment Voucher'),
                                                          _('CEO Approve Payment Voucher'));

$MenuItems['Approval']['Transactions']['URL'] = array('/InventoryExhange.php',
                                                      '/PDFFAMpaymentvoucher.php',
                                                      '/PDFCEOpaymentvoucher.php');

$MenuItems['Approval']['Reports']['Caption'] = array('Payment Voucher');

$MenuItems['Approval']['Reports']['URL'] = array('/PDFpaymentvoucher.php');

$MenuItems['Approval']['Maintenance']['Caption'] = array(_('Create Dimensions'),
                                                         _('Create Budgets'));

$MenuItems['Approval']['Maintenance']['URL'] = array('/DimensionTypes.php',
                                                     '/Budgets.php');

/***********************************************/
$MenuItems['Sales']['Transactions']['Caption'] = array(_('Point of Sale (Touch)'),
                                                       _('Lab Sample Registration'),
                                                       _('Lab Data Entry'),
                                                       _('Lab First Review'),
                                                       _('Lab Final Review'),
                                                       _('Sales Invoice'),
                                                       _('Sales Quotation'));

$MenuItems['Sales']['Transactions']['URL'] = array('/posindex.php',
                                                   '/Laboratory.php',
                                                   '/LaboratoryDataEntry.php',
                                                   '/LaboratoryReview.php',
                                                   '/LaboratoryFinalReview.php',
                                                   '/SalesInvoice.php',
                                                   '/SalesQuotation.php');



$MenuItems['Sales']['Reports']['Caption'] = array(_('Print Lab Test Report'),
                                                  _('RePrint Sales Invoice'),
                                                  _('RePrint Sales Quotation'));

$MenuItems['Sales']['Reports']['URL'] = array('/PDFLaboratorytest.php',
                                              '/PDFPrintSalesInvoice.php',
                                              '/PDFPrintSalesQuote.php');



$MenuItems['Sales']['Maintenance']['Caption'] = array(_('New/Edit TEST Types'),
                                                      _('New/Edit LAB Standards'));


$MenuItems['Sales']['Maintenance']['URL'] = array('/SampleType.php',
                                                  '/labarotoryStandards.php');

/***********************************************/
$MenuItems['AccountsReceivable']['Transactions']['Caption'] = array(_('Receipts'),
                                                                    _('Receipts Allocation'),
                                                                    _('Print Receipts'));

$MenuItems['AccountsReceivable']['Transactions']['URL'] = array('/receipts.php',
                                                                '/ReceitsAllocation.php',
                                                                '/PDFprintreceipt.php');

$MenuItems['AccountsReceivable']['Reports']['Caption'] = array(_('Customer Statements'),
                                                               _('Customer Ageing Analysis'));

$MenuItems['AccountsReceivable']['Reports']['URL'] = array('/PrintCustStatements.php'
                                                          ,'/PrintCustAgeing.php');

$MenuItems['AccountsReceivable']['Maintenance']['Caption'] = array(_('Customer Posting Groups'),
                                                                   _('Maintain Customer'),  
                                                                   _('New Sales Rep'),
                                                                   _('Update Sales Rep Details'));

$MenuItems['AccountsReceivable']['Maintenance']['URL'] = array('/CustomersPostingGroups.php',
                                                               '/Customer.php',
                                                               '/SalesMan.php',
                                                               '/SelectSalesRep.php');

/***********************************************/
$MenuItems['Purchases']['Transactions']['Caption'] = array(_('Purchase\'s Order'),
                                                           _('Goods Received Note'),
                                                           _('Supplier\'s Purchase Invoice'),
                                                           _('Supplier\'s Return Stock'),
                                                           _('Payables Debit Note'));


$MenuItems['Purchases']['Transactions']['URL'] = array('/PurchaseOder.php',
                                                       '/PurchaseOrderList.php',
                                                       '/GoodsReceivedNote.php',
                                                       '/PurchaseOrderDBlist.php',
                                                       '/PurchasevendorDBlist.php');


$MenuItems['Purchases']['Reports']['Caption'] = array(_('Last '. $_SESSION['DefaultDisplayRecordsMax'].' Goods Received Notes'),
                                                      _('Last '. $_SESSION['DefaultDisplayRecordsMax'].' Purchase Orders'),
                                                      _('Last '. $_SESSION['DefaultDisplayRecordsMax'].' Fixed assets GRN'));

$MenuItems['Purchases']['Reports']['URL'] = array('/PDFPrintgrn.php',
                                                  '/PDFPrintPurchaseOrder.php',
                                                  '/PDFPrintAssetgrn.php');

// Vendor Posting Groups and Maintain Supplier are already available under Accounts Payable.
// Keep a single menu entry for each destination to avoid duplicate navigation items.


/***********************************************/
$MenuItems['Inventory']['Transactions']['Caption'] = array(_('Purchase Order'),
                                                           _('Receive Goods for PO'),
                                                           _('Stock Transfer'),
                                                           _('Stock Issues (For BQ)'));

$MenuItems['Inventory']['Transactions']['URL'] = array('/PurchaseOder.php',
                                                       '/PurchaseOrderList.php',
                                                       '/Stocktransfer.php',
                                                       '/StockIssues.php');

$MenuItems['Inventory']['Reports']['Caption'] = array(_('BOM Issues'),
                                                      _('Stock Report'),
                                                      _('Bin Card Report'),
                                                      _('Materials Usage Planning'));

$MenuItems['Inventory']['Reports']['URL'] = array('/PDFBQissues.php',
                                                   '/PDFstockbalance.php',
                                                   '/StockMovements.php',
                                                   '/BOMcomparison.php');

$MenuItems['Inventory']['Maintenance']['Caption'] = array(_('New Stock'),
                                                          _('Stock Category'),
                                                          _('Inventory Posting Groups'),
                                                          _('Units of measure'));


$MenuItems['Inventory']['Maintenance']['URL'] = array('/Stocks.php',
                                                      '/stockitemcategories.php',
                                                      '/InventoryPostingGroups.php',
                                                      '/unitsofmesure.php');
/***********************************************************************************/
$MenuItems['Manufacturing']['Transactions']['Caption'] = array(_('Work Center Production'),
                                                               _('Work Center Quality Check'),
                                                               _('Receive Raw materials for BOM'),
                                                               _('Create Work Order BOM '),
                                                               _('BOM Finished Items'),
                                                               _('Production Batch No and Lab Results'),
                                                               _('Production Cutback'),
                                                               _('Sample Registration window (Laboratory)'));

$MenuItems['Manufacturing']['Transactions']['URL'] = array('/worksheetentry.php',
                                                           '/PDFPrintEmployeeWorkOrder.php',
                                                           '/WorkorderRequisation.php',
                                                           '/Workorder.php',
                                                           '/ProductionRequisation.php',
                                                           '/ProductionSummary.php',
                                                           '/ProductionCutback.php',
                                                           '/SalesLabtests.php');

$MenuItems['Manufacturing']['Reports']['Caption'] = array(_('Laboratory Testing List'),
                                                          _('Production of BOM Summary List'),
                                                          _('Work Center Production List'),
                                                          _('Work Center Wages Calculation'));

$MenuItems['Manufacturing']['Reports']['URL'] = array('/PDFPrintLabtest.php',
                                                      '/PDFproductionsummary.php',
                                                      '/PDFPrintQCquantities.php',
                                                      '/PDFPrintMonthlyProduction.php');

$MenuItems['Manufacturing']['Maintenance']['Caption'] = array(_('Create Bill-Of-Materials'),
                                                              _('Define Storage Capacity'),
                                                              _('Production Manager'),
                                                              _('Production Employee'),
                                                              _('Production Rates by employee'));

$MenuItems['Manufacturing']['Maintenance']['URL'] = array('/Billofmaterial.php',
                                                          '/Tanksunit.php',
                                                          '/productionManager.php',
                                                          '/productionEmployee.php',
                                                          '/productionRates.php');


/******************************************************************************************/
$MenuItems['AccountsPayable']['Transactions']['Caption'] = array( _('Enter Bill'),
                                                                  _('Payment Voucher'),
                                                                  _('Allocate Invoices'),
                                                                  _('Receive Toll Blending'));

$MenuItems['AccountsPayable']['Transactions']['URL'] = array('/EnterBills.php',
                                                             '/PaymentVoucher.php',
                                                             '/PaymentsAllocation.php',
                                                             '/Tradein.php');

$MenuItems['AccountsPayable']['Reports']['Caption'] = array(_('Vendor Statement'),
                                                            _('Vendor Ageing Analysis'),
                                                           _('View payment vouchers') );

$MenuItems['AccountsPayable']['Reports']['URL'] = array('/PrintvendorStatements.php',
                                                        '/PrintSuppAgeing.php',
                                                        '/PDFpaymentvoucher.php');

$MenuItems['AccountsPayable']['Maintenance']['Caption'] = array(_('Vendor Posting Groups'),
                                                                _('Maintain Supplier'));

$MenuItems['AccountsPayable']['Maintenance']['URL'] = array('/SupplierPostingGroups.php',
                                                            '/Supplier.php');

//*****************************************************************
$MenuItems['GeneralLedger']['Transactions']['Caption']=array(_('General Journals'),
                                                             _('Cash Journals'));

$MenuItems['GeneralLedger']['Transactions']['URL'] = array( '/Journal.php?new=1',
                                                            '/cashJournal.php');


$MenuItems['GeneralLedger']['Reports']['Caption']=array(_('Ledger Reports'),
                                                        _('Mody Ledger Details'),
                                                        _('Trial Balance'),
                                                        _('Annual Income Statement'),
                                                        _('Monthly Income Statement'),
                                                        _('Balance Sheet'),
                                                        _('FundsFlow Summary Report'),
                                                         _('Budget Summary Report'),
                                                         _('Project Report'));





$MenuItems['GeneralLedger']['Reports']['URL'] = array('/selectReports.php',
                                                      '/Generalledgerist.php',
                                                      '/PDFFinacials.php',
                                                      '/PDFprofitloss.php',
                                                      '/PDFprofitlossbymonth.php',
                                                      '/PDFbalanceSheet.php',
                                                      '/PDFfundsflow.php',
                                                      '/PDFbudgets.php',
                                                      '/PDFprojects.php');



$MenuItems['GeneralLedger']['Maintenance']['Caption'] = array(_('Company Preferences'),
                                                              _('System Parameters'),
                                                              _('Chart of Accounts'),
                                                              _('Create Dimension Types'),
                                                              _('Create/Close Financial Periods'),
                                                              _('Create GL Posting Groups'),
                                                              _('Create VAT Categories'),
                                                              _('Income statement setup'),
                                                              _('Balance Sheet $ Funds Flow setup'));


$MenuItems['GeneralLedger']['Maintenance']['URL'] = array('/CompanyPreferences.php',
                                                          '/SystemParameters.php',
                                                          '/ChartofAccounts.php',
                                                          '/DimensionTypes.php',
                                                          '/FinancialPeriods.php?new=1',
                                                          '/GLPostingGroups.php',
                                                          '/vatcategory.php',
                                                          '/Setupincomestatement.php',
                                                          '/Setupbalancesheet.php');
//ChartofAccounts.php

//*************************************************************
$MenuItems['system']['Transactions']['Caption'] = array(_('Users Maintenance'),
                                                        _('Maintain Security Tokens'),
                                                        _('Access Permissions Maintenance'),
                                                        _('Page Security Settings'),
                                                        _('SMTP Server Details'),
                                                        _('Mailing Group Maintenance'));

$MenuItems['system']['Transactions']['URL'] = array('/WWW_Users.php',
                                                    '/SecurityTokens.php',
                                                    '/WWW_Access.php',
                                                    '/PageSecurity.php',
                                                    '/SMTPServer.php',
                                                    '/MailingGroupMaintenance.php');

$MenuItems['system']['Reports']['Caption'] = array(_('List Periods Defined'),
                                                   _('View Audit Trail'));

$MenuItems['system']['Reports']['URL'] = array('/PeriodsInquiry.php',
                                               '/AuditTrail.php');


$MenuItems['system']['Maintenance']['Caption'] = array(_('Company Preferences'),
                                                        _('System Parameters'));


$MenuItems['system']['Maintenance']['URL'] = array('/CompanyPreferences.php',
                                                   '/SystemParameters.php');

/***********************************************/




?>
