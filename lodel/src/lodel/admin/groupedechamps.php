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

// gere les champs. L'acces est reserve au adminlodelistrateur.
// assure l'edition, la supression, la restauration des champs.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include_once($home."func.php");


$id=intval($id);
$critere=$id>0 ? "id='$id'" : "";



//
// ordre
//
if ($id>0 && $dir) {
  include_once($home."connect.php");
  # cherche le groupe
  $result=mysql_query ("SELECT classe FROM $GLOBALS[tp]groupesdechamps WHERE $critere") or die (mysql_error());
  list($classe)=mysql_fetch_row($result);
  chordre("groupesdechamps",$id,"classe='$classe' AND statut>-64",$dir);
  back();
}

if ($id && !$droitadminlodel) $critere.=" AND $GLOBALS[tp]champs.statut<32";

//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  $result=mysql_query("SELECT 1 FROM $GLOBALS[tp]champs WHERE idgroupe='$id'") or die (mysql_error());
  if (mysql_num_rows($result)) die("ERROR: the field group is not empty. Clear it before deletion");
  $delete=2; // destruction en -64;
  include ($home."trash.php");
  treattrash("groupesdechamps",$critere);
  return;
}

//
// ajoute ou edit
//
if ($edit) { // modifie ou ajoute
  extract_post();
  // validation
  do {
    if (!$context[nom]) $err=$context[erreur_nom]=1;
    if (!$context[titre]) $err=$context[erreur_titre]=1;
    if ($err) break;
    include_once ($home."connect.php");

    if ($id>0) { // il faut rechercher le statut et l'ordre
      $result=mysql_query("SELECT statut,ordre,classe FROM $GLOBALS[tp]groupesdechamps WHERE $critere") or die (mysql_error());
      if (!mysql_num_rows($result)) die("ERROR: The field group does not exist or you are not allowed to modify it.");
      list($statut,$ordre,$classe)=mysql_fetch_array($result);
    } else {
      $statut=1;
      if (!$context[classe]) die ("Erreur interne. Il manque la classe dans le formulaire");
      $ordre=get_ordre_max("groupesdechamps"," classe='$context[classe]'");
    }
    if ($droitadminlodel) {
      $newstatut=$protege ? 32 : 1;
      $statut=$statut>0 ? $newstatut : -$newstatut;    
    }

    mysql_query ("REPLACE INTO $GLOBALS[tp]groupesdechamps (id,nom,titre,classe,commentaire,ordre,statut) VALUES ('$id','$context[nom]','$context[titre]','$context[classe]','$context[commentaire]','$ordre','$statut')") or die (mysql_error());
    back();
  } while (0);
  // entre en edition
} elseif ($id>0) {
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]groupesdechamps WHERE $critere AND statut>-64") or die (mysql_error());
  if (!mysql_num_rows($result)) die("ERROR: The field does not exist or you are not allowed to modify it.");
  $context=array_merge(mysql_fetch_assoc($result),$context);
} else {
  // cherche le classe.
  if ($classe && preg_match("/[\w-]/",$classe)) {
    $context[classe]=$classe;
  } else die("ERROR: Invalid class passed as argument");
}


// post-traitement
posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"groupedechamps");


?>
