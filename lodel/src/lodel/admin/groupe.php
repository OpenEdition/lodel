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

// gere les groupe. L'acces est reserve au administrateur.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include_once($home."func.php");


$id=intval($id);

if ($id==1) back(); // on ne modifie ni n'efface le groupe tous !

$critere="id='$id'";

//
// ajoute ou edit
//
//
// supression
//
if ($id>0 && $delete>=2) {
  lock_write("groupes","users_groupes","publications","documents");
  do {
    // verifie qu'il n'y a pas de publi ou de documents qui ont ce groupe
    mysql_query("SELECT id FROM $GLOBALS[tp]publications WHERE groupe='$id'") or die (mysql_error());
    if (mysql_num_rows($result)) { $context[erreur_publications_exist]=$err=1; }
    mysql_query("SELECT id FROM $GLOBALS[tp]documents WHERE groupe='$id'") or die (mysql_error());
    if (mysql_num_rows($result)) { $context[erreur_documents_exist]=$err=1; }
    if ($err) break;

    mysql_query("DELETE FROM $GLOBALS[tp]groupes WHERE id='$id'") or die (mysql_error());
    mysql_query("DELETE FROM $GLOBALS[tp]users_groupes WHERE idgroupe='$id'") or die (mysql_error());
    back();
  } while(0);
}

if ($edit && !$delete) { // modifie ou ajoute
  extract_post();
  // validation
  do {
    if (!$context[nom]) $err=$context[erreur_nom]=1;
    if ($err) break;
    include_once ($home."connect.php");

    mysql_query ("REPLACE INTO $GLOBALS[tp]groupes (id,nom) VALUES ('$id','$context[nom]')") or die (mysql_error());

    back();

  } while (0);
  // entre en edition
} elseif ($id>0) {
  $id=intval($id);
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]groupes WHERE $critere") or die (mysql_error());
  $context=array_merge($context,mysql_fetch_assoc($result));
}
$context[delete]=$delete;

// post-traitement
posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"groupe");


?>
