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
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ($home."func.php");

$idparent=intval($idparent);
$id=intval($id);
$idtype=intval($idtype);
$tplcreation="";

//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  include ($home."trash.php");
  treattrash("entites");
  return;
}

$critere="id='$id'";

//
// ordre
//
if ($id>0 && $dir) {
  # cherche le parent
  $result=mysql_query ("SELECT idparent FROM $GLOBALS[tp]entites WHERE id='$id'") or die (mysql_error());
  list($idparent)=mysql_fetch_row($result);
  // recupere les type de documents annexe
  $result=mysql_query ("SELECT id FROM $GLOBALS[tp]types WHERE type LIKE 'documentannexe-%'") or die (mysql_error());
  $idtypes=array();
  while ($row=mysql_fetch_assoc($result)) { array_push($idtypes,$row[id]); }
  chordre("entites",$id,"idparent='$idparent' AND idtype IN (".join(",",$idtypes).")",$dir);
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
    $result=mysql_query("SELECT type FROM $GLOBALS[tp]types WHERE id='$idtype' AND statut>0") or die (mysql_error());
  if (!mysql_num_rows($result)) die ("type '$type' inconnu (1)");
  list($type)=mysql_fetch_row($result);


  if ($type=="documentannexe-lienfichier") {
    // charge le fichier si necessaire
      if ($docfile && $docfile!="none") {
	if ($id>0) { // we know the document id, we can copy it.
	  $lien=save_annex_file($id,$docfile,$docfile_name);
	} else {
	  $lien="temporaire";
	}
	// else, process once we know the id of the document
      } else {
	// recherche le lien
	include_once ($home."connect.php");
	$result=mysql_query("SELECT lien FROM $GLOBALS[tp]documents WHERE identite='$id'") or die (mysql_error());
	list($lien)=mysql_fetch_row($result);
      }
    } elseif ($type=="documentannexe-liendocument") {
      $lien=intval($context[lien]);
      // cherche si le documents existe
      $result=mysql_query("SELECT identite FROM $GLOBALS[tp]documents WHERE identite='$lien'") or die (mysql_query());
      if (mysql_num_rows($result)<1) {
	$err=$context[erreur_documentdontexist]=1;
      } else {
	$lien=makeurlwithid ("document",$lien);
      }
    } elseif ($type=="documentannexe-lienpublication") {
      $lien=intval($context[lien]);
      // cherche si le documents existe
      $result=mysql_query("SELECT identite FROM $GLOBALS[tp]publications WHERE identite='$lien'") or die (mysql_query());
      if (mysql_num_rows($result)<1) {
	$err=$context[erreur_publicationdontexist]=1;
      } else {
	$lien=makeurlwithid ("sommaire",$lien);
      }
    } elseif ($type=="documentannexe-lienexterne") {
      // verifie l'adresse
      $lien=$context[lien];
      if ($lien && !preg_match("/http:\/\//i",$lien)) $lien="http://".$lien;
      $url=parse_url($lien);
      if (!$url[host] || !preg_match("/^[\w-]+(\.[\w-]+)+$/",$url[host])) { $context[erreur_urlinvalide]=$err=1; }
    } else {
      die ("erreur type incorrecte");
    }
    if (!$lien) { $context[erreur_lieninexistant]=$err=1; }
    // fin de chargement

    if ($err) break;
    require_once ($home."connect.php");
    require_once($home."entitefunc.php");
    $context[entite][nom]=$context[titre];
    $context[entite][titre]=$context[titre];
    $context[entite][commentaire]=$context[commentaire];
    $context[entite][lien]=$lien;
    $context[idparent]=$idparent;

    $newid=enregistre_entite(&$context,$id,"documents","",TRUE);
    $newid=enregistre_entite(&$context,$id,"documents","",FALSE); // ne retourne pas quand il y a une erreur !

#    if ($newid===FALSE) {
#      print_r($context[erreur]);
#      foreach ($context[erreur] as $champ=>$msg) { $context["erreur_".$champ]=$msg; }
#      break;
#    }
#    echo "newid:$newid<br/>";

    // New document, now we know the id, let's copy the uploaded file.
    if (!$id && $newid &&
	$type=="documentannexe-lienfichier" &&
	$docfile && $docfile!="none") { // we know the document id, we can copie it.
      $lien=save_annex_file($newid,$docfile,$docfile_name);
      mysql_query("UPDATE $GLOBALS[tp]documents SET lien='$lien' WHERE identite=$newid");
    }

    back();
  } while (0);
  // entre en edition
} elseif ($id>0) {
  $id=intval($id);
  include_once ($home."connect.php");
  $result=mysql_query("SELECT $GLOBALS[tp]documents.*,$GLOBALS[tp]entites.*,$GLOBALS[tp]types.type,tplcreation FROM $GLOBALS[documentstypesjoin] WHERE $GLOBALS[tp]entites.id='$id'") or die (mysql_error());
  $context=array_merge($context,mysql_fetch_assoc($result));
  if ($context[type]=="documentannexe-liendocument" || $context[type]=="documentannexe-lienpublication") {
    // recupere le numero
    preg_match("/id=(\d+)\b/",$context[lien],$result);
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
    $result=mysql_query("SELECT tplcreation,id, type FROM $GLOBALS[tp]types WHERE $critere AND statut>0") or die (mysql_error());
  if (!mysql_num_rows($result)) die ("type '$type' inconnu");
  list($tplcreation,$context[idtype],$context[type])=mysql_fetch_row($result);
}

$context[id]=$id;


// post-traitement
posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,$tplcreation);

?>
