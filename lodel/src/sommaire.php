<?

include ("lodelconfig.php");
include ("$home/auth.php");
authenticate();
include ("$home/func.php");

$context[id]=$id=intval($id);

// cherche le sommaire precedent et le suivant
include_once("$home/connect.php");

$critere=$visiteur ? "WHERE $GLOBALS[tableprefix]publications.status>-2" : "WHERE $GLOBALS[tableprefix]publications.status>0";
// cherche la publication

$result=mysql_query ("SELECT $GLOBALS[tableprefix]publications.*,tpl FROM $GLOBALS[tableprefix]typepublis,$GLOBALS[tableprefix]publications $critere AND type=$GLOBALS[tableprefix]typepublis.nom AND $GLOBALS[tableprefix]publications.id='$id'") or die (mysql_error());
$context=array_merge($context,mysql_fetch_assoc($result));
if (!$context[id]) { header ("Location: not-found.html"); return; }
$base=$context[tpl];


//
// cherche le numero precedent et le suivant
//
export_prevnextpublication (&$context);


include ("$home/cache.php");

?>
