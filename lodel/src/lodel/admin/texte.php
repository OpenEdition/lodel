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

// gere les periodes. L'acces est reserve au adminlodelistrateur.
// assure l'edition, la supression, la restauration des periodes.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ($home."func.php");

// calcul le critere pour determiner le texte a editer, restorer, detruire...
$id=intval($id);
if ($id>0) {
  $critere="id='$id'";
} else $critere="id='$id'";
if (!$restore) $critere.=" AND statut>0";

if ($id>0 && ($delete || $restore)) { 
  include ($home."trash.php");
  treattrash("textes",$critere);
  return;
//
// ajoute ou edit
//
} elseif ($edit) { // modifie ou ajoute
  include_once ($home."func.php");
  extract_post();
  // validation
  do {
    if (!$context[nom] || !preg_match("/^[\w\s]+$/",utf8_decode($context[nom]))) $err=$context[erreur_nom]=1;
    if ($err) break;

    include_once ($home."connect.php");
    $result=mysql_query ("SELECT id FROM $GLOBALS[tp]textes WHERE nom='$context[nom]' AND id!='$id'") or die (mysql_error());
    if (mysql_num_rows($result)>0) $err=$context[erreur_nom_existe]=1;
    if ($err) break;
    

    mysql_query ("REPLACE INTO $GLOBALS[tp]textes (id,nom,texte) VALUES ('$id','$context[nom]','$context[texte]')") or die (mysql_error());

    back();

  } while (0);
  // entre en edition
} elseif ($id>0) {
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]textes WHERE $critere") or die ("erreur SELECT");
  $context=array_merge($context,mysql_fetch_assoc($result));
}

// post-traitement
$context[id]=$id;
posttraitement($context);


include ($home."calcul-page.php");
calcul_page($context,"texte");

?>





