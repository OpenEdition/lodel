<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003-2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
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

if (!file_exists("lodelconfig.php")) { header("location: lodeladmin/install.php"); exit; } 	 
  	 
if (file_exists("siteconfig.php")) {
  require("siteconfig.php");
} else { 	 
  require ("lodelconfig.php"); 	 
}

require_once($home."auth.php");
authenticate();

if ($id) {
  require_once($home."connect.php");
  $id=intval($id);
  $result=mysql_query("SELECT classe FROM $GLOBALS[tp]objets WHERE id='$id'") or die(mysql_error());
  list($classe)=mysql_fetch_row($result);
  $script=array("documents"=>"document",
		"publications"=>"sommaire",
		"entrees"=>"entree",
		"typeentrees"=>"entrees",
		"personnes"=>"personne",
		"typepersonnes"=>"personnes",
		);
  if ($script[$classe]) {
    require($script[$classe].".".$extensionscripts);
  } else {
    header("location: not-found.html");
  }
  return;
}

$base="index";
include ($home."cache.php");
?>
