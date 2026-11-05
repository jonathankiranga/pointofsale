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
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `coycode` int NOT NULL DEFAULT '1',
  `coyname` varchar(50) NOT NULL,
  `PIN` varchar(20) NOT NULL,
  `vat` varchar(20) NOT NULL,
  `regoffice1` varchar(40) NOT NULL,
  `regoffice2` varchar(40) NOT NULL,
  `regoffice3` varchar(40) NOT NULL,
  `regoffice4` varchar(40) NOT NULL,
  `regoffice5` varchar(20) NOT NULL,
  `regoffice6` varchar(15) NOT NULL,
  `telephone` varchar(25) NOT NULL,
  `fax` varchar(25) NOT NULL,
  `email` varchar(55) NOT NULL,
  `currencydefault` varchar(4) NOT NULL,
  `DefaultDimension_1` int DEFAULT NULL,
  `DefaultDimension_2` int DEFAULT NULL,
  `shiftno` int DEFAULT NULL,
  `Sign1` longtext,
  `Sign2` longtext,
  `Signaturename_1` varchar(30) DEFAULT NULL,
  `Signaturename_2` varchar(30) DEFAULT NULL,
  `Signaturename_3` varchar(30) DEFAULT NULL,
  `Sign3` longtext,
  `last_depreciation` datetime DEFAULT NULL,
  PRIMARY KEY (`coycode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'Lab Works East Africa Ltd','P051601780C','P051601780C','P.O. Box 6459-00100','Shelter Afrique Centre, 3rd floor,','Upperhill','Nairobi','Kenya',' ','0202724481; 0746349943','','technical@labworksea.com','KES',0,0,49,'companies/LIMSLABWORKS/Signatures/signature1.jpg','companies/LIMSLABWORKS/Signatures/signature2.jpg','Jacob Kipkoech','Judy Kiarie','Joseph Karanja','companies/LIMSLABWORKS/Signatures/signature3.jpg',NULL);
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05 12:14:41
