<?php
/**	
 * Logique des types d'entrées
 *
 * PHP version 5
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
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 * @version CVS:$Id$
 */



/**
 * Classe de logique des types d'entrées
 * 
 * @package lodel/logic
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajouté depuis la version 0.8
 * @see logic.php
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
			);
			renderOptions($arr,isset($context['edition']) ? $context['edition'] : '');
			break;
		}
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
		$id = @$context['id'];
		// gather information for the following
		if ($id) {
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
		if (isset($this->oldvo) && $vo->type!=$this->oldvo->type) {
			// name has changed
			$GLOBALS['db']->execute(lq("UPDATE #_TP_tablefields SET name='".$vo->type."' WHERE name='".$this->oldvo->type."' AND type='entries'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
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
		$id = @$context['id'];
		// gather information for the following
		$this->vo=$dao->getById($id);
		if (!$this->vo) trigger_error("ERROR: internal error in EntryTypesLogic::_prepareDelete", E_USER_ERROR);
	}


	protected function _deleteRelatedTables($id)
	{
		DAO::getDAO('tablefields')->delete("type='entries' AND name='".$this->vo->type."'");
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
									'sort' => array('select', '+'));
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
		$etype = @$context['entitytype'];
		loop_typetable ('entitytype', 'entrytype', $context,$funcname,isset($_POST['edit']) ? $etype : -1);
	}
}
?>
