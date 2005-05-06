<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 *  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
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

$GLOBALS['languages']=array(
#			    "AA"=> "Afar",
#			    "AB"=> "Abkhazian",
#			    "AF"=> "Afrikaans",
#			    "AM"=> "Amharic",
			    "AR"=> "Arabic",
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
			    "DE"=> "German",
#			    "DZ"=> "Bhutani",
			    "EL"=> "Greek",
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
			    "IT"=> "Italian",
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
#			    "NL"=> "Dutch",
#			    "NO"=> "Norwegian",
#			    "OC"=> "Occitan",
#			    "OM"=> "Oromo",
#			    "OR"=> "Oriya",
#			    "PA"=> "Punjabi",
#			    "PL"=> "Polish",
#			    "PS"=> "Pashto",
			    "PT"=> "Portuguese",
#			    "QU"=> "Quechua",
#			    "RM"=> "Rhaeto-Romance",
#			    "RN"=> "Kirundi",
#			    "RO"=> "Romanian",
			    "RU"=> "Russian",
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
			    "TR"=> "Turkish",
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




function makeselectlangs($selectedlang="") {
  global $context,$languages;
  
  echo "<option value=\"\">--</option>\n";
  foreach ($languages as $l=>$lang) {
    $l=strtolower($l);
    $selected=$selectedlang==$l ? " selected" : "";
    echo "<option value=\"$l\"$selected>$lang</option>\n";
  }
}


function makeselectlangs_nevernil($selectedlang="") {
  global $context,$languages;

  foreach ($languages as $l=>$lang) {
    $l=strtolower($l);
    $selected=$selectedlang==$l ? " selected" : "";
    echo "<option value=\"$l\"$selected>$lang</option>\n";
  }
}

?>
