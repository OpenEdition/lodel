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
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * Copyright (c) 2008, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * Copyright (c) 2009, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
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
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * @copyright 2009, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajout� depuis la version 1.0
 * @version CVS:$Id:
 */

/**
 * Classe convertissant la TEI sortie par OTX en tableau de variables
 * 
 * @package lodel
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * @copyright 2009, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @since Classe ajout�e depuis la version 1.0
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
	 * @var array liste des styles de caract�res
	 * @access private
	 */
	private $_characterstyles = array();

	/**
	 * @var array liste des types d'entr�es pour le type courant
	 * @access private
	 */
	private $_entrytypes = array();

	/**
	 * @var array liste des types de personnes pour le type courant
	 * @access private
	 */
	private $_persontypes = array();

	/**
	 * @var array le contenu de la TEI pars�
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
	 * @var string r�f�rence vers le noeud courant
	 * @access private
	 */
	private $_currentNode;

	/**
	 * @var array noeuds pr�c�dents, pour r�cursion
	 * @access private
	 */
	private $_previousNodes = array();

	/**
	 * @var int nombre incr�mental des notes
	 * @access private
	 */
	private $_nbNotes = 0;

	/**
	 * @var string nom de la classe courante
	 * @access private
	 */
	private $_currentClass;

	/**
	 * Constructeur
	 *
	 * R�cup�re les champs ainsi que les types d'entr�es et de personnes, les styles internes et de caract�res
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
		$fields = DAO::getDAO('tablefields')->findMany('status>0 AND class='.$class.' OR class='.$entities_class, 'id', 'name,title,style,type,otx,class');
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
	 * @param string $odt le fichier sorti par OTX pour r�cup�rer les images
	 * @access public
	 * @return array le contenu pars�
	 */
	public function parse($xml, $odt)
	{
		$xml = (string) $xml;
		
		C::clean($xml);
		if(!$xml) trigger_error('ERROR: the TEI is not valid', E_USER_ERROR);

		$odt = (string) $odt;
	
		if(!empty($odt))
		{
			$unzipcmd = C::get('unzipcmd', 'cfg');
			if('pclzip' == $unzipcmd)
			{// use PCLZIP library
				$err = error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE); // packages compat
				class_exists('PclZip', false) || include 'pclzip/pclzip.lib.php';
				$archive = new PclZip($odt);
				
				$images = $archive->extract(PCLZIP_OPT_PATH, tmpdir(), PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_OPT_BY_PREG, "/^Pictures\/img-\d+/");
				error_reporting($err);
				unset($archive);
				if($images)
				{
					$tmpdir = SITEROOT.'docannexe/image/'.uniqid('tmpdir-', true).'/';
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
					$this->_log('No files were extracted from the archive');
				}
				unset($images, $archive);
			}
			else
			{ // use unzip
				$tmpdir = tmpdir();
				$line = `$unzipcmd -o -d $tmpdir $odt`;
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
						$tmpdir = SITEROOT.'docannexe/image/'.uniqid('tmpdir-', true).'/';
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
					$this->_log('No files were extracted from the archive');
				}
			}
		}
		
		libxml_use_internal_errors(true);
		
		$this->_logs = $this->_contents = $this->_tags = $this->_currentClass = $this->_currentNode = array();
		$this->_contents['entries'] = $this->_contents['persons'] = array();
		$this->_contents['entities'] = '';
		$simplexml = @simplexml_load_string($xml);
		@$this->_parseRenditions($simplexml->teiHeader->encodingDesc[1]->tagsDecl);
		if(@$this->XML((string) $simplexml->text->asXML(), 'UTF-8', LIBXML_NOBLANKS | LIBXML_COMPACT | LIBXML_NOCDATA))
		{
			unset($xml);
			while(@$this->read())
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
	 * Converti le contenu pour qu'il soit valide XHTML
	 *
	 * @access private
	 * @param string $text le texte � convertir
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
	 * @param string &$v le texte � nettoyer
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
	 * Termine la conversion des donn�es
	 *
	 * Cette fonction pr�pare les donn�es pour l'import dans Lodel
	 *
	 * @access private
	 */
	private function _parseAfter()
	{
		// strip tags from entries
		array_walk_recursive($this->_contents['entries'], 'strip_tags');
		
		// cleaning
		array_walk_recursive($this->_contents, array($this, '_clean'));
		
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
		foreach($this->_contents['persons'] as $personType=>$persons)
		{
			foreach($persons as $k=>$person)
			{
				if(!empty($person['g_name']))
				{
					$person['g_name'] = strip_tags($person['g_name']);
					if($lastname && $firstname)
					{
						$name = preg_split("/(\s|\xc2\xa0)+/u", $person['g_name'], -1, PREG_SPLIT_NO_EMPTY);
						$this->_contents['persons'][$personType][$k]['data'][$lastname] = array_pop($name);
						$this->_contents['persons'][$personType][$k]['data'][$firstname] = join(' ', $name);
					}
					elseif($lastname)
					{
						$this->_contents['persons'][$personType][$k]['data'][$lastname] = $person['g_name'];
					}
					elseif($firstname)
					{
						$this->_contents['persons'][$personType][$k]['data'][$firstname] = $person['g_name'];
					}
				}
				else
				{
					$this->_log('Warning: we have not found any firstname or lastname for an author : '.print_r($person, 1));
				}
				unset($this->_contents['persons'][$personType][$k]['g_name']);
			}
		}
	}

	/**
	 * Cherche si le style $rend est connu et renvoit la correspondance
	 *
	 * @access private
	 * @param string $rend le style � chercher
	 * @param bool $full si l'on doit renvoyer l'int�gralit� du style
	 * @return mixed tableau du style ou uniquement son nom si $full est � false
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
			return $full ? $this->_internalstyles[$rend] : $this->_internalstyles[$rend]->name;
		}
		elseif(isset($this->_characterstyles[$rend]))
		{
			return $full ? $this->_characterstyles[$rend] : $this->_characterstyles[$rend]->name;
		}
		elseif(isset($this->_entrytypes[$rend]))
		{
			return $full ? $this->_entrytypes[$rend] : $this->_entrytypes[$rend]->name;
		}
		elseif(isset($this->_persontypes[$rend]))
		{
			return $full ? $this->_persontypes[$rend] : $this->_persontypes[$rend]->name;
		}
		elseif(isset($this->_tablefields[$rend]))
		{
			return $full ? $this->_tablefields[$rend] : $this->_tablefields[$rend]->name;
		}
		else
		{
			$this->_log('Unknown style '.$rend);
			return false;
		}
	}

	/**
	 * Enregistre un message de Log
	 *
	 * @access private
	 * @param mixed $msg le(s) message(s) � stocker, peut �tre un array ou string
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
	 * R�cup�re les attributs du noeud courant
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
	 * G�re les noeud de haut niveau
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
	 * R�cup�re les styles locaux (renditions)
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
	 * Retourne le style associ� � la rendition
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
			$this->_log("Can't find the local style ".$name);

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
		while(@$this->read())
		{
			if(parent::ELEMENT === $this->nodeType)
			{
				if('div' === $this->localName) continue; // container, not used
				
				$attrs = $this->_parseAttributes();
				if(isset($attrs['rend']) && ('footnotesymbol' === strtolower($attrs['rend']) || 'endnotesymbol' === strtolower($attrs['rend'])))
				{
					$attrs['rendition'] = $attrs['rend'];
					unset($attrs['rend']);
				}
				
				if(('hi' === $this->localName && !isset($attrs['rend'])) || ('table' === $this->localName && 'frame' === $attrs['rend']) || ('p' !== $this->localName && 'hi' !== $this->localName && 'table' !== $this->localName))
				{
					$this->_currentNode .= $this->_getTagEquiv($this->localName, $attrs);
				}
				else
				{
					if('p' === $this->localName && !isset($attrs['rend']) && isset($attrs['rendition']))
					{
						$attrs['rend'] = $attrs['rendition'];
					}
					
					$style = false;

					if(isset($attrs['rend']) && 'heading' !== substr($attrs['rend'], 0, 7))
						$style = $this->_getStyle($attrs['rend'], true);

					if(!$style && isset($attrs['rend']) && 'heading' !== substr($attrs['rend'], 0, 7))
					{
						$this->_log('Unknown style for tag "'.$this->localName.'"'.(isset($attrs['rend']) ? ':rend['.$attrs['rend'].']' : ''));
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
							if('entities' !== $style->type)
							{
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
							}
							else
							{
								$this->_currentNode =& $this->_contents['entities'];
								$this->_previousNodes[] =& $this->_contents['entities'];
							}
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
				elseif('div' === $this->localName) continue;
				
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

		while(@$this->read())
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
		while(@$this->read())
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
		
		while(@$this->read())
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
		while(@$this->read() && 'figure' !== $this->localName)
		{
			if(parent::ELEMENT === $this->nodeType)
			{
				if('graphic' === $this->localName)
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
		
		while(@$this->read() && 'note' !== $this->localName)
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
	 * Retourne un �quivalent XHTML pour le tag TEI $name
	 *
	 * @access private
	 * @param string $name le nom du tag
	 * @param array $attrs les attributs du noeud
	 */
	private function _getTagEquiv($name, array $attrs)
	{
		if($this->isEmptyElement && 'lb' !== $name) return '';
		
		$tag = '';
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
				$tag = '<'.$tag;
			}
			
			if(isset($attrs['lang']))
				$tag .= ' xml:lang="'.$attrs['lang'].'" lang="'.$attrs['lang'].'"';
			
			if(isset($attrs['rendition']))
				$tag .= ' style="'.$this->_getRendition($attrs['rendition']).'"';
		}
		elseif('p' === $name || ('hi' === $name && isset($attrs['rend'])))
		{
			$tag = '<p';
			$tags = 'p';
			$closing = false;
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
							if('hr' === $tags || 'br' === $tags)
							{ // auto-closing
								$closing = true;
							}
							$tag = '<'.$m[1].(!empty($m[2]) ? $m[2] : '').($closing ? '' : '><p'); // and reconstruct the tag
						}

						$tag .= ' class="'.$s.'"';
					}
					else
					{
						$tag .= ' class="'.$style->name.'"';
					}
				}
				else $tag .= ' class="'.$attrs['rend'].'"';
			}
			else
			{
				$tag .= ' class="'.end($this->_currentClass).'"';
			}

			if(isset($attrs['lang']))
				$tag .= ' xml:lang="'.$attrs['lang'].'" lang="'.$attrs['lang'].'"';
			
			
			if(isset($attrs['rendition']))
			{
				$tag .= ' style="'.$this->_getRendition($attrs['rendition']).'"';
			}
			
			if($closing) $tag .= '/';
			else $this->_tags[] = $tags;
		}
		elseif('hi' === $name && isset($attrs['rendition']) || isset($attrs['lang']))
		{
			$this->_tags[] = 'span';
			$tag .= '<span';
			if(isset($attrs['rendition']))
				$tag .= ' style="'.$this->_getRendition($attrs['rendition']).'"';
			if(isset($attrs['lang']))
				$tag .= ' xml:lang="'.$attrs['lang'].'" lang="'.$attrs['lang'].'"';
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
		elseif('ref' === $name && isset($attrs['target']))
		{
			$this->_tags[] = 'a';
			$tag .= '<a href="'.$attrs['target'].'"';
		}
		else
		{
			$this->_log('The tag '.$name.' is not recognized');
		}

		return !empty($tag) ? $tag.'>' : '';
	}
}