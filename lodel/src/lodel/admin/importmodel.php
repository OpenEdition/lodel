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

require("siteconfig.php");
require("auth.php");
authenticate(LEVEL_ADMIN);
require("importfunc.php");
#authenticate();

$file=extract_import("model",$context);

if ($file && $delete) {
  // extra check. Need more ?
  if (dirname($file)=="CACHE") {
    unlink($file);
  }
  //
} elseif ($file || $base) {
  if ($base) $file=$GLOBALS['home']."../install/plateform/model-base.zip";
  require_once("connect.php");  
  require_once("backupfunc.php");
  require("func.php");

  $sqlfile=tempnam(tmpdir(),"lodelimport_");
  $accepteddirs=array("tpl","css","images");
  $acceptedexts=array("html","js","css","png","jpg","jpeg","gif","tiff");

  if (!importFromZip($file,$accepteddirs,$acceptedexts,$sqlfile)) { $err=$context['error_extract']=1; break; }

  // execute the editorial model
  if (!execute_dump($sqlfile)) $context['error_execute_dump']=$err->errormsg();
  @unlink($sqlfile);

  // change the id in order there are minimal and unique
  #require_once("objectfunc.php");
  echo "... a finir... mais vous pouvez poursuivre en cliquant sur le lien <a href=\"index.php\">ici</a>...<br>\n";
  #$err=makeobjetstable();
  #if ($err) die($err);

  require_once("cachefunc.php");
  removefilesincache(SITEROOT,SITEROOT."lodel/edition",SITEROOT."lodel/admin");

  if (!$err) {
    if ($frominstall) { header ("location: ../edition/index.php"); die(); }
    back();
  }
} else {
  // check the table exists
  $result=mysql_list_tables($GLOBALS['currentdb']);
  $existingtables=array();
  while ($row = mysql_fetch_row($result)) array_push($existingtables,$row[0]);
  
  // verifie qu'on peut importer le modele.
  foreach(array_intersect(array("$GLOBALS[tp]entities",
				"$GLOBALS[tp]entries",
				"$GLOBALS[tp]persons"),$existingtables) as $table) {
    $result=mysql_query("SELECT 1 FROM $table WHERE status>-64 LIMIT 0,1") or dberror();
    if (mysql_num_rows($result)) {
      $context['error_table']=$table;
      break;
    } 
  }
}


require("view.php");
$view=&getView();


if ($frominstall) {
  $context['frominstall']=true;
  $GLOBALS['nodesk']=true;
  $view->render($context,"importmodel-frominstall");
} else {
  $view->render($context,"importmodel");
}


function loop_files(&$context,$funcname)
{
  global $fileregexp,$importdirs,$home;

  foreach ($importdirs as $dir) {
    if ( $dh= @opendir($dir)) {
      while (($file=readdir($dh))!==FALSE) {
	if (!preg_match("/^$fileregexp$/i",$file)) continue;
	$localcontext=$context;
	$localcontext['filename']=$file;
	$localcontext['fullfilename']="$dir/$file";
	if ($dir=="CACHE") $localcontext['maybedeleted']=1;

	// open ZIP archive and extract model.sql
	if ($unzipcmd && $unzipcmd!="pclzip") {
	  $line=`$unzipcmd $dir/$file -c model.sql`;
	} else {
	  require_once("pclzip.lib.php");
	  $archive=new PclZip("$dir/$file");
	  $arr=$archive->extract(PCLZIP_OPT_BY_NAME,"model.sql",
				 PCLZIP_OPT_EXTRACT_AS_STRING);
	  $line=$arr[0]['content'];
	}
	if (!$line) continue;

	$xml="";
	if (preg_match("/<model>(.*?)<\/model>/s",$line,$result)) {
	  $lines=preg_split("/\n/",$result[1]);
	  $xml="";
	  foreach ($lines as $line) {
	    $xml.=substr($line,2)."\n";
	  }
	}

	foreach (array("lodelversion","title","description","author","date","modelversion") as $tag) {
	  if (preg_match("/<$tag>(.*?)<\/$tag>/s",$xml,$result)) {
#	    $localcontext[$tag]=utf8_encode(str_replace(array("\r","<",">","\n"),
#							array("","&lt;","&gt;","<br />"),
#							$result[1]));
	    $localcontext[$tag]=str_replace(array("\r","<",">","\n"),
					    array("","&lt;","&gt;","<br />"),
					    trim($result[1]));
	  }
	}
	#echo doubleval($localcontext[lodelversion]), ":",$GLOBALS[version],"<br />\n";

	// check only the major version, sub-version are not checked
	if (doubleval($localcontext['lodelversion'])!=doubleval($GLOBALS['version'])) $localcontext['warning_version']=1;
	call_user_func("code_do_$funcname",$localcontext);
      }
      closedir ($dh);
    }
  }
}



?>
