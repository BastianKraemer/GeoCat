CREATE TABLE Account (
account_id  SERIAL NOT NULL ,
username VARCHAR(64) NOT NULL ,
password VARCHAR(64) NOT NULL ,
salt VARCHAR(32) ,
email VARCHAR(64) NOT NULL ,
type VARCHAR(16) NOT NULL DEFAULT 'default' ,
is_administrator SMALLINT NOT NULL DEFAULT 0 ,
PRIMARY KEY (account_id),
UNIQUE (username, email)
);

CREATE TABLE Place (
coord_id INTEGER NOT NULL ,
account_id INTEGER NOT NULL ,
is_public SMALLINT NOT NULL DEFAULT 0 ,
creation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
modification_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
PRIMARY KEY (coord_id, account_id)
);

CREATE TABLE CurrentNavigation (
account_id INTEGER NOT NULL ,
coord_id INTEGER NOT NULL ,
PRIMARY KEY (account_id, coord_id)
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
PRIMARY KEY (account_id)
);

CREATE TABLE Coordinate (
coord_id  SERIAL NOT NULL ,
name VARCHAR(64) NOT NULL ,
description VARCHAR(256) ,
latitude DECIMAL NOT NULL ,
logitude DECIMAL NOT NULL ,
PRIMARY KEY (coord_id)
);

CREATE TABLE Challenge (
challenge_id  SERIAL NOT NULL ,
owner INTEGER NOT NULL ,
sessionkey VARCHAR(8) ,
name VARCHAR(64) NOT NULL ,
description VARCHAR(512) NOT NULL ,
predefined_teams SMALLINT NOT NULL DEFAULT 0 ,
max_teams INTEGER NOT NULL DEFAULT 4 ,
starttime TIMESTAMP NOT NULL ,
is_public SMALLINT NOT NULL DEFAULT 0 ,
is_visible SMALLINT NOT NULL DEFAULT 0 ,
PRIMARY KEY (challenge_id)
);

CREATE TABLE ChallengeMember (
team_id INTEGER NOT NULL ,
account_id INTEGER NOT NULL ,
PRIMARY KEY (team_id, account_id)
);

CREATE TABLE ChallengeCoords (
challenge_id INTEGER NOT NULL ,
coord_id INTEGER NOT NULL ,
code VARCHAR(16) ,
can_be_captured SMALLINT NOT NULL DEFAULT 0 ,
captured_by INTEGER ,
captured_time TIMESTAMP ,
PRIMARY KEY (challenge_id, coord_id)
);

CREATE TABLE Friends (
account_id INTEGER NOT NULL ,
friend_id INTEGER NOT NULL ,
PRIMARY KEY (account_id, friend_id)
);

CREATE TABLE ChallengeTeam (
team_id  SERIAL NOT NULL ,
challenge_id INTEGER NOT NULL ,
name VARCHAR(64) NOT NULL ,
color VARCHAR(24) NOT NULL ,
max_members INTEGER NOT NULL DEFAULT -1 ,
immutable_teamname SMALLINT NOT NULL DEFAULT 0 ,
PRIMARY KEY (team_id, challenge_id)
);

CREATE TABLE ChallengeTime (
team_id  SERIAL ,
coord_id INTEGER NOT NULL ,
time TIMESTAMP ,
PRIMARY KEY (team_id, coord_id)
);

ALTER TABLE Place ADD FOREIGN KEY (coord_id) REFERENCES Coordinate (coord_id);
ALTER TABLE Place ADD FOREIGN KEY (account_id) REFERENCES Account (account_id);
ALTER TABLE CurrentNavigation ADD FOREIGN KEY (account_id) REFERENCES Account (account_id);
ALTER TABLE CurrentNavigation ADD FOREIGN KEY (coord_id) REFERENCES Coordinate (coord_id);
ALTER TABLE AccountInformation ADD FOREIGN KEY (account_id) REFERENCES Account (account_id);
ALTER TABLE AccountInformation ADD FOREIGN KEY (my_position) REFERENCES Coordinate (coord_id);
ALTER TABLE Challenge ADD FOREIGN KEY (owner) REFERENCES Account (account_id);
ALTER TABLE ChallengeMember ADD FOREIGN KEY (team_id) REFERENCES ChallengeTeam (team_id);
ALTER TABLE ChallengeMember ADD FOREIGN KEY (account_id) REFERENCES Account (account_id);
ALTER TABLE ChallengeCoords ADD FOREIGN KEY (challenge_id) REFERENCES Challenge (challenge_id);
ALTER TABLE ChallengeCoords ADD FOREIGN KEY (coord_id) REFERENCES Coordinate (coord_id);
ALTER TABLE ChallengeCoords ADD FOREIGN KEY (captured_by) REFERENCES ChallengeTeam (team_id);
ALTER TABLE Friends ADD FOREIGN KEY (account_id) REFERENCES Account (account_id);
ALTER TABLE Friends ADD FOREIGN KEY (friend_id) REFERENCES Account (account_id);
ALTER TABLE ChallengeTeam ADD FOREIGN KEY (challenge_id) REFERENCES Challenge (challenge_id);
ALTER TABLE ChallengeTime ADD FOREIGN KEY (team_id) REFERENCES ChallengeTeam (team_id);
ALTER TABLE ChallengeTime ADD FOREIGN KEY (coord_id) REFERENCES Coordinate (coord_id);
