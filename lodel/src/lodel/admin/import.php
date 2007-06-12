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
include ($home."auth.php");
authenticate(LEVEL_ADMINLODEL,NORECORDURL);
include ($home."func.php");
#authenticate();


$context[importdir]=$importdir;
$fileregexp='(site|revue)-[a-z0-9\-]+-\d+.zip';
$importdirs=array("CACHE");
if ($importdir) $importdirs[]=$importdir;


$archive=$_FILES['archive']['tmp_name'];
$context['erreur_upload']=$_FILES['archive']['error'];
if (!$context['erreur_upload'] && $archive && $archive!="none" && is_uploaded_file($archive)) { // Upload
  $prefixre="(site|revue)";
  $prefixunix="{site,revue}";
  $fichier=$archive;

} elseif ($fichier && preg_match("/^(?:".str_replace("/",'\/',join("|",$importdirs)).")\/$fileregexp$/",$fichier,$result) && file_exists($fichier)) { // fichier sur le disque
  $prefixre=$prefixunix=$result[1];

} else { // rien
  $fichier="";
}

if ($fichier) {
  do { // control block 

    $sqlfile=tempnam(tmpdir(),"lodelimport_");
    $accepteddirs=array("lodel/txt","lodel/rtf","lodel/sources","docannexe/fichier","docannexe/image");

    require_once ($home."backupfunc.php");

    if (!importFromZip($fichier,$accepteddirs,array(),$sqlfile)) { $err=$context[erreur_extract]=1; break; }

    require_once ($home."connect.php");

    // drop les tables existantes
    // recupere la liste des tables

    mysql_query("DROP TABLE IF EXISTS ".join(",",$GLOBALS[lodelsitetables])) or die(mysql_error()); 

    if (!execute_dump($sqlfile)) $context[erreur_execute_dump]=$err=mysql_error();
    @unlink($sqlfile);

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

  } while(0);

  if (!$err) { 
    include_once ($home."func.php"); 
    touch(SITEROOT."CACHE/maj");
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
