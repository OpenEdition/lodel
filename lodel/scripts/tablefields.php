<?

// build the arrays containing tables and fields

require_once("lodelconfig.php");
require_once($home."connect.php");

#echo "::";
$GLOBALS[tablefields]=array();

foreach (array($GLOBALS[database],$GLOBALS[currentdb]) as $db) {
  $result=mysql_list_tables($db) or die(mysql_error());
  while (list($table)=mysql_fetch_row($result)) {
    $GLOBALS[tablefields][$table]=array();
    $result2=mysql_list_fields($db,$table);
    $nfields=mysql_num_fields($result2);
    for($j=0; $j<$nfields; $j++) {
      array_push($GLOBALS[tablefields][$table],mysql_field_name($result2,$j));
    }
  }
}

$fp=fopen("CACHE/tablefields.php","w");
fputs($fp,'<? $GLOBALS[tablefields]='.var_export($GLOBALS[tablefields],TRUE).' ; ?>');
fclose($fp);


if (!function_exists("var_export")) {

function var_export($arr,$t)

{
  $ret="array(";
  foreach ($arr as $k=>$v) {
    $ret.="'$k'=>";
    if (is_array($v)) {
      $ret.=var_export2($v,TRUE).",";
    } else {
      $ret.=$v.",";
    }
  }
  return $ret.")";
}

}

?>
