<?php
require("lodelconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMINLODEL);

if (file_exists("CACHE/unlockedinstall")) die("L'installation de LODEL n'est pas terminé. Veuillez la terminer ou éffacer le fichier lodel/admin/CACHE/unlockedinstall.<br><a href=\"install.php\">install.php");



include ($home."calcul-page.php");
calcul_page($context,"index");

?>
