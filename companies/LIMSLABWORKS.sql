-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 22, 2024 at 12:13 PM
-- Server version: 10.6.16-MariaDB
-- PHP Version: 8.2.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `LIMSLABWORKS`
--

-- --------------------------------------------------------

--
-- Table structure for table `accountsetup`
--

CREATE TABLE `accountsetup` (
  `salesfrom` char(10) NOT NULL,
  `salesto` char(10) NOT NULL,
  `stockfrom` char(10) NOT NULL,
  `stockto` char(10) NOT NULL,
  `purchasesfrom` char(10) NOT NULL,
  `purchasesto` char(10) NOT NULL,
  `expensefrom` char(10) NOT NULL,
  `expenseto` char(10) NOT NULL,
  `fixedassetsFrom` char(10) DEFAULT NULL,
  `fixedassetsTo` char(10) DEFAULT NULL,
  `inventoryFrom` char(10) DEFAULT NULL,
  `inventoryTo` char(10) DEFAULT NULL,
  `DebtorsFrom` char(10) DEFAULT NULL,
  `DebtorsTo` char(10) DEFAULT NULL,
  `BankFrom` char(10) DEFAULT NULL,
  `BankTo` char(10) DEFAULT NULL,
  `CurLiabFrom` char(10) DEFAULT NULL,
  `CurLiabTo` char(10) DEFAULT NULL,
  `LongLiabFrom` char(10) DEFAULT NULL,
  `LongLiabTo` char(10) DEFAULT NULL,
  `CapitalFrom` char(10) DEFAULT NULL,
  `CapitalTo` char(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acct`
--

CREATE TABLE `acct` (
  `ReportStyle` int(11) DEFAULT NULL,
  `ReportCode` varchar(10) DEFAULT NULL,
  `Sale_Purchase_Neither` int(11) DEFAULT NULL,
  `Calculation` longtext DEFAULT NULL,
  `system` tinyint(1) DEFAULT NULL,
  `direct` tinyint(1) DEFAULT NULL,
  `balance_income` int(11) DEFAULT NULL,
  `accgrp` char(2) DEFAULT NULL,
  `currency` char(3) DEFAULT NULL,
  `accno` char(20) DEFAULT NULL,
  `accdesc` char(30) NOT NULL,
  `oldaccno` char(20) DEFAULT NULL,
  `postinggroup` char(20) DEFAULT NULL,
  `inactive` tinyint(1) NOT NULL,
  `pkey` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `arpostinggroups`
--

CREATE TABLE `arpostinggroups` (
  `code` varchar(20) NOT NULL,
  `purchaseaccount` char(20) NOT NULL,
  `creditorsaccount` char(20) NOT NULL,
  `VATinclusive` tinyint(1) DEFAULT NULL,
  `IsTaxed` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assetsheader`
--

CREATE TABLE `assetsheader` (
  `documenttype` int(11) NOT NULL,
  `documentno` varchar(20) NOT NULL,
  `docdate` datetime DEFAULT NULL,
  `oderdate` datetime DEFAULT NULL,
  `duedate` datetime DEFAULT NULL,
  `postingdate` datetime DEFAULT NULL,
  `vendorcode` varchar(20) NOT NULL,
  `vendorname` varchar(50) NOT NULL,
  `yourreference` varchar(30) DEFAULT NULL,
  `externaldocumentno` varchar(30) DEFAULT NULL,
  `locationcode` char(10) DEFAULT NULL,
  `paymentterms` char(10) DEFAULT NULL,
  `postinggroup` char(20) NOT NULL,
  `currencycode` char(10) NOT NULL,
  `printed` int(11) DEFAULT NULL,
  `released` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `userid` char(20) NOT NULL,
  `period` int(11) NOT NULL,
  `entryno` bigint(20) NOT NULL,
  `vatinclusive` tinyint(1) DEFAULT NULL,
  `Dimension_1` char(10) DEFAULT NULL,
  `Dimension_2` char(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audittrail`
--

CREATE TABLE `audittrail` (
  `transactiondate` timestamp NOT NULL DEFAULT current_timestamp(),
  `userid` varchar(20) NOT NULL,
  `querystring` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bankaccounts`
--

CREATE TABLE `bankaccounts` (
  `PKey` int(11) NOT NULL,
  `accountcode` char(10) DEFAULT NULL,
  `bankName` varchar(50) NOT NULL,
  `currency` char(3) NOT NULL,
  `lastreconcileddate` date DEFAULT NULL,
  `AccountNo` char(20) DEFAULT NULL,
  `BranchCode` char(10) DEFAULT NULL,
  `BranchName` varchar(50) DEFAULT NULL,
  `lastreconbalance` decimal(18,2) DEFAULT NULL,
  `StatementNo` int(11) DEFAULT NULL,
  `lastChequeno` int(11) DEFAULT NULL,
  `PostingGroup` char(20) NOT NULL,
  `Makeinactive` tinyint(1) DEFAULT NULL,
  `Fluctuation` char(20) DEFAULT NULL,
  `AcctName` varchar(100) DEFAULT NULL,
  `bankCode` varchar(20) DEFAULT NULL,
  `swiftcode` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bankreconciliation`
--

CREATE TABLE `bankreconciliation` (
  `pkey` bigint(20) NOT NULL,
  `StatementNo` char(10) DEFAULT NULL,
  `bankcode` char(10) DEFAULT NULL,
  `narration` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `banktransactions`
--

CREATE TABLE `banktransactions` (
  `bankcode` char(10) NOT NULL,
  `DocDate` date NOT NULL,
  `doctype` int(11) DEFAULT NULL,
  `DocumentNo` char(10) NOT NULL,
  `TransType` char(10) NOT NULL,
  `itemcode` varchar(20) NOT NULL,
  `journal` varchar(20) NOT NULL,
  `amount` double NOT NULL,
  `ClearedAmount` double DEFAULT NULL,
  `narrative` longtext DEFAULT NULL,
  `exchangerate` decimal(10,4) DEFAULT NULL,
  `cleared` tinyint(1) DEFAULT NULL,
  `ChequePrinted` tinyint(1) DEFAULT NULL,
  `reconciled` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `billofmaterial`
--

CREATE TABLE `billofmaterial` (
  `parent_itemcode` char(20) NOT NULL,
  `parent_description` varchar(100) DEFAULT NULL,
  `parent_UOM` char(10) DEFAULT NULL,
  `batchsize` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bom_items`
--

CREATE TABLE `bom_items` (
  `parent_itemcode` char(20) NOT NULL,
  `sequence` int(11) DEFAULT NULL,
  `itemcode` char(20) NOT NULL,
  `description` varchar(100) NOT NULL,
  `uom` char(10) NOT NULL,
  `qty` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `periodno` int(11) NOT NULL,
  `dimecode` char(10) NOT NULL,
  `amount` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buffertable`
--

CREATE TABLE `buffertable` (
  `averagestock` double DEFAULT NULL,
  `partperunit` int(11) DEFAULT NULL,
  `units` int(11) DEFAULT NULL,
  `uom` varchar(10) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `batchno` varchar(10) DEFAULT NULL,
  `doctype` int(11) DEFAULT NULL,
  `itemcode` varchar(20) DEFAULT NULL,
  `TABLE` varchar(0) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `coycode` int(11) NOT NULL DEFAULT 1,
  `coyname` varchar(50) NOT NULL,
  `PIN` varchar(20) NOT NULL,
  `vat` varchar(20) NOT NULL,
  `regoffice1` varchar(40) NOT NULL,
  `regoffice2` varchar(40) NOT NULL,
  `regoffice3` varchar(40) NOT NULL,
  `regoffice4` varchar(40) NOT NULL,
  `regoffice5` varchar(20) NOT NULL,
  `regoffice6` varchar(15) NOT NULL,
  `telephone` varchar(25) NOT NULL,
  `fax` varchar(25) NOT NULL,
  `email` varchar(55) NOT NULL,
  `currencydefault` varchar(4) NOT NULL,
  `DefaultDimension_1` int(11) DEFAULT NULL,
  `DefaultDimension_2` int(11) DEFAULT NULL,
  `shiftno` int(11) DEFAULT NULL,
  `Sign1` longtext DEFAULT NULL,
  `Sign2` longtext DEFAULT NULL,
  `Signaturename_1` varchar(30) DEFAULT NULL,
  `Signaturename_2` varchar(30) DEFAULT NULL,
  `Signaturename_3` varchar(30) DEFAULT NULL,
  `Sign3` longtext DEFAULT NULL,
  `last_depreciation` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE `config` (
  `confname` varchar(35) NOT NULL,
  `confvalue` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `creditors`
--

CREATE TABLE `creditors` (
  `itemcode` varchar(20) DEFAULT NULL,
  `class` char(10) DEFAULT NULL,
  `defaultgl` char(10) DEFAULT NULL,
  `vat` tinyint(1) DEFAULT NULL,
  `contact` char(10) DEFAULT NULL,
  `flag` char(6) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `customer` char(30) DEFAULT NULL,
  `status` char(5) DEFAULT NULL,
  `firstn` char(50) DEFAULT NULL,
  `middlen` char(50) DEFAULT NULL,
  `lastn` char(50) DEFAULT NULL,
  `phone` char(50) DEFAULT NULL,
  `fax` char(50) DEFAULT NULL,
  `company` char(50) DEFAULT NULL,
  `altcontact` char(50) DEFAULT NULL,
  `email` char(50) DEFAULT NULL,
  `city` char(50) DEFAULT NULL,
  `country` char(50) DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT NULL,
  `vatregno` char(50) DEFAULT NULL,
  `postcode` char(100) DEFAULT NULL,
  `curr_cod` char(10) DEFAULT NULL,
  `curr_rat` decimal(9,5) DEFAULT NULL,
  `sns` char(10) DEFAULT NULL,
  `balance` decimal(18,4) DEFAULT NULL,
  `age1` decimal(18,4) DEFAULT NULL,
  `age2` decimal(18,4) DEFAULT NULL,
  `age3` decimal(18,4) DEFAULT NULL,
  `age4` decimal(18,4) DEFAULT NULL,
  `pkey` bigint(20) NOT NULL,
  `saved` tinyint(1) DEFAULT NULL,
  `supplierposting` varchar(20) DEFAULT NULL,
  `IsEmployee` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `creditorsledger`
--

CREATE TABLE `creditorsledger` (
  `date` datetime NOT NULL,
  `whenpaid` datetime DEFAULT NULL,
  `acctfolio` char(20) NOT NULL,
  `details` char(100) NOT NULL,
  `flag` char(6) DEFAULT NULL,
  `invref` char(10) DEFAULT NULL,
  `vatamt` decimal(9,2) DEFAULT NULL,
  `vatc` char(2) DEFAULT NULL,
  `taxamt` decimal(9,2) DEFAULT NULL,
  `pamount` decimal(13,2) DEFAULT NULL,
  `allocat` decimal(13,2) DEFAULT NULL,
  `amount` decimal(13,2) DEFAULT NULL,
  `module` char(5) DEFAULT NULL,
  `type` char(1) DEFAULT NULL,
  `cash` tinyint(1) DEFAULT NULL,
  `del` char(2) DEFAULT NULL,
  `id` char(1) DEFAULT NULL,
  `i_n_t` char(1) NOT NULL,
  `journal` char(20) NOT NULL,
  `typ` char(2) NOT NULL,
  `contractno` char(20) DEFAULT NULL,
  `lpolso` char(20) DEFAULT NULL,
  `curr_cod` char(10) DEFAULT NULL,
  `curr_rat` decimal(9,5) DEFAULT NULL,
  `period` int(11) DEFAULT NULL,
  `systypes_1` int(11) DEFAULT NULL,
  `ledger` char(20) DEFAULT NULL,
  `rowid` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crmtasks`
--

CREATE TABLE `crmtasks` (
  `taskid` int(11) NOT NULL,
  `leadid` varchar(20) NOT NULL,
  `followid` varchar(10) DEFAULT NULL,
  `taskdescription` longtext NOT NULL,
  `frequencydays` int(11) NOT NULL DEFAULT 365,
  `lastcompleted` datetime(6) NOT NULL,
  `userresponsible` varchar(20) NOT NULL,
  `manager` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `currencies`
--

CREATE TABLE `currencies` (
  `currency` char(20) NOT NULL,
  `currabrev` char(3) NOT NULL,
  `country` char(50) NOT NULL,
  `hundredsname` char(15) NOT NULL,
  `decimalplaces` smallint(6) NOT NULL DEFAULT 2,
  `rate` double NOT NULL DEFAULT 1,
  `webcart` smallint(6) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customerstatement`
--

CREATE TABLE `customerstatement` (
  `Date` date NOT NULL,
  `Documentno` char(20) NOT NULL,
  `Documenttype` int(11) NOT NULL,
  `Accountno` char(20) NOT NULL,
  `Grossamount` decimal(18,2) NOT NULL,
  `JournalNo` char(20) NOT NULL,
  `Dimension_One` char(10) DEFAULT NULL,
  `Dimension_Two` char(10) DEFAULT NULL,
  `Currency` char(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `debtors`
--

CREATE TABLE `debtors` (
  `type` char(1) DEFAULT NULL,
  `istaff` int(11) DEFAULT NULL,
  `cleared` tinyint(1) DEFAULT NULL,
  `pinno` char(20) DEFAULT NULL,
  `itemcode` varchar(20) DEFAULT NULL,
  `class` char(10) DEFAULT NULL,
  `cardadd` char(10) DEFAULT NULL,
  `contact` char(100) DEFAULT NULL,
  `defaultgl` char(10) DEFAULT NULL,
  `currbal` decimal(13,2) DEFAULT NULL,
  `flag` char(6) DEFAULT NULL,
  `date` datetime(6) DEFAULT NULL,
  `creditlimit` decimal(14,2) DEFAULT NULL,
  `customer` varchar(50) DEFAULT NULL,
  `status` char(5) DEFAULT NULL,
  `firstn` char(10) DEFAULT NULL,
  `middlen` char(15) DEFAULT NULL,
  `lastn` char(10) DEFAULT NULL,
  `phone` char(15) DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL,
  `company` char(100) DEFAULT NULL,
  `altcontact` char(100) DEFAULT NULL,
  `email` char(100) DEFAULT NULL,
  `city` char(100) DEFAULT NULL,
  `country` char(100) DEFAULT NULL,
  `preffpay` char(10) DEFAULT NULL,
  `crdcardno` char(10) DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT NULL,
  `namecrd` char(10) DEFAULT NULL,
  `postcode` char(100) DEFAULT NULL,
  `curr_cod` char(10) DEFAULT NULL,
  `curr_rat` decimal(9,5) DEFAULT NULL,
  `id` char(1) DEFAULT NULL,
  `i_n_t` char(1) DEFAULT NULL,
  `typ` char(2) DEFAULT NULL,
  `sns` char(10) DEFAULT NULL,
  `balance` decimal(18,4) DEFAULT NULL,
  `age1` bigint(20) DEFAULT NULL,
  `age2` decimal(18,4) DEFAULT NULL,
  `age3` decimal(18,4) DEFAULT NULL,
  `age4` decimal(18,4) DEFAULT NULL,
  `pkey` bigint(20) NOT NULL,
  `islocal` tinyint(1) DEFAULT NULL,
  `username` char(20) DEFAULT NULL,
  `customerposting` varchar(20) DEFAULT NULL,
  `salesman` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `debtorsledger`
--

CREATE TABLE `debtorsledger` (
  `date` datetime NOT NULL,
  `details` char(100) NOT NULL,
  `cleared` tinyint(1) DEFAULT NULL,
  `flag` char(6) DEFAULT NULL,
  `invref` char(20) DEFAULT NULL,
  `acctfolio` char(20) NOT NULL,
  `allocat` decimal(13,2) DEFAULT NULL,
  `amount` decimal(13,2) DEFAULT NULL,
  `pamount` decimal(13,2) DEFAULT NULL,
  `module` char(5) DEFAULT NULL,
  `type` char(1) DEFAULT NULL,
  `cash` tinyint(1) DEFAULT NULL,
  `del` char(2) DEFAULT NULL,
  `curr_cod` char(10) DEFAULT NULL,
  `curr_rat` decimal(9,5) DEFAULT NULL,
  `id` char(1) DEFAULT NULL,
  `i_n_t` char(1) NOT NULL,
  `period` int(11) DEFAULT NULL,
  `journal` char(20) NOT NULL,
  `typ` char(2) NOT NULL,
  `sns` char(10) DEFAULT NULL,
  `vatamt` decimal(10,2) DEFAULT NULL,
  `vatc` char(2) DEFAULT NULL,
  `pkey` bigint(20) NOT NULL,
  `systypes_1` int(11) DEFAULT NULL,
  `ledger` char(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dimensions`
--

CREATE TABLE `dimensions` (
  `id` int(11) NOT NULL,
  `Code` char(10) NOT NULL,
  `Dimension` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dimensionsetup`
--

CREATE TABLE `dimensionsetup` (
  `id` int(11) NOT NULL,
  `Dimension_type` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emailsettings`
--

CREATE TABLE `emailsettings` (
  `id` int(11) NOT NULL,
  `host` varchar(30) NOT NULL,
  `port` char(5) NOT NULL,
  `heloaddress` varchar(20) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(30) DEFAULT NULL,
  `timeout` int(11) DEFAULT 5,
  `companyname` varchar(50) DEFAULT NULL,
  `auth` smallint(6) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enterbillheaders`
--

CREATE TABLE `enterbillheaders` (
  `date` date DEFAULT NULL,
  `documenttype` int(11) DEFAULT NULL,
  `documentno` char(20) DEFAULT NULL,
  `narration` longtext DEFAULT NULL,
  `journalno` char(20) DEFAULT NULL,
  `VendorID` varchar(20) DEFAULT NULL,
  `whtax` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enterbillslines`
--

CREATE TABLE `enterbillslines` (
  `documenttype` int(11) DEFAULT NULL,
  `documentno` char(20) DEFAULT NULL,
  `journalno` char(20) DEFAULT NULL,
  `account` char(20) DEFAULT NULL,
  `vatamount` decimal(10,2) DEFAULT NULL,
  `grossamount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financialperiods`
--

CREATE TABLE `financialperiods` (
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `Name` varchar(20) NOT NULL,
  `newyear` tinyint(1) DEFAULT NULL,
  `closed` tinyint(1) DEFAULT NULL,
  `periodno` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fixedassetcategories`
--

CREATE TABLE `fixedassetcategories` (
  `categoryid` char(6) NOT NULL,
  `categorydescription` char(20) NOT NULL,
  `costact` varchar(20) NOT NULL,
  `depnact` varchar(20) NOT NULL,
  `disposalact` varchar(20) NOT NULL,
  `accumdepnact` varchar(20) NOT NULL,
  `defaultdepnrate` double NOT NULL DEFAULT 0.2,
  `defaultdepntype` int(11) NOT NULL DEFAULT 1,
  `Equipment_hired_act` varchar(20) DEFAULT NULL,
  `defaultgl_vat_act` varchar(20) DEFAULT NULL,
  `vatcategorycode` char(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fixedassetlocations`
--

CREATE TABLE `fixedassetlocations` (
  `locationid` char(6) NOT NULL,
  `locationdescription` char(20) NOT NULL,
  `parentlocationid` char(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fixedassets`
--

CREATE TABLE `fixedassets` (
  `assetid` int(11) NOT NULL,
  `serialno` varchar(30) NOT NULL,
  `barcode` varchar(20) NOT NULL,
  `assetlocation` varchar(6) NOT NULL,
  `cost` double NOT NULL DEFAULT 0,
  `accumdepn` double NOT NULL DEFAULT 0,
  `datepurchased` timestamp NOT NULL DEFAULT current_timestamp(),
  `disposalproceeds` double NOT NULL DEFAULT 0,
  `assetcategoryid` varchar(6) NOT NULL,
  `description` varchar(50) NOT NULL,
  `longdescription` longtext NOT NULL,
  `depntype` int(11) NOT NULL DEFAULT 1,
  `depnrate` double NOT NULL,
  `disposaldate` datetime(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fixedassetsline`
--

CREATE TABLE `fixedassetsline` (
  `entryno` bigint(20) NOT NULL,
  `documenttype` int(11) NOT NULL,
  `docdate` datetime NOT NULL,
  `documentno` varchar(20) NOT NULL,
  `locationcode` char(10) DEFAULT NULL,
  `stocktype` int(11) DEFAULT NULL,
  `code` varchar(20) DEFAULT NULL,
  `description` varchar(50) DEFAULT NULL,
  `unitofmeasure` varchar(10) DEFAULT NULL,
  `Quantity` decimal(10,2) DEFAULT NULL,
  `Quantity_toinvoice` decimal(10,2) DEFAULT NULL,
  `Qunatity_delivered` decimal(10,2) DEFAULT NULL,
  `UnitPrice` decimal(10,2) DEFAULT NULL,
  `vatamount` decimal(10,2) DEFAULT NULL,
  `invoiceamount` decimal(10,2) DEFAULT NULL,
  `completed` tinyint(1) DEFAULT NULL,
  `printed` tinyint(1) DEFAULT NULL,
  `containerprice` decimal(10,2) DEFAULT NULL,
  `containersunits` bigint(20) DEFAULT NULL,
  `totalchargedcontainers` decimal(10,2) DEFAULT NULL,
  `containercode` varchar(20) DEFAULT NULL,
  `vatrate` decimal(10,2) DEFAULT NULL,
  `inclusive` tinyint(1) DEFAULT NULL,
  `UOM` varchar(10) DEFAULT NULL,
  `shipping` double DEFAULT NULL,
  `Qunatity_returned` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fixedassettasks`
--

CREATE TABLE `fixedassettasks` (
  `taskid` int(11) NOT NULL,
  `assetid` int(11) NOT NULL,
  `taskdescription` longtext NOT NULL,
  `frequencydays` int(11) NOT NULL DEFAULT 365,
  `lastcompleted` datetime(6) NOT NULL,
  `userresponsible` varchar(20) NOT NULL,
  `manager` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fixedassettrans`
--

CREATE TABLE `fixedassettrans` (
  `id` int(11) NOT NULL,
  `assetid` int(11) NOT NULL,
  `transtype` smallint(6) NOT NULL,
  `transdate` datetime(6) NOT NULL,
  `transno` int(11) NOT NULL,
  `periodno` smallint(6) NOT NULL,
  `inputdate` datetime(6) NOT NULL,
  `fixedassettranstype` varchar(8) NOT NULL,
  `amount` double NOT NULL,
  `units` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `generalledger`
--

CREATE TABLE `generalledger` (
  `rowid` bigint(20) NOT NULL,
  `journalno` varchar(20) NOT NULL,
  `Docdate` datetime NOT NULL,
  `period` int(11) NOT NULL,
  `DocumentNo` varchar(20) NOT NULL,
  `DocumentType` int(11) NOT NULL,
  `accountcode` char(20) NOT NULL,
  `balaccountcode` char(20) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `currencycode` char(3) NOT NULL,
  `ExchangeRate` double DEFAULT NULL,
  `cutomercode` varchar(20) DEFAULT NULL,
  `suppliercode` varchar(20) DEFAULT NULL,
  `bankcode` varchar(20) DEFAULT NULL,
  `reconcilled` tinyint(1) DEFAULT NULL,
  `narration` longtext DEFAULT NULL,
  `ExchangeRateDiff` double DEFAULT NULL,
  `VATaccountcode` char(20) DEFAULT NULL,
  `VATamount` decimal(10,2) DEFAULT NULL,
  `dimension` char(10) DEFAULT NULL,
  `dimension2` char(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `geocode_param`
--

CREATE TABLE `geocode_param` (
  `geocodeid` smallint(6) NOT NULL,
  `geocode_key` varchar(200) NOT NULL,
  `center_long` varchar(20) NOT NULL,
  `center_lat` varchar(20) NOT NULL,
  `map_height` varchar(10) NOT NULL,
  `map_width` varchar(10) NOT NULL,
  `map_host` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `glpostinggroup`
--

CREATE TABLE `glpostinggroup` (
  `code` char(20) NOT NULL,
  `defaultgl_vat` char(20) DEFAULT NULL,
  `vatcategory` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventorypostinggroup`
--

CREATE TABLE `inventorypostinggroup` (
  `code` char(20) NOT NULL,
  `defaultgl_sales` char(20) DEFAULT NULL,
  `defaultgl_purch` char(20) DEFAULT NULL,
  `defaultgl_vat` char(20) DEFAULT NULL,
  `balancesheet` char(20) DEFAULT NULL,
  `vatcategory` char(1) DEFAULT NULL,
  `wip` char(20) DEFAULT NULL,
  `stockvariance` char(20) DEFAULT NULL,
  `productionexpense` char(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journalentries`
--

CREATE TABLE `journalentries` (
  `Docdate` date NOT NULL,
  `JournalNo` char(20) NOT NULL,
  `Account` char(20) NOT NULL,
  `BalAccount` char(20) NOT NULL,
  `transtype` char(10) DEFAULT NULL,
  `itemcode` char(20) DEFAULT NULL,
  `amount` double NOT NULL,
  `Currency` char(3) NOT NULL,
  `Dimension_1` char(10) DEFAULT NULL,
  `Dimension_2` char(10) DEFAULT NULL,
  `narration` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laboratorystandards`
--

CREATE TABLE `laboratorystandards` (
  `sampletypeid` varchar(10) NOT NULL,
  `ParameterID` varchar(10) DEFAULT NULL,
  `Parameter` varchar(100) DEFAULT NULL,
  `Limits` varchar(100) DEFAULT NULL,
  `Method` varchar(50) DEFAULT NULL,
  `Units` varchar(50) DEFAULT NULL,
  `SalesPrice` double DEFAULT NULL,
  `vital` tinyint(1) DEFAULT NULL,
  `category` varchar(1) DEFAULT NULL,
  `NoStandardlimit` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `labposting`
--

CREATE TABLE `labposting` (
  `DocumentNo` varchar(20) DEFAULT NULL,
  `SampleID` varchar(20) NOT NULL,
  `DateRecieved` date DEFAULT NULL,
  `ReceivedBy` varchar(20) DEFAULT NULL,
  `DateTeststarted` date DEFAULT NULL,
  `DateTestended` date DEFAULT NULL,
  `CustomerID` varchar(20) DEFAULT NULL,
  `ExternalSampleId` varchar(100) DEFAULT NULL,
  `HOD` varchar(20) DEFAULT NULL,
  `DEPUTY` varchar(20) DEFAULT NULL,
  `Interpretation` longtext DEFAULT NULL,
  `Status` int(11) DEFAULT NULL,
  `Passed` int(11) DEFAULT NULL,
  `PricingType` int(11) DEFAULT NULL,
  `sampledby` varchar(20) DEFAULT NULL,
  `lpo` varchar(20) DEFAULT NULL,
  `scopeofwork` longtext DEFAULT NULL,
  `Invoice` tinyint(1) DEFAULT NULL,
  `SalesHeader` varchar(20) DEFAULT NULL,
  `SamplingMethod` varchar(20) DEFAULT NULL,
  `samplingdate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `labpostingdetail`
--

CREATE TABLE `labpostingdetail` (
  `DocumentNo` varchar(20) NOT NULL,
  `SampleID` varchar(20) NOT NULL,
  `SampleTypeID` varchar(20) NOT NULL,
  `ParameterID` varchar(20) DEFAULT NULL,
  `Method` varchar(50) DEFAULT NULL,
  `Standard` varchar(100) DEFAULT NULL,
  `Results` varchar(50) DEFAULT NULL,
  `lastuserid` varchar(20) DEFAULT NULL,
  `lastdatetime` datetime(6) DEFAULT NULL,
  `PricingType` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `newactivity`
--

CREATE TABLE `newactivity` (
  `pkey` int(11) NOT NULL,
  `ActivityOwner` varchar(20) DEFAULT NULL,
  `Activityname` longtext DEFAULT NULL,
  `fromdue` date DEFAULT NULL,
  `todue` date DEFAULT NULL,
  `Contact` varchar(20) DEFAULT NULL,
  `Status` int(11) DEFAULT NULL,
  `valueofbusiness` double DEFAULT NULL,
  `taskdetails` longtext DEFAULT NULL,
  `createdby` varchar(20) DEFAULT NULL,
  `createdon` datetime(6) DEFAULT NULL,
  `lastactivity` datetime(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `newcontacts`
--

CREATE TABLE `newcontacts` (
  `company` longtext DEFAULT NULL,
  `postcode` longtext DEFAULT NULL,
  `city` char(20) DEFAULT NULL,
  `country` char(20) DEFAULT NULL,
  `Physical_Address` longtext DEFAULT NULL,
  `PIN_VAT` longtext DEFAULT NULL,
  `phone` char(15) DEFAULT NULL,
  `email` longtext DEFAULT NULL,
  `salesman` char(10) DEFAULT NULL,
  `Contact_Name` longtext DEFAULT NULL,
  `Contact_Designation` longtext DEFAULT NULL,
  `Contact_Telephone` char(15) DEFAULT NULL,
  `Contact_email` longtext DEFAULT NULL,
  `Alt_Contact_Name` longtext DEFAULT NULL,
  `Alt_Contact_Designation` longtext DEFAULT NULL,
  `Alt_Contact_Telephone` char(15) DEFAULT NULL,
  `Alt_Contact_email` longtext DEFAULT NULL,
  `createdby` char(20) DEFAULT NULL,
  `Date_Created` datetime(6) DEFAULT NULL,
  `Last_Activity` datetime(6) DEFAULT NULL,
  `pkey` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paymentsallocation`
--

CREATE TABLE `paymentsallocation` (
  `itemcode` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `invoiceno` char(20) NOT NULL,
  `journalno` char(20) NOT NULL,
  `doctype` int(11) NOT NULL,
  `receiptno` char(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `receiptjournal` char(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paymentvoucherheader`
--

CREATE TABLE `paymentvoucherheader` (
  `docno` char(20) NOT NULL,
  `date` date NOT NULL,
  `itemcode` char(20) NOT NULL,
  `externalref` varchar(20) DEFAULT NULL,
  `narrative` varchar(50) DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `printed` tinyint(1) DEFAULT NULL,
  `journal` varchar(20) DEFAULT NULL,
  `currency` char(10) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `Comments` longtext DEFAULT NULL,
  `ChequePrinted` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paymentvoucherline`
--

CREATE TABLE `paymentvoucherline` (
  `docno` char(20) NOT NULL,
  `itemcode` char(20) NOT NULL,
  `narrative` varchar(50) DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `journal` varchar(20) DEFAULT NULL,
  `invoice_journal` varchar(20) DEFAULT NULL,
  `Dimension_1` char(20) NOT NULL,
  `Dimension_2` char(20) NOT NULL,
  `Budget` decimal(18,2) DEFAULT NULL,
  `Committed` decimal(18,2) DEFAULT NULL,
  `Expensed` decimal(18,2) DEFAULT NULL,
  `Balance` decimal(18,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `periods`
--

CREATE TABLE `periods` (
  `periodno` smallint(6) NOT NULL DEFAULT 0,
  `lastdate_in_period` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pettdoc`
--

CREATE TABLE `pettdoc` (
  `date` datetime(6) DEFAULT NULL,
  `userid` varchar(20) DEFAULT NULL,
  `moneyin` decimal(10,2) DEFAULT NULL,
  `moneyout` decimal(10,2) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT NULL,
  `account` char(10) DEFAULT NULL,
  `expensedetails` char(200) DEFAULT NULL,
  `petteycashno` char(10) DEFAULT NULL,
  `journal` char(10) DEFAULT NULL,
  `transtype` int(11) DEFAULT NULL,
  `shiftno` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `postinggroups`
--

CREATE TABLE `postinggroups` (
  `code` varchar(20) NOT NULL,
  `salesaccount` char(20) NOT NULL,
  `debtorsaccount` char(20) NOT NULL,
  `VATinclusive` tinyint(1) DEFAULT NULL,
  `IsTaxed` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prodcutionmasterline`
--

CREATE TABLE `prodcutionmasterline` (
  `Batchno` varchar(20) NOT NULL,
  `itemcode` varchar(10) NOT NULL,
  `UOM` char(10) NOT NULL,
  `qty` double NOT NULL,
  `cost` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `productionemployee`
--

CREATE TABLE `productionemployee` (
  `istaff` int(11) DEFAULT NULL,
  `pinno` char(20) DEFAULT NULL,
  `code` varchar(10) DEFAULT NULL,
  `currbal` decimal(13,2) DEFAULT NULL,
  `date` datetime(6) DEFAULT NULL,
  `manager` varchar(10) DEFAULT NULL,
  `salesman` char(50) DEFAULT NULL,
  `status` char(5) DEFAULT NULL,
  `firstn` char(10) DEFAULT NULL,
  `middlen` char(10) DEFAULT NULL,
  `lastn` char(10) DEFAULT NULL,
  `phone` char(10) DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL,
  `company` char(100) DEFAULT NULL,
  `altcontact` char(100) DEFAULT NULL,
  `email` char(100) DEFAULT NULL,
  `city` char(100) DEFAULT NULL,
  `country` char(100) DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT NULL,
  `postcode` char(100) DEFAULT NULL,
  `curr_cod` char(10) DEFAULT NULL,
  `curr_rat` decimal(9,5) DEFAULT NULL,
  `pkey` bigint(20) NOT NULL,
  `commissionposting` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `productionmanager`
--

CREATE TABLE `productionmanager` (
  `istaff` int(11) DEFAULT NULL,
  `pinno` char(20) DEFAULT NULL,
  `code` varchar(10) DEFAULT NULL,
  `currbal` decimal(13,2) DEFAULT NULL,
  `date` datetime(6) DEFAULT NULL,
  `commission` decimal(14,2) DEFAULT NULL,
  `salesman` char(50) DEFAULT NULL,
  `status` char(5) DEFAULT NULL,
  `firstn` char(10) DEFAULT NULL,
  `middlen` char(10) DEFAULT NULL,
  `lastn` char(10) DEFAULT NULL,
  `phone` char(10) DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL,
  `company` char(100) DEFAULT NULL,
  `altcontact` char(100) DEFAULT NULL,
  `email` char(100) DEFAULT NULL,
  `city` char(100) DEFAULT NULL,
  `country` char(100) DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT NULL,
  `postcode` char(100) DEFAULT NULL,
  `curr_cod` char(10) DEFAULT NULL,
  `curr_rat` decimal(9,5) DEFAULT NULL,
  `pkey` bigint(20) NOT NULL,
  `commissionposting` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `productionmaster`
--

CREATE TABLE `productionmaster` (
  `Batchno` varchar(20) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `userid` varchar(50) DEFAULT NULL,
  `labparam1` varchar(50) DEFAULT NULL,
  `labparam2` varchar(50) DEFAULT NULL,
  `labparam3` varchar(50) DEFAULT NULL,
  `labparam4` varchar(50) DEFAULT NULL,
  `labparam5` varchar(50) DEFAULT NULL,
  `averagestock` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `productionunit`
--

CREATE TABLE `productionunit` (
  `itemcode` char(20) NOT NULL,
  `capacity` double NOT NULL,
  `tankname` char(30) NOT NULL,
  `UOM` char(10) DEFAULT NULL,
  `CapacityUOM` char(10) DEFAULT NULL,
  `units` double DEFAULT NULL,
  `balance` double NOT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `pkey` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchaseheader`
--

CREATE TABLE `purchaseheader` (
  `documenttype` int(11) NOT NULL,
  `documentno` varchar(20) NOT NULL,
  `docdate` datetime DEFAULT NULL,
  `oderdate` datetime DEFAULT NULL,
  `duedate` datetime DEFAULT NULL,
  `postingdate` datetime DEFAULT NULL,
  `vendorcode` varchar(20) NOT NULL,
  `vendorname` varchar(50) NOT NULL,
  `yourreference` varchar(30) DEFAULT NULL,
  `externaldocumentno` varchar(30) DEFAULT NULL,
  `locationcode` char(10) DEFAULT NULL,
  `paymentterms` char(10) DEFAULT NULL,
  `postinggroup` char(20) NOT NULL,
  `currencycode` char(10) NOT NULL,
  `printed` int(11) DEFAULT NULL,
  `released` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `userid` char(20) NOT NULL,
  `period` int(11) NOT NULL,
  `entryno` bigint(20) NOT NULL,
  `vatinclusive` tinyint(1) DEFAULT NULL,
  `Dimension_1` char(10) DEFAULT NULL,
  `Dimension_2` char(10) DEFAULT NULL,
  `freight` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchaseline`
--

CREATE TABLE `purchaseline` (
  `entryno` bigint(20) NOT NULL,
  `documenttype` int(11) NOT NULL,
  `docdate` datetime NOT NULL,
  `documentno` varchar(20) NOT NULL,
  `locationcode` char(10) DEFAULT NULL,
  `stocktype` int(11) DEFAULT NULL,
  `code` varchar(20) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
  `unitofmeasure` varchar(10) DEFAULT NULL,
  `Quantity` decimal(10,2) DEFAULT NULL,
  `Quantity_toinvoice` decimal(10,2) DEFAULT NULL,
  `Qunatity_delivered` decimal(10,2) DEFAULT NULL,
  `UnitPrice` decimal(10,2) DEFAULT NULL,
  `vatamount` decimal(10,2) DEFAULT NULL,
  `invoiceamount` decimal(10,2) DEFAULT NULL,
  `completed` tinyint(1) DEFAULT NULL,
  `printed` tinyint(1) DEFAULT NULL,
  `containerprice` decimal(10,2) DEFAULT NULL,
  `containersunits` bigint(20) DEFAULT NULL,
  `totalchargedcontainers` decimal(10,2) DEFAULT NULL,
  `containercode` varchar(20) DEFAULT NULL,
  `vatrate` decimal(10,2) DEFAULT NULL,
  `inclusive` tinyint(1) DEFAULT NULL,
  `UOM` varchar(10) DEFAULT NULL,
  `shipping` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receiptheader`
--

CREATE TABLE `receiptheader` (
  `docno` char(20) NOT NULL,
  `date` date NOT NULL,
  `itemcode` char(20) NOT NULL,
  `externalref` varchar(20) DEFAULT NULL,
  `narrative` varchar(50) DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `printed` tinyint(1) DEFAULT NULL,
  `journal` varchar(20) DEFAULT NULL,
  `currency` char(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receiptsallocation`
--

CREATE TABLE `receiptsallocation` (
  `itemcode` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `invoiceno` char(20) NOT NULL,
  `journalno` char(20) NOT NULL,
  `doctype` int(11) NOT NULL,
  `receiptno` char(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `receiptjournal` char(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reportcolumns`
--

CREATE TABLE `reportcolumns` (
  `reportid` smallint(6) NOT NULL DEFAULT 0,
  `colno` smallint(6) NOT NULL DEFAULT 0,
  `heading1` varchar(15) NOT NULL,
  `heading2` varchar(15) DEFAULT NULL,
  `calculation` smallint(6) NOT NULL DEFAULT 0,
  `periodfrom` smallint(6) DEFAULT NULL,
  `periodto` smallint(6) DEFAULT NULL,
  `datatype` varchar(15) DEFAULT NULL,
  `colnumerator` smallint(6) DEFAULT NULL,
  `coldenominator` smallint(6) DEFAULT NULL,
  `calcoperator` char(1) DEFAULT NULL,
  `budgetoractual` smallint(6) NOT NULL DEFAULT 0,
  `valformat` char(1) NOT NULL,
  `constant` double NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reportfields`
--

CREATE TABLE `reportfields` (
  `id` int(11) NOT NULL,
  `reportid` int(11) NOT NULL DEFAULT 0,
  `entrytype` varchar(15) NOT NULL DEFAULT '',
  `seqnum` int(11) NOT NULL DEFAULT 0,
  `fieldname` varchar(80) NOT NULL DEFAULT '',
  `displaydesc` varchar(25) NOT NULL DEFAULT '',
  `visible` varchar(1) NOT NULL DEFAULT '1',
  `columnbreak` varchar(1) NOT NULL DEFAULT '1',
  `params` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reportheaders`
--

CREATE TABLE `reportheaders` (
  `reportid` smallint(6) NOT NULL,
  `reportheading` varchar(80) NOT NULL,
  `groupbydata1` varchar(15) NOT NULL,
  `newpageafter1` smallint(6) NOT NULL DEFAULT 0,
  `lower1` varchar(10) NOT NULL,
  `upper1` varchar(10) NOT NULL,
  `groupbydata2` varchar(15) DEFAULT NULL,
  `newpageafter2` smallint(6) NOT NULL DEFAULT 0,
  `lower2` varchar(10) DEFAULT NULL,
  `upper2` varchar(10) DEFAULT NULL,
  `groupbydata3` varchar(15) DEFAULT NULL,
  `newpageafter3` smallint(6) NOT NULL DEFAULT 0,
  `lower3` varchar(10) DEFAULT NULL,
  `upper3` varchar(10) DEFAULT NULL,
  `groupbydata4` varchar(15) NOT NULL,
  `newpageafter4` smallint(6) NOT NULL DEFAULT 0,
  `upper4` varchar(10) NOT NULL,
  `lower4` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reportlinks`
--

CREATE TABLE `reportlinks` (
  `table1` varchar(25) NOT NULL,
  `table2` varchar(25) NOT NULL,
  `equation` varchar(75) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `reportname` varchar(30) NOT NULL DEFAULT '',
  `reporttype` char(3) NOT NULL DEFAULT 'rpt',
  `groupname` varchar(9) NOT NULL DEFAULT 'misc',
  `defaultreport` char(1) NOT NULL DEFAULT '0',
  `papersize` varchar(15) NOT NULL DEFAULT 'A4,210,297',
  `paperorientation` char(1) NOT NULL DEFAULT 'P',
  `margintop` int(11) NOT NULL DEFAULT 10,
  `marginbottom` int(11) NOT NULL DEFAULT 10,
  `marginleft` int(11) NOT NULL DEFAULT 10,
  `marginright` int(11) NOT NULL DEFAULT 10,
  `coynamefont` varchar(20) NOT NULL DEFAULT 'Helvetica',
  `coynamefontsize` int(11) NOT NULL DEFAULT 12,
  `coynamefontcolor` varchar(11) NOT NULL DEFAULT '0,0,0',
  `coynamealign` char(1) NOT NULL DEFAULT 'C',
  `coynameshow` char(1) NOT NULL DEFAULT '1',
  `title1desc` varchar(50) NOT NULL DEFAULT '%reportname%',
  `title1font` varchar(20) NOT NULL DEFAULT 'Helvetica',
  `title1fontsize` int(11) NOT NULL DEFAULT 10,
  `title1fontcolor` varchar(11) NOT NULL DEFAULT '0,0,0',
  `title1fontalign` char(1) NOT NULL DEFAULT 'C',
  `title1show` char(1) NOT NULL DEFAULT '1',
  `title2desc` varchar(50) NOT NULL DEFAULT 'Report Generated %date%',
  `title2font` varchar(20) NOT NULL DEFAULT 'Helvetica',
  `title2fontsize` int(11) NOT NULL DEFAULT 10,
  `title2fontcolor` varchar(11) NOT NULL DEFAULT '0,0,0',
  `title2fontalign` char(1) NOT NULL DEFAULT 'C',
  `title2show` char(1) NOT NULL DEFAULT '1',
  `filterfont` varchar(10) NOT NULL DEFAULT 'Helvetica',
  `filterfontsize` int(11) NOT NULL DEFAULT 8,
  `filterfontcolor` varchar(11) NOT NULL DEFAULT '0,0,0',
  `filterfontalign` char(1) NOT NULL DEFAULT 'L',
  `datafont` varchar(10) NOT NULL DEFAULT 'Helvetica',
  `datafontsize` int(11) NOT NULL DEFAULT 10,
  `datafontcolor` varchar(10) NOT NULL DEFAULT 'black',
  `datafontalign` char(1) NOT NULL DEFAULT 'L',
  `totalsfont` varchar(10) NOT NULL DEFAULT 'Helvetica',
  `totalsfontsize` int(11) NOT NULL DEFAULT 10,
  `totalsfontcolor` varchar(11) NOT NULL DEFAULT '0,0,0',
  `totalsfontalign` char(1) NOT NULL DEFAULT 'L',
  `col1width` int(11) NOT NULL DEFAULT 25,
  `col2width` int(11) NOT NULL DEFAULT 25,
  `col3width` int(11) NOT NULL DEFAULT 25,
  `col4width` int(11) NOT NULL DEFAULT 25,
  `col5width` int(11) NOT NULL DEFAULT 25,
  `col6width` int(11) NOT NULL DEFAULT 25,
  `col7width` int(11) NOT NULL DEFAULT 25,
  `col8width` int(11) NOT NULL DEFAULT 25,
  `col9width` int(11) NOT NULL DEFAULT 25,
  `col10width` int(11) NOT NULL DEFAULT 25,
  `col11width` int(11) NOT NULL DEFAULT 25,
  `col12width` int(11) NOT NULL DEFAULT 25,
  `col13width` int(11) NOT NULL DEFAULT 25,
  `col14width` int(11) NOT NULL DEFAULT 25,
  `col15width` int(11) NOT NULL DEFAULT 25,
  `col16width` int(11) NOT NULL DEFAULT 25,
  `col17width` int(11) NOT NULL DEFAULT 25,
  `col18width` int(11) NOT NULL DEFAULT 25,
  `col19width` int(11) NOT NULL DEFAULT 25,
  `col20width` int(11) NOT NULL DEFAULT 25,
  `table1` varchar(25) NOT NULL DEFAULT '',
  `table2` varchar(25) DEFAULT NULL,
  `table2criteria` varchar(75) DEFAULT NULL,
  `table3` varchar(25) DEFAULT NULL,
  `table3criteria` varchar(75) DEFAULT NULL,
  `table4` varchar(25) DEFAULT NULL,
  `table4criteria` varchar(75) DEFAULT NULL,
  `table5` varchar(25) DEFAULT NULL,
  `table5criteria` varchar(75) DEFAULT NULL,
  `table6` varchar(25) DEFAULT NULL,
  `table6criteria` varchar(75) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salesheader`
--

CREATE TABLE `salesheader` (
  `documenttype` int(11) NOT NULL,
  `documentno` varchar(20) NOT NULL,
  `docdate` datetime DEFAULT NULL,
  `oderdate` datetime DEFAULT NULL,
  `duedate` datetime DEFAULT NULL,
  `postingdate` datetime DEFAULT NULL,
  `customercode` varchar(20) NOT NULL,
  `customername` varchar(50) NOT NULL,
  `yourreference` varchar(30) DEFAULT NULL,
  `externaldocumentno` varchar(30) DEFAULT NULL,
  `locationcode` char(10) DEFAULT NULL,
  `paymentterms` char(10) DEFAULT NULL,
  `postinggroup` char(20) NOT NULL,
  `currencycode` char(10) NOT NULL,
  `salespersoncode` char(10) DEFAULT NULL,
  `printed` int(11) DEFAULT NULL,
  `released` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `userid` char(20) NOT NULL,
  `period` int(11) NOT NULL,
  `entryno` bigint(20) NOT NULL,
  `vatinclusive` tinyint(1) DEFAULT NULL,
  `discount` double DEFAULT NULL,
  `sampleID` varchar(20) DEFAULT NULL,
  `lpo` varchar(20) DEFAULT NULL,
  `BatchNO` varchar(20) DEFAULT NULL,
  `Bybatch` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salesline`
--

CREATE TABLE `salesline` (
  `entryno` bigint(20) NOT NULL,
  `documenttype` int(11) NOT NULL,
  `docdate` datetime NOT NULL,
  `documentno` varchar(20) NOT NULL,
  `locationcode` char(10) DEFAULT NULL,
  `stocktype` int(11) DEFAULT NULL,
  `code` varchar(20) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
  `unitofmeasure` varchar(10) DEFAULT NULL,
  `Quantity` decimal(10,2) DEFAULT NULL,
  `Quantity_toinvoice` decimal(10,2) DEFAULT NULL,
  `Qunatity_delivered` decimal(10,2) DEFAULT NULL,
  `UnitPrice` decimal(10,2) DEFAULT NULL,
  `vatamount` decimal(10,2) DEFAULT NULL,
  `invoiceamount` decimal(10,2) DEFAULT NULL,
  `completed` tinyint(1) DEFAULT NULL,
  `printed` tinyint(1) DEFAULT NULL,
  `containerprice` decimal(10,2) DEFAULT NULL,
  `containersunits` bigint(20) DEFAULT NULL,
  `totalchargedcontainers` decimal(10,2) DEFAULT NULL,
  `containercode` varchar(20) DEFAULT NULL,
  `vatrate` decimal(10,2) DEFAULT NULL,
  `inclusive` tinyint(1) DEFAULT NULL,
  `UOM` varchar(10) DEFAULT NULL,
  `shipping` double DEFAULT NULL,
  `Itemlist` longtext DEFAULT NULL,
  `BatchNO` varchar(20) DEFAULT NULL,
  `sampleID` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salesrepsinfo`
--

CREATE TABLE `salesrepsinfo` (
  `istaff` int(11) DEFAULT NULL,
  `pinno` char(20) DEFAULT NULL,
  `code` varchar(10) DEFAULT NULL,
  `currbal` decimal(13,2) DEFAULT NULL,
  `date` datetime(6) DEFAULT NULL,
  `target` decimal(14,2) DEFAULT NULL,
  `commission` decimal(14,2) DEFAULT NULL,
  `salesman` char(50) DEFAULT NULL,
  `status` char(5) DEFAULT NULL,
  `firstn` char(10) DEFAULT NULL,
  `middlen` char(10) DEFAULT NULL,
  `lastn` char(10) DEFAULT NULL,
  `phone` char(10) DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL,
  `company` char(100) DEFAULT NULL,
  `altcontact` char(100) DEFAULT NULL,
  `email` char(100) DEFAULT NULL,
  `city` char(100) DEFAULT NULL,
  `country` char(100) DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT NULL,
  `postcode` char(100) DEFAULT NULL,
  `curr_cod` char(10) DEFAULT NULL,
  `curr_rat` decimal(9,5) DEFAULT NULL,
  `pkey` bigint(20) NOT NULL,
  `commissionposting` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sampletypeline`
--

CREATE TABLE `sampletypeline` (
  `sampletypeid` varchar(10) DEFAULT NULL,
  `Narration` longtext DEFAULT NULL,
  `fieldid` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sampletypes`
--

CREATE TABLE `sampletypes` (
  `sampletypeid` varchar(10) NOT NULL,
  `salesgroup` varchar(10) DEFAULT NULL,
  `sampletypeName` varchar(50) DEFAULT NULL,
  `SalesPrice` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scripts`
--

CREATE TABLE `scripts` (
  `script` varchar(78) NOT NULL,
  `pagesecurity` int(11) NOT NULL DEFAULT 1,
  `description` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `securitygroups`
--

CREATE TABLE `securitygroups` (
  `secroleid` int(11) NOT NULL DEFAULT 0,
  `tokenid` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `securityroles`
--

CREATE TABLE `securityroles` (
  `secroleid` int(11) NOT NULL,
  `secrolename` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `securitytokens`
--

CREATE TABLE `securitytokens` (
  `tokenid` int(11) NOT NULL DEFAULT 0,
  `tokenname` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stockcategory`
--

CREATE TABLE `stockcategory` (
  `categoryid` char(6) NOT NULL,
  `categorydescription` char(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stockexchange`
--

CREATE TABLE `stockexchange` (
  `docdate` datetime NOT NULL,
  `docno` char(10) NOT NULL,
  `item` char(20) NOT NULL,
  `uom` char(10) NOT NULL,
  `store` char(10) NOT NULL,
  `qty` int(11) NOT NULL,
  `userid` char(50) NOT NULL,
  `acctfolio` varchar(20) DEFAULT NULL,
  `reason` char(50) DEFAULT NULL,
  `authorised` int(11) DEFAULT NULL,
  `authorisedby` char(50) DEFAULT NULL,
  `authoriseddate` datetime DEFAULT NULL,
  `direction` int(11) DEFAULT NULL,
  `cost` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stockledger`
--

CREATE TABLE `stockledger` (
  `date` datetime NOT NULL,
  `stname` char(30) DEFAULT NULL,
  `doctyp` char(2) NOT NULL,
  `batch` char(20) DEFAULT NULL,
  `itemcode` char(20) NOT NULL,
  `invref` char(20) DEFAULT NULL,
  `netamt` decimal(13,2) DEFAULT NULL,
  `vat` decimal(13,2) DEFAULT NULL,
  `amount` decimal(18,4) DEFAULT NULL,
  `fulqty` decimal(13,4) NOT NULL,
  `loosqty` decimal(13,4) NOT NULL,
  `price` decimal(9,2) DEFAULT NULL,
  `curr_rat` decimal(9,4) DEFAULT NULL,
  `stockvalue` decimal(18,4) DEFAULT NULL,
  `store` char(10) NOT NULL,
  `journal` char(20) NOT NULL,
  `curr_cod` char(10) DEFAULT NULL,
  `period` int(11) DEFAULT NULL,
  `del` char(2) DEFAULT NULL,
  `factory` tinyint(1) DEFAULT NULL,
  `jobcard` char(10) DEFAULT NULL,
  `len` decimal(10,2) DEFAULT NULL,
  `wid` decimal(10,2) DEFAULT NULL,
  `sman` char(10) DEFAULT NULL,
  `pkey` bigint(20) NOT NULL,
  `BQitemcode` char(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stockmaster`
--

CREATE TABLE `stockmaster` (
  `isstock` char(1) NOT NULL,
  `barcode` char(20) DEFAULT NULL,
  `itemcode` char(20) DEFAULT NULL,
  `descrip` char(100) NOT NULL,
  `postinggroup` char(20) NOT NULL,
  `averagestock` decimal(10,2) DEFAULT NULL,
  `sellingprice` decimal(10,2) DEFAULT NULL,
  `partperunit` decimal(10,2) DEFAULT NULL,
  `reorderlevel` bigint(20) DEFAULT NULL,
  `eoq` bigint(20) DEFAULT NULL,
  `category` char(10) DEFAULT NULL,
  `units` char(10) DEFAULT NULL,
  `subunits` char(10) DEFAULT NULL,
  `container` char(20) DEFAULT NULL,
  `nextserialno` int(11) DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT NULL,
  `pkey` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stockregister`
--

CREATE TABLE `stockregister` (
  `itemcode` char(20) DEFAULT NULL,
  `StockIn` double DEFAULT NULL,
  `cost` double DEFAULT NULL,
  `StockOut` double DEFAULT NULL,
  `StockBalance` double DEFAULT NULL,
  `rowid` bigint(20) NOT NULL,
  `journal` char(20) DEFAULT NULL,
  `GRN` char(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stores`
--

CREATE TABLE `stores` (
  `code` char(10) NOT NULL,
  `Storename` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplierstatement`
--

CREATE TABLE `supplierstatement` (
  `Date` date NOT NULL,
  `Documentno` char(20) NOT NULL,
  `Documenttype` int(11) NOT NULL,
  `Accountno` char(20) NOT NULL,
  `Grossamount` decimal(18,2) NOT NULL,
  `JournalNo` char(20) NOT NULL,
  `Dimension_One` char(10) DEFAULT NULL,
  `Dimension_Two` char(10) DEFAULT NULL,
  `Currency` char(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_printers`
--

CREATE TABLE `system_printers` (
  `ipaddress` varchar(50) DEFAULT NULL,
  `printerName` longtext DEFAULT NULL,
  `defaultprinter` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `systypes_1`
--

CREATE TABLE `systypes_1` (
  `typeid` smallint(6) NOT NULL DEFAULT 0,
  `typename` char(50) NOT NULL,
  `typeno` int(11) NOT NULL DEFAULT 1,
  `prefix` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tanktrans`
--

CREATE TABLE `tanktrans` (
  `tankname` char(30) DEFAULT NULL,
  `units` double DEFAULT NULL,
  `uom` char(10) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `batchno` char(10) DEFAULT NULL,
  `variance` double DEFAULT NULL,
  `doctype` int(11) DEFAULT NULL,
  `itemcode` varchar(20) DEFAULT NULL,
  `BQitemcode` char(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `pkey` int(11) NOT NULL,
  `userid` varchar(20) DEFAULT NULL,
  `datecreated` datetime(6) DEFAULT NULL,
  `TaskOwner` varchar(20) DEFAULT NULL,
  `Taskname` longtext DEFAULT NULL,
  `datedue` date DEFAULT NULL,
  `Status` int(11) DEFAULT NULL,
  `Priority` int(11) DEFAULT NULL,
  `frequency` int(11) DEFAULT NULL,
  `taskdetails` longtext DEFAULT NULL,
  `alertedbymail` tinyint(1) DEFAULT NULL,
  `lastactivity` datetime(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `unit`
--

CREATE TABLE `unit` (
  `code` char(10) DEFAULT NULL,
  `descrip` char(50) NOT NULL,
  `sns` char(10) DEFAULT NULL,
  `pkey` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vatcategory`
--

CREATE TABLE `vatcategory` (
  `vatc` char(10) NOT NULL,
  `vat` decimal(10,2) NOT NULL,
  `vatdecrip` char(100) NOT NULL,
  `taxcatid` smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `www_users`
--

CREATE TABLE `www_users` (
  `userid` varchar(20) NOT NULL,
  `password` longtext NOT NULL,
  `realname` varchar(35) NOT NULL,
  `customerid` varchar(10) DEFAULT NULL,
  `supplierid` varchar(10) DEFAULT NULL,
  `salesman` char(3) DEFAULT NULL,
  `phone` varchar(30) NOT NULL,
  `email` varchar(55) DEFAULT NULL,
  `defaultlocation` varchar(5) NOT NULL,
  `fullaccess` int(11) NOT NULL DEFAULT 1,
  `cancreatetender` smallint(6) NOT NULL DEFAULT 0,
  `lastvisitdate` datetime(6) DEFAULT NULL,
  `branchcode` varchar(10) NOT NULL,
  `pagesize` varchar(20) NOT NULL,
  `modulesallowed` varchar(40) NOT NULL,
  `blocked` smallint(6) NOT NULL DEFAULT 0,
  `displayrecordsmax` int(11) NOT NULL DEFAULT 0,
  `theme` varchar(30) NOT NULL,
  `language` varchar(10) NOT NULL,
  `pdflanguage` smallint(6) NOT NULL DEFAULT 0,
  `department` int(11) NOT NULL DEFAULT 0,
  `Currentshiftno` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `acct`
--
ALTER TABLE `acct`
  ADD UNIQUE KEY `IX_acct` (`accno`);

--
-- Indexes for table `arpostinggroups`
--
ALTER TABLE `arpostinggroups`
  ADD PRIMARY KEY (`code`);

--
-- Indexes for table `assetsheader`
--
ALTER TABLE `assetsheader`
  ADD PRIMARY KEY (`documentno`);

--
-- Indexes for table `audittrail`
--
ALTER TABLE `audittrail`
  ADD KEY `audittrail$audittrail$audittrail_ibfk_1` (`userid`);

--
-- Indexes for table `billofmaterial`
--
ALTER TABLE `billofmaterial`
  ADD PRIMARY KEY (`parent_itemcode`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`coycode`);

--
-- Indexes for table `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`confname`);

--
-- Indexes for table `crmtasks`
--
ALTER TABLE `crmtasks`
  ADD PRIMARY KEY (`taskid`);

--
-- Indexes for table `currencies`
--
ALTER TABLE `currencies`
  ADD PRIMARY KEY (`currabrev`);

--
-- Indexes for table `dimensions`
--
ALTER TABLE `dimensions`
  ADD PRIMARY KEY (`Code`),
  ADD UNIQUE KEY `IX_Dimensions` (`id`,`Code`);

--
-- Indexes for table `dimensionsetup`
--
ALTER TABLE `dimensionsetup`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emailsettings`
--
ALTER TABLE `emailsettings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `financialperiods`
--
ALTER TABLE `financialperiods`
  ADD PRIMARY KEY (`start_date`);

--
-- Indexes for table `fixedassetcategories`
--
ALTER TABLE `fixedassetcategories`
  ADD PRIMARY KEY (`categoryid`);

--
-- Indexes for table `fixedassetlocations`
--
ALTER TABLE `fixedassetlocations`
  ADD PRIMARY KEY (`locationid`);

--
-- Indexes for table `fixedassets`
--
ALTER TABLE `fixedassets`
  ADD PRIMARY KEY (`assetid`);

--
-- Indexes for table `fixedassetsline`
--
ALTER TABLE `fixedassetsline`
  ADD KEY `FK_FixedAssetsLine_AssetsHeader` (`documentno`);

--
-- Indexes for table `fixedassettasks`
--
ALTER TABLE `fixedassettasks`
  ADD PRIMARY KEY (`taskid`);

--
-- Indexes for table `fixedassettrans`
--
ALTER TABLE `fixedassettrans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `generalledger`
--
ALTER TABLE `generalledger`
  ADD KEY `FK_Generalledger_acct` (`accountcode`),
  ADD KEY `FK_Generalledger_acct1` (`balaccountcode`),
  ADD KEY `FK_Generalledger_currencies` (`currencycode`);

--
-- Indexes for table `geocode_param`
--
ALTER TABLE `geocode_param`
  ADD PRIMARY KEY (`geocodeid`);

--
-- Indexes for table `glpostinggroup`
--
ALTER TABLE `glpostinggroup`
  ADD PRIMARY KEY (`code`);

--
-- Indexes for table `inventorypostinggroup`
--
ALTER TABLE `inventorypostinggroup`
  ADD PRIMARY KEY (`code`);

--
-- Indexes for table `laboratorystandards`
--
ALTER TABLE `laboratorystandards`
  ADD UNIQUE KEY `IX_LaboratoryStandards` (`sampletypeid`,`ParameterID`);

--
-- Indexes for table `labposting`
--
ALTER TABLE `labposting`
  ADD PRIMARY KEY (`SampleID`);

--
-- Indexes for table `labpostingdetail`
--
ALTER TABLE `labpostingdetail`
  ADD KEY `FK_LabPostingDetail_LabPosting` (`SampleID`);

--
-- Indexes for table `paymentvoucherheader`
--
ALTER TABLE `paymentvoucherheader`
  ADD UNIQUE KEY `IX_paymentvoucherheader` (`docno`,`journal`);

--
-- Indexes for table `periods`
--
ALTER TABLE `periods`
  ADD PRIMARY KEY (`periodno`);

--
-- Indexes for table `postinggroups`
--
ALTER TABLE `postinggroups`
  ADD PRIMARY KEY (`code`);

--
-- Indexes for table `prodcutionmasterline`
--
ALTER TABLE `prodcutionmasterline`
  ADD KEY `FK_ProdcutionMasterLine_ProductionMaster` (`Batchno`);

--
-- Indexes for table `productionmaster`
--
ALTER TABLE `productionmaster`
  ADD UNIQUE KEY `IX_ProductionMaster` (`Batchno`);

--
-- Indexes for table `productionunit`
--
ALTER TABLE `productionunit`
  ADD PRIMARY KEY (`tankname`);

--
-- Indexes for table `purchaseheader`
--
ALTER TABLE `purchaseheader`
  ADD PRIMARY KEY (`documentno`);

--
-- Indexes for table `purchaseline`
--
ALTER TABLE `purchaseline`
  ADD KEY `FK_PurchaseLine_PurchaseHeader` (`documentno`);

--
-- Indexes for table `reportcolumns`
--
ALTER TABLE `reportcolumns`
  ADD PRIMARY KEY (`reportid`,`colno`);

--
-- Indexes for table `reportfields`
--
ALTER TABLE `reportfields`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reportheaders`
--
ALTER TABLE `reportheaders`
  ADD PRIMARY KEY (`reportid`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `salesheader`
--
ALTER TABLE `salesheader`
  ADD PRIMARY KEY (`documentno`);

--
-- Indexes for table `sampletypeline`
--
ALTER TABLE `sampletypeline`
  ADD KEY `FK_SampleTypeLine_SampleTypes` (`sampletypeid`);

--
-- Indexes for table `sampletypes`
--
ALTER TABLE `sampletypes`
  ADD PRIMARY KEY (`sampletypeid`);

--
-- Indexes for table `scripts`
--
ALTER TABLE `scripts`
  ADD PRIMARY KEY (`script`);

--
-- Indexes for table `securitygroups`
--
ALTER TABLE `securitygroups`
  ADD PRIMARY KEY (`secroleid`,`tokenid`),
  ADD KEY `securitygroups$securitygroups$securitygroups_tokenid_fk` (`tokenid`);

--
-- Indexes for table `securityroles`
--
ALTER TABLE `securityroles`
  ADD PRIMARY KEY (`secroleid`);

--
-- Indexes for table `securitytokens`
--
ALTER TABLE `securitytokens`
  ADD PRIMARY KEY (`tokenid`);

--
-- Indexes for table `stockcategory`
--
ALTER TABLE `stockcategory`
  ADD PRIMARY KEY (`categoryid`);

--
-- Indexes for table `stockmaster`
--
ALTER TABLE `stockmaster`
  ADD KEY `FK_stockmaster_inventorypostinggroup` (`postinggroup`);

--
-- Indexes for table `stores`
--
ALTER TABLE `stores`
  ADD PRIMARY KEY (`code`);

--
-- Indexes for table `systypes_1`
--
ALTER TABLE `systypes_1`
  ADD PRIMARY KEY (`typeid`);

--
-- Indexes for table `vatcategory`
--
ALTER TABLE `vatcategory`
  ADD PRIMARY KEY (`vatc`);

--
-- Indexes for table `www_users`
--
ALTER TABLE `www_users`
  ADD PRIMARY KEY (`userid`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audittrail`
--
ALTER TABLE `audittrail`
  ADD CONSTRAINT `audittrail$audittrail$audittrail_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `www_users` (`userid`);

--
-- Constraints for table `dimensions`
--
ALTER TABLE `dimensions`
  ADD CONSTRAINT `FK_Dimensions_DimensionSetUp` FOREIGN KEY (`id`) REFERENCES `dimensionsetup` (`id`);

--
-- Constraints for table `fixedassetsline`
--
ALTER TABLE `fixedassetsline`
  ADD CONSTRAINT `FK_FixedAssetsLine_AssetsHeader` FOREIGN KEY (`documentno`) REFERENCES `assetsheader` (`documentno`);

--
-- Constraints for table `generalledger`
--
ALTER TABLE `generalledger`
  ADD CONSTRAINT `FK_Generalledger_acct` FOREIGN KEY (`accountcode`) REFERENCES `acct` (`accno`),
  ADD CONSTRAINT `FK_Generalledger_acct1` FOREIGN KEY (`balaccountcode`) REFERENCES `acct` (`accno`),
  ADD CONSTRAINT `FK_Generalledger_currencies` FOREIGN KEY (`currencycode`) REFERENCES `currencies` (`currabrev`);

--
-- Constraints for table `laboratorystandards`
--
ALTER TABLE `laboratorystandards`
  ADD CONSTRAINT `FK_LaboratoryStandards_SampleTypes` FOREIGN KEY (`sampletypeid`) REFERENCES `sampletypes` (`sampletypeid`);

--
-- Constraints for table `labpostingdetail`
--
ALTER TABLE `labpostingdetail`
  ADD CONSTRAINT `FK_LabPostingDetail_LabPosting` FOREIGN KEY (`SampleID`) REFERENCES `labposting` (`SampleID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `prodcutionmasterline`
--
ALTER TABLE `prodcutionmasterline`
  ADD CONSTRAINT `FK_ProdcutionMasterLine_ProductionMaster` FOREIGN KEY (`Batchno`) REFERENCES `productionmaster` (`Batchno`);

--
-- Constraints for table `purchaseline`
--
ALTER TABLE `purchaseline`
  ADD CONSTRAINT `FK_PurchaseLine_PurchaseHeader` FOREIGN KEY (`documentno`) REFERENCES `purchaseheader` (`documentno`);

--
-- Constraints for table `reportcolumns`
--
ALTER TABLE `reportcolumns`
  ADD CONSTRAINT `reportcolumns$reportcolumns$reportcolumns_ibfk_1` FOREIGN KEY (`reportid`) REFERENCES `reportheaders` (`reportid`);

--
-- Constraints for table `sampletypeline`
--
ALTER TABLE `sampletypeline`
  ADD CONSTRAINT `FK_SampleTypeLine_SampleTypes` FOREIGN KEY (`sampletypeid`) REFERENCES `sampletypes` (`sampletypeid`);

--
-- Constraints for table `securitygroups`
--
ALTER TABLE `securitygroups`
  ADD CONSTRAINT `securitygroups$securitygroups$securitygroups_secroleid_fk` FOREIGN KEY (`secroleid`) REFERENCES `securityroles` (`secroleid`),
  ADD CONSTRAINT `securitygroups$securitygroups$securitygroups_tokenid_fk` FOREIGN KEY (`tokenid`) REFERENCES `securitytokens` (`tokenid`);

--
-- Constraints for table `stockmaster`
--
ALTER TABLE `stockmaster`
  ADD CONSTRAINT `FK_stockmaster_inventorypostinggroup` FOREIGN KEY (`postinggroup`) REFERENCES `inventorypostinggroup` (`code`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
