<?php
/**
 * Fichier utilitaire pour gérer l'authentification de Lodel
 *
 * Ce script définit les méthodes de base pour l'authentification.
 * Il se charge aussi d'initialiser le context, ainsi que le jeu de caractère utilisé
 * *IMPORTANT* : ce script devrait être transformé en classe.
 *
 * PHP version 4
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno Cénou, Jean Lamy
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
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno Cénou, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */

// les niveaux de droits
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
 * LEVEL_VISITOR : 10, LEVEL_REDACTOR : 20, LEVEL_EDITOR : 30, LEVEL_ADMIN : 40,
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
			break;
		}
		require_once 'connect.php';
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

		// verifie que la session n'est pas expiree
		$time = time();
		if ($row['expire'] < $time || $row['expire2'] < $time) {
			$login = "";
			if (file_exists('login.php'))	{
				$login = 'login.php';
			}	elseif (file_exists('lodel/edition/login.php'))	{
				$login = 'lodel/edition/login.php';
			}	else {
				break;
			}
			header("location: $login?error_timeout=1&". $retour);
			exit;
		}

		// passe les variables en global
		$lodeluser = unserialize($row['context']);
		if ($lodeluser['rights'] < $level) { //teste si l'utilisateur a les bons droits
			header("location: login.php?error_privilege=1&". $retour);
			exit;
		}

		// verifie encore une fois au cas ou...
		if ($lodeluser['rights'] < LEVEL_ADMINLODEL && !$site) {
			break;
		}

		$lodeluser['adminlodel'] = $lodeluser['rights'] >= LEVEL_ADMINLODEL;
		$lodeluser['admin']      = $lodeluser['rights'] >= LEVEL_ADMIN;
		$lodeluser['editor']     = $lodeluser['rights'] >= LEVEL_EDITOR;
		$lodeluser['redactor']   = $lodeluser['rights'] >= LEVEL_REDACTOR;
		$lodeluser['visitor']    = $lodeluser['rights'] >= LEVEL_VISITOR;

		$context['lodeluser']    = $lodeluser;

		if ($lodeluser['lang']) {
			$GLOBALS['la'] = $lodeluser['lang'];
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

	// exception
	if ($level == 0) {
		return; // les variables ne sont pas mises... on retourne
	}
	elseif ($mode == 'HTTP') {
 		require_once 'loginHTTP.php';
		return;
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

// tres important d'initialiser le context.
$context = array ('version' => $GLOBALS['version'],
			'shareurl' => $GLOBALS['shareurl'],
			'extensionscripts' => $GLOBALS['extensionscripts'], 
			'currenturl' => 'http://'. $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT']!=80 ? ':'. $_SERVER['SERVER_PORT'] : ''). $_SERVER['REQUEST_URI'],
			'siteroot' => defined('SITEROOT') ? SITEROOT : '',
			'site' => $site,
			'charset' => getacceptedcharset($charset),
			'langcache' => array ());

// Tableau des options du site dans le $context
if (!empty($context['site'])) { // pas besoin quand on est dans l'admin générale (options définies pour un site)
	$context['options'] = array ();
	require_once 'connect.php';
	require_once 'optionfunc.php';
	$context['options'] = cacheOptionsInFile();
}

if (!$GLOBALS['filemask']) {
	$GLOBALS['filemask'] = "0700";
}

header("Content-type: text/html; charset=". $context['charset']);

// Langue ?
$GLOBALS['la'] = $_GET['la'] ? $_GET['la'] : $_POST['la'];
if (!preg_match("/^\w{2}(-\w{2})?$/", $GLOBALS['la'])) {
	$GLOBALS['la'] = '';
}
?>