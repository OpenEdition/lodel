<?php
/**	
 * Logique des groupes d'options utilisateur
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
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 * @version CVS:$Id$
 */



function humanfieldtype($text)

{
  return $GLOBALS['fieldtypes'][$text];
}

/***/



/**
 * Classe de logique des groupes d'options utilisateurs
 * 
 * @package lodel/logic
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajouté depuis la version 0.8
 * @see logic.php
 */
class UserOptionGroupsLogic extends Logic {

	/** Constructor
	*/
	function UserOptionGroupsLogic() {
		$this->Logic("optiongroups"); // UserOptionGroups use the same table as OptionGroups but restrein permitted operations to change the option values.
	}

	/**
	 * Affichage d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	function viewAction(&$context,$error) 

	{
		function loop_useroptions($context,$funcname)
		{
			global $db;
			$result=$db->execute(lq("SELECT * FROM #_TP_options WHERE status > 0 AND idgroup='".$context['id']."' ORDER BY rank,name ")) or dberror();
			while (!$result->EOF) {
				$localcontext=array_merge($context,$result->fields);
				$name=$result->fields['name'];
				if ($context[$name]) $localcontext['value']=$context[$name];
					call_user_func("code_do_$funcname",$localcontext);
				$result->MoveNext();
			}
		}
		return Logic::viewAction($context,$funcname);
	}


	function editAction(&$context,&$error)

	{
		global $lodeluser,$home;
		// get the dao for working with the object
		
		$dao=&getDAO("options");
		$options=$dao->findMany("idgroup='".$context['id']."'","","id,name,type,defaultvalue,userrights");     
		require_once("validfunc.php");
		foreach ($options as $option) {
			if ($option->type=="passwd" && !trim($context['data'][$option->name])) continue; // empty password means we keep the previous one.
			$valid=validfield($context['data'][$option->name],$option->type,"",$option->name);
			if ($valid===false) die("ERROR: \"".$option->type."\" can not be validated in UserOptionGroups::editAction.php");
			if ( ($option->type=="file" || $option->type=="image") && preg_match("/\/tmpdir-\d+\/[^\/]+$/",$context['data'][$option->name]) ) {
				$dir=dirname($context['data'][$option->name]);
				rename(SITEROOT.$dir,SITEROOT.preg_replace("/\/tmpdir-\d+$/","/option-".$option->id,$dir));
			}
			if (is_string($valid)) $error[$option->name]=$valid;
		}

		if ($error) return "_error";

		foreach ($options as $option) {
			if ($lodeluser['rights'] < $option->userrights) continue; // the user has not the right to do that.
			if ($option->type=="passwd" && !trim($context['data'][$option->name])) continue; // empty password means we keep the previous one.
			if ($option->type!="boolean" && trim($context['data'][$option->name])==="") $context['data'][$option->name]=$option->defaultvalue; // default value
			$option->value=$context['data'][$option->name];
			if (!$dao->save($option)) die("You don't have the rights to modify this option");
		}
		touch(SITEROOT."CACHE/maj");
		@unlink(SITEROOT."CACHE/options_cache.php");
		return "_back";
	}


	/**
		* Change rank action
		*/
	function copyAction(&$context,&$error)
	{
		die("ERROR: forbidden");
	}

	/**
	 * Changement du rang d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	function changeRankAction(&$context, &$error)
	{
		die('ERROR: forbidden');
	}

	/**
		* Delete
		*/

	function deleteAction(&$context,&$error)
	{     
		die("ERROR: forbidden");
	}

	/**
	 * Construction des balises select HTML pour cet objet
	 *
	 * @param array &$context le contexte, tableau passé par référence
	 * @param string $var le nom de la variable du select
	 */
	function makeSelect(&$context, $var)
	{
			require_once 'lang.php';
			makeselectlangs($context[$var]);
	}

	/*---------------------------------------------------------------*/
	//! Private or protected from this point
	/**
		* @private
		*/

} // class 


/*-----------------------------------*/
/* loops                             */





?>
