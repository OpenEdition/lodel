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
authenticate(LEVEL_ABONNE);

$name=addslashes($_COOKIE[$sessionname]);

include_once ($home."connect.php");
$time=time()-1;
mysql_select_db($database);
mysql_query("UPDATE $GLOBALS[tp]session SET expire2='$time' WHERE name='$name'") or die (mysql_error());
mysql_query("DELETE FROM $GLOBALS[tp]pileurl WHERE idsession='$idsession'") or die (mysql_error());
setcookie($sessionname,"",$time,$urlroot);

if (SITEROOT == "" && !empty($_GET['url_retour'])) {
	if (strpos($_GET['url_retour'], '?') === false) { $and = '?'; }
	else { $and = '&'; }
	header ("Location: http://".$_SERVER['SERVER_NAME'] . $_GET['url_retour'] . $and . "recalcul_templates=oui");
} else {
	header ("Location: " . SITEROOT); // la norme ne supporte pas les chemins relatifs !!
}
?>
