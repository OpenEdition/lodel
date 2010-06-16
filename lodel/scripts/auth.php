<?php
/**
 * Fichier utilitaire pour gérer l'authentification de Lodel
 *
 * Ce script définit les méthodes de base pour l'authentification.
 * Il se charge aussi d'initialiser le context, ainsi que le jeu de caractère utilisé
 * *IMPORTANT* : ce script devrait être transformé en classe.
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
 * @author Sophie Malafosse
 * @author Pierre-Alain Mignot
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */

// les niveaux de droits
define("LEVEL_RESTRICTEDUSER", 5);
define("LEVEL_VISITOR", 10);
define("LEVEL_REDACTOR", 20);
define("LEVEL_EDITOR", 30);
define("LEVEL_ADMIN", 40);
define("LEVEL_ADMINLODEL", 128);

// les niveaux d'interface
define("INTERFACE_DEBUG",128);
define("INTERFACE_ADVANCED", 64);
define("INTERFACE_NORMAL", 32);
define("INTERFACE_SIMPLE", 16);


// les niveaux d'erreur à afficher
error_reporting(E_CORE_ERROR | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR);

/**
 * Gestion de l'authentification.
 *
 * Cette fonction gère l'authentification suivant le niveau de l'utilisateur.
 * Le niveau de l'utilsateur est un entier parmis :
 * LEVEL_RESTRICTEDUSER : 5, LEVEL_VISITOR : 10, LEVEL_REDACTOR : 20, LEVEL_EDITOR : 30, LEVEL_ADMIN : 40,
 * LEVEL_ADMINLODEL : 128.
 *
 * @param integer $level Le niveau de l'utilisateur. Par défaut 0
 */
function authenticate($level = 0, $mode = "")
{
	global $context, $lodeluser;
	global $home, $timeout, $sessionname, $site;
	global $db;

		$retour = "url_retour=". urlencode($_SERVER['REQUEST_URI']);
		do { // block de control
			$name = addslashes($_COOKIE[$sessionname]);
			if (!$name) {
				$users = $db->getArray(lq("SELECT id, ip FROM #_TP_restricted_users WHERE status > 0 AND ip != ''"));
				if(!$users) break;
				list($oct[0],$oct[1],$oct[2],$oct[3]) = sscanf($_SERVER['REMOTE_ADDR'], "%d.%d.%d.%d");
				
				foreach($users as $user) {
					$uuser = explode(' ', $user['ip']);
					foreach($uuser as $ip)
					{
						$ip = trim($ip);
						if(!$ip) continue 2;
						$octs = explode('.', $ip);
						foreach($octs as $k=>$octet) {
							if(!$octet) continue;
							if(FALSE !== strpos($octet, '['))
							{ // plage d'ip
								$o = explode('-', substr($octet, 1, strlen($octet)-2));
								if((int)$oct[$k] < (int)$o[0] || (int)$oct[$k] > (int)$o[1]) continue 2;
							}
							elseif((int)$octet !== (int)$oct[$k]) continue 2;
						}
						
						$row = $db->getRow(lq("SELECT id, username, passwd, lang FROM #_TP_restricted_users WHERE id = '{$user['id']}'"));
						if(!function_exists('check_expiration')) require 'loginfunc.php';
						$lodeluser['rights'] = LEVEL_RESTRICTEDUSER;
						$lodeluser['lang'] = $row['lang'] ? $row['lang'] : "fr";
						$lodeluser['id'] = $row['id'];
						$lodeluser['name'] = $context['login'] = $row['username'];
						$lodeluser['groups'] = '';
						$context['lodeluser'] = $lodeluser; // export info into the context
						unset($row);
						if(!check_expiration()) {
							$context['lodeluser'] = $lodeluser = array();
							break 3;
						}
						$name = open_session($context['login']);
						// si on arrive là c'est qu'on est bon, on s'éjecte du foreach
						break 2;
					}
				}
				if(!$name || $name == 'error_opensession') break;
			}
			usemaindb();
			
	
			if (!($row = $db->getRow(lq("SELECT id,iduser,site,context,expire,expire2,currenturl FROM #_MTP_session WHERE name='$name'")))) {
				break;
			}
		
			$GLOBALS['idsession'] = $idsession = $row['id'];
			$GLOBALS['session'] = $name;
	
			// verifie qu'on est dans le bon site
			if ($row['site'] != "tous les sites" && $row['site'] != $site) {
				break;
			}

			// passe les variables en global
			$lodeluser = unserialize($row['context']);
			if($level == (LEVEL_RESTRICTEDUSER | LEVEL_VISITOR)) {
				if($lodeluser['rights'] == 5)
					$level = LEVEL_RESTRICTEDUSER;
				else
					$level = LEVEL_VISITOR;
			}
	
			// verifie que la session n'est pas expiree
			$time = time();
			if ($row['expire'] < $time || $row['expire2'] < $time) {
				$login = $lodeluser = "";
				if($level == LEVEL_RESTRICTEDUSER) {
					$login = 'index.'.$GLOBALS['extensionscripts'];
				} else {
					if (file_exists('login.php'))	{
						$login = 'login.php';
					}	elseif (file_exists('lodel/edition/login.php'))	{
						$login = 'lodel/edition/login.php';
					}	else {
						break;
					}
				}
				header("location: $login?error_timeout=1&". $retour);
				exit;
			}
	
			if ($lodeluser['rights'] < $level) { //teste si l'utilisateur a les bons droits
				if($level == LEVEL_RESTRICTEDUSER) {
					$login = 'index.'.$GLOBALS['extensionscripts'];
				} else {
					$login = 'login.php';
				}
				$lodeluser = '';
				header("location: $login?error_privilege=1&". $retour);
				exit;
			}
	
			// verifie encore une fois au cas ou...
			if($level == LEVEL_RESTRICTEDUSER) {
				if ($lodeluser['rights'] < LEVEL_RESTRICTEDUSER && !$site) {
					break;
				}
			} else {
				if ($lodeluser['rights'] < LEVEL_ADMINLODEL && !$site) {
					break;
				}
			}
			
			$lodeluser['restricted_user'] = $lodeluser['rights'] >= LEVEL_RESTRICTEDUSER;
			$lodeluser['adminlodel'] = $lodeluser['rights'] >= LEVEL_ADMINLODEL;
			$lodeluser['admin']      = $lodeluser['rights'] >= LEVEL_ADMIN;
			$lodeluser['editor']     = $lodeluser['rights'] >= LEVEL_EDITOR;
			$lodeluser['redactor']   = $lodeluser['rights'] >= LEVEL_REDACTOR;
			$lodeluser['visitor']    = $lodeluser['rights'] >= LEVEL_VISITOR;
	
			$context['lodeluser']    = $lodeluser;
	
			if ($lodeluser['lang']) {
				$GLOBALS['lang'] = $lodeluser['lang'];
				$context['sitelang'] = $GLOBALS['lang'];
			}
			
			// clean the url - nettoyage de l'url
			$url = preg_replace("/[\?&]clearcache=\w+/", "", $_SERVER['REQUEST_URI']);
			if (get_magic_quotes_gpc()) {
				$url = stripslashes($url);
			}
			$myurl = $norecordurl ? "''" : $db->qstr($url);
			$expire = $timeout + $time;
			$db->execute(lq("UPDATE #_MTP_session SET expire='$expire',currenturl=$myurl WHERE name='$name'")) or die($db->errormsg());
			$context['clearcacheurl'] = mkurl($url, "clearcache=oui");
			usecurrentdb();
			return; // ok !!!
		}	while (0);
		if (function_exists("usecurrentdb")) {
			usecurrentdb();
		}
	
		// on est pas loggé : pour éviter des attaques par DOS on désactive le clearcache
		$_REQUEST['clearcache'] = false;
		$lodeluser = '';

		// exception
		if ($level == 0) {
			return; // les variables ne sont pas mises... on retourne
		}
		elseif ($mode == 'HTTP') {
			require_once 'loginHTTP.php';
			return;
		}
		elseif($level == LEVEL_RESTRICTEDUSER) {
			header("Location: index.".$GLOBALS['extensionscripts']);
			exit;
		}
		else {
			header("location: login.php?". $retour);
			exit;
		}
}

/**
 * Enregistre l'url pour la session donnée
 *
 * Cette fonction enregistre l'url courante (url du script) pour une session donnée.
 * Cela est utile pour la navigation dans l'interface
 */
function recordurl()
{
	global $idsession, $norecordurl, $db;
	if (!$norecordurl) {
		$db->execute(lq("INSERT INTO #_MTP_urlstack (idsession,url) SELECT id,currenturl FROM #_MTP_session WHERE id='". $idsession."' AND currenturl!=''")) or dberror();
	}
}

/**
 * ATTENTION! FONTION A DEPLACER DANS UN MEILLEUR SCRIPT
 * Rajoute des arguments dans une URL
 *
 * Cette fonction permet de rajouter un argument à une URL donnée. Elle teste si
 * l'URL contient déjà des arguments et ajoute alors l'argument supplémentaire en fin
 *
 * @param string $url l'URL
 * @param string $extraarg l'argument supplémentaire
 * @return string l'url avec l'argument supplémentaire
 */
function mkurl($url, $extraarg)
{
	if (strpos($url, "?") === FALSE) {
		return $url. "?". $extraarg;
	}	else {
		return $url. "&". $extraarg;
	}
}

/**
 * Récuperer les charsets acceptés par un navigateur
 *
 * Test des jeux de caractères supportés par un navigateur. Ce test est fait grâce aux
 * informations fournis par le navigateur dans la requête HTTP (utilisation de la superglobale
 * $_SERVER)
 *
 * @param string $charset le charset
 * 
 */
function getacceptedcharset($charset)
{
	// Détermine le charset a fournir au navigateur
	$browserversion = array ("opera" => 6, "netscape" => 4, "msie" => 4, "ie" => 4, "mozilla" => 3);
	if (!$charset)	{
		// Si ce n'est pas envoye par l'url ou par cookie, on recupere ce que demande le navigateur.
		if ($_SERVER["HTTP_ACCEPT_CHARSET"]) {
			// Si le navigateur retourne HTTP_ACCEPT_CHARSET on l'analyse et on en dduit le charset
			if (preg_match("/\butf-8\b/i", $_SERVER["HTTP_ACCEPT_CHARSET"])) {
				return "utf-8";
			}
			else {
				return "iso-8859-1";
			}
			// Sinon on analyse le HTTP_USER_AGENT retourn par le navigateur et si ca matche on vrifie 
			// que la version du navigateur est suprieure ou gale  la version dclare unicode
		}	elseif ((preg_match("/\b(\w+)\W(\d+)/i", $_SERVER["HTTP_USER_AGENT"], $matches)) 
								&& ($matches[2] >= $browserversion[strtolower($matches[1])]))	{
			return "utf-8";
		}
		else {
			return "iso-8859-1"; // Si on a rien trouvé on renvoie de l'iso
		}
	}	else {
		return $charset;
	}
}

/**
 * Le site est-il en maintenance ?
 *
 * Vérifie le status d'un site. Si status == -64 ou -65 et qu'on est pas loggé en admin lodel et qu'on est pas dans la partie 
 * administration générale de Lodel alors on redirige vers la page maintenance.html
 * 
 */
function maintenance()
{
	global $urlroot, $tableprefix, $dbhost, $dbpasswd, $dbusername, $database, $lodeluser, $sessionname, $db, $site;
	$name = addslashes($_COOKIE[$sessionname]);
	$path = "http://".$_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT'] != 80 ? ":". $_SERVER['SERVER_PORT'] : '').$urlroot."maintenance.html";
	$reg = "`/lodeladmin`";
	$query = "SELECT status FROM ".$tableprefix."sites where name = '".$site."' LIMIT 1";
	if ($name) {
		$db->selectDB($database);
		$row = $db->getRow(lq("SELECT context,expire,expire2 FROM #_MTP_session WHERE name='$name'"));
		usecurrentdb();
		// verifie que la session n'est pas expiree
		$time = time();

		if (($row['expire'] < $time || $row['expire2'] < $time) && preg_match($reg, $_SERVER['SCRIPT_NAME'])) {		
			return;
		} elseif($row['expire'] < $time || $row['expire2'] < $time) {
			$db->selectDB($database);
			$row = $db->getRow($query);
			usecurrentdb();
			if($row['status'] == -64 || $row['status'] == -65) {
				if($lodeluser['rights'] <= LEVEL_RESTRICTEDUSER ) {
					header("Location: $path?error_timeout=1");
				}
				$path = "http://".$_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT'] != 80 ? ":". $_SERVER['SERVER_PORT'] : '').$urlroot."lodeladmin/login.php?error_timeout=1";
				header("Location: ".$path);
			}
		}

		// passe les variables en global
		$lodeluser = unserialize($row['context']);			
		if($lodeluser['rights'] < 128) {
			if($database != '' && $dbusername != '' && !preg_match($reg, $_SERVER['SCRIPT_NAME'])) {
				$db->selectDB($database);
				$row = $db->getRow($query);
				usecurrentdb();
				if($row['status'] == -64 || $row['status'] == -65) {
					header('Location: '.$path);
				}
			}
		}
	} elseif(!preg_match($reg, $_SERVER['SCRIPT_NAME'])) {
		if($database != '' && $dbusername != '' && !preg_match($reg, $_SERVER['SCRIPT_NAME'])) {
			$db->selectDB($database);
			$row = $db->getRow($query);
			usecurrentdb();
			if($row['status'] == -64 || $row['status'] == -65) {
				header('Location: '.$path);
			}
		}
		
	}
	
}

// import Posted variables for the Register Off case.
// this should be nicely/safely integrated inside the code, but that's
// a usefull little hack at the moment
if (!((bool) ini_get('register_globals'))){
	extract($_REQUEST, EXTR_SKIP);
}

// securite... initialisation
$lodeluser = array ();
$idsession = 0;
$session   = '';
require_once 'connect.php';
// tres important d'initialiser le context.
$context = array ('version' => $GLOBALS['version'],
			'shareurl' => $GLOBALS['shareurl'],
			'extensionscripts' => $GLOBALS['extensionscripts'], 
			'currenturl' => 'http://'. $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT']!=80 ? ':'. $_SERVER['SERVER_PORT'] : ''). $_SERVER['REQUEST_URI'],
			'siteroot' => defined('SITEROOT') ? SITEROOT : '',
			'site' => $site,
			'charset' => ($GLOBALS['db_charset'] == 'utf8' ? 'utf-8' : 'iso-8859-1'),
			'langcache' => array ());

// Tableau des options du site dans le $context
if (!empty($context['site'])) { // pas besoin quand on est dans l'admin générale (options définies pour un site)
	$context['options'] = array ();
	require_once 'optionfunc.php';
	$context['options'] = cacheOptionsInFile();
}

if (!$GLOBALS['filemask']) {
	$GLOBALS['filemask'] = "0700";
}


// Langue ?
// récupère langue dans le cookie (s'il existe et si la langue n'est pas passée en GET ou en POST)
if(!empty($_COOKIE['language']) && empty($_GET['lang']) && empty($_POST['lang'])) {
	if (preg_match("/^\w{2}(-\w{2})?$/", $_COOKIE['language'])) {
		$GLOBALS['lang'] = $_COOKIE['language']; }
	else {
		setcookie('language');
		$GLOBALS['lang'] = '';}
}
// langue passée en GET ou POST : initialise le cookie
else {
	$GLOBALS['lang'] = $_GET['lang'] ? $_GET['lang'] : $_POST['lang'];
	if (!preg_match("/^\w{2}(-\w{2})?$/", $GLOBALS['lang'])) {
		// spécifique ME Revues.org : si langue du site renseigné, alors la langue par défaut prend cette valeur
		$GLOBALS['lang'] = empty($context['options']['metadonneessite']['langueprincipale']) ? '' : $context['options']['metadonneessite']['langueprincipale'];
	}
	else {	setcookie('language', $GLOBALS['lang']); }
}
// tableaux des langues disponibles
require_once 'lang.php';
$context['defaultlang'] = $GLOBALS['languages'];
// accès à la langue dans les templates
$context['sitelang'] = $GLOBALS['lang'];
//sommes nous en maintenance ?
maintenance();
//echo $GLOBALS['lang'];
header("Content-type: text/html; charset=". $context['charset']);
?>
