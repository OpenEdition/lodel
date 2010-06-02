<?php
/**	
 * Logique du navigateur de fichiers
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

define("UPLOADDIR",SITEROOT."upload");

/**
 * Classe de logique du navigateur de fichier
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
class FileBrowserLogic {

	/** Constructor
	*/
	public function __construct() 
	{
		if (!C::get('redactor', 'lodeluser')) trigger_error("ERROR: you don't have the right to access this feature", E_USER_ERROR);
		$GLOBALS['nodesk']=true;
	}

	/**
	 * Affichage d'un objet
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function viewAction(&$context,&$error)
	{
		if (!C::get('redactor', 'lodeluser')) trigger_error("ERROR: you don't have the right to access this feature", E_USER_ERROR);
		return "filebrowser";
	}

	/**
		* dispatcher Action
		*/

	public function submitAction(&$context,&$error)
	{
		if (!C::get('redactor', 'lodeluser')) trigger_error("ERROR: you don't have the right to access this feature", E_USER_ERROR);
		if (!empty($_POST['checkmail'])) return $this->checkMailAction($context,$error);
		if (!empty($_POST['resize']) && isset($_POST['newsize'])) return $this->resizeAction($context,$error);
		if (!empty($_POST['delete'])) return $this->deleteAction($context,$error);
	}

	/**
		* check Mail and store files in attachments
		*/

	public function checkMailAction(&$context,&$error)
	{
		if (!C::get('redactor', 'lodeluser')) trigger_error("ERROR: you don't have the right to access this feature", E_USER_ERROR);
		function_exists('checkmailforattachments') || include "imapfunc.php";
		$context['nbattachments']=checkmailforattachments();
		update();
		return "filebrowser";
	}

	/**
		* delete files
		*/

	public function deleteAction(&$context,&$error)
	{
		if (!C::get('redactor', 'lodeluser')) trigger_error("ERROR: you don't have the right to access this feature", E_USER_ERROR);
		$selectedfiles= (isset($context['file']) && is_array($context['file'])) ? array_keys($context['file']) : false;
		$dh=@opendir(UPLOADDIR);
		if (!$dh) trigger_error("ERROR: can't open upload directory", E_USER_ERROR);

		while( ($file=readdir($dh))!==false ) {
			if ($file[0]!="." || is_file(UPLOADDIR."/".$file)) {
				if (in_array($file,$selectedfiles)) { // quite safe way, not efficient !
					@unlink(UPLOADDIR."/".$file);
				}
			}
		}
		update();
		return "filebrowser";
	}

	/**
		* 
		*/

	public function resizeAction(&$context,&$error)
	{
		if (!C::get('redactor', 'lodeluser')) trigger_error("ERROR: you don't have the right to access this feature", E_USER_ERROR);
		$selectedfiles= (isset($context['file']) && is_array($context['file'])) ? array_keys($context['file']) : false;
		function_exists('resize_image') || include "images.php";
		$dh=@opendir(UPLOADDIR);
		if (!$dh) trigger_error("ERROR: can't open upload directory", E_USER_ERROR);
		while( ($file=readdir($dh))!==false ) {
			if ($file[0]!="." || is_file(UPLOADDIR."/".$file)) {
				if (in_array($file,$selectedfiles)) { // quite safe way, not efficient !
					$file=UPLOADDIR."/".$file;
					resize_image($context['newsize'],$file,$file);
				}
			}
		}
		update();
		return "filebrowser";
	}

} // logic FileBrowserLogic

if(!function_exists('loop_filelist'))
{
	function loop_filelist($context,$funcname)
	{
		$dh=@opendir(UPLOADDIR);
		if (!$dh) { // create the dir if needed
			if (!@mkdir(UPLOADDIR,0777 & octdec(C::get('filemask', 'cfg')))) trigger_error("ERROR: unable to create the directory \"UPLOADDIR\"", E_USER_ERROR);
			@chmod(UPLOADDIR,0777 & octdec(C::get('filemask', 'cfg')));
			$dh=@opendir(UPLOADDIR);
			if (!$dh) trigger_error("ERROR: can't open \"UPLOADDIR\" dir", E_USER_ERROR);
		}
	
		while( ($file=readdir($dh))!==false ) {
			if ($file[0]=="." || !is_file(UPLOADDIR."/".$file)) continue;
			$localcontext=$context;
			// is it an image ?
			list($w,$h)=@getimagesize(UPLOADDIR."/".$file);
			if ($w && $h) {
				$localcontext['imagesize']="$w x $h";
			}
			//
			$localcontext['name']=$file;
			$localcontext['size']=nicefilesize(filesize(UPLOADDIR."/".$file));
			$localcontext['checked']=isset($context['file'][$file]) ? "checked=\"checked\"" : "";
			call_user_func("code_do_$funcname",$localcontext);
		}
		closedir($dh);
	}
}
?>