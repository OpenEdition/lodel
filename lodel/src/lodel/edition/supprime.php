<?

// suppression de documents et de publication en assurant la coherence de la base

include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);

$id=intval($id);
$publication=intval($publication);

if ($supprime) {
  include_once ("$home/connect.php");
  include ("$home/managedb.php");
  do {
    if ($publication>0) {
      if (!supprime_publication($publication)) break;
    } else {
      supprime_document($id);
    }
    include_once("$home/func.php");
    back();
  } while (0);
  // il y a eu un pb, on redemande confirmation
}

if ($publication>0) { # recupere les infos du publication
  $result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]publications WHERE id='$publication'") or die (mysql_error());
} else { # recupere les infos du document
  $result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]documents WHERE id='$id'") or die (mysql_error());
}

if (!($row=mysql_fetch_array($result,MYSQL_ASSOC))) { header("location: not-found.html"); }
$context=array_merge($context,$row);

include ("$home/func.php");
posttraitement($context);

include ("$home/calcul-page.php");
calcul_page($context,"supprime");

?>
