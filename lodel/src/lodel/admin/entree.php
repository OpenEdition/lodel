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

// gere les entrees. L'acces est reserve au adminlodelistrateur.
// assure l'edition, la supression, la restauration des entrees.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include_once($home."func.php");


$id=intval($id);
$critere=$id>0 ? "id='$id'" : "";

//
// supression et restauration
//
#if ($id>0 && ($delete || $restore)) { 
if ($id>0 && $delete) { 
  $delete=2; // destruction en -64;
  include ($home."trash.php");

  //$result=mysql_query("SELECT 1 FROM $GLOBALS[tp]entrees WHERE $critere") or die (mysql_error());
  //if (!mysql_num_rows($result)) die("ERROR: The 'entree' does not exist or you are not allowed to modify it.");
  // check this "entree" has no children.
  $result=mysql_query("SELECT 1 FROM $GLOBALS[tp]entrees WHERE idparent='$id' AND statut>-64 LIMIT 0,1") or die (mysql_error());
  if (mysql_num_rows($result)) die("ERROR: The 'entree' has children. Delete them first.");

  mysql_query("DELETE FROM $GLOBALS[tp]entites_entrees WHERE identree='$id'") or die (mysql_error());
  deleteuniqueid($id);
  treattrash("entrees",$critere);
  return;
}

//
// ordre
//
if ($id>0 && $dir) {
  include_once($home."connect.php");
  # cherche le parent
  $result=mysql_query ("SELECT idparent,idtype FROM $GLOBALS[tp]entrees WHERE $critere") or die (mysql_error());
  list($idparent,$idtype)=mysql_fetch_row($result);
  chordre("entrees",$id,"idparent='$idparent' AND idtype='$idtype' AND statut>-64",$dir);
  back();
}


//
// ajoute ou edit
//
if ($edit) { // modifie ou ajoute
  extract_post();
  // validation
  do {
    if (!$context[nom]) $err=$context[erreur_nom]=1;
    if ($err) break;
    include_once ($home."connect.php");

    lock_write("objets","entrees");
    $idparent=intval($context[idparent]);
    if ($id>0) { // il faut rechercher le statut, le type et l'ordre
      $result=mysql_query("SELECT statut,idtype,ordre FROM $GLOBALS[tp]entrees WHERE id='$id'") or die (mysql_error());
      list($statut,$context[idtype],$ordre)=mysql_fetch_row($result);
    } else {
      $statut=1;
      if (!$context[idtype]) die ("Erreur interne. Il manque le type dans le formulaire");
      $context[idtype]=intval($context[idtype]);
      $ordre=get_ordre_max("entrees"," idparent='$idparent' AND idtype='$context[idtype]'");
      $id=uniqueid("entrees");
    }
    $newstatut=$protege ? 32 : 1;
    $statut=$statut>0 ? $newstatut : -$newstatut;

    mysql_query ("REPLACE INTO $GLOBALS[tp]entrees (id,idparent,nom,abrev,ordre,langue,statut,idtype) VALUES ('$id','$idparent','$context[nom]','$context[abrev]','$ordre','$context[lang]','$statut','$context[idtype]')") or die (mysql_error());

    touch(SITEROOT."CACHE/maj");
    unlock();
    back();

  } while (0);
  // entre en edition
} elseif ($id>0) {
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]entrees WHERE $critere") or die (mysql_error());
  $context=array_merge($context,mysql_fetch_assoc($result));
} else {
  $context[statut]=-32; # valeur par defaut a la creation
}

// cherche le type. As-t-on l'id ou du texte ?
if ($context[idtype]) {
  $critere="id='".intval($context[idtype])."'";
} elseif ($type && preg_match("/[\w-]/",$type)) {
  $critere="type='$type'";
} else die("preciser un type");

include_once($home."connect.php");
$result=mysql_query ("SELECT * FROM $GLOBALS[tp]typeentrees WHERE $critere AND statut>0") or die (mysql_error());
if (!mysql_num_rows($result)) die("type incorrecte ($context[idtype],$type)");
$context= array_merge_withprefix($context,"type_",mysql_fetch_assoc($result));
$context[idtype]=$context[type_id]; // importe l'id du type dans type




// post-traitement
posttraitement($context);

include($home."langues.php");

include ($home."calcul-page.php");
calcul_page($context,"entree");


function make_selection_entree($idparent=0,$rep="")

{
  global $context;

  $result=mysql_query("SELECT nom,id FROM $GLOBALS[tp]entrees WHERE idtype='$context[idtype]' AND idparent='".intval($idparent)."' ORDER BY $context[type_tri]") or die (mysql_error());
  while ($row=mysql_fetch_array($result,MYSQL_ASSOC)) {
    $selected=$row[id]==$context[idparent] ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$row[id]\"$selected>$rep$row[nom]</OPTION>\n";
    make_selection_entree($row[id],"$rep$row[nom]/");
  }

}



?>
