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

if (!$_GET['do'] || !$_POST['do']) {
  require($home."auth.php");
  authenticate(LEVEL_VISITOR);
  $context['id']=$id=intval($_GET['id']);
  if ($id) {
    do {
      $row=$db->getRow(lq("SELECT tpledition,idparent,idtype FROM #_entitiestypesjoin_ WHERE #_TP_entities.id='$id'"));
      if ($row==false) dberror();
      if (!$row) { header ("Location: not-found.html"); return; }
      list($base,$idparent,$context[idtype])=$row;
      if (!$base) $context['id']=$row['id']=$row['idparent'];
    } while (!$base && $idparent);
  } else {
    $base="edition";
  }
  require($home."calcul-page.php");
  calcul_page($context,$base);
} else {
  $tables=array("entities");
  $table="entities";
  require($home."controler.php");
}

?>
