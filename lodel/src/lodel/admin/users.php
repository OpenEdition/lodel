<?

require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN);

include ($home."calcul-page.php");
calcul_page($context,"users");

?>
