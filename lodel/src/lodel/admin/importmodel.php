<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003-2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
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
require ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
#authenticate();


$context[importdir]=$importdir;
$fileregexp='(model)-\w+(?:-\d+)?.zip';

$importdirs=array("CACHE",$importdir,$home."../install/plateform");


$archive=$_FILES['archive']['tmp_name'];
$context['erreur_upload']=$_FILES['archive']['error'];
if (!$context['erreur_upload'] && $archive && $archive!="none" && is_uploaded_file($archive)) { // Upload
  $fichier=$_FILES['archive']['name'];
  if (!preg_match("/^$fileregexp$/",$fichier)) $fichier="model-import-".date("dmy").".zip";

  if (!move_uploaded_file($archive,"CACHE/".$fichier)) die("ERROR: a problem occurs while moving the uploaded file.");

  $fichier=""; // on repropose la page
} else



if ($fichier && preg_match("/^(?:".str_replace("/",'\/',join("|",$importdirs)).")\/$fileregexp$/",$fichier,$result) && file_exists($fichier)) { // fichier sur le disque
  $prefix=$result[1];

} else { // rien
  $fichier="";
}

if ($fichier && $delete) {
  // extra check. Need more ?
  if (dirname($fichier)=="CACHE") {
    unlink($fichier);
  }
  //
} elseif ($fichier) {
  require_once ($home."connect.php");  
  require_once ($home."backupfunc.php");
  require ($home."func.php");

  $sqlfile=tempnam(tmpdir(),"lodelimport_");
  $accepteddirs=array("tpl","css");
  $acceptedexts=array("html","css");

  if (!importFromZip($fichier,$accepteddirs,$acceptedexts,$sqlfile)) { $err=$context['erreur_extract']=1; break; }

  // execute the editorial model
  if (!execute_dump($sqlfile)) $context['erreur_execute_dump']=$err=mysql_error();
  @unlink($sqlfile);

  // change the id in order there are minimal and unique
  require_once($home."objetfunc.php");
  makeobjetstable();

  require_once($home."cachefunc.php");
  removefilesincache(SITEROOT,SITEROOT."lodel/edition",SITEROOT."lodel/admin");

  if (!$err) {
    if ($frominstall) { header ("location: ../edition"); die(); }
    back();
  }
} else {
  // check the table exists
  $result=mysql_list_tables($GLOBALS[currentdb]);
  $existingtables=array();
  while ($row = mysql_fetch_row($result)) array_push($existingtables,$row[0]);
  
  // verifie qu'on peut importer le modele.
  foreach(array_intersect(array("$GLOBALS[tp]entites",
				"$GLOBALS[tp]entrees",
				"$GLOBALS[tp]personnes"),$existingtables) as $table) {
    $result=mysql_query("SELECT 1 FROM $table WHERE statut>-64 LIMIT 0,1") or die(mysql_error());
    if (mysql_num_rows($result)) {
      $context[erreur_table]=$table;
      break;
    } 
  }
}


require ($home."calcul-page.php");

if ($frominstall) {
  $context[frominstall]=1;
  calcul_page($context,"importmodel-frominstall");
} else {
  calcul_page($context,"importmodel");
}


function loop_fichiers(&$context,$funcname)
{
  global $fileregexp,$importdirs,$home;

  foreach ($importdirs as $dir) {
    if ( $dh= @opendir($dir)) {
      while (($file=readdir($dh))!==FALSE) {
	if (!preg_match("/^$fileregexp$/i",$file)) continue;
	$localcontext=$context;
	$localcontext['filename']=$file;
	$localcontext['fullfilename']="$dir/$file";
	if ($dir=="CACHE") $localcontext[maybedeleted]=1;

	// open ZIP archive and extract model.sql
	if ($unzipcmd && $unzipcmd!="pclzip") {
	  $line=`$unzipcmd $dir/$file -c model.sql`;
	} else {
	  require_once($home."pclzip.lib.php");
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

	if (doubleval($localcontext[lodelversion])!=doubleval($GLOBALS[version])) $localcontext[warning_version]=1;
	call_user_func("code_do_$funcname",$localcontext);
      }
      closedir ($dh);
    }
  }
}



?>
