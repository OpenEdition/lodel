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

require("siteconfig.php");
require ($home."auth.php");
authenticate(LEVEL_ADMIN);
require_once ($home."func.php");
require_once ($home."optionfunc.php");



if ($set) {
  extract_post();

  // statut less than 0 are internal option.
  $critere=$GLOBALS[droitadminlodel] ? "statut>0" : "statut>0 AND statut<32";

  $result=mysql_query("SELECT id,nom,type FROM $GLOBALS[tp]options WHERE $critere") or die (mysql_error());
  while (list($id,$nom,$type)=mysql_fetch_row($result)) {
    if (!isset($context["option_$nom"])
	|| ($type=="pass" && $context["option_$nom"]=="")) continue;
    $v=$context["option_$nom"];
    switch ($type) {
    case "int": $v=intval($v);
      break;
    }
    mysql_query("UPDATE $GLOBALS[tp]options SET valeur='$v' WHERE id='$id'") or die(mysql_error());
  }
  touch(SITEROOT."CACHE/maj");
  if ($terminer) { header("location: index.php"); return; }
}


posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"options");

function humantype($text)

{
  return $GLOBALS[options_types][$text];
}


function make_select_types($context) {


  foreach ($GLOBALS[options_types] as $key => $value) {
    $key=htmlentities($key);
    $selected=$context[type]==$key ? " selected" : "";
    echo "<option value=\"$key\"$selected>$value</option>\n";
  }
}

?>


