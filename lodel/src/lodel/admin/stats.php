<?

// Affiche des statistiques sur la revue.

include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);

include_once ("$home/connect.php");

//////////////////////////////////  Documents
// Nombre total de documents
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tableprefix]documents") or die (mysql_error());
list($context[nbdocs])=mysql_fetch_row($result);
$nbdocs=$context[nbdocs];
if(!$nbdocs) $nbdocs=1;  // Evite les divisions par zéro

// Nombre de documents en brouillon
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tableprefix]documents WHERE status<-1") or die (mysql_error());
list($context[nbdocsbrouillons])=mysql_fetch_row($result);
$nbdocsbrouillons=$context[nbdocsbrouillons];
// pourcentage
$context[percdocsbrouillons]=round(($nbdocsbrouillons*100)/$nbdocs);

// Nombre de documents prêt à être publiés
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tableprefix]documents WHERE status>-32 AND status<0") or die (mysql_error());
list($context[nbdocsprets])=mysql_fetch_row($result);
$nbdocsprets=$context[nbdocsprets];
// pourcentage
$context[percdocsprets]=round(($nbdocsprets*100)/$nbdocs);

// Nombre de documents publiés
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tableprefix]documents WHERE status>0") or die (mysql_error());
list($context[nbdocspublies])=mysql_fetch_row($result);
$nbdocspublies=$context[nbdocspublies];
// pourcentage
$context[percdocspublies]=round(($nbdocspublies*100)/$nbdocs);

//////////////////////////////////  Publications
// Nombre total de publications
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tableprefix]publications") or die (mysql_error());
list($context[nbpublis])=mysql_fetch_row($result);
$nbpublis=$context[nbpublis];
if(!$nbpublis) $nbpublis=1;  // Evite les divisions par zéro

// Nombre de publications en brouillon
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tableprefix]publications WHERE status<-1") or die (mysql_error());
list($context[nbpublisbrouillons])=mysql_fetch_row($result);
$nbpublisbrouillons=$context[nbpublisbrouillons];
// pourcentage
$context[percpublisbrouillons]=round(($nbpublisbrouillons*100)/$nbpublis);

// Nombre de publications prêt à être publiées
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tableprefix]publications WHERE status>-32 AND status<0") or die (mysql_error());
list($context[nbpublispretes])=mysql_fetch_row($result);
$nbpublispretes=$context[nbpublispretes];
// pourcentage
$context[percpublispretes]=round(($nbpublispretes*100)/$nbpublis);

// Nombre de publications publiées
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tableprefix]publications WHERE status>0 AND status<32") or die (mysql_error());
list($context[nbpublispubliees])=mysql_fetch_row($result);
$nbpublispubliees=$context[nbpublispubliees];
// pourcentage
$context[percpublispubliees]=round(($nbpublispubliees*100)/$nbpublis);

// Nombre de publications publiées protégées
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tableprefix]publications WHERE status>1") or die (mysql_error());
list($context[nbpublisprotegees])=mysql_fetch_row($result);
$nbpublisprotegees=$context[nbpublisprotegees];
// pourcentage
$context[percpublisprotegees]=round(($nbpublisprotegees*100)/$nbpublis);

//////////////////////////////////  Types documents
// Nombre de type de documents différents
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tableprefix]typedocs") or die (mysql_error());
list($context[nbtypedocs])=mysql_fetch_row($result);

function boucle_nom_occ_type_doc(&$context,$funcname)
{
	$result=mysql_query("SELECT type AS nomtypedoc, COUNT(type) AS nbtypedoc FROM $GLOBALS[tableprefix]documents GROUP BY type") or die (mysql_error());
	while($row=mysql_fetch_array($result,MYSQL_ASSOC))
	{
		$context[nomtypedoc]=$row[nomtypedoc];
		$context[nbtypedoc]=$row[nbtypedoc];
    	call_user_func("code_boucle_$funcname",$context);
	}
}

//////////////////////////////////  Types publications
// Nombre de type de publications différentes
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tableprefix]typepublis") or die (mysql_error());
list($context[nbtypepublis])=mysql_fetch_row($result);

function boucle_nom_occ_type_publi(&$context,$funcname)
{
	$result=mysql_query("SELECT type AS nomtypepubli, COUNT(type) AS nbtypepubli FROM $GLOBALS[tableprefix]publications GROUP BY type") or die (mysql_error());
	while($row=mysql_fetch_array($result,MYSQL_ASSOC))
	{
		$context[nomtypepubli]=$row[nomtypepubli];
		$context[nbtypepubli]=$row[nbtypepubli];
    	call_user_func("code_boucle_$funcname",$context);
	}
}

include ("$home/calcul-page.php");
calcul_page($context,"stats");

?>
