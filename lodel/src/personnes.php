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

if (!function_exists("authenticate")) {
  require_once("siteconfig.php");
  require_once($home."auth.php");
  authenticate();
}
require_once($home."func.php");

$critere="";
if ($id) {
  $id=intval($id);
  $critere="id='$id'";
} elseif ($type) {
  if (!preg_match("/^[\w-]*$/",$type)) die("type incorrecte");
  $critere="type='$type'";
} else die("argument ?");


if ($suffix && !preg_match("/^[\w-]+$/",$suffix)) die("suffix non accepte");

include_once($home."connect.php");
$result=mysql_query ("SELECT * FROM $GLOBALS[tp]typepersonnes WHERE $critere AND statut>0") or die (mysql_error());
$context=array_merge_withprefix($context,"type_",mysql_fetch_assoc($result));
$context[idtype]=$context[type_id]; // import
$context[type]=$context[type_type]; // import
$context[type_tri]=$GLOBALS[tp]."personnes.".$context[type_tri];  // prefix par la table... ca aide

$base=$context[type_tplindex].$suffix;
include ($home."cache.php");


function loop_alphabet(&$context,$funcname)

{
  for($l="A"; $l!="AA"; $l++) {
    $context[lettre]=$l;
    call_user_func("code_do_$funcname",$context);
  }
}

?>
