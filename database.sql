-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 10, 2016 at 04:33 PM
-- Server version: 5.5.46-0ubuntu0.14.04.2
-- PHP Version: 5.5.9-1ubuntu4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `easyblender`
--

-- --------------------------------------------------------

--
-- Table structure for table `Exports`
--

CREATE TABLE IF NOT EXISTS `Exports` (
  `ID_Export` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ID_File_Export` int(10) unsigned NOT NULL,
  `ID_Format_Export` int(10) unsigned NOT NULL,
  `Name_Export` varchar(30) NOT NULL,
  `DateStart_Export` datetime NOT NULL,
  `Running_Export` tinyint(1) NOT NULL,
  `StartFrame_Export` int(11) NOT NULL,
  `EndFrame_Export` int(11) NOT NULL,
  PRIMARY KEY (`ID_Export`),
  KEY `ID_File_Export` (`ID_File_Export`),
  KEY `ID_Format_Export` (`ID_Format_Export`),
  KEY `Running_Export` (`Running_Export`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `Files`
--

CREATE TABLE IF NOT EXISTS `Files` (
  `ID_File` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Title_File` varchar(50) NOT NULL,
  `Name_File` varchar(30) NOT NULL,
  `DateCreate_File` datetime NOT NULL,
  PRIMARY KEY (`ID_File`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `Formats`
--

CREATE TABLE IF NOT EXISTS `Formats` (
  `ID_Format` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Name_Format` varchar(30) NOT NULL,
  `Extension_Format` varchar(10) NOT NULL,
  PRIMARY KEY (`ID_Format`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `Outputs`
--

CREATE TABLE IF NOT EXISTS `Outputs` (
  `ID_Output` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ID_Export_Output` int(10) unsigned NOT NULL,
  `File_Output` varchar(30) NOT NULL,
  `DateCreate_Output` datetime NOT NULL,
  PRIMARY KEY (`ID_Output`),
  KEY `ID_Export_Output` (`ID_Export_Output`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Exports`
--
ALTER TABLE `Exports`
  ADD CONSTRAINT `Exports_ibfk_1` FOREIGN KEY (`ID_File_Export`) REFERENCES `Files` (`ID_File`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `Exports_ibfk_2` FOREIGN KEY (`ID_Format_Export`) REFERENCES `Formats` (`ID_Format`) ON UPDATE CASCADE;

--
-- Constraints for table `Outputs`
--
ALTER TABLE `Outputs`
  ADD CONSTRAINT `Outputs_ibfk_1` FOREIGN KEY (`ID_Export_Output`) REFERENCES `Exports` (`ID_Export`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
