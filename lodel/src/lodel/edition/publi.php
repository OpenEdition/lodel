<?php
// la publication des publications et documents
// assure la coherence de la base de donnee


// -64  à la poubelle
// -32  brouillon non publiable
// -1   non publié
//  1   publié
// +32  publié protegé


// pour les publications dans l'url on peut recevoir
// online: si vrai met le status a 1 si faux met le status a 0
// confirmation: si vrai alors depublie meme si les publications sont protegees

// pour les documents dans l'url on peut recevoir
// online


require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ($home."func.php");

include_once ($home."connect.php");

if ($cancel) back();

$status=$online ? 1 : -1;

// l'utilisation dans ce script d'un status de +32 ou -32 n'est pas recommander parce qu'il opere de facon recurrente.
// utiliser plutot status.php pour ajuster le status.

if ($publication) {
  $id=intval($publication);
} else {
  $id=intval($id);
}

require($home."managedb.php");

if (!publi($id,$status,$confirmation)) { // publications protegees ?
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
