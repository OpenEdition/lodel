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
//  function table($name)
//
//    { 
//	global $site;
//	if ($name=="sites"  || $name=="session" || ($name=="users" && !$site)) {
//	  return "r2r_$name";
//	} else {
//	  if (!$site) { die ("repertoire non valide: $site table: $name"); }
//	  return "r2r_".$site."_".$name;
//	}
//    }
//}
//


//
// expressions qui facilite la vie
//

$GLOBALS['tp']=$GLOBALS['tableprefix'];



$GLOBALS['publicationsjoin']="$GLOBALS[tp]entities INNER JOIN $GLOBALS[tp]publications ON $GLOBALS[tp]entities.id=$GLOBALS[tp]publications.identity";

$GLOBALS['documentsjoin']="$GLOBALS[tp]entities INNER JOIN $GLOBALS[tp]documents ON $GLOBALS[tp]entities.id=$GLOBALS[tp]documents.identity";

$GLOBALS['entitestypesjoin']="$GLOBALS[tp]types INNER JOIN $GLOBALS[tp]entities ON $GLOBALS[tp]types.id=$GLOBALS[tp]entities.idtype";

$GLOBALS['publicationstypesjoin']="($GLOBALS[entitestypesjoin]) INNER JOIN $GLOBALS[tp]publications ON $GLOBALS[tp]entities.id=$GLOBALS[tp]publications.identity";

$GLOBALS['documentstypesjoin']="($GLOBALS[entitestypesjoin]) INNER JOIN $GLOBALS[tp]documents ON $GLOBALS[tp]entities.id=$GLOBALS[tp]documents.identity";

$GLOBALS['fieldsandgroupsjoin']="$GLOBALS[tp]fieldgroups INNER JOIN $GLOBALS[tp]fields ON $GLOBALS[tp]fields.idgroup=$GLOBALS[tp]fieldgroups.id";


//
// fonction for handling unique id
//

/**
 * get a unique id
 *
 */

function uniqueid($table)

{
  mysql_query("INSERT INTO $GLOBALS[tp]objects (class) VALUES ('$table')") or die (mysql_error());
  return mysql_insert_id();
}

/**
 * erase a unique id
 *
 */

function deleteuniqueid($id)

{

  if (is_array($id) && $id) {
    mysql_query("DELETE FROM $GLOBALS[tp]objects WHERE id IN (".join(",",$id).")") or die (mysql_error());
  } else {
    mysql_query("DELETE FROM $GLOBALS[tp]objects WHERE id='$id'") or die (mysql_error());
  }
}


#$db_ok= !!@mysql_connect("localhost","apache","") &
#!!@mysql_select_db("r2r");

?>
