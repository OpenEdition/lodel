<?php
/**
 * Fichier OAI - script du dépot OAI
 *
 * Ce fichier reçoit les commande OAI et fait renvoit le XML associé
 *
 * Mostly taken and often adapted from OAI V2 Data-Provider, Heinrich Stamerjohanns, 
 * stamer@uni-oldenburg.de, http://physnet.uni-oldenburg.de/oai
 *
 * Code fortement repris/inspire de celui d''OAI V2 Data-Provider' par Heinrich Stamerjohanns :
 * stamer@uni-oldenburg.de, http://physnet.uni-oldenburg.de/oai
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
 * @author Bruno Cénou
 * @author Loïc Bontonou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel/source
 */
require_once 'siteconfig.php';
require_once 'auth.php';
require_once 'func.php';
authenticate();

require_once 'connect.php';

define('TOKENVALID', 24); // tokens lifetime in hours
define('MAXIDS', 10); // max delivered identifiers
define('MAXRECORDS', 10); // max delivered records
$metadataformats = array ('oai_dc'); // only Dublin Core at the moment

$dateformat = '';

function getOut($hostname)
{
	header ("http/1.0 403 Forbidden");
	echo "Host $hostname is not allowed";
	log_access($hostname, 1);
	exit();
}

/*function log_access($hostname, $denied = 0){
	global $db;
	$db->execute(lq("INSERT INTO #_TP_oailogs (host, date, denied) VALUES ('$hostname', ".date('YmdHis').", $denied)"));
}*/

function log_access($hostname, $denied = 0) 
{
	global $db;
	$db->execute(lq("INSERT INTO #_TP_oailogs (host, denied) VALUES ('". $hostname. "','". $denied. "')")) or dberror();
}




/**
 * Generates OAI error messages.
 *
 * Genere les messages d'erreur OAI.
 *
 * @param string $code le code d'erreur
 * @param string $argument l'argument passé. Par défaut vide
 * @param string $value la valeur de l'argument. Par défaut vide
 * @return une chaine xml d'erreur <error code="">message</error>
 */
function oai_error ($code, $argument = '', $value = '')
{
	global $request;
	global $request_err;

	switch ($code) {
	case 'badArgument' :
		if($argument == 'granularity'){
			$text = 'mismatched granularities in from/until.';
			break;
		}
		$text = "The argument '$argument' (value='$value') included in the request is not valid.";
		break;
	case 'badGranularity' :
		$text = "The value '$value' of the argument '$argument' is not valid.";
		$code = 'badArgument';
		break;
	case 'badResumptionToken' :
		$text = "The resumptionToken '$value' does not exist or has already expired.";
		break;
	case 'badRequestMethod' :
		$text = "The request method '$argument' is unknown.";
		$code = 'badVerb';
		break;
	case 'badVerb' :
		$text = "The verb '$argument' provided in the request is illegal.";
		break;
	case 'cannotDisseminateFormat' :
		$text = "The metadata format '$value' given by $argument is not supported by this repository.";
		break;
	case 'exclusiveArgument' :
		$text = 'The usage of resumptionToken as an argument allows no other arguments.';
		$code = 'badArgument';
		break;
	case 'idDoesNotExist' :
		$text = "The value '$value' of the identifier is illegal for this repository.";
		break;
	case 'missingArgument' :
		$text = "The required argument '$argument' is missing in the request.";
		$code = 'badArgument';
		break;
	case 'noRecordsMatch' :
		$text = 'The combination of the given values results in an empty list.';
		break;
	case 'noMetadataFormats' :
		$text = 'There are no metadata formats available for the specified item.';
		break;
	case 'noVerb' :
		$text = 'The request does not provide any verb.';
		$code = 'badVerb';
		break;
	case 'noSetHierarchy' :
		$text = 'This repository does not support sets.';
		break;
	case 'sameArgument' :
		$text = 'Do not use them same argument more than once.';
		$code = 'badArgument';
		break;
	case 'sameVerb' :
		$text = 'Do not use verb more than once.';
		$code = 'badVerb';
		break;
	default:
		$text = "Unknown error: code: '$code', argument: '$argument', value: '$value'";
		$code = 'badArgument';
  }

	$error .= '<error code="'. xmlstr($code, 'utf-8', false). '">'. xmlstr($text, 'utf-8', false). '</error>'. "\n";
	return $error;
}

/**
 * Transforme une chaine pour l'inclure dans un fichier XML
 *
 * @param string $string la chaine de caractères
 * @param string $charset le jeu de caractère de la chaine (utf-8 par défaut).
 * @param boolean $xmlescaped un boolean indiquant si le xml doit être échappé ou non. Par défaut à false.
 * @return la chaîne XML
 */
function xmlstr ($string, $charset = 'utf-8', $xmlescaped = false)
{
	$string = stripslashes($string);
	// just remove invalid characters
	$pattern = "/[\x-\x8\xb-\xc\xe-\x1f]/";
	$string = preg_replace($pattern, '', $string);

	// escape only if string is not escaped
	if (!$xmlescaped) {
		$xmlstr = htmlspecialchars($string, ENT_QUOTES);
	}

	if ($charset != 'utf-8') {
		$xmlstr = utf8_encode($xmlstr);
	}
	return $xmlstr;
}

/**
 * Extracts a token's infos from the database.
 * If the token doesn't exist, the error is handled in the 'verbs_processing' function. 
 *
 * Extrait de la bd les infos concernant le token.
 * Si le token n'existe pas, l'erreur est traitee par la suite dans la fonction "verbs_processing".
 *
 * @param string $token le token
 * @return un resultset SQL
 */
function get_token_info($token)
{
	global $db;
	$result = $db->getrow(lq("SELECT * FROM #_TP_oaitokens WHERE token ='". $token. "'"));
	if ($result === false) {
		dberror();
	}
	return $result;
}

/**
 * Deletes a token from table oaitokens once it has been used.
 *
 * Une fois un token exploite, il est retire de la table oaitokens.
 * @param string $token le token
 * @return rien
 */
function del_token($token)
{
	global $db;
	$result = $db->execute(lq("DELETE FROM #_TP_oaitokens WHERE token ='". $token. "'"));
	if ($result === false) {
		dberror();
	}
}

/**
 * Inserts new token in table oaitokens.
 *
 * Insere un nouveau token dans la table oaitokens.
 *
 * @param string $token le token
 * @param string $where la clause where
 * @param string $metadataprefix le format de métadonnées de la requête
 * @param integer $deliveredrecords le nombre d'enregistrements délivrés
 * @param datetime $expirationdatetime le datetime d'expiration du token.
 */
function insert_token($token, $where, $metadataprefix, $deliveredrecords, $expirationdatetime)
{
	global $db;
	$q = "INSERT INTO #_TP_oaitokens (token, query, metadataprefix, deliveredrecords, expirationdatetime)";

	$q .= " VALUES('".$token. "', '".addslashes($where). "', '". $metadataprefix. "', '". $deliveredrecords. "', '". $expirationdatetime. "')";

	$result = $db->execute(lq($q));

	if ($result === false) {
		dberror();
	}
}



/**
 * Deletes outdated tokens from table oaitokens.
 * 
 * Supprime les tokens dont la date de validite est depassee.
 */
function clean_expired_tokens()
{
	global $db;
	$result = $db->execute(lq("DELETE FROM #_TP_oaitokens WHERE expirationdatetime < ". date('YmdHis', time() - (TOKENVALID*3600))));
	if ($result === false) {
		dberror();
	}
}

/**
 * Transforme une date SQL en timestamp unix
 * @param string $date la date au format datetime de MySQL
 * @return le timestamp unix
 */
function sql2TS($date)
{
	return mktime(substr($date, 8, 2), substr($date, 10, 2), substr($date, 12, 2), substr($date, 4, 2), substr($date, 6, 2), substr($date, 0, 4));
}

/**
 * dc types are defined in lodel's database using the template : "dc.name"
 * to fit the OAI protocol, we have to rename them using the template "dc:name"
 *
 * les types dc sont definis dans la base de donnees en utilisant la forme "dc.nom", 
 * on les renomme en "dc:nom" pour coller au protocole OAI
 *
 * @param string $str la chaine à modifier
 * @return la chaine modifiée
 */
function dc_rename($str)
{
	return preg_replace("/dc./", "dc:", $str);
}

/**
 * Remplace les & par des &amp; dans une chaine
 *
 * @param string $str la chaine à modifier
 * @return la chaine modifiée
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

function HTML2XML($str){
	$replace = array(
		"&quot;" => "&#34;",
		"&amp;" => "&#38;",
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
	return str_replace(array_keys($replace),array_values($replace),$str);
}

/**
 * Only a limited set of characters is available for sets' names.
 * This filter replaces the unapropriate characters by valid ones.
 *
 * Les noms de sets ne peuvent comporter qu'un ensemble limite de caracteres.
 * Ce filtre remplace les caracteres inappropries.
 *
 * @param string $str la chaine à modifier
 * @return la chaine modifiée
 */
function strip_set($str)
{
	require_once 'func.php';
	$str = makeSortKey($str);
	return preg_replace("/[^a-zA-Z0-9_.!~*\'()]/", "_", $str);
}

/**
 * Uses $id_class_fields to get the name of an entity's dc.description field, 
 * extract its content from the database and return it.
 *
 * A partir de la table mettant en relation un id d'entite avec le nom de sa
 * classe et celui du champ dc.description correspondant, on renvoit le contenu
 * de ce champ dc.descrition.
 *
 * @param integer l'identifiant de l'entité
 * @return un resultSet SQL
 */
function get_dc_description($id)
{
	global $id_class_fields;
	global $db;
	if ($id_class_fields[$id]['dc.description']) {
		$class_table = "#_TP_".$id_class_fields[$id]['class'];
		$field = $id_class_fields[$id]['dc.description'];
		$result =$db->getOne(lq("SELECT $field FROM $class_table WHERE identity = '$id'"));
		if ($result===false) {
			dberror();
		}
  }
  return $result;
}



/**
 * Uses $id_class_fields to get the name of an entity's dc.language field, 
 * extract its content from the database and return it.
 *
 * A partir de la table mettant en relation un id d'entite avec le nom de sa
 * classe et celui du champ dc.language correspondant, on renvoit le contenu
 * de ce champ dc.language.
 *
 * @param integer l'identifiant de l'entité
 * @return un resultSet SQL
 */
 function get_dc_language($id)
{
	global $id_class_fields;
	global $db;

	if ($id_class_fields[$id]['dc.language']) {
		$class_table = "#_TP_".$id_class_fields[$id]['class'];
		$field = $id_class_fields[$id]['dc.language'];
		$result = $db->getone(lq("SELECT $class_table.$field FROM $class_table WHERE $class_table.identity = $id"));
		if ($result===false) {
			dberror();
		}
  }
  return $result;
}

/**
 * Tests the 'verb' argument.
 */
function verbs_processing()
{
	global $args;
	global $context;
	global $errors;
	global $db;
	global $metadataformats;
	global $format;
	global $resumptionToken;

	if ($args['verb']) {
		$format = strtolower($args['verb']);
		switch ($args['verb']) {
		case 'Identify':
			unset($args['verb']);
			illegal_parameters();
			break;
		case 'ListMetadataFormats':
			unset($args['verb']);
			if ($args['identifier']) {
				check_identifier($args['identifier']);
				unset($args['identifier']);
			}
			illegal_parameters();
			break;
		case 'ListSets':
			unset($args['verb']);
			if($args['resumptionToken']) {
				$resumptionToken = $args['resumptionToken'];
				unset($args['resumptionToken']);
			}
			illegal_parameters();
			break;
		case 'GetRecord':
			unset($args['verb']);
			if (!$args['identifier']) {
				$errors .= oai_error('missingArgument', 'identifier');
			} else {
				check_identifier($args['identifier']);
				unset($args['identifier']);
			}
			if (!$args['metadataPrefix']) {
				$errors .= oai_error('missingArgument', 'metadataPrefix');
			} else {
				check_mdp($args['metadataPrefix']);
				unset($args['metadataPrefix']);
			}
			illegal_parameters();
			break;
		case 'ListIdentifiers':
			check_records();
			illegal_parameters();
			break;
		case 'ListRecords':
			check_records();
			illegal_parameters();
			break;
		default:
			$errors .= oai_error('badVerb', $args['verb']);
		} /*switch */

		if(!$errors){
			$context['oai_where'] .= "AND #_TP_entities.id ".sql_in_array($context['oai_ids'])." AND #_TP_types.oaireferenced >0 AND #_TP_entities.idtype = #_TP_types.id AND #_TP_entities.status >0 AND #_TP_types.status >0 AND #_TP_entities.creationdate<".date('Ymd');
			$context['oai_where'] = substr($context['oai_where'], 4);

			if($format == 'listidentifiers' || $format == 'listrecords') {
				$MAX = $format == 'listidentifiers' ? $context['oai_maxids'] : $context['oai_maxrecords'];

				$query = "SELECT  #_TP_entities.id FROM #_entitiestypesjoin_ WHERE ".$context[oai_where];
				$result =$db->execute(lq($query));
				if ($result === false) {
					dberror();
				}
				$context['oai_nbtot'] = $result->RowCount();

				$tokenValid            = TOKENVALID*3600;
				$exp_date              = time()+$tokenValid;
				$my_expirationdatetime = date('YmdHis', $exp_date);
				$expirationdatetime    = gmstrftime('%Y-%m-%dT%TZ', $exp_date);

				if (isset($resumptionToken)) {
					$info = get_token_info($resumptionToken);
					if (is_array($info) && sql2TS($info['expirationdatetime']) > (time() - $tokenValid)) {
						$deliveredrecords     = $info['deliveredrecords'];
						$context['oai_where'] = $info['query'];
						$metadataPrefix       = $info['metadataprefix'];
						unset($errors);
						del_token($resumptionToken);
					} else {
						$errors = oai_error('badResumptionToken', '', $resumptionToken);
					}
        }
				// Will we need a ResumptionToken?
				$context['oai_offset'] = isset($deliveredrecords) ? $deliveredrecords : 0;
				$query .= " LIMIT ". $context['oai_offset'].", $MAX";
				$result =$db->execute(lq($query));
				if ($result === false) {
					dberror();
				}

				$deliveredrecords += $result->RowCount();

				if ($context['oai_nbtot'] - $deliveredrecords > 0) {
					$token = uniqid(8); 
					insert_token($token, $context['oai_where'], $metadataPrefix, $deliveredrecords, $my_expirationdatetime);
					$context['oai_restoken'] = 
            '  <resumptionToken expirationDate="'.$expirationdatetime.'"
            completeListSize="'.$context['oai_nbtot'].'"
            cursor="'.$deliveredrecords.'">'.$token."</resumptionToken>\n";
				}
				// Last delivery, return empty ResumptionToken

				if($context['oai_nbtot'] == 0) {
					$errors = oai_error('noRecordsMatch');
				} elseif ($context['oai_nbtot'] <= $deliveredrecords) {
					del_token($token);
					$context['oai_restoken'] = 
            '  <resumptionToken completeListSize="'.$context['oai_nbtot'].'"
            cursor="'.$deliveredrecords.'"></resumptionToken>'."\n";
				}
			}
		}
	} else {
		$errors = oai_error('noVerb');
	}
}



/**
 * ListRecords and ListIdentifiers accept the same parameters, the shared 
 * tests are regrouped in this function.
 *
 * ListRecords et ListIdentifiers acceptent les meme parametres, les tests 
 * communs sont donc regroupes dans cette fonction.
 */
function check_records ()
{
	global $args;
	global $context;
	global $errors;
	global $db;
	global $metadataformats;
	global $resumptionToken;

	unset($args['verb']);
	if ((!$args['metadataPrefix']) && (!$args['resumptionToken'])) {
		$errors .= oai_error('missingArgument', 'metadataPrefix');
	} elseif($args['resumptionToken'] && (count($args)>1)) {
		$errors .= oai_error('exclusiveArgument');
	} else {
		// patterns to test date granularity
		$longdate = "/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}Z$/";
		$shortdate = "/^\\d{4}-\\d{2}-\\d{2}$/";

		if ($args['from']) {
			$from = $args['from'];
			if (preg_match($longdate, $from)) {
				$context['oai_where'] .= "AND (creationdate>= '". $from. "' || modificationdate >= '". $from. "')";
			} elseif (preg_match($shortdate, $from)) {
				$context['oai_where'] .= "AND (creationdate>= '". $from. "' || modificationdate >= '". $from. "')";
				$context['short_date'] = true;
			} else {
				$errors .= oai_error('badArgument', 'from', $from);
				unset($from);
			}
			unset($args['from']);
		}

		if ($args['until']) {
			$until = $args['until'];
			if (preg_match($longdate, $until)) {
				$context['oai_where'] .= "AND creationdate <= '".$until."'";
			} elseif (preg_match($shortdate, $until)) {
				$context['oai_where'] .= "AND creationdate <= '".$until."'";
				$context['short_date'] = true;
			} else {
				$errors .= oai_error('badArgument', 'until', $until);
				unset($until);
			}
			unset($args['until']);
		}

		if ($args['metadataPrefix']) {
			check_mdp($args['metadataPrefix']);
			unset($args['metadataPrefix']);
		}

		if($args['set']) {
			$set = $args['set'];
			$context['oai_ids'] = array();

			$result =$db->execute(lq("SELECT id FROM #_TP_entities, #_TP_relations WHERE id2 = '".substr($args['set'], 4)."'"));
			if ($result === false) {
				dberror();
			}

			while (!$result->EOF) {		
				$row = $result->fields;
				$context['oai_ids'][] = $row['id'];
				$result->MoveNext();
			}
			unset($args['set']);
		}
		if($args['resumptionToken']) {
			$resumptionToken = $args['resumptionToken'];
			unset($args['resumptionToken']);
		}
		if(isset($from)&&isset($until)&&strlen($from)!=strlen($until)){
			$errors .= oai_error('badArgument', 'granularity', '');
		}
	}
}

/**
 * Tests the 'metadataPrefix' argument.
 *
 * Verifie la validite de l'information liee a l'argument 'metadataPrefix'.
 *
 * @param string $val la valeur à vérifier
 */
function check_mdp ($val)
{
	global $errors;
	global $metadataformats;

	if (in_array($val, $metadataformats)) {
		$metadataPrefix = $val;
	} else {
		$errors .= oai_error('cannotDisseminateFormat', 'metadataPrefix', $val);
	}
}

/**
 * Tests the 'Identifier' argument.
 *
 * Verifie la validite de l'information liee a l'argument 'Identifier'.
 *
 * @param string $val la valeur de l'identifier OAI
 */
function check_identifier ($val) 
{
	global $context;
	global $errors;
	global $metadataformats;

	$identifier = $val; 
	// remove the OAI part to get the identifier
	$id = str_replace($context['oai_prefix'], '', $identifier); 
	if (in_array($id, $context['oai_ids'])) {
		$context['oai_ids']   = array();
		$context['oai_ids'][] = $id;
	} else {
		$errors .= oai_error('idDoesNotExist', '', $identifier); 
	}
}

/**
 * Tests for illegal parameters and generate the appropriate error messages.
 *
 * Traite la presence d'arguments illegaux en generant le message d'erreur approprie.
 */
function illegal_parameters()
{
	global $args;
	global $errors;

	if ($args) {
		foreach ($args as $key=>$val) {
			$errors .= oai_error('badArgument', $key, $val);
		}
	}
}

//----------- DEBUT DU SCRIPT -----------//

/**
 * Check if the required options are defined (oai_identifier, oai_allow,
 * oai_deny) and store their values.
 *
 * Verification de l'existence des options requises (oai_identifier, oai_allow,
 * oai_deny) et recuperation de leur valeur.
 * Le groupe d'option oai est aussi requis
 */
$result = getoption(array('oai.oai_identifier', 'oai.oai_allow', 'oai.oai_deny'));

if($result['oai.oai_identifier']) {
	$context['oai_identifier'] = $result['oai.oai_identifier'];
}

if($result['oai.oai_allow']){
	$allowed = $result['oai.oai_allow'];
}

if($result['oai.oai_deny']){
	$denied = $result['oai.oai_deny'];
}

if(!isset($allowed) && !isset($denied)){
	echo "<code>Acces list of your OAI repository is not configured, please check site options.<br />";
	echo "In order to enable this you must configure an optiongroup called <em>oai</em> ".
				"containing 2 options : <em>oai_allow</em> and <em>oai_deny</em>. By default,".
				" <em>oai_allow</em> should be set to '*'</code>";
  exit;
}

$oai_allowed = explode(',', $allowed);
$oai_denied  = explode(',', $denied);

if (!isset($_SERVER['REMOTE_HOST'])) {
	$hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
}
else {
	$hostname = $_SERVER['REMOTE_HOST'];
}

/**
 * Identifies the request's emitter and check its rights.
 *
 * Identification de l'emetteur de la requete et verification de ses droits.
 */
if(in_array($_SERVER['REMOTE_ADDR'], $oai_denied) || in_array($hostname, $oai_denied) || (count($oai_denied) == 1 && $oai_denied[0] == '*' && !in_array($_SERVER['REMOTE_ADDR'], $oai_allowed) && !in_array($hostname, $oai_allowed))) {
	getOut($hostname);
}

if(!(in_array($_SERVER['REMOTE_ADDR'], $oai_allowed) || in_array($hostname, $oai_allowed) || (count($oai_allowed) == 1 && $oai_allowed[0] == '*'))){
	getOut($hostname);
}

log_access($hostname);
$oai_open = "<?xml version=\"1.0\" encoding=\"utf-8\"?><OAI-PMH xmlns=\"http://www.openarchives.org/OAI/2.0/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd\">
<responseDate>". gmstrftime('%Y-%m-%dT%TZ', time()). "</responseDate>
<request>". dirname($context['currenturl']). '/oai20.'. $context['extensionscripts']. "</request>";

$oai_close = "</OAI-PMH>\n";

/* A sortir dans les options du site ? *************************************************/
$context['oai_prefix']     = isset($context['oai_identifier']) ? $context['oai_identifier'] : 'oai:'. str_replace(array('http://', '/'), array('', '.'), dirname($context['currenturl'])). ':';
$context['oai_maxids']     = MAXIDS;
$context['oai_maxrecords'] = MAXRECORDS;
/***************************************************************************************/

/**
 * List of OAI-referenced entities.
 *
 * Construit la liste des entites referencees par l'OAI.
 */
$result = $db->execute(lq("SELECT #_TP_entities.id FROM #_entitiestypesjoin_ WHERE #_TP_entities.idtype = #_TP_types.id AND #_TP_types.oaireferenced = 1"));
if ($result === false) {
	dberror();
}

while (!$result->EOF) {
	$row = $result->fields;
	$context['oai_ids'][] = $row['id'];
	$result->MoveNext();
}

if(!$context['oai_ids']) {
	$errors .= oai_error('noRecordsMatch');
	header("Content-type: application/xml");
	require_once('func.php');
	echo _indent($oai_open. $errors. $oai_close);
	exit;
}

/**
 * Creates a table associating an entity's id with its class name and its associated 
 * fields which are equivalent to dc.description or dc.language. 
 *
 * Creation de la table indiquant pour chaque entite referencee son nom de
 * classe et les noms de champs equivalents a dc.description et dc.language.
 */

$id_class_fields = array();
$result = $db->execute(lq("SELECT #_TP_entities.id, #_TP_types.class, #_TP_tablefields.name, #_TP_tablefields.g_name
  FROM #_entitiestypesjoin_, #_TP_tablefieldgroups, #_TP_tablefields
  WHERE (#_TP_tablefields.g_name = 'dc.description' || #_TP_tablefields.g_name = 'dc.language')
  AND #_TP_tablefields.class = #_TP_types.class
  AND #_TP_types.oaireferenced = '1'
  AND #_TP_entities.idtype = #_TP_types.id"));
if ($result === false) {
	dberror();
}

while (!$result->EOF) {
	$row = $result->fields;
	$id  = $row['id'];
	$id_class_fields[$id]['class']        = $row['class'];
	$id_class_fields[$id][$row['g_name']] = $row['name'];
	$result->MoveNext();
}

/**
 * Stores the request's parameters.
 *
 * Recuperation des parametres de la requete.
 */
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	$args   = $_GET;
	$getarr = explode('&', $_SERVER['QUERY_STRING']);
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$args = $_POST;
} else {
	$errors .= oai_error('badRequestMethod', $_SERVER['REQUEST_METHOD']);
}

/**
 * Detects duplicate arguments in GET request.
 *
 * Detecte les arguments dupliques dans une requete passee par GET. 
 */
if (isset($getarr)) {
	if (count($getarr) != count($args)) {
		$errors .= oai_error('sameArgument');
	}
}

/**
 * Call to the 'clean_request_variable' function in func.php to remove potential 
 * risks of injection in the request's arguments.
 *
 * Utilisation de la fonction "clean_request_variable" de func.php pour supprimer 
 * tout risque d'injection dans les arguments de la requete.
 */
array_walk($args, 'clean_request_variable');
$context['oai_args'] = '';
if (is_array($args)) {
	foreach ($args as $key => $val) {
		$context['oai_args'] .= ' '.$key.'="'.htmlspecialchars(stripslashes($val)).'"';
	}
}

/**
 * Process the request. 
 *
 * Traitement des arguments de la requete.
 */
verbs_processing();

/**
 * If error messages were generated, they are displayed and the program ends.
 *
 * Si des messages d'erreur ont ete generes, ils sont affiches et l'execution du 
 * programme se termine.
 */
if(isset($errors)) {
	header("Content-type: application/xml");
	require_once('func.php');
	echo _indent($oai_open.$errors.$oai_close);
  exit;
}



/**
 * Generates response date, required by the protocol.
 *
 * Generation de la date de reponse, requise par le protocole.
 */

$context['oai_responsedate'] = gmstrftime('%Y-%m-%dT%TZ', time());


/**
 * Displays the response.
 *
 * Affichage de la reponse.
 */

$base = 'oai20';

require_once 'view.php';
$view = &View::getView();
//$view->renderCached($context,$base);
$view->render($context, $base);


/**
 * Suppress outdated tokens.
 *
 * Suppression des tokens dont la date limite de validite a ete atteinte.
 */

clean_expired_tokens();



?>
