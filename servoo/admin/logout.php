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

require("config.php");
include (TOINCLUDE."auth.php");
authenticate(LEVEL_VISITEUR);

$name=$HTTP_COOKIE_VARS["session$database"];

mysql_connect($dbhost,$dbusername,$dbpasswd) or die (mysql_error());
mysql_select_db($database)  or die (mysql_error());

$time=time()-1;
mysql_query("UPDATE $GLOBALS[tp]session SET timeout2='$time' WHERE name='$name'") or die (mysql_error());
setcookie($sessionname,"",$time,$urlroot);

header ("Location: http://$SERVER_NAME$urlroot");

?>
