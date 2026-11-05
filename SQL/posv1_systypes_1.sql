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
-- Table structure for table `systypes_1`
--

DROP TABLE IF EXISTS `systypes_1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `systypes_1` (
  `typeid` smallint NOT NULL DEFAULT '0',
  `typename` char(50) NOT NULL,
  `typeno` int NOT NULL DEFAULT '1',
  `prefix` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`typeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `systypes_1`
--

LOCK TABLES `systypes_1` WRITE;
/*!40000 ALTER TABLE `systypes_1` DISABLE KEYS */;
INSERT INTO `systypes_1` VALUES (0,'Journal - GL',88,'Jv'),(1,'SalesOrder',10,'SO'),(2,'Pettey Cash',0,'PC'),(3,'Standing Journal',2,NULL),(10,'Sales Invoice',271,'24/'),(11,'Credit Note',0,'CN'),(12,'Receipt',1,'Rct'),(13,'Credit note debtors',0,'Rvs'),(15,'Journal - Debtors',0,NULL),(16,'Location Transfer',0,NULL),(17,'Stock Adjustment',2,NULL),(18,'Purchase Order',63,'LPO'),(19,'Picking List',4644,'LW'),(20,'Purchase Invoice',32,'PINV'),(21,'Debit Note',0,'DN'),(22,'Creditors Payment',0,NULL),(23,'Creditors Journal',0,NULL),(24,'Purchase Returns',0,'PR'),(25,'Purchase Order Delivery',1,NULL),(26,'Work Order Receipt',0,'WRK'),(28,'Work Order Issue',0,'B/No'),(29,'Work Order Variance',0,NULL),(30,'Goods Received Note',0,'GRN'),(31,'INVOICE',2,'INV'),(32,'Contract Close',0,NULL),(35,'Cost Update',0,NULL),(36,'Exchange Difference',4,NULL),(37,'Tenders',0,NULL),(38,'Stock Requests',0,NULL),(40,'Work Order',0,'REQ'),(41,'Asset Addition',34,NULL),(42,'Asset Category Change',0,NULL),(43,'Delete w/down asset',0,NULL),(44,'Depreciation',1,NULL),(49,'Import Fixed Assets',0,NULL),(50,'Opening Balance',0,NULL),(51,'Leave Application Form',0,NULL),(52,'Payroll Posting',0,NULL),(53,'Payment Voucher',0,NULL),(54,'Sales Quotation',6,'QUO'),(55,'Hire Equipments',1,'FA-'),(500,'Auto Debtor Number',0,NULL);
/*!40000 ALTER TABLE `systypes_1` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05 12:14:30
