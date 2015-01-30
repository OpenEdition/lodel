<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier contenant la liste des langues Lodel
 */

// liste des langues lodel
$GLOBALS['langues'] = array ("fr" => "fran\303\247ais", "en" => "anglais", "es" => "espagnol", 
														"de" => "allemand", "it" => "italien", "ru" => "russe", "el" => "grec");

// ne rien modifier ici
/**
 * Construction du SELECT des langues
 *
 * @param string $lang la langue sélectionnée (par défaut vide)
 */
function makeselectlangs($lang = "")
{
	global $langues;

	echo "<option value=\"\">--</option>\n";
	foreach ($langues as $l => $lang)	{
		$selected = $lang == $l ? " selected=\"selected\"" : '';
		echo "<option value=\"$l\"$selected>$lang</option>\n";
	}
}
/**
 * Construction du SELECT des langues (jamais null)
 *
 * @param string $lang la langue sélectionnée (par défaut vide)
 */
function makeselectlangs_nevernil($lang = "")
{
	global $langues;

	foreach ($langues as $l => $lang)	{
		$selected = $lang == $l ? " selected=\"selected\"" : '';
		echo "<option value=\"$l\"$selected>$lang</option>\n";
	}
}