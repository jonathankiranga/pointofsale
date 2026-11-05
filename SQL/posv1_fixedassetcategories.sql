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
-- Table structure for table `fixedassetcategories`
--

DROP TABLE IF EXISTS `fixedassetcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fixedassetcategories` (
  `categoryid` char(6) NOT NULL,
  `categorydescription` char(20) NOT NULL,
  `costact` varchar(20) NOT NULL,
  `depnact` varchar(20) NOT NULL,
  `disposalact` varchar(20) NOT NULL,
  `accumdepnact` varchar(20) NOT NULL,
  `defaultdepnrate` double NOT NULL DEFAULT '0.2',
  `defaultdepntype` int NOT NULL DEFAULT '1',
  `Equipment_hired_act` varchar(20) DEFAULT NULL,
  `defaultgl_vat_act` varchar(20) DEFAULT NULL,
  `vatcategorycode` char(10) DEFAULT NULL,
  PRIMARY KEY (`categoryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fixedassetcategories`
--

LOCK TABLES `fixedassetcategories` WRITE;
/*!40000 ALTER TABLE `fixedassetcategories` DISABLE KEYS */;
INSERT INTO `fixedassetcategories` VALUES ('EQ','EQUIPMENT','Eq0020421           ','De0020423           ','Fi0020424           ','De0020422           ',0.2,1,'LA0020416           ','va0020413           ','X');
/*!40000 ALTER TABLE `fixedassetcategories` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05 12:14:36
