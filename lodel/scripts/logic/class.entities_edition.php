<?php
/**	
 * Logique des entités - edition
 *
 * PHP version 4
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
 * @package lodel/logic
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mickaël Cixous, Sophie Malafosse
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 * @version CVS:$Id$
 */

require_once 'genericlogic.php';
/**
 * Classe de logique des entités (gestion de l'édition)
 * 
 * @package lodel/logic
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mickaël Cixous, Sophie Malafosse
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajouté depuis la version 0.8
 * @see logic.php
 */
class Entities_EditionLogic extends GenericLogic 
{

	/**
	 * Tableau des équivalents génériques
	 *
	 * @var array
	 */
	var $g_name;

	/**
	 * Constructeur
	 */
	function Entities_EditionLogic()
	{
		$this->GenericLogic('entities');
	}

	/**
	 * Affichage d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	function viewAction(&$context, &$error)
	{
		if ($context['check'] && $error) {
			return '_error';
		}
		if (!rightonentity($context['id'] ? 'edit' : 'create', $context)) {
			die('ERROR: you don\'t have the right to perform this operation');
		}

		// define some loop functions
		function loop_persons_in_entities($context, $funcname)
		{
			$varname = $context['varname'];
			if (!$varname) return;

			$idtype = $context['idtype'];
			if (!$idtype) {
				die("ERROR: internal error in Entities_EditionLogic::loop_persons_in_entities");
			}
			if (!is_numeric($idtype)) {
				return;
			}
		
			if (!$context['persons'][$idtype]) {
				call_user_func("code_alter_$funcname",$context);
				return;
			}

			foreach($context['persons'][$idtype] as $degree => $arr) {
				if (!is_numeric($degree)) {
					return;
				}
				$localcontext              = array_merge($context,$arr);
				$localcontext['name']      = $varname;
				$localcontext['classtype'] = "persons";
				$localcontext['degree']    = $degree;
				if ($degree > $maxdegree) {
					$maxdegree = $degree;
				}
				call_user_func("code_do_$funcname", $localcontext);
			}

			if (function_exists("code_after_$funcname")) {
				$localcontext = $context;
				$localcontext['maxdegree'] = $maxdegree;
				call_user_func("code_after_$funcname", $localcontext);
			}
		}	  
		/////
		function loop_entries_in_entities($context, $funcname) 
		{
			$varname = $context['varname'];
			if (!$varname) {
				return;
			}

			$idtype = $context['idtype'];
			if (!is_numeric($idtype)) {
				return;
			}
			$dao = &getDAO("entrytypes");
			$votype = $dao->getById($idtype, "id,sort,flat");
			if (!$votype) {
				die("ERROR: internal error in loop_entries_in_entities");
			}
			if ($context['entries'][$idtype]) {
				foreach ($context['entries'][$idtype] as $entry) {
					$checkarr[] = &$entry['g_name'];
				}
			}
			$context['id'] = 0; // start by the parents
			loop_entries_in_entities_rec ($context, $funcname, $votype, $checkarr);
		}
		
		function loop_entries_in_entities_rec ($context, $funcname, &$votype, &$checkarr) 
		{
			global $db;

			// get the entries
			$result = $db->execute(lq("SELECT * FROM #_TP_entries WHERE idtype='". $votype->id. "' AND idparent='". $context['id']. "' AND status>-64 ORDER BY ". $votype->sort)) or dberror();
			while (!$result->EOF) {
				$localcontext = array_merge($context, $result->fields);
				$localcontext['selected'] = $checkarr && in_array($result->fields['g_name'],$checkarr) ? "selected=\"selected\"" : "";
				call_user_func("code_do_$funcname", $localcontext);
				if (!$votype->flat) {
					$localcontext['root'].= $localcontext['g_name']. "/";
				}
				loop_entries_in_entities_rec ($localcontext, $funcname, $votype, $checkarr);
				$result->MoveNext();
			}
		}
		/////
		function loop_entities_select($context, $funcname)
		{
			global $db;
			$varname = $context['varname'];
			if (!$varname) {
				if (function_exists("code_alter_$funcname")) {
					call_user_func("code_alter_$funcname",$context);
				}
				return;
			}
			$ids = preg_split("/,/", $context['entities'][$varname], -1, PREG_SPLIT_NO_EMPTY);
			$result = $db->execute(lq("SELECT * FROM #_TP_entities WHERE status>-64 AND id ". sql_in_array($ids))) or dberror();
			while (!$result->EOF) {
				$localcontext = array_merge($context, $result->fields);
				call_user_func("code_do_$funcname", $localcontext);
				$result->MoveNext();
			}
			if (function_exists("code_after_$funcname")) {
				$localcontext = $context;
				$localcontext['all'] = $context['entities'][$varname];
				call_user_func("code_after_$funcname",$localcontext);
			}
		}
		/////
		$ret = GenericLogic::viewAction($context, $error);

		//if ((!$context['id'] || (preg_match ("/servoo.*/", $context['creationmethod']) &&  $context['status'] == -64)) && !$error) { // add
		if (!$context['id'] && !$error) { // add : récupération des valeurs par défaut
			$daotablefields = &getDAO("tablefields");
			$fields = $daotablefields->findMany("class='". $context['type']['class']. "' AND status>0 AND type!='passwd'", "",	"name,defaultvalue");
			foreach($fields as $field) {
				if (empty($context['data'][$field->name]))
				$context['data'][$field->name] = $field->defaultvalue;
			}
		}
	
		if ($context['check'] && !$error) {
			$context['status'] = -1;
			//il semble nécessaire de nettoyer request pour eviter les requetes pétées.
			require_once('func.php');
			clean_request_variable($context);
			return $this->editAction($context, $error);
		}
		return $ret ? $ret : "_ok";
	}



	/**
	 * Ajout d'un nouvel objet ou Edition d'un objet existant
	 *
	 * Ajout d'une nouvelle entité. Dans un premier temps on vérifie si l'utilisateur
	 * possède les bons droits pour modifier ou ajouter cette entité. Ensuite les différents
	 * champs de l'entité sont validés. Si ceux-ci sont valide, alors l'objet est créé dans
	 * la base de données et les objets liés sont aussi créés : personnes, entrées d'index
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	function editAction (&$context, &$error, $opt = false)
	{
		if (!rightonentity($context['id'] ? 'edit' : 'create', $context)) {
			die ("ERROR: you don't have the right to perform this operation");
		}
		if ($context['cancel']) {
			return '_back';
		}
		
		global $lodeluser, $home;
		$id = $context['id'];
		if ($id && (!isset($context['idparent']) || !isset($context['idtype']))) {
			$dao = $this->_getMainTableDAO();
			$vo  = $dao->getById($id, "idparent, idtype");
			$context['idparent'] = $vo->idparent;
			$context['idtype']   = $vo->idtype;
		}
		$idparent = $context['idparent'];
		$idtype   = $context['idtype'];
		$status   = intval($context['status']);
		$this->_isAuthorizedStatus($status);
		// iduser
		$context['iduser'] = !SINGLESITE && $lodeluser['adminlodel'] ? 0 : $lodeluser['id'];

		require_once 'entitiesfunc.php';
		if (!checkTypesCompatibility($id, $idparent, $idtype)) {
			$error['idtype'] = 'types_compatibility';
			return '_error';
		}

		// get the class 
		$daotype = &getDAO("types");
		$votype = $daotype->getById($context['idtype'], "class,creationstatus,search");
		$class = $context['class']=$votype->class;
		if (!$class) {
			die ("ERROR: idtype is not valid in Entities_EditionLogic::editAction");
		}
		
		// Récupération des valeurs par défaut pour les champs vides À L'IMPORT
		if ($context['lo'] == 'entities_import' && !empty($context['idtask']) && !$error) {
			$daotablefields = &getDAO("tablefields");
			$fields = $daotablefields->findMany("class='". $context['class']. "' AND status>0 AND type!='passwd'", "",	"name,defaultvalue");
			foreach($fields as $field) {
				if (empty($context['data'][$field->name]))
				$context['data'][$field->name] = $field->defaultvalue;
			}
		}

		if (!$this->validateFields($context, $error)) {
			// error.
			// if the entity is imported and will be checked
			// that's fine, let's continue, if not return an error
			if ($opt == FORCE) { 
				$status=-64;
				$ret="_error";
			}
			if ($status>-64) {
				return "_error";
			}
		}

		// get the dao for working with the object
		$dao = $this->_getMainTableDAO();
		$now = date("Y-m-d H:i:s");
		if ($id) { //edit the entity
			$new = false;
			$vo = $dao->getById($id, "id,identifier,status, g_title");
			if ($vo->status >= 16) {
				die("ERROR: entity is locked. No operation is allowed");
			}
			// change the usergroup of the entity ?
			if ($lodeluser['admin'] && $context['usergroup']) {
				$vo->usergroup = intval ($context['usergroup']);
			}
			if ($vo->status<=-64) {  // like a creation
				$vo->status = $votype->creationstatus;
				$vo->creationdate = $now;
			}
		} else { //create the entity
			$new = true;
			$vo = $dao->createObject ();
			$vo->idparent       = $idparent;
			$vo->usergroup      = $this->_getUserGroup ($context, $idparent);
			$vo->iduser         = $context['iduser'];
			$vo->status         = $status ? $status : $votype->creationstatus;
			$vo->creationdate   = $now;
		}
		$vo->modificationdate = $now;
		// populate the entity
		if ($idtype) {
			$vo->idtype=$idtype;
		}
		// permanent identifier management
		$dctitle = $this->getGenericEquivalent ($class, 'dc.title');
		if ($dctitle) {
			$vo->g_title = strip_tags ($context['data'][$dctitle], "<em><strong><span><sup><sub>");
		}
		// If Identifier is not set, let's calcul it with the generic title
		
		if (!$vo->identifier || trim($context['identifier']) === '') {
			$vo->identifier = $this->_calculateIdentifier ($id, $vo->g_title);
		}	else { // else simply clean bad chars

			/*if (is_null ($context['identifier'])) {//identifier desactivated
				$vo->identifier = $this->_calculateIdentifier ($id, $vo->identifier);
			} else {// else that means that we have modified it*/
				$vo->identifier= $this->_calculateIdentifier ($id, $context['identifier']);
			/*}*/
		}
		
		if ($context['creationmethod']) {
			$vo->creationmethod = $context['creationmethod'];
		}
		if ($context['creationinfo']) {
			$vo->creationinfo = $context['creationinfo'];
		}
	
		$id = $context['id'] = $dao->save($vo);
		// change the group recursively
		$gdao = &getGenericDAO ($class, "identity");
		$gdao->instantiateObject ($gvo);
		$context['data']['id'] = $context['id'];
		$this->_moveImages ($context['data']);
		$this->_populateObject ($gvo, $context['data']);
		$gvo->identity = $id;
		$this->_moveFiles ($id, $this->files_to_move, $gvo);
		$gdao->save ($gvo, $new);  // save the related table
		if ($new) {
			$this->_createRelationWithParents ($id, $idparent);
		}
		$this->_saveRelatedTables ($vo, $context);
		// check if the entities have an history field defined
		$this->_processSpecialFields ('history', $context);
		
		if ($ret=="_error") {
			return "_error";
		}
		if ($votype->search) {
			require_once "class.entities_index.php";
			$lo_entities_index = new Entities_IndexLogic();
			$lo_entities_index->addIndexAction($context, $error);
		}
		update();

		if ($context['visualiserdocument'] || $_GET['visualiserdocument']) {
			return "_location: ". SITEROOT. makeurlwithid($id);
		}
		return $ret ? $ret : "_back";
	} //end of editAction

	/**
	 * Construction des balises select HTML pour cet objet
	 *
	 * @param array &$context le contexte, tableau passé par référence
	 * @param string $var le nom de la variable du select
	 * @param string $edittype le type d'édition
	 */
	function makeSelect(&$context, $var, $edittype)
	{
		switch($var) {
		case 'creationinfo':
			if ($context['creationmethod'] && $context['creationmethod']!='form') {
				die("ERROR: error creationmethod must be 'form' to choose the creationinfo");
			}
			$arr = array("xhtml"=>"XHTML", "wiki"=>"Wiki", "bb"=>"BB");
			renderOptions($arr, $context['creationinfo']);
			break;
		}
		switch($edittype) {
		case 'lang':
			require_once 'lang.php';
			makeselectlangs($context[$var]);
			break;
		}
	}

	/**
	 * Déplace les liens images quand un nouvel identifiant est connu
	 *
	 * Cette méthode n'est pas définie ici.
	 *
	 * @param array &$context le contexte, tableau passé par référence
	 * @access private
	 */
	function _moveImages(&$context) {}

	/**
	 * Sauve des données dans des tables liées éventuellement
	 *
	 * Appelé par editAction pour effectuer des opérations supplémentaires de sauvegarde.
	 *
	 * @param object $vo l'objet qui a été créé
	 * @param array $context le contexte
	 */
	function _saveRelatedTables($vo,$context) 
	{
		global $db;
		if (!$vo->status) {
			$dao = $this->_getMainTableDAO();
			$vo  = $dao->getById($vo->id, 'status,id');
		}
		if ($vo->status>-64 && $vo->status<=-1) {
			$status = -1;
		}
		if ($vo->status >= 1) {
			$status = 1;
		}
		//Mise à jour des softs relations
		$entities_logic = &getLogic('entities');
		$entities_logic->_publishSoftRelation(array($vo->id), $vo->status);

		// Entries and Persons
		foreach (array ('entries' => 'E', 'persons' => 'G') as $table => $nature) {
			// put the id's from entrees and autresentrees into idtypes
			$idtypes = $context[$table] ? array_keys ($context[$table]) : array();
			if (!$idtypes) {
				continue;
			}
			$logic = &getLogic($table);
			$ids         = array();
			$idrelations = array();
			foreach ($idtypes as $idtype) {
				$itemscontext = $context[$table][$idtype];
				if (!$itemscontext) {
					continue;
				}
				$degree = 1;
				foreach ($itemscontext as $k => $itemcontext) {
					if (!is_numeric($k)) {
						continue;
					}
					$itemcontext['identity'] = $vo->id;
					$itemcontext['idtype']   = $idtype;
					$itemcontext['status']   = $status;
					$itemcontext['degree']   = $degree++;
					//$ret = $logic->editAction($itemcontext, $error, 'CLEAN');
					$ret = $logic->editAction($itemcontext, $error);
					if ($ret!="_error" && $itemcontext['id']) {
						$ids[$idtype][] = $itemcontext['id'];
						if ($itemcontext['idrelation']) {
							$idrelations[] = $itemcontext['idrelation'];
						}
					}
				}
			}
			// delete relation not used
			if ($ids && !$idrelations) { // new item but relation has not been created
				if (!$vo->id) {
					trigger_error("ERROR: internal error in Entities_EditionLogic ::_saveRelatedTables");
				}
				$values      = array ();
				$idrelations = array ();
				foreach ($ids as $ids2) {
					$degree = 1;
					foreach ($ids2 as $id) {
						$db->execute (lq ("REPLACE INTO #_TP_relations (id2,id1,nature,degree) VALUES ('". $id. "','". $vo->id."','". $nature."','". ($degree++). "')")) or dberror ();
						$idrelations[] = $db->insert_id();
					}
				}
			}
			$criteria = $idrelations ? "AND idrelation NOT IN ('". join ("','", $idrelations). "')" : "";
			
			#echo "criteria=$criteria";
			$this->_deleteSoftRelation ("id1='". $vo->id. "' ". $criteria, $nature);
		} // foreach entries and persons
		// Entities
		if ($context['entities']) {
			$dao = getDAO('tablefields');
			foreach (array_keys ($context['entities']) as $name) {
				$name = addslashes ($name);
				$vofield = $dao->find("class='". $context['class']. "' AND name='". $name. "' AND type='entities'");
				if (!$vofield) {
					trigger_error ("invalid name for field of type entities", E_USER_ERROR);
				}
				$idrelations = array ();
				if (is_array($context['entities'][$name])) {
					foreach ($context['entities'][$name] as $id) {
						$db->execute (lq ("REPLACE INTO #_TP_relations (id2,id1,nature,degree) VALUES ('". $id. "','". $vo->id. "','". $name. "','0')")) or dberror ();
						$idrelations[] = $db->insert_id();
					}
				}
				$criteria = $idrelations ? "AND idrelation NOT IN ('". join("','", $idrelations). "')" : "";
				$this->_deleteSoftRelation("id1='". $vo->id. "' ". $criteria, $name);
			} // for each entities fields
		}
	} //end of function

	/**
	 * Suppression des relations dites 'soft'
	 *
	 * Suppression des personnes ou entrées d'indexs liées à une entité
	 *
	 * @param array $ids les identifiants des objets liés
	 * @param string $nature la nature des objets liés
	 */
	//most of this should be transfered in the entries and persons logic
	function _deleteSoftRelation ($ids, $nature = '')
	{
		global $db;
		if (is_array ($ids)) {
			$criteria = "id1 IN ('".join ("','", $ids). "')";
		} elseif (is_numeric ($ids)) {
			$criteria = "id1='". $ids. "'";
		} else {
			$criteria = $ids;
		}

		if ($nature == 'E') {
			$naturecriteria = " AND nature='E'";
		} elseif ($nature == 'G') {
			$naturecriteria = " AND nature='G'";
			$checkjointtable = true;
		} elseif (strlen ($nature)>1) {
			$naturecriteria = " AND nature='". $nature. "'";
			$checkjointtable = false;
		} else  {
			$naturecriteria = " AND nature IN ('G','E') OR LENGTH(nature)>1";
			$checkjointtable = true;
		}

		if ($checkjointtable) {
		// with Mysql 4.0 we could do much more rapid stuff using multiple delete. How is it supported by PostgreSQL, I don't not... so brute force:
		// get the joint table first
			$dao = &getDAO('relations');
			$vos = $dao->findMany($criteria. $naturecriteria, '', 'idrelation');
			$ids = array();
			foreach ($vos as $vo) { 
				$ids[]= $vo->idrelation; 
			}
			if ($ids) {
				// getting the tables name from persons and persontype would be to long. Let's suppose
				// the number of classes are low and it is worse trying to delete in all the tables
				$dao    = &getDAO('classes');
				$tables = $dao->findMany("classtype='persons'", '', 'class');
				$where  = "idrelation IN ('".join("','",$ids)."')";
				foreach($tables as $table) {
					$db->execute(lq("DELETE FROM #_TP_entities_". $table->class. " WHERE ". $where)) or dberror();
				}
			}
		}
		$db->execute(lq("DELETE FROM #_TP_relations WHERE ". $criteria. $naturecriteria)) or dberror();

		// select all the items not in entities_$table
		// those with status<=1 must be deleted
		// thise with status> must be depublished
		foreach(array('entries', 'persons') as $table) {
			$result = $db->execute (lq ("SELECT id,status FROM #_TP_$table LEFT JOIN #_TP_relations ON id2=id WHERE id1 is NULL")) or dberror();
			$idstodelete    = array();
			$idstounpublish = array();
			while (!$result->EOF) {
				if (abs ($result->fields['status']) == 1) {
					$idstodelete[]    = $result->fields['id']; 
				} else {
					$idstounpublish[] = $result->fields['id']; 
				}
				$result->MoveNext();
			}

			if ($idstodelete) {
				$logic = &getLogic($table);
				$localcontext = array('id' => $idstodelete, 'idrelation' => array());
				$err = array();
				$logic->deleteAction($localcontext, $err);
			}

			if ($idstounpublish) {
				$db->execute(lq("UPDATE #_TP_$table SET status=-abs(status) WHERE id IN (". join(",", $idstounpublish). ") AND status>=32")) or dberror();
			}
		} //end of foreach tables
	}



	/**
	 * Récupère le groupe utilisateur d'une nouvelle entité
	 * return the usergroup for new entity
	 *
	 * @param array $contextle contexte
	 * @param integer $idparent identifiant du parent
	 */
	function _getUserGroup($context, $idparent)
	{
		global $lodeluser, $db;
		if ($lodeluser['admin']) { // take it from the context.
			$usergroup = intval($context['usergroup']);
			if ($usergroup > 0) {
				return $usergroup;
			}
		}
		if ($idparent) { // take the group of the parent
			$dao = $this->_getMainTableDAO();
			$vo = $dao->getById($idparent, 'id, usergroup');
			$usergroup = $vo->usergroup;
			if ($db->errorno()) {
				dberror();
			}
			if (!$usergroup) {
				die("ERROR: You have not the rights: (2)");
			}
		} else {
			$usergroup = 1;
			# die("ERROR: Only administrator have the rights to add an entity at this level");
		}
		return $usergroup;
	}

	/**
	 * Création des relations avec les parents d'une entité
	 *
	 * Cette méthode crée les relations entre une entité et tous ses ancêtres. 
	 *
	 * @param integer identifiant de la nouvelle entité
	 * @param integer identifiant du parent direct de la nouvelle entité
	*/
	function _createRelationWithParents($id, $idparent)
	{
		global $db;
		// can't do INSERT SELECT because work on the same table... support for old MySQL version
		$result = $db->execute(lq("SELECT id1,degree FROM #_TP_relations WHERE id2='".$idparent."' AND nature='P'")) or dberror();
		while (!$result->EOF) {
			$values.= "('". $result->fields['id1']. "','$id','P','". ($result->fields['degree']+1). "'),";
			$result->MoveNext();
		}
		$values.= "('". $idparent. "','". $id. "','P',1)";
		$db->execute(lq("REPLACE INTO #_TP_relations (id1,id2,nature,degree) VALUES ".$values)) or dberror();
		//if ($lock) unlock();
	}

	/**
	 * Utilisé dans la méthode viewAction() pour remplir le contexte d'information supplémentaire
	 *
	 * @param object &$vo l'objet utilisé dans viewAction
	 * @param array &$context le contexte, tableau passé par référence
	 */
	function _populateContextRelatedTables(&$vo, &$context)
	{
		global $db;
		foreach (array('entries' => array('E', 'identry', 'entrytypes'),
				'persons' => array('G', 'idperson', 'persontypes')) as $table => $info) {
			list($nature, $idfield, $type) = $info;
			$degree = 0;
			
			$result = $db->execute(lq("SELECT #_TP_$table.*,#_TP_relations.idrelation,#_TP_relations.degree,#_TP_$type.class FROM #_TP_$table INNER JOIN #_TP_relations ON id2=#_TP_$table.id INNER JOIN #_TP_$type ON #_TP_$table.idtype=#_TP_$type.id WHERE  id1='".$vo->id. "' AND nature='". $nature. "'  ORDER BY degree")) or dberror();
			$relatedtable         = array();
			$relatedrelationtable = array();
			while (!$result->EOF) {
				$degree = $result->fields['degree'] ? $result->fields['degree'] : (++$degree);
				unset($ref); // detach from the other references
				$ref   = $result->fields;
				$class = $result->fields['class'];
				$relatedtable[$class][$result->fields['id']] = &$ref;
				if ($table == "persons") {
					$relatedrelationtable[$class][$result->fields['idrelation']] = &$ref;
				}
				$context[$table][$result->fields['idtype']][$degree] = &$ref;
				$result->MoveNext();
			}

			// load related table
			if ($relatedtable) {
				foreach ($relatedtable as $class => $ids) {
					$result2=$db->execute(lq("SELECT * FROM #_TP_".$class." WHERE ".$idfield." ".sql_in_array(array_keys($ids)))) or dberror();
					while (!$result2->EOF) {
						$id = $result2->fields[$idfield];
						settype($ids[$id]['data'], "array");
						$ids[$id]['data'] = array_merge($ids[$id]['data'], $result2->fields);
						$result2->MoveNext();
					}
				}
			}
			// load relation related table
			if ($relatedrelationtable) {
				foreach ($relatedrelationtable as $class=>$ids) {
					$result2 = $db->execute(lq("SELECT * FROM #_TP_entities_".$class." WHERE idrelation ".sql_in_array(array_keys($ids)))) or dberror();
					while (!$result2->EOF) {
						$id = $result2->fields['idrelation'];
						$ids[$id]['data'] = array_merge($ids[$id]['data'], $result2->fields);
						$result2->MoveNext();
					}
				}
			}

		} // foreach classtype
		
		// Entities
		$result = $db->execute(lq("SELECT * FROM #_TP_relations WHERE  id1='". $vo->id. "' AND LENGTH(nature)>1")) or dberror();
		$relations = array();
		while(!$result->EOF) {
			$relations[$result->fields['nature']][] = $result->fields['id2'];
			$result->MoveNext();
		}
		foreach($relations as $k=>$v) {
			$context['entities'][$k] = join(",", $v);
		}
	}

	/**
	 * Calcul des identifiants littéraux d'une entité
	 *
	 * Cette méthode permet de générer un identifiant littéral, utilisé pour les liens permanents
	 * pour une entité. Elle utilise son titre
	 *
	 * @param integer $id identifiant numérique de l'entité
	 * @param string $title titre générique de l'entité
	 */
	function _calculateIdentifier($id, $title)
	{
		global $db, $error;
		$identifier = preg_replace(array("/\W+/", "/-+$/"), array('-', ''), makeSortKey(strip_tags($title)));
		return $identifier;
	}
	
	// begin{publicfields} automatic generation  //
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //
	// end{uniquefields} automatic generation  //

} // class

?>
