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

// securite
if (!function_exists("authenticate") || !$GLOBALS[admin]) return;

// gere les index hierarchiques. L'acces est reserve au administrateur.
// assure l'edition, la supression, la restauration des indexes hierarchiques.

// calcul le critere pour determiner le periode a editer, restorer, detruire...
$id=intval($id);
$critere=$id>0 ? "id='$id'" : "";

//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  include ($home."trash.php");
  treattrash("indexhs",$critere);
  return;
}

//
// ordre
//
if ($id>0 && $dir) {
  include_once($home."connect.php");
  # cherche le parent
  $result=mysql_query ("SELECT parent FROM indexhs WHERE $critere") or die (mysql_error());
  list($parent)=mysql_fetch_row($result);
  chordre("indexhs",$id,"parent='$parent' AND statut>0",$dir);
  back();
}

if (!$type) die("probleme interne contacter Ghislain");
$critere.=" AND type='$type'";

//
// ajoute ou edit
//
if ($edit) { // modifie ou ajoute
  extract_post();
  // validation
  do {
    if (!$context[nom]) $err=$context[erreur_nom]=1;
    if (!$context[abrev]) $err=$context[erreur_abrev]=1;
    if ($err) break;
    include_once ($home."connect.php");

    $parent=intval($context[parent]);
    if ($id>0) { // il faut rechercher le statut, le type et l'ordre
      $result=mysql_query("SELECT statut,type,ordre FROM indexhs WHERE id='$id'") or die (mysql_error());
      list($statut,$type,$ordre)=mysql_fetch_array($result);
    } else {
      $statut=1;
      $ordre=get_ordre_max("indexhs"," parent='$parent' AND type='$type'");
    }

    mysql_query ("REPLACE INTO indexhs (id,parent,nom,abrev,ordre,lang,statut,type) VALUES ('$id','$parent','$context[nom]','$context[abrev]','$ordre','$context[lang]','$statut','$type')") or die (mysql_error());

    back();

  } while (0);
  // entre en edition
} elseif ($id>0) {
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM indexhs WHERE $critere AND statut>-32") or die ("erreur SELECT");
  $context=array_merge(mysql_fetch_assoc($result),$context);
}

// post-traitement
posttraitement($context);

include($home."langues.php");
?>
