<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier contenant les fonctions permettant d'effectuer des recherches
 * sur le moteur interne ou sur une instance Solr
 */

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
 * Fonction permettant d'effectuer des requêtes auprès d'une instance Solr
 *
 * Cette fonction reconnait les paramètres suivants :
 * $context['query'] = the query
 * $context['f'] = the field(s) to search into
 * $context['fields'] = the field(s) to fetch
 * $context['start'] = the start value
 * $context['rows'] = the maximum number of rows Solr must returns
 * $context['o'] = the operator(s), can be 'OR' or 'AND' or 'NOT'
 *
 * et a besoin d'un tableau contenant la configuration de connexion ($config['instance'])
 *
 * et supporte un ajout de paramètres dans la requête envoyé, via le tableau $config['params']
 */
function solrSearch(&$context, array $config)
{
	if(empty($context['query']))
		trigger_error('ERROR: no query has been specified', E_USER_ERROR);

	if(empty($config))
		trigger_error('ERROR: empty Solr configuration', E_USER_ERROR);

	if(!class_exists('SolrClient'))
	{
		trigger_error('ERROR: the Solr PHP library is required', E_USER_ERROR);
		return false;
	}

	$client = new SolrClient($config['instance']);

	if(!$client->ping())
	{
		trigger_error('ERROR: Solr not responding', E_USER_ERROR);
		return false;
	}

	$q = $o = array();

	if(is_array($context['query']))
	{
		$context['query'] = array_filter($context['query']);
		foreach($context['query'] as $k => $query)
		{
			$q[$k] = SolrUtils::escapeQueryChars(trim($query));
			if(isset($context['o'][$k]))
			{
				$context['o'][$k] = strtoupper($context['o'][$k]);
				if('AND' !== $context['o'][$k] && 'OR' !== $context['o'][$k] && 'NOT' !== $context['o'][$k])
					$o[$k] = 'OR';
				else $o[$k] = $context['o'][$k];
			}
			else $o[$k] = 'OR';
		}
	}
	else
	{
		$q[] = SolrUtils::escapeQueryChars(trim($context['query']));
	}

	$qs = '';

	$query = new SolrQuery();

	foreach($q as $k => $qq)
	{
		if(!empty($qs))
		{
			$qs .= ' '.$o[$k].' (';
		}

		if(!empty($context['f']))
		{
			if(is_array($context['f']))
			{
				$wheres = array();
				foreach($context['f'] as $fi)
				{
					$wheres[] = $fi.':('.SolrUtils::queryPhrase($qq).')';
				}
				$qs .= join(' OR ', $wheres);
			}
			else
			{
				$qs .= $context['f'].':('.SolrUtils::queryPhrase($qq).')';
			}
		}
		else $qs .= SolrUtils::queryPhrase($qq);

		if(count($q) > 1)
			$qs .= ')';
	}

	if(!empty($config['params']))
	{
		foreach($config['params'] as $name => $equiv)
		{
			$qs .= ' AND '.$name.':('.SolrUtils::queryPhrase(C::get('siteinfos.'.$equiv)).')';
		}
	}

	$query->setQuery($qs);

	if(isset($context['start']))
		$query->setStart((int) $context['start']);
	if(isset($context['rows']))
		$query->setRows((int) $context['rows']);

	if(!empty($context['fields']))
	{
		foreach($context['fields'] as $field)
			$query->addField($field);
	}

	$response = $client->query($query);

	if(!$response->success() || 200 !== (int) $response->getHTTPStatus())
	{
		trigger_error('ERROR: Solr returned '.$response->getHTTPStatusMessage().' (errorcode '.$response->getHTTPStatus().')', E_USER_ERROR);
		return false;
	}

	if(!isset($context['rows']))
	{ // to fetch ALL documents
		$resp = $response->getResponse();
		if(!empty($resp['response']['numFound']))
		{
			$query->setRows($resp['response']['numFound']);
			$response = $client->query($query);

			if(!$response->success() || 200 !== (int) $response->getHTTPStatus())
			{
				trigger_error('ERROR: Solr returned '.$response->getHTTPStatusMessage().' (errorcode '.$response->getHTTPStatus().')', E_USER_ERROR);
				return false;
			}
		}
	}

	return $response->getResponse();
}