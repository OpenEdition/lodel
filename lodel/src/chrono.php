<?
require("revueconfig.php");
include ("$home/auth.php");
include_once ("$home/connect.php");
authenticate();

$base="chrono";
$id=intval($id);

$result=mysql_query ("SELECT * FROM indexhs WHERE id='$id'") or die (mysql_error());
$context=array_merge($context,mysql_fetch_array($result));


include ("$home/cache.php");

?>

