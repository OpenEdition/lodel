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
authenticate(LEVEL_VISITOR);

// Gère les fonctions avancées pour les publications et les documents.
// On peut passer 2 paramètres différents à ce script :
// id pour un document
// publication pour une publication

$critere=$rightadmin ? "" : "groupe IN ($usergroups) AND ";

if ($id) { // document
   $class="documents";
   $id=intval($id);
   $base="fonctionsavancees-document";
} elseif ($publication) { // publication
   $class="publications";
   $id=intval($publication);
   $base="fonctionsavancees-publication";
} else { die("id ou publication ?"); }


include_once ($home."connect.php");
$result=mysql_query("SELECT *, type  FROM $GLOBALS[tp]types, $GLOBALS[tp]entities, $GLOBALS[tp]$class WHERE $GLOBALS[tp]entities.id='$id' AND identity='$id' AND idtype=$GLOBALS[tp]types.id") or die($db->errormsg());
$context=array_merge($context,mysql_fetch_assoc($result));


include ($home."calcul-page.php");
calcul_page($context,$base);

?>
