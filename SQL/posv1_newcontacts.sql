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
-- Table structure for table `newcontacts`
--

DROP TABLE IF EXISTS `newcontacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `newcontacts` (
  `company` longtext,
  `postcode` longtext,
  `city` char(20) DEFAULT NULL,
  `country` char(20) DEFAULT NULL,
  `Physical_Address` longtext,
  `PIN_VAT` longtext,
  `phone` char(15) DEFAULT NULL,
  `email` longtext,
  `salesman` char(10) DEFAULT NULL,
  `Contact_Name` longtext,
  `Contact_Designation` longtext,
  `Contact_Telephone` char(15) DEFAULT NULL,
  `Contact_email` longtext,
  `Alt_Contact_Name` longtext,
  `Alt_Contact_Designation` longtext,
  `Alt_Contact_Telephone` char(15) DEFAULT NULL,
  `Alt_Contact_email` longtext,
  `createdby` char(20) DEFAULT NULL,
  `Date_Created` datetime(6) DEFAULT NULL,
  `Last_Activity` datetime(6) DEFAULT NULL,
  `pkey` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newcontacts`
--

LOCK TABLES `newcontacts` WRITE;
/*!40000 ALTER TABLE `newcontacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `newcontacts` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05 12:14:29
