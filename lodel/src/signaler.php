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
include ($home."auth.php");
authenticate();
include ($home."func.php");

$context[id]=$id=intval($id);

include_once($home."connect.php");


//
// get the  document
//
$critere=$user['visitor'] ? "" : "AND $GLOBALS[tp]entities.status>0 AND $GLOBALS[tp]types.status>0";
if (!(@include_once("CACHE/filterfunc.php"))) require_once($home."filterfunc.php");

$result=mysql_query("SELECT $GLOBALS[tp]documents.*,$GLOBALS[tp]entities.*,type FROM $GLOBALS[documentstypesjoin] WHERE $GLOBALS[tp]entities.id='$id' $critere") or dberror();
if (mysql_num_rows($result)<1) { header ("Location: not-found.html"); return; }
require_once($home."textfunc.php");
$context=array_merge($context,filtered_mysql_fetch_assoc($context,$result));


//
// send
//

if ($envoi) {
  extract_post();

  // validation
  do {

    if (!$context[to]) { $err=$context[error_to]=1; }
    if (!$context[from]) { $err=$context[error_from]=1; }

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
	$headers  = "MIME-Version: 1.0\r\n";
   	$headers .= "Content-type: text/html; charset=utf-8\r\n";    
	$headers .= "From: $context[from]\r\n";
   // envoie le mail
   //
   if (!mail ($context[to],$context[subject],$content,$headers)) { $context[error_mail]=1; break; }     //

    header ("location: ".makeurlwithid($id,"document"));
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
