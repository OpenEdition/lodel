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


include_once ($home."func.php");

if ($classe=="documents") {
  $visualisationscript="document";
  $modificationscript="document";
} elseif ($classe=="publications") {
  $visualisationscript="sommaire";
  $modificationscript="publications";
}


if ($idtache) {
  // lit la tache en cours
  $tache=get_tache($idtache);
  $idtype=0;

#  print_r($tache);
  // cherche le fichier a traiter
  if ($tache[fichierdecoupe1]) {  // ca veut dire qu'on a un plusieurs fichiers a traiter, cherche les suivantes
    #print_r($tache);
    $ifile=0;
    do {
      $ifile++;
      $filename=$tache["fichierdecoupe$ifile"];
    } while ($filename=="finished");
    // est-ce qu'on a encore un fichier a traiter apres celui la ?
    $context[encore]=$tache["fichierdecoupe".($ifile+1)] ? TRUE : FALSE;
    if ($ifile>1) {
      $typedoc=addslashes($tache["typedoc".$ifile]);
      // recherche l'id du type
      $result=mysql_query("SELECT id FROM $GLOBALS[tp]types WHERE type='$typedoc' AND classe='$classe'") or die (mysql_error());
      if (!mysql_num_rows($result)) die("ERROR: Type incorrect \"$typedoc\". Verifier le stylage et le modele editorial");
      list($idtype)=mysql_fetch_row($result);
    } else {
      $idtype=intval($tache[idtype]);
    }
    if ($filename!="processing") {
      $tache["fichierdecoupe$ifile"]="processing";
    }
  } else { // cas normal ou le fichier n'a pas ete decoupe
    $filename=$tache[fichier];
    $idtype=intval($tache[idtype]);
    $context[encore]=0;
    if ($filename!="processing") {
      $tache[fichier]="processing";
    }
  }
#  echo "et la <br>";
#  print_r($tache);
  // on abandonne ?
  if ($cancel) {
    if ($ifile>=2) { // on a traiter le premier fichier, c'est a dire le parent
      //il faut donc supprimer le parent et tous ces fils
      include_once($home."managedb");
      supprime($tache[idparent]);
    }
    include ("abandon.php");
  }

  if ($filename!="processing") {
    $localcontext=array();
    $localcontext[idparent]=$tache[idparent];
    $localcontext[idtype]=$idtype;
    $localcontext[id]=$tache[iddocument];
    $localcontext[statut]=-64; // car le document n'est pas correcte a priori
    // enregistre le nom du fichier original. Enleve le repertoire.

    if (!$tablefields) require($home."tablefields.php");
    if ($tablefields && (
			 !in_array("fichiersource",$tablefields["$GLOBALS[tp]$classe"]) ||
			 !in_array("importversion",$tablefields["$GLOBALS[tp]$classe"]))) {
      die("ERROR: pour que l'importation fonctionne correctement, il est vivement conseiller de créer les champs <b>fichiersource</b> et <b>importversion</b> dans le modèle éditorial. Ces champs doivent avoir le type \"texte court\".<br /><a href=\"../admin\">Adminstration</a>");
    }

    $localcontext[entite][fichiersource]="lodel/sources/".basename($tache[sourceoriginale]);
    $localcontext[entite][importversion]=$tache[importversion];



    // the lodel/sources is fake, only the basename is used.
    
    $text=file_get_contents($filename);
    require_once($home."xmlimport.php");
    $id=enregistre_entite_from_xml($localcontext,$text,$classe);
    update_tache_etape($idtache,3); // etape 3
    $tache[iddocument]=$id;

    // faut-il copier le fichier ?
    if ($tache[source]) {
      $dest=SITEROOT."lodel/sources/entite-$id.source";
      if (!(@copy($tache[source],$dest))) die("Le fichier source $tache[source] n'a pas pu etre enregistre dans $dest");
      @chmod($dest,0666 & octdec($GLOBALS[filemask]));
      $tache[source]=""; // la copie est faite, donc on efface le nom de la source pour la tache
    }

    update_tache_context($idtache,$tache);
  } else {
    $id=$tache[iddocument];
  }
} else { // tache
  // rien a faire
}

require_once($home."entitefunc.php");

$context[id]=$id=intval($id);
$context[idparent]=$idparent=intval($idparent);
$context[idtype]=intval($idtype);

####if ($parent) $idparent=$parent;
####$context[idparent]=$idparent=intval($idparent);


if ($id>0 && !$droitadmin) {
  $critere=" AND groupe IN ($usergroupes)";
} else $critere="";

if ($id>0 && $dir) {
  lock_write("entites");
  # cherche le parent
  $result=mysql_query ("SELECT idparent FROM $GLOBALS[tp]entites WHERE id='$id' $critere") or die (mysql_error());
  if (!mysql_num_rows($result)) { die ("vous n'avez pas les droits"); }
  list($idparent)=mysql_fetch_row($result);
  chordre("entites",$id,"idparent='$idparent'",$dir);
  touch(SITEROOT."CACHE/maj");
  unlock("entites");
  back();

//
// supression et restauration
//
} elseif ($id>0 && ($delete || $restore)) { 
  //include ($home."trash.php");
  die ("il faut utiliser supprime.php");
  return;
//
// ajoute ou edit
//
} elseif ($plus || $reload || $reload2) {
  extract_post();
  extract_files(&$context);
} elseif ($edit) { // modifie ou ajoute
//
// bloc principale d'extrainfo
// ce bloc peut etre appele par plusieurs scripts.
  extract_post();
  extract_files(&$context);

  $context[statut]=-1;
  if ($id=enregistre_entite($context,$id,$classe,"edition!=''")) { // ca marche... on termine
    //
    // termine en redirigeant correctement
    //
    if ($context[encore]) { // on a encore des fichiers a traiter
      if ($ifile==1) { // c'etait le premier, ca sera donc le parent des suivants.
	$tache[idparent]=$id;
      }
      $tache["fichierdecoupe$ifile"]="finished";
      $tache[iddocument]=0; // on a fini, donc on ne garde pas cet id
      update_tache_context($idtache,$tache);
      header("location: $modificationscript.php?idtache=$idtache");
      return;
    } elseif ($ajouterdocannexe) {
      $redirect="docannexe.php?idparent=$id";
    } elseif ($visualiserdocument) {
      $redirect="../../$visualisationscript.$extensionscripts?id=$id";
    } else {
      $redirect="";
    }
    // clot la tache et renvoie sur au bon endroit
    include ("abandon.php");
    return;
  }
// sinon recommence
 // edit
} elseif ($id>0) {
  include_once ($home."connect.php");
  $result=mysql_query("SELECT $GLOBALS[tp]$classe.*, $GLOBALS[tp]entites.*  FROM $GLOBALS[tp]entites INNER JOIN $GLOBALS[tp]$classe ON $GLOBALS[tp]entites.id=$GLOBALS[tp]$classe.identite WHERE $GLOBALS[tp]entites.id='$id' $critere") or die (mysql_error());
  if (!mysql_num_rows($result)) { header("location: not-found.html"); return; }
  $context[entite]=mysql_fetch_assoc($result);
  $context[idtype]=$context[entite][idtype];
  $context[identifiant]=$context[entite][identifiant];
  extrait_personnes($id,&$context);
  extrait_entrees($id,&$context);
} else {
#  require_once ($home."validfunc.php");
#  $context[type]=trim($type);
#  if (!$context[type] || !isvalidtype($context[type])) die("preciser un type valide");
#  $result=mysql_query("SELECT id,tplcreation FROM $GLOBALS[tp]types WHERE type='$context[type]' AND statut>0") or die (mysql_error());
#  if (!mysql_num_rows($result)) die("type inconnu $context[type]");
#  list($context[idtype],$context[tplcreation])=mysql_fetch_row($result);
  $context[entite]=array();
}

if (!$context[tplcreation]) {
  if (!$context[idtype]) die("preciser un type in document.php");
  $result=mysql_query("SELECT tplcreation FROM $GLOBALS[tp]types WHERE id='$context[idtype]' AND statut>0") or die (mysql_error());
  list($context[tplcreation])=mysql_fetch_row($result);
}

$context[idtache]=intval($idtache);

posttraitement($context);

require_once($home."langues.php");

require ($home."calcul-page.php");
calcul_page($context,$context[tplcreation]);


function extract_files(&$context,$classe="documents")

{
  global $HTTP_POST_FILES,$home;

#  echo "la";
#  print_r($HTTP_POST_FILES);
#  echo "ici";
#  print_r($_FILES);
  if (!$_FILES && !$HTTP_POST_FILES) return;
  $files=$_FILES[entite] ? $_FILES[entite] : $HTTP_POST_FILES[entite];
  if (!$files || !$files['tmp_name']) return;

#  print_r($files);

  // remove files with error or not uploaded
  foreach ($files['tmp_name'] as $nom=>$f) {
    if (!$f || $files['error'][$nom]!=0) { // uploaded ?
      // nothing to do
      unset($files['tmp_name'][$nom]);
      // take the previous value
      $context['entite'][$nom]=$context['entite'][$nom][previousvalue];
      // check for hack or bug
      if ($context['entite'][$nom] && !preg_match("/^docannexe\/(image|fichier)\/[^\.\/]+\/[^\/]+$/",$context['entite'][$nom])) die("ERROR: invalid filename");
    }
  }
  // if no files to upload, return.
  if (!$files['tmp_name']) return;

  require_once($home."connect.php");
  // look for the field we have to download.
  $result=mysql_query("SELECT $GLOBALS[tp]champs.nom,type FROM $GLOBALS[tp]champs,$GLOBALS[tp]groupesdechamps WHERE idgroupe=$GLOBALS[tp]groupesdechamps.id AND classe='$classe' AND $GLOBALS[tp]champs.statut>0 AND $GLOBALS[tp]groupesdechamps.statut>0 AND edition!='' AND (type='image' OR type='fichier')") or die (mysql_error());

  if (!mysql_num_rows($result)) return;

  require_once($home."func.php");
  
  // transfert
  while (list($nom,$type)=mysql_fetch_row($result)) {
    if (!$files['tmp_name'][$nom]) continue;     // should not happend

    if (!$tmpdir[$type]) { // check if the tmpdir is defined
      // look for a unique dirname.
      do {
	$tmpdir[$type]="docannexe/$type/tmpdir-".rand();
      } while (file_exists(SITEROOT.$tmpdir[$type]));
    }
    if ($type=="fichier") {
      $context['entite'][$nom]=save_annex_file($tmpdir[$type],$files['tmp_name'][$nom],$files['name'][$nom]);
    } elseif ($type=="image") {
      $context['entite'][$nom]=save_annex_image($tmpdir[$type],$files['tmp_name'][$nom],$nom);
    }
  }
}


?>
