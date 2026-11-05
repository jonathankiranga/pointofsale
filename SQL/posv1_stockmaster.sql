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
-- Table structure for table `stockmaster`
--

DROP TABLE IF EXISTS `stockmaster`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stockmaster` (
  `isstock` char(1) NOT NULL,
  `barcode` char(20) DEFAULT NULL,
  `itemcode` char(20) DEFAULT NULL,
  `descrip` char(100) NOT NULL,
  `postinggroup` char(20) NOT NULL,
  `averagestock` decimal(10,2) DEFAULT NULL,
  `sellingprice` decimal(10,2) DEFAULT NULL,
  `partperunit` decimal(10,2) DEFAULT NULL,
  `reorderlevel` bigint DEFAULT NULL,
  `eoq` bigint DEFAULT NULL,
  `category` char(10) DEFAULT NULL,
  `units` char(10) DEFAULT NULL,
  `subunits` char(10) DEFAULT NULL,
  `container` char(20) DEFAULT NULL,
  `nextserialno` int DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT NULL,
  `pkey` bigint NOT NULL,
  KEY `FK_stockmaster_inventorypostinggroup` (`postinggroup`),
  CONSTRAINT `FK_stockmaster_inventorypostinggroup` FOREIGN KEY (`postinggroup`) REFERENCES `inventorypostinggroup` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stockmaster`
--

LOCK TABLES `stockmaster` WRITE;
/*!40000 ALTER TABLE `stockmaster` DISABLE KEYS */;
INSERT INTO `stockmaster` VALUES ('1','k0001','Ke000000','Kenya Aireways','Agency',NULL,100.00,0.00,0,1212,'01','0','0','0',0,1,200000);
/*!40000 ALTER TABLE `stockmaster` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05 12:14:35
