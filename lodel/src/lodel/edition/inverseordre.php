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
authenticate(LEVEL_EDITOR,NORECORDURL);
include($home."func.php");


die("a reecrire avec entite");
$parent=intval($parent);


$critere="parent='$parent' AND status>-64";
if (!$rightadmin) $critere.=" AND groupe IN ($usergroups)";


lock_write("publications");
# cherche tous les enfants
$result=mysql_query ("SELECT max(rank) FROM $GLOBALS[tp]publications WHERE $critere") or die($db->errormsg());
if (!mysql_num_rows($result)) { die ("vous n'avez pas les rights"); }
list($max)=mysql_fetch_row($result);
mysql_query("UPDATE $GLOBALS[tp]publications SET rank=$max-rank WHERE $critere") or die($db->errormsg());

unlock("publications");
back();

?>
