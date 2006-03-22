<?php
/**
 * Fichier utilitaire proposant des fonctions sur les textes dans Lodel
 *
 * PHP version 4
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno Cénou, Jean Lamy
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
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno Cénou, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */

if (file_exists($home."textfunc_local.php"))
	require_once ("textfunc_local.php");

# fonction largement reprises de SPIP

#include_once 'func.php';

function pluriel($texte)
{
	return intval($texte) > 1 ? "s" : "";
}

function lettrine($texte)
{
	return preg_replace("/^(\s*(?:<[^>]+>)*\s*)([\w\"])/s", "\\1<span class=\"lettrine\">\\2</span>", $texte);
}

function nbsp($texte)
{
	return $texte ? $texte : '&nbsp;';
}

/**
 * Upercase the first letter of Texte
 */
function majuscule($texte)
{
	return preg_replace("/^(\s*(?:<[^>]+>)*\s*)(\w)/se", '"\\1".strtoupper("\\2")', $texte);
}

function textebrut($letexte)
{
	$letexte = preg_replace("/(<[^>]+>|&nbsp;|[\n\r\t])+/", " ", $letexte);
	return $letexte;
}

// for compatibility
/**
 * @deprecated Ne plus utiliser en 0.8
 */
function couper($texte, $long)
{
	return cuttext($texte, $long);
}

/**
 * Cut text keeping whole words
 */
function cuttext($text, $length)
{
	$GLOBALS['textfunc_hasbeencut'] = false;
	$open = strpos($text, "<");
	if ($open === false || $open > $length)
		return cut_without_tags($text, $length);
	$length -= $open;
	$stack = array ();
	while ($open !== FALSE) {
		$close = strpos($text, ">", $open);
		if ($text[$open +1] == "/") {
			array_pop($stack); // fermante
		}	elseif ($tags[$close -1] != "/") {
			array_push($stack, "</".preg_replace("/\s.*/", "", substr($text, $open +1, $close -1 - $open)).">"); // ouvrante
		}
		$open = strpos($text, "<", $close);
		$piecelen = $open -1 - $close;
		if ($open === FALSE || $piecelen > $length)
			return substr($text, 0, $close +1).cut_without_tags(substr($text, $close +1, $length +2), $length).// 2 pour laisser de la marge
			join("", array_reverse($stack));
		$length -= $piecelen;
	}
	return $text;
}

function cut_without_tags($text, $length)
{
	$text2 = substr($text." ", 0, $length);
	if (strlen($text2) < strlen($text)) {
		$GLOBALS['textfunc_hasbeencut'] = true;
	}
	$spacepos = strrpos($text2, " ");
	$text2 = preg_replace("/\S+$/", "", $text2);
	return $text2;
}

function hasbeencut()
{
	return $GLOBALS['textfunc_hasbeencut'] ? true : false;
}

function couperpara($texte, $long)
{
	$pos = -1;
	do {
		$pos = strpos($texte, "</p>", $pos +1);
		$long --;
	} while ($pos !== FALSE && $long > 0);
	return $pos > 0 ? substr($texte, 0, $pos +4) : $texte;
}

function spip($letexte)
{
	$puce = "<IMG SRC=\"Images/smallpuce.gif\">";
	// Harmoniser les retours chariot
	$letexte = ereg_replace("\r\n?", "\n", $letexte);

	// Corriger HTML
	$letexte = eregi_replace("</?p>", "\n\n\n", $letexte);

	//
	// Raccourcis liens
	//
	$regexp = "\[([^][]*)->([^]]*)\]";
	$texte_a_voir = $letexte;
	$texte_vu = '';
	while (ereg($regexp, $texte_a_voir, $regs))	{
		$lien_texte = $regs[1];
		$lien_url = trim($regs[2]);
		$compt_liens ++;
		$lien_interne = false;

		$insert = "<a href=\"$lien_url\">".$lien_texte."</a>";
		$zetexte = split($regexp, $texte_a_voir, 2);
		$texte_vu .= $zetexte[0].$insert;
		$texte_a_voir = $zetexte[1];
	}
	$letexte = $texte_vu.$texte_a_voir; // typo de la queue du texte

	//
	// Ensemble de remplacements implementant le systeme de mise
	// en forme (paragraphes, raccourcis...)
	//
	$letexte = trim($letexte);
	$cherche1 = array (
	/* 1 */
	"/\n(----+|____+)/",
	/* 2 */
	"/^-/",
	/* 3 */
	"/\n-/",
	/* 4*/
	"/(( *)\n){2,}/",
	/* 5 */
	"/\{\{\{/",
	/* 6 */
	"/\}\}\}/",
	/* 7 */
	"/\{\{/",
	/* 8 */
	"/\}\}/",
	/* 9 */
	"/\{/",
	/* 10 */
	"/\}/",
	/* 11 */
	"/(<br>){2,}/",
	/* 12 */
	"/<p>([\n]*)(<br>)+/",
	/* 13 */
	"/<p>/");
	$remplace1 = array (
	/* 1 */
	"\n<hr>\n",
	/* 2 */
	"$puce ",
	/* 3 */
	"\n<br>$puce ",
	/* 4 */
	"\n<p>",
	/* 5 */
	"$debut_intertitle",
	/* 6 */
	"$fin_intertitle",
	/* 7 */
	"<b>",
	/* 8 */
	"</b>",
	/* 9 */
	"<i>",
	/* 10 */
	"</i>",
	/* 11 */
	"\n<p>",
	/* 12 */
	"\n<p>",
	/* 13 */
	"<p>");
	$letexte = preg_replace($cherche1, $remplace1, $letexte);
	return $letexte;
}

function propre($letexte)
{
	return traite_raccourcis(trim($letexte));
}

function formateddate($date, $format)
{
	return strftime($format, strtotime($date));
}

function formatedtime($time, $format)
{
	return strftime($format, $time);
}

function humandate($s)
{ # verifie que la date est sous forme sql

	if (preg_match("/^(\d\d\d\d)-(\d\d)-(\d\d)/", $s, $result)) {
		if ($result[1] > 9000)
			return "jamais";
		if ($result[1] == 0)
			return "";

		$mois[1] = "janvier";
		$mois[2] = "fÃ©vrier";
		$mois[3] = "mars";
		$mois[4] = "avril";
		$mois[5] = "mai";
		$mois[6] = "juin";
		$mois[7] = "juillet";
		$mois[8] = "aoÃ»t";
		$mois[9] = "septembre";
		$mois[10] = "octobre";
		$mois[11] = "novembre";
		$mois[12] = "dÃ©cembre";
		$ret = intval($result[3])." ".$mois[intval($result[2])]." ".intval($result[1]);
	}
	// time
	if (preg_match("/(\s*\d\d:\d\d:\d\d)$/", $s, $result)) {
		$ret .= $result[0];
	}

	return $ret ? $ret : $s;
}

/**
 * Transform headings into toc relative links
 *
 * @param string $text the text to transform
 * @param int $level an integer representing the max level which will be transformed
 * @return string the transformed text
 */
function tocable($text, $level = 10)
{

	static $tocind = 0;
	$sect = "1";
	for ($i = 2; $i <= $level; $i ++)
		$sect .= "|$i";
	if (!function_exists("tocable_callback"))	{
		function tocable_callback($result)
		{

			static $tocid = array ();
			$level = intval($result[2][1]);
			$sig = $level."n". (++ $tocid[$level]);
			$aopen = '<a href="#tocfrom'.$sig.'" id="tocto'.$sig.'">';
			$aclose = '</a>';
			// split the result in order not to contains any a tag
			$arr = preg_split("/(<a\b[^>]*>.*?<\/a>)/", $result[3], -1, PREG_SPLIT_DELIM_CAPTURE); // split with the <a...> </a>
			$ret = $result[1];

			$c = count($arr);
			for ($i = 0; $i < $c; $i += 2) {
				if ($arr[$i])
					$ret .= $aopen.$arr[$i].$aclose;
				if ($i +1 < $c)
					$ret .= $arr[$i +1];
			}
			return $ret.$result[4];
		}
	}
	return preg_replace_callback("/(<(r2r:section|h(?:$sect))\b(?:[^>]*)>)(.*?)(<\/\\2>)/s", "tocable_callback", $text);
	//Nota : the r2r:section is conserved for compatibility with the old ServOO
}

function multilingue($text, $lang)
{
	preg_match("/<r2r:ml lang=\"".strtolower($lang)."\">(.*?)<\/r2r:ml>/s", $text, $result);
	return $result[1];
}

function vignette($text, $width)
{
	global $home;
	if (!$text)
		return;
	if (!preg_match("/^docannexe\/image\/[^\.\/]+\/[^\/]+$/", $text))	{
		return getlodeltextcontents("ERROR_INVALID_PATH_TO_IMAGE", "COMMON");
	}
	if (defined("SITEROOT"))
		$text = SITEROOT.$text;
	if (!file_exists($text))
		return getlodeltextcontents("ERROR_FILE_DOES_NOT_EXIST", "COMMON");
	if (!preg_match("/^(.*)\.([^\.]+)$/", $text, $result))
		return getlodeltextcontents("ERROR_FILE_WITHOUT_EXTENSION", "COMMON");
	$vignettefile = $result[1]."-small$width.".$result[2];
	if (file_exists($vignettefile) && filemtime($vignettefile) >= filemtime($text))
		return $vignettefile;
	// creer la vignette (de largeur width ou de hauteur width en fonction de la forme
	require_once ("images.php");
	if (!resize_image($width, $text, $vignettefile, "+"))
		return getlodeltextcontents("ERROR_IMAGE_RESIZING_FAILED", "COMMON");
	return $vignettefile;
}

# renvoie les attributs pour une image
function sizeattributs($text)
{
	$result = getImageSize($text);
	return $result[3];
}

/**
 * Return the second argument if the first is true
 */
function truefunction($text, $text2)
{
	return $text ? $text : "";
}

/**
 * Return the second argument if the first is false
 */
function falsefunction($text, $text2)
{
	return $text ? $text : "";
}

/** 
 * Supprimer les appels de notes de pied de page d'un texte.
 */

function removefootnotes($text)
{
	return preg_replace('/<a\s+class="footnotecall"[^>]*>.*?<\/a>/s', "", $text);
}
/** 
 * Supprimer les appels de notes de fin de document.
 */

function removeendnotes($text)
{
	return preg_replace('/<a\s+class="endnotecall"[^>]*>.*?<\/a>/s', "", $text);
}

/** 
 * Fonction permettant de supprimer les appels de notes d'un texte.
 */

function removenotes($text)
{
	return preg_replace('/<a\s+class="(foot|end)notecall"[^>]*>.*?<\/a>/s', "", $text);
}

/** 
 * Fonction qui enleve les images
 */

function removeimages($text)
{
	return preg_replace('/<img\b[^>]*>/', "", $text);
}

/**
* Fonction qui enleve les tags spï¿½ifiï¿½
*/

function removetags($text, $tags)
{
	foreach (explode(',', $tags) as $v) {
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
	return $text != "0000-00-00";
}

/**
 * Fonction qui remplace les guillemets d'un texte par leur name d'entitï¿½(&quot;)
 */
function replacequotationmark($text)
{
	return str_replace("\"", "&quot;", $text);
}

//
// fonction utiliser pour les options (dans l'interface uniquement)
//

function yes($texte)
{
	return $texte ? "checked" : "";
}

function no($texte)
{
	return !$texte ? "checked" : "";
}

function eq($str, $texte)
{
	return $texte == $str ? "checked" : "";
}

/**
 * fonction pour les notes
 */

function notes($texte, $type)
{
	#  preg_match_all('/<div id="sd[^>]+>.*?<\/div>/',$texte,$results,PREG_PATTERN_ORDER);
	#  return $texte;
	//  preg_match_all('/<(div|p) class="(?:foot|end)note(?:body|text)"[^>]*>.*?<\/\\1>/',$texte,$results,PREG_PATTERN_ORDER);

	// be cool... just select the paragraph or division.
	preg_match_all('/<(div|p)[^>]*>.*?<\/\\1>/', $texte, $results, PREG_PATTERN_ORDER);
	#  print_r($results);
	$notere = '<a\s+[^>]*\bclass="(foot|end)note(definition|symbol)[^>]*>';
	switch ($type) {
	case 'nombre' :
	case 'number' :
		$notes = preg_grep('/'.$notere.'\[?[0-9]+\]?<\/a>/i', $results[0]);
		break;
	case 'lettre' :
	case 'letter' :
		$notes = preg_grep('/'.$notere.'\[?[a-zA-Z]+\]?<\/a>/i', $results[0]);
		break;
	case 'asterisque' :
	case 'star' :
		$notes = preg_grep('/'.$notere.'\[?\*+\]?<\/a>/i', $results[0]);
		break;
	default :
		die("unknown note type \"$type\"");
	}
	return join("", $notes);
}

/**
 * fonctions pour le nettoyage de base des champs importes
 */

function tocss($text, $options = "")
{
	global $home;
	include_once ("balises.php");
	$srch = array ();
	$rpl = array ();
	if ($options == "heading") {
		array_push($srch, "/<r2r:section(\d+\b[^>]*)>/", "/<\/r2r:section(\d+)>/");
		array_push($rpl, '<h\\1>', '</h\\1>');
	}
	array_push($srch, "/<r2r:(\w+)\b[^>]*>/", // replace les autres balises r2r par des DIV
	"/<\/r2r:[^>]+>/");
	array_push($rpl, '<div class="\\1">', "</div>");

	#  return preg_replace($srch,$rpl,traite_separateur($text));
	return preg_replace($srch, $rpl, $text);
}

/**
 * function to format the text given the creationmethod field
 *
 */

function format($text, $creationmethod = "", $creationinfo = "")
{
	if (!$creationmethod)
		$creationmethod = $GLOBALS['context']['creationmethod'];
	if (!$creationinfo)
		$creationinfo = $GLOBALS['context']['creationinfo'];

	if ($creationmethod == "form") {
		switch ($creationinfo) {
		case 'xhtml' :
			return $text;
		case 'wiki' :
			return wiki($text);
		case 'bb' :
			die("not yet implemented");
		default :
			die("ERROR: unknown creationinfo");
		}
	}
	if (substr($creationmethod, 0, 6) == "servoo")
		return tocss($text);

	die("ERROR: unknown creationmethod");
}

/**
 * Permet de savoir si un lien est relatif 
 */

function isrelative($lien)
{
	$test = parse_url($lien);
	if ($test["scheme"])
		return false;
	return !preg_match("/^\//", $test["path"]);
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

function strip_tags_keepnotes($text, $keeptags = "")
{
	$arr = preg_split('/(<a class="(foot|end)notecall"[^>]*>.*?<\/a>)/s', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
	$count = count($arr);
	for ($i = 0; $i < $count; $i += 2)
		$arr[$i] = strip_tags($arr[$i], $keeptags);
	return join("", $arr);
}

/**
 * Renvoie la lang "human reading"
 */

function humanlang($text)
{
	global $home;
	require_once ("lang.php");
	return $GLOBALS['languages'][strtoupper($text)];
}

/**
 * Retourne la date courante sous la forme YYYY-MM-JJ
 */

function today()
{
	return date("Y-m-d H:i:s");
}

/**
 * Retourne le texte si la date est dï¿½assï¿½, sinon retourne une chaine vide.
 */

function hideifearlier($text, $date)
{

	#  echo "date:$date<br />";
	if ($date && ($date <= date("Y-m-d")))
		return $text;
	return "";
}

/**
 * Retourne la largeur de l'image.
 */

function imagewidth($image)
{
	if ($image)	{
		$result = getImageSize($image);
		return $result[0];
	}	else
		return 0;
}

/**
 * Retourne la hauteur de l'image.
 */

function imageheight($image)
{
	if ($image)	{
		$result = getImageSize($image);
		return $result[1];
	}	else
		return 0;
}

/**
 * Renvoie la taille d'un fichier, mais formate joliment
 * avec des kilo et mega
 */

function nicefilesize($lien)
{
	if (is_numeric($lien)) {
		$size = $lien;
	}	else {
		if (defined("SITEROOT"))
			$lien = SITEROOT.$lien;
		if (!file_exists($lien))
			return "0k";
		$size = filesize($lien);
	}

	if ($size < 1024)
		return $size." octets";

	foreach (array ("k", "M", "G", "T") as $unit)	{
		$size /= 1024.0;
		if ($size < 10)
			return sprintf("%.1f".$unit, $size);
		if ($size < 1024)
			return intval($size).$unit;
	}
}

/**
 * Bootstrap Lodel pour WikiRender
 * Assure la conversion texte Wiki en xhtml
 */

function wiki($text)
{
	//require_once ('wikirenderer/WikiRenderer.lib.php');
	require_once('mediawiki/Parser.php');
	$parser = new ParserMediawiki;
	$parserOutput = $parser->internalParse($text);
	print_r($parserOutput);
	//$wkr = new WikiRenderer();
	//return $wkr->render($text);
}

/**
 * Remove space for xml elements
 *
*/

function removespace($text)
{
	return str_replace(" ", "", $text);
}

/**
 * Bootstrap pour sprintf 
 * affiche l'argument selon le format
 */

function formatstring($text, $format)
{
	return sprintf($format, $text);
}

/**
  * Detect if the document is HTML
  * Bad heursitic
  */

function ishtml($text)
{
	return preg_match("/<(p|br|span|ul|li|dl|strong|em)\b[^><]*>/", $text);
}

/*
 * @internal
 *
 */
function lodeltextcolor($status)
{
	$colorstatus = array (-1 => "red", 1 => "orange", 2 => "green");
	return $colorstatus[$status];
}

/**
 * Return the value in parameter if the variable si empty/null
 */

function defaultvalue($var1, $var2)
{
	return $var1 ? $var1 : $var2;
}


/**
 * Fonction utilisée ci dessous pour la numérotation des paragraphes
 */
function replacement($arg0, $arg1, $arg2, $arg3, $count)
{
	static $count;
	
	
	++$count;
	$repl = $arg1. 'id="pn'.$count.'"'.$arg2;
	$repl .= '<span class="paranumber">'.$count.'</span>';
	return $repl;
}

/**
 * Filtre de numérotation des paragraphes
 * 
 * Ajoute un <span class="paramnumber"> contenant une ancre avec le numéro du paragraphe
 *
 * @param string $texte le texte à numéroté passé par référence
 */
function paranumber(&$texte)
{
  static $paranum_count;
	//Regexp : cherche les paragraphes à numéroter, en ignorant les paragraphes contenant une image ou un tableau
	$regexp = "/(<p\b[^>]*)(>)(?!(<a\b[^>]*><\/a>)?<img|table)/ie";
	preg_match($regexp,$texte,$matches);
	#print_r($matches);exit;
	#$texte = preg_replace($regexp, '"\\0"."<span class=\"paranumber\">". (++$paranum_count). "</span>"', $texte);
	$texte = preg_replace($regexp, 'replacement("\\0","\\1","\\2","\\3",1)', $texte);
	#echo $texte;exit;
	return $texte;
}


?>