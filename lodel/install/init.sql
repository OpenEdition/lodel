#
#  LODEL - Logiciel d'Edition ELectronique.
#
#  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
#  Copyright (c) 2003-2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
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
	nom		VARCHAR(255) NOT NULL,
	soustitre	TINYTEXT,
	rep		VARCHAR(64) NOT NULL,
	chemin		VARCHAR(64) NOT NULL, ## va remplacer rep et rep devenir site (ou nom et nom devenir titre).
	url		TINYTEXT NOT NULL,

	langdef		CHAR(2) NOT NULL,
	lang		VARCHAR(64) NOT NULL,

	statut		TINYINT DEFAULT '1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_rep (rep)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_users (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	username	VARCHAR(64) BINARY NOT NULL UNIQUE,
	passwd		VARCHAR(64) BINARY NOT NULL,
	nom		VARCHAR(64),
	courriel	VARCHAR(255),
	privilege	TINYINT UNSIGNED DEFAULT '0' NOT NULL,

	statut		TINYINT DEFAULT '1' NOT NULL,

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


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_pileurl (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment, # faudrait generer le probleme du overflow
	idsession	INT UNSIGNED DEFAULT '0' NOT NULL,

	url		CHAR(32) BINARY NOT NULL, # cle md5 de l'url en cours
	urlretour	MEDIUMBLOB NOT NULL, # url de retour de l'url en cours

	PRIMARY KEY (id),
	KEY index_idsession (idsession),
	KEY index_url (url)
);


# suppression de l'administrateur par defaut... c'est geré par l'interface d'installation.
# Administrateur par defaut. mot de passe : admintmp
#REPLACE INTO _PREFIXTABLE_users (username,passwd,nom,courriel,privilege) VALUES ('admintmp','f2a69cdb6e81c0cb25bd4fada535cccd','administrateur temporaire','',128);
