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
authenticate(LEVEL_VISITEUR);

// Gère les fonctions avancées pour les publications et les documents.
// On peut passer 2 paramètres différents à ce script :
// id pour un document
// publication pour une publication

$critere=$droitadmin ? "" : "groupe IN ($usergroupes) AND ";

if ($id) { // document
   $classe="documents";
   $id=intval($id);
   $base="fonctionsavancees-document";
} elseif ($publication) { // publication
   $classe="publications";
   $id=intval($publication);
   $base="fonctionsavancees-publication";
} else { die("id ou publication ?"); }


include_once ($home."connect.php");
$result=mysql_query("SELECT *, type  FROM $GLOBALS[tp]types, $GLOBALS[tp]entites, $GLOBALS[tp]$classe WHERE $GLOBALS[tp]entites.id='$id' AND identite='$id' AND idtype=$GLOBALS[tp]types.id") or die (mysql_error());
$context=array_merge($context,mysql_fetch_assoc($result));


include ($home."calcul-page.php");
calcul_page($context,$base);

?>
