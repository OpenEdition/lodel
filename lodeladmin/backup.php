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
include ($home."auth.php");
authenticate(LEVEL_ADMINLODEL);
require($home."func.php");
require($home."backupfunc.php");

$context[importdir]=$importdir;

if ($backup) {
  // il faut locker la base parce que le dump ne doit pas se faire en meme temps que quelqu'un ecrit un fichier.

  $dirtotar=array();

  $dirlocked=tempnam("/tmp","lodeldump_").".dir"; // this allow to be sure to have a unique dir.
  mkdir($dirlocked,0700);
  $outfile="lodel.sql";
  $fh=fopen($dirlocked."/".$outfile,"w");
  if (!$fh) die ("ERROR: unable to open a temporary file in write mode");
  // sauve la base generale

  if (fputs($fh,"DROP DATABASE $database;\nCREATE DATABASE $database;USE $database;\n")===FALSE) die("ERROR: unable to write in the temporary file");

  lock_write_all($database);
  mysql_dump($database,$GLOBALS[lodelbasetables],"",TRUE,$fh);
  unlock();

  if (!$singledatabase) {
    // cherche les sites
    mysql_select_db($database);
    $result=mysql_query("SELECT rep FROM $GLOBALS[tp]sites") or die (mysql_error());
    while (list($rep)=mysql_fetch_row($result)) {
      $dbname=$database."_".$rep;
#      echo $dbname,"<br>\n"; flush();
      if (fputs($fh,"DROP DATABASE $dbname;\nCREATE DATABASE $dbname;USE $dbname;\n")===FALSE) die("ERROR: unable to write in the temporary file");
      if (!mysql_select_db($dbname)) die("ERROR: the database $dbname does not exist or is not reactable. This is inconsistent with the sites table of $database. Please solve the problem before backing up.<br/>MySQL replied: ".mysql_error());
#      echo $dbname," try to locked<br>\n"; flush();
      lock_write_all($dbname);
#      echo $dbname," locked, let's dump<br>\n"; flush();
      mysql_dump($dbname,$GLOBALS[lodelsitetables],"",TRUE,$fh);
      unlock();

      if (!$sqlonly) array_push($dirtotar,"$rep/lodel/sources","$rep/docannexe");
#      echo $dbname," finish<br>"; flush();
    }
  } else {
    lock_write_all($database);
    mysql_dump($database,$GLOBALS[lodelsitetables],"",TRUE,$fh);
    unlock();
  }
  fclose($fh);

  // tar les sites et ajoute la base
  $archivetmp=tempnam("/tmp","lodeldump_");
  $archivefilename="lodel-".date("dmy").".tar.gz";

  chdir (LODELROOT);
#  echo "tar czf $archivetmp ".join(" ",$dirtotar)." -C $dirlocked $outfile\n"; flush();
  system("tar czf $archivetmp ".join(" ",$dirtotar)." -C $dirlocked $outfile")!==FALSE or die ("impossible d'executer tar");


  unlink($dirlocked."/".$outfile);
  rmdir($dirlocked);

  chdir ("lodeladmin");

  if (operation($operation,$archivetmp,$archivefilename,&$context)) return;
}

include ($home."calcul-page.php");
calcul_page($context,"backup");

#function dumpdb ($dbname) 
#
#{
#  global $dbhost,$dbusername,$dbpasswd,$mysqldir;
#
#  $outfile="$dbname.sql";
#  system("$mysqldir/mysqldump --quick --add-locks --extended-insert --add-drop-table -h $dbhost -u $dbusername -p$dbpasswd --databases $dbname >/tmp/$outfile")!==FALSE or die ("impossible d'executer mysqldump");
#  if (!file_exists("/tmp/$outfile")) die ("erreur dans l'execution de mysqldump");
#  # verifie que le fichier n'est pas vide
#  $result=stat("/tmp/$outfile");
#  if ($result[7]<=0) die ("erreur 2 dans l'execution de mysqldump");
#  
#  return $outfile;
#}

?>
