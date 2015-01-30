<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Logique des groupes d'options utilisateur
 */


function humanfieldtype($text)
{
  return isset($GLOBALS['fieldtypes'][$text]) ? $GLOBALS['fieldtypes'][$text] : '';
}


/**
 * Classe de logique des groupes d'options utilisateurs
 */
class UserOptionGroupsLogic extends Logic {

	/** Constructor
	*/
	public function __construct() 
	{
		parent::__construct("optiongroups"); // UserOptionGroups use the same table as OptionGroups but restrein permitted operations to change the option values.
	}

	/**
	 * Affichage d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function viewAction(&$context,&$error) 
	{
		function loop_useroptions($context,$funcname)
		{
			global $db;
			$result=$db->execute(lq("SELECT * FROM #_TP_options 
						WHERE status > 0 AND idgroup='".$context['id']."' 
						ORDER BY rank,name ")) 
				or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			while (!$result->EOF) {
				$localcontext=array_merge($context,$result->fields);
				$name=$result->fields['name'];
				if (!empty($context[$name])) $localcontext['value']=$context[$name];
				call_user_func("code_do_$funcname",$localcontext);
				$result->MoveNext();
			}
		}
		return parent::viewAction($context,$funcname);
	}


	public function editAction(&$context,&$error, $clean=false)
	{
		// get the dao for working with the object
		$dao=DAO::getDAO("options");
		if(empty($context['id'])) return '_back';
		
		$context['id'] = (int) $context['id'];
		$options=$dao->findMany("idgroup='".$context['id']."'","","id,name,type,defaultvalue,userrights");

		function_exists('validfield') || include 'validfunc.php';

		foreach ($options as $option) {
			if ($option->type=="passwd" && (!isset($context['data'][$option->name]) || !trim($context['data'][$option->name]))) continue; // empty password means we keep the previous one.
			$valid= validfield($context['data'][$option->name],$option->type,"",$option->name, true);
			if ($valid===false) trigger_error("ERROR: \"".$option->type."\" can not be validated in UserOptionGroups::editAction.php", E_USER_ERROR);
			if ( ($option->type=="file" || $option->type=="image") && preg_match("/\/tmpdir-\d+\/[^\/]+$/",$context['data'][$option->name]) ) {
				$file_informations = pathinfo($context['data'][$option->name]);
				
				$new_path = "docannexe/file/option-{$option->id}." . $file_informations['extension'];
				
				rename($context['data'][$option->name], SITEROOT . "/{$new_path}");
				$context['data'][$option->name] = $new_path;
			}
			if (is_string($valid)) $error[$option->name]=$valid;
		}

		if ($error) return "_error";

		foreach ($options as $option) {
			if (C::get('rights', 'lodeluser') < $option->userrights) continue; // the user has not the right to do that.
			if ($option->type=="passwd" && !trim($context['data'][$option->name])) continue; // empty password means we keep the previous one.
			if ($option->type!="boolean" && trim($context['data'][$option->name])==="") $context['data'][$option->name]=$option->defaultvalue; // default value
			$option->value=$context['data'][$option->name];
			if (!$dao->save($option)) trigger_error("You don't have the rights to modify this option", E_USER_ERROR);
		}
		clearcache();
		return "_back";
	}


	/**
		* Change rank action
		*/
	public function copyAction(&$context,&$error)
	{
		trigger_error("ERROR: forbidden", E_USER_ERROR);
	}

	/**
	 * Changement du rang d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function changeRankAction(&$context, &$error, $groupfields = "", $status = "status>0")
	{
		trigger_error('ERROR: forbidden', E_USER_ERROR);
	}

	/**
		* Delete
		*/

	public function deleteAction(&$context,&$error)
	{     
		trigger_error("ERROR: forbidden", E_USER_ERROR);
	}

	/**
	 * Construction des balises select HTML pour cet objet
	 *
	 * @param array &$context le contexte, tableau passé par référence
	 * @param string $var le nom de la variable du select
	 */
	public function makeSelect(&$context, $var)
	{
		function_exists('makeselectlangs') || include("lang.php");
		makeselectlangs(isset($context[$var]) ? $context[$var] : '');
	}

	/*---------------------------------------------------------------*/
	//! Private or protected from this point
	/**
		* @private
		*/

} // class 