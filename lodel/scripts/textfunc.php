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
 * Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
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
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */

if (is_readable(SITEROOT . $home.'textfunc_local.php'))
	require_once 'textfunc_local.php';

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
function cuttext($text, $length, $dots=false)
{
        $GLOBALS['textfunc_hasbeencut'] = false;
        $encoding = mb_detect_encoding($text, 'UTF-8, ISO-8859-1, ISO-8859-15, Windows-1252', true);
        $open = mb_strpos($text, "<", 0, $encoding);
        if ($open === false || $open > $length){
                return cut_without_tags($text, $length, $dots);}
        $length -= $open;
        $stack = array ();
        while ($open !== FALSE) {
                $close = mb_strpos($text, ">", $open, $encoding);
                if (mb_substr($text, $open+1, 1, $encoding) == "/") {
                        array_pop($stack); // fermante
                }       elseif (mb_substr($text, $close-1, 1, $encoding) != "/") {
                        $tag = mb_substr($text, $open +1, $close -1 - $open, $encoding);
                        if('br /' == $tag || 'br/' == $tag || 'br' == $tag) array_push($stack, '<br/>');
                        else array_push($stack, "</".preg_replace("/\s.*/", "", $tag).">"); // ouvrante
                }
                $open = mb_strpos($text, "<", $close, $encoding);
                $piecelen = $open -1 - $close;
                if ($open === FALSE || $piecelen > $length)
                {
                        if($dots) array_push($stack, ' (...)');
                        return mb_substr($text, 0, $close +1, $encoding).cut_without_tags(mb_substr($text, $close +1, $length +2, $encoding), $length).// 2 pour laisser de la marge
                        join("", array_reverse($stack));
                }
                $length -= $piecelen;
        }
        return $text;
}

function cut_without_tags($text, $length, $dots=false)
{
        $encoding = mb_detect_encoding($text, 'UTF-8, ISO-8859-1, ISO-8859-15, Windows-1252', true);
        $text2 = mb_substr($text." ", 0, $length, $encoding);
        if (mb_strlen($text2, $encoding) < mb_strlen($text, $encoding)) {
                $GLOBALS['textfunc_hasbeencut'] = true;
        }
//      $last_space_position = mb_strrpos($text2, " ", $encoding);

//      if (!($last_space_position === false)) {
                // supprime le dernier espace et tout ce qu'il y a derrière
                //$text2 = substr($text2, 0, $last_space_position);
                $text2 = rtrim($text2);
//      }

        return (($GLOBALS['textfunc_hasbeencut'] && $dots) ? $text2.' (...)' : $text2);
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
	"/(<br />){2,}/",
	/* 12 */
	"/<p>([\n]*)(<br />)+/",
	/* 13 */
	"/<p>/");
	$remplace1 = array (
	/* 1 */
	"\n<hr>\n",
	/* 2 */
	"$puce ",
	/* 3 */
	"\n<br />$puce ",
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
{
	// date
	if (preg_match("/(\d\d\d\d)-(\d\d)-(\d\d)/", $s, $result)) {
		if ($result[1] > 9000)
			return "jamais";
		if ($result[1] == 0)
			return "";
		if (is_numeric($result[1]) && $result[2] == 0 && $result[3] == 0)
			return $result[1];
		$dat = intval($result[1])."-".intval($result[2])."-".intval($result[3]);
		$ret = formateddate($dat, "%d %B %Y");
	}
	// time
	if (preg_match("/(\d\d):(\d\d)/", $s, $res)) {
		if($res[1] != "00" || $res[2] != "00")
			$ret .= " ".$res[1].'h'.$res[2];
	}
	return $ret ? $ret : $s;
}

/**
 * Transform headings into toc relative links
 *
 * @author Pierre-Alain Mignot
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
			$level = intval($result[5]);
			$sig = $level."n". (++ $tocid[$level]);
			$aopen = '<a href="#tocfrom'.$sig.'" id="tocto'.$sig.'">';
			$aclose = '</a>';
			// split the result in order not to contains any a tag
			$arr = preg_split("/(<a\b[^>]*>.*?<\/a>)/", $result[6], -1, PREG_SPLIT_DELIM_CAPTURE); // split with the <a...> </a>
			$ret = $result[1];

			$c = count($arr);
			for ($i = 0; $i < $c; $i += 2) {
				if ($arr[$i])
					$ret .= $aopen.$arr[$i].$aclose;
				if ($i +1 < $c)
					$ret .= $arr[$i +1];
			}
			return $ret.$result[7];
		}
	}

	return preg_replace_callback("/(<((r2r:)?(section|h)($sect))\b(?:[^>]*)>)(.*?)(<\/(r2r:)?(section|h)(?:$sect)\b(?:[^>]*)>)/s", "tocable_callback", $text);
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
	list($widt, $height, $type, $attr) = getImageSize($text);
	if($widt <= $width)
		return $text;
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
	return ($text != "0000-00-00" && $text != NULL);
}

/**
 * Fonction qui remplace les guillemets d'un texte par leur name d'entité (&quot;)
 */
function replacequotationmark($text)
{
	return str_replace("\"", "&quot;", $text);
}

/**
 * implemente str_replace
 */
  
function replace($str, $search, $replace){
        return str_replace($search, $replace, $str);
}

/**
 * implemente preg_replace
 */
 
function reg_replace($str, $search, $replace){
   return preg_replace($search, $replace, $str);
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
 * Fonction permettant de récupérer les notes du texte
 *
 * @author Mickael Sellapin
 * @author Pierre-Alain Mignot
 * @param string $texte le texte à parser
 * @param var $type type des notes présentes dans le texte
 */

function notes($texte, $type)
{
	// be cool... just select the paragraph or division.
	preg_match_all('/<(div|p)[^>]*>.*?<\/\\1>/', $texte, $results);
	#  print_r($results);
	$notere = '<a[^>]+class="(foot|end)note(definition|symbol)[^>]*>';
	if(is_int($type)) {
		switch($type) {
			case 1: // seulement les astérisques
				$notes = preg_grep('/'.$notere.'\[?\*+\]?<\/a>/i', $results[0]);
				break;
			case 2: // astérisques et lettres
				$notes = preg_grep('/'.$notere.'(\[?\*+\]?)|(\[?[a-zA-Z]+\]?)<\/a>/i', $results[0]);
				break;
			case 3: // seulement les lettres
				$notes = preg_grep('/'.$notere.'\[?[a-zA-Z]+\]?<\/a>/i', $results[0]);
				break;
			case 4: // toutes les notes
				$notes = preg_grep('/'.$notere.'(\[?[0-9]+\]?)|(\[?[a-zA-Z]+\]?)|(\[?\*+\]?)<\/a>/i', $results[0]);
				break;
			case 5: // lettre et nombres
				$notes = preg_grep('/'.$notere.'(\[?[0-9]+\]?)|(\[?[a-zA-Z]+\]?)<\/a>/i', $results[0]);
				break;
			case 6: // seulement les nombres
				$notes = preg_grep('/'.$notere.'\[?[0-9]+\]?<\/a>/i', $results[0]);
				break;
			case 7: // nombres et astérisques
				$notes = preg_grep('/'.$notere.'(\[?\*+\]?)|(\[?[0-9]+\]?)<\/a>/i', $results[0]);
				break;
			default:
				die("unknown note type of tag num : \"$type\"");
		}
	} else {
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
 * Retourne la date courante sous la forme YYYY-MM-JJ heures:minutes:secondes
 */

function today_with_hour()
{
	return date("Y-m-d H:i:s");
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

function replacement($arg0, $arg1, $arg2, $arg3)
{
	static $count=0;
	
	++$count;
	$repl = $arg0. ' id="pn'.$count.'"'.$arg1;
	$repl .= '<span class="paranumber">'.$count.'</span>';
	$repl .= $arg2.$arg3;
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
 * @author Pierre-Alain Mignot
 * @param string $texte le texte à numéroter passé par référence
 * @param string $styles chaine contenant les styles par défaut ou s'applique la numerotation (les styles sont separes par des ";")
 */
function paranumber($texte, $styles='texte')
{
  	static $paranum_count;

	$tab_classes = explode(";", $styles);

	$length_tab_classes = count($tab_classes);

	$chaine_classes = '"'.$tab_classes[0].'"';

	for($i=1; $i < $length_tab_classes; $i++) {
		$chaine_classes .= '|"'.$tab_classes[$i].'"';
	}
	// on veut pas de numérotation dans les tableaux ni dans les listes ni dans les paragraphes qui contiennent seulement des images
	$tmpTexte = preg_replace("/<(td|li)[^>]*>.*<\/\\1>/Us", "", $texte);
	$tmpTexte = preg_replace("/<p[^>]*>\s*<img[^>]*\/>/", "", $tmpTexte);
	$regexp = '/(<p class=('.$chaine_classes.'))([^>]*>)(.*)(<\/p>)/eiU';

	// on récupère les paragraphes à numéroter
	preg_match_all($regexp, $tmpTexte, $m);

	// on effectue la numérotation et on remplace dans le texte
	foreach($m[0] as $k=>$paragraphe) {
		$tmpTexte2 = explode($paragraphe, $texte, 2);
		$texte = $tmpTexte2[0].str_replace($paragraphe, replacement($m[1][$k], $m[3][$k], $m[4][$k], $m[5][$k]), $paragraphe).$tmpTexte2[1];
	}
	return $texte;
}
/*
function paranumber(&$texte, $styles='texte')
{
  	static $paranum_count;

	$tab_classes = explode(";", $styles);

	$length_tab_classes = count($tab_classes);

	$chaine_classes = '"'.$tab_classes[0].'"';

	for($i=1; $i < $length_tab_classes; $i++) {
		$chaine_classes .= '|"'.$tab_classes[$i].'"';
	}

	// filtre très capricieux : on va réorganiser les attributs des balises 'p' pour correspondre aux regexp. attribut class en premier
 	preg_match_all('`<p([^>]*)(class="[^"]*")([^>]*)>(.*)</p>`Us', $texte, $match);
  	foreach($match[0] as $k=>$m) {
		if(!empty($match[1][$k])) {
   			$texte = str_replace($m, "<p ".$match[2][$k]." ".$match[1][$k]." ".$match[3][$k].">".$match[4][$k]."</p>", $texte);
		}
  	}

	global $attrs;
	$attrs = array();
	// on modifie les attributs contenus dans les table td pour faciliter la regexp
	$texte = preg_replace_callback("`<td([^>]*)>(.*)</td>`Us", 
									create_function(
									// Les guillemets simples sont très importants ici
									// ou bien il faut protéger les caractères $ avec \$
									'$matches',
									'static $cpt=10000;$cpt++;global $attrs;$attrs[$cpt] = $matches[1]; return "<td id=\"$cpt\">$matches[2]</td>";'
									), $texte);

	/* Regexp : cherche les paragraphes à numéroter, en ignorant les paragraphes contenant une image ou un tableau
	 *  ignore aussi les cellules d'un tableau
	 * ignore tous les styles attribués à la balise <p> sauf "texte" ; on peut rajouter un style à <p> comme l'exemple suivant : (<p\b class=(\"texte\"|\"citation\"). On a ajouté le style "citation" avec un "|" (OU exclusif).
	 * ignore les puces
	 */
/*
	if ($length_tab_classes != 1) {
		$regexp = '/(?:(?<!(<td id="\d\d\d\d\d">)))(?:(?<!(<li>)))(<p class=('.$chaine_classes.' )[^>]*)(>)(?!(<a\b[^>]*><\/a>)?<img|<table)/ie';
	} else {
		$regexp = '`(?:(?<!(<td id="\d\d\d\d\d">)))(?:(?<!(<li>)))(<p class='.$chaine_classes.'[^>]*)(>)(?!(<a\b[^>]*><\/a>)?<img|<table)`ie';
	}

	// on formate les balises de tableau et <li> pour faciliter la reconnaissance de la balise dans la regex
	$search = array ('/<table[^>]*>/', '/<tr[^>]*>/', '/<li[^>]*>/');
	$replace = array ('<table>', '<tr>', '<li>');
	$texte = preg_replace($search, $replace, $texte);

	// presence de 2 voir 3 paragraphes dans une cellule de tableau ? on nettoie tout ca
	$regex = '`(<td id="\d\d\d\d\d">)((<p[^>]*>)(.*)(</p>))+(</td>)`Uis';
	preg_match_all($regex, $texte, $m);
	foreach($m[0] as $k=>$match) {
		preg_match_all("/(<p[^>]*>(.*)<\/p>)+/iUs", $match, $mm);
		if(($nb = count($mm[0])) > 1) {
			$i=0;
			$t .= $mm[2][$i++];
			while($i<$nb) 
				$t .= " ".$mm[2][$i++];
			
			$texte = str_replace($match, $m[1][$k].$m[3][$k].$t.$m[5][$k].$m[6][$k], $texte);
			unset($t);
		}
	}

	// on numérote les paragraphes
	$texte = preg_replace($regexp, 'replacement("\\1","\\2","\\3","\\4","\\5",1)', $texte);

	// formatage des styles des cellules
	foreach($attrs as &$attr) {
		unset( $tmpattr );
		// background
		preg_match("`background(-color)?:[^;]*;`", $attr, $r);
		if(!empty($r[0]))
			$tmpattr = ' style="'.$r[0].'" ';
		// fusion
		preg_match("`colspan=\"[^\"]*\"`", $attr, $r2);
		if(!empty($r2[0]))
			$tmpattr .= " ".$r2[0];
		preg_match("`rowspan=\"[^\"]*\"`", $attr, $r3);
		if(!empty($r3[0]))
			$tmpattr .= " ".$r3[0];
		$attr = $tmpattr;
	}
	// on remplace les id="xxxxx" par les attributs des balises <td> préformaté juste au dessus
	$texte = preg_replace_callback("`<td id=\"(\d\d\d\d\d)\">(.*)</td>`iuUs", 
									create_function(
									// Les guillemets simples sont très importants ici
									// ou bien il faut protéger les caractères $ avec \$
									'$matches',
									'global $attrs;return "<td ".$attrs[$matches[1]].">".$matches[2]."</td>";'
									), $texte);

	return $texte;
}*/



/** renvoie le type mime d'un fichier par le système (a+ windows)
* @author Bruno Cénou
* @param  string $filename le nom du fichier
*/
function getFileMime($filename){
	system('file -i -b '.escapeshellarg($filename));
}


/** renvoie le type seul d'un fichier 
* @author Bruno Cénou
* @param  string $filename le nom du fichier
*/

function getFileType($filename){
	ob_start();
	getFileMime($filename);
	$str = ob_get_contents();
	ob_end_clean();
	$tmp = explode('/', $str);
	return trim($tmp[1]) ? trim($tmp[1]) : 'unknown';
}


/** Transforme une date MySql en timestamp UNIX 
* @author Bruno Cénou
* @param string $date 
*/

function mysql2TS($date){
	$date = str_replace(array(' ', '-', ':'), '', $date);
	return mktime(substr($date, 8, 2), substr($date, 10, 2), substr($date, 12, 2), substr($date, 4, 2), substr($date, 6, 2), substr($date, 0, 4));
}

/** Transforme une date MySql en timestamp UNIX 
* @author Bruno Cénou
* @param string $time 
*/

function time2Date($time){
	return substr($time, 0, 4)."-".substr($time, 4, 2)."-".substr($time, 6, 2);
}

/** Transforme un timestamp MySql en date MySql 
* @author Bruno Cénou
* @param string $date 
*/

function date2Time($date){
	$time = mktime(substr($date, 11, 2), substr($date, 14, 2), substr($date, 17, 2), substr($date, 5, 2), substr($date, 8, 2), substr($date, 0, 4));
	return strftime('%Y%m%d%H%M%S', $time);
}

/** Formate une date/heure GMT/CUT en fonction de la configuration locale (pour LS) 
* @author Bruno Cénou
* @param string $time 
*/

function LSgmstrftime($time){
	return strftime('%Y-%m-%dT%TZ', $time);
}

/** Formate une date/heure GMT/CUT en fonction de la configuration locale (pour LS) 
* @author Bruno Cénou
* @param string $time 
*/

function formatIdentifier($str) {
		require_once 'func.php';
		return preg_replace(array("/\W+/", "/-+$/"), array('-', ''), makeSortKey(strip_tags($str)));
}

/** Nettoyage des caractères windows illegaux + nettoyage pour flux XML
* @author Bruno Cénou
* @param string $str 
*/

function cleanBadChars($str){
	$replace = array(
			129 => "",
			130 => "#8218",
			131 => "#402",
			132 => "#8222",
			133 => "#8230",
			134 => "#8224",
			135 => "#8225",
			136 => "#710",
			137 => "#8240",
			138 => "#352",
			139 => "#8249",
			140 => "#338",
			141 => "",
			142 => "#381",
			143 => "",
			144 => "",
			145 => "#8216",
			146 => "#8217",
			147 => "#8220",
			148 => "#8221",
			149 => "#8226",
			150 => "#8211",
			151 => "#8212",
			152 => "#732",
			153 => "#8482",
			154 => "#353",
			155 => "#8250",
			156 => "#339",
			157 => "",
			158 => "#382",
			159 => "#376"
	);
	$str = HTML2XML($str);
	$str = str_replace(array('&#39;', utf8_encode(chr(146))) ,"'", $str);
	//$str = ereg_replace('&([A-Za-z0-9]|[:punct:]| )+', '&amp;', $str);
	$str = preg_replace('/&(?!amp;|#[0-9]+;)/', '&amp;', $str);
	//$str = htmlspecialchars($str);
        foreach($replace as $k=>$v){
                $replace_str = $v != '' ? '&'.$v.';' : '';
                $str = preg_replace("/".utf8_encode(chr($k))."/", $replace_str, $str);
                //echo "$k = $replace_str\n";
        }


return $str;
}

/** convertit entités html en entités xml 
* @author Bruno Cénou
* @param string $str 
* @param bool $reverse xml->html
*/

function HTML2XML($str, $reverse=false){
	$replace = array(
		"&quot;" => "&#34;",
		"&amp;" => "&#38;",
		"&AMP;" => "&#38;",
		"&apos;" => "&#39;",
		"&lt;" => "&#60;",
		"&gt;" => "&#62;",
		"&nbsp;" => "&#160;",
		"&iexcl;" => "&#161;",
		"&cent;" => "&#162;",
		"&pound;" => "&#163;",
		"&curren;" => "&#164;",
		"&yen;" => "&#165;",
		"&brvbar;" => "&#166;",
		"&sect;" => "&#167;",
		"&uml;" => "&#168;",
		"&copy;" => "&#169;",
		"&ordf;" => "&#170;",
		"&laquo;" => "&#171;",
		"&not;" => "&#172;",
		"&shy;" => "&#173;",
		"&reg;" => "&#174;",
		"&macr;" => "&#175;",
		"&deg;" => "&#176;",
		"&plusmn;" => "&#177;",
		"&sup2;" => "&#178;",
		"&sup3;" => "&#179;",
		"&acute;" => "&#180;",
		"&micro;" => "&#181;",
		"&para;" => "&#182;",
		"&middot;" => "&#183;",
		"&cedil;" => "&#184;",
		"&sup1;" => "&#185;",
		"&ordm;" => "&#186;",
		"&raquo;" => "&#187;",
		"&frac14;" => "&#188;",
		"&frac12;" => "&#189;",
		"&frac34;" => "&#190;",
		"&iquest;" => "&#191;",
		"&Agrave;" => "&#192;",
		"&Aacute;" => "&#193;",
		"&Acirc;" => "&#194;",
		"&Atilde;" => "&#195;",
		"&Auml;" => "&#196;",
		"&Aring;" => "&#197;",
		"&AElig;" => "&#198;",
		"&Ccedil;" => "&#199;",
		"&Egrave;" => "&#200;",
		"&Eacute;" => "&#201;",
		"&Ecirc;" => "&#202;",
		"&Euml;" => "&#203;",
		"&Igrave;" => "&#204;",
		"&Iacute;" => "&#205;",
		"&Icirc;" => "&#206;",
		"&Iuml;" => "&#207;",
		"&ETH;" => "&#208;",
		"&Ntilde;" => "&#209;",
		"&Ograve;" => "&#210;",
		"&Oacute;" => "&#211;",
		"&Ocirc;" => "&#212;",
		"&Otilde;" => "&#213;",
		"&Ouml;" => "&#214;",
		"&times;" => "&#215;",
		"&Oslash;" => "&#216;",
		"&Ugrave;" => "&#217;",
		"&Uacute;" => "&#218;",
		"&Ucirc;" => "&#219;",
		"&Uuml;" => "&#220;",
		"&Yacute;" => "&#221;",
		"&THORN;" => "&#222;",
		"&szlig;" => "&#223;",
		"&agrave;" => "&#224;",
		"&aacute;" => "&#225;",
		"&acirc;" => "&#226;",
		"&atilde;" => "&#227;",
		"&auml;" => "&#228;",
		"&aring;" => "&#229;",
		"&aelig;" => "&#230;",
		"&ccedil;" => "&#231;",
		"&egrave;" => "&#232;",
		"&eacute;" => "&#233;",
		"&ecirc;" => "&#234;",
		"&euml;" => "&#235;",
		"&igrave;" => "&#236;",
		"&iacute;" => "&#237;",
		"&icirc;" => "&#238;",
		"&iuml;" => "&#239;",
		"&eth;" => "&#240;",
		"&ntilde;" => "&#241;",
		"&ograve;" => "&#242;",
		"&oacute;" => "&#243;",
		"&ocirc;" => "&#244;",
		"&otilde;" => "&#245;",
		"&ouml;" => "&#246;",
		"&divide;" => "&#247;",
		"&oslash;" => "&#248;",
		"&ugrave;" => "&#249;",
		"&uacute;" => "&#250;",
		"&ucirc;" => "&#251;",
		"&uuml;" => "&#252;",
		"&yacute;" => "&#253;",
		"&thorn;" => "&#254;",
		"&yuml;" => "&#255;",
		"&OElig;" => "&#338;",
		"&oelig;" => "&#339;",
		"&Scaron;" => "&#352;",
		"&scaron;" => "&#353;",
		"&Yuml;" => "&#376;",
		"&fnof;" => "&#402;",
		"&circ;" => "&#710;",
		"&tilde;" => "&#732;",
		"&Alpha;" => "&#913;",
		"&Beta;" => "&#914;",
		"&Gamma;" => "&#915;",
		"&Delta;" => "&#916;",
		"&Epsilon;" => "&#917;",
		"&Zeta;" => "&#918;",
		"&Eta;" => "&#919;",
		"&Theta;" => "&#920;",
		"&Iota;" => "&#921;",
		"&Kappa;" => "&#922;",
		"&Lambda;" => "&#923;",
		"&Mu;" => "&#924;",
		"&Nu;" => "&#925;",
		"&Xi;" => "&#926;",
		"&Omicron;" => "&#927;",
		"&Pi;" => "&#928;",
		"&Rho;" => "&#929;",
		"&Sigma;" => "&#931;",
		"&Tau;" => "&#932;",
		"&Upsilon;" => "&#933;",
		"&Phi;" => "&#934;",
		"&Chi;" => "&#935;",
		"&Psi;" => "&#936;",
		"&Omega;" => "&#937;",
		"&alpha;" => "&#945;",
		"&beta;" => "&#946;",
		"&gamma;" => "&#947;",
		"&delta;" => "&#948;",
		"&epsilon;" => "&#949;",
		"&zeta;" => "&#950;",
		"&eta;" => "&#951;",
		"&theta;" => "&#952;",
		"&iota;" => "&#953;",
		"&kappa;" => "&#954;",
		"&lambda;" => "&#955;",
		"&mu;" => "&#956;",
		"&nu;" => "&#957;",
		"&xi;" => "&#958;",
		"&omicron;" => "&#959;",
		"&pi;" => "&#960;",
		"&rho;" => "&#961;",
		"&sigmaf;" => "&#962;",
		"&sigma;" => "&#963;",
		"&tau;" => "&#964;",
		"&upsilon;" => "&#965;",
		"&phi;" => "&#966;",
		"&chi;" => "&#967;",
		"&psi;" => "&#968;",
		"&omega;" => "&#969;",
		"&thetasym;" => "&#977;",
		"&upsih;" => "&#978;",
		"&piv;" => "&#982;",
		"&ensp;" => "&#8194;",
		"&emsp;" => "&#8195;",
		"&thinsp;" => "&#8201;",
		"&zwnj;" => "&#8204;",
		"&zwj;" => "&#8205;",
		"&lrm;" => "&#8206;",
		"&rlm;" => "&#8207;",
		"&ndash;" => "&#8211;",
		"&mdash;" => "&#8212;",
		"&lsquo;" => "&#8216;",
		"&rsquo;" => "&#8217;",
		"&sbquo;" => "&#8218;",
		"&ldquo;" => "&#8220;",
		"&rdquo;" => "&#8221;",
		"&bdquo;" => "&#8222;",
		"&dagger;" => "&#8224;",
		"&Dagger;" => "&#8225;",
		"&bull;" => "&#8226;",
		"&hellip;" => "&#8230;",
		"&permil;" => "&#8240;",
		"&prime;" => "&#8242;",
		"&Prime;" => "&#8243;",
		"&lsaquo;" => "&#8249;",
		"&rsaquo;" => "&#8250;",
		"&oline;" => "&#8254;",
		"&frasl;" => "&#8260;",
		"&euro;" => "&#8364;",
		"&image;" => "&#8465;",
		"&weierp;" => "&#8472;",
		"&real;" => "&#8476;",
		"&trade;" => "&#8482;",
		"&alefsym;" => "&#8501;",
		"&larr;" => "&#8592;",
		"&uarr;" => "&#8593;",
		"&rarr;" => "&#8594;",
		"&darr;" => "&#8595;",
		"&harr;" => "&#8596;",
		"&crarr;" => "&#8629;",
		"&lArr;" => "&#8656;",
		"&uArr;" => "&#8657;",
		"&rArr;" => "&#8658;",
		"&dArr;" => "&#8659;",
		"&hArr;" => "&#8660;",
		"&forall;" => "&#8704;",
		"&part;" => "&#8706;",
		"&exist;" => "&#8707;",
		"&empty;" => "&#8709;",
		"&nabla;" => "&#8711;",
		"&isin;" => "&#8712;",
		"&notin;" => "&#8713;",
		"&ni;" => "&#8715;",
		"&prod;" => "&#8719;",
		"&sum;" => "&#8721;",
		"&minus;" => "&#8722;",
		"&lowast;" => "&#8727;",
		"&radic;" => "&#8730;",
		"&prop;" => "&#8733;",
		"&infin;" => "&#8734;",
		"&ang;" => "&#8736;",
		"&and;" => "&#8743;",
		"&or;" => "&#8744;",
		"&cap;" => "&#8745;",
		"&cup;" => "&#8746;",
		"&int;" => "&#8747;",
		"&there4;" => "&#8756;",
		"&sim;" => "&#8764;",
		"&cong;" => "&#8773;",
		"&asymp;" => "&#8776;",
		"&ne;" => "&#8800;",
		"&equiv;" => "&#8801;",
		"&le;" => "&#8804;",
		"&ge;" => "&#8805;",
		"&sub;" => "&#8834;",
		"&sup;" => "&#8835;",
		"&nsub;" => "&#8836;",
		"&sube;" => "&#8838;",
		"&supe;" => "&#8839;",
		"&oplus;" => "&#8853;",
		"&otimes;" => "&#8855;",
		"&perp;" => "&#8869;",
		"&sdot;" => "&#8901;",
		"&lceil;" => "&#8968;",
		"&rceil;" => "&#8969;",
		"&lfloor;" => "&#8970;",
		"&rfloor;" => "&#8971;",
		"&lang;" => "&#9001;",
		"&rang;" => "&#9002;",
		"&loz;" => "&#9674;",
		"&spades;" => "&#9824;",
		"&clubs;" => "&#9827;",
		"&hearts;" => "&#9829;",
		"&diams;" => "&#9830;"
		);
	$str = $reverse ? ( str_replace(array_values($replace),array_keys($replace),$str) ) : ( str_replace(array_keys($replace),array_values($replace),$str) );
	return $str;
}


/** récupère l'ID du parent d'une entité en fonction de son type
* @author Bruno Cénou
* @param int $id 
* @param string $type 
*/

function getParentByType($id,$type,$return = false){
        $q = "SELECT idparent FROM $GLOBALS[tp]entities WHERE id = '$id'";
        $r = mysql_query($q);
        if($idparent = @mysql_result($r, 0)){
                $q = "SELECT t.type FROM $GLOBALS[tp]entities e, $GLOBALS[tp]types t WHERE e.id = '$idparent'
                AND e.idtype = t.id";
                $r2 = mysql_query($q);
                $ltype = mysql_result($r2, 0);
                //echo mysql_error();
                if($ltype == $type){
			if($return) return $idparent;
                        echo $idparent;
                }else{
	                $idparent = getParentByType($idparent, $type,$return);
			if($return) return $idparent;
                }
        }else{
                return(FALSE);
        }
}

/** 
 * Crypte les emails pour qu'ils ne soient pas reconnaissable par les robots spam
 * 
 * @author Pierre-Alain Mignot
 * @param string $texte le texte à modifier
 * @param bool $codeInclude inclut directement le JS dans la page. défaut à false (fichier JS séparé)
 * @return $texte le texte avec les emails cryptés
 */
function cryptEmails($texte, $codeInclude = FALSE)
{
	if(TRUE === $codeInclude) {
		$javascript = "<script type=\"text/javascript\">
				function recomposeMail(obj, region, nom, domaine)
				{
					obj.href = 'mailto:' + nom + '@' + domaine + '.' + region;
					obj.onclick = (function() {});
				}
				</script>\n";
		$texte = $javascript . $texte;
	}

	// on récupère tous les liens mail contenus dans le texte
	preg_match_all("`<a href=\"mailto:([^\"]*)\">([^>]*)</a>`", $texte, $matches);

	foreach($matches[0] as $k=>$mail) {
		$name = explode("@", $matches[1][$k]);
		$extension = substr(strrchr($name[1], '.'), 1);
		$domain = substr($name[1], 0, strrpos($name[1], '.'));

		// email dans le contenu du lien ?
		if(array(0=>$matches[2][$k]) != $content = explode("@", $matches[2][$k])) { 
			/* 
			on met des span cachés dans le contenu du lien pour éviter que les robots puissent récupèrer le mail
			résultat dans le code source de la page avec test@domaine.com : 
			test<span style="display: none;">ANTIBOT</span>@<span style="display: none;">ANTIBOT</span>domaine<span style="display: none;">ANTIBOT</span>.com
			*/
			$domainContent = substr($content[1], 0, strrpos($content[1], '.'));
			$newContent = $content[0]."<span style=\"display: none;\">ANTIBOT</span>@<span style=\"display: none;\">ANTIBOT</span>". $domainContent ."<span style=\"display: none;\">ANTIBOT</span>.". $extension;
		}

		// création du lien crypté : la balise href ne contient qu'un dièze et l'appel à la fonction JS
		$newLink = "<a href=\"#\" onclick=\"javascript:recomposeMail(this, '".$extension."', '".$name[0]."', '".$domain."');\">";
		$newLink .= empty($newContent) ? $matches[2][$k] : $newContent;
		$newLink .= "</a>";

		// on remplace
		$texte = str_replace($mail, $newLink, $texte);
	}
	return $texte;
}

/** 
 * Nettoie les mises en forme locales sur les appels de notes
 * 
 * @author Pierre-Alain Mignot
 * @param string $text le texte à modifier
 * @return $text le texte filtré
 */
function cleanCallNotes($text)
{
	return preg_replace("/<(span|sup|sub|em|strong)[^>]*>(\s*<a class=\"(end|foot)notecall\"[^>]*>.*?<\/a>)\s*<\/\\1>/s", '\\2', $text);
}

/** 
 * Coloration syntaxique de code
 * 
 * @author Pierre-Alain Mignot
 * @param string $text le code à colorer
 * @param string $language langage pour lequel appliquer la coloration. Valeurs possibles : xml et html4strict. défaut xml
 * @param bool $lineNumbers numérotation des lignes. défaut à true
 * @return $text le texte coloré
 */
function highlight_code($text, $language='xml', $lineNumbers=true)
{
	require_once SITEROOT . $GLOBALS['sharedir'] . "/plugins/geshi/geshi.php";
	$geshi =& new GeSHi($text, $language);
	if($lineNumbers)
		$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
	$geshi->set_header_type(GESHI_HEADER_DIV);
	return $geshi->parse_code();
}

function cleanHTML( $text ) {
	$GLOBALS['textfunc_hasbeencleaned'] = true;

	require_once 'htmLawed.php';
	$config = array(
		'valid_xhtml' => 1,
		'make_tag_strict' => 0,
		'unique_ids' => 0,
		'scheme' => '*: *',
	);
	return htmLawed($text, $config);
}

