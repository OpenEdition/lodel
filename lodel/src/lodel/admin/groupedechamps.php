<?php
// gere les champs. L'acces est reserve au adminlodelistrateur.
// assure l'edition, la supression, la restauration des champs.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include_once($home."func.php");


$id=intval($id);
$critere=$id>0 ? "id='$id'" : "";

//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  die("pas encore implemente");
  $delete=2; // destruction en -64;
  include ($home."trash.php");
  treattrash("groupesdechamps",$critere);
  return;
}

//
// ordre
//
if ($id>0 && $dir) {
  include_once($home."connect.php");
  # cherche le groupe
  $result=mysql_query ("SELECT classe FROM $GLOBALS[tp]groupesdechamps WHERE $critere") or die (mysql_error());
  list($classe)=mysql_fetch_row($result);
  chordre("groupeschamps",$id,"classe='$classe' AND statut>-64",$dir);
  back();
}


//
// ajoute ou edit
//
if ($edit) { // modifie ou ajoute
  extract_post();
  // validation
  do {
    if (!$context[nom]) $err=$context[erreur_nom]=1;
    if (!$context[titre]) $err=$context[erreur_titre]=1;
    if ($err) break;
    include_once ($home."connect.php");

    if ($id>0) { // il faut rechercher le statut et l'ordre
      $result=mysql_query("SELECT statut,ordre,classe FROM $GLOBALS[tp]groupeschamps WHERE id='$id'") or die (mysql_error());
      list($statut,$ordre,$classe)=mysql_fetch_array($result);
    } else {
      $statut=1;
      if (!$context[classe]) die ("Erreur interne. Il manque la classe dans le formulaire");
      $ordre=get_ordre_max("groupesdechamps"," classe='$context[classe]'");
    }
    if ($protege) $statut=$id && $statut>0 ? 32 : -32;

    mysql_query ("REPLACE INTO $GLOBALS[tp]groupesdechamps (id,nom,titre,classe,ordre,statut) VALUES ('$id','$context[nom]','$context[titre]','$context[classe]','$ordre','$statut')") or die (mysql_error());
    back();
  } while (0);
  // entre en edition
} elseif ($id>0) {
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]groupesdechamps WHERE $critere AND statut>-32") or die (mysql_error());
  $context=array_merge(mysql_fetch_assoc($result),$context);
} else {
  // cherche le classe.
  if ($classe && preg_match("/[\w-]/",$classe)) {
    $context[classe]=$classe;
  } else die("preciser une classe");
}


// post-traitement
posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"groupedechamps");


?>
