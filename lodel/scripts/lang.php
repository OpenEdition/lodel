<?php
/**
 * Fichier contenant la liste des langues (abrégées)
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
 * Copyright (c) 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * Copyright (c) 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
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
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 * @since Fichier ajouté dans la version 0.8
 */
//contient la liste des langues disponibles pour installer lodel
$GLOBALS['installlanguages'] = array(
#			    "AA"=> "Afar",
#			    "AB"=> "Abkhazian",
#			    "AF"=> "Afrikaans",
#			    "AM"=> "Amharic",
#			    "AR"=> "Arabic",
#			    "AS"=> "Assamese",
#			    "AY"=> "Aymara",
#			    "AZ"=> "Azerbaijani",
#			    "BA"=> "Bashkir",
#			    "BE"=> "Byelorussian",
#			    "BG"=> "Bulgarian",
#			    "BH"=> "Bihari",
#			    "BI"=> "Bislama",
#			    "BN"=> "Bengali" "Bangla",
#			    "BO"=> "Tibetan",
#			    "BR"=> "Breton",
#			    "CA"=> "Catalan",
#			    "CO"=> "Corsican",
#			    "CS"=> "Czech",
#			    "CY"=> "Welsh",
#			    "DA"=> "Danish",
#			    "DE"=> "German",
#			    "DZ"=> "Bhutani",
#			    "EL"=> "Greek",
			    "EN"=> "English",
#			    "EO"=> "Esperanto",
			    "ES"=> "Spanish",
#			    "ET"=> "Estonian",
#			    "EU"=> "Basque",
#			    "FA"=> "Persian",
#			    "FI"=> "Finnish",
#			    "FJ"=> "Fiji",
#			    "FO"=> "Faeroese",
			    "FR"=> "French",
#			    "FY"=> "Frisian",
#			    "GA"=> "Irish",
#			    "GD"=> "Gaelic" "Scots Gaelic",
#			    "GL"=> "Galician",
#			    "GN"=> "Guarani",
#			    "GU"=> "Gujarati",
#			    "HA"=> "Hausa",
#			    "HI"=> "Hindi",
#			    "HR"=> "Croatian",
#			    "HU"=> "Hungarian",
#			    "HY"=> "Armenian",
#			    "IA"=> "Interlingua",
#			    "IE"=> "Interlingue",
#			    "IK"=> "Inupiak",
#			    "IN"=> "Indonesian",
#			    "IS"=> "Icelandic",
#			    "IT"=> "Italian",
#			    "IW"=> "Hebrew",
#			    "JA"=> "Japanese",
#			    "JI"=> "Yiddish",
#			    "JW"=> "Javanese",
#			    "KA"=> "Georgian",
#			    "KK"=> "Kazakh",
#			    "KL"=> "Greenlandic",
#			    "KM"=> "Cambodian",
#			    "KN"=> "Kannada",
#			    "KO"=> "Korean",
#			    "KS"=> "Kashmiri",
#			    "KU"=> "Kurdish",
#			    "KY"=> "Kirghiz",
#			    "LA"=> "Latin",
#			    "LN"=> "Lingala",
#			    "LO"=> "Laothian",
#			    "LT"=> "Lithuanian",
#			    "LV"=> "Latvian",
#			    "MG"=> "Malagasy",
#			    "MI"=> "Maori",
#			    "MK"=> "Macedonian",
#			    "ML"=> "Malayalam",
#			    "MN"=> "Mongolian",
#			    "MO"=> "Moldavian",
#			    "MR"=> "Marathi",
#			    "MS"=> "Malay",
#			    "MT"=> "Maltese",
#			    "MY"=> "Burmese",
#			    "NA"=> "Nauru",
#			    "NE"=> "Nepali",
			    "NL"=> "Dutch",
#			    "NO"=> "Norwegian",
#			    "OC"=> "Occitan",
#			    "OM"=> "Oromo",
#			    "OR"=> "Oriya",
#			    "PA"=> "Punjabi",
			    "PL"=> "Polish",
#			    "PS"=> "Pashto",
#			    "PT"=> "Portuguese",
#			    "QU"=> "Quechua",
#			    "RM"=> "Rhaeto-Romance",
#			    "RN"=> "Kirundi",
#			    "RO"=> "Romanian",
#			    "RU"=> "Russian",
#			    "RW"=> "Kinyarwanda",
#			    "SA"=> "Sanskrit",
#			    "SD"=> "Sindhi",
#			    "SG"=> "Sangro",
#			    "SH"=> "Serbo-Croatian",
#			    "SI"=> "Singhalese",
#			    "SK"=> "Slovak",
#			    "SL"=> "Slovenian",
#			    "SM"=> "Samoan",
#			    "SN"=> "Shona",
#			    "SO"=> "Somali",
#			    "SQ"=> "Albanian",
#			    "SR"=> "Serbian",
#			    "SS"=> "Siswati",
#			    "ST"=> "Sesotho",
#			    "SU"=> "Sudanese",
#			    "SV"=> "Swedish",
#			    "SW"=> "Swahili",
#			    "TA"=> "Tamil",
#			    "TE"=> "Tegulu",
#			    "TG"=> "Tajik",
#			    "TH"=> "Thai",
#			    "TI"=> "Tigrinya",
#			    "TK"=> "Turkmen",
#			    "TL"=> "Tagalog",
#			    "TN"=> "Setswana",
#			    "TO"=> "Tonga",
#			    "TR"=> "Turkish",
#			    "TS"=> "Tsonga",
#			    "TT"=> "Tatar",
#			    "TW"=> "Twi",
#			    "UK"=> "Ukrainian",
#			    "UR"=> "Urdu",
#			    "UZ"=> "Uzbek",
#			    "VI"=> "Vietnamese",
#			    "VO"=> "Volapuk",
#			    "WO"=> "Wolof",
#			    "XH"=> "Xhosa",
#			    "YO"=> "Yoruba",
#			    "ZH"=> "Chinese",
#			    "ZU"=> "Zulu"
);
$GLOBALS['languages']=array(
#			    "AA"=> "Afar",
#			    "AB"=> "Abkhazian",
#			    "AF"=> "Afrikaans",
#			    "AM"=> "Amharic",
			    "AR"=> "&#x202B;&#x627;&#x644;&#x639;&#x631;&#x628;&#x64A;&#x629;",
#			    "AS"=> "Assamese",
#			    "AY"=> "Aymara",
#			    "AZ"=> "Azerbaijani",
#			    "BA"=> "Bashkir",
#			    "BE"=> "Byelorussian",
#			    "BG"=> "Bulgarian",
#			    "BH"=> "Bihari",
#			    "BI"=> "Bislama",
#			    "BN"=> "Bengali" "Bangla",
#			    "BO"=> "Tibetan",
#			    "BR"=> "Breton",
#			    "CA"=> "Catalan",
#			    "CO"=> "Corsican",
#			    "CS"=> "Czech",
#			    "CY"=> "Welsh",
#			    "DA"=> "Danish",
			    "DE"=> "Deutsch",
#			    "DZ"=> "Bhutani",
			    "EL"=> "Ελληνικά",
			    "EN"=> "English",
#			    "EO"=> "Esperanto",
			    "ES"=> "Español",
#			    "ET"=> "Estonian",
			    "EU"=> "Euskara",
			    "FA"=> "Persian",
#			    "FI"=> "Finnish",
#			    "FJ"=> "Fiji",
#			    "FO"=> "Faeroese",
			    "FR"=> "Français",
#			    "FY"=> "Frisian",
#			    "GA"=> "Irish",
#			    "GD"=> "Gaelic" "Scots Gaelic",
#			    "GL"=> "Galician",
#			    "GN"=> "Guarani",
#			    "GU"=> "Gujarati",
#			    "HA"=> "Hausa",
#			    "HI"=> "Hindi",
#			    "HR"=> "Croatian",
			    "HU"=> "Magyar",
#			    "HY"=> "Armenian",
#			    "IA"=> "Interlingua",
#			    "IE"=> "Interlingue",
#			    "IK"=> "Inupiak",
#			    "IN"=> "Indonesian",
#			    "IS"=> "Icelandic",
			    "IT"=> "Italiano",
#			    "IW"=> "Hebrew",
#			    "JA"=> "Japanese",
#			    "JI"=> "Yiddish",
#			    "JW"=> "Javanese",
#			    "KA"=> "Georgian",
#			    "KK"=> "Kazakh",
#			    "KL"=> "Greenlandic",
#			    "KM"=> "Cambodian",
#			    "KN"=> "Kannada",
#			    "KO"=> "Korean",
#			    "KS"=> "Kashmiri",
#			    "KU"=> "Kurdish",
#			    "KY"=> "Kirghiz",
			    "LA"=> "Latine",
#			    "LN"=> "Lingala",
#			    "LO"=> "Laothian",
#			    "LT"=> "Lithuanian",
#			    "LV"=> "Latvian",
#			    "MG"=> "Malagasy",
#			    "MI"=> "Maori",
#			    "MK"=> "Macedonian",
#			    "ML"=> "Malayalam",
#			    "MN"=> "Mongolian",
#			    "MO"=> "Moldavian",
#			    "MR"=> "Marathi",
#			    "MS"=> "Malay",
#			    "MT"=> "Maltese",
#			    "MY"=> "Burmese",
#			    "NA"=> "Nauru",
#			    "NE"=> "Nepali",
#			    "NL"=> "Dutch",
#			    "NO"=> "Norwegian",
#			    "OC"=> "Occitan",
#			    "OM"=> "Oromo",
#			    "OR"=> "Oriya",
#			    "PA"=> "Punjabi",
#			    "PL"=> "Polish",
#			    "PS"=> "Pashto",
			    "PT"=> "Português",
#			    "QU"=> "Quechua",
#			    "RM"=> "Rhaeto-Romance",
#			    "RN"=> "Kirundi",
#			    "RO"=> "Romanian",
			    "RU"=> "русский язык",
#			    "RW"=> "Kinyarwanda",
#			    "SA"=> "Sanskrit",
#			    "SD"=> "Sindhi",
#			    "SG"=> "Sangro",
#			    "SH"=> "Serbo-Croatian",
#			    "SI"=> "Singhalese",
#			    "SK"=> "Slovak",
#			    "SL"=> "Slovenian",
#			    "SM"=> "Samoan",
#			    "SN"=> "Shona",
#			    "SO"=> "Somali",
#			    "SQ"=> "Albanian",
#			    "SR"=> "Serbian",
#			    "SS"=> "Siswati",
#			    "ST"=> "Sesotho",
#			    "SU"=> "Sudanese",
#			    "SV"=> "Swedish",
#			    "SW"=> "Swahili",
#			    "TA"=> "Tamil",
#			    "TE"=> "Tegulu",
#			    "TG"=> "Tajik",
#			    "TH"=> "Thai",
#			    "TI"=> "Tigrinya",
#			    "TK"=> "Turkmen",
#			    "TL"=> "Tagalog",
#			    "TN"=> "Setswana",
#			    "TO"=> "Tonga",
			    "TR"=> "Türkçe",
#			    "TS"=> "Tsonga",
#			    "TT"=> "Tatar",
#			    "TW"=> "Twi",
#			    "UK"=> "Ukrainian",
#			    "UR"=> "Urdu",
#			    "UZ"=> "Uzbek",
#			    "VI"=> "Vietnamese",
#			    "VO"=> "Volapuk",
#			    "WO"=> "Wolof",
#			    "XH"=> "Xhosa",
#			    "YO"=> "Yoruba",
#			    "ZH"=> "Chinese",
#			    "ZU"=> "Zulu"
);



/**
 * Construction du SELECT des langues
 *
 * @param string $selectedlang la langue sélectionnée (par défaut vide)
 */
function makeselectlangs($selectedlang = "") 
{
	global $languages;
  
	echo "<option value=\"\">--</option>\n";
	foreach ($languages as $l=>$lang) {
		$l = strtolower($l);
		$selected = $selectedlang == $l ? " selected=\"selected\"" : "";
		echo "<option value=\"$l\"$selected>$lang</option>\n";
	}
}

/**
 * Construction du SELECT des langues (jamais vide)
 *
 * @param string $selectedlang la langue sélectionnée (par défaut vide)
 */
function makeselectlangs_nevernil($selectedlang = "") 
{
	global $languages;

	foreach ($languages as $l=>$lang) {
		$l = strtolower($l);
		$selected = $selectedlang == $l ? " selected=\"selected\"" : "";
		echo "<option value=\"$l\"$selected>$lang</option>\n";
	}
}

/**
 * Affichage choix langue
 *
 * Cette fonction affiche une liste déroulante permettant de choisir une langue (utilisée par entrytypes)
 *
 */	
function makeSelectLang()
{
	global $languages, $context;
	echo "<option value=\"\">--</option>\n";
	foreach ($languages as $l=>$lang) {
		$l = strtolower($l);
		$selected = $context['sitelang'] == $l ? " selected=\"selected\"" : "";
		echo "<option value=\"$l\"$selected>$lang</option>\n";
	}
}
?>
