<?

require("revueconfig.php");
include ($home."auth.php");
authenticate();

$context[id]=$id=intval($id);

include_once($home."connect.php");
$entreetable="$GLOBALS[prefixtable]entrees";
$typeentreetable="$GLOBALS[prefixtable]typeentrees";
$result=mysql_query ("SELECT $entreetable.*, tpl FROM $entreetable,$typeentreetable WHERE $entreetable.idtype=$typeentreetable.id  AND $entreetable.id='$id' AND $entreetable.status>0 AND $typeentreetable.status>0") or die (mysql_error());
if (!mysql_num_rows($result)) { header("location: not-found.html"); exit(); }
$context=array_merge($context,mysql_fetch_assoc($result));

#echo $base;
#print_r($context);
$base=$context[tpl];
include ($home."cache.php");

?>
