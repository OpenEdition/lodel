<?php
// gere les types de publications. L'acces est reserve au administrateur.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);

$classe="publications";
include ($home."type.php");

include ($home."calcul-page.php");
calcul_page($context,"typepubli");


?>
