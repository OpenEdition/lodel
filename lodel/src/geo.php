<?
require("revueconfig.php");
include ($home."auth.php");
include_once ($home."connect.php");
authenticate();

$base="geo";
$id=intval($id);

$result=mysql_query ("SELECT * FROM indexhs WHERE id='$id'") or die (mysql_error());
$context=mysql_fetch_array($result);


include ($home."cache.php");

?>

