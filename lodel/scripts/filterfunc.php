<?php
/**
* Fichier de gestion des filtres
*
* PHP versions 4 et 5
*
* LODEL - Logiciel d'Edition ELectronique.
*
* Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
* Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
* Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
* Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
* Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
* Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
*
* Home page: http://www.lodel.org
*
* E-Mail: lodel@lodel.org
*
* All Rights Reserved
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*
* @author Ghislain Picard
* @author Jean Lamy
* @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
* @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
* @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
* @licence http://www.gnu.org/copyleft/gpl.html
* @version CVS:$Id:
* @package lodel
*/



makefilterfunc();
require_once 'CACHE/filterfunc.php';

/**
* Filtre les champs qu'il faut filtrer et converti les filtres en fonction
*
*/
function makefilterfunc()
{
	global $db;
	require_once 'connect.php';
	// cherche les champs a filtrer	
	$result = $db->execute(lq("SELECT class,name,filtering FROM #_TP_tablefields WHERE status > 0 AND filtering!=''")) or dberror();
	while (!$result->EOF)	{
		$row = $result->fields;
		// convert filter into a function
		$filters = preg_split("/\|/", $row['filtering']);
		#$filters = explode('|',$row['filter']);
		$filterfunc = '$x';
		foreach ($filters as $filter)	{
			if (preg_match("/^([A-Za-z][A-Za-z_0-9]*)(?:\((.*?)\))?$/", $filter, $result2)) {
				$funcname = $result2[1]; // name of the pipe function
				$arg = $result2[2]; // argument if any

				// process the variable. The processing is simple here. Need more ? Sould be associated with parser variable processing.
				$arg = preg_replace("/\[\#([A-Z][A-Z_0-9]*)\]/e", ' "$"."context[".strtolower("\\1")."]" ', $arg);

				if ($arg) {
					$arg = ','. $arg;
				}
				$filterfunc = $funcname.'('.$filterfunc.$arg.')';
			}	elseif ($filter)	{
				die("invalid filter function: $filter");
			} // do nothing if $filter is empty
		}
		$filterfunc = "return ". $filterfunc. ";";
		$filterstr .= "'". $row['class'].'.'. $row['name']. "' => '". addcslashes($filterfunc, "'")."',";
			#echo "filterstr=$filterstr";
		$result->MoveNext();
	}
	// build the function with filtering
	// to update with ADODB
	$fp = fopen("CACHE/filterfunc.php", "w");
	fputs($fp, '<'.'?php function filtered_mysql_fetch_assoc($context, $result) {
			$filters = array('.$filterstr.');
		$count = mysql_num_fields($result);
		$row = mysql_fetch_row($result);
		if (!$row) return array();
		for($i = 0; $i < $count; $i++) {
			$fieldname[$i] = mysql_field_name($result, $i);
			$fullfieldname[$i] = mysql_field_table($result,$i). ".". $fieldname[$i];
			$ret[$fieldname[$i]] = $row[$i];
		}
		$localcontext=array_merge($context, $ret);
		for($i = 0; $i < $count; $i++) {
			if ($filters[$fullfieldname[$i]]) {
					$filter = create_function(\'$x, $context\', $filters[$fullfieldname[$i]]);
					$ret[$fieldname[$i]] = $filter($ret[$fieldname[$i]], $localcontext);
	# echo $filters[$fullfieldname[$i]], " ", $fieldname[$i], " ", $ret[$fieldname[$i]]," ", $filter, "<br>";
			}
		}
		return $ret;
	}
	
	/**
	* Function to filter field of a single class.
	*/
	function merge_and_filter_fields(&$context, $class, &$assoc)
	{
		$filters = array('. $filterstr. ');
		$localcontext = array_merge($context, $assoc);
		foreach($assoc as $k=>$v) {
			if ($filters[$class. ".". $k]) {
				$filter = create_function(\'$x, $context\', $filters[$class. ".". $k]);
				$context[$k] = $filter($v, $localcontext);
			} else {
		$context[$k] = $v;
			}
		}
	}
	?'.'>');
	fclose($fp);

}
?>
