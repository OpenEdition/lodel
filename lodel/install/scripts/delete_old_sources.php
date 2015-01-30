<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

if( php_sapi_name() != "cli") die();

define('backoffice-lodeladmin', true);

require_once 'lodelconfig.php';
require_once 'lodel/scripts/context.php';
C::setCfg($cfg);

require_once 'lodel/scripts/connect.php';
require_once 'lodel/scripts/auth.php';

global $db;

if(isset($argv[1])){
	if(!preg_match("/^[a-z0-9\-]+$/", $argv[1])) die("Site name incorrect");
	$sites = array($argv[1]);
}else{
	$site_array = $db->GetArray(lq('SELECT name FROM sites WHERE status > 0'));
	foreach($site_array as $new_site){
		$sites[] = $new_site['name'];
	}
}

foreach($sites as $site){

	$db->SelectDB(DATABASE . "_{$site}");

	$db_ids = $db->GetArray(lq("SELECT id FROM entities;"));
	
	$ids = array();
	foreach($db_ids as $id){
		$ids[] = $id['id'];
	}
	
	$sources = new DirectoryIterator($site . DIRECTORY_SEPARATOR . "lodel" . DIRECTORY_SEPARATOR . "sources");
	foreach($sources as $source){
		if(preg_match("/^entite(\-\w+)?\-(\d+)\./", $source->getFilename(), $matches)){
			if(!in_array($matches[2], $ids)){
				echo "Deleting {$source->getPathName()}\n";
				unlink($source->getPathName());
			}
			
		}
	}
}