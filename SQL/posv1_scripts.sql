-- MySQL dump 10.13  Distrib 8.0.26, for Win64 (x86_64)
--
-- Host: localhost    Database: posv1
-- ------------------------------------------------------
-- Server version	8.0.26

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `scripts`
--

DROP TABLE IF EXISTS `scripts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scripts` (
  `script` varchar(78) NOT NULL,
  `pagesecurity` int NOT NULL DEFAULT '1',
  `description` longtext NOT NULL,
  PRIMARY KEY (`script`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scripts`
--

LOCK TABLES `scripts` WRITE;
/*!40000 ALTER TABLE `scripts` DISABLE KEYS */;
INSERT INTO `scripts` VALUES ('ApproveCEOVoucher.php',8,'CEO approval'),('ApprovePurchase.php',8,'Approve Purchase Order'),('ApproveVoucher.php',8,'Approve Payment Voucher'),('AuditTrail.php',0,'index.php'),('BankReconciliation.php',7,'Reconciliation'),('bankregister.php',7,'Cash book Register'),('Budgets.php',8,'Budgets'),('CashFlowReport.php',8,'CashFlow Report'),('cashJournal.php',8,'Cash Journal'),('ChartofAccounts.php',8,'Chart of Accounts'),('CompanyPreferences.php',8,' '),('ConfirmServiceInvoice.php',8,'Confirm Service Invoice'),('CreateBankAccount.php',7,'Create bank accounts'),('CreditNote.php',8,'Credit Note'),('crmclientsTasks.php',0,'CRM'),('Currencies.php',8,'Update Currencies'),('Customer.php',0,'Create Customer'),('customerReminders.php',0,'Customer relations'),('CustomersPostingGroups.php',0,' '),('debtsFollowups.php',8,'Customer Relations'),('DimensionTypes.php',8,'Dimension Setup'),('EditPostedSalesinvoice.php',8,'Edit Lab Report'),('EnterBills.php',5,'Supplier enter bills'),('EnterSalesBills.php',8,'Sales Enter Bills'),('ExchangeRateTrend.php',7,' '),('FassetsReceivedNote.php',8,'FA GRN'),('FinancialPeriods.php',8,'Fiancial Periods'),('FixedAssetCategories.php',7,'Fixed Assets Category'),('FixedAssetDepreciation.php',8,'Fixed assets depreciation'),('FixedAssetItems.php',8,'Fixed assets Items'),('FixedAssetLocations.php',8,'Fixed Assets locations'),('FixedAssetRegister.php',8,'Fixed assets register'),('FixedAssetsBackTostore.php',8,'Hire/Equipment returned'),('FixedAssetsHire.php',8,'Hire/Equipment issue'),('FixedAssetsHireReturned.php',8,'Hire/Equipment Returned'),('FixedAssetsReceived.php',8,'Receive Assets'),('FixedAssetTransfer.php',8,'Fixed assets transfer'),('Generalledgerist.php',8,'General Ledger view'),('geocode.php',0,' '),('GeocodeSetup.php',0,' '),('GLPostingGroups.php',8,'GL posting groups'),('GoodsReceivedNote.php',5,'Print GRN'),('Goodsrecievedfromorders.php',5,'Goods Received Note'),('Goodsreturnedtovendors.php',5,'Debit Notes'),('HireAssets.php',8,'Hire Fixed assets'),('HireOutAssetsList.php',8,'Equipment Request'),('index.php',0,'system(used by all)'),('InventoryExhange.php',15,'Inventory Exhange'),('InventoryPostingGroups.php',8,'Inventory Posting Group'),('Journal.php',8,'Post Journal'),('LedgerReports.php',8,'Ledger Reports'),('Logout.php',0,'system(used by all)'),('MailingGroupMaintenance.php',0,' '),('MaintenanceReminders.php',0,'Maintenace Reminders'),('MaintenanceTasks.php',0,'Fixed assets maintenace task'),('MaintenanceUserSchedule.php',0,'Fixed assets Maintenace users'),('MRPreport.php',0,'MRP'),('MultipleSalescreditnote.php',3,'Cash book Register'),('MultipleSalesselectList.php',3,'Cash book Register'),('PageSecurity.php',15,' '),('PaymentsAllocation.php',8,'Payments Allocation'),('PaymentVoucher.php',5,'Payment Vouchers Preparation'),('PDFbalanceSheet.php',8,'Balance Sheet'),('PDFbankreconciliation.php',7,'Print Bank statements'),('PDFbudgets.php',8,'Print Budgets'),('PDFCEOpaymentvoucher.php',8,'CEO approval PV'),('PDFcheque.php',8,'Print Cheque'),('PDFenterbills.php',6,'Print Enter Bills'),('PDFFAMpaymentvoucher.php',5,'Approval for FAM'),('PDFFinacials.php',8,'Print Financial Reports'),('PDFfundsflow.php',8,'Funds Flow statement'),('PDFimprestReport.php',6,'Print Pettey Cash Report'),('PDFpaymentvoucher.php',5,'Print Payment Voucher'),('PDFPrintAssetgrn.php',0,'PDF FA GRN'),('PDFPrintAssetshire.php',0,'Hire/Equipment Invoice'),('PDFPrintAssetsOrder.php',0,'PDF FA lpo'),('PDFPrintcreditnote.php',0,'Print Credit Note'),('PDFPrintdebitnote.php',0,'Debit Note Report'),('PDFPrintgrn.php',0,'Print GRN'),('PDFPrintPickingList.php',0,'Print Picking List'),('PDFPrintPurchaseOrder.php',0,'LPO printing'),('PDFprintreceipt.php',0,'Print receipt'),('PDFPrintReturnsList.php',0,'Returns inwards'),('PDFPrintSalesInvoice.php',0,'Print Sales Invoice'),('PDFPrintSalesOrder.php',0,'Sales Order'),('PDFPrintSalesQuote.php',0,'Print Sales Quotation'),('PDFPrintServiceOrder.php',0,'Service Invoice'),('PDFPrintVATcreditnote.php',8,'VAT CREDITNOTE'),('PDFproductionsummary.php',0,'Print Production'),('PDFprofitloss.php',8,'Profit & Loss Account'),('PDFprofitlossbymonth.php',8,'Profit And Loss by month'),('PDFprojects.php',8,'Budgets'),('PDFstockbalance.php',5,'Stock Report'),('PDFstockValue.php',0,'Stock Value Report'),('PeriodsInquiry.php',8,' '),('PetteyCash.php',6,'Pettey Cash Module'),('PettyCashUsers.php',6,'Petty Cash Users'),('posindex.php',4,'point of sale'),('PrintCustAgeing.php',8,'Aging Reports'),('PrintCustStatements.php',8,'Print Customer Statements'),('PrintSuppAgeing.php',8,'Print Ageing Reports'),('PrintvendorStatements.php',8,'Print Vendor statements'),('ProductionCutback.php',0,'Production cutback'),('ProductionRequisation.php',0,'Production Requisation'),('ProductionSummary.php',0,'Lab Test'),('PurchaseAssetsList.php',0,'Fixed Assets'),('PurchaseOder.php',5,'Create Purchase orders'),('PurchaseOderAssets.php',5,'Purchase Assets'),('PurchaseOrderDBlist.php',5,'Debit Note list'),('PurchaseOrderList.php',5,'Purchase order Printing'),('PurchasevendorDBlist.php',8,'Debit Note'),('receipts.php',7,'Customer Receipts'),('receiptsprepayment.php',7,'Receipts Prepayment'),('ReceitsAllocation.php',7,'Receipts Allocation'),('Salescreditnote.php',8,'Sales Credit Note'),('SalesDelivery.php',8,'Enter Delivery'),('SalesDeliveryList.php',8,'Sales Returns'),('SalesFollowups.php',0,'Customer Relations'),('SalesInvoice.php',0,'Sales Invoice'),('SalesInvoicefromorders.php',0,'Sales Invoice from sales orders'),('SalesMan.php',0,'Sales Man setup'),('SalesOder.php',0,'Sales orders'),('SalespickingList.php',0,'Sales picking List'),('SalesQuotation.php',8,'Sales Quotation'),('SalesRepTarget.php',0,'Customer Relations'),('SalesStockReturns.php',0,'Credit Note'),('SecurityTokens.php',15,' '),('SelectAsset.php',0,'Select Fixed asste'),('SelectCustomer.php',8,'Find Customer'),('SelectProduct.php',8,'Select Inventory'),('selectReports.php',8,'Print Ledger summary'),('SelectSalesRep.php',8,'Edit sales Rep'),('SelectSupplier.php',5,'Find Supplier'),('serviceInvoicecompleted.php',0,'Service Invoices Completed'),('ServiceLPO.php',0,'Service LPO'),('ServiceOder.php',0,'Service Invoice'),('Setupbalancesheet.php',10,'Balance sheet setup'),('Setupincomestatement.php',10,'Setup income statement'),('SMTPServer.php',0,' '),('StockAdjustments.php',10,'Stock Adjustment'),('StockIssues.php',0,'Stock Issues (For Constuction)'),('stockitemcategories.php',0,'Stock Categories'),('StockMovements.php',0,'Stock movement'),('Stocks.php',0,'Create Stocks'),('stockstores.php',0,'Stores'),('Stocktransfer.php',0,'Stock Transfer'),('Supplier.php',5,'Create Supplier'),('Supplierdebitnote.php',8,'Debit Note'),('SupplierInvoice.php',8,'Supplier invoice'),('SupplierInvoiceFa.php',8,'Fixed Assets Supplier invoice'),('SupplierPostingGroups.php',5,' '),('SupplierServiceInvoice.php',5,'Service LPO'),('SystemParameters.php',10,' '),('unitsofmesure.php',10,'Unit of measure'),('UserSettings.php',0,'system(used by all)'),('vatcategory.php',8,'VAT Setup'),('WriteCheque.php',8,'Write cheques'),('WWW_Access.php',8,' '),('WWW_Users.php',8,' ');
/*!40000 ALTER TABLE `scripts` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05 12:14:24
