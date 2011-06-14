-- phpMyAdmin SQL Dump
-- version 3.2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jun 14, 2011 at 11:47 PM
-- Server version: 5.5.13
-- PHP Version: 5.3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `lootTracker`
--

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE IF NOT EXISTS `groups` (
  `groupID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `opID` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`groupID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `lootData`
--

CREATE TABLE IF NOT EXISTS `lootData` (
  `groupID` mediumint(8) unsigned NOT NULL,
  `typeID` smallint(6) unsigned NOT NULL,
  `amount` mediumint(8) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `marketData`
--

CREATE TABLE IF NOT EXISTS `marketData` (
  `typeID` smallint(6) unsigned NOT NULL,
  `medianBuy` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `memberList`
--

CREATE TABLE IF NOT EXISTS `memberList` (
  `charID` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `roles` bigint(19) unsigned NOT NULL,
  PRIMARY KEY (`charID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Stand-in structure for view `memberPayout`
--
CREATE TABLE IF NOT EXISTS `memberPayout` (
`saleID` mediumint(8)
,`name` varchar(255)
,`charID` int(10) unsigned
,`payout` decimal(65,0)
);
-- --------------------------------------------------------

--
-- Table structure for table `op2sale`
--

CREATE TABLE IF NOT EXISTS `op2sale` (
  `opID` mediumint(8) NOT NULL,
  `saleID` mediumint(8) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `operations`
--

CREATE TABLE IF NOT EXISTS `operations` (
  `opID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `charID` int(10) unsigned NOT NULL COMMENT 'CharacterID of operation owner',
  `title` varchar(70) NOT NULL,
  `description` text NOT NULL,
  `timeStart` int(10) NOT NULL,
  `timeEnd` int(10) DEFAULT NULL,
  PRIMARY KEY (`opID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `participants`
--

CREATE TABLE IF NOT EXISTS `participants` (
  `charID` int(10) unsigned NOT NULL,
  `groupID` mediumint(8) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Stand-in structure for view `payoutView`
--
CREATE TABLE IF NOT EXISTS `payoutView` (
`saleID` mediumint(8)
,`groupID` mediumint(8) unsigned
,`perMember` decimal(61,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `profit-payout`
--
CREATE TABLE IF NOT EXISTS `profit-payout` (
`saleID` mediumint(8)
,`difference` decimal(65,0)
);
-- --------------------------------------------------------

--
-- Table structure for table `saleData`
--

CREATE TABLE IF NOT EXISTS `saleData` (
  `saleID` mediumint(8) unsigned NOT NULL,
  `typeID` smallint(6) unsigned NOT NULL,
  `profit` decimal(13,0) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `saleHistory`
--

CREATE TABLE IF NOT EXISTS `saleHistory` (
  `saleID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `seller` int(10) unsigned NOT NULL,
  `saleTime` int(10) unsigned NOT NULL,
  `payer` int(10) unsigned DEFAULT NULL,
  `payedTime` int(10) unsigned DEFAULT NULL,
  `tax` decimal(3,2) NOT NULL COMMENT 'Tax used with sale',
  PRIMARY KEY (`saleID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Stand-in structure for view `saleTotalProfit`
--
CREATE TABLE IF NOT EXISTS `saleTotalProfit` (
`saleID` mediumint(8) unsigned
,`profit` decimal(35,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `saleView`
--
CREATE TABLE IF NOT EXISTS `saleView` (
`saleID` mediumint(8)
,`groupID` mediumint(8) unsigned
,`typeID` smallint(6) unsigned
,`payout` decimal(43,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `saleViewVerbose`
--
CREATE TABLE IF NOT EXISTS `saleViewVerbose` (
`saleID` mediumint(8)
,`groupID` mediumint(8) unsigned
,`typeID` smallint(6) unsigned
,`typeName` varchar(100)
,`total` decimal(30,0)
,`completeTotal` decimal(30,0)
,`percent` decimal(32,2)
,`payout` decimal(43,0)
);
-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `charID` int(10) unsigned NOT NULL,
  `pass` varchar(50) NOT NULL,
  `login_date` int(10) unsigned NOT NULL,
  `login_addr` int(10) unsigned NOT NULL,
  `sessionID` varchar(255) NOT NULL,
  PRIMARY KEY (`charID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure for view `memberPayout`
--
DROP TABLE IF EXISTS `memberPayout`;

CREATE ALGORITHM=UNDEFINED VIEW `memberPayout` AS select `op2sale`.`saleID` AS `saleID`,`memberList`.`name` AS `name`,`memberList`.`charID` AS `charID`,sum(`payoutView`.`perMember`) AS `payout` from ((((`memberList` join `participants` on((`memberList`.`charID` = `participants`.`charID`))) join `groups` on((`participants`.`groupID` = `groups`.`groupID`))) join `op2sale` on((`groups`.`opID` = `op2sale`.`opID`))) join `payoutView` on(((`participants`.`groupID` = `payoutView`.`groupID`) and (`op2sale`.`saleID` = `payoutView`.`saleID`)))) group by `op2sale`.`saleID`,`memberList`.`charID`;

-- --------------------------------------------------------

--
-- Structure for view `payoutView`
--
DROP TABLE IF EXISTS `payoutView`;

CREATE ALGORITHM=UNDEFINED VIEW `payoutView` AS select `t1`.`saleID` AS `saleID`,`t1`.`groupID` AS `groupID`,truncate((sum(`t1`.`payout`) / (select count(`participants`.`charID`) from `participants` where (`participants`.`groupID` = `t1`.`groupID`) group by `participants`.`groupID`)),0) AS `perMember` from `saleView` `t1` group by `t1`.`groupID`;

-- --------------------------------------------------------

--
-- Structure for view `profit-payout`
--
DROP TABLE IF EXISTS `profit-payout`;

CREATE ALGORITHM=UNDEFINED VIEW `profit-payout` AS select `memberPayout`.`saleID` AS `saleID`,(`saleTotalProfit`.`profit` - sum(`memberPayout`.`payout`)) AS `difference` from (`memberPayout` join `saleTotalProfit` on((`memberPayout`.`saleID` = `saleTotalProfit`.`saleID`))) group by `memberPayout`.`saleID`;

-- --------------------------------------------------------

--
-- Structure for view `saleTotalProfit`
--
DROP TABLE IF EXISTS `saleTotalProfit`;

CREATE ALGORITHM=UNDEFINED VIEW `saleTotalProfit` AS select `saleData`.`saleID` AS `saleID`,sum(`saleData`.`profit`) AS `profit` from `saleData` group by `saleData`.`saleID`;

-- --------------------------------------------------------

--
-- Structure for view `saleView`
--
DROP TABLE IF EXISTS `saleView`;

CREATE ALGORITHM=UNDEFINED VIEW `saleView` AS select `t1`.`saleID` AS `saleID`,`groups`.`groupID` AS `groupID`,`t2`.`typeID` AS `typeID`,truncate((truncate((sum(`t2`.`amount`) / (select sum(`lootData`.`amount`) from ((`lootData` join `groups` on((`lootData`.`groupID` = `groups`.`groupID`))) join `op2sale` on((`groups`.`opID` = `op2sale`.`opID`))) where ((`op2sale`.`saleID` = `t1`.`saleID`) and (`lootData`.`typeID` = `t2`.`typeID`)) group by `lootData`.`typeID`)),2) * `saleData`.`profit`),0) AS `payout` from ((((`groups` join `op2sale` `t1` on((`groups`.`opID` = `t1`.`opID`))) join `saleHistory` on((`t1`.`saleID` = `saleHistory`.`saleID`))) join `lootData` `t2` on((`groups`.`groupID` = `t2`.`groupID`))) join `saleData` on(((`t1`.`saleID` = `saleData`.`saleID`) and (`t2`.`typeID` = `saleData`.`typeID`)))) group by `t2`.`groupID`,`t2`.`typeID`;

-- --------------------------------------------------------

--
-- Structure for view `saleViewVerbose`
--
DROP TABLE IF EXISTS `saleViewVerbose`;

CREATE ALGORITHM=UNDEFINED VIEW `saleViewVerbose` AS select `t1`.`saleID` AS `saleID`,`groups`.`groupID` AS `groupID`,`t2`.`typeID` AS `typeID`,`invTypes`.`typeName` AS `typeName`,sum(`t2`.`amount`) AS `total`,(select sum(`lootData`.`amount`) from ((`lootData` join `groups` on((`lootData`.`groupID` = `groups`.`groupID`))) join `op2sale` on((`groups`.`opID` = `op2sale`.`opID`))) where ((`op2sale`.`saleID` = `t1`.`saleID`) and (`lootData`.`typeID` = `t2`.`typeID`)) group by `lootData`.`typeID`) AS `completeTotal`,truncate((sum(`t2`.`amount`) / (select sum(`lootData`.`amount`) from ((`lootData` join `groups` on((`lootData`.`groupID` = `groups`.`groupID`))) join `op2sale` on((`groups`.`opID` = `op2sale`.`opID`))) where ((`op2sale`.`saleID` = `t1`.`saleID`) and (`lootData`.`typeID` = `t2`.`typeID`)) group by `lootData`.`typeID`)),2) AS `percent`,truncate((truncate((sum(`t2`.`amount`) / (select sum(`lootData`.`amount`) from ((`lootData` join `groups` on((`lootData`.`groupID` = `groups`.`groupID`))) join `op2sale` on((`groups`.`opID` = `op2sale`.`opID`))) where ((`op2sale`.`saleID` = `t1`.`saleID`) and (`lootData`.`typeID` = `t2`.`typeID`)) group by `lootData`.`typeID`)),2) * `saleData`.`profit`),0) AS `payout` from (((((`groups` join `op2sale` `t1` on((`groups`.`opID` = `t1`.`opID`))) join `saleHistory` on((`t1`.`saleID` = `saleHistory`.`saleID`))) join `lootData` `t2` on((`groups`.`groupID` = `t2`.`groupID`))) join `saleData` on(((`t1`.`saleID` = `saleData`.`saleID`) and (`t2`.`typeID` = `saleData`.`typeID`)))) join `invTypes` on((`invTypes`.`typeID` = `t2`.`typeID`))) group by `t2`.`groupID`,`t2`.`typeID`;
