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
include ("$home/auth.php");
authenticate(LEVEL_REDACTOR,NORECORDURL);

$id=intval($id);
if ($id>0 && $type=="xml") {
 $filename="r2r-$id.xml";
 $originalname=$filename;
 $rep="../txt";
} elseif ($id>0 && $type=="source") {
 $filename="entite-$id.source";
 $rep="../sources";
 // cherche le name original du fichier

 require($home."tablefields.php");
 $table=$GLOBALS[tp]."documents";
 if ($tablefields[$table] && 
      in_array("fichiersource",$tablefields[$table])) {
    $result=mysql_query("SELECT fichiersource FROM $GLOBALS[tp]documents WHERE identity='$id'") or dberror();
    list($originalname)=mysql_fetch_row($result);
  } else {
    $originalname=$filename;
  }
 $originalname=basename($originalname);

} else die ("type ou id inconnu");

if (!file_exists($rep."/".$filename)) die ("le fichier $filename n'existe pas");

require_once($home."func.php");
download ($rep."/".$filename,$originalname);

?>
