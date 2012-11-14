<?php
/**
 * Fichier utilitaire pour gérer la validation des champs
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
 * @author Sophie Malafosse
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
 * @version CVS:$Id:
 * @package lodel
 */


/**
 * Validation des champs
 *
 * <p>Validation des caractères autorisés dans les champs suivant leur type
 * leur nom, et le texte contenu. Par exemple si on a un champ de type email, il faut
 * vérifier que l'adresse mail est bien formée. Idem pour un champ de type url. Cela appliqué 
 * à tous les types de champs gérés par Lodel (cf. fichier fieldfunc.php)</p>
 *
 * @param string $&text le texte à valider. Passé par référence.
 * @param string $type le type du champ à valider.
 * @param string $default la valeur par défaut à valider (si le texte est vide). Est vide par défaut
 * @param string $name le nom du champ
 * @param string $usedata indique si le context utilise le sous tableau data pour stocker les données
 * @param array $context le context utilisé par la fonction appelante
 * @param int $filerank utilisé pour upload multiple de documents dans le meme champ
 * @return boolean true si le champ est valide. false sinon
 */
function validfield(&$text, $type, $default = "", $name = "", $usedata = "", $directory="", $context=null, $filerank=null)
{
	global $db;
	static $tmpdir;
	static $masks = array();

	isset($GLOBALS['lodelfieldtypes']) || include 'fieldfunc.php';

	if (isset($GLOBALS['lodelfieldtypes'][$type]['autostriptags']) && $GLOBALS['lodelfieldtypes'][$type]['autostriptags'] && !is_array($text)) {
		$text = strip_tags($text);
	}
	switch ($type) { //pour chaque type de champ
	case 'xpath':
		if(!$text) {
			$text = $default;
		} else {
			$dom = new DomDocument('1.0', 'UTF-8');
			$dom->loadXML('<root></root>');
			$xpath = new DomXPath($dom);
			$xpath->registerNamespace('tei', "http://www.tei-c.org/ns/1.0/");
			$test = @$xpath->query($text);
			if(false === $test) return $type;
		}
		break;
	case 'history' :
	case 'text' :
	case 'tinytext' :
	case 'longtext' :
		if (!$text) {
			$text = $default;
		} elseif($name && isset($context['class'])) {
			if(!isset($masks[$context['class']])) {
				$masks[$context['class']] = array();
				$fields = $db->execute(lq("select name, mask from #_TP_tablefields where class='{$context['class']}' AND type in ('text', 'longtext', 'tinytext') AND mask !=''"));
				if(!$fields) return true;
				while(!$fields->EOF) {
					$mask = @unserialize(html_entity_decode(stripslashes($fields->fields['mask'])));
					if(!is_array($mask))
					{
						$fields->MoveNext();
						continue;
					}
					$masks[$context['class']][$fields->fields['name']] = array();
					$masks[$context['class']][$fields->fields['name']]['lodel'] = isset($mask['lodel']) ? $mask['lodel'] : '';
					$masks[$context['class']][$fields->fields['name']]['user'] = isset($mask['user']) ? $mask['user'] : '';
					$fields->MoveNext();
				}
				unset($mask);
			}
			if(!empty($masks[$context['class']][$name]['lodel'])) {
				$ret = @preg_match($masks[$context['class']][$name]['lodel'], $text);
				if(FALSE === $ret) trigger_error('Bad regexp for validating variable '.$name.' of class '.$context['class'].' in validfunc.php. Please edit the mask in the editorial model.', E_USER_ERROR);
				// doesn't validate mask
				if(0 === $ret) return 'mask: '.getlodeltextcontents('field_doesnt_match_mask', 'common').' ("'.htmlentities($masks[$context['class']][$name]['user']).'")';
			}
		}

		return true; // always true
		break;
	case 'select_lang':
		if (!preg_match("/^[a-zA-Z]{2}$/", $text)) {
			return $type;
		}
		break;
	case 'type' :
		if ($text && !preg_match("/^[a-zA-Z0-9_][a-zA-Z0-9_ -]*$/", $text)) {
			return $type;
		}
		break;
	case 'class' :
		if (!preg_match("/^[a-zA-Z][a-zA-Z0-9_]*$/", $text)) {
			return $type;
		}
		function_exists('reservedword') || include 'fieldfunc.php';
		if (reservedword($text)) {
			return 'reservedsql'; // if the class is a reservedword -> error
		}
		break;
	case 'classtype' :
		$text = strtolower($text);
		if (!preg_match("/^[a-zA-Z][a-zA-Z0-9_]*$/", $text)) {
			return $type;
		}
		function_exists('reservedword') || include 'fieldfunc.php';
		if (reservedword($text)) {
			return 'reservedsql';
		}
		break;
	case 'tablefield' :
		$text = strtolower($text);
		if (!preg_match("/^[a-z0-9]{2,}$/", $text)) {
			return $type;
		}
		function_exists('reservedword') || include 'fieldfunc.php';
		if (reservedword($text))
			return 'reservedsql';
		break;
		if ($text && !preg_match("/^[a-zA-Z0-9]+$/", $text)) {
			return $type;
		}
		break;
	case 'mlstyle' :
		$text = strtolower($text);
		$stylesarr = preg_split("/[\n,;]/", $text);
		foreach ($stylesarr as $style) {
			$style = trim($style);
			if ($style && !preg_match("/^[a-zA-Z0-9]*(\.[a-zA-Z0-9]+)?\s*(:\s*([a-zA-Z]{2}|--))?$/", $style)) {
				return $type;
			}
		}
		break;
	case 'style' :
		if ($text)
		{
			$text = strtolower($text);
			$stylesarr = preg_split("/[\n,;]/", $text);
			foreach ($stylesarr as $style) {
				if (!preg_match("/^[a-zA-Z0-9]*(\.[a-zA-Z0-9]+)?$/", trim($style))) {
					return $type;
				}
			}
		}
		break;
	case 'passwd' :
		if(!$text) {
			return $type;
		}
		else {
			$len = strlen($text);
			if ($len < 3 || $len > 255 || !preg_match("/^[0-9A-Za-z_;.?!@:,&]+$/", $text)) {
				return $type;
			}
		}
		break;
	
	case 'username' :
		if ($text) {
			$len = strlen($text);
			if ($len < 3 || $len > 64 || !preg_match("/^[0-9A-Za-z_;.?!@:,&\-]+$/", $text)) {
				return $type;
			}
		}
		break;
	case 'lang' : //champ de type langue (i.e fr_FR, en_US)
		if ($text) {
			if (!preg_match("/^[a-zA-Z]{2}(_[a-zA-Z]{2})?$/", $text) &&
 			!preg_match("/\b[a-zA-Z]{3}\b/", $text)) {
				return $type;
			}
		}
		break;
	case 'date' :
		function_exists('mysqldatetime') || include 'date.php';
		if ($text) {
			$textx = mysqldatetime($text, $type);
			if (!$textx || $textx == $type)
				return $type;
			else 
				$text = $textx;
		}	elseif ($default) {
			$dt = mysqldatetime($default, $type);
			if ($dt) {
				$text = $dt;
			} else {
				trigger_error("ERROR: default value not a date or time: \"$default\"", E_USER_ERROR);
			}
		}
		break;
	case 'datetime' :
		function_exists('mysqldatetime') || include 'date.php';
		if ($text) {
			$textx = mysqldatetime($text, $type);
			if (!$textx || $textx == $type)
				return $type;
			else 
				$text = $textx;
		}	elseif ($default) {
			$dt = mysqldatetime($default, $type);
			if ($dt) {
				$text = $dt;
			} else {
				trigger_error("ERROR: default value not a date or time: \"$default\"", E_USER_ERROR);
			}
		}
		break;
	case 'time' : 
		function_exists('mysqldatetime') || include 'date.php';
		if ($text) {
			$textx = mysqldatetime($text, $type);
			if (!$textx || $textx == $type)
				return $type;
			else 
				$text = $textx;
		}	elseif ($default) {
			$dt = mysqldatetime($default, $type);
			if ($dt) {
				$text = $dt;
			} else {
				trigger_error("ERROR: default value not a date or time: \"$default\"", E_USER_ERROR);
			}
		}
		break;
	case 'int' :
		if ((empty ($text) || $text === "") && $default !== "") {
			$text = (int)$default;
		}
		if ($text && (!is_numeric($text) || (int)$text != $text)) {
			return 'int';
		}
		break;
	case 'number' : //nombre
		if ((empty ($text) || $text === "") && $default !== "") {
			$text = doubleval($default);
		}
		if ($text && !is_numeric($text)) {
			return 'numeric';
		}
		break;
	case 'email' :
		if (!$text && $default) {
			$text = $default;
		}
		if ($text && !preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $text)) {
			return 'email';
		}
		break;
	case 'url' :
		if (!$text && $default) {
			$text = $default;
		}
		if ($text) {
			$parsedurl = @parse_url($text);
			if (!$parsedurl || empty($parsedurl['host']) || !preg_match("/^(http|ftp|https|file|gopher|telnet|nntp|news)$/i", $parsedurl['scheme'])) {
				return 'url';
			}
		}
		break;
	case 'boolean' :
		$text = (boolean) ( empty($text) ? $default : ( $text ? 1 : 0 ) );
		break;
	case 'tplfile' :
		$text = trim($text); // should be done elsewhere but to be sure...
		if ($text && (strpos($text, "/") !== false || $text[0] == ".")) {
			return "tplfile";
		}
		break;
	case 'color' :
		if ($text && !preg_match("/^#[A-Fa-f0-9]{3,6}$/", $text)) {
			return 'color';
		}
		break;
	case 'entity' :
		$text = (int)$text;
		// check it exists
		$vo = DAO::getDAO('entities')->getById($text, "1");
		if (!$vo) {
			return 'entity';
		}
		break;
	case 'textgroups' :
		return $text == 'site' || $text == 'interface';
		break;
	case 'select' :
	case 'multipleselect' :
		return true; // cannot validate
	case 'mldate':
		if(is_array($text)){
			function_exists('mysqldatetime') || include 'date.php';
			$str = "";
			foreach($text as $key => $date){
				if(is_int($key)){
					$date = mysqldate($date, "date");
					$str .= "<r2r:ml key=\"{$key}\">{$date}</r2r:ml>";
				}
			}
			$text = $str;
		}
		return true;
	case 'mltext' :
	case 'mllongtext' :
		if (is_array($text)) {
			$str = "";
			foreach ($text as $lang => $v) {
				if ($lang != "empty" && $v)
					$str .= "<r2r:ml lang=\"". $lang. "\">$v</r2r:ml>";
			}
			$text = $str;
		}
		return true;
	case 'list' :
		return true;
	case 'image' :
	case 'file' :
		if (!is_array($text)) {
			unset($text);
			return true;
		}
		if (!$name) {
			trigger_error("ERROR: \$name is not set in validfunc.php", E_USER_ERROR);
		}
        	if(!isset($text['radio'])) $text['radio'] = '';
		$filetodelete = false;
		switch ($text['radio']) {
		case 'upload' :
			// let's upload
			if(!$usedata) {
				$files = &$_FILES;
			} else { //les informations sur le champ se trouve dans $_FILES['data']
				if(isset($filerank))
				{
					if(!isset($_FILES['data']['error'][$name][$filerank]['upload']))
					{
						$text = '';
						return true;
					}
					$files[$name]['error']['upload'] = $_FILES['data']['error'][$name][$filerank]['upload'];
					$files[$name]['tmp_name']['upload'] = $_FILES['data']['tmp_name'][$name][$filerank]['upload'];
					$files[$name]['type']['upload'] = $_FILES['data']['type'][$name][$filerank]['upload'];
					$files[$name]['size']['upload'] = $_FILES['data']['size'][$name][$filerank]['upload'];
					$files[$name]['name']['upload'] = $_FILES['data']['name'][$name][$filerank]['upload'];
				}
				else
				{
					if(!isset($_FILES['data']['error'][$name]['upload']))
					{
						$text = '';
						return true;
					}
					$files[$name]['error']['upload'] = $_FILES['data']['error'][$name]['upload'];
					$files[$name]['tmp_name']['upload'] = $_FILES['data']['tmp_name'][$name]['upload'];
					$files[$name]['type']['upload'] = $_FILES['data']['type'][$name]['upload'];
					$files[$name]['size']['upload'] = $_FILES['data']['size'][$name]['upload'];
					$files[$name]['name']['upload'] = $_FILES['data']['name'][$name]['upload'];
				}
			}
			// on récupère l'extension du fichier   
			$extension = strtolower(strrchr($files[$name]['name']['upload'],'.'));
			// on évite la possibilité d'uploader des fichiers non désirés
			if(!in_array($extension, C::get('authorizedFiles', 'cfg'))) {
				return $text['radio'];
			}
			#print_r($files);
			// look for an error ?
			if (!$files || $files[$name]['error']['upload'] != 0 || !$files[$name]['tmp_name']['upload'] || $files[$name]['tmp_name']['upload'] == "none") {
				unset ($text);
				return 'upload';
			}

			if (!empty($directory)) {
				// Champ de type file ou image qui n'est PAS un doc annexe : copié dans le répertoire $directory
				$text = save_file($type, $directory, $files[$name]['tmp_name']['upload'], $files[$name]['name']['upload'], true, true, $err, false);
			} else {
				// check if the tmpdir is defined
				if (!isset($tmpdir[$type])) {
					// look for a unique dirname.
					do {
						$tmpdir[$type] = "docannexe/$type/tmpdir-". rand();
					}	while (file_exists(SITEROOT. $tmpdir[$type]));
				}
				// let's transfer
				$text = save_file($type, $tmpdir[$type], $files[$name]['tmp_name']['upload'], $files[$name]['name']['upload'], true, true, $err);
			}
			if ($err) {
				return $err;
			}
			return true;
		case 'serverfile' :
			// check if the tmpdir is defined
			if (!empty($directory)) {
				// Champ de type file ou image qui n'est PAS un doc annexe : copié dans le répertoire $directory
				$text = basename($text['localfilename']);
				$text = save_file($type, $directory, SITEROOT."upload/$text", $text, false, false, $err, false);
			} else {
				// check if the tmpdir is defined
				if (!isset($tmpdir[$type])) {
					// look for a unique dirname.
					do {
						$tmpdir[$type] = "docannexe/$type/tmpdir-". rand();
					} while (file_exists(SITEROOT. $tmpdir[$type]));
				}

				// let's move
				$text = basename($text['localfilename']);
				$text = save_file($type, $tmpdir[$type], SITEROOT."upload/$text", $text, false, false, $err);
			}
			if ($err) {
				return $err;
			}
			return true;
		case 'delete' :
			$filetodelete = true;
		case '' :
			// validate
			$text = isset($text['previousvalue']) ? $text['previousvalue'] : '';
			if (!$text) {
				return true;
			}
			if (!empty($directory)) {//echo "text = $text <p>";
				$directory= str_replace('/', '\/', $directory);//echo $directory;
				
				if (!preg_match("/^$directory\/[^\/]+$/", $text)) {
					trigger_error("ERROR: invalid filename of type $type", E_USER_ERROR);
				}
			} else {
				if (!preg_match("/^docannexe\/(image|file|fichier)\/[^\.\/]+\/[^\/]+$/", $text)) {
					trigger_error("ERROR: invalid filename of type $type", E_USER_ERROR);
				}
			}
			if ($filetodelete) {
				unlink(SITEROOT.$text);
				$text = "deleted";
				unset ($filetodelete);
			}
			return true;
		default :
			trigger_error("ERROR: unknow radio value for $name", E_USER_ERROR);
		} // switch
	default :
		return false; // pas de validation
	}

	return true; // validated
}