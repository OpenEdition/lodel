<?
// registre dans la base de donnée le fichier

require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ($home."func.php");

$id=intval($id);

if ($edit && $fichier) {
  include ($home."dbxml.php");
  # recupere les informations a fournir a enregistre
  $result=mysql_query("SELECT publication,ordre,datepubli FROM documents WHERE id=$id AND status>-2") or die (mysql_error());
  if ($row=mysql_fetch_array($result,MYSQL_ASSOC)) {
    $row[iddocument]=$id;
    # empeche les tags d'etre coupes
    $fichier=preg_replace("/(<[^>\n]*)\n([^>\n]*>)/s","\\1 \\2",stripslashes($fichier));
    include_once($home."checkxml.php");
    if (!checkstring($fichier)) {
      echo "<br><br><a href=\"javascript: back()\">Editer à nouveau</a>";
      exit;
    }
    # efface le document d'abord
    include($home."managedb.php");
    supprime_document($id);
    enregistre($row,$fichier);
    writefile("../txt/r2r-$id.xml",$fichier);
  }
  back();

} else {
  $result=mysql_query("SELECT * FROM documents WHERE id=$id") or die (mysql_error());
  if (!($row=mysql_fetch_array($result,MYSQL_ASSOC))) { header ("index.php"); return; }
  $context=array_merge($context,$row);
  if (!file_exists("../txt/r2r-$id.xml")) {
    $context[erreur_fichiernontrouve]=1;
  } else {
    $context[fichier]=join("",file("../txt/r2r-$id.xml"));
  }
}

posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"a_editer");

?>
