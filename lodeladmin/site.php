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

// gere un site. L'acces est reserve au adminlodelistrateur.

require("lodelconfig.php");
require ($home."auth.php");
authenticate(LEVEL_ADMINLODEL,NORECORDURL);
require_once ($home."func.php");

// calcul le critere pour determiner le user a editer, restorer, detruire...
$id=intval($id);
$critere="id='$id'";
$context[installoption]=intval($installoption);
$context[version]="0.7";

//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  require ($home."trash.php");
  treattrash("sites",$critere);
  return;
}
//
// ajoute ou edit
//

if ($edit || $maindefault) { // modifie ou ajoute
  extract_post();
  if ($maindefault) { // site par defaut ?
    $context['nom']="Site principal";
    $context['rep']="principal";
    $context['atroot']=true;
  }
  // validation

  do {
    if (!$context[nom]) { $context[erreur_nom]=$err=1; }
    if (!$id && (!$context[rep] || preg_match("/\W/",$context[rep]))) { $context[erreur_rep]=$err=1; }
    if ($err) break;
    require_once ($home."connect.php");


    // verifie qu'on a qu'un site si on est en singledatabase
    if (!$id && $singledatabase=="on") {
      $result=mysql_query ("SELECT COUNT(*) FROM $GLOBALS[tp]sites WHERE statut>-64") or die (mysql_error());
      list($numsite)=mysql_fetch_row($result);
      if ($numsite>=1) {
	die("ERROR<br />\nIl n'est pas possible actuellement d'avoir plusieurs sites sur une unique base de données. Il faut utiliser plusieurs bases de donnée ou attendre la prochaine version.<br /> Merci de votre comprehension.");
      }
    }
    // lit les informations options, statut, etc... si le site existe deja
    if ($id) {
      $result=mysql_query ("SELECT statut,rep,chemin FROM $GLOBALS[tp]sites WHERE id='$id'") or die (mysql_error());
      list($statut,$rep,$context[chemin])=mysql_fetch_row($result);
      $context[rep]=$rep;
    } else {
      $options=""; $statut=-32; // -32 signifie en creation
      if ($context[atroot]) $context[chemin]="/";
      if (!$context[chemin]) $context[chemin]="/".$context[rep];
    }
    if (!$context[url]) {
      $context[url]="http://".$_SERVER['SERVER_NAME'].preg_replace("/\blodeladmin\/.*/","",$_SERVER['REQUEST_URI']).substr($context[chemin],1);
    }

    if ($reinstalle) $statut=-32;

    //suppression de l'eventuel / a la fin de l'url
    $context[url]=preg_replace("/\/$/","",$context[url]);

    mysql_query("REPLACE INTO $GLOBALS[tp]sites (id,nom,rep,chemin,url,soustitre,statut) VALUES ('$id','$context[nom]','$context[rep]','$context[chemin]','$context[url]','$context[soustitre]','$statut')") or die (mysql_error());

    if ($statut>-32) back(); // on revient, le site n'est pas en creation

    #if ($id && $oldrep!=$newrep) {
    #  $tache="";
    #}
    if (!$id) $context[id]=$id=mysql_insert_id();
    $tache="version";
  } while (0);
}

if ($id>0) {
  require_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]sites WHERE $critere AND (statut>0 || statut=-32)") or die (mysql_error());
  $context=array_merge($context,mysql_fetch_assoc($result));
}


// regexp pour reconnaitre un repertoire de version
$lodelhomere="/^lodel(-[\w.]+)$/";

if ($tache=="version") {
  // on verifie que versionrep match bien un repertoire local pour eviter un hack.
  // on verifie en meme temps qu'il est bien defini, ce qui correspond quand meme 
  // a la plupart des cas.

  // cherche les differentes versions de lodel
  function cherche_version () // on encapsule a cause du include de sites config
    {
      global $lodelhomere;
      $dir=opendir(LODELROOT);
      if (!$dir) die ("impossible d'acceder en ecriture le repertoire racine... etrange, n'est-il pas ?");
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
$context[dbname]=$singledatabase=="on" ? $database : $database."_".$context[rep];
if ($tache=="createdb") {
  if (!$context[rep]) die ("probleme interne");
  do { // bloc de controle
    if ($singledatabase=="on") break;
    // check if the database existe
    require_once ($home."connect.php");
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
  require_once ($home."connect.php");
  
  mysql_select_db($context['dbname']);

  if (!file_exists(LODELROOT."$versionrep/install/init-site.sql")) die ("impossible de faire l'installation, le fichier init-site.sql est absent");
  $text=join('',file(LODELROOT."$versionrep/install/init-site.sql"));

  $text.="\n";
  $text.="REPLACE INTO _PREFIXTABLE_options (nom,type,valeur,statut,ordre) VALUES ('servoourl','url','".($servoourl=="off" ? "" : $servoourl)."','32','1');\n";
  $text.="REPLACE INTO _PREFIXTABLE_options (nom,type,valeur,statut,ordre) VALUES ('servoousername','s','$servoousername','32','1');\n";
  $text.="REPLACE INTO _PREFIXTABLE_options (nom,type,valeur,statut,ordre) VALUES ('servoopasswd','pass','$servoopasswd','32','1');\n";

  $sqlfile=str_replace("_PREFIXTABLE_",$GLOBALS[tp],$text);

  $sqlcmds=preg_split ("/;\s*\n/",preg_replace("/#.*?$/m","",$sqlfile));
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

#  // ajoute le modele editorial
#  // on le cherche d'abord.
#  $fichier=LODELROOT."$versionrep/install/plateform/model-default.sql";
#  #echo $fichier," ",file_exists($fichier);
#  #die("");
#  if (file_exists($fichier)) {
#    $import=true;
#    // verifie qu'on peut importer le modele.
#    foreach(array("types","champs","typepersonnes","typeentrees") as $table) {
#      $result=mysql_query("SELECT 1 FROM $GLOBALS[tp]$table WHERE statut>-64 LIMIT 0,1") or die(mysql_error());
#      if (mysql_num_rows($result)) { $import=false; break; } 
#    }
#
#    if ($import) {
#      require_once ($home."backupfunc.php");
#      // execute the editorial model
#      if (!execute_dump($fichier)) $context[erreur_execute_dump]=$err=mysql_error();
#      // change the id in order there are minimal and unique
#      require_once($home."objetfunc.php");
#      makeobjetstable();
#    }
#  }

  $tache="createrep";
}

//
// Creer le repertoire principale de la site
//

if ($tache=="createrep") {
  if (!$context[chemin]) $context[chemin]="/".$context[rep];
  $dir=LODELROOT.$context[chemin];
  if (!file_exists($dir) || !@opendir($dir)) {
    // il faut creer le repertoire rep
    if ($installoption=="2" && !$lodeldo) {
      if ($mano) {
	$context[erreur_nonexists]=!file_exists($dir);
	$context[erreur_nonaccess]=!@opendir($dir);
      }
      require ($home."calcul-page.php");
      calcul_page($context,"site-createrep");
      return;
    }
    // on essaie
    if (!file_exists($dir) && !@mkdir($dir,0777 & octdec($filemask))) {
      // on y arrive pas... pas les droits surement
      $context[erreur_mkdir]=1;
      require ($home."calcul-page.php");
      calcul_page($context,"site-createrep");
      return;
    }
    @chmod($dir,0777 & octdec($filemask));
  }
  // on essaie d'ecrire dans tpl si root
  if ($context[chemin]=="/") {
    if (!@touch(LODELROOT."tpl/testecriture")) {
      $context[erreur_tplaccess]=1;
      require ($home."calcul-page.php");
      calcul_page($context,"site-createrep");
      return;
    } else {
      unlink(LODELROOT."tpl/testecriture");
    }
    //
  }
  $tache="fichier";
}
//
// verifie la presence ou copie les fichiers necessaires
// 
// cherche dans le fichier install-file.dat les fichiers a copier

if ($tache=="fichier") {
  // on peut installer les fichiers
  if (!$context[chemin]) $context[chemin]="/".$context[rep];
  $root=str_replace("//","/",LODELROOT.$context[chemin])."/";

  $siteconfigcache="CACHE/siteconfig.php";

  if ($downloadsiteconfig) { // download the siteconfig
    download($siteconfigcache,"siteconfig.php");
    return;
  }
  if (file_exists($siteconfigcache)) unlink($siteconfigcache);

  $atroot=$context[chemin]=="/" ? "root" : "";
  if (!copy(LODELROOT."$versionrep/src/siteconfig$atroot.php",$siteconfigcache)) die("ERROR: unable to write in CACHE. Strange !");
  maj_siteconfig($siteconfigcache,array("site"=>$context[rep]));

  $siteconfigdest=$root."siteconfig.php";
  // cherche si le fichier n'existe pas ou s'il est different de l'original
  if (!file_exists($siteconfigdest) || file_get_contents($siteconfigcache)!=file_get_contents($siteconfigdest)) {
    if ($installoption=="2" && !$lodeldo) {
      require ($home."calcul-page.php");
      calcul_page($context,"site-fichier");
      return;
    }
    @unlink($siteconfigdest); // try to delete before copying.
    // try to copy now.
    if (!@copy($siteconfigcache,$siteconfigdest)) {
      $context[siteconfigsrc]=$siteconfigcache;
      $context[siteconfigdest]=$siteconfigdest;
      $context[erreur_ecriture]=1;
      require ($home."calcul-page.php");
      calcul_page($context,"site-fichier");
      return;	
    }
    @chmod ($siteconfigdest,0666 & octdec($GLOBALS['filemask']));
  }
  // ok siteconfig est copie.
  if ($context[chemin]=="/") { // c'est un peu sale ca.
    install_fichier($root,"$versionrep/src","");
  } else {
    install_fichier($root,"../$versionrep/src",LODELROOT);
  }

  // ok on a fini, on change le statut du site
  mysql_select_db($GLOBALS[database]);
  mysql_query ("UPDATE $GLOBALS[tp]sites SET statut=1 WHERE id='$id'") or die (mysql_error());


  // ajouter le modele editorial ?
  if ($GLOBALS[singledatabase]!="on") {
    mysql_select_db($GLOBALS['database']."_".$context['rep']);
  }
  $import=true;
  // verifie qu'on peut importer le modele.
  foreach(array("types","champs","typepersonnes","typeentrees") as $table) {
    $result=mysql_query("SELECT 1 FROM $GLOBALS[tp]$table WHERE statut>-64 LIMIT 0,1") or die(mysql_error());
    if (mysql_num_rows($result)) { $import=false; break; } 
  }

  if (!$context['chemin']) $context['chemin']="/".$context['rep'];
  if ($import) {
    header("location: ".$context['url']."/lodel/admin/importmodel.php?frominstall=1");
  } else {
    header("location: ".$context['url']."/lodel/edition");
  }


  return;

#  header("location: index.php");
#  back();
}

// post-traitement
posttraitement ($context);

require ($home."calcul-page.php");
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
      if (!file_exists($arg1)) {
	mkdir($arg1,0777 & octdec($GLOBALS['filemask']));
      }
      @chmod($arg1,0777 & octdec($GLOBALS['filemask']));
    } elseif ($cmd=="ln" && $usesymlink && $usesymlink!="non") {
      if ($dirdest=="." && 
	  $extensionscripts=="html" &&
	  $arg1!="lodelconfig.php") $dest1=preg_replace("/\.php$/",".html",$dest1);
      if (!file_exists($dest1)) {
	$toroot=preg_replace(array("/^\.\//","/([^\/]+)\//","/[^\/]+$/"),
			     array("","../",""),"$dirdest/$arg1");

#    print "3 dirdest:$dirdest dirsource:$dirsource toroot:$toroot arg1:$arg1<br>\n";
	slink("$toroot$dirsource/$arg1",$dest1);
      }
    } elseif ($cmd=="cp" || ($cmd=="ln" && (!$usesymlink || $usesymlink=="non"))) {
      if ($dirdest=="." && 
	  $extensionscripts=="html" &&
	  $arg1!="lodelconfig.php") $dest1=preg_replace("/\.php$/",".html",$dest1);
      mycopyrec("$root$dirsource/$arg1",$dest1);
    } elseif ($cmd=="touch") {
      if (!file_exists($dest1)) touch($dest1);
      @chmod($dest1,0666 & octdec($GLOBALS['filemask']));
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
  if (file_exists("$dir/.htaccess") && file_get_contents("$dir/.htaccess")==$text) return;
  writefile ("$dir/.htaccess",$text);
  @chmod ("$dir/.htaccess",0666 & octdec($GLOBALS['filemask']));
}

function slink($src,$dest) {

  if (file_exists($dest) && file_get_contents($dest)==file_get_contents($src)) return;
  // le lien n'existe pas ou on n'y accede pas.
  @unlink($dest); // detruit le lien s'il existe
  if (!(@symlink($src,$dest))) {
    @chmod(basename($dest),0777 & octdec($GLOBALS['filemask']));
    symlink($src,$dest);
  }
  if (!file_exists($dest)) die ("impossible d'acceder au fichier $src via le lien symbolique $dest");
}

function mycopyrec($src,$dest) 

{
  if (is_dir($src)) {

    if (file_exists($dest) && !is_dir($dest)) unlink($dest);
    if (!file_exists($dest)) mkdir($dest);
    @chmod($dest,0777 & octdec($GLOBALS['filemask']));

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
#  echo $dest,"<br />";

   if (file_exists ($dest) && file_get_contents($dest)==file_get_contents($src)) return;

   if (file_exists ($dest)) unlink($dest);
   if (!(@copy($src,$dest))) {
     @chmod(basename($dest),0777 & octdec($GLOBALS['filemask']));
     copy($src,$dest);
   }
   @chmod($dest,0666 & octdec($GLOBALS['filemask']));
}


function maj_siteconfig($siteconfig,$var,$val=-1)

{

  // lit le fichier
  $text=join("",file($siteconfig));
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
  if ($newtext==$text) return false;
  // ecrit le fichier
  if (!(unlink($siteconfig)) ) { return $newtext; }
  if (($f=fopen($siteconfig,"w")) && 
      fputs($f,$newtext) && 
      fclose($f)) {
    @chmod ($siteconfig,0666 & octdec($GLOBALS['filemask']));
    return false;
  } else {
    return $newtext;
  }
}


?>
