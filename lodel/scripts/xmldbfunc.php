<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 *
 *  Home page: http://www.lodel.org
 *
 *  E-Mail: lodel@lodel.org
 *
 *                            All Rights Reserved
 *
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.*/



class XMLDB {

  var $tp;           // table prefix
  var $tables;       // contains the table to includes and information of relationshiop,element,tags
  var $documentroot; // documentroot
  var $header;

  // for the XML parser
  //
  var $state;
  var $tablestack;
  var $currentrecord;
  var $rows;
  var $data;
  var $joinfieldvaluestack;


  /* Constructor
   *
   */
  function XMLDB ($documentroot="",$tableprefix="")
  {
    $this->documentroot=$documentroot;
    $this->tp=$tableprefix;
  }

  /*
   *
   */
  function addTable()

  {
    foreach(func_get_args() as $table) {
#      if (is_array($table)) {
#	list($table,$tablename)=$table;
#      } else {
	$tablename=$table;
#      }
	$this->tables[$table]=array('element'=>array(),'attr'=>array(),'where'=>array(),'join'=>array());
      $this->tables[$table]['rowtag']="row";
    }
  }

  function setRowtag($table,$rowtag)
  {
    $this->tables[$table]['rowtag']=$rowtag;
  }

  function addWhere()
  {
    $table=func_get_arg(0);
    $numargs = func_num_args();
    for($i=1; $i<$numargs; $i++) {
      $this->tables[$table]['where'][]=func_get_arg($i);
    }
  }

  /*
   * Add Element
   * First argument is the tablename
   * Others arguments are elements. If argument is an array, it contains the field and the element name. 
   * If argument is a string, both element and field have the same name
   */
  function addElement()
  {
    $table=func_get_arg(0);
    $numargs = func_num_args();
    for($i=1; $i<$numargs; $i++) {
      $arg=func_get_arg($i);
      if (is_array($arg)) {
	list($field,$el)=$arg;
	$this->tables[$table]['element'][$field]=$el;
      } else {
	$this->tables[$table]['element'][$arg]=$arg;
      }
    }
  }

  /*
   * Add attribut
   * First argument is the tablename
   * Others arguments are elements. If argument is an array, it contains the field and the element name. 
   * If argument is a string, both element and field have the same name
   */

  function addAttr()

  {
    $table=func_get_arg(0);
    $numargs = func_num_args();
    for($i=1; $i<$numargs; $i++) {
      $arg=func_get_arg($i);
      if (is_array($arg)) {
	list($field,$attr)=$arg;
	$this->tables[$table]['attr'][$field]=$attr;
      } else {
	$this->tables[$table]['attr'][$arg]=$arg;
      }
    }
  }

  function addJoin($tableparent,$parentfield, $tablechild, $childfield)
  {
    $this->tables[$tableparent]['join'][$tablechild]=$parentfield;
    $this->tables[$tablechild]['joinfield']=$childfield;
    $this->tables[$tablechild]['child']=true;
  }

  function addHeader($xml) {
    $this->header.=$xml;
  }

  /*******************************************/
  /* Methods to create XML file              */
  /*******************************************/

  /*
   * Write the XML into a file
   */
  function saveToFile($filename) 

  {
    $this->fp=fopen($filename,"w");
    if (!$this->fp) die("ERROR: can't open filename $filename for writing");
    $this->saveToString();
  }

  /*
   *
   */

  function saveToString()
  {
    $this->string="";
    $this->_write("<".$this->documentroot.">\n");
    if ($this->header) $this->_write("<header>".$this->header."</header>\n");
    foreach ($this->tables as $table=>$info) {
      if ($info['child']) continue; # will be processed in with its parent
      $this->exporttable($table,$info);
    }
    $this->_write("</".$this->documentroot.">");
    return $this->string;
  }

  /*
   *
   */
  function exporttable($table,$info,$joinfieldvalue="")

  {
    global $db;
    //
    // select
    $select=join(",".$this->tp.$table.".",array_merge(array_keys($info['element']),array_keys($info['attr'])));
    if (!$select) return;
    $select=$this->tp.$table.".".$select;

    //
    // join
    $join=array();
    if ($info['joinfield']) {
      $join[]=$info['joinfield']."='".$joinfieldvalue."'";
    }
    //
    // where and join
    if ($info['where']) $where=" WHERE ".join(" AND ",array_merge($info['where'],$join));


    //
    // Query

    $result=$db->execute(lq("SELECT $select FROM ".$this->tp.$table.$where)) or die($db->errormsg());
    
    if ($result->recordcount()<=0) return;

    $this->_write("<$table>\n");

    if (count($info['element'])==1 && !$info['join']) {
      $rowtag=reset($info['element']);
      $elementtag=false;
    } else {
      $rowtag=$info['rowtag'];
      $elementtag=true;
    }

    while (!$result->EOF) {
      $row=$result->fields;
      // information for the table
      $this->_write("<$rowtag");
      foreach ($info['attr'] as $field=>$attr) {
	$this->_write(" ".$attr.'="'.htmlspecialchars($row[$field]).'"');
      }
      if (!$info['element'] && !$info['join']) {
	$this->_write("/>\n");
	continue;
      }
      $this->_write(">");
      foreach ($info['element'] as $field=>$el) {
	if ($elementtag) $this->_write("<$el>");
	$this->_write(htmlspecialchars($row[$field]));
	if ($elementtag) $this->_write("</$el>\n");
      }

      // export child table
      foreach ($info['join'] as $childtable=>$joinfield) {
	$this->exporttable($childtable,$this->tables[$childtable],$row[$joinfield]);
      }

      $this->_write("</$rowtag>\n");
      $result->MoveNext();
    }
    $this->_write("</$table>\n");
  }

  /*
   * generic output function
   */

  function _write($string)

  {
    if ($this->fp) {
      fwrite($this->fp,$string);
    } else {
      $this->string.=$string;
    }
  }


  /*******************************************/
  /* Methods to real XML file                */
  /*******************************************/

  function readFromString($xml)

  {

    $xml_parser=$this->_initparser();

    if (!xml_parse($xml_parser, $xml, true)) {
      die(sprintf("XML error: %s at line %d",
		  xml_error_string(xml_get_error_code($xml_parser)),
		  xml_get_current_line_number($xml_parser)));
    }
  }


  function readFromFile($filename) {

    $xml_parser=$this->_initparser();

    if (!($fp = fopen($filename, "r"))) {
      die("ERROR: could not open XML input");
    }

    while ($data = fread($fp, 4096)) {
      if (!xml_parse($xml_parser, $data, feof($fp))) {
	die(sprintf("XML error: %s at line %d",
		    xml_error_string(xml_get_error_code($xml_parser)),
		    xml_get_current_line_number($xml_parser)));
      }
    }
    xml_parser_free($xml_parser); 
  }


  function _initparser() {
    $xml_parser = xml_parser_create();
    $this->state="";
    $this->tablestack=array();
    $this->joinfieldvaluestack=array();

    xml_set_object($xml_parser,$this);
    xml_parser_set_option($xml_parser,XML_OPTION_CASE_FOLDING,false);
    xml_set_element_handler($xml_parser, "startElement", "endElement");
    xml_set_character_data_handler($xml_parser, "characterData");

    return $xml_parser;
  }


  function insertRow($currentable,$rows)

  {
    die("Redefined insertRow in a child class. insertRow must return the field used for joining");
    return null;
  }

  /*
   * @internal
   * XML Parser handler
   */

  function startElement($parser, $name, $attrs) 
  {
    #echo "<br/>startElement $name ".$this->state."    $currenttable<br/>";

    $currenttable=$this->tablestack[0];

    switch($this->state) {
    case "inrecord":
      die("ERROR: Invalid XML. Expecting data only");
      break;
    case "record":
      // going into a element ?
      if (in_array($name,$this->tables[$currenttable]['element'])) {
	// start a new record
	$this->_newrecord($name);
	$this->state="inrecord";

      } elseif ($this->tables[$currenttable]['join'] && 
		$this->tables[$currenttable]['join'][$name]) {
	// records a finish
	$this->_endrow();
	// start in the child table
	$this->_newtable($name);
	$this->state="row"; // look for a row now
	break;
      } else {
	// not good.
	die("ERROR: Invalid XML. Expecting for a element. Got &lt;$name&gt;");
      }
    break;
    case "row":
      //
      // add the attrs to the rows
      //
      $this->records=array();
      foreach ($attrs as $attrname=>$val) {
	$field=array_search($attrname,$this->tables[$currenttable]['attr']);
	if (!$field) die("ERROR: Invalid XML. Unexpected attribute $attrname in tag &lt;$name&gt;");
	$this->records[$field]=$val;
      }
      //
      // add the parent join field if we are a child
      //
      if ($this->tables[$currenttable]['child']) {
	#echo "---> $currenttable   ".$this->tables[$currenttable]['joinfield']."\n<br>";
	#print_r($this->joinfieldvaluestack);
	$this->records[$this->tables[$currenttable]['joinfield']]=$this->joinfieldvaluestack[1];
      }

      //
      // row started but also start of the record (unique record)
      //
      #echo $currenttable,"<br>";
      #print_r($this->tables[$currenttable]);

      if ($this->tables[$currenttable]['norowelement']) {
	$tag=reset($this->tables[$currenttable]['element']);
	if ($name==$tag) {
	  $this->state="inrecord";
	  $this->_newrecord($name);
	} else {
	  die("ERROR: Invalid XML. Expecting &lt;$tag&gt; but got &lt;$name&gt;");
	}
      } else {
	if ($name==$this->tables[$currenttable]['rowtag']) {
	  $this->state="record";
	} else {
	  die("ERROR: Invalid XML. Expecting &lt;".$this->tables[$currenttable]['rowtag']."&gt; but got &lt;$name&gt;");
	}
      }
      break;

  case "table":
    if ($this->tables[$name]) {
      // start of a new table
      $this->state="row";
      $this->_newtable($name);
    } else {
      die("ERROR: Invalid XML. Expecting a table name. Found &lt;$name&gt;");
    }
    break;
  default:
    if ($name!=$this->documentroot) die("ERROR: Invalid XML. Expecting a documentroot. Found &lt;$name&gt;");
    $this->state="table";
  }
}




function endElement($parser, $name) {

  #echo "endElement $name ".$this->state."<br/>";
  $currenttable=$this->tablestack[0];


  switch($this->state) {
  case "inrecord" :
    if ($name==$this->currentrecord) {
      $field=array_search($name,$this->tables[$currenttable]['element']);
      $this->records[$field]=$this->data;
      $this->state="record";
      $this->_endrecord($name);

      if ($this->tables[$currenttable]['norowelement']) {
	$tag=reset($this->tables[$currenttable]['element']);
	if ($name==$tag) {
	  $this->_endrow();
	  $this->state="row";
	} else {
	  die("ERROR: XML Invalid. Hum... XML parser should have crash");
	}
      }
    } else {
      die("ERROR: Invalid XML. Expecting &lt;/".$this->currentrecord."&gt; element. Found &lt;/$name&gt;");
    }
    break;
  case "record" :
    // rowtag element
    if ($name==$this->tables[$currenttable]['rowtag']) {
      // finish recording
      if (!$this->tables[$currenttable]['join']) { // if join, the insertion has already been done
	$this->joinfieldvaluestack[0]=$this->insertRow($currenttable,$this->records);
      }
      $this->state="row";
    } else {
      die("EROR: XML Invalid. Expecting &lt;/".$this->tables[$currenttable]['rowtag']."&gt; element. Found &lt;/$name&gt;");
    }
    break;
  case "row":
    if ($this->tables[$currenttable]['norowelement']) {
      if ($name==$currenttable) {
	$this->_endtable();
	$this->state="table";
      } else {
	die("EROR: XML Invalid. Expecting &lt;/".$currenttable.".&gt; element. Found &lt;/$name&gt;");
      }
    } else {
      if ($name==$this->tables[$currenttable]['rowtag']) {
	// nothing to do
      } else {
	die("EROR: XML Invalid. Expecting &lt;".$this->tables[$currenttable]['rowtag']."&gt; element. Found &lt;/$name&gt;");
      }
    }
    break;
  case "table":
    if ($name==$currenttable) {
      $this->_endtable();
      $this->state="table";
    }
    break;
  default:
    // nothing to do...
  }			
}


  function _newrecord($name) {
    $this->currentrecord=$name;
    if (!$this->data) die("ERROR: data should be empty here");
  }

  function _endrecord($name) {
    $this->currentrecord="";
    $this->data="";
  }

  function _endrow() {
    $this->joinfieldvaluestack[0]=$this->insertRow($this->tablestack[0],$this->records);
    #echo "lala:",$this->tablestack[0],"   ",$this->joinfieldvaluestack[0],"   ";
  }

  function _newtable($table) {
    array_unshift($this->tablestack,$table);
    array_unshift($this->joinfieldvaluestack,null);
    // make easies to work with
    $this->tables[$table]['norowelement']=count($this->tables[$table]['element'])<=1 && !$this->tables[$table]['join'];

  }

  function _endtable() {
    array_shift($this->tablestack);
    array_shift($this->joinfieldvaluestack);  
  }

function characterData($parser, $data) {
  $this->data.=$data;
}


}


?>