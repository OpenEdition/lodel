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

// for compatibility
function couper($texte,$long) { return cuttext($texte,$long); }


/*
 * Cut text keeping whole words
 *
 */

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
		     /* 5 */ 	"$debut_intertitle",
		     /* 6 */ 	"$fin_intertitle",
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

{ # verifie que la date est sous forme sql

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
 * Return the second argument if the first is true
 */
function truefunction($text,$text2)

{ return $text ? $text :""; }

/**
 * Return the second argument if the first is false
 */
function falsefunction($text,$text2)

{ return $text ? $text :""; }


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

/**
* Fonction qui enleve les tags spécifiés
*/

function removetags($text, $tags){
  foreach(explode(',', $tags) as $v){
    $find[] = '/<'.trim($v).'\b[^>]*>/';
    $find[] = '/<\/'.trim($v).'>/';
  }
  return preg_replace($find, "", $text);
}

/**
* Fonction permettant de supprimer les liens.
*/

function removelinks($text)
{
 return removetags($text, 'a');
} 


/**
 * Fonction qui dit si une date est vide ou non
 */
function isadate($text)
{
  return $text!="0000-00-00";
}

/**
 * Fonction qui remplace les guillemets d'un texte par leur name d'entité (&quot;)
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
 * Renvoie la lang "human reading"
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
  if (is_numeric($lien)) {
    $size=$lien;
  } else {
    if (defined("SITEROOT")) $lien=SITEROOT.$lien;
    if (!file_exists($lien)) return "0k";
    $size=filesize($lien);
  }

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


/*
 * @internal
 *
 */
function lodeltextcolor($status)

{
  $colorstatus=array(-1=>"red",1=>"orange",2=>"green");
  return $colorstatus[$status];
}

?>
