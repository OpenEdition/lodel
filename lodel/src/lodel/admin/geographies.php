<?

// gere les geographies. L'acces est reserve au superadministrateur.
// assure l'edition, la supression, la restauration des geographies.

include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_ADMIN);

include ("$home/calcul-page.php");
calcul_page($context,"geographies");

?>
