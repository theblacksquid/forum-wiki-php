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
	metadata	VARCHAR(1024) NOT NULL,
	INDEX(userName)
) ENGINE=INNODB;

DROP TABLE fwSecurity;
CREATE TABLE fwSecurity
(
	fwUserId	INTEGER PRIMARY KEY NOT NULL,
	authToken	VARCHAR(64) NOT NULL,
	failAttempts	TINYINT NOT NULL,
	lastUpdated	INTEGER NOT NULL,
	INDEX(authToken)
) ENGINE=INNODB;
