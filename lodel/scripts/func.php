<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier utilitaire proposant des fonctions souvent utilisées dans Lodel
 */

if (is_readable(C::get('home', 'cfg') . 'func_local.php'))
    include 'func_local.php';


function writefile($filename, $text)
{
    if (false === @file_put_contents($filename, $text))
        trigger_error("Cannot write $filename.", E_USER_ERROR);
    @chmod($filename, 0666 & octdec(C::get('filemask', 'cfg')));
    return true;
}


function postprocessing(&$context)

{
    if ($context) {
        foreach ($context as $key => $val) {
            if (is_array($val)) {
                postprocessing($context[$key]);
            } else {
                $context[$key] = str_replace("Â\240", "&nbsp;", $val);
// 	$context[$key]=str_replace(array("\n","Â\240"),array(" ","&nbsp;"),$val);
            }
        }
    }
}

function clean_request_variable(&$var, $key = '')
{
    C::clean($var);
    return;
}

function magic_addslashes($var)
{
    return addslashes(stripslashes($var));
}

function magic_stripslashes($var)
{
    return (get_magic_quotes_gpc() ? stripslashes($var) : $var);
}

/**
 * function returning the closing tag corresponding to the opening tag in the sequence
 * this function could be smarter.
 */
function closetags($text)
{
    if (!preg_match_all("/<(\w+)\b[^>]*>/", $text, $results, PREG_PATTERN_ORDER)) return '';
    $n = count($results[1]);
    $ret = '';
    for ($i = $n - 1; $i >= 0; $i--) {
        $ret .= "</" . $results[1][$i] . ">";
    }
    return $ret;
}

function update()
{
    function_exists('clearcache') || include 'cachefunc.php';
    clearcache(false);
}

function translate_xmldata($data)
{
    return strtr($data, array("&" => "&amp;", "<" => "&lt;", ">" => "&gt;"));
}


### use the transaction now.
function unlock()
{
    global $db;
    // Déverrouille toutes les tables verrouillées
    // fonction lock_write()
    if (!defined("DONTUSELOCKTABLES") || !DONTUSELOCKTABLES) {
        $db->execute(lq("UNLOCK TABLES")) or trigger_error("SQL ERROR :<br />" . $GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
    }
}


function lock_write()
{
    global $db;
    // Verrouille toutes les tables MySQL en écriture
    $list = func_get_args();
    if (!defined("DONTUSELOCKTABLES") || !DONTUSELOCKTABLES)
        $db->execute(lq("LOCK TABLES #_MTP_" . join(" WRITE ," . "#_MTP_", $list) . " WRITE")) or trigger_error("SQL ERROR :<br />" . $GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
}

function getoption($name)
{
    static $options_cache;
    if (!$name) return;
    if (!isset($options_cache)) {
        $cache = getCacheObject();
        $options_cache = $cache->get('options_cache');

        if ($options_cache) {
            eval("?>" . $options_cache);
        } else {
            function_exists('cacheOptionsInFile') || include('optionfunc.php');
            $options_cache = cacheOptionsInFile('options_cache');
        }
    }
    if (is_array($name)) {
        foreach ($name as $n) {
            if (isset($options_cache[$n])) $ret[$n] = stripslashes($options_cache[$n]);
            else $ret[$n] = null;
        }
        return ($ret);
    } else {
        if ($options_cache[$name]) // cached ?
            return stripslashes($options_cache[$name]);
        $critere = "name='$name'";
    }
}

function getlodeltext($name, $group, &$id, &$contents, &$status, $lang = -1)
{
    if ($group == "") {
        if ($name[0] != '[' && $name[1] != '@') return array(0, $name);
        $dotpos = strpos($name, ".");
        if ($dotpos) {
            $group = substr($name, 1, $dotpos);
            $name = substr($name, $dotpos + 1, -1);
        } else {
            trigger_error("ERROR: unknow group for getlodeltext", E_USER_ERROR);
        }
    }
    if ($lang == -1 || '' == $lang) $lang = C::get('sitelang');
    if (!$lang) $lang = C::get('installlang', 'cfg'); // if no lang is specified choose the default installation language
    defined('INC_CONNECT') || include 'connect.php'; // init DB if not already done
    global $db;

    if ($group != "site") {
        $prefix = lq("#_MTP_");
    } else {
        $prefix = $GLOBALS['tableprefix'];
    }

    $critere = C::get('visitor', 'lodeluser') ? "" : "AND status>0";
    $logic = false;
    $query = "SELECT id,contents,status
			FROM {$prefix}texts
			WHERE name=" . $db->quote($name) . " AND textgroup=" . $db->quote($group) . " AND (lang=" . $db->quote($lang) . " OR lang='') {$critere}
			ORDER BY lang DESC";
    $text = false;
    $create = false;
    do {
        $arr = $db->Execute($query);
        if ($arr === false) trigger_error("SQL ERROR :<br />" . $GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

        $text = $arr->fields;
        if (!$text) {
            if ($create || !C::get('admin', 'lodeluser')) {
                $arr->Close();
                break;
            }
            // create the textfield
            if (!$logic) $logic = Logic::getLogic("texts");
            $logic->createTexts($name, $group);
            $create = true;
        }
        $arr->Close();
    } while (!$text);

    $id = $text['id'];
    $contents = $text['contents'];
    $status = $text['status'];
    if (!$contents && (C::get('visitor', 'lodeluser') || C::get('debugMode', 'cfg'))) $contents = "@" . $name;
}

function getlodeltextcontents($name, $group = "", $lang = -1)
{
    if ($lang == -1) $lang = C::get('sitelang');
    if (isset($GLOBALS['langcache'][$lang][$group . "." . $name])) {
        return $GLOBALS['langcache'][$lang][$group . "." . $name];
    } else {
        #echo "name=$name,group=$group,id=$id,contents=$contents,status=$status,lang=$lang<br />";
        getlodeltext($name, $group, $id, $contents, $status, $lang);
        return $contents;
    }
}

function makeurlwithid($id, $base = 'index')
{

    if (is_numeric($base)) {
        $t = $id;
        $id = $base;
        $base = $t;
    } // exchange
    if (defined('URI')) {
        $uri = URI;
    } else {
        // compat 0.7
        if (C::get('idagauche', 'cfg')) {
            $uri = 'leftid';
        } else {
	    $uri = '';
	}
    }

    $id = trim($id);

    if (!preg_match('/^(\w+)\.(\d+)$/', $id, $m))
        $id = (int)$id;

    /*$class = $GLOBALS['db']->getOne(lq("SELECT class FROM #_TP_objects WHERE id='$id'"));
        if ($GLOBALS['db']->errorno()) {
            trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
        }
    if($class != 'entities')
        $uri = '';*/
    switch ($uri) {
        case 'leftid':
            $path = $base . $id . '.' . C::get('extensionscripts', 'cfg');
            break;
        case 'singleid':
            $path = ('index' != $base ? $base . $id : $id);
            break;
        //fabrique des urls type index.php?/rubrique/mon-titre
        case 'path':
            $path = getPath($id, 'path');
            break;
        case 'querystring':
            $path = getPath($id, 'querystring');
            break;
        default:
            $path = $base . '.' . C::get('extensionscripts', 'cfg') . '?id=' . $id;
            break;
    }

    if (C::get('mobile')) {
        $path .= (false !== strpos($path, '?') ? '&amp;mobile=1' : '?mobile=1');
    } elseif (C::get('nomobile')) {
        $path .= (false !== strpos($path, '?') ? '&amp;nomobile=1' : '?nomobile=1');
    }

    if (C::get('view.format') == 'embed') {
        $path .= (false !== strpos($path, '?') ? '&amp;format=embed' : '?format=embed');
    }

    if (C::get('view.format') == 'reader') {
        $path .= (false !== strpos($path, '?') ? '&amp;format=reader' : '?format=reader');
    }

    return $path;
}

function makeurlwithfile($id)
{
    $url = makeurlwithid($id);
    $url .= (false === strpos($url, '?')) ? '?file=1' : '&file=1';
    return $url;
}

/**
 * retourne le chemin complet vers une entitée *
 * @param integer $id identifiant numérique de l'entitée *
 * @param string $urltype le type d'url utilisée(path,querystring)
 * @return string le chemin
 * @since fonction ajoutée en 0.8
 */
function getPath($id, $urltype, $base = 'index')
{
    $urltype = 'querystring'; //la version actuelle de lodel ne gère que le type path
// 	if($urltype!='path' && $urltype!='querystring') {
// 		return;
// 	}
    $id = (int)$id;
    $result = $GLOBALS['db']->execute(lq("SELECT identifier FROM #_TP_entities INNER JOIN #_TP_relations ON id1=id WHERE id2='$id' ORDER BY degree DESC")) or trigger_error("SQL ERROR :<br />" . $GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
    while (!$result->EOF) {
        $path .= '/' . $result->fields['identifier'];
        $result->MoveNext();
    }
    $row = $GLOBALS['db']->getRow(lq("SELECT identifier FROM #_TP_entities WHERE id='$id'"));
    if ($GLOBALS['db']->errorno()) {
        trigger_error("SQL ERROR :<br />" . $GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
    }
    $path .= "/$id-" . $row['identifier'];
    if ($urltype == 'path') {
        return $base . '.' . C::get('extensionscripts', 'cfg') . $path;
    }
    return "$base." . C::get('extensionscripts', 'cfg') . "?$path";
}


/**
 * sent the header and the file for downloading
 *
 * @param     string   name of the real file.
 * @param     string   name to send to the browser.
 *
 */
function download($filename, $originalname = "", $contents = "")
{
    if (!$originalname) $originalname = $filename;
    $originalname = preg_replace("/.*\//", "", $originalname);
    $ext = substr($originalname, strrpos($originalname, ".") + 1);
    $size = $filename ? filesize($filename) : strlen($contents);
    $mime = getMimeType($ext);
    get_PMA_define();
    $mimetype = array(
        'application/msword',
	'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/html',
        'text/html',
        'image/jpeg',
        'image/gif',
        'image/png',
        'application/pdf',
        'text/plain',
        'application/vnd.ms-excel',
        'video/avi',
        'audio/x-wav',
        'audio/mpeg',
        'video/mp4',
        'video/x-flv',
        'video/quicktime',
        'video/mpeg',
        'application/ogg',
    );
    if (in_array($mime, $mimetype) && !(PMA_USR_BROWSER_AGENT == 'IE' && $ext == "pdf" && PMA_USR_OS != "Mac")) {
        $disposition = "inline";
    } else {
        $mime = "application/force-download";
        $disposition = "attachment";
    }
    if ($filename) {
        $fp = fopen($filename, "rb");
        if (!$fp) trigger_error("ERROR: The file \"$filename\" is not readable", E_USER_ERROR);
    }
    // fix for IE catching or PHP bug issue
    header("Pragma: public");
    header("Expires: 0"); // set expiration time
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

    #  header("Cache-Control: ");// leave blank to avoid IE errors (from on uk.php.net)
    #  header("Pragma: ");// leave blank to avoid IE errors (from on uk.php.net)

    header("Content-type: $mime\n");
    header("Content-transfer-encoding: binary\n");
    header("Content-length: " . $size . "\n");
    header("Content-disposition: $disposition; filename=\"$originalname\"\n");
    //  sleep(1); // don't know why... (from on uk.php.net)
    if ($filename) {
        fpassthru($fp);
    } else {
        echo $contents;
    }
}


// taken from phpMyAdmin 2.5.4

function get_PMA_define()
{

// Determines platform (OS), browser and version of the user
// Based on a phpBuilder article:
//   see http://www.phpbuilder.net/columns/tim20000821.php

    // loic1 - 2001/25/11: use the new globals arrays defined with
    // php 4.1+
    if (!empty($_SERVER['HTTP_USER_AGENT'])) {
        $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];
    }

    #} else if (!empty($HTTP_SERVER_VARS['HTTP_USER_AGENT'])) {
    #    $HTTP_USER_AGENT = $HTTP_SERVER_VARS['HTTP_USER_AGENT'];
    #} else if (!isset($HTTP_USER_AGENT)) {
    #    $HTTP_USER_AGENT = '';
    #}

    // 1. Platform
    if (strstr($HTTP_USER_AGENT, 'Win')) {
        defined('PMA_USR_OS') || define('PMA_USR_OS', 'Win');
    } else if (strstr($HTTP_USER_AGENT, 'Mac')) {
        defined('PMA_USR_OS') || define('PMA_USR_OS', 'Mac');
    } else if (strstr($HTTP_USER_AGENT, 'Linux')) {
        defined('PMA_USR_OS') || define('PMA_USR_OS', 'Linux');
    } else if (strstr($HTTP_USER_AGENT, 'Unix')) {
        defined('PMA_USR_OS') || define('PMA_USR_OS', 'Unix');
    } else if (strstr($HTTP_USER_AGENT, 'OS/2')) {
        defined('PMA_USR_OS') || define('PMA_USR_OS', 'OS/2');
    } else {
        defined('PMA_USR_OS') || define('PMA_USR_OS', 'Other');
    }

    // 2. browser and version
    // (must check everything else before Mozilla)

    if (preg_match('#Opera(/| )([0-9].[0-9]{1,2})#', $HTTP_USER_AGENT, $log_version)) {
        defined('PMA_USR_BROWSER_VER') || define('PMA_USR_BROWSER_VER', $log_version[2]);
        defined('PMA_USR_BROWSER_AGENT') || define('PMA_USR_BROWSER_AGENT', 'OPERA');
    } else if (preg_match('#MSIE ([0-9].[0-9]{1,2})#', $HTTP_USER_AGENT, $log_version)) {
        defined('PMA_USR_BROWSER_VER') || define('PMA_USR_BROWSER_VER', $log_version[1]);
        defined('PMA_USR_BROWSER_AGENT') || define('PMA_USR_BROWSER_AGENT', 'IE');
    } else if (preg_match('#OmniWeb/([0-9].[0-9]{1,2})#', $HTTP_USER_AGENT, $log_version)) {
        defined('PMA_USR_BROWSER_VER') || define('PMA_USR_BROWSER_VER', $log_version[1]);
        defined('PMA_USR_BROWSER_AGENT') || define('PMA_USR_BROWSER_AGENT', 'OMNIWEB');
        //} else if (ereg('Konqueror/([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
        // Konqueror 2.2.2 says Konqueror/2.2.2
        // Konqueror 3.0.3 says Konqueror/3
    } else if (preg_match('#(Konqueror/)(.*)(;)#', $HTTP_USER_AGENT, $log_version)) {
        defined('PMA_USR_BROWSER_VER') || define('PMA_USR_BROWSER_VER', $log_version[2]);
        defined('PMA_USR_BROWSER_AGENT') || define('PMA_USR_BROWSER_AGENT', 'KONQUEROR');
    } else if (preg_match('#Mozilla/([0-9].[0-9]{1,2})#', $HTTP_USER_AGENT, $log_version)
        && preg_match('#Safari/([0-9]*)#', $HTTP_USER_AGENT, $log_version2)
    ) {
        defined('PMA_USR_BROWSER_VER') || define('PMA_USR_BROWSER_VER', $log_version[1] . '.' . $log_version2[1]);
        defined('PMA_USR_BROWSER_AGENT') || define('PMA_USR_BROWSER_AGENT', 'SAFARI');
    } else if (preg_match('#Mozilla/([0-9].[0-9]{1,2})#', $HTTP_USER_AGENT, $log_version)) {
        defined('PMA_USR_BROWSER_VER') || define('PMA_USR_BROWSER_VER', $log_version[1]);
        defined('PMA_USR_BROWSER_AGENT') || define('PMA_USR_BROWSER_AGENT', 'MOZILLA');
    } else {
        defined('PMA_USR_BROWSER_VER') || define('PMA_USR_BROWSER_VER', 0);
        defined('PMA_USR_BROWSER_AGENT') || define('PMA_USR_BROWSER_AGENT', 'OTHER');
    }
}

/**
 * Save the file or image files associated with a entites (annex file). Check it is a valid image.
 *
 * @param    dir    If $dir is numeric it is the id of the entites. In the other case, $dir should be a temporary directory.
 * @param docAnnexe boolean = true if the file is saved in the directory "docannexe", else false
 *
 */
function save_file($type, $dir, $file, $filename, $uploaded, $move, &$error, $docAnnexe = true)
{
    if ($type != 'file' && $type != 'image') {
        trigger_error("ERROR: type is not a valid file type", E_USER_ERROR);
    }
    if (!$dir) {
        trigger_error("Internal error in saveuploadedfile dir=$dir", E_USER_ERROR);
    }
    if (is_numeric($dir)) {
        $dir = "docannexe/$type/$dir";
    }
    if (!$file) {
        trigger_error("ERROR: save_file file is not set", E_USER_ERROR);
    }
    if ($type == 'image') { // check this is really an image
        if ($uploaded) { // it must be first moved if not it cause problem on some provider where some directories are forbidden
            $tmpdir = tmpdir();
            $newfile = $tmpdir . "/" . basename($file);
            if ($file != $newfile && !move_uploaded_file($file, $newfile)) {
                trigger_error("ERROR: a problem occurs while moving the uploaded file from $file to $newfile.", E_USER_ERROR);
            }
            $file = $newfile;
        }
        if (!filesize($file)) {
            $error = 'readerror';
            return;
        }
        $info = getimagesize($file);
        if (!is_array($info)) {
            $error = 'imageformat';
            return;
        }
        $exts = array("gif", "jpg", "png", "swf", "psd", "bmp", "tiff", "tiff", "jpc", "jp2", "jpx", "jb2", "swc", "iff");
        $ext = $exts[$info[2] - 1];
        if (!$ext) { // si l'extension n'est pas bonne
            $error = 'imageformat';
            return;
        }
    }

    if ($docAnnexe === true) {
        checkdocannexedir($dir);
    }

    if ($type == 'image') {
        $filename = preg_replace("/\.\w+$/", "", basename($filename)); // take only the name, remove the extensio
        $dest = $dir . '/' . $filename . '.' . $ext;
    } else {
        $filename = rewriteFilename($filename);
        $dest = $dir . '/' . basename($filename);
    }
    if (defined("SITEROOT")) {
        $dest = SITEROOT . $dest;
    }
    if (!copy($file, $dest)) {
        trigger_error("ERROR: a problem occurs while moving the file.", E_USER_ERROR);
    }
    // and try to delete
    if ($move) {
        @unlink($file);
    }
    @chmod($dest, 0666 & octdec(C::get('filemask', 'cfg')));
    return $dest;
}

/**
 * Vérifie que le répertoire $dir, un répertoire de docannexe existe. Dans le cas
 * contraire le crée
 *
 * @param string $dir le nom du répertoire
 */
function checkdocannexedir($dir)
{
    if (defined("SITEROOT")) { //si le siteroot est défini
        $rep = SITEROOT . $dir;
        if (!file_exists(SITEROOT . "docannexe/image")) { // il n'y a pas de répertoire docannexe/image dans le siteroot, on essaye de le créer
            if (!@mkdir(SITEROOT . "docannexe/image", 0777 & octdec(C::get('filemask', 'cfg')), true)) {
                trigger_error("ERROR: impossible to create the directory \"docannexe/image\"", E_USER_ERROR); //peut rien faire
            }
            @chmod($GLOBALS['ADODB_CACHE_DIR'], 0777 & octdec(C::get('filemask', 'cfg')));
        }
    } else {
        $rep = $dir;
        if (!file_exists("docannexe/image")) {
            if (!@mkdir("docannexe/image", 0777 & octdec(C::get('filemask', 'cfg')), true)) {
                trigger_error("ERROR: impossible to create the directory \"docannexe\"", E_USER_ERROR);
            }
            @chmod($GLOBALS['ADODB_CACHE_DIR'], 0777 & octdec(C::get('filemask', 'cfg')));
        }
    }
    if (defined("SITEROOT")) { //si le siteroot est défini
        if (!file_exists(SITEROOT . "docannexe/file")) { //il n'y a pas de répertoire docannexe/image dans le siteroot, on essaye de le créer
            if (!@mkdir(SITEROOT . "docannexe/file", 0777 & octdec(C::get('filemask', 'cfg')), true)) {
                trigger_error("ERROR: impossible to create the directory \"docannexe\"", E_USER_ERROR); //peut rien faire
            }
            @chmod($GLOBALS['ADODB_CACHE_DIR'], 0777 & octdec(C::get('filemask', 'cfg')));
        }
    } else {
        if (!file_exists("docannexe/file")) {
            if (!@mkdir("docannexe/file", 0777 & octdec(C::get('filemask', 'cfg')), true)) {
                trigger_error("ERROR: impossible to create the directory \"docannexe\"", E_USER_ERROR);
            }
            @chmod($GLOBALS['ADODB_CACHE_DIR'], 0777 & octdec(C::get('filemask', 'cfg')));
        }
    }

    if (!file_exists($rep)) {
        if (!@mkdir($rep, 0777 & octdec(C::get('filemask', 'cfg')))) {
            trigger_error("ERROR: impossible to create the directory \"$rep\"", E_USER_ERROR);
        }
        @chmod($rep, 0777 & octdec(C::get('filemask', 'cfg')));
        writefile($rep . '/index.html', '');
    }
    // pseudo-sécurité. faudrait trouver mieux, ptetre ajouter directement le répertoire docannexe dans la distrib avec un .htaccess
    $htaccess = defined('SITEROOT') ? SITEROOT . "docannexe/file/.htaccess" : "docannexe/file/.htaccess";
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "deny from all");
        @chmod($htaccess, 0640);
    }
    // compatibilité 0.7
    $htaccess07 = defined('SITEROOT') ? SITEROOT . "docannexe/fichier/" : "docannexe/fichier/";
    if (file_exists($htaccess07) && !file_exists($htaccess07 . '.htaccess')) {
        file_put_contents($htaccess07 . '.htaccess', "deny from all");
        @chmod($htaccess07 . '.htaccess', 0640);
    }
}

function tmpdir($name = '')
{
    $tmpdir = '';
    if (defined("TMPDIR") && '' !== (string)TMPDIR)
        $tmpdir = TMPDIR;
    elseif ($tmpdir = C::get('tmpoutdir', 'cfg'))
        $tmpdir .= DIRECTORY_SEPARATOR . $name;
    else
        $tmpdir = cache_get_path('tmp' . DIRECTORY_SEPARATOR . $name);

    @mkdir($tmpdir, 0750, true);

    return $tmpdir . DIRECTORY_SEPARATOR;
}

function myhtmlentities($text)
{
    return str_replace(array("&", "<", ">", "\""), array("&amp;", "&lt;", "&gt;", "&quot;"), $text);
}


/**
 *
 * Function to solve the UTF8 poor support in MySQL
 * This function should be i18n in the futur to support more language
 */
/**
 * Fonction qui indique si une chaine est en utf-8 ou non
 *
 * Cette fonction est inspirée de Dotclear et de
 * http://w3.org/International/questions/qa-forms-utf-8.html.
 *
 * @param string $string la chaîne à tester
 * @return le résultat de la fonction preg_match c'est-a-dire false si la chaine n'est pas en
 * UTF8
 */
function isUTF8($string)
{
    // From http://w3.org/International/questions/qa-forms-utf-8.html
    return preg_match('%^(?:
			[\x09\x0A\x0D\x20-\x7E]			# ASCII
		| [\xC2-\xDF][\x80-\xBF]				# non-overlong 2-byte
		| \xE0[\xA0-\xBF][\x80-\xBF]			# excluding overlongs
		| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}	# straight 3-byte
		| \xED[\x80-\x9F][\x80-\xBF]			# excluding surrogates
		| \xF0[\x90-\xBF][\x80-\xBF]{2}		# planes 1-3
		| [\xF1-\xF3][\x80-\xBF]{3}			# planes 4-15
		| \xF4[\x80-\x8F][\x80-\xBF]{2}		# plane 16
	)*$%xs', $string);
}

/**
 * Transforme une chaine de caractère UTF8 en minuscules désaccentuées
 *
 * Cette fonction prends en entrée une chaîne en UTF8 et donne en sortie une chaîne
 * o les accents ont été remplacés par leur équivalent désaccentué. De plus les caractères
 * sont mis en minuscules et les espaces en début et fin de chaine sont enlevés.
 *
 * Cette fonction est utilisée pour les entrees d'index ainsi que dans le moteur de recherche
 * et pour le calcul des identifiants littéraux.
 *
 * @param string $text le texte à passer en entrée
 * @return le texte transformé en minuscule
 */
function makeSortKey($text)
{
    $text = strip_tags($text);
    //remplacement des caractères accentues en UTF8
    $replacement = array(
        chr(194) . chr(165) => 'Y', chr(194) . chr(181) => 'u',

        chr(195) . chr(128) => 'A',
        chr(195) . chr(129) => 'A', chr(195) . chr(130) => 'A',
        chr(195) . chr(131) => 'A', chr(195) . chr(132) => 'A',
        chr(195) . chr(133) => 'A', chr(195) . chr(134) => 'AE',
        chr(195) . chr(135) => 'C', chr(195) . chr(136) => 'E',
        chr(195) . chr(137) => 'E', chr(195) . chr(138) => 'E',
        chr(195) . chr(139) => 'E', chr(195) . chr(140) => 'I',
        chr(195) . chr(141) => 'I', chr(195) . chr(142) => 'I',
        chr(195) . chr(143) => 'I', chr(195) . chr(144) => 'D',
        chr(195) . chr(145) => 'N', chr(195) . chr(146) => 'O',
        chr(195) . chr(147) => 'O', chr(195) . chr(148) => 'O',
        chr(195) . chr(149) => 'O', chr(195) . chr(150) => 'O',
        chr(195) . chr(152) => 'O', chr(195) . chr(153) => 'U',
        chr(195) . chr(154) => 'U', chr(195) . chr(155) => 'U',
        chr(195) . chr(156) => 'U', chr(195) . chr(157) => 'Y',
        chr(195) . chr(159) => 'SS', chr(195) . chr(160) => 'a',
        chr(195) . chr(161) => 'a', chr(195) . chr(162) => 'a',
        chr(195) . chr(163) => 'a', chr(195) . chr(164) => 'a',
        chr(195) . chr(165) => 'a', chr(195) . chr(166) => 'ae',
        chr(195) . chr(167) => 'c', chr(195) . chr(168) => 'e',
        chr(195) . chr(169) => 'e', chr(195) . chr(170) => 'e',
        chr(195) . chr(171) => 'e', chr(195) . chr(172) => 'i',
        chr(195) . chr(173) => 'i', chr(195) . chr(174) => 'i',
        chr(195) . chr(175) => 'i', chr(195) . chr(176) => 'o',
        chr(195) . chr(177) => 'n', chr(195) . chr(178) => 'o',
        chr(195) . chr(179) => 'o', chr(195) . chr(180) => 'o',
        chr(195) . chr(181) => 'o', chr(195) . chr(182) => 'o',
        chr(195) . chr(184) => 'o', chr(195) . chr(185) => 'u',
        chr(195) . chr(186) => 'u', chr(195) . chr(187) => 'u',
        chr(195) . chr(188) => 'u', chr(195) . chr(189) => 'y',
        chr(195) . chr(191) => 'y',

        chr(196) . chr(128) => 'A', chr(196) . chr(129) => 'a',
        chr(196) . chr(130) => 'A', chr(196) . chr(131) => 'a',
        chr(196) . chr(132) => 'A', chr(196) . chr(133) => 'a',
        chr(196) . chr(134) => 'C', chr(196) . chr(135) => 'c',
        chr(196) . chr(136) => 'C', chr(196) . chr(137) => 'c',
        chr(196) . chr(138) => 'C', chr(196) . chr(139) => 'c',
        chr(196) . chr(140) => 'C', chr(196) . chr(141) => 'c',
        chr(196) . chr(142) => 'D', chr(196) . chr(143) => 'd',
        chr(196) . chr(144) => 'D', chr(196) . chr(145) => 'd',
        chr(196) . chr(146) => 'E', chr(196) . chr(147) => 'e',
        chr(196) . chr(148) => 'E', chr(196) . chr(149) => 'e',
        chr(196) . chr(150) => 'E', chr(196) . chr(151) => 'e',
        chr(196) . chr(152) => 'E', chr(196) . chr(153) => 'e',
        chr(196) . chr(154) => 'E', chr(196) . chr(155) => 'e',
        chr(196) . chr(156) => 'G', chr(196) . chr(157) => 'g',
        chr(196) . chr(158) => 'G', chr(196) . chr(159) => 'g',
        chr(196) . chr(160) => 'G', chr(196) . chr(161) => 'g',
        chr(196) . chr(162) => 'G', chr(196) . chr(163) => 'g',
        chr(196) . chr(164) => 'H', chr(196) . chr(165) => 'h',
        chr(196) . chr(166) => 'H', chr(196) . chr(167) => 'h',
        chr(196) . chr(168) => 'I', chr(196) . chr(169) => 'i',
        chr(196) . chr(170) => 'I', chr(196) . chr(171) => 'i',
        chr(196) . chr(172) => 'I', chr(196) . chr(173) => 'i',
        chr(196) . chr(174) => 'I', chr(196) . chr(175) => 'i',
        chr(196) . chr(176) => 'I', chr(196) . chr(177) => 'i',
        chr(196) . chr(178) => 'IJ', chr(196) . chr(179) => 'ij',
        chr(196) . chr(180) => 'J', chr(196) . chr(181) => 'j',
        chr(196) . chr(182) => 'K', chr(196) . chr(183) => 'k',
        chr(196) . chr(184) => 'K', chr(196) . chr(185) => 'L',
        chr(196) . chr(186) => 'l', chr(196) . chr(187) => 'L',
        chr(196) . chr(188) => 'l', chr(196) . chr(189) => 'L',
        chr(196) . chr(190) => 'l', chr(196) . chr(191) => 'L',

        chr(197) . chr(128) => 'l', chr(197) . chr(129) => 'L',
        chr(197) . chr(130) => 'l', chr(197) . chr(131) => 'N',
        chr(197) . chr(132) => 'n', chr(197) . chr(133) => 'N',
        chr(197) . chr(134) => 'n', chr(197) . chr(135) => 'N',
        chr(197) . chr(136) => 'n', chr(197) . chr(137) => 'n',
        chr(197) . chr(138) => 'N', chr(197) . chr(139) => 'n',
        chr(197) . chr(140) => 'O', chr(197) . chr(141) => 'o',
        chr(197) . chr(142) => 'O', chr(197) . chr(143) => 'o',
        chr(197) . chr(144) => 'O', chr(197) . chr(145) => 'o',
        chr(197) . chr(146) => 'OE', chr(197) . chr(147) => 'oe',
        chr(197) . chr(148) => 'R', chr(197) . chr(149) => 'r',
        chr(197) . chr(150) => 'R', chr(197) . chr(151) => 'r',
        chr(197) . chr(152) => 'R', chr(197) . chr(153) => 'r',
        chr(197) . chr(154) => 'S', chr(197) . chr(155) => 's',
        chr(197) . chr(156) => 'S', chr(197) . chr(157) => 's',
        chr(197) . chr(158) => 'S', chr(197) . chr(159) => 's',
        chr(197) . chr(160) => 'S', chr(197) . chr(161) => 's',
        chr(197) . chr(162) => 'T', chr(197) . chr(163) => 't',
        chr(197) . chr(164) => 'T', chr(197) . chr(165) => 't',
        chr(197) . chr(166) => 'T', chr(197) . chr(167) => 't',
        chr(197) . chr(168) => 'U', chr(197) . chr(169) => 'u',
        chr(197) . chr(170) => 'U', chr(197) . chr(171) => 'u',
        chr(197) . chr(172) => 'U', chr(197) . chr(173) => 'u',
        chr(197) . chr(174) => 'U', chr(197) . chr(175) => 'u',
        chr(197) . chr(176) => 'U', chr(197) . chr(177) => 'u',
        chr(197) . chr(178) => 'U', chr(197) . chr(179) => 'u',
        chr(197) . chr(180) => 'W', chr(197) . chr(181) => 'w',
        chr(197) . chr(182) => 'Y', chr(197) . chr(183) => 'y',
        chr(197) . chr(184) => 'Y', chr(197) . chr(185) => 'Z',
        chr(197) . chr(186) => 'z', chr(197) . chr(187) => 'Z',
        chr(197) . chr(188) => 'z', chr(197) . chr(189) => 'Z',
        chr(197) . chr(190) => 'z', chr(197) . chr(191) => 'z',

    );
    return trim(strtolower(strtr($text, $replacement)));
}

/**
 * rightonentity check if a user has the rights to perform an action on an entity
 * @param string $action the action to be performed : create, edit, delete,...
 * @param array $context the current context
 * @return boolean true if the user has the right, false ifnot
 */
function rightonentity($action, $context)
{
    if (C::get('admin', 'lodeluser')) return true;
    $context['idparent'] = isset($context['idparent']) ? $context['idparent'] : null;
    $context['id'] = isset($context['id']) ? $context['id'] : null;
    $context['status'] = isset($context['status']) ? $context['status'] : null;
    $context['usergroup'] = isset($context['usergroup']) ? $context['usergroup'] : C::get('usergroup', 'lodeluser');
    $context['iduser'] = isset($context['iduser']) ? $context['iduser'] : C::get('iduser', 'lodeluser');
    if (!empty($context['id']) && (empty($context['usergroup']) || empty($context['status']))) {
        // get the group, the status, and the parent
        $row = $GLOBALS['db']->getRow(lq("SELECT idparent,status,usergroup, iduser FROM #_TP_entities WHERE id='" . $context['id'] . "'"));
        if (!$row) trigger_error("ERROR: internal error in rightonentity", E_USER_ERROR);
        $context = array_merge($context, $row);
    }
    // groupright ?
    if (!empty($context['usergroup'])) {
        $groupright = in_array($context['usergroup'], explode(',', C::get('groups', 'lodeluser')));
        if (!$groupright) return false;
    }

    // only admin can delete at the base.
    $editorDelete = C::get('editor', 'lodeluser') && !empty($context['idparent']);
    $editorok = C::get('editor', 'lodeluser');
    // redactor are ok, only if they own the document and it is not protected.
    $redactorok = ($context['iduser'] == C::get('id', 'lodeluser') && C::get('redactor', 'lodeluser')) && $context['status'] < 8 && !empty($context['idparent']);

    switch ($action) {
        case 'create' :
            return ($editorok || (C::get('redactor', 'lodeluser') && $context['status'] < 8)); // &&  $context['id'];
            break;
        case 'delete' :
            return (abs($context['status']) < 8 && $editorDelete) || ($context['status'] < 0 && $redactorok);
            break;
        case 'edit':
        case 'advanced' :
            return $editorok || $redactorok;
            break;
        case 'move' :
        case 'changerank' :
        case 'publish' :
        case 'unpublish' :
        case 'protect' :
            return $editorok;
            break;
        case 'changestatus' :
            if ($context['status'] < 0) {
                return $editorok || $redactorok;
            } else {
                return $editorok;
            }
        default:
            if (C::get('visitor', 'lodeluser'))
                trigger_error("ERROR: unknown action \"$action\" in the loop \"rightonentity\"", E_USER_ERROR);
            return;
    }
}

//end of rightonentity function


/**
 * generate the SQL criteria depending whether ids is an array or a number
 */

function sql_in_array($ids)
{
    return is_array($ids) ? "IN ('" . join("','", $ids) . "')" : "='" . $ids . "'";
}

/**
 * DAO factory
 *
 */

function getDAO($table)
{
    return DAO::getDAO($table);
}

/**
 * generic DAO factory
 *
 */
function getGenericDAO($table, $idfield)
{
    return DAO::getGenericDao($table, $idfield);
}

function mystripslashes(&$var)
{
    if (is_array($var)) {
        array_walk($var, "mystripslashes");
        return $var;
    } else {
        return $var = stripslashes($var);
    }
}

/**
 * Récupération des champs génériques dc.* associés aux entités
 *
 * @param integer $id identifiant numéique de l'entité dont on veut récupérer un champ dc
 * @param string $dcfield le nom du champ à récupérer (sans le dc.devant). Ex : .'description' pour 'dc.description'
 * @return le contenu du champ passé dans le paramètre $dcfield
 */
function get_dc_fields($id, $dcfield)
{
    $dcfield = 'dc.' . $dcfield;
    global $db;
    $id = (int)$id;
    if ($result = $db->execute(lq("SELECT #_TP_entities.id, #_TP_types.class, #_TP_tablefields.name, #_TP_tablefields.g_name
	FROM #_TP_entities, #_TP_types, #_TP_tablefields
  	WHERE (#_TP_tablefields.g_name = '$dcfield')
  	AND #_TP_tablefields.class = #_TP_types.class
  	AND #_TP_entities.idtype = #_TP_types.id
  	AND #_TP_entities.id = $id")
    )
    ) {
        if ($row = $result->fields) {
            $id = $row['id'];
            $id_class_fields[$id]['class'] = $row['class'];
            $id_class_fields[$id][$row['g_name']] = $row['name'];

            if (!empty($id_class_fields[$id][$dcfield])) {
                $class_table = "#_TP_" . $id_class_fields[$id]['class'];
                $field = $id_class_fields[$id][$dcfield];
                $result = $db->getOne(lq("SELECT $field FROM $class_table WHERE identity = '$id'"));
                if ($result === false) {
                    trigger_error("SQL ERROR :<br />" . $GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
                }
            }
            return $result;
        } else return false;
    } else return false;
}

// Tente de recupérer la liste des locales du système dans un tableau
function list_system_locales()
{
    if (exec('locale -a', $arr)) {
        return $arr;
    } else {
        return FALSE;
    }
}

/**
 * Récupère les champs génériques définis pour une entité *
 * Stocke les champs génériques définis pour une entité dans un sous tableau de $context : generic
 *
 * @param array $context le contexte passé  par référence
 */
function getgenericfields(&$context)
{
    global $db;

    if (empty($context['class']) || empty($context['id'])) return $context;

    #print_r($context);
    $generic = array();
    $values = array();
    $fields = '';

    $sql = "SELECT name,g_name, defaultvalue
			FROM {$GLOBALS['tp']}tablefields
			WHERE class='" . $context['class'] . "' AND g_name!=''";
    $row = $db->getArray($sql);
    #print_r($row);

    foreach ($row as $elem) {
        $fields .= $elem['name'] . ',';
        $generic[$elem['name']] = $elem['g_name'];
    }

    $context['id'] = (int)$context['id'];
    //Retrouve les valeurs de $fields
    $sql = "SELECT " . substr($fields, 0, -1) . '
			FROM ' . $GLOBALS['tp'] . $context['class'] . "
			WHERE identity='" . $context['id'] . "'";
    #echo "sql=$sql";
    $row = $db->getRow($sql);
    foreach ($row as $key => $value) {
        $values[$key] = $value;
    }

    if (!isset($context['generic'])) $context['generic'] = array();
    //Contruit le tableau des champs génériques avec leur valeur
    foreach ($generic as $name => $g_name) {
        $g_name = str_replace('.', '_', $g_name);
        $context['generic'][$g_name] = isset($values[$name]) ? $values[$name] : '';
    }

    unset($fields);
    unset($values);
    unset($generic);
    #print_r($context['generic']);exit;

    // -- Traitement des indexs --
    //Récupère maintenant les valeurs des champs génériques des entrées d'index associés et des personnes associées
    $sql = "SELECT e.type,e.g_type, e.class
               FROM {$GLOBALS['tp']}entrytypes as e,
               {$GLOBALS['tp']}tablefields as t
               WHERE t.class='" . $context['class'] . "' AND t.name = e.type AND e.g_type!=''";
    #echo "sql=$sql";exit;
    $row = $db->getArray($sql);
    $fields = array();
    foreach ($row as $elem) {
        $fields[] = $elem['type'];
        $generic[$elem['type']] = $elem['g_type'];
    }
    //Retrouve les valeurs des entrées en utilisant le g_name de la table entries
    if (!empty($fields)) {
        $sql = "SELECT e.g_name, et.type
                  FROM {$GLOBALS['tp']}entries as e,
                  {$GLOBALS['tp']}relations as r,
                  {$GLOBALS['tp']}entrytypes as et
                  WHERE et.id=e.idtype AND e.id=r.id2 AND r.id1='" . $context['id'] . "'
                  AND et.type IN('" . join("','", $fields) . "')";
        #echo "sql=$sql";
        $array = $db->getArray($sql);
        foreach ($array as $row) {
            if (!empty($generic[$row['type']])) {
                $cle = str_replace('.', '_', $generic[$row['type']]);
                $context['generic'][$cle][] = $row['g_name'];
            }
        }
    }

    // -- Traitement des personnes --
    unset($fields);
    unset($generic);
    //Récupère maintenant les valeurs des champs génériques des entrées d'index associées et des personnes associées
    $sql = "SELECT e.type,e.g_type, e.class
               FROM {$GLOBALS['tp']}persontypes as e,
               {$GLOBALS['tp']}tablefields as t
               WHERE t.class='" . $context['class'] . "' AND t.name = e.type AND e.g_type!=''";
    #echo "sql=$sql";
    $row = $db->getArray($sql);
    if (!empty($row)) {
        $fields = array();
        foreach ($row as $elem) {
            $fields[] = $elem['type'];
            $generic[$elem['type']] = $elem['g_type'];
        }
        if (count($fields) > 0) {
            //Retrouve les valeurs des entrées en utilisant le g_name de la table entries
            $sql = "SELECT e.g_firstname, e.g_familyname, et.type FROM
					{$GLOBALS['tp']}persons as e,
					{$GLOBALS['tp']}relations as r,
					{$GLOBALS['tp']}persontypes as et
					WHERE et.id=e.idtype AND e.id=r.id2 AND r.id1='" . $context['id'] . "'
					AND et.type IN('" . join("','", $fields) . "')";
            #echo "sql=$sql";
            $array = $db->getArray($sql);
            foreach ($array as $row) {
                if ($cle = $generic[$row['type']]) {
                    $cle = str_replace('.', '_', $cle);
                    $context['generic'][$cle][] = $row['g_firstname'] . ' ' . $row['g_familyname'];
                }
            }
        }
    }


    return $context; // pas nécessaire le context est passé par référence
}

function rewriteFilename($string)
{
    if (isUTF8($string)) {
        $string = preg_replace('/[^\w.-\/]+/', '_', makeSortKey($string));
    } else {
        $string = strip_tags($string);
        $string = strtolower(htmlentities($string));
        $string = preg_replace("/&(.)(uml);/", "$1e", $string);
        $string = preg_replace("/&(.)(acute|cedil|circ|ring|tilde|uml);/", "$1", $string);
        $string = preg_replace("([^\w.-]+)/", "_", html_entity_decode($string));
        $string = trim($string, "-");

    }
    return $string;
}

/**
 * Fonction permettant d'envoyer correctement un mail en html (utf8)
 *
 * @author Pierre-Alain Mignot
 * @param string $to destinataire
 * @param string $body corps du message
 * @param string $subject sujet du mail
 * @param string $fromaddress adresse de l'expéditeur
 * @param string $fromname nom de l'expediteur
 * @param array $docs pièces jointes
 * @param bool $isHTML body html ou texte brut
 * @param bool $toBcc envoie le mail en cachant les destinataires
 * @return boolean
 */
function send_mail($to, $body, $subject, $fromaddress, $fromname, array $docs = array(), $isHTML = true, $toBcc = false, $cc = '')
{
    $replace = array(
        "\xc2\x80" => "\xe2\x82\xac", /* EURO SIGN */
        "\xc2\x81" => "",
        "\xc2\x82" => "\xe2\x80\x9a", /* SINGLE LOW-9 QUOTATION MARK */
        "\xc2\x83" => "\xc6\x92", /* LATIN SMALL LETTER F WITH HOOK */
        "\xc2\x84" => "\xe2\x80\x9e", /* DOUBLE LOW-9 QUOTATION MARK */
        "\xc2\x85" => "\xe2\x80\xa6", /* HORIZONTAL ELLIPSIS */
        "\xc2\x86" => "\xe2\x80\xa0", /* DAGGER */
        "\xc2\x87" => "\xe2\x80\xa1", /* DOUBLE DAGGER */
        "\xc2\x88" => "\xcb\x86", /* MODIFIER LETTER CIRCUMFLEX ACCENT */
        "\xc2\x89" => "\xe2\x80\xb0", /* PER MILLE SIGN */
        "\xc2\x8a" => "\xc5\xa0", /* LATIN CAPITAL LETTER S WITH CARON */
        "\xc2\x8b" => "\xe2\x80\xb9", /* SINGLE LEFT-POINTING ANGLE QUOTATION */
        "\xc2\x8c" => "\xc5\x92", /* LATIN CAPITAL LIGATURE OE */
        "\xc2\x8d" => "",
        "\xc2\x8e" => "\xc5\xbd", /* LATIN CAPITAL LETTER Z WITH CARON */
        "\xc2\x8f" => "",
        "\xc2\x90" => "",
        "\xc2\x91" => "\xe2\x80\x98", /* LEFT SINGLE QUOTATION MARK */
        "\xc2\x92" => "\xe2\x80\x99", /* RIGHT SINGLE QUOTATION MARK */
        "\xc2\x93" => "\xe2\x80\x9c", /* LEFT DOUBLE QUOTATION MARK */
        "\xc2\x94" => "\xe2\x80\x9d", /* RIGHT DOUBLE QUOTATION MARK */
        "\xc2\x95" => "\xe2\x80\xa2", /* BULLET */
        "\xc2\x96" => "\xe2\x80\x93", /* EN DASH */
        "\xc2\x97" => "\xe2\x80\x94", /* EM DASH */
        "\xc2\x98" => "\xcb\x9c", /* SMALL TILDE */
        "\xc2\x99" => "\xe2\x84\xa2", /* TRADE MARK SIGN */
        "\xc2\x9a" => "\xc5\xa1", /* LATIN SMALL LETTER S WITH CARON */
        "\xc2\x9b" => "\xe2\x80\xba", /* SINGLE RIGHT-POINTING ANGLE QUOTATION*/
        "\xc2\x9c" => "\xc5\x93", /* LATIN SMALL LIGATURE OE */
        "\xc2\x9e" => "\xc5\xbe", /* LATIN SMALL LETTER Z WITH CARON */
        "\xc2\x9f" => "\xc5\xb8", /* LATIN CAPITAL LETTER Y WITH DIAERESIS*/
        '&#39;' => "'",
        "\x20\x13" => "-"
    );

    $body = wordwrap(strtr($body, $replace), 70);
    $subject = wordwrap(strtr($subject, $replace), 70);
    
    // On sauvegarde l'adresse fournie comme adresse from pour l'afficher dans le corps du mail. 
    // Auparavant elle était utilisée comme champ from dans le header du mail envoyé
    $user_address = $fromaddress;
    
    // On initialise maintenant l'adresse pour le champ from en utilisant l'adresse fournie par le fichier lodelconfig.php, si elle est bien fournie
    $fromaddress = C::get('fromaddress', 'cfg');
    if (empty($fromaddress)) {
        $fromaddress = $user_address;
    }
    // @TODO Arrêter d'utiliser PEAR !!
    $err = error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE); // PEAR packages compat

    if (!class_exists('Mail', TRUE) || !class_exists('Mail_mime', TRUE)) require_once 'vendor/autoload.php';
    $pear = new PEAR();

    $message = new Mail_mime("\n");

    if (preg_match_all('/<img\b[^>]+src="([^"]+)"[^>]*\/?>/', $body, $m)) {
        foreach ($m[1] as $img) {
            if (false !== strpos($img, 'http://')) continue;
            $r = $message->addHTMLImage($img, getMimeType(substr(strrchr($img, '.'), 1)));
            if ($pear->isError($r)) {
                return $r->getMessage();
            }
        }
    }

    if (!empty($docs)) {
        foreach ($docs as $doc) {
            $r = $message->addAttachment($doc, getMimeType(substr(strrchr($doc, '.'), 1)), basename($doc), true, 'base64');
            if ($pear->isError($r)) {
                return $r->getMessage();
            }
        }
    }

    // set headers
    $message->setSubject($subject);
    $message->setFrom("$fromname <$fromaddress>");

    // body creation
    $isHTML ? $message->setHTMLBody($body) : $message->setTxtBody($body);

    $aParam = array(
        "text_charset" => "UTF-8",
        "html_charset" => "UTF-8",
        "head_charset" => "UTF-8",
    );
    $body =& $message->get($aParam);

    if ($toBcc) {
        if (is_array($to))
            $to = implode(', ', $to);
        $headers = array('Bcc' => $to);
        $to = '';
        $headers =& $message->headers($headers, true);
    } else $headers =& $message->headers();

    if ($cc) {
        if (is_array($cc))
            $cc = implode(', ', $cc);
        $ccs = array('Cc' => $cc);
        $headers =& $message->headers($ccs, true);
    }

    unset($message);
    // send the mail
    $Mail = new Mail;
    $r = $Mail->factory('mail')->send($to, $headers, $body);
    $ret = true;
    if ($pear->isError($r)) {
        $ret = $r->getMessage();
    }
    error_reporting($err);
    return $ret;
}

function getMimeType($ext)
{
    switch (strtolower($ext)) {
        case 'gif':
            return 'image/gif';
        case 'tif':
            return 'image/tif';
        case 'png':
            return 'image/png';
        case 'jpg':
        case 'jpeg':
            return 'image/jpeg';
        case 'doc':
            return 'application/msword';
        case 'docx':
            return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        case 'xls':
            return 'application/vnd.ms-excel';
        case 'pdf':
            return 'application/pdf';
        case 'rtf':
            return 'application/rtf';
        case 'tar':
            return 'application/x-tar';
        case 'zip':
            return 'multipart/x-zip';
        case 'epub':
            return 'application/x-zip';
        case 'gzip':
            return 'multipart/x-gzip';
        case 'htm':
        case 'html':
            return 'text/html';
        case 'txt':
            return 'text/plain';
        case 'avi':
            return 'video/avi';
        case 'wav':
            return 'audio/x-wav';
        case 'mp3':
            return 'audio/mpeg';
        case 'm4v':
        case 'mp4':
            return 'video/mp4';
        case 'flv':
            return 'video/x-flv';
        case 'mov':
            return 'video/quicktime';
        case 'mpg':
        case 'mpeg':
            return 'video/mpeg';
        case 'ogg':
            return 'application/ogg';
        case 'bin':
        default:
            return 'application/octet-stream';
    }
}

function find_in_path($fichier)
{
    static $chemins = array();
    if (empty($chemins)) {
        $chemins[] = '';
        if ($siteroot = C::get('siteroot')) $chemins[] = $siteroot;
        $chemins[] = $sharedir = C::get('sharedir', 'cfg') . DIRECTORY_SEPARATOR;
        foreach ((array)C::get('view.base_rep') as $rep) {
            $chemins[] = $sharedir . 'plugins' . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . $rep . DIRECTORY_SEPARATOR;
        }
    }
    foreach ($chemins as $chemin) {
        if (file_exists($f = $chemin . $fichier))
            return $f;
    }
    return '';
}

function thumbnail($path, $width = null, $height = null)
{
    global $context;
    class_exists('Zebra_Image', TRUE) || require_once 'vendor/autoload.php';

    $image_infos = pathinfo($path);
    $cache_options = C::get('cacheOptions', 'cfg');

    if (!file_exists($path) && strpos($path, 'http') !== 0) return $path;

    $new_path = "docannexe/image/{$context['id']}/{$image_infos['filename']}-{$width}x{$height}.{$image_infos['extension']}";

    if (!file_exists(dirname($new_path))) mkdir(dirname($new_path));

    if (file_exists($new_path)
        && (
            (strpos($path, "http://") !== 0 && (filemtime($new_path) > filemtime($path)))
            || (file_exists($new_path) && filemtime($new_path) > time() - $cache_options['default_expire'])
        )
    ) return $new_path;

    $image = new Zebra_Image();
    $image->source_path = $path;
    $image->target_path = $new_path;

    $image->jpeg_quality = 100;
    $image->preserve_aspect_ratio = true;
    $image->enlarge_smaller_images = false;
    $image->preserve_time = true;

    $final_path = $path;
    if($image->resize($width, $height, ZEBRA_IMAGE_NOT_BOXED, -1))
        $final_path = $new_path;
    return $final_path;
}

if (!function_exists('detectlanguage')) {
    /**
     * Détection de la langue du navigateur
     *
     * Cette fonction détecte la langue du navigateur, et définit la langue à afficher si elle existe.
     * Si la langue du navigateur n'est pas présente dans les langues disponibles, alors on affiche
     * la langue principale du site.
     */
    function detectLanguage()
    {
        defined('INC_CONNECT') || include 'connect.php';
        global $db, $context;

        if (!isset($_COOKIE['language'])) {
            if (isset($context['options']))
                $language = $context['options']['metadonneessite']['langueprincipale'];
            $existinglangs = $db->GetCol(lq("SELECT lang FROM #_TP_translations"));

            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $acceptedlangs);

                foreach ($acceptedlangs[1] as $lang) {
                    $lang = substr($lang, 0, 2);
                    if (in_array($lang, $existinglangs)) {
                        $language = $lang;
                        break;
                    }
                }
            }

//              setcookie('language', $language, 0, C::get('urlroot', 'cfg'));
            C::set('sitelang', $language);
            C::set('lang', $language);
        }

    }
}

function myaddslashes(&$var)
{
    if (is_array($var)) {
        array_walk($var, "myaddslashes");
        return $var;
    } else {
        return $var = addslashes($var);
    }
}

function delete_files()
{
    foreach( func_get_args() as $file )
    {
        if ( strpos( dirname($file),  sys_get_temp_dir() ) === 0 )
        {
            unlink($file);
        }
    }
}

/**
 * Analyse une url et retourne le chemin en local qu'elle contient éventuellement
 * Cf. parse_url : élément 'path' du tableau retourné
 *
 * @param string $url
 * @return le chemin contenu dans l'URL
 */
function url_path($url)
{
    $url_parts = @parse_url($url);
    return $url_parts ? $url_parts['path'] : '';
}

function extract_files_from_zip($file, $destination, $filter = null, $file_list = null) {
    $zip = new ZipArchive();
    $zip->open($file);

    if(!$file_list){
        $file_list = get_zip_file_list($file, $filter);
    }

    $zip->extractTo($destination, $file_list);

    $zip->close();

    return $file_list;

}

function get_zip_file_list($file, $filter = null)
{
    $file_list = array();

    $zip = new ZipArchive();
    $zip->open($file);

    foreach(range(0, $zip->numFiles-1) as $i)
    {
        if(!$filter || preg_match($filter, $zip->getNameIndex($i)))
            $file_list[] = $zip->getNameIndex($i);
    }

    $zip->close();

    return $file_list;
}

function create_zip_from_file_list($zipfile, $filelist)
{
    $zip = new ZipArchive();
    $zip->open($zipfile, ZipArchive::CREATE);

    foreach($filelist as $path => $filename)
    {
        if (is_readable($path))
            $ok = $zip->addFile($path, $filename);
        else {
            $zip->close();
            return "$path is not readable";
        }
    }
    $ok = $zip->close();
    if (!$ok)
        return $zip->getStatusString();
    return true;
}

function glob_recursive($pattern, $flags = 0)
{
    $files = glob($pattern, $flags);

    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
    {
        $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
    }

    return $files;
}

// effacer un répertoire et toute son arborescence
function rmtree($rep)
{
    if(!file_exists($rep)) return;
    $rep = realpath($rep);
    $is_removable = false;
    foreach (array(realpath(SITEROOT."/docannexe/"), realpath(C::get('cacheDir', 'cfg')), realpath(C::get('tmpoutdir', 'cfg'))) as $removable)
        if (0 === strpos($rep, $removable))
            $is_removable = true;
    if (!$is_removable) {
        error_log("Interdiction d'effacer le répertoire $rep");
        return;
    }
    $fd = @opendir($rep);
    if (false === $fd) {
        error_log("Impossible d'ouvrir $rep");
        return;
    }
    while (($file = readdir($fd)) !== false) {
        if('.' === $file{0}) continue;
        $file = $rep. "/". $file;
        if (is_dir($file)) { //si c'est un répertoire on execute la fonction récursivement
            rmtree($file);
        } else {unlink($file);}
    }
    closedir($fd);

    rmdir($rep);
}

function is_recaptcha_v2_valid($code, $recaptche_privatekey, $ip = null) {
        if (empty($code)) {
                return false; // Si aucun code n'est entré, on ne cherche pas plus loin
        }
        $params = [
                'secret'    => $recaptche_privatekey,
                'response'  => $code
        ];
        if( $ip ){
                $params['remoteip'] = $ip;
        }
        $url = "https://www.google.com/recaptcha/api/siteverify?" . http_build_query($params);
        if (function_exists('curl_version')) {
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_HEADER, false);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_TIMEOUT, 1);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($curl);
        } else {
                // Si curl n'est pas dispo, file_get_contents
                $response = file_get_contents($url);
        }

        if (empty($response) || is_null($response)) {
                return false;
        }

        $json = json_decode($response);
        return $json->success;
}

define('INC_FUNC', 1);
// valeur de retour identifiant ce script
// utilisé dans l'installation pour vérifier l'accès aux scripts
return 568;
