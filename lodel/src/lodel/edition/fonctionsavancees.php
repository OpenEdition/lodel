<?phprequire("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_VISITEUR);

// Gère les fonctions avancées pour les publications et les documents.
// On peut passer 2 paramètres différents à ce script :
// id pour un document
// publication pour une publication

$critere=$admin ? "" : "groupe IN ($usergroupes) AND ";

if ($id) { // document
   $classe="documents";
   $id=intval($id);
   $base="fonctionsavancees-document";
} elseif ($publication) { // publication
   $classe="publications";
   $id=intval($publication);
   $base="fonctionsavancees-publication";
} else { die("id ou publication ?"); }


include_once ($home."connect.php");
$result=mysql_query("SELECT *, type  FROM $GLOBALS[tp]types, $GLOBALS[tp]entites, $GLOBALS[tp]$classe WHERE $GLOBALS[tp]entites.id='$id' AND identite='$id' AND idtype=$GLOBALS[tp]types.id") or die (mysql_error());
$context=array_merge($context,mysql_fetch_assoc($result));


include ($home."calcul-page.php");
calcul_page($context,$base);

?>
