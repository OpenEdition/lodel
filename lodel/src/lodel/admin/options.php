<?php
require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);

include_once ($home."connect.php");


if ($edit) {
  $listoptions=array("motclefige","pasdemotcle","pasdeperiode","pasdegeographie","ordrepartypedoc");
  include ($home."func.php");
  extract_post();
  $newoptions=array();
  foreach ($listoptions as $opt) {
    if ($context["option_$opt"]) $newoptions["option_$opt"]=1;
  }
  $optionsstr=serialize($newoptions);
  mysql_db_query($GLOBALS[database],"UPDATE sites SET options='$optionsstr' WHERE rep='$site'") or die (mysql_error());
  mysql_select_db($GLOBALS[currentdb]);

  // recherche les metas
  $result=mysql_db_query($GLOBALS[database],"SELECT meta FROM sites WHERE rep='$site'") or die (mysql_error());
  // ajoute les metas
  $newmeta=addmeta($context,$meta);
  if ($newmeta!=$meta) mysql_db_query($GLOBALS[database],"UPDATE sites SET meta='$newmeta' WHERE rep='$site'") or die (mysql_error());

  back();
}

//print_r($context);

include ($home."calcul-page.php");
calcul_page($context,"options");



?>


