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
 	 	//no object identity specified
   	 	if(!$context['identity'])
   	 		return "_back";
   	 	
   	 	$id = $context['identity'];
   	 	
   	 	//if this entity is already indexed ==> clean
   	 	$this->deleteIndexAction($context,$error);
   	 	
   	 	//get the entity class by the type
   	 	$dao_temp = &getDAO("entities");
   	 	$vo_temp = $dao_temp->find("id=$id");
   	 	$dao_types = &getDAO("types");
   	 	$vo_types = $dao_types->find("id=".$vo_temp->idtype);
   	 	$class= $vo_types->class; //Here is the class !!
   	 	if (!$class) 
   	 		die("ERROR: idtype is not valid in Entities_IndexLogic::addIndexAction");
   	 	//get the fieldnames list to index
   	 	$dao_groups = &getDAO("tablefieldgroups");
   	 	$dao_fields = &getDAO("tablefields");
   	 	
   	 	$vos_groups = $dao_groups->findMany("class='$class'","","id");
   	 	$vos_fields = array();
   	 	foreach( $vos_groups as $vo_group )
   	 	{
   	 		$tab = $dao_fields->findMany("idgroup=".$vo_group->id." AND weight > 0","weight DESC","id,weight,name");
   	 		if(count($vos_fields) == 0)
   	 			$vos_fields = $tab;
   	 		else
   	 			$vos_fields = array_merge($tab,$vos_fields);
   	 	}
	 	#print_r($vos_fields);
   	 	//no fields to index
   	 	if(!$vos_fields)
   	 		return ("_back");
   	 	
   	 	//foreach field to index, index the words
   	 	foreach( $vos_fields as $vo_field)
   	 	{
   	 		$dao = &getDAO($class);
   	 		$vo = $dao->find("identity=$id",$vo_field->name);
   	 		$field = $vo_field->name;
   	 		//get the string of the current field
   	 		$string = $vo->$field;
   	 		//HTML tags cleaning
   	 		$string = " ".preg_replace("/<[^>]*>/"," ",$string)." ";
   	 		require_once("utf8.php");
   	 		convertHTMLtoUTF8($string);
   	 		 
   	 	# echo "stringnonHTML=$string";
   	 		//non alphanum chars cleaning
   	 		//include utf8 quotes at the end
   	 		$regs = "'\.],:*\"!\r\t\\/){}[|@<>$%Â«Â»\342\200\230\342\200\231\342\200\234\342\200\235";
   	 	#echo "regs=$regs";
   	 		$string = strtr( $string , $regs , preg_replace("/./", " " , $regs ) );
   	 	#echo "string=$string<br />\n";
   	 		//particular case : two letter acronym or initials
   	 		$string = preg_replace("/ ([A-Z][0-9A-Z]{1,2}) /", ' \\1___ ', $string);
			$string = strtolower( $string );
			
			//Separate string in tokens
			$tokens = preg_split("/[\s]+/", $string );
   	 		//Array of each word weight for this field
   	 		$indexs = array();
   	 		while(list(, $token) = each($tokens))
   	 		{
   	 			
   	 			//convertHTMLtoUTF8($token);
   	 	#echo "token=$token<br />\n";
   	 			$token = removeaccents(($token));
   	 			if(strlen($token) > 3)
   	 			{
   	 				//simply count word number
   	 				/*require_once("class.stemmer.inc.php");
   	 				$stemmer = new Stemmer();
   	 				$token = $stemmer->stem($token);*/
   	 				$indexs[$token] ++;
   	 	#echo "token=$token<br />"; 	
   	 			}
   	 			
   	 			
   	 		}
   	 		$vos_index = array();
   	 		
   	 		//index ponderation and virtual object creation
   	 		$dao_index = &getDAO("search_engine");
   	 		foreach($indexs as  $key => $index)
   	 		{
   	 			$dao_index->instantiateObject($vo_index);
   	 			$vo_index->identity = $id;
   	 			$vo_index->tablefield = $vo_field->name;
   	 			$vo_index->word = addslashes($key);
   	 			$vo_index->weight = $indexs[$key] * $vo_field->weight; //ponderation with field weight
   	 			$vos_index[] = $vo_index;	
   	 		}
   	 		//update database with dao and vo (or manually)
   	 		
   	 		foreach($vos_index as $vo)
   	 			$dao_index->save($vo,true); // force creation of items
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
   	  		if(!$context["identity"])
   	  			return "_error";
   	  		$id = $context["identity"];
   	  		//get tue DAO for index
   	  		$dao = &getDAO("search_engine");
   	  		//delete all lines with identity=id and return
   	  		if($dao->deleteObjects("identity=$id"))
   	  			return "_back";
   	  		else
   	  			return "_error";
   	  		
   	  				
   	  }
   	  
   	  /**
   	   * clean the index of all objet
   	   */
   	   function cleanIndexAction(&$context,&$error)
   	   {
   	   		//get tue DAO for index
   	  		$dao = &getDAO("search_engine");
   	  	#print($dao);
   	  		//delete all index lines and return
   	  		if($dao->deleteObjects("1"))
   	  			return "_back";
   	  		else
   	  			return "_back";	
   	   }
}
function removeaccents($string) 
{
 	$string = utf8_decode($string);
 	return utf8_encode(strtr($string, "ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ", "SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy"));
} 
?>