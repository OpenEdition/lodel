<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

define("UPLOADDIR",SITEROOT."upload");

/**
 * Classe de logique du navigateur de fichier
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