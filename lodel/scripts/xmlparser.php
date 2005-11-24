<?php

/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cnou
 *  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
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

require_once "func.php";

// cette fonction parse un document XML et le met dans une structure equivalente a xml_parse_into_struct, mais seul le namespace qualifie est parse

function array_last(& $arr)
{
	return $arr[count($arr) - 1];
}

function xml_parse_into_struct_ns(& $text, & $values, & $index)
{

	$parser = xml_parser_create();
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0) or die("Parser incorrect");
	xml_set_element_handler($parser, "xml_parse_into_struct_ns_startElement", "xml_parse_into_struct_ns_endElement");
	xml_set_character_data_handler($parser, "xml_parse_into_struct_ns_characterHandler");
	#  xml_set_default_handler($parser, "xml_parse_into_struct_ns_defaultHandler");

	$GLOBALS['into_struct_ns_data'] = "";
	$GLOBALS['into_struct_ns_ind'] = 0;
	$GLOBALS['into_struct_ns_index'] = array ();
	$GLOBALS['into_struct_ns_values'] = array ();

	if (!xml_parse($parser, $text)) {
		echo sprintf("<br>XML error: %s at line %d <br><br>", xml_error_string(xml_get_error_code($parser)), xml_get_current_line_number($parser));
		global $home;
		include $home."checkxml.php";
		checkstring($text);
	}
	$values = $GLOBALS['into_struct_ns_values'];
	$index = $GLOBALS['into_struct_ns_index'];
}

function rebuild_opentag($name, $attrs)
{
	$ret = "<$name";
	foreach ($attrs as $att => $val) {
		$ret .= " $att=\"".translate_xmldata($val)."\"";
	}
	$ret .= ">";
}

function xml_parse_into_struct_ns_startElement($parser, $name, $attrs)
{
	//  echo $name,"<br>";flush();
	if (strpos($name, "r2r:") === 0) {
		$ind = $GLOBALS[into_struct_ns_ind];
		$name = substr($name, 4);
		if (!$GLOBALS[into_struct_ns_index][$name])
			$GLOBALS[into_struct_ns_index][$name] = array ();
		array_push($GLOBALS[into_struct_ns_index][$name], $ind);
		$GLOBALS[into_struct_ns_values][$ind] = array ("tag" => $name, "type" => "complete");
		if ($attrs)
			$GLOBALS[into_struct_ns_values][$ind][attributes] = $attrs;

		$data = trim($GLOBALS[into_struct_ns_data]);
		if ($data)
			$GLOBALS[into_struct_ns_values][$ind -1][value] = $data;
		$GLOBALS[into_struct_ns_data] = "";
		$GLOBALS[into_struct_ns_ind]++;
	} else { # reconstruit le tags
		$GLOBALS[into_struct_ns_data] .= rebuild_opentag($name, $attrs);
	}
}

function xml_parse_into_struct_ns_endElement($parser, $name)
{

	//  echo $name,"<br>";flush();
	if (strpos($name, "r2r:") === 0) {
		$ind = $GLOBALS[into_struct_ns_ind];
		$name = substr($name, 4);
		// cherche le dernier tag ouvert avec ce name
		$openind = array_last($GLOBALS[into_struct_ns_index][$name]);
		if ($openind != $ind -1) {
			$GLOBALS[into_struct_ns_values][$openind][type] = "open";
			array_push($GLOBALS[into_struct_ns_index][$name], $ind);
			$GLOBALS[into_struct_ns_values][$ind] = array ("tag" => $name, "type" => "close");
			$GLOBALS[into_struct_ns_values][$ind][value] = trim($GLOBALS[into_struct_ns_data]);
			$GLOBALS[into_struct_ns_ind]++;
		}	else { // complete
			//      echo "$name  $openind $GLOBALS[into_struct_ns_data]<br>";
			$GLOBALS[into_struct_ns_values][$openind][value] = $GLOBALS[into_struct_ns_data];
		}
		$GLOBALS[into_struct_ns_data] = "";
	} else { # reconstruit le tags
		$GLOBALS[into_struct_ns_data] .= "</$name>";
	}
}

function xml_parse_into_struct_ns_characterHandler($parser, $data)
{
	#  echo $data,"<br>\n";flush();
	$GLOBALS[into_struct_ns_data] .= translate_xmldata($data);
}

//function xml____parse_into_struct_ns_characterDefault($parser,$data)
//
//{ echo $data,"<br>"; }
//
?>