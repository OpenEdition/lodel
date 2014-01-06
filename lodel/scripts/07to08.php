<?php
/**	
 * Script modifiant la base d'un site en 0.7 pour qu'elle puisse être utilisée par un site en 0.8
 *
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Home page: http://www.lodel.org
 * E-Mail: lodel@lodel.org
 *
 * All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @author Sophie Malafosse
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 */
require_once 'func.php';

class exportfor08
{
	/**
	 * Toutes les requêtes SQL effectuées
	 * @var string
	 */
	public $requetes;

	/**
	 * Nom du champ par défaut utilisé comme référence par le ME pour les entités (g_title)
	 * @var string
	 */
	public $defaultgTitle;

	/**
	 * Nom du champ par défaut utilisé comme référence par le ME (g_name)
	 * @var string
	 */
	public $defaultgName;

	/**
	 * Variable récupérant les éventuelles erreurs MySQL
	 * @var string
	 */
	public $mysql_errors;

	/**
	 * Équivalences entre tables de la 0.7 et de la 0.8 : tableau utilisé pour transfert des données vers les tables 0.8
	 * TABLE_08::
	 *	champ_1, champ_n,
	 * TABLE_07::
	 * 	champ_1, champ_n,
	 * @var array
	 */
	private $translations = array(
		'OBJECTS::
			id,class'=>
		'OBJETS::
			id,classe',

		'ENTITIES::
			id,idparent,idtype,identifier,usergroup,iduser,rank,status,upd'=>
		'ENTITES::
			id,idparent,idtype,identifiant,groupe,iduser,ordre,statut,maj',

		'RELATIONS::
			id1,id2,nature,degree'=>
		'RELATIONS::
			id1,id2,nature,degres',

		/* 
		TABLES PUBLICATIONS et DOCUMENTS en 0.7 : à recopier en 0.8 
		TABLE CLASSES en 0.8 : à remplir avec les classes (entités, index, index de personnes)
		*/

		'TABLEFIELDS::
			id,name,idgroup,title,style,type,cond,defaultvalue,processing,allowedtags,filtering,edition,comment,status,rank,upd'=>
		'CHAMPS::
			id,nom,idgroupe,titre,style,type,`condition`,defaut,traitement,balises,filtrage,edition,commentaire,statut,ordre,maj',

		'TABLEFIELDGROUPS::
			id,name,class,title,comment,status,rank,upd'=>
		'GROUPESDECHAMPS::
			id,nom,classe,titre,commentaire,statut,ordre,maj',

		'AUTEURS::
			idperson,nomfamille, prenom'=>
		'PERSONNES::
			id,nomfamille,prenom',

		'PERSONS::
			id,g_familyname,g_firstname,status,upd'=>
		'PERSONNES::
			id,nomfamille,prenom,statut,maj',

		'USERS::
			id,username,passwd,lastname,email,userrights,status,upd'=>
		'USERS::
			id,username,passwd,nom,courriel,privilege,statut,maj',
			// + champ expiration, en 0.7 seulement

		'USERGROUPS::
			id,name,status,upd'=>
		'GROUPES::
			id,nom,statut,maj',

		'USERS_USERGROUPS::
			idgroup,iduser'=>
		'USERS_GROUPES::
			idgroupe,iduser',
			
		'TYPES::
			id,type,title,class,tpl,tplcreation,tpledition,import,rank,status,upd'=>
		'TYPES::
			id,type,titre,classe,tpl,tplcreation,tpledition,import,ordre,statut,maj',

		/* 
		TABLES INTERNALSTYLES et CHARACTERSTYLES : en 0.8 seulement, créées par initdb()
		--> rien à faire
		*/

		'PERSONTYPES::
			id,type,title,style,tpl,tplindex,rank,status,upd'=>
		'TYPEPERSONNES::
			id,type,titre,style,tpl,tplindex,ordre,statut,maj',
			// champs présents en 0.7 seulement : titredescription,styledescription

		'ENTRYTYPES::
			id,type,title,style,tpl,tplindex,rank,status,flat,newbyimportallowed,sort,upd'=>
		'TYPEENTREES::
			id,type,titre,style,tpl,tplindex,ordre,statut,lineaire,nvimportable,tri,maj',
			// champs présents en 0.7 seulement : utiliseabrev

		'ENTRIES::
			id,idparent,g_name,idtype,rank,status,upd'=>
		'ENTREES::
			id,idparent,nom,idtype,ordre,statut,maj',
			// champs présents en 0.7 seulement : abrev, lang

		'TASKS::
			id,name,step,user,context,status,upd'=>
		'TACHES::
			id,nom,etape,user,context,statut,maj',

		'TEXTS::
			id,name,contents,status,upd'=>
		'TEXTES::
			id,nom,texte,statut,maj',

		/*
		TABLES À SUPPRIMER : en 0.8, données à transférer dans la table RELATIONS
		'entities_entries::
			identry,identity'=> 
		'ENTITES_ENTREES::
			identree,identite',

		'entities_persons::
			idperson,identity,idtype,rank,prefix,description,function,affiliation,email'=>
		'ENTITES_PERSONNES::
			idpersonne,identite,idtype,ordre,prefix,description,fonction,affiliation,courriel',
		*/

		'ENTITYTYPES_ENTITYTYPES::
			identitytype,identitytype2,cond'=>
		'TYPEENTITES_TYPEENTITES::
			idtypeentite,idtypeentite2,`condition`',

		/*
		TABLES À SUPPRIMER : en 0.8, données à transférer dans la table entitytypes_entitytypes ???)
		'entitytypes_entrytypes::
			identitytype,identrytype,cond'=>
		'TYPEENTITES_TYPEENTREES::
			idtypeentite,idtypeentree,condition',

		'entitytypes_persontypes::
			identitytype,idpersontype,condition'=>
		'TYPEENTITES_TYPEPERSONNES::
			idtypeentite,idtypepersonne,cond',
		*/

		'OPTIONS::
			id,name,type,value,rank,status,upd'=>
		'OPTIONS::
			id,nom,type,valeur,ordre,statut,maj',

		/* 
		TABLES OPTIONGROUPS TRANSLATIONS SEARCH_ENGINE OAITOKENS OAILOGS : en 0.8 seulement, créées par initdb()
		--> rien à faire
		*/	

		/*
		TABLES globales Lodel : à faire ici ??
		'translations::
			id,lang,title,textgroups,translators,modificationdate,creationdate,rank,status,upd'=>
		'TRANSLATIONS::
			'id,lang,titre,textgroups,translators,modificationdate,creationdate,ordre,statut,maj'*/
		);

	/**
	 * Constructeur
	 */
	function __construct($defaultgTitle = 'titre', $defaultgName = 'dc.title') {
		$this->old_tables = $this->get_tables();
		$this->defaultgTitle = $defaultgTitle;
		$this->defaultgName = $defaultgName;
	}

	/**
	 *  Récupère la liste des tables dans la base du site
	 *
	 * @return array
	 */

	private function get_tables() {
		$result=mysql_list_tables($GLOBALS['currentdb']);
		$tables=array();
		while (list($table) = mysql_fetch_row($result)) {
				$tables[] = $table;
		}
		if (!empty($tables)) {
			return $tables;
		} else {
			return 'Pas de tables à traiter';
		}
	}

	/**
	 * Renomme les tables de la 0.7 : ajoute le suffixe __old au nom de la table
	 * Crée ensuite les tables de la 0.8, d'après le fichier init-site.sql
	 *
	 * @return string Ok
	 * @todo Ne renommer que les tables de la 0.7
	 */
	public function init_db() {
		if (!in_array($GLOBALS['tp'] . 'objets__old', $this->old_tables) && is_readable(SITEROOT . 'init-site.sql')) {
	
			// sauvegarde des tables en 0.7 : renommées en $table . __old
			$query = '';
			foreach ($this->old_tables as $table07) {
				if (substr($table07, -5) != '__old') {
					$query .= "RENAME TABLE $table07 TO $table07" . "__old;\n";
				}
			}
			if ($err = $this->__mysql_query_cmds($query)) {
				return $err;
			}
			
			// import de la structure des tables en 0.8
			$query = '';
			$sqlfile = SITEROOT . 'init-site.sql';
			$query = join('', file($sqlfile));
			// nettoyage du fichier : on enleve les commentaires
			$query = trim(preg_replace("`(^#.*$)`m", "", $query));

			if ($err = $this->__mysql_query_cmds($query)) {
				return $err;
			}
	
			// créations tables qui dépendent du ME en 0.8 (et qui sont en dur en 0.7)
			// ENTITÉS : publications, documents
			$query = "CREATE TABLE IF NOT EXISTS _PREFIXTABLE_publications AS SELECT * FROM _PREFIXTABLE_publications__old;\n
				ALTER TABLE _PREFIXTABLE_publications CHANGE identite identity INT UNSIGNED DEFAULT '0' NOT NULL UNIQUE;\n
				CREATE TABLE IF NOT EXISTS _PREFIXTABLE_documents AS SELECT * FROM _PREFIXTABLE_documents__old;\n
				ALTER TABLE _PREFIXTABLE_documents CHANGE identite identity INT UNSIGNED DEFAULT '0' NOT NULL UNIQUE;\n
				ALTER TABLE _PREFIXTABLE_documents ADD alterfichier tinytext;\n";
	
			// INDEX : indexes
			$query .= "CREATE TABLE IF NOT EXISTS _PREFIXTABLE_indexes (
					identry int(10) unsigned default NULL,
					nom text,
					definition text,
					UNIQUE KEY identry (identry),
					KEY index_identry (identry)
					) _CHARSET_;\n";
	
			// INDEX DE PERSONNES : auteurs et entities_auteurs
			$query .= "CREATE TABLE IF NOT EXISTS _PREFIXTABLE_auteurs(
					idperson int(10) unsigned default NULL,
					nomfamille tinytext,
					prenom tinytext,
					UNIQUE KEY idperson (idperson),
					KEY index_idperson (idperson)
					) _CHARSET_;\n";
	
			$query .= "CREATE TABLE IF NOT EXISTS _PREFIXTABLE_entities_auteurs(
					idrelation int(10) unsigned default NULL,
					prefix tinytext,
					affiliation tinytext,
					fonction tinytext,
					description text,
					courriel text,
					role text,
					site text,
					UNIQUE KEY idrelation (idrelation),
					KEY index_idrelation (idrelation)
					) _CHARSET_;\n";
			// Fichiers
			$query .= "CREATE TABLE IF NOT EXISTS _PREFIXTABLE_fichiers (
					identity int(10) unsigned default NULL,
					titre text,
					document tinytext,
					description text,
					legende text,
					credits tinytext,
					vignette tinytext,
					UNIQUE KEY identity (identity),
					KEY index_identity (identity)
					) _CHARSET_;\n";
			// Liens
			$query .= "CREATE TABLE IF NOT EXISTS _PREFIXTABLE_liens (
				identity int(10) unsigned default NULL,
				titre text,
				url text,
				urlfil text,
				texte text,
				capturedecran tinytext,
				nombremaxitems int(11) default NULL,
				UNIQUE KEY identity (identity),
				KEY index_identity (identity)
				) _CHARSET_;\n";
	
			if ($err = $this->__mysql_query_cmds($query)) {
				return $err;
			} else {
				return "Ok";
			}
		} else {
			return 'Erreur : soit votre base ne contient pas de tables Lodel, soit le fichier init-site.sql n\'est pas pr&eacute;sent dans le r&eacute;pertoire racine de votre site, soit vous avez deja executer le script de migration.';
		}
	}


	/**
	 *  Copie les données de la 0.7 dans les tables de la 0.8
	 *
	 * @return Ok si la copie est ok
	 */
	public function cp_07_to_08() {
		
		if(!$result = mysql_query('select * from ' . $GLOBALS['tp'] . 'objects')) {
			return mysql_error();
		}
		$num_rows = mysql_num_rows($result);
		if ($num_rows == 0) {

			foreach ($this->translations as $new => $old) {
				list($newtable, $newfields) = explode("::", strtolower($new));
				list($oldtable, $oldfields) = explode("::", strtolower($old));
				$oldfields = trim($oldfields);
				$newfields = trim($newfields);
				$oldtable .= '__old';
				
				if ($err = $this->__mysql_query_cmds("INSERT IGNORE INTO _PREFIXTABLE_$newtable ($newfields) SELECT $oldfields FROM _PREFIXTABLE_$oldtable;\n")) {
					return $err;
				}
			}
		}

		// on crée le sortkey permettant l'indexation des personnes
		if(!$req = mysql_query("SELECT id, g_familyname, g_firstname FROM ".$GLOBALS['tp']."persons;")) {
			return mysql_error();
		}
		while($res = mysql_fetch_array($req)) {
			$q .= "UPDATE _PREFIXTABLE_persons SET sortkey = \"".strtolower(utf8_decode($res['g_familyname'])." ".utf8_decode($res['g_firstname']))."\" WHERE id = '".$res['id']."';\n";
		}
		if (!empty($q) && $err = $this->__mysql_query_cmds($q)) {
			return $err;
		}
		return "Ok";
	}

	/**
	 * Création des classes : en dur en 0.7 (publications, documents, index et index de personnes)
	 * En mou en 0.8, donc à insérer dans les tables objects et classes
	 * 
	 * @return Ok si insertions dans les tables OK
	 */

	public function create_classes() {
		// ENTITÉS : publications et documents
		$id = $this->__insert_object('classes');
		if(!is_int($id))
			return $id;
		$query = "INSERT IGNORE INTO `_PREFIXTABLE_classes` (`id` , `icon` , `class` , `title` , `altertitle` , `classtype` , `comment` , `rank` , `status` , `upd` )
		VALUES ($id , 'lodel/icons/collection.gif', 'publications', 'Publications', '', 'entities', '', '1', '1', NOW( ));\n
		UPDATE _PREFIXTABLE_objects SET class='entities' WHERE class='publications';\n
		";
	
		$id = $this->__insert_object('classes');
		$query .= "INSERT IGNORE INTO `_PREFIXTABLE_classes` ( `id` , `icon` , `class` , `title` , `altertitle` , `classtype` , `comment` , `rank` , `status` , `upd` )
		VALUES ($id , 'lodel/icons/texte.gif', 'documents', 'Documents', '', 'entities', '', '2', '1', NOW( ));\n
		UPDATE _PREFIXTABLE_objects SET class='entities' WHERE class='documents';\n
		";

		// INDEX
		$id = $this->__insert_object('classes');
		$query .= "INSERT IGNORE INTO `_PREFIXTABLE_classes` ( `id` , `icon` , `class` , `title` , `altertitle` , `classtype` , `comment` , `rank` , `status` , `upd` )
		VALUES ($id , 'lodel/icons/index.gif', 'indexes', 'Index', '', 'entries', '', '3', '1', NOW( ));\n
		UPDATE _PREFIXTABLE_objects SET class='entries' WHERE class='entrees';\n
		UPDATE _PREFIXTABLE_objects SET class='entrytypes' WHERE class='typeentrees';\n
		";

		// INDEX DE PERSONNES
		$id = $this->__insert_object('classes');
		$query .= "INSERT IGNORE INTO `_PREFIXTABLE_classes` ( `id` , `icon` , `class` , `title` , `altertitle` , `classtype` , `comment` , `rank` , `status` , `upd` )
		VALUES ($id , 'lodel/icons/personne.gif', 'auteurs', 'Auteurs', '', 'persons', '', '4', '1', NOW( ));\n
		UPDATE _PREFIXTABLE_objects SET class='persons' WHERE class='personnes';\n
		UPDATE _PREFIXTABLE_objects SET class='persontypes' WHERE class='typepersonnes';\n
		";

		if ($err = $this->__mysql_query_cmds($query)) {
			return $err;
		} else {
			$ret = $this->__update_entities();
			if($ret !== true)
				return $ret;
		}
		return "Ok";
	}

	/**
	 * Insertion d'une ligne dans la table objects
	 * 
	 * @return l'identifiant inséré (champ auto-incrémenté id)
	 */

	private function __insert_object($class) {
		$this->requetes .= 'INSERT INTO ' . $GLOBALS['tp'] . "objects (class) VALUES ('$class');\n";
		if(!$result = mysql_query('INSERT INTO ' . $GLOBALS['tp'] . "objects (class) VALUES ('$class')")) {
			return mysql_error();
		}
		return mysql_insert_id();
	}

	/**
	 * Mise à jour de la table entities avec les données issues des tables documents et publications
	 *
	 * @return true si OK
	 */

	private function __update_entities() {
		foreach (array("publications","documents") as $classe) {
	  		if(!$result = mysql_query("SELECT identity, " . $this->defaultgTitle . " FROM " . $GLOBALS['tp'] . $classe)) {
				return mysql_error();
			}
	  		while (list($id,$titre) = mysql_fetch_row($result)) {
	    			$titre = strip_tags($titre);
	    			if (strlen($titre)>255) {
	      				$titre=substr($titre,0,256);
	      				$titre=trim($titre);
	    			}
				$titre = str_replace("'", "\\'", $titre);
				$query .= "UPDATE _PREFIXTABLE_entities set g_title='".$titre."' WHERE id=$id;\n";
	  		}
		}
		if ($err = $this->__mysql_query_cmds($query)) {
			return $err;
		}
		return true;
	}

	/**
	 * Mise à jour des champs pour les classes
	 * Pour les entités, reprise des champs des tables publications et documents
	 * Pour les index et index de personnes, ajout dans table tablefields
	 *
	 * @return Ok si insertions dans les tables OK
	 */

	public function update_fields() {
		// ENTITÉS : mise à jour des colonnes 'class' et 'g_name' seulement
		if(!$result = mysql_query("SELECT id,class FROM ".$GLOBALS['tp']."tablefieldgroups WHERE status>0")) {
			return mysql_error();
		}
		$query = '';
		while ($row = mysql_fetch_assoc($result)) {
			$query .= "UPDATE _PREFIXTABLE_tablefields SET g_name = '".$this->defaultgName."', class='" . $row['class'] . "' WHERE idgroup = " . $row['id'] . ";\n";
		}

		$query .= "UPDATE $GLOBALS[tp]entities SET status = 8 WHERE status = 32;\n";
		if(!$result = mysql_query("SELECT $GLOBALS[tp]entites__old.id, $GLOBALS[tp]entites__old.maj, $GLOBALS[tp]documents__old.fichiersource FROM $GLOBALS[tp]entites__old JOIN $GLOBALS[tp]documents__old ON ($GLOBALS[tp]entites__old.id = $GLOBALS[tp]documents__old.identite)")) {
			return mysql_error();
		}
		while ($row = mysql_fetch_assoc($result)) {
			$query .= "UPDATE $GLOBALS[tp]entities SET creationmethod = 'servoo', creationdate = '".$row['maj']."', modificationdate = '".$row['maj']."', creationinfo = \"".$row['fichiersource']."\" WHERE id = " . $row['id'] . ";\n";
		}
		if(!$result = mysql_query("SELECT $GLOBALS[tp]entites__old.id, $GLOBALS[tp]entites__old.maj FROM $GLOBALS[tp]entites__old JOIN $GLOBALS[tp]publications__old ON ($GLOBALS[tp]entites__old.id = $GLOBALS[tp]publications__old.identite)")) {
			return mysql_error();
		}
		while ($row = mysql_fetch_assoc($result)) {
			$query .= "UPDATE $GLOBALS[tp]entities SET creationdate = '".$row['maj']."', modificationdate = '".$row['maj']."' WHERE id = " . $row['id'] . ";\n";
		}
		
		// INDEX : ajout des champs dans tablefields
		$query .= "INSERT INTO _PREFIXTABLE_tablefields (id, name, idgroup, class, title, altertitle, style, type, g_name, cond, defaultvalue, processing, allowedtags, gui_user_complexity, filtering, edition, editionparams, weight, comment, status, rank, upd) VALUES
			(NULL, 'nom', '0', 'indexes', 'Dénomination de l\'entrée d\'index', '', '', 'text', 'index key', '*', 'Tous droits réservés', '', '', '16', '', 'editable', '', '4', '', '32', '', NOW( )),

			(NULL, 'definition', '0', 'indexes', 'Définition', '', '', 'text', '', '*', '', '', '', '16', '', 'fckeditor', 'Basic', '1', '', '32', '', NOW( ));\n";

		// INDEX DE PERSONNES : ajout des champs dans tablefields
		$query .= "INSERT INTO _PREFIXTABLE_tablefields (id, name, idgroup, class, title, altertitle, style, type, g_name, cond, defaultvalue, processing, allowedtags, gui_user_complexity, filtering, edition, editionparams, weight, comment, status, rank, upd) VALUES
			(NULL, 'nomfamille', '0', 'auteurs', 'Nom de famille', '', '', 'tinytext', 'familyname', '*', '', '', '', '32', '', 'editable', '', '4', '', '32', '', NOW( )),

			(NULL, 'prenom', '0', 'auteurs', 'Prénom', '', '', 'tinytext', 'firstname', '*', '', '', '', '32', '', 'editable', '', '4', '', '32', '', NOW( )),

			(NULL, 'prefix', '0', 'entities_auteurs', 'Préfixe', '', 'prefixe, .prefixe', 'tinytext', '', '*', '', '', '', '64', '', 'editable', '', '0', '', '1', '', NOW( )),

			(NULL, 'affiliation', '0', 'entities_auteurs', 'Affiliation', '', 'affiliation, .affiliation', 'tinytext', '', '*', '', '', '', '32', '', 'editable', '', '4', '', '1', '', NOW( )),
			
			(NULL, 'fonction', '0', 'entities_auteurs', 'Fonction', '', 'fonction, .fonction', 'tinytext', '', '*', '', '', '', '32', '', 'editable', '', '0', '', '1', '', NOW( )),

			(NULL, 'description', '0', 'entities_auteurs', 'Description de l\'auteur', '', 'descriptionauteur', 'text', '', '*', '', '', '', '16', '', 'fckeditor', '5', '4', '', '1', '', NOW( )),

			(NULL, 'courriel', '0', 'entities_auteurs', 'Courriel', '', 'courriel, .courriel', 'email', '', '*', '', '', '', '32', '', 'editable', '', '4', '', '1', '', NOW( )),

			(NULL, 'role', '0', 'entities_auteurs', 'Role dans l\'élaboration du document', '', 'role,.role', 'text', '', '*', '', '', '', '64', '', 'editable', '', '0', '', '1', '', NOW( ));\n";

		if ($err = $this->__mysql_query_cmds($query)) {
				return $err;
		} else {
			return "Ok";
		}
	}

	/**
	 * Mise à jour des types
	 * 
	 * @return Ok si insertions dans les tables OK
	 */

	public function update_types() {
		// ENTITES
		$query = "UPDATE _PREFIXTABLE_types SET display='';\n";

		// INDEX
		$query .= "UPDATE _PREFIXTABLE_entrytypes SET class = 'indexes', sort = 'sortkey';\n";
		
		// INDEX DE PERSONNES
		$query .= "UPDATE _PREFIXTABLE_persontypes SET class = 'auteurs';\n";

		// type personne
		$query .= "UPDATE _PREFIXTABLE_persons JOIN _PREFIXTABLE_entites_personnes__old ON id = idpersonne SET _PREFIXTABLE_persons.idtype = _PREFIXTABLE_entites_personnes__old.idtype;\n";

		
		$query .= "INSERT INTO _PREFIXTABLE_translations (id, lang, title, textgroups, translators, modificationdate, creationdate, rank, status, upd) VALUES ('1', 'FR', 'Français', 'site', '', '', NOW(), '1', '1', NOW());\n";
	
		$query .= "UPDATE _PREFIXTABLE_texts SET lang = 'FR', textgroup = 'site';\n
		UPDATE _PREFIXTABLE_objects SET class='persons' WHERE class='personnes';\n
		UPDATE _PREFIXTABLE_objects SET class='entrytypes' WHERE class='typeentrees';\n
		UPDATE _PREFIXTABLE_objects SET class='persontypes' WHERE class='typepersonnes';\n";
		
		if ($err = $this->__mysql_query_cmds($query)) {
				return $err;
		} else {
			return "Ok";
		}
	}

	/**
	 * Remplissage des tables des index et index de personnes à partir des tables 0.7 (entrees et auteurs)
	 * Mise à jour des relations : en 0.8, toutes les relations sont stockées dans la table relations,
	 * en 0.7 dans les tables entites_entrees et entites_personnes
	 * @var bool $meRevuesorg
	 * @return Ok si insertions dans les tables OK
	 */

	public function insert_index_data($meRevuesorg=false) {
		// id unique pour les entrées d'index
		if(!$req = mysql_query("SELECT ".$GLOBALS['tp']."entries.id FROM ".$GLOBALS['tp']."entries JOIN ".$GLOBALS['tp']."objects ON ".$GLOBALS['tp']."entries.id = ".$GLOBALS['tp']."objects.id WHERE ".$GLOBALS['tp']."objects.class != 'entries';")) {
			return mysql_error();
		}
		while($res = mysql_fetch_row($req)) {
			$id = $this->__insert_object('entries');
			$query .= "UPDATE _PREFIXTABLE_entries SET id = '".$id."' WHERE id = '".$res[0]."';\n";
			$q .= "UPDATE _PREFIXTABLE_relations SET id2 = '".$id."' WHERE id2 = '".$res[0]."' AND nature = 'E';\n";
		}
		mysql_free_result($req);
		// id unique pour les entrées de personnes
		if(!$req = mysql_query("SELECT ".$GLOBALS['tp']."persons.id FROM ".$GLOBALS['tp']."persons JOIN ".$GLOBALS['tp']."objects ON ".$GLOBALS['tp']."persons.id = ".$GLOBALS['tp']."objects.id WHERE ".$GLOBALS['tp']."objects.class != 'persons';")) {
			return mysql_error();
		}
		while($res = mysql_fetch_row($req)) {
			$id = $this->__insert_object('persons');
			$query .= "UPDATE _PREFIXTABLE_persons SET id = '".$id."' WHERE id = '".$res[0]."';\n";
			$query .= "UPDATE _PREFIXTABLE_auteurs SET idperson = '".$id."' WHERE idperson = '".$res[0]."';\n";
			$q .= "UPDATE _PREFIXTABLE_relations SET id2 = '".$id."' WHERE id2 = '".$res[0]."' AND nature = 'G';\n";
		}
		mysql_free_result($req);
		// besoin d'executer certaines requetes avant de continuer
		if (!empty($query) && $err = $this->__mysql_query_cmds($query)) {
			return $err;
		} else {
			unset($query, $id);
		}
		// MAJ classe des objets entité
		if(!$req = mysql_query("SELECT id FROM ".$GLOBALS['tp']."entities;")) {
			return mysql_error();
		}
		while($res = mysql_fetch_row($req)) {
			$query .= "UPDATE _PREFIXTABLE_objects SET class = 'entities' WHERE id = '".$res[0]."';\n";
		}


		$query .= "REPLACE INTO _PREFIXTABLE_indexes (identry, nom) SELECT id, g_name from _PREFIXTABLE_entries;\n
		INSERT INTO _PREFIXTABLE_relations (id2, id1, nature, degree) SELECT DISTINCT identree, identite, 'E' as nat, '1' as deg from _PREFIXTABLE_entites_entrees__old;\n
		";
		if($meRevuesorg) {
			// licence
			if(!$result = mysql_query("SELECT distinct droitsauteur from ".$GLOBALS['tp']."documents__old;")) {
				return mysql_error();
			}
			$i = 1;
			while($res = mysql_fetch_array($result)) {
				if($res['droitsauteur'] != "") {
					$id = $this->__insert_object('entries');
					$query .= "INSERT INTO _PREFIXTABLE_entries(id, g_name, sortkey, idtype, rank, status, upd) VALUES ('".$id."', \"".$res['droitsauteur']."\", \"".makeSortKey($res['droitsauteur'])."\", (select id from _PREFIXTABLE_entrytypes where type = 'licence'), '".$i."', '1', NOW());\n";
					$query .= "INSERT INTO _PREFIXTABLE_indexavances (identry, nom) SELECT id, g_name from _PREFIXTABLE_entries WHERE id = '".$id."';\n";
		
					if(!$req = mysql_query("SELECT identite FROM ".$GLOBALS['tp']."documents__old WHERE droitsauteur = \"".$res['droitsauteur']."\"")) {
						return mysql_error();
					}
					while($re = mysql_fetch_array($req)) {
						$query .= "INSERT INTO _PREFIXTABLE_relations (id2, id1, nature, degree) VALUES ('".$id."', '".$re['identite']."', 'E', 1);\n";	
					}
					$i++;	
				}	
			}
			mysql_free_result($result);
			mysql_free_result($req);
		}
		// besoin d'executer certaines requetes avant de continuer
		if (!empty($query) && $err = $this->__mysql_query_cmds($query)) {
			return $err;
		} else {
			unset($query, $max_id);
		}
		// INDEX DE PERSONNES : tables auteurs, entities_auteurs et relations
		$query = "REPLACE INTO _PREFIXTABLE_auteurs (idperson, nomfamille, prenom) SELECT id, g_familyname, g_firstname from _PREFIXTABLE_persons;\n
		INSERT INTO _PREFIXTABLE_relations (id2, id1, degree, nature) SELECT DISTINCT idpersonne, identite, ordre, 'G' as nat from _PREFIXTABLE_entites_personnes__old;\n
		REPLACE INTO _PREFIXTABLE_entities_auteurs (idrelation, prefix, affiliation, fonction, description, courriel) SELECT DISTINCT idrelation, prefix, affiliation, fonction, description, courriel from _PREFIXTABLE_relations, _PREFIXTABLE_entites_personnes__old where nature='G' and idpersonne=id2 and identite=id1;\n
		";
		
		if ($err = $this->__mysql_query_cmds($query) || (!empty($q) && $err = $this->__mysql_query_cmds($q))) {
				return $err;
		} else {
			unset($query, $q, $i, $max_id);
			// mise à jour des degrés entre les entrées d'index
			if(!$result = mysql_query("SELECT * FROM " . $GLOBALS['tp'] . "relations WHERE nature='E' AND id1 != 0 ORDER BY id1, id2")) {
				return mysql_error();
			}
			while($res = mysql_fetch_array($result)) {
				$i = 1;
				if(!$re = mysql_query("SELECT id2 FROM " . $GLOBALS['tp'] . "relations WHERE id1 = '".$res['id1']."' AND nature = 'E' ORDER BY id2")) {
					return mysql_error();
				}
				while($resu = mysql_fetch_array($re)) {
					$query .= "UPDATE _PREFIXTABLE_relations SET degree = ".$i." WHERE id1 = '".$res['id1']."' AND id2 = '".$resu['id2']."' AND nature = 'E';\n";
					$i++;
				}
			}
			if (!empty($query) && $err = $this->__mysql_query_cmds($query)) {
					return $err;
			} else {
				unset($query);
			}			
			// on récupère les équivalences entre les IDs 0.7 et 0.8
			if(!$resultat = mysql_query("SELECT t.id, t.titre, tp.id as tid, tp.title as title FROM " . $GLOBALS['tp'] . "typepersonnes__old as t, " . $GLOBALS['tp'] . "persontypes as tp where replace(titre, ' ', '%') LIKE replace(replace(title, ' ', '%'), 's', '%')")) {
				die(mysql_error());
			}
			while($rtypes = mysql_fetch_array($resultat)) { 
				$type08[$rtypes['id']] = $rtypes['tid'];
			}
			// puis on travaille avec
			if(!$result = mysql_query("SELECT * FROM ".$GLOBALS['tp']."personnes__old")) {
				return mysql_error();
			} else {
				/* on règle un problème de compatibilité : en 0.7, une seule entrée dans la table personne permettait d'avoir un auteur de type différent (auteur, dir de publication ..
				En 0.8 chaque entrée dans la table correspond à un idtype bien précis.
				*/
				while($res = mysql_fetch_array($result)) {
					// pour chaque personne on récupère chaque idtype
					if(!$resu = mysql_query("SELECT DISTINCT idtype FROM " . $GLOBALS['tp'] . "entites_personnes__old WHERE idpersonne = '".$res['id']."'")) {
						return mysql_error();
					}
					// plus d'un idtype par personne ? ok faut donc créer une entrée correspondante
					if(mysql_num_rows($resu) > 1) {
						// on récupère l'idtype après migration de l'entrée déjà créée
						if(!$resulta = mysql_query("SELECT DISTINCT idtype FROM " . $GLOBALS['tp'] . "persons WHERE (g_familyname = \"".$res['nomfamille']."\" OR g_familyname = \"".utf8_encode($res['nomfamille'])."\") AND (g_firstname = \"".$res['prenom']."\" OR g_firstname = \"".utf8_encode($res['prenom'])."\")")) {
							die(mysql_error());
						}
						unset($idtype);
						while($resr = mysql_fetch_array($resulta)) {
							$idtype[] = $resr['idtype'];
						}
						while($r = mysql_fetch_array($resu)) {
							if(!in_array($type08[$r['idtype']], $idtype)) { // n'existe pas encore .. on la crée

								$id = $this->__insert_object('persons');

								$query .= "INSERT INTO _PREFIXTABLE_persons (id, idtype, g_familyname, g_firstname, sortkey, status, upd) VALUES ('".$id."', '".$type08[$r['idtype']]."', \"".$res['nomfamille']."\", \"".$res['prenom']."\", \"".strtolower($res['nomfamille']." ".$res['prenom'])."\" , '".$res['statut']."', '".$res['maj']."');\n";

								$query .= "INSERT INTO _PREFIXTABLE_auteurs (idperson, nomfamille, prenom) VALUES ('".$id."', \"".$res['nomfamille']."\", \"".$res['prenom']."\");\n";

								// puis on met à jour la table relations pour indiquer l'ID de l'entrée créée!
								if(!$resul = mysql_query("SELECT * FROM ".$GLOBALS['tp']."entites_personnes__old WHERE idpersonne = '".$res['id']."' AND idtype = '".$r['idtype']."'")) {
									return mysql_error();
								}

								while($rr = mysql_fetch_array($resul)) {
									$query .= "UPDATE _PREFIXTABLE_relations SET id2 = '".$id."' WHERE id1 = '".$rr['identite']."' AND id2 = '".$rr['idpersonne']."';\n";
								}
								if(!empty($query) && $err = $this->__mysql_query_cmds($query)) {
									return $err;
								}
								unset($query);
							}
						}
					}
				}
			}
			// on s'attaque à l'ordre d'affichage des entités
			// articles en premiers, regroupements et autres rubriques après
			/*
			// on récupère chaque partie du site
			if(!$result = mysql_query("SELECT DISTINCT idparent FROM ".$GLOBALS['tp']."entities;")) {
				return mysql_error();
			}
			unset($query);
			// pour chaque partie on va vérifier le template utilisé. Si tpl = article alors l'entité passe avant le reste
			while($res = mysql_fetch_array($result)) {
				if(!$results = mysql_query("SELECT ".$GLOBALS['tp']."entities.id as id,
								idtype,
								idparent,
								tpl,
								".$GLOBALS['tp']."entities.rank,
								(SELECT COUNT(".$GLOBALS['tp']."entities.id) 
									FROM ".$GLOBALS['tp']."entities JOIN ".$GLOBALS['tp']."types ON ".$GLOBALS['tp']."entities.idtype = ".$GLOBALS['tp']."types.id
									WHERE tpl != 'article' AND ".$GLOBALS['tp']."entities.idparent = '".$res['idparent']."') as nb,
								(SELECT COUNT(".$GLOBALS['tp']."entities.id) 
									FROM ".$GLOBALS['tp']."entities JOIN ".$GLOBALS['tp']."types ON ".$GLOBALS['tp']."entities.idtype = ".$GLOBALS['tp']."types.id
									WHERE tpl = 'article' AND ".$GLOBALS['tp']."entities.idparent = '".$res['idparent']."') as nbart 
								FROM ".$GLOBALS['tp']."entities JOIN ".$GLOBALS['tp']."types on ".$GLOBALS['tp']."types.id = idtype 
								WHERE idparent = '".$res['idparent']."' 
								ORDER BY ".$GLOBALS['tp']."entities.rank DESC;")) {
					return mysql_error();

				}
				$nb = mysql_num_rows($results)+1;
				$i = $j = 0;
				while($resu = mysql_fetch_array($results)) {
					if($resu['nbart'] > 0) {
						$rank = 0;
						if($resu['tpl'] != "article") {
							$rank = intval($nb - $resu['rank']);
						} else {
							$rank = $resu['rank'] - $resu['nb'];
						}
						$query .= "UPDATE _PREFIXTABLE_entities SET rank = '".$rank."' WHERE id = '".$resu['id']."';\n";
					}
				}	
			}
			if(!empty($query) && $err = $this->__mysql_query_cmds($query)) {
				return $err;
			}
			*/
		}
		return "Ok";
	}

	/**
	 * Mise à jour du ME pour conformité avec ME revues.org de la 0.8
	 * 
	 * @return Ok si insertions dans les tables OK
	 */

	public function update_ME() {
		// classe documents devient textes
		if($err = $this->__mysql_query_cmds("RENAME TABLE _PREFIXTABLE_documents TO _PREFIXTABLE_textes;\n")) {
			return $err;
		}
		$query = "UPDATE _PREFIXTABLE_classes SET class = 'textes',title = 'Textes' WHERE class = 'documents';\n
		UPDATE _PREFIXTABLE_objects SET class = 'textes' WHERE class='documents';\n
		UPDATE _PREFIXTABLE_types SET class = 'textes' WHERE class='documents';\n
		UPDATE _PREFIXTABLE_tablefields SET class = 'textes' WHERE class='documents';\n
		UPDATE _PREFIXTABLE_tablefieldgroups SET class = 'textes' WHERE class='documents';\n
		";

		// Nom des TEMPLATES dans l'onglet Édition
		$query .= "UPDATE _PREFIXTABLE_types SET tpledition = 'edition', tplcreation = 'entities';\n";

		// CLASSES supplémentaires
		$query .= "INSERT IGNORE INTO `_PREFIXTABLE_classes` (`id` , `icon` , `class` , `title` , `altertitle` , `classtype` , `comment` , `rank` , `status` , `upd` ) VALUES
		(NULL, 'lodel/icons/doc_annexe.gif', 'fichiers', 'Fichiers', '', 'entities', '', '5', '32', NOW()),
		(NULL, 'lodel/icons/lien.gif', 'liens', 'Sites', '', 'entities', '', '6', '32', NOW()),
		(NULL, 'lodel/icons/texte_simple.gif', 'textessimples', 'Textes simples', '', 'entities', '', '3', '32', NOW()),
		(NULL, 'lodel/icons/individu.gif', 'individus', 'Personnes', '', 'entities', '', '4', '1', NOW()),
		(NULL, 'lodel/icons/index_avance.gif', 'indexavances', 'Index avancés', '', 'entries', '', '10', '1', NOW());\n
		
		CREATE TABLE _PREFIXTABLE_textessimples (
  			identity int(10) unsigned default NULL,
  			titre tinytext,
  			texte text,
  			url text,
  			`date` datetime default NULL,
  			UNIQUE KEY identity (identity),
  			KEY index_identity (identity)
		) _CHARSET_;\n

		CREATE TABLE _PREFIXTABLE_individus (
  			identity int(10) unsigned default NULL,
  			nom tinytext,
  			prenom tinytext,
  			email text,
  			siteweb text,
  			description text,
  			accroche text,
  			adresse text,
  			telephone tinytext,
  			photographie tinytext,
  			UNIQUE KEY identity (identity),
  			KEY index_identity (identity)
		) _CHARSET_;\n

		CREATE TABLE _PREFIXTABLE_indexavances (
			identry int(10) unsigned default NULL,
  			nom tinytext,
  			description text,
  			url text,
  			icone tinytext,
  			UNIQUE KEY identry (identry),
  			KEY index_identry (identry)
		) _CHARSET_;\n

		";

		// TYPES

		// articlevide convertis en article non cliquable
		if(!$result = mysql_query("SELECT id FROM " . $GLOBALS['tp'] . "entities WHERE idtype = (SELECT id FROM " . $GLOBALS['tp'] . "types WHERE type = 'articlevide')")) {
			return mysql_error();
		}
		while($r = mysql_fetch_array($result)) {
			$iddc[] = $r['id'];
		}
		$nb = count($iddc);
		$ids = $iddc[0];
		for($i=1; $i<$nb; $i++) {
			$ids .= ", ".$iddc[$i];
		}
		/*for($i=0;$i<23;$i++) {
			$id[] = $this->__insert_object('types');
		}	
*/
		$q .= "DELETE FROM _PREFIXTABLE_types;\n
		INSERT INTO _PREFIXTABLE_types (id, icon, type, title, altertitle, class, tpl, tplcreation, tpledition, import, display, creationstatus, search, public, gui_user_complexity, oaireferenced, rank, status, upd) VALUES 

		('1', '', 'editorial', 'Editorial', '', 'textes', 'article', 'entities', '', '1', '', '-1', '1', '0', '32', '1', '1', '32', NOW()),
		('2', '', 'article', 'Article', '', 'textes', 'article', 'entities', '', '1', '', '-1', '1', '0', '16', '1', '2', '1', NOW()),
		('3', '', 'actualite', 'Annonce et actualité', '', 'textes', 'article', 'entities', '', '1', '', '-1', '1', '0', '32', '0', '3', '32', NOW()),
		('4', '', 'compte rendu', 'Compte-rendu', '', 'textes', 'article', 'entities', '', '1', '', '-1', '1', '0', '32', '1', '5', '32', NOW()),
		('5', '', 'note de lecture', 'Note de lecture', '', 'textes', 'article', 'entities', '', '1', '', '-1', '1', '0', '64', '1', '6', '32', NOW()),
		('6', '', 'informations', 'Informations pratiques', '', 'textes', 'article', 'entities', '', '1', '', '-1', '1', '0', '32', '0', '7', '32', NOW()),
		('7', '', 'chronique', 'Chronique', '', 'textes', 'article', 'entities', '', '1', '', '-1', '1', '0', '64', '0', '8', '32', NOW()),
		('8', '', 'collection', 'Collection', '', 'publications', 'sommaire', 'entities', 'edition', '0', '', '-1', '1', '0', '16', '0', '1', '32', NOW()),
		('9', 'lodel/icons/volume.gif', 'numero', 'Numéro de revue', '', 'publications', 'sommaire', 'entities', 'edition', '0', '', '-1', '1', '0', '32', '0', '3', '32', NOW()),
		('10', 'lodel/icons/rubrique.gif', 'rubrique', 'Rubrique', '', 'publications', 'sommaire', 'entities', 'edition', '0', '', '-1', '1', '0', '16', '0', '5', '32', NOW()),
		('11', 'lodel/icons/rubrique_plat.gif', 'souspartie', 'Sous-partie', '', 'publications', '', 'entities', 'edition', '0', 'unfolded', '-1', '1', '0', '16', '0', '6', '32', NOW()),
		('12', '', 'image', 'Image', '', 'fichiers', 'image', 'entities', '', '0', '', '-1', '1', '0', '64', '1', '1', '1', NOW()),
		('13', '', 'noticedesite', 'Notice de site', '', 'liens', 'lien', 'entities', '', '0', '', '-1', '1', '0', '64', '0', '16', '1', NOW()),
		('14', 'lodel/icons/commentaire.gif', 'commentaire', 'Commentaire du document', '', 'textessimples', '', 'entities', '', '0', 'advanced', '-1', '1', '1', '16', '0', '2', '1', NOW()),
		('25', '', 'videoannexe', 'Vidéo placée en annexe', '', 'fichiers', '', 'entities', 'edition', '0', 'advanced', '-1', '1', '0', '64', '0', '4', '1', NOW()),
		('20', '', 'annuairedequipe', 'Équipe', '', 'publications', 'sommaire', 'entities', 'edition', '0', '', '-1', '1', '0', '16', '0', '8', '32', NOW()),
		('21', '', 'annuairemedias', 'Médiathèque', '', 'publications', 'sommaire', 'entities', 'edition', '0', '', '-1', '1', '0', '16', '0', '9', '32', NOW()),
		('15', '', 'image_annexe', 'Image placée en annexe', '', 'fichiers', '', 'entities', '', '0', 'advanced', '-1', '1', '0', '64', '0', '2', '1', NOW()),
		('16', '', 'lienannexe', 'Lien placé en annexe', '', 'liens', 'lien', 'entities', '', '0', 'advanced', '-1', '1', '0', '64', '0', '24', '1', NOW()),
		('17', '', 'individu', 'Notice biographique de membre', '', 'individus', 'individu', 'entities', '', '0', '', '-1', '1', '0', '16', '0', '25', '1', NOW()),
		('18', '', 'billet', 'Billet', '', 'textessimples', 'article', 'entities', '', '0', '', '-1', '1', '0', '16', '0', '1', '1', NOW()),
		('19', '', 'annuairedesites', 'Annuaire de sites', '', 'publications', 'sommaire', 'entities', 'edition', '0', '', '-1', '1', '0', '16', '0', '7', '32', NOW()),
		('22', 'lodel/icons/rss.gif', 'fluxdesyndication', 'Flux de syndication', '', 'liens', 'lien', 'entities', '', '0', '', '-1', '1', '0', '64', '0', '30', '1', NOW()),
		('23', '', 'video', 'Vidéo', '', 'fichiers', '', 'entities', '', '0', '', '-1', '1', '0', '64', '0', '3', '1', NOW()),
		('24', '', 'son', 'Document sonore', '', 'fichiers', '', 'entities', '', '0', '', '-1', '1', '0', '32', '0', '5', '1', NOW()),
		('26', '', 'fichierannexe', 'Fichier placé en annexe', '', 'fichiers', 'image', 'entities', '', '0', 'advanced', '-1', '1', '0', '32', '0', '7', '1', NOW()),
		('27', '', 'sonannexe', 'Document sonore placé en annexe', '', 'fichiers', '', 'entities', '', '0', 'advanced', '-1', '1', '0', '32', '0', '6', '1', NOW()),
		('326', '', 'imageaccroche', 'Image d\'accroche', '', 'fichiers', 'image', 'entities', '', '0', 'advanced', '-1', '1', '0', '16', '0', '31', '32', NOW()),
		('327', 'lodel/icons/rubrique.gif', 'rubriqueannuaire', 'Rubrique (d\'annuaire de site)', '', 'publications', 'sommaire', 'entities', 'edition', '0', '', '-1', '1', '0', '16', '0', '32', '32', NOW()),
		('328', '', 'rubriquemediatheque', 'Rubrique (de médiathèque)', '', 'publications', 'sommaire', 'entities', 'edition', '0', '', '-1', '1', '0', '16', '0', '33', '32', NOW()),
		('329', 'lodel/icons/rubrique.gif', 'rubriqueequipe', 'Rubrique (d\'équipe)', '', 'publications', 'sommaire', 'entities', 'edition', '0', 'unfolded', '-1', '1', '0', '16', '0', '34', '32', NOW()),
		('81', 'lodel/icons/rubrique.gif', 'rubriqueactualites', 'Rubrique (d\'actualités)', '', 'publications', 'sommaire', 'entities', 'edition', '0', '', '-1', '1', '0', '16', '0', '35', '32', NOW());\n";

		if ($err = $this->__mysql_query_cmds($q)) {
				return $err;
		} else {
			unset($q);
		}
		if(!$result = mysql_query("SELECT " . $GLOBALS['tp'] . "types__old.id as toid, " . $GLOBALS['tp'] . "types__old.type tot, " . $GLOBALS['tp'] . "types.id, " . $GLOBALS['tp'] . "types.type FROM " . $GLOBALS['tp'] . "types__old LEFT JOIN " . $GLOBALS['tp'] . "types ON (" . $GLOBALS['tp'] . "types__old.type = " . $GLOBALS['tp'] . "types.type)")) {
			return mysql_error();
		}
		$types07to08 = array	(
					"volume"=>"rubrique",
					"colloque"=>"rubrique",
					"objetdelarecension"=>"compte rendu",
					"regroupement"=>"souspartie",
					"presentation"=>"informations",
					"breve"=>"billet",
					"articlevide"=>"article"
					);
		while($res = mysql_fetch_array($result)) {
			if($res['id'] != NULL) {
				$query .= "UPDATE _PREFIXTABLE_entities SET idtype = '".$res['id']."' WHERE idtype = '".$res['toid']."';\n";
			} else {
				$corr[$res['toid']] = $res['tot'];
			}
		}
		$idsToUpdate = join(', ', array_keys($corr));
		if(!$result = mysql_query("SELECT id, idtype FROM " . $GLOBALS['tp'] . "entities WHERE idtype IN (".$idsToUpdate.")")) {
			return mysql_error();
		}
		while($r = mysql_fetch_array($result)) {
			$query .= "UPDATE _PREFIXTABLE_entities SET idtype = (SELECT id FROM _PREFIXTABLE_types WHERE type = '".$types07to08[$corr[$r['idtype']]]."') WHERE id = '".$r['id']."';\n";
		}
		// 7 types à adapter 
/*		$query .= "UPDATE _PREFIXTABLE_entities SET idtype = '10' WHERE idtype IN ('".$corr['volume']."', '".$corr['colloque']."');\n";
		//	  UPDATE _PREFIXTABLE_entitytypes_entitytypes SET identitytype = '10' WHERE identitytype IN ('".$corr['volume']."', '".$corr['colloque']."');\n
		//	  UPDATE _PREFIXTABLE_entitytypes_entitytypes SET identitytype2 = '10' WHERE identitytype2 IN ('".$corr['volume']."', '".$corr['colloque']."');\n";	

		$query .= "UPDATE _PREFIXTABLE_entities SET idtype = '4' WHERE idtype = '".$corr['objetdelarecension']."';\n";
		//	  UPDATE _PREFIXTABLE_entitytypes_entitytypes SET identitytype = '4' WHERE identitytype = '".$corr['objetdelarecension']."';\n
		//	  UPDATE _PREFIXTABLE_entitytypes_entitytypes SET identitytype2 = '4' WHERE identitytype2 = '".$corr['objetdelarecension']."';\n";

		$query .= "UPDATE _PREFIXTABLE_entities SET idtype = '11' WHERE idtype = '".$corr['regroupement']."';\n";
		//	  UPDATE _PREFIXTABLE_entitytypes_entitytypes SET identitytype = '11' WHERE identitytype = '".$corr['regroupement']."';\n
		//	  UPDATE _PREFIXTABLE_entitytypes_entitytypes SET identitytype2 = '11' WHERE identitytype2 = '".$corr['regroupement']."';\n";

		$query .= "UPDATE _PREFIXTABLE_entities SET idtype = '6' WHERE idtype = '".$corr['presentation']."';\n";
		//	  UPDATE _PREFIXTABLE_entitytypes_entitytypes SET identitytype = '6' WHERE identitytype = '".$corr['presentation']."';\n
		//	  UPDATE _PREFIXTABLE_entitytypes_entitytypes SET identitytype2 = '6' WHERE identitytype2 = '".$corr['presentation']."';\n";

		$query .= "UPDATE _PREFIXTABLE_entities SET idtype = '18' WHERE idtype = '".$corr['breve']."';\n";
		//	  UPDATE _PREFIXTABLE_entitytypes_entitytypes SET identitytype = '18' WHERE identitytype = '".$corr['breve']."';\n
		//	  UPDATE _PREFIXTABLE_entitytypes_entitytypes SET identitytype2 = '18' WHERE identitytype2 = '".$corr['breve']."';\n";

		$query .= "UPDATE _PREFIXTABLE_entities SET idtype = '2' WHERE idtype = '".$corr['articlevide']."';\n";
		//	  UPDATE _PREFIXTABLE_entitytypes_entitytypes SET identitytype = '2' WHERE identitytype = '".$corr['articlevide']."';\n
		//	  UPDATE _PREFIXTABLE_entitytypes_entitytypes SET identitytype2 = '2' WHERE identitytype2 = '".$corr['articlevide']."';\n";
*/
		
		if ($err = $this->__mysql_query_cmds($query)) {
				return $err;
		} else {
			unset($query);
		}
		// MAJ des relations entres les types d'entité
		$query = "DELETE FROM _PREFIXTABLE_entitytypes_entitytypes;\n
			INSERT INTO _PREFIXTABLE_entitytypes_entitytypes (identitytype, identitytype2, cond) VALUES ('8', '0', '*'),
				('11', '11', '*'),
				('11', '9', '*'),
				('1', '10', '*'),
				('2', '327', '*'),
				('2', '10', '*'),
				('3', '328', '*'),
				('20', '8', '*'),
				('3', '11', '*'),
				('3', '327', '*'),
				('3', '81', '*'),
				('3', '10', '*'),
				('21', '10', '*'),
				('4', '9', '*'),
				('4', '8', '*'),
				('4', '21', '*'),
				('5', '328', '*'),
				('6', '8', '*'),
				('6', '21', '*'),
				('6', '19', '*'),
				('6', '20', '*'),
				('7', '9', '*'),
				('7', '8', '*'),
				('7', '21', '*'),
				('7', '19', '*'),
				('26', '6', '*'),
				('14', '5', '*'),
				('13', '11', '*'),
				('13', '327', '*'),
				('13', '10', '*'),
				('20', '0', '*'),
				('14', '6', '*'),
				('14', '1', '*'),
				('14', '4', '*'),
				('14', '7', '*'),
				('26', '1', '*'),
				('1', '9', '*'),
				('9', '8', '*'),
				('1', '8', '*'),
				('1', '21', '*'),
				('2', '9', '*'),
				('14', '2', '*'),
				('14', '3', '*'),
				('14', '13', '*'),
				('12', '11', '*'),
				('12', '10', '*'),
				('19', '10', '*'),
				('19', '8', '*'),
				('14', '12', '*'),
				('26', '4', '*'),
				('25', '18', '*'),
				('25', '6', '*'),
				('25', '5', '*'),
				('25', '1', '*'),
				('25', '4', '*'),
				('25', '7', '*'),
				('25', '2', '*'),
				('25', '3', '*'),
				('2', '8', '*'),
				('5', '11', '*'),
				('19', '0', '*'),
				('12', '9', '*'),
				('13', '9', '*'),
				('15', '5', '*'),
				('15', '6', '*'),
				('15', '1', '*'),
				('15', '4', '*'),
				('15', '7', '*'),
				('15', '2', '*'),
				('15', '3', '*'),
				('16', '1', '*'),
				('16', '4', '*'),
				('16', '7', '*'),
				('16', '2', '*'),
				('16', '3', '*'),
				('16', '10', '*'),
				('16', '9', '*'),
				('21', '8', '*'),
				('18', '328', '*'),
				('21', '0', '*'),
				('22', '11', '*'),
				('22', '327', '*'),
				('22', '10', '*'),
				('22', '9', '*'),
				('22', '8', '*'),
				('17', '10', '*'),
				('23', '11', '*'),
				('23', '10', '*'),
				('24', '11', '*'),
				('24', '10', '*'),
				('24', '9', '*'),
				('26', '7', '*'),
				('26', '2', '*'),
				('26', '3', '*'),
				('26', '10', '*'),
				('26', '9', '*'),
				('27', '18', '*'),
				('27', '5', '*'),
				('27', '6', '*'),
				('27', '1', '*'),
				('27', '4', '*'),
				('27', '7', '*'),
				('27', '2', '*'),
				('27', '3', '*'),
				('17', '20', '*'),
				('22', '328', '*'),
				('13', '328', '*'),
				('24', '328', '*'),
				('10', '10', '*'),
				('10', '8', '*'),
				('18', '327', '*'),
				('23', '9', '*'),
				('23', '21', '*'),
				('24', '21', '*'),
				('12', '8', '*'),
				('12', '21', '*'),
				('18', '10', '*'),
				('18', '9', '*'),
				('13', '8', '*'),
				('13', '21', '*'),
				('22', '21', '*'),
				('22', '19', '*'),
				('18', '8', '*'),
				('18', '21', '*'),
				('18', '19', '*'),
				('4', '19', '*'),
				('5', '327', '*'),
				('16', '6', '*'),
				('16', '5', '*'),
				('26', '5', '*'),
				('26', '18', '*'),
				('326', '6', '*'),
				('326', '1', '*'),
				('326', '4', '*'),
				('326', '7', '*'),
				('326', '2', '*'),
				('326', '3', '*'),
				('326', '328', '*'),
				('326', '329', '*'),
				('326', '11', '*'),
				('326', '327', '*'),
				('326', '10', '*'),
				('326', '9', '*'),
				('326', '8', '*'),
				('326', '21', '*'),
				('326', '19', '*'),
				('326', '20', '*'),
				('326', '13', '*'),
				('326', '22', '*'),
				('22', '20', '*'),
				('327', '19', '*'),
				('327', '327', '*'),
				('328', '328', '*'),
				('328', '21', '*'),
				('329', '20', '*'),
				('329', '329', '*'),
				('13', '19', '*'),
				('22', '0', '*'),
				('1', '19', '*'),
				('2', '21', '*'),
				('2', '19', '*'),
				('3', '9', '*'),
				('3', '8', '*'),
				('3', '21', '*'),
				('3', '19', '*'),
				('1', '327', '*'),
				('1', '11', '*'),
				('1', '328', '*'),
				('2', '11', '*'),
				('2', '328', '*'),
				('4', '10', '*'),
				('4', '327', '*'),
				('4', '11', '*'),
				('4', '328', '*'),
				('5', '10', '*'),
				('5', '9', '*'),
				('5', '8', '*'),
				('5', '21', '*'),
				('5', '19', '*'),
				('7', '10', '*'),
				('7', '327', '*'),
				('7', '11', '*'),
				('7', '328', '*'),
				('12', '328', '*'),
				('23', '328', '*'),
				('18', '20', '*'),
				('18', '0', '*'),
				('17', '329', '*'),
				('326', '5', '*'),
				('326', '18', '*'),
				('326', '14', '*'),
				('81', '8', '*'),
				('18', '11', '*');\n";

		/*$correspondances = array('editorial'=>'1', 
					'article'=>'2',
					'actualite'=>'3',
					'compte rendu'=>'4',
					'note de lecture'=>'5',
					'informations'=>'6',
					'chronique'=>'7',
					'collection'=>'8',
					'numero'=>'9',
					'rubrique'=>'10',
					'souspartie'=>'11',
					'image'=>'12',
					'noticedesite'=>'13',
					'commentaire'=>'14',
					'image_annexe'=>'15',
					'lienannexe'=>'16',
					'individu'=>'17',
					'billet'=>'18',
					'annuairedesites'=>'19',
					'annuairedequipe'=>'20',
					'annuairemedias'=>'21',
					'fluxdesyndication'=>'22',
					'video'=>'23',
					'son'=>'24',
					'videoannexe'=>'25',
					'fichierannexe'=>'26',
					'sonannexe'=>'27',
					'rubriqueactualites'=>'81',
					'imageaccroche'=>'326',
					'rubriqueannuaire'=>'327',
					'rubriquemediatheque'=>'328',
					'rubriqueequipe'=>'329');

		mysql_free_result($result);
		if(!$result = mysql_query("SELECT id, type FROM " . $GLOBALS['tp'] . "types ORDER BY id;")) {
			return mysql_error();
		}
		while($res = mysql_fetch_array($result)) {
			$prerequete = str_replace("'".$correspondances[$res['type']]."'", "'".$res['id']."'", $prerequete);
		}

		$query .= $prerequete;*/
		if (!empty($query) && $err = $this->__mysql_query_cmds($query)) {
				return $err;
		} else {
			unset($query);
		}
/*
		if(!$result = mysql_query('SELECT * FROM ' . $GLOBALS['tp'] . 'types ORDER BY id')) {
			return mysql_error();
		}
		$nb = mysql_num_rows($result);
		unset($id);
		for($i=0;$i<$nb;$i++) {
			$id[] = $this->__insert_object('types');
		}
		$i = 0;		
		while($r = mysql_fetch_array($result)) {
			$query .= "UPDATE _PREFIXTABLE_types SET id = '".$id[$i]."' WHERE id = '".$r['id']."';\n";
			$query .= "UPDATE _PREFIXTABLE_entities SET idtype = '".$id[$i]."' WHERE idtype = '".$r['id']."';\n";
			$query .= "UPDATE _PREFIXTABLE_entitytypes_entitytypes SET identitytype = '".$id[$i]."' WHERE identitytype = '".$r['id']."';\n";
			$query .= "UPDATE _PREFIXTABLE_entitytypes_entitytypes SET identitytype2 = '".$id[$i]."' WHERE identitytype2 = '".$r['id']."';\n";
			$i++;
		}
		if ($err = $this->__mysql_query_cmds($query)) {
				return $err;
		} else {
			unset($query);
		}
		mysql_free_result($result);
		unset($id);
	
		// nettoyage table entitytypes_entitytypes
		if(!$result = mysql_query("SELECT * FROM " . $GLOBALS['tp'] . "entitytypes_entitytypes ORDER BY identitytype, identitytype2;")) {
			return mysql_error();
		}		
		while($res = mysql_fetch_array($result)) {
			$idtype1 = $res['identitytype'];
			$idtype2 = $res['identitytype2'];
			$q .= "DELETE FROM " . $GLOBALS['tp'] . "entitytypes_entitytypes WHERE identitytype = ".$res['identitytype']." && identitytype2 = ".$res['identitytype2'].";\n";
			$q .= "INSERT INTO " . $GLOBALS['tp'] . "entitytypes_entitytypes(identitytype, identitytype2, cond) VALUES ('".$res['identitytype']."', '".$res['identitytype2']."', '*');\n";
		}
		if (!empty($q) && $err = $this->__mysql_query_cmds($q)) {
				return $err;
		} else {
			unset($q);
		}		
		//$query = "UPDATE _PREFIXTABLE_types SET class = 'liens', tpl = 'lien', tplcreation = 'entities', tpledition = '', display = 'advanced' WHERE type = 'documentannexe-liendocument' OR type = 'documentannexe-lienpublication' OR type = 'documentannexe-lienexterne';\n";
		//$query .= "UPDATE _PREFIXTABLE_types SET class = 'fichiers', tpl = 'image', tplcreation = 'entities', display = 'advanced', tpledition = '' WHERE type = 'documentannexe-lienfichier';\n";
		//$query .= "UPDATE _PREFIXTABLE_types SET display = 'advanced' WHERE type = 'documentannexe-lienfichier';\n";
		$query .= "UPDATE _PREFIXTABLE_types SET display = 'unfolded' WHERE type = 'regroupement';\n";
		$query .= "UPDATE _PREFIXTABLE_types set tpledition = '' WHERE class = 'textes';\n";
		$query .= "UPDATE _PREFIXTABLE_types set tpl = '' WHERE class = 'textessimples';\n";
		//$query .= "DELETE FROM _PREFIXTABLE_types where type = 'documentannexe-lienfacsimile';\n";
*/
		// entrytypes
		unset($id);
		mysql_free_result($result);
		for($i=0;$i<8;$i++) {
			$id[$i] = $this->__insert_object('entrytypes');
		}
		if(!$result = mysql_query('SELECT * FROM ' . $GLOBALS['tp'] . 'entrytypes ORDER BY id')) {
			return mysql_error();
		}			
		if(!$resu = mysql_query('SELECT id, g_name FROM ' . $GLOBALS['tp'] . 'entries ORDER BY g_name')) {
			return mysql_error();
		}
		$query .= "REPLACE INTO _PREFIXTABLE_entrytypes (id, icon, type, class, title, altertitle, style, g_type, tpl, tplindex, gui_user_complexity, rank, status, flat, newbyimportallowed, edition, sort, upd, lang) VALUES 
		(".$id[0].", '', 'motcle', 'indexes', 'Index de mots-clés', '', 'motscles, .motcles,motscls,motsclesfr', 'dc.subject', 'entree', 'entrees', '32', '1', '1', '1', '1', 'pool', 'sortkey', NOW(), 'fr'),
		(".$id[1].", '', 'motsclesen', 'indexes', 'Index by keyword', '', 'keywords,motclesen', '', 'entree', 'entrees', '64', '2', '1', '1', '1', 'pool', 'sortkey', NOW(), 'en'),
		(".$id[2].", '', 'periode', 'indexes', 'Index chronologique', '', 'periode, .periode, priode', '', 'entree', 'entrees', '64', '5', '1', '0', '1', 'pool', 'sortkey', NOW(), 'fr'),
		(".$id[3].", '', 'theme', 'indexes', 'Index thématique', '', 'themes,thmes,.themes', '', 'entree', 'entrees', '16', '6', '1', '0', '1', 'pool', 'sortkey', NOW(), 'fr'),
		(".$id[4].", '', 'geographie', 'indexes', 'Index géographique', '', 'geographie, gographie,.geographie', '', 'entree', 'entrees', '64', '4', '1', '0', '1', 'pool', 'sortkey', NOW(), 'fr'),
		(".$id[5].", '', 'motscleses', 'indexes', 'Indice de palabras clave', '', 'palabrasclaves, .palabrasclaves, motscleses', '', 'entree', 'entrees', '64', '9', '1', '1', '1', 'pool', 'sortkey', NOW(), 'es'),
		(".$id[6].", '', 'licence', 'indexavances', 'Licence portant sur le document', '', 'licence, droitsauteur', 'dc.rights', 'entree', 'entrees', '16', '7', '1', '1', '1', 'select', 'rank', NOW(), 'fr'),
		(".$id[7].", '', 'motsclesde', 'indexes', 'Schlagwortindex', '', 'schlusselworter, .schlusselworter, motsclesde, schlagworter, .schlagworter', '', 'entree', 'entrees', '32', '8', '1', '1', '1', 'pool', 'sortkey', NOW(), 'de');\n";

		while($res = mysql_fetch_array($result)) {
			$query .= "UPDATE _PREFIXTABLE_entries SET idtype = (SELECT id FROM _PREFIXTABLE_entrytypes WHERE type = '".$res['type']."') WHERE idtype = '".$res['id']."';\n";
		}
		$i = 1;
		require_once 'func.php';
		while($re = mysql_fetch_array($resu)) {
			$query .= "UPDATE _PREFIXTABLE_entries SET sortkey = \"".makeSortKey(str_replace('"', '\"', $re['g_name']))."\", rank = '".$i++."' WHERE id = '".$re['id']."';\n";
		}
		$query .= "UPDATE _PREFIXTABLE_entrytypes SET type = 'motsclesfr' where id = '".$id[0]."';\n";
		$query .= "UPDATE _PREFIXTABLE_entrytypes SET type = 'chrono' where id = '".$id[2]."';\n";

		// OPTIONGROUPS & OPTIONS
		$query .= "INSERT INTO _PREFIXTABLE_optiongroups (id, idparent, name, title, altertitle, comment, logic, exportpolicy, rank, status, upd) VALUES
				('4', '0', 'from07', 'Suite import de données de Lodel 0.7', '', '', '', '1', '1', '32', NOW()),
				('1', '0', 'servoo', 'Servoo', '', '', 'servooconf', '1', '1', '32', NOW()),
				('2', '0', 'metadonneessite', 'Métadonnées du site', '', '', '', '1', '2', '1', NOW()),
				('3', '0', 'oai', 'OAI', '', '', '', '1', '5', '1', NOW())\n;
			UPDATE _PREFIXTABLE_options SET idgroup = 2, title = 'Signaler par mail' WHERE name = 'signaler_mail';\n
			UPDATE _PREFIXTABLE_options SET idgroup = 3, userrights = 40 WHERE name LIKE 'oai_%';\n
			UPDATE _PREFIXTABLE_options SET idgroup = 2, title = 'ISSN électronique', userrights = 30, rank = 7, status = 32 WHERE name = 'issn_electronique';\n
			UPDATE _PREFIXTABLE_options SET type = 'tinytext' WHERE type = 's';\n
			UPDATE _PREFIXTABLE_options SET type = 'passwd' WHERE type = 'pass';\n
			UPDATE _PREFIXTABLE_options SET type = 'email' WHERE type = 'mail';\n
			UPDATE _PREFIXTABLE_options SET title = 'oai_allow' WHERE name = 'oai_allow';\n
			UPDATE _PREFIXTABLE_options SET title = 'oai_deny' WHERE name = 'oai_deny';\n
			UPDATE _PREFIXTABLE_options SET title = 'Email de l\'administrateur du dépôt' WHERE name = 'oai_email';\n
			INSERT INTO _PREFIXTABLE_options (id, idgroup, name, title, type, defaultvalue, comment, userrights, rank, status, upd, edition, editionparams) VALUES 
				(NULL, '1', 'url', 'url', 'tinytext', '', '', '40', '1', '32', NOW(), 'editable', ''),
				(NULL, '1', 'username', 'username', 'username', '', '', '40', '2', '32', NOW(), 'editable', ''),
				(NULL, '1', 'passwd', 'password', 'passwd', '', '', '40', '3', '32', NOW(), '', ''),
				(NULL, '2', 'titresite', 'Titre du site', 'tinytext', 'Titresite', '', '40', '1', '1', NOW(), '', ''),
				(NULL, '2', 'titresiteabrege', 'Titre abrégé du site', 'tinytext', 'Titre abrégé du site', '', '40', '3', '1', NOW(), '', ''),
				(NULL, '2', 'descriptionsite', 'Description du site', 'text', '', '', '40', '4', '1', NOW(), 'textarea', ''),
				(NULL, '2', 'urldusite', 'URL officielle du site', 'url', '', '', '40', '5', '1', NOW(), 'editable', ''),
				(NULL, '2', 'issn', 'issn', 'tinytext', '', '', '30', '6', '1', NOW(), 'editable', ''),
				(NULL, '2', 'editeur', 'Nom de l\'éditeur du site', 'tinytext', '', '', '30', '8', '1', NOW(), '', ''),
				(NULL, '2', 'adresseediteur', 'Adresse postale de l\'éditeur', 'text', '', '', '30', '9', '1', NOW(), '', ''),
				(NULL, '2', 'producteursite', 'Nom du producteur du site', 'tinytext', '', '', '30', '10', '1', NOW(), '', ''),
				(NULL, '2', 'diffuseursite', 'Nom du diffuseur du site', 'tinytext', '', '', '30', '11', '1', NOW(), '', ''),
				(NULL, '2', 'droitsauteur', 'Droits d\'auteur par défaut', 'tinytext', '', '', '30', '12', '1', NOW(), '', ''),
				(NULL, '2', 'directeurpublication', 'Nom du directeur de la publication', 'tinytext', '', '', '30', '13', '1', NOW(), '', ''),
				(NULL, '2', 'redacteurenchef', 'Nom du Rédacteur en chef', 'tinytext', '', '', '30', '14', '1', NOW(), '', ''),
				(NULL, '2', 'courrielwebmaster', 'Courriel du webmaster', 'email', '', '', '30', '15', '1', NOW(), '', ''),
				(NULL, '2', 'courrielabuse', 'Courriel abuse', 'tinytext', '', '', '40', '16', '1', NOW(), 'editable', ''),
				(NULL, '2', 'motsclesdusite', 'Mots clés décrivant le site (entre virgules)', 'text', '', '', '30', '17', '1', NOW(), '', ''),
				(NULL, '2', 'langueprincipale', 'Langue principale du site', 'lang', 'fr', '', '40', '18', '1', NOW(), 'editable', ''),
				(NULL, '2', 'soustitresite', 'Sous titre du site', 'tinytext', '', '', '40', '2', '1', NOW(), 'editable', '');\n";

		// persontypes
		unset($id, $i);
		for($i=0;$i<5;$i++) {
			$id[$i] = $this->__insert_object('persontypes');
		}
		if(!$result = mysql_query('SELECT * FROM ' . $GLOBALS['tp'] . 'persontypes ORDER BY id')) {
			return mysql_error();
		}		
		$query .= "REPLACE INTO _PREFIXTABLE_persontypes (id, icon, type, title, altertitle, class, style, g_type, tpl, tplindex, gui_user_complexity, rank, status, upd) VALUES 
		(".$id[0].", '', 'traducteur', 'Traducteur', '', 'auteurs', 'traducteur', 'dc.contributor', 'personne', 'personnes', '64', '2', '1', NOW()),
		(".$id[1].", '', 'auteuroeuvre', 'Auteur d\'une oeuvre commentée', '', 'auteurs', 'auteuroeuvre', '', 'personne', 'personnes', '64', '4', '32', NOW()),
		(".$id[2].", '', 'editeurscientifique', 'Éditeur scientifique', '', 'auteurs', 'editeurscientifique', '', 'personne', 'personnes', '64', '5', '1', NOW()),
		(".$id[3].", 'lodel/icons/auteur.gif', 'auteur', 'Auteur', '', 'auteurs', 'auteur', 'dc.creator', 'personne', 'personnes', '32', '1', '1', NOW()),
		(".$id[4].", '', 'directeur de publication', 'Directeur de la publication', '', 'auteurs', 'directeur', '', 'personne', 'personnes', '32', '3', '32', NOW());\n
		";
		while($res = mysql_fetch_array($result)) {
			$query .= "UPDATE _PREFIXTABLE_persons SET idtype = (SELECT id FROM _PREFIXTABLE_persontypes WHERE type = '".$res['type']."') WHERE idtype = '".$res['id']."';\n";
		}
 		$query .= "UPDATE _PREFIXTABLE_persontypes SET type = 'directeurdelapublication' WHERE title = 'Directeur de la publication';\n";

		// styles internes
		$query .= "INSERT INTO _PREFIXTABLE_internalstyles (id, style, surrounding, conversion, greedy, rank, status, upd) VALUES 
			(NULL, 'citation', '*-', '<blockquote>', '0', '1', '1', NOW()),
			(NULL, 'quotations', '*-', '<blockquote>', '0', '2', '1', NOW()),
			(NULL, 'citationbis', '*-', '<blockquote class=\"citationbis\">', '0', '3', '1', NOW()),
			(NULL, 'citationter', '*-', '<blockquote class=\"citationter\">', '0', '4', '1', NOW()),
			(NULL, 'titreillustration', '*-', '', '0', '5', '1', NOW()),
			(NULL, 'legendeillustration', '*-', '', '0', '6', '1', NOW()),
			(NULL, 'titredoc', '*-', '', '0', '7', '1', NOW()),
			(NULL, 'legendedoc', '*-', '', '0', '8', '1', NOW()),
			(NULL, 'puces', '*-', '<ul><li>', '0', '9', '1', NOW()),
			(NULL, 'code', '*-', '', '0', '10', '1', NOW()),
			(NULL, 'question', '*-', '', '0', '11', '1', NOW()),
			(NULL, 'reponse', '*-', '', '0', '12', '1', NOW()),
			(NULL, 'separateur', '*-', '<hr style=\"style\">', '0', '19', '1', NOW()),
			(NULL, 'section1', '-*', '<h1>', '0', '13', '1', NOW()),
			(NULL, 'section3', '*-', '<h3>', '0', '15', '1', NOW()),
			(NULL, 'section4', '*-', '<h4>', '0', '16', '1', NOW()),
			(NULL, 'section5', '*-', '<h5>', '0', '17', '1', NOW()),
			(NULL, 'section6', '*-', '<h6>', '0', '18', '1', NOW()),
			(NULL, 'paragraphesansretrait', '*-', '', '0', '20', '1', NOW()),
			(NULL, 'epigraphe', '*-', '', '0', '21', '1', NOW()),
			(NULL, 'section2', '-*', '<h2>', '0', '14', '1', NOW()),
			(NULL, 'pigraphe', '-*', '', '0', '22', '1', NOW()),
			(NULL, 'sparateur', '-*', '', '0', '23', '1', NOW()),
			(NULL, 'quotation', '-*', '<blockquote>', '0', '24', '1', NOW()),
			(NULL, 'terme', '-*', '', '0', '25', '1', NOW()),
			(NULL, 'definitiondeterme', '-*', '', '0', '26', '1', NOW()),
			(NULL, 'bibliographieannee', '-*', '', '0', '27', '1', NOW()),
			(NULL, 'bibliographieauteur', 'bibliographie', '', '0', '28', '1', NOW()),
			(NULL, 'bibliographiereference', 'bibliographie', '', '0', '29', '1', NOW()),
			(NULL, 'creditillustration,crditillustration,creditsillustration,crditsillustration', '-*', '', '0', '30', '1', NOW()),
			(NULL, 'remerciements', '-*', '', '0', '31', '1', NOW());\n";

		// textes

		if ($err = $this->__mysql_query_cmds("RENAME TABLE _PREFIXTABLE_textes TO _PREFIXTABLE_textes__oldME;\n")) {
				return $err;
		}
		$q = "CREATE TABLE _PREFIXTABLE_textes (
				identity int(10) unsigned default NULL,
				titre text,
				surtitre text,
				soustitre text,
				texte longtext,
				notesbaspage longtext,
				annexe text,
				bibliographie text,
				datepubli date default NULL,
				datepublipapier date default NULL,
				noticebiblio text,
				pagination tinytext,
				langue char(5) default NULL,
				prioritaire tinyint(4) default NULL,
				addendum text,
				ndlr text,
				commentaireinterne text,
				dedicace text,
				ocr tinyint(4) default NULL,
				documentcliquable tinyint(4) default 1,
				`resume` text,
				altertitre text,
				titreoeuvre text,
				noticebibliooeuvre text,
				datepublicationoeuvre tinytext,
				ndla text,
				icone tinytext,
				alterfichier tinytext,
				numerodocument double default NULL,
				notefin longtext,
				UNIQUE KEY identity (identity),
				KEY index_identity (identity)
			) _CHARSET_;\n";

		$q .= "INSERT INTO _PREFIXTABLE_textes (identity, titre, surtitre, soustitre, texte, notesbaspage, annexe, bibliographie, datepubli, datepublipapier, noticebiblio, pagination, langue, prioritaire, ndlr, commentaireinterne, resume, icone, alterfichier, notefin) SELECT identity, titre, surtitre, soustitre, texte, notebaspage, annexe, bibliographie, datepubli, datepublipapier, noticebiblio, pagination, langue, prioritaire, ndlr, commentaireinterne, resume, icone, alterfichier, notefin FROM _PREFIXTABLE_textes__oldME;\n";
		if(!empty($ids)) {
			$q .= "UPDATE _PREFIXTABLE_textes SET documentcliquable = 0 WHERE identity IN (".$ids.");\n";
		}
		if ($err = $this->__mysql_query_cmds($q)) {
				return $err;
		} else {
			unset($q);
		}
		unset($ids);
		// champ historique : transformé en ndlr
		if(!$result = mysql_query("select identite, historique from " . $GLOBALS['tp'] . "documents__old where historique != ''")) {
			return mysql_error();
		}
		while($res = mysql_fetch_array($result)) {
			$ids[] = $res['identite'];
			$historique[$res['identite']] = $res['historique'];
		}
		if(!empty($ids)) {
			$id=join(",",$ids);
			unset($result, $res, $ids);
			if(!$result = mysql_query("select identity, ndlr from " . $GLOBALS['tp'] . "textes where identity in ($id)")) {
				return mysql_error();
			}
			while($res = mysql_fetch_array($result)) {
				$ndlr = "";
				$ndlr = $historique[$res['identity']].$res['ndlr'];
				$ndlr = str_replace("'", "\'", $ndlr);
			
				$q .= "update " . $GLOBALS['tp'] . "textes set ndlr = '$ndlr' WHERE identity = '".$res['identity']."';\n";
			}

			if ($err = $this->__mysql_query_cmds($q)) {
					return $err;
			} else {
				unset($q);
			}
		}

		// publications
		if ($err = $this->__mysql_query_cmds("RENAME TABLE _PREFIXTABLE_publications TO _PREFIXTABLE_publications__oldME;\n")) {
				return $err;
		}
		
		$q = "CREATE TABLE _PREFIXTABLE_publications (
				identity int(10) unsigned default NULL,
				titre text,
				surtitre text,
				soustitre text,
				commentaireinterne text,
				prioritaire tinyint(4) default NULL,
				datepubli date default NULL,
				datepublipapier date default NULL,
				noticebiblio text,
				introduction text,
				ndlr text,
				historique text,
				periode tinytext,
				isbn tinytext,
				paraitre tinyint(4) default 0,
				integralite tinyint(4) default 1,
				numero tinytext,
				icone tinytext,
				langue varchar(5) default NULL,
				altertitre text,
				urlpublicationediteur text,
				descriptionouvrage text,
				erratum text,
				UNIQUE KEY identity (identity),
				KEY index_identity (identity)
			) _CHARSET_;\n";

		$q .= "INSERT INTO _PREFIXTABLE_publications (identity, titre, surtitre, soustitre, commentaireinterne, prioritaire, datepubli, datepublipapier, noticebiblio, introduction, ndlr, historique, icone, erratum) SELECT identity, titre, surtitre, soustitre, commentaireinterne, prioritaire, datepubli, datepublipapier, noticebiblio, introduction, ndlr, historique, icone, erratum FROM _PREFIXTABLE_publications__oldME;\n";

		if ($err = $this->__mysql_query_cmds($q)) {
				return $err;
		} else {
			unset($q);
		}

		// ajustement spécifique : champ icone de publication en 0.7 = image d'accroche document annexe en 0.8
		if(!$result = mysql_query('SELECT id, icone, titre, identifier, g_title, status FROM ' . $GLOBALS['tp'] . 'publications JOIN  ' . $GLOBALS['tp'] . 'entities ON (' . $GLOBALS['tp'] . 'entities.id = ' . $GLOBALS['tp'] . 'publications.identity) where icone != ""')) {
			return mysql_error();
		}			
		while($res = mysql_fetch_array($result)) {
			$id = $this->__insert_object('entities');
			$titre = str_replace("'", "\\'", $res['titre']);
			$identifier = str_replace("'", "\\'", $res['identifier']);
			$g_title = str_replace("'", "\\'", $res['g_title']);
			$query .= "INSERT INTO _PREFIXTABLE_fichiers (identity, titre, document) VALUES ('".$id."', '".$titre."', '".$res['icone']."');\n";
			$query .= "INSERT INTO _PREFIXTABLE_entities (id, idparent, idtype, identifier, g_title, rank, status) VALUES ('".$id."', '".$res['id']."', (select id from types where type = 'imageaccroche'), '".$identifier."', '".$g_title."', 1, '".$res['status']."');\n";
		}

		// idem classe texte
		if(!$result = mysql_query('SELECT id, icone, titre, identifier, g_title, status FROM ' . $GLOBALS['tp'] . 'textes JOIN  ' . $GLOBALS['tp'] . 'entities ON (' . $GLOBALS['tp'] . 'entities.id = ' . $GLOBALS['tp'] . 'textes.identity) where icone != ""')) {
			return mysql_error();
		}			
		while($res = mysql_fetch_array($result)) {
			$id = $this->__insert_object('entities');
			$titre = str_replace("'", "\\'", $res['titre']);
			$identifier = str_replace("'", "\\'", $res['identifier']);
			$g_title = str_replace("'", "\\'", $res['g_title']);
			$query .= "INSERT INTO _PREFIXTABLE_fichiers (identity, titre, document) VALUES ('".$id."', '".$titre."', '".$res['icone']."');\n";
			$query .= "INSERT INTO _PREFIXTABLE_entities (id, idparent, idtype, identifier, g_title, rank, status) VALUES ('".$id."', '".$res['id']."', (select id from types where type = 'imageaccroche'), '".$identifier."', '".$g_title."', 1, '".$res['status']."');\n";
		}

		// tablefieldgroups
		$query .= "DELETE FROM _PREFIXTABLE_tablefieldgroups;\n
			INSERT INTO _PREFIXTABLE_tablefieldgroups (id, name, class, title, altertitle, comment, status, rank, upd) VALUES 
				('1', 'grtitre', 'textes', 'Titres', '', '', '1', '1', NOW()),
				('2', 'grtexte', 'textes', 'Texte', '', '', '1', '3', NOW()),
				('3', 'grmeta', 'textes', 'Métadonnées', '', '', '1', '4', NOW()),
				('4', 'graddenda', 'textes', 'Addenda', '', '', '1', '5', NOW()),
				('5', 'grtitre', 'liens', 'Titre', '', '', '1', '5', NOW()),
				('6', 'grsite', 'liens', 'Définition du site', '', '', '1', '6', NOW()),
				('7', 'grtitre', 'fichiers', 'Titre', '', '', '1', '7', NOW()),
				('8', 'grmultimedia', 'fichiers', 'Définition', '', '', '1', '8', NOW()),
				('9', 'grresumes', 'textes', 'Résumés', '', '', '1', '2', NOW()),
				('10', 'grtitre', 'publications', 'Groupe de titre', '', '', '32', '1', NOW()),
				('11', 'grgestion', 'publications', 'Gestion des publications', '', '', '1', '4', NOW()),
				('12', 'grmetadonnees', 'publications', 'Groupe des métadonnées', '', '', '32', '3', NOW()),
				('13', 'graddenda', 'publications', 'Groupe des addenda', '', '', '32', '2', NOW()),
				('14', 'grpersonnes', 'textes', 'Auteurs', '', '', '1', '7', NOW()),
				('15', 'grindex', 'textes', 'Index', '', '', '1', '6', NOW()),
				('16', 'grgestion', 'textes', 'Gestion du document', '', '', '1', '9', NOW()),
				('17', 'grrecension', 'textes', 'Oeuvre commentée (si ce document est un compte-rendu d\'oeuvre ou d\'ouvrage...)', '', '', '1', '8', NOW()),
				('18', 'grtitre', 'textessimples', 'Titre', '', '', '1', '10', NOW()),
				('19', 'grtexte', 'textessimples', 'Texte', '', '', '1', '11', NOW()),
				('24', 'grdroits', 'fichiers', 'Droits', '', '', '32', '16', NOW()),
				('25', 'grauteurs', 'liens', 'Auteurs', '', '', '32', '17', NOW()),
				('26', 'grauteurs', 'textessimples', 'Auteurs', '', '', '32', '18', NOW()),
				('28', 'grtitre', 'individus', 'Titre', '', '', '1', '20', NOW()),
				('30', 'grdescription', 'individus', 'Description', '', '', '1', '21', NOW());\n";

		// tablefields
		$q = "DELETE FROM _PREFIXTABLE_tablefields;\n
			INSERT INTO _PREFIXTABLE_tablefields (id, name, idgroup, class, title, altertitle, style, type, g_name, cond, defaultvalue, processing, allowedtags, gui_user_complexity, filtering, edition, editionparams, weight, comment, status, rank, upd) VALUES 
		(NULL, 'titre', '1', 'textes', 'Titre du document', '', 'title, titre, titleuser, heading', 'text', 'dc.title', '+', 'Document sans titre', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;Lien;Appel de Note', '16', '', 'editable', '', '8', '', '32', '3', NOW()),
		(NULL, 'surtitre', '1', 'textes', 'Surtitre du document', '', 'surtitre', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;Lien;Appel de Note', '32', '', 'importable', '', '8', '', '32', '2', NOW()),
		(NULL, 'soustitre', '1', 'textes', 'Sous-titre du document', '', 'subtitle, soustitre', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;Lien;Appel de Note', '32', '', 'editable', '', '8', '', '32', '5', NOW()),
		(NULL, 'texte', '2', 'textes', 'Texte du document', '', 'texte, standard, normal, textbody', 'longtext', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note', '16', '', 'display', '', '4', '', '32', '1', NOW()),
		(NULL, 'notesbaspage', '2', 'textes', 'Notes de bas de page', '', 'notebaspage, footnote, footnotetext', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien', '32', '', 'importable', '', '4', '', '32', '2', NOW()),
		(NULL, 'annexe', '2', 'textes', 'Annexes du document', '', 'annexe', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note', '32', '', 'importable', '', '4', '', '32', '4', NOW()),
		(NULL, 'bibliographie', '2', 'textes', 'Bibliographie du document', '', 'bibliographie', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note', '32', '', 'importable', '', '4', '', '32', '5', NOW()),
		(NULL, 'datepubli', '3', 'textes', 'Date de la publication électronique', '', 'datepubli', 'date', 'dc.date', '*', 'today', '', '', '16', '', 'editable', '', '0', '', '32', '1', NOW()),
		(NULL, 'datepublipapier', '3', 'textes', 'Date de la publication sur papier', '', 'datepublipapier', 'date', '', '*', '', '', '', '32', '', 'editable', '', '0', '', '32', '2', NOW()),
		(NULL, 'noticebiblio', '3', 'textes', 'Notice bibliographique du document', '', 'noticebiblio', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special', '64', '', 'importable', '', '0', '', '32', '3', NOW()),
		(NULL, 'pagination', '3', 'textes', 'Pagination du document sur le papier', '', 'pagination', 'tinytext', '', '*', '', '', '', '64', '', 'editable', '', '0', '', '32', '4', NOW()),
		(NULL, 'editeurscientifique', '14', 'textes', 'Éditeur scientifique', '', '', 'persons', '', '', '', '', '', '64', '', 'editable', '', '0', '', '1', '109', NOW()),
		(NULL, 'langue', '3', 'textes', 'Langue du document', '', 'langue', 'lang', 'dc.language', '*', 'fr', '', '', '32', '', 'editable', '', '0', '', '1', '6', NOW()),
		(NULL, 'prioritaire', '16', 'textes', 'Document prioritaire', '', '', 'boolean', '', '*', '', '', '', '64', '', 'editable', '', '0', '', '32', '7', NOW()),
		(NULL, 'addendum', '4', 'textes', 'Addendum', '', 'erratum, addendum', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note', '64', '', 'importable', '', '2', '', '32', '3', NOW()),
		(NULL, 'ndlr', '4', 'textes', 'Note de la rédaction', '', 'ndlr', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note', '64', '', 'importable', '', '2', '', '32', '1', NOW()),
		(NULL, 'commentaireinterne', '16', 'textes', 'Commentaire interne sur le document', '', 'commentaire', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien', '64', '', 'importable', '', '0', '', '32', '4', NOW()),
		(NULL, 'dedicace', '4', 'textes', 'Dédicace', '', 'dedicace', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note', '64', '', 'importable', '', '2', '', '32', '4', NOW()),
		(NULL, 'ocr', '16', 'textes', 'Document issu d\'une numérisation dite OCR', '', '', 'boolean', '', '*', '', '', '', '64', '', 'importable', '', '0', '', '32', '9', NOW()),
		(NULL, 'documentcliquable', '16', 'textes', 'Document cliquable dans les sommaires', '', '', 'boolean', '', '*', 'true', '', '', '64', '', 'editable', '', '0', '', '32', '10', NOW()),
		(NULL, 'nom', '0', 'indexes', 'Dénomination de l\'entrée d\'index', '', '', 'text', 'index key', '*', 'Tous droits réservés', '', '', '16', '', 'editable', '', '4', '', '32', '25', NOW()),
		(NULL, 'motsclesfr', '15', 'textes', 'Index de mots-clés', '', '', 'entries', '', '', '', '', '', '64', '', 'editable', '', '0', '', '32', '2', NOW()),
		(NULL, 'definition', '0', 'indexes', 'Définition', '', '', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien', '16', '', 'fckeditor', 'Basic', '1', '', '32', '27', NOW()),
		(NULL, 'nomfamille', '0', 'auteurs', 'Nom de famille', '', '', 'tinytext', 'familyname', '*', '', '', '', '32', '', 'editable', '', '4', '', '32', '28', NOW()),
		(NULL, 'prenom', '0', 'auteurs', 'Prénom', '', '', 'tinytext', 'firstname', '*', '', '', '', '32', '', 'editable', '', '4', '', '32', '29', NOW()),
		(NULL, 'prefix', '0', 'entities_auteurs', 'Préfixe', '', 'prefixe, .prefixe', 'tinytext', '', '*', '', '', '', '64', '', 'editable', '', '0', '', '1', '2', NOW()),
		(NULL, 'affiliation', '0', 'entities_auteurs', 'Affiliation', '', 'affiliation, .affiliation', 'tinytext', '', '*', '', '', '', '32', '', 'editable', '', '4', '', '1', '3', NOW()),
		(NULL, 'fonction', '0', 'entities_auteurs', 'Fonction', '', 'fonction, .fonction', 'tinytext', '', '*', '', '', '', '32', '', 'editable', '', '0', '', '1', '4', NOW()),
		(NULL, 'description', '0', 'entities_auteurs', 'Description de l\'auteur', '', 'descriptionauteur', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;Lien', '16', '', 'fckeditor', '5', '4', '', '1', '1', NOW()),
		(NULL, 'courriel', '0', 'entities_auteurs', 'Courriel', '', 'courriel, .courriel', 'email', '', '*', '', '', '', '32', '', 'editable', '', '4', '', '1', '5', NOW()),
		(NULL, 'auteur', '14', 'textes', 'Auteur du document', '', '', 'persons', '', '', '', '', '', '64', '', 'editable', '', '0', '', '32', '11', NOW()),
		(NULL, 'traducteur', '14', 'textes', 'Traducteur du document', '', '', 'persons', '', '', '', '', '', '64', '', 'editable', '', '0', '', '32', '12', NOW()),
		(NULL, 'alias', '16', 'textes', 'Alias', '', '', 'entities', '', '*', '', '', '', '64', '', 'editable', '', '0', '', '1', '119', NOW()),
		(NULL, 'date', '19', 'textessimples', 'Date de publication en ligne', '', '', 'datetime', '', '*', 'now', '', '', '16', '', 'editable', '', '0', '', '1', '100', NOW()),
		(NULL, 'url', '19', 'textessimples', 'Lien', '', '', 'url', '', '*', '', '', '', '16', '', 'editable', '', '2', '', '1', '99', NOW()),
		(NULL, 'licence', '24', 'fichiers', 'Licence', '', '', 'entries', '', '', '', '', '', '64', '', 'editable', '', '0', '', '1', '118', NOW()),
		(NULL, 'titre', '5', 'liens', 'Titre du site', '', '', 'text', 'dc.title', '*', 'Site sans titre', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;Appel de Note', '16', '', 'editable', '', '8', '', '32', '43', NOW()),
		(NULL, 'url', '6', 'liens', 'URL du site', '', '', 'url', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '32', '1', NOW()),
		(NULL, 'urlfil', '6', 'liens', 'URL du fil de syndication du site', '', '', 'url', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '32', '4', NOW()),
		(NULL, 'texte', '6', 'liens', 'Description du site', '', '', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note', '16', '', 'fckeditor', 'Simple', '2', '', '32', '2', NOW()),
		(NULL, 'titre', '7', 'fichiers', 'Titre', '', '', 'text', 'dc.title', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;Appel de Note', '16', '', 'editable', '', '4', '', '32', '47', NOW()),
		(NULL, 'document', '8', 'fichiers', 'Document', '', '', 'file', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '32', '1', NOW()),
		(NULL, 'altertitre', '1', 'textes', 'Titre alternatif du document (dans une autre langue)', '', 'titretraduitfr:fr,titretraduiten:en,titretraduites:es,titretraduitpt:pt,titretraduitit:it,titretraduitde:de,titretraduitru:ru,titleen:en,titoloit:it,titelde:de,tituloes:es', 'mltext', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;Lien;Appel de Note', '16', '', 'editable', '', '8', '', '32', '4', NOW()),
		(NULL, 'resume', '9', 'textes', 'Résumé', '', 'rsum,resume:fr,resumefr:fr,abstract:en,resumeen:en,extracto:es,resumen:es, resumees:es,resumo:pt,resumept:pt,riassunto:it,resumeit:it,zusammenfassung:de,resumede:de,resumeru:ru', 'mltext', 'dc.description', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note', '16', '', 'display', '5', '8', '', '32', '50', NOW()),
		(NULL, 'titre', '10', 'publications', 'Titre de la publication', '', 'title, titre, titleuser, heading', 'text', 'dc.title', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;Appel de Note', '16', '', 'editable', '', '8', '', '32', '2', NOW()),
		(NULL, 'surtitre', '10', 'publications', 'Surtitre de la publication', '', 'surtitre', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;Appel de Note', '16', '', 'importable', '', '8', '', '32', '1', NOW()),
		(NULL, 'soustitre', '10', 'publications', 'Sous-titre de la publication', '', 'soustitre', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;Appel de Note', '16', '', 'editable', '', '8', '', '32', '3', NOW()),
		(NULL, 'commentaireinterne', '11', 'publications', 'Commentaire interne sur la publication', '', '', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien', '64', '', 'editable', '4', '0', '', '32', '54', NOW()),
		(NULL, 'prioritaire', '11', 'publications', 'Cette publication est-elle prioritaire ?', '', '', 'boolean', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '32', '55', NOW()),
		(NULL, 'datepubli', '12', 'publications', 'Date de publication électronique', '', '', 'date', 'dc.date', '*', 'today', '', '', '16', '', 'editable', '', '0', '', '32', '2', NOW()),
		(NULL, 'datepublipapier', '12', 'publications', 'Date de publication papier', '', '', 'date', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '32', '3', NOW()),
		(NULL, 'noticebiblio', '12', 'publications', 'Notice bibliographique décrivant la publication', '', '', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special', '64', '', 'importable', '', '0', '', '32', '4', NOW()),
		(NULL, 'introduction', '13', 'publications', 'Introduction de la publication', '<r2r:ml lang=\"fr\">Introduction de la publication</r2r:ml>', 'texte, standard, normal', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note', '16', '', 'fckeditor', 'Simple,550,400', '8', '', '32', '60', NOW()),
		(NULL, 'geographie', '15', 'textes', 'Index géographique', '', '', 'entries', '', '', '', '', '', '64', '', 'editable', '', '0', '', '1', '110', NOW()),
		(NULL, 'chrono', '15', 'textes', 'Index chronologique', '', '', 'entries', '', '', '', '', '', '64', '', 'editable', '', '0', '', '1', '111', NOW()),
		(NULL, 'ndlr', '13', 'publications', 'Note de la rédaction au sujet de la publication', '', '', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note', '64', '', 'fckeditor', '', '2', '', '32', '62', NOW()),
		(NULL, 'historique', '13', 'publications', 'Historique de la publication', '', '', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note', '64', '', 'importable', '', '0', '', '32', '63', NOW()),
		(NULL, 'periode', '12', 'publications', 'Période de publication', '', '', 'tinytext', '', '*', '', '', '', '16', '', 'importable', '', '0', '', '1', '5', NOW()),
		(NULL, 'isbn', '12', 'publications', 'ISBN', '', '', 'tinytext', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '1', '7', NOW()),
		(NULL, 'paraitre', '11', 'publications', 'Cette publication est-elle à paraitre ?', '', '', 'boolean', '', '*', '', '', '', '32', '', 'editable', '', '0', '', '32', '66', NOW()),
		(NULL, 'integralite', '11', 'publications', 'Cette publication en ligne est-elle intégrale ?', '', '', 'boolean', '', '*', '', '', '', '32', '', 'editable', '', '0', '', '32', '67', NOW()),
		(NULL, 'numero', '12', 'publications', 'Numéro de la publication', '', '', 'tinytext', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '32', '6', NOW()),
		(NULL, 'motsclesen', '15', 'textes', 'Keywords index', '', '', 'entries', '', '', '', '', '', '64', '', 'editable', '', '0', '', '32', '3', NOW()),
		(NULL, 'role', '0', 'entities_auteurs', 'Role dans l\'élaboration du document', '', 'role,.role', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special', '64', '', 'editable', '', '0', '', '1', '7', NOW()),
		(NULL, 'email', '30', 'individus', 'Courriel', '', '', 'email', '', '*', '', '', '', '16', '', 'editable', '', '4', '', '1', '3', NOW()),
		(NULL, 'siteweb', '30', 'individus', 'Site web', '', '', 'url', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '1', '4', NOW()),
		(NULL, 'description', '30', 'individus', 'Description', '', '', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note', '16', '', 'fckeditor', 'Simple', '4', '', '1', '2', NOW()),
		(NULL, 'titreoeuvre', '17', 'textes', 'Titre de l\'oeuvre commentée', '', 'titreoeuvre', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;Appel de Note', '64', '', 'display', '', '4', '', '32', '2', NOW()),
		(NULL, 'noticebibliooeuvre', '17', 'textes', 'Notice bibliographique de l\'oeuvre commentée', '', 'noticebibliooeuvre', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Appel de Note', '64', '', 'display', '', '4', '', '32', '1', NOW()),
		(NULL, 'datepublicationoeuvre', '17', 'textes', 'Date de publication de l\'oeuvre commentée', '', 'datepublioeuvre', 'tinytext', '', '*', '', '', '', '64', '', 'display', '', '4', '', '32', '70', NOW()),
		(NULL, 'auteuroeuvre', '17', 'textes', 'Auteur de l\'oeuvre commentée', '', '', 'persons', '', '', '', '', '', '64', '', 'editable', '', '0', '', '32', '71', NOW()),
		(NULL, 'titre', '18', 'textessimples', 'Titre', '', '', 'tinytext', 'dc.title', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note', '16', '', 'editable', '', '4', '', '32', '72', NOW()),
		(NULL, 'texte', '19', 'textessimples', 'Texte', '', '', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note', '16', '', 'fckeditor', 'Simple', '4', '', '1', '73', NOW()),
		(NULL, 'ndla', '4', 'textes', 'Note de l\'auteur', '', 'ndla', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note', '64', '', 'importable', '', '2', '', '32', '2', NOW()),
		(NULL, 'icone', '12', 'publications', 'Icône de la publication', '', '', 'image', '', '*', '', '', '', '16', '', 'none', '', '0', '', '32', '1', NOW()),
		(NULL, 'icone', '3', 'textes', 'Icône du document', '', '', 'image', '', '*', '', '', '', '64', '', 'none', '', '0', '', '32', '88', NOW()),
		(NULL, 'description', '8', 'fichiers', 'Description', '', '', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note', '16', '', 'fckeditor', 'Simple', '4', '', '32', '2', NOW()),
		(NULL, 'alterfichier', '2', 'textes', 'Texte au format PDF', '', '', 'file', '', '*', '', '', '', '32', '', 'editable', '', '0', '', '32', '6', NOW()),
		(NULL, 'langue', '12', 'publications', 'Langue de la publication', '', '', 'lang', 'dc.language', '*', 'fr', '', '', '64', '', 'editable', '', '0', '', '32', '8', NOW()),
		(NULL, 'auteur', '24', 'fichiers', 'Auteur', '', '', 'persons', '', '', '', '', '', '64', '', 'editable', '', '0', '', '32', '91', NOW()),
		(NULL, 'auteur', '25', 'liens', 'Auteur de la notice décrivant ce site', '', '', 'persons', '', '', '', '', '', '64', '', 'editable', '', '0', '', '32', '92', NOW()),
		(NULL, 'capturedecran', '6', 'liens', 'Capture d\'écran du site', '', '', 'image', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '32', '3', NOW()),
		(NULL, 'auteur', '26', 'textessimples', 'Auteur', '', '', 'persons', '', '', '', '', '', '64', '', 'editable', '', '0', '', '32', '93', NOW()),
		(NULL, 'numerodocument', '1', 'textes', 'Numéro du document', '', 'numerodocument,numrodudocument', 'number', '', '*', '', '', '', '64', '', 'editable', '', '0', '', '32', '1', NOW()),
		(NULL, 'nom', '28', 'individus', 'Nom', '', '', 'tinytext', 'dc.title', '*', '', '', '', '16', '', 'editable', '', '4', '', '1', '1', NOW()),
		(NULL, 'prenom', '28', 'individus', 'Prénom', '', '', 'tinytext', '', '*', '', '', '', '16', '', 'editable', '', '4', '', '1', '2', NOW()),
		(NULL, 'accroche', '28', 'individus', 'Accroche', '', '', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note', '16', '', 'fckeditor', 'Simple', '4', '', '1', '3', NOW()),
		(NULL, 'adresse', '30', 'individus', 'Adresse', '', '', 'text', '', '*', '', '', '', '16', '', 'editable', '3', '4', '', '1', '102', NOW()),
		(NULL, 'telephone', '30', 'individus', 'Téléphone', '', '', 'tinytext', '', '*', '', '', '', '16', '', 'editable', '', '4', '', '1', '103', NOW()),
		(NULL, 'photographie', '28', 'individus', 'Photographie', '', '', 'image', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '1', '104', NOW()),
		(NULL, 'vignette', '8', 'fichiers', 'Vignette', '', '', 'image', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '32', '3', NOW()),
		(NULL, 'directeurdelapublication', '12', 'publications', 'Directeur de la publication', '', '', 'persons', '', '', '', '', '', '64', '', 'editable', '', '0', '', '1', '10', NOW()),
		(NULL, 'legende', '8', 'fichiers', 'Légende', '', '', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien', '16', '', 'fckeditor', 'Basic', '4', '', '1', '4', NOW()),
		(NULL, 'credits', '24', 'fichiers', 'Crédits', '', '', 'tinytext', '', '*', '', '', '', '16', '', 'editable', '', '4', '', '1', '108', NOW()),
		(NULL, 'theme', '15', 'textes', 'Index thématique', '', '', 'entries', '', '', '', '', '', '64', '', 'editable', '', '0', '', '1', '112', NOW()),
		(NULL, 'licence', '12', 'publications', 'Licence portant sur la publication', '', '', 'entries', '', '', '', '', '', '64', '', 'editable', '', '0', '', '1', '9', NOW()),
		(NULL, 'nom', '0', 'indexavances', 'Dénomination de l\'entrée d\'index', '', '', 'tinytext', 'index key', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block', '16', '', 'editable', '', '4', '', '1', '113', NOW()),
		(NULL, 'description', '0', 'indexavances', 'Description de l\'entrée d\'index', '', '', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note', '16', '', 'fckeditor', 'Basic', '4', '', '1', '114', NOW()),
		(NULL, 'url', '0', 'indexavances', 'URL', '', '', 'url', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '1', '115', NOW()),
		(NULL, 'icone', '0', 'indexavances', 'Icône', '', '', 'image', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '1', '116', NOW()),
		(NULL, 'licence', '3', 'textes', 'Licence portant sur le document', '', '', 'entries', '', '', '', '', '', '64', '', 'editable', '', '0', '', '1', '117', NOW()),
		(NULL, 'notefin', '2', 'textes', 'Notes de fin de document', '', 'notefin', 'text', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien', '32', '', 'importable', '', '4', '', '32', '3', NOW()),
		(NULL, 'altertitre', '10', 'publications', 'Titre alternatif de la publication (dans une autre langue)', '', 'titretraduitfr:fr,titretraduiten:en,titretraduites:es,titretraduitpt:pt,titretraduitit:it,titretraduitde:de,titretraduitru:ru,titleen:en', 'mltext', '', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Appel de Note', '32', '', 'editable', '', '4', '', '1', '120', NOW()),
		(NULL, 'motscleses', '15', 'textes', 'Palabras claves', '', '', 'entries', '', '', '', '', '', '64', '', 'editable', '', '0', '', '1', '121', NOW()),
		(NULL, 'motsclesde', '15', 'textes', 'Schlagworter', '', '', 'entries', '', '', '', '', '', '64', '', 'editable', '', '0', '', '1', '122', NOW()),
		(NULL, 'urlpublicationediteur', '13', 'publications', 'Voir sur le site de l\'éditeur', '', '', 'url', '', '*', '', '', '', '32', '', 'editable', '', '0', '', '1', '123', NOW()),
		(NULL, 'nombremaxitems', '6', 'liens', 'Nombre maximum d\'items du flux', '', '', 'int', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '32', '124', NOW()),
		(NULL, 'descriptionouvrage', '12', 'publications', 'Description physique de l\'ouvrage', '', '', 'text', '', '*', '', '', '', '64', '', 'editable', '', '0', '', '32', '125', NOW()),
		(NULL, 'erratum', '11', 'publications', 'Erratum', '', '', 'text', '', '*', '', '', '', '64', '', 'editable', '', '0', '', '32', '126', NOW()),
		(NULL, 'site', '0', 'entities_auteurs', 'Site', '', 'site, .site', 'url', '', '*', '', '', '', '32', '', 'editable', '', '4', '', '1', '6', NOW());\n";

		if ($err = $this->__mysql_query_cmds($q)) {
				return $err;
		} else {
			unset($q);
		}
		// suppression du type 'documentannexe-lienfichier' : on maj dans la table entities le type de l'entrée et on supprime le type
		//$query .= "UPDATE _PREFIXTABLE_entities SET idtype = (SELECT id FROM _PREFIXTABLE_types WHERE type = 'fichierannexe') WHERE idtype = (SELECT id from _PREFIXTABLE_types WHERE type = 'documentannexe-lienfichier');\n";
		//$query .= "DELETE FROM _PREFIXTABLE_types WHERE type = 'documentannexe-lienfichier';\n";


		if ($err = $this->__mysql_query_cmds($query)) {
				return $err;
		} else {
			unset($query);
			// index
			// langue
			$i = 1;
			if(!$result = mysql_query("SELECT id, langue, nom, idtype FROM $GLOBALS[tp]entrees__old;")) {
				return mysql_error();
			}
			while ($row = mysql_fetch_assoc($result)) {
				unset($q);
				if(!empty($row['langue'])) {
					if($row['langue'] == 'fr') {
						$q = "SELECT id FROM ".$GLOBALS['tp']."entrytypes WHERE type = 'motsclesfr'";
					} elseif($row['langue'] == 'en') {
						$q = "SELECT id FROM ".$GLOBALS['tp']."entrytypes WHERE type = 'motsclesen'";
					} elseif($row['langue'] == 'de') {
						$q = "SELECT id FROM ".$GLOBALS['tp']."entrytypes WHERE type = 'motsclesde'";
					} elseif($row['langue'] == 'es') {
						$q = "SELECT id FROM ".$GLOBALS['tp']."entrytypes WHERE type = 'motscleses'";
					} else {
						$q = "SELECT id FROM ".$GLOBALS['tp']."entrytypes WHERE type = 'motsclesfr'";
					}
				}
				if(!empty($q) && !$resu = mysql_query($q)) {
					return $q."<br>".mysql_error();
				}
				while ($rows = mysql_fetch_array($resu)) {
					$query .= "UPDATE _PREFIXTABLE_entries SET idtype = '".$rows['id']."', sortkey = \"".makeSortKey($row['nom'])."\" WHERE id = " . $row['id'] . ";\n";
				}
				$i++;
			}
			if (!empty($query) && $err = $this->__mysql_query_cmds($query)) {
					return $err;
			} else {
				unset($query, $q);
			}
			mysql_free_result($result);

			// maj identifier pour affichage correct des champs auteur lors de l'édition d'une entité
			// sans ça, seul les champs nom/prénom sont affichés
			if(!$result = mysql_query("SELECT id, g_title, identifier FROM $GLOBALS[tp]entities")) {
				return mysql_error();
			}
			while($res = mysql_fetch_array($result)) {
				if($res['identifier'] == "") {
					$identifier = preg_replace(array("/\W+/", "/-+$/"), array('-', ''), makeSortKey(strip_tags($res['g_title'])));
					$q .= "UPDATE _PREFIXTABLE_entities SET identifier = '".$identifier."' WHERE id = '".$res['id']."';\n";
				}
			}
			if (!empty($q) && $err = $this->__mysql_query_cmds($q)) {
				return $err;
			} else {
				unset($q);
			}
			mysql_free_result($result);
			return "Ok";
		}

	}


	/**
	 * Execute une ou plusieurs commandes Mysql
	 */

	private function __mysql_query_cmds($cmds, $table = '') {
		$sql = str_replace('_PREFIXTABLE_', $GLOBALS['tp'], $cmds);
		$sql = str_replace('#_TP_', $GLOBALS['tp'], $sql);

		//$charset
		$sql = str_replace('_CHARSET_', '', $sql);
		
		if (!$sql) {
			$err = 'Pb pour executer la commande suivante : ' . $cmds;
			return $err;
		}

		$request = preg_split ("/;(\n|$)/", $sql);

		if ($table) { // select the commands operating on the table  $table
			$request = preg_grep("/(REPLACE|INSERT)\s+INTO\s+$GLOBALS[tp]$table\s/i",$request);
		}
		if (!$request) {
			$err = 'Pb pour executer la commande suivante : ' . $cmds;
			return $err;
		}

		foreach ($request as $cmd) {
			// on vérifie bien que la commande a executer ne soit pas nul ni contenant que des caracteres d'espacement
			if (!empty($cmd) && $cmd != "" && preg_match("`^\s*$`", $cmd) == 0) {
				// détection stricte de l'utf8 dans la requete via le 'true'
				if(mb_detect_encoding($cmd, "auto", TRUE) != "UTF-8") {	
					$cmd = mb_convert_encoding($cmd, "UTF-8");
				}
				$this->requetes .= "\n".$cmd;
				if (!mysql_query($cmd)) {
					$this->mysql_errors = $cmd."\nL'erreur retournee est : ".mysql_error()."\n";
      				}
    			}
  		}
		if(!empty($this->mysql_errors)) {
			return $this->mysql_errors;
		}
		return false;
	}

	/**
	 * Gère la migration des documentannexes dans les tables respectives
	 */
	public function cp_docs07_to_08()
	{
		// on récupère tous les documents annexes dans la base
		$query_select = "SELECT 
					".$GLOBALS['tp']."entites__old.*,
					".$GLOBALS['tp']."documents__old.*,
					".$GLOBALS['tp']."types__old.type,
					".$GLOBALS['tp']."types__old.classe
				FROM 
					".$GLOBALS['tp']."types__old,
					".$GLOBALS['tp']."entites__old, 
					".$GLOBALS['tp']."documents__old 
				WHERE 
					type LIKE 'documentannexe-%'
					AND ".$GLOBALS['tp']."documents__old.identite=".$GLOBALS['tp']."entites__old.id 
					AND ".$GLOBALS['tp']."entites__old.idtype=".$GLOBALS['tp']."types__old.id;";
		if(!$req = mysql_query($query_select)) {
			return mysql_error();
		}
		while($res = mysql_fetch_array($req)) {
			/* on tri le résultat :
			* - lien vers fac-similé devient alterfichier
			* - lien vers fichier devient une entrée de la classe fichier
			* - Le reste on le considère comme des liens normaux
			*/
			if($res['type'] != "documentannexe-lienfacsimile" && $res['type'] != "documentannexe-lienfichier") {
				$query .= "INSERT INTO ".$GLOBALS['tp']."liens (identity, titre, url) VALUES ('".$res['id']."', \"".addslashes($res['titre'])."\", \"".$res['lien']."\");\n";
				$query .= "UPDATE ".$GLOBALS['tp']."entities SET idtype = (SELECT id FROM ".$GLOBALS['tp']."types WHERE type = 'documentannexe-liendocument') WHERE id = '".$res['id']."';\n";
			} elseif($res['type'] == "documentannexe-lienfacsimile") {
				$query .= "UPDATE ".$GLOBALS['tp']."documents SET alterfichier = \"".$res['lien']."\" WHERE identity = '".$res['idparent']."';\n";
				$query .= "DELETE FROM ".$GLOBALS['tp']."entities WHERE id = '".$res['id']."';\n";
			} elseif($res['type'] == "documentannexe-lienfichier") {
				$query .= "INSERT INTO ".$GLOBALS['tp']."fichiers (identity, titre, document) VALUES ('".$res['id']."', \"".addslashes($res['titre'])."\", \"".$res['lien']."\");\n";
				$query .= "UPDATE ".$GLOBALS['tp']."entities SET idtype = (SELECT id FROM ".$GLOBALS['tp']."types WHERE type = 'documentannexe-lienfichier') WHERE id = '".$res['id']."';\n";
			}
		}
		//$query .= "DELETE FROM _PREFIXTABLE_types WHERE type LIKE 'documentannexe-%';\n";
		if (!empty($query) && $err = $this->__mysql_query_cmds($query)) {
				return $err;
		} else {
			return "Ok";
		}
	}

	/**
	 * Dump de la base avant modifs
	 */	
	public function dump_before_changes()
	{
		global $home, $site, $zipcmd; 
		require($home."backupfunc.php");
		
		$outfile="site-$site.sql";
		$tmpdir=tmpdir();
		$GLOBALS['uselodelprefix']=true;
		mysql_dump($GLOBALS['currentdb'],$GLOBALS['lodelsitetables'],$tmpdir."/".$outfile);
		# verifie que le fichier n'est pas vide
		if (filesize($tmpdir."/".$outfile)<=0) return "ERROR: mysql_dump failed";
		
		// tar les sites et ajoute la base
		$archivetmp=tempnam($tmpdir,"lodeldump_").".zip";
		$archivefilename="site-$site-".date("dmy").".zip";
		if ($zipcmd && $zipcmd!="pclzip") {
			system($zipcmd." -q $archivetmp -j $tmpdir/$outfile");
		} else { // pclzip
			require($home."pclzip.lib.php");
			$archive=new PclZip ($archivetmp);
			$archive->create($tmpdir."/".$outfile,PCLZIP_OPT_REMOVE_ALL_PATH);
		} // end of pclzip option
		
		if (!file_exists($archivetmp)) return "ERROR: the zip command or library does not produce any output";
		@unlink($tmpdir."/".$outfile); // delete the sql file

		if (operation("download",$archivetmp,$archivefilename,$context)) 
			return "Ok";		
	}

	/**
	 * Dump de la base après modifs
	 */	
	public function dump_after_changes_to08()
	{
		global $home, $site, $zipcmd; 
		require($home."backupfunc.php");
		
		$outfile="site-$site.sql";
		$tmpdir=tmpdir();

		$tables = $this->get_tables();

		mysql_dump($GLOBALS['currentdb'],$tables,$tmpdir."/".$outfile);
		# verifie que le fichier n'est pas vide
		if (filesize($tmpdir."/".$outfile)<=0) return "ERROR: mysql_dump failed";
		
		// tar les sites et ajoute la base
		$archivetmp=tempnam($tmpdir,"lodeldump_").".zip";
		$archivefilename="site-$site-".date("dmy").".zip";
		if ($zipcmd && $zipcmd!="pclzip") {
			system($zipcmd." -q $archivetmp -j $tmpdir/$outfile");
		} else { // pclzip
			require($home."pclzip.lib.php");
			$archive=new PclZip ($archivetmp);
			$archive->create($tmpdir."/".$outfile,PCLZIP_OPT_REMOVE_ALL_PATH);
		} // end of pclzip option
		
		if (!file_exists($archivetmp)) return "ERROR: the zip command or library does not produce any output";
		@unlink($tmpdir."/".$outfile); // delete the sql file

		if (operation("download",$archivetmp,$archivefilename,$context)) 
			return "Ok";		
	}

	/**
	 * Dump des modifs effectuées sur la base
	 */	
	public function dump_changes_to08()
	{
		global $home, $site, $zipcmd; 
		require($home."backupfunc.php");
		
		$outfile="site-$site-changesto08.sql";
		$tmpdir=tmpdir();
		$f = fopen($tmpdir."/".$outfile, "w");
		fwrite($f, $this->requetes);
		fclose($f);
		# verifie que le fichier n'est pas vide
		if (filesize($tmpdir."/".$outfile)<=0) return "ERROR: dumping changes failed";
		
		// tar les sites et ajoute la base
		$archivetmp=tempnam($tmpdir,"lodeldump_").".zip";
		$archivefilename="siteto08-$site-".date("dmy").".zip";
		if ($zipcmd && $zipcmd!="pclzip") {
			system($zipcmd." -q $archivetmp -j $tmpdir/$outfile");
		} else { // pclzip
			require($home."pclzip.lib.php");
			$archive=new PclZip ($archivetmp);
			$archive->create($tmpdir."/".$outfile,PCLZIP_OPT_REMOVE_ALL_PATH);
		} // end of pclzip option
		
		if (!file_exists($archivetmp)) return "ERROR: the zip command or library does not produce any output";

		if (operation("download",$archivetmp,$archivefilename,$context)) 
			return "Ok";		
	}

	/**
	* Copie les fichiers contenus dans $source vers $target
	* @param string $source 
	* @param string $target 
	*/	
	public function datas_copy( $source, $target )
	{
		if ( is_dir( $source ) )
		{
			if(!@mkdir( $target )) return "Command mkdir failed for repertory '".$target."'";
			
			$d = dir( $source );
			
			while ( FALSE !== ( $entry = $d->read() ) )
			{
				if ( $entry{0} == '.' )
				{
					continue;
				}
			
				$Entry = $source . '/' . $entry;
				if ( is_dir( $Entry ) )
				{
					$this->datas_copy( $Entry, $target . '/' . $entry );
					continue;
				}

				if(!@copy( $Entry, $target . '/' . $entry )) return "Error during copying file ".$entry;
			}
			
			$d->close();
		} else {
			if(!@copy( $source, $target)) return "Error during copying file ".$entry;
		}
		return "Ok";
	}

	public function update_tpl($target)
	{
/*
		// ce qu'on cherche à remplacer
		$lookfor = array("#NOTEBASPAGE",
				 "textes",
				 "documents",
				 "objets",
				 "entites.ordre",
				 "entites",
				 "champs",
				 "groupesdechamps",
				 "personnes",
				 "groupes",
				 "users_groupes",
				 "typespersonnes",
				 "typeentrees",
				 "taches",
				 "typeentites_typeentites",
				 "typeentites_typepersonnes",
				 "typeentites_typeentrees",
				 "entites_personnes",
				 "entites_entrees",
				 "WHERE=\"rep='",
				 "statut",
				 "identifiant",
				 "ordre",
				 "degres",
				 "identite",
				 "auteurs\.",
				 "entrees",
				 "entries\.",
				 "entrees\.id",
				 "directeur de publication",
				 "WHERE=\"ok\"",
				 "nomfamille",
				 "prenom",
				 "nom",
				 "identree",
				 "idpersonne",
				 "date",
				 "maj"
				 );
		// et ce qu'on met à remplacer
		$replace = array("#NOTESBASPAGE",
				"texts",
				"textes",
				"objects",
				"entities.rank",
				"entities",
				"tablefields",
				"tablefieldgroups",
				"auteurs",
				"usergroups",
				"users_usergroups",
				"persontypes",
				"entrytypes",
				"tasks",
				"entitytypes_entitytypes",
				"entitytypes_persontypes",
				"entitytypes_entrytypes",
				"entities_persons",
				"entities_entries",
				"WHERE=\"name='",
				"status",
				"identifier",
				"rank",
				"degree",
				"identity",
				"personnes.",
				"entries",
				"entrees.",
				"entries.id",
				"directeurdelapublication",
				"WHERE=\"status GT 0\"",
				"g_familyname",
				"g_firstname",
				"g_name",
				"identry",
				"idperson",
				"datepubli",
				"upd"
				);
		// variable de travail : on fait deux tours : le premier pour récupérer le nom de toutes les macros/fonctions présentes dans le répertoire source et target
		// le second pour travailler :)
/*		$i = 0; 
		// tableau des noms de macros/fonctions 0.7
		$funclist = array();
		// tableau des macros en double
		$funcToAdd = array();	
		// liste des fichiers macros de la 0.7 à ajouter dans les tpl de la 0.8
		$upMacroFile = '<USE MACROFILE="macros.html" />
				<USE MACROFILE="macros_admin.html" />
				<USE MACROFILE="macros_affichage.html" />
				<USE MACROFILE="macros_technique.html" />
				<USE MACROFILE="macros_images.html" />
				<USE MACROFILE="macros_navigation.html" />
				<USE MACROFILE="macros_presentation.html" />
				<USE MACROFILE="macros_site.html" />';

		// c'est parti on traite tous les templates et fichiers de macros contenus dans les répertoires tpl
		if (is_dir("tpl")) {
			while($i < 2) {
				if($i === 0) {
					$i++;
					if ($dh = opendir("tpl")) {
						while (($file = readdir($dh)) !== false) {
							unset($tmp, $defins);
							// est-ce bien un fichier de macros ? extension html obligatoire et 'macros' dans le nom
							if("html" === substr($file, -4, 4) && !is_link("tpl/".$file) && !preg_match("/oai/", $file)) {
								if(preg_match("`macros`i", $file)) {	
									$tmp = file_get_contents("tpl/".$file);
									preg_match_all("`<(DEFMACRO|DEFFUNC) NAME=\"([^\"]*)\"[^>]*>(.*)</(DEFMACRO|DEFFUNC)>`iUs", $tmp, $defins);
									// on récupère le nom des macros/fonctions de la 0.7
									$funclist = array_merge($funclist, $defins[2]);
								}
							}
						}
						closedir($dh);
					} else {
						return "ERROR : cannot open directory 'tpl'.";
					}
					
					$funclist = array_unique($funclist);

					if ($dh = opendir($target."/tpl")) {
						while (($file = readdir($dh)) !== false) {
							unset($tmp, $defins, $defin, $def);
							// est-ce bien un fichier de tpl/macros ? extension html obligatoire et/ou 'macros' dans le nom
							if("html" === substr($file, -4, 4) && !is_link($target."/tpl/".$file) && !preg_match("/oai/", $file)) {
								$tmp = file_get_contents($target."/tpl/".$file);
								if(preg_match_all("`<(DEFMACRO|DEFFUNC) NAME=\"([^\"]*)\"[^>]*>(.*)</(DEFMACRO|DEFFUNC)>`iUs", $tmp, $defins)) {
									$defins[2] = array_unique($defins[2]);
									// on récupère les macros/fonctions en double
									foreach($defins[2] as $k=>$def) {
										if(!in_array($def, $funclist)) {
											$funcToAdd[$file][] = $defins[0][$k];
										}
									}
								}

								if(!file_exists("tpl/".$file)) {
									$tmp = strtr($tmp, array("\n<USE MACROFILE=\"macros_site.html\">\n"=>$upMacroFile,"\n<USE MACROFILE=\"macros_site.html\" />\n"=>$upMacroFile));
									$tmp = strtr($tmp, array("<MACRO NAME=\"FERMER_HTML\" />"=>"<MACRO NAME=\"FERMER_HTML08\" />", "<MACRO NAME=\"FERMER_HTML\">"=>"<MACRO NAME=\"FERMER_HTML08\" />"));
								}
								$f = fopen($target."/tpl/".$file, "w");
								fwrite($f, $tmp);
								fclose($f);
							}
						}
						closedir($dh);
					} else {
						return "ERROR : cannot open directory 'tpl'.";
					}
				} elseif($i === 1) {
					$i++;
					if ($dh = opendir("tpl")) {
						while (($file = readdir($dh)) !== false) {
							unset($tmpFile, $tmp2, $defs, $def, $fntc);
							// est-ce bien un template ou un fichier de macros ? extension html obligatoire
							if("html" === substr($file, -4, 4) && !is_link("tpl/".$file) && !preg_match("/oai/", $file)) {
								$tmpFile = file_get_contents("tpl/".$file);

								// on ajoute les macros de la 0.8 dans les tpl de la 0.7
								if(!empty($funcToAdd[$file])) {
									foreach($funcToAdd[$file] as $fcta) {
										$tmpFile .= "\n\n".$fcta;
									}
								}	
			
								// on cherche dans chaque tpl et on remplace par l'équivalent 0.8
 								foreach($lookfor as $k=>$look) {
 									$tmpFile = str_ireplace($look, $replace[$k], $tmpFile);
 								}

								// ajustement précis
								if($file == "barre.html" || $file == "macros_presentation.html") {
									$tmpFile = strtr($tmpFile, array("[#TITRE]"=>"[#TITLE]", "[#NOM]"=>"[#TITLE]"));	
								} elseif($file == "macros_site.html") {
									$tmpFile = strtr($tmpFile, array("entriesALPHABETIQUES"=>"ENTREESALPHABETIQUES", "entriesRECURSIF"=>"ENTREESRECURSIF", "entriesauteurs"=>"ENTREESPERSONNES"));
									$tmpFile .= '\n\n<DEFMACRO NAME="FERMER_HTML08">
													</body>
												</html>
											</DEFMACRO>';
								}
								// on met en majuscule ce qui doit l'être
								// cad variables lodel et nom des macros
								$tmpFile = preg_replace_callback("`\[\(?\#[^\]]*\)?\]`", create_function('$matches','return strtoupper($matches[0]);'), $tmpFile);
								$tmpFile = preg_replace_callback("`MACRO NAME=\"([^\"]*)\"`", create_function('$matches','return strtoupper($matches[0]);'), $tmpFile);
								// on écrit le fichier
								$f = fopen($target."/tpl/".$file, "w");
								fwrite($f, $tmpFile);
								fclose($f);
							}
						}
						closedir($dh);
					} else {
						return "ERROR : cannot open directory 'tpl'.";
					}
				}
			}

			// on crée des liens symboliques pointant vers index.php pour simuler les scripts document.php, sommaire.php, etc ..
			symlink("index.".$GLOBALS['extensionscripts'], $target."/document.".$GLOBALS['extensionscripts']);
			symlink("index.".$GLOBALS['extensionscripts'], $target."/sommaire.".$GLOBALS['extensionscripts']);
			symlink("index.".$GLOBALS['extensionscripts'], $target."/personnes.".$GLOBALS['extensionscripts']);
			symlink("index.".$GLOBALS['extensionscripts'], $target."/personne.".$GLOBALS['extensionscripts']);
			symlink("index.".$GLOBALS['extensionscripts'], $target."/entrees.".$GLOBALS['extensionscripts']);
			symlink("index.".$GLOBALS['extensionscripts'], $target."/entree.".$GLOBALS['extensionscripts']);
			symlink("index.".$GLOBALS['extensionscripts'], $target."/docannexe.".$GLOBALS['extensionscripts']);
		} else {
			return "ERROR : directory 'tpl' is missing.";
		}
		return "Ok";
	*/
		function _decode_attributs($text, $options = '')
		{
				// decode attributs
			$arr = explode('"', $text);
			$n = count($arr);
			for ($i = 0; $i < $n; $i += 2) {
				$attr = trim(substr($arr[$i], 0, strpos($arr[$i], "=")));
				if (!$attr)
					continue;
				if ($options == "flat")	{
					$ret[] = array ("name" => $attr, "value" => $arr[$i +1]);
				}	else {
					$ret[$attr] = $arr[$i +1];
				}
			}
			return $ret;
		}
		
		function replace_to08($matches) {
			global $tables, $champs, $types;

			// on récupère un tableau des attributs de la loop
			$tab = _decode_attributs($matches[1]);
			foreach($tab as $name=>$value) {
				$toreplace .= $name."=\"";
				switch($name) {
					case 'NAME':
						break;
					case 'TABLE':
						foreach($tables as $table07=>$table08) {
							$value = str_replace($table07, $table08, $value);
						}
						break;
					case 'WHERE':
					case 'ORDER':
					case 'SELECT':
					case 'DONTSELECT':
					case 'GROUPBY':
					case 'HAVING':
						foreach($champs as $champ07=>$champ08) {
							$value = str_replace($champ07, $champ08, $value);
						}
						foreach($types as $types07=>$types08) {
							$value = str_replace($types07, $types08, $value);
						}
						break;
				}
				$toreplace .= $value."\" ";
			}
			$toreplace = "<LOOP ".$toreplace.">";
			return $toreplace;
		}
		
		$tpl = "./tpl/";
		$tplmigred = $target."/tpl/";

		$variables = array (	"TEXTES"=>"TEXTS", 
					"NOTEBASPAGE"=>"NOTESBASPAGE",
					"IDENTIFIANT"=>"IDENTIFIER",
					"IDENTITE"=>"IDENTITY",
					"NOMFAMILLE"=>"G_FAMILYNAME",
					"PRENOM"=>"G_FIRSTNAME",
					"NOM"=>"G_NAME",
					"OPTION_SIGNALER_MAIL"=>"OPTIONS.METADONNEESSITE.SIGNALER_MAIL",
					"STATUT"=>"STATUS",
					"DROITREDACTEUR"=>"LODELUSER.REDACTOR",
					"DROITEDITEUR"=>"LODELUSER.EDITOR",
					"DROITVISITEUR"=>"LODELUSER.VISITOR",
					"RETOUR"=>"URL_RETOUR",
					"DATE"=>"DATEPUBLI",
					"LANGUE"=>"LANG",
					"OPTION_LISTE_DIFFUSION"=>"OPTIONS.LETTRE",
					"OPTION_LISTE_TITRE"=>"OPTIONS.LETTRE.NOMDELALETTRE",
					"ORDRE"=>"ORDER",
					"MAJ"=>"UPD",
					"LIEN"=>"ALTERFICHIER",
					"LOGO"=>"ICONE",
				);
		$filtres = array (	
					"couper"=>"cuttext",
					"makeurlwithid\('(sommaire|document|personnes|personne|entrees|entree)'\)"=>"makeurlwithid"
				);
		
		$GLOBALS['champs'] = array(	
					"statut"=>"status",
					"maj"=>"upd",
					"identifiant"=>"identifier",
					"groupe"=>"usergroup",
					"ordre"=>"rank",
					"maj"=>"upd",
					"degres"=>"degree",
					"identite"=>"identity",
					"nom"=>"name",
					"valeur"=>"value",
					"classe"=>"class",
					"nomfamille"=>"g_familyname",
					"prenom"=>"g_firstname",
					"idpersonne"=>"idperson",
					"rep"=>"path",
					//"titre"=>"title", // faux selon la table, on laisse comme çà
				);
		
		$GLOBALS['tables'] = array(	"champs"=>"tablefields",
					"documents"=>"textes",
					"entites"=>"entities",
					//"entites_personnes" comportement différent, non migrable
					"entrees"=>"entries",
					"groupes"=>"usergroups",
					"groupesdechamps"=>"tablefieldgroups",
					"objets"=>"objects",
					"personnes"=>"persons",
					"taches"=>"tasks",
					"typeentites_typeentites"=>"entitytypes_entitytypes",
					"typeentites_typeentrees"=>"relations",
					"typeentites_typepersonnes"=>"relations",
					"typeentrees"=>"entrytypes",
					"typepersonnes"=>"persontypes",
					"users_groupes"=>"usergroups"
				);
		
		$GLOBALS['types'] = array(
					"regroupement"=>"souspartie",
					"volume"=>"rubrique",
					"colloque"=>"rubrique",
					"presentation"=>"informations",
					"breve"=>"billet",
					"articlevide"=>"article"
				);
		
		if(is_writeable($tplmigred)) {
			if (is_dir($tpl) && ($dh = opendir($tpl))) {
				while (($file = readdir($dh)) !== false) {
					$tplFile = $tpl.$file;
					if(!is_link($tplFile) && is_readable($tplFile) && $file != '.' && $file != '..') {
						$content = file_get_contents($tplFile);
						
						// remplacement spécifique
						$content = preg_replace("/\[\(#ID\|makeurlwithid\('docannexe'\)\)]/Ui", "[#ID|makeurlwithfile]", $content);
						$content = preg_replace("/<USE TEMPLATEFILE=\"desk\">/Ui", "", $content);
		
						// remplacement des variables
						foreach($variables as $k=>$var) {
							$content = preg_replace("/\[\(?#$k(:[A-Z]{2})*(\|[\w\"'\(\)]+)*\)?\]/U", "[#$var\\1\\2]", $content);
						}
						// remplacement des filtres
						foreach($filtres as $k=>$filtre) {
							$content = preg_replace("/\|$k/i", "|$filtre", $content);
						}
			
						// remplacement des champs dans les LOOPs
						$content = preg_replace_callback("/<LOOP ([^>]+)>/U", "replace_to08", $content);
		
						// ecriture du template migré
						file_put_contents($tplmigred.$file, $content);
					}
				}
				closedir($dh);
			} else {
				return "ERROR : cannot open directory $tpl.";
			}
			// on crée des liens symboliques pointant vers index.php pour simuler les scripts document.php, sommaire.php, etc ..
			symlink("index.".$GLOBALS['extensionscripts'], $target."/document.".$GLOBALS['extensionscripts']);
			symlink("index.".$GLOBALS['extensionscripts'], $target."/sommaire.".$GLOBALS['extensionscripts']);
			symlink("index.".$GLOBALS['extensionscripts'], $target."/personnes.".$GLOBALS['extensionscripts']);
			symlink("index.".$GLOBALS['extensionscripts'], $target."/personne.".$GLOBALS['extensionscripts']);
			symlink("index.".$GLOBALS['extensionscripts'], $target."/entrees.".$GLOBALS['extensionscripts']);
			symlink("index.".$GLOBALS['extensionscripts'], $target."/entree.".$GLOBALS['extensionscripts']);
			symlink("index.".$GLOBALS['extensionscripts'], $target."/docannexe.".$GLOBALS['extensionscripts']);
		} else {
			return "ERROR : directory $tplmigred is not writeable.";
		}
		return 'Ok';
	}

}


?>
