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
require ($home."auth.php");
authenticate(LEVEL_ADMIN);
require ($home."func.php");


$context['importdir']=$importdir;

if ($backup) {
  require_once($home."func.php");

  // il faut locker la base parce que le dump ne doit pas se faire en meme temps que quelqu'un ecrit un fichier.
  // lock les tables
  require($home."backupfunc.php");

#  lock_write_all($currentdb);

  $outfile="site-$site.sql";
  $uselodelprefix=true;
  $tmpdir=tmpdir();

  dump_site($site,$tmpdir."/".$outfile);

  # verifie que le fichier n'est pas vide
  if (filesize($tmpdir."/".$outfile)<=0) die ("ERROR: mysql_dump failed");

  // tar les sites et ajoute la base
  $archivetmp=tempnam($tmpdir,"lodeldump_").".zip";
#  $archivefilename="site-$site-".date("dmy").".tar.gz";
  $archivefilename="site-$site-".date("dmy").".zip";

#  $dirs=$sqlonly ? "" : "--exclude lodel/sources/.htaccess --exclude docannexe/fichier/.htaccess --exclude docannexe/image/index.html lodel/sources docannexe";

  $excludes=array("lodel/sources/.htaccess","docannexe/fichier/.htaccess","docannexe/image/index.html","docannexe/index.html","docannexe/image/tmpdir-\*","docannexe/tmp\*");
  $dirs=$sqlonly ? "" : "lodel/sources docannexe";


#  echo "/bin/tar czf $archivetmp -C ".SITEROOT." $dirs -C $tmpdir $outfile\n"; flush();

###  system("/bin/tar czf $archivetmp -C ".SITEROOT." $dirs -C ".($tmpdir[0]=="/" ? $tmpdir : "lodel/admin/".$tmpdir)." $outfile")!==FALSE or die ("ERROR: execution of tar command failed");

#  echo $zipcmd;
  if ($zipcmd && $zipcmd!="pclzip") {
    if (!$sqlonly) {
      if (!chdir(SITEROOT)) die ("ERROR: can't chdir in SITEROOT");
      $prefixdir=$tmpdir[0]=="/" ? "" : "lodel/admin/";
      $excludefiles=$excludes ? " -x ".join(" -x ",$excludes) : "";
      system($zipcmd." -q $prefixdir$archivetmp -r $dirs $excludefiles");
      if (!chdir("lodel/admin")) die ("ERROR: can't chdir in lodel/admin");
      system($zipcmd." -q -g $archivetmp -j $tmpdir/$outfile");
    } else {
      system($zipcmd." -q $archivetmp -j $tmpdir/$outfile");
    }
  } else { // pclzip
    require($home."pclzip.lib.php");
    $archive=new PclZip ($archivetmp);
    if (!$sqlonly) {
      // function to exclude files and rename directories
      function preadd($p_event,&$p_header) {
	global $excludes,$tmpdir; // that's bad to depend on globals like that
	$p_header['stored_filename']=preg_replace("/^".preg_quote($tmpdir,"/")."\//","",$p_header['stored_filename']);

	foreach ($excludes as $exclude) {
	  if (preg_match ("/^".str_replace('\\\\\*','.*',preg_quote($exclude,"/"))."$/",$p_header['stored_filename'])) return 0;
	}
	return 1;
      }
      // end of function to exclude files
      $archive->create(array(SITEROOT."lodel/sources",
			     SITEROOT."docannexe",
			     $tmpdir."/".$outfile
			     ),
		       PCLZIP_OPT_REMOVE_PATH,SITEROOT,
		       PCLZIP_CB_PRE_ADD, 'preadd'
		       );
    } else {
      $archive->create($tmpdir."/".$outfile,PCLZIP_OPT_REMOVE_ALL_PATH);
    }
  } // end of pclzip option

  if (!file_exists($archivetmp)) die ("ERROR: the zip command or library does not produce any output");
  @unlink($tmpdir."/".$outfile); // delete the sql file

#  echo $archivetmp;
#  unlock();
#  exit;

#  unlock();
  if (operation($operation,$archivetmp,$archivefilename,$context)) return;

#  $context[archive]=$archive;
#  $context[size]=intval(filesize("upload/$archive")/1024);

#} elseif ($terminer) {
#  // verifie que $terminer n'est pas hacke
#  if (preg_match("/^site-$site-\d+.tar.gz$/",$terminer)) {
#    unlink ("upload/$terminer");
#  }

  header ("location: index.php");
  return;
}


require ($home."view.php");
$view=&getView();
$view->render($context,"backup");


?>
