<?php

//
// functions or pieces taken from phpMyAdmin version 2.5.4 release under the GPL license.
// Thanks to the authors !
//


$userlink=TRUE;
$server=TRUE;
$GLOBALS['strDatabase']="Database";
$GLOBALS['strTableStructure']="Table structure for table";
$GLOBALS['strDumpingData'] = "Dumping data for table";

require($home."pma/mysql_wrappers.lib.php");
require($home."pma/defines.lib.php");
require($home."pma/defines_php.lib.php");
require($home."pma/common.lib.php");
require($home."pma/sql.php");
require($home."pma/read_dump.lib.php");

// parser SQL
require($home."pma/string.lib.php");
require($home."pma/sqlparser.data.php");
require($home."pma/sqlparser.lib.php");



/**
 * Lock all
 *
 * @param string database to lock
 *
 */

function lock_write_all($db)

{
  // recupere la liste des tables
  $result=PMA_mysql_list_tables($db) or die(mysql_error());
  if (!$result) die (mysql_error());
  $num_tables = mysql_numrows($result);
  for($i = 0; $i<$num_tables; $i++) {
    $tables[] = PMA_mysql_tablename($result, $i);
  }
  if (!$tables) die("WARNING: no table to lock in database \"$db\"");
  mysql_query("LOCK TABLES ".join (" WRITE ,",$tables)." WRITE") or die (mysql_error());
}



/**
 * Dump the database using phpMyAdmin functions
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

function mysql_dump($db,$output,$drop=TRUE,$fh=0)

{
  if ($fh) {
    $GLOBALS['mysql_dump_file_handle']=$fh;
  } else {
    $GLOBALS['mysql_dump_file_handle']=fopen($output,"w");
    if (!$GLOBALS['mysql_dump_file_handle']) die("ERROR: unable to write file \"$output\"");
  }

  $GLOBALS['drop']=$drop;
  $err_url = $GLOBALS['PHP_SELF']."?erreur=1";
  $crlf = PMA_whichCrlf();

  $tables     = PMA_mysql_list_tables($db);
  if (!$tables) die (mysql_error());
  $num_tables = ($tables) ? @mysql_numrows($tables) : 0;
  if ($num_tables == 0) { return; }




#} elseif ($export_type == 'database') {
    PMA_exportDBHeader($db);
#    if (isset($table_select)) {
#        $tmp_select = implode($table_select, '|');
#        $tmp_select = '|' . $tmp_select . '|';
#    }
    $i = 0;
    while ($i < $num_tables) {
        $table = PMA_mysql_tablename($tables, $i);
        $local_query  = 'SELECT * FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table);
#        if ((isset($tmp_select) && strpos(' ' . $tmp_select, '|' . $table . '|'))
#            || !isset($tmp_select)) {

#            if (isset($GLOBALS[$what . '_structure']))
	      PMA_exportStructure($db, $table, $crlf, $err_url);
 #           if (isset($GLOBALS[$what . '_data'])) 
	      PMA_exportData($db, $table, $crlf, $err_url, $local_query);
#        }
        $i++;
    }
    PMA_exportDBFooter($db);

    if (!$fh) fclose($GLOBALS['mysql_dump_file_handle']);
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

  $write_result = @fwrite($GLOBALS['mysql_dump_file_handle'], $line);
  if (!$write_result || ($write_result != strlen($line))) {
#    $GLOBALS['message'] = sprintf($GLOBALS['strNoSpace'], htmlspecialchars($save_filename));
    return FALSE;
  }

  $time_now = time(); // keep the browser alive !
 if (!$time_start) {
   $time_start = $time_now;
 } elseif ($time_now >= $time_start + 30) {
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


function PMA_convert_charset($what) {
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

{ return $text; }
    
?>
