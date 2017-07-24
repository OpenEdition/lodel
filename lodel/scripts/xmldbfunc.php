<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier de la classe XMLDB
 */

/**
 * Classe XMLDB
 */
class XMLDB
{
	/**#@+
	 * @access private
	 */
	/**
	 * Préfix des tables
	 * @var string
	 */
	var $tp;
	/**
	 * Les tables à inclure et les informations sur les relations, elements, tags
	 * @var array
	 */
	var $tables; // contains the table to includes and information of relationshiop,element,tags
	
	/**
	 * Element racine
	 * @var string
	 */
	var $documentroot;

	/**
	 * L'en-tête
	 * @var string
	 */
	var $header;

	/**
	 * Etat du parser ?
	 * @var ?
	 */
	var $state;

	/**
	 * Pile des tables  (pour le parser XML)
	 * @var array
	 */
	var $tablestack;
	/**
	 * Enregistrement courant
	 * @var array
	 */
	
	var $currentrecord;

	/**
	 * ?
	 * @var array
	 */
	var $rows;
	
	/**
	 * ?
	 * @var array
	 */
	var $data;

	/**
	 * Pile des champs de jointures
	 * @var array
	 */
	var $joinfieldvaluestack;
	/**#@-*/

	/**
	 * Constructeur
	 *
	 *
	 * @param string $documentroot l'élement racine
	 * @param string $tableprefix le prefix des tables
	 */
	function __construct($documentroot = '', $tableprefix = '')
	{
		$this->documentroot = $documentroot;
		$this->tp = $tableprefix;
	}

	/**
	 * Ajout d'une table
	 */
	function addTable()
	{
		foreach (func_get_args() as $table) {
			$tablename = $table;
			$this->tables[$table] = array ('element' => array (), 'attr' => array (), 'where' => array (), 'join' => array ());
			$this->tables[$table]['rowtag'] = "row";
		}
	}
	/**
	 * Définit un tag pour une ligne
	 * @param string $table le nom de la table
	 * @param string $rowtag ??
	 */
	function setRowtag($table, $rowtag)
	{
		$this->tables[$table]['rowtag'] = $rowtag;
	}

	/**
	 * Ajoute une condition where sur une table
	 */	
	function addWhere()
	{
		$table = func_get_arg(0);
		$numargs = func_num_args();
		for ($i = 1; $i < $numargs; $i ++) {
			$this->tables[$table]['where'][] = func_get_arg($i);
		}
	}

	/**
	 * Add Element
	 * First argument is the tablename
	 * Others arguments are elements. If argument is an array, it contains the field and the element name. 
	 * If argument is a string, both element and field have the same name
	 */
	function addElement()
	{
		$table = func_get_arg(0);
		$numargs = func_num_args();
		for ($i = 1; $i < $numargs; $i ++) {
			$arg = func_get_arg($i);
			if (is_array($arg)) {
				list ($field, $el) = $arg;
				$this->tables[$table]['element'][$field] = $el;
			} else {
				$this->tables[$table]['element'][$arg] = $arg;
			}
		}
	}

	/**
	 * Add attribut
	 * First argument is the tablename
	 * Others arguments are elements. If argument is an array, it contains the field and the element name. 
	 * If argument is a string, both element and field have the same name
	 */
	function addAttr()
	{
		$table = func_get_arg(0);
		$numargs = func_num_args();
		for ($i = 1; $i < $numargs; $i ++) {
			$arg = func_get_arg($i);
			if (is_array($arg)) {
				list ($field, $attr) = $arg;
				$this->tables[$table]['attr'][$field] = $attr;
			} else {
				$this->tables[$table]['attr'][$arg] = $arg;
			}
		}
	}
	/**
	 * Ajoute une jointure entre une table parent et une table enfant
	 * 
	 * @param string $tableparent nom de la table parente
	 * @param string $parentfield nom du champ parent
	 * @param string $tablechild nom de la table enfant
	 * @param string $childfield nom du champ enfant
	 */
	function addJoin($tableparent, $parentfield, $tablechild, $childfield)
	{
		$this->tables[$tableparent]['join'][$tablechild] = $parentfield;
		$this->tables[$tablechild]['joinfield'] = $childfield;
		$this->tables[$tablechild]['child'] = true;
	}
	/**
	 * Définition d'un header XML
	 *
	 * @param string $xml le header XML
	 */
	function addHeader($xml)
	{
		$this->header .= $xml;
	}

	/*******************************************/
	/* Methods to create XML file              */
	/*******************************************/

	/**
	 * Ecrire le XML dans un fichier
	 *
	 * Write the XML into a file
	 *
	 * @param string $filename le nom du fichier XML
	 */
	function saveToFile($filename)
	{
		$this->fp = fopen($filename, "w");
		if (!$this->fp)
			trigger_error("ERROR: can't open filename $filename for writing", E_USER_ERROR);
		$this->saveToString();
	}

	/**
	 * Ecrire le XML dans une chaine de caractère
	 *
	 * @return string le XML
	 */
	function saveToString()
	{
		$this->string = '';
		$this->_write("<". $this->documentroot. ">\n");
		if ($this->header)
			$this->_write("<header>". $this->header. "</header>\n");
		foreach ($this->tables as $table => $info) {
			if (isset($info['child']) && $info['child'])
				continue; # will be processed in with its parent
			$this->exporttable($table, $info);
		}
		$this->_write("</".$this->documentroot.">");
		return $this->string;
	}

	/**
	 * Exportation d'une table dans le fichier XML
	 *
	 * @param string $table le nom de la table
	 * @param string $info
	 * @param string $joinfieldvalue
	 */
	function exporttable($table, $info, $joinfieldvalue = "")
	{
		global $db;
		//
		// select
		$select = join(",".$this->tp.$table.".", array_merge(array_keys($info['element']), array_keys($info['attr'])));
		if (!$select)
			return;
		$select = $this->tp.$table.".".$select;

		//
		// join
		$join = array ();
		if (isset($info['joinfield']) && $info['joinfield']) {
			$join[] = $info['joinfield']."='".$joinfieldvalue."'";
		}
		//
		// where and join
		if (isset($info['where']) && $info['where'])
			$where = " WHERE ".join(" AND ", array_merge($info['where'], $join));

		//
		// Query

		$result = $db->execute(lq("SELECT $select FROM ".$this->tp.$table.$where)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

		if ($result->recordcount() <= 0)
			return;

		$this->_write("<$table>\n");

		if (count($info['element']) == 1 && !$info['join']) {
			$rowtag = reset($info['element']);
			$elementtag = false;
		}	else {
			$rowtag = $info['rowtag'];
			$elementtag = true;
		}

		while (!$result->EOF) {
			$row = $result->fields;
			// information for the table
			$this->_write("<$rowtag");
			foreach ($info['attr'] as $field => $attr) {
				$this->_write(" ".$attr.'="'.htmlspecialchars($row[$field]).'"');
			}
			if (!$info['element'] && !$info['join']) {
				$this->_write("/>\n");
				continue;
			}
			$this->_write(">");
			foreach ($info['element'] as $field => $el) {
				if ($elementtag)
					$this->_write("<$el>");
				$this->_write(htmlspecialchars($row[$field]));
				if ($elementtag)
					$this->_write("</$el>\n");
			}

			// export child table
			foreach ($info['join'] as $childtable => $joinfield) {
				$this->exporttable($childtable, $this->tables[$childtable], $row[$joinfield]);
			}

			$this->_write("</$rowtag>\n");
			$result->MoveNext();
		}
		$this->_write("</$table>\n");
	}

	/**
	 * Ecriture d'une chaine dans le fichier XML
	 *
	 * generic output function
	 *
	 * @param string $string la chaîne à écrire
	 * @access private
	 */
	function _write($string)
	{
		if ($this->fp) {
			fwrite($this->fp, $string);
		} else {
			$this->string .= $string;
		}
	}

	/**
	 * Lecture d'un fichier XML (depuis une chaîne)
	 *
	 * @param string $xml la chaîne contenant le XML
	 */
	function readFromString($xml)
	{

		$xml_parser = $this->_initparser();

		if (!xml_parse($xml_parser, $xml, true)) {
			trigger_error(sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser)), E_USER_ERROR);
		}
	}
	
	/**
	 * Lecture d'un fichier XML (depuis un fichier)
	 *
	 * @param string $filename le fichier contenant le XML
	 */
	
	function readFromFile($filename)
	{
		$xml_parser = $this->_initparser();
		if (!($fp = fopen($filename, "r"))) {
			trigger_error("ERROR: could not open XML input", E_USER_ERROR);
		}

		while ($data = fread($fp, 4096)) {
			if (!xml_parse($xml_parser, $data, feof($fp))) {
				trigger_error(sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser)), E_USER_ERROR);
			}
		}
		xml_parser_free($xml_parser);
	}

	/**
	 * Initialisation du parser XML
	 * @access private
	 */
	function _initparser()
	{
		$xml_parser = xml_parser_create();
		$this->state = '';
		$this->tablestack = array ();
		$this->joinfieldvaluestack = array ();

		xml_set_object($xml_parser, $this);
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_element_handler($xml_parser, 'startElement', 'endElement');
		xml_set_character_data_handler($xml_parser, 'characterData');

		return $xml_parser;
	}
	/**
	 * Insertion d'une ligne
	 *
	 * Cette fonction est abstraite. Elle doit être définie dans une classe dérivée.
	 *
	 * @param string $currenttable la table courante
	 * @param  array $rows les données à insérer
	 */
	function insertRow($currentable, $rows)
	{
		trigger_error("Redefined insertRow in a child class. insertRow must return the field used for joining", E_USER_ERROR);
		return null;
	}

	/**
	 * @internal
	 * XML Parser handler
	 */
	function startElement($parser, $name, $attrs)
	{
		#echo "<br/>startElement $name ".$this->state."    $currenttable<br/>";
		$currenttable = @$this->tablestack[0];

		switch ($this->state) {
		case "inrecord" :
			trigger_error("ERROR: Invalid XML. Expecting data only", E_USER_ERROR);
			break;
		case "record" :
			// going into a element ?
			if (in_array($name, $this->tables[$currenttable]['element'])) {
				// start a new record
				$this->_newrecord($name);
				$this->state = "inrecord";
			}	elseif (isset($this->tables[$currenttable]['join'][$name]) && $this->tables[$currenttable]['join'][$name]) {
				// records a finish
				$this->_endrow();
				// start in the child table
				$this->_newtable($name);
				$this->state = "row"; // look for a row now
				break;
			} else {// not good.
				trigger_error("ERROR: Invalid XML. Expecting for a element. Got &lt;$name&gt;", E_USER_ERROR);
			}
			break;
		case "row" :
			// add the attrs to the rows
			$this->records = array ();
			foreach ($attrs as $attrname => $val) {
				$field = array_search($attrname, $this->tables[$currenttable]['attr']);
				if (!$field)
					trigger_error("ERROR: Invalid XML. Unexpected attribute $attrname in tag &lt;$name&gt;", E_USER_ERROR);
				$this->records[$field] = $val;
			}
			// add the parent join field if we are a child
			if (isset($this->tables[$currenttable]['child']) && $this->tables[$currenttable]['child']) {
				$this->records[$this->tables[$currenttable]['joinfield']] = $this->joinfieldvaluestack[1];
			}
			// row started but also start of the record (unique record)
			if (isset($this->tables[$currenttable]['norowelement']) && $this->tables[$currenttable]['norowelement']) {
				$tag = reset($this->tables[$currenttable]['element']);
				if ($name == $tag) {
					$this->state = "inrecord";
					$this->_newrecord($name);
				}	else {
					trigger_error("ERROR: Invalid XML. Expecting &lt;$tag&gt; but got &lt;$name&gt;", E_USER_ERROR);
				}
			}	else {
				if (isset($this->tables[$currenttable]['rowtag']) && $name == $this->tables[$currenttable]['rowtag']) {
					$this->state = "record";
				} else {
					trigger_error("ERROR: Invalid XML. Expecting &lt;".$this->tables[$currenttable]['rowtag']."&gt; but got &lt;$name&gt;", E_USER_ERROR);
				}
			}
			break;
		case "table" :
			if (isset($this->tables[$name]) && $this->tables[$name]) {
				// start of a new table
				$this->state = "row";
				$this->_newtable($name);
			} else {
				trigger_error("ERROR: Invalid XML. Expecting a table name. Found &lt;$name&gt;", E_USER_ERROR);
			}
			break;
		default :
			if ($name != $this->documentroot)
				trigger_error("ERROR: Invalid XML. Expecting a documentroot. Found &lt;$name&gt;", E_USER_ERROR);
			$this->state = "table";
		} // end swith }}}
	}

	function endElement($parser, $name)
	{
		#echo "endElement $name ".$this->state."<br/>";
		$currenttable = @$this->tablestack[0];
		switch ($this->state)	{
		case "inrecord" :
			if ($name == $this->currentrecord) {
				$field = array_search($name, $this->tables[$currenttable]['element']);
				$this->records[$field] = $this->data;
				$this->state = "record";
				$this->_endrecord($name);

				if (isset($this->tables[$currenttable]['norowelement']) && $this->tables[$currenttable]['norowelement']) {
					$tag = reset($this->tables[$currenttable]['element']);
					if ($name == $tag) {
						$this->_endrow();
						$this->state = "row";
					}	else {
						trigger_error("ERROR: XML Invalid. Hum... XML parser should have crash", E_USER_ERROR);
					}
				}
			}	else {
				trigger_error("ERROR: Invalid XML. Expecting &lt;/".$this->currentrecord."&gt; element. Found &lt;/$name&gt;", E_USER_ERROR);
			}
			break;
		case "record" :
			// rowtag element
			if (isset($this->tables[$currenttable]['rowtag']) && $name == $this->tables[$currenttable]['rowtag']) {
				// finish recording
				if (!$this->tables[$currenttable]['join']) { // if join, the insertion has already been done
					$this->joinfieldvaluestack[0] = $this->insertRow($currenttable, $this->records);
				}
				$this->state = "row";
			} else {
				trigger_error("EROR: XML Invalid. Expecting &lt;/".$this->tables[$currenttable]['rowtag']."&gt; element. Found &lt;/$name&gt;", E_USER_ERROR);
			}
			break;
		case "row" :
			if (isset($this->tables[$currenttable]['norowelement']) && $this->tables[$currenttable]['norowelement']) {
				if ($name == $currenttable) {
					$this->_endtable();
					#####$this->state="table";
					$this->state = "row"; // stay in the row state
				} else {
					trigger_error("EROR: XML Invalid. Expecting &lt;/".$currenttable.".&gt; element. Found &lt;/$name&gt;", E_USER_ERROR);
				}
			} else {
				if (isset($this->tables[$currenttable]['rowtag']) && $name == $this->tables[$currenttable]['rowtag']) {
					// nothing to do
				} elseif ($name == $currenttable) {
					// closing current table
					$this->_endtable();
					$this->state = "table";
				} else {
					trigger_error("EEEROR: XML Invalid. Expecting &lt;".$this->tables[$currenttable]['rowtag']."&gt; element. Found &lt;/$name&gt;", E_USER_ERROR);
				}
			}
			break;
		case "table" :
			if ($name == $currenttable) {
				$this->_endtable();
				$this->state = "table";
			}
			break;
		default :
			// nothing to do...
		}
	}
	/**
	 * @access private
	 * @param string $name nom du nouvel enregistrement
	 */
	function _newrecord($name)
	{
		$this->currentrecord = $name;
		if (!$this->data)
			trigger_error("ERROR: data should be empty here", E_USER_ERROR);
	}
	/**
	 * @access private
	 * @param string $name fin de l'enregistrement
	 */
	function _endrecord($name)
	{
		$this->currentrecord = '';
		$this->data = '';
	}
	/**
	 * @access private
	 */
	function _endrow()
	{
		$this->joinfieldvaluestack[0] = $this->insertRow($this->tablestack[0], $this->records);
		#echo "lala:",$this->tablestack[0],"   ",$this->joinfieldvaluestack[0],"   ";
	}
	/**
	 * @access private
	 */
	function _newtable($table)
	{
		array_unshift($this->tablestack, $table);
		array_unshift($this->joinfieldvaluestack, null);
		// make easies to work with
		$this->tables[$table]['norowelement'] = count($this->tables[$table]['element']) <= 1 && !$this->tables[$table]['join'];

	}
	/**
	 * @access private
	 */
	function _endtable()
	{
		array_shift($this->tablestack);
		array_shift($this->joinfieldvaluestack);
	}
	/**
	 * ??
	 * @param object $parser
	 * @param string $data
	 */
	function characterData($parser, $data)
	{
		$this->data .= $data;
	}

} //class }}}
