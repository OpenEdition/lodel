<?

// charge le fichier xml et
include ("lodelconfig.php");
include ("$home/auth.php");
authenticate();

$context[id]=$id=intval($id);

include_once("$home/connect.php");


$critere=$visiteur ? "" : "AND documents.status>0";

//
// cherche le document, et le template
//
$result=mysql_query("SELECT documents.*,tpl,datepubli,(datepubli<=NOW()) as textepublie FROM documents,typedocs WHERE documents.id='$id' AND nom=type $critere") or die (mysql_error());
if (mysql_num_rows($result)<1) { header ("Location: not-found.html"); return; }
$context=array_merge($context,mysql_fetch_assoc($result));

$base=$context[tpl];

//
// charge le fichier XML et extrait les balises
//
if (!file_exists("lodel/txt/r2r-$id.xml")) { header ("Location: not-found.html"); return; }
$text=join("",file("lodel/txt/r2r-$id.xml"));

include ("$home/xmlfunc.php");
include ("$home/balises.php");

$balises=$balisesdocument_nonlieautexte;
array_push($balises,"surtitre","titre","soustitre");

if ($context[textepublie] || $visiteur) $balises=array_merge($balises,$balisesdocument_lieautexte);

echo "styles reconnus dans document: ",join(" ",$balises),"<br>";
$context=array_merge($context,extract_xml($balises,$text));

//
// cherche s'il y a des documents annexe et combien
//
$result=mysql_query("SELECT count(*) FROM documentsannexes WHERE iddocument='$id' AND status>0") or die (mysql_error());
list($context[documentsannexes])=mysql_fetch_row($result);

//
// cherche l'article precedent et le suivant
//

// suivant:
$result=mysql_query ("SELECT id FROM documents WHERE publication='$context[publication]' AND ordre>$context[ordre] ORDER BY ordre LIMIT 0,1") or die (mysql_error());
if (mysql_num_rows($result)) {
  list($nextid)=mysql_fetch_row($result);
  $context[nextdocument]="document.html?id=$nextid";
}
// precedent:
$result=mysql_query ("SELECT id FROM documents WHERE publication='$context[publication]' AND ordre<$context[ordre] ORDER BY ordre DESC LIMIT 0,1") or die (mysql_error());
if (mysql_num_rows($result)) {
  list($previd)=mysql_fetch_row($result);
  $context[prevdocument]="document.html?id=$previd";
}



// fin suivant et precedent


include ("$home/cache.php");

?>
