<?php

/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cnou
 *  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
 *  Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 *  Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 *  Copyright (c) 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 *  Copyright (c) 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
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
 * Search
 * needs following parameters
 * 	- query : query string
 * 	- type (optional) : specific type
 * 	- status (optional) : specific status
 * 
 */

function search(&$context, $funcname, $arguments)
{
	global $db;
	if (empty($context['query']))
		return;
	$query = $context['query'];
	//non alphanum chars cleaning
	//include utf8 quotes at the end
	$regs = "'\.],:\"!\r\t\\/){}[|@<>$%Â«Â»\342\200\230\342\200\231\342\200\234\342\200\235";
	$query = strtr($query, $regs, preg_replace("/./", " ", $regs));
	//cut query string in token
	$tokens = preg_split("/\s+/", $query);
	#print_r($tokens);
	$we = array (); // we is an array that contains : key as entity identifier and value as weight
	$context['nbresults'] = 0;
    foreach($tokens as $token)
    {
        $token = trim($token);
		if ($token == "")
			continue; //if token is empty or just whitespace --> not search it !
		if ($token[0] == '-')	{
			$cond = "exclude";
			$token = substr($token, 1);
		}	elseif ($token[0] == '+')	{
			$cond = "include";
			$token = substr($token, 1);
		}	else
			$cond = 0;

		//if wildcard * used
		if ($token[strlen($token) - 1] == '*') {
			$end_wildcard = "%";
			$token = substr($token, 0, strlen($token) - 1);
		}	else {
			$end_wildcard = "";
		}
		if ($token[0] == '*')	{
			$begin_wildcard = "%";
			$token = substr($token, 1);
		}	else {
			$begin_wildcard = "";
		}
		//little hack because oe ligature is not supported in ISO-latin!!
		$token = strtolower(str_replace(array ("\305\223", "\305\222"), array ("oe", "OE"), $token));
		$token = makeSortKey($token);
		//foreach word search entities that match this word
		$dao =  DAO::getDAO("search_engine");
		$criteria_index = "word LIKE '$begin_wildcard$token$end_wildcard'";
		#echo "criteria_index=$criteria_index bim=$end_wildcard";
		$from = "#_TP_search_engine";
		if (!empty($context['qfield'])) {
			#echo "qfield :".$context['qfield'];
			//get all tablefields for q_field specified
			$dao_dc_fields = DAO::getDAO("tablefields");
			$vos_dc_fields = $dao_dc_fields->findMany("g_name='".addslashes($context['qfield'])."'");
			$field_in = array ();
			foreach ($vos_dc_fields as $vo_field)
				$field_in[] = $vo_field->name;
			if ($field_in)
				$criteria_index .= " AND tablefield ".sql_in_array($field_in);
		}
        $join = '';
		if (!empty($context['qtype']) || !empty($context['qstatus']) || !C::get('visitor', 'lodeluser')) {
			$join = "INNER JOIN #_TP_entities ON #_TP_search_engine.identity = #_TP_entities.id";
		}
		if (!empty($context['qtype'])) {
			$criteria_index .= " AND #_TP_entities.idtype ='".(int)$context['qtype']."'";
		}
		if (!empty($context['qstatus']) && C::get('visitor', 'lodeluser')) {
			$criteria_index .= " AND #_TP_entities.status ='".(int)$context['qstatus']."'";
		}
		if (!C::get('visitor', 'lodeluser')) {
			$criteria_index .= " AND #_TP_entities.status >= 1";
		}
		$groupby = " GROUP BY identity ";
		$sql = lq("SELECT identity,sum(weight) as weight  FROM ".$from." ".$join." WHERE ".$criteria_index.$groupby);
		#echo "hey :".$sql;
		$sqlc = lq("SELECT identity FROM ".$from." ".$join." WHERE ".$criteria_index.$groupby);
		$result = $db->execute($sql) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		$we_temp = array ();
		while (!$result->EOF) {
			$row = $result->fields;
			$we_temp[$row['identity']] = $row['weight'];
			$result->MoveNext();
		}

		switch ($cond) { // differents cases : word inclusion, exclusion and no condition
		case "" :
			foreach ($we_temp as $id => $weight) {
				if (isset($we[$id]))
					$we[$id] += $weight;
				else
					$we[$id] = $weight;
			}
			break;
		case "exclude" :
			foreach ($we_temp as $id => $weight) {
				if (isset($we[$id]))
					unset ($we[$id]);
			}
			break;
		case "include" :
			if (count($we) > 0) {
				foreach ($we as $id => $weight) {
					if (isset($we_temp[$id]))
						$we[$id] += $we_temp[$id];
					else
						unset ($we[$id]);
				}
			}	else {
				foreach ($we_temp as $id => $weight)
					$we[$id] = $weight;
			}
			break;
		} //end switch
	}
	asort($we, SORT_NUMERIC);
	return array_reverse($we, true);
}

/*
 * LOOP SEARCH 
 * print search result using pagination
 * 
 */
function loop_search(& $context, $funcname, $arguments)
{
	if (!isset($arguments['split']))
		$arguments['split'] = 10; //split results by 10 by default
	$local_context = $context;
	static $cache;
	if (!isset ($cache[$funcname])) {
		$results = search($local_context, $funcname, $arguments);
		$context['nbresults'] = count($results);
		$cache[$funcname] = $results;
	}
	$results = $cache[$funcname];
	$count = 0;
	if ($context['nbresults'] === 0) {
		if (function_exists("code_alter_$funcname"))
			call_user_func("code_alter_$funcname", $local_context);
		return;
	}
	$offsetname = "offset_".substr(md5($funcname), 0, 5);
	$currentoffset = (isset($_REQUEST[$offsetname]) ? $_REQUEST[$offsetname] : 0);
	#echo "currentoffset :$currentoffset";
	$context['offsetname'] = $offsetname;
	$context['limitinfo'] = $arguments['split'];
	$context["resultfrom"] = $currentoffset +1;
	if ($context['nbresults'] < ($currentoffset + $arguments['split']))
		$context["resultto"] = $context['nbresults'];
	else
		$context["resultto"] = $currentoffset + $arguments['split'];
	//call before function
	if (function_exists("code_before_$funcname"))
		call_user_func("code_before_$funcname", $context);
	$dao2 = DAO::getDAO("entities");
	//call do function with the results

	$res = _array_slice_key($results, $currentoffset, $arguments['split']);
	#print_r($results);

	foreach ($res as $key => $weight) {
		$vo = $dao2->getById($key);
		if ($vo->id && $vo->status != -64) {
			foreach ($vo as $key => $value)
				$local_context[$key] = $value;
			$local_context['weight'] = $weight;
			$local_context['idtype'] = $vo->idtype;
			$dao_type = DAO::getDAO("types");
			$vo_type = $dao_type->getByID($vo->idtype);
			$local_context['type'] = $vo_type->type;
			//added information on tpledition
			$local_context['tpledition'] = $vo_type->tpledition;
			$local_context['count'] = $count;
			call_user_func("code_do_$funcname", $local_context);
			$count ++;
		}
	}

	//call after function
	if (function_exists("code_after_$funcname"))
		call_user_func("code_after_$funcname", $context);

}

function _array_slice_key($array, $offset, $len = -1)
{

	if (!is_array($array))
		return FALSE;
	$length = $len >= 0 ? $len : count($array);
	$keys = array_slice(array_keys($array), $offset, $length);
	foreach ($keys as $key) {
		$return[$key] = $array[$key];
	}
	return $return;
}

/**
 * Results page script - Lodel part
 * 
 */
recordurl();
View::getView()->renderCached("search");
return;
?>