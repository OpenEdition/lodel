<?php
// gere les types de document. L'acces est reserve au superadministrateur.
// assure l'edition, la supression, la restauration des types de document.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN);

include ($home."calcul-page.php");
calcul_page($context,"typedocs");

?>
