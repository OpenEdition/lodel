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

die("importsommaire: desuet");
require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_REDACTOR,NORECORDURL);
include ($home."func.php");
include ($home."langues.php");

if ($cancel) include ("abandon.php");

# recupere les infos dans le fichier xml
$row=gettask($idtache);

//////////// prepare la creation des publications et documents

// creer le repertoire pour y mettre les differents fichiers "xml".
$dir=$row[fichier].".dir";
if (!file_exists($dir)) {
  mkdir($dir,0777 & octdec($GLOBALS['filemask'])) or die ("impossible de creer le repertoire $row[fichier]");
}

// lit le fichier
$text=file_get_contents($row[fichier].".html");

// efface les tags articles
$text=preg_replace("/<\/?r2r:article\b[^>]*>/i","",$text);

//
// decoupe le document
//

//$tasks=array();

//
// cherche le name et le title du sommaire
//

foreach(array("titrenumero","nomnumero","typenumero") as $bal) {
  if (preg_match("/<r2r:$bal>(.*)<\/r2r:$bal>/is",$text,$result)) {
    $numero[$bal]=$result[1];
    $text=str_replace($result[0],"",$text);
  }
}

require_once("xmlimport.php");

$parent=mkxmlpublication($numero[nomnumero],
			 $numero[titrenumero],
			 $numero[typenumero] ? $numero[typenumero] : "numero", // valeur par defaut
			 $row[publication]);

//
// on decoupe en fonction des regroupements
//
$regroupements=preg_split("/<r2r:regroupement>/i",$text);

$currentpubli=$parent;
foreach ($regroupements as $regroupement) {
  // si c'est effectivement un regroupement, alors on cree le regroupement
  if (preg_match("/(.*)<\/r2r:regroupement>/i",$regroupement,$result)) {
    $currentpubli=mkxmlpublication($result[1],$result[1],"regroupement",$parent);
    $numero[$bal]=$result[1];
    $regroupement=str_replace($result[0],"",$regroupement);
  }
  //
  // on decoupe selon les balises auteurs
  //
  $tagdelimit="<r2r:auteurs>";
  $documents=preg_split("/$tagdelimit/i",$regroupement);
  array_shift($documents); // enleve le debut qui ne contient rien
  foreach ($documents as $document) {
    mkxmldocument($tagdelimit.$document,$currentpubli);
  }
}

// copie le rtf en lieu sur

$rtfname="$row[fichier].rtf";
if (file_exists($rtfname)) { 
  $dest="../rtf/r2r-pub-$parent.rtf";
  copy ($rtfname,$dest);
  chmod($dest,0666  & octdec($GLOBALS[filemask])) or die ("impossible de chmod'er $dest");
}


// clot la tache en cours.
include("abandon.php");

///////////////////////////
// functions


function mkxmlpublication($name,$title,$type,$idparent)

{
  global $home;

  myquote($name); myquote($title); myquote($type);

  // cherche le type dans la base
  if ($type) {
    // recherche l'id du type
    $result=mysql_query("SELECT id FROM $GLOBALS[tp]types WHERE type='$type' AND class='publications'") or dberror();
    list($idtype)=mysql_fetch_row($result);
  } else {
    $idtype=0;
  }

  if (!$idtype) {
    die("Impossible de trouber le type $type. Veuillez verifier que ce type existe");
    // on fait rien, mais c'est peut etre pas une bonne idee
  }

  $name=strip_tags($name,"<I><B><U>");$title=strip_tags($title,"<I><B><U>");

  $localcontext=array("name"=>$name,"idtype"=>$idtype,"idparent"=>$idparent,
		      "entite"=>array("title"=>$title));

  $id=enregistre_entite($localcontext,0,"publications","",FALSE); // ne declenche pas d'error

  if (!$id) die ("error dans mkxmlpublication");
  return $id;
}


function mkxmldocument($text,$idpublication)

{
  // ajoute les debuts et fins corrects
  $text='<r2r:article xmlns:r2r="http://www.lodel.org/xmlns/r2r" xmlns="http://www.w3.org/1999/xhtml">'.$text.'</r2r:article>';

  $localcontext=array("idparent"=>$idpublication,"status"=>-1);
  enregistre_entite_from_xml($localcontext,$text,"documents");
}

?>
