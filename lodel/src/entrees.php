<?
require_once("revueconfig.php");
include ($home."auth.php");
authenticate();
include ($home."func.php");

if (!$type || !preg_match("/[\w-]/",$type)) die("type incorrecte");

include_once($home."connect.php");
$result=mysql_query ("SELECT * FROM typeentrees WHERE nom='$type'") or die (mysql_error());
$context=array_merge_withprefix($context,"type_",mysql_fetch_assoc($result));
$context[typeid]=$context[type_id]; // import
$context[type_tri]=$GLOBALS[tableprefix]."entrees.".$context[type_tri];  // prefix par la table... ca aide

$base=$context[type_tplindex];
include ($home."cache.php");


function boucle_alphabet(&$context,$funcname)

{
  for($l="A"; $l!="AA"; $l++) {
    $context[lettre]=$l;
    call_user_func("code_boucle_$funcname",$context);
  }
}

?>
