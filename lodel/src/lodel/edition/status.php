<?

// change le status d'un document ou d'une publication


require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ($home."func.php");

include_once ($home."connect.php");

if ($status=="protege") {
  $newstatus=+32;
} elseif ($status=="deprotege") {
  $newstatus=+1;
} elseif ($status=="brouillon") {
  $newstatus=-32;
} elseif ($status=="pret") {
  $newstatus=-1;
} else {
  die ("status invalide");
}

// ce script permet de changer le status, mais pas lui faire changer de signe.
// pour publie ou depublie (changer le signe de status) il faut utiliser publi.php

// le statut ne doit pas changer de signe...
$critere=$newstatus>0 ? "status>0" : "status<0";

if ($publication) {
  $id=intval($publication);

  mysql_query("UPDATE $GLOBALS[tableprefix]publications SET status=$newstatus WHERE id='$id' AND $critere") or die(mysql_error());

} elseif ($id) {
  $id=intval($id);

  mysql_query("UPDATE $GLOBALS[tableprefix]documents SET status=$newstatus WHERE id='$id' AND $critere") or die(mysql_error());
} else {
  die("specifier une publication");
}

back();
return;

?>
