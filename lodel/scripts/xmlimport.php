<?php
/**
 * Fichier de la classe XMLImport
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

/**
 * Classe XMLImport
 * 
 * Import XMLLodelBasic file in the database
 *
 * @package lodel
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajoutée depuis la version 0.8
 */
class XMLImportParser
{
	/**
	 * Styles principaux
	 * @var array
	 */
	var $commonstyles;
	
	/**
	 * Styles contextuels
	 * @var array
	 */
	var $contextstyles;
	
	/**
	 * Styles courant
	 * @var array
	 */
	var $cstyles;
	
	/**
	 * Classe du document
	 * @var string
	 */
	var $mainclass;

	/**
	 * Constructeur
	 */
	function XMLImportParser()
	{
	}

	/**
	 * Initialisation du parser
	 *
	 * Initialize the parser, getting all the styles defined in the ME : internal styles 
	 * and characterstyles (synonym styles and different language styles are detected).
	 *
	 * @param string $class the name of the class of the object (entity) imported
	 */
	function init($class)
	{
		global $phpversion;
		if (!$this->commonstyles) {
			defined('INC_FUNC') || include 'func.php';
			// get internal styles and prepare them (detect synonym styles, same style in different lang)
			$dao = DAO::getDAO('internalstyles');
			$iss = $dao->findMany('status > 0');
			foreach ($iss as $is) {
				// analyse the styles
				foreach (preg_split("/[,;]/", $is->style) as $style) {
					$this->_prepare_style($style, $is);
					if ($style)
						$this->commonstyles[$style] = $is;
				}
			}
			// get characterstyles
			$dao = DAO::getDAO('characterstyles');
			$css = $dao->findMany('status > 0');
			foreach ($css as $cs) {
				foreach (preg_split("/[,;]/", $cs->style) as $style) {
					$this->_prepare_style($style, $cs);
					if ($style)
						$this->commonstyles[$style] = $cs;
				}
			}
		}
		$phpversion = explode('.', PHP_VERSION);
		$this->_init_class($class);
		$this->mainclass = $class;
	} //end of init()

	/**
	 * Analyse du contenu XHTML de l'entité
	 *
	 * Parse the XHTML contents of the entity and send the data to the $handler object
	 * This function is a hard piece of work.
	 * I choose not to go to DOM too avoid using lot of memory and processing
	 * to build the tree but I'm not sure to perform much better here.
	 * @param string $string the string to parse
	 * @param object &$handler the handler of the parser
	 */
	function parse($string, &$handler)
	{
		$this->handler = &$handler; // non-reentrant
		# ! Pay attention to the following line ! 
		$this->handler->commonstyles = $this->commonstyles; //get the styles used to parse the doc
		$arr = preg_split("/<(\/?)soo:block(>|\s+class=\"[^\"]*\"\s*>)/", $string, -1, PREG_SPLIT_DELIM_CAPTURE); //split the string using the ServOO block <soo:block>
		//$arr contain now all the elements of the doc
		/* the array is constructed like the following
			[1] => 
			[2] => tablefieldsvo Object or internalstylesvo Object
			[3] => content
			[4] => / (end of the object)
			[5] => tablefieldsvo Object or internalstylesvo Object
			 this explain why in the loop the iterator on the object is incremented by 3.
		*/

		unset ($string); // save memory
		$this->_objectize($arr, true); // make object whereever it is possible.
	
		// second pass
		// process the internalstyles
		// this is an hard piece of code doing no so much... but I find no better way.
		$isave = false;
		$n = count($arr);
		for ($i = 1; $i < $n; $i += 3) { //for each block element
			if ($arr[$i] == "/" || !is_object($arr[$i +1]))
				continue;
			$obj = & $arr[$i +1];
			$class = strtolower(get_class($obj));
			if (!$isave && $class == 'internalstylesvo') {
				$forcenext = false;
				if ($obj->surrounding == "-*") {
					// check what is the previous on.
					if ($arr[$i -3] == "/" && strtolower(get_class($arr[$i -2])) == 'tablefieldsvo') {
						// good, put the closing tag further
						$closing = array_splice($arr, $i -3, 3);
						$i += 3;
						array_splice($arr, $i, 0, $closing); // put after the closing internalstyles 
					} else {
						$forcenext = true;
					}
					continue;
				}
				if ($forcenext || $obj->surrounding == "*-") {
					$isave = $i; // where to insert the next opening
				} else {
					// surrounding is a proper tag
					$obj = & $this->commonstyles[$obj->surrounding];
					array_splice($arr, $i, 0, array ('', $obj, '')); // opening tag
					$i += 3;
					$n += 3;
					array_splice($arr, $i +6, 0, array ("/", $obj, '')); // closing tag after the closing internal tag
					$n += 3;
				}
			}	else
				if ($class == 'tablefieldsvo' && $isave) {
					// put the opening at $isave
					$arr[$i -1] .= $arr[$i +2]; // copy data. This is not the most efficient, must the nicest way to do
					$arr[$i +2] = '';
					$opening = array_splice($arr, $i, 3);
					array_splice($arr, $isave, 0, $opening);
					$isave = false;
				}
		} // end for

		// deal with the non-greedy internalstyle
		// data using such style and containing table, lists, ... are modified : those elements are excluded
		// a little bit hard, isn't it ?
		for ($i = 1; $i < $n; $i += 3) {
			if (!is_object($arr[$i +1]))
				continue;
			$obj = & $arr[$i +1];
			$class = strtolower(get_class($obj));
			if ($class != 'internalstylesvo' || $obj->greedy)
				continue;
			if ($arr[$i] == "/") {
				// closing
			}	else {
				$arr2 = preg_split("/(<\/?)((?:table|ul|ol|dl|dd|pre|blockquote|object)\b[^>]*>)/", $arr[$i +2], -1, PREG_SPLIT_DELIM_CAPTURE);
				$arr[$i +2] = $arr2[0];
				$m = count($arr2);
				$blockel = 0;
				for ($j = 1; $j < $m; $j += 3) {
					if ($arr2[$j] == '</') { // closing
						$arr[$i +2] .= $arr2[$j].$arr2[$j +1];
						$blockel --;
						if ($blockel == 0) {
							array_splice($arr, $i +3, 0, array ("", $obj, "")); // opening tag
							$i += 3;
							$n += 3;
						}
					} else {
						// opening
						$blockel ++;
						if ($blockel == 1) {
							array_splice($arr, $i +3, 0, array ("/", $obj, '')); // closing tag
							$i += 3;
							$n += 3;
						}
						$arr[$i +2] .= $arr2[$j].$arr2[$j +1];
					}
					$arr[$i +2] .= $arr2[$j +2];
				}
			} // opening
		}
		// proper parser. Launch the handlers
		$datastack = array ();
		$classstack = array (array ($this->mainclass, 'entities'));
		$handler->openClass($classstack[0]);
		$this->nbdoc = 0;
		
		for ($i = 1; $i < $n; $i += 3) {
			$this->_parseOneStep($arr, $i, $datastack, $classstack, 'block');
			
			$larr = preg_split("/<(\/)?soo:inline(>|\s+class=\"[^\"]*\"\s*>)/", $arr[$i +2], -1, PREG_SPLIT_DELIM_CAPTURE);
			$nj = count($larr);
			$datastack[0] .= $larr[0];
			if ($nj > 1) {
				$this->_objectize($larr, false);
				for ($j = 1; $j < $nj; $j += 3) {
					$this->_parseOneStep($larr, $j, $datastack, $classstack, 'inline');
					$datastack[0] .= $larr[$j +2];
				}
			}
		}
		// close the last tags
		while ($classstack) {
			$handler->closeClass(array_shift($classstack), $this->nbdoc > 1);
		}
	} // end of function parser

	/**
	 * Première étape du parser
	 *
	 * do one step of the parser.
	 * 1/ call the handler corresponding to the current style/tag/object
	 * 2/ change the context if required
	 * 3/ feed the datastack
	 *
	 * @param array &$arr ??
	 * @param integer $i ??
	 * @param array &$datastack pile des données
	 * @param array &$classstack pile des classes utilisées
	 * @param string $level can be inline or ?
	 * @access private
	 */
	function _parseOneStep(& $arr, $i, & $datastack, & $classstack, $level)
	{
		//echo $classstack[0];
		$opening = $arr[$i] != "/";
		$obj = & $arr[$i +1];
		if (((!$opening && isset($arr[$i +4]) && $obj == $arr[$i +4]) || ($opening && isset($arr[$i -2]) && $obj == $arr[$i -2])) && (strtolower(get_class($obj)) != 'internalstylesvo' || $obj->greedy)) {
			// current closing equals next opening
			// or current opening equals last closing
			return;
		}
		if(!isset($datastack[0])) $datastack[0] = '';
		if (!is_object($obj)) {
			// unknow style
			if ($opening) {
				if ($level == 'inline') {
					array_unshift($datastack, '');
				} else {
					$datastack[0] = "";
				}
			} elseif ($obj == 'documents') {
				// do nothing
			} else {
				if ($level == 'inline') {
					$data = array_shift($datastack);
					$datastack[0] .= $this->handler->unknownCharacterStyle($obj, $data);
				} else {
					// close up to the base
					while (count($classstack) > 1) {
						$this->handler->closeClass($classstack[0]);
						array_shift($classstack);
					}
					$datastack[0] = $this->handler->unknownParagraphStyle($obj, $datastack[0]);
				}
			}
			return;
		}
		$class = strtolower(get_class($obj));
		switch ($class) {
			case 'internalstylesvo' :
			case 'characterstylesvo' :
				if ($opening) {
					array_unshift($datastack, '');
				} else {
					$call = 'process'. substr($class, 0, -2);
					#echo count($datastack);
					$data = array_shift($datastack);
					$datastack[0] .= $this->handler->$call ($obj, $data); // call the method associated with the object class
				}
			break;
			case 'tablefieldsvo' :
				$cstyles = & $this->contextstyles[$classstack[0][0]];
				if (empty($cstyles[$obj->style])) { // context change	 ?
					$this->handler->closeClass($classstack[0]);
					$cl = array_shift($classstack);
					if (empty($this->contextstyles[$cl[0]][$obj->style])) {
						// must be in the context below
						// if not... problem.
					}
					// new context
				}
				if ($opening) {
					if ($obj->g_name == 'dc.title' && count($classstack) == 1) {
						$this->nbdoc++;
						if ($this->nbdoc > 1) {
							$this->handler->closeClass($classstack[0], true);
							$this->handler->openClass($classstack[0], null, true);
						}
					}
					array_unshift($datastack, '');
				} else {
					$data = array_shift($datastack);
					$datastack[0] .= $this->handler->processTableFields($obj, $data); // call the method associated with the object class
				}
				break;
			case 'entrytypesvo' :
			case 'persontypesvo' :
				if ($opening) { // opening. Switch the lowest context
					// close up to the base
					while (count($classstack) > 1) {
						$this->handler->closeClass($classstack[0]);
						array_shift($classstack);
					}
					array_unshift($datastack, "");
	
					// change the context
					$classtype = $class == 'entrytypesvo' ? 'entries' : 'persons';
					array_unshift($classstack, array ($obj->class, $classtype));
					$this->handler->openClass($classstack[0], $obj);
				}	else {
					$call = 'process'. substr($class, 0, -2);
					$this->handler->$call ($obj, $datastack[0]); // call the method associated with the object class
					$datastack[0] = '';
				}
				break;
			default :
				trigger_error("ERROR: internal error in XMLImportParser::parse. Unknown class $class", E_USER_ERROR);
		}
	} //end of _parse_step_one

	/**
	 * Initialise la classe en cherchant à savoir ce qu'il faut faire avec les styles
	 * détectés. (suivant les champs définies dans le ME).
	 *
	 * Gather information from tablefield to know what to do with the various styles.
	 * class is the context and criteria is the where to select the tablefields
	 *
	 * @param string $class the name of the class to init
	 * @param string $criteria the possible SQL criterions (by default empty)
	 */
	function _init_class($class, $criteria = '')
	{
		global $phpversion;
// 		if(!function_exists('clone')) // pour pouvoir utiliser clone en php5
// 			require 'php4.inc.php';
	
		if (isset($this->contextstyles[$class]))
			return; // already done

		// get all the information from the database for all the fields
		$dao = DAO::getDAO('tablefields');
		if (!$criteria) {
			$criteria = "class='".$class."'";
		}
		$tfs = $dao->findMany("(".$criteria.") AND status>0");
		
		// create an assoc array style => tf information
		foreach ($tfs as $tf) {
			// is it an index ?
			if ($tf->type == 'entries' || $tf->type == 'persons') {
				// yes, it's an index. Get the object
				$dao = DAO::getDAO($tf->type == 'entries' ? 'entrytypes' : 'persontypes');
				if($tf->type == 'entries' && preg_match('/^([a-z0-9\-]+)\.(\d+)$/', $tf->name, $res))
				{
					$GLOBALS['db']->SelectDB(DATABASE.'_'.$res[1]);
					$tf = $dao->find("id=".$res[2]);
					$tf->external = $res[1];
				}
				else $tf = $dao->find("type='".$tf->name."'");
				$this->_init_class($tf->class, "class='".$tf->class."' OR class='entities_".$tf->class. "'");
				usecurrentdb();
			}
			// analyse the styles of the tablefields
			foreach (preg_split("/[,;]/", $tf->style) as $style) {
					$tf2 = clone ($tf);
					$this->_prepare_style($style, $tf2);
					if ($style)
						$this->commonstyles[$style] = $this->contextstyles[$class][$style] = $tf2;
			}
		}
	} //end of init_class

	/**
	 * Prépare un style pour stocakge en détectant les synonymes et les langues d'un style
	 * Prepare the style for storage detecting synonyms and same language style
	 *
	 * @param string &$style the name of the style
	 * @param object &$object the VO corresponding to the style
	 */
	function _prepare_style(& $style, & $obj)
	{
		$style = strtolower(trim($style));
		// style synonyme. take the first one
		@list ($style, $lang) = explode(":", $style);
		if ($lang) {
			$obj->lang = $lang;
			$obj->style = $style;
		} else {
			// style synonyme. take the first one
			$obj->style = preg_replace("/[:,;].*$/", '', $obj->style);
		}
	}

	/**
	 * Remplace un style par un objet quand c'est possible
	 *
	 * Replace style by object whenever it is possible
	 *
	 * @param array &$arr an array containing all the elements of a doc
	 * @param boolean $blockstyle true if the style is a block style and false if not
	 */
	function _objectize(& $arr, $blockstyle)
	{
		$stylesstack = array ();
		$n = count($arr);
		for ($i = 1; $i < $n; $i += 3) {
			$opening = $arr[$i] != "/";
			if ($opening) { // opening tag
				if (!preg_match("/class=\"([^\"]*)\"/", $arr[$i +1], $result))
					trigger_error("ERROR: in _objectize", E_USER_ERROR);
				$name = preg_replace("/\W/", "", makeSortKey($result[1]));
				$obj = & $this->commonstyles[$blockstyle ? $name : ".".$name];
				if ($obj) {
					$arr[$i +1] = & $obj;
				} else {
					$arr[$i +1] = $name;
				}
				array_push($stylesstack, $arr[$i +1]);
			} else { // closingtag
				$arr[$i +1] = array_pop($stylesstack);
				continue; // nothing to do
			}
		}
		if ($stylesstack) {
			print_r($arr);
			print_r($stylesstack);
			trigger_error("ERROR: XML is likely invalid in XMLImportParser::_objectize", E_USER_ERROR);
		}
	}
} // end of class XMLImportParser
?>
