<?php
// la publication des publications et documents
// assure la coherence de la base de donnee


// -64  à la poubelle
// -32  brouillon non publiable
// -1   non publié
//  1   publié
// +32  publié protegé


// pour les publications dans l'url on peut recevoir
// online: si vrai met le statut a 1 si faux met le statut a 0
// confirmation: si vrai alors depublie meme si les publications sont protegees

// pour les documents dans l'url on peut recevoir
// online


require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ($home."func.php");

include_once ($home."connect.php");

if ($cancel) back();

$statut=$online ? 1 : -1;

// l'utilisation dans ce script d'un statut de +32 ou -32 n'est pas recommander parce qu'il opere de facon recurrente.
// utiliser plutot statut.php pour ajuster le statut.

if ($publication) {
  $id=intval($publication);
} else {
  $id=intval($id);
}

require($home."managedb.php");

if (!publi($id,$statut,$confirmation)) { // publications protegees ?
  $context[id]=$id;
  // post-traitement
  posttraitement($context);

  include ($home."calcul-page.php");
  calcul_page($context,"publi_erreur");
  return;
}

 
unlock();

back();
return;


?>
