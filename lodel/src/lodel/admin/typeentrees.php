<?

// gere les types de publications. L'acces est reserve au superadministrateur.
// assure l'edition, la supression, la restauration des types de document.

require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN);

include ($home."calcul-page.php");
calcul_page($context,"typeentrees");

?>
