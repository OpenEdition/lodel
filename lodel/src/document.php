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

// charge le fichier xml et
if (!function_exists("authenticate")) {
  require("siteconfig.php");
  require_once ($home."auth.php");
  authenticate();
}
require_once($home."func.php");

$context[id]=$id=intval($id);

include_once($home."connect.php");


$critere=$user['visitor'] ? "" : "AND $GLOBALS[tp]entities.status>0 AND $GLOBALS[tp]types.status>0";

if ($identifier) {
  $identifier=addslashes(stripslashes($identifier));
  $critere="$GLOBALS[tp]entities.identifier='$identifier' ".$critere;
} else {
  $critere="$GLOBALS[tp]entities.id='$id' ".$critere;
}

//
// cherche le document, et le template
//
if (!(@include_once("CACHE/filterfunc.php"))) require_once($home."filterfunc.php");

$result=mysql_query("SELECT $GLOBALS[tp]documents.*,$GLOBALS[tp]entities.*,tpl,type FROM $GLOBALS[documentstypesjoin] WHERE  $critere") or dberror();
if (mysql_num_rows($result)<1) { header ("Location: not-found.html"); return; }
require_once($home."textfunc.php");
$context=array_merge($context,filtered_mysql_fetch_assoc($context,$result));
if (!$context[tpl]) { 
  header("location: ".makeurlwithid("document",$context[idparent]));
  return;
}
$base=$context[tpl];


/*
//
// cherche s'il y a des documents annexe et combien
//

$result=mysql_query("SELECT count(*) FROM $GLOBALS[entitestypesjoin] WHERE idparent='$id' AND $GLOBALS[tp]entities.status>0 AND type LIKE 'documentannexe-%'") or dberror();
list($context[documentsannexes])=mysql_fetch_row($result);
//
// cherche l'article precedent et le suivant
//


// suivant:

$querybase="SELECT $GLOBALS[tp]entities.id FROM $GLOBALS[entitestypesjoin] WHERE idparent='$context[idparent]' AND";

$nextid=0;
do {// exception
  $result=mysql_query ("$querybase $GLOBALS[tp]entities.rank>$context[rank] $critere ORDER BY $GLOBALS[tp]entities.rank LIMIT 0,1") or dberror();
  if (mysql_num_rows($result)) {
    list($nextid)=mysql_fetch_row($result);
    break;
  }

  // ok, on a pas trouve on cherche alors le pere suivant et son premier fils (e2)
  $result=mysql_query ("SELECT e2.id FROM $GLOBALS[entitestypesjoin], $GLOBALS[tp]relations, $GLOBALS[tp]entities as e2 WHERE id2='$context[id]' AND degree=2 AND $GLOBALS[tp]entities.idparent=id1 AND type='regroupement' AND  $GLOBALS[tp]entities.rank>$context[rank] $critere ORDER BY $GLOBALS[tp]entities.rank, e2.rank LIMIT 0,1") or dberror();
  if (mysql_num_rows($result)) {
    list($nextid)=mysql_fetch_row($result);
    break;
  }
} while (0);

if ($nextid) $context[nextdocument]=makeurlwithid("document",$nextid);

// precedent:

$previd=0;
do {  // exception
  $result=mysql_query ("$querybase $GLOBALS[tp]entities.rank<$context[rank] $critere ORDER BY $GLOBALS[tp]entities.rank DESC LIMIT 0,1") or dberror();
  if (mysql_num_rows($result)) {
    list($previd)=mysql_fetch_row($result);
    break;
  }

  // ok, on a pas trouve on cherche alors le pere precedent et son dernier fils (e2)
  $result=mysql_query ("SELECT e2.id FROM $GLOBALS[entitestypesjoin], $GLOBALS[tp]relations, $GLOBALS[tp]entities as e2 WHERE id2='$context[id]' AND degree=2 AND $GLOBALS[tp]entities.idparent=id1 AND type='regroupement' AND  $GLOBALS[tp]entities.rank<$context[rank] $critere ORDER BY $GLOBALS[tp]entities.rank DESC, e2.rank DESC LIMIT 0,1") or dberror();
  if (mysql_num_rows($result)) {
    list($nextid)=mysql_fetch_row($result);
    break;
  }
  // ok, c'est surement hors regroupement alors.
  $result=mysql_query ("SELECT e2.id FROM $GLOBALS[entitestypesjoin], $GLOBALS[tp]relations, $GLOBALS[tp]entities as e2 WHERE id2='$context[id]' AND degree=2 AND $GLOBALS[tp]entities.idparent=id1 AND $GLOBALS[tp]types.class='documents' AND  $GLOBALS[tp]entities.rank<$context[rank] $critere ORDER BY $GLOBALS[tp]entities.rank DESC, e2.rank DESC LIMIT 0,1") or dberror();
  if (mysql_num_rows($result)) {
    list($nextid)=mysql_fetch_row($result);
    break;
  }
} while (0);

if ($previd) $context[prevdocument]=makeurlwithid("document",$previd);
*/
// fin suivant et precedent


include ($home."cache.php");

?>
