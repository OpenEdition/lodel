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


if (file_exists($home."boucles_local.php")) require_once($home."boucles_local.php");

/*********************************************************************/
/*  Boucle permettant de trouver depuis une publication toutes les   */
/*  infos concernant la publication parente la plus haute dans       */
/*  l'arborescence et qui ne soit pas une au sommet.                 */
/*                                                                   */
/*  Appeller cette boucle dans le code lodelscript par :             */
/*  <BOUCLE NAME="topparentpubli">[#ID]</BOUCLE>                     */
/*********************************************************************/
function loop_topparentpubli(&$context,$funcname)
{
  // $context est un tableau qui contient une pile. Si on fait $context[toto] 
  // alors [#TOTO] sera accessible dans lodelscript !!!
  $id=$context[id];       // On récupère le paramètre id

  $result=mysql_query("SELECT * FROM $GLOBALS[publicationstypesjoin],$GLOBALS[tp]relations WHERE $GLOBALS[tp]entites.id=id1 AND id2='$id' AND $GLOBALS[tp]entites.statut>".($GLOBALS[droitvisiteur] ? -64 : 0)." ORDER BY degres DESC LIMIT 1,1") or die (mysql_error());

  while ($row=mysql_fetch_assoc($result)) {       
    // On fait un array_merge pour récupérer toutes les infos contenues
    // dans le tableau $row et les mettre dans le tableau $context.
    $localcontext=array_merge($context,$row);
    // Puis on fait appel à la fonction en concaténant avant "code_" 
    // et en lui passant en paramètre la dernière valeur.
    // C'est équivalent à un return et ça permet d'avoir les
    // valeurs accessibles en lodelscript. 
    call_user_func("code_do_$funcname",$localcontext);
    return;
  }
}

/*********************************************************************/
/*  Boucle permettant de trouver depuis un document toutes les       */
/*  infos concernant la publication parente la plus haute dans       */
/*  l'arborescence et qui ne soit pas une série.                     */
/*  La condition d'arrêt de la boucle est la chaine de caractères :  */
/*  "serie_"                                                         */
/*                                                                   */
/*  Appeller cette boucle dans le code lodelscript par :             */
/*  <BOUCLE NAME="topparentdoc">[#ID]</BOUCLE>                       */
/*********************************************************************/
function loop_topparentdoc(&$context,$funcname)
{
  topparentpubli($context,funcname);
}


function loop_publisparentes(&$context,$funcname,$critere="")
{
  $id=intval($context[id]);
  if (!$id) return;
  
  $result=mysql_query("SELECT *, type  FROM $GLOBALS[publicationstypesjoin],$GLOBALS[tp]relations WHERE $GLOBALS[tp]entites.id=id1 AND id2='$id' AND $GLOBALS[tp]entites.statut>".($GLOBALS[droitvisiteur] ? -64 : 0)." ORDER BY degres DESC") or die (mysql_error());
    
  while ($row=mysql_fetch_assoc($result)) {
    $localcontext=array_merge($context,$row);
    call_user_func("code_do_$funcname",$localcontext);
  }
}


function loop_toc($context,$funcname,$arguments)

{
  if (!isset($arguments[text])) {
    if ($GLOBALS[droitvisiteur]) die("ERROR: the loop \"toc\" requires a TEXT attribut");
    return;
  }

  if (!preg_match_all("/<(r2r:section(\d+))>(.*?)<\/\\1>/is",$arguments[text],$results,PREG_SET_ORDER)) {
    if (!preg_match_all("/<(div)\s+class=\"section(\d+)\">(.*?)<\/\\1>/is",$context[texte],$results,PREG_SET_ORDER)) return;
  }
  foreach($results as $result) {
    $localcontext=$context;
    $localcontext[tocid]=(++$tocid);
    $localcontext[titre]=$result[3];
    $localcontext[niveau]=intval($result[2]);
    call_user_func("code_do_$funcname",$localcontext);
  }
}




function loop_paragraphes($context,$funcname,$arguments)

{
  if (!isset($arguments[text])) {
    if ($GLOBALS[droitvisiteur]) die("ERROR: the loop \"paragraph\" requires a TEXT attribut");
    return;
  }

  preg_match_all("/<p\b[^>]*>(.*?)<\/p>/is",$arguments[text],$results,PREG_SET_ORDER);

  $count=0;
  foreach($results as $result) {
    $localcontext=$context;
    $localcontext[compteur]=(++$count);
    $localcontext[paragraphe]=$result[0];
    call_user_func("code_do_$funcname",$localcontext);
  }
}



function loop_extrait_images($context,$funcname,$arguments)

{
  if (!isset($arguments[text])) {
    if ($GLOBALS[droitvisiteur]) die("ERROR: the loop \"paragraph\" requires a TEXT attribut");
    return;
  }
  if ($arguments[limit]) {
    list($start,$length)=explode(",",$arguments[limit]);
    $end=$start+$length;
  } else {
    $start=0;
  }

  $validattrs=array("src","alt","border","style","class","name");

  preg_match_all("/<img\b([^>]*)>/",$arguments[text],$results,PREG_SET_ORDER);

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

    $localcontext[compteur]=(++$count);
    $localcontext[image]=$result[0];
    call_user_func("code_do_$funcname",$localcontext);
  }
}



function previousnext ($dir,$context,$funcname,$arguments)

{
  if (!isset($arguments[id])) {
    if ($GLOBALS[droitvisiteur]) die("ERROR: the loop \"previous\" requires a ID attribut");
    return;
  }

  $id=intval($arguments[id]);
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

  $statutmin=$GLOBALS[droitvisiteur] ? -32 : 0;

  $querybase="SELECT e3.*,t3.type,t3.classe FROM $GLOBALS[tp]entites as e0 INNER JOIN $GLOBALS[tp]types as t0 ON e0.idtype=t0.id, $GLOBALS[tp]entites as e3 INNER JOIN $GLOBALS[tp]types as t3 ON e3.idtype=t3.id WHERE e0.id='$id' AND e3.idparent=e0.idparent AND e3.statut>$statutmin AND e0.statut>$statutmin AND e3.ordre".$compare."e0.ordre AND ".mysql_not_xor("t0.classe='publications'","t3.classe='publications'")." ORDER BY e3.ordre ".$sort." LIMIT 0,1";

  do {
    $result=mysql_query ($querybase) or die (mysql_error());
    if (mysql_num_rows($result)) { // found
      $localcontext=array_merge($context,mysql_fetch_assoc($result));
      break;
    }

    if (!$arguments[through]) break;
    $quotedtypes=join("','",explode(",",addslashes($arguments[through])));
    if (!$quotedtypes) break;
    $result=mysql_query("SELECT id FROM $GLOBALS[tp]types WHERE type IN ('$quotedtypes')");
    while (list($idtype)=mysql_fetch_row($result)) { $idtypes[]=$idtype; }
    if (!$idtypes) break;
    $types=join("','",$idtypes);
    // ok, on a pas trouve on cherche alors le pere suivant l'entite (e0) et son premier fils (e2)
    // not found, well, we look for the next/previous parent above and it's first/last son.

    $result=mysql_query ("SELECT e3.*,t3.type,t3.classe FROM $GLOBALS[tp]entites as e0 INNER JOIN $GLOBALS[tp]types as t0 ON e0.idtype=t0.id, $GLOBALS[tp]entites as e1, $GLOBALS[tp]entites as e2, $GLOBALS[tp]entites as e3 INNER JOIN $GLOBALS[tp]types as t3 ON e3.idtype=t3.id  WHERE e0.id='$id' AND e1.id=e0.idparent AND e2.idparent=e1.idparent AND e3.idparent=e2.id AND e2.ordre".$compare."e1.ordre AND e1.idtype IN ('$types') AND e2.idtype IN ('$types') AND e0.statut>$statutmin AND e1.statut>$statutmin AND e2.statut>$statutmin AND e3.statut>$statutmin AND  ".mysql_not_xor("t0.classe='publications'","t3.classe='publications'")." ORDER BY e2.ordre ".$sort.", e3.ordre ".$sort." LIMIT 0,1") or die (mysql_error());

    if (mysql_num_rows($result)) {
      $localcontext=array_merge($context,mysql_fetch_assoc($result));
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


function mysql_not_xor($a,$b) 

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

  if (!isset($arguments[url])) {
    if ($GLOBALS[droitvisiteur]) die("ERROR: the loop \"rss\" requires a URL attribut");
    return;
  }
  if ($arguments[refresh] && !is_numeric($arguments[refresh])) {
    if ($GLOBALS[droitvisiteur]) die("ERROR: the REFRESH attribut in the loop \"rss\" has to be a number of second ");
    $arguments[refresh]=0;
  }


  require_once($home."magpierss/rss_fetch.inc");

  $rss = fetch_rss( $arguments['url'] , $arguments['refresh'] ? $arguments['refresh'] : 3600);

  if (!$rss) {
    if ($GLOBALS[droitediteur]) {
      echo "<b>Warning: Erreur de connection RSS sur l'url ",$arguments['url'],"</b><br/>";
    } else {
      if ($GLOBALS[contactbug]) @mail($contactbug,"[WARNING] LODEL - $GLOBALS[version] - $GLOBALS[database]","Erreur de connection RSS sur l'url ".$arguments['url']);
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
  $context['nbresultats']=count($items);
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


?>
