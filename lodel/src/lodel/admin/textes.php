<?php
// gere les periodes. L'acces est reserve au superadministrateur.
// assure l'edition, la supression, la restauration des periodes.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR);

include ($home."calcul-page.php");
calcul_page($context,"textes");

?>
