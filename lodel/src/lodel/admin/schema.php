<?php
// charge le fichier xml et
require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_VISITEUR);
include ($home."func.php");


include_once($home."connect.php");

$context[classe]="documents";

ob_start();
include ($home."calcul-page.php");
calcul_page($context,"schema-xsd");
$contents=ob_get_contents();
ob_end_clean();

$arr=preg_split("/\s*(<(\/?)[^>]+>)\s*/",$contents,-1,PREG_SPLIT_DELIM_CAPTURE);
#print_r($arr);

// "telechargement"
$originalname="entite-$id.xml";
#temporairement commente
#header("Content-type: application/force-download");
#header("Content-Disposition: attachment; filename=$originalname");
#header("Content-type: application/$type");


echo '<?xml version="1.0" encoding="utf-8" ?>
';
$tab="";
for($i=1; $i<count($arr); $i+=3) {
  if ($arr[$i+1]) $tab=substr($tab,2);
  echo $tab.$arr[$i],"\n";
  if (!$arr[$i+1] && !preg_match("/\/\s*>/",$arr[$i])) $tab.="  ";
  if (trim($arr[$i+2])) { echo $tab.$arr[$i+2]."\n"; }
}


?>
