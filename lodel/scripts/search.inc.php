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
 
 /**
  * Search script
  * @author Jean Lamy
  * @since 2005-02-02
  * 
  */
  
/**
 * search
 * needs following parameters
 * 	- query : query string
 * 	- type (optional) : specific type
 * 	- status (optional) : specific status
 * 
 */  
function search($context)
{
	//TODO
	//if decided change the system in order to manage strict queries (with quotes)
	
	
	require_once("../../../lodel/scripts/dao.php");
	global $db;
	require_once("func.php");
	if(!$context['query'])
		return;
	$query = $context['query'];
	//non alphanum chars cleaning
   	 //include utf8 quotes at the end
   	 $regs = "'\.],:*\"!\r\t\\/){}[|@<>$%Â«Â»\342\200\230\342\200\231\342\200\234\342\200\235";
#echo "regs=$regs";
   	 $query = strtr( $query , $regs , preg_replace("/./", " " , $regs ) );
#echo "string=$string<br />\n";
   	 //particular case : two letter acronym or initials
   	 $query = preg_replace("/ ([A-Z][0-9A-Z]{1,2}) /", ' \\1___ ', $query);
	$query = strtolower( $query );
			
	//cut query string in token
	$tokens = preg_split("/[\s]+/", $query );
#print_r($tokens);
	 
	/* we is an array that contains :
	 * 	- key : entity identifier
	 * 	- value : weight calculated
	 */
	$we = array();
	while(list(, $token) = each($tokens))
	{
		if(trim($token)=="") //if token is empty or just whitespace --> not search it !
			continue;
		
		if($token[0]=='-')
		{
			$cond = "exclude";
			$token = substr($token,1);
		}
		elseif($token[0]=="+")
		{
			$cond = "include";
			$token = substr($token,1);
		}
		else
			$cond ="";
		
		
		$token = removeaccents($token);
		//foreach word search entities that match this word
		$dao = &getDAO("search_engine");
		
		//Added stemmer
		/*require_once("class.stemmer.inc.php");
   	 	$stemmer = new Stemmer();
   	 	$token = $stemmer->stem($token); */
		
		
		$criteria_index = "word LIKE '$token%'";
		if($context["q_field"] && $context["q_field"]!="")
		{
			//added by Jean Lamy - get all tablefields for q_field specified
			$dao_dc_fields = &getDAO("tablefields");
			$vos_dc_fields = $dao_dc_fields->findMany("g_name='".$context["q_field"]."'");
			$field_in = array();
			foreach($vos_dc_fields as $vo_field)
				$field_in[] = "'".$vo_field->name."'";
			//$criteria_index .= "AND tablefield='".$context["q_field"]."'";
			if(count($field_in) > 0)
			$criteria_index .= "AND tablefield IN (".implode(",",$field_in).")";
	#echo $criteria_index;
		}
		$vos = $dao->findMany($criteria_index);
		
		$we_temp = array();
		foreach($vos as $vo)
		{
			$dao_entity = &getDAO("entities");
		#echo $vo->identity." ";
			$flag_add = true;
			//if a particular type or status has been selected, must exclude entities that don't match this choice
			if( $context["q_type"]!="" )
			{
				$vo_entity = $dao_entity->getById($vo->identity);
				if($vo_entity && $vo_entity->idtype != intval($context["q_type"]) )
					$flag_add = false;
			}
			if( $context["q_status"]!="" )
			{
				$vo_entity = $dao_entity->getById($vo->identity);
				if($vo_entity && $vo_entity->status != intval($context["q_status"]) )
					$flag_add = false;	
			}
			
			if($flag_add)
				$we_temp[$vo->identity] += $vo->weight;
			
			
			
		}//end foreach
		
		if( $cond == "")
		{
			//$we = array_merge($we,$we_temp);
			foreach($we_temp as $id=> $weight)
			{
				if($we[$id])
					$we[$id] += $weight;
				else
					$we[$id] = $weight;
			}
		}
		elseif( $cond=="exclude" )
		{
			/*echo "$cond";
			print_r($we);
			print_r($we_temp);*/
			foreach($we_temp as $id=> $weight)
			{
				if($we[$id])
					unset($we[$id]);
				
			}
			//$we = array_diff($we,$we_temp);
		}
		elseif ($cond == "include" )
		{
			/*echo "$cond";
			print_r($we);
			print_r($we_temp);*/
			if( count($we) > 0 )
			{	foreach($we as $id=>$weight)
				{
					if($we_temp[$id])
						$we[$id] += $we_temp[$id];
					else
						unset($we[$id]);
				}
			}
			else
			{
				foreach($we_temp as $id=> $weight)
				{
					$we[$id] = $weight;
				}
			}
		}
	}
	
	
	
	
	
	/*
	echo "<br />----------------------------<br />";
	print_r($we);
	echo "<br />----------------------------<br />";
	*/
	//TODO : sort array we by value
	asort($we,SORT_NUMERIC);
	$we = array_reverse($we,true);
	return $we;
		
	
	
		
		
} 
function removeaccents($string) 
{
 	$string = utf8_decode($string);
 	return utf8_encode(strtr($string, "ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ", "SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy"));
}
/*
 * NOTA BENE :
 * 	Avec le tableau res, on peut par une boucle LODELSCRIPT afficher chaque resultat.
 * 
 */
function loop_search_public(&$context,$funcname)
{
	
}
function loop_search(&$context,$funcname,$arguments)
{
	$local_context = $context;
	$results = search($local_context);
	$count = 0;
	if(!$results || count($results)==0)
	{
		//echo "coucou";
		$local_context["nbresults"] = 0;
		call_user_func("code_alter_$funcname",$local_context);
		return;
	}
	
	$local_context["nbresults"] = count($results);
	if(function_exists("code_before_$funcname"))
		call_user_func("code_before_$funcname",$local_context);
	
	foreach($results as $key => $weight)
	{
		
		$dao2 = &getDAO("entities");
		$vo = $dao2->getById($key);
		//print_r($vo);
		$local_context["id"] = $key;
		$local_context["weight"] = $weight;
		$local_context["idparent"] = $vo->idparent;
		$local_context["idtype"] = $vo->idtype;
		$dao_type = &getDAO("types");
		$vo_type = $dao_type->getByID($vo->idtype);
		$local_context["type"] = $vo_type->type;
		//added information on tpledition
		$local_context["tpledition"] = $vo_type->tpledition;
		$local_context["g_title"] = $vo->g_title;
		$local_context["status"] = $vo->status;
		$local_context["identifier"] = $vo->identifier;
		$local_context["creationdate"] = $vo->creationdate ;
		$local_context["iduser"] = $vo->iduser ;
		$local_context["usergroup"] = $vo->usergroup ;
		$local_context["modificationdate"] = $vo->modificationdate ;
		$local_context["creationmethod"] = $vo->creationmethod ;
		$local_context["rank"] = $vo->rank ;
		$local_context["upd"] = $vo->upd ;
		$local_context["count"] = $count;
		//To complete
		call_user_func("code_do_$funcname",$local_context);
		$count++;
	}
	if(function_exists("code_after_$funcname"))
		call_user_func("code_after_$funcname",$local_context);
		
}

/**
 * Results page script - Lodel part
 * 
 */




require_once("view.php");
$view=&getView();
$base="search";
if($_GET["query"])	
	$context["query"] = $_GET["query"];
if($_GET["q_type"])
	$context["q_type"] = $_GET["q_type"];
if($_GET["q_status"])
	$context["q_status"] = $_GET["q_status"];
if($_GET["q_field"])
	$context["q_field"] = $_GET["q_field"];
	
	
recordurl();	
$view->renderCached($context,$base);
return;

 ?>