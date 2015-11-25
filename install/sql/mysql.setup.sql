-- ---
-- Globals
-- ---

-- SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
-- SET FOREIGN_KEY_CHECKS=0;

-- ---
-- Table 'Account'
-- 
-- ---

DROP TABLE IF EXISTS `Account`;
		
CREATE TABLE `Account` (
  `accountid` INTEGER NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(64) NOT NULL,
  `email` VARCHAR(128) NOT NULL,
  `password` VARCHAR(64) NOT NULL,
  PRIMARY KEY (`accountid`)
);

-- ---
-- Table 'Place'
-- 
-- ---

DROP TABLE IF EXISTS `Place`;
		
CREATE TABLE `Place` (
  `place_id` INTEGER NOT NULL AUTO_INCREMENT,
  `accountid` INTEGER NOT NULL,
  `name` VARCHAR(32) NOT NULL,
  `description` MEDIUMTEXT NULL DEFAULT NULL,
  `latitude` DECIMAL NOT NULL,
  `longitude` DECIMAL NOT NULL,
  PRIMARY KEY (`place_id`)
);

-- ---
-- Foreign Keys 
-- ---

ALTER TABLE `Place` ADD FOREIGN KEY (accountid) REFERENCES `Account` (`accountid`);

-- ---
-- Table Properties
-- ---

-- ALTER TABLE `Account` ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
-- ALTER TABLE `Place` ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- ---
-- Test Data
-- ---

-- INSERT INTO `Account` (`accountid`,`username`,`email`,`password`) VALUES
-- ('','','','');
-- INSERT INTO `Place` (`place_id`,`accountid`,`name`,`description`,`latitude`,`longitude`) VALUES
-- ('','','','','','');
