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

require_once("siteconfig.php");
include_once ($home."auth.php");
authenticate(LEVEL_REDACTEUR,NORECORDURL);
include_once ($home."func.php");

// lit la tache en cours
$tache=get_tache($id);

// cherche le fichier a traiter
if ($tache[fichierdecoupe1]) {  // ca veut dire qu'on a un plusieurs fichiers a traiter, cherche les suivantes
  $ifile=0;
  do {
    $ifile++;
    $filename=$tache["fichierdecoupe$ifile"];
  } while ($filename=="finished");
  // est-ce qu'on a encore un fichier a traiter apres celui la ?
  $context[encore]=$tache["fichierdecoupe".($ifile+1)] ? TRUE : FALSE;
  if ($ifile>1) {
    $context[typedoc]=$tache["typedoc".$ifile];
    if ($context[typedoc]) $context[typedocfixe]=1;
#    print_r($tache);
#    echo "<br>typedoc:$context[typedoc]";
  }
} else { // cas normal ou le fichier n'a pas ete decoupe
  $filename=$tache[fichier];
  $context[encore]=0;
}

 // on abandonne ?
if ($cancel) {
  if ($ifile>=2) { // on a traiter le premier fichier, c'est a dire le parent
    //il faut donc supprimer le parent et tous ces fils
    include_once($home."managedb");
    supprime($tache[idparent]);
  }
  include ("abandon.php");
}




require_once($home."extrainfofunc.php");
//
// bloc principale d'extrainfo
// ce bloc peut etre appele par plusieurs scripts.

if ($edit || $plus || $reload) {
  if (ei_edition($filename,$tache,$document,$text,$entrees,$autresentrees,$plus)) { // ca marche... on termine
    $iddocument=ei_enregistrement($filename,$tache,$document,$text);
    //
    // termine en redirigeant correctement
    //
    if ($context[encore]) { // on a encore des fichiers a traiter
      if ($ifile==1) { // c'etait le premier, c'est donc ca sera le parent des suivants.
	$tache[idparent]=$iddocument;
      }
      $tache["fichierdecoupe$ifile"]="finished";
      update_tache_context($id,$tache);
      header("location: extrainfo.php?id=$id"); // on revient dans extrainfo
      return;
    } elseif ($ajouterdocannexe) {
      $redirect="docannexe.php?iddocument=$iddocument";
    } elseif ($visualiserdocument) {
      $redirect="../../document.html?id=$iddocument";
    } else {
      $redirect="";
    }
    // clot la tache et renvoie sur au bon endroit
    include ("abandon.php");
    return;
  }
// sinon recommence
} // edit
else {
  ei_pretraitement($filename,$tache,$context,$text);
}


$balises_sstag=array("typedoc");
foreach ($balises_sstag as $b) {
  $context[$b]=strip_tags($context[$b]);
}

posttraitement($context);


// cherche le idtype
$result=mysql_query("SELECT id FROM $GLOBALS[tp]types WHERE type='$context[typedoc]'") or die(mysql_error());
list($context[idtype])=mysql_fetch_row($result);



update_tache_etape($id,3); // etape 3
$context[id]=$id;

include ($home."calcul-page.php");
calcul_page($context,"extrainfo");


?>
