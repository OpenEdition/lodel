<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier racine de lodel/admin
 */

define('backoffice', true);
define('backoffice-admin', true);
require 'siteconfig.php';

try
{

	include 'auth.php';
	authenticate(LEVEL_VISITOR);
	
	if (isset($_GET['page'])) { // call a special page (and template)
		$page = $_GET['page'];
		if (strlen($page) > 64 || preg_match("/[^a-zA-Z0-9_\/-]/", $page)) {
			trigger_error('invalid page', E_USER_ERROR);
		}
		View::getView()->renderCached($page);
		exit;
	}

    	$authorized_logics = array('entrytypes', 'persontypes',
                    'entries', 'persons',
                    'tablefieldgroups', 'tablefields', 'indextablefields',
                    'translations', 'texts',
                    'usergroups', 'users', 'restricted_users',
                    'types', 'classes',
                    'options', 'optiongroups', 'useroptiongroups', 'otxconf',
                    'internalstyles', 'characterstyles', 'entities_index',
                    'filebrowser', 'xml', 'data', 'internal_messaging', 'plugins');

	$do = C::get('do');
	if(!empty($do) && '_' === $do{0})
		C::set('lo', 'plugins');

    	Controller::getController()->execute($authorized_logics);
}
catch(LodelException $e)
{
	echo $e->getContent();
	exit();
}
?>
