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
include ($home."auth.php");
authenticate(LEVEL_ADMINLODEL,NORECORDURL);
#authenticate();



$context[importdir]=$importdir;
$fileregexp='(model)-\w+-\d+.sql';

$importdirs=array($importdir,"CACHE",$home."../install/plateform");




$archive=$HTTP_POST_FILES['archive']['tmp_name'];
$context['erreur_upload']=$HTTP_POST_FILES['archive']['error'];
if (!$context['erreur_upload'] && $archive && is_uploaded_file($archive)) { // Upload
  $prefix="*";
  $fichier=$archive;

} elseif ($fichier && preg_match("/^(?:".str_replace("/",'\/',join("|",$importdirs)).")\/$fileregexp$/",$fichier,$result) && file_exists($fichier)) { // fichier sur le disque
  $prefix=$result[1];

} else { // rien
  $fichier="";
}


if ($fichier) {
  require_once ($home."connect.php");  
  require_once ($home."backupfunc.php");
  include ($home."func.php");

  if (!execute_dump($fichier)) $context[erreur_execute_dump]=$err=mysql_error();

  if (!$err) {     back();   }
} else {
  // verifie qu'on peut.
  foreach(array("entites","entrees","personnes") as $table) {
    $result=mysql_query("SELECT 1 FROM $GLOBLAS[tp]$table WHERE statut>-64 LIMIT 0,1") or die(mysql_error());
    if (mysql_num_rows($result)) {
#      $context[erreur_table]=$table;
#      break;
    } 
  }
}


include ($home."calcul-page.php");
calcul_page($context,"importmodel");


function loop_fichiers(&$context,$funcname)
{
  global $fileregexp,$importdirs;

  foreach ($importdirs as $dir) {
    if ( $dh= @opendir($dir)) {
      while (($file=readdir($dh))!==FALSE) {
	if (!preg_match("/^$fileregexp$/i",$file)) continue;
	$context[nom]=$file;
	$context[fullname]="$dir/$file";

	$fh=fopen($context[fullname],"r");
	if (!$fh) continue;
	$result="";
	do {
	  $buf.=fread($fh,1024);
	  if (preg_match("/# comment\n(.*?\n)# end of comment\n/s",$buf,$result)) break;
	} while(!feof($fh));
	$lines=preg_split("/\n/",htmlentities($result[1]));
	$context[comment]="";
	foreach ($lines as $line) {
	  $context[comment].=substr($line,2)."<br />";
	}

	call_user_func("code_do_$funcname",$context);
      }
      closedir ($dh);
    }
  }
}



?>
