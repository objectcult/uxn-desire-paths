-- ------------------------------------------------------
-- MySQL Server version	5.1.56

CREATE DATABASE `your database name here`;

--
-- Table structure for table `activity`
--
DROP TABLE IF EXISTS `activity`;
CREATE TABLE `activity` (
  `activity_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id_ref` int(11) unsigned NOT NULL,
  `activity_date` date NOT NULL,
  `type` varchar(10) NOT NULL,
  `tool_id_ref1` int(11) NOT NULL,
  `tool_id_ref2` int(11) DEFAULT NULL,
  `difficulty` int(11) DEFAULT NULL,
  `worth` int(11) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`activity_id`)
) ENGINE=MyISAM AUTO_INCREMENT=177 DEFAULT CHARSET=latin1;


--
-- Table structure for table `tools`
--
DROP TABLE IF EXISTS `tools`;
CREATE TABLE `tools` (
  `tool_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tool_name` varchar(150) NOT NULL,
  `added_by` int(11) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`tool_id`),
  KEY `added_by` (`added_by`)
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=latin1;


--
-- Table structure for table `users`
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(150) NOT NULL,
  `password` varchar(150) NOT NULL,
  `org` varchar(150) NOT NULL,
  `role` varchar(150) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
