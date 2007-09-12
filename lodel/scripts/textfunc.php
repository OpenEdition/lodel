<?php
/**
 * Fichier utilitaire proposant des fonctions sur les textes dans Lodel
 *
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
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
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
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
	//$letexte = preg_replace("/(<[^>]+>|&nbsp;|[\n\r\t])+/", "", $letexte);
	$letexte = preg_replace("/(&nbsp;|[\n\r\t])+/", " ", $letexte);
	$letexte = strip_tags($letexte);
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
	if ($open === false || $open > $length){
		return cut_without_tags($text, $length);}
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
	$last_space_position = strrpos($text2, " ");
	
	if (!($last_space_position === false)) {
		// supprime le dernier espace et tout ce qu'il y a derrière
		//$text2 = substr($text2, 0, $last_space_position);
		$text2 = preg_replace("/\S+$/", "", $text2);
	}
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
	if(preg_match("/^(\d\d\d\d)-(\d\d)-(\d\d) (\s*\d\d:\d\d:\d\d)$/", $s, $result))
	{
		if ($result[1] > 9000)
			return "jamais";
		if ($result[1] == 0)
			return "";
		if (is_numeric($result[1]) && $result[2] == 0 && $result[3] == 0)
			return $result[1];

		$dat = intval($result[3])."-".intval($result[2])."-".intval($result[1]);
		if($result[4] != "")
			$ret = formateddate($dat, "%d %B %Y") . " " . $result[4];
		else
			$ret = formateddate($dat, "%d %B %Y");

		//$ret = intval($result[3])." ".$mois[intval($result[2])]." ".intval($result[1])." ".$result[4];
	}
	elseif (preg_match("/^(\d\d\d\d)-(\d\d)-(\d\d)/", $s, $result)) {
		if ($result[1] > 9000)
			return "jamais";
		if ($result[1] == 0)
			return "";
		if (is_numeric($result[1]) && $result[2] == 0 && $result[3] == 0)
			return $result[1];
		$dat = intval($result[3])."-".intval($result[2])."-".intval($result[1]);
		$ret = formateddate($dat, "%d %B %Y");
		//$ret = intval($result[3])." ".utf8_encode($mois[intval($result[2])])." ".intval($result[1]);
	}
	// time
	elseif (preg_match("/(\s*\d\d:\d\d:\d\d)$/", $s, $result)) {
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
	/*if (!preg_match("/^docannexe\/image\/[^\.\/]+\/[^\/]+$/", $text))	{
		return getlodeltextcontents("ERROR_INVALID_PATH_TO_IMAGE", "COMMON");
	}*/
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
* Fonction qui enleve les tags spécifiés
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
 * Fonction qui remplace les guillemets d'un texte par leur name d'entité (&quot;)
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
	return date("Y-m-d");
}

/**
 * Retourne le texte si la date est dépassée, sinon retourne une chaine vide.
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
	/* Fonction pour mediawiki : A TESTER !!!
	require_once('mediawiki/Parser.php');
	$parser = new ParserMediawiki;
	$parserOutput = $parser->internalParse($text);
	print_r($parserOutput);
	*/
	require_once ('wikirenderer/WikiRenderer.lib.php');
	$wkr = new WikiRenderer();
	return $wkr->render($text);
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

function replacement($arg0, $arg1, $arg2, $arg3, $arg4, $count)
{
	static $count;
	
	++$count;
	$repl = $arg1. $arg2. ' id="pn'.$count.'"'.$arg3;
	$repl .= '<span class="paranumber">'.$count.'</span>';
	return $repl;
}

/**
 * Filtre de numerotation des paragraphes
 * 
 * Ajoute un <span class="paranumber"> contenant une ancre avec le numero du paragraphe
 * aux paragraphes ayant le style texte par défaut.
 *
 * Les paramètres sont modifiables dans le template et écrasent les paramètres par défaut.
 *
 * @author Mickael Sellapin
 *
 * @param string $texte le texte à numéroter passé par référence
 * @param string $styles chaine contenant les styles par défaut ou s'applique la numerotation (les styles sont separes par des ";")
 */
function paranumber(&$texte, $styles='texte')
{
  	static $paranum_count;

	$tab_classes = explode(";", $styles);

	$length_tab_classes = count($tab_classes);

	$chaine_classes = '"'.$tab_classes[0].'"';

	for($i=1; $i < $length_tab_classes; $i++) {
		$chaine_classes .= '|"'.$tab_classes[$i].'"';
	}
	
	/* Regexp : cherche les paragraphes à numéroter, en ignorant les paragraphes contenant une image ou un tableau
	 *  ignore aussi les cellules d'un tableau
	 * ignore tous les styles attribués à la balise <p> sauf "texte" ; on peut rajouter un style à <p> comme l'exemple suivant : (<p\b class=(\"texte\"|\"citation\"). On a ajouté le style "citation" avec un "|" (OU exclusif).
	 * ignore les puces
	 */

	//$regexp = "/(?:(?<!(<td>)))(?:(?<!(<li>)))(<p\b class=(\"texte\"|\"citation\"|\"annexe\") [^>]*)(>)(?!(<a\b[^>]*><\/a>)?<img|<table)/ie";


	if ($length_tab_classes != 1) {
		$regexp = '/(?:(?<!(<td>)))(?:(?<!(<li>)))(<p\b class=('.$chaine_classes.' )[^>]*)(>)(?!(<a\b[^>]*><\/a>)?<img|<table)/ie';
	} else {
		$regexp = '/(?:(?<!(<td>)))(?:(?<!(<li>)))(<p\b class='.$chaine_classes.' [^>]*)(>)(?!(<a\b[^>]*><\/a>)?<img|<table)/ie';
	}


	// on formate les balises de tableau et <li> pour faciliter la reconnaissance de la balise dans la regex
	
	$search = array ('/<table\b[^>]*>/', '/<tr\b[^>]*>/', '/<td\b[^>]*>/', '/<li\b[^>]*>/');
	$replace = array ('<table>', '<tr>', '<td>', '<li>');
	
	$texte = preg_replace($search, $replace, $texte);
	// presence de 2 voir 3 paragraphes dans une cellule de tableau ? on nettoie tout ca
	$regex = '`(<td>\s*<p class="texte" dir="[^"]*">([^<]*)</p>\s*<p class="texte" dir="[^"]*">([^<]*)</p>\s*</td>)|(<td>\s*<p class="texte" dir="[^"]*">([^<]*)</p>\s*<p class="texte" dir="[^"]*">([^<]*)</p>\s*<p class="texte" dir="[^"]*">([^<]*)</p>\s*</td>)`U';
	$replacer = '<td><p class="texte" dir="[^"]*">\\2\\3\\5\\6\\7</p></td>';
	
	$texte = preg_replace($regex, $replacer, $texte);
	$texte = preg_replace($regexp, 'replacement("\\1","\\2","\\3","\\4","\\5",1)', $texte);

	return $texte;
}


/**
 * Filtre pour l'ajout des notes marginales
 *
 * Ajoute un <div class="textandnotes"> et encapsule les notes dans une liste : <ul class="sidenotes"> <li> ... </li> </ul> 
 * contient le numéro de la note et celle-ci tronquée 
 * (si trop longue) à 100 caractères.
 * 
 * Le seul paramètre sera le seuil d'affichage des notes
 *
 * @author Mickael Sellapin
 *
 * @param string $texte le texte à numéroter passé par référence
 * @param string $coupe un entier (définit dans le template)
 *
 */

function notesmarginales(&$texte, $coupe) {

	static $condition = 0;

	$cptendnote = 0;
        $cptendpage = 0;
	$compteur = 0;
	$titre = $GLOBALS['context']['titre'];

	//on récupère toutes les notes du texte
	$regexp = '/<a\s+class="(foot|end)notecall"[^>](.*?)>(.*?)<\/a>/s';
	preg_match_all($regexp,$texte,$matches);

	$regexpnote = '/<p\s+class="notebaspage"[^>]*><a\b[^>](.*?)>(.*?)<\/a>(.*?)<\/p>/s';	

	$search = '/<p\s+class="notebaspage"[^>](.*?)>/';
	$replace = '<br /><p class="notebaspage" \\1>';

	$notesformatees = preg_replace($search, $replace, $GLOBALS['context']['notesbaspage']);

	if(notes($GLOBALS['context']['notesbaspage'], "asterisque")) {
		$notesmodif = notes($GLOBALS['context']['notesbaspage'], "asterisque");
	}

	if(notes($GLOBALS['context']['notesbaspage'], "lettre")) {
		$notesmodif .= notes($GLOBALS['context']['notesbaspage'], "lettre");
	}

	if(notes($notesformatees, "nombre")) {
		$notesmodif .= notes($GLOBALS['context']['notesbaspage'], "nombre");
	}

	//on recupére chaque note du bloc de notes
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
	$regexppar = '/(((<h[0-9] dir=[^>]*>.*?<\/h[0-9]>)?<p\b class="(.*?)" * dir=[^>]*>(.*?)<\/p>))|(<h[0-9] dir=[^>]*><a [^>]*>[^<]*<\/a><\/h[0-9]>)|(<table[^>]*>.*<\/table>)/';
	

	preg_match_all($regexppar,$texte,$paragraphes);

	//on incrémente cette variable pour palier l'affichage de la note asterisque et afficher la toute première note
	if($condition == 1)
		$cptendpage++;

	$retour = "";
	$nbparagraphes = sizeof($paragraphes[0]);

	// on affiche à la suite de chaque paragraphe les notes correspondantes que l'on met dans la variable "buffer"
	for($i = 0; $i < $nbparagraphes; $i++) {
		$buffer = "";
		preg_match_all($regexp, $paragraphes[0][$i], $tmp);

		$tailletmp = sizeof($tmp[0]);
		
		for($j = 0; $j < $tailletmp; $j++) {

			if(strlen($matchesnotebaspages[3][$cptendpage]) > 0) {

				if((preg_match('/[0-9]+/',$matchesnotebaspages[2][$cptendpage],$m)) || (preg_match('/[a-zA-Z]+/',$matchesnotebaspages[2][$cptendpage],$m))) {

				$search = array ('/id="(.*?)" class=".*" href="#(.*?)"/');
				$replace = array ('href="#\\1"');

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
}

/** Extrait les images au sein du texte pour en faire une version petite taille et fournir un lien vers une version haute résolution dans un popup 
* @author Bruno Cénou
* @author Pierre-Alain Mignot
* @param  string $text le texte à parser
* @param  integer $width la largeur souhaitée pour les images dans le texte
* @param  string $titlePos la position du titre de l'image dans le doc stylé
* @param  integer $max la largeur maximale du popup
* @param  integer $min la largeur minimale du popup
*/

function iconifier($text, $width=150, $titlePos='up', $max=640, $min=400){
	if(!$text) return;
	preg_match_all("|(<a[^>]+href=\"[^>]+\"[^>]*>)?<img[^>]+src=\"([^\">]+)\" alt=\"[^\">]+\" ([^>]*)/>|U", $text, $result, PREG_PATTERN_ORDER);
	preg_match_all("`<p class=\"titreillustration\"[^>]*>.*</p>`U", $text, $regs, PREG_PATTERN_ORDER);
	foreach($result[2] as $k=>$v){
		$info = getimagesize($v);
		$w = $info[0];
		$h = $info[1];
		if($w > $width){
			$vign = $w > $width ? vignette($v, $width) : $v;
			if($w > $max){
				$full = vignette($v, $max);
				$info = getimagesize($full);
				$w = $info[0];
				$h = $info[1];
			}else{
				$full = $v;
			}
			$w = $w < $min ? $min : $w;
			$w += 100;
			$h += 300;
			$stop = !$regs[0][$k+1] ? strlen($text) : strpos($text, $regs[0][$k+1]);
			$title = getImageTitle($text, $v, $titlePos, $stop);
			$desc = getImageDesc($text, $v);
			$credits = getImageCredits($text, $v);
			$link = "<div class=\"textIcon\">";
 			if($titlePos == 'up') $link .= "\n".$title;
			
			$link .= "<a href=\"image.php?source=$full&amp;titlepos=$titlePos\" rel=\"nofollow\" onclick=\"window.open(this.href, '', 'top=0, left=0, width=".$w.", height=".$h.", resizable=yes, scrollbars=yes'); return false;\">".str_replace($v, $vign, $result[0][$k])."</a>";
			$link .= "<a class=\"fullSize\" href=\"image.php?source=$full&amp;titlepos=$titlePos\"  rel=\"nofollow\" onclick=\"window.open(this.href, '', 'top=0, left=0, width=".$w.", height=".$h.", resizable=yes, scrollbars=yes'); return false;\"><img src=\"images/magnify.png\" alt=\"Agrandir\" /></a>";
 			if($titlePos == 'down') $link .= "\n".$title;
 			$link .= "\n".$desc."\n".$credits."\n";
			$link .= "</div>";
 			$text = str_replace(array($title, $desc, $credits), '', $text);
			$link = str_replace($result[3][$k], '', $link);
			$text = str_replace($result[0][$k], $link, $text);
			$text = str_replace($result[1][$k], '', $text);
		}
	}
	$text = str_replace("</a></a>", "</a>", $text);
	return($text);
}

/** Récupère le titre d'une image lorsque celui-là se trouve au dessus ou au dessous de celle-ci
* @author Bruno Cénou
* @author Pierre-Alain Mignot
* @param  string $text le texte à parser
* @param  mixed $source le nom du fichier image ou sa position dans le texte
* @param  string $titlePos la position du titre de l'image dans le doc stylé
* @param  integer $posi position de l'image suivante
*/

function getImageTitle($text, $source, $titlePos='up', $posi='0')
{	
	if(!$text) return;
	if($titlePos == 'up'){
		if(!is_numeric($source) && $posi == '0')
		{ // ici on a pas la position de l'image suivante pour delimiter la recherche regex
		// on le calcule
			$test = explode('-', $source);
			$nb = substr($test[1], 0, 1);
			$nb++; //chose faite
			//on cree maintenant le nom de l'image
			$testing = $test[0]."-".$nb.".jpg";
			//on recherche sa position dans le texte
			$posi = strpos($text, $testing);
			// si on ne trouve pas d'image suivante, $text reste inchange, sinon on le coupe
			// pour obtenir seulement la portion du texte qui nous interesse
			$text = !$posi ? $text : substr($text, 0 , $posi);
			// on prepare notre regex
			$regex = "`(<p class=\"titreillustration\"[^>]*>.+</p>[^<]*<p class=\"texte\"[^>]*>[^<]*<img src=\"".$source."\"[^>]*/>)`U";
			// on l'execute
			preg_match_all($regex, $text, $regs, PREG_PATTERN_ORDER);
			// on met dans $titre un tableau contenant : 1. le titre 2. l'img 3.une deuxieme image ? pas normal
			$titre = split('<p class="titreillustration"[^>]*>', array_pop($regs[1]));
			$titre = explode('<img', array_pop($titre));
			if(sizeof($titre) > 2)
			{ // tiens il y a une autre image .. notre image n'a donc pas de titre, hop on sort
				return;
			}
			// on renvoit le titre
			return $titre[0];
		}
		else
		{// on connait la position de l'image suivante, on coupe le texte
			$text = substr($text, 0 , $posi);
			// on prepare la regex et on l'execute
			$regex = "`(<p class=\"titreillustration\"[^>]*>.*</p>[^<]*<p class=\"texte\"[^>]*>[^<]*<img src=\"".$source."\"[^>]*/>)`U";
			preg_match_all($regex, $text, $regs, PREG_PATTERN_ORDER);
			// on recupere le titre
			$titre = explode('<img', array_pop($regs[1]));
		}
		return($titre[0]);
	}elseif($titlePos == 'down'){
		$text = str_replace($source, '', strstr($text, $source));
		if(FALSE !== strpos($text, '<img')){
			$text = substr($text, 0, strpos($text, '<img'));
		}
		if(FALSE !== strpos($text, '<table')){
			$text = substr($text, 0, strpos($text, '<table'));
		}
		preg_match("|(<p class=\"titreillustration\"[^>]*>.*</p>)|U", $text, $regs);
		return($regs[1]);
	}
}

/** Récupère la légende d'une image lorsque celle-là se trouve au dessous de celle-ci 
* @author Bruno Cénou
* @param  string $text le texte à parser
* @param  string $source le nom du fichier image
*/

function getImageDesc($text, $source){
	if(!$text) return;
	$text = str_replace($source, '', strstr($text, $source));
	if(FALSE !== strpos($text, '<img')){
		$text = substr($text, 0, strpos($text, '<img'));
	}
	if(FALSE !== strpos($text, '<table')){
		$text = substr($text, 0, strpos($text, '<table'));
	}
	preg_match("|(<p class=\"legendeillustration\"[^>]*>.*</p>)|U", $text, $regs);
	return($regs[1]);
}

/** Récupère les crédits d'une image lorsque ceux-là se trouvent au dessous de celle-ci
* @author Bruno Cénou
* @param  string $text le texte à parser
* @param  string $source le nom du fichier image
*/

function getImageCredits($text, $source){
	if(!$text) return;
	$text = str_replace($source, '', strstr($text, $source));
	if(FALSE !== strpos($text, '<img')){
		$text = substr($text, 0, strpos($text, '<img'));
	}
	if(FALSE !== strpos($text, '<table')){
		$text = substr($text, 0, strpos($text, '<table'));
	}
	preg_match("|(<p class=\"creditillustration\"[^>]*>.*</p>)|U", $text, $regs);
	return($regs[1]);
}

/** renvoie le type mime d'un fichier, soit par l'extension PEAR fileinfo, soit par le système 
* @author Bruno Cénou
* @param  string $filename le nom du fichier
*/

function getFileMime($filename){
	if(function_exists("finfo_open")){
		$finfo = finfo_open(FILEINFO_MIME, "/usr/share/misc/file/magic");
		return finfo_file($finfo, $filename);
	}else{
		system("file -i -b $filename");
	}
}

?>
