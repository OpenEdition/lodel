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
require("auth.php");
authenticate(LEVEL_ADMIN);
require_once("func.php");
require_once("optionfunc.php");



if ($set) {
  extract_post();

  // status less than 0 are internal option.
  $critere=$GLOBALS['lodeluser']['adminlodel'] ? "status>0" : "status>0 AND status<32";

  $result=mysql_query("SELECT id,name,type FROM $GLOBALS[tp]options WHERE $critere") or dberror();
  while (list($id,$name,$type)=mysql_fetch_row($result)) {
    if (!isset($context["option_$name"])
	|| ($type=="pass" && $context["option_$name"]=="")) continue;
    $v=$context["option_$name"];
    switch ($type) {
    case "int": $v=intval($v);
      break;
    }
    mysql_query("UPDATE $GLOBALS[tp]options SET value='$v' WHERE id='$id'") or dberror();
  }
  touch(SITEROOT."CACHE/maj");
  if ($terminer) { header("location: index.php"); return; }
}


postprocessing($context);

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


