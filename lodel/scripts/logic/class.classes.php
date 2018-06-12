<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */


/**
 * Classe de logique des classes du système - Fille de la classe Logic
 *
 */
class ClassesLogic extends Logic
{

	/**
	 * Constructeur
	 */
	public function __construct()
	{
		parent::__construct('classes');
	}

	/**
	 *  Indique si un objet est protégé en suppression
	 *
	 * Cette méthode indique si un objet, identifié par son identifiant numérique et
	 * éventuellement son status, ne peut pas être supprimé. Dans le cas où un objet ne serait
	 * pas supprimable un message est retourné indiquant la cause. Sinon la méthode renvoit le
	 * booleen false.
	 *
	 * @param integer $id identifiant de l'objet
	 * @param integer $status status de l'objet
	 * @return false si l'objet n'est pas protégé en suppression, un message sinon
	 */
	public function isdeletelocked($id, $status = 0)
	{
		global $db;
		$dao = $this->_getMainTableDAO ();
		$vo  = $dao->getById ($id, 'classtype');
		if(!$vo) return false;
		$types = $this->typestable($vo->classtype);
		switch ($vo->classtype) {
			case 'entities':
				$msg = 'cannot_delete_hasentities';
				break;
			case 'entries':
				$msg = 'cannot_delete_hasentries';
				break;
			case 'persons':
				$msg = 'cannot_delete_haspersons';
				break;
		}
		$count = $db->getOne (lq ("SELECT count(*) FROM #_TP_". $vo->classtype. " INNER JOIN #_TP_". $types. " ON idtype=#_TP_". $types. ".id INNER JOIN #_TP_classes ON #_TP_".$types. ".class=#_TP_classes.class WHERE #_TP_classes.id='$id' AND #_TP_". $vo->classtype. ".status>-64 AND #_TP_". $types. ".status>-64  AND #_TP_classes.status>-64"));
		if ($db->errorno ()){
			trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}
		if ($count == 0) {
			return false;
		} else {
			return sprintf (getlodeltextcontents ($msg, 'admin'), $count);
		}
	}

	/**
	 * Indique le nom de la table type associée avec le type de classe
	 *
	 * Return the type table associated with the classtype
	 * @param string $classtype le type de la classe
	 * @return une valeur parmis : type, entrytypes et persontypes
	*/
	public function typestable ($classtype)
	{
		switch ($classtype) {
		case 'entities':
			return 'types';
		case 'entries':
			return 'entrytypes';
		case 'persons' :
			return 'persontypes';
		}
	}

	/**
	 * Préparation de l'action Edit
	 *
	 * @access private
	 * @param object $dao la DAO utilisée
	 * @param array &$context le context passé par référence
	 */
	protected function _prepareEdit ($dao, &$context)
	{
		// gather information for the following
		if (!empty($context['id'])) {
			$this->oldvo = $dao->getById ($context['id']);
			if (!$this->oldvo) {
				trigger_error("ERROR: internal error in Classes::deleteAction", E_USER_ERROR);
			}
		}
	}

	/**
	 * Implémenation de l'action d'ajout ou d'édition d'un objet.
	 *
	 * <p>Cette fonction crée un nouvel objet ou édite un objet existant. Dans un premier temps les
	 * données sont validées (suivant leur type) puis elles sont rentrées dans la base de données <em>via</em> la DAO associée à l'objet.
	 * Utilise _prepareEdit() pour effectuer des opérations de préparation avant l'édition de l'objet puis _populateContext() pour ajouter des informations supplémentaires au context. Et enfin _saveRelatedTables() pour sauver d'éventuelles informations dans des tables liées.
	 * </p>

	 * add/edit Action
	 * @param array $context le tableau des données passé par référence.
	 * @param array $error le tableau des erreurs rencontrées passé par référence.
	 * @param boolean $clean false si on ne doit pas nettoyer les données (par défaut à false).
	 * @return string les différentes valeurs possibles de retour d'une action (_ok, _back, _error ou xxxx).
	 */
	public function editAction(&$context, &$error, $clean = false)
	{
		if ($clean != 'CLEAN') {      // validate the forms data
			if (!$this->validateFields($context, $error)) {
				return '_error';
			}
		}
		function_exists('reservedByLodel') || include 'fieldfunc.php';
		if(reservedByLodel($context['class'])) {
			$error['class'] = 'reservedsql';
			return '_error';
		}
		// get the dao for working with the object
		$dao = $this->_getMainTableDAO();
		$id = isset($context['id']) ? $context['id'] : null;
		$this->_prepareEdit($dao, $context);
		// create or edit
		if ($id) {
			$dao->instantiateObject($vo);
			$vo->id = $id;
		} else {
			$vo = $dao->createObject();
		}
		if (!empty($dao->rights['protect'])) {
			$vo->protect = !empty($context['protected']) ? 1 : 0;
		}
		// put the context into
		$this->_populateObject($vo, $context);
		if (!$dao->save($vo)) trigger_error("You don't have the rights to modify or create this object", E_USER_ERROR);
		$ret = $this->_saveRelatedTables($vo, $context);

		update();
		return $ret ? $ret : "_back";
	}
	/**
	 * Sauve des données dans des tables liées éventuellement
	 *
	 * Appelé par editAction pour effectuer des opérations supplémentaires de sauvegarde.
	 *
	 * @param object $vo l'objet qui a été créé
	 * @param array $context le contexte
	 */
	protected function _saveRelatedTables ($vo, &$context)
	{
		global $db;

		$alter = false;
		//----------------new, create the table
		if (!isset($this->oldvo->class)) {
			switch($vo->classtype) {
			case 'entities' :
				$create = "identity INTEGER UNSIGNED PRIMARY KEY";
				break;
			case 'entries' :
				$create = "identry INTEGER UNSIGNED PRIMARY KEY";
				$db->execute (lq ("CREATE TABLE IF NOT EXISTS #_TP_entities_". $vo->class." ( idrelation INTEGER UNSIGNED PRIMARY KEY )")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				break;
			case 'persons' :
				$create = "idperson INTEGER UNSIGNED PRIMARY KEY";
				$db->execute (lq ("CREATE TABLE IF NOT EXISTS #_TP_entities_". $vo->class." ( idrelation INTEGER UNSIGNED PRIMARY KEY )")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				break;
			}
			$db->execute(lq("CREATE TABLE IF NOT EXISTS #_TP_". $vo->class." ( ". $create." )")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			$alter=true;
			//---------------- change class name ?
		} elseif ($this->oldvo->class!=$vo->class) {
			// change table name
			$db->execute (lq ("RENAME TABLE #_TP_". $this->oldvo->class. " TO #_TP_". $vo->class)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			if ($vo->classtype=="persons" || $vo->classtype=="entries") {
				$db->execute (lq ("RENAME TABLE #_TP_entities_". $this->oldvo->class. " TO #_TP_entities_". $vo->class)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				$db->execute (lq ("UPDATE #_TP_tablefields SET class='entities_". $vo->class. "' WHERE class='entities_". $this->oldvo->class."'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
			// update tablefields, objects and types
			foreach (array ('objects',
					$this->typestable ($vo->classtype),
					'tablefields',
					'tablefieldgroups')
				as $table) {
				$db->execute (lq ("UPDATE #_TP_". $table. " SET class='". $vo->class. "' WHERE class='". $this->oldvo->class."'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
			$alter = true;
		}

		if($vo->classtype == 'entries' && !empty($context['externalentrytypes'])) {
			$entrytypes = array_filter(array_map('trim', explode(',', $context['externalentrytypes'])));
			if(!empty($entrytypes)) {
				$cursite = $db->quote(C::get('site', 'cfg'));
				$sql = lq('REPLACE INTO #_TP_relations_ext(id1,id2,nature,degree,site) VALUES ');
				foreach($entrytypes as $entrytype) {
					preg_match('/^([\w\-_]+)\.(\d+)$/', $entrytype, $result);
					$sql .= "({$vo->id}, {$result[2]}, 'ET', 0, '{$result[1]}'),";
					if(!C::get('db_no_intrusion.'.$result[1], 'cfg')) {
						$db->SelectDB(DATABASE.'_'.$result[1]) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
						$db->execute(lq("REPLACE INTO #_TP_relations_ext SET id1={$result[2]}, id2={$vo->id}, nature='EET', degree=0, site=".$cursite)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
					}
				}
				usecurrentdb();
				$db->Execute(substr($sql, 0, -1)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
		}

		if ($alter) {        // update the cache ?
			clearcache();
		}
	}

	/**
	 * Appelé avant l'action delete
	 *
	 * Cette méthode est appelée avant l'action delete pour effectuer des vérifications
	 * préliminaires à une suppression.
	 *
	 * @param object $dao la DAO utilisée
	 * @param array &$context le contexte passé par référénce
	 */
	protected function _prepareDelete ($dao, &$context)
	{
		if(empty($context['id']))
			trigger_error("ERROR: internal error in Classes::deleteAction", E_USER_ERROR);
		// gather information for the following
		$this->vo = $dao->getById ($context['id']);
		if (!$this->vo) {
			trigger_error("ERROR: internal error in Classes::deleteAction", E_USER_ERROR);
		}
	}
	/**
	 * Suppression éventuelle dans des tables liées
	 *
	 * @param integer $id identifiant numérique de l'objet supprimé
	 */
	protected function _deleteRelatedTables ($id) {
		global $db;
		if (!$this->vo) trigger_error("ERROR: internal error in Classes::deleteAction", E_USER_ERROR);
		$db->execute (lq ("DROP TABLE #_TP_".$this->vo->class)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		if ( in_array($this->vo->classtype, array("persons", "entries")) ) {
			$db->execute(lq("DROP TABLE #_TP_entities_".$this->vo->class)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}
		// delete externalentrytypes
		if ($this->vo->classtype == "entries") {
			$db->Execute(lq("DELETE FROM #_TP_relations_ext WHERE id1=$id"));
		}
		// delete associated types
		// collect the type to delete
		$types=DAO::getDAO ($this->typestable ($this->vo->classtype))->findMany ("class='". $this->vo->class. "'", "id");
		$logic=Logic::getLogic ($this->typestable ($this->vo->classtype));
		foreach ($types as $type) {
			$localcontext['id']=$type->id;
			$logic->deleteAction ($localcontext, $err);
		}
		// delete tablefields and tablefieldgroups
		$criteria="class='".$this->vo->class."'";
		if ($this->vo->classtype=="persons") {
			$criteria.=" OR class='entities_".$this->vo->class."'";
		}
		DAO::getDAO ("tablefields")->deleteObjects ($criteria);

		// delete tablefields
		DAO::getDAO ("tablefieldgroups")->deleteObjects ($criteria);
		unset ($this->vo);

		clearcache();
		return "_back";
	}


	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	protected function _publicfields()
	{
		return array('class' => array('class', '+'),
									'classtype' => array('text', '+'),
									'title' => array('text', '+'),
									'altertitle' => array('mltext', ''),
									'icon' => array('image', ''),
									'comment' => array('longtext', ''));
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	/**
	 * Retourne la liste des champs uniques
	 * @access private
	 */
	protected function _uniqueFields()
	{
		return array(array('class'), );
	}
	// end{uniquefields} automatic generation  //

} // class
