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
// Tiré de http://fr.wikipedia.org/wiki/Liste_des_codes_ISO_639-1
$GLOBALS['installlanguages'] = array(
#			    "AA"=> "Afaraf",
#			    "AB"=> "Аҧсуа",
#			    "AF"=> "Afrikaans",
#			    "AM"=> "አማርኛ",
#			    "AR"=> "العربية",
#			    "AS"=> "অসমীয়া",
#			    "AY"=> "Aymar aru",
#			    "AZ"=> "Azərbaycan dili",
#			    "BA"=> "башҡорт теле",
#			    "BE"=> "Беларуская",
#			    "BG"=> "български език",
#			    "BH"=> "भोजपुरी",
#			    "BI"=> "Bislama",
#			    "BN"=> "বাংলা",
#			    "BO"=> "བོད་ཡིག",
#			    "BR"=> "Brezhoneg",
#			    "CA"=> "Català",
#			    "CO"=> "Corsu",
#			    "CS"=> "Česky",
#			    "CY"=> "Cymraeg",
#			    "DA"=> "Dansk",
#			    "DE"=> "Deutsch",
#			    "DZ"=> "རྫོང་ཁ",
#			    "EL"=> "Ελληνικά",
			    "EN"=> "English",
#			    "EO"=> "Esperanto",
			    "ES"=> "Español",
#			    "ET"=> "Eesti keel",
#			    "EU"=> "Euskara",
#			    "FA"=> "‫فارسی",
#			    "FI"=> "Suomen kieli",
#			    "FJ"=> "Vosa Vakaviti",
#			    "FO"=> "Føroyskt",
			    "FR"=> "Français",
#			    "FY"=> "Frysk",
#			    "GA"=> "Gaeilge",
#			    "GD"=> "Gàidhlig",
#			    "GL"=> "Galego",
#			    "GN"=> "Avañe'ẽ",
#			    "GU"=> "ગુજરાતી",
#			    "HA"=> "‫هَوُسَ",
#			    "HI"=> "हिन्दी",
#			    "HR"=> "Hrvatski",
#			    "HU"=> "Magyar",
#			    "HY"=> "Հայերեն",
#			    "IA"=> "Interlingua",
#			    "IE"=> "Interlingue",
#			    "IK"=> "Iñupiaq",
#			    "ID"=> "Bahasa Indonesia",
#			    "IS"=> "Íslenska",
#			    "IT"=> "Italiano",
#			    "HE"=> "‫עברית",
#			    "JA"=> "日本語",
#			    "JV"=> "Basa Jawa",
#			    "KA"=> "ქართული",
#			    "KK"=> "Қазақ тілі",
#			    "KL"=> "Kalaallisut",
#			    "KM"=> "ភាសាខ្មែរ",
#			    "KN"=> "ಕನ್ನಡ",
#			    "KO"=> "한국어",
#			    "KS"=> "कश्मीरी",
#			    "KU"=> "Kurdî",
#			    "KY"=> "кыргыз тили",
#			    "LA"=> "Latine",
#			    "LN"=> "Lingala",
#			    "LO"=> "ພາສາລາວ",
#			    "LT"=> "Lietuvių kalba",
#			    "LV"=> "Latviešu valoda",
#			    "MG"=> "Fiteny malagasy",
#			    "MI"=> "Te reo Māori",
#			    "MK"=> "македонски",
#			    "ML"=> "മലയാളം",
#			    "MN"=> "Монгол",
#			    "MO"=> "лимба молдовеняскэ",
#			    "MR"=> "मराठी",
#			    "MS"=> "Bahasa Melayu",
#			    "MT"=> "Malti",
#			    "MY"=> "ဗမာစာ",
#			    "NA"=> "Ekakairũ Naoero",
#			    "NE"=> "नेपाली",
			    "NL"=> "Nederlands",
#			    "NO"=> "Norsk",
#			    "OC"=> "Occitan",
#			    "OM"=> "Afaan Oromoo",
#			    "OR"=> "ଓଡ଼ିଆ",
#			    "PA"=> "ਪੰਜਾਬੀ",
			    "PL"=> "Polski",
#			    "PS"=> "‫پښتو",
#			    "PT"=> "Português",
#			    "QU"=> "Kichwa",
#			    "RM"=> "Rumantsch grischun",
#			    "RN"=> "kiRundi",
#			    "RO"=> "Română",
#			    "RU"=> "русский язык",
#			    "RW"=> "Kinyarwanda",
#			    "SA"=> "संस्कृतम्",
#			    "SD"=> "सिन्धी",
#			    "SG"=> "Yângâ tî sängö",
#			    "SI"=> "සිංහල",
#			    "SK"=> "Slovenčina",
#			    "SL"=> "Slovenščina",
#			    "SM"=> "Gagana fa'a Samoa",
#			    "SN"=> "chiShona",
#			    "SO"=> "Soomaaliga",
#			    "SQ"=> "Shqip",
#			    "SR"=> "српски језик",
#			    "SS"=> "SiSwati",
#			    "ST"=> "seSotho",
#			    "SU"=> "Basa Sunda",
#			    "SV"=> "Svenska",
#			    "SW"=> "Kiswahili",
#			    "TA"=> "தமிழ்",
#			    "TE"=> "తెలుగు",
#			    "TG"=> "тоҷикӣ",
#			    "TH"=> "ไทย",
#			    "TI"=> "ትግርኛ",
#			    "TK"=> "Türkmen",
#			    "TL"=> "Tagalog",
#			    "TN"=> "seTswana",
#			    "TO"=> "faka Tonga",
#			    "TR"=> "Türkçe",
#			    "TS"=> "xiTsonga",
#			    "TT"=> "татарча",
#			    "TW"=> "Twi",
#			    "UK"=> "українська мова",
#			    "UR"=> "‫اردو",
#			    "UZ"=> "O'zbek",
#			    "VI"=> "Tiếng Việt",
#			    "VO"=> "Volapük",
#			    "WO"=> "Wollof",
#			    "XH"=> "isiXhosa",
#			    "YI"=> "‫ייִדיש",
#			    "YO"=> "Yoruba",
#			    "ZH"=> "中文",
#			    "ZU"=> "isiZulu"
);
$GLOBALS['languages']=array(
#			    "AA"=> "Afaraf",
#			    "AB"=> "Аҧсуа",
#			    "AF"=> "Afrikaans",
#			    "AM"=> "አማርኛ",
			    "AR"=> "العربية",
#			    "AS"=> "অসমীয়া",
#			    "AY"=> "Aymar aru",
#			    "AZ"=> "Azərbaycan dili",
#			    "BA"=> "башҡорт теле",
#			    "BE"=> "Беларуская",
#			    "BG"=> "български език",
#			    "BH"=> "भोजपुरी",
#			    "BI"=> "Bislama",
#			    "BN"=> "বাংলা",
#			    "BO"=> "བོད་ཡིག",
#			    "BR"=> "Brezhoneg",
#			    "CA"=> "Català",
#			    "CO"=> "Corsu",
#			    "CS"=> "Česky",
#			    "CY"=> "Cymraeg",
#			    "DA"=> "Dansk",
			    "DE"=> "Deutsch",
#			    "DZ"=> "རྫོང་ཁ",
			    "EL"=> "Ελληνικά",
			    "EN"=> "English",
#			    "EO"=> "Esperanto",
			    "ES"=> "Español",
#			    "ET"=> "Eesti keel",
			    "EU"=> "Euskara",
			    "FA"=> "‫فارسی",
#			    "FI"=> "Suomen kieli",
#			    "FJ"=> "Vosa Vakaviti",
#			    "FO"=> "Føroyskt",
			    "FR"=> "Français",
#			    "FY"=> "Frysk",
#			    "GA"=> "Gaeilge",
#			    "GD"=> "Gàidhlig",
#			    "GL"=> "Galego",
#			    "GN"=> "Avañe'ẽ",
#			    "GU"=> "ગુજરાતી",
#			    "HA"=> "‫هَوُسَ",
#			    "HI"=> "हिन्दी",
#			    "HR"=> "Hrvatski",
			    "HU"=> "Magyar",
#			    "HY"=> "Հայերեն",
#			    "IA"=> "Interlingua",
#			    "IE"=> "Interlingue",
#			    "IK"=> "Iñupiaq",
#			    "ID"=> "Bahasa Indonesia",
#			    "IS"=> "Íslenska",
			    "IT"=> "Italiano",
#			    "HE"=> "‫עברית",
#			    "JA"=> "日本語",
#			    "JV"=> "Basa Jawa",
#			    "KA"=> "ქართული",
#			    "KK"=> "Қазақ тілі",
#			    "KL"=> "Kalaallisut",
#			    "KM"=> "ភាសាខ្មែរ",
#			    "KN"=> "ಕನ್ನಡ",
#			    "KO"=> "한국어",
#			    "KS"=> "कश्मीरी",
#			    "KU"=> "Kurdî",
#			    "KY"=> "кыргыз тили",
			    "LA"=> "Latine",
#			    "LN"=> "Lingala",
#			    "LO"=> "ພາສາລາວ",
#			    "LT"=> "Lietuvių kalba",
#			    "LV"=> "Latviešu valoda",
#			    "MG"=> "Fiteny malagasy",
#			    "MI"=> "Te reo Māori",
#			    "MK"=> "македонски",
#			    "ML"=> "മലയാളം",
#			    "MN"=> "Монгол",
#			    "MO"=> "лимба молдовеняскэ",
#			    "MR"=> "मराठी",
#			    "MS"=> "Bahasa Melayu",
#			    "MT"=> "Malti",
#			    "MY"=> "ဗမာစာ",
#			    "NA"=> "Ekakairũ Naoero",
#			    "NE"=> "नेपाली",
			    "NL"=> "Nederlands",
#			    "NO"=> "Norsk",
#			    "OC"=> "Occitan",
#			    "OM"=> "Afaan Oromoo",
#			    "OR"=> "ଓଡ଼ିଆ",
#			    "PA"=> "ਪੰਜਾਬੀ",
			    "PL"=> "Polski",
#			    "PS"=> "‫پښتو",
			    "PT"=> "Português",
#			    "QU"=> "Kichwa",
#			    "RM"=> "Rumantsch grischun",
#			    "RN"=> "kiRundi",
#			    "RO"=> "Română",
			    "RU"=> "русский язык",
#			    "RW"=> "Kinyarwanda",
#			    "SA"=> "संस्कृतम्",
#			    "SD"=> "सिन्धी",
#			    "SG"=> "Yângâ tî sängö",
#			    "SI"=> "සිංහල",
#			    "SK"=> "Slovenčina",
#			    "SL"=> "Slovenščina",
#			    "SM"=> "Gagana fa'a Samoa",
#			    "SN"=> "chiShona",
#			    "SO"=> "Soomaaliga",
#			    "SQ"=> "Shqip",
#			    "SR"=> "српски језик",
#			    "SS"=> "SiSwati",
#			    "ST"=> "seSotho",
#			    "SU"=> "Basa Sunda",
#			    "SV"=> "Svenska",
#			    "SW"=> "Kiswahili",
#			    "TA"=> "தமிழ்",
#			    "TE"=> "తెలుగు",
#			    "TG"=> "тоҷикӣ",
#			    "TH"=> "ไทย",
#			    "TI"=> "ትግርኛ",
#			    "TK"=> "Türkmen",
#			    "TL"=> "Tagalog",
#			    "TN"=> "seTswana",
#			    "TO"=> "faka Tonga",
			    "TR"=> "Türkçe",
#			    "TS"=> "xiTsonga",
#			    "TT"=> "татарча",
#			    "TW"=> "Twi",
#			    "UK"=> "українська мова",
#			    "UR"=> "‫اردو",
#			    "UZ"=> "O'zbek",
#			    "VI"=> "Tiếng Việt",
#			    "VO"=> "Volapük",
#			    "WO"=> "Wollof",
#			    "XH"=> "isiXhosa",
#			    "YI"=> "‫ייִדיש",
#			    "YO"=> "Yoruba",
#			    "ZH"=> "中文",
#			    "ZU"=> "isiZulu"
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
