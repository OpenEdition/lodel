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
require("auth.php");
authenticate(LEVEL_VISITOR,NORECORDURL);
require("func.php");

if ($lang) {
  if (!preg_match("/^\w\w(-\w\w)?$/",$lang)) die("ERROR: invalid lang");
  $db->execute(lq("UPDATE #_TP_users SET lang='$lang'")) or dberror();
  setcontext("lang","setvalue",$lang);
}

if ($translationmode) {
  switch($translationmode) {
  case 'off':
    setcontext("translationmode","clear");
    break;
  case 'site':
  case 'interface':
    setcontext("translationmode","setvalue",$translationmode);
    break;
  }
}

update();
require_once("view.php");
View::back();


function setcontext($var,$operation,$value="") 

{
  global $db;
  usemaindb();
  $where="name='".addslashes($_COOKIE[$GLOBALS['sessionname']])."' AND iduser='".$GLOBALS['lodeluser']['id']."'";
  $context=$db->getOne(lq("SELECT context FROM $GLOBALS[tp]session WHERE ".$where));
  if ($db->errorno()) dberror();

  $arr=unserialize($context);
  switch ($operation) {
  case "toggle" :  $arr[$var]=$arr[$var] ? 0 : 1; // toggle
    break;
  case "setvalue" : $arr[$var]=$value;  // set
    break;
  case "clear" : unset($arr[$var]);  // clear
    break;
  }

  $db->execute(lq("UPDATE #_MTP_session SET context='".addslashes(serialize($arr))."' WHERE ".$where)) or dberror();
  usecurrentdb();
}

?>
