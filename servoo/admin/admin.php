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

// gere les utilisateurs. L'acces est reserve au adminlodelistrateur.
// assure l'edition, la supression, la restauration des utilisateurs.

require("config.php");
require(TOINCLUDE."auth.php");
authenticate(LEVEL_ADMINLODEL);
require(TOINCLUDE."func.php");

// calcul le critere pour determiner le user a editer, restorer, detruire...
$id=intval($id);

//
// supression et restauration
//
if ($id>0 && $delete) { 
  mysql_query("DELETE FROM $GLOBALS[tp]admins WHERE id='$id'");
  mysql_query("DELETE FROM $GLOBALS[tp]session WHERE iduser='$id'");
  header("Location: admins.php");  
  return;
}


$critere="id='$id' AND status>0";

//
// ajoute ou edit
//

if ($edit) { // modifie ou ajoute

  extract_post();
  // "ms" stands for Manager Safe. Avoid auto-completion.
  $context[name]=$context[namems];
  $context[passwd]=$context[passwdms];

  // validation
  do {
    $len=strlen($context[name]);
    if ($len<3 || $len>10 || !preg_match("/^[0-9A-Za-z]+$/",$context[name])) { $err=$context[erreur_name]=1; }

    if (!$context[realname]) { $context[erreur_realname]=$err=1; }
    $passwd=$context[passwd];
    if ($passwd || !$id) { // si le pass a ete modifie
      $len=strlen($passwd);
      if ($len<3 || $len>10) { $err=$context[erreur_passwd]=1; }
    }

    // verifie le email
    if ($context[email] && !ereg(".*\@[^\.]*\..*",$context[email])) { $context[erreur_email]=$err=1; }// repris de SPIP
      
    if ($err) break;

    mysql_connect($dbhost,$dbusername,$dbpasswd) or die (mysql_error());
    mysql_select_db($database)  or die (mysql_error());

    // cherche si le name existe deja
    $result=mysql_query("SELECT id FROM $GLOBALS[tp]admins WHERE name='$context[name]' AND id!='$id'") or die (mysql_error());  
    if (mysql_num_rows($result)>0) { $context[erreur_dupname]=$err=1; }

    if ($context[rights]>$adminrights) { $err=1; } // securite

    if ($err) break;

    if ($id>0) { // il faut rechercher le statut et (peut etre) le passwd
      $result=mysql_query("SELECT passwd,status FROM $GLOBALS[tp]admins WHERE id='$id'") or die (mysql_error());
      list($passwd_db,$status)=mysql_fetch_array($result);
    } else {
      $status=1;
    }
    if (!$passwd) { // pas de passwd... on prend celui de la base de donnee
      $passwd=$passwd_db;
    } else { // on encrypte le passwd
      $passwd=md5($context[passwd].$context[name]);
    }

    mysql_query ("REPLACE INTO $GLOBALS[tp]admins (id,name,passwd,realname,email,rights,status) VALUES ('$id','$context[name]','$passwd','$context[realname]','$context[email]','$context[rights]','$status')") or die (mysql_error());

    header("Location: admins.php");
  } while (0);
  // entre en edition
} elseif ($id>0) {
  $id=intval($id);
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]admins WHERE $critere") or die (mysql_error());
  //$context=mysql_fetch_assoc($result);
  $context[name]="";
  $context=array_merge($context,mysql_fetch_assoc($result));
  $context[namems]=$context[name];
}


// post-traitement
posttraitement($context);


$context[passwd]="";


require(TOINCLUDE."calcul-page.php");
calcul_page($context,"admin");




function makeselectrights()

{
  global $context,$adminpriv;
  $arr=array(LEVEL_VISITEUR=>"Visiteur",
	     LEVEL_ADMIN=>"Administrateur",
	     LEVEL_ADMINLODEL=>"Administrateur Principal",
	     );

  foreach ($arr as $k=>$v) {
    $selected=$context[rights]==$k ? "selected" : "";
    echo "<option value=\"$k\" $selected>$v</option>\n";
  }
}


?>
