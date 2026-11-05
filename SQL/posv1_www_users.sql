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
-- Table structure for table `www_users`
--

DROP TABLE IF EXISTS `www_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `www_users` (
  `userid` varchar(20) NOT NULL,
  `password` longtext NOT NULL,
  `realname` varchar(35) NOT NULL,
  `customerid` varchar(10) DEFAULT NULL,
  `supplierid` varchar(10) DEFAULT NULL,
  `salesman` char(3) DEFAULT NULL,
  `phone` varchar(30) NOT NULL,
  `email` varchar(55) DEFAULT NULL,
  `defaultlocation` varchar(5) NOT NULL,
  `fullaccess` int NOT NULL DEFAULT '1',
  `cancreatetender` smallint NOT NULL DEFAULT '0',
  `lastvisitdate` datetime(6) DEFAULT NULL,
  `branchcode` varchar(10) NOT NULL,
  `pagesize` varchar(20) NOT NULL,
  `modulesallowed` varchar(40) NOT NULL,
  `blocked` smallint NOT NULL DEFAULT '0',
  `displayrecordsmax` int NOT NULL DEFAULT '0',
  `theme` varchar(30) NOT NULL,
  `language` varchar(10) NOT NULL,
  `pdflanguage` smallint NOT NULL DEFAULT '0',
  `department` int NOT NULL DEFAULT '0',
  `Currentshiftno` int DEFAULT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `www_users`
--

LOCK TABLES `www_users` WRITE;
/*!40000 ALTER TABLE `www_users` DISABLE KEYS */;
INSERT INTO `www_users` VALUES ('admin','smarterp',' Administrator','','','','','jonathankiranga@gmail.com','',11,1,'2024-12-24 12:18:35.000000','','A4','1,1,1,1,1,1,1,1,1,1,1,1,',0,999,'xenos','en_GB.utf8',3,0,NULL);
/*!40000 ALTER TABLE `www_users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05 12:14:23
