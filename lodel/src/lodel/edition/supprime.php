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

// suppression de documents et de publication en assurant la coherence de la base

require("siteconfig.php");
require ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
require_once ($home."func.php");


if ($publication) die("Changer le template... remplacer publication par id");
$id=intval($id);
if (!$id) die("ERROR: invalid id of the \"entitie\" to delete");

if ($supprime || $confirmation) {
  include_once ($home."connect.php");
  include ($home."managedb.php");

  if (!supprime($id,$confirmation)) {
    $context[id]=$id;
    // post-traitement
    posttraitement($context);
    
    include ($home."calcul-page.php");
    calcul_page($context,"supprime_erreur");
    return;
  }
  touch(SITEROOT."CACHE/maj");
  back();
  return;
}

posttraitement($context);


$result=mysql_query("SELECT * FROM $GLOBALS[entitestypesjoin] WHERE $GLOBALS[tp]entites.id='$id'") or die (mysql_error());
if (mysql_num_rows($result)<=0) { header("location: not-found.html"); }
$context=array_merge($context,mysql_fetch_assoc($result));

$result=mysql_query("SELECT * FROM $GLOBALS[tp]$context[classe] WHERE identite='$id'") or die (mysql_error());
$context=array_merge($context,mysql_fetch_assoc($result));



include ($home."calcul-page.php");
calcul_page($context,"supprime");

?>
