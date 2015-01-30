<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier racine
 */

define('backoffice-lodeladmin', true);
define('backoffice-lodelindex', true);

if (!file_exists("lodelconfig.php")) {
	header('location: lodeladmin/install.php');
	exit;
}


if (file_exists("siteconfig.php")) {
	require 'siteconfig.php';
} else {
	require 'lodelconfig.php';
	ini_set('include_path', $cfg['home']. PATH_SEPARATOR. ini_get('include_path'));
	require 'context.php';
	C::setCfg($cfg);
	require 'class.errors.php';
}

try
{
    include 'auth.php';
    authenticate();
    //on indique qu'on veut pas le desk
    $GLOBALS['nodesk'] = true;
    include 'view.php';
    View::getView()->renderCached('index');
}
catch(Exception $e)
{
	echo $e->getContent();
	exit();
}
?>
