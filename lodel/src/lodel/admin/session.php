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

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include ($home."func.php");

$delete=intval($delete);
$deleteuser=intval($deleteuser);

mysql_select_db($database);
$ids=array();
if ($deleteuser) {
  $result=mysql_query("SELECT id FROM $GLOBALS[tp]session WHERE iduser='$deleteuser'") or die (mysql_error());
  while(list($id)=mysql_fetch_row($result)) { array_push($ids,$id); }
} elseif ($delete) {
  array_push($ids,$delete);
} else {
  die ("ERROR: unknow operation");
}

if ($ids) {
  $idstr=join(",",$ids);
  // remove the session
  mysql_query("DELETE FROM $GLOBALS[tp]session WHERE id IN ($idstr)") or die (mysql_error());
  // remove the url related to the session
  mysql_query("DELETE FROM $GLOBALS[tp]pileurl WHERE idsession IN ($idstr)") or die (mysql_error());
}

mysql_select_db($currentdb);
back();

?>
