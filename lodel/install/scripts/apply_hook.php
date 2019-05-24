<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

if( php_sapi_name() != "cli") die();

if(!( isset($argv[1]) || isset($argv[2]) || isset($argv[3]) ) ) die("Usage php apply_hook.php [class] [field] [hook]");

list($command, $class, $field, $hook) = $argv;

define('backoffice-lodeladmin', true);

require_once 'lodelconfig.php';
require_once 'lodel/scripts/context.php';
C::setCfg($cfg);

require_once 'lodel/scripts/connect.php';
require_once 'lodel/scripts/auth.php';

global $db;

$site_array = $db->GetArray(lq('SELECT name FROM sites WHERE status > 0'));
foreach($site_array as $site){

	$db->SelectDB(DATABASE . "_{$site['name']}");

	if($tablefields = $db->GetArray(lq("SELECT id, name, class, editionhooks FROM tablefields WHERE class = " . $db->Quote($class) . " AND name = " . $db->Quote($field))))
	{
	
		foreach($tablefields as $tablefield){
			$hooks = array_filter(explode(',', $tablefield['editionhooks']));
			$hooks[] = $hook;
			
			$db->Execute(lq("UPDATE tablefields SET editionhooks = " . $db->Quote(implode(",",$hooks)) . " WHERE id = " . $db->Quote($tablefield['id'])));
			
			//implode($hooks);
		}
	}
}

