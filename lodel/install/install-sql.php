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

require("lodelconfig.php");
$rep="";
include ($home."connect.php");

$sql=preg_split ("/;/",join('',file("init.sql")));

#ifndef LODELLIGHT
$sql=str_replace("_PREFIXTABLE_","",$sql);
#endif

?>
<H1>Initialisation SQL</H1>

<?php 

foreach ($sql as $cmd) {
  $cmd=trim(preg_replace ("/^#.*?$/m","",$cmd));
  if ($cmd) {
    print "$cmd<BR>\n";
    mysql_query($cmd) or print ("<font COLOR=red>".mysql_error()."</font><br>");
    print "<BR>\n";
  }
}

?>

<H1>Initialisation des repertoires</H1>

<?php
# essaie d'ecrire dans differents repertoires.

$dirs=array("CACHE","admin/CACHE");
foreach ($dirs as $dir) {
  $file="../$dir/ess.tmp";
  $f=@fopen ($file,"w");
  if (!$f) {
    print ("<font COLOR=red>Impossible d'ecrire dans le repertoire $dir. Faire: chmod 770 $dir ou chmod 777 $dir</font><br>");
  } else {
    fclose ($f);
    unlink ($file);
  }
}

?>

