<?php

require("siteconfig.php");
include ($home."auth.php");
authenticate();
include ($home."func.php");


$context[id]=$id=intval($id);


// cherche le sommaire precedent et le suivant
include_once($home."connect.php");


$critere=$visiteur ? " AND $GLOBALS[tp]entites.statut>=-1" : " AND $GLOBALS[tp]entites.statut>0";
$critere.=" AND $GLOBALS[tp]types.statut>0";
// cherche la publication
$relocation=FALSE;
$base="";

if (!(@include_once("CACHE/filterfunc.php"))) require_once($GLOBALS[home]."filterfunc.php");

if ($id) {
  require_once($home."textfunc.php");
  do {
    $result=mysql_query("SELECT $GLOBALS[tp]publications.*,$GLOBALS[tp]entites.*,tpl,type FROM $GLOBALS[publicationstypesjoin] WHERE $GLOBALS[tp]entites.id='$id' $critere") or die (mysql_error());
    if (mysql_num_rows($result)<1) { header ("Location: not-found.html"); return; }
    $row=filtered_mysql_fetch_assoc($context,$result);
    $base=$row[tpl];
    if (!$base) { $id=$row[idparent]; $relocation=TRUE; }
  } while (!$base);
  if ($relocation) { 
    header("location: ".makeurlwithid("sommaire",$row[id]));
    return;
  }
  $context=array_merge($context,$row);
} else {
  $base="sommaire";
}
//
// cherche le numero precedent et le suivant
//
export_prevnextpublication (&$context);

include ($home."cache.php");


?>
