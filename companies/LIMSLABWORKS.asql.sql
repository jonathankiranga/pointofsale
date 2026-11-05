-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 22, 2024 at 12:37 PM
-- Server version: 10.6.16-MariaDB
-- PHP Version: 8.2.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `LIMSLABWORKS`
--

--
-- Dumping data for table `assetsheader`
--

INSERT INTO `assetsheader` (`documenttype`, `documentno`, `docdate`, `oderdate`, `duedate`, `postingdate`, `vendorcode`, `vendorname`, `yourreference`, `externaldocumentno`, `locationcode`, `paymentterms`, `postinggroup`, `currencycode`, `printed`, `released`, `status`, `userid`, `period`, `entryno`, `vatinclusive`, `Dimension_1`, `Dimension_2`) VALUES
(41, '10', '2017-09-12 00:00:00', NULL, '2017-09-13 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 3, 17, 1, '', ''),
(41, '11', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'obal', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 42, 1, '', ''),
(41, '12', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'obal', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 43, 1, '', ''),
(41, '13', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 44, 1, '', ''),
(41, '14', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 45, 1, '', ''),
(41, '15', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 46, 1, '', ''),
(41, '16', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 47, 1, '', ''),
(41, '17', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 48, 1, '', ''),
(41, '18', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 49, 1, '', ''),
(41, '19', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 50, 1, '', ''),
(18, '2', '2017-09-05 00:00:00', NULL, '2017-09-07 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', 1, 2, 1, 'admin', 3, 1, 1, '', ''),
(41, '20', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 51, 1, '', ''),
(41, '21', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 52, 1, '', ''),
(41, '22', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 53, 1, '', ''),
(41, '23', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 54, 1, '', ''),
(41, '24', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 55, 1, '', ''),
(41, '25', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 56, 1, '', ''),
(41, '26', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 57, 1, '', ''),
(41, '27', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 58, 1, '', ''),
(41, '28', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 59, 1, '', ''),
(41, '29', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 60, 1, '', ''),
(41, '3', '2017-09-05 00:00:00', NULL, '2017-09-07 00:00:00', NULL, 'cr2', 'Imported From USA', 'obal', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 3, 3, 1, '', ''),
(41, '30', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 61, 1, '', ''),
(41, '31', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 62, 1, '', ''),
(41, '32', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 63, 1, '', ''),
(41, '33', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 4, 64, 1, '', ''),
(19, '34', '2017-09-15 00:00:00', NULL, '2017-09-23 00:00:00', NULL, 'Dr9', 'SGS Kenya', 'FA-1', NULL, '', NULL, 'General', 'KES', NULL, NULL, 1, 'MarthaLabworks', 4, 66, 0, '', ''),
(41, '4', '2017-09-12 00:00:00', NULL, '2017-09-13 00:00:00', NULL, 'cr2', 'Imported From USA', 'obal', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 3, 11, 1, '', ''),
(41, '5', '2017-09-12 00:00:00', NULL, '2017-09-13 00:00:00', NULL, 'cr2', 'Imported From USA', 'obal', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 3, 12, 1, '', ''),
(41, '6', '2017-09-12 00:00:00', NULL, '2017-09-13 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 3, 13, 1, '', ''),
(41, '7', '2017-09-12 00:00:00', NULL, '2017-09-13 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 3, 14, 1, '', ''),
(41, '8', '2017-09-12 00:00:00', NULL, '2017-09-13 00:00:00', NULL, 'cr2', 'Imported From USA', 'open', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 3, 15, 1, '', ''),
(41, '9', '2017-09-12 00:00:00', NULL, '2017-09-13 00:00:00', NULL, 'cr2', 'Imported From USA', 'ope', NULL, '', NULL, 'Default', 'USD', NULL, NULL, 1, 'admin', 3, 16, 1, '', ''),
(55, 'FA-1', '2017-09-15 00:00:00', NULL, '2017-09-23 00:00:00', NULL, 'Dr9', 'SGS Kenya', '', NULL, '', NULL, 'General', 'KES', 1, 1, 1, 'MarthaLabworks', 3, 65, 0, '', ''),
(18, 'LPO10', '2017-09-12 00:00:00', NULL, '2017-09-13 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'MarthaLabworks', 3, 8, 1, '', ''),
(18, 'LPO11', '2017-09-12 00:00:00', NULL, '2017-09-13 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'MarthaLabworks', 3, 9, 1, '', ''),
(18, 'LPO12', '2017-09-12 00:00:00', NULL, '2017-09-13 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'MarthaLabworks', 3, 10, 1, '', ''),
(18, 'LPO13', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 18, 1, '', ''),
(18, 'LPO14', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 19, 1, '', ''),
(18, 'LPO15', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 20, 1, '', ''),
(18, 'LPO16', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', 1, 2, 1, 'admin', 4, 21, 1, '', ''),
(18, 'LPO17', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 22, 1, '', ''),
(18, 'LPO18', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 23, 1, '', ''),
(18, 'LPO19', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', 1, 2, 1, 'admin', 4, 24, 1, '', ''),
(18, 'LPO20', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', 1, 2, 1, 'admin', 4, 25, 1, '', ''),
(18, 'LPO21', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 26, 1, '', ''),
(18, 'LPO22', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 27, 1, '', ''),
(18, 'LPO23', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 28, 1, '', ''),
(18, 'LPO24', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 29, 1, '', ''),
(18, 'LPO25', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 30, 1, '', ''),
(18, 'LPO26', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 31, 1, '', ''),
(18, 'LPO27', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', 1, 2, 1, 'admin', 4, 32, 1, '', ''),
(18, 'LPO28', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 33, 1, '', ''),
(18, 'LPO29', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 34, 1, '', ''),
(18, 'LPO30', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 35, 1, '', ''),
(18, 'LPO31', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 36, 1, '', ''),
(18, 'LPO33', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 37, 1, '', ''),
(18, 'LPO34', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 38, 1, '', ''),
(18, 'LPO35', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 39, 1, '', ''),
(18, 'LPO36', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 40, 1, '', ''),
(18, 'LPO37', '2017-10-02 00:00:00', NULL, '2017-10-03 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'admin', 4, 41, 1, '', ''),
(18, 'LPO7', '2017-09-12 00:00:00', NULL, '2017-09-13 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', 1, 2, 1, 'MarthaLabworks', 3, 5, 1, '', ''),
(18, 'LPO8', '2017-09-12 00:00:00', NULL, '2017-09-13 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'MarthaLabworks', 3, 6, 1, '', ''),
(18, 'LPO9', '2017-09-12 00:00:00', NULL, '2017-09-13 00:00:00', NULL, 'cr2', 'Imported From USA', '', NULL, '', NULL, 'Default', 'USD', NULL, 2, 1, 'MarthaLabworks', 3, 7, 1, '', '');

--
-- Dumping data for table `creditors`
--

INSERT INTO `creditors` (`itemcode`, `class`, `defaultgl`, `vat`, `contact`, `flag`, `date`, `customer`, `status`, `firstn`, `middlen`, `lastn`, `phone`, `fax`, `company`, `altcontact`, `email`, `city`, `country`, `inactive`, `vatregno`, `postcode`, `curr_cod`, `curr_rat`, `sns`, `balance`, `age1`, `age2`, `age3`, `age4`, `pkey`, `saved`, `supplierposting`, `IsEmployee`) VALUES
('AP1', NULL, NULL, NULL, '', NULL, NULL, 'LUBIMBAYI', NULL, '', '', NULL, '', '', '', '', '', '', 'Kenya', 0, '', '', 'KES', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, 'Default', NULL),
('AP2', NULL, NULL, NULL, '', NULL, NULL, 'Imported From USA', NULL, '', '', NULL, '', '', '', '', '', '', 'United States', 0, '', '', 'USD', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, 'Default', NULL),
('AP3', NULL, NULL, NULL, '', NULL, NULL, 'Chemoquip', NULL, '', '', NULL, '', '', '', '', '', '', 'Kenya', 0, '', '', 'KES', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, 'Default', NULL),
('AP4', NULL, NULL, NULL, '', NULL, NULL, 'PYREX EAST AFRICA LTD', NULL, 'JULIUS', '', NULL, '0722615464', '', 'P.O. BOX 4769-00100', '0733918058', 'sales@py-rexeastafrica.com', 'NAIROBI', 'Kenya', 0, 'P051163178', '', 'KES', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, 'Default', NULL),
('AP5', NULL, NULL, NULL, '', NULL, NULL, 'Gradko International Ltd', NULL, '', '', NULL, '+44 (0)196', '', 'St Martins House', '', 'sales@gradko.com', 'Winchester', 'United Kingdom', 0, '', '77 Wales Street', 'EUR', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, 'Default', NULL),
('AP6', NULL, NULL, NULL, '', NULL, NULL, 'Vision Scientific and Eng. KE', NULL, '', '', NULL, '0706538240', '', 'Alpha Center Mbs Rd', '', 'info@visionscienticficafrica.com', 'Nairobi', 'Kenya', 0, '051454950G', '', 'KES', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, NULL, 'Default', NULL),
('AP7', NULL, NULL, NULL, '', NULL, NULL, 'Hilack Traders &amp; Company', NULL, 'Lucas', '', NULL, '', '', 'PO Box 50192', '', 'hillacktraderco@gmail.com', 'Nairobi', 'Kenya', 0, 'A0022470231', '00200', 'KES', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, NULL, 'Default', NULL),
('AP8', NULL, NULL, NULL, '', NULL, NULL, 'Mission for Essential Drugs an', NULL, '', '', NULL, '0719086000', '', 'P.O. Box 78040 â€“00507,', '', 'laboratory@meds.or.ke', ' Viwandani- Nairobi', 'Kenya', 0, '', '', 'EUR', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 8, NULL, 'Default', NULL),
('AP9', NULL, NULL, NULL, '', NULL, NULL, 'DLA Scientific Ltd', NULL, '', '', NULL, '0727801143', '', 'P.O. Box 7482', '', 'rebeccah.waithanji@dlascientific.com', 'Nairobi', 'Kenya', 0, 'P051167387N', '00100', 'KES', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, NULL, 'Default', NULL),
('AP10', NULL, NULL, NULL, '', NULL, NULL, 'Africa Biosystems Limited', NULL, '', '', NULL, '0729244884', '', 'PO Box 66814 - 00800', '', 'info@africabiosystems.com', 'Nairobi', 'Kenya', 0, '', '', 'EUR', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 10, NULL, 'Default', NULL),
('AP11', NULL, NULL, NULL, '', NULL, NULL, 'KEBS', NULL, '', '', NULL, '0206948211', '', 'P.O BOX 54974', '', 'Info@kebs.org', 'Nairobi', 'Kenya', 0, 'P0510928837Y', '00200', 'KES', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 'Default', NULL),
('AP12', NULL, NULL, NULL, '', NULL, NULL, 'UNBS', '', '', '', '', '2564142861', '', 'Bweyogerere Industrial and BusinessPark', '', 'info@unbs.go.ug', 'Kampala', 'Uganda', 0, '', 'P.O Box 6329', 'USD', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, NULL, 'Default', NULL),
('AP15', NULL, NULL, NULL, '', NULL, NULL, 'Muhimbili University', NULL, '', '', NULL, '2552221503', '', '9 United Nations Road', '', '', 'Dar Es Salaam', 'Tanzania, United Rep. of', 0, '', 'P.O Box 65001', 'EUR', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, 'Default', NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
