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


die("desuet, appeler directement document.php");

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ($home."func.php");

$id=intval($id);

if ($id>0 && $dir) {
  # cherche le parent
  $result=mysql_query ("SELECT idparent,idtype FROM $GLOBALS[tp]entites WHERE id='$id'") or die (mysql_error());
  list($idparent,$idtype)=mysql_fetch_row($result);
  getsiteoptions ();
  $critere=$options[ordrepartypedoc] ? "AND idtype='$idtype'": "";
  chordre("entites",$id,"idparent='$idparent' $critere",$dir);
  back();
//
// supression et restauration
//
} elseif ($edit || $charge) { # prepare l'envoi dans extrainfo
# on fait une copie dans tmp
# cherche les info pour creer la tache
  $result=mysql_query("SELECT idparent,ordre FROM $GLOBALS[tp]entites WHERE id=$id AND statut>-64") or die (mysql_error());
  if ($row=mysql_fetch_assoc($result)) {
    $row[iddocument]=$id;
    if ($edit) {
      $tempname=tempnam("","r2r");
      copy("../txt/r2r-$id.xml",$tempname.".html");	
      $row[fichier]=$tempname;
      $idtache=make_tache("Edition $id",3,$row);
      header("location: extrainfo.php?id=$idtache");
    } else {
      $idtache=make_tache("Rechargement $id",1,$row);
      header("location: oochargement.php?tache=$idtache");
    }
  } else {
    header("location: ../../not-found.html");
  }
} else {
  # extrait le statut de l'article
  $result=mysql_query("SELECT statut FROM $GLOBALS[tp]entites WHERE id='$id'") or die (mysql_error());
  list($context[statut])=mysql_fetch_row($result);
}


$context[id]=$id;

posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"editer");


?>
