<?

require_once("revueconfig.php");
include_once ($home."auth.php");
authenticate(LEVEL_REDACTEUR,NORECORDURL);
include_once ($home."func.php");

if ($cancel) include ("abandon.php");

# lit la tache en cours
$row=get_tache($id);

$filename=$row[fichier];

require_once($home."extrainfofunc.php");
//
// bloc principale d'extrainfo
// ce bloc peut etre appele par plusieurs scripts.

if ($edit || $plusauteurs) {
  $iddocument=ei_edition($filename,$row,$context,$text,$motcles,$periodes,$geographies);
  if ($iddocument) { // ca marcher... on termine
    //
    // termine en redirigeant correctement
    // 
    if ($ajouterdocannexe) {
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
  ei_pretraitement($filename,$row,$context,$text);
}


$balises_sstag=array("typedoc");
foreach ($balises_sstag as $b) {
  $context[$b]=strip_tags($context[$b]);
}

posttraitement($context);


update_tache_etape($id,3); // etape 3
$context[id]=$id;
$context[interactive]=$interactive;

include ($home."calcul-page.php");
calcul_page($context,"extrainfo");


?>
