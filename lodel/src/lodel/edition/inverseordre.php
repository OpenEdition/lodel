<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003-2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
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
authenticate(LEVEL_EDITEUR,NORECORDURL);
include($home."func.php");


die("a reecrire avec entite");
$parent=intval($parent);


$critere="parent='$parent' AND statut>-64";
if (!$droitadmin) $critere.=" AND groupe IN ($usergroupes)";


lock_write("publications");
# cherche tous les enfants
$result=mysql_query ("SELECT max(ordre) FROM $GLOBALS[tp]publications WHERE $critere") or die (mysql_error());
if (!mysql_num_rows($result)) { die ("vous n'avez pas les droits"); }
list($max)=mysql_fetch_row($result);
mysql_query("UPDATE $GLOBALS[tp]publications SET ordre=$max-ordre WHERE $critere") or die (mysql_error());

unlock("publications");
back();

?>
