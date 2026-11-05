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
-- Table structure for table `inventorypostinggroup`
--

DROP TABLE IF EXISTS `inventorypostinggroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventorypostinggroup` (
  `code` char(20) NOT NULL,
  `defaultgl_sales` char(20) DEFAULT NULL,
  `defaultgl_purch` char(20) DEFAULT NULL,
  `defaultgl_vat` char(20) DEFAULT NULL,
  `balancesheet` char(20) DEFAULT NULL,
  `vatcategory` char(1) DEFAULT NULL,
  `wip` char(20) DEFAULT NULL,
  `stockvariance` char(20) DEFAULT NULL,
  `productionexpense` char(20) DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventorypostinggroup`
--

LOCK TABLES `inventorypostinggroup` WRITE;
/*!40000 ALTER TABLE `inventorypostinggroup` DISABLE KEYS */;
INSERT INTO `inventorypostinggroup` VALUES ('Agency','an0000030','on0000034','','','z','','',''),('default','LA0020406','PU0020410','VA0020413','IN0020425','X','IN0020425','ST0020427','In0020428'),('novat','LA0020406','PU0020410','va0020413','IN0020425','E','IN0020425','ST0020427','In0020428');
/*!40000 ALTER TABLE `inventorypostinggroup` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05 12:14:21
