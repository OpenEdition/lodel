<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
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
$fileregexp='(model)-\w+-\d+.sql';

$importdirs=array("CACHE",$importdir,$home."../install/plateform");


$archive=$HTTP_POST_FILES['archive']['tmp_name'];
$context['erreur_upload']=$HTTP_POST_FILES['archive']['error'];
if (!$context['erreur_upload'] && $archive && is_uploaded_file($archive)) { // Upload
  $fichier=$HTTP_POST_FILES['archive']['name'];
  if (!preg_match("/^$fileregexp$/",$fichier)) $fichier="model-import-".date("dmy").".sql";

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

  // execute the editorial model
  if (!execute_dump($fichier)) $context[erreur_execute_dump]=$err=mysql_error();

  // change the id in order there are minimal and unique
  require_once($home."objetfunc.php");
  makeobjetstable();

  require_once($home."cachefunc.php");
  removefilesincache(SITEROOT,SITEROOT."lodel/edition",SITEROOT."lodel/admin");

  if (!$err) {     back();   }
} else {


  // check the table exists
  $result=mysql_list_tables($GLOBALS[currentdb]);
  $existingtables=array();
  while ($row = mysql_fetch_row($result)) array_push($existingtables,$row[0]);
  
  // verifie qu'on peut importer le modele.
  foreach(array_intersect(array("entites","entrees","personnes"),$existingtables) as $table) {
    $result=mysql_query("SELECT 1 FROM $GLOBLAS[tp]$table WHERE statut>-64 LIMIT 0,1") or die(mysql_error());
    if (mysql_num_rows($result)) {
      $context[erreur_table]=$table;
      break;
    } 
  }
}


require ($home."calcul-page.php");
calcul_page($context,"importmodel");


function loop_fichiers(&$context,$funcname)
{
  global $fileregexp,$importdirs;

  foreach ($importdirs as $dir) {
    if ( $dh= @opendir($dir)) {
      while (($file=readdir($dh))!==FALSE) {
	if (!preg_match("/^$fileregexp$/i",$file)) continue;
	$localcontext=$context;
	$localcontext[nom]=$file;
	$localcontext[fullname]="$dir/$file";
	if ($dir=="CACHE") $localcontext[maybedeleted]=1;

	$fh=fopen($localcontext[fullname],"r");
	if (!$fh) continue;
	$xml="";
	do {
	  $buf.=fread($fh,1024);
	  if (preg_match("/<model>(.*?)<\/model>/s",$buf,$result)) {
	    $lines=preg_split("/\n/",$result[1]);
	    $xml="";
	    foreach ($lines as $line) {
	      $xml.=substr($line,2);
	    }
	    break;
	  }
	} while(!feof($fh));
	foreach (array("lodelversion","description","author","date") as $tag) {
	  if (preg_match("/<$tag>(.*?)<\/$tag>/",$xml,$result)) {
	    $localcontext[$tag]=str_replace(array("\n","\r","<",">"),
				       array("<br />","","&lt;","&gt;"),
				       $result[1]);
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
