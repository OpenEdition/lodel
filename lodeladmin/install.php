<?php


//
// choix de la plateforme
// Copie le fichier lodelconfig choisi dans le CACHE
// Verifie qu'on peut ecrire dans le cache
//

$plateformdir="../install/plateform";
  $lodelconfig="CACHE/lodelconfig-tmp.php";

if ($tache=="plateform") {
  $plateform=preg_replace("/[^A-Za-z_-]/","",$plateform);
  $lodelconfigplatform=$plateformdir."/lodelconfig-$plateform.php";
  echo $lodelconfigplatforme;
  if (file_exists($lodelconfigplatform)) {
    // essai de copier ce fichier dans le CACHE
    do { // do de controle
      if (@copy($lodelconfigplatform,$lodelconfig)) break; // essai de copier
      if (function_exists("chmod") && @chmod("CACHE",0750)
	  && @copy($lodelconfigplatform,$lodelconfig)) break; // sinon essai de chmode puis de copier.      
      // rien ne marche, alors on demande de changer les droits
      include("tpl/install-chmod-CACHE.php");
      return;
    } while (0);
    if (function_exists("chmod")) @chmod($lodelconfig,0640);
  } else {
    die("le fichier $lodelconfigplatform n'existe pas. Erreur interne.");
  }
}

//
// gestion du home
//

if ($tache=="home") {
  if ($withautoinclude=="non") {
    // changer le $pathroot et le $home
    maj_lodelconfig(array("pathroot" => $newpathroot,
			  "home" => "\$pathroot/lodel/scripts/",
			  "includepath"=>""));
    // si ca marche pas on aura l'erreur suivante
    $erreur_homeinaccessible=1;
  } else {
    maj_lodelconfig(array("pathroot"=>"",
			  "home"=>"",
			  "includepath"=>$newincludepath));
    $includepath=$newincludepath;
    // on essaie de creer le repertoire include
    if (!file_exists("../../".$includepath)) {
      if (!@mkdir("../../".$includepath,0750)) {
	$erreur_mkdir=1;      include ("tpl/install-home.html");      return;
      }
    }
    // on essai de copier dans le repertoire $includepath
    // cherche les scripts
    $dirname="../scripts";
    $dir=opendir($dirname);
    while ($file=readdir($dir)) {
      $srcfile=$dirname."/".$file;
      $destfile="../../$includepath/$file";
      if (!is_file($srcfile) || preg_match("/~$/",$srcfile)) continue;
      if (!@copy ($srcfile,$destfile)) {    $erreur_copyscripts=1;      include ("tpl/install-home.html");      return; }
      @chmod($destfile,0640);
    }
    $erreur_includeincorrecte=1; // si plus loin ca plante ca peut venir du fait que l'include est incorrecte
  }
}

//
// gestion de mysql. Connexion mysql uniquement.
//

if ($tache=="mysql") {
  maj_lodelconfig(array("dbusername"=>$newdbusername,
			"dbpasswd"=>$newdppasswd,
			"dbhost"=>$newdbhost));
}

//
// gestion de la database
//

if ($tache=="database") {
  $newmultidatabases=$newmultidatabases ? "oui" : "";
  maj_lodelconfig(array("database"=>$newdatabase ? $newdatabase : $createdatabase,
			"multidatabases"=>$newmultidatabases,
			"tableprefix"=>$newtableprefix));
  if (!$newdatabase) { // il faut creer la database
    @include($lodelconfig); // insere lodelconfig, normalement pas de probleme
    @mysql_connect($dbhost,$dbusername,$dbpasswd); // connect
    if (!@mysql_query("CREATE DATABASE $createdatabase")) {
      $erreur_createdatabase=1;
      include ("tpl/install-database.html");
      return;
    }
  }
}

if ($tache=="admin") {
    @include($lodelconfig); // insere lodelconfig, normalement pas de probleme
    @mysql_connect($dbhost,$dbusername,$dbpasswd); // connect
    @mysql_select_db($database); // selectionne la database
    $adminusername=addslashes($adminusername);
    $pass=md5($adminpasswd.$adminusername);

    if (!@mysql_query("INSERT INTO $GLOBAL[prefixtable]users (username,passwd,nom,email,privilege) VALUES ('$adminusername','$pass','','',128)")) {
      $pass="";  // enleve de la memoire
      $erreur_create=1;
      include("tpl/install-admin.html");
      return;
    }
    $pass=""; // enleve de la memoire
}

/////////////////////////////////////////////////////////////////
//                              TESTS                          //
/////////////////////////////////////////////////////////////////

// include: lodelconfig
//
// essai de trouver une configuration
//
if (@include ($lodelconfig)) {

} else {
  // demander une plateforme pour l'install
  include ("tpl/install-plateform.html");
  return;
}

//
// essaie d'etablir si on accede au script func.php
//
if ((@include($home."func.php"))!=568) { // on accede au fichier func.php
  // il faut determiner si on fonctionne avec un $home ou si on fonctionne avec un include automatique.
  include ("tpl/install-home.html");
  return;
}

//
// essaie la connection a la base de donnée
//

if (!$dbusername && !$dbhost) {
  include ("tpl/install-mysql.html");
  return;
} elseif (!@mysql_connect($dbhost,$dbusername,$dbpasswd)) { // tente une connexion
  $erreur_connect=1;
  include ("tpl/install-mysql.html");
  return;
}

// on cherche si on a une database

if (!$database) {
  // cherche les databases    
  if (!($result=@mysql_query("SHOW DATABASES"))) { // probleme ?
    $erreur_connect=1;
    include ("tpl/install-mysql.html");
    return;
  } else { // ok, on a les databases, on demande la database principale
    include ("tpl/install-database.html");
    return;
  }
} elseif (!@mysql_select_db($database)) { // ok, database est defini, on tente la connection
  $erreur_usedatabase=1;
  include ("tpl/install-database.html");
  return;
} else { // on cree les tables si necessaire
  // il faudrait tester ici que les tables sur la database sont bien les memes que celles dans le fichier
  // les IF NOT EXISTS sont necessaires dans le fichier init.sql sinon ca va produire une erreur.
  if ($erreur_createtables=mysql_query_file("../install/init.sql")) {
    include ("tpl/install-database.html");
    return;
  }
}

//
// Vérifie qu'il y a un super administrateur, sinon demande la creation
//

$result=mysql_query("SELECT id FROM users LIMIT 1") or die (mysql_error());
if (!mysql_num_rows($result)) { // il faut demander la creation d'un admin
  include("tpl/install-admin.html");
  return;
}

//
//
//

echo "Félicitations !";


/////////////////////////////////////////////////////////////////
//                           FONCTIONS                         //
/////////////////////////////////////////////////////////////////


function maj_lodelconfig($var,$val=-1)

{
  global $lodelconfig;
  // lit le fichier
  $text=join("",file($lodelconfig));
  $search=array(); $rpl=array();

  if (is_array($var)) {
    foreach ($var as $v =>$val) {
      if (!preg_match("/^\s*\\\$$v\s*=\s*\".*?\"/m",$text)) {	die ("la variable \$$v est introuvable dans le fichier de config.");      }
      array_push($search,"/^(\s*\\\$$v\s*=\s*\").*?\"/m");
      array_push($rpl,"\\1$val\"");
    }
  } else {
      array_push($search,"/^(\s*\\\$$var\s*=\s*\").*?\"/m");
      array_push($rpl,"\\1$val\"");
  }
  $newtext=preg_replace($search,$rpl,$text);
  if ($newtext==$text) return;
  // ecrit le fichier
  if (!(unlink($lodelconfig)) ) die ("Ne peut pas supprimer $lodelconfig.");
   return ($f=fopen($lodelconfig,"w")) && fputs($f,$newtext) && fclose($f) && chmod ($lodelconfig,0640);
}


function mysql_query_file($filename) 

{
  $sqlfile=str_replace("_PREFIXTABLE_","$GLOBALS[tableprefix]",
		       join('',file($filename)));
  if (!$sqlfile) return;
  $sql=preg_split ("/;/",preg_replace("/#.*?$/m","",$sqlfile));
  if (!$sql) return;

  foreach ($sql as $cmd) {
    $cmd=trim(preg_replace ("/^#.*?$/m","",$cmd));
    if ($cmd) {
      if (!mysql_query($cmd)) { 
	$err.="$cmd <font COLOR=red>".mysql_error()."</font><br>";
      }
    }
  }
  return $err;
}

?>
