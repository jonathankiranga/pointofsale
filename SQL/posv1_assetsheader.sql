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
-- Table structure for table `assetsheader`
--

DROP TABLE IF EXISTS `assetsheader`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assetsheader` (
  `documenttype` int NOT NULL,
  `documentno` varchar(20) NOT NULL,
  `docdate` datetime DEFAULT NULL,
  `oderdate` datetime DEFAULT NULL,
  `duedate` datetime DEFAULT NULL,
  `postingdate` datetime DEFAULT NULL,
  `vendorcode` varchar(20) NOT NULL,
  `vendorname` varchar(50) NOT NULL,
  `yourreference` varchar(30) DEFAULT NULL,
  `externaldocumentno` varchar(30) DEFAULT NULL,
  `locationcode` char(10) DEFAULT NULL,
  `paymentterms` char(10) DEFAULT NULL,
  `postinggroup` char(20) NOT NULL,
  `currencycode` char(10) NOT NULL,
  `printed` int DEFAULT NULL,
  `released` int DEFAULT NULL,
  `status` int DEFAULT NULL,
  `userid` char(20) NOT NULL,
  `period` int NOT NULL,
  `entryno` bigint NOT NULL,
  `vatinclusive` tinyint(1) DEFAULT NULL,
  `Dimension_1` char(10) DEFAULT NULL,
  `Dimension_2` char(10) DEFAULT NULL,
  PRIMARY KEY (`documentno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assetsheader`
--

LOCK TABLES `assetsheader` WRITE;
/*!40000 ALTER TABLE `assetsheader` DISABLE KEYS */;
INSERT INTO `assetsheader` VALUES (41,'10','2017-09-12 00:00:00',NULL,'2017-09-13 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',3,17,1,'',''),(41,'11','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','obal',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,42,1,'',''),(41,'12','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','obal',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,43,1,'',''),(41,'13','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,44,1,'',''),(41,'14','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,45,1,'',''),(41,'15','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,46,1,'',''),(41,'16','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,47,1,'',''),(41,'17','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,48,1,'',''),(41,'18','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,49,1,'',''),(41,'19','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,50,1,'',''),(18,'2','2017-09-05 00:00:00',NULL,'2017-09-07 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',1,2,1,'admin',3,1,1,'',''),(41,'20','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,51,1,'',''),(41,'21','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,52,1,'',''),(41,'22','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,53,1,'',''),(41,'23','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,54,1,'',''),(41,'24','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,55,1,'',''),(41,'25','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,56,1,'',''),(41,'26','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,57,1,'',''),(41,'27','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,58,1,'',''),(41,'28','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,59,1,'',''),(41,'29','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,60,1,'',''),(41,'3','2017-09-05 00:00:00',NULL,'2017-09-07 00:00:00',NULL,'cr2','Imported From USA','obal',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',3,3,1,'',''),(41,'30','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,61,1,'',''),(41,'31','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,62,1,'',''),(41,'32','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,63,1,'',''),(41,'33','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',4,64,1,'',''),(19,'34','2017-09-15 00:00:00',NULL,'2017-09-23 00:00:00',NULL,'Dr9','SGS Kenya','FA-1',NULL,'',NULL,'General','KES',NULL,NULL,1,'MarthaLabworks',4,66,0,'',''),(41,'4','2017-09-12 00:00:00',NULL,'2017-09-13 00:00:00',NULL,'cr2','Imported From USA','obal',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',3,11,1,'',''),(41,'5','2017-09-12 00:00:00',NULL,'2017-09-13 00:00:00',NULL,'cr2','Imported From USA','obal',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',3,12,1,'',''),(41,'6','2017-09-12 00:00:00',NULL,'2017-09-13 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',3,13,1,'',''),(41,'7','2017-09-12 00:00:00',NULL,'2017-09-13 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',3,14,1,'',''),(41,'8','2017-09-12 00:00:00',NULL,'2017-09-13 00:00:00',NULL,'cr2','Imported From USA','open',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',3,15,1,'',''),(41,'9','2017-09-12 00:00:00',NULL,'2017-09-13 00:00:00',NULL,'cr2','Imported From USA','ope',NULL,'',NULL,'Default','USD',NULL,NULL,1,'admin',3,16,1,'',''),(55,'FA-1','2017-09-15 00:00:00',NULL,'2017-09-23 00:00:00',NULL,'Dr9','SGS Kenya','',NULL,'',NULL,'General','KES',1,1,1,'MarthaLabworks',3,65,0,'',''),(18,'LPO10','2017-09-12 00:00:00',NULL,'2017-09-13 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'MarthaLabworks',3,8,1,'',''),(18,'LPO11','2017-09-12 00:00:00',NULL,'2017-09-13 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'MarthaLabworks',3,9,1,'',''),(18,'LPO12','2017-09-12 00:00:00',NULL,'2017-09-13 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'MarthaLabworks',3,10,1,'',''),(18,'LPO13','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,18,1,'',''),(18,'LPO14','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,19,1,'',''),(18,'LPO15','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,20,1,'',''),(18,'LPO16','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',1,2,1,'admin',4,21,1,'',''),(18,'LPO17','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,22,1,'',''),(18,'LPO18','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,23,1,'',''),(18,'LPO19','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',1,2,1,'admin',4,24,1,'',''),(18,'LPO20','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',1,2,1,'admin',4,25,1,'',''),(18,'LPO21','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,26,1,'',''),(18,'LPO22','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,27,1,'',''),(18,'LPO23','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,28,1,'',''),(18,'LPO24','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,29,1,'',''),(18,'LPO25','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,30,1,'',''),(18,'LPO26','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,31,1,'',''),(18,'LPO27','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',1,2,1,'admin',4,32,1,'',''),(18,'LPO28','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,33,1,'',''),(18,'LPO29','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,34,1,'',''),(18,'LPO30','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,35,1,'',''),(18,'LPO31','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,36,1,'',''),(18,'LPO33','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,37,1,'',''),(18,'LPO34','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,38,1,'',''),(18,'LPO35','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,39,1,'',''),(18,'LPO36','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,40,1,'',''),(18,'LPO37','2017-10-02 00:00:00',NULL,'2017-10-03 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'admin',4,41,1,'',''),(18,'LPO7','2017-09-12 00:00:00',NULL,'2017-09-13 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',1,2,1,'MarthaLabworks',3,5,1,'',''),(18,'LPO8','2017-09-12 00:00:00',NULL,'2017-09-13 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'MarthaLabworks',3,6,1,'',''),(18,'LPO9','2017-09-12 00:00:00',NULL,'2017-09-13 00:00:00',NULL,'cr2','Imported From USA','',NULL,'',NULL,'Default','USD',NULL,2,1,'MarthaLabworks',3,7,1,'','');
/*!40000 ALTER TABLE `assetsheader` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05 12:14:17
