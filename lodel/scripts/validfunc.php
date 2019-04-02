<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier utilitaire pour gérer la validation des champs
 */

/**
 * Validation des champs
 *
 * <p>Validation des caractères autorisés dans les champs suivant leur type
 * leur nom, et le texte contenu. Par exemple si on a un champ de type email, il faut
 * vérifier que l'adresse mail est bien formée. Idem pour un champ de type url. Cela appliqué 
 * à tous les types de champs gérés par Lodel (cf. fichier fieldfunc.php)</p>
 *
 * @param string $&text le texte à valider. Passé par référence.
 * @param string $type le type du champ à valider.
 * @param string $default la valeur par défaut à valider (si le texte est vide). Est vide par défaut
 * @param string $name le nom du champ
 * @param string $usedata indique si le context utilise le sous tableau data pour stocker les données
 * @param array $context le context utilisé par la fonction appelante
 * @param int $filerank utilisé pour upload multiple de documents dans le meme champ
 * @return boolean true si le champ est valide. false sinon
 */
function validfield(&$text, $type, $default = "", $name = "", $usedata = "", $directory="", $context=null, $filerank=null)
{
	global $db;
	static $tmpdir;
	static $masks = array();

	isset($GLOBALS['lodelfieldtypes']) || include 'fieldfunc.php';

	if (isset($GLOBALS['lodelfieldtypes'][$type]['autostriptags']) && $GLOBALS['lodelfieldtypes'][$type]['autostriptags'] && !is_array($text)) {
		$text = strip_tags($text);
	}
	switch ($type) { //pour chaque type de champ
	case 'xpath':
		if(!$text) {
			$text = $default;
		} else {
			$dom = new DomDocument('1.0', 'UTF-8');
			$dom->loadXML('<root></root>');
			$xpath = new DomXPath($dom);
			$xpath->registerNamespace('tei', "http://www.tei-c.org/ns/1.0/");
			$test = @$xpath->query($text);
			if(false === $test) return $type;
		}
		break;
	case 'history' :
	case 'text' :
	case 'tinytext' :
	case 'longtext' :
		if (!$text) {
			$text = $default;
		} elseif($name && isset($context['class'])) {
			if(!isset($masks[$context['class']])) {
				$masks[$context['class']] = array();
				$fields = $db->execute(lq("select name, mask from #_TP_tablefields where class='{$context['class']}' AND type in ('text', 'longtext', 'tinytext') AND mask !=''"));
				if(!$fields) return true;
				while(!$fields->EOF) {
					$mask = @unserialize(html_entity_decode(stripslashes($fields->fields['mask'])));
					if(!is_array($mask))
					{
						$fields->MoveNext();
						continue;
					}
					$masks[$context['class']][$fields->fields['name']] = array();
					$masks[$context['class']][$fields->fields['name']]['lodel'] = isset($mask['lodel']) ? $mask['lodel'] : '';
					$masks[$context['class']][$fields->fields['name']]['user'] = isset($mask['user']) ? $mask['user'] : '';
					$fields->MoveNext();
				}
				unset($mask);
			}
			if(!empty($masks[$context['class']][$name]['lodel'])) {
				$ret = @preg_match($masks[$context['class']][$name]['lodel'], $text);
				if(FALSE === $ret) trigger_error('Bad regexp for validating variable '.$name.' of class '.$context['class'].' in validfunc.php. Please edit the mask in the editorial model.', E_USER_ERROR);
				// doesn't validate mask
				if(0 === $ret) return 'mask: '.getlodeltextcontents('field_doesnt_match_mask', 'common').' ("'.htmlentities($masks[$context['class']][$name]['user']).'")';
			}
		}
		$text = unicode_to_numeric_entity($text); // mysql utf8 encoding workaround

		return true; // always true
		break;
	case 'select_lang':
		if (!preg_match("/^[a-zA-Z]{2}$/", $text)) {
			return $type;
		}
		break;
	case 'type' :
		if ($text && !preg_match("/^[a-zA-Z0-9_][a-zA-Z0-9_ -]*$/", $text)) {
			return $type;
		}
		break;
	case 'class' :
		if (!preg_match("/^[a-zA-Z][a-zA-Z0-9_]*$/", $text)) {
			return $type;
		}
		function_exists('reservedword') || include 'fieldfunc.php';
		if (reservedword($text)) {
			return 'reservedsql'; // if the class is a reservedword -> error
		}
		break;
	case 'classtype' :
		$text = strtolower($text);
		if (!preg_match("/^[a-zA-Z][a-zA-Z0-9_]*$/", $text)) {
			return $type;
		}
		function_exists('reservedword') || include 'fieldfunc.php';
		if (reservedword($text)) {
			return 'reservedsql';
		}
		break;
	case 'tablefield' :
		$text = strtolower($text);
		if (!preg_match("/^[a-z0-9]{2,}$/", $text)) {
			return $type;
		}
		function_exists('reservedword') || include 'fieldfunc.php';
		if (reservedword($text))
			return 'reservedsql';
		break;
		if ($text && !preg_match("/^[a-zA-Z0-9]+$/", $text)) {
			return $type;
		}
		break;
	case 'mlstyle' :
		$text = strtolower($text);
		$stylesarr = preg_split("/[\n,;]/", $text);
		foreach ($stylesarr as $style) {
			$style = trim($style);
			if ($style && !preg_match("/^[a-zA-Z0-9]*(\.[a-zA-Z0-9]+)?\s*(:\s*([a-zA-Z]{2}|--))?$/", $style)) {
				return $type;
			}
		}
		break;
	case 'style' :
		if ($text)
		{
			$text = strtolower($text);
			$stylesarr = preg_split("/[\n,;]/", $text);
			foreach ($stylesarr as $style) {
				if (!preg_match("/^[a-zA-Z0-9]*(\.[a-zA-Z0-9]+)?$/", trim($style))) {
					return $type;
				}
			}
		}
		break;
	case 'passwd' :
		if(!$text) {
			return $type;
		}
		else {
			$len = strlen($text);
			if ($len < 3 || $len > 255 || !preg_match("/^\PC+$/", $text)) {
				return $type;
			}
		}
		break;
	
	case 'username' :
		if ($text) {
			$len = strlen($text);
			if ($len < 3 || $len > 64 || !preg_match("/^[0-9A-Za-z_;.?!@:,&\-]+$/", $text)) {
				return $type;
			}
		}
		break;
	case 'lang' : //champ de type langue (i.e fr_FR, en_US)
		if ($text) {
			if (!preg_match("/^[a-zA-Z]{2}(_[a-zA-Z]{2})?$/", $text) &&
 			!preg_match("/\b[a-zA-Z]{3}\b/", $text)) {
				return $type;
			}
		}
		break;
	case 'date' :
		function_exists('mysqldatetime') || include 'date.php';
		if ($text) {
			$textx = mysqldatetime($text, $type);
			if (!$textx || $textx == $type)
				return $type;
			else 
				$text = $textx;
		}	elseif ($default) {
			$dt = mysqldatetime($default, $type);
			if ($dt) {
				$text = $dt;
			} else {
				trigger_error("ERROR: default value not a date or time: \"$default\"", E_USER_ERROR);
			}
		}
		break;
	case 'datetime' :
		function_exists('mysqldatetime') || include 'date.php';
		if ($text) {
			$textx = mysqldatetime($text, $type);
			if (!$textx || $textx == $type)
				return $type;
			else 
				$text = $textx;
		}	elseif ($default) {
			$dt = mysqldatetime($default, $type);
			if ($dt) {
				$text = $dt;
			} else {
				trigger_error("ERROR: default value not a date or time: \"$default\"", E_USER_ERROR);
			}
		}
		break;
	case 'time' : 
		function_exists('mysqldatetime') || include 'date.php';
		if ($text) {
			$textx = mysqldatetime($text, $type);
			if (!$textx || $textx == $type)
				return $type;
			else 
				$text = $textx;
		}	elseif ($default) {
			$dt = mysqldatetime($default, $type);
			if ($dt) {
				$text = $dt;
			} else {
				trigger_error("ERROR: default value not a date or time: \"$default\"", E_USER_ERROR);
			}
		}
		break;
	case 'int' :
		if ((empty ($text) || $text === "") && $default !== "") {
			$text = (int)$default;
		}
		if ($text && (!is_numeric($text) || (int)$text != $text)) {
			return 'int';
		}
		break;
	case 'number' : //nombre
		if ((empty ($text) || $text === "") && $default !== "") {
			$text = doubleval($default);
		}
		if ($text && !is_numeric($text)) {
			return 'numeric';
		}
		break;
	case 'email' :
		if (!$text && $default) {
			$text = $default;
		}
		if ($text && !filter_var($text, FILTER_VALIDATE_EMAIL)) {
			return 'email';
		}
		break;
	case 'url' :
		if (!$text && $default) {
			$text = $default;
		}
		if ($text) {
			$parsedurl = @parse_url($text);
			if (!$parsedurl || empty($parsedurl['host']) || !preg_match("/^(http|ftp|https|file|gopher|telnet|nntp|news)$/i", $parsedurl['scheme'])) {
				return 'url';
			}
		}
		break;
	case 'boolean' :
		if (empty($context['id']) && empty($text)) $text = $default; // un booléen ne peut valoir son défaut qu'à l'initialisation
		$text = (boolean) ( empty($text) ? 0 : 1 );
		break;
	case 'tplfile' :
		$text = trim($text); // should be done elsewhere but to be sure...
		if ($text && (strpos($text, "/") !== false || $text[0] == ".")) {
			return "tplfile";
		}
		break;
	case 'color' :
		if ($text && !preg_match("/^#[A-Fa-f0-9]{3,6}$/", $text)) {
			return 'color';
		}
		break;
	case 'entity' :
		$text = (int)$text;
		// check it exists
		$vo = DAO::getDAO('entities')->getById($text, "1");
		if (!$vo) {
			return 'entity';
		}
		break;
	case 'textgroups' :
		return $text == 'site' || $text == 'interface';
		break;
	case 'select' :
	case 'multipleselect' :
		return true; // cannot validate
	case 'mldate':
		if(is_array($text)){
			function_exists('mysqldatetime') || include 'date.php';
			$str = "";
			foreach($text as $key => $date){
				if(is_int($key)){
					$date = mysqldate($date, "date");
					$str .= "<r2r:ml key=\"{$key}\">{$date}</r2r:ml>";
				}
			}
			$text = $str;
		}
		return true;
	case 'mltext' :
	case 'mllongtext' :
		if (is_array($text)) {
			$str = "";
			foreach ($text as $lang => $v) {
				if ($lang != "empty" && $v)
					$str .= "<r2r:ml lang=\"". $lang. "\">$v</r2r:ml>";
			}
			$text = $str;
		}
		$text = unicode_to_numeric_entity($text); // mysql utf8 encoding workaround

		return true;
	case 'list' :
		return true;
	case 'image' :
	case 'file' :
		if (!is_array($text)) {
			unset($text);
			return true;
		}
		if (!$name) {
			trigger_error("ERROR: \$name is not set in validfunc.php", E_USER_ERROR);
		}
        	if(!isset($text['radio'])) $text['radio'] = '';
		$filetodelete = false;
		switch ($text['radio']) {
		case 'upload' :
			// let's upload
			if(!$usedata) {
				$files = &$_FILES;
			} else { //les informations sur le champ se trouve dans $_FILES['data']
				if(isset($filerank))
				{
					if(!isset($_FILES['data']['error'][$name][$filerank]['upload']))
					{
						$text = '';
						return true;
					}
					$files[$name]['error']['upload'] = $_FILES['data']['error'][$name][$filerank]['upload'];
					$files[$name]['tmp_name']['upload'] = $_FILES['data']['tmp_name'][$name][$filerank]['upload'];
					$files[$name]['type']['upload'] = $_FILES['data']['type'][$name][$filerank]['upload'];
					$files[$name]['size']['upload'] = $_FILES['data']['size'][$name][$filerank]['upload'];
					$files[$name]['name']['upload'] = $_FILES['data']['name'][$name][$filerank]['upload'];
				}
				else
				{
					if(!isset($_FILES['data']['error'][$name]['upload']))
					{
						$text = '';
						return true;
					}
					$files[$name]['error']['upload'] = $_FILES['data']['error'][$name]['upload'];
					$files[$name]['tmp_name']['upload'] = $_FILES['data']['tmp_name'][$name]['upload'];
					$files[$name]['type']['upload'] = $_FILES['data']['type'][$name]['upload'];
					$files[$name]['size']['upload'] = $_FILES['data']['size'][$name]['upload'];
					$files[$name]['name']['upload'] = $_FILES['data']['name'][$name]['upload'];
				}
			}
			// on récupère l'extension du fichier   
			$extension = strtolower(strrchr($files[$name]['name']['upload'],'.'));
			// on évite la possibilité d'uploader des fichiers non désirés
			if(!in_array($extension, C::get('authorizedFiles', 'cfg'))) {
				return $text['radio'];
			}
			#print_r($files);
			// look for an error ?
			if (!$files || $files[$name]['error']['upload'] != 0 || !$files[$name]['tmp_name']['upload'] || $files[$name]['tmp_name']['upload'] == "none") {
				unset ($text);
				return 'upload';
			}

			if (!empty($directory)) {
				// Champ de type file ou image qui n'est PAS un doc annexe : copié dans le répertoire $directory
				$text = save_file($type, $directory, $files[$name]['tmp_name']['upload'], $files[$name]['name']['upload'], true, true, $err, false);
			} else {
				// check if the tmpdir is defined
				if (!isset($tmpdir[$type])) {
					// look for a unique dirname.
					do {
						$tmpdir[$type] = "docannexe/$type/tmpdir-". rand();
					}	while (file_exists(SITEROOT. $tmpdir[$type]));
				}
				// let's transfer
				$text = save_file($type, $tmpdir[$type], $files[$name]['tmp_name']['upload'], $files[$name]['name']['upload'], true, true, $err);
			}
			if ($err) {
				return $err;
			}
			return true;
		case 'serverfile' :
			// check if the tmpdir is defined
			if (!empty($directory)) {
				// Champ de type file ou image qui n'est PAS un doc annexe : copié dans le répertoire $directory
				$text = basename($text['localfilename']);
				$text = save_file($type, $directory, SITEROOT."upload/$text", $text, false, false, $err, false);
			} else {
				// check if the tmpdir is defined
				if (!isset($tmpdir[$type])) {
					// look for a unique dirname.
					do {
						$tmpdir[$type] = "docannexe/$type/tmpdir-". rand();
					} while (file_exists(SITEROOT. $tmpdir[$type]));
				}

				// let's move
				$text = basename($text['localfilename']);
				$text = save_file($type, $tmpdir[$type], SITEROOT."upload/$text", $text, false, false, $err);
			}
			if ($err) {
				return $err;
			}
			return true;
		case 'delete' :
			$filetodelete = true;
		case '' :
			// validate
			$text = isset($text['previousvalue']) ? $text['previousvalue'] : '';
			if (!$text) {
				return true;
			}
			if (!empty($directory)) {//echo "text = $text <p>";
				$directory= str_replace('/', '\/', $directory);//echo $directory;
				
				if (!preg_match("/^$directory\/[^\/]+$/", $text)) {
					trigger_error("ERROR: invalid filename of type $type", E_USER_ERROR);
				}
			} else {
				if (!preg_match("/^docannexe\/(image|file|fichier)\/[^\.\/]+\/?[^\/]+$/", $text)) {
					trigger_error("ERROR: invalid filename of type $type", E_USER_ERROR);
				}
			}
			if ($filetodelete) {
				unlink(SITEROOT.$text);
				$text = "deleted";
				unset ($filetodelete);
			}
			return true;
		default :
			trigger_error("ERROR: unknow radio value for $name", E_USER_ERROR);
		} // switch
	default :
		return false; // pas de validation
	}

	return true; // validated
}

// Les fonctions dessous sont appliqués sur tous les textes avant d'être enregistrés dans la base mysql
// mysql n'encode pas (de base) en utf-8 sur plus de trois bytes…
// Alors on convertit ces carcatères dans leur équivalent numérique

/**
 * Convertir les caractères utf-8 codés sur 4 bytes dans leur équivalent numerique => &#10000;
 */
function unicode_to_numeric_entity($text) {
	// detect 4-byte UTF-8 characters : trouvé sur http://www.w3.org/International/questions/qa-forms-utf-8.en.php
	$regex = '/(?:\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})/xs';
	              # planes 1-3                  # planes 4-15             # plane 16
	$text = preg_replace_callback($regex,'unicode_to_numeric_entity_callback', $text);
	return $text;
}

// used by unicode_to_numeric_entity() to do the real job
function unicode_to_numeric_entity_callback($matches) {
	// Convertir l'utf-8 en UCS-4LE : four byte direct encodings of ISO-10646 (unicode)
	$char = iconv('UTF-8', 'UCS-4LE', $matches[0]);

	if ($char) {
		// convertir en unsigned long pour avoir la représentation décimal
		$char = unpack('V', $char);
		if (isset($char[1]))
			return '&#' . $char[1] . ';';
	}

	// on retourne un caractère qui ne cassera pas mysql…
	return '�';
}
