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
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 */


class exportfor08
{
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
			id,nom,idgroupe,titre,style,type,condition,defaut,traitement,balises,filtrage,edition,commentaire,statut,ordre,maj',

		'TABLEFIELDGROUPS::
			id,name,class,title,comment,status,rank,upd'=>
		'GROUPESDECHAMPS::
			id,nom,classe,titre,commentaire,statut,ordre,maj',

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
			idtypeentite,idtypeentite2,condition',

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
	function __construct() {
		$this->old_tables = $this->get_tables();
		if (!in_array($GLOBALS['tp'] . 'objets__old', $this->old_tables)) {
			$this->init_db();
		}
		
	}

	/**
	 *  Récupère la liste des tables dans la base du site
	 *
	 * @return array
	 */

	private function get_tables() {
		$result=mysql_list_tables($GLOBALS[currentdb]);
		$tables=array();
		while (list($table) = mysql_fetch_row($result)) {
			$tables[] = $table;
		}
		if (!empty($tables)) {
			return $tables;
		} else {
			die('Pas de tables à traiter');
		}
	}

	/**
	 * Renomme les tables de la 0.7 : ajoute le suffixe __old au nom de la table
	 * Crée ensuite les tables de la 0.8, d'après le fichier init-site.sql
	 *
	 * @return array
	 * @todo Ne renommer que les tables de la 0.7
	 */
	private function init_db() {
		// sauvegarde des tables en 0.7 : renommées en $table . __old
		$query = '';
		foreach ($this->old_tables as $table07) {
			if (substr($table07, -5) != '__old') {
				$query .= "RENAME TABLE _PREFIXTABLE_$table07 TO _PREFIXTABLE_$table07" . '__old;';
			}
		}
		if ($err = $this->__mysql_query_cmds($query)) {
			die($err);
		}
		
		// import de la structure des tables en 0.8
		$query = '';
		$sqlfile = SITEROOT . 'init-site.sql';
		if (is_readable($sqlfile)) {
			$query = join('', file($sqlfile));
		} else {
			die('Impossible de lire le fichier init-site.sql pour la 0.8');
		}
		if ($err = $this->__mysql_query_cmds($query)) {
			die($err);
		}

		// créations tables qui dépendent du ME en 0.8 (et qui sont en dur en 0.7)
		// ENTITÉS : publications, documents
		$query = "CREATE TABLE IF NOT EXISTS _PREFIXTABLE_publications AS SELECT * FROM _PREFIXTABLE_publications__old;
			ALTER TABLE _PREFIXTABLE_publications CHANGE identite identity INT UNSIGNED DEFAULT '0' NOT NULL UNIQUE;
			CREATE TABLE IF NOT EXISTS _PREFIXTABLE_documents AS SELECT * FROM _PREFIXTABLE_documents__old;
			ALTER TABLE _PREFIXTABLE_documents CHANGE identite identity INT UNSIGNED DEFAULT '0' NOT NULL UNIQUE;";

		// INDEX : indexes
		$query .= "CREATE TABLE IF NOT EXISTS _PREFIXTABLE_indexes (
  				identry int(10) unsigned default NULL,
  				nom text,
  				definition text,
  				UNIQUE KEY identry (identry),
  				KEY index_identry (identry)
				);";

		// INDEX DE PERSONNES : auteurs et entities_auteurs
		$query .= "CREATE TABLE IF NOT EXISTS _PREFIXTABLE_auteurs(
  				idperson int(10) unsigned default NULL,
  				nomfamille tinytext,
  				prenom tinytext,
  				UNIQUE KEY idperson (idperson),
  				KEY index_idperson (idperson)
				);";

		$query .= "CREATE TABLE IF NOT EXISTS _PREFIXTABLE_entities_auteurs(
  				idrelation int(10) unsigned default NULL,
  				prefix tinytext,
  				affiliation tinytext,
  				fonction tinytext,
  				description text,
  				courriel text,
  				role text,
  				UNIQUE KEY idrelation (idrelation),
  				KEY index_idrelation (idrelation)
				);";

		if ($err = $this->__mysql_query_cmds($query)) {
			die($err);
		} else {
			echo '<h1>initdb ok</h1>';
			return true;
		}
	}


	/**
	 *  Copie les données de la 0.7 dans les tables de la 0.8
	 *
	 * @return true si la copie est ok
	 */
	public function cp_07_to_08() {
		
		$result = mysql_query('select * from ' . $GLOBALS['tp'] . 'objects');
		$num_rows = mysql_num_rows($result);
		if ($num_rows == 0) {

			foreach ($this->translations as $new => $old) {
				list($newtable, $newfields) = explode("::", strtolower($new));
				list($oldtable, $oldfields) = explode("::", strtolower($old));
				$oldfields = trim($oldfields);
				$newfields = trim($newfields);
				$oldtable .= '__old';

				if ($err = $this->__mysql_query_cmds("INSERT INTO _PREFIXTABLE_$newtable ($newfields) SELECT $oldfields FROM _PREFIXTABLE_$oldtable")) {
					die($err);
				}
			}
			return true;
		} else {
			// probablemnt déjà fait
		}
	}

	/**
	 * Création des classes : en dur en 0.7 (publications, documents, index et index de personnes)
	 * En mou en 0.8, donc à insérer dans les tables objects et classes
	 * 
	 * @return true si insertions dans les tables OK
	 */

	public function create_classes() {
		// ENTITÉS : publications et documents
		$id = $this->__insert_object('classes');
		$query = 
		"INSERT IGNORE INTO `_PREFIXTABLE_classes` (`id` , `icon` , `class` , `title` , `altertitle` , `classtype` , `comment` , `rank` , `status` , `upd` )
		VALUES ($id , 'lodel/icons/collection.gif', 'publications', 'Publications', '', 'entities', '', '1', '1', NOW( ));
		UPDATE _PREFIXTABLE_objects SET class='entities' WHERE class='publications';
		";
	
		$id = $this->__insert_object('classes');
		$query .= "INSERT IGNORE INTO `_PREFIXTABLE_classes` ( `id` , `icon` , `class` , `title` , `altertitle` , `classtype` , `comment` , `rank` , `status` , `upd` )
		VALUES ($id , 'lodel/icons/texte.gif', 'documents', 'Documents', '', 'entities', '', '2', '1', NOW( ));
		UPDATE _PREFIXTABLE_objects SET class='entities' WHERE class='documents';
		";

		// INDEX
		$id = $this->__insert_object('classes');
		$query .= "INSERT IGNORE INTO `_PREFIXTABLE_classes` ( `id` , `icon` , `class` , `title` , `altertitle` , `classtype` , `comment` , `rank` , `status` , `upd` )
		VALUES ($id , 'lodel/icons/index.gif', 'indexes', 'Index', '', 'entries', '', '3', '1', NOW( ));
		UPDATE _PREFIXTABLE_objects SET class='entries' WHERE class='entrees';
		";

		// INDEX DE PERSONNES
		$id = $this->__insert_object('classes');
		$query .= "INSERT IGNORE INTO `_PREFIXTABLE_classes` ( `id` , `icon` , `class` , `title` , `altertitle` , `classtype` , `comment` , `rank` , `status` , `upd` )
		VALUES ($id , 'lodel/icons/personne.gif', 'auteurs', 'Auteurs', '', 'persons', '', '4', '1', NOW( ));
		UPDATE _PREFIXTABLE_objects SET class='persons' WHERE class='personnes';
		";

		if ($err = $this->__mysql_query_cmds($query)) {
			die($err);
		} else {
			$this->__update_entities();
			echo '<h1>create_classes ok</h1>';
			return true;
		}
	}

	/**
	 * Insertion d'une ligne dans la table objects
	 * 
	 * @return l'identifiant inséré (champ auto-incrémenté id)
	 */

	private function __insert_object($class) {
		$result = mysql_query('INSERT INTO ' . $GLOBALS['tp'] . "objects (class) VALUES ('$class')") or die (mysql_error());
		return $id = mysql_insert_id();
	}

	/**
	 * Mise à jour de la table entities avec les données issues des tables documents et publications
	 *
	 * @param string $title nom du champ qui fait office de titre dans le ME de la 0.7, pour les classes documents et publications : vaut 'titre' dans le ME revues.org 0.7
	 * @todo générer aussi les champs suivants : identifier, creationdate, modificationdate, creationmethod, creationinfo
	 * @todo demander à l'utilisateur le nom du champ, au cas où il serait différent de 'titre'
	 *
	 * @return true si OK
	 */

	private function __update_entities($title = 'titre') {
		
		foreach (array("publications","documents") as $classe) {
	  		$result = mysql_query('SELECT identity,' . $title . ' FROM ' . $GLOBALS['tp'] . $classe) or die (mysql_error());
	  		while (list($id,$titre) = mysql_fetch_row($result)) {
	    			$titre = strip_tags($titre);
	    			if (strlen($titre)>255) {
	      				$titre=substr($titre,0,256);
	      				$titre=preg_replace("/\S+$/","",$titre);
	    			}
	    			$titre = addslashes($titre);
				$query = 'UPDATE ' . $GLOBALS['tp'] . "entities set g_title='$titre' WHERE id=$id;";
				mysql_query($query) or die (mysql_error());
	  		}
		}
		echo '<h1>__update_entities ok</h1>';
		return true;
		/*if ($err = $this->__mysql_query_cmds($query)) {
				die($err);
		} else {
			return true;
		}*/
	}

	/**
	 * Mise à jour des champs pour les classes
	 * Pour les entités, reprise des champs des tables publications et documents
	 * Pour les index et index de personnes, ajout dans table tablefields
	 *
	 * @param string $dctitle nom du champ qui fait office de titre dans le ME de la 0.7, pour les classes documents et publications : vaut 'titre' dans le ME revues.org 0.7
	 * @todo demander à l'utilisateur le nom du champ, au cas où il serait différent de 'titre'
	 * @return true si insertions dans les tables OK
	 */

	public function update_fields($dctitle = 'titre') {
		// ENTITÉS : mise à jour des colonnes 'class' et 'g_name' seulement
		$result = mysql_query("SELECT id,class FROM $GLOBALS[tp]tablefieldgroups WHERE status>0") or die(mysql_error());
		$query = '';
		while ($row = mysql_fetch_assoc($result)) {
			$query .= "UPDATE _PREFIXTABLE_tablefields SET g_name = 'dc.title', class='" . $row['class'] . "' WHERE idgroup = " . $row['id'] . ';';
		}


		// INDEX : ajout des champs dans tablefields
		$query .= "INSERT INTO _PREFIXTABLE_tablefields (id, name, idgroup, class, title, altertitle, style, type, g_name, cond, defaultvalue, processing, allowedtags, gui_user_complexity, filtering, edition, editionparams, weight, comment, status, rank, upd) VALUES
			(NULL, 'nom', '0', 'indexes', 'Dénomination de l\'entrée d\'index', '', '', 'text', 'index key', '*', 'Tous droits réservés', '', '', '16', '', 'editable', '', '4', '', '32', '', NOW( )),

			(NULL, 'definition', '0', 'indexes', 'Définition', '', '', 'text', '', '*', '', '', '', '16', '', 'fckeditor', 'Basic', '1', '', '32', '', NOW( ));";

		// INDEX DE PERSONNES : ajout des champs dans tablefields
		$query .= "INSERT INTO _PREFIXTABLE_tablefields (id, name, idgroup, class, title, altertitle, style, type, g_name, cond, defaultvalue, processing, allowedtags, gui_user_complexity, filtering, edition, editionparams, weight, comment, status, rank, upd) VALUES
			(NULL, 'nomfamille', '0', 'auteurs', 'Nom de famille', '', '', 'tinytext', 'familyname', '*', '', '', '', '32', '', 'editable', '', '4', '', '32', '', NOW( )),

			(NULL, 'prenom', '0', 'auteurs', 'Prénom', '', '', 'tinytext', 'firstname', '*', '', '', '', '32', '', 'editable', '', '4', '', '32', '', NOW( )),

			(NULL, 'prefix', '0', 'entities_auteurs', 'Préfixe', '', 'prefixe, .prefixe', 'tinytext', '', '*', '', '', '', '64', '', 'editable', '', '0', '', '1', '', NOW( )),

			(NULL, 'affiliation', '0', 'entities_auteurs', 'Affiliation', '', 'affiliation, .affiliation', 'tinytext', '', '*', '', '', '', '32', '', 'editable', '', '4', '', '1', '', NOW( )),
			
			(NULL, 'fonction', '0', 'entities_auteurs', 'Fonction', '', 'fonction, .fonction', 'tinytext', '', '*', '', '', '', '32', '', 'editable', '', '0', '', '1', '', NOW( )),

			(NULL, 'description', '0', 'entities_auteurs', 'Description de l\'auteur', '', 'descriptionauteur', 'text', '', '*', '', '', '', '16', '', 'fckeditor', '5', '4', '', '1', '', NOW( )),

			(NULL, 'courriel', '0', 'entities_auteurs', 'Courriel', '', 'courriel, .courriel', 'email', '', '*', '', '', '', '32', '', 'editable', '', '4', '', '1', '', NOW( )),

			(NULL, 'role', '0', 'entities_auteurs', 'Role dans l\'élaboration du document', '', 'role,.role', 'text', '', '*', '', '', '', '64', '', 'editable', '', '0', '', '1', '', NOW( ));";

		if ($err = $this->__mysql_query_cmds($query)) {
				die($err);
		} else {
			return true;
		}
	}

	/**
	 * Mise à jour des types
	 * 
	 * @return true si insertions dans les tables OK
	 */

	public function update_types() {
		// ENTITES
		$query = "UPDATE _PREFIXTABLE_types SET display='';";

		// INDEX
		$query .= "UPDATE _PREFIXTABLE_entrytypes SET class = 'indexes', sort = 'sortkey';";
		
		// INDEX DE PERSONNES
		$query .= "UPDATE _PREFIXTABLE_persontypes SET class = 'auteurs';
		UPDATE _PREFIXTABLE_persons JOIN _PREFIXTABLE_entites_personnes__old ON id = idpersonne SET _PREFIXTABLE_persons.idtype = _PREFIXTABLE_entites_personnes__old.idtype;
		";
		
		if ($err = $this->__mysql_query_cmds($query)) {
				die($err);
		} else {echo '<h1>update_types ok</h1>';
			return true;
		}
	}

	/**
	 * Remplissage des tables des index et index de personnes à partir des tables 0.7 (entrees et auteurs)
	 * Mise à jour des relations : en 0.8, toutes les relations sont stockées dans la table relations,
	 * en 0.7 dans les tables entites_entrees et entites_personnes
	 * 
	 * @return true si insertions dans les tables OK
	 */

	public function insert_index_data() {
		// INDEX : tables indexes et relations
		$result = mysql_query('SELECT MAX(idrelation) FROM ' . $GLOBALS['tp'] . 'relations');
		$max_id = mysql_result($result, 0);
		$query = "INSERT IGNORE INTO _PREFIXTABLE_indexes (identry, nom) SELECT id, g_name from _PREFIXTABLE_entries;
		INSERT INTO _PREFIXTABLE_relations (id2, id1) SELECT DISTINCT identree, identite from _PREFIXTABLE_entites_entrees__old;
		UPDATE _PREFIXTABLE_relations SET nature='E', degree=1 WHERE degree IS NULL AND idrelation > $max_id;
		";

		// INDEX DE PERSONNES : tables auteurs, entities_auteurs et relations
		$result = mysql_query('SELECT MAX(idrelation) FROM ' . $GLOBALS['tp'] . 'relations');
		$max_id = mysql_result($result, 0);
		$query .= "INSERT IGNORE INTO _PREFIXTABLE_auteurs (idperson, nomfamille, prenom) SELECT id, g_familyname, g_firstname from _PREFIXTABLE_persons;
		INSERT INTO _PREFIXTABLE_relations (id2, id1) SELECT DISTINCT idpersonne, identite from _PREFIXTABLE_entites_personnes__old;
		UPDATE _PREFIXTABLE_relations SET nature='G', degree=1 WHERE degree IS NULL AND idrelation > $max_id;
		INSERT INTO _PREFIXTABLE_entities_auteurs (idrelation, prefix, affiliation, fonction, description, courriel) SELECT DISTINCT idrelation, prefix, affiliation, fonction, description, courriel from relations, entites_personnes__old where nature='G' and idpersonne=id2 and identite=id1
		";


		if ($err = $this->__mysql_query_cmds($query)) {
				die($err);
		} else {echo '<h1>insert_index_data ok</h1>';
			return true;
		}
	}

	/**
	 * Mise à jour du ME pour conformité avec ME revues.org de la 0.8
	 * 
	 * @return true si insertions dans les tables OK
	 */

	public function update_ME() {
		// classe documents devient textes
		$query = "UPDATE _PREFIXTABLE_classes SET class = 'textes',title = 'Textes' WHERE class = 'documents';
		RENAME TABLE _PREFIXTABLE_documents TO _PREFIXTABLE_textes;
		UPDATE _PREFIXTABLE_objects SET class = 'textes' WHERE class='documents';
		UPDATE _PREFIXTABLE_types SET class = 'textes' WHERE class='documents';
		UPDATE _PREFIXTABLE_tablefields SET class = 'textes' WHERE class='documents';
		UPDATE _PREFIXTABLE_tablefieldgroups SET class = 'textes' WHERE class='documents';
		";

		// Nom des TEMPLATES dans l'onglet Édition
		$query .= "UPDATE _PREFIXTABLE_types SET tpledition = 'edition',tplcreation = 'creation';";

		// CLASSES supplémentaires
		$query .= "INSERT IGNORE INTO `_PREFIXTABLE_classes` (`id` , `icon` , `class` , `title` , `altertitle` , `classtype` , `comment` , `rank` , `status` , `upd` ) VALUES
		(NULL, 'lodel/icons/doc_annexe.gif', 'fichiers', 'Fichiers', '', 'entities', '', '5', '32', NOW()),
		(NULL, 'lodel/icons/lien.gif', 'liens', 'Sites', '', 'entities', '', '6', '32', NOW()),
		(NULL, 'lodel/icons/texte_simple.gif', 'textessimples', 'Textes simples', '', 'entities', '', '3', '32', NOW()),
		(NULL, 'lodel/icons/individu.gif', 'individus', 'Personnes', '', 'entities', '', '4', '1', NOW()),
		(NULL, 'lodel/icons/index_avance.gif', 'indexavances', 'Index avancés', '', 'entries', '', '10', '1', NOW());
		
		CREATE TABLE _PREFIXTABLE_fichiers (
  			identity int(10) unsigned default NULL,
  			titre text,
  			document tinytext,
  			description text,
  			legende tinytext,
  			credits tinytext,
  			vignette tinytext,
  			UNIQUE KEY identity (identity),
  		KEY index_identity (identity)
		);

		CREATE TABLE _PREFIXTABLE_liens (
  			identity int(10) unsigned default NULL,
  			titre text,
  			url text,
  			urlfil text,
  			texte text,
  			capturedecran tinytext,
  			UNIQUE KEY identity (identity),
  			KEY index_identity (identity)
		);

		CREATE TABLE _PREFIXTABLE_textessimples (
  			identity int(10) unsigned default NULL,
  			titre tinytext,
  			texte text,
  			url text,
  			`date` datetime default NULL,
  			UNIQUE KEY identity (identity),
  			KEY index_identity (identity)
		);

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
		);

		CREATE TABLE _PREFIXTABLE_indexavances (
			identry int(10) unsigned default NULL,
  			nom tinytext,
  			description text,
  			url text,
  			icone tinytext,
  			UNIQUE KEY identry (identry),
  			KEY index_identry (identry)
		);

		";

		// CHAMPS des classes
		/*$query .= "INSERT INTO _PREFIXTABLE_tablefields (id, name, idgroup, class, title, altertitle, style, type, g_name, cond, defaultvalue, processing, allowedtags, gui_user_complexity, filtering, edition, editionparams, weight, comment, status, rank, upd) VALUES

		(NULL, 'licence', '24', 'fichiers', 'Licence', '', '', 'entries', '', '', '', '', '', '64', '', 'editable', '', '0', '', '1', '118', NOW()),
		(NULL, 'titre', '7', 'fichiers', 'Titre', '', '', 'text', 'dc.title', '*', '', '', '', '16', '', 'editable', '', '4', '', '32', '47', NOW()),
		(NULL, 'document', '8', 'fichiers', 'Document', '', '', 'file', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '32', '1', NOW()),
		(NULL, 'description', '8', 'fichiers', 'Description', '', '', 'text', '', '*', '', '', '', '16', '', 'fckeditor', 'Simple', '4', '', '32', '2', NOW()),
		(NULL, 'auteur', '24', 'fichiers', 'Auteur', '', '', 'persons', '', '', '', '', '', '64', '', 'editable', '', '0', '', '32', '91', NOW()),
		(NULL, 'vignette', '8', 'fichiers', 'Vignette', '', '', 'image', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '32', '3', NOW()),
		(NULL, 'legende', '8', 'fichiers', 'Légende', '', '', 'tinytext', '', '*', '', '', '', '16', '', 'fckeditor', 'Basic', '4', '', '1', '4', NOW()),
		(NULL, 'credits', '24', 'fichiers', 'Crédits', '', '', 'tinytext', '', '*', '', '', '', '16', '', 'editable', '', '4', '', '1', '108', NOW()),

		(NULL, 'titre', '5', 'liens', 'Titre du site', '', '', 'text', 'dc.title', '*', 'Site sans titre', '', '', '16', '', 'editable', '', '8', '', '32', '43', NOW()),
		(NULL, 'url', '6', 'liens', 'URL du site', '', '', 'url', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '32', '1', NOW()),
		(NULL, 'urlfil', '6', 'liens', 'URL du fil de syndication du site', '', '', 'url', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '32', '4', NOW()),
		(NULL, 'texte', '6', 'liens', 'Description du site', '', '', 'text', '', '*', '', '', '', '16', '', 'fckeditor', 'Simple', '2', '', '32', '2', NOW()),
		(NULL, 'auteur', '25', 'liens', 'Auteur de la notice décrivant ce site', '', '', 'persons', '', '', '', '', '', '64', '', 'editable', '', '0', '', '32', '92', NOW()),
		(NULL, 'capturedecran', '6', 'liens', 'Capture d\'écran du site', '', '', 'image', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '32', '3', NOW()),

		(NULL, 'date', '19', 'textessimples', 'Date de publication en ligne', '', '', 'datetime', '', '*', 'now', '', '', '16', '', 'editable', '', '0', '', '1', '100', '2006-06-27 23:51:45'),
		(NULL, 'url', '19', 'textessimples', 'Lien', '', '', 'url', '', '*', '', '', '', '16', '', 'editable', '', '2', '', '1', '99', NOW()),
		(NULL, 'titre', '18', 'textessimples', 'Titre', '', '', 'tinytext', 'dc.title', '*', '', '', '', '16', '', 'editable', '', '4', '', '32', '72', NOW()),
		(NULL, 'texte', '19', 'textessimples', 'Texte', '', '', 'text', '', '*', '', '', '', '16', '', 'fckeditor', 'Simple', '4', '', '1', '73', NOW()),
		(NULL, 'auteur', '26', 'textessimples', 'Auteur', '', '', 'persons', '', '', '', '', '', '64', '', 'editable', '', '0', '', '32', '93', NOW()),

		(NULL, 'email', '30', 'individus', 'Courriel', '', '', 'email', '', '*', '', '', '', '16', '', 'editable', '', '4', '', '1', '3', NOW()),
		(NULL, 'siteweb', '30', 'individus', 'Site web', '', '', 'url', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '1', '4', NOW()),
		(NULL, 'description', '30', 'individus', 'Description', '', '', 'text', '', '*', '', '', '', '16', '', 'fckeditor', 'Simple', '4', '', '1', '2', NOW()),
		(NULL, 'nom', '28', 'individus', 'Nom', '', '', 'tinytext', 'dc.title', '*', '', '', '', '16', '', 'editable', '', '4', '', '1', '1', NOW()),
		(NULL, 'prenom', '28', 'individus', 'Prénom', '', '', 'tinytext', '', '*', '', '', '', '16', '', 'editable', '', '4', '', '1', '2', NOW()),
		(NULL, 'accroche', '28', 'individus', 'Accroche', '', '', 'text', '', '*', '', '', '', '16', '', 'fckeditor', 'Simple', '4', '', '1', '3', NOW()),
		(NULL, 'adresse', '30', 'individus', 'Adresse', '', '', 'text', '', '*', '', '', '', '16', '', 'editable', '3', '4', '', '1', '102', NOW()),
		(NULL, 'telephone', '30', 'individus', 'Téléphone', '', '', 'tinytext', '', '*', '', '', '', '16', '', 'editable', '', '4', '', '1', '103', NOW()),
		(NULL, 'photographie', '28', 'individus', 'Photographie', '', '', 'image', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '1', '104', NOW()),

		(NULL, 'nom', '0', 'indexavances', 'Dénomination de l\'entrée d\'index', '', '', 'tinytext', 'index key', '*', '', '', '', '16', '', 'editable', '', '4', '', '1', '113', NOW()),
		(NULL, 'description', '0', 'indexavances', 'Description de l\'entrée d\'index', '', '', 'text', '', '*', '', '', '', '16', '', 'fckeditor', 'Basic', '4', '', '1', '114', NOW()),
		(NULL, 'url', '0', 'indexavances', 'URL', '', '', 'url', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '1', '115', NOW()),
		(NULL, 'icone', '0', 'indexavances', 'Icône', '', '', 'image', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '1', '116', NOW());
		";

		$query .= "INSERT INTO _PREFIXTABLE_tablefieldgroups (id, name, class, title, altertitle, comment, status, rank, upd) VALUES 
		(NULL, 'grtitre', 'liens', 'Titre', '', '', '1', '5', NOW()),
		(NULL, 'grsite', 'liens', 'Définition du site', '', '', '1', '6', NOW()),
		(NULL, 'grtitre', 'fichiers', 'Titre', '', '', '1', '7', NOW()),
		(NULL, 'grmultimedia', 'fichiers', 'Définition', '', '', '1', '8', NOW()),
		(NULL, 'grtitre', 'textessimples', 'Titre', '', '', '1', '10', NOW()),
		(NULL, 'grtexte', 'textessimples', 'Texte', '', '', '1', '11', NOW()),
		(NULL, 'grdroits', 'fichiers', 'Droits', '', '', '32', '16', NOW()),
		(NULL, 'grauteurs', 'liens', 'Auteurs', '', '', '32', '17', NOW()),
		(NULL, 'grauteurs', 'textessimples', 'Auteurs', '', '', '32', '18', NOW()),
		(NULL, 'grtitre', 'individus', 'Titre', '', '', '1', '20', NOW()),
		(NULL, 'grdescription', 'individus', 'Description', '', '', '1', '21', NOW());
		";
*/
		// TYPES
		$query .= "INSERT INTO _PREFIXTABLE_types (id, icon, type, title, altertitle, class, tpl, tplcreation, tpledition, import, display, creationstatus, search, public, gui_user_complexity, oaireferenced, rank, status, upd) VALUES 

		(NULL, '', 'image', 'Image', '', 'fichiers', 'image', 'entities', '', '0', '', '-1', '1', '0', '64', '1', '1', '1', NOW()),
		(NULL, '', 'noticedesite', 'Notice de site', '', 'liens', 'lien', 'entities', '', '0', '', '-1', '1', '0', '64', '0', '16', '1', NOW()),
		(NULL, 'lodel/icons/commentaire.gif', 'commentaire', 'Commentaire du document', '', 'textessimples', '', 'entities', '', '0', 'advanced', '-1', '1', '1', '16', '0', '2', '1', NOW()),
		(NULL, '', 'videoannexe', 'Vidéo placée en annexe', '', 'fichiers', '', 'entities', 'edition', '0', 'advanced', '-1', '1', '0', '64', '0', '4', '1', NOW()),
		(NULL, '', 'annuairedepersonnes', 'Biographies des membres', '', 'publications', 'sommaire', 'entities', 'edition', '0', '', '-1', '1', '0', '16', '0', '8', '32', NOW()),
		(NULL, '', 'annuairemedias', 'Médiathèque', '', 'publications', 'sommaire', 'entities', 'edition', '0', '', '-1', '1', '0', '16', '0', '9', '32', NOW()),
		(NULL, '', 'image_annexe', 'Image placée en annexe', '', 'fichiers', '', 'entities', '', '0', 'advanced', '-1', '1', '0', '64', '0', '2', '1', NOW()),
		(NULL, '', 'lienannexe', 'Lien placé en annexe', '', 'liens', 'lien', 'entities', '', '0', 'advanced', '-1', '1', '0', '64', '0', '24', '1', NOW()),
		(NULL, '', 'individu', 'Notice biographique de membre', '', 'individus', 'individu', 'entities', '', '0', '', '-1', '1', '0', '16', '0', '25', '1', NOW()),
		(NULL, '', 'billet', 'Billet', '', 'textessimples', 'article', 'entities', '', '0', '', '-1', '1', '0', '16', '0', '1', '1', NOW()),
		(NULL, '', 'annuairedesites', 'Annuaire de sites', '', 'publications', 'sommaire', 'entities', 'edition', '0', '', '-1', '1', '0', '16', '0', '7', '32', NOW()),
		(NULL, 'lodel/icons/rss.gif', 'fluxdesyndication', 'Flux de syndication', '', 'liens', 'lien', 'entities', '', '0', '', '-1', '1', '0', '64', '0', '30', '1', NOW()),
		(NULL, '', 'video', 'Vidéo', '', 'fichiers', '', 'entities', '', '0', '', '-1', '1', '0', '64', '0', '3', '1', NOW()),
		(NULL, '', 'son', 'Document sonore', '', 'fichiers', '', 'entities', '', '0', '', '-1', '1', '0', '32', '0', '5', '1', NOW()),
		(NULL, '', 'fichierannexe', 'Fichier placé en annexe', '', 'fichiers', 'image', 'entities', '', '0', 'advanced', '-1', '1', '0', '32', '0', '7', '1', NOW()),
		(NULL, '', 'sonannexe', 'Document sonore placé en annexe', '', 'fichiers', '', 'entities', '', '0', 'advanced', '-1', '1', '0', '32', '0', '6', '1', NOW());
		";

		$query .= "INSERT INTO _PREFIXTABLE_entrytypes (id, icon, type, class, title, altertitle, style, g_type, tpl, tplindex, gui_user_complexity, rank, status, flat, newbyimportallowed, edition, sort, upd) VALUES
		(NULL, '', 'licence', 'indexavances', 'Licence portant sur le document', '', 'licence', 'dc.rights', 'entree', 'entrees', '16', '7', '1', '1', '1', 'select', 'rank', NOW());
		";

		// OPTIONS
		$query .= "INSERT INTO _PREFIXTABLE_optiongroups (id, idparent, name, title, altertitle, comment, logic, exportpolicy, rank, status, upd) VALUES
		('1', '0', 'from07', 'Suite import de données de Lodel 0.7', '', '', '', '1', '1', '32', NOW());
		UPDATE _PREFIXTABLE_options SET idgroup = 1;
		UPDATE _PREFIXTABLE_options SET type = 'tinytext' WHERE type = 's';
		UPDATE _PREFIXTABLE_options SET type = 'passwd' WHERE type = 'pass';
		UPDATE _PREFIXTABLE_options SET type = 'email' WHERE type = 'mail';
		";

		if ($err = $this->__mysql_query_cmds($query)) {
				die($err);
		} else {echo '<h1>update_ME ok</h1>';
			return true;
		}

	}

	/*private function __getfields($table,$database="") {
		if (!$database) $database=$GLOBALS['currentdb'];
  		$fields = mysql_list_fields($database,$GLOBALS[tp].$table) or die (mysql_error());
  		$columns = mysql_num_fields($fields);
  		$arr=array();
  		for ($i = 0; $i < $columns; $i++) {
    			$fieldname=mysql_field_name($fields, $i);
    			$arr[$fieldname]=1;
  		}
  		return $arr;
	}*/

	

	/**
	 * Execute une ou plusieurs commandes Mysql
	 */

	private function __mysql_query_cmds($cmds, $table = '') {
		$sql = str_replace('_PREFIXTABLE_', $GLOBALS['tp'], $cmds);
		$sql = str_replace('#_TP_', $GLOBALS['tp'], $sql);

		//$charset
		$sql = str_replace('_CHARSET_', '', $sql);
		
		if (!$sql) {
			$err = 'Pb pour exécuter la commande suivante : ' . $cmds;
			return $err;
		}
	
		$request = preg_split ("/;/", preg_replace("/#.*?$/m","",$sql));
		if ($table) { // select the commands operating on the table  $table
			$request = preg_grep("/(REPLACE|INSERT)\s+INTO\s+$GLOBALS[tp]$table\s/i",$request);
		}
		if (!$request) {
			$err = 'Pb pour exécuter la commande suivante : ' . $request;
			return $err;
		}

		foreach ($request as $cmd) {echo $cmd . '<p>';
			$cmd=trim(preg_replace ("/^#.*?$/m","",$cmd));
			if ($cmd) {
				if (!mysql_query($cmd)) {
					$err = '<font COLOR=red>' . mysql_error() . '<p>requete : ' . $cmd . '</font><br>';
					return $err; // sort, ca sert a rien de continuer
      				}
    			}
  		}
		return false;
	}



}


?>