CREATE TABLE Account (
account_id  SERIAL NOT NULL ,
username VARCHAR(64) NOT NULL ,
password VARCHAR(64) ,
salt VARCHAR(32) ,
email VARCHAR(64) ,
type INTEGER NOT NULL DEFAULT 0 ,
is_administrator SMALLINT NOT NULL DEFAULT 0 ,
PRIMARY KEY (account_id),
UNIQUE (username)
);

CREATE TABLE Place (
coord_id INTEGER NOT NULL ,
account_id INTEGER NOT NULL ,
is_public SMALLINT NOT NULL DEFAULT 0 ,
creation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
modification_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
UNIQUE (coord_id)
);

CREATE TABLE CurrentNavigation (
account_id INTEGER NOT NULL ,
coord_id INTEGER NOT NULL ,
UNIQUE (account_id, coord_id)
);

CREATE TABLE AccountInformation (
account_id INTEGER NOT NULL ,
lastname VARCHAR(64) ,
firstname VARCHAR(64) ,
avatar VARCHAR(128) ,
show_email_addr SMALLINT NOT NULL DEFAULT 0 ,
my_position INTEGER ,
my_position_timestamp TIMESTAMP ,
last_login TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
creation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
failed_login_timestamp TIMESTAMP ,
UNIQUE (account_id)
);

CREATE TABLE Coordinate (
coord_id  SERIAL NOT NULL ,
name VARCHAR(64) NOT NULL ,
description VARCHAR(256) ,
latitude DECIMAL(9,6) NOT NULL ,
longitude DECIMAL(9,6) NOT NULL ,
PRIMARY KEY (coord_id)
);

CREATE TABLE Challenge (
challenge_id  SERIAL NOT NULL ,
challenge_type_id INTEGER NOT NULL ,
owner INTEGER NOT NULL ,
sessionkey VARCHAR(8) ,
name VARCHAR(64) NOT NULL ,
description VARCHAR(512) NOT NULL ,
predefined_teams SMALLINT NOT NULL DEFAULT 0 ,
max_teams INTEGER NOT NULL DEFAULT 4 ,
max_team_members INTEGER NOT NULL DEFAULT 4 ,
start_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
end_time TIMESTAMP ,
is_public SMALLINT NOT NULL DEFAULT 0 ,
is_visible SMALLINT NOT NULL DEFAULT 0 ,
PRIMARY KEY (challenge_id),
UNIQUE (sessionkey)
);

CREATE TABLE ChallengeMember (
team_id INTEGER NOT NULL ,
account_id INTEGER NOT NULL ,
UNIQUE (team_id, account_id)
);

CREATE TABLE ChallengeCoord (
challenge_coord_id  SERIAL NOT NULL ,
challenge_id INTEGER NOT NULL ,
coord_id INTEGER NOT NULL ,
index INTEGER NOT NULL ,
code VARCHAR(16) ,
verify_user_pos SMALLINT(1) ,
captured_by INTEGER ,
capture_time TIMESTAMP ,
PRIMARY KEY (challenge_coord_id),
UNIQUE (challenge_id, coord_id)
);

CREATE TABLE Friends (
account_id INTEGER NOT NULL ,
friend_id INTEGER NOT NULL ,
UNIQUE (account_id, friend_id)
);

CREATE TABLE ChallengeTeam (
team_id  SERIAL NOT NULL ,
challenge_id INTEGER NOT NULL ,
name VARCHAR(64) NOT NULL ,
color VARCHAR(24) NOT NULL ,
max_members INTEGER NOT NULL DEFAULT -1 ,
immutable_teamname SMALLINT NOT NULL DEFAULT 0 ,
starttime TIMESTAMP ,
PRIMARY KEY (team_id),
UNIQUE (team_id, challenge_id)
);

CREATE TABLE ChallengeCheckpoint (
challenge_coord_id INTEGER NOT NULL ,
team_id INTEGER NOT NULL ,
time TIMESTAMP ,
UNIQUE (team_id, challenge_coord_id)
);

CREATE TABLE AccountType (
acc_type_id INTEGER NOT NULL ,
name VARCHAR(8) NOT NULL ,
PRIMARY KEY (acc_type_id)
);

CREATE TABLE ChallengeType (
challenge_type_id INTEGER NOT NULL ,
acronym VARCHAR(8) NOT NULL ,
full_name VARCHAR(32) NOT NULL ,
PRIMARY KEY (challenge_type_id)
);

CREATE TABLE GuestAccount (
next_number INTEGER NOT NULL 
);

ALTER TABLE Account ADD FOREIGN KEY (type) REFERENCES AccountType (acc_type_id);
ALTER TABLE Place ADD FOREIGN KEY (coord_id) REFERENCES Coordinate (coord_id);
ALTER TABLE Place ADD FOREIGN KEY (account_id) REFERENCES Account (account_id);
ALTER TABLE CurrentNavigation ADD FOREIGN KEY (account_id) REFERENCES Account (account_id);
ALTER TABLE CurrentNavigation ADD FOREIGN KEY (coord_id) REFERENCES Coordinate (coord_id);
ALTER TABLE AccountInformation ADD FOREIGN KEY (account_id) REFERENCES Account (account_id);
ALTER TABLE AccountInformation ADD FOREIGN KEY (my_position) REFERENCES Coordinate (coord_id);
ALTER TABLE Challenge ADD FOREIGN KEY (challenge_type_id) REFERENCES ChallengeType (challenge_type_id);
ALTER TABLE Challenge ADD FOREIGN KEY (owner) REFERENCES Account (account_id);
ALTER TABLE ChallengeMember ADD FOREIGN KEY (team_id) REFERENCES ChallengeTeam (team_id);
ALTER TABLE ChallengeMember ADD FOREIGN KEY (account_id) REFERENCES Account (account_id);
ALTER TABLE ChallengeCoord ADD FOREIGN KEY (challenge_id) REFERENCES Challenge (challenge_id);
ALTER TABLE ChallengeCoord ADD FOREIGN KEY (coord_id) REFERENCES Coordinate (coord_id);
ALTER TABLE ChallengeCoord ADD FOREIGN KEY (captured_by) REFERENCES ChallengeTeam (team_id);
ALTER TABLE Friends ADD FOREIGN KEY (account_id) REFERENCES Account (account_id);
ALTER TABLE Friends ADD FOREIGN KEY (friend_id) REFERENCES Account (account_id);
ALTER TABLE ChallengeTeam ADD FOREIGN KEY (challenge_id) REFERENCES Challenge (challenge_id);
ALTER TABLE ChallengeCheckpoint ADD FOREIGN KEY (challenge_coord_id) REFERENCES ChallengeCoord (challenge_coord_id);
ALTER TABLE ChallengeCheckpoint ADD FOREIGN KEY (team_id) REFERENCES ChallengeTeam (team_id);
