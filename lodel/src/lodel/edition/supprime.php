<?php
// suppression de documents et de publication en assurant la coherence de la base

require("siteconfig.php");
require ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
require_once ($home."func.php");


if ($publication) $id=$publication;
$id=intval($id);


if ($supprime || $confirmation) {
  include_once ($home."connect.php");
  include ($home."managedb.php");

  if (!supprime($id,$confirmation)) {
    $context[id]=$id;
    // post-traitement
    posttraitement($context);
    
    include ($home."calcul-page.php");
    calcul_page($context,"supprime_erreur");
    return;
  }
  back();
  return;
}

if ($publication>0) { # recupere les infos du publication
  $result=mysql_query("SELECT * FROM $GLOBALS[publicationsjoin] WHERE id='$publication'") or die (mysql_error());
} else { # recupere les infos du document
  $result=mysql_query("SELECT * FROM $GLOBALS[documentsjoin] WHERE id='$id'") or die (mysql_error());
}

if (!($row=mysql_fetch_assoc($result))) { header("location: not-found.html"); }
$context=array_merge($context,$row);

posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"supprime");

?>
