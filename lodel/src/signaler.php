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

require("siteconfig.php");
include ($home."auth.php");
authenticate();
include ($home."func.php");

$context[id]=$id=intval($id);

include_once($home."connect.php");
//
// cherche le document
//

$result=mysql_query("SELECT *,datepubli,(datepubli<=NOW()) as textepublie FROM documents WHERE identite='$id'") or die (mysql_error());
if (mysql_num_rows($result)<1) { header ("Location: not-found.html"); return; }
$context=array_merge($context,mysql_fetch_assoc($result));
//
// charge le fichier XML et extrait les balises
//
if (!file_exists("lodel/txt/r2r-$id.xml")) { header ("Location: not-found.html"); return; }
$text=join("",file("lodel/txt/r2r-$id.xml"));

include ($home."xmlfunc.php");
include ($home."balises.php");

$balises=$balisesdocument_nonlieautexte;
array_push($balises,"surtitre","titre","soustitre");

if ($context[textepublie]) $balises=array_merge($balises,$balisesdocument_lieautexte);

$context=array_merge($context,extract_xml($balises,$text));


//
// envoi
//

if ($envoi) {
  extract_post();

  // validation
  do {

    if (!$context[to]) { $err=$context[erreur_to]=1; }
    if (!$context[from]) { $err=$context[erreur_from]=1; }

    if ($err) break;

    //
    // calcul le mail
    // 
    foreach (array("to","from","message") as $bal) {
      $context[$bal]=htmlspecialchars(stripslashes($context[$bal]));
    }
    $context[subject]=""; // securite
    include ($home."calcul-page.php");
    ob_start();
    calcul_page($context,"signaler-mail");
    $content=ob_get_contents();
    ob_end_clean();

    //
    // envoie le mail
    //
    if (!mail ($context[to],$context[subject],$content,"From: $context[from]")) { $context[erreur_mail]=1; break; }

    header ("location: document.html?id=$id");
    return;
  } while (0);
}


// post-traitement
foreach (array("to","from","message") as $bal) {
  $context[$bal]=htmlspecialchars(stripslashes($context[$bal]));
}


include_once ($home."calcul-page.php");
calcul_page($context,"signaler");

?>
