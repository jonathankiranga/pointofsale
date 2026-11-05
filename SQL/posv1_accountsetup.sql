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
-- Table structure for table `accountsetup`
--

DROP TABLE IF EXISTS `accountsetup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accountsetup`
--

LOCK TABLES `accountsetup` WRITE;
/*!40000 ALTER TABLE `accountsetup` DISABLE KEYS */;
/*!40000 ALTER TABLE `accountsetup` ENABLE KEYS */;
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
