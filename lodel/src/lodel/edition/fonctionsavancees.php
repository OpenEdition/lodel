<?
require("revueconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_VISITEUR);

// Gère les fonctions avancées pour les publications et les documents.
// On peut passer 2 paramètres différents à ce script :
// id pour un document
// publication pour une publication

$critere=$admin ? "" : "groupe IN ($usergroupes) AND ";

if ($id) { // document
   $id=intval($id);
   $critere.="id='$id'";
   include_once ("$home/connect.php");
   $result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]documents WHERE $critere") or die (mysql_error());
   $context=array_merge($context,mysql_fetch_assoc($result));
   $base="fonctionsavancees-document";
} elseif ($publication) { // publication
   $publication=intval($publication);
   $critere.="id='$publication'";
   include_once ("$home/connect.php");
   $result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]publications WHERE $critere") or die (mysql_error());
   $context=array_merge($context,mysql_fetch_assoc($result));
   $base="fonctionsavancees-publication";
} else { die("id ou publication ?"); }

include ("$home/calcul-page.php");
calcul_page($context,$base);
?>