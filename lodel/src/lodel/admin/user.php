<?php
// gere les utilisateurs. L'acces est reserve au administrateur.
// assure l'edition, la supression, la restauration des utilisateurs.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
#include_once ($home."func.php");

#if ($context[privilege]>=LEVEL_ADMINLODEL) return;
include ($home."userinc.php");


include ($home."calcul-page.php");
calcul_page($context,"user");

?>
