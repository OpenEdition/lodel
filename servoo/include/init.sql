#
#  LODEL - Logiciel d'Edition ELectronique.
#
#  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
#  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
#
#  Home page: http://www.lodel.org
#
#  E-Mail: lodel@lodel.org
#
#                            All Rights Reserved
#
#     This program is free software; you can redistribute it and/or modify
#     it under the terms of the GNU General Public License as published by
#     the Free Software Foundation; either version 2 of the License, or
#     (at your option) any later version.
#
#     This program is distributed in the hope that it will be useful,
#     but WITHOUT ANY WARRANTY; without even the implied warranty of
#     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#     GNU General Public License for more details.
#
#     You should have received a copy of the GNU General Public License
#     along with this program; if not, write to the Free Software
#     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.



CREATE TABLE IF NOT EXISTS users (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	username	VARCHAR(64) BINARY NOT NULL UNIQUE,
	passwd		VARCHAR(64) BINARY NOT NULL,
	url		TINYTEXT,

	realname	TINYTEXT,
	email		TINYTEXT,
	priority	TINYINT UNSIGNED DEFAULT '0' NOT NULL,

	status		TINYINT DEFAULT '1' NOT NULL,

	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_username (username)
);


CREATE TABLE IF NOT EXISTS log (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	iduser		INT UNSIGNED DEFAULT '0' NOT NULL,
	commands	TEXT,

	maj		TIMESTAMP,

	PRIMARY KEY (id)
);




# Administration of the ServOO

CREATE TABLE IF NOT EXISTS admins (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	name		VARCHAR(64) BINARY NOT NULL UNIQUE,
	passwd		VARCHAR(64) BINARY NOT NULL,

	realname	TINYTEXT,
	email		TINYTEXT,
	rights		TINYINT UNSIGNED DEFAULT '0' NOT NULL,

	status		TINYINT DEFAULT '1' NOT NULL,

	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_name (name)
);

CREATE TABLE IF NOT EXISTS session (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	name		VARCHAR(64) BINARY NOT NULL UNIQUE,
	idadmin		INT UNSIGNED DEFAULT '0' NOT NULL,

	context		TEXT,
	timeout		INT,  # temps d'expiration entre deux access
	timeout2	INT,  # expiration de cette session

	PRIMARY KEY (id),
	KEY index_name (name)
);



# Administrateur par defaut. mot de passe : admintmp

REPLACE INTO admins (name,passwd,realname,rights) VALUES ('admintmp','f2a69cdb6e81c0cb25bd4fada535cccd','administrateur temporaire',128);
