<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier site - Gère un site
 */

define('backoffice-lodeladmin', true);
// gere un site. L'acces est reserve au niveau lodeladmin.
require 'lodelconfig.php';

try
{
	include 'auth.php';
	authenticate(LEVEL_ADMINLODEL, NORECORDURL);
	
	include 'class.siteManage.php';
	$website = new siteManage();
	C::set('installoption', C::get('installoption', 'cfg'));
	if(C::get('maintenance') > 0)
	{
		$website->maintenance();
	} 
	elseif (C::get('id') > 0) 
	{ // suppression et restauration
		if(C::get('delete'))
			$website->remove();
		elseif(C::get('restore'))
			$website->restore();
        
        	$result = $db->GetRow("
            SELECT * 
                FROM `$GLOBALS[tp]sites` 
                WHERE ".$website->get('critere')." AND (status>0 || status=-32)") 
                or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
        
        	C::mergeC($result); // preserve possible post datas
        	unset($result);
	}
	
    	// reinstall all the sites
	if (C::get('reinstall') == 'all') {
		$website->reinstall();
	}
	
	$task = C::get('task');

	// ajoute ou edit
	if (C::get('edit') || C::get('maindefault')) {
		if($website->manageSite())
		{
			if (!preg_match($website->get('lodelhomere'),$website->get('versiondir'))) {
				trigger_error("ERROR: versiondir", E_USER_ERROR);
			}
			$task = 'createdb';
		}
	}

	// creation de la DataBase si besoin
	if (defined('DATABASE')) {
		$database = DATABASE;
	}
	$website->set('database', $database);
	
	if(C::get('name') && !C::get('dbname'))
	C::set('dbname', (C::get('singledatabase', 'cfg') == 'on') ? $database : $database. '_'. C::get('name'));

	if ($task === 'createdb') 
	{
		$website->createDB();
		$task = 'createtables';
	}
	
	// creation des tables des sites
	if ($task === 'createtables') 
	{
		$website->createTables();
		$task = 'createdir';
	}
	
	// Creer le repertoire principale du site
	if ($task === 'createdir'){
		$website->createDir();
		$task = 'file';
	}

	// verifie la presence ou copie les fichiers necessaires
	// cherche dans le fichier install-file.dat les fichiers a copier
	if ($task === 'file') {
		$website->manageFiles();
	}
	
	// post-traitement
    	defined('INC_FUNC') || include 'func.php';
	postprocessing(C::getC());
	
	View::getView()->render('site');
}
catch(Exception $e)
{
	echo $e->getContent();
	exit();
}
?>