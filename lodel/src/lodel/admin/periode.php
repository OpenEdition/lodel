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

die("fichier a supprimer du CVS");

// gere les periodes. L'acces est reserve au adminlodelistrateur.
// assure l'edition, la supression, la restauration des periodes.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include_once($home."func.php");

$type=TYPE_PERIODE;

include("indexh.php");



function make_selection_periode($parent=0,$rep="")

{
  global $context;

  $result=mysql_query("SELECT nom,id FROM indexhs WHERE type='".TYPE_PERIODE."' AND parent='$parent' ORDER BY ordre") or die (mysql_error());
  while ($row=mysql_fetch_array($result,MYSQL_ASSOC)) {
    $selected=$row[id]==$context[parent] ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$row[id]\"$selected>$rep$row[nom]</OPTION>\n";
    make_selection_periode($row[id],"$rep$row[nom]/");
  }

}


include ($home."calcul-page.php");
calcul_page($context,"periode");

?>
