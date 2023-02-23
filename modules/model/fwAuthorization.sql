DROP DATABASE fwAuthorization;
CREATE DATABASE fwAuthorization;

USE fwAuthorization;

DROP TABLE fwUsers;
CREATE TABLE fwUsers
(
	fwUserId	INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
	userName        VARCHAR(32) NOT NULL,
	passwordHash	VARCHAR(32) NOT NULL,
	dateRegistered	INTEGER NOT NULL,
	INDEX(userName)
) ENGINE=INNODB;

