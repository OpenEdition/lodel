<?php
// gere les utilisateurs. L'acces est reserve au adminlodelistrateur.
// assure l'edition, la supression, la restauration des utilisateurs.

require("lodelconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMINLODEL);
include_once ($home."func.php");

$url_retour="users.php";
$context[privilege]=LEVEL_ADMINLODEL;

include ($home."userinc.php");


include ($home."calcul-page.php");
calcul_page($context,"user");

?>
