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

require("siteconfig.php");
include ($home."auth.php");
authenticate();


if (!$type || !preg_match("/[\w-]/",$type)) die("type incorrecte");

$context[id]=$id=intval($id);

include_once($home."connect.php");
$personnetable="";
$critere=$visiteur ?  "" : "AND statut>0";
$result=mysql_query ("SELECT * FROM $GLOBALS[tp]personnes WHERE id='$id' $critere") or die (mysql_error());
if (!mysql_num_rows($result)) { header("location: not-found.html"); exit(); }
$context=array_merge($context,mysql_fetch_assoc($result));


$result=mysql_query ("SELECT tpl FROM $GLOBALS[tp]typepersonnes WHERE type='$type' AND statut>0") or die (mysql_error());
list($base)=mysql_fetch_row($result);

include ($home."cache.php");

?>
