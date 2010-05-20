<?php
/**
 * Fichier de la classe Genericlogic
 *
 * PHP versions 5
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
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 * @version CVS:$Id:
 */


/**
 * Classe des logiques métiers générique.
 * 
 * <p>Cette classe définit la logique par défaut pour les objets dynamiques de l'interface :
 * entrées, personnes par exemple</p>
 *
 * @package lodel
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
 * @since Classe ajoutée depuis la version 0.8
 * @see logic.php
 */

class GenericLogic extends Logic
{
	/** 
	 * Constructeur de la classe
	 *
	 * Définit le nom de la table type pour l'objet ainsi que le nom du champ identifiant unique.
	 *
	 * @param string $classtype le type d'objet generique, parmis : entities, entries et persons.
	 */
	public function __construct($classtype)
	{
		switch ($classtype) {
		case 'entities' :
			$this->_typetable = "types";
			$this->_idfield = "identity";
			break;
		case 'entries' :
			$this->_typetable = "entrytypes";
			$this->_idfield = "identry";
			break;
		case 'persons' :
			$this->_typetable = "persontypes";
			$this->_idfield = "idperson";
		}
		parent::__construct($classtype);
	}

	/**
	 * Implémentation pour les objets générique de l'action permettant d'appeler l'affichage d'un objet.
	 *
	 * Cette fonction récupère les données de l'objet <em>via</em> la DAO de l'objet. Ensuite elle
	 * met ces données dans le context (utilisation de la fonction privée _populateContext())
	 * 
	 * view an object Action
	 * @param array $context le tableau des données passé par référence.
	 * @param array $error le tableau des erreurs rencontrées passé par référence.
	 * @return string les différentes valeurs possibles de retour d'une action (_ok, _back, _error ou xxxx).
	 */
	public function viewAction(&$context, &$error)
	{
		// define some loop functions
		if(!function_exists('loop_edition_fields')) {
			function loop_edition_fields($context, $funcname)
			{
				global $db;
				function_exists('validfield') || include 'validfunc.php';
				if (!empty($context['class'])) {
					validfield($context['class'], 'class', '', '','data');
					$class = $context['class'];
				}	elseif (!empty($context['type']['class']))	{
					validfield($context['type']['class'], 'class', '', '', 'data');
					$class = $context['type']['class'];
				}	else {
					trigger_error("ERROR: internal error in loop_edition_fields", E_USER_ERROR);
				}
				if(!empty($context['classtype']))
				{
					if ($context['classtype'] == "persons") {
						$criteria = "class='".$class."'";
						// degree is defined only when the persons is related to a document. Is it a hack ? A little no more...
						if (isset($context['identifier'])) {
						$criteria .= " OR class='entities_".$class."'";
						}
					}   elseif ($context['classtype'] == "entries") {
						$criteria = "class='".$class."'";
					}   else {
						$context['id'] = @$context['id'];
						$criteria = "idgroup='". $context['id']."'";
						$context['idgroup'] = $context['id'];
					}
				}
				else
				{
					$context['id'] = @$context['id'];
					$criteria = "idgroup='". $context['id']."'";
					$context['idgroup'] = $context['id'];
				}

				$result = $db->execute(lq("
			SELECT * FROM #_TP_tablefields 
				WHERE ".$criteria." AND status>0 AND edition!='' AND edition!='none'
				AND edition!='importable' ORDER BY rank")) 
					or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
	
				$haveresult = !empty ($result->fields);
				if ($haveresult) {
					call_user_func("code_before_$funcname", $context);
				}
				while (!$result->EOF)	{
					postprocessing($result->fields);
					$localcontext = array_merge($context, $result->fields);
					$name = $result->fields['name'];
					if(isset($context['data'][$name]))
					{
						$localcontext['value'] = ($result->fields['edition'] != "display" && is_string($context['data'][$name])) ?
                                            htmlspecialchars($context['data'][$name]) : $context['data'][$name];
					} else $localcontext['value'] = '';

					call_user_func("code_do_$funcname", $localcontext);
					$result->MoveNext();
				}
				if ($haveresult) {
					call_user_func("code_after_$funcname", $context);
				}
			} //function }}}
		}
		$id = @$context['id'];
		if ($id && !$error) {
			$vo = $this->_getMainTableDAO()->getById($id);
			if (!$vo) {
				trigger_error("ERROR: can't find object $id in the table ".$this->maintable, E_USER_ERROR);
			}
			$this->_populateContext($vo, $context);
		}
		$context['idtype'] = @$context['idtype'];
		$daotype = DAO::getDAO($this->_typetable);
		$votype = $daotype->getById($context['idtype']);
		if (!$votype) {
			trigger_error("ERROR: idtype must me known in GenericLogic::viewAction", E_USER_ERROR);
		}
		
        	$this->_populateContext($votype, $context['type']);
        	$ret = false;
		if ($id && !$error)	{
			$gvo = DAO::getGenericDAO($votype->class, $this->_idfield)->getById($id);
			if (!$gvo) {
				trigger_error("ERROR: can't find object $id in the associated table. Please report this bug", E_USER_ERROR);
			}
			$this->_populateContext($gvo, $context['data']);
			$ret = $this->_populateContextRelatedTables($vo, $context);
		}
		// nettoyage avant affichage
		postprocessing($context);
		return $ret ? $ret : "_ok";
	}

	

	/**
	 * Validated the public fields and the unicity as usual and in addition the typescompatibility
	 *
	 * Validation des champs publics et de l'unicité comme dans la fonction de logic.php. Mais vérifie
	 * la compatibilité des types d'objet en plus.
	 *
	 * @param array $context le tableau des données passé par référence.
	 * @param array $error le tableau des erreurs rencontrées passé par référence.
	 */
	public function validateFields(&$context, &$error)
	{
		// get the fields of class
		function_exists('validfield') || include "validfunc.php";
		if (!empty($context['class'])) {
			$ret = validfield($context['class'], 'class', '', '', 'data');
			$class = $context['class'];
		}	elseif (!empty($context['type']['class'])) {
			$ret = validfield($context['type']['class'], "class", '', '', 'data');
			$class = $context['type']['class'];
		}	else {
			trigger_error("ERROR: internal error in GenericLogic::validateFields", E_USER_ERROR);
		}

		if(true !== $ret) trigger_error('ERROR: invalid class name', E_USER_ERROR);

		$daotablefields = DAO::getDAO("tablefields");
		$fields = $daotablefields->findMany("(class='". $class. "' OR class='entities_". $class. "') AND status>0 ", "", "name,type,class,cond,defaultvalue,allowedtags,edition,g_name");

		// file to move once the document id is know.
		$this->files_to_move = array ();
		$this->_publicfields = array ();
		isset($GLOBALS['lodelfieldtypes']) || include "fieldfunc.php";
		$context['id'] = @$context['id'];
		$context['do'] = @$context['do'];
		foreach ($fields as $field) {
			if ($field->g_name) {
				$this->addGenericEquivalent($class, $field->g_name, $field->name); // save the generic field
			}
			$type = $field->type;
			$name = $field->name;
			$context['data'][$name] = @$context['data'][$name];
			// check if the field is required or not, and rise an error if any problem.
			$value = &$context['data'][$name];

			if (!is_array($value)) {
				$value = trim($value);
			}
			if ($value) {
  				if(is_array($value))
 				{
					$keys = array_keys($value);
					$j = count($value);
 					for($i=0;$i<$j;$i++) {
 						$value[$keys[$i]] = lodel_strip_tags($value[$keys[$i]], $field->allowedtags);
 					}
 				}
 				else {
					$value = lodel_strip_tags($value, $field->allowedtags);
				}
			}

			// is empty ?
			$empty = $type != "boolean" && (// boolean are always true or false
							!isset ($context['data'][$name]) || // not set
							$context['data'][$name] === "" || (is_array($context['data'][$name]) && empty($context['data'][$name]))); // or empty

            		if ($context['do'] == "edit" && ($field->edition == "importable" || 
					$field->edition == "none" || $field->edition == "display")) {

				// in edition interface and field is not editable in the interface
				if ($field->cond != "+") { // the field is not required.
					unset ($value);
					continue;
				} else {
					$value = lodel_strip_tags($field->defaultvalue, $field->allowedtags); // default value
					$empty = false;
				}
			}
			if ($context['id'] > 0 && (($field->cond == "permanent") || ($field->cond == "defaultnew" && $empty))) {
				// or a permanent field
				// or field is empty and the default value must not be used
				unset ($value);
				continue;
			}

			if ($type != "persons" && $type != "entries" && $type != "entities") {
				
				$this->_publicfields[$field->class][$name] = true; // this field is public
			}
			if ($field->edition == "none") {
				unset ($value);
			}			
			if ($empty) {
				$value = lodel_strip_tags($field->defaultvalue, $field->allowedtags); // default value
			}
			if ($field->cond == "+" && $empty) {
				$error[$name] = "+"; // required
				continue;
			}

			// champ unique
			if ($field->cond == 'unique' && !$this->_is_unique($class, $name, $value, $context['id'])) {
				$error[$name] = "1"; // must be unique
				continue;
			}

			// clean automatically the fields when required.
			if (!is_array($value) && isset($GLOBALS['lodelfieldtypes'][$type]['autostriptags']) 
            			&& $GLOBALS['lodelfieldtypes'][$type]['autostriptags']) {
				$value = trim(strip_tags($value));
			}
			
            		$valid = validfield($value, $type, $field->defaultvalue, $name, 'data', '', $context);
			if ($valid === true)	{
				// good, nothing to do.
				if ($type == "file" || $type == "image") {
					if (preg_match("/\/tmpdir-\d+\/[^\/]+$/", $value)) {
						// add this file to the file to move.
						$this->files_to_move[$name] = array ('filename' => $value, 'type' => $type, 'name' => $name);
					}
				}
			}	elseif (is_string($valid))	{
				$error[$name] = $valid; // error
			}	else	{

				// not validated... let's try other type
				switch ($type) {
				case 'persons' :
				case 'entries' :
					// get the type
					if ($type == "persons") {
						$dao = DAO::getDAO("persontypes");
					}	else	{
						$dao = DAO::getDAO("entrytypes");
					}
					$vo = $dao->find("type='".$name."'", "class,id");
					if(!$vo) break; // strange
					$idtype = $vo->id;
					$context[$type][$idtype] = @$context[$type][$idtype];
					$localcontext = &$context[$type][$idtype];
					if (!$localcontext) {
						break;
					}
					if ($type == "entries" && !is_array($localcontext))	{
						$keys = explode(",", $localcontext);
						$localcontext = array ();
						foreach ($keys as $key)	{
							$localcontext[] = array ("g_name" => $key);
						}
					}
					$logic = Logic::getLogic($type); // the logic is used to validate
					if (!is_array($localcontext)) {
						trigger_error("ERROR: internal error in GenericLogic::validateFields", E_USER_ERROR);
					}

					foreach (array_keys($localcontext) as $k)	{
						if (!is_numeric($k) || !$localcontext[$k]) {
							continue;
						}
						$localcontext[$k]['class'] = $vo->class;
						$localcontext[$k]['idtype'] = $idtype;
						$err = array ();
						$logic->validateFields($localcontext[$k], $err);
						if ($err) {
							$error[$type][$idtype][$k] = $err;
						}
					}
					break;
				case 'entities' :
					$context['entities'][$name] = @$context['entities'][$name];
					$value = &$context['entities'][$name];
					if (!$value) {
						// commented by pierre-alain
						// if unset, aliases will not be remove if there were present in database
						// see bug [#5796]
						//unset ($context['entities'][$name]);
						break;
					}
					$ids = array ();
					if(!is_array($value))
					{
						foreach (explode(",", $value) as $id) {
							if ($id > 0) {
								$ids[] = (int)$id;
							}
						}
						$value = $ids;
					}
					else
					{
						foreach ($value as $v) {
							foreach (explode(",", $v) as $id) {
								if ($id > 0) {
									$ids[] = (int)$id;
								}
							}
						}	
					}
					$ids = array_unique($ids);
					$count = $GLOBALS['db']->getOne(lq("SELECT count(*) FROM #_TP_entities WHERE status>-64 AND id ".sql_in_array($ids)));
					if ($GLOBALS['db']->errorno()) {
						trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
					}
					if ($count != count($ids)) {
						trigger_error("ERROR: some entities in $name are invalid. Please report the bug", E_USER_ERROR);
					}
					// don't check they exists, the interface ensure it ! (... hum)
					break;
				default :
					trigger_error("ERROR: unable to check the validity of the field ".$name." of type ".$type, E_USER_ERROR);
				} // switch
			} // if valid
 		} // foreach files
		return empty ($error);
	}
	/**#@+
	 * @access private
	 */
	/**
	 * Déplacement des fichiers associés à l'objet dans le bon répertoire
	 *
	 * @param integer $id l'identifiant numérique de l'objet
	 * @param array $files_to_move un tableau contenant les informations de tous les fichiers (nom et type)
	 * @param object &$vo l'objet virtuel correspondant à l'objet passé par référence
	 */
	protected function _moveFiles($id, $files_to_move, $vo)
	{
		foreach ($files_to_move as $file)
		{
			$src = preg_match("`".SITEROOT."`", $file['filename']) ? $file['filename'] : SITEROOT.$file['filename'];
			$dest = basename($file['filename']); // basename
			if (!$dest) {
				trigger_error("ERROR: error in move_files", E_USER_ERROR);
			}
			// new path to the file
			$dirdest = "docannexe/". $file['type']. "/". $id;
			checkdocannexedir($dirdest);
			$dest = $dirdest. "/". $dest;

			$vo->$file['name'] = addslashes($dest);
			if ($src == SITEROOT. $dest) {
				continue;
			}
			rename($src, SITEROOT. $dest);
			chmod(SITEROOT. $dest, 0666 &octdec(C::get('filemask', 'cfg')));
			@rmdir(dirname($src)); // do not complain, the directory may not be empty
		}
	}

	/**
	 * Définition de l'équivalent générique permanent.
	 * 
	 * <p> Cette fonction utilise un cache statique (tableau global). Elle définit l'équivalent
	 * générique suivant la classe et le nom de l'objet.
	 * </p>
	 * <p> Info :These functions simulate a static cache by using a global array
	 * PHP5 would solve the problem</p>
	 *
	 * @param string $class le nom de la classe de l'objet.
	 * @param string $name le nom du champ.
	 * @param string $value la valeur associée au champ.
	 */
	protected function addGenericEquivalent($class, $name, $value)
	{
		global $genericlogic_g_name;
		@$genericlogic_g_name[$class][$name] = $value;
	}

	/**
	 * Retourne un équivalent générique pour une classe et un champ donné
	 *
	 * @param string $class le nom de la classe de l'objet.
	 * @param string $name le nom du champ.
	 */
	protected function getGenericEquivalent($class, $name)
	{
		global $genericlogic_g_name;
		return @$genericlogic_g_name[$class][$name];
	}

	/**
	 * Vérifie que la valeur d'un champ est unique (pas d'autre occurrence dans la table)
	 *
	 * @param string $class le nom de la classe de l'objet.
	 * @param string $name le nom du champ.
	 * @param string $value la valeur associée au champ.
	 * @return bool true si pas d'autre occurrence, false sinon
	 */
	protected function _is_unique($class, $name, $value, $id) {
		global $db;
 		$id = (int)$id;
		$result = $db->getOne(lq("SELECT count(*) FROM #_TP_$class WHERE $name='$value' AND " . $this->_idfield . " !=$id"));
		if ($result == 0) {
			return true; } else {
			return false; }
	}


	/**
	 * Populate the object from the context. Only the public fields are inputted.
	 * GenericLogic can deal with related table by detecting the class of $vo
	 *
	 * @param object &$vo L'objet virtuel à remplir.
	 * @param array &$context Le tableau contenant les données.
	 */
	protected function _populateObject($vo, &$context)
	{
		//print_r($context);
		$class = strtolower(substr(get_class($vo), 0, -2)); // remove the VO from the class name
		$publicfields = $this->_publicfields();
		
		if (isset($publicfields[$class])) {
			foreach ($publicfields[$class] as $field => $fielddescr) {
				$vo->$field = isset($context[$field]) ? $context[$field] : '';
			}
		}
	}
	

	// begin{publicfields} automatic generation  //
	protected function _publicfields()
	{
		if (!isset ($this->_publicfields))
			trigger_error("ERROR: publicfield has not be created in ". get_class($this). "::_publicfields", E_USER_ERROR);
		return $this->_publicfields;
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	// end{uniquefields} automatic generation  //
	/**#@-*/
} // class 


/**
 *	Fonction de nettoyage des tags XHTML
 *
 * <p>Cette fonction nettoie une chaine de ses balises XHTML. Ce nettoyage tiens compte d'une liste
 * de balises autorisé (attribut allowedtags)</p>
 *
 * @param string $text le texte à nettoyer
 * @param array $allowedtags un tableau contenant la liste des balises autorisées.
 * @param integer $k par défaut à -1. ???
 * @return $text le texte nettoyé
 */
if(!function_exists('lodel_strip_tags'))
{
function lodel_strip_tags($text, $allowedtags, $k = -1)
{
	if (is_array($text)) { //si text est un array alors applique le nettoyage à chaque partie du tableau
		array_walk($text, "lodel_strip_tags", $allowedtags);
		return $text;
	}
	if ((is_numeric($allowedtags) && !is_numeric($k)) || (!is_numeric($allowedtags) && !is_numeric($k))) {
		$allowedtags = $k;
		$k = -1;
	} // for call via array_walk

	isset($GLOBALS['xhtmlgroups']) || include "balises.php";
	static $accepted; // cache the accepted balise;
	global $multiplelevel, $xhtmlgroups;

	// simple case.
	if (!$allowedtags) {
		return strip_tags($text);
	}

	if (!isset($accepted[$allowedtags]))	{ // not cached ?
		$accepted[$allowedtags] = array ();

		// split the groupe of balises
		$groups = preg_split("/\s*;\s*/", $allowedtags);
		array_push($groups, ""); // balises speciales
		// feed the accepted string with accepted tags.
		foreach ($groups as $group) {
            $group = trim($group);
            if(!$group || !isset($xhtmlgroups[$group])) continue;
			// xhtml groups
            foreach ($xhtmlgroups[$group] as $k => $v) {
                if (is_numeric($k))	{
                    $accepted[$allowedtags][$v] = true; // accept the tag with any attributs
                }	else {
                    // accept the tag with attributs matching unless it is already fully accepted
                    if (!isset($accepted[$allowedtags][$k])) {
                        $accepted[$allowedtags][$k][] = $v; // add a regexp
                    }
                }
            }
		} // foreach group
	} // not cached.

	$acceptedtags = $accepted[$allowedtags];

	// the simpliest case.
	if (!$accepted) {
		return strip_tags($text);
	}

	$arr = preg_split("/(<\/?)(\w+:?\w*)\b([^>]*>)/", $text, -1, PREG_SPLIT_DELIM_CAPTURE);

	$stack = array ();
	$count = count($arr);
	for ($i = 1; $i < $count; $i += 4) {
		if ($arr[$i] == "</")	{ // closing tag
			if (!array_pop($stack)) {
				$arr[$i] = $arr[$i +1] = $arr[$i +2] = "";
			}
		}	else { // opening tag
			$tag = $arr[$i +1];
			$keep = false;
			if (isset ($acceptedtags[$tag]))	{
				// simple case.
				if ($acceptedtags[$tag] === true)	{ // simple
					$keep = true;									
				}	else	{ // must valid the regexp
					foreach ($acceptedtags[$tag] as $re)	{
						#echo $re," ",$arr[$i+2]," ",preg_match("/(^|\s)$re(\s|>|$)/",$arr[$i+2]),"<br/>";
						if (preg_match("/(^|\s)$re(\s|>|$)/", $arr[$i +2]))	{
							$keep = true;
							break;
						}
					}
				}
				#	echo "keep:$keep<br/>";
			}
			#echo ":",$arr[$i],$arr[$i+1],$arr[$i+2]," ",htmlentities(substr($arr[$i+2],-2)),"<br/>";
			if (substr($arr[$i +2], -2) != "/>") {// not an opening closing.
				array_push($stack, $keep); // whether to keep the closing tag or not.
			}
			if (!$keep)	{
				$arr[$i] = $arr[$i +1] = $arr[$i +2] = "";
			}
		}
	}
	// now, we know the accepted tags
	return join("", $arr);
}
}
/*-----------------------------------*/
/* loops                             */
?>