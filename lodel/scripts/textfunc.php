<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier utilitaire proposant des fonctions sur les textes dans Lodel
 */

if (is_readable(C::get('home', 'cfg').'textfunc_local.php'))
	include_once 'textfunc_local.php';

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
 * Uppercase the first letter of Texte
 */
function majuscule($texte) {
	return preg_replace_callback("/^(\s*(?:<[^>]+>)*\s*)(\w)/s",
		function($matches) {
			return $matches[1] . strtoupper($matches[2]);
		},
		$texte
	);
}

function textebrut($letexte)
{
	//$letexte = preg_replace("/(<[^>]+>|&nbsp;|[\n\r\t])+/", "", $letexte);
	return strip_tags(preg_replace("/(&nbsp;|[\n\r\t]|<\/(blockquote|div|dl|h[1-6]|ol|p|table|ul|li)>|<br\/?>)+/", " ", $letexte));
//	return strip_tags(preg_replace("/(&nbsp;|[\n\r\t])+/", " ", $letexte));
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
 * Truncates text.
 *
 * Cuts a string to the length of $length and replaces the last characters
 * with the ending if the text is longer than length.
 *
 * This function is checked out from CakePHP code.
 *
 * ### Options:
 *
 * - `ending` Will be used as Ending and appended to the trimmed string
 * - `exact` If false, $text will not be cut mid-word
 * - `html` If true, HTML tags would be handled correctly
 *
 * @param string  $text String to truncate.
 * @param integer $length Length of returned string, including ellipsis.
 * @param array $options An array of html attributes and options.
 * @return string Trimmed string.
 * @access public
 * @link http://book.cakephp.org/view/1469/Text#truncate-1625
 */
function cuttext($text, $length = 100, $dots = false, $html = true) {
	$GLOBALS['textfunc_hasbeencut'] = false;
	$options = array(
		'ending' => '', 'exact' => true, 'html' => $html,
	);
	$text = $text;

	if($dots) $options['ending'] = '...';

	extract($options);
	$encoding = mb_detect_encoding($text, 'UTF-8, ISO-8859-1, ISO-8859-15, Windows-1252', true);

	if ($html) {
		if (mb_strlen(preg_replace('/<.*?>/', '', $text), $encoding) <= $length) {
			return $text;
		}
		$totalLength = mb_strlen(strip_tags($ending), $encoding);
		$openTags = array();
		$truncate = '';

		preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
		foreach ($tags as $tag) {
			if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
				if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
					array_unshift($openTags, $tag[2]);
				} else if (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
					$pos = array_search($closeTag[1], $openTags);
					if ($pos !== false) {
						array_splice($openTags, $pos, 1);
					}
				}
			}
			$truncate .= $tag[1];

			$contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]), $encoding);
			if ($contentLength + $totalLength > $length) {
				$left = $length - $totalLength;
				$entitiesLength = 0;
				if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
					foreach ($entities[0] as $entity) {
						if ($entity[1] + 1 - $entitiesLength <= $left) {
							$left--;
							$entitiesLength += mb_strlen($entity[0], $encoding);
						} else {
							break;
						}
					}
				}

				$truncate .= mb_substr($tag[3], 0 , $left + $entitiesLength, $encoding);
				break;
			} else {
				$truncate .= $tag[3];
				$totalLength += $contentLength;
			}
			if ($totalLength >= $length) {
				break;
			}
		}
		$truncate = preg_replace('/\&[0-9a-z]{0,8}$/i', '', $truncate);

	} else {
		if (mb_strlen($text, $encoding) <= $length) {
			return $text;
		} else {
			$truncate = mb_substr($text, 0, $length - mb_strlen($ending, $encoding), $encoding);
		}
	}
	if (!$exact) {
		$spacepos = mb_strrpos($truncate, ' ', $encoding);
		if (isset($spacepos)) {
			if ($html) {
				$bits = mb_substr($truncate, $spacepos, null, $encoding);
				preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
				if (!empty($droppedTags)) {
					foreach ($droppedTags as $closingTag) {
						if (!in_array($closingTag[1], $openTags)) {
							array_unshift($openTags, $closingTag[1]);
						}
					}
				}
			}
			$truncate = mb_substr($truncate, 0, $spacepos, $encoding);
		}
	}
	$truncate .= $ending;

	if ($html) {
		foreach ($openTags as $tag) {
			$truncate .= '</'.$tag.'>';
		}
	}
	$GLOBALS['textfunc_hasbeencut'] = true;
	return  $truncate;
}

function cut_without_tags($text, $length, $dots=false)
{
	$encoding = mb_detect_encoding($text, 'UTF-8, ISO-8859-1, ISO-8859-15, Windows-1252', true);
	$text2 = mb_substr($text." ", 0, $length, $encoding);
	if (mb_strlen($text2, $encoding) < mb_strlen($text, $encoding)) {
		$GLOBALS['textfunc_hasbeencut'] = true;
	}
// 	$last_space_position = mb_strrpos($text2, " ", $encoding);
	
// 	if (!($last_space_position === false)) {
		// supprime le dernier espace et tout ce qu'il y a derrière
		//$text2 = substr($text2, 0, $last_space_position);
		$text2 = rtrim($text2);
// 	}
	
	return (($GLOBALS['textfunc_hasbeencut'] && $dots) ? $text2.' (...)' : $text2);
}

function hasbeencut()
{
	return $GLOBALS['textfunc_hasbeencut'] ? true : false;
}

function couperpara($texte, $long)
{
	$encoding = mb_detect_encoding($texte, 'UTF-8, ISO-8859-1, ISO-8859-15, Windows-1252', true);
	$pos = -1;
	do {
		$pos = mb_strpos($texte, "</p>", $pos +1, $encoding);
		$long --;
	} while ($pos !== FALSE && $long > 0);
	return $pos > 0 ? mb_substr($texte, 0, $pos +4, $encoding) : $texte;
}

function spip($letexte)
{
	$puce = "<IMG SRC=\"Images/smallpuce.gif\">";
	// Harmoniser les retours chariot
	$letexte = preg_replace("\r\n?", "\n", $letexte);

	// Corriger HTML
	$letexte = preg_replace("</?p>/i", "\n\n\n", $letexte);

	//
	// Raccourcis liens
	//
	$regexp = "\[([^][]*)->([^]]*)\]";
	$texte_a_voir = $letexte;
	$texte_vu = '';
	while (preg($regexp, $texte_a_voir, $regs))	{
		$lien_texte = $regs[1];
		$lien_url = trim($regs[2]);
		$compt_liens ++;
		$lien_interne = false;

		$insert = "<a href=\"$lien_url\">".$lien_texte."</a>";
		$zetexte = preg_split($regexp, $texte_a_voir, 2);
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
	if(!$s) return $s;
	$ret = false;
	// date
	if (preg_match("/(\d\d\d\d)-(\d\d)-(\d\d)/", $s, $result)) {
		if ($result[1] > 9000)
			return "jamais";
		if ($result[1] == 0)
			return "";
		if (is_numeric($result[1]) && $result[2] == 0 && $result[3] == 0)
			return $result[1];
		$dat = (int)$result[1]."-".(int)$result[2]."-".(int)$result[3];
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
			$level = (int)$result[5];
            		if(!isset($tocid[$level])) $tocid[$level] = 0;
			$sig = $level."n". (++ $tocid[$level]);
			$aopenfirst = '<a href="#tocfrom'.$sig.'" id="tocto'.$sig.'">';
			$aopennext = '<a href="#tocfrom'.$sig.'">';
			$aclose = '</a>';
			// split the result in order not to contains any a tag
			$arr = preg_split("/(<a\b[^>]*>.*?<\/a>)/", $result[6], -1, PREG_SPLIT_DELIM_CAPTURE); // split with the <a...> </a>
			$ret = $result[1];

			$c = count($arr);
			for ($i = 0; $i < $c; $i += 2) {
				if ($arr[$i]) {                    
					if ($i == 0) {
						$ret .= $aopenfirst.$arr[$i].$aclose;
					} else {
						$ret .= $aopennext.$arr[$i].$aclose;
					}
				}
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
	return isset($result[1]) ? $result[1] : null;
}

function vignette($text, $width)
{
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
	list($widt, $height, $type, $attr) = @getImageSize($text);
	if($widt && $widt <= $width)
		return $text;
	// creer la vignette (de largeur width ou de hauteur width en fonction de la forme
	function_exists('resize_image') || include("images.php");
	if (!resize_image($width, $text, $vignettefile))
		return getlodeltextcontents("ERROR_IMAGE_RESIZING_FAILED", "COMMON");
	return $vignettefile;
}

# renvoie les attributs pour une image
function sizeattributs($text)
{
	$result = @getImageSize($text);
    	return isset($result[3]) ? $result[3] : null;
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
	return preg_replace('/<a\b[^>]+class="footnotecall"[^>]*>.*?<\/a>/s', "", $text);
}
/** 
 * Supprimer les appels de notes de fin de document.
 */

function removeendnotes($text)
{
	return preg_replace('/<a\b[^>]+class="endnotecall"[^>]*>.*?<\/a>/s', "", $text);
}

/** 
 * Fonction permettant de supprimer les appels de notes d'un texte.
 */

function removenotes($text)
{
	$text = preg_replace('/<a\b[^>]+class="(foot|end)notecall"[^>]*>.*?<\/a>/s', "", $text);
	$text = preg_replace('/<sup[^>]*>\s*(<[^>]+\/?>\s*<\/[^>]+>)*\s*<\/sup>/s', "", $text);
	return $text;
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
	$tags = explode(',', $tags);
	foreach ($tags as $v) {
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
	return (isset($text) && (string)$text !== "0000-00-00");
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
 
function reg_replace($str, $search, $replace, $limit = -1, &$cpt = null){
   return preg_replace($search, $replace, $str, $limit, $cpt);
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
	return !yes($texte);
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
	$texte = str_replace("\n", "", $texte);
	preg_match_all('/<(div|p)[^>]*>.*?<\/\\1>/', $texte, $results);
	#  print_r($results);
	$notere = '<a\b[^>]+class="(foot|end)note(definition|symbol)[^>]*>';
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
				trigger_error("unknown note type of tag num : \"$type\"", E_USER_ERROR);
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
				trigger_error("unknown note type \"$type\"", E_USER_ERROR);
		}
	}
	return join("", $notes);
}

/**
 * fonctions pour le nettoyage de base des champs importes
 */

function tocss($text, $options = "")
{
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
		$creationmethod = C::get('creationmethod');
	if (!$creationinfo)
		$creationinfo = C::get('creationinfo');

	if ($creationmethod == "form") {
		switch ($creationinfo) {
		case 'xhtml' :
			return $text;
		case 'wiki' :
			return wiki($text);
		case 'bb' :
			trigger_error("not yet implemented", E_USER_ERROR);
		default :
			trigger_error("ERROR: unknown creationinfo", E_USER_ERROR);
		}
	}
	if (substr($creationmethod, 0, 6) == "servoo")
		return tocss($text);

	trigger_error("ERROR: unknown creationmethod", E_USER_ERROR);
}

/**
 * Permet de savoir si un lien est relatif 
 */

function isrelative($lien)
{
	$test = @parse_url($lien);
	if(!$test) return false;
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
	$arr = preg_split('/(<a\b[^>]+class="(foot|end)notecall"[^>]*>.*?<\/a>)/s', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
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
	$text = strtoupper($text);
	return (isset($GLOBALS['languages'][$text]) ? $GLOBALS['languages'][$text] : '');
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
		$result = @getImageSize($image);
		return isset($result[0]) ? $result[0] : 0;
	}
	return 0;
}

/**
 * Retourne la hauteur de l'image.
 */

function imageheight($image)
{
	if ($image)	{
		$result = @getImageSize($image);
        	return isset($result[1]) ? $result[1] : 0;
	}
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
			return (int)$size.$unit;
	}
}

/**
 * Bootstrap Lodel pour WikiRender
 * Assure la conversion texte Wiki en xhtml
 */

function wiki($text)
{
	class_exists('WikiRenderer', TRUE) || require_once ('vendor/autoload.php');
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
	return preg_match("/<(p|br|span|ul|li|dl|strong|em|table|tr|td|th|sup|sub|h[1-9]|a|blockquote|code|del|img|ol|dd|dt|div|code)\b[^><]*>/i", $text);
}

/*
 * @internal
 *
 */
function lodeltextcolor($status)
{
	$colorstatus = array (-1 => "red", 1 => "orange", 2 => "green");
	return isset($colorstatus[$status]) ? $colorstatus[$status] : 'red';
}

/**
 * Return the value in parameter if the variable is empty/null
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

// fonction pour réparer le HTML invalide
function cleanHTML( $text ) {
	$GLOBALS['textfunc_hasbeencleaned'] = true;
	return C::clean($text);
}

/**
 * Nettoie le texte de balises <a> vides et autofermées
 * 
 * @param DOMDocument $dom Le dom du corps du texte
 */
function cleanIllegalTags( DOMDocument &$dom ){
	
	$xpath = new DOMXpath($dom);
	$paths = array(
					'//a[not(@href) and not(text())]',
					'//em[not(text()) and not(node())]',
					'//p[not(./*) and not(text())]',
					);
	foreach( $paths as $path ){
		foreach($xpath->query($path) as $elem){
			$elem->parentNode->removeChild($elem);
		}
	}
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
	$doc = new DOMDocument();
	if(!isset($GLOBALS['textfunc_hasbeencleaned'])) $texte = cleanHTML($texte);
	if(!$doc->loadXML("<body>" . $texte . "</body>"))
		return $texte;

	$dom = new DOMXpath($doc);

	$dom->preserveWhiteSpace = true;
	$dom->formatOutput       = false;

	cleanIllegalTags($doc);

	$res = $dom->query("//p[@class='$styles']");
	$count = 0;
	$illegalparents = array('li', 'td');
	foreach($res as $node){
		if(in_array($node->parentNode->localName, $illegalparents) 
			|| in_array($node->parentNode->parentNode->localName, $illegalparents)
		    || ($dom->query(".//img", $node)->length && ($dom->query('.//img[@class="inline"]', $node)->length == 0))) continue;
		++$count;
		$parnum = $doc->createElement('span');
		$parnum->setAttribute('class', 'paranumber');
		$parnum->nodeValue = $count;

		$node->insertBefore($parnum, $node->firstChild);
	}

	$texte = "";
	foreach($dom->query('/body/*') as $elem ) {
		$texte .= $doc->saveXML($elem);
	}
	return cleanHTML($texte);
}


/** renvoie le type mime d'un fichier par le système (a+ windows)
* @author Bruno Cénou
* @param  string $filename le nom du fichier
* @param bool $return 
*/
function getFileMime($filename, $return=false){
        $mime = null;
        exec('file --mime-type -b '.escapeshellarg($filename), $mime);
        if($return) return join('',$mime);
        foreach($mime as $l)
                echo $l;
}


/** renvoie le type seul d'un fichier 
* @author Bruno Cénou
* @param  string $filename le nom du fichier
*/

function getFileType($filename){
	$tmp = explode('/', getFileMime($filename));
	return (!isset($tmp[1]) || !($tmp[1] = trim($tmp[1]))) ? 'unknown' : $tmp[1];
}

/** Convertit date texte en timestamp
* @author Bruno Cénou
* @param string $date
*/

function mysql2TS($date){
	return strtotime($date);
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
	return preg_replace(array("/\W+/", "/-+$/"), array('-', ''), makeSortKey(strip_tags($str)));
}

/** Nettoyage des caractères windows illegaux + nettoyage pour flux XML
* @author Bruno Cénou
* @param string $str 
*/

function cleanBadChars($str){

	if(!is_string($str)) return $str;
	$replace = array (
		"\xc2\x80" => "\xe2\x82\xac", /* EURO SIGN */
		"\xc2\x81" => "",
		"\xc2\x82" => "\xe2\x80\x9a", /* SINGLE LOW-9 QUOTATION MARK */
		"\xc2\x83" => "\xc6\x92",     /* LATIN SMALL LETTER F WITH HOOK */
		"\xc2\x84" => "\xe2\x80\x9e", /* DOUBLE LOW-9 QUOTATION MARK */
		"\xc2\x85" => "\xe2\x80\xa6", /* HORIZONTAL ELLIPSIS */
		"\xc2\x86" => "\xe2\x80\xa0", /* DAGGER */
		"\xc2\x87" => "\xe2\x80\xa1", /* DOUBLE DAGGER */
		"\xc2\x88" => "\xcb\x86",     /* MODIFIER LETTER CIRCUMFLEX ACCENT */
		"\xc2\x89" => "\xe2\x80\xb0", /* PER MILLE SIGN */
		"\xc2\x8a" => "\xc5\xa0",     /* LATIN CAPITAL LETTER S WITH CARON */
		"\xc2\x8b" => "\xe2\x80\xb9", /* SINGLE LEFT-POINTING ANGLE QUOTATION */
		"\xc2\x8c" => "\xc5\x92",     /* LATIN CAPITAL LIGATURE OE */
		"\xc2\x8d" => "",
		"\xc2\x8e" => "\xc5\xbd",     /* LATIN CAPITAL LETTER Z WITH CARON */
		"\xc2\x8f" => "",
		"\xc2\x90" => "",
		"\xc2\x91" => "\xe2\x80\x98", /* LEFT SINGLE QUOTATION MARK */
		"\xc2\x92" => "\xe2\x80\x99", /* RIGHT SINGLE QUOTATION MARK */
		"\xc2\x93" => "\xe2\x80\x9c", /* LEFT DOUBLE QUOTATION MARK */
		"\xc2\x94" => "\xe2\x80\x9d", /* RIGHT DOUBLE QUOTATION MARK */
		"\xc2\x95" => "\xe2\x80\xa2", /* BULLET */
		"\xc2\x96" => "\xe2\x80\x93", /* EN DASH */
		"\xc2\x97" => "\xe2\x80\x94", /* EM DASH */
		"\xc2\x98" => "\xcb\x9c",     /* SMALL TILDE */
		"\xc2\x99" => "\xe2\x84\xa2", /* TRADE MARK SIGN */
		"\xc2\x9a" => "\xc5\xa1",     /* LATIN SMALL LETTER S WITH CARON */
		"\xc2\x9b" => "\xe2\x80\xba", /* SINGLE RIGHT-POINTING ANGLE QUOTATION*/
		"\xc2\x9c" => "\xc5\x93",     /* LATIN SMALL LIGATURE OE */
		"\xc2\x9e" => "\xc5\xbe",     /* LATIN SMALL LETTER Z WITH CARON */
		"\xc2\x9f" => "\xc5\xb8",      /* LATIN CAPITAL LETTER Y WITH DIAERESIS*/
		'&#39;'    => "'",
		"\x20\x13" => "-"
	);
	$str = HTML2XML($str);
	$str = preg_replace('/&(?!amp;|#[0-9]+;)/', '&amp;', $str);
	$str = strtr($str, $replace);
	return $str;
}

/** convertit entités html en entités xml 
* @author Bruno Cénou
* @param string $str 
* @param bool $reverse xml->html
*/

function HTML2XML($str, $reverse=false)
{
	if($reverse)
	{
		$replace = array (
			'#34' => '&quot;',
			'#38' => '&amp;',
			'#39' => '&apos;',
			'#60' => '&lt;',
			'#62' => '&gt;',
			'#160' => '&nbsp;',
			'#161' => '&iexcl;',
			'#162' => '&cent;',
			'#163' => '&pound;',
			'#164' => '&curren;',
			'#165' => '&yen;',
			'#166' => '&brvbar;',
			'#167' => '&sect;',
			'#168' => '&uml;',
			'#169' => '&copy;',
			'#170' => '&ordf;',
			'#171' => '&laquo;',
			'#172' => '&not;',
			'#173' => '&shy;',
			'#174' => '&reg;',
			'#175' => '&macr;',
			'#176' => '&deg;',
			'#177' => '&plusmn;',
			'#178' => '&sup2;',
			'#179' => '&sup3;',
			'#180' => '&acute;',
			'#181' => '&micro;',
			'#182' => '&para;',
			'#183' => '&middot;',
			'#184' => '&cedil;',
			'#185' => '&sup1;',
			'#186' => '&ordm;',
			'#187' => '&raquo;',
			'#188' => '&frac14;',
			'#189' => '&frac12;',
			'#190' => '&frac34;',
			'#191' => '&iquest;',
			'#192' => '&Agrave;',
			'#193' => '&Aacute;',
			'#194' => '&Acirc;',
			'#195' => '&Atilde;',
			'#196' => '&Auml;',
			'#197' => '&Aring;',
			'#198' => '&AElig;',
			'#199' => '&Ccedil;',
			'#200' => '&Egrave;',
			'#201' => '&Eacute;',
			'#202' => '&Ecirc;',
			'#203' => '&Euml;',
			'#204' => '&Igrave;',
			'#205' => '&Iacute;',
			'#206' => '&Icirc;',
			'#207' => '&Iuml;',
			'#208' => '&ETH;',
			'#209' => '&Ntilde;',
			'#210' => '&Ograve;',
			'#211' => '&Oacute;',
			'#212' => '&Ocirc;',
			'#213' => '&Otilde;',
			'#214' => '&Ouml;',
			'#215' => '&times;',
			'#216' => '&Oslash;',
			'#217' => '&Ugrave;',
			'#218' => '&Uacute;',
			'#219' => '&Ucirc;',
			'#220' => '&Uuml;',
			'#221' => '&Yacute;',
			'#222' => '&THORN;',
			'#223' => '&szlig;',
			'#224' => '&agrave;',
			'#225' => '&aacute;',
			'#226' => '&acirc;',
			'#227' => '&atilde;',
			'#228' => '&auml;',
			'#229' => '&aring;',
			'#230' => '&aelig;',
			'#231' => '&ccedil;',
			'#232' => '&egrave;',
			'#233' => '&eacute;',
			'#234' => '&ecirc;',
			'#235' => '&euml;',
			'#236' => '&igrave;',
			'#237' => '&iacute;',
			'#238' => '&icirc;',
			'#239' => '&iuml;',
			'#240' => '&eth;',
			'#241' => '&ntilde;',
			'#242' => '&ograve;',
			'#243' => '&oacute;',
			'#244' => '&ocirc;',
			'#245' => '&otilde;',
			'#246' => '&ouml;',
			'#247' => '&divide;',
			'#248' => '&oslash;',
			'#249' => '&ugrave;',
			'#250' => '&uacute;',
			'#251' => '&ucirc;',
			'#252' => '&uuml;',
			'#253' => '&yacute;',
			'#254' => '&thorn;',
			'#255' => '&yuml;',
			'#338' => '&OElig;',
			'#339' => '&oelig;',
			'#352' => '&Scaron;',
			'#353' => '&scaron;',
			'#376' => '&Yuml;',
			'#402' => '&fnof;',
			'#710' => '&circ;',
			'#732' => '&tilde;',
			'#913' => '&Alpha;',
			'#914' => '&Beta;',
			'#915' => '&Gamma;',
			'#916' => '&Delta;',
			'#917' => '&Epsilon;',
			'#918' => '&Zeta;',
			'#919' => '&Eta;',
			'#920' => '&Theta;',
			'#921' => '&Iota;',
			'#922' => '&Kappa;',
			'#923' => '&Lambda;',
			'#924' => '&Mu;',
			'#925' => '&Nu;',
			'#926' => '&Xi;',
			'#927' => '&Omicron;',
			'#928' => '&Pi;',
			'#929' => '&Rho;',
			'#931' => '&Sigma;',
			'#932' => '&Tau;',
			'#933' => '&Upsilon;',
			'#934' => '&Phi;',
			'#935' => '&Chi;',
			'#936' => '&Psi;',
			'#937' => '&Omega;',
			'#945' => '&alpha;',
			'#946' => '&beta;',
			'#947' => '&gamma;',
			'#948' => '&delta;',
			'#949' => '&epsilon;',
			'#950' => '&zeta;',
			'#951' => '&eta;',
			'#952' => '&theta;',
			'#953' => '&iota;',
			'#954' => '&kappa;',
			'#955' => '&lambda;',
			'#956' => '&mu;',
			'#957' => '&nu;',
			'#958' => '&xi;',
			'#959' => '&omicron;',
			'#960' => '&pi;',
			'#961' => '&rho;',
			'#962' => '&sigmaf;',
			'#963' => '&sigma;',
			'#964' => '&tau;',
			'#965' => '&upsilon;',
			'#966' => '&phi;',
			'#967' => '&chi;',
			'#968' => '&psi;',
			'#969' => '&omega;',
			'#977' => '&thetasym;',
			'#978' => '&upsih;',
			'#982' => '&piv;',
			'#8194' => '&ensp;',
			'#8195' => '&emsp;',
			'#8201' => '&thinsp;',
			'#8204' => '&zwnj;',
			'#8205' => '&zwj;',
			'#8206' => '&lrm;',
			'#8207' => '&rlm;',
			'#8211' => '&ndash;',
			'#8212' => '&mdash;',
			'#8216' => '&lsquo;',
			'#8217' => '&rsquo;',
			'#8218' => '&sbquo;',
			'#8220' => '&ldquo;',
			'#8221' => '&rdquo;',
			'#8222' => '&bdquo;',
			'#8224' => '&dagger;',
			'#8225' => '&Dagger;',
			'#8226' => '&bull;',
			'#8230' => '&hellip;',
			'#8240' => '&permil;',
			'#8242' => '&prime;',
			'#8243' => '&Prime;',
			'#8249' => '&lsaquo;',
			'#8250' => '&rsaquo;',
			'#8254' => '&oline;',
			'#8260' => '&frasl;',
			'#8364' => '&euro;',
			'#8465' => '&image;',
			'#8472' => '&weierp;',
			'#8476' => '&real;',
			'#8482' => '&trade;',
			'#8501' => '&alefsym;',
			'#8592' => '&larr;',
			'#8593' => '&uarr;',
			'#8594' => '&rarr;',
			'#8595' => '&darr;',
			'#8596' => '&harr;',
			'#8629' => '&crarr;',
			'#8656' => '&lArr;',
			'#8657' => '&uArr;',
			'#8658' => '&rArr;',
			'#8659' => '&dArr;',
			'#8660' => '&hArr;',
			'#8704' => '&forall;',
			'#8706' => '&part;',
			'#8707' => '&exist;',
			'#8709' => '&empty;',
			'#8711' => '&nabla;',
			'#8712' => '&isin;',
			'#8713' => '&notin;',
			'#8715' => '&ni;',
			'#8719' => '&prod;',
			'#8721' => '&sum;',
			'#8722' => '&minus;',
			'#8727' => '&lowast;',
			'#8730' => '&radic;',
			'#8733' => '&prop;',
			'#8734' => '&infin;',
			'#8736' => '&ang;',
			'#8743' => '&and;',
			'#8744' => '&or;',
			'#8745' => '&cap;',
			'#8746' => '&cup;',
			'#8747' => '&int;',
			'#8756' => '&there4;',
			'#8764' => '&sim;',
			'#8773' => '&cong;',
			'#8776' => '&asymp;',
			'#8800' => '&ne;',
			'#8801' => '&equiv;',
			'#8804' => '&le;',
			'#8805' => '&ge;',
			'#8834' => '&sub;',
			'#8835' => '&sup;',
			'#8836' => '&nsub;',
			'#8838' => '&sube;',
			'#8839' => '&supe;',
			'#8853' => '&oplus;',
			'#8855' => '&otimes;',
			'#8869' => '&perp;',
			'#8901' => '&sdot;',
			'#8968' => '&lceil;',
			'#8969' => '&rceil;',
			'#8970' => '&lfloor;',
			'#8971' => '&rfloor;',
			'#9001' => '&lang;',
			'#9002' => '&rang;',
			'#9674' => '&loz;',
			'#9824' => '&spades;',
			'#9827' => '&clubs;',
			'#9829' => '&hearts;',
			'#9830' => '&diams;',
		);
	}
	else
	{
		$replace = array(
			"quot" => "&#34;",
			"amp" => "&#38;",
			"apos" => "&#39;",
			"lt" => "&#60;",
			"gt" => "&#62;",
			"nbsp" => "&#160;",
			"iexcl" => "&#161;",
			"cent" => "&#162;",
			"pound" => "&#163;",
			"curren" => "&#164;",
			"yen" => "&#165;",
			"brvbar" => "&#166;",
			"sect" => "&#167;",
			"uml" => "&#168;",
			"copy" => "&#169;",
			"ordf" => "&#170;",
			"laquo" => "&#171;",
			"not" => "&#172;",
			"shy" => "&#173;",
			"reg" => "&#174;",
			"macr" => "&#175;",
			"deg" => "&#176;",
			"plusmn" => "&#177;",
			"sup2" => "&#178;",
			"sup3" => "&#179;",
			"acute" => "&#180;",
			"micro" => "&#181;",
			"para" => "&#182;",
			"middot" => "&#183;",
			"cedil" => "&#184;",
			"sup1" => "&#185;",
			"ordm" => "&#186;",
			"raquo" => "&#187;",
			"frac14" => "&#188;",
			"frac12" => "&#189;",
			"frac34" => "&#190;",
			"iquest" => "&#191;",
			"Agrave" => "&#192;",
			"Aacute" => "&#193;",
			"Acirc" => "&#194;",
			"Atilde" => "&#195;",
			"Auml" => "&#196;",
			"Aring" => "&#197;",
			"AElig" => "&#198;",
			"Ccedil" => "&#199;",
			"Egrave" => "&#200;",
			"Eacute" => "&#201;",
			"Ecirc" => "&#202;",
			"Euml" => "&#203;",
			"Igrave" => "&#204;",
			"Iacute" => "&#205;",
			"Icirc" => "&#206;",
			"Iuml" => "&#207;",
			"ETH" => "&#208;",
			"Ntilde" => "&#209;",
			"Ograve" => "&#210;",
			"Oacute" => "&#211;",
			"Ocirc" => "&#212;",
			"Otilde" => "&#213;",
			"Ouml" => "&#214;",
			"times" => "&#215;",
			"Oslash" => "&#216;",
			"Ugrave" => "&#217;",
			"Uacute" => "&#218;",
			"Ucirc" => "&#219;",
			"Uuml" => "&#220;",
			"Yacute" => "&#221;",
			"THORN" => "&#222;",
			"szlig" => "&#223;",
			"agrave" => "&#224;",
			"aacute" => "&#225;",
			"acirc" => "&#226;",
			"atilde" => "&#227;",
			"auml" => "&#228;",
			"aring" => "&#229;",
			"aelig" => "&#230;",
			"ccedil" => "&#231;",
			"egrave" => "&#232;",
			"eacute" => "&#233;",
			"ecirc" => "&#234;",
			"euml" => "&#235;",
			"igrave" => "&#236;",
			"iacute" => "&#237;",
			"icirc" => "&#238;",
			"iuml" => "&#239;",
			"eth" => "&#240;",
			"ntilde" => "&#241;",
			"ograve" => "&#242;",
			"oacute" => "&#243;",
			"ocirc" => "&#244;",
			"otilde" => "&#245;",
			"ouml" => "&#246;",
			"divide" => "&#247;",
			"oslash" => "&#248;",
			"ugrave" => "&#249;",
			"uacute" => "&#250;",
			"ucirc" => "&#251;",
			"uuml" => "&#252;",
			"yacute" => "&#253;",
			"thorn" => "&#254;",
			"yuml" => "&#255;",
			"OElig" => "&#338;",
			"oelig" => "&#339;",
			"Scaron" => "&#352;",
			"scaron" => "&#353;",
			"Yuml" => "&#376;",
			"fnof" => "&#402;",
			"circ" => "&#710;",
			"tilde" => "&#732;",
			"Alpha" => "&#913;",
			"Beta" => "&#914;",
			"Gamma" => "&#915;",
			"Delta" => "&#916;",
			"Epsilon" => "&#917;",
			"Zeta" => "&#918;",
			"Eta" => "&#919;",
			"Theta" => "&#920;",
			"Iota" => "&#921;",
			"Kappa" => "&#922;",
			"Lambda" => "&#923;",
			"Mu" => "&#924;",
			"Nu" => "&#925;",
			"Xi" => "&#926;",
			"Omicron" => "&#927;",
			"Pi" => "&#928;",
			"Rho" => "&#929;",
			"Sigma" => "&#931;",
			"Tau" => "&#932;",
			"Upsilon" => "&#933;",
			"Phi" => "&#934;",
			"Chi" => "&#935;",
			"Psi" => "&#936;",
			"Omega" => "&#937;",
			"alpha" => "&#945;",
			"beta" => "&#946;",
			"gamma" => "&#947;",
			"delta" => "&#948;",
			"epsilon" => "&#949;",
			"zeta" => "&#950;",
			"eta" => "&#951;",
			"theta" => "&#952;",
			"iota" => "&#953;",
			"kappa" => "&#954;",
			"lambda" => "&#955;",
			"mu" => "&#956;",
			"nu" => "&#957;",
			"xi" => "&#958;",
			"omicron" => "&#959;",
			"pi" => "&#960;",
			"rho" => "&#961;",
			"sigmaf" => "&#962;",
			"sigma" => "&#963;",
			"tau" => "&#964;",
			"upsilon" => "&#965;",
			"phi" => "&#966;",
			"chi" => "&#967;",
			"psi" => "&#968;",
			"omega" => "&#969;",
			"thetasym" => "&#977;",
			"upsih" => "&#978;",
			"piv" => "&#982;",
			"ensp" => "&#8194;",
			"emsp" => "&#8195;",
			"thinsp" => "&#8201;",
			"zwnj" => "&#8204;",
			"zwj" => "&#8205;",
			"lrm" => "&#8206;",
			"rlm" => "&#8207;",
			"ndash" => "&#8211;",
			"mdash" => "&#8212;",
			"lsquo" => "&#8216;",
			"rsquo" => "&#8217;",
			"sbquo" => "&#8218;",
			"ldquo" => "&#8220;",
			"rdquo" => "&#8221;",
			"bdquo" => "&#8222;",
			"dagger" => "&#8224;",
			"Dagger" => "&#8225;",
			"bull" => "&#8226;",
			"hellip" => "&#8230;",
			"permil" => "&#8240;",
			"prime" => "&#8242;",
			"Prime" => "&#8243;",
			"lsaquo" => "&#8249;",
			"rsaquo" => "&#8250;",
			"oline" => "&#8254;",
			"frasl" => "&#8260;",
			"euro" => "&#8364;",
			"image" => "&#8465;",
			"weierp" => "&#8472;",
			"real" => "&#8476;",
			"trade" => "&#8482;",
			"alefsym" => "&#8501;",
			"larr" => "&#8592;",
			"uarr" => "&#8593;",
			"rarr" => "&#8594;",
			"darr" => "&#8595;",
			"harr" => "&#8596;",
			"crarr" => "&#8629;",
			"lArr" => "&#8656;",
			"uArr" => "&#8657;",
			"rArr" => "&#8658;",
			"dArr" => "&#8659;",
			"hArr" => "&#8660;",
			"forall" => "&#8704;",
			"part" => "&#8706;",
			"exist" => "&#8707;",
			"empty" => "&#8709;",
			"nabla" => "&#8711;",
			"isin" => "&#8712;",
			"notin" => "&#8713;",
			"ni" => "&#8715;",
			"prod" => "&#8719;",
			"sum" => "&#8721;",
			"minus" => "&#8722;",
			"lowast" => "&#8727;",
			"radic" => "&#8730;",
			"prop" => "&#8733;",
			"infin" => "&#8734;",
			"ang" => "&#8736;",
			"and" => "&#8743;",
			"or" => "&#8744;",
			"cap" => "&#8745;",
			"cup" => "&#8746;",
			"int" => "&#8747;",
			"there4" => "&#8756;",
			"sim" => "&#8764;",
			"cong" => "&#8773;",
			"asymp" => "&#8776;",
			"ne" => "&#8800;",
			"equiv" => "&#8801;",
			"le" => "&#8804;",
			"ge" => "&#8805;",
			"sub" => "&#8834;",
			"sup" => "&#8835;",
			"nsub" => "&#8836;",
			"sube" => "&#8838;",
			"supe" => "&#8839;",
			"oplus" => "&#8853;",
			"otimes" => "&#8855;",
			"perp" => "&#8869;",
			"sdot" => "&#8901;",
			"lceil" => "&#8968;",
			"rceil" => "&#8969;",
			"lfloor" => "&#8970;",
			"rfloor" => "&#8971;",
			"lang" => "&#9001;",
			"rang" => "&#9002;",
			"loz" => "&#9674;",
			"spades" => "&#9824;",
			"clubs" => "&#9827;",
			"hearts" => "&#9829;",
			"diams" => "&#9830;"
		);
	}
	$ret = preg_replace_callback("/&(\w+);/",
			function ($matches) use ($replace) {
				if (isset($replace[$matches[1]]))
					return $replace[$matches[1]];
				return strtolower($matches[0]);
			}, $str
		);
	return $ret;
}


/** récupère l'ID du parent d'une entité en fonction de son type
* @author Bruno Cénou
* @param int $id 
* @param string $type 
*/

function getParentByType($id,$type, $return=false){
	global $db;
	$id = (int)$id;
        $q = "SELECT idparent FROM {$GLOBALS['tp']}entities WHERE id = '$id'";
	$idparent = $db->GetOne($q);
        if($idparent){
                $q = "SELECT t.type FROM {$GLOBALS['tp']}entities e, {$GLOBALS['tp']}types t WHERE e.id = '$idparent'
                AND e.idtype = t.id";
		$ltype = $db->GetOne($q);
                if($ltype == $type){
			if($return) return $idparent;
                        else echo $idparent;
                }else{
			if($return)
                        	return getParentByType($idparent, $type, $return);
			else
				getParentByType($idparent, $type);
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
	if($codeInclude) {
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
	if(preg_match_all("`<a href=\"mailto:([^\"]*)\">([^>]*)</a>`", $texte, $matches))
	{
		foreach($matches[0] as $k=>$mail) {
			$name = explode("@", $matches[1][$k]);
			$extension = substr(strrchr($name[1], '.'), 1);
			$domain = substr($name[1], 0, strrpos($name[1], '.'));
	
			// email dans le contenu du lien ?
			if(array(0=>$matches[2][$k]) != ($content = explode("@", $matches[2][$k]))) { 
				/* 
				on met des span cachés dans le contenu du lien pour éviter que les robots puissent récupèrer le mail
				résultat dans le code source de la page avec test@domaine.com : 
				test<span style="display: none;">ANTIBOT</span>@<span style="display: none;">ANTIBOT</span>domaine<span style="display: none;">ANTIBOT</span>.com
				*/
				$domainContent = substr($content[1], 0, strrpos($content[1], '.'));
				$newContent = $content[0]."<span style=\"display: none;\">ANTIBOT</span>@<span style=\"display: none;\">ANTIBOT</span>". $domainContent ."<span style=\"display: none;\">ANTIBOT</span>.". $extension;
			}
	
			// création du lien crypté : la balise href ne contient rien, le onclick déclenche l'appel à la fonction JS
			$newLink = "<a href=\"javascript:;\" onclick=\"javascript:recomposeMail(this, '".$extension."', '".$name[0]."', '".$domain."');\">";
			$newLink .= empty($newContent) ? $matches[2][$k] : $newContent;
			$newLink .= "</a>";
	
			// on remplace
			$texte = str_replace($mail, $newLink, $texte);
		}
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
	class_exists('GeSHi', TRUE) || require_once 'vendor/autoload.php';
	$geshi = new GeSHi($text, $language);
	if($lineNumbers)
		$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
	$geshi->set_header_type(GESHI_HEADER_DIV);
	return $geshi->parse_code();
}

/**
 * implémentation de la fonction explode
 * utile pour les tableaux
 *
 * @author Pierre-Alain Mignot
 * @param string $text le texte à découper
 * @param string $delimiter le délimiteur
 * @return array
 */
function lexplode($text, $delimiter) 
{
	return explode($delimiter, $text);
}

/**
 * fonction permettant de faire des opérations mathématiques simples
 *
 * @author Pierre-Alain Mignot
 * @param mixed $text
 * @param string $operator (+,-,*,/)
 * @param mixed $var
 */
function lmath($text, $operator, $var) 
{
	switch($operator) 
	{
		case '+': $text += $var; break;
		case '-': $text -= $var; break;
		case '/': $text /= $var; break;
		case '*': $text *= $var; break;
		default:break;
	}
	return $text;
}

/**
 * implémente empty()
 *
 * @author Pierre-Alain Mignot
 * @param mixed $var la variable à tester
 */
function lempty($var)
{
    return empty($var);
}

/**
 * implémente isset()
 *
 * cette fonction est utilisée en interne par le parser
 * pour initialiser une variable si vide, et éviter les E_NOTICE
 * ! à ne pas modifier !
 *
 * @author Pierre-Alain Mignot
 * @param mixed $var la variable à tester
 */
function &lisset(&$var = null)
{
    return $var;
}

/**
 * implémente in_array()
 *
 * @author Pierre-Alain Mignot
 * @param array $arr la variable sur laquelle tester
 * @param mixed $search la valeur à rechercher
 */
function lin_array($arr, $search)
{
	return in_array($search, (array)$arr);
}

define('INC_TEXTFUNC', 1);

/**
 * Permet de savoir si une revue a un accès restreint ou pas.
 * 
 * @return boolean 
 * Enter description here ...
 * @return boolean
 */
function is_restricted(){
	return cache_get('no_restricted') ? false : true ;
}

/**
 * Retourne la valeur héxadécimale d'une chaîne de caractère UTF-8
 *
 * @param string $data la chaine à transformer
 * @return string
 */
function hex_chars($data) {
	$mb_hex = '';
	for ($i=0; $i<mb_strlen($data, 'UTF-8'); $i++) {
		$c = mb_substr($data, $i, 1, 'UTF-8');
		$o = unpack('N', mb_convert_encoding($c, 'UCS-4BE', 'UTF-8'));
		$mb_hex .= hex_format($o[1]);
	}
	return $mb_hex;
}

/**
 * Retourne la valeur hexadécimale d'un caractère
 * 
 * @param string $o caractère
 * @return string
 */
function hex_format($o) {
	$h = strtoupper(dechex($o));
	$len = strlen($h);
	if ($len % 2 == 1)
		$h = "0$h";
	return $h;
}

/**
 * Split une chaine UTF-8 en un tableau
 * 
 * @param string $str la chaine de caractère à splitter
 * @param int $l longueur du tableau retourné
 * @return array
 */
function str_split_unicode($str, $l = 0) {
	if ($l > 0) {
		$ret = array();
		$len = mb_strlen($str, "UTF-8");
		for ($i = 0; $i < $len; $i += $l) {
			$ret[] = mb_substr($str, $i, $l, "UTF-8");
		}
		return $ret;
	}
	return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
}

/**
 * Lodelisation de la fonction preg_split
 * 
 * @param string $str 
 * @param string $pattern
 * @param int $limit 
 * @param number $flags
 * @return multitype:
 */
function lreg_split($str, $pattern, $limit = -1, $flags = 0)
{
	return preg_split($pattern, $str, $limit, $flags);
}

/**
	* * Tri d'un tableau de tableau
	* *
	* * @param array $arr
	* * @return $sorted_arr
	* */
function msort($arr, $key, $sort_order = SORT_LOCALE_STRING) {
	        $values = array_column($arr, $key);
		        array_multisort($values, $sort_order, $arr);
		        return $arr;
}








