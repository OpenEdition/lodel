<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fonctions de backup :p
 */

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
	}	elseif ( $operation == 'importdir' )	{
		$context['outfilename'] = C::get('importdir', 'cfg')."/$archivefilename";
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

	if (!empty($GLOBALS['currentprefix'])) {
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
 * @param array  $accepteddirs la liste des répertoires acceptés dans l'archive ZIP
 * @param array  $acceptedexts la liste des types de fichiers acceptés
 * @param string $modelfile le nom du fichier model à traiter. Vide par défaut
 * @param bool   $xml si on utilise l'import XML
 */
function importFromZip($archive, $accepteddirs, $acceptedexts = array (), $modelfile = '', $xml=false)
{
    $tmpdir = tmpdir(uniqid('site_import'));

    // find files to unzip
    $listfiles = get_zip_file_list($archive);

    if (empty($listfiles))
        return false;

    $dirs = '';
    $model_zip = null;
    $model_ext = $xml ? '.xml' : '.sql';
    $files_to_extract = array();

    /* Vérification que les fichiers sont biens autorisés en matchant le nom du répertoire et des extensions
     *
     * */
    foreach($listfiles as $file)
    {
        foreach($accepteddirs as $dir)
        {
            if(strpos($file, $dir) === 0){
                if(!file_exists(SITEROOT . DIRECTORY_SEPARATOR . $dir)){
                    mkdir(SITEROOT . DIRECTORY_SEPARATOR . $dir, 0770, true);
                }
                if($acceptedexts){
                    $pathinfos = pathinfo($file);
                    foreach($acceptedexts as $ext)
                    {
                        if((isset($pathinfos['extension'])) && ($pathinfos['extension'] == $ext))
                        {
                            $files_to_extract[] = $file;
                            break;
                        }
                    }
                }else{
                    $files_to_extract[] = $file;
                    break;
                }

            }
        }
        if($modelfile && ( strpos($file, $model_ext) === strlen($file) - strlen($model_ext) ) ){
            $model_zip = $file ;
        }
    }

    extract_files_from_zip($archive, realpath(SITEROOT), null, $files_to_extract);

    /* Extraction du fichier sql */
    extract_files_from_zip($archive, $tmpdir, null, $model_zip);
    rename($tmpdir . DIRECTORY_SEPARATOR . $model_zip, $modelfile);
    rmdir($tmpdir);

    if (filesize($modelfile) <= 0)
        return false;

	return true;
}
