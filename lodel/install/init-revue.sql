


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_publications (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	parent		INT UNSIGNED DEFAULT '0' NOT NULL,
	nom		VARCHAR(255) NOT NULL,
	titre		TINYTEXT,
	soustitre	TEXT,
	directeur	TINYTEXT,
	texte		TEXT,
	meta		TEXT,

	ordre		TINYINT DEFAULT '0' NOT NULL,
	date		DATE,
	type		VARCHAR(64) NOT NULL,
	groupe		TINYINT UNSIGNED DEFAULT '1' NOT NULL,

	status		TINYINT DEFAULT '1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_nom (nom),
	KEY index_parent (parent),
	KEY index_type (type)
);

#ifndef LODELLIGHT

CREATE TABLE IF NOT EXISTS _PREFIXTABLE_documents (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,

	surtitre	TEXT NOT NULL,
	titre		TEXT NOT NULL,
	soustitre	TEXT NOT NULL,
	intro		TEXT NOT NULL,
	langresume	VARCHAR(64) NOT NULL, # langues des resumes
	lang		VARCHAR(64) NOT NULL, # langues dans le texte
	meta		TEXT,
	datepubli	DATE, # date de publication du texte

	publication	INT UNSIGNED DEFAULT '0' NOT NULL, # id du publication
	ordre		TINYINT DEFAULT '0' NOT NULL,
	type		VARCHAR(64) NOT NULL,
	user		INT UNSIGNED DEFAULT '0' NOT NULL,
	groupe		TINYINT UNSIGNED DEFAULT '1' NOT NULL,

	status		TINYINT DEFAULT '-1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_publication (publication),
	KEY index_type (type)
);

#else
#
#CREATE TABLE IF NOT EXISTS _PREFIXTABLE_documents (
#	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
#
#	titre		MEDIUMTEXT NOT NULL,
#	soustitre	MEDIUMTEXT NOT NULL,
#	texte		TEXT NOT NULL,
#	textetype	VARCHAR(4),
#	image		TINYTEXT NOT NULL,
#	meta		MEDIUMTEXT,
#	datepubli	DATE,
#
#	publication	INT UNSIGNED DEFAULT '0' NOT NULL, # id du publication
#	ordre		TINYINT DEFAULT '0' NOT NULL,
#	type		VARCHAR(64) NOT NULL,
#	user		INT UNSIGNED DEFAULT '0' NOT NULL,
#	groupe		TINYINT UNSIGNED DEFAULT '1' NOT NULL,
#
#	status		TINYINT DEFAULT '-1' NOT NULL,
#	maj		TIMESTAMP,
#
#	PRIMARY KEY (id),
#	KEY index_publication (publication),
#	KEY index_type (type)
#);
#
#endif


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_documentsannexes (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	iddocument	INT UNSIGNED DEFAULT '0' NOT NULL,

	titre		TINYTEXT NOT NULL,
	commentaire	TEXT NOT NULL,
	lien		TINYTEXT NOT NULL,

	type		VARCHAR(64) NOT NULL,
	ordre		TINYINT DEFAULT '0' NOT NULL,
	status		TINYINT DEFAULT '1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_iddocument (iddocument)
);

#ifndef LODELLIGHT

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



CREATE TABLE IF NOT EXISTS _PREFIXTABLE_typedocs (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	nom		VARCHAR(64) NOT NULL UNIQUE,
	tpl		TINYTEXT NOT NULL,			# nom du fichier template pour ce type de document

	status		TINYINT DEFAULT '1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_nom (nom)
);

CREATE TABLE IF NOT EXISTS _PREFIXTABLE_typepublis (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	nom		VARCHAR(64) NOT NULL UNIQUE,
	tpl		TINYTEXT NOT NULL,			# nom du fichier template pour le sommaire de type de publication
	tpledit		TINYTEXT NOT NULL,			# nom du fichier template pour l'edition 

	status		TINYINT DEFAULT '1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_nom (nom)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_typepersonnes (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	nom		VARCHAR(64) NOT NULL UNIQUE,	# nom/identifiant unique
	titre		TINYTEXT NOT NULL,		# nom en clair, utiliser dans l'interface
	style		TINYTEXT NOT NULL,		# style qui conduit a cette balises
	tpl		TINYTEXT NOT NULL,			# nom du fichier template pour l'entree
	tplindex	TINYTEXT NOT NULL,			# nom du fichier template pour l'index

	ordre		INT DEFAULT '0' NOT NULL,	# ordre sert pour l'interface.
	status		TINYINT DEFAULT '1' NOT NULL,


	maj		TIMESTAMP,
	PRIMARY KEY (id),
	KEY index_nom (nom)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_typeentrees (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	nom		VARCHAR(64) NOT NULL UNIQUE,	# nom/identifiant unique
	titre		TINYTEXT NOT NULL,		# nom en clair, utiliser dans l'interface
	style		TINYTEXT NOT NULL,		# style qui conduit a cette balises
	tpl		TINYTEXT NOT NULL,			# nom du fichier template pour l'entree
	tplindex	TINYTEXT NOT NULL,			# nom du fichier template pour l'index

	ordre		INT DEFAULT '0' NOT NULL,	# ordre sert pour l'interface.
	status		TINYINT DEFAULT '1' NOT NULL,

# options
	lineaire	TINYINT DEFAULT '0' NOT NULL,
	newimportable	TINYINT DEFAULT '0' NOT NULL,
	useabrev	TINYINT DEFAULT '0' NOT NULL,
	tri		VARCHAR(64) NOT NULL DEFAULT 'ordre' NOT NULL, # 

	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_nom (nom)
);




CREATE TABLE IF NOT EXISTS _PREFIXTABLE_entrees (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	parent		INT UNSIGNED DEFAULT '0' NOT NULL,
	nom		VARCHAR(255) NOT NULL,
	abrev		VARCHAR(15) NOT NULL,
	lang		CHAR(2) NOT NULL,
	idtype		TINYINT DEFAULT '0' NOT NULL,
	ordre		INT DEFAULT '0' NOT NULL,

	status		TINYINT DEFAULT '1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_nom (nom),
	KEY index_abrev (abrev),
	KEY index_parent (parent),
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


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_documents_personnes (
	idpersonne		INT UNSIGNED DEFAULT '0' NOT NULL,
	iddocument		INT UNSIGNED DEFAULT '0' NOT NULL,
	idtype			INT UNSIGNED DEFAULT '0' NOT NULL, # type de lien entre la personne et le document

	ordre			TINYINT NOT NULL DEFAULT '0',
	prefix             	TINYTEXT NOT NULL,
	description             TEXT NOT NULL,
	fonction		TINYTEXT NOT NULL,
	affiliation		TINYTEXT NOT NULL,
	courriel		TINYTEXT NOT NULL,

	KEY index_idpersonne (idpersonne),
	KEY index_iddocument (iddocument),
	KEY index_idtype (idtype)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_documents_entrees (
	identree		INT UNSIGNED DEFAULT '0' NOT NULL,
	iddocument		INT UNSIGNED DEFAULT '0' NOT NULL,

	KEY index_identree (identree),
	KEY index_iddocument (iddocument)
);



# voir le fichier inserts-revue.sql pour les inserts automatiques !