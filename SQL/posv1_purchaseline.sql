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
-- Table structure for table `purchaseline`
--

DROP TABLE IF EXISTS `purchaseline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchaseline` (
  `entryno` bigint NOT NULL,
  `documenttype` int NOT NULL,
  `docdate` datetime NOT NULL,
  `documentno` varchar(20) NOT NULL,
  `locationcode` char(10) DEFAULT NULL,
  `stocktype` int DEFAULT NULL,
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
  `containersunits` bigint DEFAULT NULL,
  `totalchargedcontainers` decimal(10,2) DEFAULT NULL,
  `containercode` varchar(20) DEFAULT NULL,
  `vatrate` decimal(10,2) DEFAULT NULL,
  `inclusive` tinyint(1) DEFAULT NULL,
  `UOM` varchar(10) DEFAULT NULL,
  `shipping` double DEFAULT NULL,
  KEY `FK_PurchaseLine_PurchaseHeader` (`documentno`),
  CONSTRAINT `FK_PurchaseLine_PurchaseHeader` FOREIGN KEY (`documentno`) REFERENCES `purchaseheader` (`documentno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchaseline`
--

LOCK TABLES `purchaseline` WRITE;
/*!40000 ALTER TABLE `purchaseline` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchaseline` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05 12:14:15
