<?

require("lodelconfig.php");
include ($home."auth.php");
authenticate(LEVEL_SUPERADMIN);

include ($home."calcul-page.php");
calcul_page($context,"users");

?>
