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

function typetypes_delete($critere)

{
  mysql_query("DELETE FROM $GLOBALS[tp]typeentites_typeentrees WHERE $critere") or die (mysql_error());
  mysql_query("DELETE FROM $GLOBALS[tp]typeentites_typepersonnes WHERE $critere") or die (mysql_error());
  mysql_query("DELETE FROM $GLOBALS[tp]typeentites_typeentites WHERE $critere") or die (mysql_error());
}

function typetype_delete($typetable,$critere)

{
  mysql_query("DELETE FROM $GLOBALS[tp]typeentites_".$typetable."s WHERE $critere") or die (mysql_error());
}

function typetype_insert($idtypeentite,$idtypetable,$typetable)

{
  // l'un ou l'autre des idtype doit etre un array, l'autre est un id fixe.
  //
  if (!$idtypeentite || !$idtypetable) return;

  $values=array();
  if (is_array($idtypetable)) {
    foreach($idtypetable as $idtype=>$cond) {
      array_push($values,"('$idtypeentite','$idtype','*')");
    }
  } else {
    foreach($idtypeentite as $idtype=>$cond) {
      array_push($values,"('$idtype','$idtypetable','*')");
    }
  }
  $table=$typetable!="typeentite2" ? $typetable : "typeentite";

  mysql_query("INSERT INTO $GLOBALS[tp]typeentites_".$table."s (idtypeentite,id$typetable,condition) VALUES ".join(",",$values)) or die(mysql_error());
}




////////////////////////////////////////////////



function loop_typetable ($listtype,$criteretype,$context,$funcname)

{
  if ($listtype=="typeentite" || $listtype=="typeentite2") {
    $maintable="types";
    $ordre="classe,type";
    $relationtable=$criteretype;
  } else {
    $maintable=$listtype."s";
    $relationtable=$listtype;
    $ordre="type";
  }
  #if ($relationtable=="typeentite2") $relationtable="typeentite";

  $result=mysql_query("SELECT * FROM $GLOBALS[tp]$maintable LEFT JOIN $GLOBALS[tp]typeentites_".$relationtable."s ON id$listtype=$GLOBALS[tp]$maintable.id AND id$criteretype='$context[id]' WHERE statut>0 ORDER BY $ordre") or die(mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    $localcontext=array_merge($context,$row);
    call_user_func("code_do_$funcname",$localcontext);
  }
}


?>
