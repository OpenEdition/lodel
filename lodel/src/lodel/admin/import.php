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
include ($home."auth.php");
authenticate(LEVEL_ADMINLODEL,NORECORDURL);
include ($home."func.php");
#authenticate();


$context[importdir]=$importdir;
$fileregexp='(site|revue)-\w+-\d+.zip';
$importdirs=array($importdir,"CACHE");


$archive=$_FILES['archive']['tmp_name'];
$context['erreur_upload']=$_FILES['archive']['error'];
if (!$context['erreur_upload'] && $archive && is_uploaded_file($archive)) { // Upload
  $prefix="*";
  $fichier=$archive;

} elseif ($fichier && preg_match("/^(?:".str_replace("/",'\/',join("|",$importdirs)).")\/$fileregexp$/",$fichier,$result) && file_exists($fichier)) { // fichier sur le disque
  $prefix=$result[1];

} else { // rien
  $fichier="";
}


if ($fichier) {
#  // detar dans le repertoire du site
#  $listfiles=`tar ztf $fichier 2>/dev/null`;
#  $dirs="";
#  foreach (array("lodel/txt","lodel/rtf","lodel/sources","docannexe") as $dir) {
#    if (preg_match("/^(\.\/)?".str_replace("/",'\/',$dir)."\b/m",$listfiles) && file_exists(SITEROOT.$dir)) $dirs.=$dir." ";
#  }
#  if ($dirs) {
#    #echo "tar zxf $fichier -C ".SITEROOT." $dirs 2>&1";
#    system("tar zxf $fichier -C ".SITEROOT." $dirs 2>&1")!==FALSE or die ("impossible d'executer tar");
#  }


  $tmpfile=tempnam(tmpdir(),"lodelimport_");
  $accepteddirs=array("lodel/txt","lodel/rtf","lodel/sources","docannexe/fichier","docannexe/image");
  #system("tar zxf $fichier -O '$prefix-*.sql' >$tmpfile")!==FALSE or die ("impossible d'executer tar");

  if ($unzipcmd && $unzipcmd!="pclzip") {
    $listfiles=`$unzipcmd -Z -1 $fichier`;
    $dirs="";
    foreach ($accepteddirs as $dir) {
      if (preg_match("/^(\.\/)?".str_replace("/",'\/',$dir)."\//m",$listfiles) && file_exists(SITEROOT.$dir)) $dirs.=$dir."/* ".$dir."/*/* ";
    }
    if (!chdir (SITEROOT)) die("ERROR: chdir fails");
    system ($unzipcmd." -oq $fichier  $dirs");
    if (!chdir ("lodel/admin")) die("ERROR: chdir 2 fails");
    system ($unzipcmd." -qp $fichier  $prefix-*.sql >$tmpfile");
  } else { // PCLZIP
    require($home."pclzip.lib.php");
    $archive=new PclZip($fichier);
    // function callback
    function preextract($p_event, &$p_header) { // choose the files to extract
      global $accepteddirs,$prefix,$tmpfile;
      if (preg_match("/^(\.\/)*$prefix-.*\.sql$/",$p_header['filename'])) { // extract the sql file
	unlink($tmpfile); // remove the tmpfile if not it is not overwriten... 
	//                   may cause problem if the file is recreated but it's so uncertain !
	$p_header['filename']=$tmpfile;
	return 1;
      }
      if (preg_match("/^(\.\/)*".str_replace("/","\/",join("|",$accepteddirs))."\//",$p_header['filename'])) {
	$p_header['filename']=SITEROOT.$p_header['filename'];
	return 1;
      }
      return 0; // don't extract
    }
    function postextract($p_event, &$p_header) { // chmod
      global $tmpfile;
      if ($p_header['filename']!=$tmpfile && file_exists($p_header['filename'])) {
	@chmod($p_header['filename'],octdec($GLOBALS[filemask]) & 
	       (substr($p_header['filename'],-1)=="/" ? 0777 : 0666));
      }
      return 1;
    }
    $archive->extract(PCLZIP_CB_PRE_EXTRACT, 'preextract',
		      PCLZIP_CB_POST_EXTRACT, 'postextract');
  }

  require_once ($home."connect.php");
  require_once ($home."backupfunc.php");

  // drop les tables existantes
  // recupere la liste des tables

  mysql_query("DROP TABLE IF EXISTS ".join(",",$GLOBALS[lodelsitetables])) or die(mysql_error()); 

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
