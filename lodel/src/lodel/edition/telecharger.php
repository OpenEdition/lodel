<?php
require("siteconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_REDACTEUR,NORECORDURL);

$id=intval($id);
if ($id>0 && $type=="xml") {
 $filename="r2r-$id.xml";
 $originalname=$filename;
 $rep="../txt";
} elseif ($id>0 && $type=="source") {
 $filename="entite-$id.source";
 $rep="../sources";
 // cherche le nom original du fichier

 require($home."tablefields.php");
 $table=$GLOBALS[tp]."documents";
 if ($tablefields[$table] && 
      in_array("fichiersource",$tablefields[$table])) {
    $result=mysql_query("SELECT fichiersource FROM $GLOBALS[tp]documents WHERE identite='$id'") or die(mysql_error());
    list($originalname)=mysql_fetch_row($result);
  } else {
    $originalname=$filename;
  }
} else die ("type ou id inconnu");

if (!file_exists($rep."/".$filename)) die ("le fichier $filename n'existe pas");

require_once($home."func.php");
download ($rep."/".$filename,$originalname);

?>
