<?php// gere un index. L'acces est reserve au administrateur.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN);
include_once ($home."func.php");


if (!$type || !preg_match("/[\w-]/",$type)) die("type incorrecte");

include_once($home."connect.php");
$result=mysql_query ("SELECT * FROM typeentrees WHERE type='$type'") or die (mysql_error());
$context=array_merge_withprefix($context,"type_",mysql_fetch_assoc($result));
$context[idtype]=$context[type_id]; // import
$context[type_tri]=$GLOBALS[tp]."entrees.".$context[type_tri]; // prefix par la table... ca aide

include ($home."calcul-page.php");
calcul_page($context,"entrees");

?>
