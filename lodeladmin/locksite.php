<?php
// gere les utilisateurs. L'acces est reserve au adminlodelistrateur.
// assure l'edition, la supression, la restauration des utilisateurs.

require("lodelconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMINLODEL,NORECORDURL);
include_once ($home."func.php");

// calcul le critere pour determiner le user a editer, restorer, detruire...
$id=intval($id);
$critere=" id='$id' AND statut>0";

mysql_select_db($database) or die (mysql_error());
if ($lock) { // lock
  // lock le site en ecriture le site.
  lock_write ("session","sites");
  // cherche le nom de la site
  $result=mysql_query ("SELECT rep FROM  $GLOBALS[tp]sites WHERE $critere") or die (mysql_error());
  list($site)=mysql_fetch_row($result);
  if (!$site) die ("erreur lors de l'appel de la locksite. La site est inconnue ou supprimee");
  // delogue tout le monde sauf moi.
  mysql_query ("DELETE FROM $GLOBALS[tp]session WHERE site='$site' AND iduser!='$iduser'") or die (mysql_error());
  // change le statut de la site
  mysql_query ("UPDATE $GLOBALS[tp]sites SET statut=32 WHERE $critere") or die (mysql_error());
  unlock();

} elseif ($unlock) { // unlock
  mysql_query ("UPDATE $GLOBALS[tp]sites SET statut=1 WHERE $critere") or die (mysql_error());
} else { die ("lock ou unlock"); }

mysql_select_db($currentdb) or die (mysql_error());

back();
return;
?>
