<?

require_once("lodelconfig.php"); // en general il est deja inclue

mysql_connect($GLOBALS[dbhost],$GLOBALS[dbusername],$GLOBALS[dbpasswd]) or die (mysql_error());
if ($GLOBALS[revue] && $GLOBALS[singledatabase]!="on") {
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


//
// expressions qui facilite la vie
//

$GLOBALS[tp]=$GLOBALS[tp];



$GLOBALS[publicationsjoin]="$GLOBALS[tp]entites INNER JOIN $GLOBALS[tp]publications ON $GLOBALS[tp]entites.id=$GLOBALS[tp]publications.identite";

$GLOBALS[documentsjoin]="$GLOBALS[tp]entites INNER JOIN $GLOBALS[tp]documents ON $GLOBALS[tp]entites.id=$GLOBALS[tp]documents.identite";

$GLOBALS[entitestypesjoin]="$GLOBALS[tp]types INNER JOIN $GLOBALS[tp]entites ON $GLOBALS[tp]types.id=$GLOBALS[tp]entites.idtype";

$GLOBALS[publicationstypesjoin]="($GLOBALS[entitestypesjoin]) INNER JOIN $GLOBALS[tp]publications ON $GLOBALS[tp]entites.id=$GLOBALS[tp]publications.identite";

$GLOBALS[documentstypesjoin]="($GLOBALS[entitestypesjoin]) INNER JOIN $GLOBALS[tp]documents ON $GLOBALS[tp]entites.id=$GLOBALS[tp]documents.identite";






#$db_ok= !!@mysql_connect("localhost","apache","") &
#!!@mysql_select_db("r2r");

?>
