<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
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

require_once 'unset_globals.php';
define("LODELPREFIX","__LODELTP__");

$GLOBALS['lodelsitetables']=array("$GLOBALS[tp]objets",
				  "$GLOBALS[tp]entites",
				  "$GLOBALS[tp]relations",
				  "$GLOBALS[tp]publications",
				  "$GLOBALS[tp]documents",
				  "$GLOBALS[tp]champs",
				  "$GLOBALS[tp]groupesdechamps",
				  "$GLOBALS[tp]personnes",
				  "$GLOBALS[tp]users",
				  "$GLOBALS[tp]groupes",
				  "$GLOBALS[tp]users_groupes",
				  "$GLOBALS[tp]types",
				  "$GLOBALS[tp]typepersonnes",
				  "$GLOBALS[tp]typeentrees",
				  "$GLOBALS[tp]entrees",
				  "$GLOBALS[tp]taches",
				  "$GLOBALS[tp]textes",
				  "$GLOBALS[tp]entites_personnes",
				  "$GLOBALS[tp]entites_entrees",
				  "$GLOBALS[tp]typeentites_typeentites",
				  "$GLOBALS[tp]typeentites_typeentrees",
				  "$GLOBALS[tp]typeentites_typepersonnes",
				  "$GLOBALS[tp]options");

$GLOBALS['lodelbasetables']=array("$GLOBALS[tp]sites",
				  "$GLOBALS[tp]users",
				  "$GLOBALS[tp]pileurl",
				  "$GLOBALS[tp]session");



$userlink=TRUE;
$server=TRUE;
$GLOBALS['strDatabase']="Database";
$GLOBALS['strTableStructure']="Table structure for table";
$GLOBALS['strDumpingData'] = "Dumping data for table";

require($home."pma/mysql_wrappers.lib.php");
require($home."pma/defines.lib.php");
require($home."pma/defines_php.lib.php");
require($home."pma/common.lib.php");
require($home."pma/sql-modified.php");
#require($home."pma/read_dump.lib.php");

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
 *
 *
*/


function operation($operation,$archivetmp,$archivefilename,&$context) {

  if ($operation=="download") {
    download($archivetmp,$archivefilename);
    @unlink($archivetmp);
    return TRUE;
  } elseif ($operation=="cache" || $operation=="importdir") {
    $context[outfilename]=$operation=="cache" ? 
      "CACHE/$archivefilename" : $GLOBALS[importdir]."/$archivefilename";
    if (!(@rename($archivetmp,$context[outfilename]))) {
      $context[erreur]=1;
      return FALSE;
    } else {
      // ok, continue
      return FALSE;
    }
  } else {
    die ("ERROR: unknonw operation");
  }
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

function mysql_dump($db,$tables,$output,$fh=0,$create=true,$drop=true,$contents=true)

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

  if (!$tables) die("ERROR: tables is not defined in mysql_dump");
#    $results = PMA_mysql_list_tables($db);
#    if (!$results) die (mysql_error());
#    $num_tables = @mysql_numrows($results);
#    for($i=0; $i<$num_tables; $i++) $tables[]=PMA_mysql_tablename($results,$i);
#  }
  $num_tables=count($tables);


#} elseif ($export_type == 'database') {
    PMA_exportDBHeader($db);
#    if (isset($table_select)) {
#        $tmp_select = implode($table_select, '|');
#        $tmp_select = '|' . $tmp_select . '|';
#    }
    $i = 0;
    while ($i < $num_tables) {
        $table = $tables[$i];
        $local_query  = 'SELECT * FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table);
	
	if ($create) PMA_exportStructure($db, $table, $crlf, $err_url);
	if ($contents) PMA_exportData($db, $table, $crlf, $err_url, $local_query);
        $i++;
    }
    PMA_exportDBFooter($db);

    if (!$fh) fclose($GLOBALS['mysql_dump_file_handle']);
}


function execute_dump($filename)

{
  require_once($GLOBALS[home]."func.php");
  // constant
  $chunk=16384;
#  $chunk=2048;

  $fh=fopen($filename,"r") or die("ERROR: invalid $filename");

  while (!feof($fh)) {
    $buf=fread($fh,$chunk);    
    #echo "<font color=\"red\">",htmlentities($buf),"</font>";
    $pieces=array();
    $buf=$lastpiece.$buf; // add the last piece in front of the buffer
    $fullstatment=PMA_splitSqlFile($pieces, $buf, PMA_MYSQL_INT_VERSION);
    $pieces_count = count($pieces);
    $pieces_to_execute=$fullstatment ? $pieces_count : $pieces_count-1;

    #echo "pieces_to_execute=",$pieces_to_execute,"<br />";

    for ($i = 0; $i < $pieces_to_execute; $i++) {
      #echo "<li>",$i," ",htmlentities($pieces[$i]),"</li>";
      // add the prefix

      #if (preg_match("/CREATE/",$pieces[$i])) echo $pieces[$i],"<br />";

       PMA_mysql_query(str_replace(array('condition varchar(', 'condition tinytext'), array('`condition` varchar(',  '`condition` tinytext'), $pieces[$i])) or die(mysql_error());
#       if ($result == FALSE) {     
#        //      echo $pieces[$i],"<br>\n"; flush();
#	return FALSE; }
    }
    $lastpiece=$fullstatment ? "" : $pieces[$pieces_count-1];
  }

  // simple version for small files. Big files exeed memory !!!
  //PMA_splitSqlFile($pieces, file_get_contents($filename), PMA_MYSQL_INT_VERSION);
  //$pieces_count = count($pieces);
  //
  //for ($i = 0; $i < $pieces_count; $i++) {
  //  $a_sql_query = $pieces[$i];
  //  $result = PMA_mysql_query($a_sql_query);
  //  if ($result == FALSE) {     
# //     echo $a_sql_query,"<br>\n"; flush();
  //    return FALSE; }
  //} // end for

  return TRUE;
}

/**
 * Remove the actual prefix and add the common prefix
 *
 *
 */

function lodelprefix($table)

{
  $table=substr($table,strlen($GLOBALS[tp]));
  return $GLOBALS[uselodelprefix] ? LODELPREFIX.$table : $table;
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


//////// version legerement modifie: suppression des trim et ltrim
/**
 * Removes comment lines and splits up large sql files into individual queries
 *
 * Last revision: September 23, 2001 - gandon
 * Modified by Ghislain:  trim and ltrim removed
 * Modified by Ghislain:  return TRUE only when it really finish a complete statement.
 *
 * @param   array    the splitted sql commands
 * @param   string   the sql commands
 * @param   integer  the MySQL release number (because certains php3 versions
 *                   can't get the value of a constant from within a function)
 *
 * @return  boolean  always true
 *
 * @access  public
 */
function PMA_splitSqlFile(&$ret, $sql, $release)
{
#    $sql          = trim($sql);
    $sql_len      = strlen($sql);
    $char         = '';
    $string_start = '';
    $in_string    = FALSE;
    $time0        = time();

    $prefixescape  =LODELPREFIX;

    for ($i = 0; $i < $sql_len; ++$i) {
        $char = $sql[$i];

        // We are in a string, check for not escaped end of strings except for
        // backquotes that can't be escaped
        if ($in_string) {
            for (;;) {
                $i         = strpos($sql, $string_start, $i);
                // No end of string found -> add the current substring to the
                // returned array
                if (!$i) {
                    $ret[] = $sql;
                    return FALSE;
                }
                // Backquotes or no backslashes before quotes: it's indeed the
                // end of the string -> exit the loop
                else if ($string_start == '`' || $sql[$i-1] != '\\') {
                    $string_start      = '';
                    $in_string         = FALSE;
                    break;
                }
                // one or more Backslashes before the presumed end of string...
                else {
                    // ... first checks for escaped backslashes
                    $j                     = 2;
                    $escaped_backslash     = FALSE;
                    while ($i-$j > 0 && $sql[$i-$j] == '\\') {
                        $escaped_backslash = !$escaped_backslash;
                        $j++;
                    }
                    // ... if escaped backslashes: it's really the end of the
                    // string -> exit the loop
                    if ($escaped_backslash) {
                        $string_start  = '';
                        $in_string     = FALSE;
                        break;
                    }
                    // ... else loop
                    else {
                        $i++;
                    }
                } // end if...elseif...else
            } // end for
        } // end if (in string)

        // We are not in a string, first check for delimiter...
        else if ($char == ';') {
            // if delimiter found, add the parsed part to the returned array
            $ret[]      = substr($sql, 0, $i);
#            $sql        = ltrim(substr($sql, min($i + 1, $sql_len)));
            $sql        = substr($sql, min($i + 1, $sql_len));
            $sql_len    = strlen($sql);
            if ($sql_len) {
                $i      = -1;
            } else {
                // The submited statement(s) end(s) here
	      return TRUE; // c'est bon alors
            }
        } // end else if (is delimiter)

        // ... then check for start of a string,...
        else if (($char == '"') || ($char == '\'') || ($char == '`')) {
            $in_string    = TRUE;
            $string_start = $char;
        } // end else if (is start of string)

        // ... for start of a comment (and remove this comment if found)...
	// ghislain: ajout du cas ou on a des ---
        else if ($char == '#'
                 || ( ($char == ' ' || $char == '-') && $i > 1 && $sql[$i-2] . $sql[$i-1] == '--')) {
            // starting position of the comment depends on the comment type
            $start_of_comment = (($sql[$i] == '#') ? $i : $i-2);
            // if no "\n" exits in the remaining string, checks for "\r"
            // (Mac eol style)
            $end_of_comment   = (strpos(' ' . $sql, "\012", $i+2))
                              ? strpos(' ' . $sql, "\012", $i+2)
                              : strpos(' ' . $sql, "\015", $i+2);
            if (!$end_of_comment) {
                // no eol found after '#', add the parsed part to the returned
                // array if required and exit
                if ($start_of_comment > 0) {
#                    $ret[]    = trim(substr($sql, 0, $start_of_comment));
#                    $ret[]    = substr($sql, 0, $start_of_comment);
# faut qu'on recupere tout.... pour le traitement ulterieur.
		  $ret[]    = $sql;
                }
                return FALSE;
            } else {
#                $sql          = substr($sql, 0, $start_of_comment)
#                              . ltrim(substr($sql, $end_of_comment));
                $sql          = substr($sql, 0, $start_of_comment)
                              . substr($sql, $end_of_comment);
                $sql_len      = strlen($sql);
                $i--;
            } // end if...else
        } // end else if (is comment)

        // ... and finally disactivate the "/*!...*/" syntax if MySQL < 3.22.07
        else if ($release < 32270
                 && ($char == '!' && $i > 1  && $sql[$i-2] . $sql[$i-1] == '/*')) {
            $sql[$i] = ' ';
        }// end else if
	else if ($char == $prefixescape[0] && // look for prefix table
		 substr($sql,$i,strlen($prefixescape)) == $prefixescape) { 
	  // replace
	  $sql = substr($sql,0,$i).$GLOBALS[tp].substr($sql,$i+strlen($prefixescape));
	  $sql_len    = strlen($sql);
	}

        // loic1: send a fake header each 30 sec. to bypass browser timeout
        $time1     = time();
#        if ($time1 >= $time0 + 30) {
#            $time0 = $time1;
#            header('X-pmaPing: Pong');
#        } // end if
    } // end for

    // add any rest to the returned array
    if (!empty($sql) && preg_match('@[^[:space:]]+@', $sql)) {
        $ret[] = $sql;
	return FALSE;
    }

    return TRUE;
} // end of the 'PMA_splitSqlFile()' function



function backupME($sqlfile,$sqlonly) {
  global $zipcmd;

  $acceptedexts=array("html","css");

  $tmpdir=tmpdir();
  $archivetmp=tempnam($tmpdir,"lodeldump_").".zip";

  if (!$sqlonly) {
    // search dirs to archive. Dirs must contains file !
    $dirs=array();
    foreach (array("tpl","css") as $dir) {
      if (!file_exists(SITEROOT.$dir)) continue;
      $dh=opendir(SITEROOT.$dir);
      while ( ($file=readdir($dh)) && !preg_match("/\.(".join("|",$acceptedexts).")$/",$file)) {}
      if ($file) $dirs[]=$dir;
      closedir($dh);
    }
  }
  //

  if ($zipcmd && $zipcmd!="pclzip") {
    if ($dirs) {
      foreach ($dirs as $dir) {
	foreach($acceptedexts as $ext) {
	  $files.=" $dir/*.$ext";
	}
      }
      if (!chdir(SITEROOT)) die ("ERROR: can't chdir in SITEROOT");
      $prefixdir=$tmpdir[0]=="/" ? "" : "lodel/admin/";
      system($zipcmd." -q $prefixdir$archivetmp $files");
      if (!chdir("lodel/admin")) die ("ERROR: can't chdir in lodel/admin");
      system($zipcmd." -q -g $archivetmp -j $sqlfile");
    } else {
      system($zipcmd." -q $archivetmp -j $sqlfile");
    }
  } else { // pclzip
    require($home."pclzip.lib.php");
    $archive=new PclZip ($archivetmp);
    if ($dirs) {
      // function to exclude files and rename directories
      function preadd($p_event,&$p_header,$user_vars) {
	$p_header['stored_filename']=preg_replace("/^".preg_quote($user_vars['tmpdir'],"/")."\//","",$p_header['stored_filename']);

	#echo $p_header['stored_filename'],"<br>";
	return preg_match("/\.(".join("|",$user_vars['acceptedexts']).
			  "|sql)$/",$p_header['stored_filename']);
      }
      // end of function to exclude files
      foreach ($dirs as $dir) { $files[]=SITEROOT.$dir; }
      $files[]=$sqlfile;
      $archive->user_vars=array("tmpdir"=>$tmpdir,"acceptedexts"=>$acceptedexts);
      $res=$archive->create($files,
			    PCLZIP_OPT_REMOVE_PATH,SITEROOT,
			    PCLZIP_CB_PRE_ADD, 'preadd'
			    );
      if (!$res) die("ERROR: Error while creating zip archive: ".$archive->error_string);
    } else {
      $archive->create($sqlfile,PCLZIP_OPT_REMOVE_ALL_PATH);
    }
  } // end of pclzip option

  return $archivetmp;
}


function importFromZip ($archive,$accepteddirs,$acceptedexts=array(),$sqlfile="")

{
  global $unzipcmd;

  $tmpdir=tmpdir();

  // use UNZIP command
  if ($unzipcmd && $unzipcmd!="pclzip") {
    // find files to unzip
    $listfiles=`$unzipcmd -Z -1 $archive`;
    if (!$listfiles)  return false;
    $dirs="";
    foreach ($accepteddirs as $dir) {
      if (preg_match("/^(\.\/)?".str_replace("/",'\/',$dir)."\//m",$listfiles) && 
	  file_exists(SITEROOT.$dir)) {
	if ($acceptedexts) {
	  foreach($acceptedexts as $ext) {
	    $dirs.=$dir."/*.$ext ".$dir."/*/*.$ext ";
	  }
	} else {
	  $dirs.=$dir."/* ".$dir."/*/* ";
	}
      }
    }
    if (!chdir (SITEROOT)) die("ERROR: chdir fails");

    // erase the files if there exists
    $listfiles=preg_split("/\n/",`$unzipcmd -Z -1 $archive $dirs`);
    foreach ($listfiles as $file) {
      if (file_exists($file))	unlink($file);
    }
    //
    system ($unzipcmd." -oq $archive  $dirs");
    if (!chdir ("lodel/admin")) die("ERROR: chdir 2 fails");
    if ($sqlfile) {
      system ($unzipcmd." -qp $archive  *.sql >$sqlfile");
      if (filesize($sqlfile)<=0)  return false;
    }
  } else { // use PCLZIP library
      require($home."pclzip.lib.php");
      $archive=new PclZip($archive);

      // functions callback
      function preextract($p_event, &$p_header, $user_vars) { // choose the files to extract
	//echo $p_header['filename'],"<br>";
	if (preg_match("/^(\.\/)*.*\.sql$/",$p_header['filename'])) { // extract the sql file
	  unlink($user_vars['sqlfile']); // remove the tmpfile if not it is not overwriten... 
	  //                   may cause problem if the file is recreated but it's so uncertain !
	  $p_header['filename']=$user_vars['sqlfile'];
	  return 1;
	}
	$exts=$user_vars['acceptedexts'] ? ".*\.(".join("|",$user_vars['acceptedexts']).")$" : "";

      if (preg_match("/^(\.\/)*".str_replace("/","\/",join("|",$user_vars['accepteddirs'])).
		     "\/$exts/",$p_header['filename'])) {
	$p_header['filename']=SITEROOT.$p_header['filename'];
	if (file_exists($p_header['filename']) && is_file($p_header['filename'])) unlink($p_header['filename']);
	return 1;
      }
      return 0; // don't extract
    }

      function postextract($p_event, &$p_header, $user_vars) { // chmod
	#if ($p_header['filename']!=$user_vars{'sqlfile'} && 
	#    file_exists($p_header['filename'])) {
	  @chmod($p_header['filename'],octdec($GLOBALS[filemask]) & 
		 (substr($p_header['filename'],-1)=="/" ? 0777 : 0666));
        #}
	return 1;
      }
      $archive->user_vars=array("sqlfile"=>$sqlfile,
				"accepteddirs"=>$accepteddirs,
				"acceptedexts"=>$acceptedexts,
				"tmpdir"=>$tmpdir);
      $res=$archive->extract(PCLZIP_CB_PRE_EXTRACT, 'preextract',
			PCLZIP_CB_POST_EXTRACT, 'postextract');

      if (!$res) die("ERROR: unable to extract $archive.<br>".$archive->error_string);
      if (filesize($sqlfile)<=0) return false;
  }

  return true;
}




    /**
     * Removes comment lines and splits up large sql files into individual queries
     *
     * Last revision: September 23, 2001 - gandon
     * Changed by ghislain for speeding the reading. substr are a nightmare 
     * when the file is huge.
     *
     * @param   array    the splitted sql commands
     * @param   string   the sql commands
     * @param   integer  the MySQL release number (because certains php3 versions
     *                   can't get the value of a constant from within a function)
     *
     * @return  boolean  always true
     *
     * @access  public
     */
/* Faster Version  by Ghislain
function PMA_splitSqlFile(&$ret, &$sql, $release)
{
#        $sql          = trim($sql);
        $sql_len      = strlen($sql);
        $char         = '';
        $string_start = '';
        $in_string    = FALSE;
        $time0        = time();
	$start_cmd = 0;
    
        for ($i = 0; $i < $sql_len; ++$i) {
            $char = $sql[$i];
#	    echo $i," ",$sql_len,"<br>\n"; flush();
    
            // We are in a string, check for not escaped end of strings except for
            // backquotes that can't be escaped
            if ($in_string) {
                for (;;) {
                    $i         = strpos($sql, $string_start, $i);
                    // No end of string found -> add the current substring to the
                    // returned array
                    if ($i===FALSE) {
#                        $ret[] = $sql;
                        return TRUE;
                    }
                    // Backquotes or no backslashes before quotes: it's indeed the
                    // end of the string -> exit the loop
                    else if ($string_start == '`' || $sql[$i-1] != '\\') {
                        $string_start      = '';
                        $in_string         = FALSE;
                        break;
                    }
                    // one or more Backslashes before the presumed end of string...
                    else {
                        // ... first checks for escaped backslashes
                        $j                     = 2;
                        $escaped_backslash     = FALSE;
                        while ($i-$j > 0 && $sql[$i-$j] == '\\') {
                            $escaped_backslash = !$escaped_backslash;
                            $j++;
                        }
                        // ... if escaped backslashes: it's really the end of the
                        // string -> exit the loop
                        if ($escaped_backslash) {
                            $string_start  = '';
                            $in_string     = FALSE;
                            break;
                        }
                        // ... else loop
                        else {
                            $i++;
                        }
                    } // end if...elseif...else
                } // end for
            } // end if (in string)
    
            // We are not in a string, first check for delimiter...
            else if ($char == ';') {
                // if delimiter found, add the parsed part to the returned array
                $ret[]      = trim(substr($sql, $start_cmd, $i-$start_cmd+1));
#		echo "::::",substr($sql, $start_cmd, $i-$start_cmd),"::::<br>";
#                $sql        = ltrim(substr($sql, min($i + 1, $sql_len)));
		$start_cmd=$i+1;
#                $sql_len    = strlen($sql);
#                if ($sql_len) {
#                    $i      = -1;
#                } else {
#                    // The submited statement(s) end(s) here
#                    return TRUE;
#                }
            } // end else if (is delimiter)
    
            // ... then check for start of a string,...
            else if (($char == '"') || ($char == '\'') || ($char == '`')) {
                $in_string    = TRUE;
                $string_start = $char;
            } // end else if (is start of string)
    
            // ... for start of a comment (and remove this comment if found)...
            else if ($char == '#'
		       || ($char == ' ' && $i > 1 && $sql[$i-2] . $sql[$i-1] == '--')) {
                // starting position of the comment depends on the comment type
                $start_of_comment = (($sql[$i] == '#') ? $i : $i-2);
                // if no "\n" exits in the remaining string, checks for "\r"
                // (Mac eol style)
                $end_of_comment   = (strpos($sql, "\012", $i+2)!==FALSE)
                                  ? strpos($sql, "\012", $i+2)
                                  : strpos($sql, "\015", $i+2);
                if ($end_of_comment===FALSE) {
                    // no eol found after '#', add the parsed part to the returned
                    // array if required and exit
#                    if ($start_of_comment > 0) {
#                        $ret[]    = trim(substr($sql, 0, $start_of_comment));
#                    }
                    return TRUE;
                } else {
#                    $sql          = substr($sql, 0, $start_of_comment)
#                                  . ltrim(substr($sql, $end_of_comment+1));

#		  echo "comment: ",substr($sql, $start_of_comment,$end_of_comment-$start_of_comment),".....",$sql[$end_of_comment+1],"<br>\n";

		  $start_cmd=$end_of_comment+1;
		  $i=$end_of_comment;

#                  $sql_len      = strlen($sql);
#                    $i--;

                } // end if...else
            } // end else if (is comment)
    
#            // ... and finally disactivate the "/ *!...* /" syntax if MySQL < 3.22.07
#            else if ($release < 32270
#                     && ($char == '!' && $i > 1  && $sql[$i-2] . $sql[$i-1] == '/'.'*')) {
#                $sql[$i] = ' ';
#            } // end else if
    
            // loic1: send a fake header each 30 sec. to bypass browser timeout
            $time1     = time();
            if ($time1 >= $time0 + 30) {
                $time0 = $time1;
                header('X-pmaPing: Pong');
            } // end if
        } // end for
    
        // add any rest to the returned array
#        if (!empty($sql) && ereg('[^[:space:]]+', $sql)) {
#            $ret[] = $sql;
#        }
    
        return TRUE;
    } // end of the 'PMA_splitSqlFile()' function
*/

    
?>
