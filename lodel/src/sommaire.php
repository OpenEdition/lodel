<?


require("siteconfig.php");
include ($home."auth.php");
authenticate();
include ($home."func.php");


$context[id]=$id=intval($id);


// cherche le sommaire precedent et le suivant
include_once($home."connect.php");


$critere=$visiteur ? " AND $GLOBALS[tp]entites.status>=-1" : " AND $GLOBALS[tp]entites.status>0";
$critere.=" AND $GLOBALS[tp]types.status>0";
// cherche la publication


$result=mysql_query("SELECT $GLOBALS[tp]publications.*,$GLOBALS[tp]entites.*,tpl,type FROM $publicationstypesjoin WHERE $GLOBALS[tp]entites.id='$id' $critere") or die (mysql_error());
$row=mysql_fetch_assoc($result);
if (!$row[id]) { header ("Location: not-found.html"); return; }

if (!$row[tpl]) { 
  header("location: ".makeurlwithid("sommaire",$context[idparent]));
  return;
}

$context=array_merge($context,$row);
$base=$context[tpl];



//
// cherche le numero precedent et le suivant
//
export_prevnextpublication (&$context);



include ($home."cache.php");


?>
