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


if (file_exists($home."loops_local.php")) require_once("loops_local.php");



function loop_parentsentities(&$context,$funcname,$critere="")
{

  global $db;
  $id=intval($context['id']);
  if (!$id) return;
  $result=$db->execute(lq("SELECT *, type  FROM #_entitiestypesjoin_,#_TP_relations WHERE #_TP_entities.id=id1 AND id2='".$id."' AND nature='P' AND #_TP_entities.status>".($GLOBALS['lodeluser']['visitor'] ? -64 : 0)." ORDER BY degree DESC")) or dberror();

  while (!$result->EOF) {
    $localcontext=array_merge($context,$result->fields);
    call_user_func("code_do_$funcname",$localcontext);
    $result->MoveNext();
  }
}


function loop_toc($context,$funcname,$arguments)

{
#  print_R($arguments);
#  echo isset($arguments['text'])===false;
#  if (! (isset($arguments['text'])) ) {
#    if ($GLOBALS['lodeluser']['visitor']) die("ERROR: the loop \"toc\" requires a TEXT attribut");
#    return;
#  }

  if (!preg_match_all("/<(r2r:section(\d+))>(.*?)<\/\\1>/is",$arguments['text'],$results,PREG_SET_ORDER)) {
    if (!preg_match_all("/<(div)\s+class=\"section(\d+)\">(.*?)<\/\\1>/is",$arguments['text'],$results,PREG_SET_ORDER)) {
      if (function_exists("code_alter_$funcname")) 
	call_user_func("code_alter_$funcname",$context);
      return;
    }
  }

  if (function_exists("code_before_$funcname")) 
    call_user_func("code_before_$funcname",$context);


  foreach($results as $result) {
    $localcontext=$context;
    $localcontext['tocid']=(++$tocid);
    $localcontext['title']=$result[3];
    $localcontext['level']=intval($result[2]);
    if ($tocid==1 && function_exists("code_dofirst_$funcname")) {
      call_user_func("code_dofirst_$funcname",$localcontext);
    } elseif ($tocid==count($results) && function_exists("code_dolast_$funcname")) {
      call_user_func("code_dolast_$funcname",$localcontext);
    } else {
      call_user_func("code_do_$funcname",$localcontext);
    }
  }

  if (function_exists("code_after_$funcname")) 
    call_user_func("code_after_$funcname",$context);

}




function loop_paragraphs($context,$funcname,$arguments)

{
  if (!isset($arguments['text'])) {
    if ($GLOBALS['lodeluser']['visitor']) die("ERROR: the loop \"paragraph\" requires a TEXT attribut");
    return;
  }

  preg_match_all("/<p\b[^>]*>(.*?)<\/p>/is",$arguments['text'],$results,PREG_SET_ORDER);

  $count=0;
  foreach($results as $result) {
    $localcontext=$context;
    $localcontext['count']=(++$count);
    $localcontext['paragraph']=$result[0];
    call_user_func("code_do_$funcname",$localcontext);
  }
}



function loop_extract_images($context,$funcname,$arguments)

{
  if (!isset($arguments['text'])) {
    if ($GLOBALS['lodeluser']['visitor']) die("ERROR: the loop \"paragraph\" requires a TEXT attribut");
    return;
  }
  if ($arguments['limit']) {
    list($start,$length)=explode(",",$arguments['limit']);
    $end=$start+$length;
  } else {
    $start=0;
  }

  $validattrs=array("src","alt","border","style","class","name");

  preg_match_all("/<img\b([^>]*)>/",$arguments['text'],$results,PREG_SET_ORDER);

  if (!$end) $end=count($results);

  $count=0;
  for($j=$start; $j<$end; $j++)  {
    $result=$results[$j];
    $localcontext=$context;
    $attrs=preg_split("/\"/",$result[1]);
#    print_r($attrs);
    $countattrs=2*intval(count($attrs)/2);
    for($i=0; $i<$countattrs; $i+=2) {
      $attr=trim(str_replace("=","",$attrs[$i]));
#      print_r($attrs[$i]);
#      echo ":$attr $attrs[$i]<br>";
      if (in_array($attr,$validattrs)) $localcontext[$attr]=$attrs[$i+1];
    }

    $localcontext['count']=(++$count);
    $localcontext['image']=$result[0];
    call_user_func("code_do_$funcname",$localcontext);
  }
}



function previousnext ($dir,$context,$funcname,$arguments)

{
  global $db;

  if (!isset($arguments['id'])) {
    if ($GLOBALS['lodeluser']['visitor']) die("ERROR: the loop \"previous\" requires a ID attribut");
    return;
  }

  $id=intval($arguments['id']);
//
// cherche le document precedent ou le suivante
//
  if ($dir=="previous") {
    $sort="DESC";
    $compare="<";
  } else {
    $sort="ASC";
    $compare=">";
  }

  $statusmin=$GLOBALS['lodeluser']['visitor'] ? -32 : 0;

  $querybase="SELECT e3.*,t3.type,t3.class FROM $GLOBALS[tp]entities as e0 INNER JOIN $GLOBALS[tp]types as t0 ON e0.idtype=t0.id, $GLOBALS[tp]entities as e3 INNER JOIN $GLOBALS[tp]types as t3 ON e3.idtype=t3.id WHERE e0.id='$id' AND e3.idparent=e0.idparent AND e3.status>$statusmin AND e0.status>$statusmin AND e3.rank".$compare."e0.rank AND ".sql_not_xor("t0.class='publications'","t3.class='publications'")." ORDER BY e3.rank ".$sort; ###." LIMIT 0,1";

  do {
    $row=$db->getRow($querybase);
    if ($row===false) dberror();
    if ($row) { // found
      $localcontext=array_merge($context,$row);
      break;
    }

    if (!$arguments['through']) break;
    $quotedtypes=join("','",explode(",",addslashes($arguments['through'])));
    if (!$quotedtypes) break;
    $result=$db->execute(lq("SELECT id FROM #_TP_types WHERE type IN ('$quotedtypes')")) or dberror();


  while (!$result->EOF) {
      $idtypes[]=$result->fields['id'];
      $result->MoveNext();
    }
    if (!$idtypes) break;
    $types=join("','",$idtypes);
    // ok, on a pas trouve on cherche alors le pere suivant l'entite (e0) et son premier fils (e2)
    // not found, well, we look for the next/previous parent above and it's first/last son.

    $row=$db->getrow(lq("SELECT e3.*,t3.type,t3.class FROM $GLOBALS[tp]entities as e0 INNER JOIN $GLOBALS[tp]types as t0 ON e0.idtype=t0.id, $GLOBALS[tp]entities as e1, $GLOBALS[tp]entities as e2, $GLOBALS[tp]entities as e3 INNER JOIN $GLOBALS[tp]types as t3 ON e3.idtype=t3.id  WHERE e0.id='$id' AND e1.id=e0.idparent AND e2.idparent=e1.idparent AND e3.idparent=e2.id AND e2.rank".$compare."e1.rank AND e1.idtype IN ('$types') AND e2.idtype IN ('$types') AND e0.status>$statusmin AND e1.status>$statusmin AND e2.status>$statusmin AND e3.status>$statusmin AND  ".sql_not_xor("t0.class='publications'","t3.class='publications'")." ORDER BY e2.rank ".$sort.", e3.rank ".$sort));
    if ($row===false) dberror();

    if ($row) {
      $localcontext=array_merge($context,$row);
      break;
    }
  } while (0);

  if ($localcontext) {
    call_user_func("code_do_$funcname",$localcontext);
  } else {
    if (function_exists("code_alter_$funcname")) 
      call_user_func("code_alter_$funcname",$context);
  }
}


function sql_not_xor($a,$b) 

{
  return "((($a) AND ($b)) OR (NOT ($a) AND NOT ($b)))";
}


function loop_previous ($context,$funcname,$arguments)

{
  previousnext("previous",$context,$funcname,$arguments);
}

function loop_next ($context,$funcname,$arguments)

{
  previousnext("next",$context,$funcname,$arguments);
}



/*********************************************************************/
/*  Loop for reading RSS Flux using Magpie                           */
/*                                                                   */
/*  Appeller cette boucle dans le code lodelscript par :             */
/*  <BOUCLE NAME="topparentdoc">[#ID]</BOUCLE>                       */
/*********************************************************************/


function loop_rss ($context,$funcname,$arguments)

{
  define ("MAGPIE_CACHE_ON",TRUE);
  define ("MAGPIE_CACHE_DIR","./CACHE");
  define ("DIRECTORY_SEPARATOR","/");
  define('MAGPIE_OUTPUT_ENCODING', 'UTF-8');

  if (!isset($arguments['url'])) {
    if ($GLOBALS['lodeluser']['visitor']) die("ERROR: the loop \"rss\" requires a URL attribut");
    return;
  }
  if ($arguments['refresh'] && !is_numeric($arguments['refresh'])) {
    if ($GLOBALS['lodeluser']['visitor']) die("ERROR: the REFRESH attribut in the loop \"rss\" has to be a number of second ");
    $arguments['refresh']=0;
  }


  require_once("magpierss/rss_fetch.inc");

  $rss = fetch_rss( $arguments['url'] , $arguments['refresh'] ? $arguments['refresh'] : 3600);

  if (!$rss) {
    if ($GLOBALS['lodeluser']['editor']) {
      echo "<b>Warning: Erreur de connection RSS sur l'url ",$arguments['url'],"</b><br/>";
    } else {
      if ($GLOBALS['contactbug']) @mail($contactbug,"[WARNING] LODEL - $GLOBALS[version] - $GLOBALS[database]","Erreur de connection RSS sur l'url ".$arguments['url']);
      return;
    }
  }

  $localcontext=$context;
  foreach (array(# obligatoire
		   "title",
		   "link",
		   "description",
		   # optionel
		   "language","copyright","managingEditor","webMaster","pubDate","lastBuildDate","category","generator","docs","cloud","ttl","rating","textInput","skipHours","skipDays")
	     as $v) $localcontext[strtolower($v)]=$rss->channel[$v];

  // special treatment for "image"
  if ($rss->channel['image']) {
      $localcontext['image_url']=$rss->channel['image']['url'];
      $localcontext['image_title']=$rss->channel['image']['title'];
      $localcontext['image_link']=$rss->channel['image']['link'];
      $localcontext['image_description']=$rss->channel['image']['description'];
      $localcontext['image_width']=$rss->channel['image']['link'];
      if (!$localcontext['image_width']) $localcontext['image_width']=88;
      if ($localcontext['image_width']>144) $localcontext['image_width']=144;
      $localcontext['image_height']=$rss->channel['image']['link'];
      if (!$localcontext['image_height']) $localcontext['image_height']=31;
      if ($localcontext['image_height']>400) $localcontext['image_height']=400;
  }

  $localcontext['rssobject']=$rss;
  if (function_exists("code_before_$funcname")) call_user_func("code_before_$funcname",$context);
  call_user_func("code_do_$funcname",$localcontext);
  if (function_exists("code_after_$funcname")) call_user_func("code_after_$funcname",$context);
}

function loop_rssitem($context,$funcname,$arguments)

{
  // check whether there are some items in the rssobject.
  if (!$context['rssobject'] || !$context['rssobject']->items) {
    if (function_exists("code_alter_$funcname")) 
      call_user_func("code_alter_$funcname",$localcontext);
    return;
  }

  // yes, there are, let's loop over them.
  if (function_exists("code_before_$funcname")) call_user_func("code_before_$funcname",$localcontext);

  $items=$context['rssobject']->items;
  $context['nbresults']=$context['nbresultats']=count($items);
  $count=0;
  if ($arguments['limit']) {
    list($start,$length)=preg_split("/\s*,\s*/",$arguments['limit']);
  } else {
    $start=0;
    $length=count($context['rssobject']->items);
  }

  for($i=$start; $i<$start+$length; $i++) {
    $item=$items[$i];
    $localcontext=$context;
    $count++;
    $localcontext['count']=$count;
    foreach (array("title","link","description","author","category","comments","enclosure","guid","pubDate","source")
	     as $v) $localcontext[strtolower($v)]=$item[$v];
    call_user_func("code_do_$funcname",$localcontext);
  }
  if (function_exists("code_after_$funcname")) call_user_func("code_after_$funcname",$localcontext);
}

/* added 2005-03-09
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

/*
 * Function _constructPages 
 * construct page listing by given nbresults and currentoffset in the results
 * 
 */
function _constructPages(&$context,$funcname,$arguments)
{
	//get current offset and construct url
	$arguments['limit'] = $context['limitinfo'];
	if(!$context['limitinfo'])
		return;
	$offsetname=$context['offsetname'];
	$currentoffset = ($_REQUEST[$offsetname]? $_REQUEST[$offsetname] : 0);
	$currenturl = basename($_SERVER['SCRIPT_NAME'])."?";
	$cleanquery=preg_replace("/(^|&)".$offsetname."=\d+/","",$_SERVER['QUERY_STRING']);
	if ($cleanquery[0]=="&") $cleanquery=substr($cleanquery,1); 
  if ($cleanquery) $currenturl.=$cleanquery."&";

 	//construct next url
 	if($context['nbresults'] > ($currentoffset+$arguments['limit']))
 		$context['nexturl']=$currenturl.$offsetname."=".($currentoffset + $arguments['limit']);
 	else
 		$context['nexturl'] = "";
  //construct previous url
  if($currentoffset > 0)
  	$context['previousurl'] = $currenturl.$offsetname."=".($currentoffset - $arguments['limit']);
  else
  	$context['previousurl'] ="";
  //construct pages table
  $pages = array();
  //previous pages 
   	$i = 0;
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
?>
