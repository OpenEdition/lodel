<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier de Logout
 */

require 'siteconfig.php';

try
{
	include 'auth.php';
	authenticate();
	$time = time()-1;

	if(isset($_COOKIE[C::get('sessionname', 'cfg')]))
	{
		$name = addslashes($_COOKIE[C::get('sessionname', 'cfg')]);
		
		$db->execute(lq("
			UPDATE #_MTP_session
				SET expire2='$time'
				WHERE name='$name'"))
			or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		
		$db->execute(lq("
			DELETE FROM #_MTP_urlstack
				WHERE idsession='".C::get('idsession', 'lodeluser')."'"))
			or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		
		setcookie(C::get('sessionname', 'cfg'), "", $time,C::get('urlroot', 'cfg'));
	}

	@setcookie('language', '', $time, C::get('urlroot', 'cfg'));
	
	// la norme ne supporte pas les chemins relatifs !! :/
	if(C::get('url_retour'))
		header('Location: '.C::get('url_retour'));
	else
	{
		header('Location: '.('' !== SITEROOT ? SITEROOT : './'));
	}
}
catch(LodelException $e)
{
	echo $e->getContent();
	exit();
}
?>