# LODEL - Logiciel d Édition ÉLectronique.
# @license    GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
# @authors    See COPYRIGHT file

CREATE TABLE IF NOT EXISTS #_MTP_sites (
	id		INT UNSIGNED NOT NULL auto_increment,
	title		VARCHAR(255) NOT NULL,
	subtitle	TINYTEXT,
	name		VARCHAR(64) NOT NULL,
	path		VARCHAR(64) NOT NULL,
	url		TINYTEXT NOT NULL,

	langdef		CHAR(2) NOT NULL DEFAULT '',
	lang		VARCHAR(64) NOT NULL DEFAULT '',

	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_name (name)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_MTP_users (
	id												INT UNSIGNED NOT NULL auto_increment,
	username									VARCHAR(64) BINARY NOT NULL UNIQUE,
	passwd										VARCHAR(64) BINARY NOT NULL,
	lastname									VARCHAR(255),
	firstname 								VARCHAR(255),
	email											VARCHAR(255),

	lang											CHAR(5) NOT NULL,       # user lang
	userrights								TINYINT UNSIGNED DEFAULT '0' NOT NULL,
	gui_user_complexity									TINYINT UNSIGNED DEFAULT '64' NOT NULL,

	nickname									VARCHAR(64),
	function									VARCHAR(255),
	biography									MEDIUMTEXT,
	photo											VARCHAR(255),
	professional_website			VARCHAR(255),
	url_professional_website			VARCHAR(255),
	rss_professional_website	VARCHAR(255),
	personal_website					VARCHAR(255),
	url_personal_website					VARCHAR(255),
	rss_personal_website			VARCHAR(255),
	pgp_key										MEDIUMTEXT,
	alternate_email						VARCHAR(255),
	phonenumber								VARCHAR(15),
	im_identifier							VARCHAR(100),
	im_name										VARCHAR(64),

	rank											INT UNSIGNED DEFAULT '0' NOT NULL,
	status										TINYINT DEFAULT '1' NOT NULL,
	upd												TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_username (username)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_MTP_session (
	id		INT UNSIGNED NOT NULL auto_increment,
	name		VARCHAR(64) BINARY NOT NULL UNIQUE,
	iduser		INT UNSIGNED DEFAULT '0' NOT NULL,
	site		VARCHAR(64) BINARY NOT NULL,
	currenturl	MEDIUMBLOB,
	userrights	tinyint(3) unsigned NOT NULL default '0',
	context		TEXT,
	expire		INT,  # temps d'expiration entre deux access
	expire2		INT,  # expiration de cette session

	PRIMARY KEY (id),
	KEY index_name (name)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_MTP_urlstack (
	id		INT UNSIGNED NOT NULL auto_increment, # faudrait generer le probleme du overflow
	idsession	INT UNSIGNED DEFAULT '0' NOT NULL,
	url		MEDIUMBLOB NOT NULL, # url de retour de l'url en cours
	site		VARCHAR(64) BINARY NOT NULL,
	PRIMARY KEY (id),
	KEY index_idsession (idsession)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_MTP_texts (
	id		INT UNSIGNED NOT NULL auto_increment,
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
) _CHARSET_;

CREATE TABLE IF NOT EXISTS #_MTP_internal_messaging (
  `id` int(10) unsigned NOT NULL auto_increment,
  `idparent` int(10) unsigned NOT NULL,
  `iduser` varchar(255) default NULL,
  `recipient` longtext NOT NULL,
  `recipients` longtext NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` longtext NOT NULL,
  `incom_date` datetime NOT NULL,
  `cond` tinyint(1) NOT NULL default '0',
  `status` tinyint(4) NOT NULL default '0',
  `upd` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) _CHARSET_;

CREATE TABLE IF NOT EXISTS #_MTP_translations (
	id			INT UNSIGNED NOT NULL auto_increment,
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
) _CHARSET_;

CREATE TABLE IF NOT EXISTS #_MTP_mainplugins (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(64) character set utf8 collate utf8_bin NOT NULL,
  `upd` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `status` tinyint(4) NOT NULL default '0',
  `trigger_preedit` tinyint(1) NOT NULL default '0',
  `trigger_postedit` tinyint(1) NOT NULL default '0',
  `trigger_prelogin` tinyint(1) NOT NULL default '0',
  `trigger_postlogin` tinyint(1) NOT NULL default '0',
  `trigger_preauth` tinyint(1) NOT NULL default '0',
  `trigger_postauth` tinyint(1) NOT NULL default '0',
  `trigger_preview` tinyint(1) NOT NULL default '0',
  `trigger_postview` tinyint(1) NOT NULL default '0',
  `config` longtext NOT NULL,
  `hooktype` varchar(5) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) _CHARSET_;

