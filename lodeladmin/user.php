<?

// gere les utilisateurs. L'acces est reserve au superadministrateur.
// assure l'edition, la supression, la restauration des utilisateurs.

require("lodelconfig.php");
include ($home."auth.php");
authenticate(LEVEL_SUPERADMIN);
include_once ($home."func.php");

$url_retour="users.php";
$context[privilege]=LEVEL_SUPERADMIN;

include ($home."userinc.php");


include ($home."calcul-page.php");
calcul_page($context,"user");

?>
