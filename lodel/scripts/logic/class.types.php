<?php
/**	
 * Logique des types
 *
 * PHP versions 5
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
 * Classe de logique des types
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
class TypesLogic extends Logic {

	/** Constructor
	*/
	public function __construct() 
	{
		parent::__construct("types");
	}


	/**
	 * Affichage d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function viewAction(&$context,&$error)
	{
		if ($error) return;
		$context['id'] = @$context['id'];
		if (!$context['id']) {
			// creation
			$context['creationstatus']=-1;
			$context['search']=1;
			$context['tpledition']="edition";
			$context['tplcreation']="entities";
			return "_ok";
		}

		return parent::viewAction($context,$error);
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
		$count=$db->getOne(lq("SELECT count(*) FROM #_TP_entities WHERE idtype='$id' AND status>-64"));
		if ($db->errorno())  trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		if ($count==0) {
			return false;
		} else {
			return sprintf(getlodeltextcontents("cannot_delete_hasentity","admin"),$count);
		}
		//) { $error["error_has_entities"]=$count; return "_back"; }
	}


	/**
	 * Changement du rang d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function changeRankAction(&$context, &$error, $groupfields = "", $status = "status>0")
	{
		return parent::changeRankAction($context, $error, 'class');
	}


	/**
		*
		*/

	public function makeSelect(&$context,$var)
	{
		switch($var) {
		case "import" :
			$arr = array();
			$arr[] = getlodeltextcontents('form','common');
			$arr[] = getlodeltextcontents('import_from_servoo','common');
			renderOptions($arr,isset($context['import']) ? $context['import'] : '');
			break;
		case "display" :
			$arr=array(""=>getlodeltextcontents("folded","admin"),
			"unfolded"=>getlodeltextcontents("unfolded","admin"),
			"advanced"=>getlodeltextcontents("advanced_functions","admin")
				);
			renderOptions($arr,isset($context['import']) ? $context['display'] : '');
			break;
		case "creationstatus" :
			$arr=array("-8"=>getlodeltextcontents("draft","common"),
			"-1"=>getlodeltextcontents("ready_for_publication","common"),
			"1"=>getlodeltextcontents("published","common"),
			"8"=>getlodeltextcontents("protected","common"),
			"17"=>getlodeltextcontents("locked","common"));
			renderOptions($arr,isset($context['creationstatus']) ? $context['creationstatus'] : '');
			break;
		case 'gui_user_complexity' :
			function_exists('makeSelectGuiUserComplexity') || include("commonselect.php");
			makeSelectGuiUserComplexity(isset($context['gui_user_complexity']) ? $context['gui_user_complexity'] : '');
			break;
		}
	}
		

	/*---------------------------------------------------------------*/
	//! Private or protected from this point
	/**
		* @private
		*/

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
		function_exists('typetype_delete') || include("typetypefunc.php");
		$context['id'] = @$context['id'];
		if ($context['id']) {
			typetype_delete("entitytype","identitytype='".$context['id']."'");
		}
		typetype_insert($vo->id,isset($context['entitytype']) ? $context['entitytype'] : null,"entitytype2");
	}



	protected function _deleteRelatedTables($id) 
	{
		function_exists('typetype_delete') || include("typetypefunc.php");
		if(is_array($id)) $id = array_map($id);
		else $id = (int)$id;
		$criteria="(identitytype ".sql_in_array($id)." OR identitytype2 ".sql_in_array($id).")";
		typetype_delete("entitytype",$criteria);
	}



	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	protected function _publicfields() 
	{
		return array('type' => array('type', '+'),
									'title' => array('text', '+'),
									'altertitle' => array('mltext', ''),
									'class' => array('class', '+'),
									'icon' => array('image', ''),
									'gui_user_complexity' => array('select', '+'),
									'tpledition' => array('tplfile', ''),
									'display' => array('select', ''),
									'tplcreation' => array('tplfile', ''),
									'import' => array('select', '+'),
									'creationstatus' => array('select', '+'),
									'search' => array('boolean', '+'),
									'oaireferenced' => array('boolean', '+'),
									'public' => array('boolean', '+'),
									'tpl' => array('tplfile', ''));
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	/**
	 * Retourne la liste des champs uniques
	 * @access private
	 */
	protected function _uniqueFields() 
	{ 
		return array(array('type', 'class'), );
	}
	// end{uniquefields} automatic generation  //


} // class 


/*-----------------------------------*/
/* loops                             */
if(!function_exists('loop_entitytypes'))
{
	function loop_entitytypes($context,$funcname)
	{ 
		function_exists('loop_typetable') || include ("typetypefunc.php"); 
		loop_typetable ("entitytype2","entitytype",$context,$funcname,isset($context['entitytype']) ? $context['entitytype'] : null);
	}
}
?>