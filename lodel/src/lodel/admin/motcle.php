<?

die("fichier a supprimer du CVS");
// gere les mots cle permanent. L'acces est reserve au superadministrateur.
// assure l'edition, la supression, la restauration des periodes.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include_once ($home."func.php");

$type=TYPE_MOTCLE_PERMANENT;

include("indexl.php");

include ($home."calcul-page.php");
calcul_page($context,"motcle");

?>
