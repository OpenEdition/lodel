<?php
require_once("siteconfig.php");
include ($home."auth.php");
authenticate();
include ($home."func.php");

if (!$type || !preg_match("/^[\w-]*$/",$type)) die("type incorrecte");
if ($suffix && !preg_match("/^[\w-]+$/",$suffix)) die("suffix non accepte");

include_once($home."connect.php");
$result=mysql_query ("SELECT * FROM $GLOBALS[tp]typeentrees WHERE type='$type' AND status>0") or die (mysql_error());
$context=array_merge_withprefix($context,"type_",mysql_fetch_assoc($result));
$context[idtype]=$context[type_id]; // import
$context[type_tri]=$GLOBALS[tp]."entrees.".$context[type_tri];  // prefix par la table... ca aide

$base=$context[type_tplindex].$suffix;
require ($home."cache.php");


function loop_alphabet(&$context,$funcname)

{
  for($l="A"; $l!="AA"; $l++) {
    $context[lettre]=$l;
    call_user_func("code_do_$funcname",$context);
  }
}

?>
