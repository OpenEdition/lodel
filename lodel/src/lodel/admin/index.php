<?


require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN);

include ($home."calcul-page.php");
calcul_page($context,"index");

?>
