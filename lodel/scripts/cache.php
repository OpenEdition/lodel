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
require_once ($home."func.php");

if (!function_exists("authenticate")) {
  require ($home."auth.php");
  authenticate();
}


if ($GLOBALS[rightvisiteur]) {
  require ($home."calcul-page.php");
  calcul_page($context,$base);
  return;
}


if (!$maj) $maj=myfilemtime("CACHE/maj");


// Calcul du name du fichier cache

$cachedfile = substr(rawurlencode(preg_replace("/#[^#]*$/","",$_SERVER['REQUEST_URI'])), 0, 255);
// The variable $cachedfile must exist and be visible in the global scope
// The compiled file need it to know if it must produce cacheable output or direct output.
// An object should be created in order to avoid the global scope pollution.

$rep_cache = substr(md5($cachedfile), 0, 1);
if ($context[charset]!="utf-8") $rep_cache="il1.".$rep_cache;
if (!file_exists("CACHE/$rep_cache")) {
  mkdir("CACHE/$rep_cache", 0777 & octdec($GLOBALS['filemask']));
}
$cachedfile = "CACHE/$rep_cache/$cachedfile";

///////////$recalcul_templates=1;

$extension=file_exists($cachedfile.".php") ? "php" : "html";

if ($maj>=myfilemtime($cachedfile.".".$extension)) $recalcul_templates=1;

// si le fichier de mise-a-jour est plus recent
if ($recalcul_templates) {
  require_once ($home."calcul-page.php");
  calculate_cache_and_output($context,$base,$cachedfile);
} elseif ($extension=="php") {
  // execute le cache.
#  echo "cache:$cachedfile";
  $ret=include($cachedfile.".php");
  // c'est etrange ici, un require ne marche pas. Ca provoque des plantages lourds !
#  echo "required: ",join(",",get_required_files()),"<br>";
#  echo "return:$ret fichier$cachedfile.php<br/>";
  if ($ret=="refresh") {
    #echo "refresh";
    require_once ($home."calcul-page.php");
#    echo "planter?<br/>\n";
    calculate_cache_and_output($context,$base,$cachedfile);
  }
} else {
  // sinon affiche le cache.
  readfile($cachedfile.".html");
}



function calculate_cache_and_output ($context,$base,$cachedfile) {
  global $home;

  ob_start();
  $extension=calcul_page($context,$base);
  $content=ob_get_contents();
  ob_end_clean();

  $extension= substr($content,0,5)=='<'.'?php' ? "php" : "html";
  
  if ($extension=="html") {
    echo $content; // send right now the html. Do other thing later. That may save few milliseconde !
    @unlink($cache.".php"); // remove if the php file exists because it has the precedence above.
  }

  // ecrit le fichier dans le cache
  $f = fopen($cachedfile.".".$extension, "w");
  fputs($f,$content);
  fclose($f);
#  echo "ext:$extension";

  if ($extension=="php") { 
    $dontcheckrefresh=1;
    include($cachedfile.".php"); 
  }
}

?>
