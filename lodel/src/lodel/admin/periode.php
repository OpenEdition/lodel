<?

// gere les periodes. L'acces est reserve au superadministrateur.
// assure l'edition, la supression, la restauration des periodes.

include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include_once("$home/func.php");

$type=TYPE_PERIODE;

include("indexh.php");



function make_selection_periode($parent=0,$rep="")

{
  global $context;

  $result=mysql_query("SELECT nom,id FROM indexhs WHERE type='".TYPE_PERIODE."' AND parent='$parent' ORDER BY ordre") or die (mysql_error());
  while ($row=mysql_fetch_array($result,MYSQL_ASSOC)) {
    $selected=$row[id]==$context[parent] ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$row[id]\"$selected>$rep$row[nom]</OPTION>\n";
    make_selection_periode($row[id],"$rep$row[nom]/");
  }

}


include ("$home/calcul-page.php");
calcul_page($context,"periode");

?>
