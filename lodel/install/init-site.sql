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


#
# table commun a documents/publications
#

CREATE TABLE IF NOT EXISTS _PREFIXTABLE_entites (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	idparent	INT UNSIGNED DEFAULT '0' NOT NULL,
	idtype		INT UNSIGNED DEFAULT '0' NOT NULL,

	identifiant	VARCHAR(255) NOT NULL, # nom utilisé en interne

	groupe		TINYINT UNSIGNED DEFAULT '1' NOT NULL,
	iduser		INT UNSIGNED DEFAULT '0' NOT NULL,

	ordre		INT UNSIGNED DEFAULT '0' NOT NULL,
	statut		TINYINT DEFAULT '-1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_idparent (idparent),
	KEY index_idtype (idtype),
	KEY index_nom (nom)
);

#
# table contenant les relations entre les entitess
#

CREATE TABLE IF NOT EXISTS _PREFIXTABLE_relations (
	id1		INT UNSIGNED DEFAULT '0' NOT NULL,
	id2		INT UNSIGNED DEFAULT '0' NOT NULL,
	nature		CHAR(1) DEFAULT 'P' NOT NULL,

	degres		TINYINT DEFAULT '0' NOT NULL,

	KEY index_id1 (id1),
	KEY index_id2 (id2),
	KEY index_degres (degres),
	KEY index_nature (nature)
);



CREATE TABLE IF NOT EXISTS _PREFIXTABLE_publications (
	identite	INT UNSIGNED DEFAULT '0' NOT NULL UNIQUE,
	KEY index_identite (identite)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_documents (
	identite	INT UNSIGNED DEFAULT '0' NOT NULL UNIQUE,
	KEY index_identite (identite)
);




#
# table qui contient les champs supplementaires
#

CREATE TABLE IF NOT EXISTS _PREFIXTABLE_champs (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	nom		VARCHAR(64) NOT NULL,		# nom/identifiant unique
	idgroupe	INT UNSIGNED DEFAULT '0' NOT NULL,

	titre		TINYTEXT NOT NULL,		# nom en clair, utiliser dans l'interface

	style		TINYTEXT NOT NULL,		# style qui conduit a cette balises
	type		TINYTEXT NOT NULL,		# type du champ
	condition	TINYTEXT NOT NULL,		# condition
	defaut		TINYTEXT NOT NULL,		# valeur par defaut
	traitement	TINYTEXT NOT NULL,		# traitement a faire a l'import
	balises         TINYTEXT NOT NULL,		# balises acceptees
	filtrage	TEXT NOT NULL,			# traitement a faire a l'exportation
	edition		TINYTEXT NOT NULL,		# input pour l'edition
	commentaire	TEXT NOT NULL,			# commentaire sur le champs

	statut		TINYINT DEFAULT '1' NOT NULL,	# determine qui a les droits de le modifier
	ordre		INT UNSIGNED DEFAULT '0' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_nom (nom),
	KEY index_idgroupe (idgroupe)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_groupesdechamps (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	nom		VARCHAR(64) NOT NULL,		# nom/identifiant unique
	classe		VARCHAR(64) NOT NULL,   	# nom de la table complementaire

	titre		TINYTEXT NOT NULL,		# nom en clair, utiliser dans l'interface
	commentaire	TEXT NOT NULL,			# commentaire sur le groupe de champs

	statut		TINYINT DEFAULT '1' NOT NULL,	# determine qui a les droits de le modifier
	ordre		INT UNSIGNED DEFAULT '0' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_nom (nom),
	KEY index_classe (classe)
);




CREATE TABLE IF NOT EXISTS _PREFIXTABLE_personnes (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
#	prefix		TINYTEXT NOT NULL,
	nomfamille	TINYTEXT NOT NULL,
	prenom		TINYTEXT NOT NULL,
#	site		TEXT NOT NULL, #
#	bio		TEXT NOT NULL, # inutile pour le moment

	statut		TINYINT DEFAULT '1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id)
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

CREATE TABLE IF NOT EXISTS _PREFIXTABLE_groupes (
	id		TINYINT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	nom		VARCHAR(64),

	statut		TINYINT DEFAULT '1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_nom (nom)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_users_groupes (
	idgroupe	TINYINT UNSIGNED DEFAULT '0' NOT NULL,
	iduser		INT UNSIGNED DEFAULT '0' NOT NULL,

	KEY index_idgroupe (idgroupe),
	KEY index_iduser (iduser)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_types (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	type		VARCHAR(64) NOT NULL,
	titre		TINYTEXT NOT NULL,

	classe		VARCHAR(64) NOT NULL,   # nom de la table complementaire

	tpl		TINYTEXT NOT NULL,			# nom du fichier template utilise dans la zone de revue
	tplcreation	TINYTEXT NOT NULL,			# nom du fichier template pour la creation, ou information decrivant la creation
	tpledition	TINYTEXT NOT NULL,			# nom du fichier template pour l'edition de son contenu

	import		TINYINT DEFAULT '0' NOT NULL,		# 1=import par OO

	ordre		INT UNSIGNED DEFAULT '0' NOT NULL,
	statut		TINYINT DEFAULT '1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_type (type)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_typepersonnes (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	type		VARCHAR(64) NOT NULL UNIQUE,	# nom/identifiant unique
	titre		TINYTEXT NOT NULL,		# nom en clair, utiliser dans l'interface
	style		TINYTEXT NOT NULL,		# style qui conduit a ce type

	titredescription		TINYTEXT NOT NULL,		# affichage "description de la personne"
	styledescription		TINYTEXT NOT NULL,		# style qui conduit a la description de ce type.


	tpl		TINYTEXT NOT NULL,			# nom du fichier template pour l'entree
	tplindex	TINYTEXT NOT NULL,			# nom du fichier template pour l'index

	ordre		INT UNSIGNED DEFAULT '0' NOT NULL,	# ordre sert pour l'interface.
	statut		TINYINT DEFAULT '1' NOT NULL,


	maj		TIMESTAMP,
	PRIMARY KEY (id),
	KEY index_type (type)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_typeentrees (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	type		VARCHAR(64) NOT NULL UNIQUE,	# nom/identifiant unique
	titre		TINYTEXT NOT NULL,		# nom en clair, utiliser dans l'interface
	style		TINYTEXT NOT NULL,		# style qui conduit a cette balises
	tpl		TINYTEXT NOT NULL,			# nom du fichier template pour l'entree
	tplindex	TINYTEXT NOT NULL,			# nom du fichier template pour l'index

	ordre		INT UNSIGNED DEFAULT '0' NOT NULL,	# ordre sert pour l'interface.
	statut		TINYINT DEFAULT '1' NOT NULL,

# options
	lineaire	TINYINT DEFAULT '0' NOT NULL,
	nvimportable	TINYINT DEFAULT '0' NOT NULL,
	utiliseabrev	TINYINT DEFAULT '0' NOT NULL,
	tri		VARCHAR(64) NOT NULL DEFAULT 'ordre' NOT NULL, # 

	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_type (type)
);




CREATE TABLE IF NOT EXISTS _PREFIXTABLE_entrees (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	idparent	INT UNSIGNED DEFAULT '0' NOT NULL,
	nom		VARCHAR(255) NOT NULL,
	abrev		VARCHAR(15) NOT NULL,
	langue		CHAR(2) NOT NULL,
	idtype		TINYINT DEFAULT '0' NOT NULL,
	ordre		INT UNSIGNED DEFAULT '0' NOT NULL,

	statut		TINYINT DEFAULT '1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_nom (nom),
	KEY index_abrev (abrev),
	KEY index_idparent (idparent),
	KEY index_idtype (idtype)
);



CREATE TABLE IF NOT EXISTS _PREFIXTABLE_taches (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	nom		TINYTEXT NOT NULL,
	etape		TINYINT NOT NULL DEFAULT '0',
	user		INT UNSIGNED DEFAULT '0' NOT NULL,
	context		TEXT,

	statut		TINYINT DEFAULT '1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_textes (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	nom		VARCHAR(255) NOT NULL,
	texte		TEXT,

	statut		TINYINT DEFAULT '1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_nom (nom)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_entites_personnes (
	idpersonne		INT UNSIGNED DEFAULT '0' NOT NULL,
	identite		INT UNSIGNED DEFAULT '0' NOT NULL,
	idtype			INT UNSIGNED DEFAULT '0' NOT NULL, # type de lien entre la personne et le entite

	ordre			TINYINT UNSIGNED NOT NULL DEFAULT '0',
	prefix             	TINYTEXT NOT NULL,
	description             TEXT NOT NULL,
	fonction		TINYTEXT NOT NULL,
	affiliation		TINYTEXT NOT NULL,
	courriel		TINYTEXT NOT NULL,

	KEY index_idpersonne (idpersonne),
	KEY index_identite (identite),
	KEY index_idtype (idtype)
);


# decrit le liens entre les entites et les entrees
CREATE TABLE IF NOT EXISTS _PREFIXTABLE_entites_entrees (
	identree		INT UNSIGNED DEFAULT '0' NOT NULL,
	identite		INT UNSIGNED DEFAULT '0' NOT NULL,

	KEY index_identree (identree),
	KEY index_identite (identite)
);

# table qui decrit la possibilite de presence ou non de l'entite de type 1 dans l'entite de type 2.
CREATE TABLE IF NOT EXISTS _PREFIXTABLE_typeentites_typeentites (
	idtypeentite		INT UNSIGNED DEFAULT '0' NOT NULL, # contenu
	idtypeentite2		INT UNSIGNED DEFAULT '0' NOT NULL, # contenant
	condition		VARCHAR(16),

	KEY index_idtypeentite (idtypeentite),
	KEY index_idtypeentite2 (idtypeentite2)
);


# table qui decrit la presence ou non d'un type d'entree dans un type d'entite
CREATE TABLE IF NOT EXISTS _PREFIXTABLE_typeentites_typeentrees (
	idtypeentite		INT UNSIGNED DEFAULT '0' NOT NULL,
	idtypeentree		INT UNSIGNED DEFAULT '0' NOT NULL,
	condition		VARCHAR(16),

	KEY index_idtypeentree (idtypeentree),
	KEY index_idtypeentite (idtypeentite)
);

# table qui decrit la presence ou non d'un type d'personne dans un type d'entite
CREATE TABLE IF NOT EXISTS _PREFIXTABLE_typeentites_typepersonnes (
	idtypeentite		INT UNSIGNED DEFAULT '0' NOT NULL,
	idtypepersonne		INT UNSIGNED DEFAULT '0' NOT NULL,
	condition		VARCHAR(16),

	KEY index_idtypepersonne (idtypepersonne),
	KEY index_idtypeentite (idtypeentite)
);



# voir le fichier inserts-revue.sql pour les inserts automatiques !
