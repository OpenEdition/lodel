<?
// 
require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ($home."func.php");

$context[id]=$context[iddocument]=$id=intval($id);
$idparent=intval($idparent);

if ($idparent) {
  lock_write("entites","relations");
  mysql_query ("UPDATE $GLOBALS[tp]entites SET idparent='$idparent' WHERE id='$id'") or die (mysql_error());
  if (mysql_affected_rows()) { // on a effectivement changer l'id du parent
    // cherche les nouveaux parents de $id
    $result=mysql_query("SELECT id1,degres FROM $GLOBALS[tp]relations WHERE id2='$idparent' AND nature='P'") or die(mysql_error());

    $values="";
    $dmax=0;
    while ($row=mysql_fetch_assoc($result)) {
      $parents[$row[degres]]=$row[id1];
      if ($row[degres]>$dmax) $dmax=$row[degres];
      $values.="('$row[id1]','$id','P','".($row[degres]+1)."'),";
    }
    $parents[0]=$idparent;

    // recherche les enfants
    $delete="";
    $result=mysql_query("SELECT id2,degres FROM $GLOBALS[tp]relations WHERE id1='$id' AND nature='P'") or die(mysql_error());
    while ($row=mysql_fetch_assoc($result)) {
      $delete.=" (id2='$row[id2]' AND degres>$row[degres]) OR "; // efface tous les parents au dessus de $id.
      for ($d=0; $d<=$dmax; $d++) { // pour chaque degres
	$values.="('$parents[$d]','$row[id2]','P','".($row[degres]+$d+1)."'),"; // ajoute tous les parents
      }
    }

    $delete.=" id2='$id' ";
    $values.="('$idparent','$id','P',1)";
 
#   echo $values,"<br>",$delete;
    // detruit les liens vers le parent de id
    mysql_query ("DELETE FROM $GLOBALS[tp]relations WHERE ($delete) AND nature='P'") or die (mysql_error());
    mysql_query("INSERT INTO $GLOBALS[tp]relations (id1,id2,nature,degres) VALUES $values") or die(mysql_error());
  }
  unlock();
  back();
  return;
}

$context[id]=0;
posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"deplacer");

?>
