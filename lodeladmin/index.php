<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier racine de lodeladmin
 */

if (!file_exists('../lodelconfig.php')) {
	header("location: install.php");
	exit;
}
define('backoffice-lodeladmin', true);
require 'lodelconfig.php';

try
{
	include 'auth.php';
	authenticate(LEVEL_ADMINLODEL);
	
	$accepted_logics = array('texts', 'translations', 'texts', 'users', 'sites', 'data', 'internal_messaging', 'mainplugins');
	
	Controller::getController()->execute($accepted_logics);
}
catch(Exception $e)
{
	echo $e->getContent();
	exit();
}
?>