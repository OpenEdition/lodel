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


if (!(INC_LODELCONFIG)) die("inc lodelconfig please"); // security
// compatibility 0.7
if (!defined("DATABASE")) {
  define("DATABASE",$GLOBALS['database']);
  define("DBUSERNAME",$GLOBALS['dbusername']);
  define("DBPASSWD",$GLOBALS['dbpasswd']);
  define("DBHOST",$GLOBALS['dbhost']);
  define("DBDRIVER","mysql");
}


//
// connect to the database server
//

require_once($GLOBALS['home']."adodb/adodb.inc.php");

$GLOBALS['db']=ADONewConnection(DBDRIVER);
$GLOBALS['db']->debug = false;



//
//
//

if ($GLOBALS['site'] && $GLOBALS['singledatabase']!="on") {
  $GLOBALS['currentdb']=DATABASE."_".$GLOBALS['site'];
} else {
  $GLOBALS['currentdb']=DATABASE;
}

if (!defined("SINGLESITE")) define("SINGLESITE",$GLOBALS['singledatabase']!="on"); // synonyme currently but may change in the future

$GLOBALS['db']->connect(DBHOST,DBUSERNAME,DBPASSWD, $GLOBALS['currentdb']) or dberror();

$GLOBALS['tp']=$GLOBALS['tableprefix'];

/*------------------------------------------------------------------------------*/


function dberror()

{
  global $db;
  $ret=trigger_error($db->errormsg(),E_USER_ERROR);
}


$GLOBALS['maindb']="";
$GLOBALS['savedb']="";

function usemaindb()

{
  global $db,$maindb,$savedb;

  if (DATABASE==$GLOBALS['currentdb']) return false; // nothing to do
  if ($db->selectDB(DATABASE)) return true; // try to selectdb

  if (!$maindb) { // not connected
    $maindb=ADONewConnection(DBDRIVER);
    if (!$maindb->nconnect(DBHOST,DBUSERNAME,DBPASSWD,DATABASE)) die("ERROR: reconnection is not allow with the driver: ".DBDRIVER);
  }

  // set $db as $maindb
  $savedb=&$db;
  $db=&$maindb;

  return true;
}


function usecurrentdb()

{
  if (DATABASE==$GLOBALS['currentdb']) return; // nothing to do
  global $db,$savedb;
  if ($db->selectDB($GLOBALS['currentdb'])) return; // try to selectdb
  $db=&$savedb;
}




// Convenience function
// Lodel query extension
//

function lq ($query)

{
  static $cmd;
  // the easiest, fats replace
  $query=str_replace("#_TP_",$GLOBALS['tableprefix'],$query);

  // any other ?
  if (strpos($query,"#_")!==false) {
    if (!$cmd) $cmd=array(
			  "#_MTP_"=>DATABASE.".".$GLOBALS['tableprefix'],
			  "#_publicationsjoin_"=>"$GLOBALS[tableprefix]entities INNER JOIN $GLOBALS[tableprefix]publications ON $GLOBALS[tableprefix]entities.id=$GLOBALS[tableprefix]publications.identity",
			
			  ###			"#_documentsjoin_"=>"$GLOBALS[tableprefix]entities INNER JOIN $GLOBALS[tableprefix]documents ON $GLOBALS[tableprefix]entities.id=$GLOBALS[tableprefix]documents.identity",

			  "#_entitiestypesjoin_"=>"$GLOBALS[tableprefix]types INNER JOIN $GLOBALS[tableprefix]entities ON $GLOBALS[tableprefix]types.id=$GLOBALS[tableprefix]entities.idtype",

			  "#_publicationstypesjoin_"=>"($GLOBALS[entitestypesjoin]) INNER JOIN $GLOBALS[tableprefix]publications ON $GLOBALS[tableprefix]entities.id=$GLOBALS[tableprefix]publications.identity",

			  ####			"#_documentstypesjoin_"=>"($GLOBALS[entitestypesjoin]) INNER JOIN $GLOBALS[tableprefix]documents ON $GLOBALS[tableprefix]entities.id=$GLOBALS[tableprefix]documents.identity",

			  "#_tablefieldsandgroupsjoin_"=>"$GLOBALS[tableprefix]tablefieldgroups INNER JOIN $GLOBALS[tableprefix]tablefields ON $GLOBALS[tableprefix]tablefields.idgroup=$GLOBALS[tableprefix]tablefieldgroups.id",


			  "#_tablefieldgroupsandclassesjoin_"=>"$GLOBALS[tableprefix]tablefieldgroups INNER JOIN $GLOBALS[tableprefix]classes ON $GLOBALS[tableprefix]classes.class=$GLOBALS[tableprefix]tablefieldgroups.class",
			  );

    $query=strtr($query,$cmd);
  }
  return $query;
}


//
// fonction for handling unique id
//

/**
 * get a unique id
 *
 */

function uniqueid($table)

{
  global $db;
  $db->execute(lq("INSERT INTO #_TP_objects (class) VALUES ('$table')"));
  return $db->insert_id();
}

/**
 * erase a unique id
 *
 */

function deleteuniqueid($id)

{
  global $db;
  if (is_array($id) && $id) {
    $db->execute(lq("DELETE FROM $GLOBALS[tableprefix]objects WHERE id IN (".join(",",$id).")"));
  } else {
    $db->execute(lq("DELETE FROM $GLOBALS[tableprefix]objects WHERE id='$id'"));
  }
}


#$db_ok= !!@mysql_connect("localhost","apache","") &
#!!@mysql_select_db("r2r");

?>
