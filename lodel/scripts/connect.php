<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier pour gérer la connection à la base de donnée - initialise les connexions
 */

defined('INC_LODELCONFIG') || trigger_error("inc lodelconfig please", E_USER_ERROR); // security

// compatibility 0.7
defined("DATABASE") 	|| define("DATABASE", C::get('database', 'cfg'));
defined("DBUSERNAME") 	|| define("DBUSERNAME", C::get('dbusername', 'cfg'));
defined("DBPASSWD")	|| define("DBPASSWD", C::get('dbpasswd', 'cfg'));
defined("DBHOST")	|| define("DBHOST", C::get('dbhost','cfg'));
defined("DBDRIVER") 	|| define("DBDRIVER", C::get('dbDriver', 'cfg'));

$err = error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE); // packages compat
require_once "vendor/autoload.php";
error_reporting($err);

// connect to the database server
$GLOBALS['db'] = ADONewConnection(DBDRIVER);
$GLOBALS['db']->debug = false; // mettre à true pour activer le mode debug
$single = C::get('singledatabase', 'cfg') != "on";
$GLOBALS['currentdb'] = (C::get('site', 'cfg') && $single) ? DATABASE. "_".C::get('site', 'cfg') : DATABASE;

defined("SINGLESITE") || define("SINGLESITE", !$single); // synonyme currently but may change in the future
unset($single);

$cache_config = cache_get_config();
if( $cache_config['driver'] == "memcache") {
	$GLOBALS['db']->memCache = true;

	$servers = array();
	foreach( $cache_config['servers'] as $server) $servers[] = $server['host'];
	$GLOBALS['db']->memCacheHost = $servers;
	$GLOBALS['db']->memCachePort = 11211;
	$GLOBALS['db']->memCacheCompress= false;
} else if( $cache_config['driver'] == "file") {
	// Création d'un dossier cache spécifique pour adodb
	$GLOBALS['ADODB_CACHE_DIR'] = $cache_config['cache_dir']."adodb/";
	@mkdir($GLOBALS['ADODB_CACHE_DIR']);
}

$GLOBALS['db']->connect(DBHOST, DBUSERNAME, DBPASSWD, $GLOBALS['currentdb']) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
$GLOBALS['db']->execute("SET SESSION sql_mode = ''");

$GLOBALS['db_charset'] = mysql_find_db_variable($GLOBALS['currentdb'], 'character_set_database');

if ($GLOBALS['db_charset'] === false) {
	$GLOBALS['db_charset'] = 'utf8';
}

if(!in_array($GLOBALS['db_charset'], array('utf8', 'utf8mb4'))) trigger_error('Please use utf8 for the database to avoid encoding problems', E_USER_ERROR);

$GLOBALS['db']->execute('SET NAMES ' . $GLOBALS['db_charset']);
C::set('db_charset', $GLOBALS['db_charset']);


$GLOBALS['db']->SetFetchMode(ADODB_FETCH_ASSOC);
$GLOBALS['tp'] = $GLOBALS['tableprefix'] = C::get('tableprefix', 'cfg');

C::set('siteinfos', $GLOBALS['db']->GetRow(lq('SELECT * FROM #_MTP_sites WHERE name='.$GLOBALS['db']->quote(C::get('site', 'cfg')))));

/**
 * Déclenche une erreur lors d'une erreur concernant la base de données
 * @deprecated
 */
function dberror()
{
	global $db;
	$ret = trigger_error($db->errormsg(), E_USER_ERROR);
}


/**
 * Positionne la connexion de la base de données sur la table principale (en cas d'installation 
 * multisite.
 */
function usemaindb()
{
	if (DATABASE == $GLOBALS['currentdb'] || $GLOBALS['db']->database == DATABASE) {
		return false; // nothing to do
	}
	$GLOBALS['db']->SelectDB(DATABASE);
	return true;
}

/**
 * Positionne la connexion de la base de données sur la base de données du site (si Lodel est
 * installé en multisite, l'unique base sinon
 */
function usecurrentdb()
{
	if ($GLOBALS['db']->database == $GLOBALS['currentdb']) {
		return false; // nothing to do
	}
    	$GLOBALS['db']->SelectDB($GLOBALS['currentdb']);
    	return true;
}

/**
 * Lodel Query : 
 *
 * Transforme les requêtes en résolvant les jointures et en cherchant les bonnes
 * tables dans les bases de données (suivant notamment le préfix utilisé pour le nommage des
 * tables).
 *
 * @param string $query la requête à traduire
 * @return string la requête traduite
 */
function lq($query)
{
	if (strpos($query, '#_') !== false)	{
		// the easiest, fast replace
		$query = strtr($query, array('#_TP_'=>$GLOBALS['tableprefix'], '#_MTP_' => '`'.DATABASE.'`.'.$GLOBALS['tableprefix']));

		// any other ?
		if (strpos($query, '#_') !== false) {
			$cmd = array (
	'#_entitiestypesjoin_' => "{$GLOBALS['tp']}types INNER JOIN {$GLOBALS['tp']}entities 
                                ON {$GLOBALS['tp']}types.id={$GLOBALS['tp']}entities.idtype",

	'#_tablefieldsandgroupsjoin_' => "{$GLOBALS['tp']}tablefieldgroups INNER JOIN {$GLOBALS['tp']}tablefields 
                                ON {$GLOBALS['tp']}tablefields.idgroup={$GLOBALS['tp']}tablefieldgroups.id",

	'#_tablefieldgroupsandclassesjoin_' => "{$GLOBALS['tp']}tablefieldgroups INNER JOIN {$GLOBALS['tp']}classes 
                                ON {$GLOBALS['tp']}classes.class={$GLOBALS['tp']}tablefieldgroups.class");

			$query = strtr($query, $cmd);
		}
	}
	return $query;
}

/**
 * Fonction nécessaire pour la gestion des id numériques uniques (dans la table object)
 *
 * get a unique id
 * fonction for handling unique id
 *
 * @param string $table le nom de la table dans laquelle on veut insérer un objet
 * @return integer Un entier correspondant à l'id inséré.
 */
function uniqueid($table)
{
	global $db;
	$db->execute("INSERT INTO {$GLOBALS['tp']}objects (class) VALUES ('{$table}')") 
		or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
	return $db->insert_id();
}

/**
 * Suppression d'un identifiant uniques (table objets)
 *
 * erase a unique id.
 * Cette fonction accepte en entrée un id ou un tableau d'id
 *
 * @param integer or array un id ou un tableau d'ids.
 */
function deleteuniqueid($id)
{
	global $db;

	if(!$id) return false;

	if (is_array($id))	{
		$id = array_map('intval', $id);
		$db->execute("DELETE FROM {$GLOBALS['tp']}objects WHERE id IN (". join(",", $id). ")");
	}	else {
		$id = (int)$id;
		$db->execute("DELETE FROM {$GLOBALS['tp']}objects WHERE id='{$id}'");
	}
}

/**
 * Recherche d'une variable MySQL
 *
 * @param string $database_name nom de la base de donnée
 * @param string $var nom de la variable recherchée
 * @return valeur de la variable
 */
function mysql_find_db_variable ($database_name, $var = 'character_set_database') 
{
	global $db;
	if($db->database != $database_name)
    	{
		$dbname = $db->database;
	    	$db->SelectDB($database_name) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
    	}
	$value = $db->GetRow("SHOW VARIABLES LIKE '$var'") or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
	if(isset($dbname)) $db->SelectDB($dbname);

	return ($value ? $value['Value'] : false);
}

define('INC_CONNECT', true);
