<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier proposant des fonctions pour valider le XML Lodel
 */

/**
 * Vérifie le contenu d'un fichier.
 *
 * Cette méthode récupère le contenu d'un fichier et appelle la méthode checkstring
 *
 * @param string $filename le nom du fichier
 * @param integer $error (n'est pas utilisé dans la fonction) par défaut à 0
 * @return un booleen indiquant si le XML est valide ou non.
 */
function checkfile($filename, $error = 0)
{
	$text = file($filename);
	return checkstring($text);
}

/**
 * Vérifie le XML d'une chaine de caractère
 *
 * @param string &$text la chaine de caractère
 * @param integer $error (n'est pas utilisé dans la fonction) par défaut à 0
 * @return un booleen indiquant si le XML est valide ou non.
 */
function checkstring(&$text, $error = 0)
{
	$xml_parser = xml_parser_create();
	xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0) or trigger_error("Parser incorrect", E_USER_ERROR);
	if ($error)	{
		xml_set_element_handler($xml_parser, "startElementCHECK", "endElementCHECK");
		xml_set_character_data_handler($xml_parser, "characterHandlerCHECK");
	}

	if (!xml_parse($xml_parser, $text))	{
		if (!$error) {
			echo '<h1>ERROR</h1><p>Le fichier produit n\'est pas XML. Veuillez svp poster un rapport de bug sur <a href="http://sourceforge.net/projects/lodel/">http://sourceforge.net/projects/lodel<a/>. Pensez &agrave; joindre le fichier.<br />En attendant que le problème soit résolu, essayez de changer le stylage de votre fichier.</p><p><hr /></p>';

			include C::get('home', 'cfg')."xmlfunc.php";
			$text = indentXML($text);
			checkstring($text, 1);
			return;
		} else {
			echo "<font color=red>";
			//echo preg_replace("/\n/se", "'<br /><b>'.((\$GLOBALS['line']++)+2).'</b> '", htmlspecialchars(substr($text, xml_get_current_byte_index($xml_parser) - 2)));
			echo preg_replace_callback("/\n/s", function ($str) { return '<br /><b>'.(($GLOBALS['line']++)+2).'</b> '; } , htmlspecialchars(substr($text, xml_get_current_byte_index($xml_parser) - 2)));
			echo "</font>\n";
			echo sprintf("<br /><H2>XML error: %s ligne %d</H2>", xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser));
			echo "L'erreur se situe avant la zone rouge. Elle peut etre due a une erreur bien au dessus la ligne donne par le parser<br />";
			echo "<br />".htmlentities($text);

			xml_parser_free($xml_parser);
			return FALSE;
		}
	}
	xml_parser_free($xml_parser);
	return TRUE;
}

/**
 *
 *
 *
 */
function characterHandlerCHECK($parser, $data)
{
	//echo preg_replace("/\n/se", "'<br /><b>'.((\$GLOBALS[line]++)+2).'</b> '", $data);
    echo preg_replace_callback("/\n/s", function ($str) { return '<br /><b>'.(($GLOBALS[line]++)+2).'</b> '; }, $data);
}


/**
 *
 *
 *
 */
function startElementCHECK($parser, $name, $attrs)
{
	$balise = "<$name";
	foreach ($attrs as $att => $val) {
		$balise .= " $att=\"$val\"";
	}
	$balise .= ">";

	echo "<font color=blue>". htmlentities($balise). "</font>";
}

/**
 *
 *
 *
 */
function endElementCHECK($parser, $name)
{
	echo "<font color=blue>&lt;/$name&gt;</font>";
}