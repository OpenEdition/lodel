<?
// gere un index. L'acces est reserve au administrateur.

require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN);
include_once ($home."func.php");


if (!$type || !preg_match("/[\w-]/",$type)) die("type incorrecte");

include_once($home."connect.php");
$result=mysql_query ("SELECT * FROM typeentrees WHERE nom='$type'") or die (mysql_error());
$context=array_merge_withprefix($context,"type_",mysql_fetch_assoc($result));
$context[typeid]=$context[type_id]; // import


include ($home."calcul-page.php");
calcul_page($context,"entrees");

?>
