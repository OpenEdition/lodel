<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
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


// right
define("LEVEL_VISITOR",10);
define("LEVEL_REDACTOR",20);
define("LEVEL_EDITOR",30);
define("LEVEL_ADMIN",40);
define("LEVEL_ADMINLODEL",128);

error_reporting(E_CORE_ERROR | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR);


function authenticate ($level=0)

{
  global $context,$lodeluser;
  global $home,$timeout,$sessionname,$site;
  global $db;

  $retour="url_retour=".urlencode($_SERVER['REQUEST_URI']);
  do { // block de control
    $name=addslashes($_COOKIE[$sessionname]);

    if (!$name) break;
    require_once($home."connect.php");
    usemaindb();
    if (!($row=$db->getRow(lq("SELECT id,iduser,site,context,expire,expire2,currenturl FROM #_MTP_session WHERE name='$name'"))))  break;
    $GLOBALS['idsession']=$idsession=$row['id'];
    $GLOBALS['session']=$name;


    // verifie qu'on est dans le bon site
    if ($row['site']!="tous les sites" && $row['site']!=$site) break;

    // verifie que la session n'est pas expiree
    $time=time();
    //        echo $name,"   ",$row[expire],"  ",$time,"<br>";
    if ($row['expire']<$time || $row['expire2']<$time) { 
      $login="";
      if (file_exists("login.php")) { 
	$login="login.php"; 
      } elseif (file_exists("lodel/edition/login.php")) {
	$login="lodel/edition/login.php"; 
      } else {
	break;
      }
      header("location: $login?error_timeout=1&".$retour); exit();
    }

    // pass les variables en global
   
    $lodeluser=unserialize($row['context']);

    if ($lodeluser['rights']<$level) { header("location: login.php?error_privilege=1&".$retour); exit(); }

    // verifie encore une fois au cas ou...
    if ($lodeluser['rights']<LEVEL_ADMINLODEL && !$site) break;

    $lodeluser['adminlodel']=$lodeluser['rights']>=LEVEL_ADMINLODEL;
    $lodeluser['admin']=$lodeluser['rights']>=LEVEL_ADMIN;
    $lodeluser['editor']=$lodeluser['rights']>=LEVEL_EDITOR;
    $lodeluser['redactor']=$lodeluser['rights']>=LEVEL_REDACTOR;
    $lodeluser['visitor']=$lodeluser['rights']>=LEVEL_VISITOR;

    $context['lodeluser']=$lodeluser;

    // efface les donnees de la memoire et protege pour la suite
    #$_COOKIE[$sessionname]=0;
    //
    // change l'expiration de la session et l'url courrante
    //

    // clean the url
    $url=preg_replace("/[\?&]clearcache=\w+/","",$_SERVER['REQUEST_URI']);
    if (get_magic_quotes_gpc()) $url=stripslashes($url);
    $myurl=$norecordurl ? "''" : $db->qstr($url);
    $expire=$timeout+$time;
    $db->execute(lq("UPDATE #_MTP_session SET expire='$expire',currenturl=$myurl WHERE name='$name'")) or die ($db->errormsg());

    $context['clearcacheurl']=mkurl($url,"clearcache=oui");
    usecurrentdb();
    return; // ok !!!
  } while (0);
  if (function_exists("usecurrentdb")) {  usecurrentdb();}

  // exception
  if ($level==0) {
    return; // les variables ne sont pas mises... on retourne
  } else {
    header("location: login.php?".$retour);
    exit;
  }
}


function recordurl()

{
  global $idsession,$norecordurl,$db;

  if (!$norecordurl)
    $db->execute(lq("INSERT INTO #_MTP_urlstack (idsession,url) SELECT id,currenturl FROM #_MTP_session WHERE id='".$idsession."' AND currenturl!=''")) or dberror();
}


function mkurl ($url,$extraarg)

{
  if (strpos($url,"?")===FALSE) {
    return $url."?".$extraarg;
  } else {
    return $url."&".$extraarg;
  }
}


function getacceptedcharset($charset) {
	// Détermine le charset a fournir au navigateur

  $browserversion = array (	
			   "opera" => 6,
			   "netscape" => 4,
			   "msie" => 4,
			   "ie" => 4,
			   "mozilla" => 3
			   );
	if (!$charset) {
		// Si ce n'est pas envoye par l'url ou par cookie, on recupere ce que demande le navigateur.
		if ($_SERVER["HTTP_ACCEPT_CHARSET"]) {
			// Si le navigateur retourne HTTP_ACCEPT_CHARSET on l'analyse et on en déduit le charset
			if (preg_match("/\butf-8\b/i", $_SERVER["HTTP_ACCEPT_CHARSET"])) return "utf-8";
			else return "iso-8859-1";
		// Sinon on analyse le HTTP_USER_AGENT retourné par le navigateur et si ca matche on vérifie 
		// que la version du navigateur est supérieure ou égale à la version déclarée unicode
		} elseif ((preg_match("/\b(\w+)\W(\d+)/i", $_SERVER["HTTP_USER_AGENT"], $matches)) && 
				($matches[2] >= $browserversion[strtolower($matches[1])])) {
			return "utf-8";
		} else return "iso-8859-1"; // Si on a rien trouvé on renvoie de l'iso
	}
	else return $charset;
}

// import Posted variables for the Register Off case.
// this should be nicely/safely integrated inside the code, but that's
// a usefull little hack at the moment
if (!((bool) ini_get("register_globals"))) { // 
  extract($_REQUEST,EXTR_SKIP);
}

// securite... initialisation
$lodeluser=array();
$idsession=0;
$session="";

$context=array(
	       "version" => $GLOBALS['version'],
	       "shareurl"=>$GLOBALS['shareurl'],
	       "extensionscripts"=>$GLOBALS['extensionscripts'],
	       "currenturl"=>"http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'],
	       #"database"=>defined("DATABASE") ? DATABASE : $GLOBALS['database'],
	       "siteroot"=>defined("SITEROOT") ? SITEROOT : "",
	       ); // tres important d'initialiser le context.

if (!$filemask) $filemask="0700";

// cherche le name du site

$context['site']=$site;



$context['charset'] = getacceptedcharset($charset);
header("Content-type: text/html; charset=$context[charset]");

?>
