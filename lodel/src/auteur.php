<?

// charge le fichier xml et
require("revueconfig.php");
include ("$home/auth.php");
authenticate();

$context[id]=$id=intval($id);

include_once("$home/connect.php");
$result=mysql_query ("SELECT * FROM auteurs WHERE id='$id'") or die (mysql_error());
$context=array_merge($context,mysql_fetch_array($result,MYSQL_ASSOC));

$base="auteur";

include ("$home/cache.php");

?>
