<?

// charge le fichier xml et
require("siteconfig.php");
include ($home."auth.php");
authenticate();
include ($home."func.php");

$context[id]=$id=intval($id);

include_once($home."connect.php");


$critere=$visiteur ? "" : "AND $GLOBALS[tp]entites.status>0 AND $GLOBALS[tp]types.status>0";

//
// cherche le document, et le template
//

$result=mysql_query("SELECT $GLOBALS[tp]documents.*,$GLOBALS[tp]entites.*,tpl,type , datepubli,(datepubli<=NOW()) as textepublie FROM $GLOBALS[documentstypesjoin] WHERE $GLOBALS[tp]entites.id='$id' $critere") or die (mysql_error());
if (mysql_num_rows($result)<1) { header ("Location: not-found.html"); return; }
$context=array_merge($context,mysql_fetch_assoc($result));
if (!$context[tpl]) { 
  header("location: ".makeurl("document",$context[idparent]));
  return;
}

$base=$context[tpl];

//
// charge le fichier XML et extrait les balises
//
if (!file_exists("lodel/txt/r2r-$id.xml")) { header ("Location: not-found.html"); return; }
$text=join("",file("lodel/txt/r2r-$id.xml"));

require ($home."balises.php");
require ($home."xmlfunc.php");


$balises=$balisesdocument_nonlieautexte;
array_push($balises,"surtitre","titre","soustitre");

if ($context[textepublie] || $visiteur) $balises=array_merge($balises,$balisesdocument_lieautexte);

$context=array_merge($context,extract_xml($balises,$text));

//
// cherche s'il y a des documents annexe et combien
//
$result=mysql_query("SELECT count(*) FROM $GLOBALS[entitestypesjoin] WHERE idparent='$id' AND $GLOBAL[tp]entites.status>0 AND type LIKE 'documentannexe-%'") or die (mysql_error());
list($context[documentsannexes])=mysql_fetch_row($result);
//
// cherche l'article precedent et le suivant
//

// suivant:

$querybase="SELECT $GLOBALS[tp]entites.id FROM $GLOBALS[entitestypesjoin] WHERE idparent='$context[idparent]' AND";

$nextid=0;
do {// exception
  $result=mysql_query ("$querybase $GLOBALS[pt]entites.ordre>$context[ordre] $critere ORDER BY $GLOBALS[pt]entites.ordre LIMIT 0,1") or die (mysql_error());
  if (mysql_num_rows($result)) {
    list($nextid)=mysql_fetch_row($result);
    break;
  }

  // ok, on a pas trouve on cherche alors le pere suivant et son premier fils (e2)
  $result=mysql_query ("SELECT e2.id FROM $GLOBALS[entitestypesjoin], $GLOBALS[tp]relations, $GLOBALS[tp]entites as e2 WHERE id2='$context[id]' AND degres=2 AND $GLOBALS[tp]entites.idparent=id1 AND type='regroupement' AND  $GLOBALS[tp]entites.ordre>$context[ordre] $critere ORDER BY $GLOBALS[tp]entites.ordre, e2.ordre LIMIT 0,1") or die (mysql_error());
  if (mysql_num_rows($result)) {
    list($nextid)=mysql_fetch_row($result);
    break;
  }
} while (0);

if ($nextid) $context[nextdocument]=makeurlwithid("document",$nextid);

// precedent:

$previd=0;
do {  // exception
  $result=mysql_query ("$querybase $GLOBALS[pt]entites.ordre<$context[ordre] $critere ORDER BY $GLOBALS[pt]entites.ordre DESC LIMIT 0,1") or die (mysql_error());
  if (mysql_num_rows($result)) {
    list($previd)=mysql_fetch_row($result);
    break;
  }

  // ok, on a pas trouve on cherche alors le pere precedent et son dernier fils (e2)
  $result=mysql_query ("SELECT e2.id FROM $GLOBALS[entitestypesjoin], $GLOBALS[tp]relations, $GLOBALS[tp]entites as e2 WHERE id2='$context[id]' AND degres=2 AND $GLOBALS[tp]entites.idparent=id1 AND type='regroupement' AND  $GLOBALS[tp]entites.ordre<$context[ordre] $critere ORDER BY $GLOBALS[tp]entites.ordre DESC, e2.ordre DESC LIMIT 0,1") or die (mysql_error());
  if (mysql_num_rows($result)) {
    list($nextid)=mysql_fetch_row($result);
    break;
  }
  // ok, c'est surement hors regroupement alors.
  $result=mysql_query ("SELECT e2.id FROM $GLOBALS[entitestypesjoin], $GLOBALS[tp]relations, $GLOBALS[tp]entites as e2 WHERE id2='$context[id]' AND degres=2 AND $GLOBALS[tp]entites.idparent=id1 AND $GLOBALS[tp]types.classe='documents' AND  $GLOBALS[tp]entites.ordre<$context[ordre] $critere ORDER BY $GLOBALS[tp]entites.ordre DESC, e2.ordre DESC LIMIT 0,1") or die (mysql_error());
  if (mysql_num_rows($result)) {
    list($nextid)=mysql_fetch_row($result);
    break;
  }
} while (0);

if ($previd) $context[prevdocument]=makeurlwithid("document",$previd);

// fin suivant et precedent


include ($home."cache.php");

?>
