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

 if (!(@include_once("CACHE/tablefields.php")) || !$GLOBALS[tablefields]) require_once($home."tablefields.php");
  if ($GLOBALS[tablefields][documents] && 
      in_array("fichiersource",$GLOBALS[tablefields][documents])) {
    $result=mysql_query("SELECT fichiersource FROM $GLOBALS[tp]documents WHERE identite='$id'") or die(mysql_error());
    list($originalname)=mysql_fetch_row($result);
  } else {
    $originalname=$filename;
  }
} else die ("type ou id inconnu");

if (!file_exists($rep."/".$filename)) die ("le fichier $filename n'existe pas");

telecharger ($rep,$filename,$originalname);

function telecharger($rep,$filename,$originalname="")
{
  $path=$rep."/".$filename;
  if (!$originalname) $originalname=$filename;
  header("Content-type: application/force-download");
  header("Content-Disposition: attachment; filename=$originalname");
  header("Content-type: application/$type");
  readfile("$path"); 
}
?>
