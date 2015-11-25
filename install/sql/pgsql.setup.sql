CREATE TABLE Account (
accountid  SERIAL NOT NULL ,
username VARCHAR(64) NOT NULL ,
email VARCHAR(128) NOT NULL ,
password VARCHAR(64) NOT NULL ,
PRIMARY KEY (accountid)
);

CREATE TABLE Place (
place_id  SERIAL NOT NULL ,
accountid INTEGER NOT NULL ,
name VARCHAR(32) NOT NULL ,
description MEDIUMTEXT ,
latitude DECIMAL NOT NULL ,
longitude DECIMAL NOT NULL ,
PRIMARY KEY (place_id)
);

ALTER TABLE Place ADD FOREIGN KEY (accountid) REFERENCES Account (accountid);

