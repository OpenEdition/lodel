<?php
function typetypes_delete($critere)

{
  mysql_query("DELETE FROM $GLOBALS[tp]typeentites_typeentrees WHERE $critere") or die (mysql_error());
  mysql_query("DELETE FROM $GLOBALS[tp]typeentites_typepersonnes WHERE $critere") or die (mysql_error());
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
  mysql_query("INSERT INTO $GLOBALS[tp]typeentites_".$typetable."s (idtypeentite,id$typetable,condition) VALUES ".join(",",$values)) or die(mysql_error());
}




////////////////////////////////////////////////



function loop_typetable ($listtype,$criteretype,$context,$funcname)

{
  if ($listtype=="typeentite") {
    $maintable="types";
    $ordre="classe,type";
    $relationtable=$criteretype;
  } else {
    $maintable=$listtype."s";
    $relationtable=$listtype;
    $ordre="type";
  }

  $result=mysql_query("SELECT * FROM $GLOBALS[tp]$maintable LEFT JOIN $GLOBALS[tp]typeentites_".$relationtable."s ON id$listtype=$GLOBALS[tp]$maintable.id AND id$criteretype='$context[id]' WHERE statut>0 ORDER BY $ordre") or die(mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    $localcontext=array_merge($context,$row);
    call_user_func("code_do_$funcname",$localcontext);
  }
}


?>
