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

// charge le fichier xml et
if (!function_exists("authenticate")) {
  require("siteconfig.php");
  require_once($home."auth.php");
  authenticate();
}

$id=intval($id);

require_once($home."connect.php");

$critere=$user['visitor'] ? "" : "AND $GLOBALS[tp]entities.status>0 AND $GLOBALS[tp]types.status>0";

$result=mysql_query("SELECT lien,type FROM $GLOBALS[documentstypesjoin] WHERE $GLOBALS[tp]entities.id='$id' $critere AND type LIKE 'documentannexe-%'") or dberror();
if (mysql_num_rows($result)<1) { header ("Location: not-found.html"); return; }
list($lien,$type)=mysql_fetch_row($result);

if ($type=="documentannexe-lienfichier") {
  require_once($home."func.php");
  download($lien);
  return;
} else {
  header("location: $lien");
  return;
}

?>
