<?

// gere les periodes. L'acces est reserve au superadministrateur.
// assure l'edition, la supression, la restauration des periodes.

require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN);

include ($home."calcul-page.php");
calcul_page($context,"periodes");

?>
