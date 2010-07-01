<?php
/**
 * Fichier du parser XML - Analyse les éléments d'un document XML
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
 * Copyright (c) 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * Copyright (c) 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
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
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 * @since Fichier ajouté depuis la version 0.8
 */

// cette fonction parse un document XML et le met dans une structure equivalente a xml_parse_into_struct, mais seul le namespace qualifie est parse
/**
 * Retourne le dernier élément d'un tableau
 *
 * @param array &$arr le tableau passé par référence
 * @return le dernier élément du tableau
 */
function array_last(& $arr)
{
	//return $arr[count($arr) - 1];
	return end($arr); //cette solution semble plus rapide
}
/**
 * Analyse une structure XML
 *
 * Analyse le fichier XML $text et le place dans deux tableaux : le premier $index contient
 * des pointeurs sur la position des valeurs correspondantes dans le tableau $values. Ces
 * deux paramètres sont passés par références.
 * @param string &$text les données à parser
 * @param array &$values 
 * @param array &$index contient des pointeurs sur la position des valeurs correspondantes dans
 * le tableau values
 */
function xml_parse_into_struct_ns(& $text, & $values, & $index)
{
	$parser = xml_parser_create();
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0) or trigger_error("Parser incorrect", E_USER_ERROR);
	xml_set_element_handler($parser, "xml_parse_into_struct_ns_startElement", "xml_parse_into_struct_ns_endElement");
	xml_set_character_data_handler($parser, "xml_parse_into_struct_ns_characterHandler");

	$GLOBALS['into_struct_ns_data'] = "";
	$GLOBALS['into_struct_ns_ind'] = 0;
	$GLOBALS['into_struct_ns_index'] = array ();
	$GLOBALS['into_struct_ns_values'] = array ();

	if (!xml_parse($parser, $text)) {
		echo sprintf("<br />XML error: %s at line %d <br /><br />", xml_error_string(xml_get_error_code($parser)), xml_get_current_line_number($parser));
		include C::get('home', 'cfg')."checkxml.php";
		checkstring($text);
	}
	$values = $GLOBALS['into_struct_ns_values'];
	$index = $GLOBALS['into_struct_ns_index'];
}

/**
 * Reconstruit un tag ouvert
 *
 * Construit correctement un tag
 *
 * @param string $name le nom du tag
 * @param array $attrs les attributs du tags
 */
function rebuild_opentag($name, $attrs)
{
	$ret = "<$name";
	foreach ($attrs as $att => $val) {
		$ret .= " $att=\"".translate_xmldata($val)."\"";
	}
	$ret .= ">";
}

/**
 * Analyse le début d'élément XML
 *
 *
 * @param resource $parser le parser XML
 * @param string $name le nom de l'élément
 * @param array $attrs les attributs
 */
function xml_parse_into_struct_ns_startElement($parser, $name, $attrs)
{
	//  echo $name,"<br>";flush();
	if (strpos($name, "r2r:") === 0) {
		$ind = $GLOBALS['into_struct_ns_ind'];
		$name = substr($name, 4);
		if (!$GLOBALS['into_struct_ns_index'][$name])
			$GLOBALS['into_struct_ns_index'][$name] = array ();
		array_push($GLOBALS['into_struct_ns_index'][$name], $ind);
		$GLOBALS['into_struct_ns_values'][$ind] = array ("tag" => $name, "type" => "complete");
		if ($attrs)
			$GLOBALS['into_struct_ns_values'][$ind]['attributes'] = $attrs;

		$data = trim($GLOBALS['into_struct_ns_data']);
		if ($data)
			$GLOBALS['into_struct_ns_values'][$ind -1]['value'] = $data;
		$GLOBALS['into_struct_ns_data'] = "";
		$GLOBALS['into_struct_ns_ind']++;
	} else { # reconstruit le tags
		$GLOBALS['into_struct_ns_data'] .= rebuild_opentag($name, $attrs);
	}
}

/**
 * Analyse la fin d'un élément XML
 *
 * @param resource $parser le parser XML
 * @param string $name le nom de l'élément
 */
function xml_parse_into_struct_ns_endElement($parser, $name)
{

	//  echo $name,"<br>";flush();
	if (strpos($name, "r2r:") === 0) {
		$ind = $GLOBALS['into_struct_ns_ind'];
		$name = substr($name, 4);
		// cherche le dernier tag ouvert avec ce name
		$openind = array_last($GLOBALS['into_struct_ns_index'][$name]);
		if ($openind != $ind -1) {
			$GLOBALS['into_struct_ns_values'][$openind]['type'] = "open";
			array_push($GLOBALS['into_struct_ns_index'][$name], $ind);
			$GLOBALS['into_struct_ns_values'][$ind] = array ("tag" => $name, "type" => "close");
			$GLOBALS['into_struct_ns_values'][$ind]['value'] = trim($GLOBALS['into_struct_ns_data']);
			$GLOBALS['into_struct_ns_ind']++;
		}	else { // complete
			//      echo "$name  $openind $GLOBALS[into_struct_ns_data]<br>";
			$GLOBALS['into_struct_ns_values'][$openind]['value'] = $GLOBALS['into_struct_ns_data'];
		}
		$GLOBALS['into_struct_ns_data'] = "";
	} else { # reconstruit le tags
		$GLOBALS['into_struct_ns_data'] .= "</$name>";
	}
}

/**
 * Analyse les données contenues dans un élément
 *
 * @param resource $parser le parser XML
 * @param string $data les données
 */
function xml_parse_into_struct_ns_characterHandler($parser, $data)
{
	#  echo $data,"<br>\n";flush();
	$GLOBALS['into_struct_ns_data'] .= translate_xmldata($data);
}