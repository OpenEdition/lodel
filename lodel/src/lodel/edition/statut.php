<?php
// change le statut d'un document ou d'une publication


require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ($home."func.php");

include_once ($home."connect.php");

if ($statut=="protege") {
  $newstatut=+32;
} elseif ($statut=="deprotege") {
  $newstatut=+1;
} elseif ($statut=="brouillon") {
  $newstatut=-32;
} elseif ($statut=="pret") {
  $newstatut=-1;
} else {
  die ("statut invalide");
}

// ce script permet de changer le statut, mais pas lui faire changer de signe.
// pour publie ou depublie (changer le signe de statut) il faut utiliser publi.php

// le statut ne doit pas changer de signe...
$critere=$newstatut>0 ? "statut>0" : "statut<0";

if ($publication) {
  $id=intval($publication);
} elseif ($id) {
  $id=intval($id);
} else {
  die("specifier une publication");
}
 
mysql_query("UPDATE $GLOBALS[tp]entites SET statut=$newstatut WHERE id='$id' AND $critere") or die(mysql_error());


back();
return;

?>
