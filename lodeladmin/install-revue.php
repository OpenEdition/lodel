<?

require("lodelconfig.php");
include ($home."auth.php");
authenticate(LEVEL_SUPERADMIN);



if ($create) {
  extract_post();

  $err=0;
  do { // bloc de controle
    if (!$rep) $err=$context[erreur_nomrepertoireabsent]=1; // 
    if (preg_match("/\W/",$rep)) $err=$context[erreur_nomrepertoire]=1;
    if (!$revue)  $err=$context[erreur_nomrevue]=1;
    
    if (!$err) break;
    require ($home."calcul-page.php");
    calcul_page($context,"install-revue");
    return;
  } while (0);
  // ok, on cree la revue dans la base principale
  include ($home."connect.php");
  mysql_select_db($GLOBALS[database]);
  mysql_query("INSERT ");


    } else {

    }



  {
  $revue=$rep;
  include ($home."connect.php");

  if (!file_exists("../install/init-revue.sql")) die ("impossible de faire l'installation, le fichier init-revue.sql est absent");

  $sqlfile=str_replace("_PREFIXTABLE_",$tableprefix,
		       join('',file("../install/init-revue.sql")));

  $sqlcmds=preg_split ("/;/",preg_replace("/#.*?$/m","",$sqlfile));
  if (!$sqlcmds) die("le fichier init-revue.sql ne contient pas de commande. Probleme!");

  $erreur_sqls=array();
  foreach ($sqlcmds as $cmd) {
    $cmd=trim($cmd);
    if ($cmd && !mysql_query($cmd)) array_push($erreur_sqls,$cmd,mysql_error());
  }
  if ($erreur_sqls) {
    require ($home."calcul-page.php");
    calcul_page($context,"install-revue-errsql");
    return;
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

//
// creation de l'administrateur de la revue
//

?>
