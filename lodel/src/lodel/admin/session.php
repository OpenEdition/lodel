<?

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include ($home."func.php");

$delete=intval($delete);

if ($delete) {
  mysql_db_query($database,"DELETE FROM $GLOBALS[tp]session WHERE id=$delete") or die (mysql_error());
} else {
die ("a finir");
}

back();

?>
