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
require($home."auth.php");
authenticate(LEVEL_VISITOR,NORECORDURL);
require($home."func.php");

if ($lang) {
  if (!preg_match("/^\w\w(-\w\w)?$/",$lang)) die("ERROR: invalid lang");
  mysql_query("UPDATE $GLOBALS[tp]users SET lang='$lang'") or die($db->errormsg());
  setcontext("userlang","setvalue",$lang);
}

if ($translationmode) {
  setcontext("usertranslationmode","toggle");
}

back();


function setcontext($var,$operation,$value="") {
  mysql_select_db($GLOBALS['database']);
  $where="name='".addslashes($_COOKIE[$GLOBALS[sessionname]])."' AND iduser='".$GLOBALS['iduser']."'";
  $result=mysql_query("SELECT context FROM $GLOBALS[tp]session WHERE ".$where) or die($db->errormsg());
  list($context)=mysql_fetch_row($result);
  $arr=unserialize($context);
  switch ($operation) {
  case "toggle" :  $arr[$var]=$arr[$var] ? 0 : 1; // toggle
    break;
  case "setvalue" : $arr[$var]=$value;  // set
    break;
  case "clear" : unset($arr[$var]);  // clear
    break;
  }
  mysql_query ("UPDATE $GLOBALS[tp]session SET context='".addslashes(serialize($arr))."' WHERE ".$where) or die($db->errormsg());
  mysql_select_db($GLOBALS['currentdb']);
}

?>