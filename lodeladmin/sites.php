<?php
require("lodelconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMINLODEL);

include ($home."calcul-page.php");
calcul_page($context,"sites");

?>
