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
-- Table structure for table `debtors`
--

DROP TABLE IF EXISTS `debtors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtors` (
  `type` char(1) DEFAULT NULL,
  `istaff` int DEFAULT NULL,
  `cleared` tinyint(1) DEFAULT NULL,
  `pinno` char(20) DEFAULT NULL,
  `itemcode` varchar(20) DEFAULT NULL,
  `class` char(10) DEFAULT NULL,
  `cardadd` char(10) DEFAULT NULL,
  `contact` char(100) DEFAULT NULL,
  `defaultgl` char(10) DEFAULT NULL,
  `currbal` decimal(13,2) DEFAULT NULL,
  `flag` char(6) DEFAULT NULL,
  `date` datetime(6) DEFAULT NULL,
  `creditlimit` decimal(14,2) DEFAULT NULL,
  `customer` varchar(50) DEFAULT NULL,
  `status` char(5) DEFAULT NULL,
  `firstn` char(10) DEFAULT NULL,
  `middlen` char(15) DEFAULT NULL,
  `lastn` char(10) DEFAULT NULL,
  `phone` char(15) DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL,
  `company` char(100) DEFAULT NULL,
  `altcontact` char(100) DEFAULT NULL,
  `email` char(100) DEFAULT NULL,
  `city` char(100) DEFAULT NULL,
  `country` char(100) DEFAULT NULL,
  `preffpay` char(10) DEFAULT NULL,
  `crdcardno` char(10) DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT NULL,
  `namecrd` char(10) DEFAULT NULL,
  `postcode` char(100) DEFAULT NULL,
  `curr_cod` char(10) DEFAULT NULL,
  `curr_rat` decimal(9,5) DEFAULT NULL,
  `id` char(1) DEFAULT NULL,
  `i_n_t` char(1) DEFAULT NULL,
  `typ` char(2) DEFAULT NULL,
  `sns` char(10) DEFAULT NULL,
  `balance` decimal(18,4) DEFAULT NULL,
  `age1` bigint DEFAULT NULL,
  `age2` decimal(18,4) DEFAULT NULL,
  `age3` decimal(18,4) DEFAULT NULL,
  `age4` decimal(18,4) DEFAULT NULL,
  `pkey` bigint NOT NULL,
  `islocal` tinyint(1) DEFAULT NULL,
  `username` char(20) DEFAULT NULL,
  `customerposting` varchar(20) DEFAULT NULL,
  `salesman` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `debtors`
--

LOCK TABLES `debtors` WRITE;
/*!40000 ALTER TABLE `debtors` DISABLE KEYS */;
/*!40000 ALTER TABLE `debtors` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05 12:14:12
