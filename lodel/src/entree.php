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

if (!function_exists("authenticate")) {
  require("siteconfig.php");
  require_once($home."auth.php");
  authenticate();
}
require_once($home."connect.php");

$context[id]=$id=intval($id);

$entreetable="$GLOBALS[tp]entries";
$typeentreetable="$GLOBALS[tp]entrytypes";

$critere=$rightvisiteur ?  "" : "AND $entreetable.status>0";

include_once($home."connect.php");
$result=mysql_query ("SELECT $entreetable.*, tpl, type FROM $entreetable,$typeentreetable WHERE $entreetable.idtype=$typeentreetable.id  AND $entreetable.id='$id' $critere AND $typeentreetable.status>0") or die (mysql_error());
if (!mysql_num_rows($result)) { header("location: not-found.html"); exit(); }
$context=array_merge($context,mysql_fetch_assoc($result));

#echo $base;
#print_r($context);
$base=$context[tpl];
include ($home."cache.php");

?>
