<?php

// securise l'entree si le fichier unlockedinstall n'existe pas.
if (!file_exists("CACHE/unlockedinstall")) {
  require("lodelconfig.php"); // le lodelconfig.php doit exister 
  // et permettre un acces a une DB valide... 
  // meme si on reconfigure une nouvelle DB ca doit marcher... 
  // on pourrait aussi remettre un fichier unlockedinstall, mais le risque est
  // de ne pas terminer l'install
  include ($home."auth.php");
  authenticate(LEVEL_ADMINLODEL);
}


if (!defined(LODELROOT)) define(LODELROOT,".."); // acces relatif vers la racine de LODEL
$lodelconfig="CACHE/lodelconfig-cfg.php";

//
// choix de la plateforme
// Copie le fichier lodelconfig choisi dans le CACHE
// Verifie qu'on peut ecrire dans le cache
//
$plateformdir=LODELROOT."/lodel/install/plateform";

$have_chmod=function_exists("chmod");

if ($tache=="plateform") {
  $plateform=preg_replace("/[^A-Za-z_-]/","",$plateform);
  $lodelconfigplatform=$plateformdir."/lodelconfig-$plateform.php";
  echo $lodelconfigplatforme;
  if (file_exists($lodelconfigplatform)) {
    // essai de copier ce fichier dans le CACHE
    if (!@copy($lodelconfigplatform,$lodelconfig)) { die ("problème de droits... étrange on a déjà vérifié"); }
    if ($have_chmod) @chmod($lodelconfig,0600); // c'est plus sur, surtout a cause du mot de passe sur la DB qui apparaitra dans ce fichier.
  } else {
    die("le fichier $lodelconfigplatform n'existe pas. Erreur interne.");
  }
}

//
// gestion du home
//

/*
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
    if (!file_exists(LODELROOT."/".$includepath)) {
      if (!@mkdir(LODELROOT."/".$includepath,0750)) {
	$erreur_mkdir=1;
	if (!(@include ("tpl/install-home.html"))) problem_include("install-home.html");
	return;
      }
    }
    // on essai de copier dans le repertoire $includepath
    // cherche les scripts
    $dirname=LODELROOT."/lodel/scripts";
    $dir=opendir($dirname);
    while ($file=readdir($dir)) {
      $srcfile=$dirname."/".$file;
      $destfile=LODELROOT."/$includepath/$file";
      if (!is_file($srcfile) || preg_match("/~$/",$srcfile)) continue;
      if (!@copy ($srcfile,$destfile)) {
	$erreur_copyscripts=1;
	if (!(@include ("tpl/install-home.html"))) problem_include("install-home.html");
	return; }
      if ($have_chmod) @chmod($destfile,0640);
    }
    // normalement c'est ok, mais reverfie quand meme.
    if (file_exists("$dirname/func.php")) {
      $erreur_includeincorrecte=1; // si plus loin ca plante ca peut venir du fait que l'include est incorrecte
    } else {
      $erreur_copyscripts=1;
    }
  }
}
*/

//
// gestion de mysql. Connexion mysql uniquement.
//

if ($tache=="mysql") {
  maj_lodelconfig(array("dbusername"=>$newdbusername,
			"dbpasswd"=>$newdbpasswd,
			"dbhost"=>$newdbhost));
}

//
// gestion de la database
//

if ($tache=="database") {
  $newsingledatabase=$newsingledatabase ? "on" : "";
  maj_lodelconfig(array("database"=>$newdatabase ? $newdatabase : $createdatabase,
			"singledatabase"=>$newsingledatabase,
			"tableprefix"=>$newtableprefix));
  if (!$newdatabase) { // il faut creer la database
    @include($lodelconfig); // insere lodelconfig, normalement pas de probleme
    @mysql_connect($dbhost,$dbusername,$dbpasswd); // connect
    if (!@mysql_query("CREATE DATABASE $createdatabase")) {
      $erreur_createdatabase=1;
      if (!(@include ("tpl/install-database.html"))) problem_include("install-database.html");
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

    if (!@mysql_query("REPLACE INTO $GLOBAL[tp]users (username,passwd,nom,courriel,privilege) VALUES ('$adminusername','$pass','','',128)")) {
      $pass="";  // enleve de la memoire
      $erreur_create=1;
      if (!(@include ("tpl/install-admin.html"))) problem_include("install-admin.html");
      return;
    }
    $pass=""; // enleve de la memoire
}

$protecteddir=array("lodel",
		    "lodeladmin/CACHE",
		    "lodeladmin/tpl");

if ($tache=="htaccess") {
  if ($verify || $write) maj_lodelconfig("htaccess","on");
  if ($nohtaccess) maj_lodelconfig("htaccess","off");
  if ($write) {
    foreach ($protecteddir as $dir) {
      if (file_exists(LODELROOT."/".$dir) && !file_exists(LODELROOT."/".$dir."/.htaccess")) {
	$file=@fopen(LODELROOT."/".$dir."/.htaccess","w");
	if (!$file) {
	  $erreur_htaccesswrite=1;
	} else {
	  fputs($file,"deny from all\n");
	  fclose($file);
	}
      }
    }
  }
}


if ($tache=="options") {
  maj_lodelconfig(array("extensionscripts"=>$extensionscripts,
			"usesymlink"=>$usesymlink));
}


if (!$tache) {
  if (!(@include ("tpl/install-bienvenue.html"))) problem_include ("install-bienvenue.html");
  return;
}

/////////////////////////////////////////////////////////////////
//                              TESTS                          //
/////////////////////////////////////////////////////////////////


//
// Vérifie les droits sur les fichiers, (verifie juste les droits d'apache, pas les droits des autres users, et verifie les droits minimum, pas de verification de la securite) dans la zone admin
//

if (function_exists("is_readable") && function_exists("is_writable") && function_exists("is_executable")) {
  // les fonctions de tests existent, donc on peut faire des tests sur les droits
  $files=array("lodeladmin/CACHE"=>7,
	       "lodeladmin/tpl"=>5,
	       "lodel"=>5,
	       "lodel/install"=>5,
	       "lodel/install/plateform"=>5,
	       "lodel/scripts"=>5,
	       "lodel/src"=>5,
	       "lodeladmin/images"=>5);
	       
  foreach ($files as $file => $mode) {
    do { // block de control
      if ((mode(LODELROOT."/".$file) & $mode)==$mode) break;
      // essaie de chmoder
      if ($have_chmod && @chmod ($file) && (mode($file) & $mode)==$mode) break;
      if (!$entete) { probleme_droits_debut(); $entete=1; }
      probleme_droits($file,$mode);
    } while (0);
  }
  if ($entete) { probleme_droits_fin(); return; }
} // sinon on suppose que le serveur a les bons droits... hum


// include: lodelconfig
//
// essai de trouver une configuration
//
if (@include ($lodelconfig)) {
  // ok c'est bon...
} else {
  // demander une plateforme pour l'install
  if (!(@include ("tpl/install-plateform.html"))) problem_include ("install-plateform.html");
  return;
}

//
// essaie d'etablir si on accede au script func.php
//
if ((@include($home."func.php"))!=568) { // on accede au fichier func.php
  // il faut determiner si on fonctionne avec un $home ou si on fonctionne avec un include automatique.
  // essaie de deviner le repertoire absolu
  if (!$pathroot && function_exists("realpath")) {
    $pathroot=@realpath(LODELROOT);
    if ($pathroot) $erreur_guess=1;
  }
  if (!(@include ("tpl/install-home.html"))) problem_include("install-home.html");
  return;
}

//
// essaie la connection a la base de donnée
//

if (!$dbusername && !$dbhost) {
  if (!(@include ("tpl/install-mysql.html"))) problem_include("install-mysql.html");
  return;
} elseif (!@mysql_connect($dbhost,$dbusername,$dbpasswd)) { // tente une connexion
  $erreur_connect=1;
  if (!(@include ("tpl/install-mysql.html"))) problem_include("install-mysql.html");
  return;
}

// on cherche si on a une database

if (!$database) {
  // cherche les databases
  if (!($result=@mysql_query("SHOW DATABASES"))) { // probleme ?
    $erreur_connect=1;
    if (!(@include ("tpl/install-mysql.html"))) problem_include("install-mysql.html");
    return;
  } else { // ok, on a les databases, on demande la database principale
    if (!(@include ("tpl/install-database.html"))) problem_include("install-database.html");
    return;
  }
} 

$sitesexistsrequest="SELECT id,statut,nom FROM $GLOBALS[tableprefix]sites LIMIT 1";

if (!@mysql_select_db($database)) { // ok, database est defini, on tente la connection
  $erreur_usedatabase=1;
  if (!(@include ("tpl/install-database.html"))) problem_include("install-database.html");
  return;
} elseif (!@mysql_query($sitesexistsrequest)) {   // regarde si la table sites exists ?
  // non, alors on cree les tables

  // il faudrait tester ici que les tables sur la database sont bien les memes que celles dans le fichier
  // les IF NOT EXISTS sont necessaires dans le fichier init.sql sinon ca va produire une erreur.

  if ($erreur_createtables=mysql_query_file(LODELROOT."/lodel/install/init.sql")) {
    // mince, ca marche pas... bon on detruit la table sites si elle existe pour pouvoir revenir ici
    if (@mysql_query($sitesexistsrequest)) {
      if (!@mysql_query("DROP TABLE IF EXISTS $GLOBALS[tableprefix]sites")) { // ok, on n'arrive vraiment a rien faire
	$erreur_createtables.="<br /><br />La commande DROP TABLE IF EXISTS $GLOBALS[tableprefix]sites n'a pas pu être executée. On ne peut vraiment rien faire !";
      }
    }
    if (!(@include ("tpl/install-database.html"))) problem_include("install-database.html");
    return;
  }
}

//
// Vérifie qu'il y a un administrateur Lodel, sinon demande la creation
//

$result=mysql_query("SELECT id FROM $GLOBALS[tableprefix]users LIMIT 1") or die (mysql_error());
if (!mysql_num_rows($result)) { // il faut demander la creation d'un admin
      if (!(@include ("tpl/install-admin.html"))) problem_include("install-admin.html");
  return;
}

//
// Vérifie la présence des htaccess
//

// $protecteddir est defini plus haut dans le fichier
if ($htaccess!="non") {
  $erreur_htaccess=array();
  foreach ($protecteddir as $dir) {
    if (file_exists($dir) && !file_exists($dir."/.htaccess")) array_push($erreur_htaccess,$dir);
  }
  if ($erreur_htaccess) {
    if (!(@include("tpl/install-htaccess.html"))) probleme_include("install-htaccess.html");
    return;
  }
}

//
// Demander des options generales
//

if (!$extensionscripts || !$usesymlink) {
  if (!(@include("tpl/install-options.html"))) probleme_include("install-options.html");
  return;
}


//
// Vérifie maintenant que les lodelconfig sont les meme que celui qu'on vient de produire
//

$textlc=join('',file($lodelconfig));

// les deux fichiers sont différents, il faut copier le fichier lodelconfig.php
$dirs=array(".","lodel","lodel/admin");
$sitedir=array(".","lodel","lodel/edition","lodel/admin");

// cherche les sites qui existent deja et cree le tableau $dirs
$result=mysql_query("SELECT rep FROM $GLOBALS[tableprefix]sites WHERE statut>0");
while ($row=mysql_fetch_row($result)) {
  foreach ($sitedir as $dir) { array_push($dirs,$row[0]."/".$dir); }
}
// ok, on a tout, on lance la copie
$erreur_lodelconfigdir=array();
$have_is_link=function_exists("is_link"); // fonction is_link existe ?
foreach ($dirs as $dir) {
  $file=$dir."/lodelconfig.php";
  if (file_exists(LODELROOT."/".$file) && $have_is_link && is_link(LODELROOT."/".$file)) continue; // inutile de copie sur les links
  if (file_exists(LODELROOT."/".$file) && $textlc==join('',file(LODELROOT."/".$file))) continue; // verifie si le lodelconfig.php existe et s'il est identique
  if (@copy($lodelconfig,LODELROOT."/".$file)) {
    if ($have_chmod) @chmod(LODELROOT."/".$file,0600);
  } else { // erreur, on le note
    array_push($erreur_lodelconfigdir,$dir);
  }  
}

if ($erreur_lodelconfigdir) {
  include ("tpl/install-lodelconfig.html");
  return;
}

//
// ok, c'est fini, on a plus qu'a bloquer l'install
//

if (file_exists("CACHE/unlockedinstall") && !@unlink("CACHE/unlockedinstall")) {
  die("Etrange, on peut pas effacer ce fichier, alors qu'on a les droits d'écriture sur le repertoire lodel/admin/CACHE. N'est-ce pas ?");
}

if (!(@include("tpl/install-fin.html"))) probleme_include("install-fin.html");

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
      array_push($search,"/^(\s*\\\$$v\s*=\s*)\".*?\"/m");
      array_push($rpl,'\\1"'.$val.'"');
    }
  } else {
      if (!preg_match("/^\s*\\\$$var\s*=\s*\".*?\"/m",$text)) {	die ("la variable \$$var est introuvable dans le fichier de config.");      }
      array_push($search,"/^(\s*\\\$$var\s*=\s*)\".*?\"/m");
      array_push($rpl,'\\1"'.$val.'"');
  }
  $newtext=preg_replace($search,$rpl,$text);
  if ($newtext==$text) return;
  // ecrit le fichier
  if (!(unlink($lodelconfig)) ) die ("Ne peut pas supprimer $lodelconfig. Erreur interne.");
   return ($f=fopen($lodelconfig,"w")) && fputs($f,$newtext) && fclose($f) && $have_chmod && chmod ($lodelconfig,0640);
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

function probleme_include($filename)

{
?>
<html>
<body>
<b>Impossible d'accéder au fichier <?php echo $filename; ?></b><br />
Vérifiez que le répertoire tpl ainsi que le fichier tpl/<?php echo $filename; ?> existent et sont accessibles par le serveur web<br>
<br />
Notez que pour assurer une sécurité maximale (mais jamais totale) de LODEL et du serveur, il convient de gérer les droits d'acces de tous les fichiers par vous même.<br>

LODEL ne vient avec AUCUNE GARANTIE d'aucune sorte. Lisez le fichier LICENSE s'il vous plait.
</body>
</html>
<?php
  die();
}

function mode($filename)

{
  return is_readable($filename)*4+is_writable($filename)*2+is_executable($filename);
}

function probleme_droits_debut()

{
?>
<hmlt>
<head>
      <title>Installation de LODEL</title>
</head>
<body bgcolor="#FFFFFF"  text="Black" vlink="black" link="black" alink="blue" onLoad="" marginwidth="0" marginheight="0" rightmargin="0" leftmargin="0" topmargin="0" bottommargin="0"> 

<h1>Installation de LODEL</h1>


<p align="center">
<table width="600">
<tr>
  <td>
   <p align="center">Accès aux répertoires.</p>

   <b>Le serveur n'a pas accès au(x) répertoire(s) suivant(s). Vérifier que ce(s) répertoire(s) existent et que le serveur web (l'utilisateur nobody ou apache) puisse y accéder en lecture et si mentioner ci-dessosu y écrire</b>
<p></p>
<ul>
<?php }

function probleme_droits($file,$mode)

{
 echo "<li>Répertoire: $file<br> droits requis: lecture, exécution"; if ($mode & 4 == 4) echo ", <u>écriture</u>";
 echo "<p></p>\n";
}

function probleme_droits_fin()

{
?>
</ul>
<p></p>
Notez que pour assurer une sécurité maximale de LODEL et du serveur, il convient de gérer les droits d'acces de tous les fichiers par vous même.<br />
LODEL est distribué SANS AUCUNE GARANTIE.
  </td>
</table>
</body>
<?php }

?>
