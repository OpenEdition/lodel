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
  require_once("auth.php");
  authenticate();
}

if (!$type || !preg_match("/[\w-]/",$type)) die("ERROR: unknow type '$type'");

$context[id]=$id=intval($id);
$context[type]=$type;

include_once("connect.php");
$personnetable="";
$critere=$user['visitor'] ?  "" : "AND status>0";
$result=mysql_query ("SELECT * FROM $GLOBALS[tp]persons WHERE id='$id' $critere") or dberror();
if (!mysql_num_rows($result)) { header("location: not-found.html"); exit(); }
$context=array_merge($context,mysql_fetch_assoc($result));


$result=mysql_query ("SELECT tpl FROM $GLOBALS[tp]persontypes WHERE type='$type' AND status>0") or dberror();
list($base)=mysql_fetch_row($result);

include ($home."cache.php");

?>
