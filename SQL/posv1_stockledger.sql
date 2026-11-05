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
-- Table structure for table `stockledger`
--

DROP TABLE IF EXISTS `stockledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `period` int DEFAULT NULL,
  `del` char(2) DEFAULT NULL,
  `factory` tinyint(1) DEFAULT NULL,
  `jobcard` char(10) DEFAULT NULL,
  `len` decimal(10,2) DEFAULT NULL,
  `wid` decimal(10,2) DEFAULT NULL,
  `sman` char(10) DEFAULT NULL,
  `pkey` bigint NOT NULL,
  `BQitemcode` char(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stockledger`
--

LOCK TABLES `stockledger` WRITE;
/*!40000 ALTER TABLE `stockledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `stockledger` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05 12:14:33
