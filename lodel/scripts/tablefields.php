<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier de gestion des tables et des champs
 */

// build the arrays containing tables and fields
// try first to get the cached array
if (!($tablefields = cache_get('tablefields'))) 
{
	if (!function_exists("maketablefields"))	
	{
		function maketablefields(& $tablefields)
		{
			defined('INC_CONNECT') || include 'connect.php';
			global $db;
			$start = DATABASE != $GLOBALS['currentdb'] ? 0 : 1;
			#      $dbs[$GLOBALS['currentdb']]="";
			#      ) $dbs[DATABASE]=DATABASE.".";
	
			for ($i = $start; $i <= 1; $i ++)	{
				// select the DB
				if ($i == 0)	{ // main database
					usemaindb();
					$prefix = '`'.DATABASE."`.";
				}	else	{ // current database
					usecurrentdb();
					$prefix = "";
				}
				$result = $db->MetaTables();
				foreach ($result as $table)	{
					$fields = $db->MetaColumns($table) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
					$table = $prefix.$table;
	
					$tablefields[$table] = array ();
					foreach ($fields as $field)	{
						$tablefields[$table][] = $field->name;
					}
				}
			}
			$cache = getCacheObject();
			$cache->set(getCacheIdFromId('tablefields'), $tablefields);
		}
	}
	maketablefields($tablefields);
}