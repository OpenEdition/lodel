
#
# table commun a documents/publications
#

CREATE TABLE IF NOT EXISTS _PREFIXTABLE_entites (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	idparent	INT UNSIGNED DEFAULT '0' NOT NULL,
	idtype		INT UNSIGNED DEFAULT '0' NOT NULL,

	nom		VARCHAR(64) NOT NULL, # nom utilisé en interne

	groupe		TINYINT UNSIGNED DEFAULT '1' NOT NULL,
	iduser		INT UNSIGNED DEFAULT '0' NOT NULL,

	ordre		INT UNSIGNED DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '-1' NOT NULL,
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
	traitement	TINYTEXT NOT NULL,		# traitement a faire a l'import
	filtrage	TEXT NOT NULL,		# traitement a faire a l'exportation
	edition		TINYTEXT NOT NULL,		# input pour l'edition

	status		TINYINT DEFAULT '1' NOT NULL,	# determine qui a les droits de le modifier
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

	status		TINYINT DEFAULT '1' NOT NULL,	# determine qui a les droits de le modifier
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

	status		TINYINT DEFAULT '1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id)
);


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

CREATE TABLE IF NOT EXISTS _PREFIXTABLE_groupes (
	id		TINYINT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	nom		VARCHAR(64),

	status		TINYINT DEFAULT '1' NOT NULL,
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
	type		VARCHAR(64) NOT NULL UNIQUE,
	titre		TINYTEXT NOT NULL,

	classe		VARCHAR(64) NOT NULL,   # nom de la table complementaire

	tpl		TINYTEXT NOT NULL,			# nom du fichier template utilise dans la zone de revue
	tplcreation	TINYTEXT NOT NULL,			# nom du fichier template pour la creation, ou information decrivant la creation
	tpledit		TINYTEXT NOT NULL,			# nom du fichier template pour l'edition de son contenu

	ordre		INT UNSIGNED DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_type (type)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_typepersonnes (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	type		VARCHAR(64) NOT NULL UNIQUE,	# nom/identifiant unique
	titre		TINYTEXT NOT NULL,		# nom en clair, utiliser dans l'interface
	style		TINYTEXT NOT NULL,		# style qui conduit a cette balises
	tpl		TINYTEXT NOT NULL,			# nom du fichier template pour l'entree
	tplindex	TINYTEXT NOT NULL,			# nom du fichier template pour l'index

	ordre		INT UNSIGNED DEFAULT '0' NOT NULL,	# ordre sert pour l'interface.
	status		TINYINT DEFAULT '1' NOT NULL,


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
	status		TINYINT DEFAULT '1' NOT NULL,

# options
	lineaire	TINYINT DEFAULT '0' NOT NULL,
	newimportable	TINYINT DEFAULT '0' NOT NULL,
	useabrev	TINYINT DEFAULT '0' NOT NULL,
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
	lang		CHAR(2) NOT NULL,
	idtype		TINYINT DEFAULT '0' NOT NULL,
	ordre		INT UNSIGNED DEFAULT '0' NOT NULL,

	status		TINYINT DEFAULT '1' NOT NULL,
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

	status		TINYINT DEFAULT '1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_textes (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	nom		VARCHAR(255) NOT NULL,
	texte		TEXT,

	status		TINYINT DEFAULT '1' NOT NULL,
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



# table qui decrive la presence ou non d'un type d'entree dans un type d'entite
CREATE TABLE IF NOT EXISTS _PREFIXTABLE_typeentites_typeentrees (
	idtypeentree		INT UNSIGNED DEFAULT '0' NOT NULL,
	idtypeentite		INT UNSIGNED DEFAULT '0' NOT NULL,
	condition		VARCHAR(16),

	KEY index_idtypeentree (idtypeentree),
	KEY index_idtypeentite (idtypeentite)
);

# table qui decrive la presence ou non d'un type d'personne dans un type d'entite
CREATE TABLE IF NOT EXISTS _PREFIXTABLE_typeentites_typepersonnes (
	idtypepersonne		INT UNSIGNED DEFAULT '0' NOT NULL,
	idtypeentite		INT UNSIGNED DEFAULT '0' NOT NULL,
	condition		VARCHAR(16),

	KEY index_idtypepersonne (idtypepersonne),
	KEY index_idtypeentite (idtypeentite)
);



# voir le fichier inserts-revue.sql pour les inserts automatiques !
