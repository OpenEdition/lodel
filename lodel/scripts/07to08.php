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
			ALTER TABLE _PREFIXTABLE_documents CHANGE identite identity INT UNSIGNED DEFAULT '0' NOT NULL UNIQUE;
			ALTER TABLE _PREFIXTABLE_documents ADD alterfichier tinytext;";

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
				site text,
  				UNIQUE KEY idrelation (idrelation),
  				KEY index_idrelation (idrelation)
				);";

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
				);";

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
				);";

		if ($err = $this->__mysql_query_cmds($query)) {
			die($err);
		} else {
			echo '<p>initdb ok</p>';
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
			echo '<p>create_classes ok</p>';
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
		echo '<p>__update_entities ok</p>';
		return true;
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
		$result = mysql_query("SELECT id,class FROM $GLOBALS[tp]tablefieldgroups WHERE status>0") or die('<font COLOR=red>' . mysql_error() . '<p>requete : ' . $cmd . '</font><br>');
		$query = '';
		while ($row = mysql_fetch_assoc($result)) {
			$query .= "UPDATE _PREFIXTABLE_tablefields SET g_name = 'dc.title', class='" . $row['class'] . "' WHERE idgroup = " . $row['id'] . ';';
		}
		$result = mysql_query("SELECT $GLOBALS[tp]entites__old.id, $GLOBALS[tp]entites__old.maj, $GLOBALS[tp]documents__old.fichiersource FROM $GLOBALS[tp]entites__old JOIN $GLOBALS[tp]documents__old ON ($GLOBALS[tp]entites__old.id = $GLOBALS[tp]documents__old.identite)") or die('<font COLOR=red>' . mysql_error() . '<p>requete : ' . $cmd . '</font><br>');
		while ($row = mysql_fetch_assoc($result)) {
			$query .= "UPDATE _PREFIXTABLE_entities SET creationmethod = 'servoo', creationdate = '".$row['maj']."', modificationdate = '".$row['maj']."', creationinfo = '".$row['fichiersource']."' WHERE id = " . $row['id'] . ';';
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
		} else {echo '<p>update_types ok</p>';
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
		$result = mysql_query('SELECT MAX(idrelation) FROM ' . $GLOBALS['tp'] . 'relations') or die('<font COLOR=red>' . mysql_error() . '<p>requete : ' . $cmd . '</font><br>');
		$max_id = mysql_result($result, 0);
		$query = "REPLACE INTO _PREFIXTABLE_indexes (identry, nom) SELECT id, g_name from _PREFIXTABLE_entries;
		INSERT INTO _PREFIXTABLE_relations (id2, id1) SELECT DISTINCT identree, identite from _PREFIXTABLE_entites_entrees__old;
		UPDATE _PREFIXTABLE_relations SET nature='E', degree=1 WHERE degree IS NULL AND idrelation > $max_id;
		";

		// INDEX DE PERSONNES : tables auteurs, entities_auteurs et relations
		$result = mysql_query('SELECT MAX(idrelation) FROM ' . $GLOBALS['tp'] . 'relations') or die('<font COLOR=red>' . mysql_error() . '<p>requete : ' . $cmd . '</font><br>');
		$max_id = mysql_result($result, 0);
		$query .= "REPLACE INTO _PREFIXTABLE_auteurs (idperson, nomfamille, prenom) SELECT id, g_familyname, g_firstname from _PREFIXTABLE_persons;
		INSERT INTO _PREFIXTABLE_relations (id2, id1) SELECT DISTINCT idpersonne, identite from _PREFIXTABLE_entites_personnes__old;
		UPDATE _PREFIXTABLE_relations SET nature='G', degree=1 WHERE degree IS NULL AND idrelation > $max_id;
		INSERT INTO _PREFIXTABLE_entities_auteurs (idrelation, prefix, affiliation, fonction, description, courriel) SELECT DISTINCT idrelation, prefix, affiliation, fonction, description, courriel from relations, entites_personnes__old where nature='G' and idpersonne=id2 and identite=id1;
		";
		mysql_free_result($result);

		if ($err = $this->__mysql_query_cmds($query)) {
				die($err);
		} else {
			$nature = array('1'=>'G', '2'=>'E');
			$j = 1;
			while($j < 3) {
				$result = mysql_query("SELECT * FROM " . $GLOBALS['tp'] . "relations WHERE nature='".$nature[$j]."' AND id1 != 0 ORDER BY id1, id2") or die('<font COLOR=red>' . mysql_error() . '<p>requete : ' . $cmd . '</font><br>');
				while($res = mysql_fetch_array($result)) {
					$i = 1;
					$re = mysql_query("SELECT id2 FROM " . $GLOBALS['tp'] . "relations WHERE id1 = '".$res['id1']."' AND nature = '".$nature[$j]."' ORDER BY id2") or die('<font COLOR=red>' . mysql_error() . '<p>requete : ' . $cmd . '</font><br>');
					while($resu = mysql_fetch_array($re)) {
						mysql_query("UPDATE " . $GLOBALS['tp'] . "relations SET degree = ".$i." WHERE id1 = '".$res['id1']."' AND id2 = '".$resu['id2']."' AND nature = '".$nature[$j]."'") or die('<font COLOR=red>' . mysql_error() . '<p>requete : ' . $cmd . '</font><br>');
						$i++;
					}
				}
				$j++;
			}
			echo '<p>insert_index_data ok</p>';
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
		$query .= "UPDATE _PREFIXTABLE_types SET tpledition = 'edition',tplcreation = 'entities';";

		// CLASSES supplémentaires
		$query .= "INSERT IGNORE INTO `_PREFIXTABLE_classes` (`id` , `icon` , `class` , `title` , `altertitle` , `classtype` , `comment` , `rank` , `status` , `upd` ) VALUES
		(NULL, 'lodel/icons/doc_annexe.gif', 'fichiers', 'Fichiers', '', 'entities', '', '5', '32', NOW()),
		(NULL, 'lodel/icons/lien.gif', 'liens', 'Sites', '', 'entities', '', '6', '32', NOW()),
		(NULL, 'lodel/icons/texte_simple.gif', 'textessimples', 'Textes simples', '', 'entities', '', '3', '32', NOW()),
		(NULL, 'lodel/icons/individu.gif', 'individus', 'Personnes', '', 'entities', '', '4', '1', NOW()),
		(NULL, 'lodel/icons/index_avance.gif', 'indexavances', 'Index avancés', '', 'entries', '', '10', '1', NOW());
		
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

		// TYPES
		$result = mysql_query('SELECT MAX(id) FROM ' . $GLOBALS['tp'] . 'types');
		$max_id = mysql_result($result, 0);
		mysql_query(utf8_encode("INSERT INTO " . $GLOBALS['tp'] . "types (id, icon, type, title, altertitle, class, tpl, tplcreation, tpledition, import, display, creationstatus, search, public, gui_user_complexity, oaireferenced, rank, status, upd) VALUES 

		(NULL, 'lodel/icons/rubrique_plat.gif', 'rubriqueaplat', 'Sous-partie', '', 'publications', '', 'entities', 'edition', '0', 'unfolded', '-1', '1', '0', '16', '0', '6', '32', NOW()),
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
		(NULL, '', 'sonannexe', 'Document sonore placé en annexe', '', 'fichiers', '', 'entities', '', '0', 'advanced', '-1', '1', '0', '32', '0', '6', '1', NOW()),
		(NULL, '', 'imageaccroche', 'Image d\'accroche', '', 'fichiers', 'image', 'entities', '', '0', 'advanced', '-1', '1', '0', '16', '0', '31', '32', NOW()),
		(NULL, 'lodel/icons/rubrique.gif', 'rubriqueannuaire', 'Rubrique (d\'annuaire de site)', '', 'publications', 'sommaire', 'entities', 'edition', '0', '', '-1', '1', '0', '16', '0', '32', '32', NOW()),
		(NULL, '', 'rubriquemediatheque', 'Rubrique (de médiathèque)', '', 'publications', 'sommaire', 'entities', 'edition', '0', '', '-1', '1', '0', '16', '0', '33', '32', NOW()),
		(NULL, 'lodel/icons/rubrique.gif', 'rubriqueequipe', 'Rubrique (d\'équipe)', '', 'publications', 'sommaire', 'entities', 'edition', '0', 'unfolded', '-1', '1', '0', '16', '0', '34', '32', NOW()),
		(NULL, 'lodel/icons/rubrique.gif', 'rubriqueactualites', 'Rubrique (d\'actualités)', '', 'publications', 'sommaire', 'entities', 'edition', '0', '', '-1', '1', '0', '16', '0', '35', '32', NOW());")) or die('<font COLOR=red>' . mysql_error() . '<p>requete : ' . $cmd . '</font><br>');

		$prerequete = "INSERT INTO _PREFIXTABLE_entitytypes_entitytypes (identitytype, identitytype2, cond) VALUES ('8', '0', '*'),
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
				('18', '327', '*'),
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
				('18', '10', '*'),
				('23', '9', '*'),
				('23', '21', '*'),
				('24', '21', '*'),
				('12', '8', '*'),
				('12', '21', '*'),
				('18', '9', '*'),
				('18', '8', '*'),
				('13', '8', '*'),
				('13', '21', '*'),
				('22', '21', '*'),
				('22', '19', '*'),
				('18', '21', '*'),
				('18', '19', '*'),
				('18', '20', '*'),
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
				('18', '11', '*'),
				('18', '328', '*'),
				('17', '329', '*'),
				('326', '5', '*'),
				('326', '18', '*'),
				('326', '14', '*'),
				('81', '8', '*');";

		$correspondances = array('editorial'=>'1', 
					'article'=>'2',
					'actualite'=>'3',
					'compte rendu'=>'4',
					'note de lecture'=>'5',
					'informations'=>'6',
					'chronique'=>'7',
					'collection'=>'8',
					'numero'=>'9',
					'rubrique'=>'10',
					'rubriqueaplat'=>'11',
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
		$result = mysql_query("SELECT id, type FROM " . $GLOBALS['tp'] . "types WHERE id > ".$max_id.";");
		while($res = mysql_fetch_array($result)) {
			$prerequete = str_replace("'".$correspondances[$res['type']]."'", "'".$res['id']."'", $prerequete);
		}

		$query .= $prerequete;
		$query .= "UPDATE _PREFIXTABLE_types SET class = 'liens', tpl = 'lien', tplcreation = 'entities', tpledition = '', display = 'advanced' WHERE type = 'documentannexe-liendocument' OR type = 'documentannexe-lienpublication' OR type = 'documentannexe-lienexterne';";
		$query .= "UPDATE _PREFIXTABLE_types SET class = 'fichiers', tpl = 'image', tplcreation = 'entities', display = 'advanced', tpledition = '' WHERE type = 'documentannexe-lienfichier';";
		$query .= "UPDATE _PREFIXTABLE_types SET display = 'advanced' WHERE type = 'documentannexe-lienfichier';";
		$query .= "UPDATE _PREFIXTABLE_types SET display = 'unfolded' WHERE type = 'regroupement';";
		$query .= "UPDATE _PREFIXTABLE_types set tpledition = '' WHERE class = 'textes';";
		$query .= "UPDATE _PREFIXTABLE_types set tpl = '' WHERE class = 'textessimples';";
		$query .= "DELETE FROM _PREFIXTABLE_types where type = 'documentannexe-lienfacsimile';";

		// entrytypes
		$query .= "UPDATE _PREFIXTABLE_entrytypes SET tpl = 'entree', tplindex = 'entrees', edition = 'pool';";
		$query .= "INSERT INTO _PREFIXTABLE_entrytypes (id, icon, type, class, title, altertitle, style, g_type, tpl, tplindex, gui_user_complexity, rank, status, flat, newbyimportallowed, edition, sort, upd) VALUES
		(NULL, '', 'motscleses', 'indexes', 'Indice de palabras clave', '', 'palabrasclaves, .palabrasclaves, motscleses', '', 'entree', 'entrees', '64', '9', '1', '0', '1', 'pool', 'sortkey', NOW()),
		(NULL, '', 'licence', 'indexavances', 'Licence portant sur le document', '', 'licence, droitsauteur', 'dc.rights', 'entree', 'entrees', '16', '7', '1', '1', '1', 'select', 'rank', NOW()),
		(NULL, '', 'motsclede', 'indexes', 'Schlusselwortindex', '', 'schlusselworter, .schlusselworter, motsclesde', '', 'entree', 'entrees', '32', '8', '1', '0', '0', 'pool', 'sortkey', NOW()),
		(NULL, '', 'motsclesen', 'indexes', 'Index by keyword', '', 'keywords,motclesen', '', 'entree', 'entrees', '64', '2', '1', '1', '1', 'pool', 'sortkey', NOW());
		";
		$query .= "UPDATE _PREFIXTABLE_entrytypes SET style = 'geographie, gographie,.geographie' WHERE type = 'geographie';";
		$query .= "UPDATE _PREFIXTABLE_entrytypes SET style = 'themes,thmes,.themes', gui_user_complexity = 16, rank = 6 WHERE type = 'theme';";
		$query .= "UPDATE _PREFIXTABLE_entrytypes SET type = 'chrono', style = 'periode, .periode, priode', rank = 5 WHERE type = 'periode';";
		$query .= "UPDATE _PREFIXTABLE_entrytypes SET title = 'Index de mots-clés', style = 'motscles, .motcles,motscls,motsclesfr', g_type = 'dc.subject', gui_user_complexity = 32 WHERE type = 'motcle';";

		// OPTIONS
		$query .= "INSERT INTO _PREFIXTABLE_optiongroups (id, idparent, name, title, altertitle, comment, logic, exportpolicy, rank, status, upd) VALUES
			('4', '0', 'from07', 'Suite import de données de Lodel 0.7', '', '', '', '1', '1', '32', NOW()),
			('1', '0', 'servoo', 'Servoo', '', '', 'servooconf', '1', '1', '32', NOW()),
			('2', '0', 'metadonneessite', 'Métadonnées du site', '', '', '', '1', '2', '1', NOW()),
			('3', '0', 'oai', 'OAI', '', '', '', '1', '5', '1', NOW());
		UPDATE _PREFIXTABLE_options SET idgroup = 4, title = 'Signaler par mail' WHERE name = 'signaler_mail';
		UPDATE _PREFIXTABLE_options SET idgroup = 3, userrights = 40 WHERE name LIKE 'oai_%';
		UPDATE _PREFIXTABLE_options SET idgroup = 2, title = 'ISSN électronique', name = 'ISSN_electronique', userrights = 30, rank = 7, status = 32 WHERE name = 'issn_electronique';
		UPDATE _PREFIXTABLE_options SET type = 'tinytext' WHERE type = 's';
		UPDATE _PREFIXTABLE_options SET type = 'passwd' WHERE type = 'pass';
		UPDATE _PREFIXTABLE_options SET type = 'email' WHERE type = 'mail';
		UPDATE _PREFIXTABLE_options SET title = 'oai_allow' WHERE name = 'oai_allow';
		UPDATE _PREFIXTABLE_options SET title = 'oai_deny' WHERE name = 'oai_deny';
		UPDATE _PREFIXTABLE_options SET title = 'Email de l\'administrateur du dépôt' WHERE name = 'oai_email';
		INSERT INTO _PREFIXTABLE_options (id, idgroup, name, title, type, defaultvalue, comment, userrights, rank, status, upd, edition, editionparams) VALUES 
			(NULL, '1', 'url', 'url', 'tinytext', '', '', '40', '1', '32', NOW(), 'editable', ''),
			(NULL, '1', 'username', 'username', 'username', '', '', '40', '2', '32', NOW(), 'editable', ''),
			(NULL, '1', 'passwd', 'password', 'passwd', '', '', '40', '3', '32', NOW(), '', ''),
			(NULL, '2', 'titresite', 'Titre du site', 'tinytext', 'Titresite', '', '40', '1', '1', NOW(), '', ''),
			(NULL, '2', 'titresiteabrege', 'Titre abrégé du site', 'tinytext', 'Titre abrégé du site', '', '40', '3', '1', NOW(), '', ''),
			(NULL, '2', 'descriptionsite', 'Description du site', 'text', '', '', '40', '4', '1', NOW(), 'textarea', ''),
			(NULL, '2', 'urldusite', 'URL officielle du site', 'url', '', '', '40', '5', '1', NOW(), 'editable', ''),
			(NULL, '2', 'issn', 'ISSN', 'tinytext', '', '', '30', '6', '1', NOW(), 'editable', ''),
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
			(NULL, '2', 'soustitresite', 'Sous titre du site', 'tinytext', '', '', '40', '2', '1', NOW(), 'editable', '');
		";

		// persontypes
		$query .= "INSERT INTO _PREFIXTABLE_persontypes (id, icon, type, title, altertitle, class, style, g_type, tpl, tplindex, gui_user_complexity, rank, status, upd) VALUES 
		(NULL, '', 'traducteur', 'Traducteur', '', 'auteurs', 'traducteur', 'dc.contributor', 'personne', 'personnes', '64', '2', '1', NOW()),
		(NULL, '', 'auteuroeuvre', 'Auteur d\'une oeuvre commentée', '', 'auteurs', 'auteuroeuvre', '', 'personne', 'personnes', '64', '4', '32', NOW()),
		(NULL, '', 'editeurscientifique', 'Editeur scientifique', '', 'auteurs', 'editeurscientifique', '', 'personne', 'personnes', '64', '5', '1', NOW());
		";
		$query .= "UPDATE _PREFIXTABLE_persontypes SET icon = 'lodel/icons/auteur.gif', g_type = 'dc.creator', tpl = 'personne', tplindex = 'personnes', gui_user_complexity = 32 WHERE title = 'Auteur';";
		$query .= "UPDATE _PREFIXTABLE_persontypes SET type = 'directeurdelapublication', style = 'directeur', tpl = 'personne', tplindex = 'personnes', gui_user_complexity = 32 WHERE title = 'Directeur de la publication';";


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
			(NULL, 'remerciements', '-*', '', '0', '31', '1', NOW());";

		// textes
		$query .= "ALTER TABLE _PREFIXTABLE_textes CHANGE notebaspage notesbaspage text, ADD addendum text, ADD dedicace text, ADD ocr tinyint(4) default NULL, ADD documentcliquable tinyint(4) default NULL, ADD altertitre text, ADD titreoeuvre text, ADD noticebibliooeuvre text, ADD datepublicationoeuvre tinytext, ADD ndla text, ADD numerodocument double default NULL;";

		// publications
		$query .= "ALTER TABLE _PREFIXTABLE_publications ADD periode tinytext, ADD isbn tinytext, ADD paraitre tinyint(4) default NULL, ADD numero tinytext, ADD langue varchar(5) default NULL, ADD altertitre text, ADD urlpublicationediteur text, ADD descriptionouvrage text;";

		// tablefieldgroups
		$query .= "DELETE FROM _PREFIXTABLE_tablefieldgroups;
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
			('30', 'grdescription', 'individus', 'Description', '', '', '1', '21', NOW());";

		// tablefields
		mysql_query("DELETE FROM ".$GLOBALS['tp']."tablefields;") or die('<font COLOR=red>' . mysql_error() . '<p>requete : ' . $cmd . '</font><br>');
		mysql_query(utf8_encode("INSERT INTO ".$GLOBALS['tp']."tablefields (id, name, idgroup, class, title, altertitle, style, type, g_name, cond, defaultvalue, processing, allowedtags, gui_user_complexity, filtering, edition, editionparams, weight, comment, status, rank, upd) VALUES 
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
		(NULL, 'motcle', '15', 'textes', 'Index de mots-clés', '', '', 'entries', '', '', '', '', '', '64', '', 'editable', '', '0', '', '32', '2', NOW()),
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
		(NULL, 'paraitre', '11', 'publications', 'Cette publication est-elle Ã  paraitre ?', '', '', 'boolean', '', '*', '', '', '', '32', '', 'editable', '', '0', '', '32', '66', NOW()),
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
		(NULL, 'motsclede', '15', 'textes', 'Schlusselworter', '', '', 'entries', '', '', '', '', '', '64', '', 'editable', '', '0', '', '1', '122', NOW()),
		(NULL, 'urlpublicationediteur', '13', 'publications', 'Voir sur le site de l\'éditeur', '', '', 'url', '', '*', '', '', '', '32', '', 'editable', '', '0', '', '1', '123', NOW()),
		(NULL, 'nombremaxitems', '6', 'liens', 'Nombre maximum d\'items du flux', '', '', 'int', '', '*', '', '', '', '16', '', 'editable', '', '0', '', '32', '124', NOW()),
		(NULL, 'descriptionouvrage', '12', 'publications', 'Description physique de l\'ouvrage', '', '', 'text', '', '*', '', '', '', '64', '', 'editable', '', '0', '', '32', '125', NOW()),
		(NULL, 'site', '0', 'entities_auteurs', 'Site', '', 'site, .site', 'url', '', '*', '', '', '', '32', '', 'editable', '', '4', '', '1', '6', NOW())")) or die('<font COLOR=red>' . mysql_error() . '<p>requete : ' . $cmd . '</font><br>');

		$query .= "UPDATE ".$GLOBALS['tp']."entities SET idtype = (SELECT id FROM ".$GLOBALS['tp']."types WHERE type = 'fichierannexe') WHERE idtype = (SELECT id from ".$GLOBALS['tp']."types WHERE type = 'documentannexe-lienfichier');";
		$query .= "DELETE FROM ".$GLOBALS['tp']."types WHERE type = 'documentannexe-lienfichier';";


		if ($err = $this->__mysql_query_cmds($query)) {
				die($err);
		} else {
			// index
			$i = 1;
			$result = mysql_query("SELECT id, langue, nom FROM $GLOBALS[tp]entrees__old;") or die('<font COLOR=red>' . mysql_error() . '<p>requete : ' . $cmd . '</font><br>');
			while ($row = mysql_fetch_assoc($result)) {
				unset($q);
				if($row['langue'] == 'fr') {
					$q = "SELECT id FROM ".$GLOBALS['tp']."entrytypes WHERE type = 'motcle';";
				} elseif($row['langue'] == 'en') {
					$q = "SELECT id FROM ".$GLOBALS['tp']."entrytypes WHERE type = 'motsclesen';";
				} elseif($row['langue'] == 'de') {
					$q = "SELECT id FROM ".$GLOBALS['tp']."entrytypes WHERE type = 'motsclede';";
				} elseif($row['langue'] == 'es') {
					$q = "SELECT id FROM ".$GLOBALS['tp']."entrytypes WHERE type = 'motscleses';";
				} else {
					$q = "SELECT id FROM ".$GLOBALS['tp']."entrytypes WHERE type = 'motcle';";
				}
				$resu = mysql_query($q) or die('<font COLOR=red>' . mysql_error() . '<p>requete : ' . $cmd . '</font><br>');
				while ($rows = mysql_fetch_array($resu)) {
					mysql_query("UPDATE ".$GLOBALS['tp']."entries SET idtype = '".$rows['id']."', rank = '".$i."', sortkey = \"".strtolower(trim($row['nom']))."\" WHERE id = " . $row['id'] . ';') or die('<font COLOR=red>' . mysql_error() . '<p>requete : ' . $cmd . '</font><br>');
				}
				$i++;
			}
			mysql_free_result($result);

			// maj identifier pour affichage correct des champs auteur lors de l'édition d'une entité
			// sans ça, seul les champs nom/prénom sont affichés
			$result = mysql_query("SELECT id, g_title FROM $GLOBALS[tp]entities") or die('<font COLOR=red>' . mysql_error() . '<p>requete : ' . $cmd . '</font><br>');
			while($res = mysql_fetch_array($result)) {
				$identifier = preg_replace(array("/\W+/", "/-+$/"), array('-', ''), makeSortKey(strip_tags($res['g_title'])));
				mysql_query("UPDATE $GLOBALS[tp]entities SET identifier = '".$identifier."' WHERE id = '".$res['id']."'") or die('<font COLOR=red>' . mysql_error() . '<p>requete : ' . $cmd . '</font><br>');
			}
			mysql_free_result($result);
			echo '<p>update_ME ok</p>';
			return true;
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

		foreach ($request as $cmd) {//echo $cmd . '<p>';
			$cmd=trim(preg_replace ("/^#.*?$/m","",$cmd));
			if ($cmd) {	
				if(preg_match("/(REPLACE|INSERT|UPDATE)/i", $cmd)) 
					$cmd = utf8_encode($cmd);
				if (!mysql_query($cmd)) {
					$err = '<font COLOR=red>' . mysql_error() . '<p>requete : ' . $cmd . '</font><br>';
					return $err; // sort, ca sert a rien de continuer
      				}
    			}
  		}
		return false;
	}

	public function cp_docs07_to_08()
	{
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
					AND ".$GLOBALS['tp']."documents__old.identite=entites__old.id 
					AND ".$GLOBALS['tp']."entites__old.idtype=types__old.id;";
		$req = mysql_query($query_select) or die('<font COLOR=red>' . mysql_error() . '<p>requete : ' . $cmd . '</font><br>');
		while($res = mysql_fetch_array($req)) {
			if($res['type'] != "documentannexe-lienfacsimile" && $res['type'] != "documentannexe-lienfichier") {
				$query .= "INSERT INTO ".$GLOBALS['tp']."liens (identity, titre, url) VALUES ('".$res['id']."', \"".$res['titre']."\", \"".$res['lien']."\");";
			} elseif($res['type'] == "documentannexe-lienfacsimile") {
				$query .= "UPDATE ".$GLOBALS['tp']."documents SET alterfichier = \"".$res['lien']."\" WHERE identity = '".$res['idparent']."';";
			} elseif($res['type'] == "documentannexe-lienfichier") {
				$query .= "INSERT INTO ".$GLOBALS['tp']."fichiers (identity, titre, document) VALUES ('".$res['id']."', \"".$res['titre']."\", \"".$res['lien']."\");";
			}
		}

		if ($err = $this->__mysql_query_cmds($query)) {
				die($err);
		} else {
			echo '<p>update_docannexes ok</p>';
			return true;
		}
	}

}


?>