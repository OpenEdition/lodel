<?php
// gere les periodes. L'acces est reserve au adminlodelistrateur.
// assure l'edition, la supression, la restauration des periodes.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ($home."func.php");

// calcul le critere pour determiner le texte a editer, restorer, detruire...
$id=intval($id);
if ($id>0) {
  $critere="id='$id'";
} else $critere="id='$id'";
if (!$restore) $critere.=" AND statut>0";

if ($id>0 && ($delete || $restore)) { 
  include ($home."trash.php");
  treattrash("textes",$critere);
  return;
//
// ajoute ou edit
//
} elseif ($edit) { // modifie ou ajoute
  include_once ($home."func.php");
  extract_post();
  // validation
  do {
    if (!$context[nom] || !preg_match("/^[\w\s]+$/",utf8_decode($context[nom]))) $err=$context[erreur_nom]=1;
    if ($err) break;

    include_once ($home."connect.php");
    $result=mysql_query ("SELECT id FROM $GLOBALS[tp]textes WHERE nom='$context[nom]' AND id!='$id'") or die (mysql_error());
    if (mysql_num_rows($result)>0) $err=$context[erreur_nom_existe]=1;
    if ($err) break;
    

    mysql_query ("REPLACE INTO $GLOBALS[tp]textes (id,nom,texte) VALUES ('$id','$context[nom]','$context[texte]')") or die (mysql_error());

    back();

  } while (0);
  // entre en edition
} elseif ($id>0) {
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]textes WHERE $critere") or die ("erreur SELECT");
  $context=array_merge($context,mysql_fetch_assoc($result));
}

// post-traitement
$context[id]=$id;
posttraitement($context);


include ($home."calcul-page.php");
calcul_page($context,"texte");

?>





