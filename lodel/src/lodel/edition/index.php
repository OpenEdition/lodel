<?php
require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_VISITEUR);


$context[id]=$id=intval($id);
$context[idtype]=0;

if ($id) {
  do {
    $result=mysql_query ("SELECT tpledition,idparent,idtype FROM $GLOBALS[entitestypesjoin] WHERE $GLOBALS[tp]entites.id='$id' AND $GLOBALS[tp]types.classe='publications'") or die (mysql_error());
    if (mysql_num_rows($result)<1) { header ("Location: not-found.html"); return; }
    list($base,$idparent,$context[idtype])=mysql_fetch_row($result);
    if (!$base) $context[id]=$id=$idparent;
  } while (!$base);
} else {
  $base="edition";
}

include ($home."boucles.php");
include ($home."calcul-page.php");
calcul_page($context,$base);

?>
