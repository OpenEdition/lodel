<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003-2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *
 *  Home page: http://www.lodel.org
 *
 *  E-Mail: lodel@lodel.org
 *
 *                            All Rights Reserved
 *
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.*/

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
