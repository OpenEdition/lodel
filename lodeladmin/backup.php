<?php

require("lodelconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMINLODEL);
require($home."func.php");
require($home."backupfunc.php");


if ($backup) {
  // il faut locker la base parce que le dump ne doit pas se faire en meme temps que quelqu'un ecrit un fichier.

  $dirtotar=array();

  $fh=fopen(tempnam("/tmp","lodeldump_"),"w");
  if (!$fh) die ("ERROR: unable to open a temporary file in write mode");
  // sauve la base generale

  if (fputs($fh,"DROP DATABASE $database;\nCREATE DATABASE $database;USE $database;\n")===FALSE) die("ERROR: unable to write in the temporary file");

#  lock_write_all($database);
#  mysql_dump($database,"",TRUE,$fh);

  if (!$singledatabase) {
    // cherche les sites
    mysql_select_db($database);
    $result=mysql_query("SELECT rep FROM $GLOBALS[tp]sites") or die (mysql_error());
    while (list($rep)=mysql_fetch_row($result)) {
      $dbname=$database."_".$rep;
      echo $dbname,"<br>\n"; flush();
      if (fputs($fh,"DROP DATABASE $dbname;\nCREATE DATABASE $dbname;USE $dbname;\n")===FALSE) die("ERROR: unable to write in the temporary file");
      if (!mysql_select_db($dbname)) die("ERROR: the database $dbname does not exist or is not reactable. This is inconsistent with the sites table of $database. Please solve the problem before backing up.<br/>MySQL replied: ".mysql_error());
      lock_write_all($dbname);
      echo $dbname," locked<br>\n"; flush();
      mysql_dump($dbname,"",TRUE,$fh);

      array_push($dirtotar,"$rep/lodel/txt","$rep/lodel/rtf","$rep/docannexe");
      echo $dbname," finish<br>"; flush();
    }
  }

  // tar les sites et ajoute la base
  $archivetmp=tempnam("/tmp","lodeldump_");
  $archivefilename="lodel-".date("dmy").".tar.gz";

  chdir ("../..");
  echo "tar czf $archivetmp ".join(" ",$dirtotar)." -C /tmp $outfile\n"; flush();
#  system("tar czf $archivetmp ".join(" ",$dirtotar)." -C /tmp $outfile")!==FALSE or die ("impossible d'executer tar");
  chdir ("lodel/admin");

  unlock();

  download($archivetmp,$archivefilename);

  @unlink($archivetmp);
  return;

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
