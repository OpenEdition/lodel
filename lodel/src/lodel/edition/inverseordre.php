<?

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include($home."func.php");


die("a reecrire avec entite");
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
