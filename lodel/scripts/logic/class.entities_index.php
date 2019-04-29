<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Logique des entités - indexation moteur de recherche
 */

/**
 * Classe de logique des entités (gestion de l'indexation dans le moteur de recherche)
 *
 */
class Entities_IndexLogic extends Logic 
{
	/**
	 * Tableau des équivalents génériques
	 *
	 * @var array
	 */
	public  $g_name;

	/**
	 * Constructeur
	 */
	public function __construct()
	{
		parent::__construct('search_engine');
	}

	/**
	 * Add an object to the search_engine. An object is added only its type must be indexed 
	 * and if its fields have weight defined > 0
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	*/
	public function addIndexAction (&$context, &$error)
	{
		global $db;
		//no object identity specified
		if (empty($context['id'])) trigger_error("ERROR: no id given", E_USER_ERROR);
		$id = $context['id'];
		//if this entity is already indexed ==> clean
		$this->deleteIndexAction ($context, $error);

		$sql = "SELECT e.id, class, search FROM #_TP_entities as e";
		//join table type on idtype
		$sql .= " INNER JOIN #_TP_types ON e.idtype=#_TP_types.id";
		//where
		$sql .= " WHERE e.id='$id'";
		$row = $db->getRow (lq ($sql)) ;
		if (!$row || empty($row['id'])) trigger_error("ERROR: can't find object $id ". $dao_temp->table, E_USER_ERROR);

		$class= $row['class'];
		if (!$class) trigger_error("ERROR: idtype is not valid in Entities_IndexLogic::addIndexAction", E_USER_ERROR);
		//if the field search is not equal to 1, dont index the entity
		if ($row['search'] != 1) return "_back";

		//get the fieldnames list to index
		$vos_fields = DAO::getDAO ("tablefields")->findMany ("class='$class' AND weight > 0", "weight DESC", "id,weight,name");

		//no fields to index --> return
		if (!$vos_fields) return ("_back");

		$sql = "SELECT * FROM #_TP_$class WHERE identity='$id'";
		$row = $db->getRow(lq($sql)) ;
		if (!$row) trigger_error("ERROR: can't find object $id in table ". lq ("#_TP_$class"), E_USER_ERROR);
		$daoIndex = DAO::getDAO ("search_engine"); 	
		foreach ( $vos_fields as $vo_field)
		{
			if(!empty($row[$vo_field->name]))
				$this->_indexField ($id, $row[$vo_field->name], $vo_field->name, $vo_field->weight, $daoIndex);
		}
		//Index entries relations
		$this->_indexEntitiesRelations($id,'E',$daoIndex);
		//Index persons relations
		$this->_indexEntitiesRelations($id,'G',$daoIndex);
		return "_back";
	}

	/**
	 * delete an objet from the index
	 * needed parameters
	 * 	- object id
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function deleteIndexAction(&$context,&$error)
	{
		if (empty($context['id'])) trigger_error("ERROR: give the id ", E_USER_ERROR);

		$id = $context["id"];
		if (DAO::getDAO("search_engine")->deleteObjects ("identity='$id'"))//delete all lines with identity=id and return
			return '_back';
		else
			return '_error';
	}

	/**
	 * clean the index of all objet
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function cleanIndexAction(&$context,&$error)
	{
		DAO::getDAO ("search_engine")->deleteObjects("1");    //delete all index lines and return
		#echo "index cleaning";
		return '_ok';
	}

	/**
	 * Rebuild entirely the Index
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function rebuildIndexAction(&$context, &$error) 
	{
		global $db;
		if (!isset ($context['clean'])) $context['clean'] = 1; # assumed its true
		$context['clean'] = (int)$context['clean'];
		if ($context['clean'] == 1) {
			#echo "cooucou".$context['clean'];
			if($this->cleanIndexAction($context,$error) != '_ok')
				return '_error';
		}
		$timeout = ini_get ("max_execution_time");
		$prudent_timeout = $timeout*0.8;
		$start = time();
		//boucle sur toutes les entites a indexer.
		$sql = "SELECT e.id,t.class,t.search from (#_TP_entities e,#_TP_types t)";
		$sql .=" LEFT OUTER JOIN #_TP_search_engine se ON e.id=se.identity ";
		$sql .=" WHERE se.identity is null AND t.id=e.idtype AND t.search=1";
		$result=$db->execute(lq($sql)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		while (!$result->EOF) {
			$context["id"] = $result->fields['id'];
			$this->addIndexAction($context,$error);
			$current = time();
			if ( ($current - $start) < $prudent_timeout)
				$result->MoveNext();
			else {
				//80% du timeout est dépassé il faut rediriger.
				header("Location: index.php?do=rebuildIndex&lo=entities_index&clean=0");
			}
		}
		return "_back";
	}

	/**
	 * index a given field in the database (using dao_index)
	 *
	 * @param $id : entity database identifier
	 * @param $fieldValue : the value of the field
	 * @param $fieldName : the name of the field
	 * @param $fieldWeight : the weight used to ponderate the field
	 * @param $daoIndex : the dao to use to save the data
	 * @param $prefixtablefield : empty by default but used to prefix the field 'tablefield' (for entries or persons for example)
	 */
	protected function _indexField ($id, $fieldValue, $fieldName, $fieldWeight, $daoIndex, $prefixtablefield = '') 
	{
		if (!$fieldValue) return;
		$fieldValue = preg_replace ("/<[^>]*>/", " ", $fieldValue);//HTML tags cleaning
		$fieldValue = $this->_decode_html_entities ($fieldValue); //HTML Entities decode
		$indexs = $this->_cleanAndcountTokens ($fieldValue); //clean and count tokens
		//Indexation de tous les mots.
		foreach($indexs as  $key => $index) {
			$daoIndex->instantiateObject ($voIndex);
			$voIndex->identity = $id;
			$voIndex->tablefield = $prefixtablefield.$fieldName;
			$voIndex->word = addslashes($key);
			$voIndex->weight = $indexs[$key] * $fieldWeight; //ponderation with field weight
			$daoIndex->save($voIndex,true);
		}
	}

	/** Private function
	 *  Description : decode HTML entities,
	 * @param $text the text where HTML entities must be decoded
	 * @return $text the text with HTML entities decoded
	 * @access private
	 */
	protected function _decode_html_entities($text) 
	{
	    $text= preg_replace_callback('/&#(\d+);/m',function ($str) { return utf8_encode(chr($str[1])); },$text); #decimal notation
	    $text= preg_replace_callback('/&#x([a-f0-9]+);/mi',function ($str) { return utf8_encode(chr(hexdec('0x'.$str[1]))); },$text);  #hex notation
		//$text= preg_replace('/&#(\d+);/me',utf8_encode("chr(\\1)"),$text); #decimal notation
		//$text= preg_replace('/&#x([a-f0-9]+);/mei',utf8_encode("chr(0x\\1)"),$text);  #hex notation
		return $text;
	}

	/**
	 *  Split a string into tokens by given regs
	 * @param $string the string to be splitted
	 * @param $regs the regs used to split the string
	 * @return an array of tokens
	 * @access private
	*/
	protected function _splitInTokens ($string, $regs = 0)
	{
		if(!$regs)
			$regs = "'\.],:;*\"!\r\t\\/)({}[|@<>$%Â«Â»\342\200\230\342\200\231\342\200\234\342\200\235";
		$string = strtr( $string , $regs , preg_replace("/./", " " , $regs ) );//non alphanum chars cleaning
		$tokens = preg_split("/[\s]+/", $string );//Separate string in tokens
		return $tokens;	
	}

	/**
	 * Function to split a string into tokens
	 * @param $string the string to be clean and word count
	 * @param $regs the regs used to clean the string
	 * @return an array with for each word its count
	 * @access private
	 */
	protected function _cleanAndcountTokens ($string, $regs=0) 
	{
		$tokens = $this->_splitInTokens($string,$regs);
		$indexs = array();//Array of each word weight for this field
		foreach ($tokens as $token) {
			//particular case : two letter acronym or initials
			if (preg_match ("/([A-Z][0-9A-Z]{1,2})/", $token) || strlen ($token) > 3) {
				//little hack because oe ligature is not supported in ISO-latin!!
				$token = strtolower (str_replace (array ("\305\223", "\305\222"), array ("oe", "OE"), $token));
				$token = makeSortKey($token); // clean accents
				if(!isset($indexs[$token])) $indexs[$token]=0;
				$indexs[$token] ++; //simply count word number
				/*require_once("class.stemmer.inc.php");
				$stemmer = new Stemmer();
				$token = $stemmer->stem($token);*/
			}
		}
		return $indexs;
	}

	/**
	 * Generic function to index relations of type entries and persons : E and G relations
	 * @param $id the id of the entity
	 * @param $nature the nature of the relation
	 * @param $daoIndex the DAO object
	 * @access private
	 */
	protected function _indexEntitiesRelations ($id, $nature, $daoIndex) 
	{
		global $db;
		$id = (int)$id;
		if (!$id) return false;
		if ($nature != 'E' && $nature != 'G') return false;
		if ($nature == 'G') { $table1 = 'persons'; $table2 = 'person';}
		if ($nature == 'E') { $table1 = 'entries'; $table2 = 'entry';}

		//build query to select the right fields to index for the entry or the person
		$sql = "SELECT DISTINCT tf.name, tf.weight, e.id, t.class
				FROM #_TP_relations as r,#_TP_$table1 as e, #_TP_".$table2."types as t
				INNER JOIN #_TP_tablefields as tf ON tf.class=t.class
				WHERE r.id2= e.id AND r.id1='$id' AND r.nature='$nature' 
				AND t.id=e.idtype
				AND tf.weight > 0";
		$result = $db->execute (lq ($sql)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		$arr = array();
		while (!$result->EOF) {
			$row = $result->fields;
			$arr[$row['class']]['id'][$row['id']] = $row['id'] ;
			$arr[$row['class']]['fieldname'][$row['name']] = $row['weight'];
			$result->moveNext();
		}
		foreach($arr as $classe => $values)	{
			$cols = implode (",", array_keys ($values['fieldname']));
			$sql2 = "SELECT $cols FROM #_TP_".$classe." WHERE id$table2 ".sql_in_array($values['id']);
			$result2 = $db->execute (lq ($sql2)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			while (!$result2->EOF) {
				foreach ($result2->fields as $field => $value)
					$this->_indexField ($id, $value, $field, $values['fieldname'][$field], $daoIndex, $classe.".");
				$result2->moveNext ();
			}
		}
		//can't do the following above because I must select idrelation and not id from the table
		if ($nature == 'G') //special for nature G, get field from entities_$class table to index
		{
			$table2='relation';
			$sql = "SELECT DISTINCT tf.name, tf.weight, tf.class, r.idrelation AS id 
					FROM #_TP_relations AS r, #_TP_tablefields AS tf, #_TP_persontypes as t, #_TP_persons as p
					WHERE r.nature='G' AND r.id1='$id' AND tf.weight > 0 AND t.id=p.idtype AND tf.class=CONCAT('entities_',t.class)";
			$result = $db->execute(lq($sql)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			$arr = array();
			while (!$result->EOF) {
				$row = $result->fields;
				$arr[$row['class']]['id'][$row['id']] = $row['id'] ;
				$arr[$row['class']]['fieldname'][$row['name']] = $row['weight'];
				$result->MoveNext();
			}
			foreach ($arr as $classe => $values) {
				$cols = implode (",", array_keys ($values['fieldname']));
				$sql2 = "SELECT $cols FROM #_TP_".$classe." WHERE id$table2 ".sql_in_array ($values['id']);
				$result2 = $db->execute (lq ($sql2)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				while (!$result2->EOF)	{
					foreach ($result2->fields as $field => $value)
						$this->_indexField($id,$value,$field,$values['fieldname'][$field],$daoIndex,$classe.".");
					$result2->moveNext();
				}
			}
		}//end if nature==G
	}

}//end of class
?>
