#
#  LODEL - Logiciel d'Edition ELectronique.
#
#  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
#  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
#  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
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



CREATE TABLE IF NOT EXISTS _PREFIXTABLE_sites (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	title		VARCHAR(255) NOT NULL,
	subtitle	TINYTEXT,
	name		VARCHAR(64) NOT NULL,
	path		VARCHAR(64) NOT NULL,
	url		TINYTEXT NOT NULL,

	langdef		CHAR(2) NOT NULL,
	lang		VARCHAR(64) NOT NULL,

	status		TINYINT DEFAULT '1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_name (name)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_users (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	username	VARCHAR(64) BINARY NOT NULL UNIQUE,
	passwd		VARCHAR(64) BINARY NOT NULL,
	name		VARCHAR(64),
	email		VARCHAR(255),
	userrights	TINYINT UNSIGNED DEFAULT '0' NOT NULL,
	lang		CHAR(5) NOT NULL,       # text lang

	status		TINYINT DEFAULT '1' NOT NULL,

	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_username (username)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_session (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	name		VARCHAR(64) BINARY NOT NULL UNIQUE,
	iduser		INT UNSIGNED DEFAULT '0' NOT NULL,
	site		VARCHAR(64) BINARY NOT NULL,
	currenturl	MEDIUMBLOB,

	context		TEXT,
	expire		INT,  # temps d'expiration entre deux access
	expire2		INT,  # expiration de cette session

	PRIMARY KEY (id),
	KEY index_name (name)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_urlstack (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment, # faudrait generer le probleme du overflow
	idsession	INT UNSIGNED DEFAULT '0' NOT NULL,
	url		MEDIUMBLOB NOT NULL, # url de retour de l'url en cours

	PRIMARY KEY (id),
	KEY index_idsession (idsession)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_texts (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	name		VARCHAR(255) NOT NULL,  # name
	contents	TEXT,                   # texte

	lang		CHAR(5) NOT NULL,       # text lang
	textgroup	VARCHAR(255) NOT NULL,   # text group

	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_name (name),
	KEY index_lang (lang),
	KEY index_textgroup (textgroup)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_translations (
	id			INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	lang			CHAR(5) NOT NULL,		# code of the lang
	title			TINYTEXT,
	textgroups		VARCHAR(255),

	translators		TEXT,
	modificationdate	DATE,
	creationdate		DATE,

	rank			INT UNSIGNED DEFAULT '0' NOT NULL,
	status			TINYINT DEFAULT '1' NOT NULL,
	upd			TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_lang (lang)
);



# suppression de l'administrateur par defaut... c'est geré par l'interface d'installation.
# Administrateur par defaut. mot de passe : admintmp
#REPLACE INTO _PREFIXTABLE_users (username,passwd,nom,courriel,privilege) VALUES ('admintmp','f2a69cdb6e81c0cb25bd4fada535cccd','administrateur temporaire','',128);
