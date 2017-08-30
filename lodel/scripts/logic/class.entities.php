<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */


/**
 * Classe de logique des entités
 *
 */
class EntitiesLogic extends Logic
{

	/**
	* generic equivalent assoc array
	*/
	public $g_name;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct("entities");
	}


	/**
	 * Affichage d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function viewAction(&$context, &$error)
	{
		trigger_error("EntitiesLogic::viewAction", E_USER_ERROR);
	}


	/**
	 * Changement du rang d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function changeRankAction(&$context, &$error, $groupfields = "", $status = "status>0")
	{
		global $db;
		if(empty($context['id']))
			trigger_error('ERROR: missing id in EntitiesLogic::changeRankAction', E_USER_ERROR);

		$id  = $context['id'];
		$vo  = $this->_getMainTableDAO()->getById($id,"idparent");
		if(!$vo)
			trigger_error('ERROR: invalid id', E_USER_ERROR);
		$this->_changeRank($id, isset($context['dir']) ? $context['dir'] : '', "status<64 AND idparent='". $vo->idparent. "'");
		update();
		return '_back';
	}

	/**
	 * Ajout d'un nouvel objet ou Edition d'un objet existant
	 *
	 * Cette méthode est abstraite ici. On utilise die() pour simuler le fonctionnement
	 * d'une méthode abstraite.
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function editAction(&$context,&$error, $clean = false)
	{
		trigger_error("EntitiesLogic::editAction", E_USER_ERROR);
	}


	/**
	 * Opérations de masse : suppression massive, publication ou dépublication massive
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function massAction(&$context,&$error)
	{
		if (empty($context['entity'])) {
			return "_back";
		}
		$context['id'] = array();
        	$entities = array_keys($context['entity']);
		foreach($entities as $id) {
			$context['id'][] = (int)$id;
		}

		if (isset($context['delete'])) {
			return $this->deleteAction($context,$error);
		} elseif (isset($context['publish'])) {
			$context['status'] = 1;
			return $this->publishAction($context,$error);
		} elseif (isset($context['unpublish'])) {
			$context['status'] = -1;
			return $this->publishAction($context,$error);
		}
		trigger_error("unknow mass operation",E_USER_ERROR);
	}


	/**
	 * Suppression d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function deleteAction(&$context, &$error)
	{
		global $db;
		// get the entities to modify and ancillary information
		if (!rightonentity("delete",$context)) trigger_error("ERROR: you don't have the right to perform this operation", E_USER_ERROR);
		$ids = $classes = $softprotectedids = $lockedids = null;

		if(empty($context['id']))
			return '_back';

		$this->_getEntityHierarchy($context['id'],"write","",$ids,$classes,$softprotectedids,$lockids);
		if (!$ids) {
			return '_back';
		}
		if ($lockedids)  {
			trigger_error("ERROR: some entities are locked in the family. No operation is allowed", E_USER_ERROR);
		}

		// needs confirmation ?
		if (!isset($context['confirm']) && $softprotectedids) {
			$context['softprotectedentities'] = $softprotectedids;
			$this->define_loop_protectedentities();
			return 'delete_confirm';
		}

		/* Éxécution des hooks */
		$this->_executeHooks($context, $error, 'pre');

		// delete all the entities
		$this->_getMainTableDAO()->deleteObject($ids);

		// delete in the joint table
		if(!empty($classes))
		{
			$classes = array_keys($classes);
		}

		foreach($classes as $class) {
			$db->execute(lq("DELETE FROM #_TP_$class WHERE identity ".sql_in_array($ids))) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}

		// delete the relations
		$this->_deleteSoftRelation($ids);

		// delete other relations
		$db->execute(lq("DELETE FROM #_TP_relations WHERE id1 ".sql_in_array($ids)." OR id2 ".sql_in_array($ids))) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

		// delete the entity from the search_engine table
		$db->execute(lq("DELETE FROM #_TP_search_engine WHERE identity ".sql_in_array($ids))) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

		/* Éxécution des hooks */
		$this->_executeHooks($context, $error, 'post');

		update();
		return '_back';
	}

	/**
	 * Publication ou dépublication d'une entité
	 *
	 * Change le status de l'entité à 1 (publication) ou -1 (dépublication).
	 * Fonction récursive
	 * Ne modifie pas les entités dont le status est inférieur ou égal à -8
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */

	public function publishAction (&$context, &$error) 
	{
		global $db;
		
		if (!isset($context['status']) || $context['status'] == 0) {
			trigger_error("ERROR: invalid status in EntitiesLogic::publishAction", E_USER_ERROR);
		}

		if(empty($context['id']))
			return '_back';

		// get the class
		$context['class'] = $db->getOne(lq("SELECT t.class from #_TP_entities as e, #_TP_types as t WHERE e.id=".$context['id']." AND e.idtype=t.id")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

		$status = $context['status'];
		$this->_isAuthorizedStatus($status);

		if (!rightonentity ($status > 0 ? 'publish' : 'unpublish', $context)) {
			trigger_error("ERROR: you don't have the right to perform this operation", E_USER_ERROR);
		}

		$this->_executeHooks($context, $error, 'pre');

		// get the entities to modify and ancillary information
		$access = abs ($status) >= 32 ? 'protect' : 'write';


		$this->_getEntityHierarchy($context['id'], $access,"#_TP_entities.status>-8", $ids, $classes, $softprotectedids, $lockedids);

		if (!$ids) {
			return '_back';
		}

		if ($lockedids && $status < 0) {
			trigger_error("ERROR: some entities are locked in the family. No operation is allowed", E_USER_ERROR);
		}

		// depublish protected entity ? need confirmation.
		if (empty($context['confirm']) && $status < 0 && $softprotectedids) {
			$context['softprotectedentities'] = $softprotectedids;
			$this->define_loop_protectedentities();
			return 'unpublish_confirm';
		}
		$criteria=" id IN (". join(",", $ids). ')';

		// mais attention, il ne faut pas reduire le status quand on publie
		if ($status > 0) {
			$criteria.= " AND status < '$status'";
		}
		//mise à jour des entités
		$db->execute(lq("UPDATE #_TP_entities SET status=$status WHERE ". $criteria)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

		// check if the entities have an history field defined
		$this->_processSpecialFields('history', $context, $status);

		//mise à jour des personnes et entrées liées à ces entités
		$this->_publishSoftRelation($ids, $status);
		update();

		$this->_executeHooks($context, $error, 'post');
		return '_back';
	}

	/**
	 * Suppression des relations dites 'soft'
	 *
	 * Suppression des personnes ou entrées d'indexs liées à une entité
	 *
	 * @param mixed $ids array d'id des entitées, int de l'entitée ou string critère WHERE SQL de sélection des entitées dans la table relation
	 * @param string $nature la nature des objets liés
	 */
	//most of this should be transfered in the entries and persons logic
	public function _deleteSoftRelation ($ids, $nature = '')
	{
		global $db;
		if (is_array ($ids)) {
			$criteria = "id1 IN ('".join ("','", $ids). "')";
		} elseif (is_numeric ($ids)) {
			$criteria = "id1='". $ids. "'";
		} elseif ( strlen($ids) > 0 ) {
			$criteria = $ids;
		}
		$checkjointtable = false;
		if ($nature == 'E') {
			$naturecriteria = " AND nature='E'";
		} elseif ($nature == 'G') {
			$naturecriteria = " AND nature='G'";
			$checkjointtable = true;
		} elseif (strlen ($nature)>1) {
			$naturecriteria = " AND nature='". $nature. "'";
		} else  {
			$naturecriteria = " AND ( nature IN ('G','E') OR LENGTH(nature)>1 )";
			$checkjointtable = true;
		}

		// Effacer les attributs des relations qui vont être effacé
		// TODO: il manque les attributs de relation des indexs là !!!!!
		if ($checkjointtable) {
		// with Mysql 4.0 we could do much more rapid stuff using multiple delete. How is it supported by PostgreSQL, I don't not... so brute force:
		// get the joint table first
			$dao = DAO::getDAO('relations');
			$vos = $dao->findMany($criteria. $naturecriteria, '', 'idrelation');
			$ids = array();
			foreach ($vos as $vo) { 
				$ids[]= $vo->idrelation; 
			}
			if ($ids) {
				// getting the tables name from persons and persontype would be to long. Let's suppose
				// the number of classes are low and it is worse trying to delete in all the tables
				$dao    = DAO::getDAO('classes');
				$tables = $dao->findMany("classtype='persons'", '', 'class');
				$where  = "idrelation IN ('".join("','",$ids)."')";
				foreach($tables as $table) {
					$db->execute(lq("DELETE FROM #_TP_entities_". $table->class. " WHERE ". $where)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				}
			}
		}
		$db->execute(lq("DELETE FROM #_TP_relations WHERE ". $criteria. $naturecriteria)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

		// select all the items not in entities_$table
		// those with status<=1 must be deleted
		// those with status> must be depublished
		// SAUF status == 21 == INDÉPUBLIABLE
		foreach(array('entries', 'persons') as $table) {
			$result = $db->execute (lq ("SELECT id,status FROM #_TP_$table LEFT JOIN #_TP_relations ON id2=id WHERE id1 is NULL AND status!=21")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
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
				$logic = Logic::getLogic($table);
				$localcontext = array('id' => $idstodelete, 'idrelation' => array());
				$err = array();
				$logic->deleteAction($localcontext, $err);
			}

			if ($idstounpublish) {
				$db->execute(lq("UPDATE #_TP_$table SET status=-abs(status) WHERE id IN (". join(",", $idstounpublish). ") AND status>=32")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
		}
	}

	/**
	 * Mise à jour du status des objets liées (liaisons 'soft', c'est à dire des personnes 
	 * ou des entrées d'index.
	 *
	 * Lors d'une publication c'est simple, le status des entrées ou personnes liées à l'entité
	 * est mis à +32 ou +1 suivant si l'entrée ou la personne est permanente.
	 *
	 * Lors d'une dépublication, c'est plus compliqué, il ne faut pas toucher aux entrées qui ont
	 * publiées par d'autres entités. Ensuite de la même manière le status est mis à -32 ou -1
	 *
	 * @param array les identifiants
	 * @param integer le status de l'entité concernée ou des entités concernées
	 * @access private
	 */
	public function _publishSoftRelation($ids, $status)
	{
		global $db;
		$criteria = "id1 IN (". join(",", $ids). ")";
		$status = $status > 0 ? 1 : -1; // dans les tables le status est seulement a +1 ou -1
		$result = $db->execute(lq("SELECT id2,nature FROM #_TP_relations WHERE nature IN ('E','G') AND ". $criteria)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		$ids = array();
		while (!$result->EOF) {
			if(!isset($ids[$result->fields['nature']])) $ids[$result->fields['nature']] = array();
			$ids[$result->fields['nature']][$result->fields['id2']] = true;
			$result->MoveNext();
		}
		if (!$ids) {
			return; // get back, nothing to do
		}
		foreach(array_keys($ids) as $nature) {
			$idlist = join(',', array_keys($ids[$nature]));
			$table = $nature == 'G' ? 'persons' : 'entries';

			//------- PUBLISH ---------
			if ($status > 0) {
				// simple : on doit mettre le status à positif : +32 ou +1 si l'entree ou la personne
				// est permanente ou non
				
				$db->execute(lq("UPDATE #_TP_$table SET status=abs(status) WHERE id IN ($idlist)")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
	
			//------- UNPUBLISH ---------
			} else { // status < 0
				// plus difficile. On vérifie si les entries ou persons sont attachés à des entités publiées.
				$result =  $db->execute(lq("SELECT id1,id2 FROM #_TP_relations INNER JOIN #_TP_entities ON id1=id WHERE #_TP_entities.status>0 AND id2 IN (". $idlist. ") AND nature='".$nature."' GROUP BY id2")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				while (!$result->EOF) {
					unset($ids[$nature][$result->fields['id2']]); // remove the id from the list to unpublish
					$result->MoveNext();
				}
				if (!empty($ids[$nature])) {
					$idlist = join(',', array_keys($ids[$nature]));
					// dépublie les entrées ou personnes qui n'ont pas été publiés par d'autres entités (SAUF status==21==INDÉPUBLIABLE):
					$db->execute(lq("UPDATE #_TP_$table SET status=-abs(status) WHERE id IN ($idlist) AND status!=21")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				}
			} // status < 0
		} // foreach
	}

	/**
	 * Récupère une entité et tous ses fils
	 *
	 * Récupère une entité et tous ses fils pour une opération donnée et par accès.
	 * On obtiens une liste d'identifiant, d'entité protégés et les classes auxquelles elles
	 * appartiennent.
	 *
	 * @param integer $id Identifiant de l'entité
	 * @param string $access l'accès
	 * @param string $criteria les critères de sélections
	 * @param array &$ids les identifiants des fils et de l'entité, tableau passé par référence
	 * @param array &$classes les classes des differentes entités de $ids, tableau passé par
	 * référence
	 * @param array &$softprotectedids les entités protégés de $ids, tableau passé par référence
	 * @param array &$lockedids les entités verrouillées de $ids, tableau passé par référence
	 */
	protected function _getEntityHierarchy($id, $access, $criteria, &$ids, &$classes, &$softprotectedids, &$lockedids)
	{
		global $db;

		// check the rights to $access the current entity
		$hasrights="(1 ".$this->_getMainTableDAO()->rightsCriteria($access).") as hasrights";

		// get the central object
		if ($criteria) {
			$criteria=" AND ".$criteria;
		}
		$result = $db->execute(lq("SELECT #_TP_entities.id,#_TP_entities.status,$hasrights,class FROM #_entitiestypesjoin_ WHERE #_TP_entities.id ".sql_in_array($id).$criteria));

		// list the entities
		$ids              = array();
		$classes          = array();
		$softprotectedids = array();
		$lockedids        = array();
		while (!$result->EOF) {
			if (!$result->fields['hasrights']) trigger_error("This object is locked. Please report the bug",E_USER_ERROR);
			if ($result->fields['id']>0) $ids[]=$result->fields['id'];
			$classes[$result->fields['class']]=true;
			if ($result->fields['status']>=8) $softprotectedids[]=$result->fields['id'];      
			if ($result->fields['status']>=16) $lockedids[]=$result->fields['id'];      
			$result->MoveNext();
		}
		
		// check the rights to delete the sons and get their ids
		// criteria to determin if one of the sons is locked
		$result = $db->execute(lq("SELECT #_TP_entities.id,#_TP_entities.status,$hasrights,class FROM #_entitiestypesjoin_ INNER JOIN #_TP_relations ON id2=#_TP_entities.id WHERE id1 ". sql_in_array($id). " AND nature='P' ". $criteria)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

		while (!$result->EOF) {
			if (!$result->fields['hasrights']) trigger_error("This object is locked. Please report the bug",E_USER_ERROR);
			if ($result->fields['id']>0) $ids[]=$result->fields['id'];
			$classes[$result->fields['class']]=true;
			if ($result->fields['status']>=8) $softprotectedids[]=$result->fields['id'];
			if ($result->fields['status']>=16) $lockedids[]=$result->fields['id'];
			$result->MoveNext();
		}
	}
	
	protected function define_loop_protectedentities()
	{
		if(function_exists('loop_protectedentities')) return;
		function loop_protectedentities($context,$funcname) {
			global $db;
			$result=$db->execute(lq("SELECT * FROM #_TP_entities WHERE id ".sql_in_array($context['softprotectedentities']))) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			while(!$result->EOF) {
				$localcontext=array_merge($context,$result->fields);
				call_user_func("code_do_$funcname",$localcontext);
				$result->MoveNext();
			}
		} // loop
	}

	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	protected function _publicfields() 
	{
		return array();
	}

	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	// end{uniquefields} automatic generation  //
} // class 
