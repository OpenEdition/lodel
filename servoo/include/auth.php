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


// droit
define(LEVEL_VISITEUR,10);
define(LEVEL_ADMIN,40);
define(LEVEL_ADMINLODEL,128);

function authenticate ($level=0)

{
  global $HTTP_COOKIE_VARS,$context,$idadmin,$adminrights;
  global $logintimeout,$database,$sessionname;
  global $dbhost,$dbusername,$dbpasswd;

  $urlreturn="url_retour=".urlencode($_SERVER['REQUEST_URI']);

  do { // block de control
    $name=addslashes($_COOKIE[$sessionname]);
    if (!$name) break;

    mysql_connect($dbhost,$dbusername,$dbpasswd) or die (mysql_error());
    mysql_select_db($database)  or die (mysql_error());

    if (!($result=mysql_query ("SELECT id,idadmin,context,timeout,timeout2 FROM $GLOBALS[tp]session WHERE name='$name'")))  break;
    if (!($row=mysql_fetch_assoc($result))) break;
    $GLOBALS[idsession]=$idsession=$row[id];
    $GLOBALS[session]=$name;

    // verifie que la session n'est pas expiree
    $time=time();
#    echo $name,"   ",$row[timeout],"  ",$time," ",$row[timeout]<$time || $row[timeout2]<$time,"<br>";
    if ($row[timeout]<$time || $row[timeout2]<$time) { 
      $login="";
      if (file_exists("login.php")) { 
	$login="login.php"; 
      } else {
	break;
      }
      header("location: $login?erreur_timeout=1&".$urlreturn); exit();
    }

    // pass les variables en global
   
    $context=array_merge($context,unserialize($row[context])); // recupere le contexte
    $adminrights=$context[adminrights];
    $context[idadmin]=$idadmin=$row[idadmin];

    if ($adminrights<$level) { header("location: login.php?erreur_privilege=1&".$urlreturn); exit; }

    // verifie encore une fois au cas ou...
    if ($adminrights>=LEVEL_ADMINLODEL) $context[droitadminlodel]=$GLOBALS[droitadminlodel]=1;
    if ($adminrights>=LEVEL_ADMIN) $context[droitadmin]=$GLOBALS[droitadmin]=1;
    if ($adminrights>=LEVEL_VISITEUR) $context[droitvisiteur]=$GLOBALS[droitvisiteur]=1;
    // efface les donnees de la memoire et protege pour la suite
    $_COOKIE[session]=0;

    //
    // change l'expiration de la session et l'url courrante
    //

    // nettoie l'url
    $url=preg_replace("/[\?&]recalcul\w+=oui/","",$GLOBALS[REQUEST_URI]);
    $context[url_recompile]=mkurl($url,"recalcul_templates=oui");

    $timeout=$logintimeout+$time;
    mysql_query("UPDATE $GLOBALS[tp]session SET timeout='$timeout' WHERE name='$name'") or die (mysql_error());


    return; // ok !!!
  } while (0);

  // exception
  if ($level==0) {
    return; // les variables ne sont pas mises... on retourne
  } else {
    header("location: login.php?".$urlreturn);
    exit;
  }
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
	global $HTTP_SERVER_VARS;
	$browserversion = array (	
						"opera" => 6,
						"netscape" => 4,
						"msie" => 4,
						"ie" => 4,
						"mozilla" => 3
					);
	if (!$charset) {
		// Si ce n'est pas envoye par l'url ou par cookie, on recupere ce que demande le navigateur.
		if ($HTTP_SERVER_VARS["HTTP_ACCEPT_CHARSET"]) {
			// Si le navigateur retourne HTTP_ACCEPT_CHARSET on l'analyse et on en déduit le charset
			if (preg_match("/\butf-8\b/i", $HTTP_SERVER_VARS["HTTP_ACCEPT_CHARSET"])) return "utf-8";
			else return "iso-8859-1";
		// Sinon on analyse le HTTP_USER_AGENT retourné par le navigateur et si ca matche on vérifie 
		// que la version du navigateur est supérieure ou égale à la version déclarée unicode
		} elseif ((preg_match("/\b(\w+)\W(\d+)/i", $HTTP_SERVER_VARS["HTTP_USER_AGENT"], $matches)) && 
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
$adminrights=0;
$idadmin=0;
$idsession=0;
$session="";
$tp="";

$context=array(
	       "version" => doubleval($version),
	       "shareurl"=>$GLOBALS[shareurl],
	       ); // tres important d'initialiser le context.
$droitadminlodel=0;
$droitadmin=0;
$admin=0;
if (!$filemask) $filemask="0700";


$context[charset] = getacceptedcharset($charset);
header("Content-type: text/html; charset=$context[charset]");

?>
