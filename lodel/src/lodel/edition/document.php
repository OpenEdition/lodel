<?php
require_once("siteconfig.php");
include_once ($home."auth.php");
authenticate(LEVEL_REDACTEUR,NORECORDURL);
include_once ($home."func.php");


if ($idtache) {
  // lit la tache en cours
  $tache=get_tache($idtache);

#  print_r($tache);
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
    }
    if ($filename!="processing") {
      $tache["fichierdecoupe$ifile"]="processing";
    }
  } else { // cas normal ou le fichier n'a pas ete decoupe
    $filename=$tache[fichier];
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
    $localcontext[id]=$tache[iddocument];
    $localcontext[statut]=-64; // car le document n'est pas correcte a priori
    // enregistre le nom du fichier original. Enleve le repertoire.

    #echo "sourceoriginale: $tache[sourceoriginale]";
    $localcontext[entite][fichiersource]=$tache[sourceoriginale];
    //    if ($tache[sourceoriginale]) $localcontext[fichiersource]=preg_replace("/.*\//","",$tache[sourceoriginale]);
    
    $text=file_get_contents($filename.".html");
    require_once($home."xmlimport.php");
    $id=enregistre_entite_from_xml($localcontext,$text,"documents");
    update_tache_etape($idtache,3); // etape 3
    $tache[iddocument]=$id;

    // faut-il copier le fichier ?
    if ($tache[source]) {
      $dest="../sources/entite-$id.source";
      if (!(@copy($tache[source],$dest))) die("Le fichier source $tache[source] n'a pas pu etre enregistre dans $dest");
      @chmod($dest,0600);
      $tache[source]=""; // la copie est faite, donc on efface le nom de la source pour la tache
    }

    update_tache_context($idtache,$tache);
  } else {
    $id=$tache[iddocument];
  }
} else {
  // rien a faire
}

require_once($home."entitefunc.php");

$context[id]=$id=intval($id);
####if ($parent) $idparent=$parent;
####$context[idparent]=$idparent=intval($idparent);


if ($id>0 && !$admin) {
  $critere=" AND groupe IN ($usergroupes)";
} else $critere="";

if ($id>0 && $dir) {
  lock_write("entites");
  # cherche le parent
  $result=mysql_query ("SELECT idparent FROM $GLOBALS[tp]entites WHERE id='$id' $critere") or die (mysql_error());
  if (!mysql_num_rows($result)) { die ("vous n'avez pas les droits"); }
  list($idparent)=mysql_fetch_row($result);
  chordre("entites",$id,"idparent='$idparent'",$dir);
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
} elseif ($edit) { // modifie ou ajoute
//
// bloc principale d'extrainfo
// ce bloc peut etre appele par plusieurs scripts.
  extract_post();
  $context[entite][nom]=$context[entite][titre];
  $context[statut]=-1;
  if ($id=enregistre_entite($context,$id,"documents","edition!=''")) { // ca marche... on termine
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
      header("location: document.php?idtache=$idtache"); // on revient dans extrainfo
      return;
    } elseif ($ajouterdocannexe) {
      $redirect="docannexe.php?iddocument=$id";
    } elseif ($visualiserdocument) {
      $redirect="../../document.html?id=$id";
    } else {
      $redirect="";
    }
    // clot la tache et renvoie sur au bon endroit
    include ("abandon.php");
    return;
  }
// sinon recommence
 // edit
} else {
  include_once ($home."connect.php");
  $result=mysql_query("SELECT $GLOBALS[tp]documents.*, $GLOBALS[tp]entites.*  FROM $GLOBALS[documentsjoin] WHERE $GLOBALS[tp]entites.id='$id' $critere") or die (mysql_error());
  if (!mysql_num_rows($result)) { header("location: not-found.html"); return; }
  $context[entite]=mysql_fetch_assoc($result);
  $context[idtype]=$context[entite][idtype];
  $context[nom]=$context[entite][nom];
  extrait_personnes($id,&$context);
  extrait_entrees($id,&$context);
}

$context[idtache]=$idtache;

posttraitement($context);

require_once($home."langues.php");

require ($home."calcul-page.php");
calcul_page($context,"document");

?>
