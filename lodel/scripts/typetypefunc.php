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


  //
  //
  // Function to be rewritten in an Object framework: NN-relationship.
  //

function typetype_delete($typetable,$critere)

{
  global $db;
  $db->execute(lq("DELETE FROM #_TP_entitytypes_".$typetable."s WHERE $critere")) or die($db->errormsg());
}

function typetype_insert($identitytype,$idtypetable,$typetable)

{
  global $db;
  // l'un ou l'autre des idtype doit etre un array, l'autre est un id fixe.
  //
  if (!$identitytype || !$idtypetable) return;

  $values=array();
  if (is_array($idtypetable)) {
    foreach($idtypetable as $idtype=>$cond) {
      array_push($values,"('$identitytype','$idtype','*')");
    }
  } else {
    foreach($identitytype as $idtype=>$cond) {
      array_push($values,"('$idtype','$idtypetable','*')");
    }
  }
  $table=$typetable!="entitytype2" ? $typetable : "entitytype";

  $db->execute(lq("INSERT INTO #_TP_entitytypes_".$table."s (identitytype,id$typetable,condition) VALUES ".join(",",$values))) or die($db->errormsg());
}




////////////////////////////////////////////////



function loop_typetable ($listtype,$criteretype,$context,$funcname,$checked=-1)

{
  global $db;
  if ($listtype=="entitytype" || $listtype=="entitytype2") {
    $maintable="types";
    $rank="class,type";
    $relationtable=$criteretype;
  } else {
    $maintable=$listtype."s";
    $relationtable=$listtype;
    $rank="type";
  }
  
  $result=$db->execute(lq("SELECT * FROM #_TP_$maintable LEFT JOIN #_TP_entitytypes_".$relationtable."s ON id$listtype=#_TP_$maintable.id AND id$criteretype='$context[id]' WHERE status>0 ORDER BY $rank")) or die($db->errormsg());

  while (!$result->EOF) {
    $localcontext=array_merge($context,$result->fields);
    if (is_array($checked)) {
      $localcontext['value']=$checked[$result->fields['id']] ? "checked" : "";
    } else {
      $localcontext['value']=$result->fields['condition'] ? "checked" : "";
    }
    
    call_user_func("code_do_$funcname",$localcontext);
    $result->MoveNext();
  }
}


?>
