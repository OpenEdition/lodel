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
include ($home."auth.php");
authenticate(LEVEL_EDITOR,NORECORDURL);
include ($home."func.php");

$context[iddocument]=$id=intval($id);
$idparent=intval($idparent);

if ($idparent) {
  lock_write("entites","relations","typeentites_typeentites","entites as parent","entites as fils");
  // check whether we have the right or not
  if ($idparent>0) { // yeah there is a parent
    $result=mysql_query("SELECT condition FROM $GLOBALS[tp]entitytypes_entitytypes,$GLOBALS[tp]entities as parent,$GLOBALS[tp]entities as fils WHERE parent.id='$idparent' AND fils.id='$id' AND idtypeentite2=parent.idtype AND identitytype=fils.idtype") or die(mysql_error());
  } else { // no parent, the base.
    $result=mysql_query("SELECT condition FROM $GLOBALS[tp]entitytypes_entitytypes,$GLOBALS[tp]entities as fils WHERE fils.id='$id' AND idtypeentite2=0 AND identitytype=fils.id") or die(mysql_error());
  }
  if (mysql_num_rows($result)<=0) die("ERROR: Can move the entities $id into $idparent. Check the editorial model.");

  // yes we have the right

  mysql_query ("UPDATE $GLOBALS[tp]entities SET idparent='$idparent' WHERE id='$id'") or die (mysql_error());
  if (mysql_affected_rows()) { // on a effectivement changer l'id du parent
    // cherche les nouveaux parents de $id
    $result=mysql_query("SELECT id1,degree FROM $GLOBALS[tp]relations WHERE id2='$idparent' AND nature='P'") or die(mysql_error());

    $values="";
    $dmax=0;
    while ($row=mysql_fetch_assoc($result)) {
      $parents[$row[degree]]=$row[id1];
      if ($row[degree]>$dmax) $dmax=$row[degree];
      $values.="('$row[id1]','$id','P','".($row[degree]+1)."'),";
    }
    $parents[0]=$idparent;

    // recherche les enfants
    $delete="";
    $result=mysql_query("SELECT id2,degree FROM $GLOBALS[tp]relations WHERE id1='$id' AND nature='P'") or die(mysql_error());
    while ($row=mysql_fetch_assoc($result)) {
      $delete.=" (id2='$row[id2]' AND degree>$row[degree]) OR "; // efface tous les parents au dessus de $id.
      for ($d=0; $d<=$dmax; $d++) { // pour chaque degree
	$values.="('$parents[$d]','$row[id2]','P','".($row[degree]+$d+1)."'),"; // ajoute tous les parents
      }
    }

    $delete.=" id2='$id' ";
    $values.="('$idparent','$id','P',1)";
 
#   echo $values,"<br>",$delete;
    // detruit les liens vers le parent de id
    mysql_query ("DELETE FROM $GLOBALS[tp]relations WHERE ($delete) AND nature='P'") or die (mysql_error());
    mysql_query("INSERT INTO $GLOBALS[tp]relations (id1,id2,nature,degree) VALUES $values") or die(mysql_error());
    touch(SITEROOT."CACHE/maj");
  }
  unlock();
  back();
  return;
}

$context[id]=0;
postprocessing($context);

include ($home."calcul-page.php");
calcul_page($context,"deplacer");

?>
