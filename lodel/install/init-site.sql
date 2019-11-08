# LODEL - Logiciel d Édition ÉLectronique.
# @license    GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
# @authors    See COPYRIGHT file

#
# table commun a documents/publications
#


CREATE TABLE IF NOT EXISTS #_TP_objects (
	id		INT UNSIGNED NOT NULL auto_increment,
	class		VARCHAR(64),

	PRIMARY KEY (id)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_TP_entities (
	id		INT UNSIGNED NOT NULL auto_increment,
	idparent	INT UNSIGNED DEFAULT '0' NOT NULL,
	idtype		INT UNSIGNED DEFAULT '0' NOT NULL,

	identifier	VARCHAR(255) NOT NULL, # internal name
	g_title		TINYTEXT NOT NULL,     # short title used in the interface

	usergroup	TINYINT UNSIGNED DEFAULT '1' NOT NULL,
	iduser		INT UNSIGNED DEFAULT '0' NOT NULL,

	creationdate		DATETIME,
	modificationdate	DATETIME,

	creationmethod	VARCHAR(16),
	creationinfo	TINYTEXT,

	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '-1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_idparent (idparent),
	KEY index_idtype (idtype),
	KEY index_identifier (identifier)
) _CHARSET_;

#
# table contenant les relations entre les entitess
#

CREATE TABLE IF NOT EXISTS #_TP_relations (
	idrelation	INT UNSIGNED NOT NULL auto_increment,
	id1		INT UNSIGNED DEFAULT '0' NOT NULL,
	id2		INT UNSIGNED DEFAULT '0' NOT NULL,
	nature		VARCHAR(32) DEFAULT 'P' NOT NULL,

	degree		TINYINT, # can be null

	PRIMARY KEY (idrelation),
	UNIQUE (id1,id2,degree,nature),
	KEY index_id1 (id1),
	KEY index_id2 (id2),
	KEY index_nature (nature)
) _CHARSET_;



##CREATE TABLE IF NOT EXISTS #_TP_publications (
##	identity	INT UNSIGNED DEFAULT '0' NOT NULL UNIQUE,
##	KEY index_identite (identity)
##) _CHARSET_;
##
##
##CREATE TABLE IF NOT EXISTS #_TP_documents (
##	identity	INT UNSIGNED DEFAULT '0' NOT NULL UNIQUE,
##	KEY index_identite (identity)
##) _CHARSET_;



CREATE TABLE IF NOT EXISTS #_TP_classes (
	id		INT UNSIGNED NOT NULL auto_increment,
	icon VARCHAR(255) NOT NULL DEFAULT '',
	class		VARCHAR(64) NOT NULL UNIQUE,
	title		TINYTEXT NOT NULL,
	altertitle TEXT NOT NULL DEFAULT '',
	classtype	VARCHAR(64) NOT NULL,
	comment		TEXT NOT NULL,			# commentaire sur la class

	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_class (class)
) _CHARSET_;




#
# table qui contient les champs supplementaires
#

CREATE TABLE IF NOT EXISTS #_TP_tablefields (
	id		INT UNSIGNED NOT NULL auto_increment,
	name		VARCHAR(32) NOT NULL,		# name/identifiant unique
	idgroup		INT UNSIGNED DEFAULT '0' NOT NULL,
	class		VARCHAR(64) NOT NULL,   	# name de la table complementaire

	title		TINYTEXT NOT NULL,		# name en clair, utiliser dans l'interface
	altertitle	TEXT NOT NULL DEFAULT '',		# titre multilingue
	style		TINYTEXT NOT NULL,		# style qui conduit a cette balises
	type		TINYTEXT NOT NULL,		# type du champ
	g_name		VARCHAR(255) NOT NULL,		# equivalent generic du champ
	cond		VARCHAR(16) NOT NULL,		# condition d'import
	defaultvalue	TINYTEXT NOT NULL,		# valeur par defaut
	processing	TINYTEXT NOT NULL,		# traitement a faire a l'import
	allowedtags 	TINYTEXT NOT NULL,		# balises acceptees
	gui_user_complexity			TINYINT UNSIGNED DEFAULT '64' NOT NULL,
	filtering	TEXT NOT NULL,			# traitement a faire a l'exportation
	edition		TINYTEXT NOT NULL,		# input pour l'edition
	editionparams	TINYTEXT NOT NULL,		# input pour l'edition
	editionhooks   TEXT NOT NULL,      # hooks pour l'edition
	weight		TINYINT NOT NULL,
	comment		TEXT NOT NULL,			# commentaire sur le champs
	mask 		TEXT NOT NULL DEFAULT '',	# masque � appliquer sur le champs pour validation
	status		TINYINT DEFAULT '1' NOT NULL,	# determine qui a les droits de le modifier
	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	otx tinytext NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_name (name),
	KEY index_g_name (g_name),
	KEY index_idgroup (idgroup),
	KEY index_class (class)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_TP_tablefieldgroups (
	id		INT UNSIGNED NOT NULL auto_increment,
	name		VARCHAR(64) NOT NULL,		# name/identifiant unique
	class		VARCHAR(64) NOT NULL,   	# name de la table complementaire

	title		TINYTEXT NOT NULL,		# name en clair, utiliser dans l'interface
	altertitle	TEXT NOT NULL DEFAULT '',		# titre multilingue
	comment		TEXT NOT NULL,			# commentaire sur le groupe de champs

	status		TINYINT DEFAULT '1' NOT NULL,	# determine qui a les droits de le modifier
	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_name (name),
	KEY index_class (class)
) _CHARSET_;




CREATE TABLE IF NOT EXISTS #_TP_persons (
	id		INT UNSIGNED NOT NULL auto_increment,
	idtype		INT UNSIGNED DEFAULT '0' NOT NULL, # type de lien entre la personne et le entite

	g_familyname	TINYTEXT NOT NULL,
	g_firstname	TINYTEXT NOT NULL,
	sortkey		VARCHAR(255) NOT NULL,

	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_idtype (idtype)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_TP_users (
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

CREATE TABLE IF NOT EXISTS #_TP_usergroups (
	id		INT UNSIGNED NOT NULL auto_increment,
	name		VARCHAR(64),

	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_name (name)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_TP_users_usergroups (
	idgroup		INT UNSIGNED DEFAULT '0' NOT NULL,
	iduser		INT UNSIGNED DEFAULT '0' NOT NULL,

	KEY index_idgroup (idgroup),
	KEY index_iduser (iduser)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_TP_types (
	id		INT UNSIGNED NOT NULL auto_increment,
	icon VARCHAR(255) NOT NULL DEFAULT '',
	type		VARCHAR(64) NOT NULL,
	title		TINYTEXT NOT NULL,
	altertitle	TEXT NOT NULL DEFAULT '',		# titre multilingue
	class		VARCHAR(64) NOT NULL,   # name de la table complementaire

	tpl		TINYTEXT NOT NULL,			# name du fichier template utilise dans la zone de revue
	tplcreation	TINYTEXT NOT NULL,			# name du fichier template pour la creation, ou information decrivant la creation
	tpledition	TINYTEXT NOT NULL,			# name du fichier template pour l'edition de son contenu

	import		TINYINT DEFAULT '0' NOT NULL,		# 1=import par OO
	display		VARCHAR(10),				# where/how to display this type
	creationstatus	TINYINT DEFAULT '-1' NOT NULL,		# status for the new entities created with this type
	search		TINYINT DEFAULT '1' NOT NULL,
	public		TINYINT DEFAULT '0' NOT NULL,
	gui_user_complexity			TINYINT UNSIGNED DEFAULT '64' NOT NULL,
	oaireferenced	TINYINT DEFAULT '0' NOT NULL,

	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_type (type)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_TP_internalstyles (
	id		INT UNSIGNED NOT NULL auto_increment,
	style		VARCHAR(255) NOT NULL,
	surrounding	VARCHAR(255) NOT NULL,
	conversion	VARCHAR(255) NOT NULL,
	greedy		TINYINT DEFAULT '1' NOT NULL,

	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,
	otx tinytext NOT NULL,
	PRIMARY KEY (id)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_TP_characterstyles (
	id		INT UNSIGNED NOT NULL auto_increment,
	style		VARCHAR(255) NOT NULL,
	conversion	VARCHAR(255) NOT NULL,

	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,
	otx tinytext NOT NULL,
	PRIMARY KEY (id)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_TP_persontypes (
	id		INT UNSIGNED NOT NULL auto_increment,
	icon VARCHAR(255) NOT NULL DEFAULT '',
	type		VARCHAR(64) NOT NULL UNIQUE,	# name/identifiant unique
	title		TINYTEXT NOT NULL,		# name en clair, utiliser dans l'interface
	altertitle	TEXT NOT NULL DEFAULT '',		# titre multilingue
	class		VARCHAR(64) NOT NULL,   	# name de la table complementaire

	style		TINYTEXT NOT NULL,		# style qui conduit a ce type
	g_type		VARCHAR(255) NOT NULL,		# equivalent generic du type


	tpl		TINYTEXT NOT NULL,			# name du fichier template pour l'entree
	tplindex	TINYTEXT NOT NULL,			# name du fichier template pour l'index
	gui_user_complexity			TINYINT UNSIGNED DEFAULT '64' NOT NULL,
	rank		INT UNSIGNED DEFAULT '0' NOT NULL,	# rank sert pour l'interface.
	status		TINYINT DEFAULT '1' NOT NULL,

	otx tinytext NOT NULL,
	upd		TIMESTAMP,
	PRIMARY KEY (id),
	KEY index_type (type),
	KEY index_g_type (g_type)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_TP_entrytypes (
	id		INT UNSIGNED NOT NULL auto_increment,
	icon VARCHAR(255) NOT NULL DEFAULT '',
	type		VARCHAR(64) NOT NULL UNIQUE,	# name/identifiant unique
	class		VARCHAR(64) NOT NULL,   	# name de la table complementaire

	title		TINYTEXT NOT NULL,		# name en clair, utiliser dans l'interface
	altertitle	TEXT NOT NULL DEFAULT '',		# titre multilingue
	style		TINYTEXT NOT NULL,		# style qui conduit a cette balises
	g_type		VARCHAR(255) NOT NULL,		# equivalent generic du type

	tpl		TINYTEXT NOT NULL,			# name du fichier template pour l'entree
	tplindex	TINYTEXT NOT NULL,			# name du fichier template pour l'index
	gui_user_complexity			TINYINT UNSIGNED DEFAULT '64' NOT NULL,
	rank		INT UNSIGNED DEFAULT '0' NOT NULL,	# rank sert pour l'interface.
	status		TINYINT DEFAULT '1' NOT NULL,

	flat			TINYINT DEFAULT '0' NOT NULL,
	newbyimportallowed	TINYINT DEFAULT '0' NOT NULL,
	edition		TINYTEXT NOT NULL,		# input pour l'edition
	sort			VARCHAR(64) NOT NULL DEFAULT 'rank' NOT NULL, # 
	otx tinytext NOT NULL,
	upd		TIMESTAMP,
	lang		VARCHAR(10) NOT NULL DEFAULT 'fr',
	externalallowed tinyint(4) NOT NULL default '0',

	PRIMARY KEY (id),
	KEY index_type (type)
) _CHARSET_;




CREATE TABLE IF NOT EXISTS #_TP_entries (
	id		INT UNSIGNED NOT NULL auto_increment,
	idparent	INT UNSIGNED DEFAULT '0' NOT NULL,
	g_name		VARCHAR(255) NOT NULL,
	sortkey		TINYTEXT NOT NULL,
	idtype		INT DEFAULT '0' NOT NULL,

	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_g_name (g_name),
	KEY index_idparent (idparent),
	KEY index_idtype (idtype)
) _CHARSET_;



CREATE TABLE IF NOT EXISTS #_TP_tasks (
	id		INT UNSIGNED NOT NULL auto_increment,
	name		TINYTEXT NOT NULL,
	step		TINYINT NOT NULL DEFAULT '0',
	user		INT UNSIGNED DEFAULT '0' NOT NULL,
	context		LONGTEXT,

	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_TP_texts (
	id		INT UNSIGNED NOT NULL auto_increment,
	name		VARCHAR(128) NOT NULL,  # name
	contents	TEXT,                   # texte

	lang		CHAR(5) NOT NULL,       # text lang
	textgroup	VARCHAR(64) NOT NULL,   # text group

	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_name (name),
	KEY index_lang (lang),
	KEY index_textgroup (textgroup),
	UNIQUE unique_text (name,textgroup,lang)
) _CHARSET_;



# table qui decrit la possibilite de presence ou non de l'entite de type 1 dans l'entite de type 2.
CREATE TABLE IF NOT EXISTS #_TP_entitytypes_entitytypes (
	identitytype		INT UNSIGNED DEFAULT '0' NOT NULL, # contenu
	identitytype2		INT UNSIGNED DEFAULT '0' NOT NULL, # contenant
	cond		VARCHAR(16),

	KEY index_identitytype (identitytype),
	KEY index_identitytype2 (identitytype2)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_TP_options (
	id		INT UNSIGNED NOT NULL auto_increment,
	idgroup		INT UNSIGNED DEFAULT '0' NOT NULL,
	name		VARCHAR(128) NOT NULL,		# name/identifiant unique
	title		TINYTEXT NOT NULL,		# title
	altertitle	TEXT NOT NULL,		# titre multilingue
	type		VARCHAR(128) NOT NULL,		# type du champ
	edition		TINYTEXT NOT NULL,		# input pour l'edition
	editionparams	TINYTEXT NOT NULL,		# input pour l'edition

	value		TEXT NOT NULL,	# value
	defaultvalue	TEXT NOT NULL,	# value
	comment		TEXT NOT NULL,			# commentaire sur le groupe de champs

	userrights	TINYINT UNSIGNED DEFAULT '0' NOT NULL,

	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_name (name),
	KEY index_idgroup (idgroup),
	UNIQUE unique_name (name,idgroup)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_TP_optiongroups (
	id		INT UNSIGNED NOT NULL auto_increment,
	idparent	INT UNSIGNED NOT NULL, # parent optiongroup id
	name		VARCHAR(255) NOT NULL,		# name/identifiant unique
	title		VARCHAR(255) NOT NULL,		# type du champ
	altertitle	TEXT NOT NULL,		# titre multilingue
	comment		TEXT NOT NULL,			# commentaire sur le groupe de champs
	logic		TINYTEXT NOT NULL,		# url to edit the group
	exportpolicy	TINYINT DEFAULT '1' NOT NULL,

	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_name (name),
	KEY index_idparent (idparent)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_TP_translations (
	id			INT UNSIGNED NOT NULL auto_increment,
	lang			CHAR(5) NOT NULL,		# code of the lang
	title			TINYTEXT,
	textgroups		VARCHAR(128),

	translators		TEXT,
	modificationdate	DATE,
	creationdate		DATE,

	rank			INT UNSIGNED DEFAULT '0' NOT NULL,
	status			TINYINT DEFAULT '1' NOT NULL,
	upd			TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_lang (lang),
	UNIQUE unique_lang_groups (lang,textgroups)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_TP_search_engine (
	identity		INT UNSIGNED NOT NULL DEFAULT '0',
	tablefield 		VARCHAR(32) NOT NULL DEFAULT '',
	word			VARCHAR(30) NOT NULL DEFAULT '',
	weight 			DOUBLE NOT NULL DEFAULT '0',

	KEY index_word (word),
	KEY index_identity (identity)
) _CHARSET_;

CREATE TABLE IF NOT EXISTS #_TP_oaitokens (
  token varchar(14) NOT NULL default '',
  query text NOT NULL,
  metadataprefix varchar(35) NOT NULL default '',
  deliveredrecords int(11) NOT NULL default '0',
  expirationdatetime timestamp NOT NULL,
  UNIQUE KEY token (token)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_TP_oailogs (
  id int(11) NOT NULL auto_increment,
  host tinytext NOT NULL,
  date timestamp NOT NULL,
  denied tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id)
) _CHARSET_;

CREATE TABLE IF NOT EXISTS #_TP_restricted_users (
  id int(10) unsigned NOT NULL auto_increment,
  username varchar(64) NOT NULL,
  passwd varchar(64) NOT NULL,
  lastname varchar(255) default NULL,
  firstname varchar(255) default NULL,
  email varchar(255) default NULL,
  lang char(5) NOT NULL,
  userrights tinyint(3) unsigned NOT NULL default '5',
  ip longtext NOT NULL,
  rank int(10) unsigned NOT NULL default '0',
  status tinyint(4) NOT NULL default '1',
  expiration date default NULL,
  upd timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `index_username` (`username`)
) _CHARSET_;

CREATE TABLE IF NOT EXISTS #_TP_plugins (
  `id` int(10) unsigned NOT NULL default '0',
  `name` varchar(64) character set utf8 collate utf8_bin NOT NULL,
  `upd` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `status` tinyint(4) NOT NULL default '0',
  `config` longtext NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) _CHARSET_;

CREATE TABLE IF NOT EXISTS #_TP_relations_ext (
  idrelation int(10) unsigned NOT NULL auto_increment,
  id1 int(10) unsigned NOT NULL default '0',
  id2 int(10) unsigned NOT NULL default '0',
  nature varchar(32) NOT NULL default 'E',
  degree tinyint(4) default NULL,
  site varchar(64) NOT NULL,
  PRIMARY KEY  (idrelation),
  UNIQUE KEY id1 (id1,id2,degree,nature,site),
  KEY index_id1 (id1),
  KEY index_id2 (id2),
  KEY index_nature (nature),
  KEY index_site (site)
) _CHARSET_;

CREATE TABLE IF NOT EXISTS #_TP_history (
  id int(10) unsigned NOT NULL auto_increment,
  nature varchar(32) NOT NULL,
  context text,
  upd timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (id)
) _CHARSET_;
