<?php
require_once("siteconfig.php");
if (!function_exists("authenticate")) {
  include_once ($home."auth.php");
  authenticate(LEVEL_EDITEUR,NORECORDURL);
}

// detruit la tache en cours
$id=intval($id);
if ($id) {
  include_once ($home."connect.php");
  mysql_query("DELETE FROM taches WHERE id='$id'") or die (mysql_error());
}

if ($redirect) {
  header("Location: $redirect");
  return;
} else {
  include_once ($home."func.php"); back();
}

?>
