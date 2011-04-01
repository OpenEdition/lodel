<?php

/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 *  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
 *  Copyright (c) 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 *  Copyright (c) 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 *
 *  Home page: http://www.lodel.org
 *
 *  E-Mail: lodel@lodel.org
 *
 *                            All Rights Reserved
 *
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.*/

//
// functions or pieces taken from phpMyAdmin version 2.5.4 release under the GPL license.
// Thanks to the authors !
//

###define("OLDLODELPREFIX","__LODELTP__");

// liste des tables a sauvegarder lors d'un backup de site (dump structure + donnees)
$GLOBALS['lodelsitetables'] = array ("#_TP_objects", "#_TP_classes", "#_TP_entities", "#_TP_relations", "#_TP_tablefields", "#_TP_tablefieldgroups", "#_TP_persons", "#_TP_users", "#_TP_usergroups", "#_TP_users_usergroups", "#_TP_types", "#_TP_persontypes", "#_TP_entrytypes", "#_TP_entries", "#_TP_tasks", "#_TP_texts", "#_TP_entitytypes_entitytypes", "#_TP_options", "#_TP_optiongroups", "#_TP_translations", "#_TP_internalstyles", "#_TP_characterstyles", "#_TP_oailogs", "#_TP_oaitokens", "#_TP_restricted_users", "#_TP_plugins", "#_TP_history", '#_TP_relations_ext');

// liste des tables a sauvegarder lors d'un backup de site (dump de la structure seulement, pas des donnees)
$GLOBALS['lodelsitetables_nodatadump'] = array ("#_TP_search_engine");

$GLOBALS['lodelbasetables'] = array ("#_MTP_sites", "#_MTP_users", "#_MTP_urlstack", "#_MTP_session", "#_MTP_internal_messaging", "#_MTP_mainplugins");
if(C::get('singledatabase', 'cfg') != 'on') {
	array_push($GLOBALS['lodelbasetables'], "#_MTP_translations", "#_MTP_texts");
}


$userlink = TRUE;
$server = TRUE;
$GLOBALS['strDatabase'] = "Database";
$GLOBALS['strTableStructure'] = "Table structure for table";
$GLOBALS['strDumpingData'] = "Dumping data for table";

require "pma/mysql_wrappers.lib.php";
require "pma/defines.lib.php";
require "pma/defines_php.lib.php";
require "pma/common.lib.php";
require "pma/sql-modified.php";

// parser SQL
require "pma/string.lib.php";
require "pma/sqlparser.data.php";
require "pma/sqlparser.lib.php";

/**
 * Operation. Propose various way to retrieve/store a file
 *
 */
function operation($operation, $archivetmp, $archivefilename, &$context)
{
	if ($operation == 'download')	{
		download($archivetmp, $archivefilename);
		@ unlink($archivetmp);
		return TRUE;
	}	elseif ($operation == 'cache' || $operation == 'importdir')	{
		$context['outfilename'] = $operation == 'cache' ? getCachePath($archivefilename) : C::get('importdir', 'cfg')."/$archivefilename";
		if (!(@ rename($archivetmp, $context['outfilename']))) {
			$context['error'] = 1;
			return FALSE;
		}	else	{
			// ok, continue
			return FALSE;
		}
	}	else	{
		trigger_error("ERROR: unknonw operation", E_USER_ERROR);
	}
}

/**
 * Dump the database using phpMyAdmin functions
 * (should be encaplused into an Object, this have far too much arguments!
 *  or use an assoc for the options!!)
 *
 * @param   string   database
 * @param   string   output filename
 * @param   string   drop the tables
 * @param   string   the file handle for the outputfile. If not set, a file is open and close.
 *
 * @return  string   dump
 *
 * @access  public
 *
 */
function mysql_dump($db, $tables, $output, $fh = 0, $create = true, $drop = true, $contents = true, $select = '*', $where = '')
{
	if ($fh) {
		$GLOBALS['mysql_dump_file_handle'] = $fh;
	}	else {
		$GLOBALS['mysql_dump_file_handle'] = @fopen($output, "w");
		if (!$GLOBALS['mysql_dump_file_handle'])
			trigger_error("ERROR: unable to write file \"$output\"", E_USER_ERROR);
	}

	$GLOBALS['drop'] = $drop;
	$err_url = $_SERVER['PHP_SELF']."?error=1";
	$crlf = PMA_whichCrlf();

	if (!$tables) {
		trigger_error("ERROR: tables is not defined in mysql_dump", E_USER_ERROR);
	}
	$num_tables = count($tables);

	if ($where) {
		$where = ' WHERE '. $where;
	}
		PMA_exportDBHeader($db);
	$i = 0;
	while ($i < $num_tables) {
		$table = lq($tables[$i]);
		$table = preg_replace("/.*\./", "", $table); // remove the reference to the database. PMA do that itself.
		$local_query = 'SELECT '. $select. ' FROM '.PMA_backquote($db). '.'.PMA_backquote($table). $where;
		if ($create) {
			PMA_exportStructure($db, $table, $crlf, $err_url);
		}
		if ($contents) {
			PMA_exportData($db, $table, $crlf, $err_url, $local_query);
		}
		$i ++;
	}
	PMA_exportDBFooter($db);
	if (!$fh) {
		@fclose($GLOBALS['mysql_dump_file_handle']);
	}
}

/**
 * Parse un dump MySQL et execute les requêtes.
 *
 * @param string $url le fichier SQL
 * @param boolean $ignoreerrors. false par défaut
 */
function parse_mysql_dump($url, $ignoreerrors = false) 
{
	global $db;
	$file_content = file($url);
	$query = '';
	foreach($file_content as $sql_line) {
		$tsl = trim($sql_line);
		if (($sql_line != "") && (substr($tsl, 0, 2) != "--") && (substr($tsl, 0, 1) != "#")) {
			$query .= $sql_line;
			if(preg_match("/;\s*$/", $sql_line)) {
				#echo "query:".lq($query)."<br />";
				$result = $db->Execute(lq($query));
				if (!$result && !$ignoreerrors) trigger_error($db->ErrorMsg(), E_USER_ERROR);
				$query = '';
			}
		}
	}
}



/**
 * Enlève le préfixe actuel et ajoute le préfixe par défaut dans une table MySQL
 *
 * Cette fonction est utilisée dans les fonctions PMA
 *
 * @param string $table le nom de la table
 */
function lodelprefix($table)
{
	// remove up to the dot
	$table = preg_replace("/.*\./", "", $table);
	if (C::get('tableprefix', 'cfg') && strpos($table, C::get('tableprefix', 'cfg')) !== 0)
		trigger_error("ERROR: table $table should be prefixed", E_USER_ERROR);

	$table = substr($table, strlen(C::get('tableprefix', 'cfg')));

	if (@$GLOBALS['currentprefix']) {
		return $GLOBALS['currentprefix'].$table;
	}	else {
		trigger_error("ERROR: currentprefix is not defined", E_USER_ERROR);
	}
}

/**
 * Output handler.
 *
 * @param   string  the insert statement
 *
 * @return  bool    Whether output suceeded
 */
function PMA_exportOutputHandler($line)
{
	static $time_start;
	$write_result = @ fwrite($GLOBALS['mysql_dump_file_handle'], $line);
	if (!$write_result || ($write_result != strlen($line)))	{
		return FALSE;
	}
	$time_now = time(); // keep the browser alive !
	if (!$time_start)	{
		$time_start = $time_now;
	}	elseif ($time_now >= $time_start +30)	{
		$time_start = $time_now;
		header('X-pmaPing: Pong');
	} // end if 

	return TRUE;
}

/**
 * A do nothing function Used by phpMyAdmin functions.
 * Conversion is not needed here.
 *
 * @param   string   text
 *
 * @return  string   unmodified text
 *
 * @access  public
 *
 */
function PMA_convert_charset($what)
{
	return $what;
}

/**
 * A do nothing function Used by phpMyAdmin functions.
 * Conversion is not needed here.
 *
 * @param   string   text
 *
 * @return  string   unmodified text
 *
 * @access  public
 *
 */
function PMA_convert_display_charset($text)
{
	return $text;
}

/**
 * Importation d'un ME ou de données depuis son archive ZIP
 *
 * @param string $archive le chemin vers le fichier ZIP
 * @param array $accepteddirs la liste des répertoires acceptés dans l'archive ZIP
 * @param array $acceptedexts la liste des types de fichiers acceptés
 * @param string $sqlfile le nom du fichier SQL à traiter. Vide par défaut
 * @param bool $xml si on utilise l'import XML
 */
function importFromZip($archive, $accepteddirs, $acceptedexts = array (), $sqlfile = '', $xml=false)
{
	$unzipcmd = C::get('unzipcmd', 'cfg');
	$tmpdir = tmpdir();

	// use UNZIP command
	if ($unzipcmd && $unzipcmd != 'pclzip')	{
		// find files to unzip
		$listfiles = `$unzipcmd -Z -1 $archive`;
		$listfilesarray = preg_split("/\n/", `$unzipcmd -Z -1 $archive`);
		if(!$acceptedexts) {
			$acceptedexts = array('*');
		}
		if (!$listfiles)
			return false;
		$dirs = '';
		foreach ($accepteddirs as $dir) {
			if (preg_match("/^(\.\/)?".str_replace("/", '\/', $dir)."\//m", $listfiles)) {
				if(!file_exists(SITEROOT.$dir)) {
					if(!@mkdir(SITEROOT.$dir) || !@chmod(SITEROOT.$dir, 0770 & octdec(C::get('filemask', 'cfg')))) continue;
				} elseif(!is_dir(SITEROOT.$dir)) continue;
				if ($acceptedexts) {
					foreach ($acceptedexts as $ext)	{
						$dirs .= "\\".$dir."\/\*.$ext ".$dir."\/\*\/\*.$ext ";
					}
				}	else {
					$dirs .= "\\".$dir."\/\* ".$dir."/\*/\* ";
				}
			}
		}
		system($unzipcmd." -oq $archive  $dirs -d ../..");
		#if (!chdir("lodel/admin"))
		#  trigger_error("ERROR: chdir 2 fails", E_USER_ERROR);
		if ($sqlfile)	{
			$ext = $xml ? 'xml' : 'sql';
			system($unzipcmd." -qp $archive  \*.$ext > $sqlfile");
			if (filesize($sqlfile) <= 0)
				return false;
		}
	}	else {
		$err = error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE); // packages compat
		// use PCLZIP library
		class_exists('PclZip', false) || include 'pclzip/pclzip.lib.php';
		$archivename = $archive;
		//require_once "pclzip.lib.php";
		$archive = new PclZip($archive);

		// functions callback
		function preextract($p_event, &$p_header)
		{ // choose the files to extract
			//echo $p_header['filename'],"<br>";
			global $user_vars;

			if (preg_match("/^(\.\/)*.*\.(sql|xml)$/", $p_header['filename']))	{ // extract the sql file
				unlink($user_vars['sqlfile']); // remove the tmpfile if not it is not overwriten... 
				//                   may cause problem if the file is recreated but it's so uncertain !
				$p_header['filename'] = $user_vars['sqlfile'];
				return 1;
			}
			$exts = $user_vars['acceptedexts'] ? ".*\.(".join("|", $user_vars['acceptedexts']).")$" : "";
			if (preg_match("/^(\.\/)*(".str_replace("/", "\/", join("|", $user_vars['accepteddirs'])).")\/$exts/", str_replace('//', '/', $p_header['filename']))) {
				$p_header['filename'] = SITEROOT.$p_header['filename'];
				if (file_exists($p_header['filename']) && is_file($p_header['filename']))
					unlink($p_header['filename']);
				return 1;
			}
			return 0; // don't extract
		}

		function postextract($p_event, &$p_header)
		{ // chmod
			#if ($p_header['filename']!=$user_vars{'sqlfile'} && 
			#    file_exists($p_header['filename'])) {
			@ chmod($p_header['filename'], octdec(C::get('filemask', 'cfg')) & (substr($p_header['filename'], -1) == "/" ? 0777 : 0666));
			#}
			return 1;
		}
		$GLOBALS['user_vars'] = array ('sqlfile' => $sqlfile, 'accepteddirs' => $accepteddirs, 'acceptedexts' => $acceptedexts, 'tmpdir' => $tmpdir);
		$res = $archive->extract(PCLZIP_CB_PRE_EXTRACT, 'preextract', PCLZIP_CB_POST_EXTRACT, 'postextract');
		#echo "ici $res";
		error_reporting($err);
		if (!$res)
			trigger_error("ERROR: unable to extract $archivename.<br />".$archive->error_string, E_USER_ERROR);
		unset($archive);
		if (filesize($sqlfile) <= 0)
			return false;
	}

	return true;
}
?>
