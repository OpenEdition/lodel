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
$fileregexp='(site|revue)-\w+-\d+.tar.gz';
$importdirs=array($importdir,"CACHE");


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
  // detar dans le repertoire du site
  $listfiles=`tar ztf $fichier 2>/dev/null`;
  $dirs="";
  foreach (array("lodel/txt","lodel/rtf","lodel/sources","docannexe") as $dir) {
    if (preg_match("/^(\.\/)?".str_replace("/",'\/',$dir)."\b/m",$listfiles) && file_exists(SITEROOT.$dir)) $dirs.=$dir." ";
  }
  if ($dirs) {
    #echo "tar zxf $fichier -C ".SITEROOT." $dirs 2>&1";
    system("tar zxf $fichier -C ".SITEROOT." $dirs 2>&1")!==FALSE or die ("impossible d'executer tar");
  }

  require_once ($home."connect.php");
  // drop les tables existantes
  // recupere la liste des tables
  $result=mysql_list_tables($GLOBALS[currentdb]);
  $tables=array();
  while ($row = mysql_fetch_row($result)) array_push($tables,$row[0]);
  if($tables) mysql_query("DROP TABLE IF EXISTS ".join(",",$tables)) or die(mysql_error()); 
  $tmpfile=tempnam("/tmp","lodelimport_");
  system("tar zxf $fichier -O '$prefix-*.sql' >$tmpfile")!==FALSE or die ("impossible d'executer tar");
  require_once ($home."backupfunc.php");
  if (!execute_dump($tmpfile)) $context[erreur_execute_dump]=$err=mysql_error();
  @unlink($tmpfile);

  require_once($home."cachefunc.php");
  removefilesincache(SITEROOT,SITEROOT."lodel/edition",SITEROOT."lodel/admin");

// verifie les .htaccess dans le CACHE
  $dirs=array("CACHE","lodel/admin/CACHE","lodel/edition/CACHE","lodel/txt","lodel/rtf","lodel/sources");
   foreach ($dirs as $dir) {
     if (!file_exists(SITEROOT.$dir)) continue;
     $file=SITEROOT.$dir."/.htaccess";
     if (file_exists($file)) @unlink($file);
     $f=@fopen ($file,"w");
     if (!$f) {
       $context[erreur_htaccess].=$dir." ";
       $err=1;
     } else {
       fputs($f,"deny from all\n");
       fclose ($f);
     }
   }
   if (!$err) { 
     include_once ($home."func.php"); 
     back();
   }
}


include ($home."calcul-page.php");
calcul_page($context,"import");


function loop_fichiers(&$context,$funcname)
{
  global $importdirs,$fileregexp;

  foreach ($importdirs as $dir) {
    if ( $dh= @opendir($dir)) {
      while (($file=readdir($dh))!==FALSE) {
	if (!preg_match("/^$fileregexp$/i",$file)) continue;
	$context[nom]="$dir/$file";
	call_user_func("code_do_$funcname",$context);
      }
      closedir ($dh);
    }
  }
}



?>
