<?php
/**
 * Fichier utilitaire de gestion des balises XHTML
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


// $balises contient toutes les balises reconnues.
// la valeur de la balise definie l'affichage dans chkbalisage.php (et n'a aucune incidence ailleurs).
// les balises principales doivent etre associe a leur name litteral
// les ss balises doivent etre associees a une/ou des balises html ou etre vide.

/*$GLOBALS['balises'] = array (
		"-" => "-",
		"citation" => "<blockquote>",
		"epigraphe" => "<div class=\"balisesinternes\">",
		"typedoc" => "Type de document",
		"finbalise" => "fin",
		"section1" => "<h1>",
		"section2" => "<h2>",
		"section3" => "<h3>",
		"section4" => "<h4>",
		"section5" => "<h5>",
		"section6" => "<h6>",
		"titredoc" => "<i>",
		"legendedoc" => "<i>",
		"titreillustration" => "<i>",
		"legendeillustration" => "<i>",
		"langues" => "Langues",
		# champs auteurs
		"description" => "Description de l'auteur prcdent",
		#
		# balises pour l'import de sommaire
		"regroupement" => "Regroupement",
		"titrenumero" => "Titre de la publication",
		"nomnumero" => "Nom de la publication", 
		"typenumero" => "Type de la publication");*/

// transparent style . Useful for the PDF export
$GLOBALS['stylestransparents'] = "paragraphetransparent|caracteretransparent";

$GLOBALS['balisesdocumentassocie'] = array ("objetdelarecension" => "Objet de la recension", "traduction" => "de la traduction");


// Groups of xhtml tags
// temporaire en attendant la 0.8

$GLOBALS['xhtmlgroups']['xhtml:fontstyle'] = array ("tt", "i", "b", "big", "small");

$GLOBALS['xhtmlgroups']['xhtml:phrase'] = array ("em", "strong", "dfn", "code", "q", "samp", "kbd", "var", "cite", "abbr", "acronym", "sub", "sup");

$GLOBALS['xhtmlgroups']['xhtml:special'] = array ("span", "img", "object", "br", "bdo", "map", "embed");

	$GLOBALS['xhtmlgroups']['xhtml:block'] = array (
		"p", "h1", "h2", "h3", "h4", "h5", "h6", # heading
		"div", "ul", "ol", "dl", "li", "dt", "dd", # list
		"pre", "hr", "blockquote", "address", # blocktext
		"fieldset", "table", "tr", "td", "th", "thead", "tfoot", "tbody", 
		"col", "colgroup", "caption");

$GLOBALS['xhtmlgroups']['Lien'] = array ("a");

$GLOBALS['xhtmlgroups']['Appel de Note'] = array ("a" => "class=\"(foot|end)notecall\"");
?>
