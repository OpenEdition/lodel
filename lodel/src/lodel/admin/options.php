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
authenticate(LEVEL_ADMIN,NORECORDURL);

include_once ($home."connect.php");


if ($edit) {
  $listoptions=array("motclefige","pasdemotcle","pasdeperiode","pasdegeographie","ordrepartypedoc");
  include ($home."func.php");
  extract_post();
  $newoptions=array();
  foreach ($listoptions as $opt) {
    if ($context["option_$opt"]) $newoptions["option_$opt"]=1;
  }
  $optionsstr=serialize($newoptions);
  mysql_db_query($GLOBALS[database],"UPDATE sites SET options='$optionsstr' WHERE rep='$site'") or die (mysql_error());
  mysql_select_db($GLOBALS[currentdb]);

  // recherche les metas
  $result=mysql_db_query($GLOBALS[database],"SELECT meta FROM sites WHERE rep='$site'") or die (mysql_error());
  // ajoute les metas
  $newmeta=addmeta($context,$meta);
  if ($newmeta!=$meta) mysql_db_query($GLOBALS[database],"UPDATE sites SET meta='$newmeta' WHERE rep='$site'") or die (mysql_error());

  back();
}

//print_r($context);

include ($home."calcul-page.php");
calcul_page($context,"options");



?>


