<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Ajout des tables pour les attributs d'entrées d'index
 */

define('backoffice-lodeladmin', true);

require_once 'lodelconfig.php';
require_once 'lodel/scripts/context.php';
C::setCfg($cfg);

require_once 'lodel/scripts/connect.php';
require_once 'lodel/scripts/auth.php';

/* Vérification que nous sommes en php-cli, sinon nous vérifions les droits*/
if (!array_key_exists('SHELL', $_ENV))
authenticate(LEVEL_ADMINLODEL);

global $db;
$sites = $db->Execute(lq("
            SELECT name, status 
                FROM #_MTP_sites 
                WHERE status > 0")) 
or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);


foreach($sites as $site){
	$db->SelectDB(DATABASE . "_{$site['name']}");
	$classes = $db->Execute(lq('SELECT * FROM #_TP_classes WHERE classtype = "entries";'));
	foreach($classes as $class){
		$db->execute(lq ("CREATE TABLE IF NOT EXISTS #_TP_entities_". $class['class'] ." ( idrelation INTEGER UNSIGNED UNIQUE, KEY index_idrelation (idrelation) )"));
	}
}
