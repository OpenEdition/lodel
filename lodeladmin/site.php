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

// gere un site. L'acces est reserve au adminlodelistrateur.

require("lodelconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMINLODEL,NORECORDURL);
include_once ($home."func.php");

// calcul le critere pour determiner le user a editer, restorer, detruire...
$id=intval($id);
$critere="id='$id'";
$context[installoption]=intval($installoption);

//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  include ($home."trash.php");
  treattrash("sites",$critere);
  return;
}
//
// ajoute ou edit
//

if ($edit) { // modifie ou ajoute
  extract_post();
  // validation
  do {
    if (!$context[nom]) { $context[erreur_nom]=$err=1; }
    if (!$context[rep] || preg_match("/\W/",$context[rep])) { $context[erreur_rep]=$err=1; }
    if ($err) break;
    include_once ($home."connect.php");

    // lit les informations options, statut, etc... si le site existe deja
    if ($id) {
      $result=mysql_query ("SELECT options,statut FROM $GLOBALS[tp]sites WHERE id='$id'") or die (mysql_error());
      list($options,$statut)=mysql_fetch_row($result);
    } else {
      $options=""; $statut=-32; // -32 signifie en creation
    }
    if ($reinstalle) $statut=-32;

    mysql_query("REPLACE INTO $GLOBALS[tp]sites (id,nom,rep,soustitre,options,statut) VALUES ('$id','$context[nom]','$context[rep]','$context[soustitre]','$options','$statut')") or die (mysql_error());

    if ($statut>-32) back(); // on revient, le site n'est pas en creation

    if (!$id) $context[id]=$id=mysql_insert_id();
    $tache="version"; 

  } while (0);

} elseif ($id>0) {
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]sites WHERE $critere AND (statut>0 || statut=-32)") or die (mysql_error());
  $context=array_merge($context,mysql_fetch_assoc($result));
}




// regexp pour reconnaitre un repertoire de version
$lodelhomere="/^lodel(-[\w.]+)?$/";

if ($tache=="version") {
  // on verifie que versionrep match bien un repertoire local pour eviter un hack.
  // on verifie en meme temps qu'il est bien defini, ce qui correspond quand meme 
  // a la plupart des cas.

  // cherche les differentes versions de lodel
  function cherche_version () // on encapsule a cause du include de sites config
    {
      global $lodelhomere;
      $dir=opendir(LODELROOT);
      if (!$dir) die ("impossible d'acceder en ecirture le repertoire racine... etrange, n'est-il pas ?");
      $versions=array();
      while ($file=readdir($dir)) {
	#echo $file," ";
	if (is_dir(LODELROOT."/".$file) && 
	    preg_match($lodelhomere,$file) &&
	    is_dir(LODELROOT."/".$file."/src")) {
	  if (!(@include(LODELROOT."/$file/src/siteconfig.php"))) {
	    echo "ERROR: Unable to open the file: $file/src/siteconfig.php<br>";
	  } else {
	    $versions[$file]=$version ? $version : "devel";
	  }
	}
      }
      return $versions;
    }
  if  (!$versionrep) {
    $versions=cherche_version();	  
    // ok, maintenant on connait les versions
    $context[countversions]=count($versions);
    if ($context[countversions]==1) {// ok, une seule version, on la choisit
      list($versionrep)=array_keys($versions);
    } elseif ($context[countversions]==0) { // aie, aucune version on crach
      die ("Verifiez le package que vous avez, il manque le repertoire lodel/src. L'installation ne peut etre poursuivie !");
    } else { // il y en a plusieurs, faut choisir
      $context[count]=count($versions);
      function makeselectversion()
      {
	global $versionrep,$versions;
	foreach ($versions as $dir=>$ver) {
	  $selected=$versionrep==$dir ? " selected" : "";
	  echo "<option value=\"$dir\"$selected>$dir  ($ver)</option>\n";
	}
      }
      require ($home."calcul-page.php");
      calcul_page($context,"site-version");
      return;	
    }
  }
  $tache="createdb";
}   // on connait le repertoire dans lequel est la "bonne" version de lodel/site

if ($tache) {
  if (!preg_match($lodelhomere,$versionrep)) die ("ERROR: versionrep");
  $context[versionrep]=$versionrep;
}


//
// creation de la DataBase si besoin
//
$context[dbname]=$singledatabase ? $database : $database."_".$context[rep];
if ($tache=="createdb") {
  if (!$context[rep]) die ("probleme interne");
  do { // bloc de controle
    if ($singledatabase) break;
    // check if the database existe
    include_once ($home."connect.php");
    $db_list = mysql_list_dbs();
    $i = 0;
    $cnt = mysql_num_rows($db_list);
    while ($i < $cnt) {
      if ($context[dbname]==mysql_db_name($db_list, $i)) break 2; // la database existe
      $i++;
    }
    // well, it does not exist, let's create it.
    //
    $context[command1]="CREATE DATABASE $context[dbname]";
    $context[command2]="GRANT ALL ON $context[dbname].* TO $dbusername@$dbhost";
    $pass=$dbpasswd ? " IDENTIFIED BY '$dbpasswd'" : "";

    if ($installoption=="2" && !$lodeldo) {
      $context[dbusername]=$dbusername;
      $context[dbhost]=$dbhost;
      require ($home."calcul-page.php");
      calcul_page($context,"site-createdb");
      return;
    }
    if (!@mysql_query($context[command1]) ||
	!@mysql_query($context[command2].$pass)) {
      $context[erreur]=mysql_error();
      $context[dbusername]=$dbusername;
      $context[dbhost]=$dbhost;
      require ($home."calcul-page.php");
      calcul_page($context,"site-createdb");
      return;
    }
  } while (0);
  $tache="createtables";
}
//
// creation des tables des sites
//

if ($tache=="createtables") {
  if (!$context[rep]) die ("probleme interne");
  include_once ($home."connect.php");
  
  mysql_select_db($context[dbname]);

  if (!file_exists(LODELROOT."/$versionrep/install/init-site.sql")) die ("impossible de faire l'installation, le fichier init-site.sql est absent");
  $text=join('',file(LODELROOT."/$versionrep/install/init-site.sql"));
#  if (file_exists(LODELROOT."lodel/install/inserts-site.sql")) {
#    $text.=utf8_encode(join('',file(LODELROOT."lodel/install/inserts-site.sql")));
#  }
  $sqlfile=str_replace("_PREFIXTABLE_",$GLOBALS[tp],$text);

  $sqlcmds=preg_split ("/;/",preg_replace("/#.*?$/m","",$sqlfile));
  if (!$sqlcmds) die("le fichier init-site.sql ne contient pas de commande. Probleme!");

  $erreur=array();
  foreach ($sqlcmds as $cmd) {
    $cmd=trim($cmd);
    if ($cmd && !@mysql_query($cmd)) array_push($erreur,$cmd,mysql_error());
  }
  if ($erreur) {
    $context[erreur_createtables]=$erreur;
    function loop_erreurs_createtables(&$context,$funcname)
    {
      $erreur=$context[erreur_createtables];
      do {
	$localcontext[command]=array_shift($erreur);
	$localcontext[error]=array_shift($erreur);
	call_user_func("code_do_$funcname",array_merge($context,$localcontext));
      } while ($erreur);
    }
    require ($home."calcul-page.php");
    calcul_page($context,"site-createtables");
    return;
  }

  $tache="createrep";
}

//
// Creer le repertoire principale de la site
//

if ($tache=="createrep") {
  $dir=LODELROOT."/".$context[rep];
  if (!file_exists($dir) || !@opendir($dir)) {
    // il faut creer le repertoire rep
    if ($installoption=="2" && !$lodeldo) {
      if ($mano) $context[erreur_nonexists]=1;
      require ($home."calcul-page.php");
      calcul_page($context,"site-createrep");
      return;
    }
    // on essaie
    if (!@mkdir($dir,0777 & octdec($filemask))) {
      // on y arrive pas... pas les droits surement
      $context[erreur_mkdir]=1;
      require ($home."calcul-page.php");
      calcul_page($context,"site-createrep");
      return;
    }
    if (function_exists("chmod")) @chmod($dir,0777 & octdec($filemask)); // pour etre sur.
  }
  $tache="fichier";
}
//
// verifie la presence ou copie les fichiers necessaires
// 
// cherche dans le fichier install-file.dat les fichiers a copier

if ($tache=="fichier") {
  // on peut installer les fichiers
  $root=LODELROOT.$context[rep]."/";
  $siteconfigsrc=LODELROOT."/$versionrep/src/siteconfig.php";
  $siteconfigdest=$root."siteconfig.php";
  // cherche si le fichier n'existe pas ou s'il est different de l'original
  if (!file_exists($siteconfigdest) || file($siteconfigsrc)!=file($siteconfigdest)) {
    if ($installoption=="2" && !$lodeldo) {
      require ($home."calcul-page.php");
      calcul_page($context,"site-fichier");
      return;	
    }
    // on essaie de copier alors
    if (!@copy($siteconfigsrc,$siteconfigdest)) {
      $context[siteconfigsrc]=$siteconfigsrc;
      $context[siteconfigdest]=$siteconfigdest;
      $context[erreur_ecriture]=1;
      require ($home."calcul-page.php");
      calcul_page($context,"site-fichier");
      return;	
    }
  }
  // ok siteconfig est copier.
  install_fichier($root,LODELROOT."/$versionrep/src",LODELROOT);

  // ok on a fini, on change le statut de la site
  mysql_select_db($GLOBALS[database]);
  mysql_query ("UPDATE $GLOBALS[tp]sites SET statut=1 WHERE id='$id'") or die (mysql_error());

  header("location: ".$urlroot.$context[rep]."/lodel/admin");

#  header("location: index.php");
#  back();
}

// post-traitement
posttraitement ($context);

include ($home."calcul-page.php");
calcul_page($context,"site");


function install_fichier($root,$homesite,$homelodel)

{
  global $extensionscripts,$usesymlink;

  $file="$root$homesite/../install/install-fichier.dat"; // homelodel est necessaire pour choper le bon fichier d'install
  if (!file_exists($file)) die("Fichier $file introuvable. Verifiez votre pactage");
  $lines=file ($file);

  $dirsource=".";
  $dirdest=".";

  $search=array("/\#.*$/",'/\$homesite/','/\$homelodel/');
  $rpl=array ("",$homesite,$homelodel);

  foreach ($lines as $line) {
    $line=rtrim(preg_replace($search,$rpl,$line));
    if (!$line) continue;
    list ($cmd,$arg1,$arg2)=preg_split ("/\s+/",$line);

    $dest1="$root$dirdest/$arg1";
# quelle commande ?
    if ($cmd=="dirsource") {
      $dirsource=$arg1;
    } elseif ($cmd=="dirdestination") {
      $dirdest=$arg1;
    } elseif ($cmd=="mkdir") {
      $arg1=$root.$arg1;
      if (file_exists($arg1)) {
	// il existe, on essaie juste de chmoder
	@chmod($arg1,octdec($arg2));
      } else {
	mkdir($arg1,octdec($arg2));
      }
    } elseif ($cmd=="ln" && $usesymlink!="non") {
      if ($dirdest=="." && 
	  $extensionscripts=="html" &&
	  $arg1!="lodelconfig.php") $dest1=preg_replace("/\.php$/",".html",$dest1);
      if (!file_exists($dest1)) {
	$toroot=preg_replace(array("/^\.\//","/([^\/]+)\//","/[^\/]+$/"),
			     array("","../",""),"$dirdest/$arg1");
#    print "3 dirdest:$dirdest dirsource:$dirsource toroot:$toroot arg1:$arg1<br>\n";
	slink("$toroot$dirsource/$arg1",$dest1);
      }
    } elseif ($cmd=="cp" || ($cmd=="ln" && $usesymlink=="non")) {
      if ($dirdest=="." && 
	  $extensionscripts=="html" &&
	  $arg1!="lodelconfig.php") $dest1=preg_replace("/\.php$/",".html",$dest1);
      mycopyrec("$root$dirsource/$arg1",$dest1);
    } elseif ($cmd=="touch") {
      if (!file_exists($dest1)) touch($dest1);
    } elseif ($cmd=="htaccess") {
      if (!file_exists("$dest1/.htaccess")) htaccess($dest1);
    } else {
      die ("command inconnue: \"$cmd\"");
    }
  }

  return TRUE;
}



function htaccess ($dir) {
  //if (!@unlink("$dir/.htaccess")) die("Impossible d'effacer le fichier $dir/.htaccess");
  $text="deny from all\n";
  writefile ("$dir/.htaccess",$text);
  @chmod ("$dir/.htaccess",0640);
}

function slink($src,$dest) {
  // le lien n'existe pas ou on n'y accede pas.
  @unlink($dest); // detruit le lien s'il existe
  symlink($src,$dest);
  if (!file_exists($dest)) die ("impossible d'acceder au fichier $src via le lien symbolique $dest");
}

function mycopyrec($src,$dest) 

{
  global $filemask;
  if (is_dir($src)) {

    if (!is_dir($dest)) unlink($dest);
    if (!file_exists($dest)) mkdir($dest,0755 & octdec($filemask));

    $dir=opendir($src);
    while ($file=readdir($dir)) {
      if ($file=="." || $file=="..") continue;
      $srcfile=$src."/".$file;
      $destfile=$dest."/".$file;
      // pour le moment on ne copie pas les repertoires, que les fichiers
      if (is_file($srcfile)) mycopy($srcfile,$destfile);
    }
    closedir($dir);
  } else {
    mycopy($src,$dest);
  }
}

function mycopy($src,$dest) 
  
{
  global $filemask;
#  echo $dest,"<br />";

   if (!file_exists ($dest) || 
       filemtime($dest)<=filemtime($src)) {
     if (file_exists ($dest)) unlink($dest);
     copy($src,$dest);
     @chmod($dest,0644 & octdec($filemask));
   }
}

?>
