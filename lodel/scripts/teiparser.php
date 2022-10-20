<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier de la classe TEIParser
 */

/**
 * Classe convertissant la TEI renvoyée par OTX en tableau de variables prêt à être inséré dans Lodel
 */
class TEIParser extends XMLReader
{
	private $isfoot = false;
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
	 * @var array liste des styles par champs pour le type courant
	 * @access private
	 */
	private $_styles = array();

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
	 * @var int nombre incrémental des notes
	 * @access private
	 */
	private $_nbNotes = 0;

	/**
	 * @var array pile des classes utilisées
	 * @access private
	 */
	private $_currentClass;

	/**
	 * @var array liste des images
	 * @access private
	 */
	private $_images = array();

	/**
	 * @var array liste des tableaux
	 * @access private
	 */
	private $_tables = array();

	/**
	 * @var string titre du document si trouvé (tablefields.g_name == 'dc.title')
	 * @access private
	 */
	private $_docTitle = '';

	/**
	 * @var array namespaces du document
	 * @access private
	 */
	 private $_namespaces;

	 /**
	  * @var string répertoire temporaire d'extraction du document
	  * @access private
	  */
	 private $_tmpdir;

	 /**
	  * @var array tableau de styles correspondant aux notes de fin et bas de page
	  * @access private
	  */
	 private $_notesstyles = array(
                                   'footnote',
	                               'footnotesymbol',
	                               'footnotereference',
	                               'endnotesymbol',
	                               'endnotereference',
	                               );

	/**
	 * Constructeur
	 *
	 * Récupère les champs ainsi que les types d'entrées et de personnes, les styles internes et de caractères
	 * pour le type courant
	 *
	 * @param int $idtype id du type courant
	 * @access public
	 */
	public function __construct($idtype)
	{
		function_exists('convertHTMLtoUTF8') || include 'utf8.php';
		defined('INC_FUNC') || include 'func.php';

		$idtype = (int) $idtype;
		if(!$idtype) throw new Exception('ERROR: Invalid idtype');

		$vo = DAO::getDAO('types')->find('import=1 AND id='.$idtype, 'class');
		if(!$vo) throw new Exception('ERROR: Invalid idtype');

		$class = $vo->class;
		unset($vo);

		$personsClassTypes = $entriesClassTypes = array();

		$fields = DAO::getDAO('tablefields')->findMany('status>0 AND class='.$GLOBALS['db']->quote($class), 'id', 'name,title,style,type,otx,class,defaultvalue,g_name, idgroup, rank');
		//Sorting fields using fieldgroups
		$fieldgroups = DAO::getDAO('tablefieldgroups')->findMany('1=1','rank', 'id,rank');
		$fgs = array();
		foreach($fieldgroups as $fieldgroup) {
			$fgs[intval($fieldgroup->id)] = intval($fieldgroup->rank);
		}
		$fieldgroups = $fgs;
		usort($fields,
			function($a, $b) use($fieldgroups){
				$fgra = $fieldgroups[$a->idgroup];
				$fgrb = $fieldgroups[$b->idgroup];
				return $fgra == $fgrb ? $a->rank - $b->rank : $fgra - $fgrb;
			}
		);

		if($fields)
		{
			foreach($fields as $field)
			{
				if($field->type === 'persons')
				{
					$classType = DAO::getDAO('persontypes')->find('type='.$GLOBALS['db']->quote($field->name), 'class');
					if($classType && !in_array($classType->class, $personsClassTypes))
					{
						$personsClassTypes[] = $classType->class;
						$personsFields = DAO::getDAO('tablefields')->findMany('status>0 AND (class='.$GLOBALS['db']->quote($classType->class).' OR class='.$GLOBALS['db']->quote('entities_'.$classType->class).')', 'id', 'name,title,style,type,otx,class,g_name');
						if($personsFields)
						{
							foreach($personsFields as $personField)
							{
								if(!empty($personField->g_name)) $this->_styles[$personField->g_name] = $personField;

								$this->_persontypes[str_replace('entities_', '', $personField->class)]['fields'][] = $personField;
							}
						}
					}
				}
				elseif($field->type === 'entries')
				{
					$classType = DAO::getDAO('entrytypes')->find('type='.$GLOBALS['db']->quote($field->name), 'class');
					if($classType && !in_array($classType->class, $entriesClassTypes))
						$entriesClassTypes[] = $classType->class;
				}
				else
				{
					// extract the name of the converted style
					if(preg_match("/\[(@(type|rend)='([^']+)')]$/", $field->otx, $m))
                                        {
                                            $this->_styles[$m[3]] = $field;
                                            // get associated blocks
                                            // $field->otx = array($field->otx, "//tei:*[starts-with(@type, '".$m[3]."-')]", "//tei:*[starts-with(@rend, '".$m[3]."-')]");
                                        }

					$this->_styles[$field->name] = $field;

					if(!empty($field->g_name)) $this->_styles[$field->g_name] = $field;

					$styles = array_filter(array_map('trim', explode(',', $field->style)));
					foreach($styles as $style)
						$this->_styles[$style] = $field;
				}

				$this->_tablefields[$field->name] = $field;
			}
		}

		$fields = DAO::getDAO('internalstyles')->findMany('status>0', 'id', 'style,surrounding,conversion,otx');
		if($fields)
		{
			foreach($fields as $field)
			{
				if(0 === strpos($field->otx, '/tei:TEI/'))
				{ // burk, in a perfect world, it should NOT be a block but an inline style
				// we set it as standard block
					$style = explode(',', $field->style); // take the first style
					$this->_tablefields[$style[0]] = $field;
				}
				elseif(preg_match("/\[@rend='([^']+)'\]$/", $field->otx, $m))
					$this->_internalstyles[$m[1]] = $field;
				elseif(!empty($field->otx))
					$this->_internalstyles[$field->otx] = $field;

				$styles = array_filter(array_map('trim', explode(',', $field->style)));

				foreach($styles as $style)
					$this->_internalstyles[$style] = $field;
			}
		}

		$fields = DAO::getDAO('characterstyles')->findMany('status>0', 'id', 'style,conversion,otx');
		if($fields)
		{
			foreach($fields as $field)
			{
				if(0 === strpos($field->otx, '/tei:TEI/'))
				{ // burk, in a perfect world, it should NOT be a block but an inline style
				// we set it as standard block
					$style = explode(',', $field->style); // take the first style
					$this->_tablefields[$style[0]] = $field;
				}
				elseif(preg_match("/\[@rend='([^']+)'\]$/", $field->otx, $m))
					$this->_characterstyles[$m[1]] = $field;
				elseif(!empty($field->otx))
					$this->_characterstyles[$field->otx] = $field;

				$styles = array_filter(array_map('trim', explode(',', $field->style)));

				foreach($styles as $style)
					$this->_characterstyles[$style] = $field;
			}
		}

		$fields = DAO::getDAO('persontypes')->findMany('status>0 AND class IN ('.join(',', array_map(array($GLOBALS['db'], 'quote'), $personsClassTypes)).')', 'id', 'id,type,title,style,otx,class');
		if($fields)
		{
			foreach($fields as $field)
				$this->_persontypes[$field->class][$field->type] = $field;
		}

		$fields = null;
		if (!empty($entriesClassTypes)) {
			$fields = DAO::getDAO('entrytypes')->findMany('status>0 AND class IN ('.join(',', array_map(array($GLOBALS['db'], 'quote'), $entriesClassTypes)).')', 'id', 'id,type,title,style,otx,lang,newbyimportallowed');
			if($fields)
			{
				foreach($fields as $field)
				{
					$this->_entrytypes[$field->type] = $field;
					$this->_entrytypes[$field->id] = $field;
							}
			}
		}
	}

	/**
	 * Fonction principale, parse la TEI
	 * Extrait les images
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
		if(!$xml) throw new Exception('ERROR: the TEI is not valid');

		$odt = (string) $odt;
		$this->_docTitle = (string) $filename;
		$this->_tmpdir   = (string) $tmpdir;
		if(!empty($odt))
		{
			$this->_extractImages($odt, $tmpdir);
		}

		libxml_use_internal_errors(true);

		$this->_logs = $this->_contents = $this->_tags = $this->_currentClass = array();
		$this->_contents['entries'] = $this->_contents['persons'] = $this->_contents['error'] = array();

		$simplexml = simplexml_load_string($xml);
		if(!$simplexml)
		{
			$this->_log(libxml_get_errors());
			throw new Exception("ERROR: Can't open XML".var_export(array(libxml_get_errors(),$xml),true));
		}

		/* Récupération des namespaces du document */
		$this->_namespaces = $simplexml->getDocNamespaces(true);

		if($simplexml->teiHeader->encodingDesc->tagsDecl)
			$this->_parseRenditions($simplexml->teiHeader->encodingDesc->tagsDecl);

		$this->_parseBlocks($simplexml);
		unset($simplexml);
		$this->_parseAfter();

		if(count($this->_tags)) throw new Exception('ERROR: The number of opening/closing tag does not match : '.var_export($this->_tags, true));

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
		return (array) array_unique($this->_logs);
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
	 * Extraction des images contenus dans le document source
	 *
	 * @access private
	 * @param string $odt nom du fichier source
	 * @param string $tmpdir répertoire temporaire d'extraction
	 */
	private function _extractImages($odt, $tmpdir)
	{
        $images = extract_files_from_zip($odt, $tmpdir, "/^Pictures\/.+/" );

        $destination = array_filter(explode('/', $tmpdir));
        $destination = end($destination);
        $destination = SITEROOT.'docannexe/image/'.$destination;
        if (!is_dir($destination))
            mkdir($destination);
        foreach($images as $image)
        {
            $dest_file = $destination . DIRECTORY_SEPARATOR . basename($image);
            rename($tmpdir . DIRECTORY_SEPARATOR . $image, $dest_file);
            $this->_images[basename($image)] = $dest_file;

        }
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
		if(!trim($v)) return;

		C::clean($v);

		$v = preg_replace(array(
			'/<(?!td)([a-z]+)\b[^>]*><\/\\1>/i', // strip empty tags
			'/<p[^>]*>\s*(<(table|ul)[^>]*>)/i', // remove paragraph before tables and lists
			'/<\/(table|ul)>\s*<\/p>/i',
			'/<span>(.*?)<\/span>/s'), // replace empty spans
				array('', "\\1", "</\\1>", "\\1"), $v);
	}

	/**
	 * Pré-valide le contenu en fonction du type du champ
	 * afin d'indiquer à l'utilisateur les erreurs éventuelles
	 * avant passage à la page d'édition de l'entité
	 *
	 * @access private
	 * @param mixed &$field le champ à valider, string ou array si type == mltext
	 * @param string $name le nom du champ
	 */
	private function _validField(&$field, $name)
	{
		if('error' === $name) return;

		if(!isset($this->_tablefields[$name]))
		{
			$this->_log(sprintf(getlodeltextcontents('TEIPARSER_CANNOT_VALIDATE_UNKNOWN_FIELD', 'edition'), $name));
			return;
		}

		function_exists('validfield') || include 'validfunc.php';

		$def = $this->_tablefields[$name];
		if($def->type === 'mltext'  || $def->type === 'mllongtext')
		{
			foreach($field as $k => $f)
			{
				$valid = validfield($f, 'text', $def->defaultvalue, $def->name);
				if(false === $valid)
					$this->_log(sprintf(getlodeltextcontents('TEIPARSER_INVALID_FIELD', 'edition'), $name));
				elseif(is_string($valid))
					$this->_contents['error'][$name][$k] = $valid;
			}

			if(mb_strlen(join('', $field), 'UTF-8') > 65535)
				$this->_log(sprintf(getlodeltextcontents('TEIPARSER_FIELD_WILL_BE_TRUNCATED', 'edition'), $name));
		}
		else
		{
			$valid = validfield($field, $def->type, $def->defaultvalue, $def->name);
			if(false === $valid)
				$this->_log(sprintf(getlodeltextcontents('TEIPARSER_INVALID_FIELD', 'edition'), $name));
			elseif(is_string($valid))
				$this->_contents['error'][$name] = $valid;

			$isError = false;

			switch($def->type)
			{
				case 'text': // 65535
					$isError = mb_strlen($field, 'UTF-8') > 65535;
					break;

				case 'tinytext': // 255
					$isError = mb_strlen($field, 'UTF-8') > 255;
					break;

				case 'longtext': // 4294967295
					$isError = mb_strlen($field, 'UTF-8') > 4294967295;
					break;

				case 'color': // 10
					$isError = mb_strlen($field, 'UTF-8') > 10;
					break;

				case 'int': // 0-4294967295
					$isError = $field > 4294967295;
					break;

				case 'tinyint': // 0-255
					$isError = $field > 255;
					break;

				default: break;
			}

			if($isError) $this->_log(sprintf(getlodeltextcontents('TEIPARSER_FIELD_WILL_BE_TRUNCATED', 'edition'), $name));

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

		// re-order blocks from their ids
		// used for inline blocks that are converted to real blocks in the TEI
		
		foreach($this->_tablefields as $name => $obj)
		{
			if($obj instanceof internalstylesVO || $obj instanceof characterstylesVO || 'entries' === $obj->type || 'persons' === $obj->type) continue;

			if(empty($this->_contents[$name])) continue;

			if('mltext' === $obj->type || 'mllongtext' === $obj->type)
			{
				foreach($this->_contents[$name] as $lang => $v)
				{
					ksort($this->_contents[$name][$lang]);
					$this->_contents[$name][$lang] = join('', $this->_contents[$name][$lang]);
				}
			}
			else
			{
				ksort($this->_contents[$name]);
				$this->_contents[$name] = join('', $this->_contents[$name]);
			}
		}

		// cleaning XHTML
		array_walk_recursive($this->_contents, array($this, '_clean'));

		// validating fields
		array_walk($this->_contents, array($this, '_validField'));
            // strip tags from entries

        foreach($entries as $k => $v) {
            $g_name = false;
            foreach($v as $ind_v => $val_v) {
                if (isset($val_v['g_name'])){
                    $g_name = true;
                    break;
                }
            }
            if (!$g_name) {
                $entries[$k] = strip_tags(join(',', $v));
            }
        }

		$this->_contents['entries'] = $entries;

		foreach($this->_contents['entries'] as $type => $arrType)
		{
                    if(!$this->_entrytypes[$type]->newbyimportallowed)
                        $this->_contents['error'][$this->_entrytypes[$type]->type] = 'importnotallowed';
		}
		unset($entries);

		// get the firstname/lastname field name
		$firstname = $this->_getStyle('firstname');
		$lastname = $this->_getStyle('familyname');

		if(!$firstname && !$lastname)
		{
			$this->_log(sprintf(getlodeltextcontents('TEIPARSER_NO_FIRSTNAME_LASTNAME', 'edition')));
			return;
		}

		// manages persons
		// Créer une entrée de tableau par auteur lorsque ceux-ci sont enregistrés dans le même champ et séparés par des virgules
		// laisse les entrées vides qui seront dénoncées dans la fonction suivante
		$personExplode = array();
		foreach ($persons as $personType => $ps){
			foreach ($ps as $k => $person) {
				if (!empty($person['g_name']))	{
					foreach(preg_split("/\s*,\s*/u", $person['g_name'], -1, PREG_SPLIT_NO_EMPTY) as $name) {
						$person['g_name'] = $name;
						$personExplode[$personType][] = $person;
					}
				} else {
					$personExplode[$personType][] = $person;
				}
			}
		}
		$persons = $personExplode;
		// we construct the var for lodel and strip tags from firstname/lastname
		foreach($persons as $personType => $ps)
		{
			foreach($ps as $k => $person)
			{
				if(!empty($person['g_name']))
				{
					if(!empty($person['data']))
						array_walk_recursive($person['data'], array($this, '_clean'));

					foreach($person['data'] as $key => $val)
						if(!trim($val)) unset($persons[$personType][$k]['data'][$key]);

					$person['g_name'] = strip_tags($person['g_name']);
					if($lastname && $firstname)
					{
                                                $name = array_filter(explode(" ", $person['g_name']));
                                                if(count($name) === 1)
                                                {
                                                    $name = preg_split("/(\xc2\xa0)+/u", $person['g_name'], -1, PREG_SPLIT_NO_EMPTY);
                                                }
						$persons[$personType][$k]['data'][$lastname['class']] = array_pop($name);
						$persons[$personType][$k]['data'][$firstname['class']] = join(' ', $name);
					}
					elseif($lastname)
						$persons[$personType][$k]['data'][$lastname['class']] = $person['g_name'];
					elseif($firstname)
						$persons[$personType][$k]['data'][$firstname['class']] = $person['g_name'];
				}
				else
				{
					$this->_log(sprintf(getlodeltextcontents('TEIPARSER_FIRSTNAME_LASTNAME_NOT_FOUND', 'edition'), print_r($person, 1)).print_r($ps, 1));
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
		if(false !== strpos($rend, ' '))
		{
			$rend = explode(' ', $rend);
			$styles = array();
			foreach($rend as $r)
				$styles = array_merge_recursive($styles, (array) $this->_getStyle($r, $full));

			return $styles;
		}

		if(in_array($rend, array('italic', 'sup', 'sub', 'uppercase', 'lowercase', 'bold', 'underline', 'strike', 'small-caps', 'direction(rtl)', 'direction(ltr)')))
			return array('inline' => array($rend));

		if(isset($this->_internalstyles[$rend]))
			return array('class' => $full ? $this->_internalstyles[$rend] : $this->_internalstyles[$rend]->style);
		elseif(isset($this->_characterstyles[$rend]))
			return array('class' => $full ? $this->_characterstyles[$rend] : $this->_characterstyles[$rend]->style);
		elseif(isset($this->_entrytypes[$rend]))
			return array('class' => $full ? $this->_entrytypes[$rend] : $this->_entrytypes[$rend]->type);
		elseif(isset($this->_persontypes[$rend]))
			return array('class' => $full ? $this->_persontypes[$rend] : $this->_persontypes[$rend]->type);
		elseif(isset($this->_styles[$rend]))
			return array('class' => $full ? $this->_styles[$rend] : $this->_styles[$rend]->name);

		if(false !== strpos($rend, '-'))
		{
			$style = array_reverse(explode('-', $rend, 2));
            foreach($style as $k => $v)
            {
                if(isset($this->_internalstyles[$v]))
                        return array('class' => $full ? $this->_internalstyles[$v] : $this->_internalstyles[$v]->style);
                elseif(isset($this->_characterstyles[$v]))
                        return array('class' => $full ? $this->_characterstyles[$v] : $this->_characterstyles[$v]->style);
                elseif(isset($this->_entrytypes[$v]))
                        return array('class' => $full ? $this->_entrytypes[$v] : $this->_entrytypes[$v]->type);
                elseif(isset($this->_persontypes[$v]))
                        return array('class' => $full ? $this->_persontypes[$v] : $this->_persontypes[$v]->type);
                elseif(isset($this->_styles[$v]))
                        return array('class' => $full ? $this->_styles[$v] : $this->_styles[$v]->name);
            }
		}

		$this->_log(sprintf(getlodeltextcontents('TEIPARSER_UNKNOWN_STYLE', 'edition'), $rend, $rend));
		return false;
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
				$this->_logs[] = (string) (is_object($m) ? $m->message : $m);
		}
 		elseif (!empty($msg)) {
			$this->_logs[] = (string)$msg;
		}
	}

	/**
	 * Récupère les attributs du noeud courant
	 *
	 * @access private
	 * @return array tableau associatif nom => valeur
	 */
	private function _parseAttributes(XMLReader $element = null)
	{
		if(!isset($element))
			$element =& $this;

		if(!$element->hasAttributes) return array();

		$attrs = array();

		$element->moveToFirstAttribute();
		do
		{
			$attrs[$element->localName] = 'rendition' === $element->localName ? substr($element->value, 1) : $element->value;
		}
		while($element->moveToNextAttribute());

		$element->moveToElement();
		return $attrs;
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
			$this->_renditions[(string) $r->attributes('http://www.w3.org/XML/1998/namespace')->id] = (string) $r;
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

		if(!in_array($name, $this->_notesstyles))
			$this->_log(sprintf(getlodeltextcontents("TEIPARSER_UNKNOWN_LOCAL_STYLE", 'edition'), $name));

		return '';
	}

	/**
	 * Fermeture d'une balise
	 *
	 * @access private
	 * @return string les balises fermées
	 */
	private function _closeTag()
	{
		if(empty($this->_tags)) return;
		$tags = array_pop($this->_tags);

		$text = '';

		if(!empty($tags))
		{
			if(is_array($tags))
			{
				foreach($tags as $tag)
				{
					if('hr' !== $tag && 'br' !== $tag)
						$text .= '</'.$tag.'>';
				}
			}
			elseif('hr' !== $tags && 'br' !== $tags) $text .= '</'.$tags.'>';
		}

		return $text;
	}

	/**
	 * Parcours de tous les champs définis dans le ME et extraction depuis la TEI
	 *
	 * @access private
	 * @param mixed $simplexml l'instance de simplexml sur laquelle on va travailler
	 */
	private function _parseBlocks(SimpleXMLElement $simplexml)
	{
		$namespaces = $simplexml->getDocNamespaces();
		$simplexml->registerXPathNamespace('tei', $namespaces['']);

		foreach($this->_tablefields as $name => $obj)
		{
			if($obj instanceof tablefieldsVO && ('entries' === $obj->type || 'persons' === $obj->type))
			{
				if('entries' === $obj->type)
				{
					if(!isset($this->_entrytypes[$obj->name]))
					{
						$this->_log(sprintf(getlodeltextcontents('TEIPARSER_UNDEFINED_ENTRYTYPE'), $obj->name));
						continue;
					}

					if(empty($this->_entrytypes[$obj->name]->otx)) continue;

					$xpath = array($this->_entrytypes[$obj->name]->otx."[not(@xml:lang)]",  $this->_entrytypes[$obj->name]->otx."[@xml:lang='".$this->_entrytypes[$obj->name]->lang."']");
				}
				elseif('persons' === $obj->type)
				{
					$style = $class = null;
					foreach($this->_persontypes as $c => $array)
					{
						if(isset($array[$obj->name]))
						{
							$style = $array[$obj->name];
							$class = $c;
							break;
						}
					}

					if(!isset($style))
					{
						$this->_log(sprintf(getlodeltextcontents('TEIPARSER_UNDEFINED_PERSONTYPE'), $obj->name));
						continue;
					}

					if(empty($style->otx)) continue;

					$xpath = $style->otx;
				}
			}
			else
			{
				if(empty($obj->otx)) continue;

				$xpath = $obj->otx;
			}

            $block = array();

            if(is_array($xpath))
            {
                foreach($xpath as $x)
                {
                    $b = $simplexml->xpath($x);
                    if(!empty($b))
                    {
                        foreach($b as $v)
                            array_push($block, $v);
                    }
                }
            }
            else
                $block = $simplexml->xpath($xpath);
// 			if(false === $block)
// 			{
// 				$this->_log('Invalid xpath 1 : '.$xpath);
// 				continue;
// 			}

			if(empty($block)) continue;
			

			if($obj instanceof tablefieldsVO && ('entries' === $obj->type || 'persons' === $obj->type))
			{
				if($obj->type === 'entries')
				{
					$idtype = $this->_entrytypes[$obj->name]->id;
					$this->_contents['entries'][$idtype] = array();

					$block = array_shift($block);

                    if(isset($block->list[0]))
                                        {
					    foreach($block->list[0]->item as $k => $v)
					    $this->_contents['entries'][$idtype][] = (string) $v;
					} elseif (isset($block->term[0])) {           
                        foreach($block->term as $term) { 
                             $ark = (string) $term->attributes()['ref'];
                             $mlnoms = array(); 
                             foreach ($term->term as $ssterm => $v) {
                                $lang=$v->attributes("http://www.w3.org/XML/1998/namespace")['lang']->__toString();
                                $mlnoms[$lang] = $v->__toString();
                            }
                             $data = array ('g_name' => $ark, 'data' => array('ark' => $ark, 'mlnom' => $mlnoms));
                             $this->_contents['entries'][$idtype][] = $data;
                        }
					}
					else
					{
						$this->_contents['entries'][$idtype][] = $this->_parse($block->asXML());
					}
				}
				elseif($obj->type === 'persons')
				{
					$this->_contents['persons'][$style->id] = array();

					foreach($block as $k => $v)
					{
						// quite boring to have to re-do this here
						// but if we don't do this, the xpath will not be valid :/
						$this->_updateNameSpaces($v);
						$namespaces = $v->getDocNamespaces();
						$v->registerXPathNamespace('tei', $namespaces['']);

                                                $this->_contents['persons'][$style->id][$k] = array('data' => array());
                                                if(!isset($v->persName))
                                                    $this->_contents['persons'][$style->id][$k]['g_name'] = (string) (isset($v->name) ? $v->name : $v);
                                                elseif(isset($v->persName[0]->forename) || isset($v->persName[0]->surname)) {
                                                    $this->_contents['persons'][$style->id][$k]['g_name'] = '';

                                                    if(isset($v->persName[0]->forename))
                                                        $this->_contents['persons'][$style->id][$k]['g_name'] .= preg_replace("/\s+/u", "\xc2\xa0", (string) $v->persName[0]->forename)." ";
                                                    if(isset($v->persName[0]->surname))
                                                        $this->_contents['persons'][$style->id][$k]['g_name'] .= preg_replace("/\s+/u", "\xc2\xa0", (string) $v->persName[0]->surname)." ";
                                                } else {
                                                    $this->_contents['persons'][$style->id][$k]['g_name'] = (string) $v->persName[0];
                                                }


						foreach($this->_persontypes[$class]['fields'] as $key => $field)
						{
							if(empty($field->otx)) continue;

							$fieldContent = $v->xpath('.'.$field->otx); // concatenate '.' to specify we want in the current element, for relative xpath
// 							if(false === $fieldContent)
// 							{
// 								$this->_log('Invalid xpath 2 : '.$field->otx);
// 								continue;
// 							}

							if(empty($fieldContent)) continue;
							$currentNode =& $this->_contents['persons'][$style->id][$k]['data'][$field->name];

							foreach($fieldContent as $fC)
							{
								if(!isset($this->_contents['persons'][$style->id][$k]['data'][$field->name]))
									$this->_contents['persons'][$style->id][$k]['data'][$field->name] = "";

								$this->_currentClass[] = $field->name;

// 								$this->_contents['persons'][$style->id][$k]['data'][$field->name] = $this->_parse($fC->asXML());
								$reader = new XMLReader();
								$reader->XML($fC->asXML(), 'UTF-8', LIBXML_COMPACT | LIBXML_NOCDATA);

								while($reader->read())
								{
									if(parent::ELEMENT === $reader->nodeType
										&& ( $reader->localName !== "hi" || $reader->getAttribute('rendition') ) )
									{
										$attrs = $this->_parseAttributes($reader);
										$currentNode .= $this->_getTagEquiv($reader->localName, $attrs);
										if($reader->isEmptyElement && 'lb' !== $reader->localName) $currentNode .= $this->_closeTag();
									}elseif(parent::TEXT === $reader->nodeType || parent::WHITESPACE === $reader->nodeType || parent::SIGNIFICANT_WHITESPACE === $reader->nodeType){
										$currentNode .= $reader->readOuterXML();
									}elseif(parent::END_ELEMENT === $reader->nodeType
											&& ( $reader->localName !== "hi" || $reader->getAttribute('rendition') ) ){
										$currentNode .= $this->_closeTag();
									}
								}
								//$currentNode .= $this->_parse($fC->asXML());
							}
						}
					}

				}
			}
			else
			{
				if($obj instanceof internalstylesVO || $obj instanceof characterstylesVO)
				{
					$style = $this->_getStyle('standard');
					$obj = $this->_tablefields[$style['class']];
				}

				if(!isset($this->_contents[$obj->name]))
					$this->_contents[$obj->name] = array();

				foreach($block as $k => $v)
				{
					$this->_updateNameSpaces($v);
					$xmlAttrs = $v->attributes('http://www.w3.org/XML/1998/namespace');
					if(isset($xmlAttrs['id']) && 0 === strpos((string) $xmlAttrs['id'], 'otx_'))
						$id = substr((string) $xmlAttrs['id'], '4'); // remove 'otx_'
					elseif(!isset($id)) $id = $k;

					$this->_currentClass[] = $name;

					if('mltext' === $obj->type || 'mllongtext' === $obj->type)
					{
						$lang = (string) $xmlAttrs['lang'];
						if(!isset($this->_contents[$obj->name][$lang]))
							$this->_contents[$obj->name][$lang] = array();

						$currentNode =& $this->_contents[$obj->name][$lang][$id];
					}
					else $currentNode =& $this->_contents[$obj->name][$id];
					
					$currentNode .= $this->_parse($v->asXML());
				}
			}
			$this->_currentClass = array();
		}
	}

	/**
	 * Parse et converti le contenu en XHTML
	 *
	 * @access private
	 * @param string $xml la chaîne XML à convertir
	 * @return string le XHTML équivalent à la chaine XML
	 */
	
	private function _parse($xml)
	{
        $this->XML($xml, 'UTF-8', LIBXML_COMPACT | LIBXML_NOCDATA);

		$text = '';

		while($this->read())
		{
			if(parent::ELEMENT === $this->nodeType)
			{
				if('div' === $this->localName) continue; // container, not used
				
				$attrs = $this->_parseAttributes();
				
				if(isset($attrs['rend']) && in_array(strtolower($attrs['rend']), $this->_notesstyles) ) continue;
				$text .= $this->_getTagEquiv($this->localName, $attrs);
				
				if ('formula' === $this->localName)
				{
					if (isset($attrs['notation'])) {
						$math = $this->readInnerXml();
						$text .= $math; //.$this->_closeTag(); //Tag fermé ici et aussi ligne 1062
						$this->read();
					}
				}
			}
			elseif(parent::END_ELEMENT === $this->nodeType)
			{
				if('body' === $this->localName || 'front' === $this->localName || 'back' === $this->localName) break;
				//elseif(('div' === $this->localName) || ($this->localName === 'formula')) continue;
				elseif('div' === $this->localName) continue;

				$rend = $this->getAttribute('rend');

                if(isset($rend) && in_array(strtolower($rend), $this->_notesstyles) ) continue;
				
                $text .= $this->_closeTag();

				if(!empty($rend))
				{
					$rend = $this->_getStyle($rend);
					if(!empty($rend['class']))
						array_pop($this->_currentClass);
				}

				if(empty($this->_currentClass))
				{
					$style = $this->_getStyle('standard');
					$this->_currentClass[] = $style['class'];
				}
			}
			elseif(parent::TEXT === $this->nodeType || parent::WHITESPACE === $this->nodeType || parent::SIGNIFICANT_WHITESPACE === $this->nodeType)
			{
				$text .= $this->_getText($this->value);
			}
		}

		$this->close();
		
		return $text;
	}

	/**
	 * Ajoute un style local ou une langue
	 *
	 * @access private
	 * @param array $attrs les attributs du noeuds
	 * @param boolean $inline si la méthode est appellée sur un <hi>
	 * @return string les balises suivants les attributs fournis
	 */
	private function _addLocalStyle(array $attrs, $inline = false)
	{
		$ret = '';
		
		if(empty($attrs)) return;
		
		if(!empty($attrs['lang']))
			$lang = $attrs['lang'];
		if(!empty($attrs['rendition']))
			$rendition = $this->_getRendition($attrs['rendition']);
		
		if(empty($attrs['notation']) && empty($lang) && empty($rendition) && empty($attrs['rend'])) return;
		print_r($this->_tags);
		print('<br/>');
		
		$tags = $inline ? array() : array_pop($this->_tags);
		if(!is_array($tags)) $tags = array($tags);

		if (!empty($attrs['notation'])) {
			
			if ($tags[count($tags)-1] === 'span') {
				$this->_tags[] = 'span';
			}
			
			$tags = array();
			
			if( $this->localName !== "p" && $this->localName !== "q"){ // le <p> indique un paragraphe, le <q> indique une citation
				$ret   = '<span class="' . $attrs['notation']. '">';
				$tags[] = 'span';
			}
			
		}

		//if(empty($attrs['notation']) && empty($lang) && empty($rendition) && empty($attrs['rend'])) return;

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
					unset($rendition[$i]);
				}elseif(0 === strpos($style, 'text-align')){
					$rendition[] = "display:block";
				}
			}
		}
		if((!empty($attrs['rend'])))
		{
			$styles = $this->_getStyle($attrs['rend']);
			if(!empty($styles['inline']))
			{
				foreach($styles['inline'] as $style)
				{
					if('italic' === $style)
					{
						$ret .= '<em>';
						$tags[] = 'em';
					}
					elseif('bold' === $style)
					{
						$ret .= '<strong>';
						$tags[] = 'strong';
					}
					elseif('small-caps' === $style)
						$rendition[] = 'font-variant:small-caps';
					elseif('underline' === $style)
						$rendition[] = 'text-decoration:underline';
					elseif('strike' === $style)
					{
						$ret .= '<del>';
						$tags[] = 'del';
					}
					elseif('uppercase' === $style)
						$rendition[] = 'text-transform:uppercase';
					elseif('lowercase' === $style)
						$rendition[] = 'text-transform:lowercase';
					elseif('sup' === $style)
					{
						$ret .= '<sup>';
						$tags[] = 'sup';
					}
					elseif('sub' === $style)
					{
						$ret .= '<sub>';
						$tags[] = 'sub';
					}
					elseif('direction(rtl)' === $style)
						$attrsAdd[] = 'dir="rtl"';
					elseif('direction(ltr)' === $style)
						$attrsAdd[] = 'dir="ltr"';
				}
			}elseif( $this->localName !== "p" && $this->localName !== "q"){ // le <p> indique un paragraphe, le <q> indique une citation
				$ret   .= '<span class="' . $styles['class'] . '">';
				$tags[] = 'span';
			}
			unset($attrs['rend']);
		}

		if(!empty($rendition) || !empty($lang) || !empty($attrs['rend']) || !empty($attrsAdd))
		{
			$tags[] = 'span';
			$ret .= '<span'.(!empty($attrs['rend']) ? ' class="'.$attrs['rend'].'"' : '').
					(!empty($lang) ? ' xml:lang="'.$lang.'" lang="'.$lang.'"' : '') .
					(!empty($rendition) ? ' style="'.join(';', $rendition).'"' : '').
					(!empty($attrsAdd) ? ' '.join(' ', $attrsAdd) : '').
				'>';
		}

		if(!empty($tags[0])) {
			$this->_tags[] = array_reverse($tags);
		}
		/*if ($this->localName ==='formula') {
			print('<br/>');
			print_r($this->_tags);
			die();
		}*/
		
		return $ret;
	}

	/**
	 * Retourne un équivalent XHTML pour le tag TEI $name
	 *
	 * @access private
	 * @param string $name le nom du tag
	 * @param array $attrs les attributs du noeud
	 * @return string la balise équivalement à $name
	 */
	private function _getTagEquiv($name, array $attrs)
	{
		// empty element, don't need it unless it is a line break, which will be converted to <br/>
		if($this->isEmptyElement && 'lb' !== $name) return '';

		// head title in bibliography
		if(('bibl' === $name || 'ab' === $name) && isset($attrs['type']) && $attrs['type'] === 'head')
			$name = 'head';

		switch($name)
		{
			case 'note': // note
				if(!isset($attrs['place']))
					break;

			case 'quote':
			case 'head': // title
			case 'seg':
			case 'list': // list
			case 'table': // table
			case 'figure': // image
			case 'listBibl': // bibliography
			case 'code':
				return $this->{'_parse'.$name}($attrs);
				break;

			case 'hi': // local style
			case 's':
				return $this->_addLocalStyle($attrs, true);
				break;
			case 'formula':
				return $this->_addLocalStyle($attrs, false);
				break;
// 			case 'pb': // page break, we don't need it
// 				return '';
// 				break;

			case 'lb': // line break
				return '<br/>';
				break;

			case 'ref':
			case 'ptr':
				if(!isset($attrs['target'])) break;

				$this->_tags[] = 'a';
				return '<a href="'.$attrs['target'].'">';
				break;

			default: break;
		}


		$tag = '';
		$closing = $inline = false;
		if(isset($attrs['rend']))
		{
			$style = $this->_getStyle($attrs['rend'], true);
			if(!empty($style['class']))
			{
				$tag = '<p';
				$tags = 'p';
				$style = $style['class'];

				if(($style instanceof entrytypesVO) || ($style instanceof persontypesVO))
					return;

				if((($style instanceof internalstylesVO) || ($style instanceof characterstylesVO)))
				{
					$s = $style->style;
					if(false !== strpos($s, ','))
					{
						$s = explode(',', $s);
						$s = $s[0];
					}

					if(!empty($style->conversion) && preg_match('/<([a-z0-9]+)(\s+[^>\/]+)?(\/?)>/', $style->conversion, $m))
					{
						$tags = array($tags, $m[1]); // replace the 'p'
						if('hr' === $m[1] || 'br' === $m[1] || !empty($m[3]))
						{ // auto-closing
							$closing = true;
							array_shift($tags);
						}
						$tag = '<'.$m[1].(!empty($m[2]) ? $m[2] : '').($closing ? '' : '><p').' class="'.$s.'"'.(!$closing ? '>' : ''); // and reconstruct the tag
					}
					elseif($style instanceof characterstylesVO)
					{
						$tags = $tag = '';
						$inline = true;
					}
					else $tag .= ' class="'.$s.'">';

				}
				else
				{
					$tag .= ' class="'.$style->name.'">';
				}
			}

			if(empty($style))
			{
				$tags = 'p';
				$tag .= '<p class="'.$attrs['rend'].'">';
			}
		}
		else
		{
			$tag .= '<p class="'.end($this->_currentClass).'">';
			$tags = 'p';
		}

		if('p' === $name && empty($tags))
		{
			$tag .= '<p class="'.end($this->_currentClass).'">';
			$tags = 'p';
		}

		if(!empty($tags)) $this->_tags[] = $tags;

		$inlineTag = $this->_addLocalStyle($attrs, $inline);
		if (!$inline && $inlineTag) {
			list($inlineTag, $removed) = $this->_getAttributes($inlineTag,array('dir'));
			if ($removed) {
				$tag = preg_replace('/^<([^ >]*)/',"<$1$removed",$tag);
			}
		}
		return $tag.($closing ? '/>' : '').$inlineTag;
	}

	/**
	 * Ajoute des attributs dans le noeud en cours
	 *
	 * @access private
	 * @param array $attrs les attributs du noeud
	 * @return string les attributs XHTML selon les attributs donnés en entrée
	 */
	private function _addAttributes(array $attrs)
	{
		$text = '';

		if(isset($attrs['rendition']))
			$text .= ' style="'.$this->_getRendition($attrs['rendition']).'"';

		if(isset($attrs['lang']))
			$text .= ' xml:lang="'.$attrs['lang'].'" lang="'.$attrs['lang'].'"';

		if(isset($attrs['cols']))
			$text .= ' colspan="'.$attrs['cols'].'"';

		if(isset($attrs['rows']))
			$text .= ' rowspan="'.$attrs['rows'].'"';

		if(isset($attrs['class']))
			$text .= ' class="'.$attrs['class'].'"';

		return $text;
	}

	/**
	 * Parse un niveau de titre
	 *
	 * @access private
	 * @param array $attrs les attributs du noeud
	 * @return string le niveau de titre
	 */
	private function _parseHead(array $attrs)
	{
		if(!isset($attrs['subtype']))
		{
			$this->_log(getlodeltextcontents('TEIPARSER_MISSING_ATTRIBUTE_SUBTYPE_IN_HEADING', 'edition'));
			$level = 0;
		}
		else
			$level = (int) substr($attrs['subtype'], 5);

		if($level <= 0 || $level > 6)
		{
			$this->_log(sprintf(getlodeltextcontents('TEIPARSER_BAD_LEVEL_TITLE', 'edition'), $level, $level));
			$text = '<p class="heading'.$level.'">'.$this->_addLocalStyle($attrs);
			$this->_tags[] = 'p';
		}
		else
		{
			$tag = 'h'.$level;
			$this->_tags[] = $tag;
			$localAttrs = '';
			if ($inlineTag = $this->_addLocalStyle($attrs, false))
				list($inlineTag, $localAttrs) = $this->_getAttributes($inlineTag, array('dir'));
			$text = '<'.$tag . $localAttrs . $this->_addAttributes($attrs) . $this->_addAttributes(array('class' => end($this->_currentClass))) . '>';
		}

		return $text;
	}

	/**
	 * Parse une liste
	 *
	 * @access private
	 * @param array $attrs les attributs du noeud
	 * @return string la liste
	 */
	private function _parseList(array $attrs)
	{
		$tag = !isset($attrs['type']) || 'unordered' === $attrs['type'] ? 'ul' : 'ol';
		$this->_tags[] = $tag;
		$text = '<'.$tag . $this->_addAttributes($attrs) . $this->_addAttributes(array('class' => end($this->_currentClass))) . '>';
		$tags = '';

		while($this->read()) {
			if(parent::ELEMENT === $this->nodeType)
			{
				if('list' === $this->localName){
					$text .= $this->_parseList($this->_parseAttributes());
                                }elseif('item' === $this->localName)
				{
                                        $tags = '<li' . $this->_addAttributes($this->_parseAttributes()) . '>';
					$this->_tags[] = 'li';
				/*}elseif('formula' === $this->localName){
					continue;*/
				}else{
                                    $tags .= $this->_getTagEquiv($this->localName, $this->_parseAttributes());
                                }
			}
			elseif(parent::END_ELEMENT === $this->nodeType)
			{
                if('formula' === $this->localName) continue;
				$text .= $tags . $this->_closeTag();
				$tags = '';
				if('list' === $this->localName) break;
			}
			elseif(parent::TEXT === $this->nodeType || parent::WHITESPACE === $this->nodeType || parent::SIGNIFICANT_WHITESPACE === $this->nodeType)
			{
				if ($tags) {
					list($tags, $removed) = $this->_getAttributes($tags, array('dir'));
					if ($removed)
						$tags = preg_replace('/^<([^ >]*)/', "<$1$removed", $tags);
				}
				$text .= $tags . $this->_getText($this->value);
				$tags = '';
			}
		}

		return $text;
	}

	/**
	 * Parse une bibliographie
	 *
	 * @access private
	 * @param array $attrs les attributs du noeud
	 * @return string la bibliographie
	 */
	private function _parseListBibl(array $attrs)
	{
		$text = '';

		while($this->read())
		{
			if(parent::ELEMENT === $this->nodeType)
				$text .= $this->_getTagEquiv($this->localName, $this->_parseAttributes());
			elseif(parent::END_ELEMENT === $this->nodeType)
			{
				if('listBibl' === $this->localName) break;

				$text .= $this->_closeTag();
			}
			elseif(parent::TEXT === $this->nodeType || parent::WHITESPACE === $this->nodeType || parent::SIGNIFICANT_WHITESPACE === $this->nodeType)
				$text .= $this->_getText($this->value);
		}

		return $text;
	}

	/**
	 * Parse une table
	 *
	 * @access private
	 * @param array $attrs les attributs du noeud
	 * @return string le tableau
	 */
	private function _parseTable(array $attrs)
	{
		static $tableCount = 0;
		if (!isset($attrs['id'])) $attrs['id'] = "Table".(++$tableCount);

		$attributs = $this->_addAttributes($attrs);
		if (preg_match('/direction:([^\'";]+)/',$attributs,$m)) $attributs .= " dir=\"".$m[1]."\"";
		$text = '<table id="'.$attrs['id'].'"' . $attributs . $this->_addAttributes(array('class' => end($this->_currentClass))) .'>';
		$this->_tags[] = 'table';
	
		while($this->read())
		{
			if(parent::ELEMENT === $this->nodeType)
			{
				if('table' === $this->localName)
					$text .= $this->_parseTable($attrs);
				elseif('row' === $this->localName)
				{
					$text .= '<tr' . $this->_addAttributes($this->_parseAttributes()) . '>';
					$this->_tags[] = 'tr';
				}
				elseif('cell' === $this->localName)
				{
					$attributs = $this->_addAttributes($this->_parseAttributes());
					if (preg_match('/direction:([^\'";]+)/',$attributs,$m)) $attributs .= " dir=\"".$m[1]."\"";
					$text .= '<td' . $attributs . '>';
					$this->_tags[] = 'td';
				}
				elseif('anchor' === $this->localName) // || 'formula' === $this->localName)
				{
						continue;
				}
				else {
					if($this->localName === 's' || $this->localName === 'formula') 
				
					{
						$localname = 'p';
					} else 
					{
						$localname = $this->localName;
					}
					$text .= $this->_getTagEquiv($localname, $this->_parseAttributes());
				}
				if( $this->isEmptyElement && in_array($this->localName, array('table', 'row', 'cell')) ){
					$text .= $this->_closeTag();
				}

			}
			elseif(parent::END_ELEMENT === $this->nodeType)
			{
				$text .= $this->_closeTag();

				if('table' === $this->localName) break;
			}
			elseif(parent::TEXT === $this->nodeType || parent::WHITESPACE === $this->nodeType || parent::SIGNIFICANT_WHITESPACE === $this->nodeType){
				$text .= $this->_getText($this->value);
			}
		}

		if(in_array($attrs['id'], $this->_tables))
			return;

		$this->_tables[] = $attrs['id'];

		return $text;
	}

	/**
	 * Parse une figure (== image)
	 *
	 * @access private
	 * @param array $attrs les attributs du noeud
	 * @return string l'image
	 */
	private function _parseFigure($attrs)
	{
		$text = '';

		while($this->read())
		{
			if(parent::ELEMENT === $this->nodeType && 'graphic' === $this->localName)
			{
				$attrs = $this->_parseAttributes();
				$nb    = array();
				$id    = "";
				$source = realpath($this->_tmpdir) . DIRECTORY_SEPARATOR;

				if(isset($attrs['url']) && is_readable($source . $attrs['url'])){
					/* Creation of import folder */
					$array  = array_filter(explode('/', $this->_tmpdir));
					$tmpdir = SITEROOT . 'docannexe/image/' . end($array) . DIRECTORY_SEPARATOR;
					if( ! file_exists($tmpdir) ) mkdir($tmpdir);
					chmod($tmpdir, 0777 & octdec(C::get('filemask', 'cfg')));

					/* Getting file name */
					if (is_file($source . $attrs['url']))
						copy($source . $attrs['url'], $tmpdir . basename($attrs['url']) );
					else
						$this->_log(sprintf(getlodeltextcontents('TEIPARSER_INVALID_FIELD', 'edition'), htmlspecialchars($this->readOuterXML())));

					$attrs['url'] = $tmpdir . basename($attrs['url']);
				}else{
					$id = basename($attrs['url']);
					$nb = explode('-', $id);
					// get images temporary url
					if(isset($this->_images[$id]))
						$attrs['url'] = $this->_images[$id];
				}
				$rend = '';
				if (!empty($attrs['rend']) && ($attrs['rend'] === 'inline')) {
				    $rend = ' class="'.$attrs['rend'].'"';
				}
				$text .= '<img src="'.$attrs['url'].'"'.$rend.' alt="Image '.end($nb).'" id="'.$id.'"/>';
			}
			elseif(parent::END_ELEMENT === $this->nodeType && 'figure' === $this->localName)
				break;
		}
		return $text;
	}

	/**
	 * Parse une note
	 *
	 * @access private
	 * @param array $attrs les attributs du noeud
	 * @return string la note
	 */
	private function _parseNote(array $attrs)
	{
		++$this->_nbNotes;

		if(!isset($attrs['place']))
		{
			$this->_log(getlodeltextcontents('TEIPARSER_MISSING_PLACE_ATTRIBUTE_FOR_NOTE', 'edition'));
			$attrs['place'] = 'foot';
		}

		if(!isset($attrs['n']))
		{
			$this->_log(sprintf(getlodeltextcontents('TEIPARSER_MISSING_NOTE_NUMBER', 'edition'), $this->_nbNotes));
			$attrs['n'] = $this->_nbNotes;
		}

		$type = $this->_getStyle($attrs['place'].'note');
		$type = $type['class'];

		isset($this->_contents[$type]) || $this->_contents[$type] = array();
		$first = false;

		$text =& $this->_contents[$type][$this->_nbNotes];

		while($this->read())
		{
			if(parent::ELEMENT === $this->nodeType)
			{
				$text .= $this->_getTagEquiv($this->localName, $this->_parseAttributes());
				if(!$first)
				{ // we add the anchor at the beginning
					$text .= '<a class="'.ucfirst($attrs['place']).'noteSymbol" href="#bodyftn'.$this->_nbNotes.'" id="ftn'.$this->_nbNotes.'">'.$attrs['n'].'</a> ';
					$first = true;
				}elseif('formula' === $this->localName){
					continue;
				}
			}
			elseif(parent::END_ELEMENT === $this->nodeType)
			{
				if('note' === $this->localName) break;

				$text .= $this->_closeTag();
			}
			elseif(parent::TEXT === $this->nodeType || parent::WHITESPACE === $this->nodeType || parent::SIGNIFICANT_WHITESPACE === $this->nodeType)
				$text .= $this->_getText($this->value);
		}

		return '<a class="'.$attrs['place'].'notecall" id="bodyftn'.$this->_nbNotes.'" href="#ftn'.$this->_nbNotes.'">'.$attrs['n'].'</a>';
	}

    /**
     * Parse une citation ou un exemple (balise quote)
     *
     * @access private
     * @param array $attrs les attributs du noeud
     * @return string la citation ou l'exemple
     */
	private function _parseQuote(array $attrs)
	{
	    /* Count the childs */
	    $childs = array();
	    $text   = "";
        foreach($this->expand()->childNodes as $child){
            if(!isset($childs[$child->nodeName])) $childs[$child->nodeName] = 0;
        	$childs[$child->nodeName]++;
        }

        if( isset($childs['quote']) && (int) $childs['quote'] > 0 ){
            if(isset($attrs['quoteline'])){
                $text .= "<tr><td>{$attrs['n']}</td><td>";
                $this->_tags[] = array("table", "td", "tr");
                $attrs = $this->_parseAttributes();
            }else{
                $this->_tags[] = "table";
            }
            $text .= "<table class=\"{$attrs['type']}\">";

        }elseif( (int) $childs['seg'] > 0 ) {
            $localattrs = $this->_parseAttributes();
            if(!isset($localattrs['subtype'])) $localattrs['subtype'] = "";
            $text .= "<tr class=\"{$localattrs['subtype']}\"><td>{$attrs['n']}</td>";
            $this->_tags[] = 'tr';
        }else{
            $text .= "<tr><td>{$attrs['n']}</td><td colspan=\"{$attrs['cols']}\">";
            $this->_tags[] = array('td','tr');
        }

        $cols = 1;

        while($this->read()){
            if(parent::ELEMENT === $this->nodeType){
                if( "quote" == $this->localName){
                       $childchilds = array();
                       foreach($this->expand()->childNodes as $child){
                           if(!isset($childchilds[$child->nodeName])) $childchilds[$child->nodeName] = 0;
                           $childchilds[$child->nodeName]++;
                       }
                       if($childchilds['seg']) $cols = $childchilds['seg'];

    	               $text .= $this->_getTagEquiv($this->localName, array('quoteline'   => true,
                                                                     'n'           => $attrs['n'],
                                                                     'cols'        => $cols,
                                                                   ));
                       $attrs['n'] = "&nbsp;";
	            }elseif( "bibl" == $this->localName || "gloss" == $this->localName ){
	                $text .= "<tr><td>&nbsp;</td><td class=\"{$this->localName}\" colspan=\"$cols\">";
	                $this->_tags[] = array('td','tr');
	            }else{
	                $text .= $this->_getTagEquiv($this->localName, $this->_parseAttributes());
	            }
	        }elseif(parent::END_ELEMENT === $this->nodeType){
                $text .= $this->_closeTag();
	            if( "quote" == $this->localName ) break;

	        }elseif(parent::TEXT === $this->nodeType || parent::WHITESPACE === $this->nodeType || parent::SIGNIFICANT_WHITESPACE === $this->nodeType){
                $text .= $this->_getText($this->value);
	        }
	    }

	    return $text;
	}

	private function _parseSeg(array $attrs)
	{
	    $this->_tags[] = "td";

        $localAttrs = '';
        if ($inlineTag = $this->_addLocalStyle($attrs, false)) {
            list($inlineTag, $localAttrs) = $this->_getAttributes($inlineTag, array('dir'));
        }
        $text = '<td' . $localAttrs . $this->_addAttributes($attrs) . '>';

	    while($this->read()){
	        if(parent::ELEMENT === $this->nodeType){
	            $text .= $this->_getTagEquiv($this->localName, $this->_parseAttributes());
	        }elseif(parent::END_ELEMENT === $this->nodeType){
	            if('seg' === $this->localName) break;

	            $text .= $this->_closeTag();

	        }elseif(parent::TEXT === $this->nodeType || parent::WHITESPACE === $this->nodeType || parent::SIGNIFICANT_WHITESPACE === $this->nodeType){
                $text .= $this->_getText($this->value);
            }
	    }

	    $text .= $this->_closeTag();

	    return $text;
	}

    /**
     * Parse une bloc de code
     *
     * @access private
     * @param array $attrs les attributs du noeud
     * @return string le code
     */
    private function _parseCode(array $attrs)
    {
        $text = "<pre><code class=\"brush: {$attrs['lang']};\">";
        while($this->read()){
            if( parent::END_ELEMENT === $this->nodeType ){
                if ('code' === $this->localName) // ne pas prendre l'élément précedent quand on tombe sur </code>
                    break;
                $text .= $this->_closeTag();
                break;
            }elseif( parent::TEXT === $this->nodeType ){
                $text .= "<![CDATA[\n{$this->value}\n]]>";
            }
        }
        $text .= "</code></pre>";
        return $text;
    }

	/**
	 * Remet les namespaces
	 *
	 * @access private
	 * @param  SimpleXMLElement
	 */
	 private function _updateNameSpaces(SimpleXMLElement &$v)
	 {
  		foreach($this->_namespaces as $k => $ns){
  		    if(empty($k) && !isset($empty)){
  		        $empty = $ns;
  		        $v->addAttribute("xmlns", $ns);
  		    }else{
  		        /* Hack permettant de générer le bon XML, PHP 5.3 a changé le comportement
  		         * de SimpleXML, qui supprime le prefixe du namespace, mais qui ne sait pas
  		         * le traiter après coup. Bizarre.
  		         */
   				$v->addAttribute("xmlns:xmlns:{$k}", $ns, $empty);
  		    }
  		}
	 }

	/**
	 * Trouve certains attributs d'un tag
	 *
	 * @access private
	 * @param string $tagStr le noeud en XHTML
	 * @param array $attributes les attributs
	 * @param bool $remove permet d'effacer les attributs par la même occasion
	 * @return array string le noeud en XTML, string les attributs XHTML
	 */
	private function _getAttributes($tagStr, $attributes = array('dir','xml:lang','lang'), $remove=false) {
		$removed = array();
		$tags = explode('>',$tagStr);
		foreach ($tags as $tag) {
			if ($tag) {
				$tag .= '>';
				if (!$nodeElem = $this->_tag2domNode($tag)) {
					$newTags[] = $tag;
				} else {
					foreach ($attributes as $attr) {
						if ($nodeElem->hasAttribute($attr)) {
							$removed[] = " $attr=\"".$nodeElem->getAttribute($attr)."\"";
							if ($remove) $nodeElem->removeAttribute($attr);
						}
					}
					if ($remove) $newTags[] = $this->_domNode2tag($nodeElem, !((strpos($tag, '/>')===false)));
					else $newTags[] = $tag;
				}
			}
		}
		$newTags = implode('',$newTags);
		$removed = implode('',array_unique($removed));
		return array($newTags, $removed);
	}

	/**
	 * Transforme un tag en DOMNode
	 *
	 * @access private
	 * @param string $tag le tag en caractères
	 * @return DOMNode le tag en DOMNode
	 */
	private function _tag2domNode($tag) {
		if (! preg_match('/^<[^>]+>$/', $tag)) return false;
		if (strpos($tag, '/>')===false)
			$tag = str_replace('>','/>',$tag);
		$doc = new DOMDocument();
		if (!$doc->loadXML($tag)) return false;
		return $doc->firstChild;
	}

	/**
	 * Transforme un DOMNode en tag
	 *
	 * @access private
	 * @param DOMNode le tag en DOMNode
	 * @return string $tag le tag en caractères
	 */
	private function _domNode2tag($domNode, $selfClosed = false) {
		$tag = "<".$domNode->nodeName;
		foreach ($domNode->attributes as $DOMAttr) {
			$prefix = $DOMAttr->prefix;
			$tag .= " ".($prefix?"$prefix:":"").$DOMAttr->name."=\"".$DOMAttr->value."\"";
		}
		return $tag . ($selfClosed ? " />" : ">");
	}

}
