
#DROP TABLE _PREFIXTABLE_revues;
#DROP TABLE _PREFIXTABLE_users;
#DROP TABLE _PREFIXTABLE_session;

#ifndef LODELLIGHT
CREATE TABLE IF NOT EXISTS _PREFIXTABLE_revues (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	nom		VARCHAR(255) BINARY NOT NULL UNIQUE,
	soustitre	TINYTEXT,
	rep		VARCHAR(64) BINARY NOT NULL UNIQUE,
	langdef		CHAR(2) NOT NULL,
	lang		VARCHAR(64) NOT NULL,
	options		TEXT NOT NULL,
	meta		TEXT,

	status		TINYINT DEFAULT '1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_rep (rep)
);
#endif

CREATE TABLE IF NOT EXISTS _PREFIXTABLE_users (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	username	VARCHAR(64) BINARY NOT NULL UNIQUE,
	passwd		VARCHAR(64) BINARY NOT NULL,
	nom		VARCHAR(64),
	email		VARCHAR(255),
	privilege	TINYINT UNSIGNED DEFAULT '0' NOT NULL,

	status		TINYINT DEFAULT '1' NOT NULL,

	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_username (username)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_session (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	name		VARCHAR(64) BINARY NOT NULL UNIQUE,
	iduser		INT UNSIGNED DEFAULT '0' NOT NULL,
	revue		VARCHAR(64) BINARY NOT NULL,
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


# Administrateur par defaut. mot de passe : admintmp
INSERT INTO _PREFIXTABLE_users (username,passwd,nom,email,privilege) VALUES ('admintmp','f2a69cdb6e81c0cb25bd4fada535cccd','administrateur temporaire','',128);
