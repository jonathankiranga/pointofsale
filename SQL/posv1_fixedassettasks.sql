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
-- Table structure for table `fixedassettasks`
--

DROP TABLE IF EXISTS `fixedassettasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fixedassettasks` (
  `taskid` int NOT NULL,
  `assetid` int NOT NULL,
  `taskdescription` longtext NOT NULL,
  `frequencydays` int NOT NULL DEFAULT '365',
  `lastcompleted` datetime(6) NOT NULL,
  `userresponsible` varchar(20) NOT NULL,
  `manager` varchar(20) NOT NULL,
  PRIMARY KEY (`taskid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fixedassettasks`
--

LOCK TABLES `fixedassettasks` WRITE;
/*!40000 ALTER TABLE `fixedassettasks` DISABLE KEYS */;
INSERT INTO `fixedassettasks` VALUES (2,1,'NEXT CALIBRATION DATE',51,'2017-09-05 00:00:00.000000','david','Administrator'),(3,2,'NEXT CALIBRATION DATE',51,'2017-09-05 00:00:00.000000','david','Administrator'),(4,3,'NEXT SERVICE DATE',48,'2017-09-05 00:00:00.000000','david','Administrator'),(5,4,'NEXT SERVICE DATE',51,'2017-09-05 00:00:00.000000','david','Administrator'),(6,5,'NEXT SERVICE DATE',48,'2017-09-05 00:00:00.000000','david','Administrator'),(7,6,'NEXT CALIBRATION DATE',51,'2017-09-05 00:00:00.000000','david','Administrator'),(8,7,'NEXT CALIBRATION DATE',51,'2017-09-05 00:00:00.000000','david','Administrator'),(9,8,'NEXT CALIBRATION DATE',51,'2017-09-12 00:00:00.000000','david','Administrator'),(10,9,'NEXT CALIBRATION DATE',51,'2017-09-12 00:00:00.000000','david','Administrator'),(11,10,'NEXT CALIBRATION DATE',44,'2017-09-12 00:00:00.000000','david','Administrator');
/*!40000 ALTER TABLE `fixedassettasks` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05 12:14:40
