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


require("config.php");
include (TOINCLUDE."auth.php");

// timeout pour les cookies
$cookietimeout=4*3600;


if ($login) {
  include_once(TOINCLUDE."func.php");
  extract_post();
  do {
    mysql_connect($dbhost,$dbusername,$dbpasswd) or die (mysql_error());
    mysql_select_db($database)  or die (mysql_error());
    if (!check_auth()) {
      $context[erreur_login]=1; break; 
    }
    // ouvre une session

    // context
    $contextstr=serialize(array("adminrights"=>intval($adminrights),"adminname"=>$context[login]));
    $timeout=time()+$logintimeout;
#    echo "set timeout: $timeout ",time();
    $timeout2=time()+$cookietimeout;

    for ($i=0; $i<5; $i++) { // essaie cinq fois, au cas ou on ait le meme nom de session
      // nom de la session
      $name=md5($context[login].microtime());
      // enregistre la session, si ca marche sort de la boucle
      if (mysql_query("INSERT INTO $GLOBALS[tp]session (name,idadmin,context,timeout,timeout2) VALUES ('$name','$idadmin','$contextstr','$timeout','$timeout2')")) { break; }
    }
    if ($i==5) { $context[erreur_opensession]=1; break; }

    if (!setcookie($sessionname,$name,time()+$cookietimeout,$urlroot)) die("Probleme avec setcookie... probablement du texte avant");

    if (!$url_retour) $url_retour="index.php";
    header ("Location: ".$_SERVER['SERVER_NAME']."/".$url_retour);
    die ("::$url_retour");

  } while (0);
}

$context[passwd]=$passwd=0;
$context[url_retour]=$url_retour;
$context[erreur_timeout]=$erreur_timeout;
$context[erreur_rights]=$erreur_rights;


include (TOINCLUDE."calcul-page.php");
calcul_page($context,"login");



function check_auth ()

{
  global $context,$idadmin,$adminrights;

  do { // block de control
    if (!$context[login] || !$context[passwd]) break;

    $admin=addslashes($context[login]);
    $pass=md5($context[passwd].$context[login]);

    // cherche d'abord dans la base generale.

    $result=mysql_query ("SELECT id,status,rights FROM $GLOBALS[tp]admins WHERE name='$admin' AND passwd='$pass' AND status>0")  or die(mysql_error());
    if ($row=mysql_fetch_assoc($result)) {
      // l'admin est dans la base
     } else {
       break; // on s'eject
     }
    // pass les variables en global
    $adminrights=$row[rights];
    $context[idadmin]=$idadmin=$row[id];

    // efface les donnees de la memoire et protege pour la suite
    $context[passwd]=0;

    return true;
  } while (0);

  return false;
}
?>
