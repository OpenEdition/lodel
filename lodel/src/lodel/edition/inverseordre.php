<?

// gere les publications. L'acces est reserve aux administrateurs de la revue.
// assure l'edition, la supression, la restauration des publications.

require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include($home."func.php");

// calcul le critere pour determiner le user a editer, restorer, detruire...

$parent=intval($parent);


$critere="parent='$parent' AND status>-64";
if (!$admin) $critere.=" AND groupe IN ($usergroupes)";


lock_write("publications");
# cherche tous les enfants
$result=mysql_query ("SELECT max(ordre) FROM $GLOBALS[tp]publications WHERE $critere") or die (mysql_error());
if (!mysql_num_rows($result)) { die ("vous n'avez pas les droits"); }
list($max)=mysql_fetch_row($result);
mysql_query("UPDATE $GLOBALS[tp]publications SET ordre=$max-ordre WHERE $critere") or die (mysql_error());

unlock("publications");
back();

?>
