<?

// Affiche des statistiques sur la revue.

include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);

include_once ("$home/connect.php");

$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tableprefix]documents") or die (mysql_error());
list($context[nbdocs])=mysql_fetch_row($result);
$nbdocs=$context[nbdocs];

$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tableprefix]documents WHERE status<-1") or die (mysql_error());
list($context[nbdocsbrouillons])=mysql_fetch_row($result);
$nbdocsbrouillons=$context[nbdocsbrouillons];
$context[percdocsbrouillons]=round(($nbdocsbrouillons*100)/$nbdocs);

$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tableprefix]documents WHERE status>-32 AND status<0") or die (mysql_error());
list($context[nbdocsprets])=mysql_fetch_row($result);
$nbdocsprets=$context[nbdocsprets];
$context[percdocsprets]=round(($nbdocsprets*100)/$nbdocs);

$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tableprefix]documents WHERE status>0") or die (mysql_error());
list($context[nbdocspublies])=mysql_fetch_row($result);
$nbdocspublies=$context[nbdocspublies];
$context[percdocspublies]=round(($nbdocspublies*100)/$nbdocs);

include ("$home/calcul-page.php");
calcul_page($context,"stats");

?>
