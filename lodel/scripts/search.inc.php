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
 * search
 * needs following parameters
 * 	- query : query string
 * 	- type (optional) : specific type
 * 	- status (optional) : specific status
 * 
 */  
function search(&$context,$funcname,$arguments)
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
   	 $regs = "'\.],:\"!\r\t\\/){}[|@<>$%Â«Â»\342\200\230\342\200\231\342\200\234\342\200\235";
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
	$context['nbresults'] = 0;
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
			//get all tablefields for q_field specified
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
		if( $context['qstatus']!="" && $context["visitor"])
		{
			$criteria_index .= " AND #_TP_entities.status ='".intval($context['qstatus'])."'";	
		}
		
		
		
		
		$offsetname="offset_".substr(md5($funcname),0,5);
		#echo $offsetname;
		$offset = ($context[$offsetname] ? intval($context[$offsetname]) : 0);
		#echo "offset:".$offset;
		
		$limit = " LIMIT $offset,".$arguments['limit'];
		#echo "limit :".$limit;
		$groupby = " GROUP BY identity ";
		$sql = lq("SELECT identity,sum(weight) as weight  FROM ".$from." ".$join." WHERE ".$criteria_index.$groupby.$limit);
	#echo "hey :".$sql;
	
		$sqlc = lq("SELECT identity FROM ".$from." ".$join." WHERE ".$criteria_index.$groupby);	
	#echo "hey2 :".$sqlc;
		//print_r($db->GetAll($sqlc));
		
		$context['nbresults'] += count($db->GetAll($sqlc));
		//print_r($row);
	#echo "Nombre de résultats absolu :".$nbresabs."<br />";
		$result=$db->execute($sql) or dberror();
		$we_temp = array();
		//$we_temp = $db->getarray($sql) or dberror();
		//print_r($we_temp);
		while(!$result->EOF)
		{
			$row = $result->fields;
			$we_temp[$row['identity']] = $row['weight'];
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
	
	asort($we,SORT_NUMERIC);
	$we = array_reverse($we,true);
	return $we;
		
} 

/*
 * LOOP SEARCH 
 * affichage des résultats d'une recherche
 * avec gestion de la pagination
 * 
 */

function _constructPages(&$context,$funcname,$arguments)
{
	//get current offset and construct url
	$arguments['limit'] = 10;
	
	$offsetname=$context['offsetname'];
	$currentoffset = ($_REQUEST[$offsetname]? $_REQUEST[$offsetname] : 0);
	$currenturl = basename($_SERVER['SCRIPT_NAME'])."?";
	
	
	$cleanquery=preg_replace("/(^|&)".$offsetname."=\d+/","",$_SERVER['QUERY_STRING']);
	if ($cleanquery[0]=="&") $cleanquery=substr($cleanquery,1); 
  if ($cleanquery) $currenturl.=$cleanquery."&";
 // echo $currentoffset.$currenturl;
	 
   	//construct next url
  # print_r($context);
  
   	if($context['nbresults'] > ($currentoffset+$arguments['limit']))
   	{
   		
   		$context['nexturl']=$currenturl.$offsetname."=".($currentoffset + $arguments['limit']);
	 	}
   	else
   	{
   		$context['nexturl'] = "";
   	}
   
    //construct previous url
  	if($currentoffset > 0)
  	{
   		$context['previousurl'] = $currenturl.$offsetname."=".($currentoffset - $arguments['limit']);
  	}
   	else
   	{
   		$context['previousurl'] ="";
   	}
   	
   	//construct pages table
   	$pages = array();
   	
   	//previous pages 
   	$i = 0;
  # 	echo "i=$i,limit=".$arguments['limit']."currentoffset=".intval($currentoffset);
   	// exit(0);
   	while($i + $arguments['limit'] <= intval($currentoffset))
   	{
   			$urlpage = $currenturl.$offsetname."=".$i;
   			$pages[($i/$arguments['limit']+ 1)] = $urlpage;
   			$i += $arguments['limit'];
   	}
   	
		//add current page   
   	$pages[($currentoffset/$arguments['limit']+ 1)] = "";
   
   	//next pages 
   	$i = $currentoffset;
   	while($i + $arguments['limit'] < $context['nbresults'])
   	{
   			$i += $arguments['limit'];
   			$urlpage = $currenturl.$offsetname."=".$i;
   			$pages[($i/$arguments['limit']+ 1)] = $urlpage;
   	}
   
   	return $pages;
}




function loop_search(&$context,$funcname,$arguments)
{
	$local_context = $context;
	$results = search($local_context,$funcname,$arguments);
	$count = 0;
	if(!$results || $local_context['nbresults'] == 0)
	{
		//echo "coucou";
		call_user_func("code_alter_$funcname",$local_context);
		return;
	}
	$offsetname="offset_".substr(md5($funcname),0,5);
	$currentoffset = ($_REQUEST[$offsetname]? $_REQUEST[$offsetname] : 0);
	$local_context['offsetname'] = $offsetname;
	
	
#echo $offsetname;

	//call before function
	if(function_exists("code_before_$funcname"))
	{
		//calcul of the current results show
		$local_context["resultfrom"] = $currentoffset+1;
		if($local_context['nbresults'] < ($currentoffset+$arguments['limit']))
			$local_context["resultto"] = $local_context['nbresults'];
		else
			$local_context["resultto"] = $currentoffset+$arguments['limit'];
		
			
			
			
		call_user_func("code_before_$funcname",$local_context);
	}
	
	//call do function with the results
	foreach($results as $key => $weight)
	{
		$dao2 = &getDAO("entities");
		$vo = $dao2->getById($key);
		foreach($vo as $key => $value)
			$local_context[$key] = $value;
		#print_r($vo);
		$local_context['weight'] = $weight;
		$local_context['idtype'] = $vo->idtype;
		$dao_type = &getDAO("types");
		$vo_type = $dao_type->getByID($vo->idtype);
		$local_context['type'] = $vo_type->type;
		//added information on tpledition
		$local_context['tpledition'] = $vo_type->tpledition;
		$local_context['count'] = $count;
		call_user_func("code_do_$funcname",$local_context);
		$count++;
	}
	
	//call after function
	if(function_exists("code_after_$funcname"))
	{
		//calcul of the current results show
		$local_context["resultfrom"] = $currentoffset+1;
		if($local_context['nbresults'] < ($currentoffset+$arguments['limit']))
			$local_context["resultto"] = $local_context['nbresults'];
		else
			$local_context["resultto"] = $currentoffset+$arguments['limit'];
		
		
		
		//get current offset and construct url
	/*	$currentoffset = ($_REQUEST[$offsetname]? $_REQUEST[$offsetname] : 0);
		$currenturl = basename($_SERVER['SCRIPT_NAME'])."?";
		$cleanquery=preg_replace("/(^|&)".$offsetname."=\d+/","",$_SERVER['QUERY_STRING']);
		if ($cleanquery[0]=="&") $cleanquery=substr($cleanquery,1); 
   	if ($cleanquery) $currenturl.=$cleanquery."&";
   
   	//construct next url
   	if($local_context['nbresults'] > ($currentoffset+$arguments['limit']))
   		$local_context['nexturl']=$currenturl.$offsetname."=".($currentoffset + $arguments['limit']);
   	else
   		$local_context['nexturl'] = "";
    
    //construct previous url
  	if($currentoffset > 0)
   		$local_context['previousurl'] = $currenturl.$offsetname."=".($currentoffset - $arguments['limit']);
   	else
   		$local_context['previousurl'] ="";
   	
   	//construct pages table
   	$pages = array();
   	
   	//previous pages 
   	$i = 0;
   	while($i + $arguments['limit'] <= $currentoffset)
   	{
   			$urlpage = $currenturl.$offsetname."=".$i;
   			$pages[($i/$arguments['limit']+ 1)] = $urlpage;
   			$i += $arguments['limit'];
   	}
		//add current page   
   	$pages[($currentoffset/$arguments['limit']+ 1)] = "";
   
   	//next pages 
   	$i = $currentoffset;
   	while($i + $arguments['limit'] < $local_context['nbresults'])
   	{
   			$i += $arguments['limit'];
   			$urlpage = $currenturl.$offsetname."=".$i;
   			$pages[($i/$arguments['limit']+ 1)] = $urlpage;
   	}
   	*/
   //$pages = _constructPages($local_context,$funcname,$arguments);
   	#print_r($pages);
   	/*if(count($pages))
   		$local_context["pages"] = $pages;*/
		call_user_func("code_after_$funcname",$local_context);
	}//end code_after call
		
}

/*
 * LOOP_PAGE_SCALE
 * this loop walk on the array pages to print pages number and links 
 */

function loop_page_scale(&$context,$funcname,$arguments)
{
	//Local cache
	static $cache;
	if(!isset($cache[$funcname]))
	{
		$pages = _constructPages($context,$funcname,$arguments);
		$cache[$funcname] = $page;
	}
	
	$local_context = $context;
	$local_context['pages'] = $pages;
	if(!$local_context["pages"] || count($local_context["pages"]) == 0)
	{
		call_user_func("code_alter_$funcname",$local_context);
		return;
	}
	//call before
	if(function_exists("code_before_$funcname"))
		call_user_func("code_before_$funcname",$local_context);
				
	foreach($local_context["pages"] as $key => $value)
	{
		$local_context["pagenumber"] = $key;
		$local_context["urlpage"] = $value;
		call_user_func("code_do_$funcname",$local_context);
	}
		
	
	//call after
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