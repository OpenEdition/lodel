<?

require("lodelconfig.php");
include ($home."auth.php");
authenticate(LEVEL_VISITEUR);

$name=$HTTP_COOKIE_VARS["session$database"];
include_once ($home."connect.php");
$time=time()-1;
mysql_db_query($database,"UPDATE $GLOBALS[tp]session SET expire2='$time' WHERE name='$name'") or die (mysql_error());
setcookie($sessionname,"",$time,$urlroot);

#ifndef LODELLIGHT
#else
# if (!$admin) touch("../../CACHE/maj"); // fait la mise a jour si ce n'est pas des administrateurs
#endif

include_once($home."func.php");

header ("Location: http://$SERVER_NAME$urlroot");
#back();

?>
