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


if (!(INC_LODELCONFIG)) die("inc lodelconfig");



mysql_connect($GLOBALS[dbhost],$GLOBALS[dbusername],$GLOBALS[dbpasswd]) or die ("ERROR  connect: ".mysql_error());
if ($GLOBALS[site] && $GLOBALS[singledatabase]!="on") {
  $GLOBALS[currentdb]=$GLOBALS[database]."_".$GLOBALS[site];
} else {
  $GLOBALS[currentdb]=$GLOBALS[database];
}
mysql_select_db($GLOBALS[currentdb])  or die ("ERROR select: ".mysql_error());

//
//if (!function_exists("table")) {
//  function table($nom)
//
//    { 
//	global $site;
//	if ($nom=="sites"  || $nom=="session" || ($nom=="users" && !$site)) {
//	  return "r2r_$nom";
//	} else {
//	  if (!$site) { die ("repertoire non valide: $site table: $nom"); }
//	  return "r2r_".$site."_".$nom;
//	}
//    }
//}
//


//
// expressions qui facilite la vie
//

$GLOBALS['tp']=$GLOBALS['tableprefix'];



$GLOBALS[publicationsjoin]="$GLOBALS[tp]entites INNER JOIN $GLOBALS[tp]publications ON $GLOBALS[tp]entites.id=$GLOBALS[tp]publications.identite";

$GLOBALS[documentsjoin]="$GLOBALS[tp]entites INNER JOIN $GLOBALS[tp]documents ON $GLOBALS[tp]entites.id=$GLOBALS[tp]documents.identite";

$GLOBALS[entitestypesjoin]="$GLOBALS[tp]types INNER JOIN $GLOBALS[tp]entites ON $GLOBALS[tp]types.id=$GLOBALS[tp]entites.idtype";

$GLOBALS[publicationstypesjoin]="($GLOBALS[entitestypesjoin]) INNER JOIN $GLOBALS[tp]publications ON $GLOBALS[tp]entites.id=$GLOBALS[tp]publications.identite";

$GLOBALS[documentstypesjoin]="($GLOBALS[entitestypesjoin]) INNER JOIN $GLOBALS[tp]documents ON $GLOBALS[tp]entites.id=$GLOBALS[tp]documents.identite";

$GLOBALS[champsgroupesjoin]="$GLOBALS[tp]groupesdechamps INNER JOIN $GLOBALS[tp]champs ON $GLOBALS[tp]champs.idgroupe=$GLOBALS[tp]groupesdechamps.id";


//
// fonction for handling unique id
//

/**
 * get a unique id
 *
 */

function uniqueid($table)

{
  mysql_query("INSERT INTO $GLOBALS[tp]objets (classe) VALUES ('$table')") or die (mysql_error());
  return mysql_insert_id();
}

/**
 * erase a unique id
 *
 */

function deleteuniqueid($id)

{

  if (is_array($id) && $id) {
    mysql_query("DELETE FROM $GLOBALS[tp]objets WHERE id IN (".join(",",$id).")") or die (mysql_error());
  } else {
    mysql_query("DELETE FROM $GLOBALS[tp]objets WHERE id='$id'") or die (mysql_error());
  }
}


#$db_ok= !!@mysql_connect("localhost","apache","") &
#!!@mysql_select_db("r2r");

?>
