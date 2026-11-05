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
-- Table structure for table `salesheader`
--

DROP TABLE IF EXISTS `salesheader`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salesheader` (
  `documenttype` int NOT NULL,
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
  `printed` int DEFAULT NULL,
  `released` int DEFAULT NULL,
  `status` int DEFAULT NULL,
  `userid` char(20) NOT NULL,
  `period` int NOT NULL,
  `entryno` bigint NOT NULL,
  `vatinclusive` tinyint(1) DEFAULT NULL,
  `discount` double DEFAULT NULL,
  `sampleID` varchar(20) DEFAULT NULL,
  `lpo` varchar(20) DEFAULT NULL,
  `BatchNO` varchar(20) DEFAULT NULL,
  `Bybatch` tinyint(1) DEFAULT NULL,
  `total` float DEFAULT NULL,
  `cash` float DEFAULT NULL,
  `change_amount` float DEFAULT NULL,
  PRIMARY KEY (`documentno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesheader`
--

LOCK TABLES `salesheader` WRITE;
/*!40000 ALTER TABLE `salesheader` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesheader` ENABLE KEYS */;
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
