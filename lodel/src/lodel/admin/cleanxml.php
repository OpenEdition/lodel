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

// script de nettoyage des fichiers XML.
// utile lorsque les versions se succede.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include_once($home."func.php");


$search=array();
$rpl=array();

array_push($search,"/<sup><small>(<[^>]+>)*?<\/small><\/sup>/i");
array_push($rpl,"");

$dirname="../txt";
if ($dir=@opendir ($dirname)) {
  while (($file = readdir($dir)) !== false) {
    $file=$dirname."/".$file;
    if (!preg_match("/.xml$/",$file) || !is_file($file)) continue; // passe si ce n'est pas un fichier standart
    echo "$file";
    $text=join("",file($file));
    $newtext=preg_replace($search,$rpl,$text);
    if ($newtext!=$text) {
      echo "...cleaned";
      writefile($file,$newtext);
    }
    echo "<br>";
  }  
  closedir($dir);
} else {
  die ("impossible d'ouvrir le repertoire ../txt");
}



?>
