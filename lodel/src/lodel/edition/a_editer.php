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

// registre dans la base de donnée le fichier

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ($home."func.php");

$id=intval($id);

if ($edit && $fichier) {
  include ($home."dbxml.php");
  # recupere les informations a fournir a enregistre
  $result=mysql_query("SELECT publication,ordre,datepubli FROM documents WHERE id=$id AND statut>-2") or die (mysql_error());
  if ($row=mysql_fetch_array($result,MYSQL_ASSOC)) {
    $row[iddocument]=$id;
    # empeche les tags d'etre coupes
    $fichier=preg_replace("/(<[^>\n]*)\n([^>\n]*>)/s","\\1 \\2",stripslashes($fichier));
    include_once($home."checkxml.php");
    if (!checkstring($fichier)) {
      echo "<br><br><a href=\"javascript: back()\">Editer à nouveau</a>";
      exit;
    }
    # efface le document d'abord
    include($home."managedb.php");
    supprime_document($id);
    enregistre($row,$fichier);
    writefile("../txt/r2r-$id.xml",$fichier);
  }
  back();

} else {
  $result=mysql_query("SELECT * FROM documents WHERE id=$id") or die (mysql_error());
  if (!($row=mysql_fetch_array($result,MYSQL_ASSOC))) { header ("index.php"); return; }
  $context=array_merge($context,$row);
  if (!file_exists("../txt/r2r-$id.xml")) {
    $context[erreur_fichiernontrouve]=1;
  } else {
    $context[fichier]=join("",file("../txt/r2r-$id.xml"));
  }
}

posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"a_editer");

?>
