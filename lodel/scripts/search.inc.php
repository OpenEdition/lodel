<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
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
	
	
	require_once("dao.php");
	global $db;
	require_once("func.php");
	if(!$context['query'])
		return;
	$query = $context['query'];
	//non alphanum chars cleaning
   	 //include utf8 quotes at the end
   	 $regs = "'\.],:\"!\r\t\\/){}[|@<>$%«»\342\200\230\342\200\231\342\200\234\342\200\235";
#echo "regs=$regs";
   	 $query = strtr( $query , $regs , preg_replace("/./", " " , $regs ) );
#echo "string=$string<br />\n";
   	 //particular case : two letter acronym or initials
   	 //$query = preg_replace("/\s([A-Z][0-9A-Z]{1,2})\s/", ' \\1___ ', $query);
	//$query = strtolower( $query );
			
	//cut query string in token
	$tokens = preg_split("/\s+/", $query );
#print_r($tokens);
	 
	/* we is an array that contains :
	 * 	- key : entity identifier
	 * 	- value : weight calculated
	 */
	$we = array();
	while(list(, $token) = each($tokens))
	{
		if($token == "") //if token is empty or just whitespace --> not search it !
			continue;
		
		if($token[0]=='-')
		{
			$cond = "exclude";
			$token = substr($token,1);
		}
		elseif($token[0]=='+')
		{
			$cond = "include";
			$token = substr($token,1);
		}
		else
			$cond ="";
		
		//if wildcard * used
		
		
		if( $token[strlen($token)-1] == '*' )
		{
			$end_wildcard = "%";
			$token = substr($token,0,strlen($token)-1);
		} 
		else
		{
			$end_wildcard = "";
			
		}
		if( $token[0] == '*')
		{
			$begin_wildcard = "%";
			$token = substr($token,1);
		}
		else
		{
			$begin_wildcard = "";
			
		}
		
	
		//$token = preg_replace("/([A-Z][0-9a-zA-Z]{1,2})$/", ' \\1___ ', $token);	
	#echo "token=$token";
		//little hack because oe ligature is not supported in ISO-latin!!
		$token = strtolower(str_replace(array("\305\223","\305\222"),array("oe","OE"),$token));
		$token = makeSortKey($token);
		//foreach word search entities that match this word
		$dao = &getDAO("search_engine");
		
		//Added stemmer
		/*require_once("class.stemmer.inc.php");
   	 	$stemmer = new Stemmer();
   	 	$token = $stemmer->stem($token); */
		
		
		$criteria_index = "word LIKE '$begin_wildcard$token$end_wildcard'";
		#echo "criteria_index=$criteria_index bim=$end_wildcard";
		$from = "#_TP_search_engine";
		if($context['qfield'] && $context['qfield']!="")
		{
			//added by Jean Lamy - get all tablefields for q_field specified
			$dao_dc_fields = &getDAO("tablefields");
			$vos_dc_fields = $dao_dc_fields->findMany("g_name='".addslashes($context['qfield'])."'");
			$field_in = array();
			foreach($vos_dc_fields as $vo_field)
				$field_in[] = $vo_field->name;
			//$criteria_index .= "AND tablefield='".$context["q_field"]."'";
			if($field_in)
			//$criteria_index .= "AND tablefield IN (".implode(",",$field_in).")";
				$criteria_index .=" AND tablefield ".sql_in_array($field_in);
			
			
		}
		if( $context['qtype']!=""  || $context['qstatus']!="")
		{
			$join = "INNER JOIN #_TP_entities ON #_TP_search_engine.identity = #_TP_entities.id";
		}
			 	
		if( $context['qtype']!="" )
		{
			$criteria_index .=" AND #_TP_entities.idtype ='".intval($context['qtype'])."'";	
		}
		if( $context['qstatus']!="")
		{
			$criteria_index .= " AND #_TP_entities.status ='".intval($context['qstatus'])."'";	
		}
		
		$limit = " LIMIT 0,100";
		$sql = lq("SELECT identity,weight FROM ".$from." ".$join." WHERE ".$criteria_index.$limit);
	#echo "hey :".$sql;
		$result=$db->execute($sql) or dberror();
		$we_temp = array();
		while(!$result->EOF)
		{
			$row = $result->fields;
			$we_temp[$row['identity']] += $row['weight'];
			$result->MoveNext();
		}
		
		switch($cond) // differents cases : word inclusion, exclusion and no condition
		{
			case "":
				foreach($we_temp as $id=> $weight)
				{
					if($we[$id])
						$we[$id] += $weight;
					else
						$we[$id] = $weight;
				}
				break;
				
			case "exclude":
				foreach($we_temp as $id=> $weight)
				{
					if($we[$id])
						unset($we[$id]);
					
				}
				break;
			
			case "include" :
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
				break;	
		}//end switch
		
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
		$local_context['nbresults'] = 0;
		call_user_func("code_alter_$funcname",$local_context);
		return;
	}
	
	$local_context['nbresults'] = count($results);
	if(function_exists("code_before_$funcname"))
		call_user_func("code_before_$funcname",$local_context);
	
	foreach($results as $key => $weight)
	{
		
		$dao2 = &getDAO("entities");
		$vo = $dao2->getById($key);
		//print_r($vo);
		$local_context['id'] = $key;
		$local_context['weight'] = $weight;
		$local_context['idparent'] = $vo->idparent;
		$local_context['idtype'] = $vo->idtype;
		$dao_type = &getDAO("types");
		$vo_type = $dao_type->getByID($vo->idtype);
		$local_context['type'] = $vo_type->type;
		//added information on tpledition
		$local_context['tpledition'] = $vo_type->tpledition;
		$local_context['g_title'] = $vo->g_title;
		$local_context['status'] = $vo->status;
		$local_context['identifier'] = $vo->identifier;
		$local_context['creationdate'] = $vo->creationdate ;
		$local_context['iduser'] = $vo->iduser ;
		$local_context['usergroup'] = $vo->usergroup ;
		$local_context['modificationdate'] = $vo->modificationdate ;
		$local_context['creationmethod'] = $vo->creationmethod ;
		$local_context['rank'] = $vo->rank ;
		$local_context['upd'] = $vo->upd ;
		$local_context['count'] = $count;
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
require_once("func.php");
$view=&getView();
$base="search";

extract_post($_GET);

	
recordurl();	
$view->renderCached($context,$base);
return;

 ?>