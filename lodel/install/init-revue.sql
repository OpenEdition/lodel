

#DROP TABLE _PREFIXTABLE_publications;
#DROP TABLE _PREFIXTABLE_typepublis;
#DROP TABLE _PREFIXTABLE_indexhs;
#DROP TABLE _PREFIXTABLE_indexls;
#DROP TABLE _PREFIXTABLE_taches;
#DROP TABLE _PREFIXTABLE_auteurs;
#DROP TABLE _PREFIXTABLE_documents;
#DROP TABLE _PREFIXTABLE_documentsannexes;
#DROP TABLE typearts;
#DROP TABLE textes;


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

CREATE TABLE IF NOT EXISTS _PREFIXTABLE_auteurs (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	prefix		TINYTEXT NOT NULL,
	nomfamille	TINYTEXT NOT NULL,
	prenom		TINYTEXT NOT NULL,
	fonction	TINYTEXT NOT NULL,
	affiliation	TINYTEXT NOT NULL,
	courriel	VARCHAR(255),
	site		TEXT NOT NULL,
	bio		TEXT NOT NULL,

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


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_typeindexs (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	nom		VARCHAR(64) NOT NULL UNIQUE,	# nom/identifiant unique
	titre		TINYTEXT NOT NULL,		# nom en clair, utiliser dans l'interface
	balise		TINYTEXT NOT NULL,		# nom en clair, utiliser dans l'interface
	tpl		TINYTEXT NOT NULL,			# nom du fichier template pour ce type de document

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



#CREATE TABLE IF NOT EXISTS _PREFIXTABLE_indexls (
#	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
#	mot		VARCHAR(128) NOT NULL,
#	lang		CHAR(2) NOT NULL,
#	type		TINYINT DEFAULT '0' NOT NULL,
#	ordre		INT DEFAULT '0' NOT NULL,
#
#	status		TINYINT DEFAULT '1' NOT NULL,
#	maj		TIMESTAMP,
#
#	PRIMARY KEY (id),
#	KEY index_mot (mot)
#);

CREATE TABLE IF NOT EXISTS _PREFIXTABLE_indexs (
	id		INT UNSIGNED DEFAULT '0' NOT NULL auto_increment,
	parent		INT UNSIGNED DEFAULT '0' NOT NULL,
	nom		VARCHAR(255) NOT NULL,
	abrev		VARCHAR(15) NOT NULL,
	lang		CHAR(2) NOT NULL,
	type		TINYINT DEFAULT '0' NOT NULL,
	ordre		INT DEFAULT '0' NOT NULL,

	status		TINYINT DEFAULT '1' NOT NULL,
	maj		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_nom (nom),
	KEY index_abrev (abrev),
	KEY index_parent (parent)
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


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_documents_auteurs (
	idauteur		INT UNSIGNED DEFAULT '0' NOT NULL,
	iddocument		INT UNSIGNED DEFAULT '0' NOT NULL,

	ordre			TINYINT NOT NULL DEFAULT '0',
	prefix             	VARCHAR(64) NOT NULL,
	description             TEXT NOT NULL,

	KEY index_idauteur (idauteur),
	KEY index_iddocument (iddocument)
);


CREATE TABLE IF NOT EXISTS _PREFIXTABLE_documents_indexs (
	idindex			INT UNSIGNED DEFAULT '0' NOT NULL,
	iddocument		INT UNSIGNED DEFAULT '0' NOT NULL,

	KEY index_idindex (idindex),
	KEY index_iddocument (iddocument)
);


#CREATE TABLE IF NOT EXISTS _PREFIXTABLE_documents_indexls (
#	idindexl		INT UNSIGNED DEFAULT '0' NOT NULL,
#	iddocument		INT UNSIGNED DEFAULT '0' NOT NULL,
#
#	KEY index_idindexl (idindexl),
#	KEY index_iddocument (iddocument)
#);

#ifndef LODELLIGHT
REPLACE INTO _PREFIXTABLE_typepublis (nom,tpl,tpledit) VALUES('serie_lineaire','sommaire-lineaire','edition-lineaire');
REPLACE INTO _PREFIXTABLE_typepublis (nom,tpl,tpledit) VALUES('serie_hierarchique','sommaire-hierarchique','edition-hierarchique');
REPLACE INTO _PREFIXTABLE_typepublis (nom,tpl,tpledit) VALUES('numero','sommaire-numero','edition-numero');
REPLACE INTO _PREFIXTABLE_typepublis (nom,tpl,tpledit) VALUES('theme','sommaire-hierarchique','edition-theme');
REPLACE INTO _PREFIXTABLE_typepublis (nom,tpl,tpledit) VALUES('regroupement','','');
REPLACE INTO _PREFIXTABLE_typedocs (nom,tpl,status) VALUES('article','article','1');
REPLACE INTO _PREFIXTABLE_groupes (id,nom) VALUES('1','tous');
REPLACE INTO _PREFIXTABLE_typeindexs (id,nom,titre,balise,tpl,status,lineaire,newimportable,useabrev,tri,ordre) VALUES('1','periode','période','periode','chrono','1','0','0','1','ordre','2');
REPLACE INTO _PREFIXTABLE_typeindexs (id,nom,titre,balise,tpl,status,lineaire,newimportable,useabrev,tri,ordre) VALUES('4','geographie','géographie','geographie','geo','1','0','0','1','ordre','3');
REPLACE INTO _PREFIXTABLE_typeindexs (id,nom,titre,balise,tpl,status,lineaire,newimportable,useabrev,tri,ordre) VALUES('2','motcle','mot clé','motcle','mot','1','1','1','0','nom','1');

#else
#REPLACE INTO _PREFIXTABLE_typepublis (nom,tpl,tpledit) VALUES('album_photo','sommaire-album','edition-album');
#REPLACE INTO _PREFIXTABLE_typepublis (nom,tpl,tpledit) VALUES('theme_photo','sommaire-photo','edition-photo');
#REPLACE INTO _PREFIXTABLE_typepublis (nom,tpl,tpledit) VALUES('rubrique','sommaire-rubrique','edition-rubrique');
#REPLACE INTO _PREFIXTABLE_typedocs (nom,tpl,status) VALUES('article','article','1');
#REPLACE INTO _PREFIXTABLE_typedocs (nom,tpl,status) VALUES('photo','photo','1');
#REPLACE INTO _PREFIXTABLE_groupes (id,nom) VALUES('1','tous');
#endif
