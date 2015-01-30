<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Logique des types de personnes
 */

/**
 * Classe de logique des types de personnes
 */
class PersonTypesLogic extends Logic
{
	/**
	 * Constructeur
	 */
	public function __construct() 
	{
		parent::__construct("persontypes");
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
		$count=$db->getOne(lq("SELECT count(*) FROM #_TP_persons WHERE idtype='$id' AND status>-64"));
		if ($db->errorno())  trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		if ($count==0) {
			return false;
		} else {
			return sprintf(getlodeltextcontents("cannot_delete_hasperson","admin"),$count);
		}
	}

	/**
	 * Construction des balises select HTML pour cet objet
	 *
	 * @param array &$context le contexte, tableau passé par référence
	 * @param string $var le nom de la variable du select
	 */
	function makeSelect(&$context, $var)
	{
		switch($var) {
		case 'gui_user_complexity' :
			function_exists('makeSelectGuiUserComplexity') || include 'commonselect.php';
			makeSelectGuiUserComplexity(isset($context['gui_user_complexity']) ? $context['gui_user_complexity'] : '');
			break;
		case 'g_type' :
			function_exists('reservedByLodel') || include 'fieldfunc.php';
			$g_typefields = $GLOBALS['g_persontypes_fields'];
			$types = $this->_getMainTableDAO()->findMany('status > 0', '', 'g_type, title');
			$arr = array();
			foreach($types as $type){
				$arr[$type->g_type] = $type->title;
			}

			$arr2 = array('' => '--');
			foreach($g_typefields as $g_type) {
				$lg_type=strtolower($g_type);
				if (isset($arr[$lg_type])) {
					$arr2[$lg_type]=$g_type." &rarr; ".$arr[$lg_type];
				} else {
					$arr2[$lg_type]=$g_type;
				}
			}
			renderOptions($arr2,isset($context['g_type']) ? $context['g_type'] : '');
			break;
		}
	}

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
			if (!$this->oldvo) trigger_error("ERROR: internal error in PersonTypesLogic::_prepareEdit", E_USER_ERROR);
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
		if (isset($this->oldvo) && $vo->type!=$this->oldvo->type) {
			// name has changed
			$GLOBALS['db']->execute(lq("UPDATE #_TP_tablefields SET name='".$vo->type."' WHERE name='".$this->oldvo->type."' AND type='persons'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}
	}
	/**
	* Appelé avant l'action delete
	*
	* Cette méthode est appelée avant l'action delete pour effectuer des vérifications
	* préliminaires à une suppression.
	*
	* @param object $dao la DAO utilisée
	* @param array &$context le contexte passé par référence
	*/
	protected function _prepareDelete($dao,&$context)
	{
		if(empty($context['id']))
			trigger_error("ERROR: missing id in PersonTypesLogic::_prepareDelete", E_USER_ERROR);
		$id = $context['id'];
		// gather information for the following
		$this->vo=$dao->getById($id);
		if (!$this->vo) trigger_error("ERROR: internal error in PersonTypesLogic::_prepareDelete", E_USER_ERROR);
	}

	protected function _deleteRelatedTables($id) 
	{
		DAO::getDAO("tablefields")->delete("type='persons' AND name='".$this->vo->type."'");
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
									'icon' => array('image', ''),
									'gui_user_complexity' => array('select', '+'),
									'g_type' => array('select', ''),
									'style' => array('style', ''),
									'tpl' => array('tplfile', ''),
									'tplindex' => array('tplfile', ''),
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
		return array(array('type'), array('otx', 'class'));
	}
	// end{uniquefields} automatic generation  //
} // class 

/*-----------------------------------*/
/* loops                             */
if(!function_exists('loop_entitytypes'))
{
	function loop_entitytypes($context,$funcname)
	{
		function_exists('loop_typetable') || include "typetypefunc.php";
		$context['entitytype'] = isset($context['entitytype']) ? $context['entitytype'] : null;
		loop_typetable ("entitytype", "persontype", $context, $funcname, !empty($_POST['edit']) ? $context['entitytype'] : -1);
	}
}