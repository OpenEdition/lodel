<?php
/**
 * Fichier pour gérer la connection à la base de donnée - initialise les connexions
 *
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 *
 * Home page: http://www.lodel.org
 *
 * E-Mail: lodel@lodel.org
 *
 * All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */
if (!(INC_LODELCONFIG)) {
	trigger_error("inc lodelconfig please", E_USER_ERROR); // security
}
// compatibility 0.7
if (!defined("DATABASE")) {
	define("DATABASE", $GLOBALS['database']);
	define("DBUSERNAME", $GLOBALS['dbusername']);
	define("DBPASSWD", $GLOBALS['dbpasswd']);
	define("DBHOST", $GLOBALS['dbhost']);
	define("DBDRIVER", $GLOBALS['dbDriver']);
}

// connect to the database server
require "adodb/adodb.inc.php";
require 'adodb_hack.php';
$GLOBALS['db'] = ADONewConnection(DBDRIVER);
$GLOBALS['db']->debug = false; // mettre à true pour activer le mode debug

if ($GLOBALS['site'] && $GLOBALS['singledatabase'] != "on") {
	$GLOBALS['currentdb'] = DATABASE. "_".$GLOBALS['site'];
} else {
	$GLOBALS['currentdb'] = DATABASE;
}

if (!defined("SINGLESITE")) {
	define("SINGLESITE", $GLOBALS['singledatabase'] == "on"); // synonyme currently but may change in the future
}
$GLOBALS['ADODB_CACHE_DIR'] = './CACHE/adodb/';
if(!file_exists($GLOBALS['ADODB_CACHE_DIR']))
{
	if(!@mkdir($GLOBALS['ADODB_CACHE_DIR'], 0777 & octdec($GLOBALS['filemask'])))
		trigger_error('No way to write into cache directory', E_USER_ERROR);
	@chmod($GLOBALS['ADODB_CACHE_DIR'], 0777 & octdec($GLOBALS['filemask']));
}
$GLOBALS['db']->connect(DBHOST, DBUSERNAME, DBPASSWD, $GLOBALS['currentdb']) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

$info_mysql = $GLOBALS['db']->ServerInfo();
$vs_mysql = explode(".", substr($info_mysql['version'], 0, 3));
$GLOBALS['version_mysql'] = $vs_mysql[0] . $vs_mysql[1];
unset($info_mysql, $vs_mysql);
if ($GLOBALS['version_mysql'] > 40) {
	$GLOBALS['db_charset'] = mysql_find_db_variable($GLOBALS['currentdb'], 'character_set_database');
	if ($GLOBALS['db_charset'] === false) {
		$GLOBALS['db_charset'] = 'utf8';
	}
	$GLOBALS['db']->execute('SET NAMES ' . $GLOBALS['db_charset']);
}


$GLOBALS['db']->SetFetchMode(ADODB_FETCH_ASSOC);
$GLOBALS['tp'] = $GLOBALS['tableprefix'];

/**
 * Déclenche une erreur lors d'une erreur concernant la base de données
 */
function dberror()
{
	global $db;
	$ret = trigger_error($db->errormsg(), E_USER_ERROR);
}

$GLOBALS['maindb'] = '';
$GLOBALS['savedb'] = '';

/**
 * Positionne la connexion de la base de données sur la table principale (en cas d'installation 
 * multisite.
 */
function usemaindb()
{
	global $db, $maindb, $savedb;
	if (DATABASE == $GLOBALS['currentdb']) {
		return false; // nothing to do
	}
	if ($db->selectDB(DATABASE)) {
		return true; // try to selectdb
	}

	if (!$maindb)	{ // not connected
		$maindb = ADONewConnection(DBDRIVER);
		if (!$maindb->nconnect(DBHOST, DBUSERNAME, DBPASSWD, DATABASE)) {
			trigger_error("ERROR: reconnection is not allowed with the driver: ".DBDRIVER, E_USER_ERROR);
		}
	}

	// set $db as $maindb
	$savedb = &$db;
	$db = &$maindb;
	return true;
}

/**
 * Positionne la connexion de la base de données sur la base de données du site (si Lodel est
 * installé en multisite, l'unique base sinon
 */
function usecurrentdb()
{
	if (DATABASE == $GLOBALS['currentdb']) {
		return; // nothing to do
	}
	global $db, $savedb;
	if ($db->selectDB($GLOBALS['currentdb'])) {
		return; // try to selectdb
	}
	$db = &$savedb;
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
	static $cmd;
	// the easiest, fats replace
	$query = str_replace('#_TP_', $GLOBALS['tableprefix'], $query);
	// any other ?
	if (strpos($query, '#_') !== false)	{
		if (!$cmd)
			$cmd = array ('#_MTP_' => '`'.DATABASE.'`.'.$GLOBALS['tableprefix'],
	'#_entitiestypesjoin_' => "$GLOBALS[tableprefix]types INNER JOIN $GLOBALS[tableprefix]entities ON $GLOBALS[tableprefix]types.id=$GLOBALS[tableprefix]entities.idtype",

	'#_tablefieldsandgroupsjoin_' => "$GLOBALS[tableprefix]tablefieldgroups INNER JOIN $GLOBALS[tableprefix]tablefields ON $GLOBALS[tableprefix]tablefields.idgroup=$GLOBALS[tableprefix]tablefieldgroups.id",

	'#_tablefieldgroupsandclassesjoin_' => "$GLOBALS[tableprefix]tablefieldgroups INNER JOIN $GLOBALS[tableprefix]classes ON $GLOBALS[tableprefix]classes.class=$GLOBALS[tableprefix]tablefieldgroups.class");

		$query = strtr($query, $cmd);
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
	$db->execute(lq("INSERT INTO #_TP_objects (class) VALUES ('$table')")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
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

	if(empty($id)) return false;

	if (is_array($id))	{
		$db->execute(lq("DELETE FROM $GLOBALS[tableprefix]objects WHERE id IN (". join(",", $id). ")"));
	}	else {
		$db->execute(lq("DELETE FROM $GLOBALS[tableprefix]objects WHERE id='$id'"));
	}
}

/**
 * Recherche d'une variable MySQL
 *
 * @param string $database_name nom de la base de donnée
 * @param string $var nom de la variable recherchée
 * @return valeur de la variable
 */
function mysql_find_db_variable ($database_name, $var = 'character_set_database') {
	global $db;
	if($db->database != $database_name)
		$dbname = $db->database;
	$db->SelectDB($database_name) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
	$dbCharset = $db->GetRow("SHOW VARIABLES LIKE '$var'") or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
	if(isset($dbname)) $db->SelectDB($dbname);

	return ($dbCharset ? $dbCharset['Value'] : false);
}

?>