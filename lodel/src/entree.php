<?

require("revueconfig.php");
include ($home."auth.php");
authenticate();

$context[id]=$id=intval($id);

include_once($home."connect.php");
$entreetable="$GLOBALS[prefixtable]entrees";
$result=mysql_query ("SELECT $entreetable.*, tpl FROM $entreetable,$GLOBALS[prefixtable]typeentrees WHERE $entreetable.typeid=$GLOBALS[prefixtable]typeentrees.id  AND $entreetable.id='$id' AND $entreetable.status>0") or die (mysql_error());
$context=array_merge($context,mysql_fetch_assoc($result));

#echo $base;
#print_r($context);
$base=$context[tpl];
include ($home."cache.php");

?>
