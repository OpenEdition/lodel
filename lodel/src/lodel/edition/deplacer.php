<?
// 
include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ("$home/func.php");

$context[iddocument]=$iddocument=intval($iddocument);
$publication=intval($publication);

if ($publication) {
  mysql_query ("UPDATE $GLOBALS[tableprefix]documents SET publication='$publication' WHERE id='$iddocument'") or die (mysql_error());
  back();
  return;
}

$context[id]=0;
posttraitement($context);

include ("$home/calcul-page.php");
calcul_page($context,"deplacer");

?>
