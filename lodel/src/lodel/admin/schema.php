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
authenticate(LEVEL_VISITEUR);
include ($home."func.php");


include_once($home."connect.php");

$context[classe]="documents";

ob_start();
include ($home."calcul-page.php");
calcul_page($context,"schema-xsd");
$contents=ob_get_contents();
ob_end_clean();

$arr=preg_split("/\s*(<(\/?)[^>]+>)\s*/",$contents,-1,PREG_SPLIT_DELIM_CAPTURE);
#print_r($arr);

// "telechargement"
$originalname="entite-$id.xml";
#temporairement commente
#header("Content-type: application/force-download");
#header("Content-Disposition: attachment; filename=$originalname");
#header("Content-type: application/$type");


echo '<?xml version="1.0" encoding="utf-8" ?>
';
$tab="";
for($i=1; $i<count($arr); $i+=3) {
  if ($arr[$i+1]) $tab=substr($tab,2);
  echo $tab.$arr[$i],"\n";
  if (!$arr[$i+1] && !preg_match("/\/\s*>/",$arr[$i])) $tab.="  ";
  if (trim($arr[$i+2])) { echo $tab.$arr[$i+2]."\n"; }
}


?>
