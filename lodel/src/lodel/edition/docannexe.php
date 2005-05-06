<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 *  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
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
include ($home."func.php");

$idparent=intval($idparent);
$id=intval($id);
$idtype=intval($idtype);
$tplcreation="";

//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  include_once("connect.php");
  include ($home."managedb.php");

  supprime($id,true);

  touch(SITEROOT."CACHE/maj");
  back();

  return;
}

$critere="id='$id'";

//
// rank
//
if ($id>0 && $dir) {
  # cherche le parent
  $result=mysql_query ("SELECT idparent FROM $GLOBALS[tp]entities WHERE id='$id'") or dberror();
  list($idparent)=mysql_fetch_row($result);
  // recupere les type de documents annexe
  $result=mysql_query ("SELECT id FROM $GLOBALS[tp]types WHERE type LIKE 'documentannexe-%'") or dberror();
  $idtypes=array();
  while ($row=mysql_fetch_assoc($result)) { array_push($idtypes,$row[id]); }
  chrank("entites",$id,"idparent='$idparent' AND idtype IN (".join(",",$idtypes).")",$dir);
  touch(SITEROOT."CACHE/maj");
  back();
}


//
// ajoute ou edit
//


if ($edit) { // modifie ou ajoute
  extract_post();
  // validation
  do {
    if (!$idtype) die("il faut preciser l'idtype");
    $result=mysql_query("SELECT type FROM $GLOBALS[tp]types WHERE id='$idtype' AND status>0") or dberror();
  if (!mysql_num_rows($result)) die ("type '$type' inconnu (1)");
  list($type)=mysql_fetch_row($result);

  $docfile=$_FILES['docfile'];
  if ($type=="documentannexe-lienfichier") {
    // charge le fichier si necessaire
      if ($docfile && $docfile['tmp_name'] && $docfile['tmp_name']!="none") {
	if ($id>0) { // we know the document id, we can copy it.
	  $lien=save_annex_file($id,$docfile['tmp_name'],$docfile['name'],FALSE,$error);
	} else {
	  $lien="temporaire";
	}
	// else, process once we know the id of the document
      } else {
	// recherche le lien
	include_once("connect.php");
	$result=mysql_query("SELECT lien FROM $GLOBALS[tp]documents WHERE identity='$id'") or dberror();
	list($lien)=mysql_fetch_row($result);
      }
    } elseif ($type=="documentannexe-liendocument") {
      $lien=intval($context[lien]);
      // cherche si le documents existe
      $result=mysql_query("SELECT identity FROM $GLOBALS[tp]documents WHERE identity='$lien'") or die (mysql_query());
      if (mysql_num_rows($result)<1) {
	$err=$context[error_documentinexistant]=1;
      } else {
	$lien=makeurlwithid ("document",$lien);
      }
    } elseif ($type=="documentannexe-lienpublication") {
      $lien=intval($context[lien]);
      // cherche si le documents existe
      $result=mysql_query("SELECT identity FROM $GLOBALS[tp]publications WHERE identity='$lien'") or die (mysql_query());
      if (mysql_num_rows($result)<1) {
	$err=$context[error_publicationinexistant]=1;
      } else {
	$lien=makeurlwithid ("sommaire",$lien);
      }
    } elseif ($type=="documentannexe-lienexterne") {
      // verifie l'adresse
      $lien=$context[lien];
      if ($lien && !preg_match("/http:\/\//i",$lien)) $lien="http://".$lien;
      $url=parse_url($lien);
      if (!$url[host] || !preg_match("/^[\w-]+(\.[\w-]+)+$/",$url[host])) { $context[error_urlinvalide]=$err=1; }
    } else {
      die ("error type incorrecte");
    }
    if (!$lien) { $context[error_lieninexistant]=$err=1; }
    // fin de chargement

    if ($err) break;
    require_once("connect.php");
    require_once("entitefunc.php");
    $context[entite][name]=$context[title];
    $context[entite][title]=$context[title];
    $context[entite][texte]=$context[texte];
    $context[entite][lien]=$lien;
    $context[idparent]=$idparent;

    $newid=enregistre_entite($context,$id,"documents","",FALSE); // ne retourne pas quand il y a une error !

#    if ($newid===FALSE) {
#      print_r($context[error]);
#      foreach ($context[error] as $champ=>$msg) { $context["error_".$champ]=$msg; }
#      break;
#    }
#    echo "newid:$newid<br/>";

    // New document, now we know the id, let's copy the uploaded file.
    if (!$id && $newid &&
	$type=="documentannexe-lienfichier" &&
	$docfile['tmp_name']) { // we know the document id, we can copie it.
      $lien=save_annex_file($newid,$docfile['tmp_name'],$docfile['name'],FALSE,$error);
      mysql_query("UPDATE $GLOBALS[tp]documents SET lien='$lien' WHERE identity=$newid");
    }

    back();
  } while (0);
  // entre en edition
} elseif ($id>0) {
  $id=intval($id);
  include_once("connect.php");
  $result=mysql_query("SELECT $GLOBALS[tp]documents.*,$GLOBALS[tp]entities.*,$GLOBALS[tp]types.type,tplcreation FROM $GLOBALS[documentstypesjoin] WHERE $GLOBALS[tp]entities.id='$id'") or dberror();
  $context=array_merge($context,mysql_fetch_assoc($result));
  if ($context[type]=="documentannexe-liendocument" || $context[type]=="documentannexe-lienpublication") {
    // recupere le numero
    preg_match("/(\d+)/",$context[lien],$result); # c'est sale !!!!
    $context[lien]=$result[1];
  }
  $tplcreation=$context[tplcreation];
} elseif ($idparent) {
  $context[idparent]=$idparent;
  if (!$idtype && !$type) die("il faut preciser le type de docannexe");
} else {
  die("il faut preciser un document auquel on veut ajouter le document annexe");
}


if (!$tplcreation) {
  // cherche le tpl
  $critere=$idtype ? "id='$idtype'" : "type='$type'";
    $result=mysql_query("SELECT tplcreation,id, type FROM $GLOBALS[tp]types WHERE $critere AND status>0") or dberror();
  if (!mysql_num_rows($result)) die ("type '$type' inconnu");
  list($tplcreation,$context[idtype],$context[type])=mysql_fetch_row($result);
}
#echo $context[type];
$context[id]=$id;


// post-traitement
postprocessing($context);

include ($home."calcul-page.php");
calcul_page($context,$tplcreation);

?>
