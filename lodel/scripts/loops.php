<?php
/**
 * Boucles Lodelscript prédéfinies
 * PHP versions 4 et 5, 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * Copyright (c) 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * Copyright (c) 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 *
 * Home page: http://www.lodel.org
 *
 * E-Mail: lodel@lodel.org
 *
 * All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @author Ghislain Picard
 * @author Jean Lamy
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */

if (is_readable(C::get('home', 'cfg') . 'loops_local.php'))
	include "loops_local.php";

/**
 * Loop parententities
 * @param array $context the context containing all the data
 * @param string $funcname the name of the Lodelscript function to call
 * @param string $critere the criterions to select the entities
 */
function loop_parentsentities(& $context, $funcname, $critere = "")
{
	global $db;
	$id = (int)@$context['id'];
	if (!$id)
		return;
	$result = $db->execute(lq("SELECT * 
                            FROM #_entitiestypesjoin_,#_TP_relations 
                            WHERE #_TP_entities.id=id1 AND id2='".$id."' 
                            AND nature='P' AND #_TP_entities.status>". (C::get('visitor', 'lodeluser') ? -64 : 0)." 
                            ORDER BY degree DESC")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

	while (!$result->EOF) {
		$localcontext = array_merge($context, $result->fields);
		if (function_exists("code_do_$funcname")) {
			call_user_func("code_do_$funcname", $localcontext); }
		$result->MoveNext();
	}
}



/**
 * Loop displaying the table of contents (toc)
 * @param array $context the context containing all the data
 * @param string $funcname the name of the Lodelscript function to call
 * @param array $arguments an array that can contain some arguments
 * @access public
 */
function loop_toc($context, $funcname, $arguments)
{
	if (!preg_match_all("/<((?:r2r:section|h)(\d+))\b[^>]*>(.*?)<\/\\1>/is", $arguments['text'], $results, PREG_SET_ORDER)) {
		if (!preg_match_all("/<(div)\s+class=\"section(\d+)\">(.*?)<\/\\1>/is", $arguments['text'], $results, PREG_SET_ORDER)) {
			if (function_exists("code_alter_$funcname"))
				call_user_func("code_alter_$funcname", $context);
			return;
		}
	}

	if (function_exists("code_before_$funcname"))
		call_user_func("code_before_$funcname", $context);

	$i = 0;
	$tocid = array ();
	foreach ($results as $result) {
		++$i;
		$localcontext = $context;
		$level = (int)$result[2];
		$localcontext['level'] = $localcontext['niveau'] = $level; //for compatibility
        	if(!isset($tocid[$level])) $tocid[$level] = 0; 
		$localcontext['tocid'] = $level."n". (++ $tocid[$level]);
		// cleaning bad anchor putted by servoo
		$localcontext['title'] = $localcontext['titre'] = preg_replace('/<a\b\s* id="[^"]+">\s*<\/a>/', '', $result[3]); //for compatibility
		if ($i == 1 && function_exists("code_dofirst_$funcname")) {
			call_user_func("code_dofirst_$funcname", $localcontext);
		}	elseif ($i == count($results) && function_exists("code_dolast_$funcname")) {
			call_user_func("code_dolast_$funcname", $localcontext);
		}	else {
			call_user_func("code_do_$funcname", $localcontext);
		}
	}

	if (function_exists("code_after_$funcname"))
		call_user_func("code_after_$funcname", $context);
} //end loop toc

function loop_paragraphs($context, $funcname, $arguments)
{
	if (!isset ($arguments['text'])) {
		if (C::get('visitor', 'lodeluser'))
			trigger_error("ERROR: the loop \"paragraph\" requires a TEXT attribut", E_USER_ERROR);
		return;
	}
	if(preg_match_all("/<p\b[^>]*>(.*?)<\/p>/is", $arguments['text'], $results, PREG_SET_ORDER))
	{
		$count = 0;
		foreach ($results as $result)	{
			$localcontext = $context;
			$localcontext['count'] = (++ $count);
			$localcontext['paragraph'] = $result[0];
			call_user_func("code_do_$funcname", $localcontext);
		}
	}
}

function loop_extract_images($context, $funcname, $arguments)
{
	if (!isset ($arguments['text'])) {
		if (C::get('visitor', 'lodeluser'))
			trigger_error("ERROR: the loop \"paragraph\" requires a TEXT attribut", E_USER_ERROR);
		return;
	}
	$end = 0;
	if (isset($arguments['limit'])) {
		list ($start, $length) = explode(",", $arguments['limit']);
		$end = $start + $length;
	} else {
		$start = 0;
	}
	$validattrs = array ("src", "alt", "border", "style", "class", "name");
	preg_match_all("/<img\b([^>]*)>/", $arguments['text'], $results, PREG_SET_ORDER);
	if (!$end)
		$end = count($results);
	$count = 0;
	for ($j = $start; $j < $end; $j ++) {
		$result = $results[$j];
		$localcontext = $context;
		$attrs = preg_split("/\"/", $result[1]);
		$countattrs = 2 * ((int)(count($attrs) / 2));
		for ($i = 0; $i < $countattrs; $i += 2) {
			$attr = trim(str_replace("=", "", $attrs[$i]));
			if (in_array($attr, $validattrs))
				$localcontext[$attr] = $attrs[$i +1];
		}
		$localcontext['count'] = (++ $count);
		$localcontext['image'] = $result[0];
		call_user_func("code_do_$funcname", $localcontext);
	}
}

function previousnext($dir, $context, $funcname, $arguments)
{
	global $db;
	if (!isset ($arguments['id'])) {
		if (C::get('visitor', 'lodeluser'))
			trigger_error("ERROR: the loop \"previous\" requires a ID attribut", E_USER_ERROR);
		return;
	}

	$id = (int)$arguments['id'];
	// cherche le document precedent ou le suivant
	if ($dir == "previous") {
		$sort = "DESC";
		$compare = "<";
	} else {
		$sort = "ASC";
		$compare = ">";
	}

	$statusmin = C::get('visitor', 'lodeluser') ? -32 : 0;

	$querybase = "SELECT e3.*,t3.type,t3.class 
			        FROM {$GLOBALS['tp']}entities as e0 INNER JOIN {$GLOBALS['tp']}types as t0 ON e0.idtype=t0.id, 
			        {$GLOBALS['tp']}entities as e3 INNER JOIN {$GLOBALS['tp']}types as t3 ON e3.idtype=t3.id 
			        WHERE e0.id='{$id}' AND e3.idparent=e0.idparent AND e3.status>{$statusmin} 
                    			AND e0.status>{$statusmin} AND e3.rank{$compare}e0.rank";

	if (!empty($arguments['through']))
	{
		$quotedtypes = join("','", explode(",", addslashes($arguments['through'])));
		$result = $db->execute("SELECT id FROM {$GLOBALS['tp']}types WHERE type IN ('$quotedtypes')") 
			or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		$idtypes = array();
		while (!$result->EOF)	
		{
			$idtypes[] = $result->fields['id'];
			$result->MoveNext();
		}

		if($idtypes)
		{
			$types = join(",", $idtypes);
			$querybase .= ' AND t3.id IN ('.$types.')';
		}
	}

	$querybase .= " ORDER BY e3.rank ".$sort;

	do {
		$row = $db->getRow($querybase);
		if ($row === false)
			trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		if ($row)	{ // found
			$localcontext = array_merge($context, $row);
			break;
		}

		if (!isset($types))
			break;

		// ok, on a pas trouve on cherche alors le pere suivant l'entite (e0) et son premier fils (e2)
		// not found, well, we look for the next/previous parent above and it's first/last son.
		$row = $db->getrow("
			SELECT e3.*,t3.type,t3.class 
				FROM $GLOBALS[tp]entities as e0 INNER JOIN $GLOBALS[tp]types as t0 ON e0.idtype=t0.id, 
				$GLOBALS[tp]entities as e1, $GLOBALS[tp]entities as e2, 
				$GLOBALS[tp]entities as e3 INNER JOIN $GLOBALS[tp]types as t3 ON e3.idtype=t3.id  
				WHERE e0.id='$id' AND e1.id=e0.idparent AND e2.idparent=e1.idparent AND e3.idparent=e2.id 
					AND e2.rank".$compare."e1.rank AND e1.idtype IN ({$types}) AND e2.idtype IN ({$types}) 
					AND e0.status>$statusmin AND e1.status>$statusmin AND e2.status>$statusmin 
					AND e3.status>$statusmin 
				ORDER BY e2.rank ".$sort.", e3.rank ".$sort);

		if ($row === false)
			trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

		if ($row) {
			$localcontext = array_merge($context, $row);
			break;
		}
	}	while (0);

	if (isset($localcontext)) {
		call_user_func("code_do_$funcname", $localcontext);
	}	else {
		if (function_exists("code_alter_$funcname"))
			call_user_func("code_alter_$funcname", $context);
	}
} //end loop_previousnext

function sql_not_xor($a, $b)
{
	return "((($a) AND ($b)) OR (NOT ($a) AND NOT ($b)))";
}

function loop_previous($context, $funcname, $arguments)
{
	previousnext("previous", $context, $funcname, $arguments);
}

function loop_next($context, $funcname, $arguments)
{
	previousnext("next", $context, $funcname, $arguments);
}

/**  Loop for reading RSS Flux using Magpie 
 *
 */
function loop_rss($context, $funcname, $arguments)
{
	defined('MAGPIE_CACHE_ON') || define("MAGPIE_CACHE_ON", TRUE);
	defined('MAGPIE_CACHE_DIR') || define("MAGPIE_CACHE_DIR", "./CACHE");
	defined('DIRECTORY_SEPARATOR') || define("DIRECTORY_SEPARATOR", "/");
	defined('MAGPIE_OUTPUT_ENCODING') || define('MAGPIE_OUTPUT_ENCODING', 'UTF-8');
	if (!isset ($arguments['url'])) {
		if (C::get('visitor', 'lodeluser'))
			trigger_error("ERROR: the loop \"rss\" requires a URL attribut", E_USER_ERROR);
		return;
	}
	if (isset($arguments['refresh']) && !is_numeric($arguments['refresh'])) {
		if (C::get('visitor', 'lodeluser'))
			trigger_error("ERROR: the REFRESH attribut in the loop \"rss\" has to be a number of second ", E_USER_ERROR);
		$arguments['refresh'] = 0;
	}
	if(isset($arguments['timeout']) && is_numeric($arguments['timeout']) && $arguments['timeout'] > 0)
	{
		defined('MAGPIE_FETCH_TIME_OUT') || define('MAGPIE_FETCH_TIME_OUT', (int)$arguments['timeout']);
	}
	$err = error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);
	function_exists('fetch_rss') || include "magpierss/rss_fetch.inc";
	$rss = fetch_rss(html_entity_decode($arguments['url'], ENT_COMPAT, 'UTF-8'), isset($arguments['refresh']) ? $arguments['refresh'] : 3600);
	if (!$rss) {
		if (C::get('visitor', 'lodeluser')) {
			echo "<b>Warning: Erreur de connection RSS sur l'url ", $arguments['url'], "</b><br/>";
		}	else {
			if (C::get('contactbug', 'cfg'))
				@mail(C::get('contactbug', 'cfg'), "[WARNING] LODEL - ".C::get('version', 'cfg')." - $GLOBALS[currentdb]", "Erreur de connection RSS sur l'url ".$arguments['url']);
			error_reporting($err);
			return;
		}
	}

	$localcontext = $context;
	foreach (array (# obligatoire
	"title", "link", "description", # optionel
	"language", "copyright", "managingEditor", "webMaster", "pubDate", "lastBuildDate", "category", "generator", "docs", "cloud", "ttl", "rating", "textInput", "skipHours", "skipDays") as $v)
		$localcontext[strtolower($v)] = isset($rss->channel[$v]) ? $rss->channel[$v] : '';

	// special treatment for "image"
	if (isset($rss->channel['image'])) {
		$localcontext['image_url'] = isset($rss->channel['image']['url']) ? $rss->channel['image']['url'] : '';
		$localcontext['image_title'] = isset($rss->channel['image']['title']) ? $rss->channel['image']['title'] : '';
		$localcontext['image_link'] = isset($rss->channel['image']['link']) ? $rss->channel['image']['link'] : '';
		$localcontext['image_description'] = isset($rss->channel['image']['description']) ? $rss->channel['image']['description'] : '';
		$localcontext['image_width'] = isset($rss->channel['image']['width']) ? $rss->channel['image']['width'] : '';
        	$localcontext['image_height'] = isset($rss->channel['image']['height']) ? $rss->channel['image']['height'] : '';
		if (!$localcontext['image_width'])
			$localcontext['image_width'] = 88;
		if ($localcontext['image_width'] > 144)
			$localcontext['image_width'] = 144;
		if (!$localcontext['image_height'])
			$localcontext['image_height'] = 31;
		if ($localcontext['image_height'] > 400)
			$localcontext['image_height'] = 400;
	}

	$localcontext['rssobject'] = $rss;
	if (function_exists("code_before_$funcname"))
		call_user_func("code_before_$funcname", $context);
	call_user_func("code_do_$funcname", $localcontext);
	if (function_exists("code_after_$funcname"))
		call_user_func("code_after_$funcname", $context);
} //end loop_rss

function loop_rssitem($context, $funcname, $arguments)
{
	// check whether there are some items in the rssobject.
	if (!is_object($context['rssobject']) || !isset($context['rssobject']->items)) {
		if (function_exists("code_alter_$funcname"))
			call_user_func("code_alter_$funcname", $localcontext);
		error_reporting($err);
		return;
	}

	$localcontext = $context;
	// yes, there are, let's loop over them.
	if (function_exists("code_before_$funcname"))
		call_user_func("code_before_$funcname", $localcontext);

	$items = $context['rssobject']->items;
	$context['nbresults'] = $context['nbresultats'] = count($items);
	$count = 0;
	if (isset($arguments['limit'])) {
		list ($start, $length) = preg_split("/\s*,\s*/", $arguments['limit']);
	} else {
		$start = 0;
		$length = count($context['rssobject']->items);
	}

	for ($i = $start; $i < $start + $length; $i ++) {
        	if(!isset($items[$i])) continue;
		$item = $items[$i];
		$localcontext = $context;
		++$count;
		$localcontext['count'] = $count;
		foreach (array ("title", "link", "description", "author", "category", "comments", "enclosure", "guid", "pubdate", "source") as $v)
			$localcontext[strtolower($v)] = isset($item[$v]) ? $item[$v] : '';
		call_user_func("code_do_$funcname", $localcontext);
	}
	if (function_exists("code_after_$funcname"))
		call_user_func("code_after_$funcname", $localcontext);
	error_reporting($err);
} //end loop rss tiem

/**
 * This loop walk on the array pages to print pages number and links 
 */
function loop_page_scale(& $context, $funcname, $arguments)
{
	//Local cache
	static $cache;
	if (!isset ($cache[$funcname]))	{
		$cache[$funcname] = _constructPages($context, $funcname, $arguments);
	}

	$local_context = $context;
	$local_context['pages'] = $cache[$funcname];
	if (!$local_context["pages"] || count($local_context["pages"]) == 0) {
		call_user_func("code_alter_$funcname", $local_context);
		return;
	}
	//call before
	if (function_exists("code_before_$funcname"))
		call_user_func("code_before_$funcname", $local_context);
	$oldpagenum = 1;
	foreach ($local_context["pages"] as $key => $value)	{
		$local_context["pagenumber"] = $key;
		if ($key - $oldpagenum > 1)
			$local_context["hole"] = 1;
		else
			$local_context["hole"] = 0;
		$oldpagenum = $key;
		$local_context["urlpage"] = $value;
		call_user_func("code_do_$funcname", $local_context);
	}
	//call after
	if (function_exists("code_after_$funcname"))
		call_user_func("code_after_$funcname", $local_context);
}

/**
 * @private
 * construct page listing by given nbresults and currentoffset in the results
 * 
 */
function _constructPages(& $context, $funcname, $arguments)
{
	//get current offset and construct url
	$arguments['limit'] = @$context['limitinfo'];
	if (!$arguments['limit'])
		return;

	$offsetname = @$context['offsetname'];
	$currentoffset = (isset($_REQUEST[$offsetname]) ? $_REQUEST[$offsetname] : 0);
	$currenturl = basename($_SERVER['SCRIPT_NAME'])."?";
	$cleanquery = preg_replace(array("/(^|&)".$offsetname."=\d+/","/(^|&)clearcache=[^&]+/"), "", $_SERVER['QUERY_STRING']);
	if ($cleanquery[0] == "&")
		$cleanquery = substr($cleanquery, 1);
	if ($cleanquery)
		$currenturl .= $cleanquery."&";

	$context['nbresults'] = @$context['nbresults'];
	//construct next url
	if ($context['nbresults'] > ($currentoffset + $arguments['limit']))
		$context['nexturl'] = $currenturl.$offsetname."=". ($currentoffset + $arguments['limit']);
	else
		$context['nexturl'] = "";
	//construct previous url
	if ($currentoffset > 0)
		$context['previousurl'] = $currenturl.$offsetname."=". ($currentoffset - $arguments['limit']);
	else
		$context['previousurl'] = "";
	//construct pages table
	$pages = array ();
	//previous pages 
	$i = 0;
	while ($i + $arguments['limit'] <= (int)$currentoffset) {
		$urlpage = $currenturl.$offsetname."=".$i;
		$pages[($i / $arguments['limit'] + 1)] = $urlpage;
		$i += $arguments['limit'];
	}
	//add current page   
	$pages[($currentoffset / $arguments['limit'] + 1)] = "";
	//next pages 
	$i = $currentoffset;
	while ($i + $arguments['limit'] < $context['nbresults']) {
		$i += $arguments['limit'];
		$urlpage = $currenturl.$offsetname."=".$i;
		$pages[($i / $arguments['limit'] + 1)] = $urlpage;
	}
	if (count($pages) > 10)	{
		$res = plageDeRecherche($currentoffset / $arguments['limit'], count($pages));
		foreach ($pages as $key => $value) {
			if (($key < $res[0] || $key > $res[1] + 1) && $key != 1)
				unset ($pages[$key]);
		}
	}
	return $pages;
}
/**
 * Return an array with the first and last page taking into account the current
 * page and the total number of pages (from In-Extenso function)
 * 
 */
function plageDeRecherche($numPageCourante, $nbPagesTotal)
{
	$nbPagesTotal = $nbPagesTotal;
	$numPageCourante = $numPageCourante +1;
	$precision = 4;
	$res = array ();
	$ecart_inf = 0;
	$ecart_sup = 0;
	$ecart_inf = $numPageCourante -1;
	$ecart_sup = abs($numPageCourante - $nbPagesTotal);
	if ($numPageCourante - $precision > 0) {
		$res[0] = $numPageCourante - $precision;
	}	else {
		$res[0] = 1;
	}
	if ($ecart_sup < 5) {
		if ($res[0] - ($precision - $ecart_sup) > 0)
			$res[0] -= ($precision - $ecart_sup);
		else
			$res[0] = 1;
	}
	if ($numPageCourante + $precision < $nbPagesTotal) {
		$res[1] = $numPageCourante + $precision;
	}	else {
		$res[1] = $nbPagesTotal;
	}
	if ($ecart_inf < 5)	{
		if ($res[1] + ($precision - $ecart_inf) < $nbPagesTotal)
			$res[1] += ($precision - $ecart_inf);
		else
			$res[1] = $nbPagesTotal;
	}
	return $res;
}

/** function loop_mltext
 * Display multilingual texts. 
 * 
 */
function loop_mltext(& $context, $funcname)
{
	if (is_array($context['value'])) {
		foreach ($context['value'] as $lang => $value) {
			$localcontext = $context;
			$localcontext['lang'] = $lang;
			$localcontext['value'] = $value;
			call_user_func("code_do_$funcname", $localcontext);
		}
		// pas super cette regexp... mais l argument a deja ete processe !
	}	elseif (/*preg_match_all("/&amp;lt;r2r:ml lang\s*=&amp;quot;(\w+)&amp;quot;&amp;gt;(.*?)&amp;lt;\/r2r:ml&amp;gt;/s", 
													$context['value'], $results, PREG_SET_ORDER) || */
        preg_match_all("/(?:&amp;lt;|&lt;|<)r2r:ml lang\s*=(?:&amp;quot;|&quot;|\")(\w+)(?:&amp;quot;|&quot;|\")(?:&amp;gt;|&gt;|>)(.*?)(?:&amp;lt;|&lt;|<)\/r2r:ml(?:&amp;gt;|&gt;|>)/s", 
														$context['value'], $results, PREG_SET_ORDER))	{
		foreach ($results as $result)	{
			$localcontext = $context;
			$localcontext['lang'] = $result[1];
			$localcontext['value'] = $result[2];
			call_user_func("code_do_$funcname", $localcontext);
		}
	}
}

/**
 * loop which return the right to perform an action or not
 */
function loop_rightonentity(& $context, $funcname, $arguments)
{
	if (!isset ($arguments['action'])) {
		if (C::get('visitor', 'lodeluser'))
			trigger_error("ERROR: the loop \"rightonentity\" requires an ACTION attribut", E_USER_ERROR);
		return;
	}
	if (rightonentity($arguments['action'], $context)) {
		if (function_exists("code_do_$funcname"))
			call_user_func("code_do_$funcname", $context);
	}	else {
		if (function_exists("code_alter_$funcname"))
			call_user_func("code_alter_$funcname", $context);
	}
}

/**
 * loop_errors and loop_fielderror are used to show potential errors in the forms.
 */
function loop_errors(& $context, $funcname, $arguments)
{
	$localcontext = $context;
	if (!empty($localcontext['error']) && is_array($localcontext['error'])) {
		if (function_exists("code_before_$funcname")) {
			$context['count'] = count($context['error']);
			call_user_func("code_before_$funcname", $context);
		}
		foreach ($localcontext['error'] as $field => $message) {
			$localcontext['varname'] = $field;
			$localcontext['error'] = $message;
			call_user_func("code_do_$funcname", $localcontext);
		}
		if (function_exists("code_after_$funcname"))
			call_user_func("code_after_$funcname", $context);
	}	else {
		if (function_exists("code_alter_$funcname"))
			call_user_func("code_alter_$funcname", $localcontext);
	}
}

function loop_fielderror(& $context, $funcname, $arguments)
{
	if (empty($arguments['field']))
		trigger_error("ERROR: loop fielderror require a field attribute", E_USER_ERROR);
	
	if (isset($context['error'][$arguments['field']])) {
        	$localcontext = $context;
        	$localcontext['error'] = $context['error'][$arguments['field']];
		call_user_func("code_do_$funcname", $localcontext);
	}
}

function loop_field_selection_values(& $context, $funcname, $arguments)
{
	//Get values of the list in the editionparams field for the current field
	// and if no editionparams call alter
	if (!isset ($context['editionparams']))
		trigger_error("ERROR: internal error in loop_field_selection_values", E_USER_ERROR);
	
	$arr = explode(",", $context['editionparams']);
	$context['value'] = @$context['value'];
	$choosenvalues = explode(",", $context['value']); //if field contains more than one value (comma separated)
	foreach ($arr as $value) {
		$value = trim($value);
		$localcontext = $context;
		$localcontext['value'] = $value;
		if (in_array($value, $choosenvalues)) {
			$localcontext['checked'] = 'checked="checked"';
			$localcontext['selected'] = 'selected="selected"';
		}
		call_user_func("code_do_$funcname", $localcontext);
	}
}

/**
 * Parcours un tableau passé en argument de la LOOP : 
 * <LOOP NAME="foreach" ARRAY="[#MONARRAY]">
 * On considère que le tableau est passé par l'argument array
 * 
 */
function loop_foreach(&$context, $funcname, $arguments)
{
	$localcontext = $context;

	if(empty($arguments['array']) || !is_array($arguments['array'])) {
		if(function_exists("code_alter_$funcname"))
			call_user_func("code_alter_$funcname", $localcontext);
		return;
	}
	$localcontext['count'] = count($arguments['array']);
	//Le before
	if (function_exists("code_before_$funcname")) {
		call_user_func("code_before_$funcname", $context);
	}
	// Parcours du tableau
	foreach($arguments['array'] as $key => $value) {
		$localcontext['key'] = $key;
		$localcontext['value'] = $value;
		call_user_func("code_do_$funcname", $localcontext);
	}
		
	//L'after
	if (function_exists("code_after_$funcname")) {
		call_user_func("code_after_$funcname", $context);
	}
}

/**
 * Liste les types compatibles pour une entité. Cette boucle permet de construire
 * la liste de modification du type d'une entité. 
 * 
 * Elle vérifie les types que peut contenir le type parent (si on est pas à la racine).
 * Si l'entité contient des enfants, elle vérifie
 * aussi que les enfants peuvent contenir un type.
 *
 * @param array $context le contexte passé par référence
 * @param string $funcname le nom de la fonction loop
 * @param array $arguments les arguments éventuels
 */
function loop_compatible_types(&$context, $funcname, $arguments)
{
	global $db;
	static $compatible_types;
	function_exists('checkTypesCompatibility') || include 'entitiesfunc.php';
	if(!$compatible_types) {
		//selectionne tous les types de la classe
		$context['type']['class'] = @$context['type']['class'];
		$context['idparent'] = @$context['idparent'];
		$context['id'] = @$context['id'];
		$sql = lq("SELECT * FROM #_TP_types WHERE class='".$context['type']['class']."'");
		$compatible_types = array();
		$types = $db->getArray($sql);
		foreach ($types as $row) {
			if(checkTypesCompatibility(0, $context['idparent'],$row['id'])) {
				//pour chaque enfant il faut tester si il peut être contenu dans le type testé.
				if(childCanBeInThisType($row['id'],$context['id'])) {
					$compatible_types[] = $row;
				}
			}
		}
	}
	$localcontext = $context;
	if($compatible_types && count($compatible_types) > 0) {
		if (function_exists("code_before_$funcname")) {
			call_user_func("code_before_$funcname", $context);
		}
		foreach($compatible_types as $type) {
			$localcontext = array_merge($localcontext,$type);
			call_user_func("code_do_$funcname", $localcontext);
		}
		if (function_exists("code_after_$funcname")) {
			call_user_func("code_after_$funcname", $context);
		}
	} else {
		if(function_exists("code_alter_$funcname")) {
		call_user_func("code_alter_$funcname", $localcontext);
	}
	}
}

/**
 * Test si un type $type peut être  appliqué à une entité $id suivant le type de ses enfants.
 *
 * Cette fonction est utilisée dans loop_compatible type
 *
 * @param integer $type l'identifiant du type
 * @param integer $id l'identifiant de l'entité
 */
function childCanBeInThisType($type,$id)
{
	global $db;
	$id = (int)$id;
	if($id == 0) { //si id = 0 cela veut dire qu'on est en création d'entité
		return true;
	}
	$sql = lq("SELECT id,idtype FROM #_TP_entities WHERE idparent='$id'");
	$entities = $db->getArray($sql);
	//pour chaque entité on teste si elle peut être contenu dans $type
	foreach($entities as $entity) {
		$query = lq("SELECT cond FROM #_TP_entitytypes_entitytypes WHERE identitytype='".$entity['idtype']."' AND identitytype2='".$type."'");
		$condition = $db->getOne($query);
		if(!$condition) {
			return false;
		}
	}
	return true;
}

/**
 * Boucle Lodelscript qui affiche l'alphabet
 *
 * @param array $context le contexte
 * @param string $funcname le nom de la fonction
 */
function loop_alphabet($context, $funcname)
{
	for ($l = 'A'; $l != 'AA'; $l++) {
		$context['lettre'] = $l;
		call_user_func("code_do_$funcname", $context);
	}
}

/**
 * Boucle Lodelscript qui affiche la première lettre (distincte) de tous les tuples d'un champ
 *
 * @param array $context le contexte
 * @param string $funcname le nom de la fonction
 */
function loop_alphabetSpec($context, $funcname)
{
	global $db;
	if(empty($context['table']) || empty($context['field']))
		trigger_error("ERROR: loop_alphabetSpec requires arguments 'table' and 'field'.", E_USER_ERROR);

	$whereSelect = $whereCount = '';

	if(!empty($context['idtype'])) { // classtype
		$table = $context['table'];
		$whereSelect = "WHERE idtype = '{$context['idtype']}'";
		$whereCount = " idtype = '{$context['idtype']}' AND ";
	} else { // class
		$table = $context['table'].' LEFT JOIN #_TP_entities ON (#_TP_'.$context['table'].'.identity=#_TP_entities.id)';
	}
	$status = C::get('editor', 'lodeluser') ? ' status > -64 ' : ' status > 0 ';
	$whereSelect .= !empty($whereSelect) ? ' AND '.$status : 'WHERE '.$status;	
	$sql = "SELECT DISTINCT(SUBSTRING({$context['field']},1,1)) as l 
			FROM #_TP_{$table} 
			{$whereSelect} 
			ORDER BY l";
	
	$lettres = $db->getArray(lq($sql));
	if(empty($lettres))
	{
		if(function_exists('code_alter_'.$funcname))
			call_user_func('code_alter_'.$funcname, $context);

		return;
	}

	foreach($lettres as &$lettre) {
		if($lettre['l'] != '<' && $lettre['l'] != '>' && $lettre['l'] != ' ')
			$lettre['l'] = strtoupper(makeSortKey($lettre['l']));
	}
	
	$sql = lq("SELECT COUNT({$context['field']}) as nbresults FROM #_TP_{$table} WHERE {$whereCount} {$status} AND SUBSTRING({$context['field']},1,1) = ");

	for ($l = 'A'; $l != 'AA'; $l++) {
		$context['lettre'] = $l;
		$context['nbresults'] = $db->getOne($sql."'{$context['lettre']}'");
		call_user_func("code_do_$funcname", $context);
	}
	
	// bug PHP : si on ne passe le tableau en référence, il modifie la derniere valeur du tableau et vire les références !!!
	foreach($lettres as &$lettre) {
		if($lettre['l'] >= '0' && $lettre['l'] <= '9') {
			$context['lettre'] = $lettre['l'];
			$context['nbresults'] = $db->getOne($sql.$context['lettre']);
			call_user_func("code_do_$funcname", $context);
		}
	}

	foreach($lettres as &$lettre) {
		if($lettre['l'] == '') continue;
		if(!preg_match("/[A-Z]/", $lettre['l']) && !preg_match("/[0-9]/", $lettre['l'])) {
			$context['lettre'] = $lettre['l'];
			$context['nbresults'] = $db->getOne($sql."'".addcslashes($context['lettre'], "'")."'");
			call_user_func("code_do_$funcname", $context);
		}
	}
}

function loop_classtypes($context, $funcname)
{
    global $db;
    foreach(array('entities', 'entries', 'persons') as $classtype) {
        $localcontext = $context;
        $localcontext['classtype'] = $classtype;
        $localcontext['title']     = getlodeltextcontents("classtype_$classtype", 'admin');
	call_user_func("code_do_$funcname", $localcontext);
    }
}

define('INC_LOOPS', 1);
?>
