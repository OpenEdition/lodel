<?

// gere les types de documents. L'acces est reserve au administrateur.

require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);

$classe="documents";
$context[tplcreation]="chargement";
include ($home."type.php");

include ($home."calcul-page.php");
calcul_page($context,"typedoc");


?>
