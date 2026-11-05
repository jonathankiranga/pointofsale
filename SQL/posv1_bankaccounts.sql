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
-- Table structure for table `bankaccounts`
--

DROP TABLE IF EXISTS `bankaccounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bankaccounts` (
  `PKey` int NOT NULL,
  `accountcode` char(10) DEFAULT NULL,
  `bankName` varchar(50) NOT NULL,
  `currency` char(3) NOT NULL,
  `lastreconcileddate` date DEFAULT NULL,
  `AccountNo` char(20) DEFAULT NULL,
  `BranchCode` char(10) DEFAULT NULL,
  `BranchName` varchar(50) DEFAULT NULL,
  `lastreconbalance` decimal(18,2) DEFAULT NULL,
  `StatementNo` int DEFAULT NULL,
  `lastChequeno` int DEFAULT NULL,
  `PostingGroup` char(20) NOT NULL,
  `Makeinactive` tinyint(1) DEFAULT NULL,
  `Fluctuation` char(20) DEFAULT NULL,
  `AcctName` varchar(100) DEFAULT NULL,
  `bankCode` varchar(20) DEFAULT NULL,
  `swiftcode` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bankaccounts`
--

LOCK TABLES `bankaccounts` WRITE;
/*!40000 ALTER TABLE `bankaccounts` DISABLE KEYS */;
INSERT INTO `bankaccounts` VALUES (1,'BK1','FLOAT','KES',NULL,'','','',0.00,NULL,0,'Pe0020419',0,'EX0020412',NULL,NULL,NULL),(2,'BK2','CFC KES','KES',NULL,'','','',0.00,NULL,0,'CF0020420',0,'EX0020412',NULL,NULL,NULL);
/*!40000 ALTER TABLE `bankaccounts` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05 12:14:13
