<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Classe de logique des entités (gestion avancée)
 *
 */
class Entities_AdvancedLogic extends Logic
{
	/**
	 * Tableau des équivalents génériques
	 *
	 * @var array
	 */
	public $g_name;

	/**
	 * Constructeur
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
		if ($error) {
			return;
		}
		recordurl();

		if (empty($context['id'])) {
			trigger_error("ERROR: invalid id in Entities_AdvancedLogic::viewAction", E_USER_ERROR);
		}

		$id = $context['id'];

		if (!rightonentity('advanced', $context)) {
			trigger_error("ERROR: you don't have the right to perform this operation", E_USER_ERROR);
		}

		$vo  = $this->_getMainTableDAO()->getById($id);
		if (!$vo) {
			trigger_error("ERROR: can't find object $id in the table ". $this->maintable, E_USER_ERROR);
		}
		$this->_populateContext($vo, $context);

		$votype  = DAO::getDAO('types')->getById($vo->idtype);

		if(empty($context['type']) || !is_array($context['type']))
			$context['type'] = array();

		$this->_populateContext($votype, $context['type']);

		// look for the source
		$context['sourcefile'] = file_exists(SITEROOT."lodel/sources/entite-".$id.".source");

		return '_ok';
	}

	/**
	 * Changer le status d'une entité
	 *
	 * Modifie le status d'une entité en utilisant la valeur passée dans le context :
	 * $context['status'].
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function changeStatusAction(&$context, &$error)
	{
		global $db;
		if (!isset($context['status'])) {
			trigger_error("ERROR: invalid status in EntitiesLogic::publishAction", E_USER_ERROR);
		}

		$status = $context['status'];
		$this->_isAuthorizedStatus($status);
		$dao = $this->_getMainTableDAO();
		if(empty($context['id']))
			trigger_error("ERROR: missing id", E_USER_ERROR);

		$id = $context['id'];
		$vo  = $dao->find("id='".$id. "' AND status*$status>0 AND status<16", 'status,id');
		if (!$vo) {
			trigger_error("ERROR: interface error in Entities_AdvancedLogic::changeStatusAction ", E_USER_ERROR);
		}
		$vo->status = $status;
		$dao->save($vo);
	
		// check if the entities have an history field defined
		$this->_processSpecialFields('history', $context, $status);
	
		update();
		return '_back';
	}

	/**
	 * Préparation du déplacement  d'une entité.
	 *
	 * Cette méthode est appellée avant l'action move. Elle prépare le déplacement en vérifiant
	 * certaines conditions à celui-ci.
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function prepareMoveAction(&$context, &$error)
	{
		if (!rightonentity('move', $context)) {
			trigger_error("ERROR: you don't have the right to perform this operation", E_USER_ERROR);
		}
		if(function_exists('loop_move_right')) return 'move';
		/**
		 * Boucle permettant de savoir si on a le droit de déplacer l'entité IDDOCUMENT (identifiée
		 * par son type IDTYPE) dans l'entité courante.
		 * On teste si le type de l'entité courante peut contenir le type de l'entité ID.
		 * On doit aussi tester si l'entité courante n'est pas un descendant de IDDOCUMENT
		 *
		 * @param array $context le context passé par référence
		 * @param string $funcname le nom de la fonction
		 */
		function loop_move_right(&$context,$funcname)
		{
			static $cache,$idtypes;
			global $db;
			$context['iddocument'] = isset($context['iddocument']) ? $context['iddocument'] : null;
			$context['idtype'] = isset($context['idtype']) ? $context['idtype'] : null;
			$context['id'] = isset($context['id']) ? $context['id'] : null;
			//test1 : si le type de l'entité courante peut contenir ce type d'entité
			if (!isset($cache[$context['idtype']])) {
				//mise en cache du type du document
				$idtype = $idtypes[$context['iddocument']];
				if (!$idtype) { // get the type, we don't have it!
					$dao = DAO::getDAO("entities");
					$vo = $dao->getById($context['iddocument'],"idtype");
					$idtype = $idtypes[$context['iddocument']]=$vo->idtype;
				}
				// récupère la condition sur les deux types testé.
				$condition = $db->getOne(lq("SELECT cond FROM #_TP_entitytypes_entitytypes WHERE identitytype='". $idtype. "' AND identitytype2='". $context['idtype']. "'"));
				$cache[$context['idtype']] = (bool)$condition;
				if ($db->errorno()) {
					trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				}
			}
			//test2 : si l'entité courante est une descendante de l'entité IDDOCUMENT
			function_exists('isChild') || include 'entitiesfunc.php';
			$boolchild = isChild($context['iddocument'], $context['id']);
			if (!empty($cache[$context['idtype']]) && $boolchild) { //si c'est ok
				if (function_exists("code_do_$funcname")) {
					call_user_func("code_do_$funcname", $context);
				}
			} else {
				if (function_exists("code_alter_$funcname")) {
					call_user_func("code_alter_$funcname", $context);
				}
			}
		}
		return 'move';
	}

	/**
	 * Déplacer une entité
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function moveAction(&$context, &$error)
	{
		global $db;
		if (!rightonentity('move', $context)) {
			trigger_error("ERROR: you don't have the right to perform this operation", E_USER_ERROR);
		}

		if(!isset($context['id']) || !isset($context['idparent']))
			trigger_error('ERROR: missing id or idparent', E_USER_ERROR);

		$id = $context['id']; // which entities
		$idparent = $context['idparent']; // where to move it

		function_exists('checkTypesCompatibility') || include 'entitiesfunc.php';
		if (!checkTypesCompatibility($id, $idparent)) {
			trigger_error("ERROR: Can move the entities $id into $idparent. Check the editorial model.", E_USER_ERROR);
		}

		// yes we have the right, move the entities
		$dao = $this->_getMainTableDAO();
		$dao->instantiateObject($vo);
		$vo->id       = $id;
		$vo->rank     = 0; // recalculate
		$vo->idparent = $idparent;
		$dao->save($vo);

		if ($db->affected_Rows()>0) { // effective change
			// get the new parent hierarchy
			$result=$db->execute(lq("SELECT id1,degree FROM #_TP_relations WHERE id2='$idparent' AND nature='P'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			$parents = array();
			$values = '';
			$dmax   = 0;
			while (!$result->EOF) {
				$id1 = $result->fields['id1'];
				$degree = $result->fields['degree'];
				$parents[$degree] = $id1;
				if ($degree>$dmax) {
					$dmax = $degree;
				}
				$values.= "('". $id1. "','". $id. "','P','". ($degree+1). "'),";
				$result->MoveNext();
			}
			$parents[0] = $idparent;
			// search for the children
			$result=$db->execute(lq("SELECT id2,degree FROM #_TP_relations WHERE id1='$id' AND nature='P'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

			$delete = '';
			while (!$result->EOF) {
				$id2 = $result->fields['id2'];
				$degree = $result->fields['degree'];
		
				$delete.= " (id2='".$id2."' AND degree>".$degree.") OR "; // remove all the parent above $id.
				for ($d=0; $d<=$dmax; $d++) { // for each degree
					$values.= "('".$parents[$d]."','".$id2."','P','".($degree+$d+1)."'),"; // add all the parent
				}
				$result->MoveNext();
			}
	
			$delete.= " id2='".$id."' ";
			$values.= "('".$idparent."','".$id."','P',1)";
	
			// delete the relation to the parent 
			if ($delete) {
				$db->execute(lq("DELETE FROM #_TP_relations WHERE (".$delete.") AND nature='P'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
			if ($values) {
				$db->execute(lq("REPLACE INTO #_TP_relations (id1,id2,nature,degree) VALUES ".$values)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
		}

		update();
		return '_back';
	}

	/**
	 * Récuperer le fichier source correspondant à une entité
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function downloadAction(&$context, &$error)
	{
		if(!isset($context['id']) || !isset($context['type']))
			trigger_error('ERROR: missing id or type', E_USER_ERROR);

		$id = $context['id'];
		$context['type'] = $context['type'];
		$multidoc = false;
		switch($context['type']) {
		case 'tei':
			$filename = "entite-tei-$id.xml";
			$dir = "../sources";
			// get the official name 
			$vo  = $this->_getMainTableDAO()->getById($id,"creationmethod,creationinfo");
			if ($vo->creationmethod != "servoo" && $vo->creationmethod != "otx") {
				trigger_error("ERROR: error creationmethod is not compatible with download", E_USER_ERROR);
			}
			$originalname = pathinfo($vo->creationinfo ? basename($vo->creationinfo) : basename($filename), PATHINFO_FILENAME).'.xml';
			break;
		case 'odt':
			$filename = "entite-odt-$id.source";
			$dir = "../sources";
			// get the official name 
			$vo  = $this->_getMainTableDAO()->getById($id,"creationmethod,creationinfo");
			if ($vo->creationmethod != "servoo" && $vo->creationmethod != "otx") {
				trigger_error("ERROR: error creationmethod is not compatible with download", E_USER_ERROR);
			}
			$originalname = pathinfo($vo->creationinfo ? basename($vo->creationinfo) : basename($filename), PATHINFO_FILENAME).'.odt';
			break;
		case 'source' :
			$filename = "entite-$id.source";
			$dir = "../sources";
			// get the official name 
			$vo  = $this->_getMainTableDAO()->getById($id,"creationmethod,creationinfo");
			if ($vo->creationmethod != "servoo" && $vo->creationmethod != "otx") {
				trigger_error("ERROR: error creationmethod is not compatible with download", E_USER_ERROR);
			}
			$originalname = $vo->creationinfo ? basename($vo->creationinfo) : basename($filename);
			break;
		default:
			trigger_error("ERROR: unknow type of download in downloadAction", E_USER_ERROR);
		}
		
		if (!file_exists($dir."/".$filename)) {
			trigger_error("ERROR: the filename $filename does not exists", E_USER_ERROR);
		}
		download ($dir. '/'. $filename, $originalname);
		exit;
	}

	// begin{publicfields} automatic generation  //
	/**
	 * @access private
	 */
	protected function _publicfields() {
		return array();
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	// end{uniquefields} automatic generation  //
} // class 