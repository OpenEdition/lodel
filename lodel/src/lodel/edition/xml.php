<?php
// charge le fichier xml et
require("siteconfig.php");
include ($home."auth.php");
authenticate();
include ($home."func.php");

$context[id]=$id=intval($id);

include_once($home."connect.php");

$critere=$visiteur ? "" : "AND $GLOBALS[tp]entites.statut>0 AND $GLOBALS[tp]types.statut>0";

////
//// cherche le document, et le template
////
//if (!(@include_once("CACHE/filterfunc.php"))) require_once($home."filterfunc.php");
//
//$result=mysql_query("SELECT $GLOBALS[tp]documents.*,$GLOBALS[tp]entites.*,tpl,type , datepubli,(datepubli<=NOW()) as textepublie FROM $GLOBALS[documentstypesjoin] WHERE $GLOBALS[tp]entites.id='$id' $critere") or die (mysql_error());
//if (mysql_num_rows($result)<1) { header ("Location: not-found.html"); return; }
//$context=array_merge($context,filtered_mysql_fetch_assoc($result));
//if (!$context[tpl]) { 
//  header("location: ".makeurl("document",$context[idparent]));
//  return;
//}


$result=mysql_query("SELECT $GLOBALS[tp]documents.*,$GLOBALS[tp]entites.*,type FROM $GLOBALS[documentstypesjoin] WHERE $GLOBALS[tp]entites.id='$id' $critere") or die (mysql_error());
if (mysql_num_rows($result)<1) { header ("Location: not-found.html"); return; }
$context=array_merge($context,mysql_fetch_assoc($result));

$context[identite]=$context[id];
$context[classe]="documents";

//
// cherche s'il y a des documents annexe et combien
//
$result=mysql_query("SELECT count(*) FROM $GLOBALS[entitestypesjoin] WHERE idparent='$id' AND $GLOBAL[tp]entites.statut>0 AND type LIKE 'documentannexe-%'") or die (mysql_error());
list($context[documentsannexes])=mysql_fetch_row($result);

# calculate the page and store it into $contents

ob_start();
include ($home."calcul-page.php");
calcul_page($context,"xml-classe");
$contents=ob_get_contents();
ob_end_clean();

$arr=preg_split("/\s*(<(\/?)r2r:[^>]+>)\s*/",$contents,-1,PREG_SPLIT_DELIM_CAPTURE);
#print_r($arr);

echo '<?xml version="1.0" encoding="utf-8" ?>';
$tab="";
for($i=1; $i<count($arr); $i+=3) {
  if ($arr[$i+1]) $tab=substr($tab,2);
  echo $tab.$arr[$i],"\n";
  if (!$arr[$i+1]) $tab.="  ";
  if (trim($arr[$i+2])) { echo $tab.$arr[$i+2]."\n"; }
}


function loop_champs($context,$funcname)

{
  global $erreur;

  $result=mysql_query("SELECT nom,type FROM $GLOBALS[tp]champs WHERE idgroupe='$context[id]' AND statut>0 ORDER BY ordre") or die(mysql_error());

  $haveresult=mysql_num_rows($result)>0;
  if ($haveresult && function_exists("code_before_$funcname")) 
    call_user_func("code_before_$funcname",$context);

  while ($row=mysql_fetch_assoc($result)) {
    $row[value]=$context[$row[nom]];
    call_user_func("code_do_$funcname",$row);
  }
  if ($haveresult && function_exists("code_after_$funcname")) 
    call_user_func("code_after_$funcname",$context);
}

function loop_champs_require() { return array("id"); }


?>
