<?
// 
include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);

include ("$home/calcul-page.php");
calcul_page($context,"fonctions_avancees");
?>
