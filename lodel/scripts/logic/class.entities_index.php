<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-
 * Beccot, Bruno Cénou, Jean Lamy
 * 
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
 
/**
 * Entities_Index logic.
 * Define actions for the search engine
 * 
 * 
 */ 
class Entities_IndexLogic extends Logic 
{
  /**
   * generic equivalent assoc array
   */
  var $g_name;
  /** 
	 * Constructor
   */
  function Entities_IndexLogic() 
  {
    $this->Logic("search_engine");
  }
   	
	/**
	 * Add an object to the search_engine. An object is added only its type must be indexed 
	 * and if its fields have weight defined > 0
	 * 
	 */ 
  function addIndexAction(&$context,&$error)
  {
    global $db;
    //no object identity specified
    $id = $context['id'];
   	if (!$id) die("ERROR: no id given");
   	//if this entity is already indexed ==> clean
   	$this->deleteIndexAction($context,$error);
   	 	
   	$sql = "SELECT e.id, class, search FROM #_TP_entities as e";
   	//join table type on idtype
   	$sql .= " INNER JOIN #_TP_types ON e.idtype=#_TP_types.id";
   	//where
   	$sql .= " WHERE e.id='$id'";
   	$row = $db->getRow(lq($sql)) ;
   	if (!$row['id']) die("ERROR: can't find object $id ".$dao_temp->table);
 	
 		$class= $row['class'];
 		if (!$class)die("ERROR: idtype is not valid in Entities_IndexLogic::addIndexAction");
 		//if the field search is not equal to 1, dont index the entity
 		if($row['search'] != 1)return "_back";
 	
 	#echo "id=$id;class=$class";
 	
	 	//get the fieldnames list to index
	 	$dao_fields = &getDAO("tablefields");
	 	$vos_fields = array();
	 	$vos_fields = $dao_fields->findMany("class='$class' AND weight > 0","weight DESC","id,weight,name");
 	#	print_r($vos_fields);
 	
	 	//no fields to index --> return
	 	if(!$vos_fields)	return ("_back");
 	
 		$sql = "SELECT * FROM #_TP_$class WHERE identity='$id'";
	 	$row = $db->getRow(lq($sql)) ;
	 #echo lq($sql);
	 	if(!$row)	die("ERROR: can't find object $id in table ".lq("#_TP_$class"));
 		$daoIndex = &getDAO("search_engine"); 	
	 	foreach( $vos_fields as $vo_field)
	 		$this->_indexField($id,$row[$vo_field->name],$vo_field->name,$vo_field->weight,$daoIndex);
		
		$this->_indexEntitiesRelations($id,'E',$daoIndex);
		//fonctionne pas pour les personnes apparemment
		$this->_indexEntitiesRelations($id,'G',$daoIndex);
		return "_back";
  }
 
 /**
  * delete an objet from the index
  * needed parameters
  * 	- object id
  */
  function deleteIndexAction(&$context,&$error)
  {
    $id = $context["id"];
    if(!$id)
      die("ERROR: give the id ");
   	$dao = &getDAO("search_engine");
  	if($dao->deleteObjects("identity='$id'"))//delete all lines with identity=id and return
  	  return "_back";
  	else
  	  return "_error";
   }
  
  /**
   * clean the index of all objet
   */
  function cleanIndexAction(&$context,&$error)
  {
    $dao = &getDAO("search_engine");
  	$dao->deleteObjects("1");    //delete all index lines and return
  	return "_back";
  }
  
  /**
   * Rebuild entirely the Index
   * 
   */
  function rebuildIndexAction(&$context,&$error)
  {
  	global $db;
  	$timeout = ini_get("max_execution_time");
  	$prudent_timeout = $timeout*0.8;
  	$start = time();
  	//boucle sur toutes les entites a indexer.
  	$sql = "SELECT e.id,t.class,t.search from #_TP_entities e,#_TP_types t";
  	$sql .=" LEFT OUTER JOIN #_TP_search_engine se ON e.id=se.identity ";
  	$sql .=" WHERE se.identity is null AND t.id=e.idtype AND t.search=1";
  	$result=$db->execute(lq($sql));
  	while (!$result->EOF) 
  	{
			//print_r($result->fields);
			$context["id"] = $result->fields['id'];
			$this->addIndexAction($context,$error);
			$current = time();
			if(($current - $start) < $prudent_timeout)
				$result->MoveNext();
			else
			{
				//80% du timeout est dépassé, il faut rediriger.
				header("Location: index.php?do=rebuildIndex&lo=entities_index");
			}	  		
  	}
   	return "_back";
  }
  
  /**
	 * index a given field in the database (using dao_index)
	 * @param $id : entity database identifier
	 * @param $fieldValue : the value of the field
	 * @param $fieldName : the name of the field
	 * @param $fieldWeight : the weight used to ponderate the field
	 * @param $daoIndex : the dao to use to save the data
	 * @param $prefixtablefield : empty by default but used to prefix the field 'tablefield' (for entries or persons for example)
	 * 
	 */
	function _indexField($id,$fieldValue,$fieldName,$fieldWeight,$daoIndex,$prefixtablefield="")
	{
		if(!$fieldValue)
			return;
		
		$fieldValue = preg_replace("/<[^>]*>/"," ",$fieldValue);//HTML tags cleaning
	 	$fieldValue = $this->_decode_html_entities($fieldValue); //HTML Entities decode
		$indexs = $this->_cleanAndcountTokens($fieldValue); //clean and count tokens
		
		foreach($indexs as  $key => $index)
	 	{
			$daoIndex->instantiateObject($voIndex);
		 	$voIndex->identity = $id;
		 	$voIndex->tablefield = $prefixtablefield.$fieldName;
		 	$voIndex->word = addslashes($key);
		 	$voIndex->weight = $indexs[$key] * $fieldWeight; //ponderation with field weight
		 	#print_r($voIndex);
		 	$daoIndex->save($voIndex,true);
		}
	}
    
  /** Private function
   *  Description : decode HTML entities,
   * @param $text the text where HTML entities must be decoded
   * @return $text the text with HTML entities decoded
   *  
   */
  function _decode_html_entities($text) 
  {
    //$text= html_entity_decode($text,ENT_QUOTES,"ISO-8859-1"); #NOTE: UTF-8 does not work!
    //$text= html_entity_decode($text,ENT_QUOTES,"UTF-8"); 
		$text= preg_replace('/&#(\d+);/me',utf8_encode("chr(\\1)"),$text); #decimal notation
		$text= preg_replace('/&#x([a-f0-9]+);/mei',utf8_encode("chr(0x\\1)"),$text);  #hex notation
    return $text;
  }
  
  /**
   *  Split a string into tokens by given regs
   * @param $string the string to be splitted
   * @param $regs the regs used to split the string
   * @return an array of tokens
   */
	function _splitInTokens($string,$regs=0)
  {
  	if(!$regs)
 			$regs = "'\.],:;*\"“!\r\t\\/)({}[|@<>$%Â«Â»\342\200\230\342\200\231\342\200\234\342\200\235";
 		$string = strtr( $string , $regs , preg_replace("/./", " " , $regs ) );//non alphanum chars cleaning
 		#echo "string=$string<br />\n";
 		$tokens = preg_split("/[\s]+/", $string );//Separate string in tokens
 		return $tokens;	
  }

  /**
   * Function to split a string into tokens
   * @param $string the string to be clean and word count
   * @param $regs the regs used to clean the string
   * @return an array with for each word its count
   * 
   */
 	function _cleanAndcountTokens($string,$regs=0)
 	{
 		$tokens = $this->_splitInTokens($string,$regs);
 		$indexs = array();//Array of each word weight for this field
 		while(list(, $token) = each($tokens))
 		{
 		  //particular case : two letter acronym or initials
 		  if(preg_match("/([A-Z][0-9A-Z]{1,2})/",$token) || strlen($token) > 3)
 		  {
	 	     //little hack because oe ligature is not supported in ISO-latin!!
	 	    $token = strtolower(str_replace(array("\305\223","\305\222"),array("oe","OE"),$token));
 		  	$token = makeSortKey($token); // clean accents
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
 	 */
	function _indexEntitiesRelations($id,$nature,$daoIndex)
	{
		global $db;
		if(!$id)return false;
		if($nature!='E' && $nature!='P' && $nature!='G')return false;
		if($nature=="G"){	$table1 = "persons";$table2 = "person";}
		if($nature=="P"){	return ;} // don't know how to do it
		if($nature=="E"){	$table1 = "entries";$table2 = "entry";}
		
		//build query to select the good fields to index for the entry or the person
		if($nature=='G' || $nature=='E')
		{
			$sql = "SELECT DISTINCT tf.name, tf.weight, e.id, t.class
					FROM #_TP_relations as r,#_TP_$table1 as e, #_TP_".$table2."types as t
					INNER JOIN #_TP_tablefields as tf ON tf.class=t.class
					WHERE r.id2= e.id AND r.id1='$id' AND r.nature='$nature' 
					AND t.id=e.idtype
					AND tf.weight > 0
					ORDER BY e.id";
		}
	
		
		#echo "sql=".lq($sql)."<br />";
		$result = $db->execute(lq($sql));
		while($result && !$result->EOF)
		{
			$row = $result->fields;
			$sql2 = "SELECT ".$row['name']." FROM #_TP_".$row['class']." WHERE id".$table2."=".$row['id'];
			#echo "<pre>sql2=".lq($sql2)."<br /></pre>";
			$field = $db->getOne(lq($sql2));
			$this->_indexField($id,$field,$row['name'],$row['weight'],$daoIndex,$row['class'].".");
			$result->moveNext();
		}
		
		//can't do the following above because I must selection idrelation and not id from the table
		if($nature=='G') //special for nature G, get field from entities_$class table to index
		{
			$table2='relation';
			$sql = "SELECT DISTINCT tf.name, tf.weight, tf.class, r.idrelation AS id 
							FROM #_TP_relations AS r, #_TP_tablefields AS tf, #_TP_persontypes as t, #_TP_persons as p
							WHERE r.nature='G' AND r.id1='$id' AND tf.weight > 0 AND t.id=p.idtype AND tf.class=CONCAT('entities_',t.class)" .
							" ORDER BY r.id";
			#echo "<pre>sql=".lq($sql)."</pre><br />";
			$result = $db->execute(lq($sql));
			while($result && !$result->EOF)
			{
				$row = $result->fields;
				$sql2 = "SELECT ".$row['name']." FROM #_TP_".$row['class']." WHERE id".$table2."=".$row['id'];
				#echo "<pre>sql2=".lq($sql2)."<br /></pre>";
				$field = $db->getOne(lq($sql2));
				$this->_indexField($id,$field,$row['name'],$row['weight'],$daoIndex,$row['class'].".");
				$result->MoveNext();
			}
		}//end if nature==G
	}
	
}//end of class

?>