<?php

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN);
include ($home."func.php");

if ($backup) {
  require_once($home."func.php");

  // il faut locker la base parce que le dump ne doit pas se faire en meme temps que quelqu'un ecrit un fichier.
  // lock les tables
  require($home."backupfunc.php");

  lock_write_all($currentdb);

  $outfile="site-$site.sql";
  mysql_dump($currentdb,"/tmp/".$outfile);

  #if (!file_exists("/tmp/$outfile")) die ("erreur dans l'execution de mysqldump");
  # verifie que le fichier n'est pas vide
  if (filesize("/tmp/$outfile")<=0) die ("ERROR: mysql_dump failed");

  // tar les sites et ajoute la base
  $archivetmp=tempnam("/tmp","lodeldump_");
  $archivefilename="site-$site-".date("dmy").".tar.gz";

  chdir ("../..");
  system("/bin/tar czf $archivetmp lodel/txt lodel/rtf docannexe  -C /tmp $outfile")!==FALSE or die ("ERROR: execution of tar command failed");

  if (!file_exists($archivetmp)) die ("ERROR: the tar command does not produce any output");
  chdir ("lodel/admin");

  unlock();

  download($archivetmp,$archivefilename);
  @unlink($archivetmp);

  return;

#  $context[archive]=$archive;
#  $context[size]=intval(filesize("upload/$archive")/1024);

#} elseif ($terminer) {
#  // verifie que $terminer n'est pas hacke
#  if (preg_match("/^site-$site-\d+.tar.gz$/",$terminer)) {
#    unlink ("upload/$terminer");
#  }
#  header ("location: index.php");
#  return;
}


include ($home."calcul-page.php");
calcul_page($context,"backup");


?>
