<?


require("revueconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_ADMIN);

if ($backup) {
  // il faut locker la base parce que le dump ne doit pas se faire en meme temps que quelqu'un ecrit un fichier.

  $outfile="revue-$revue.sql";
  system("$mysqldir/mysqldump --quick --add-locks --extended-insert --add-drop-table -h $dbhost -u $dbusername -p$dbpasswd $currentdb >/tmp/$outfile")!==FALSE or die ("impossible d'executer mysqldump");
  if (!file_exists("/tmp/$outfile")) die ("erreur dans l'execution de mysqldump");
  # verifie que le fichier n'est pas vide
  $result=stat("/tmp/$outfile");
  if ($result[7]<=0) die ("erreur 2 dans l'execution de mysqldump");

  // tar les revues et ajoute la base
  $archive="revue-$revue-".date("dmy").".tar.gz";

  chdir ("../..");
  system("tar czf lodel/admin/upload/$archive lodel/txt lodel/rtf docannexe  -C /tmp $outfile")!==FALSE or die ("impossible d'executer tar");
  chdir ("lodel/admin");

  $context[archive]=$archive;
  $context[size]=intval(filesize("upload/$archive")/1024);
} elseif ($terminer) {
  // verifie que $terminer n'est pas hacke
  if (preg_match("/^revue-$revue-\d+.tar.gz$/",$terminer)) {
    unlink ("upload/$terminer");
  }
  header ("location: index.php");
  return;
}


include ("$home/calcul-page.php");
calcul_page($context,"backup");


?>
