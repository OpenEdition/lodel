<?

require("revueconfig.php");
include ($home."auth.php");
authenticate();

$context[id]=$id=intval($id);

include_once($home."connect.php");
$indextable="$GLOBALS[prefixtable]indexs";
$result=mysql_query ("SELECT $indextable.*, tpl FROM $indextable,$GLOBALS[prefixtable]typeindexs WHERE $indextable.type=$GLOBALS[prefixtable]typeindexs.id  AND $indextable.id='$id' AND $indextable.status>0") or die (mysql_error());
$context=array_merge($context,mysql_fetch_assoc($result));

echo $base;
print_r($context);
$base=$context[tpl];
include ($home."cache.php");

?>
