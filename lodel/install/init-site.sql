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


#
# table commun a documents/publications
#


CREATE TABLE IF NOT EXISTS #_TP_objects (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	class		VARCHAR(64),

	PRIMARY KEY (id)
);


CREATE TABLE IF NOT EXISTS #_TP_entities (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	idparent	INT UNSIGNED DEFAULT '0' NOT NULL,
	idtype		INT UNSIGNED DEFAULT '0' NOT NULL,

	identifier	VARCHAR(255) NOT NULL, # internal name
	g_title		TINYTEXT NOT NULL,     # short title used in the interface

	usergroup	TINYINT UNSIGNED DEFAULT '1' NOT NULL,
	iduser		INT UNSIGNED DEFAULT '0' NOT NULL,

	creationdate		DATETIME,
	modificationdate	DATETIME,

	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '-1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_idparent (idparent),
	KEY index_idtype (idtype),
	KEY index_identifier (identifier)
);

#
# table contenant les relations entre les entitess
#

CREATE TABLE IF NOT EXISTS #_TP_relations (
	idrelation	INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	id1		INT UNSIGNED DEFAULT '0' NOT NULL,
	id2		INT UNSIGNED DEFAULT '0' NOT NULL,
	nature		CHAR(1) DEFAULT 'P' NOT NULL,

	degree		TINYINT DEFAULT '0' NOT NULL,

	PRIMARY KEY (idrelation),
	KEY index_id1 (id1),
	KEY index_id2 (id2),
	KEY index_degree (degree),
	KEY index_nature (nature)
);



##CREATE TABLE IF NOT EXISTS #_TP_publications (
##	identity	INT UNSIGNED DEFAULT '0' NOT NULL UNIQUE,
##	KEY index_identite (identity)
##);
##
##
##CREATE TABLE IF NOT EXISTS #_TP_documents (
##	identity	INT UNSIGNED DEFAULT '0' NOT NULL UNIQUE,
##	KEY index_identite (identity)
##);



CREATE TABLE IF NOT EXISTS #_TP_classes (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	class		VARCHAR(64) NOT NULL UNIQUE,
	title		TINYTEXT NOT NULL,

	classtype	VARCHAR(64) NOT NULL,
	comment		TEXT NOT NULL,			# commentaire sur la class

	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_class (class)
);




#
# table qui contient les champs supplementaires
#

CREATE TABLE IF NOT EXISTS #_TP_tablefields (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	name		VARCHAR(64) NOT NULL,		# name/identifiant unique
	idgroup		INT UNSIGNED DEFAULT '0' NOT NULL,
	class		VARCHAR(64) NOT NULL,   	# name de la table complementaire

	title		TINYTEXT NOT NULL,		# name en clair, utiliser dans l'interface

	style		TINYTEXT NOT NULL,		# style qui conduit a cette balises
	type		TINYTEXT NOT NULL,		# type du champ
	g_name		VARCHAR(255) NOT NULL,		# equivalent generic du champ
	condition	TINYTEXT NOT NULL,		# condition d'import
	defaultvalue	TINYTEXT NOT NULL,		# valeur par defaut
	processing	TINYTEXT NOT NULL,		# traitement a faire a l'import
	allowedtags 	TINYTEXT NOT NULL,		# balises acceptees
	filtering	TEXT NOT NULL,			# traitement a faire a l'exportation
	edition		TINYTEXT NOT NULL,		# input pour l'edition
	editionparams	TINYTEXT NOT NULL,		# input pour l'edition
	comment		TEXT NOT NULL,			# commentaire sur le champs

	status		TINYINT DEFAULT '1' NOT NULL,	# determine qui a les droits de le modifier
	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_name (name),
	KEY index_g_name (g_name),
	KEY index_idgroup (idgroup)
);


CREATE TABLE IF NOT EXISTS #_TP_tablefieldgroups (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	name		VARCHAR(64) NOT NULL,		# name/identifiant unique
	idclass		INT UNSIGNED DEFAULT '0' NOT NULL,   	# name de la table complementaire

	title		TINYTEXT NOT NULL,		# name en clair, utiliser dans l'interface
	comment		TEXT NOT NULL,			# commentaire sur le groupe de champs

	status		TINYINT DEFAULT '1' NOT NULL,	# determine qui a les droits de le modifier
	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_name (name),
	KEY index_idclass (idclass)
);




CREATE TABLE IF NOT EXISTS #_TP_persons (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	idtype		INT UNSIGNED DEFAULT '0' NOT NULL, # type de lien entre la personne et le entite

	g_familyname	TINYTEXT NOT NULL,
	g_firstname	TINYTEXT NOT NULL,
	sortkey		TINYTEXT NOT NULL,

	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_idtype (idtype)
);


CREATE TABLE IF NOT EXISTS #_TP_users (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	username	VARCHAR(64) BINARY NOT NULL UNIQUE,
	passwd		VARCHAR(64) BINARY NOT NULL,
	name		VARCHAR(64),
	email		VARCHAR(255),
	userrights	TINYINT UNSIGNED DEFAULT '0' NOT NULL,
	lang		CHAR(5) NOT NULL,       # user lang

	status		TINYINT DEFAULT '1' NOT NULL,

	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_username (username)
);

CREATE TABLE IF NOT EXISTS #_TP_usergroups (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	name		VARCHAR(64),

	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_name (name)
);


CREATE TABLE IF NOT EXISTS #_TP_users_usergroups (
	idgroup		INT UNSIGNED DEFAULT '0' NOT NULL,
	iduser		INT UNSIGNED DEFAULT '0' NOT NULL,

	KEY index_idgroup (idgroup),
	KEY index_iduser (iduser)
);


CREATE TABLE IF NOT EXISTS #_TP_types (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	type		VARCHAR(64) NOT NULL,
	title		TINYTEXT NOT NULL,

	class		VARCHAR(64) NOT NULL,   # name de la table complementaire

	tpl		TINYTEXT NOT NULL,			# name du fichier template utilise dans la zone de revue
	tplcreation	TINYTEXT NOT NULL,			# name du fichier template pour la creation, ou information decrivant la creation
	tpledition	TINYTEXT NOT NULL,			# name du fichier template pour l'edition de son contenu

	import		TINYINT DEFAULT '0' NOT NULL,		# 1=import par OO

	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_type (type)
);


CREATE TABLE IF NOT EXISTS #_TP_internalstyles (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	style		VARCHAR(255) NOT NULL,
	surrounding	VARCHAR(255) NOT NULL,
	conversion	VARCHAR(255) NOT NULL,

	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id)
);


CREATE TABLE IF NOT EXISTS #_TP_characterstyles (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	style		VARCHAR(255) NOT NULL,
	conversion	VARCHAR(255) NOT NULL,

	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id)
);


CREATE TABLE IF NOT EXISTS #_TP_persontypes (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	type		VARCHAR(64) NOT NULL UNIQUE,	# name/identifiant unique
	title		TINYTEXT NOT NULL,		# name en clair, utiliser dans l'interface
	style		TINYTEXT NOT NULL,		# style qui conduit a ce type

	g_type		VARCHAR(255) NOT NULL,		# equivalent generic du type


	tpl		TINYTEXT NOT NULL,			# name du fichier template pour l'entree
	tplindex	TINYTEXT NOT NULL,			# name du fichier template pour l'index

	rank		INT UNSIGNED DEFAULT '0' NOT NULL,	# rank sert pour l'interface.
	status		TINYINT DEFAULT '1' NOT NULL,


	upd		TIMESTAMP,
	PRIMARY KEY (id),
	KEY index_type (type),
	KEY index_g_type (g_type)
);


CREATE TABLE IF NOT EXISTS #_TP_entrytypes (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	type		VARCHAR(64) NOT NULL UNIQUE,	# name/identifiant unique
	class		VARCHAR(64) NOT NULL,   	# name de la table complementaire

	title		TINYTEXT NOT NULL,		# name en clair, utiliser dans l'interface
	style		TINYTEXT NOT NULL,		# style qui conduit a cette balises
	tpl		TINYTEXT NOT NULL,			# name du fichier template pour l'entree
	tplindex	TINYTEXT NOT NULL,			# name du fichier template pour l'index

	rank		INT UNSIGNED DEFAULT '0' NOT NULL,	# rank sert pour l'interface.
	status		TINYINT DEFAULT '1' NOT NULL,

	flat			TINYINT DEFAULT '0' NOT NULL,
	newbyimportallowed	TINYINT DEFAULT '0' NOT NULL,
	edition		TINYTEXT NOT NULL,		# input pour l'edition
	sort			VARCHAR(64) NOT NULL DEFAULT 'rank' NOT NULL, # 

	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_type (type)
);




CREATE TABLE IF NOT EXISTS #_TP_entries (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	idparent	INT UNSIGNED DEFAULT '0' NOT NULL,
	g_name	VARCHAR(255) NOT NULL,
	sortkey		TINYTEXT NOT NULL,
	lang		CHAR(2) NOT NULL,
	idtype		INT DEFAULT '0' NOT NULL,

	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_g_name (g_name),
	KEY index_idparent (idparent),
	KEY index_idtype (idtype)
);



CREATE TABLE IF NOT EXISTS #_TP_tasks (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	name		TINYTEXT NOT NULL,
	step		TINYINT NOT NULL DEFAULT '0',
	user		INT UNSIGNED DEFAULT '0' NOT NULL,
	context		TEXT,

	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id)
);


CREATE TABLE IF NOT EXISTS #_TP_texts (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	name		VARCHAR(255) NOT NULL,  # name
	contents		TEXT,                   # texte

	lang		CHAR(5) NOT NULL,       # text lang
	textgroup	VARCHAR(255) NOT NULL,   # text group

	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_name (name),
	KEY index_lang (lang),
	KEY index_textgroup (textgroup)
);



#CREATE TABLE IF NOT EXISTS #_TP_entities_persons (
#	idperson		INT UNSIGNED DEFAULT '0' NOT NULL,
#	identity		INT UNSIGNED DEFAULT '0' NOT NULL,
#	rank			TINYINT UNSIGNED NOT NULL DEFAULT '0',
#	idrelation		INT UNSIGNED DEFAULT '0' NOT NULL,
#
#	KEY index_idperson (idperson),
#	KEY index_identity (identity)
#);
#
#
## decrit le liens entre les entites et les entrees
#CREATE TABLE IF NOT EXISTS #_TP_entities_entries (
#	identry		INT UNSIGNED DEFAULT '0' NOT NULL,
#	identity	INT UNSIGNED DEFAULT '0' NOT NULL,
#
#	KEY index_identry (identry),
#	KEY index_identity (identity)
#);

# table qui decrit la possibilite de presence ou non de l'entite de type 1 dans l'entite de type 2.
CREATE TABLE IF NOT EXISTS #_TP_entitytypes_entitytypes (
	identitytype		INT UNSIGNED DEFAULT '0' NOT NULL, # contenu
	identitytype2		INT UNSIGNED DEFAULT '0' NOT NULL, # contenant
	condition		VARCHAR(16),

	KEY index_identitytype (identitytype),
	KEY index_identitytype2 (identitytype2)
);


## table qui decrit la presence ou non d'un type d'entree dans un type d'entite
#CREATE TABLE IF NOT EXISTS #_TP_entitytypes_entrytypes (
#	identitytype		INT UNSIGNED DEFAULT '0' NOT NULL,
#	identrytype		INT UNSIGNED DEFAULT '0' NOT NULL,
#	condition		VARCHAR(16),
#
#	KEY index_identrytype (identrytype),
#	KEY index_identitytype (identitytype)
#);
#
#
#CREATE TABLE IF NOT EXISTS #_TP_entitytypes_persontypes (
#	identitytype		INT UNSIGNED DEFAULT '0' NOT NULL,
#	idpersontype		INT UNSIGNED DEFAULT '0' NOT NULL,
#	condition		VARCHAR(16),
#
#	KEY index_idpersontype (idpersontype),
#	KEY index_identitytype (identitytype)
#);


CREATE TABLE IF NOT EXISTS #_TP_options (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	idgroup		INT UNSIGNED DEFAULT '0' NOT NULL,
	name		VARCHAR(255) NOT NULL,		# name/identifiant unique
	type		VARCHAR(255) NOT NULL,		# type du champ
	value		TEXT NOT NULL,	# value
	defaultvalue	TEXT NOT NULL,	# value
	comment		TEXT NOT NULL,			# commentaire sur le groupe de champs

	userrights	TINYINT UNSIGNED DEFAULT '0' NOT NULL,
	exportpolicy	TINYINT DEFAULT '1' NOT NULL,

	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_name (name),
	KEY index_idgroup (idgroup)
);


CREATE TABLE IF NOT EXISTS #_TP_optiongroups (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	name		VARCHAR(255) NOT NULL,		# name/identifiant unique
	title		VARCHAR(255) NOT NULL,		# type du champ
	comment		TEXT NOT NULL,			# commentaire sur le groupe de champs
	editscript	TINYTEXT NOT NULL,		# url to edit the group

	rank		INT UNSIGNED DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_name (name)
);


CREATE TABLE IF NOT EXISTS #_TP_translations (
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
