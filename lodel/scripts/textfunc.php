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


require_once 'unset_globals.php';

if (file_exists($home."textfunc_local.php")) require_once($home."textfunc_local.php");


# fonction largement reprises de SPIP

require_once($home."func.php");

#function cleanurl ($texte)
#
#{
#  $texte=strtr(strtolower($texte),
#		"ÈÉÊËèéêëÀÁÂÃÄÅÆàáâãäåæÌÍÎÏìíîïÒÓÔÕÖØðòóôõöøÙÚÛÜùúûüÇçýÿÝÑñ.,?!' ",
#		"eeeeeeeeaaaaaaaaaaaaaaiiiiiiiioooooooooooouuuuuuuuccyyynn-------");
#  $texte=preg_replace (array("/[\200-\377]/","/ß/","/^-/","/--+/"),
#			array("","ss","","-"),$texte);
#
#  return urlencode($texte);
#}
#

function pluriel($texte)

{ return intval($texte)>1 ? "s" : ""; }

function lettrine($texte)

{
  //return preg_replace("/^(\s*(?:<[^>]+>)*\s*)([\w\"])/su","\\1<span class=\"lettrine\">\\2</span>",$texte);
  // utf-8 ok

  return preg_replace("/^(\s*(?:<[^>]+>)*\s*)([\w\"])/s","\\1<span class=\"lettrine\">\\2</span>",$texte);

}


function nbsp($texte) 

{ return $texte ? $texte : "&nbsp;"; }

/*
 * Upercase the first letter of Texte
 *
 */

function majuscule($texte)

{
  //return preg_replace("/^(\s*(?:<[^>]+>)*\s*)(\w)/sue",'"\\1".strtoupper("\\2")',$texte);
  // utf-8 ok

  return preg_replace("/^(\s*(?:<[^>]+>)*\s*)(\w)/se",'"\\1".strtoupper("\\2")',$texte);
}


function textebrut($letexte) {
  $letexte = preg_replace("/(<[^>]+>|&nbsp;|[\n\r\t])+/", " ", $letexte);
  return $letexte;
}

function cuttext($text,$length) {
#  $texte = substr($texte, 0, ($long +50) * 3); /* heuristique pour prendre seulement le necessaire */

  $GLOBALS['textfunc_hasbeencut']=false;
  $open=strpos($text,"<");
  if ($open===false || $open>$length) return cut_without_tags($text,$length);

  $length-=$open;
  $stack=array();

  while ($open!==FALSE) {
    $close=strpos($text,">",$open);
    if ($text[$open+1]=="/") {
      array_pop($stack); // fermante
    } elseif ($tags[$close-1]!="/") {
      array_push($stack,
                                           "</".preg_replace("/\s.*/","",
                                                             substr($text,$open+1,$close-1-$open))
                                           .">"); // ouvrante
    }
    $open=strpos($text,"<",$close);
    $piecelen=$open-1-$close;

    if ($open===FALSE || $piecelen>$length)
      return substr($text,0,$close+1).
        cut_without_tags(substr($text,$close+1,$length+2),$length).  // 2 pour laisser de la marge
        join("",array_reverse($stack));

    $length-=$piecelen;
    #echo $length,"<br>";
  }
  return $text;
}

function cut_without_tags($text,$length) {
  $text2 = substr($text." ", 0, $length);
  if (strlen($text2)<strlen($text)) { $GLOBALS['textfunc_hasbeencut']=true;}
  $spacepos=strrpos($text2," ");
##  $text2 = ereg_replace("([^[:space:]][[:space:]]+)[^[:space:]]*$", "\\1", $text2);

  $text2 = preg_replace("/\S+$/", "", $text2);

  return $text2;
}

function hasbeencut() {
  return $GLOBALS['textfunc_hasbeencut'] ? true : false;
}


function couper($texte,$long) {
#  $texte = substr($texte, 0, ($long +50) * 3); /* heuristique pour prendre seulement le necessaire */



  $open=strpos($texte,"<");
  if ($open===FALSE || $open>$long) return couper_sans_tags($texte,$long);
  $long-=$open;
  $stack=array();

  while ($open!==FALSE) {
    $close=strpos($texte,">",$open);
    if ($texte[$open+1]=="/") {
      array_pop($stack); // fermante
    } elseif ($tags[$close-1]!="/") {
      array_push($stack,
					   "</".preg_replace("/\s.*/","",
							     substr($texte,$open+1,$close-1-$open))
					   .">"); // ouvrante
    }
    $open=strpos($texte,"<",$close);
    $piecelen=$open-1-$close;

    if ($open===FALSE || $piecelen>$long) 
      return substr($texte,0,$close+1).
	couper_sans_tags(substr($texte,$close+1,$long),$long).
	join("",array_reverse($stack));	

    $long-=$piecelen;
    #echo $long,"<br>";
  }
  return $texte;
}

function couper_sans_tags($texte,$long) {
  #$texte2 = substr($texte, 0, ($long+10)* 1.1); /* heuristique pour prendre seulement le necessaire */
#  if (strlen($texte2) < strlen($texte)) $plus_petit = true;
  
  $texte2 = substr($texte." ", 0, $long);
#  echo ":",$texte2;
  $texte2 = ereg_replace("([^[:space:]][[:space:]]+)[^[:space:]]*$", "\\1", $texte2);
#  if ((strlen($texte2) + 3) < strlen($texte)) $plus_petit = true;
  return trim($texte2);
}

#function couper($texte,$long) {
#  $texte = substr($texte, 0, ($long +20) * 2); /* heuristique pour prendre seulement le necessaire */
#
#  $arr=preg_split("/(<\/?)([^>]+?)(\/?".">)/",$texte,-1,PREG_SPLIT_DELIM_CAPTURE);
#  $count=count($arr);
#  $piecelen=strlen($arr[0]);
#  if ($long<$piecelen) return couper_sans_tags($arr[0],$long);
#  $long-=$piecelen;
#  $stack=array();
#  for($i=1; $i<$count; $i+=4) {
#    if ($arr[$i]!="</" && !$arr[$i+2]!="/>") array_push($stack,"</".$arr[$i+1].">"); // ouvrante
#    if ($arr[$i]=="</") array_pop($stack); // fermante
#
#    $piecelen=strlen($arr[$i+3]);
#    if ($long<$piecelen) return join("",array_slice($arr,0,$i)).couper_sans_tags($arr[$i+3],$long).join("",array_reverse($stack));
#   
#    $long-=$piecelen;
#  }
#}




function couperpara($texte,$long) {

  $pos=-1;
  do {
    $pos=strpos($texte,"</p>",$pos+1);
    $long--;
  } while ($pos!==FALSE && $long>0);
  
  return $pos>0 ? substr($texte,0,$pos+4) : $texte;
}




function spip ($letexte)

{
  $puce="<IMG SRC=\"Images/smallpuce.gif\">";
  // Harmoniser les retours chariot
  $letexte = ereg_replace ("\r\n?", "\n",$letexte);

  // Corriger HTML
  $letexte = eregi_replace("</?p>","\n\n\n",$letexte);

  //
  // Raccourcis liens
  //
  $regexp = "\[([^][]*)->([^]]*)\]";
  $texte_a_voir = $letexte;
  $texte_vu = '';
  while (ereg($regexp, $texte_a_voir, $regs)){
    $lien_texte = $regs[1];
    $lien_url = trim($regs[2]);
    $compt_liens++;
    $lien_interne = false;

    $insert = "<a href=\"$lien_url\">".$lien_texte."</a>";
    $zetexte = split($regexp,$texte_a_voir,2);
    $texte_vu .= $zetexte[0].$insert;
    $texte_a_voir = $zetexte[1];
  }
  $letexte = $texte_vu.$texte_a_voir; // typo de la queue du texte

  //
  // Ensemble de remplacements implementant le systeme de mise
  // en forme (paragraphes, raccourcis...)
  //
  $letexte = trim($letexte);
  $cherche1 = array(
		    /* 1 */		"/\n(----+|____+)/",
		    /* 2 */		"/^-/",
		    /* 3 */		"/\n-/",
		    /* 4*/		"/(( *)\n){2,}/",
		    /* 5 */		"/\{\{\{/",
		    /* 6 */		"/\}\}\}/",
		    /* 7 */		"/\{\{/",
		    /* 8 */		"/\}\}/",
		    /* 9 */		"/\{/",
		    /* 10 */	"/\}/",
		    /* 11 */	"/(<br>){2,}/",
		    /* 12 */	"/<p>([\n]*)(<br>)+/",
		    /* 13 */	"/<p>/"
					);
  $remplace1 = array(
		     /* 1 */ 	"\n<hr>\n",
		     /* 2 */ 	"$puce ",
		     /* 3 */ 	"\n<br>$puce ",
		     /* 4 */		"\n<p>",
		     /* 5 */ 	"$debut_intertitre",
		     /* 6 */ 	"$fin_intertitre",
		     /* 7 */ 	"<b>",
		     /* 8 */ 	"</b>",
		     /* 9 */ 	"<i>",
		     /* 10 */ 	"</i>",
		     /* 11 */ 	"\n<p>",
		     /* 12 */ 	"\n<p>",
		     /* 13 */	"<p>"
				);
  $letexte = preg_replace($cherche1, $remplace1, $letexte);
  return $letexte;
}


function propre($letexte) {
	return traite_raccourcis(trim($letexte));
}


function formateddate($date,$format) 

{
  return strftime($format,strtotime($date));
}

function formatedtime($time,$format) 

{
  return strftime($format,$time);
}



function humandate($s)

{ # verifie que la date est sous forme mysql

  if (preg_match("/^(\d\d\d\d)-(\d\d)-(\d\d)/",$s,$result)) {
    if ($result[1]>9000) return "jamais";
    if ($result[1]==0) return "";

    $mois[1]="janvier";
    $mois[2]="fÃ©vrier";
    $mois[3]="mars";
    $mois[4]="avril";
    $mois[5]="mai";
    $mois[6]="juin";
    $mois[7]="juillet";
    $mois[8]="aoÃ»t";
    $mois[9]="septembre";
    $mois[10]="octobre";
    $mois[11]="novembre";
    $mois[12]="dÃ©cembre";
    $ret=intval($result[3])." ".$mois[intval($result[2])]." ".intval($result[1]);
  }
  // time
  if (preg_match("/(\s*\d\d:\d\d:\d\d)$/",$s,$result)) {
    $ret.=$result[0];
  }

  return $ret ? $ret : $s;
}


function tocable($text,$level=10)

{
  static $tocind=0;

  $sect="1";
  for($i=2;$i<=$level;$i++) $sect.="|$i";

  function tocable_callback($result) {
      static $tocind=0;
      $tocind++;
      return $result[1].'<a href="#tocfrom'.$tocind.'" id="tocto'.$tocind.'">'.$result[3].'</a>'.$result[4];
  }

  return preg_replace_callback("/(<(r2r:section(?:$sect))\b(?:[^>]*)>)(.*?)(<\/\\2>)/s","tocable_callback",$text);
}

## version souple qui travaille sur les div... mais c'est une mauvaise habitude.
## function tocable($level,$text=-1)
## 
## {
##   static $tocind=0;
## 
##   if ($text==-1) { $text=$level; $level=10; }// gestion etrange du level par defaut.
##   $sect="1";
##   for($i=2;$i<=$level;$i++) $sect.="|$i";
## 
##   $arr=preg_split("/(<\/?)(r2r:section(?:$sect)>|div\b[^>]*>)/",$text,-1,PREG_SPLIT_DELIM_CAPTURE);
##   $count=count($arr);
##   $stack=array();
##   for($i=1; $i<$count; $i+=3) {
##     if ($arr[$i]=="</") { // fermante
##       $arr[$i+1].=array_pop($stack);
##     } else { // ouvrante
##       if (strpos("r2r:section",$arr[$i+1])===0 ||
## 	  preg_match("/^div\b[^>]+class\s*=\s*\"section($sect)\"/",$arr[$i+1]) ) { // toc it
## 
## 	$tocind++;
## 	array_push($stack,"</a>");
## 	$arr[$i]='<a href="#tocfrom'.$tocind.'" NAME="tocto'.$tocind.'">'.$arr[$i];
##       } else { // don't toc it
## 	array_push($stack,"");
##       }
##     }
##   } // for
##   return join("",$arr);
## 
## 
## }


function multilingue($text,$lang)

{
  preg_match("/<r2r:ml lang=\"".strtolower($lang)."\">(.*?)<\/r2r:ml>/s",$text,$result);
  return $result[1];
}



function vignette($text,$width)

{
  global $home;
  //  if (preg_match("/^<img\b[^>]+src=\"([^\">]+)\"/",$text,$result)) $text=$result[1];
  if (!$text) return;

  if (!preg_match("/^docannexe\/image\/[^\.\/]+\/[^\/]+$/",$text)) {
    return "invalid path to image";
  }

  if (defined("SITEROOT")) $text=SITEROOT.$text;
  if (!file_exists($text)) return "file does not exist";

  if (!preg_match("/^(.*)\.([^\.]+)$/",$text,$result)) return "file without extension";

  $vignettefile=$result[1]."-small$width.".$result[2];

  if (file_exists($vignettefile) && filemtime($vignettefile)>=filemtime($text)) return $vignettefile;

  // creer la vignette (de largeur width ou de hauteur width en fonction de la forme
  require_once($home."images.php");

  if (!resize_image($width,$text,$vignettefile,"+")) return "image resizing failed";

  return $vignettefile;
}


# renvoie les attributs pour une image
function sizeattributs($text)

{
  $result=getImageSize($text);
  return $result[3];
}

/** 
 * Supprimer les appels de notes de pied de page d'un texte.
 */

function removefootnotes($text)
{
  return preg_replace('/<a class="footnotecall"[^>]*>.*?<\/a>/s',"",$text);
}
/** 
 * Supprimer les appels de notes de fin de document.
 */

function removeendnotes($text)
{
  return preg_replace('/<a class="endnotecall"[^>]*>.*?<\/a>/s',"",$text);
}

/** 
 * Fonction permettant de supprimer les appels de notes d'un texte.
 */

function removenotes($text)
{
  return preg_replace('/<a class="(foot|end)notecall"[^>]*>.*?<\/a>/s',"",$text);
}


/** 
 * Fonction qui enleve les images
 */

function removeimages($text)
{
  return preg_replace('/<img\b[^>]*>/',"",$text);
}


function removetags($text, $tags){
  foreach(explode(',', $tags) as $v){
    $find[] = '/<'.trim($v).'\b[^>]*>/';
    $find[] = '/<\/'.trim($v).'>/';
  }
  return preg_replace($find, "", $text);
}


/**
 * Fonction qui dit si une date est vide ou non
 */
function isadate($text)
{
  return $text!="0000-00-00";
}

/**
 * Fonction qui remplace les guillemets d'un texte par leur nom d'entité (&quot;)
 */
function replacequotationmark($text)
{
        return str_replace("\"","&quot;",$text);
}

//
// fonction utiliser pour les options (dans l'interface uniquement)
//

function yes ($texte)

{ return $texte ? "checked" : "";}

function no ($texte)

{ return !$texte ? "checked" : "";}

function eq($str,$texte)

{ return $texte==$str ? "checked" : ""; }

/**
 * fonction pour les notes
 */


function notes($texte,$type)
{
#  preg_match_all('/<div id="sd[^>]+>.*?<\/div>/',$texte,$results,PREG_PATTERN_ORDER);
#  return $texte;
  preg_match_all('/<div class="(?:foot|end)notebody"[^>]*>.*?<\/div>/',$texte,$results,PREG_PATTERN_ORDER);
#  print_r($results);
  if ($type=="nombre") {
    $notes=preg_grep('/<a class="(foot|end)notedefinition[^>]*>\[?[0-9]+\]?<\/a>/',$results[0]);
  } elseif ($type=="lettre") {
    $notes=preg_grep('/<a class="(foot|end)notedefinition[^>]*>\[?[a-zA-Z]+\]?<\/a>/',$results[0]);
  } elseif ($type=="asterisque") {
    $notes=preg_grep('/<a class="(foot|end)notedefinition[^>]*>\[?\*+\]?<\/a>/',$results[0]);
  } else die ("type \"$type\" inconnues");
  return join("",$notes);
}

/**
 * fonctions pour le nettoyage de base des champs importes
 */

function tocss($text,$options="")

{
  global $home;
  include_once($home."balises.php");
  $srch=array();   $rpl=array();
  if ($options=="heading") {
    array_push($srch,
	       "/<r2r:section(\d+\b[^>]*)>/",
	       "/<\/r2r:section(\d+)>/");
    array_push($rpl,
	       '<h\\1>',
	       '</h\\1>');
  }
  array_push($srch,
	     "/<r2r:(\w+)\b[^>]*>/", // replace les autres balises r2r par des DIV
	     "/<\/r2r:[^>]+>/");
  array_push($rpl,
	     '<div class="\\1">',
	     "</div>");

  return preg_replace($srch,$rpl,traite_separateur($text));
}

/**
 * Permet de savoir si un lien est relatif 
 */ 

function isrelative($lien)
{
	$test=parse_url($lien);
	if($test["scheme"]) return false;
	return !preg_match("/^\//",$test["path"]);
}

/**
 * Permet de savoir si un lien est absolu 
 */

function isabsolute($lien)
{ 
	return !isrelative($lien);
}


/**
 * Enleve les tags HTML qui garde les footnotes et les endnotes de OpenOffice
 */

function strip_tags_keepnotes($text,$keeptags="")

{
  $arr=preg_split('/(<a class="(foot|end)notecall"[^>]*>.*?<\/a>)/s',$text,-1,PREG_SPLIT_DELIM_CAPTURE);
  $count=count($arr);
  for($i=0; $i<$count; $i+=2) $arr[$i]=strip_tags($arr[$i],$keeptags);
  return join("",$arr);
}

/**
 * Renvoie la langue "human reading"
 */

function humanlang($text)

{
  global $home;
  require_once($home."langues.php");
  return $GLOBALS[langues][$text];
}


/**
 * Retourne la date courante sous la forme YYYY-MM-JJ
 */

function today() {
	return date("Y-m-d H:i:s");
}


/**
 * Retourne le texte si la date est dépassée, sinon retourne une chaine vide.
 */

function hideifearlier($text,$date) {

#  echo "date:$date<br />";
  if ($date && ($date <= date("Y-m-d"))) return $text;
  return "";
}

/**
 * Retourne la largeur de l'image.
 */

function imagewidth($image)
{
  if ($image) {
    $result=getImageSize($image);
    return $result[0];
  } else return 0;
}

/**
 * Retourne la hauteur de l'image.
 */

function imageheight($image)
{
  if ($image) {
    $result=getImageSize($image);
    return $result[1];
  } else return 0;
}

/**
 * Renvoie la taille d'un fichier, mais formate joliment
 * avec des kilo et mega
 */

function nicefilesize($lien)
{
  if (defined("SITEROOT")) $lien=SITEROOT.$lien;
  if (!file_exists($lien)) return "0k";
  $size=filesize($lien);
  if ($size<1024) return $size." octets";

  foreach(array("k","M","G","T") as $unit) {
    $size/=1024.0;
    if ($size<10) return sprintf("%.1f".$unit,$size);
    if ($size<1024) return intval($size).$unit;
  }
}

/**
 * Bootstrap Lodel pour WikiRender
 * Assure la conversion texte Wiki en xhtml
 */

function wiki($text) 

{
  require_once('wikirenderer/WikiRenderer.lib.php');
  $wkr = new WikiRenderer();
  return $wkr->render($text);
}


/**
 * Remove space for xml elements
 *
*/

function removespace($text) {
  return str_replace(" ","",$text);
}

/**
 * Bootstrap pour sprintf 
 * affiche l'argument selon le format
 */

function format($text,$format)

{
  return sprintf($format,$text);
}

/**
  * Detect if the document is HTML
  * Bad heursitic
  */


function ishtml($text)

{
  return preg_match("/<(p|br|span|ul|li|dl|strong|em)\b[^><]*>/",$text);
}

/**
 * Filtre pour l'ajout des notes marginales
 *
 * Ajoute un <div class="textandnotes"> et encapsule les notes dans une liste : <ul class="sidenotes"> <li> ... </li> </ul> 
 * contient le numÃ©ro de la note et celle-ci tronquee 
 * (si trop longue) Ã  100 caractÃ¨res.
 * 
 * Le seul parametre sera le seuil d'affichage des notes
 *
 * @author Mickael Sellapin
 *
 * @param string $text le texte par défaut a numeroter 
 * @param string $coupe un entier (definit dans le template), nombre de caractères maximal de la note marginale
 *
 */

function notesmarginales($text, $coupe) {

	static $condition = 0;

	$cptendnote = 0;
        $cptendpage = 0;
	$compteur = 0;
	$titre = $GLOBALS['context']['titre'];

	//on recupere toutes les notes du texte
	$regexp = '/<a\s+class="(foot|end)notecall"[^>](.*?)>(.*?)<\/a>/s';
	preg_match_all($regexp,$text,$matches);

	//print_r($matches);

	$regexpnote = '/<div\s+class="footnotebody"[^>]*><a\b[^>](.*?)>(.*?)<\/a>(.*?)<\/div>/s';	

	$search = '/<div\s+class="footnotebody"[^>](.*?)>/';
	$replace = '<br /><div class="footnotebody" \\1>';


	// pour ajouter la note marginale asterisque du titre principal - obligé de procéder comme ceci car la fonction "notes" utilisée ne renvoie rien pour une raison inconnue (il est à noter que j'ai fait
	// plusieurs tests et que je n'ai pas trouvé l'origine du problème
	preg_match_all('/<div class="(?:foot|end)notebody"[^>]*>.*?<\/div>/',$GLOBALS['context']['notebaspage'],$results,PREG_PATTERN_ORDER);

	$notetitre = preg_grep('/<a class="(foot|end)notedefinition[^>]*>\[?\*+\]?<\/a>/',$results[0]);
	if($notetitre != "")
		$notesmodif .= join("", $notetitre);

	if(notes($GLOBALS['context']['notebaspage'], "lettre")) {
		$notesmodif .= notes($GLOBALS['context']['notebaspage'], "lettre");
	}

	if(notes($GLOBALS['context']['notebaspage'], "nombre")) {
		$notesmodif .= notes($GLOBALS['context']['notebaspage'], "nombre");
	}

	/*
	if(notes($GLOBALS['context']['notebaspage'], "asterisque")) {
		$notesmodif .= notes($GLOBALS['context']['notesbaspage'], "asterisque");
	}
	*/

	//on recupere chaque note du bloc de notes
	preg_match_all($regexpnote, $GLOBALS['context']['notefin'], $matchesnotefin);

	preg_match_all($regexpnote, $notesmodif, $matchesnotebaspages);

	//pour traiter les cas d'une note dans le titre principal
	
	if(!preg_match($regexp,$titre,$matchestitre) && $condition == 0) {

		$condition = 2;
		return $titre;

	} elseif($condition == 0) {

		$search = array ('/id="(.*?)" href="#(.*?)"/');

		$replace = array ('href="#\\2"');

		$hreftitre = preg_replace($search, $replace, $matches[2][0]);

		$titre_modif = $titre;
		
		$titre_modif .= "\n<ul class=\"sidenotes\"><li><a ".$hreftitre.">".cuttext(strip_tags($matchesnotebaspages[3][0]), $coupe);
		
		if(strlen($matchesnotebaspages[3][0]) > $coupe) {
			$titre_modif .= cuttext(strip_tags($matchesnotebaspages[3][0]), $coupe).'(...)';
		} else {
			$titre_modif .= strip_tags($matchesnotebaspages[3][0]);
		}

		$titre_modif .= "</a></li></ul>";

		$condition = 1;

		return $titre_modif;
	} 
	
	

	//on recupere chaque paragraphe du texte mais pas seulement le texte, les <p class="citation", etc ... pour les afficher ensuite
	//$regexppar = '/(<h[0-9] dir=[^>]*>.*?<\/h[0-9]>)?<p\b class="(.*?)" * dir=[^>]*>(.*?)<\/p>/';
	$regexppar = '/(<div class="section[0-9]+"><a \s*href="#tocfrom[0-9]+" \s*id="tocto[0-9]+"\s*>.*?<\/a><\/div>)?<p\b class="(.*?)" * dir=[^>]*>(.*?)<\/p>/';

	preg_match_all($regexppar,$text,$paragraphes);

	//on incremente cette variable pour palier l'affichage de la note asterisque et afficher la toute premiere note
	if($condition == 1) $cptendpage++;

	$retour = "";
	$nbparagraphes = sizeof($paragraphes[0]);

	// on affiche a la suite de chaque paragraphe les notes correspondantes que l'on met dans la variable "buffer"
	for($i = 0; $i < $nbparagraphes; $i++) {
		$buffer = "";
		preg_match_all($regexp, $paragraphes[0][$i], $tmp);

		$tailletmp = sizeof($tmp[0]);
		
		for($j = 0; $j < $tailletmp; $j++) {

			if(strlen($matchesnotebaspages[3][$cptendpage]) > 0) {

				if((preg_match('/[0-9]+/',$matchesnotebaspages[2][$cptendpage],$m)) || (preg_match('/[a-zA-Z]+/',$matchesnotebaspages[2][$cptendpage],$m))) {

				$search = '/class=".*" *id="(.*?)" *href="#(.*?)"/';
				$replace = 'href="#\\1"';

				$matchesnotebaspages[1][$cptendpage] = preg_replace($search, $replace, $matchesnotebaspages[1][$cptendpage]);

				$r = '<a '.$matchesnotebaspages[1][$cptendpage].'>'.$matchesnotebaspages[2][$cptendpage];

				if(strlen($matchesnotebaspages[3][$cptendpage]) > $coupe) {
					$r .= cuttext(strip_tags($matchesnotebaspages[3][$cptendpage]), $coupe).'(...)</a>';
				} else {
					$r .= strip_tags($matchesnotebaspages[3][$cptendpage]).'</a>';
				}

				$buffer .= '<li>'.$r.'</li>';
			
				$cpt++;
				} else {
					$cptendpage++;
				}
			}
			$cptendpage++;			
		}

		$retour = $cpt > 0 ? $retour."<div class=\"textandnotes\">\n".$paragraphes[0][$i]."\n<ul class=\"sidenotes\">".$buffer."\n</ul></div>\n" : $retour.$paragraphes[0][$i];
        	$cpt = 0;

	}

	$condition = 0;

	return $retour;
	//return $text;
}

?>
