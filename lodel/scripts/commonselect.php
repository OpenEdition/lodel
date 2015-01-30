<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier utilitaire de gestion des makeSelect (utile en LODELSCRIPT)
 */

/**
 * Construit le SELECT des différents types de champs
 *
 * Cette fonction récupère la liste des types de champs (dans fieldfunc.php) et appelle
 * renderOptions()
 * @param string $value la valeur courante (pour positionner le selected="selected"
 */
function makeSelectFieldTypes($value)
{
	isset($GLOBALS['lodelfieldtypes']) || include_once 'fieldfunc.php';
	$arr2 = array ();
	foreach ($GLOBALS['lodelfieldtypes'] as $k => $v) {
		if ($v) {
			$arr2[$k] = getlodeltextcontents($k, "common");
		}
	}
	renderOptions($arr2, $value);
}

/**
 * Construit le SELECT des différents types d'utilisateurs
 *
 * Cette fonction récupère la liste des niveaux d'utilisateurs et appelle
 * renderOptions()
 * @param string $value la valeur courante (pour positionner le selected="selected"
 * @param boolean $adminlodel un booleen qui indique si on doit inclure le niveau Admin Lodel
 * @param array $rights on peut forcer un tableau de droits (utilisé pour les plugins)
 */
function makeSelectUserRights($value, $adminlodel, array $rights=array())
{
	if(!empty($rights))
	{
		$lodelrights = array(	LEVEL_VISITOR=>'user_visitor', 
					LEVEL_REDACTOR=>'user_redactor', 
					LEVEL_EDITOR=>'user_editor', 
					LEVEL_ADMIN=>'user_admin', 
					LEVEL_ADMINLODEL=>'user_adminlodel');

		foreach($rights as $right)
		{
			if(isset($lodelrights[$right]))
				$arr[$right] = getlodeltextcontents($lodelrights[$right], "common");
		}
	}
	elseif (C::get('site', 'cfg') || SINGLESITE) 
	{
		$arr = array (	
			LEVEL_VISITOR => getlodeltextcontents("user_visitor", "common"), 
			LEVEL_REDACTOR => getlodeltextcontents("user_redactor", "common"), 
			LEVEL_EDITOR => getlodeltextcontents("user_editor", "common"), 
			LEVEL_ADMIN => getlodeltextcontents("user_admin", "common"));
	}

	if ($adminlodel && !isset($arr[LEVEL_ADMINLODEL])) {
		$arr[LEVEL_ADMINLODEL] = getlodeltextcontents("user_adminlodel", "common");
	}

	// take only the rights below the current user rights
	// in pratice only the ADMIN and ADMINLODEL should be authorized to add/remove users...
	$arr2 = array ();
	$userrights = C::get('rights', 'lodeluser');
	foreach ($arr as $k => $v) {
		if ($userrights >= $k) {
			$arr2[$k] = $v;
		}
	}
	renderOptions($arr2, $value);
}
function makeSelectGuiUserComplexity($value)
{
	$arr = array( 	INTERFACE_SIMPLE => getlodeltextcontents('simple', 'common'),
			INTERFACE_NORMAL => getlodeltextcontents('normal', 'common'),
			INTERFACE_ADVANCED => getlodeltextcontents('advanced', 'common'),
			INTERFACE_DEBUG => getlodeltextcontents('debug', 'common'));

	if(!$value || $value == 0) {
		$value = INTERFACE_ADVANCED;
	}
	renderOptions($arr, $value);
}

/**
 * Construit le SELECT des différents types d'édition d'un champ
 * Appel de renderOptions()
 * Dans $arr, si la clé commence par "OPTGROUP", on génère une balise <optgroup> au lieu de <option>
 * Cf class.tablefields.php (makeselect, case edition)
 *
 * @param string $value la valeur courante (pour positionner le selected="selected")
 *
 */

function makeSelectEdition($value)
{
	$arr = array(
		'OPTGROUP-1'=> getlodeltextcontents("edit_in_the_interface", "admin"),
			"editable" => getlodeltextcontents("edit_in_form_field", "admin"),
			"wysiwyg_ckeditor" => getlodeltextcontents("edit_wysiwyg_fckeditor", "admin"),
		"ENDOPTGROUP-1"=> "",
		"OPTGROUP-2"=> getlodeltextcontents("no_edit_in_interface_but_import", "admin"),
			"importable" => getlodeltextcontents("no_show_in_interface", "admin"),
			"display" => getlodeltextcontents("show_in_interface", "admin"),
		"ENDOPTGROUP-2"=> "",
		"OPTGROUP-3"=> getlodeltextcontents("no_edit_and_no_import", "admin"),
			"none" => getlodeltextcontents("no_show_in_interface", "admin"),
		"ENDOPTGROUP-3"=> "",
		"OPTGROUP-4"=> getlodeltextcontents('for_list_only', 'admin'),
			"select" => getlodeltextcontents("edit_select", "admin"),
			"multipleselect" => getlodeltextcontents("edit_multiple_select", "admin"),
			"radio" => getlodeltextcontents("edit_radio", "admin"),
			"checkbox" => getlodeltextcontents("edit_checkbox", "admin"),
		"ENDOPTGROUP-4"=> "");
	renderOptions($arr, $value);
}