<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003-2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
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


// securise l'entree

if (file_exists("lodelconfig.php") && file_exists("../lodelconfig.php")) {
  if (!(@file("../lodelconfig.php"))) problem("reading_lodelconfig");
  require("lodelconfig.php");
  // le lodelconfig.php doit exister 
  // et permettre un acces a une DB valide... 
  // meme si on reconfigure une nouvelle DB ca doit marcher... 

  if ($tache=="lodelconfig") $GLOBALS[REQUEST_URI].="?tache=lodelconfig";

  require($home."auth.php");
  // test whether we access to a DB and whether the table users exists or not and whether it is empty or not.
  if (@mysql_connect($dbhost,$dbusername,$dbpasswd)) {
    $result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]users LIMIT 1");
    if ($result && mysql_num_rows($result)>0) {
      authenticate(LEVEL_ADMINLODEL);
    } else {
      // no authentification required. The table users does not exists or is empty!
    }
  } else {
    // well, no access to the DB but a lodelconfig ?
    // ask for erasing the lodelconfig.php ?
    problem("lodelconfig_but_no_database");
  }

  if ($_REQUEST[installoption]) $installoption=$_REQUEST[installoption]; // overwrite the lodelconfig
}

// import Posted variables for the Register Off case.
// this should be nicely/safely integrated inside the code, but that's
// a usefull little hack at the moment
if (!((bool) ini_get("register_globals"))) { // 
  extract($_REQUEST,EXTR_SKIP);
}

header("Content-type: text/html; charset=iso-8859-1");

//
// Version of lodel to be installed.
//

$versioninstall="0.7";
$versionsuffix="-$versioninstall";   # versioning

if (!defined("LODELROOT")) define("LODELROOT","../"); // acces relatif vers la racine de LODEL. Il faut un / a la fin.
$lodelconfig="CACHE/lodelconfig-cfg.php";


//
// option
//
if ($erase_and_option1) { $option1=true; @unlink($lodelconfig); }
if ($erase_and_option2) { $option2=true; @unlink($lodelconfig); }

if ($option1) $installoption="1";
if ($option2) $installoption="2";


//
// Test the PHP version
//
preg_match("/^\d+\.\d+/",phpversion(),$result);
if (doubleval($result[0]<4.1)) {
  probleme_version();
  exit;
}





//
// choix de la plateforme
// Copie le fichier lodelconfig choisi dans le CACHE
// Verifie qu'on peut ecrire dans le cache
//
$plateformdir=LODELROOT."lodel$versionsuffix/install/plateform";

if ($tache=="plateform") {
  $plateform=preg_replace("/[^A-Za-z_-]/","",$plateform);
  if (!$plateform) $plateform="default";

  $lodelconfigplatform=$plateformdir."/lodelconfig-$plateform.php";
  if (file_exists($lodelconfigplatform)) {
    // essai de copier ce fichier dans le CACHE
    if (!@copy($lodelconfigplatform,$lodelconfig)) { die ("problème de droits... étrange on a déjà vérifié"); }
    if (file_exists(LODELROOT."lodelloader.php")) {
      // the installer has been use, let's chmod safely
      $chmod=decoct(fileperms(LODELROOT."lodel-$versioninstall"));
    } else {
      $chmod=0600;  // c'est plus sur, surtout a cause du mot de passe sur la DB qui apparaitra dans ce fichier.
    }
    @chmod($lodelconfig,$chmod);
    maj_lodelconfig(array("home"=>'$pathroot/lodel'.$versionsuffix.'/scripts/'));    
  } else {
    die("ERROR: $lodelconfigplatform does not exist. Internal error, please report this bug.");
  }
  $arr=array();
  $needoptions=false;
  $arr['installoption']=intval($installoption);

  // guess the urlroot
  $me=$_SERVER['PHP_SELF'];
  if ($me) {
    // enleve moi
    $urlroot=preg_replace("/\/+lodeladmin\/install.php$/","",$me);
    if ($urlroot==$me) die("ERROR: the install.php script is not at the right place, please report this bug.");
    if (LODELROOT!="../") die("ERROR: the lodeladmin directory has been moved, please report this bug.");

    $arr['urlroot']=$urlroot."/";
  }

  // is there a filemask ?

  if ($_REQUEST['filemask']) {
    // passed via the URL
    $arr['filemask']="0".$_REQUEST['filemask'];
  } elseif ($GLOBALS['filemask']) {
    // was in the previous lodelconfig.php
    $arr['filemask']=$GLOBALS['filemask'];
  } else {
    $arr['filemask']="0".decoct(guessfilemask());
  }

  if ($installoption==1) {
    // try to guess the options.
    // use pclzip ?
    if (function_exists("gzopen")) {
      $arr['unzipcmd']=$arr['zipcmd']="pclzip";
    } else {
      $arr['unzipcmd']=$arr['zipcmd']="";
      $needoptions=true;
    }
  }
  if ($installoption==1) $arr['extensionscripts']="php";

  $arr['chooseoptions']=$needoptions && $installoption==1 ? "oui" : "non";
  maj_lodelconfig($arr);
}


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
  if ($continue) {
    $tache="continue";
    // nothing to do
  } elseif ($erasetables) {
    @include($lodelconfig);    // insert the lodelconfig. Should not be a problem.
    @mysql_connect($dbhost,$dbusername,$dbpasswd); // connect
    @mysql_select_db($database); // selectionne la database

    // erase the table of each site
    $result=mysql_query("SELECT rep FROM $GLOBALS[tableprefix]sites") or die (mysql_error());

    if ($singledatabase) {
      // currently singledatabase implies single site ! That's a shame but...
      // Let's destroyed everything in the database with the prefix !
      if (!$tableprefix) {
	// we can't destroy... too dangerous. Should find another solution.
	die("Sans tableprefix les tables ne peuvent pas etre efface en toute securite. Veuillez effacer vous-même les tables de Lodel. Merci.");
      } else {
	// get all table names.
	$result=mysql_list_tables($database);
	while ($row = mysql_fetch_row($result)) {
	  if (preg_match("/^$tableprefix/",$row[0]) && $row[0]!=$tableprefix."users") {
	    // let's drop it
	    mysql_query("DROP TABLE $row[0]");
	  }
	}
      }
    } else {
      die("L'effacement des tables avec plusieurs bases de donnée n'est pas implementé. Veuillez effacer les bases de données vous même. Merci.");
    }
    // erase the main tables below.
  } else { // normal case
    $set=array();

    if (isset($newsingledatabase)) {
      $set['singledatabase']=$newsingledatabase ? "on" : "";
    }
    if (isset($newtableprefix)) {
      $set['tableprefix']=$newtableprefix;
    }

    if ($newdatabase==-1) $newdatabase=$existingdatabase;
    if ($newdatabase==-2) { 
      $newdatabase=$createdatabase;
    } else {
      $createdatabase="";
    }
    $set['database']=$newdatabase;

    maj_lodelconfig($set);

    if ($createdatabase) { // il faut creer la database
      @include($lodelconfig); // insere lodelconfig, normalement pas de probleme
      @mysql_connect($dbhost,$dbusername,$dbpasswd); // connect
      if (!@mysql_query("CREATE DATABASE $createdatabase")) {
	$erreur_createdatabase=1;
	if (!(@include ("tpl/install-database.html"))) problem_include("install-database.html");
	return;
      }
    } else {
      // check whether the database contains already Lodel.

    }
  }
}

if ($tache=="admin") {
    @include($lodelconfig); // insere lodelconfig, normalement pas de probleme
    @mysql_connect($dbhost,$dbusername,$dbpasswd); // connect
    @mysql_select_db($database); // selectionne la database
    $adminusername=addslashes($adminusername);
    $pass=md5($adminpasswd.$adminusername);

    if (!@mysql_query("REPLACE INTO $GLOBALS[tableprefix]users (username,passwd,nom,courriel,privilege) VALUES ('$adminusername','$pass','','',128)")) {
      $pass="";  // enleve de la memoire
      $erreur_create=1;
      if (!(@include ("tpl/install-admin.html"))) problem_include("install-admin.html");
      return;
    }
    // log this user in 
    require($home."loginfunc.php");
    $site="";
    if (check_auth($adminusername,&$pass,&$site)) {
      open_session($adminusername);
    }

    $pass=""; // enleve de la memoire
}

$protecteddir=array("lodel$versionsuffix",
		    "CACHE",
		    "tpl",
		    "lodeladmin/CACHE",
		    "lodeladmin/tpl");

if ($tache=="htaccess") {
  if ($verify || $write) maj_lodelconfig("htaccess","on");
  if ($nohtaccess) maj_lodelconfig("htaccess","off");
  if ($write) {
    foreach ($protecteddir as $dir) {
      if (file_exists(LODELROOT.$dir) && !file_exists(LODELROOT.$dir."/.htaccess")) {
	$file=@fopen(LODELROOT.$dir."/.htaccess","w");
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
#  if (!preg_match("/\/$/",$newurlroot)) $newurlroot.="/";
  $newurlroot.="/"; // ensure their is a / at the end
  $newurlroot=preg_replace("/\/\/+/","/",$newurlroot); // ensure there is no double slashes because it causes problem with the cookies
  $filemask="07".
    (5*($permission[group][read]!="")+2*($permission[group][write]!="")).
    (5*($permission[all][read]!="")+2*($permission[all][write]!=""));

  if ($pclzip=="pclzip") { $newunzipcmd=$newzipcmd="pclzip"; }

  maj_lodelconfig(array("chooseoptions"=>"oui",
			"urlroot"=>$newurlroot,
			"importdir"=>$newimportdir,
			"extensionscripts"=>$newextensionscripts,
			"usesymlink"=>$newusesymlink,
			"filemask"=>$filemask,
			"contactbug"=>$newcontactbug,
			"unzipcmd"=>$newunzipcmd,
			"zipcmd"=>$newzipcmd));
}


if ($tache=="servoo") {
  if ($noservoo) {
    maj_lodelconfig(array("servoourl"=>"off",
			  "servoousername"=>"",
			  "servoopasswd"=>""));
  } elseif (!$skip) {
    maj_lodelconfig(array("servoourl"=>$newservoourl,
			  "servoousername"=>$newservoousername,
			  "servoopasswd"=>$newservoopasswd
			  ));
  }
}


if ($tache=="downloadlodelconfig") {
  header("Content-type: application/force-download");
  header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
  if (ereg('MSIE ([0-9].[0-9]{1,2})', $_SERVER['HTTP_USER_AGENT'], $log_version)) { // from phpMyAdmin
    header('Content-Disposition: inline; filename="lodelconfig.php"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
  } else {
    header('Content-Disposition: attachment; filename="lodelconfig.php"');
    header('Pragma: no-cache');
  }
  readfile($lodelconfig);
  return;
}


if ($tache=="showlodelconfig") {
  if (!(@include ("tpl/install-showlodelconfig.html"))) problem_include ("install-showlodelconfig.html");
  return;
}


if (!$tache) {
  $installing=file_exists($lodelconfig); // has an install been started
  if (!(@include ("tpl/install-bienvenue.html"))) problem_include ("install-bienvenue.html");
  return;
}

/////////////////////////////////////////////////////////////////
//                              TESTS                          //
/////////////////////////////////////////////////////////////////

//
// Vérifie les droits sur les fichiers, (verifie juste les droits d'apache, pas les droits des autres users, et verifie les droits minimum, pas de verification de la securite) dans la zone admin
//

// les fonctions de tests existent, donc on peut faire des tests sur les droits
$dirs=array("CACHE"=>7,
	    "lodeladmin/CACHE"=>7,
	    "lodeladmin/tpl"=>5,
	    "lodel$versionsuffix"=>5,
	    "lodel$versionsuffix/install"=>5,
	    "lodel$versionsuffix/install/plateform"=>5,
	    "lodel$versionsuffix/scripts"=>5,
	    "lodel$versionsuffix/src"=>5,
	    "lodeladmin/images"=>5);

$have_chmod=function_exists("chmod");
	       
$entete=0;
foreach ($dirs as $dir => $mode) {
  do { // block de control
    if (!file_exists(LODELROOT.$dir)) { die("ERROR: the directory $dir does not exists. Check your distribution and/or report the bug."); }
    if (testdirmode($dir,$mode)) break;
    // let try to chmod
    if ($have_chmod) {
      @chmod (LODELROOT.$dir);
      if (testdirmode($dir,$mode)) break;
    }
    if (!$entete) { probleme_droits_debut(); $entete=1; }
    probleme_droits($dir,$mode);
  } while(0);
  if ($entete) { probleme_droits_fin(); return; }
}

//
// Check PHP has the needed function 
//
$erreur[functions]=array();
foreach(array("utf8_encode","mysql_connect") as $fct) {
  if (!function_exists($fct)) array_push($erreur[functions],$fct);
}
if ($erreur[functions]) {
  if (!(include ("tpl/install-php.html"))) problem_include ("install-php.html");
  return;
}



// include: lodelconfig
//
// essai de trouver une configuration
//
#echo file_exists($lodelconfig),":",(require($lodelconfig)),":",$lodelconfig;
if (file_exists($lodelconfig) && (@include($lodelconfig))) {
  // ok c'est bon...
} else {
  // demander une plateforme pour l'install
  if (!(@include ("tpl/install-plateform.html"))) problem_include ("install-plateform.html");
  return;
}

// does what ./lodelconfig.php does.
$home=LODELROOT.$home;


//
// essaie d'etablir si on accede au script func.php
//
if ((@include($home."func.php"))!=568) { // on accede au fichier func.php
#  // il faut determiner si on fonctionne avec un $home ou si on fonctionne avec un include automatique.
#  // essaie de deviner le repertoire absolu
#  if (!$pathroot && function_exists("realpath")) {
#    $pathroot=@realpath(LODELROOT);
#    if ($pathroot) $erreur_guess=1;
#  }
#  if (!(@include ("tpl/install-home.html"))) problem_include("install-home.html");
#  return;
  die ("ERROR: unable to access the ".$home."func.php file from lodeladmin. Check the file exists and the rights and/or report the bug.");
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
  if (!($resultshowdatabases=@mysql_query("SHOW DATABASES"))) { // probleme ?
    // non, c'est surement pas une erreur de connection. Ca peut etre 
    // qu'on n'a pas les droits.
    // donc faut gerer autrement.

    //$erreur_connect=1;
    //if (!(@include ("tpl/install-mysql.html"))) problem_include("install-mysql.html");
    //return;
  } // ok, on a les databases, on demande la database principale
  if (!(@include ("tpl/install-database.html"))) problem_include("install-database.html");
  return;
} 

$sitesexistsrequest="SELECT id,statut FROM $GLOBALS[tableprefix]sites LIMIT 1";

if (!@mysql_select_db($database)) { // ok, database est defini, on tente la connection
  $erreur_usedatabase=1;

  if (!(@include ("tpl/install-database.html"))) problem_include("install-database.html");
  return;
} elseif ($erasetables || !@mysql_query($sitesexistsrequest)) {   // regarde si la table sites exists ?
  // non, alors on cree les tables

  // il faudrait tester ici que les tables sur la database sont bien les memes que celles dans le fichier
  // les IF NOT EXISTS sont necessaires dans le fichier init.sql sinon ca va produire une erreur.

  if ($erreur_createtables=mysql_query_file(LODELROOT."lodel$versionsuffix/install/init.sql",$erasetables)) {
    // mince, ca marche pas... bon on detruit la table sites si elle existe pour pouvoir revenir ici
    if (@mysql_query($sitesexistsrequest)) {
      if (!@mysql_query("DROP TABLE IF EXISTS $GLOBALS[tableprefix]sites")) { // ok, on n'arrive vraiment a rien faire
	$erreur_createtables.="<br /><br />La commande DROP TABLE IF EXISTS $GLOBALS[tableprefix]sites n'a pas pu être executée. On ne peut vraiment rien faire !";
      }
    }
    if (!(@include ("tpl/install-database.html"))) problem_include("install-database.html");
    return;
  }
} elseif ($tache=="database") { // the table site already exists but we just have asked for which database... check what to do.
  // ask for erasing the table content or not.
  $erreur_tablesexist=1;
    if (!(@include ("tpl/install-database.html"))) problem_include("install-database.html");
    return;
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
    if (file_exists(LODELROOT.$dir) && !file_exists(LODELROOT.$dir."/.htaccess")) array_push($erreur_htaccess,$dir);
  }
  if ($erreur_htaccess) {
    if (!(include("tpl/install-htaccess.html"))) problem_include("install-htaccess.html");
    return;
  }
}

//
// Demander des options generales
//
if ($installoption==1) {

} elseif ($importdir && !testdirmode($importdir,5)) {
  $erreur_importdir=1;
  if (!(@include("tpl/install-options.html"))) problem_include("install-options.html");
  return;
} elseif ($chooseoptions!="oui") {
  if (!(@include("tpl/install-options.html"))) problem_include("install-options.html");
  return;
}

//
// ServOO configuration
//

if ($servoourl!="off") {
  // test la configuration

  if ($servoourl && $servoousername && $servoopasswd) {
    $cmds="VER;";

    require($home."serveurfunc.php");
    list($ret,$retvar)=upload($servoourl,
			      array("username"=>$servoousername,
				    "passwd"=>$servoopasswd,
				    "commands"=>$cmds));
#    print_r($ret);
#    print_r($retvar);
    if (strpos($ret,"SAY:")===0) {
      if (!$skip && $tache=="servoo") {
	$message=substr($ret,4); // delete the SAY:
	if (!(@include("tpl/install-servoo.html"))) problem_include("install-servoo.html");
	return;
      }
    } else {
      $erreur_connect=$ret;
      if (!(@include("tpl/install-servoo.html"))) problem_include("install-servoo.html");
      return;
    }
  } else {
    if (!(@include("tpl/install-servoo.html"))) problem_include("install-servoo.html");
    return;
  }
}


//
// Vérifie maintenant que les lodelconfig sont les meme que celui qu'on vient de produire
//

$textlc=join('',file($lodelconfig));


$file="lodelconfig.php";

// check $file is readable
$rootlodelconfig_exists=file_exists(LODELROOT.$file);
if ($rootlodelconfig_exists && !is_readable(LODELROOT.$file)) {
  $erreur_exists_but_not_readable=1;
  include ("tpl/install-lodelconfig.html");
  return;
}

// compare the two config files

if (!$rootlodelconfig_exists || $textlc!=join('',file(LODELROOT.$file))) { // are they different ?
  @unlink(LODELROOT.$file);
  if (@copy($lodelconfig,LODELROOT.$file)) { // let copy
    @chmod(LODELROOT.$file,0666 & $GLOBALS[filemask]);
  } else { // error
    include ("tpl/install-lodelconfig.html");
    return;
  }  
}

// finish !
if ($installoption==1) { // essaie de creer automatiquement le site
  header("location: site.php?maindefault=1");
}
if (!(@include("tpl/install-fin.html"))) problem_include("install-fin.html");


/////////////////////////////////////////////////////////////////
//                           FONCTIONS                         //
/////////////////////////////////////////////////////////////////


function maj_lodelconfig($var,$val=-1)

{
  global $lodelconfig,$have_chmod;

  // lit le fichier
  $text=join("",file($lodelconfig));
  //  if (!$text) die("ERROR: $lodelconfig can't be read. Internal error, please report this bug");
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
  if (!(unlink($lodelconfig)) ) die ("ERROR: $lodelconfig can't be deleted. Internal error, please report this bug.");
   return ($f=fopen($lodelconfig,"w")) && fputs($f,$newtext) && fclose($f) && $have_chmod && chmod ($lodelconfig,0600);
}


function mysql_query_file($filename,$droptables=false)

{
  $sqlfile=str_replace("_PREFIXTABLE_", $GLOBALS['tableprefix'] ,
		       join('',file($filename)));
  if (!$sqlfile) return;
  $sql=preg_split ("/;/",preg_replace("/#.*?$/m","",$sqlfile));
  if (!$sql) return;

  foreach ($sql as $cmd) {
    $cmd=trim(preg_replace ("/^#.*?$/m","",$cmd));
    if ($cmd) {

      // should we drop tables before create them ?
      if ($droptables && preg_match("/^\s*CREATE\s+(?:TABLE\s+IF\s+NOT\s+EXISTS\s+)?".$GLOBALS['tableprefix']."(\w+)/",$cmd,$result)) {
	if (!mysql_query("DROP TABLE IF EXISTS ".$result[1])) {
	  $err.="$cmd <font COLOR=red>".mysql_error()."</font><br>";
	}
      }
      // execute the command
      if (!mysql_query($cmd)) { 
	$err.="$cmd <font COLOR=red>".mysql_error()."</font><br>";
      }
    }
  }
  return $err;
}

function guessfilemask() {
  //
  // Guess the correct filemask setting
  // (code from SPIP)

  $self = basename($_SERVER['PHP_SELF']);
  $uid_dir = @fileowner('.');
  $uid_self = @fileowner($self);
  $gid_dir = @filegroup('.');
  $gid_self = @filegroup($self);
  $perms_self = @fileperms($self);

  // Compare the ownership and groupship of the directory, the installer script and 
  // the file created by php.

  if ($uid_dir > 0 && $uid_dir == $uid_self && @fileowner($testfile) == $uid_dir)
    $chmod = 0700;
  else if ($gid_dir > 0 && $gid_dir == $gid2_self && @filegroup($testfile) == $gid_dir)
    $chmod = 0770;
  else
    $chmod = 0777;

  // Add the same read and executation rights as the installer script has.
  if ($perms_self > 0) {
    // add the execution right where there is read right
    $perms_self = ($perms_self & 0777) | (($perms_self & 0444) >> 2); 
    $chmod |= $perms_self;
  }

  return $chmod;
}



function problem_include($filename)

{
?>
<html>
<body>
<b>Impossible d'accéder au fichier <?php echo $filename; ?></b><br />
Vérifiez que le répertoire tpl ainsi que le fichier tpl/<?php echo $filename; ?> existent et sont accessibles par le serveur web<br>
<br />
Notez que pour assurer une sécurité maximale (mais jamais totale) de LODEL et du serveur, il convient de gérer les droits d'acces de tous les fichiers par vous même.<br>

LODEL est livré avec AUCUNE GARANTIE d'aucune sorte. Lisez le fichier LICENSE s'il vous plait.
</body>
</html>
<?php
  die();
}

function testdirmode($dir,$mode)

{
  if ($mode & 2) { // writeable ?
    $testfile=LODELROOT.$dir."/tmp_install_test.tmp";
    if (file_exists($testfile)) @unlink($testfile); // if I have not the write permission in the directory, I won't be able to do that.
    $fh=@fopen($testfile,"w");
    if (!$fh) return FALSE;
    if (!(@fputs($fh,"Lodel is great\n"))) return FALSE;
    fclose($fh);
    if (!(@unlink($testfile))) return FALSE;
  }
  if ($mode & 4) { // readable ? (et executable)
    if (substr($dir,0,1)!="/") $dir=LODELROOT.$dir;
    $dh=@opendir($dir);
    if (!$dh) return FALSE;
    if (!(@readdir($dh))) return FALSE;
    closedir($dh);
  }    
  return TRUE;
}



function problem($msg)

{
  $messages=array(
  "version"=>'La version de php sur votre serveur est trop ancienne pour le fonctionnement correcte de Lodel.<br />Version de php sur votre serveur: '.phpversion().'<br />Version recommandée: php 4.3 ou supérieure',

  "reading_lodelconfig"=>'Le fichier lodelconfig.php n\'a pas pu être lu. Veuillez verifier que le serveur web à les droits de lecteur sur ce fichier.<form method="post" action="install.php"><input type="hidden" name="tache" value="lodelconfig"><input type="submit" value="continuer"></form>',

  "lodelconfig_but_no_database"=>'Un fichier de configuration lodelconfig.php a été trouvé dans le répertoire principale de Lodel mais ce fichier ne permet pas actuellement d\'acceder à une base de donnée valide. Si vous souhaitez poursuivre l\'installation, veuillez effacer manuellement. Ensuite, veuillez cliquer sur le bouton "Recharger" de votre navigateur.</form>'
  );

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
  <?php echo $messages[$msg]; ?>
  </td>
</table>
</body>
?>
<?php 

  die();
}


function probleme_droits_debut()

{
?>
<h2>Accès aux répertoires.</h2>
<p align="center">
   <strong>Le serveur n'a pas accès au(x) répertoire(s) suivant(s). Vérifier que ce(s) répertoire(s) existent et que le serveur web (l'utilisateur nobody ou apache ou encore www-data) puisse y accèder en lecture et, si mentioné, ci-dessous y écrire</strong>
</p>
<ul>
<?php }

function probleme_droits($file,$mode)

{
 echo "<li>Répertoire: $file<br> droits requis: lecture, exécution"; if (($mode & 2) == 2) echo ", <u>écriture</u>";
 echo "</li>";
}

function probleme_droits_fin()

{
  global $installoption;
?>
</ul>
<p align="center">
<form method="post" action="install.php">
<input type="hidden" name="tache" value="droits">
<input type="hidden" name="installoption" value="<?php echo $installoption; ?>">
<input type="submit" value="continuer">
</form>
</p>
<p>
Notez que pour assurer une sécurité maximale de LODEL et du serveur, il convient de gérer les droits d'accès de tous les fichiers par vous-même.<br />
LODEL est livré avec SANS AUCUNE GARANTIE.</p>
<?php
 }

?>


