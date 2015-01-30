<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Classe de logique des types d'entrées
 */
class EntryTypesLogic extends Logic
{

	/** Constructor
	*/
	public function __construct() {
		parent::__construct("entrytypes");
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
	public function isdeletelocked($id,$status=0) 
	{
		global $db;
		$id = (int)$id;
		$count=$db->getOne(lq("SELECT count(*) FROM #_TP_entries WHERE idtype='$id' AND status>-64"));
		if ($db->errorno())  trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		if ($count==0) {
			return false;
		} else {
			return sprintf(getlodeltextcontents("cannot_delete_hasentry","admin"),$count);
		}
		//) { $error["error_has_entities"]=$count; return "_back"; }
	}


	/**
		* makeSelect
		*/

	public function makeSelect(&$context,$var)

	{
		switch($var) {
		case 'sort' :
			renderOptions(array('sortkey' => getlodeltextcontents('alphabetical_order', 'admin'),
				'rank' => getlodeltextcontents('order_defined_in_interface', 'admin'),
				'id'   => getlodeltextcontents('order_of_creation', 'admin'),
				),isset($context['sort']) ? $context['sort'] : '');
			break;
		case 'g_type' :
			#$g_typefields=array("DC.Subject");
			function_exists('reservedByLodel') || include 'fieldfunc.php';
			$g_typefields = $GLOBALS['g_entrytypes_fields'];#array('DC.Subject', 'DC.Coverage', 'DC.Rights', 'oai.set');
			$types=$this->_getMainTableDAO()->findMany('status > 0', '','g_type,title');
			$arr = array();
			foreach($types as $type) {
				$arr[$type->g_type]=$type->title;
			}

			$arr2 = array('' => '--');
			foreach($g_typefields as $g_type) {
				$lg_type = strtolower($g_type);
				if (isset($arr[$lg_type])) {
					$arr2[$lg_type] = $g_type." &rarr; ".$arr[$lg_type];
				} else {
					$arr2[$lg_type] = $g_type;
				}
			}
			renderOptions($arr2,isset($context['g_type']) ? $context['g_type'] : '');
			break;
		case 'gui_user_complexity' :
			function_exists('makeSelectGuiUserComplexity') || include 'commonselect.php';
			makeSelectGuiUserComplexity(isset($context['gui_user_complexity']) ? $context['gui_user_complexity'] : '');
			break;
		case 'edition' :
			$arr = array(
			'pool' => getlodeltextcontents('edit_pool', 'admin'),
			'multipleselect' => getlodeltextcontents('edit_multipleselect', 'admin'),
			'select' => getlodeltextcontents('edit_select', 'admin'),
			'inline' => getlodeltextcontents('edit_inline', 'admin'),
			);
			renderOptions($arr,isset($context['edition']) ? $context['edition'] : '');
			break;
		}
	}

	/**
	*  Récupère les types externes
	*
	* @param array &$error le tableau contenant les éventuelles erreurs, par référence
	* @param array &$context le context passé par référence
	*/
	public function getExternalAction(&$context, &$error)
	{
		global $db;

		$context['externalentrytypes'] = array();
		$GLOBALS['nodesk'] = true;

		usemaindb();
		$sites = DAO::getDAO('sites')->findMany('status > 0', 'name', 'name, title');
		usecurrentdb();
		if(!$sites) return 'entrytypesbrowser';
		
		$dao = DAO::getDAO('entrytypes');

		foreach($sites as $site)
		{
			if(!$db->SelectDB(DATABASE.'_'.$site->name)) continue;
			$entrytypes = $dao->findMany('externalallowed = 1', 'type', 'title, id');
			if(!$entrytypes) continue;

			$types = array();

			foreach($entrytypes as $entrytype)
			{
				$types[$site->name.'.'.$entrytype->id] = $entrytype->title;
			}

			$context['externalentrytypes'][$site->title] = $types;
		}

		usecurrentdb();

		$context['currentexternalentrytypes'] = array();
		$currents = DAO::getDAO('relations_ext')->findMany('nature="ET"', 'site', 'id2,site');
		foreach($currents as $current)
		{
			$context['currentexternalentrytypes'][] = $current->site.'.'.$current->id2;
		}

		return 'entrytypesbrowser';
	}

	/*---------------------------------------------------------------*/
	//! Private or protected from this point
	/**
		* @private
		*/

	/**
	* Préparation de l'action Edit
	*
	* @access private
	* @param object $dao la DAO utilisée
	* @param array &$context le context passé par référence
	*/
	protected function _prepareEdit($dao,&$context)
	{
		// gather information for the following
		if (!empty($context['id'])) {
			$id = $context['id'];
			$this->oldvo=$dao->getById($id);
			if (!$this->oldvo) trigger_error("ERROR: internal error in EntryTypesLogic::_prepareEdit", E_USER_ERROR);
		}
	}


	/**
	* Sauve des données dans des tables liées éventuellement
	*
	* Appelé par editAction pour effectuer des opérations supplémentaires de sauvegarde.
	*
	* @param object $vo l'objet qui a été créé
	* @param array $context le contexte
	*/
	protected function _saveRelatedTables($vo,&$context) 
	{
		if (isset($this->oldvo)) {
			if($vo->type!=$this->oldvo->type)
			{
				// name has changed
				$GLOBALS['db']->execute(lq("UPDATE #_TP_tablefields SET name='".$vo->type."' WHERE name='".$this->oldvo->type."' AND type='entries'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
			
			if($vo->sort == 'rank' && $vo->sort != $this->oldvo->sort)
			{
				$vos = DAO::getDAO('entries')->findMany('idtype='.$vo->id, 'id,idparent,idtype');
				if($vos)
				{
					$localcontext = $context;
					$lo = Logic::getLogic('entries');
					foreach($vos as $v)
					{
						$localcontext['id'] = $v->id;
						$localcontext['degree'] = 0;
						$lo->saveRelations($v, $localcontext);
					}
				}
			}
			elseif($this->oldvo->sort == 'rank')
			{
				$vos = DAO::getDAO('entries')->findMany('idtype='.$vo->id, 'id,idparent,idtype');
				if($vos)
				{
					foreach($vos as $v)
					{
						$GLOBALS['db']->execute(lq('DELETE FROM #_TP_relations WHERE id2='.$v->id.' AND nature="P"')) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
					}
				}
			}
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
	protected function _prepareDelete($dao,&$context)
	{
		if(empty($context['id']))
			trigger_error("ERROR: missing id in EntryTypesLogic::_prepareDelete", E_USER_ERROR);
		$id = $context['id'];
		// gather information for the following
		$this->vo=$dao->getById($id);
		if (!$this->vo) trigger_error("ERROR: internal error in EntryTypesLogic::_prepareDelete", E_USER_ERROR);
	}


	protected function _deleteRelatedTables($id)
	{
		global $db;

		DAO::getDAO('tablefields')->delete("type='entries' AND name='".$this->vo->type."'");

		$shared = DAO::getDAO('relations_ext')->findMany('id1='.(int)$id);
		if(!$shared) return;

		foreach($shared as $vo)
		{
			if('ET' == $vo->nature)
			{
				$db->SelectDB(DATABASE.'_'.$vo->site) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				$vos = DAO::getDAO('entries')->findMany('idtype='.$vo->id2, 'id');
				if($vos)
				{
					foreach($vos as $v)
					{
						$db->execute(lq('DELETE FROM #_TP_relations_ext WHERE nature="EE" AND id2='.$v->id.' AND site='.$db->quote(C::get('site', 'cfg'))))
							or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
						$db->execute(lq('DELETE FROM `'.DATABASE.'_'.C::get('site', 'cfg').'`.#_TP_relations_ext
									WHERE nature="E" AND id2='.$v->id.' AND site='.$db->quote($v->site)))
							or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
					}
				}
				DAO::getDAO('tablefields')->delete("type='entries' AND name='".C::get('site', 'cfg').'.'.$id."'");
			}
			elseif('EET' == $vo->nature)
			{
				$vos = DAO::getDAO('entries')->findMany('idtype='.$vo->id2, 'id');
				if($vos)
				{
					foreach($vos as $v)
					{
						$db->execute(lq('DELETE FROM #_TP_relations_ext WHERE nature="EE" AND id2='.$v->id.' AND site='.$db->quote($v->site)))
							or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
						$db->execute(lq('DELETE FROM `'.DATABASE.'_'.$v->site.'`.#_TP_relations_ext
									WHERE nature="E" AND id2='.$v->id.' AND site='.$db->quote(C::get('site', 'cfg'))))
							or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
					}
				}
			}
		}
		usecurrentdb();
	}


	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	protected function _publicfields() 
	{
		return array('type' => array('type', '+'),
									'class' => array('class', '+'),
									'title' => array('text', '+'),
									'altertitle' => array('mltext', ''),
									'lang' => array('select_lang', '+'),
									'icon' => array('image', ''),
									'gui_user_complexity' => array('select', '+'),
									'edition' => array('select', ''),
									'flat' => array('boolean', '+'),
									'g_type' => array('select', ''),
									'newbyimportallowed' => array('boolean', ''),
									'style' => array('style', ''),
									'tpl' => array('tplfile', ''),
									'tplindex' => array('tplfile', ''),
									'sort' => array('select', '+'),
									'externalallowed' => array('boolean','+'),
									'otx' => array('xpath', ''));
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	/**
	 * Retourne la liste des champs uniques
	 * @access private
	 */
	protected function _uniqueFields() 
	{ 
		return array(array('type'), );
	}
	// end{uniquefields} automatic generation  //


} // class 


/*-----------------------------------*/
/* loops                             */
if(!function_exists('loop_entitytypes')) 
{
	function loop_entitytypes($context,$funcname)
	{
		function_exists('loop_typetable') || include 'typetypefunc.php';
		$etype = isset($context['entitytype']) ? $context['entitytype'] : null;
		loop_typetable ('entitytype', 'entrytype', $context,$funcname, isset($_POST['edit']) ? $etype : -1);
	}
}