<?

// charge le fichier xml et
include ("lodelconfig.php");
include ("$home/auth.php");
authenticate();

$context[id]=$id=intval($id);

include_once("$home/connect.php");
$result=mysql_query ("SELECT * FROM indexls WHERE id='$id'") or die (mysql_error());
$context=mysql_fetch_array($result);

$base="mot";

include ("$home/cache.php");

?>
