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

// charge le fichier xml et
require("siteconfig.php");
include ($home."auth.php");
authenticate();
include ($home."func.php");

$context[id]=$id=intval($id);

include_once($home."connect.php");


$critere=$visiteur ? "" : "AND $GLOBALS[tp]entites.statut>0 AND $GLOBALS[tp]types.statut>0";

//
// cherche le document, et le template
//
if (!(@include_once("CACHE/filterfunc.php"))) require_once($home."filterfunc.php");

$result=mysql_query("SELECT $GLOBALS[tp]documents.*,$GLOBALS[tp]entites.*,tpl,type FROM $GLOBALS[documentstypesjoin] WHERE $GLOBALS[tp]entites.id='$id' $critere") or die (mysql_error());
if (mysql_num_rows($result)<1) { header ("Location: not-found.html"); return; }
require_once($home."textfunc.php");
$context=array_merge($context,filtered_mysql_fetch_assoc($context,$result));
if (!$context[tpl]) { 
  header("location: ".makeurl("document",$context[idparent]));
  return;
}
$base=$context[tpl];


//
// cherche s'il y a des documents annexe et combien
//

$result=mysql_query("SELECT count(*) FROM $GLOBALS[entitestypesjoin] WHERE idparent='$id' AND $GLOBALS[tp]entites.statut>0 AND type LIKE 'documentannexe-%'") or die (mysql_error());
list($context[documentsannexes])=mysql_fetch_row($result);
//
// cherche l'article precedent et le suivant
//


// suivant:

$querybase="SELECT $GLOBALS[tp]entites.id FROM $GLOBALS[entitestypesjoin] WHERE idparent='$context[idparent]' AND";

$nextid=0;
do {// exception
  $result=mysql_query ("$querybase $GLOBALS[tp]entites.ordre>$context[ordre] $critere ORDER BY $GLOBALS[tp]entites.ordre LIMIT 0,1") or die (mysql_error());
  if (mysql_num_rows($result)) {
    list($nextid)=mysql_fetch_row($result);
    break;
  }

  // ok, on a pas trouve on cherche alors le pere suivant et son premier fils (e2)
  $result=mysql_query ("SELECT e2.id FROM $GLOBALS[entitestypesjoin], $GLOBALS[tp]relations, $GLOBALS[tp]entites as e2 WHERE id2='$context[id]' AND degres=2 AND $GLOBALS[tp]entites.idparent=id1 AND type='regroupement' AND  $GLOBALS[tp]entites.ordre>$context[ordre] $critere ORDER BY $GLOBALS[tp]entites.ordre, e2.ordre LIMIT 0,1") or die (mysql_error());
  if (mysql_num_rows($result)) {
    list($nextid)=mysql_fetch_row($result);
    break;
  }
} while (0);

if ($nextid) $context[nextdocument]=makeurlwithid("document",$nextid);

// precedent:

$previd=0;
do {  // exception
  $result=mysql_query ("$querybase $GLOBALS[tp]entites.ordre<$context[ordre] $critere ORDER BY $GLOBALS[tp]entites.ordre DESC LIMIT 0,1") or die (mysql_error());
  if (mysql_num_rows($result)) {
    list($previd)=mysql_fetch_row($result);
    break;
  }

  // ok, on a pas trouve on cherche alors le pere precedent et son dernier fils (e2)
  $result=mysql_query ("SELECT e2.id FROM $GLOBALS[entitestypesjoin], $GLOBALS[tp]relations, $GLOBALS[tp]entites as e2 WHERE id2='$context[id]' AND degres=2 AND $GLOBALS[tp]entites.idparent=id1 AND type='regroupement' AND  $GLOBALS[tp]entites.ordre<$context[ordre] $critere ORDER BY $GLOBALS[tp]entites.ordre DESC, e2.ordre DESC LIMIT 0,1") or die (mysql_error());
  if (mysql_num_rows($result)) {
    list($nextid)=mysql_fetch_row($result);
    break;
  }
  // ok, c'est surement hors regroupement alors.
  $result=mysql_query ("SELECT e2.id FROM $GLOBALS[entitestypesjoin], $GLOBALS[tp]relations, $GLOBALS[tp]entites as e2 WHERE id2='$context[id]' AND degres=2 AND $GLOBALS[tp]entites.idparent=id1 AND $GLOBALS[tp]types.classe='documents' AND  $GLOBALS[tp]entites.ordre<$context[ordre] $critere ORDER BY $GLOBALS[tp]entites.ordre DESC, e2.ordre DESC LIMIT 0,1") or die (mysql_error());
  if (mysql_num_rows($result)) {
    list($nextid)=mysql_fetch_row($result);
    break;
  }
} while (0);

if ($previd) $context[prevdocument]=makeurlwithid("document",$previd);

// fin suivant et precedent


include ($home."cache.php");

?>
