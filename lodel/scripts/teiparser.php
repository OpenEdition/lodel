<?php
/**
 * Fichier de la classe TEIParser
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
 * @since Fichier ajouté depuis la version 1.0
 * @version CVS:$Id:
 */

/**
 * Classe convertissant la TEI sortie par OTX en tableau de variables
 * 
 * @package lodel
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
 * @since Classe ajoutée depuis la version 1.0
 */
class TEIParser extends XMLReader
{
	/**
	 * @var array tableau des styles locaux
	 * @access private
	 */
	private $_renditions = array();

	/**
	 * @var array liste des champs pour le type courant
	 * @access private
	 */
	private $_tablefields = array();

	/**
	 * @var array liste des styles internes
	 * @access private
	 */
	private $_internalstyles = array();

	/**
	 * @var array liste des styles de caractères
	 * @access private
	 */
	private $_characterstyles = array();

	/**
	 * @var array liste des types d'entrées pour le type courant
	 * @access private
	 */
	private $_entrytypes = array();

	/**
	 * @var array liste des types de personnes pour le type courant
	 * @access private
	 */
	private $_persontypes = array();

	/**
	 * @var array le contenu de la TEI parsé
	 * @access private
	 */
	private $_contents = array();

	/**
	 * @var array logs
	 * @access private
	 */
	private $_logs = array();

	/**
	 * @var array pile des tags ouverts
	 * @access private
	 */
	private $_tags = array();

	/**
	 * @var string référence vers le noeud courant
	 * @access private
	 */
	private $_currentNode;

	/**
	 * @var array noeuds précédents, pour récursion
	 * @access private
	 */
	private $_previousNodes = array();

	/**
	 * @var int nombre incrémental des notes
	 * @access private
	 */
	private $_nbNotes = 0;

	/**
	 * @var string nom de la classe courante
	 * @access private
	 */
	private $_currentClass;

	/**
	 * @var array liste des images
	 * @access private
	 */
	private $_images = array();

	/**
	 * @var string titre du document si trouvé
	 * @access private
	 */
	private $_docTitle = '';

	/**
	 * @var int nombre incrémental des erreurs de styles, utilisé pour les ancres dans le checkbalisage
	 * @access private
	 */
	private $_stylesError = 0;
	
	/**
	 * Constructeur
	 *
	 * Récupère les champs ainsi que les types d'entrées et de personnes, les styles internes et de caractères
	 * pour le style courant
	 *
	 * @param int $idtype id du type courant
	 * @access public
	 */
	public function __construct($idtype)
	{
		function_exists('convertHTMLtoUTF8') || include 'utf8.php';

		$idtype = (int)$idtype;
		if(!$idtype) trigger_error('Invalid idtype', E_USER_ERROR);
		
		$vo = DAO::getDAO('types')->find('import=1 AND id='.$idtype, 'class');
		if(!$vo) trigger_error('Invalid idtype', E_USER_ERROR);
		
		$class =  $GLOBALS['db']->quote($vo->class);
		$entities_class = $GLOBALS['db']->quote('entities_'.$vo->class);
		$personsClassTypes = $entriesClassTypes = array();
		$fields = DAO::getDAO('tablefields')->findMany('status>0 AND class='.$class.' OR class='.$entities_class, 'id', 'name,title,style,type,otx,class,defaultvalue,g_name');
		if($fields)
		{
			foreach($fields as $field)
			{
				if($field->type === 'persons')
				{
					$classType = DAO::getDAO('persontypes')->find('type='.$GLOBALS['db']->quote($field->name), 'class');
					if($classType)
					{
						$personsClassTypes[] = $GLOBALS['db']->quote($classType->class);
						$personsFields = DAO::getDAO('tablefields')->findMany('status>0 AND (class='.$GLOBALS['db']->quote($classType->class).' OR class='.$GLOBALS['db']->quote('entities_'.$classType->class).')', 'id', 'name,title,style,type,otx,class');
						if($personsFields)
						{
							foreach($personsFields as $personField)
							{
								isset($this->_tablefields[$personField->name]) || $this->_tablefields[$personField->name] = $personField;
								$otx = explode(':', $personField->otx);
								$otx = end($otx);
								isset($this->_tablefields[$otx]) || $this->_tablefields[$otx] = $personField;
								$styles = array_map('trim', explode(',', $personField->style));
								foreach($styles as $style)
								{
									isset($this->_tablefields[$style]) || $this->_tablefields[$style] = $personField;
								}
							}
						}
					}
				}
				elseif($field->type === 'entries')
				{
					$classType = DAO::getDAO('entrytypes')->find('type='.$GLOBALS['db']->quote($field->name), 'class');
					if($classType)
					{
						$entriesClassTypes[] = $GLOBALS['db']->quote($classType->class);
					}
				}
				isset($this->_tablefields[$field->name]) || $this->_tablefields[$field->name] = $field;
				$otx = explode(':', $field->otx);
				$otx = end($otx);
				isset($this->_tablefields[$otx]) || $this->_tablefields[$otx] = $field;
				$styles = array_map('trim', explode(',', $field->style));
				foreach($styles as $style)
				{
					isset($this->_tablefields[$style]) || $this->_tablefields[$style] = $field;
				}
			}
			$personsClassTypes = array_unique($personsClassTypes);
			$entriesClassTypes = array_unique($entriesClassTypes);
		}

		$fields = DAO::getDAO('internalstyles')->findMany('status>0', 'id', 'style,surrounding,conversion,otx');
		if($fields)
		{
			foreach($fields as $field)
			{
				$otx = explode(':', $field->otx);
				$otx = end($otx);
				isset($this->_internalstyles[$otx]) || $this->_internalstyles[$otx] = $field;
				$styles = array_map('trim', explode(',', $field->style));
				foreach($styles as $style)
				{
					isset($this->_internalstyles[$style]) || $this->_internalstyles[$style] = $field;
				}
			}
		}

		$fields = DAO::getDAO('characterstyles')->findMany('status>0', 'id', 'style,conversion');
		if($fields)
		{
			foreach($fields as $field)
			{
				$styles = array_map('trim', explode(',', $field->style));
				foreach($styles as $style)
				{
					isset($this->_characterstyles[$style]) || $this->_characterstyles[$style] = $field;
				}
			}
		}

		$fields = DAO::getDAO('persontypes')->findMany('status>0 AND class IN ('.join(',', $personsClassTypes).')', 'id', 'id,type,title,style,otx');
		if($fields)
		{
			foreach($fields as $field)
			{
				isset($this->_persontypes[$field->type]) || $this->_persontypes[$field->type] = $field;
				$otx = explode(':', $field->otx);
				$otx = end($otx);
				isset($this->_persontypes[$otx]) || $this->_persontypes[$otx] = $field;
				$styles = array_map('trim', explode(',', $field->style));
				foreach($styles as $style)
				{
					isset($this->_persontypes[$style]) || $this->_persontypes[$style] = $field;
				}
			}
		}

		$fields = DAO::getDAO('entrytypes')->findMany('status>0 AND class IN ('.join(',', $entriesClassTypes).')', 'id', 'id,type,title,style,otx');
		if($fields)
		{
			foreach($fields as $field)
			{
				isset($this->_entrytypes[$field->type]) || $this->_entrytypes[$field->type] = $field;
				$otx = explode(':', $field->otx);
				$otx = end($otx);
				isset($this->_entrytypes[$otx]) || $this->_entrytypes[$otx] = $field;
				$styles = array_map('trim', explode(',', $field->style));
				foreach($styles as $style)
				{
					isset($this->_entrytypes[$style]) || $this->_entrytypes[$style] = $field;
				}
			}
		}
	}

	/**
	 * Fonction principale, parse la TEI
	 *
	 * @param string $xml la TEI
	 * @param string $odt le fichier sorti par OTX pour récupèrer les images
	 * @param string $tmpdir nom du répertoire temporaire où tout est stocké
	 * @param string $filename nom du fichier à l'import
	 * @access public
	 * @return array le contenu parsé
	 */
	public function parse($xml, $odt, $tmpdir, $filename)
	{
		$xml = (string) $xml;
		$copy = $xml;
		C::clean($xml);
		if(!$xml) trigger_error('ERROR: the TEI is not valid: '.htmlentities($copy, ENT_COMPAT, 'UTF-8'), E_USER_ERROR);

		$odt = (string) $odt;
		$this->_docTitle = (string) $filename;

		if(!empty($odt))
		{
			$unzipcmd = C::get('unzipcmd', 'cfg');
			if('pclzip' == $unzipcmd)
			{// use PCLZIP library
				$err = error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE); // packages compat
				class_exists('PclZip', false) || include 'pclzip/pclzip.lib.php';
				$archive = new PclZip($odt);
				
				$images = $archive->extract(PCLZIP_OPT_PATH, $tmpdir, PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_OPT_BY_PREG, "/^Pictures\/img-\d+/");
				error_reporting($err);
				unset($archive);
				if($images)
				{
					$tmpdir = array_filter(explode('/', $tmpdir));
					$tmpdir = end($tmpdir);
					$tmpdir = SITEROOT.'docannexe/image/'.$tmpdir.'/';
					mkdir($tmpdir);
					chmod($tmpdir, 0777 & octdec(C::get('filemask', 'cfg')));
					foreach($images as $image)
					{
						$file = basename($image['filename']);
						$this->_images[$file] = $tmpdir.$file;
						rename($image['filename'], $this->_images[$file]);
					}
				}
				else
				{
					trigger_error('No files were extracted from the archive', E_USER_ERROR);
				}
				unset($images, $archive);
			}
			else
			{ // use unzip
				$line = `$unzipcmd -o -d $tmpdir $odt`;
				$tmpdir = array_filter(explode('/', $tmpdir));
				$tmpdir = end($tmpdir);
				$line = explode("\n", $line);
				if(count($line) > 1 && !empty($line[1]))
				{
					unset($line[0]);
					$images = array();
					foreach($line as $file)
					{
						$file = trim(substr($file, strpos($file, ':') + 1));
						if(preg_match("/Pictures\/(img-\d+)/", $file))
						{
							$f = basename($file);
							$images[$f] = $file;
						}
					}
					
					if(!empty($images))
					{
						$tmpdir = SITEROOT.'docannexe/image/'.$tmpdir.'/';
						mkdir($tmpdir);
						chmod($tmpdir, 0777 & octdec(C::get('filemask', 'cfg')));
						foreach($images as $image=>$fullimage)
						{
							$this->_images[$image] = $tmpdir.$image;
							rename($fullimage, $this->_images[$image]);
						}
					}
					unset($images);
				}
				else
				{
					trigger_error('No files were extracted from the archive', E_USER_ERROR);
				}
			}
		}
		
		libxml_use_internal_errors(true);
		
		$this->_logs = $this->_contents = $this->_tags = $this->_currentClass = $this->_currentNode = array();
		$this->_contents['entries'] = $this->_contents['persons'] = $this->_contents['errors'] = array();
		$simplexml = simplexml_load_string($xml);
		if(!$simplexml)
		{
			$this->_log("Can't open XML");
			return array();
		}

		$this->_parseRenditions($simplexml->teiHeader->encodingDesc->tagsDecl);

		if($this->XML((string) $simplexml->text->asXML(), 'UTF-8', LIBXML_NOBLANKS | LIBXML_COMPACT | LIBXML_NOCDATA))
		{
			unset($xml);
			while($this->read())
			{
				if(parent::ELEMENT === $this->nodeType)
				{
					$this->_parseElement();
				}
				elseif(parent::END_ELEMENT === $this->nodeType && 'text' !== $this->localName && 'TEI' !== $this->localName && 'front' !== $this->localName && 'back' !== $this->localName)
				{
					$this->_log('Uncatched closing tag '.$this->localName);
				}
				elseif(parent::TEXT === $this->nodeType || parent::WHITESPACE === $this->nodeType || parent::SIGNIFICANT_WHITESPACE === $this->nodeType)
				{
					$this->_log('Uncatched text '.htmlentities($this->value, ENT_COMPAT, 'UTF-8'));
				}
			}
			$this->close();
			$this->_log(libxml_get_errors());

			$this->_parseAfter();
		}
		else
		{
			$this->_log(libxml_get_errors());
			unset($xml);
			$this->_log('Can not open document, aborting');
		}

		if(count($this->_tags)) $this->_log('The number of opening/closing tag does not match : '.print_r($this->_tags,1));

		return $this->_contents;
	}

	/**
	 * Cherche si le style $style est connu
	 *
	 * @access private
	 * @param string $rend le style à chercher
	 * @return bool true si le style est reconnu
	 */
	public function getStyle($style)
	{
		return (bool) $this->_getStyle($style);
	}

	/**
	 * Retourne les logs
	 *
	 * @access public
	 * @return array les logs
	 */
	public function getLogs()
	{
		return (array) $this->_logs;
	}

	/**
	 * Retourne le titre du document si trouvé, ou le nom original du fichier chargé
	 *
	 * @access public
	 * @return string le titre du document
	 */
	public function getDocTitle()
	{
		return (string) $this->_docTitle;
	}

	/**
	 * Converti le contenu pour qu'il soit valide XHTML
	 *
	 * @access private
	 * @param string $text le texte à convertir
	 * @return string le texte converti
	 */
	private function _getText($text)
	{
		$text = htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
		convertHTMLtoUTF8($text);
		return $text;
	}

	/**
	 * Nettoie le texte
	 *
	 * @access private
	 * @param string &$v le texte à nettoyer
	 */
	private function _clean(&$v)
	{
		$v = preg_replace(array(
			'/<(?!td)([a-z]+)\b[^>]*><\/\\1>/i', // strip empty tags
			'/<p[^>]*>\s*(<(table|ul)[^>]*>)/i', // remove paragraph before tables and lists
			'/<\/(table|ul)>\s*<\/p>/i',
			'/<span>(.*?)<\/span>/s', // replace empty spans
			"/\n\s*/"),
				array('', "\\1", "</\\1>", "\\1", ''), $v);

		C::clean($v);
	}

	/**
	 * Pré-valide le contenu en fonction du type du champs
	 * afin d'indiquer à l'utilisateur les erreurs éventuelles
	 * avant passage à la page d'édition de l'entité
	 *
	 * @access private
	 * @param mixed $field le champ à valider, string ou array si type == mltext
	 * @param string $name le nom du champ
	 */
	private function _validField($field, $name)
	{
		if('errors' === $name) return;
		if(!isset($this->_tablefields[$name]))
		{
			$this->_log('Cannot validate unknown field '.$name);
			return;
		}
		$def = $this->_tablefields[$name];
		if($def->type === 'mltext')
		{
			foreach($field as $k => $f)
			{
				$valid = validfield($f, 'text', $def->defaultvalue, $def->name);
				if(false === $valid)
					$this->_log('The field "'.$name.'" is not valid');
				elseif(is_string($valid))
				{
					$this->_contents['errors'][$name][$k] = $valid;
				}
			}

			if(mb_strlen(join('', $field), 'UTF-8') > 65535)
				$this->_log('Field "'.$name.'" is bigger than table definition, the field will be truncated on import');
		}
		else
		{
			$valid = validfield($field, $def->type, $def->defaultvalue, $def->name);
			if(false === $valid)
				$this->_log('The field "'.$name.'" is not valid');
			elseif(is_string($valid))
			{
				$this->_contents['errors'][$name] = $valid;
			}

			switch($def->type)
			{
				case 'text': // 65535
					if(mb_strlen($field, 'UTF-8') > 65535)
						$this->_log('Field "'.$name.'" is bigger than table definition, the field will be truncated on import');
					break;

				case 'tinytext': // 255
					if(mb_strlen($field, 'UTF-8') > 255)
						$this->_log('Field "'.$name.'" is bigger than table definition, the field will be truncated on import');
					break;

				case 'longtext': // 4294967295
					if(mb_strlen($field, 'UTF-8') > 4294967295)
						$this->_log('Field "'.$name.'" is bigger than table definition, the field will be truncated on import');
					break;

				case 'color': // 10
					if(mb_strlen($field, 'UTF-8') > 10)
						$this->_log('Field "'.$name.'" is bigger than table definition, the field will be truncated on import');
					break;

				case 'int': // 0-4294967295
					if($field > 4294967295)
						$this->_log('Field "'.$name.'" is bigger than table definition, the field will be truncated on import');
					break;

				case 'tinyint': // 0-255
					if($field > 255)
						$this->_log('Field "'.$name.'" is bigger than table definition, the field will be truncated on import');
					break;

				default: break;
			}

			if('dc.title' === $def->g_name) $this->_docTitle = $field;
		}
	}

	/**
	 * Termine la conversion des données
	 *
	 * Cette fonction prépare les données pour l'import dans Lodel
	 *
	 * @access private
	 */
	private function _parseAfter()
	{
		$entries = $this->_contents['entries'];
		$persons = $this->_contents['persons'];
		unset($this->_contents['entries'], $this->_contents['persons']);

		// cleaning XHTML
		array_walk_recursive($this->_contents, array($this, '_clean'));

		// validating fields
		include_once 'validfunc.php';
		array_walk($this->_contents, array($this, '_validField'));

		// strip tags from entries
		array_walk_recursive($entries, 'strip_tags');

		$this->_contents['entries'] = $entries;
		unset($entries);

		// get the firstname/lastname field name
		$firstname = $this->_getStyle('author-firstname');
		$lastname = $this->_getStyle('author-lastname');
		
		if(!$firstname && !$lastname)
		{
			$this->_log('No field declared as firstname/lastname for authors, please edit your editorial model');
			return;
		}

		// manages persons
		// we construct the var for lodel and strip tags from firstname/lastname
		foreach($persons as $personType => $ps)
		{
			foreach($ps as $k => $person)
			{
				if(!empty($person['g_name']))
				{
					array_walk_recursive($person['data'], array($this, '_clean'));
					$person['g_name'] = strip_tags($person['g_name']);
					if($lastname && $firstname)
					{
						$name = preg_split("/(\s|\xc2\xa0)+/u", $person['g_name'], -1, PREG_SPLIT_NO_EMPTY);
						$persons[$personType][$k]['data'][$lastname] = array_pop($name);
						$persons[$personType][$k]['data'][$firstname] = join(' ', $name);
					}
					elseif($lastname)
					{
						$persons[$personType][$k]['data'][$lastname] = $person['g_name'];
					}
					elseif($firstname)
					{
						$persons[$personType][$k]['data'][$firstname] = $person['g_name'];
					}
				}
				else
				{
					$this->_log('Warning: we have not found any firstname or lastname for an author : '.print_r($person, 1));
				}
				unset($persons[$personType][$k]['g_name']);
			}
		}

		$this->_contents['persons'] = $persons;
	}

	/**
	 * Cherche si le style $rend est connu et renvoit la correspondance
	 *
	 * @access private
	 * @param string $rend le style à chercher
	 * @param bool $full si l'on doit renvoyer l'intégralité du style
	 * @return mixed tableau du style ou uniquement son nom si $full est à false
	 */
	private function _getStyle($rend, $full = false)
	{
		if(false !== strpos($rend, '-'))
		{
			$style = explode('-', $rend);
			if(isset($this->_tablefields[$style[0]]))
			{
				return $full ? $this->_tablefields[$style[0]] : $this->_tablefields[$style[0]]->name;
			}
		}
		
		if(isset($this->_internalstyles[$rend]))
		{
			return $full ? $this->_internalstyles[$rend] : $this->_internalstyles[$rend]->style;
		}
		elseif(isset($this->_characterstyles[$rend]))
		{
			return $full ? $this->_characterstyles[$rend] : $this->_characterstyles[$rend]->style;
		}
		elseif(isset($this->_entrytypes[$rend]))
		{
			return $full ? $this->_entrytypes[$rend] : $this->_entrytypes[$rend]->type;
		}
		elseif(isset($this->_persontypes[$rend]))
		{
			return $full ? $this->_persontypes[$rend] : $this->_persontypes[$rend]->type;
		}
		elseif(isset($this->_tablefields[$rend]))
		{
			return $full ? $this->_tablefields[$rend] : $this->_tablefields[$rend]->name;
		}
		else
		{
			$this->_log('Unknown style <a href="#unknown_'.$rend.'_'.++$this->_stylesError.'">['.$rend.']</a>');
			return false;
		}
	}

	/**
	 * Enregistre un message de Log
	 *
	 * @access private
	 * @param mixed $msg le(s) message(s) à stocker, peut être un array ou string
	 */
	private function _log($msg)
	{
		if(is_array($msg))
		{
			foreach($msg as $m)
			{
				if(is_object($m))
					$this->_logs[] = $m->message;
				else $this->_logs[] = (string) $m;
			}
		}
		else $this->_logs[] = (string)$msg;
	}

	/**
	 * Récupère les attributs du noeud courant
	 *
	 * @access private
	 * @return array tableau associatif nom => valeur
	 */
	private function _parseAttributes()
	{
		$attrs = array();

		if($this->hasAttributes)
		{
			$this->moveToFirstAttribute();
			do
			{
				$attrs[$this->localName] = 'rendition' === $this->localName ? substr($this->value, 1) : $this->value;
			} while($this->moveToNextAttribute());

			$this->moveToElement();
		}

		return $attrs;
	}

	/**
	 * Gère les noeud de haut niveau
	 *
	 * @access private
	 */
	private function _parseElement()
	{
		if('teiHeader' === $this->localName)
		{
			$this->next();
		}
		elseif('front' === $this->localName)
		{
			$this->_parse();
		}
		elseif('body' === $this->localName)
		{
			$this->_parse();
		}
		elseif('back' === $this->localName)
		{
			$this->_parse();
		}
	}

	/**
	 * Récupère les styles locaux (renditions)
	 *
	 * @access private
	 * @param SimpleXMLElement $tagsDecl le noeud <tagsDecl>
	 */
	private function _parseRenditions(SimpleXMLElement $tagsDecl)
	{
		if(empty($tagsDecl->rendition)) return;

		foreach($tagsDecl->rendition as $r)
		{
			$this->_renditions[(string)$r->attributes('http://www.w3.org/XML/1998/namespace')->id] = (string) $r;
		}
	}

	/**
	 * Retourne le style associé à la rendition
	 *
	 * @access private
	 * @param string $name le nom de la rendition
	 * @return string les styles correspondant ou rien
	 */
	private function _getRendition($name)
	{
		if(isset($this->_renditions[$name]))
			return $this->_renditions[$name];

		$name = strtolower($name);

		if('footnotesymbol' !== $name && 'endnotesymbol' !== $name)
			$this->_log("Can't find the local style for the rendition \"#".$name."\"");

		return '';
	}

	/**
	 * Parse le contenu
	 *
	 * @access private
	 */
	private function _parse()
	{
		$lastAuthor = null;
		$currentTag = '';
		while($this->read())
		{
			if(parent::ELEMENT === $this->nodeType)
			{
				if('div' === $this->localName) continue; // container, not used
				
				$attrs = $this->_parseAttributes();

				if(isset($attrs['rend']) && ('footnotesymbol' === strtolower($attrs['rend']) || 'endnotesymbol' === strtolower($attrs['rend']))) continue;
				if(isset($attrs['rend']) && 'internetlink' === $attrs['rend']) unset($attrs['rend']);

				if(('hi' === $this->localName && !isset($attrs['rend'])) || ('table' === $this->localName && 'frame' === $attrs['rend']) || ('p' !== $this->localName && 'hi' !== $this->localName && 'table' !== $this->localName))
				{
					$this->_currentNode .= $this->_getTagEquiv($this->localName, $attrs);
				}
				else
				{
// 					if('p' === $this->localName && !isset($attrs['rend']) && isset($attrs['rendition']))
// 					{
// 						$attrs['rend'] = $attrs['rendition'];
// 					}
					
					$style = false;

					if(isset($attrs['rend']) && 'heading' !== substr($attrs['rend'], 0, 7))
						$style = $this->_getStyle($attrs['rend'], true);

					if(!$style && isset($attrs['rend']) && 'heading' !== substr($attrs['rend'], 0, 7))
					{
// 						$this->_log('Unknown style for tag "'.$this->localName.'"'.(isset($attrs['rend']) ? ':rend['.$attrs['rend'].']' : ''));
						$attrs['rendition'] = $attrs['rend'];
						unset($attrs['rend']);
					}

					switch(get_class($style))
					{
						case 'persontypesVO':
							!empty($this->_contents['persons'][$style->id]) || isset($this->_contents['persons'][$style->id]) || $this->_contents['persons'][$style->id] = array();
							$this->_contents['persons'][$style->id][] = array();
							$this->_currentNode =& $this->_contents['persons'][$style->id][count($this->_contents['persons'][$style->id])-1]['g_name'];
							$this->_previousNodes[] =& $this->_contents['persons'][$style->id][count($this->_contents['persons'][$style->id])-1]['g_name'];
							$lastAuthor =& $this->_contents['persons'][$style->id][count($this->_contents['persons'][$style->id])-1]['data'];
							break;

						case 'entrytypesVO':
							isset($this->_contents['entries'][$style->id]) || $this->_contents['entries'][$style->id] = '';
							$this->_currentNode =& $this->_contents['entries'][$style->id];
							$this->_previousNodes[] =& $this->_contents['entries'][$style->id];
							break;

						case 'tablefieldsVO':
							if('entities' === $style->type) break;

							$this->_currentClass[] = $style->name;
							if(false === strpos($style->class, 'entities_'))
							{
								if($style->type === 'mltext')
								{
									if(!isset($attrs['lang']))
									{
										if(isset($attrs['rend']))
										{
											$lang = explode('-', $attrs['rend']);
											if(count($lang) > 2)
											{
												$attrs['rend'] = $lang[1];
											}
											$attrs['lang'] = end($lang);
											unset($lang);
										}
										else
										{
											$this->_log('We have a multilangual field and no language available');
											$attrs['lang'] = 'unknown';
										}
									}

									if(isset($this->_contents[end($this->_currentClass)]) && !is_array($this->_contents[end($this->_currentClass)]))
									{
										trigger_error('ERROR: it seems that there are already datas for multilingual style '.end($this->_currentClass).' and no lang has been predefined', E_USER_ERROR);
									}
									isset($this->_contents[end($this->_currentClass)]) || $this->_contents[end($this->_currentClass)] = array();
									isset($this->_contents[end($this->_currentClass)][$attrs['lang']]) || $this->_contents[end($this->_currentClass)][$attrs['lang']] = '';
									$this->_currentNode =& $this->_contents[end($this->_currentClass)][$attrs['lang']];
									$this->_previousNodes[] =& $this->_contents[end($this->_currentClass)][$attrs['lang']];
								}
								else
								{
									if(false !== strpos($attrs['rend'], '-'))
									{
										$rend = explode('-', $attrs['rend']);
										if('frame' === end($rend)) array_pop($rend);
										else array_shift($rend);
										$attrs['rend'] = join('-', $rend);
									}
									isset($this->_contents[end($this->_currentClass)]) || $this->_contents[end($this->_currentClass)] = '';
									$this->_currentNode =& $this->_contents[end($this->_currentClass)];
									$this->_previousNodes[] =& $this->_contents[end($this->_currentClass)];
								}
							}
							else
							{
								$this->_currentNode =& $lastAuthor[$style->name];
								$this->_previousNodes[] =& $lastAuthor[$style->name];
							}

							$this->_currentNode .= $this->_getTagEquiv($this->localName, $attrs);
							break;

						default:
							isset($this->_contents[end($this->_currentClass)]) || $this->_contents[end($this->_currentClass)] = '';
							$this->_currentNode =& $this->_contents[end($this->_currentClass)];
							$this->_previousNodes[] =& $this->_contents[end($this->_currentClass)];
							$this->_currentNode .= $this->_getTagEquiv($this->localName, $attrs);
							break;
					}
				}
			}
			elseif(parent::END_ELEMENT === $this->nodeType)
			{
				if('body' === $this->localName || 'front' === $this->localName || 'back' === $this->localName) break;
				elseif('div' === $this->localName || ($this->getAttribute('rend') && ('footnotesymbol' === strtolower($this->getAttribute('rend')) || 'endnotesymbol' === strtolower($this->getAttribute('rend'))))) continue;
				
				$tags = array_pop($this->_tags);
				if(!empty($tags))
				{
					if(is_array($tags))
					{
						foreach($tags as $tag)
							$this->_currentNode .= '</'.$tag.'>';
					}
					else
					{
						$this->_currentNode .= '</'.$tags.'>';
					}
				}
				
				if('p' === $this->localName || ('hi' === $this->localName && $this->getAttribute('rend') && 'footnotesymbol' !== strtolower($this->getAttribute('rend')) && 'endnotesymbol' !== strtolower($this->getAttribute('rend'))))
				{
					array_pop($this->_currentClass);
					array_pop($this->_previousNodes);
					end($this->_previousNodes);
					if(isset($this->_previousNodes[key($this->_previousNodes)]))
						$this->_currentNode =& $this->_previousNodes[key($this->_previousNodes)];
					elseif(!empty($this->_currentClass))
					{
						$this->_currentNode =& $this->_contents[end($this->_currentClass)];
					}
				}
				
				if(empty($this->_currentClass))
				{
					$this->_currentClass[] = $this->_getStyle('standard');
					$this->_currentNode =& $this->_contents[end($this->_currentClass)];
				}
			}
			elseif(parent::TEXT === $this->nodeType || parent::WHITESPACE === $this->nodeType || parent::SIGNIFICANT_WHITESPACE === $this->nodeType)
			{
				$this->_currentNode .= $this->_getText($this->value);
			}
		}
	}

	/**
	 * Parse une liste
	 *
	 * @access private
	 * @param array $attrs les attributs du noeud
	 */
	private function _parseList(array $attrs)
	{
		$tag = !isset($attrs['type']) || 'unordered' === $attrs['type'] ? 'ul' : 'ol';
		$this->_tags[] = $tag;
		$this->_currentNode .= '<'.$tag;
		!isset($attrs['rendition']) || $this->_currentNode .= ' style="'.$this->_getRendition($attrs['rendition']).'"';
		!isset($attrs['lang']) || $this->_currentNode .= ' xml:lang="'.$attrs['lang'].'" lang="'.$attrs['lang'].'"';
		$this->_currentNode .= ' class="'.end($this->_currentClass).'">';

		while($this->read())
		{
			if(parent::ELEMENT === $this->nodeType)
			{
				if('list' === $this->localName)
				{
					$this->_parseList($attrs);
				}
				elseif('item' === $this->localName)
				{
					$this->_currentNode .= '<li';
					$attributes = $this->_parseAttributes();
					!isset($attributes['lang']) || $this->_currentNode .= ' xml:lang="'.$attrs['lang'].'" lang="'.$attributes['lang'].'"';
					!isset($attributes['rendition']) || $this->_currentNode .= ' style="'.$this->_getRendition($attributes['rendition']).'"';
					$this->_currentNode .= '>';
					$this->_tags[] = 'li';
				}
				else
				{
					$this->_currentNode .= $this->_getTagEquiv($this->localName, $this->_parseAttributes());
				}
			}
			elseif(parent::END_ELEMENT === $this->nodeType)
			{
				$tags = array_pop($this->_tags);
				if(!empty($tags))
				{
					if(is_array($tags))
					{
						foreach($tags as $tag)
							$this->_currentNode .= '</'.$tag.'>';
					}
					else $this->_currentNode .= '</'.$tags.'>';
				}
				
				if('list' === $this->localName) break;
			}
			elseif(parent::TEXT === $this->nodeType || parent::WHITESPACE === $this->nodeType || parent::SIGNIFICANT_WHITESPACE === $this->nodeType)
			{
				$this->_currentNode .= $this->_getText($this->value);
			}
		}
	}

	/**
	 * Parse une bibliographie
	 *
	 * @access private
	 * @param array $attrs les attributs du noeud
	 */
	private function _parseBiblio(array $attrs)
	{
		while($this->read())
		{
			if(parent::ELEMENT === $this->nodeType)
			{
				if('bibl' === $this->localName)
				{
					$this->_currentNode .= $this->_getTagEquiv('p', $this->_parseAttributes());
				}
				else
				{
					$this->_currentNode .= $this->_getTagEquiv($this->localName, $this->_parseAttributes());
				}
			}
			elseif(parent::END_ELEMENT === $this->nodeType)
			{
				if('listBibl' === $this->localName) break;
				$tags = array_pop($this->_tags);
				if(!empty($tags))
				{
					if(is_array($tags))
					{
						foreach($tags as $tag)
							$this->_currentNode .= '</'.$tag.'>';
					}
					else $this->_currentNode .= '</'.$tags.'>';
				}
			}
			elseif(parent::TEXT === $this->nodeType || parent::WHITESPACE === $this->nodeType || parent::SIGNIFICANT_WHITESPACE === $this->nodeType)
			{
				$this->_currentNode .= $this->_getText($this->value);
			}
		}
	}

	/**
	 * Parse une table
	 *
	 * @access private
	 * @param array $attrs les attributs du noeud
	 */
	private function _parseTable(array $attrs)
	{
		$tags = array();
		$this->_currentNode .= '<table id="'.$attrs['id'].'"';
		!isset($attrs['lang']) || $this->_currentNode .= ' xml:lang="'.$attrs['lang'].'" lang="'.$attrs['lang'].'"';
		$this->_currentNode .= ' class="'.end($this->_currentClass).'">';
		!isset($attrs['rendition']) || $this->_currentNode .= ' style="'.$this->_getRendition($attrs['rendition']).'">"';
		$this->_tags[] = 'table';
		
		while($this->read())
		{
			if(parent::ELEMENT === $this->nodeType)
			{
				if('table' === $this->localName)
				{
					$this->_parseTable($attrs);
				}
				elseif('row' === $this->localName)
				{
					$this->_currentNode .= '<tr';
					$attributes = $this->_parseAttributes();
					!isset($attributes['lang']) || $this->_currentNode .= ' xml:lang="'.$attrs['lang'].'" lang="'.$attributes['lang'].'"';
					!isset($attributes['rendition']) || $this->_currentNode .= ' style="'.$this->_getRendition($attributes['rendition']).'"';
					$this->_currentNode .= '>';
					$this->_tags[] = 'tr';
				}
				elseif('cell' === $this->localName)
				{
					$this->_currentNode .= '<td';
					$attributes = $this->_parseAttributes();
					!isset($attributes['cols']) || $this->_currentNode .= ' colspan="'.$attributes['cols'].'"';
					!isset($attributes['rows']) || $this->_currentNode .= ' rowspan="'.$attributes['rows'].'"';	
					!isset($attributes['lang']) || $this->_currentNode .= ' xml:lang="'.$attrs['lang'].'" lang="'.$attributes['lang'].'"';
					!isset($attributes['rendition']) || $this->_currentNode .= ' style="'.$this->_getRendition($attributes['rendition']).'"';
					$this->_currentNode .= '>';
					$this->_tags[] = 'td';
				}
				elseif('s' === $this->localName)
				{
					$this->_currentNode .= $this->_getTagEquiv('p', $this->_parseAttributes());
				}
				else
				{
					$this->_currentNode .= $this->_getTagEquiv($this->localName, $this->_parseAttributes());
				}
			}
			elseif(parent::END_ELEMENT === $this->nodeType)
			{
				$tags = array_pop($this->_tags);
				if(!empty($tags))
				{
					if(is_array($tags))
					{
						foreach($tags as $tag)
							$this->_currentNode .= '</'.$tag.'>';
					}
					else $this->_currentNode .= '</'.$tags.'>';
				}
				
				if('table' === $this->localName) break;
			}
			elseif(parent::TEXT === $this->nodeType || parent::WHITESPACE === $this->nodeType || parent::SIGNIFICANT_WHITESPACE === $this->nodeType)
			{
				$this->_currentNode .= $this->_getText($this->value);
			}
		}
	}

	/**
	 * Parse une figure (== image)
	 *
	 * @access private
	 * @param array $attrs les attributs du noeud
	 */
	private function _parseFigure($attrs)
	{
		while($this->read() && 'figure' !== $this->localName)
		{
			if(parent::ELEMENT === $this->nodeType && 'graphic' === $this->localName)
			{
				$attrs = $this->_parseAttributes();
				$id = basename($attrs['url']);
				$nb = explode('-', $id);
				// get images
				if(isset($this->_images[$id]))
				{
					$attrs['url'] = $this->_images[$id];
				}
				$this->_currentNode .= '<img src="'.$attrs['url'].'" alt="Image '.end($nb).'" id="'.$id.'"/>';
			}
		}
	}

	/**
	 * Ajoute un style local ou une langue
	 *
	 * @access private
	 * @param array $attrs les attributs du noeuds
	 * @param boolean $inline si la méthode est appellée sur un <hi>
	 */
	private function _addLocalStyle(array $attrs, $inline = false)
	{
		if(!empty($attrs['lang']))
			$lang = $attrs['lang'];
		if(!empty($attrs['rendition']))
			$rendition = $this->_getRendition($attrs['rendition']);

		if(empty($lang) && empty($rendition) && empty($attrs['rend'])) return;

		$tags = $inline ? array() : array_pop($this->_tags);
		if(!is_array($tags)) $tags = array($tags);
		$ret = '';

		if(!empty($rendition))
		{
			$styles = array();
			$rendition = preg_split("/\s*(?<!&(apos|quot));\s*/", $rendition);
			$nb = count($rendition);
			for($i=0;$i<$nb;$i++)
			{
				$style = $rendition[$i];
				if('font-style:italic' === $style)
				{
					$ret .= '<em>';
					$tags[] = 'em';
					unset($rendition[$i]);
				}
				elseif('font-weight:bold' === $style)
				{
					$ret .= '<strong>';
					$tags[] = 'strong';
					unset($rendition[$i]);
				}
				elseif('vertical-align:super' === $style)
				{
					$ret .= '<sup>';
					$tags[] = 'sup';
					unset($rendition[$i]);
				}
				elseif('vertical-align:sub' === $style)
				{
					$ret .= '<sub>';
					$tags[] = 'sub';
					unset($rendition[$i]);
				}
				elseif('text-decoration:line-through' === $style)
				{
					$ret .= '<del>';
					$tags[] = 'del';
					unset($rendition[$i]);
				}
				elseif(0 === strpos($style, 'direction'))
				{
					$style = explode(':', $style);
					$attrsAdd[] = 'dir="'.$style[1].'"';
				}
			}
		}

		if(!empty($rendition) || !empty($lang) || !empty($attrs['rend']))
		{
			$tags[] = 'span';
			$ret .= '<span'.(!empty($attrs['rend']) ? ' class="'.$attrs['rend'].'"' : '').
					(!empty($lang) ? ' xml:lang="'.$lang.'" lang="'.$lang.'"' : '') .
					(!empty($rendition) ? ' style="'.join(';', $rendition).'"' : '').
					(!empty($attrsAdd) ? join(' ', $attrsAdd) : '').
				'>';
		}

		$this->_tags[] = $tags;

		return $ret;
	}

	/**
	 * Parse une note
	 *
	 * @access private
	 * @param array $attrs les attributs du noeud
	 */
	private function _parseNote(array $attrs)
	{
		++$this->_nbNotes;
		if(!isset($attrs['place']))
		{
			$this->_log('Missing attribute "place" for note, setting it to "foot"');
			$attrs['place'] = 'foot';
		}
		
		if(!isset($attrs['n']))
		{
			$this->_log('Missing attribute "n" for note, setting it to '.$this->_nbNotes);
			$attrs['n'] = $this->_nbNotes;
		}

		$type = $this->_getStyle($attrs['place'].'note');

		isset($this->_contents[$type]) || $this->_contents[$type] = '';
		$first = false;
		
		while($this->read() && 'note' !== $this->localName)
		{
			if(parent::ELEMENT === $this->nodeType)
			{
				$this->_contents[$type] .= $this->_getTagEquiv($this->localName, $this->_parseAttributes());
				if(!$first)
				{
					$this->_contents[$type] .= '<a class="'.ucfirst($attrs['place']).'noteSymbol" href="#bodyftn'.$this->_nbNotes.'" id="ftn'.$this->_nbNotes.'">'.$attrs['n'].'</a> ';
					$first = true;
				}
			}
			elseif(parent::END_ELEMENT === $this->nodeType)
			{
				$tags = array_pop($this->_tags);
				if(!empty($tags))
				{
					if(is_array($tags))
					{
						foreach($tags as $tag)
							$this->_contents[$type] .= '</'.$tag.'>';
					}
					else $this->_contents[$type] .= '</'.$tags.'>';
				}
			}
			elseif(parent::TEXT === $this->nodeType || parent::WHITESPACE === $this->nodeType || parent::SIGNIFICANT_WHITESPACE === $this->nodeType)
			{
				$this->_contents[$type] .= $this->_getText($this->value);
			}
		}
		$this->_contents[$type] .= '</p>';
		
		return '<a class="'.$attrs['place'].'notecall" id="bodyftn'.$this->_nbNotes.'" href="#ftn'.$this->_nbNotes.'">'.$attrs['n'].'</a>';
	}

	/**
	 * Retourne un équivalent XHTML pour le tag TEI $name
	 *
	 * @access private
	 * @param string $name le nom du tag
	 * @param array $attrs les attributs du noeud
	 */
	private function _getTagEquiv($name, array $attrs)
	{
		if($this->isEmptyElement && 'lb' !== $name) return '';
		
		if(isset($attrs['rend']) && 'heading' === substr($attrs['rend'], 0, 7))
		{
			$level = (int) substr($attrs['rend'], -1);
			if($level <= 0 || $level > 6)
			{
				$this->_log('Warning, you styled a title level ('.$level.') equal to 0 or higher than 6, parsed like a paragraph');
				$tag = '<p class="'.$attrs['rend'].'"';
				$this->_tags[] = 'p';
			}
			else
			{
				$tag = 'h'.$level;
				$this->_tags[] = $tag;
				$tag = '<'.$tag.'>';
			}
			unset($attrs['rend']);

			return $tag.$this->_addLocalStyle($attrs);
		}
		elseif('p' === $name || ('hi' === $name && isset($attrs['rend'])))
		{
			$tag = '<p';
			$tags = 'p';
			$closing = $inline = false;
			if(isset($attrs['rend']) && 'item' !== $attrs['rend']) // escape <li>
			{
				$style = $this->_getStyle($attrs['rend'], true);
				if($style)
				{
					if(($style instanceof entrytypesVO) || ($style instanceof persontypesVO)) 
					{
						return;
					}
					
					if((($style instanceof internalstylesVO) || ($style instanceof characterstylesVO)))
					{
						$s = $style->style;
						if(false !== strpos($s, ','))
						{
							$s = explode(',', $s);
							$s = $s[0];
						}

						if(!empty($style->conversion) && preg_match('/<([a-z0-9]+)(\s+[^>\/]+)?\/?>/', $style->conversion, $m))
						{
							$tags = array($tags, $m[1]); // replace the 'p'
							if('hr' === $m[1] || 'br' === $m[1])
							{ // auto-closing
								$closing = true;
							}
							$tag = '<'.$m[1].(!empty($m[2]) ? $m[2] : '').($closing ? '' : '><p').' class="'.$s.'">'; // and reconstruct the tag
						}
						elseif($style instanceof characterstylesVO)
						{
							$tags = $tag = '';
							$inline = true;
						}
						else $tag .= ' class="'.$s.'">';

						if(!($style instanceof characterstylesVO))
							unset($attrs['rend']);
					}
					else
					{
						unset($attrs['rend']);
						$tag .= ' class="'.$style->name.'">';
					}
				}
				else
				{
					$tag .= ' class="'.$attrs['rend'].'">';
					unset($attrs['rend']);
				}
			}
			else
			{
				$tag .= ' class="'.end($this->_currentClass).'">';
			}

			if($closing)
			{
				return $tag.'/>';
			}
			else
			{
				if(!empty($tags))
					$this->_tags[] = $tags;
				return $tag.$this->_addLocalStyle($attrs, $inline);
			}
		}
		elseif('hi' === $name && isset($attrs['rendition']) || isset($attrs['lang']))
		{
			unset($attrs['rend']);
			return $this->_addLocalStyle($attrs, true);
		}
		elseif('hi' === $name)
		{
			$this->_tags[] = 'span';
			return '<span>';
		}
		elseif('list' === $name)
		{
			return $this->_parseList($attrs);
		}
		elseif('table' === $name)
		{
			return $this->_parseTable($attrs);
		}
		elseif('note' === $name)
		{
			return $this->_parseNote($attrs);
		}
		elseif('figure' === $name)
		{
			return $this->_parseFigure($attrs);
		}
		elseif('listBibl' === $name)
		{
			return $this->_parseBiblio($attrs);
		}
		elseif('pb' === $name)
		{ // page break, we don't need it
			return '';
		}
		elseif('lb' === $name)
		{
			return '<br/>';
		}
		elseif(('ref' === $name || 'ptr' === $name) && isset($attrs['target']))
		{
			$this->_tags[] = 'a';
			return '<a href="'.$attrs['target'].'">';
		}

		$this->_log('The tag '.$name.' is not recognized');
	}
}