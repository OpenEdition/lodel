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

die("desuet");
require("lodelconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN);


## $macrofile=join("",file("tpl/macros.html"));
## preg_match_all("/<defmacro[^>]+name=\"([^\"]+)\"/i",$macrofile,$results,PREG_SET_ORDER);

$dir=opendir("tpl");
while ($filename=readdir($dir)) {
	if (!preg_match("/.html$/",$filename)) continue;
	preg_match_all("/<macro[^>]+name=\"([^\"]+)\"/i",join("",file("tpl/".$filename)),$results,PREG_SET_ORDER);
	foreach ($results as $result) { $macros[$result[1]].="$filename ";}
}

echo "<TABLE WIDTH=\"100%\" BORDER=\"1\">\n";
foreach ($macros as $macro=>$files) {
	echo "<TR><TD>$macro</TD><TD>$files</TD>\n";
}

?>
</TABLE>
