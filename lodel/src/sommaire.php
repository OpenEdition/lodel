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
require_once($home."func.php");


$context[id]=$id=intval($id);


// cherche le sommaire precedent et le suivant
include_once($home."connect.php");


$critere=$rightvisitor ? " AND $GLOBALS[tp]entities.status>=-1" : " AND $GLOBALS[tp]entities.status>0";
$critere.=" AND $GLOBALS[tp]types.status>0";
// cherche la publication
$relocation=FALSE;
$base="";


if (!(@include_once("CACHE/filterfunc.php"))) require_once($GLOBALS[home]."filterfunc.php");

if ($id || $identifier) {
  require_once($home."textfunc.php");
  do {
    if ($identifier) {
      $identifier=addslashes(stripslashes($identifier));
      $where="$GLOBALS[tp]entities.identifier='$identifier' ".$critere;
    } else {
      $where="$GLOBALS[tp]entities.id='$id' ".$critere;
    }

    $result=mysql_query("SELECT $GLOBALS[tp]publications.*,$GLOBALS[tp]entities.*,tpl,type FROM $GLOBALS[publicationstypesjoin] WHERE $where") or die($db->errormsg());
    if (mysql_num_rows($result)<1) { header ("Location: not-found.html"); return; }
    $row=filtered_mysql_fetch_assoc($context,$result);
    $base=$row[tpl];
    if (!$base) { $id=$row[idparent]; $relocation=TRUE; }
  } while (!$base && !$identifier);
  if ($relocation) { 
    header("location: ".makeurlwithid("sommaire",$row[id]));
    return;
  }
  $context=array_merge($context,$row);
} else {
  $base="sommaire";
  if (!file_exists("tpl/".$base.".html")) {
    header("Location: index.$GLOBALS[extensionscripts]");
    return;
  }
}


include ($home."cache.php");


?>
