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

// gere les utilisateurs. L'acces est reserve au adminlodelistrateur.
// assure l'edition, la supression, la restauration des utilisateurs.

die("desuet");

if (!function_exists("authenticate")) die("ERROR: invalid include of userinc.php");
if ($userrights<LEVEL_ADMIN) return; // secu
require_once("func.php");


// calcul le critere pour determiner le user a editer, restorer, detruire...
$id=intval($id);

//
// supression et restauration
//
if ($id>0 && ($delete | $restore)) { 
  if ($delete>=2) {
    mysql_query ("DELETE FROM $GLOBALS[tp]users_usergroups WHERE iduser='$id'") or dberror();
  }

  require("trash.php");
  treattrash("users");
  return;
}


$critere="id='$id' AND status>0";

//
// ajoute ou edit
//

if ($edit) { // modifie ou ajoute

  extract_post();
  // "ms" stands for Manager Safe. Avoid auto-completion.
  $context[username]=$context[usernamems];
  $context[passwd]=$context[passwdms];

  // validation
  do {
    $len=strlen($context[username]);
    if ($len<3 || $len>10 || !preg_match("/^[0-9A-Za-z]+$/",$context[username])) { $err=$context[error_username]=1; }

    if (!$context[name]) { $context[error_nom]=$err=1; }
    $passwd=$context[passwd];
    if ($passwd || !$id) { // si le pass a ete modifie
      $len=strlen($passwd);
      if ($len<3 || $len>10) { $err=$context[error_passwd]=1; }
    }

    // verifie le courriel
    if ($context[courriel] && !ereg(".*\@[^\.]*\..*",$context[courriel])) { $context[error_courriel]=$err=1; }// repris de SPIP
      
    if (!$groupes || !is_array($groupes)) { $context[error_groupes]=$err=1; }
 
    if ($err) break;
    include_once("connect.php");

    // cherche si le username existe deja
    $result=mysql_query("SELECT id FROM $GLOBALS[tp]users WHERE username='$context[username]' AND id!='$id'") or dberror();  
    if (mysql_num_rows($result)>0) { $context[error_dupusername]=$err=1; }
    if ($GLOBALS[database]!=$GLOBALS[currentdb]) {
      // cherche si le username existe deja
      $result=mysql_query("SELECT id FROM $GLOBALS[database].$GLOBALS[tp]users WHERE username='$context[username]' AND id!='$id'") or dberror();  
      if (mysql_num_rows($result)>0) { $context[error_dupusernameadmin]=$err=1; }
    }

    if ($context[userrights]>$userrights) { $err=1; } // securite

    if ($err) break;

    if ($id>0) { // il faut rechercher le status et (peut etre) le passwd
      $result=mysql_query("SELECT passwd,status FROM $GLOBALS[tp]users WHERE id='$id'") or dberror();
      list($passwd_db,$status)=mysql_fetch_array($result);
    } else {
      $status=1;
    }
    if (!$passwd) { // pas de passwd... on prend celui de la base de donnee
      $passwd=$passwd_db;
    } else { // on encrypte le passwd
      $passwd=md5($context[passwd].$context[username]);
    }

    mysql_query ("REPLACE INTO $GLOBALS[tp]users (id,username,passwd,name,courriel,userrights,lang,status) VALUES ('$id','$context[username]','$passwd','$context[name]','$context[courriel]','$context[userrights]','$context[lang]','$status')") or dberror();

    if ($context[userrights]<LEVEL_ADMIN) {
      if (!$id) $id=mysql_insert_id();

      // change les groupes
      mysql_query("DELETE FROM $GLOBALS[tp]users_usergroups WHERE iduser='$id'") or dberror();
      foreach ($groupes as $groupe) {
	mysql_query("INSERT INTO $GLOBALS[tp]users_usergroups (idgroup, iduser) VALUES  ('$groupe','$id')") or dberror();
      }
    }

    back();
  } while (0);
  // entre en edition
} elseif ($id>0) {
  include_once("connect.php");
  $id=intval($id);
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]users WHERE $critere") or dberror();
  //$context=mysql_fetch_assoc($result);
  $context[username]="";
  $context=array_merge($context,mysql_fetch_assoc($result));
  $context[usernamems]=$context[username];
}

require_once("langues.php");



// post-traitement
postprocessing($context);


$context[passwd]="";



function makeselectprivilege()

{
  global $context,$userrights;
  $arr=array(LEVEL_VISITOR=>"Visiteur",
	     LEVEL_REDACTOR=>"R&eacute;dacteur",
	     LEVEL_EDITOR=>"Editeur",
	     LEVEL_ADMIN=>"Administrateur",
	     );

  foreach ($arr as $k=>$v) {
    $selected=$context[userrights]==$k ? "selected" : "";
    echo "<option value=\"$k\" $selected>$v</option>\n";
  }
}

function makeselectgroupes() 

{
  global $context,$groupes;

  // cherche les groupes de l'utilisateur
  $groupes=array();
  if ($context[id] && $context[userrights]<LEVEL_ADMIN) {
    $result=mysql_query("SELECT idgroup FROM $GLOBALS[tp]users_usergroups WHERE iduser='$context[id]'") or dberror();
    while ($row=mysql_fetch_row($result)) array_push($groupes,$row[0]);
  }

  // cherche le name des groupes sauf le groupe "tous"
  $result=mysql_query("SELECT id,name FROM $GLOBALS[tp]usergroups WHERE id>1") or dberror();

  while ($row=mysql_fetch_assoc($result)) {
    $selected=in_array($row[id],$groupes) ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$row[id]\"$selected>$row[name]</OPTION>\n";
  }
}

?>
