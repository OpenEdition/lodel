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
authenticate(LEVEL_ADMIN);

include ($home."checkxml.php");

$rep=$home."../r2r/tmptxt";
$d=dir($rep) or die ("impossible d'ouvrir $rep");
while ($entry=$d->read()) {
  if (!preg_match("/^\d+-\d+$/",$entry)) continue;
  echo "$entry<br>";
  $d2=dir("$rep/$entry") or die ("impossible d'ouvrir $entry");
  $files=array();
  while ($entry2=$d2->read()) if (preg_match("/\.xml$/",$entry2)) array_push($files,$entry2);
  sort($files);
  $d2->close();
  foreach($files as $entry2) {
    echo "<u>$rep/$entry/$entry2</u>:<br>";
    if (!checkfile("$rep/$entry/$entry2")) die("");
  }
}
$d->close();


?>
