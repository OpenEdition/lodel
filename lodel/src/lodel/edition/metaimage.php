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
include ($home."validfunc.php");

if ($idsite) { $id=$context[idsite]=intval($idsite); $class="sites"; $critere="id='$id'"; $db=$database; $champ="image"; }
elseif ($iddocument) { $id=$context[iddocument]=intval($iddocument); $class="documents"; $critere="identity='$id'"; $db=$currentdb; }
elseif ($idpublication) { $id=$context[idpublication]=intval($idpublication); $class="publications"; $critere="identity='$id'"; $db=$currentdb; }
else { die("Erreur preciser idsite, iddocument ou idpublication"); }

if (!$champ || !isvalidfield($champ)) die("Erreur. Preciser un champ");
$context[champ]=$champ;
$context[id]=$id;


do {
  if ($delete) {
    require_once($home."images.php");
    change_image("delete",$id,$class,$champ);
    $newimagefile="";
  } elseif ($edit) {
    require_once($home."images.php");
    $newimagefile=change_image($imagefile,$id,$class,$champ);
    if ($newimagefile===FALSE) { $context[error_chargement]=1; break; }
  } else break;

  mysql_db_query($db,"UPDATE $GLOBALS[tp]$class SET $champ='$newimagefile' WHERE $critere") or die(mysql_error());
  if (mysql_affected_rows()) touch(SITEROOT."CACHE/maj");
  back();
} while (0);

$result=mysql_db_query($db,"SELECT $champ FROM $GLOBALS[tp]$class WHERE $critere") or die (mysql_error());
if (!mysql_num_rows($result)) { header("Location: not-found.html"); return; }
list($oldimagefile)=mysql_fetch_row($result);

$context[image]=$oldimagefile;

// post-traitement
postprocessing($context);

include ($home."calcul-page.php");
calcul_page($context,"metaimage");


?>
