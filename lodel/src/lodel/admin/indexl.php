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

// securite
if (!function_exists("authenticate") || !$GLOBALS[admin]) return;

// gere les index linéaire permanent. L'acces est reserve au adminlodelistrateur.
// assure l'edition, la supression, la restauration des index linéaire.


// calcul le critere pour determiner le periode a editer, restorer, detruire...
$id=intval($id);
$critere=$id>0 ? "id='$id'" : "";


//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  include ($home."trash.php");
  treattrash("indexls",$critere);
  return;
}

if (!$type) die("probleme interne contacter Ghislain");
$context[type]=intval($type); // cette variable peut etre reajuste correctement dans la suite du code (dans edit, via l'import, ou dans la clause. Il faut quand meme positionner cette valeur pour le cas ou on ajoute simplement un motcle

//
// ajoute ou edit
//
if ($edit) { // modifie ou ajoute
  // pretraitement des entrees... met le resultat dans $context
  extract_post();
  // validation
  do {
    if (!$context[mot]) $err=$context[erreur_mot]=1;
    if ($err) break;
    include_once ($home."connect.php");
    $context[type]=intval($context[type]);

    if ($id>0) { // il faut rechercher le statut
      $result=mysql_query("SELECT statut FROM indexls WHERE id='$id'") or die (mysql_error());
      list($statut)=mysql_fetch_array($result);
      mysql_query ("REPLACE INTO indexls (id,mot,lang,statut,type) VALUES ('$id','$context[mot]','$context[lang]','$statut','$context[type]')") or die (mysql_error());
    } else {
      // cree les mots cles
      $mots=preg_split("/\s*[,;\n]\s*/",$context[mot]);
      foreach($mots as $mot) {
	$mot=trim(strip_tags($mot));
	mysql_query ("INSERT INTO indexls (mot,lang,type) VALUES ('$mot','$context[lang]','$context[type]')") or die (mysql_error());
      }
    }

    include_once($home."func.php");back();

  } while (0);
  // entre en edition
} elseif ($id>0) {
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM indexls WHERE $critere AND statut>-32") or die (mysql_error());
  $context=array_merge(mysql_fetch_assoc($result),$context);
}

// post-traitement
posttraitement($context);

include($home."langues.php");

?>
