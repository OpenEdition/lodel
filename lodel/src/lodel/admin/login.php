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


require("siteconfig.php");
include ($home."auth.php");

// timeout pour les cookies
$cookietimeout=4*3600;


if ($login) {
  include_once($home."func.php");
  extract_post();
  do {
    include_once ($home."connect.php");
    if (!check_auth(&$site)) {
      $context[erreur_login]=1; break; 
    }
    // ouvre une session

    // context
    $contextstr=serialize(array("userpriv"=>intval($userpriv),"usergroupes"=>$usergroupes,"username"=>$context[login]));
    $expire=time()+$timeout;
    $expire2=time()+$cookietimeout;

    mysql_select_db($database);
    if ($userpriv<LEVEL_ADMINLODEL) {
      lock_write("sites","session"); // seulement session devrait etre locke en write... mais c'est pas hyper grave vu le peu d'acces sur site.
      // verifie que c'est ok
      $result=mysql_query("SELECT 1 FROM $GLOBALS[tp]sites WHERE rep='$site' AND statut>=32") or die(mysql_error());
      if (mysql_num_rows($result)) { $context[erreur_sitebloque]=1; unlock(); break; }
    }

    for ($i=0; $i<5; $i++) { // essaie cinq fois, au cas ou on ait le meme nom de session
      // nom de la session
      $name=md5($context[login].microtime());
      // enregistre la session, si ca marche sort de la boucle
      if (mysql_query("INSERT INTO $GLOBALS[tp]session (name,iduser,site,context,expire,expire2) VALUES ('$name','$iduser','$site','$contextstr','$expire','$expire2')")) break;
    }
    unlock();
    if ($i==5) { $context[erreur_opensession]=1; break; }

    if (!setcookie($sessionname,$name,time()+$cookietimeout,$urlroot)) die("Probleme avec setcookie... probablement du texte avant");

    header ("Location: http://$SERVER_NAME$url_retour");
    die ("$url_retour");
  } while (0);
}

$context[passwd]=$passwd=0;


// variable: sitebloque
if ($context[erreur_sitebloque]) { // on a deja verifie que la site est bloque.
  $context[sitebloque]=1;
} else { // test si la site est bloque dans la DB.
  include_once ($home."connect.php");
  mysql_select_db($database);
  $result=mysql_query("SELECT 1 FROM $GLOBALS[tp]sites WHERE rep='$site' AND statut>=32") or die(mysql_error());
  $context[sitebloque]=mysql_num_rows($result);
}


$context[url_retour]=$url_retour;
$context[erreur_timeout]=$erreur_timeout;
$context[erreur_privilege]=$erreur_privilege;



include ($home."calcul-page.php");
calcul_page($context,"login");



function check_auth (&$site)

{
  global $context,$iduser,$userpriv,$usergroupes;

  do { // block de control
    if (!$context[login] || !$context[passwd]) break;

    $user=addslashes($context[login]);
    $pass=md5($context[passwd].$context[login]);

    // cherche d'abord dans la base generale.

    mysql_select_db($GLOBALS[database]);
    $result=mysql_query ("SELECT id,statut,privilege FROM $GLOBALS[tp]users WHERE username='$user' AND passwd='$pass' AND statut>0")  or die(mysql_error());
    if ($row=mysql_fetch_assoc($result)) {
      // le user est dans la base generale
      $site="tous les sites";
     } elseif ($GLOBALS[currentdb]!=$GLOBALS[database]) { // le user n'est pas dans la base generale
      if (!$site) break; // si $site n'est pas definie on s'ejecte

      // cherche ensuite dans la base du site
      mysql_select_db($GLOBALS[currentdb]);
      $result=mysql_query ("SELECT id,statut,privilege FROM $GLOBALS[tp]users WHERE username='$user' AND passwd='$pass' AND statut>0")  or die(mysql_error());
      if (!($row=mysql_fetch_assoc($result))) break;
     } else {
       break; // on s'eject
     }
    // pass les variables en global
    $userpriv=$row[privilege];
    $context[iduser]=$iduser=$row[id];

    // cherche les groupes pour les non administrateurs
    if ($userpriv<LEVEL_ADMIN) {
      $result=mysql_query("SELECT idgroupe FROM $GLOBALS[tp]users_groupes WHERE iduser='$iduser'") or die(mysql_error());
      $usergroupes="1"; // sont tous dans le groupe "tous"
      while ($row=mysql_fetch_row($result)) $usergroupes.=",".$row[0];
    } else {
      $usergroupes="";
    }
    $context[usergroupes]=$usergroupes;

    // efface les donnees de la memoire et protege pour la suite
    $context[passwd]=0;

    return true;
  } while (0);

  return false;
}
?>
