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
authenticate(LEVEL_ADMIN);
include ($home."func.php");

if (system("tar --version >/dev/null")) die("ERROR: tar command is not available on this server");

$context[importdir]=$importdir;

if ($backup) {
  require_once($home."func.php");

  // il faut locker la base parce que le dump ne doit pas se faire en meme temps que quelqu'un ecrit un fichier.
  // lock les tables
  require($home."backupfunc.php");

#  lock_write_all($currentdb);

  $outfile="site-$site.sql";
  $uselodelprefix=true;
  $tmpdir=tmpdir();
  mysql_dump($currentdb,$GLOBALS[lodelsitetables],$tmpdir."/".$outfile);

  # verifie que le fichier n'est pas vide
  if (filesize($tmpdir."/".$outfile)<=0) die ("ERROR: mysql_dump failed");

  // tar les sites et ajoute la base
  $archivetmp=tempnam($tmpdir,"lodeldump_");
  $archivefilename="site-$site-".date("dmy").".tar.gz";

  $dirs=$sqlonly ? "" : "--exclude lodel/sources/.htaccess --exclude docannexe/fichier/.htaccess --exclude docannexe/image/index.html lodel/sources docannexe";
#  echo "/bin/tar czf $archivetmp -C ".SITEROOT." $dirs -C $tmpdir $outfile\n"; flush();

  system("/bin/tar czf $archivetmp -C ".SITEROOT." $dirs -C ".($tmpdir[0]=="/" ? $tmpdir : "lodel/admin/".$tmpdir)." $outfile")!==FALSE or die ("ERROR: execution of tar command failed");
  if (!file_exists($archivetmp)) die ("ERROR: the tar command does not produce any output");
  @unlink($tmpdir."/".$outfile); // delete the sql file

  unlock();
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


include ($home."calcul-page.php");
calcul_page($context,"backup");


?>
