<?php

require("lodelconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMINLODEL);


if ($backup) {
  // il faut locker la base parce que le dump ne doit pas se faire en meme temps que quelqu'un ecrit un fichier.

  // sauve la base generale
  $outfiles=dumpdb ($database);

  include ($home."connect.php");
  // cherche les sites
  $result=mysql_db_query($database,"SELECT rep FROM sites") or die (mysql_error());
  while ($row=mysql_fetch_row($result)) {
    $outfiles.=" ".dumpdb ($database."_".$row[0]);
  }

  // tar les sites et ajoute la base
  $archive="lodel-".date("dmy").".tar.gz";

  chdir ("../..");
  system("tar czf lodel/admin/upload/$archive */lodel/txt */lodel/rtf */docannexe -C /tmp $outfiles")!==FALSE or die ("impossible d'executer tar");
  chdir ("lodel/admin");

  $context[archive]=$archive;
  $context[size]=intval(filesize("upload/$archive")/1024);
} elseif ($terminer) {
  // verifie que $terminer n'est pas hacke
  if (preg_match("/^lodel-\d+.tar.gz$/",$terminer)) {
    unlink ("upload/$terminer");
  }
  header ("location: index.php");
  return;
}


include ($home."calcul-page.php");
calcul_page($context,"backup");

function dumpdb ($dbname) 

{
  global $dbhost,$dbusername,$dbpasswd,$mysqldir;

  $outfile="$dbname.sql";
  system("$mysqldir/mysqldump --quick --add-locks --extended-insert --add-drop-table -h $dbhost -u $dbusername -p$dbpasswd --databases $dbname >/tmp/$outfile")!==FALSE or die ("impossible d'executer mysqldump");
  if (!file_exists("/tmp/$outfile")) die ("erreur dans l'execution de mysqldump");
  # verifie que le fichier n'est pas vide
  $result=stat("/tmp/$outfile");
  if ($result[7]<=0) die ("erreur 2 dans l'execution de mysqldump");
  
  return $outfile;
}

?>
