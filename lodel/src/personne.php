<?php
require("siteconfig.php");
include ($home."auth.php");
authenticate();


if (!$type || !preg_match("/[\w-]/",$type)) die("type incorrecte");

$context[id]=$id=intval($id);

include_once($home."connect.php");
$personnetable="";
$result=mysql_query ("SELECT * FROM $GLOBALS[tp]personnes WHERE id='$id' AND status>0") or die (mysql_error());
if (!mysql_num_rows($result)) { header("location: not-found.html"); exit(); }
$context=array_merge($context,mysql_fetch_assoc($result));


$result=mysql_query ("SELECT tpl FROM $GLOBALS[tp]typepersonnes WHERE nom='$type' AND status>0") or die (mysql_error());
list($base)=mysql_fetch_row($result);

include ($home."cache.php");

?>
