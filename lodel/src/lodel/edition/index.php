<?

require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_VISITEUR);

//if ($id) { // c'est l'id et non le parent qu'on veut... il faut chercher le parent
//  $id=intval($id);
//  $result=mysql_query ("SELECT parent FROM $GLOBALS[tableprefix]publications WHERE id='$id'") or die (mysql_error());
//  if (mysql_num_rows($result)<1) { header ("Location: not-found.html"); return; }
//  list($parent)=mysql_fetch_row($result);
//}
//

//// workaround temporaire. TRES TEMPORAIRE
//if ($id) $parent=$id;
//
//$context[id]=$context[parent]=$parent=intval($parent);

$context[id]=$id=intval($id);

if ($id) {
  $result=mysql_query ("SELECT tpledit FROM $GLOBALS[tableprefix]typepublis,$GLOBALS[tableprefix]publications WHERE $GLOBALS[tableprefix]publications.id='$id' AND type=$GLOBALS[tableprefix]typepublis.nom") or die (mysql_error());
  if (mysql_num_rows($result)<1) { header ("Location: not-found.html"); return; }
  list($base)=mysql_fetch_row($result);
} else {
  $base="edition";
}

include ($home."boucles.php");
include ($home."calcul-page.php");
calcul_page($context,$base);

?>
