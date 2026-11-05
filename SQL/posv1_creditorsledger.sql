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
-- Table structure for table `creditorsledger`
--

DROP TABLE IF EXISTS `creditorsledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `period` int DEFAULT NULL,
  `systypes_1` int DEFAULT NULL,
  `ledger` char(20) DEFAULT NULL,
  `rowid` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `creditorsledger`
--

LOCK TABLES `creditorsledger` WRITE;
/*!40000 ALTER TABLE `creditorsledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `creditorsledger` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05 12:14:22
