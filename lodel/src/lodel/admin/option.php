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

// gere les options. L'acces est reserve au admin.
// assure la supression, la restauration des options.


require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include_once($home."func.php");
include_once($home."optionfunc.php");


$id=intval($id);
$critere=$id>0 ? "id='$id'" : "";

//
// supression et restauration
//
if ($id>0 && $delete) { 
  $delete=2; // destruction en -64;
  include ($home."trash.php");

  treattrash("options",$critere);
  return;
}

//
// rank
//
if ($id>0 && $dir) {
  include_once($home."connect.php");
  # cherche le parent
  chordre("options",$id,"status>0",$dir);
  back();
}


//
// ajoute ou edit
//

if ($edit) {
  extract_post();
  do { // block de control
    if (!$context[name] || preg_match("/\W/",$context[$name])) { $context[error_nom]=$err=1; }
    
    if ($err) break;

    $id=intval($id);
    if ($id>0) { // il faut rechercher l'rank
      $result=mysql_query("SELECT rank,valeur FROM $GLOBALS[tp]options WHERE id='$id'") or dberror();
      list($rank,$valeur)=mysql_fetch_row($result);
    } else {
      $result=mysql_query("SELECT 1 FROM $GLOBALS[tp]options WHERE name='$context[name]'") or dberror();
      if (mysql_num_rows($result)) { $context[error_nom_existe]=$err=1; break; }
      $rank=get_max_rank("options","status>0");
      $valeur="";
    }

    $status=$GLOBALS['user']['adminlodel'] && $protege ? "32" : "1";

    mysql_query("REPLACE INTO $GLOBALS[tp]options (id,name,type,status,rank,valeur) VALUES ('$id','$context[name]','$context[type]','$status','$rank','$valeur')") or dberror();

    touch(SITEROOT."CACHE/maj");
    unlock();
    back();

  } while (0);
} elseif ($id>0) {
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]options WHERE $critere") or dberror();
  $context=array_merge($context,mysql_fetch_assoc($result));
}

// post-traitement
postprocessing($context);


#require_once($home."langues.php");

include ($home."calcul-page.php");
calcul_page($context,"option");



function make_select_types($context) {


  foreach ($GLOBALS[options_types] as $key => $value) {
    $key=htmlentities($key);
    $selected=$context[type]==$key ? " selected" : "";
    echo "<option value=\"$key\"$selected>$value</option>\n";
  }
}

?>