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
-- Table structure for table `creditors`
--

DROP TABLE IF EXISTS `creditors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `creditors` (
  `itemcode` varchar(20) DEFAULT NULL,
  `class` char(10) DEFAULT NULL,
  `defaultgl` char(10) DEFAULT NULL,
  `vat` tinyint(1) DEFAULT NULL,
  `contact` char(10) DEFAULT NULL,
  `flag` char(6) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `customer` char(30) DEFAULT NULL,
  `status` char(5) DEFAULT NULL,
  `firstn` char(50) DEFAULT NULL,
  `middlen` char(50) DEFAULT NULL,
  `lastn` char(50) DEFAULT NULL,
  `phone` char(50) DEFAULT NULL,
  `fax` char(50) DEFAULT NULL,
  `company` char(50) DEFAULT NULL,
  `altcontact` char(50) DEFAULT NULL,
  `email` char(50) DEFAULT NULL,
  `city` char(50) DEFAULT NULL,
  `country` char(50) DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT NULL,
  `vatregno` char(50) DEFAULT NULL,
  `postcode` char(100) DEFAULT NULL,
  `curr_cod` char(10) DEFAULT NULL,
  `curr_rat` decimal(9,5) DEFAULT NULL,
  `sns` char(10) DEFAULT NULL,
  `balance` decimal(18,4) DEFAULT NULL,
  `age1` decimal(18,4) DEFAULT NULL,
  `age2` decimal(18,4) DEFAULT NULL,
  `age3` decimal(18,4) DEFAULT NULL,
  `age4` decimal(18,4) DEFAULT NULL,
  `pkey` bigint NOT NULL,
  `saved` tinyint(1) DEFAULT NULL,
  `supplierposting` varchar(20) DEFAULT NULL,
  `IsEmployee` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `creditors`
--

LOCK TABLES `creditors` WRITE;
/*!40000 ALTER TABLE `creditors` DISABLE KEYS */;
INSERT INTO `creditors` VALUES ('AP1',NULL,NULL,NULL,'',NULL,NULL,'LUBIMBAYI',NULL,'','',NULL,'','','','','','','Kenya',0,'','','KES',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'Default',NULL),('AP2',NULL,NULL,NULL,'',NULL,NULL,'Imported From USA',NULL,'','',NULL,'','','','','','','United States',0,'','','USD',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'Default',NULL),('AP3',NULL,NULL,NULL,'',NULL,NULL,'Chemoquip',NULL,'','',NULL,'','','','','','','Kenya',0,'','','KES',NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,'Default',NULL),('AP4',NULL,NULL,NULL,'',NULL,NULL,'PYREX EAST AFRICA LTD',NULL,'JULIUS','',NULL,'0722615464','','P.O. BOX 4769-00100','0733918058','sales@py-rexeastafrica.com','NAIROBI','Kenya',0,'P051163178','','KES',NULL,NULL,NULL,NULL,NULL,NULL,NULL,4,NULL,'Default',NULL),('AP5',NULL,NULL,NULL,'',NULL,NULL,'Gradko International Ltd',NULL,'','',NULL,'+44 (0)196','','St Martins House','','sales@gradko.com','Winchester','United Kingdom',0,'','77 Wales Street','EUR',NULL,NULL,NULL,NULL,NULL,NULL,NULL,5,NULL,'Default',NULL),('AP6',NULL,NULL,NULL,'',NULL,NULL,'Vision Scientific and Eng. KE',NULL,'','',NULL,'0706538240','','Alpha Center Mbs Rd','','info@visionscienticficafrica.com','Nairobi','Kenya',0,'051454950G','','KES',NULL,NULL,NULL,NULL,NULL,NULL,NULL,6,NULL,'Default',NULL),('AP7',NULL,NULL,NULL,'',NULL,NULL,'Hilack Traders &amp; Company',NULL,'Lucas','',NULL,'','','PO Box 50192','','hillacktraderco@gmail.com','Nairobi','Kenya',0,'A0022470231','00200','KES',NULL,NULL,NULL,NULL,NULL,NULL,NULL,7,NULL,'Default',NULL),('AP8',NULL,NULL,NULL,'',NULL,NULL,'Mission for Essential Drugs an',NULL,'','',NULL,'0719086000','','P.O. Box 78040 â€“00507,','','laboratory@meds.or.ke',' Viwandani- Nairobi','Kenya',0,'','','EUR',NULL,NULL,NULL,NULL,NULL,NULL,NULL,8,NULL,'Default',NULL),('AP9',NULL,NULL,NULL,'',NULL,NULL,'DLA Scientific Ltd',NULL,'','',NULL,'0727801143','','P.O. Box 7482','','rebeccah.waithanji@dlascientific.com','Nairobi','Kenya',0,'P051167387N','00100','KES',NULL,NULL,NULL,NULL,NULL,NULL,NULL,9,NULL,'Default',NULL),('AP10',NULL,NULL,NULL,'',NULL,NULL,'Africa Biosystems Limited',NULL,'','',NULL,'0729244884','','PO Box 66814 - 00800','','info@africabiosystems.com','Nairobi','Kenya',0,'','','EUR',NULL,NULL,NULL,NULL,NULL,NULL,NULL,10,NULL,'Default',NULL),('AP11',NULL,NULL,NULL,'',NULL,NULL,'KEBS',NULL,'','',NULL,'0206948211','','P.O BOX 54974','','Info@kebs.org','Nairobi','Kenya',0,'P0510928837Y','00200','KES',NULL,NULL,NULL,NULL,NULL,NULL,NULL,11,NULL,'Default',NULL),('AP12',NULL,NULL,NULL,'',NULL,NULL,'UNBS','','','','','2564142861','','Bweyogerere Industrial and BusinessPark','','info@unbs.go.ug','Kampala','Uganda',0,'','P.O Box 6329','USD',NULL,NULL,NULL,NULL,NULL,NULL,NULL,12,NULL,'Default',NULL),('AP15',NULL,NULL,NULL,'',NULL,NULL,'Muhimbili University',NULL,'','',NULL,'2552221503','','9 United Nations Road','','','Dar Es Salaam','Tanzania, United Rep. of',0,'','P.O Box 65001','EUR',NULL,NULL,NULL,NULL,NULL,NULL,NULL,15,NULL,'Default',NULL);
/*!40000 ALTER TABLE `creditors` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05 12:14:25
