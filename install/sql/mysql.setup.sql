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
  UNIQUE KEY (`username`),
  UNIQUE KEY (`email`)
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
  PRIMARY KEY (`account_id`)
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
  `max_teams` INTEGER NOT NULL DEFAULT -1,
  `max_team_members` INTEGER NOT NULL DEFAULT 4,
  `start_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `end_time` TIMESTAMP NULL DEFAULT NULL,
  `is_public` TINYINT NOT NULL DEFAULT 0,
  `is_enabled` TINYINT NOT NULL DEFAULT 0,
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
  `priority` INTEGER NOT NULL DEFAULT 1,
  `hint` VARCHAR(256) NULL DEFAULT NULL,
  `code` VARCHAR(32) NULL DEFAULT NULL,
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
  `name` VARCHAR(32) NOT NULL,
  `color` VARCHAR(24) NOT NULL,
  `access_code` VARCHAR(16) NULL DEFAULT NULL,
  `is_predefined` TINYINT NOT NULL DEFAULT 0,
  `starttime` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`team_id`),
  UNIQUE KEY (`team_id`, `challenge_id`),
  UNIQUE KEY (`challenge_id`, `name`)
);

-- ---
-- Table 'ChallengeCheckpoint'
-- 
-- ---

DROP TABLE IF EXISTS `ChallengeCheckpoint`;
		
CREATE TABLE `ChallengeCheckpoint` (
  `challenge_coord_id` INTEGER NOT NULL,
  `team_id` INTEGER NOT NULL,
  `time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
-- Table 'LoginToken'
-- 
-- ---

DROP TABLE IF EXISTS `LoginToken`;
		
CREATE TABLE `LoginToken` (
  `account_id` INTEGER NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  PRIMARY KEY (`account_id`)
);

-- ---
-- Table 'GeoCat'
-- 
-- ---

DROP TABLE IF EXISTS `GeoCat`;
		
CREATE TABLE `GeoCat` (
  `db_version` VARCHAR(16) NOT NULL DEFAULT 'NULL',
  `db_revision` INTEGER NOT NULL
);

-- ---
-- Table 'ChallengeStats'
-- 
-- ---

DROP TABLE IF EXISTS `ChallengeStats`;
		
CREATE TABLE `ChallengeStats` (
  `challenge_id` INTEGER NOT NULL,
  `team_id` INTEGER NOT NULL,
  `total_time` INTEGER NOT NULL,
  PRIMARY KEY (`challenge_id`, `team_id`)
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
ALTER TABLE `LoginToken` ADD FOREIGN KEY (account_id) REFERENCES `Account` (`account_id`);
ALTER TABLE `ChallengeStats` ADD FOREIGN KEY (challenge_id) REFERENCES `Challenge` (`challenge_id`);
ALTER TABLE `ChallengeStats` ADD FOREIGN KEY (team_id) REFERENCES `ChallengeTeam` (`team_id`);

