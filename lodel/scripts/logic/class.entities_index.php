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
 *  Logic Entities_Index
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
    *  add an object to the index
    * needed parameters :
    * 		- object id
    */
  function addIndexAction(&$context,&$error)
  {
    
    global $db;
    //no object identity specified
    $id = $context['id'];
   	if (!$id)
   		die("ERROR: give the id ");
   	 	
   	//if this entity is already indexed ==> clean
   	$this->deleteIndexAction($context,$error);
   	 	
   	$sql = "SELECT e.id, class, search FROM #_TP_entities as e";
   	//join table type on idtype
   	$sql .= " INNER JOIN #_TP_types ON e.idtype=#_TP_types.id";
   	//where
   	$sql .= " WHERE e.id='$id'";
   	$row = $db->getRow(lq($sql)) ;
   	   	
   	if (!$row['id'])
 		die("ERROR: can't find object $id ".$dao_temp->table);
 	
 	$class= $row['class'];
 	if (!$class) 
 		die("ERROR: idtype is not valid in Entities_IndexLogic::addIndexAction");
 	//if the field search is not equal to 1, dont index the entity
 	if($row['search'] != 1)
 			return "_back";
 	
 	#echo "id=$id;class=$class";
 	
 	//get the fieldnames list to index
 	$dao_fields = &getDAO("tablefields");
 	$vos_fields = array();
 	$vos_fields = $dao_fields->findMany("class='$class' AND weight > 0","weight DESC","id,weight,name");
 	#print_r($vos_fields);
 	
 	//no fields to index --> return
 	if(!$vos_fields) 
 		return ("_back");
 	
 	//foreach field to index, index the words
 	//first get the vo
 	//$dao = &getDAO($class);
 	//$vo = $dat->getById($id);
 	
 	$sql = "SELECT * FROM #_TP_$class WHERE identity='$id'";
 	$row = $db->getRow(lq($sql)) ;
 	#echo lq($sql);
 	if(!$row)
 		die("ERROR: can't find object $id in table ".lq("#_TP_$class"));
 	 	
 	foreach( $vos_fields as $vo_field)
 	{
 		$string = $row[$vo_field->name]; //get the string of the current field
 		//$string = " ".preg_replace("/<[^>]*>/"," ",$string)." ";
 		$string = preg_replace("/<[^>]*>/","",$string);//HTML tags cleaning
 		$string = $this->_decode_html_entities($string); //HTML Entities decode
 		//include utf8 quotes at the end
 		$regs = "'\.],:;*\"“!\r\t\\/)({}[|@<>$%Â«Â»\342\200\230\342\200\231\342\200\234\342\200\235";
 	#echo "regs=$regs";
 		$string = strtr( $string , $regs , preg_replace("/./", " " , $regs ) );//non alphanum chars cleaning
 	#echo "string=$string<br />\n";
 		$tokens = preg_split("/[\s]+/", $string );//Separate string in tokens
 		$indexs = array();//Array of each word weight for this field
 		while(list(, $token) = each($tokens))
 		{
 		  //particular case : two letter acronym or initials
 		  if(preg_match("/([A-Z][0-9A-Z]{1,2})/",$token) || strlen($token) > 3)
 		  {
	 	     //little hack because oe ligature is not supported in ISO-latin!!
	 	    $token = strtolower(str_replace(array("\305\223","\305\222"),array("oe","OE"),$token));
 		  	$token = makeSortKey($token);
	 	    /*require_once("class.stemmer.inc.php");
	 	    $stemmer = new Stemmer();
	 	    $token = $stemmer->stem($token);*/
	 	    $indexs[$token] ++; //simply count word number
	 	  }
 		}
 		$vos_index = array();
 		
 		//index ponderation and virtual object creation
 		//and update database with dao and vo (or manually)
 		$dao_index = &getDAO("search_engine");
 		foreach($indexs as  $key => $index)
 		{
 		  $dao_index->instantiateObject($vo_index);
 		  $vo_index->identity = $id;
 		  $vo_index->tablefield = $vo_field->name;
 		  $vo_index->word = addslashes($key);
 		  $vo_index->weight = $indexs[$key] * $vo_field->weight; //ponderation with field weight
 		  $dao_index->save($vo_index,true);
 		}
 		
 	}//end of foreach fields
 
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
   * rebuild the index
   * 
   */
  function rebuildIndexAction(&$context,&$error)
  {
  	global $db;
  	//TODO
  	//recuperer les id des entites indexable (par rapport aux types) et non indexées
  	// prendre un groupe de 10 et les indexées
  	// ou bien récuperer le timout et tant que on a pas atteint 80% du timeout continuer
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
			//index
			$context["id"] = $result->fields['id'];
			$this->addIndexAction($context,$error);
			$current = time();
			//echo "currenttime=".($current - $start)."<br />";
			if(($current - $start) < $prudent_timeout)
				$result->MoveNext();
			else
			{
				//80% du timeout est dépassé, il faut rediriger.
				//header
				header("Location: index.php?do=rebuildIndex&lo=entities_index");
			}	  		
  	}
   	return "_back";
  	
  	 
  }
  
  /** Private function
   *  Description : decode HTML entities, 
   */
  function _decode_html_entities($text) 
  {
    //$text= html_entity_decode($text,ENT_QUOTES,"ISO-8859-1"); #NOTE: UTF-8 does not work!
    //$text= html_entity_decode($text,ENT_QUOTES,"UTF-8"); 
	$text= preg_replace('/&#(\d+);/me',utf8_encode("chr(\\1)"),$text); #decimal notation
	$text= preg_replace('/&#x([a-f0-9]+);/mei',utf8_encode("chr(0x\\1)"),$text);  #hex notation
    return $text;
  }
 

}//end of class

?>