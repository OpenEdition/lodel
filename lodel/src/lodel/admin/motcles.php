<?

// gere les motcles. L'acces est reserve au superadministrateur.
// assure l'edition, la supression, la restauration des motcles.

require("revueconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_VISITEUR);

include ("$home/calcul-page.php");
calcul_page($context,"motcles");

?>
