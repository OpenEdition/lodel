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



function open_session ($login) {

  global $userpriv,$usergroupes,$userlang,$sessionname,$timeout,$cookietimeout;
  global $database,$urlroot,$site,$iduser;

  // timeout pour les cookies
  if (!$cookietimeout) $cookietimeout=4*3600; // to ensure compatibility


  // context
  $contextstr=addslashes(serialize(array("userpriv"=>intval($userpriv),"usergroupes"=>$usergroupes,"userlang"=>$userlang,"username"=>$login)));
  $expire=time()+$timeout;
  $expire2=time()+$cookietimeout;

  mysql_select_db($database);
  if (defined("LEVEL_ADMINLODEL") && $userpriv<LEVEL_ADMINLODEL) {
    if (function_exists("lock_write")) lock_write("sites","session"); // seulement session devrait etre locke en write... mais c'est pas hyper grave vu le peu d'acces sur site.
    // verifie que c'est ok
    $result=mysql_query("SELECT 1 FROM $GLOBALS[tableprefix]sites WHERE rep='$site' AND statut>=32") or die(mysql_error());
    if (mysql_num_rows($result)) { 
      if (function_exists("unlock")) unlock(); 
      return "erreur_sitebloque"; 
    }
  }

  for ($i=0; $i<5; $i++) { // essaie cinq fois, au cas ou on ait le meme nom de session
    // nom de la session
    $name=md5($login.microtime());
    // enregistre la session, si ca marche sort de la boucle
    if (mysql_query("INSERT INTO $GLOBALS[tableprefix]session (name,iduser,site,context,expire,expire2) VALUES ('$name','$iduser','$site','$contextstr','$expire','$expire2')")) break;
  }
  if (function_exists("unlock")) unlock(); 
  if ($i==5) return "erreur_opensession";

  if (!setcookie($sessionname,$name,time()+$cookietimeout,$urlroot)) die("Probleme avec setcookie... probablement du texte avant");
}


function check_auth ($login,&$passwd,&$site)

{
  global $context,$iduser,$userpriv,$usergroupes;

  do { // block de control
    if (!$login || !$passwd) break;

    $user=addslashes($login);
    $pass=md5($passwd.$login);

    // cherche d'abord dans la base generale.

    mysql_select_db($GLOBALS[database]);
    $result=mysql_query ("SELECT * FROM $GLOBALS[tableprefix]users WHERE username='$user' AND passwd='$pass' AND statut>0")  or die(mysql_error());
    if ($row=mysql_fetch_assoc($result)) {
      // le user est dans la base generale
      $site="tous les sites";
     } elseif ($GLOBALS[currentdb] && $GLOBALS[currentdb]!=$GLOBALS[database]) { // le user n'est pas dans la base generale
      if (!$site) break; // si $site n'est pas definie on s'ejecte

      // cherche ensuite dans la base du site
      mysql_select_db($GLOBALS[currentdb]);
      $result=mysql_query ("SELECT id,statut,privilege FROM $GLOBALS[tableprefix]users WHERE username='$user' AND passwd='$pass' AND statut>0")  or die(mysql_error());
      if (!($row=mysql_fetch_assoc($result))) break;
     } else {
       break; // on s'eject
     }
    // pass les variables en global
    $userpriv=$row['privilege'];
    $userlang=$row['lang'];
    $context['iduser']=$iduser=$row['id'];

    // cherche les groupes pour les non administrateurs
    if (defined("LEVEL_ADMIN") && $userpriv<LEVEL_ADMIN) { // defined is useful only for the install.php
      $result=mysql_query("SELECT idgroupe FROM $GLOBALS[tableprefix]users_groupes WHERE iduser='$iduser'") or die(mysql_error());
      $usergroupes="1"; // sont tous dans le groupe "tous"
      while ($row=mysql_fetch_row($result)) $usergroupes.=",".$row[0];
    } else {
      $usergroupes="";
    }
    $context['usergroupes']=$usergroupes;

    // efface les donnees de la memoire et protege pour la suite
    $passwd=0;

    return true;
  } while (0);

  return false;
}


?>