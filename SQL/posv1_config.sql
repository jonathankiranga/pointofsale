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
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `config` (
  `confname` varchar(35) NOT NULL,
  `confvalue` longtext NOT NULL,
  PRIMARY KEY (`confname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config`
--

LOCK TABLES `config` WRITE;
/*!40000 ALTER TABLE `config` DISABLE KEYS */;
INSERT INTO `config` VALUES ('CheckCreditLimits','1'),('Conformity','Whenever Labworks East Africa Ltd issues a statement of conformity, the decision rules are applied taking into considerations the uncertainty of measurements for the individual parameters. The statements shall be issued on outright &quot;PASS&quot; and outright &quot;FAIL&quot;. Conditional PASS or FAIL can maybe issued for borderline results at the discretion of the Laboratory.'),('CountryOfOperation','KE'),('DB_Maintenance','0'),('DB_Maintenance_LastRun','2013-05-25'),('DefaultDateFormat','d/m/Y'),('DefaultDisplayRecordsMax','50'),('DefaultPriceList','DE'),('DefaultTheme','xenos'),('DimensionID_1','0'),('DimensionID_2','0'),('ExchangeRateFeed','ECB'),('expire_time','20*60'),('FinancialYearBegins','2024-01-01'),('HighFile','companies/LIMSLABWORKS/database.sql'),('LowFile','companies/LIMSLABWORKS/database.sql'),('MonthsAuditTrail','1'),('OptimumFile','companies/LIMSLABWORKS/database.sql'),('reports_dir','companies/LIMSLABWORKS/reports'),('RomalpaClause','ACOUNT NAME: LABWORKS EAST AFRICA LIMITED&lt;br&gt;BANK: STANBIC BANK, KENYA&lt;br&gt;BRANCH: UPPERHILL&lt;br&gt;BANK CODE: 31000&lt;br&gt;BRANCH CODE: 010&lt;br&gt;ACCOUNT NUMBER:&lt;br&gt;BUSINESS ACCOUNT KES: 0100004721335&lt;br&gt;BUSINESS ACCOUNT USD: 0100004721343&lt;br&gt;SWIFT CODE: SBICKENX&lt;br&gt;PHYSICAL ADDRESS: STANBIC LIMITED, CHIROMO ROAD, P.O BOX 72833, 00200 NAIROBI&lt;br&gt;&lt;br&gt;'),('UpdateCurrencyRatesDaily','2017-09-28'),('VersionNumber','1.1.1');
/*!40000 ALTER TABLE `config` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05 12:14:16
