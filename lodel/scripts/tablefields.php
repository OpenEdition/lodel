<?php
// build the arrays containing tables and fields

require_once($GLOBALS[home]."connect.php");

// try first to get the cached array
if (!(@include("CACHE/tablefields.php"))) {

  // no, we have to build the tablefields array

  if (!function_exists("var_export")) {
    function var_export($arr,$t)

      {
	$ret="array(";
	foreach ($arr as $k=>$v) {
	  $ret.="'$k'=>";
	  if (is_array($v)) {
	    $ret.=var_export($v,TRUE).",\n";
	  } else {
	    $ret.="'$v',\n";
	  }
	}
	return $ret.")";
      }
  }

  $tablefields=array();

  ////////////////////////


  function maketablefields(&$tablefields)

    {
      $dbs[$GLOBALS[currentdb]]="";
      if ($GLOBALS[database]!=$GLOBALS[currentdb]) $dbs[$GLOBALS[database]]=$GLOBALS[database].".";

      foreach ($dbs as $db => $prefix) {
	$result=mysql_list_tables($db) or die(mysql_error());
	while (list($table)=mysql_fetch_row($result)) {
	  $result2=mysql_list_fields($db,$table);
	  $nfields=mysql_num_fields($result2);
	  $table=$prefix.$table;
	  $tablefields[$table]=array();
	  for($j=0; $j<$nfields; $j++) {
	    array_push($tablefields[$table],mysql_field_name($result2,$j));
	  }
	}
      }
      mysql_select_db($GLOBALS[currentdb]);

      $fp=fopen("CACHE/tablefields.php","w");
      fputs($fp,'<?php  $tablefields='.var_export($tablefields,TRUE).' ; ?>');
      fclose($fp);
    }

  maketablefields(&$tablefields);



}
?>
