<?

include_once($home."connect.php");
$GLOBALS[tablefields]=array();
foreach(array($GLOBALS[database],$GLOBALS[currentdb]) as $db) {
  $result=mysql_list_tables($db);
  while (list($table)=mysql_fetch_row($result)) { // pour chaque table
	$result2=mysql_list_fields($db,$table);
    $nfields = mysql_num_fields($result2);
    for ($i = 0; $i < $nfields; $i++) {
	  $GLOBALS[tablefields][$table][$i]=mysql_field_name($result2, $i);
    }
  }
}

//print_r($GLOBALS[tablefields]);

?>
