<?

require_once("lodelconfig.php"); // en general il est deja inclue

mysql_connect($GLOBALS[dbhost],$GLOBALS[dbusername],$GLOBALS[dbpasswd]) or die (mysql_error());
if ($GLOBALS[revue]) {
  $GLOBALS[currentdb]=$GLOBALS[database]."_".$GLOBALS[revue];
} else {
  $GLOBALS[currentdb]=$GLOBALS[database];
}
mysql_select_db($GLOBALS[currentdb])  or die (mysql_error());


//
//if (!function_exists("table")) {
//  function table($nom)
//
//    { 
//	global $revue;
//	if ($nom=="revues"  || $nom=="session" || ($nom=="users" && !$revue)) {
//	  return "r2r_$nom";
//	} else {
//	  if (!$revue) { die ("repertoire non valide: $revue table: $nom"); }
//	  return "r2r_".$revue."_".$nom;
//	}
//    }
//}
//


#$db_ok= !!@mysql_connect("localhost","apache","") &
#!!@mysql_select_db("r2r");

?>
