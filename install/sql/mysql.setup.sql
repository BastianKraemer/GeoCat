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
  `account_id` INTEGER NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(64) NOT NULL,
  `password` VARCHAR(64) NULL,
  `salt` VARCHAR(32) NULL,
  `email` VARCHAR(64) NULL,
  `type` INTEGER NOT NULL DEFAULT 0,
  `is_administrator` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY (`username`)
);

-- ---
-- Table 'Place'
-- 
-- ---

DROP TABLE IF EXISTS `Place`;
		
CREATE TABLE `Place` (
  `coord_id` INTEGER NOT NULL,
  `account_id` INTEGER NOT NULL,
  `is_public` TINYINT NOT NULL DEFAULT 0,
  `creation_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modification_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY (`coord_id`)
);

-- ---
-- Table 'CurrentNavigation'
-- 
-- ---

DROP TABLE IF EXISTS `CurrentNavigation`;
		
CREATE TABLE `CurrentNavigation` (
  `account_id` INTEGER NOT NULL,
  `coord_id` INTEGER NOT NULL,
  UNIQUE KEY (`account_id`, `coord_id`)
);

-- ---
-- Table 'AccountInformation'
-- 
-- ---

DROP TABLE IF EXISTS `AccountInformation`;
		
CREATE TABLE `AccountInformation` (
  `account_id` INTEGER NOT NULL,
  `lastname` VARCHAR(64) NULL DEFAULT NULL,
  `firstname` VARCHAR(64) NULL DEFAULT NULL,
  `avatar` VARCHAR(128) NULL DEFAULT NULL,
  `show_email_addr` TINYINT NOT NULL DEFAULT 0,
  `my_position` INTEGER NULL DEFAULT NULL,
  `my_position_timestamp` TIMESTAMP NULL DEFAULT NULL,
  `last_login` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creation_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `failed_login_timestamp` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY (`account_id`)
);

-- ---
-- Table 'Coordinate'
-- 
-- ---

DROP TABLE IF EXISTS `Coordinate`;
		
CREATE TABLE `Coordinate` (
  `coord_id` INTEGER NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(64) NOT NULL,
  `description` VARCHAR(256) NULL DEFAULT NULL,
  `latitude` DECIMAL(9,6) NOT NULL,
  `longitude` DECIMAL(9,6) NOT NULL,
  PRIMARY KEY (`coord_id`)
);

-- ---
-- Table 'Challenge'
-- 
-- ---

DROP TABLE IF EXISTS `Challenge`;
		
CREATE TABLE `Challenge` (
  `challenge_id` INTEGER NOT NULL AUTO_INCREMENT,
  `challenge_type_id` INTEGER NOT NULL,
  `owner` INTEGER NOT NULL,
  `sessionkey` VARCHAR(8) NULL DEFAULT NULL,
  `name` VARCHAR(64) NOT NULL,
  `description` VARCHAR(512) NOT NULL,
  `predefined_teams` TINYINT NOT NULL DEFAULT 0,
  `max_teams` INTEGER NOT NULL DEFAULT 4,
  `max_team_members` INTEGER NOT NULL DEFAULT 4,
  `start_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `end_time` TIMESTAMP NULL DEFAULT NULL,
  `is_public` TINYINT NOT NULL DEFAULT 0,
  `is_visible` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`challenge_id`),
  UNIQUE KEY (`sessionkey`)
);

-- ---
-- Table 'ChallengeMember'
-- 
-- ---

DROP TABLE IF EXISTS `ChallengeMember`;
		
CREATE TABLE `ChallengeMember` (
  `team_id` INTEGER NOT NULL,
  `account_id` INTEGER NOT NULL,
  UNIQUE KEY (`team_id`, `account_id`)
);

-- ---
-- Table 'ChallengeCoord'
-- 
-- ---

DROP TABLE IF EXISTS `ChallengeCoord`;
		
CREATE TABLE `ChallengeCoord` (
  `challenge_coord_id` INTEGER NOT NULL AUTO_INCREMENT,
  `challenge_id` INTEGER NOT NULL,
  `coord_id` INTEGER NOT NULL,
  `index` INTEGER NOT NULL,
  `code` VARCHAR(16) NULL DEFAULT NULL,
  `verify_user_pos` TINYINT(1) NULL DEFAULT NULL,
  `captured_by` INTEGER NULL DEFAULT NULL,
  `capture_time` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`challenge_coord_id`),
  UNIQUE KEY (`challenge_id`, `coord_id`)
);

-- ---
-- Table 'Friends'
-- 
-- ---

DROP TABLE IF EXISTS `Friends`;
		
CREATE TABLE `Friends` (
  `account_id` INTEGER NOT NULL,
  `friend_id` INTEGER NOT NULL,
  UNIQUE KEY (`account_id`, `friend_id`)
);

-- ---
-- Table 'ChallengeTeam'
-- 
-- ---

DROP TABLE IF EXISTS `ChallengeTeam`;
		
CREATE TABLE `ChallengeTeam` (
  `team_id` INTEGER NOT NULL AUTO_INCREMENT,
  `challenge_id` INTEGER NOT NULL,
  `name` VARCHAR(64) NOT NULL,
  `color` VARCHAR(24) NOT NULL,
  `max_members` INTEGER NOT NULL DEFAULT -1,
  `immutable_teamname` TINYINT NOT NULL DEFAULT 0,
  `starttime` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`team_id`),
  UNIQUE KEY (`team_id`, `challenge_id`)
);

-- ---
-- Table 'ChallengeCheckpoint'
-- 
-- ---

DROP TABLE IF EXISTS `ChallengeCheckpoint`;
		
CREATE TABLE `ChallengeCheckpoint` (
  `challenge_coord_id` INTEGER NOT NULL,
  `team_id` INTEGER NOT NULL,
  `time` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY (`team_id`, `challenge_coord_id`)
);

-- ---
-- Table 'AccountType'
-- 
-- ---

DROP TABLE IF EXISTS `AccountType`;
		
CREATE TABLE `AccountType` (
  `acc_type_id` INTEGER NOT NULL,
  `name` VARCHAR(8) NOT NULL,
  PRIMARY KEY (`acc_type_id`)
);

-- ---
-- Table 'ChallengeType'
-- 
-- ---

DROP TABLE IF EXISTS `ChallengeType`;
		
CREATE TABLE `ChallengeType` (
  `challenge_type_id` INTEGER NOT NULL,
  `acronym` VARCHAR(8) NOT NULL,
  `full_name` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`challenge_type_id`)
);

-- ---
-- Table 'GuestAccount'
-- 
-- ---

DROP TABLE IF EXISTS `GuestAccount`;
		
CREATE TABLE `GuestAccount` (
  `next_number` INTEGER NOT NULL
);

-- ---
-- Foreign Keys 
-- ---

ALTER TABLE `Account` ADD FOREIGN KEY (type) REFERENCES `AccountType` (`acc_type_id`);
ALTER TABLE `Place` ADD FOREIGN KEY (coord_id) REFERENCES `Coordinate` (`coord_id`);
ALTER TABLE `Place` ADD FOREIGN KEY (account_id) REFERENCES `Account` (`account_id`);
ALTER TABLE `CurrentNavigation` ADD FOREIGN KEY (account_id) REFERENCES `Account` (`account_id`);
ALTER TABLE `CurrentNavigation` ADD FOREIGN KEY (coord_id) REFERENCES `Coordinate` (`coord_id`);
ALTER TABLE `AccountInformation` ADD FOREIGN KEY (account_id) REFERENCES `Account` (`account_id`);
ALTER TABLE `AccountInformation` ADD FOREIGN KEY (my_position) REFERENCES `Coordinate` (`coord_id`);
ALTER TABLE `Challenge` ADD FOREIGN KEY (challenge_type_id) REFERENCES `ChallengeType` (`challenge_type_id`);
ALTER TABLE `Challenge` ADD FOREIGN KEY (owner) REFERENCES `Account` (`account_id`);
ALTER TABLE `ChallengeMember` ADD FOREIGN KEY (team_id) REFERENCES `ChallengeTeam` (`team_id`);
ALTER TABLE `ChallengeMember` ADD FOREIGN KEY (account_id) REFERENCES `Account` (`account_id`);
ALTER TABLE `ChallengeCoord` ADD FOREIGN KEY (challenge_id) REFERENCES `Challenge` (`challenge_id`);
ALTER TABLE `ChallengeCoord` ADD FOREIGN KEY (coord_id) REFERENCES `Coordinate` (`coord_id`);
ALTER TABLE `ChallengeCoord` ADD FOREIGN KEY (captured_by) REFERENCES `ChallengeTeam` (`team_id`);
ALTER TABLE `Friends` ADD FOREIGN KEY (account_id) REFERENCES `Account` (`account_id`);
ALTER TABLE `Friends` ADD FOREIGN KEY (friend_id) REFERENCES `Account` (`account_id`);
ALTER TABLE `ChallengeTeam` ADD FOREIGN KEY (challenge_id) REFERENCES `Challenge` (`challenge_id`);
ALTER TABLE `ChallengeCheckpoint` ADD FOREIGN KEY (challenge_coord_id) REFERENCES `ChallengeCoord` (`challenge_coord_id`);
ALTER TABLE `ChallengeCheckpoint` ADD FOREIGN KEY (team_id) REFERENCES `ChallengeTeam` (`team_id`);

-- ---
-- Table Properties
-- ---

-- ALTER TABLE `Account` ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
-- ALTER TABLE `Place` ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
-- ALTER TABLE `CurrentNavigation` ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
-- ALTER TABLE `AccountInformation` ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
-- ALTER TABLE `Coordinate` ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
-- ALTER TABLE `Challenge` ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
-- ALTER TABLE `ChallengeMember` ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
-- ALTER TABLE `ChallengeCoord` ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
-- ALTER TABLE `Friends` ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
-- ALTER TABLE `ChallengeTeam` ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
-- ALTER TABLE `ChallengeCheckpoint` ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
-- ALTER TABLE `AccountType` ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
-- ALTER TABLE `ChallengeType` ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
-- ALTER TABLE `GuestAccount` ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- ---
-- Test Data
-- ---

-- INSERT INTO `Account` (`account_id`,`username`,`password`,`salt`,`email`,`type`,`is_administrator`) VALUES
-- ('','','','','','','');
-- INSERT INTO `Place` (`coord_id`,`account_id`,`is_public`,`creation_date`,`modification_date`) VALUES
-- ('','','','','');
-- INSERT INTO `CurrentNavigation` (`account_id`,`coord_id`) VALUES
-- ('','');
-- INSERT INTO `AccountInformation` (`account_id`,`lastname`,`firstname`,`avatar`,`show_email_addr`,`my_position`,`my_position_timestamp`,`last_login`,`creation_date`,`failed_login_timestamp`) VALUES
-- ('','','','','','','','','','');
-- INSERT INTO `Coordinate` (`coord_id`,`name`,`description`,`latitude`,`longitude`) VALUES
-- ('','','','','');
-- INSERT INTO `Challenge` (`challenge_id`,`challenge_type_id`,`owner`,`sessionkey`,`name`,`description`,`predefined_teams`,`max_teams`,`max_team_members`,`start_time`,`end_time`,`is_public`,`is_visible`) VALUES
-- ('','','','','','','','','','','','','');
-- INSERT INTO `ChallengeMember` (`team_id`,`account_id`) VALUES
-- ('','');
-- INSERT INTO `ChallengeCoord` (`challenge_coord_id`,`challenge_id`,`coord_id`,`index`,`code`,`verify_user_pos`,`captured_by`,`capture_time`) VALUES
-- ('','','','','','','','');
-- INSERT INTO `Friends` (`account_id`,`friend_id`) VALUES
-- ('','');
-- INSERT INTO `ChallengeTeam` (`team_id`,`challenge_id`,`name`,`color`,`max_members`,`immutable_teamname`,`starttime`) VALUES
-- ('','','','','','','');
-- INSERT INTO `ChallengeCheckpoint` (`challenge_coord_id`,`team_id`,`time`) VALUES
-- ('','','');
-- INSERT INTO `AccountType` (`acc_type_id`,`name`) VALUES
-- ('','');
-- INSERT INTO `ChallengeType` (`challenge_type_id`,`acronym`,`full_name`) VALUES
-- ('','','');
-- INSERT INTO `GuestAccount` (`next_number`) VALUES
-- ('');
