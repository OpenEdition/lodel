<?

require("lodelconfig.php");
include ($home."auth.php");
authenticate(LEVEL_SUPERADMIN);

#ifndef LODELLIGHT
if (!$rep) {
  echo "Preciser une revue";
  return;
}
#endif

$revue=$rep;
include ($home."connect.php");


#ifndef LODELLIGHT
$sqlfile=str_replace("_PREFIXTABLE_","",
		     join('',file("../install/init-revue.sql")));
#else
#// met init.sql et init-revue.sql
#$sqlfile=str_replace("_PREFIXTABLE_","$GLOBALS[tableprefix]",
#				   join('',file("init.sql"))
#				   .join('',file("init-revue.sql")));
#endif

if (!$sqlfile) return;
$sql=preg_split ("/;/",preg_replace("/#.*?$/m","",$sqlfile));
if (!$sql) return;

?>
<H1>Initialisation SQL</H1>

<?

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

<?

// essaie d'ecrire dans differents repertoires.
#ifndef LODELLIGHT

$dirs=array("CACHE","lodel/admin/CACHE","lodel/edition/CACHE","lodel/txt","lodel/rtf");

//foreach ($dirs as $dir) {
//  $file="../../$rep/$dir/.htaccess";
//  if (file_exists($file)) @unlink($file);
//  $f=@fopen ($file,"w");
//  if (!$f) {
//    print ("<font COLOR=red>Impossible d'ecrire dans le repertoire $dir. Faire: chmod 770 $dir ou chmod 777 $dir</font><br>");
//  } else {
//    fputs($f,"deny from all\n");
//    fclose ($f);
//  }
//}

// verifie les htaccess

$dirs=array("tpl");
foreach ($dirs as $dir) {
  $file="../$rep/$dir/.htaccess";
  if (!file_exists($file)) {
    print ("<font COLOR=red>Un .htaccess devrait etre dans le repertoire $dir. Faite la copie à la main</font><br>");
  }
}

#endif

?>
