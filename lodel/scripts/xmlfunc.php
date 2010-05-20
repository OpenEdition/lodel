<?php
/**
 * Fichier utilitaire de fonctions XML
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
 */

/**
 * Calcul the XML file for an entity
 *
 * @return the indented XML
 */
function calculateXML($context)
{
	function_exists("insert_template") || include 'view.php';
	ob_start();
	insert_template($context, "xml-classe", "", SITEROOT."lodel/edition/tpl/");
	$contents = ob_get_contents();
	ob_end_clean();
	return indentXML($contents);
}
/**
 * Calcul the XSD scheme for a class of entity
 * @return the indented XSD
 */
function calculateXMLSchema($context)
{
	function_exists("insert_template") || include 'view.php';
	ob_start();
	insert_template($context, "schema-xsd", "", SITEROOT."lodel/admin/tpl/");
	$contents = ob_get_contents();
	ob_end_clean();
	return indentXML($contents);
}

/**
 * Indentation d'un contenu XML
 *
 * Indent an XML content
 *
 * @param string $contents le contenu à indenter
 * @param boolean $output indique si on affiche ou non le résultat
 * @param string $indenter la chaine utilisé pour l'indentation
 * @return string le XML indenté
 */
function indentXML($contents, $output = false, $indenter= '  ')
{
    // on vire toute l'indentation existante
    $contents = trim(strtr(preg_replace("/[\t\n\r]+/", '', $contents), array(
                    "\n"    => '',
                    "\r"    => '',
                    "\t"    => '')));
    $dom = new DomDocument;
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    if(@$dom->loadXML($contents)) 
    {
    	$contents = $dom->saveXML();
	unset($dom);
    	if($output)
    	{ 
        	echo $contents;
        	return;
    	}
    	else return $contents;
    }
    
    
	$arr = preg_split("/\s*(<(\/?)(?:\w+:)?[\w-]+(?:\s[^>]*)?>)\s*/", $contents, -1, PREG_SPLIT_DELIM_CAPTURE);
	$ret = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
	if ($output)
		echo $ret;
	$tab = '';
	for ($i = 1 ; $i < count($arr) ; $i += 3) {
		if ($arr[$i +1]) {
			$tab = substr($tab, 2); // closing tag
		}
		if (substr($arr[$i], -2) == "/>") { // opening closing tag
			$out = $tab.$arr[$i].$arr[$i +2]."\n";
		} else
			if (!$arr[$i +1] && $arr[$i +4]) { // opening follow by a closing tags
				$out = $tab.$arr[$i].$arr[$i +2].$arr[$i +3].$arr[$i +5]."\n";
				$i += 3;
			}	else {
				$out = $tab.$arr[$i]."\n";
				if (!$arr[$i +1]) {
					$tab .= "$indenter";
				}
				if (trim($arr[$i +2])) {
					$out .= $tab.$arr[$i +2]."\n";
				}
			}
		if ($output) {
			echo $out;
		}	else {
			$ret .= $out;
		};
	}
	if (!$output)
		return $ret;
	
}

/**
 * Loop Lodelscript permettant de générer le XML d'une entité
 * Decode Balise field
 *
 * @param array &$context le context qui contient toutes les données
 * @param string $funcname le nom de la fonction LOOP
 */
function loop_xsdtypes(&$context, $funcname)
{
	$balises = preg_split("/;/", $context['allowedtags'], -1, PREG_SPLIT_NO_EMPTY);
	if ($balises) {
		call_user_func("code_before_$funcname", $context);
	}
	$count = 0;
	foreach ($balises as $name) {
		if (is_numeric($name)) {
			continue;
		}
		$localcontext = $context;
		$localcontext['count'] = ++$count;
		$localcontext['name'] = preg_replace("/\s/", "_", $name);
		call_user_func("code_do_$funcname", $localcontext);
	}
	if ($balises) {
		call_user_func("code_after_$funcname", $context);
	}
}

/**
 * LOOP lodelscript qui récupère chaque champ d'une entité avec sa valeur
 *
 * Loop that select each field with its value for an entity
 *
 * @param array &$context le context qui contient toutes les données
 * @param string $funcname le nom de la fonction LOOP
 */
function loop_fields_values(& $context, $funcname)
{
	global $error;
	global $db;
	$id = (int)@$context['id'];
	$result = $db->execute(lq("SELECT name,type FROM #_TP_tablefields WHERE idgroup='$id' AND status>0 ORDER BY rank")) 
		or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
	$haveresult = $result->NumRows() > 0;
	if ($haveresult && function_exists("code_before_$funcname")) {
		call_user_func("code_before_$funcname", $context);
	}
	$fields = array();
	$fieldvalued = array();
	while (!$result->EOF) {
		$row = $result->fields;
		if ($row['type'] != 'persons' && $row['type'] != 'entries' && $row['type'] != 'entities') {
			$fieldvalued[] = $row['name'];
		}
		$fields[] = $row;
		$result->moveNext();
	}
	$context['class'] = @$context['class'];
	$context['identity'] = @$context['identity'];
	if (count($fieldvalued) > 0) {
		$sql = lq("SELECT ". implode(',', $fieldvalued). " FROM #_TP_". $context['class']. " WHERE identity='". $context['identity']. "'");
		$rowsvalued = $db->getRow($sql);
	}

	foreach ($fields as $row) {
		$localcontext = array ();
		$localcontext['name'] = $row['name'];
		$localcontext['type'] = $row['type'];
		$localcontext['identity'] = $context['identity'];
		if (isset($rowsvalued[$row['name']])) {
			$localcontext['value'] = $rowsvalued[$row['name']];
		}
		call_user_func("code_do_$funcname", $localcontext);
	}

	if ($haveresult && function_exists("code_after_$funcname")) {
		call_user_func("code_after_$funcname", $context);
	}
}


/**
 * LOOP lodelscript qui récupère chaque champ d'une personne ou d'une entrée avec sa valeur
 *
 * @param array &$context le context qui contient toutes les données
 * @param string $funcname le nom de la fonction LOOP
 */
function loop_entry_or_persons_fields_values(&$context, $funcname)
{
	global $error;
	global $db;

	$context['nature'] = @$context['nature'];
	if ($context['nature'] == 'G') {
		$table = '#_TP_persontypes';
		$id = 'idperson';
	} elseif ($context['nature'] == 'E') {
		$table = '#_TP_entrytypes';
		$id = 'identry';
	}
	$sql = "SELECT t.name, t.class, t.type,t.cond FROM #_TP_tablefields as t, $table as et";
	$sql .= " WHERE et.type='". $context['name']. "' AND et.class=t.class";
	$result = $db->execute(lq($sql));
	$haveresult = $result->NumRows() > 0;
	if ($haveresult && function_exists("code_before_$funcname")) {
		call_user_func("code_before_$funcname", $context);
	}

	while (!$result->EOF) {
		$row = $result->fields;
		if (!$class) {
			$class = $row['class'];
		}
		$fields[$row['name']] = $row;
		$result->moveNext();
	}
	
	if (is_array($fields)&& count($fields) > 0) $fieldnames = array_keys($fields);
	$context['id2'] = @$context['id2'];
	//$fieldnames = array_keys($fields);
	if (is_array($fieldnames) && count($fieldnames) > 0) {
		$sql = lq("SELECT ". implode(',', $fieldnames). " FROM #_TP_". $class. " WHERE $id='". $context['id2']."'");
		$values = $db->getRow($sql);
		foreach ($fields as $key => $row) {
			$localcontext = array();
			$localcontext['name'] = $row['name'];
			if ($values[$row['name']]) {
				$localcontext['value'] = $values[$row['name']];
			}
			else {
				$localcontext['value'] = '';
			}
			call_user_func("code_do_$funcname", $localcontext);
		}
	}

	if ($haveresult && function_exists("code_after_$funcname")) {
		call_user_func("code_after_$funcname", $context);
	}

}
/**
 * Loop that select each field of a relation between an entity and a person for an entity
 *
 * @param array &$context le context qui contient toutes les données
 * @param string $funcname le nom de la fonction LOOP
 */
function loop_person_relations_fields(&$context, $funcname)
{
	global $error;
	global $db;
	$context['class'] = @$context['class'];
	$sql = "SELECT t.name, t.class, t.type,t.cond FROM #_TP_tablefields as t";
	$sql .= " WHERE t.class='entities_". $context['class']."'";
	$result = $db->execute(lq($sql));
	$haveresult = $result->NumRows() > 0;
	if ($haveresult && function_exists("code_before_$funcname")) {
		call_user_func("code_before_$funcname", $context);
	}

	while (!$result->EOF) {
		$row = $result->fields;
		if (!$class) {
			$class = $row['class'];
		}
		$fields[$row['name']] = $row;
		$result->moveNext();
	}

	if (is_array($fields)&& count($fields) > 0) $fieldnames = array_keys($fields);	
	//$fieldnames = array_keys($fields);
	if (is_array($fieldnames) && count($fieldnames) > 0) {
		$context['idrelation'] = @$context['idrelation'];
		$sql = lq("SELECT ". implode(',', $fieldnames). " FROM #_TP_". $row['class']. " WHERE idrelation='". $context['idrelation']. "'");
		$values = $db->getRow($sql);
		foreach ($fields as $key => $row) {
			$localcontext = array ();
			$localcontext['name'] = $row['name'];
			if ($values[$row['name']]) {
				$localcontext['value'] = $values[$row['name']];
			}
			call_user_func("code_do_$funcname", $localcontext);
		}
	}
	if ($haveresult && function_exists("code_after_$funcname"))
		call_user_func("code_after_$funcname", $context);

}

/**
 * Put the XHTML namespace in each tag with no namespace and delete r2r namespace
 * Met le namespace xhtml pour toutes balises qui n'ont pas de namespace et supprime le namespace r2r.
 *
 * @param string $text le texte auquel on rajoute le namespace
 */
function namespace($text)
{
	$ns = "xhtml";
	// put namespace on each html tag
	$text = preg_replace(array("/<(\/?)(\w+(\s+[^>]*)?>)/", // add xhtml
											"/(<\/?)r2r:/"), // remove r2r
	array("<\\1$ns:\\2", "\\1"), $text);
	// then put namespace on each attribute
	return $text;
}
?>