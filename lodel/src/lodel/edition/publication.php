<?php
// gere les publications. L'acces est reserve aux administrateurs du site.
// assure l'edition, la supression, la restauration des publications.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include($home."func.php");
include($home."entitefunc.php");

// calcul le critere pour determiner le user a editer, restorer, detruire...

$context[id]=$id=intval($id);
if ($parent) { die("veuillez changer l'appel, parent =>idparent");$idparent=$parent; }
$context[idparent]=$idparent=intval($idparent);


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
  include ($home."trash.php");
  die ("il faut utiliser supprime.php a la place");
  return;
//
// ajoute ou edit
//
} elseif ($plus) {
  extract_post();
} elseif ($edit) { // modifie ou ajoute

  extract_post();
  // edition et sort si ca marche
  if (enregistre_entite($context,$id,"publications")) back();

} elseif ($id>0) {
  include_once ($home."connect.php");
  $result=mysql_query("SELECT $GLOBALS[tp]publications.*, $GLOBALS[tp]entites.*, type  FROM  $GLOBALS[publicationstypesjoin] WHERE $GLOBALS[tp]entites.id='$id'  $critere") or die (mysql_error());
  $context[entite]=mysql_fetch_assoc($result);
  $context[idparent]=$context[entite][idparent];
  $context[type]=$context[entite][type];
  $context[idtype]=$context[entite][idtype];
  $context[nom]=$context[entite][nom];
  extrait_personnes($id,&$context);
  extrait_entrees($id,&$context);
} else {
  include_once ($home."textfunc.php");
  $context[type]=trim(rmscript(strip_tags($type)));
  if ($context[type]) {
    $result=mysql_query("SELECT id FROM $GLOBALS[tp]types WHERE type='$context[type]' AND statut>0") or die (mysql_error());
    if (!mysql_num_rows($result)) die("type inconnu $context[type]");
    list($context[idtype])=mysql_fetch_row($result);
  }
  $context[entite]=array();
}


// post-traitement
posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"publication");



?>
