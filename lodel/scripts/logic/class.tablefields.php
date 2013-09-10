<?php
/**	
 * Logique des champs
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
 * @package lodel/logic
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 * @version CVS:$Id$
 */


/**
 * Classe de logique des champs
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
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajouté depuis la version 0.8
 * @see logic.php
 */
class TableFieldsLogic extends Logic 
{

	/**
	 * Constructeur
	 */
	function TableFieldsLogic()
	{
		$this->Logic('tablefields');
	}

	/**
	 * Affichage d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	function viewAction(&$context,&$error)

	{
		global $db;

		$ret=Logic::viewAction($context,$error);
		$this->_getClass($context);
		return $ret;
	}

	/**
	 * Ajout d'un nouvel objet ou Edition d'un objet existant
	 *
	 * Ajout d'un nouveau champ
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	function editAction(&$context, &$error)
	{
		$this->_getClass($context);
		// must be done before the validation
		return Logic::editAction($context, $error);
	}


	/**
	 * Changement du rang d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	function changeRankAction(&$context, &$error)
	{
		return Logic::changeRankAction($context, $error, 'idgroup,class');
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
		case 'type':
			require_once 'commonselect.php';
			makeSelectFieldTypes($context['type']);
			break;
		case 'gui_user_complexity' :
			require_once 'commonselect.php';
			makeSelectGuiUserComplexity($context['gui_user_complexity']);
			break;
		case 'cond':
			$arr = array(
			'*' => getlodeltextcontents('nocondition', 'admin'),
			'+' => getlodeltextcontents('fieldrequired', 'admin'),
			'defaultnew' => getlodeltextcontents('use_default_at_creation only', 'admin'),
			'permanent' => getlodeltextcontents('permanent', 'admin'),
			'unique' => getlodeltextcontents('single', 'admin'),
			);
		renderOptions($arr,$context['cond']);
			break;
		case 'edition':
			require_once 'commonselect.php';
			makeSelectEdition($context['edition']);
			break;
		case 'allowedtags':
			require_once 'balises.php';
			$groups = array_keys($GLOBALS['xhtmlgroups']);
			$arr2 = array();
			foreach($groups as $k) {
				if ($k && !is_numeric($k)) $arr2[$k]=$k;
			}
			renderOptions($arr2,$context['allowedtags']);
			break;
		case 'idgroup':
			$arr = array();
			// get the groups having of the same class as idgroup
			$result = $GLOBALS['db']->execute(lq("SELECT #_TP_tablefieldgroups.id,#_TP_tablefieldgroups.title FROM #_tablefieldgroupsandclassesjoin_ INNER JOIN #_TP_tablefieldgroups as tfg2 ON tfg2.class=#_TP_classes.class WHERE tfg2.id='".$context['idgroup']."'")) or die($GLOBALS['db']->errormsg());
			while(!$result->EOF) {
				$arr[$result->fields['id']]=$result->fields['title'];
				$result->MoveNext();
			}
			renderOptions($arr, $context['idgroup']);
			break;
	case 'g_name':
		require_once 'fieldfunc.php';
		if (!$context['classtype']) $this->_getClass($context);
		switch ($context['classtype']) {
		case 'entities':
			$g_namefields = $GLOBALS['g_entities_fields'];/*array('DC.Title', 'DC.Description',
					'DC.Publisher', 'DC.Date',
					'DC.Format', 'DC.Identifier',
					'DC.Source', 'DC.Language',
					'DC.Relation', 'DC.Coverage',
					'DC.Rights','generic_icon');*/
			break;
		case 'persons':
			$g_namefields = $GLOBALS['g_persons_fields'];#array('Firstname', 'Familyname', 'Title');
			break;
		case 'entities_persons':
			$g_namefields = $GLOBALS['g_entities_persons_fields'];# array('Title');
			break;
		case 'entries':
			$g_namefields = array('Index key');
			break;
		default:
			trigger_error("class type ?",E_USER_ERROR);
		}

		$dao = $this->_getMainTableDAO();
		$tablefields = $dao->findMany("class='".$context['class']."'","","g_name,title");     
		foreach($tablefields as $tablefield) { $arr[$tablefield->g_name]=$tablefield->title; }

		$arr2 = array(''=>'--');
		foreach($g_namefields as $g_name) {
			$lg_name=strtolower($g_name);
			if ($arr[$lg_name]) {
				$arr2[$lg_name]=$g_name." &rarr; ".$arr[$lg_name];
			} else {
				$arr2[$lg_name]=$g_name;
			}
		}
		renderOptions($arr2, $context['g_name']);
		break;
		case 'weight':
			$arr = array('0' => getlodeltextcontents('not_indexed', 'admin'),
			'1' => '1', '2' => '2', '4' => '4', '8' => '8');
			renderOptions($arr, $context['weight']);
			break;
		}
	}

	/*** In 0.7 we check the field is not moved in another class... it's not 100% required in fact
		function validateFields(&$context,&$error) {
		if (!Logic::validateFields($context,$error)) return false;
		// check the group does not change 
		if ($oldidgroup!=$idgroup) {
	$set['rank']=get_rank_max("fields","idgroup='$idgroup'");      
	// check the new group has the same class (extra security)
	$result=mysql_query("SELECT 1 FROM $GLOBALS[tp]tablefieldgroups WHERE id='$idgroup' AND class='".$context['class']."'") or dberror();
	if (mysql_num_rows($result)!=1) die("ERROR: the new and the old group of the field are not in the same class");
			}**/

	/**
	* Préparation de l'action Edit
	*
	* @access private
	* @param object $dao la DAO utilisée
	* @param array &$context le context passé par référence
	*/
	function _prepareEdit($dao,&$context)

	{
		// gather information for the following
		if ($context['id']) {
			$this->oldvo=$dao->getById($context['id']);
			if (!$this->oldvo) die("ERROR: internal error in TableFields::_prepareEdit");
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
	function _saveRelatedTables($vo,$context) 

	{
		global $home,$lodelfieldtypes,$db;
		require_once("fieldfunc.php");

		// remove the dc for all the other fields
		if ($vo->g_name) {
			$db->execute(lq("UPDATE #_TP_tablefields SET g_name='' WHERE g_name='".$vo->g_name."' AND id!='".$vo->id."' AND class='".$vo->class."'")) or dberror();
		}

		// manage the physical field 
		if ($vo->class && $this->oldvo->class && 
	$this->oldvo->class!=$vo->class) die("ERROR: field change of class is not implemented yet");

		if ($vo->type!="entities") {
			if (!$this->oldvo) {
	$alter="ADD";
			} elseif ($this->oldvo->name!=$vo->name) {
	$alter="CHANGE ".$this->oldvo->name;
			} elseif ($lodelfieldtypes[$this->oldvo->type]['sql']=$lodelfieldtypes[$vo->type]['sql']) {
	$alter="MODIFY";
			}

			if ($alter) { // modify or add or rename the field
	if (!$lodelfieldtypes[$vo->type]['sql']) die("ERROR: internal error in TableFields:: _saveRelatedTables ".$vo->type);
	$db->execute(lq("ALTER TABLE #_TP_".$context['class']." $alter ".$vo->name." ".$lodelfieldtypes[$vo->type]['sql'])) or dberror();
			}
			if ($alter || $vo->filtering!=$this->oldvo->filtering) {
	// should be in view ??
	require_once("cachefunc.php");
	clearcache();
			}
		}
		unset($this->oldvo);
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
	function _prepareDelete($dao,&$context)

	{     
		// gather information for the following
		$this->vo=$dao->getById($context['id']);
		if (!$this->vo) die("ERROR: internal error in TableFields::deleteAction");
	}
	
	/**
	 * Suppression dans les tables liées
	 *
	 * @param integer $id identifiant numérique de l'objet supprimé
	 */
	function _deleteRelatedTables($id)
	{
		global $db, $home;

		if (!$this->vo) {
			die("ERROR: internal error in TableFields::deleteAction");
		}
		if ($this->vo->type!="entities") {
			$db->execute(lq("ALTER TABLE #_TP_".$this->vo->class." DROP ".$this->vo->name)) or dberror();
		}
		unset($this->vo);

		// should be in the view....
		require_once 'cachefunc.php';
		clearcache();
		//

		return '_back';
	}

	/**
	 * Récupère la classe d'un groupe de champ
	 *
	 * @param array &$context le contexte, tableau passé par référence
	 */
	function _getClass(&$context)
	{
		global $db;
		if ($context['idgroup']) {
			$row = $db->getRow(lq("SELECT #_TP_classes.class,classtype FROM #_tablefieldgroupsandclassesjoin_ WHERE #_TP_tablefieldgroups.id='". $context['idgroup']. "'")) or dberror();
			if ($context['class'] && $context['class'] != $row['class']) {
				die("ERROR: idgroup and class are incompatible in TableFieldsLogic::editAction");
			}
			$context['class']     = $row['class'];
			$context['classtype'] = $row['classtype'];
		} else {
			if (substr($context['class'],0,9) == 'entities_') {
				$class = substr($context['class'],9);
				$classtype = 'entities_';
			} else {
				$class = $context['class'];
			}
			$classtype.= $db->getOne(lq("SELECT classtype FROM #_TP_classes WHERE class='". $class. "'"));
			$context['classtype'] = $classtype;
		}
	}

	/**
		*
		* Special treatment for allowedtags, from/to the context
		*/
	function _populateContext(&$vo,&$context)
	{
		Logic::_populateContext($vo, $context);
		$context['allowedtags'] = explode(';', $vo->allowedtags);
	}

	function _populateObject(&$vo, &$context)
	{
		Logic::_populateObject($vo,$context);
		$vo->class = $context['class']; // it is safe, we now that !
		$vo->allowedtags = is_array($context['allowedtags']) ? join(';', $context['allowedtags']) : '';
	}

	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	function _publicfields() 
	{
		return array('name' => array('tablefield', '+'),
									'class' => array('class', '+'),
									'title' => array('text', '+'),
									'altertitle' => array('mltext', ''),
									'gui_user_complexity' => array('select', '+'),
									'idgroup' => array('select', '+'),
									'type' => array('select', '+'),
									'g_name' => array('select', ''),
									'style' => array('mlstyle', ''),
									'cond' => array('select', '+'),
									'defaultvalue' => array('text', ''),
									'processing' => array('text', ''),
									'allowedtags' => array('multipleselect', ''),
									'edition' => array('select', ''),
									'editionparams' => array('text', ''),
									'weight' => array('select', ''),
									'filtering' => array('text', ''),
									'comment' => array('longtext', ''));
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	/**
	 * Retourne la liste des champs uniques
	 * @access private
	 */
	function _uniqueFields() 
	{ 
		return array(array('name', 'class'), );
	}
	// end{uniquefields} automatic generation  //


} // class 

/*-----------------------------------*/
/* loops                             */
function loop_allowedtags_documentation(&$context,$funcname)

{
	##$groups=array_merge(array_keys($GLOBALS['xhtmlgroups']),array_keys($GLOBALS['multiplelevel']));
	require_once("balises.php");
	foreach($GLOBALS['xhtmlgroups'] as $groupname => $tags) {
		$localcontext=$context;
		$localcontext['count']=$count;
		$count++;
		$localcontext['groupname']=$groupname;
		$localcontext['allowedtags']="";
		foreach ($tags as $k=>$v) { if (!is_numeric($k)) unset($tags[$k]); }
		if (!$tags) continue;
		$localcontext['allowedtags']=join(", ",$tags);
		call_user_func("code_do_$funcname",$localcontext);
	}
}

?>
