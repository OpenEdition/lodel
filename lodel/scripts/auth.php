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
 * @author Sophie Malafosse
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
function authenticate($level = 0, $mode = "", $return = false)
{
	C::trigger('preauth');
	$lodeluser = array();
	$retour = "url_retour=". urlencode($_SERVER['REQUEST_URI']);
	do { // block de control
		if (!isset($_COOKIE[C::get('sessionname', 'cfg')]) && !defined('backoffice-lodeladmin') && !defined('backoffice'))
        	{
                	if(file_exists(SITEROOT.'./CACHE/.no_restricted')) break;
                	maintenance();
            
            		defined('INC_CONNECT') || include 'connect.php';
			global $db;
			// check for restricted users by client IP address
			$users = $db->GetArray(lq("SELECT id, ip FROM #_TP_restricted_users WHERE status > 0"));
			if(!$users)
            		{
                		touch(SITEROOT.'./CACHE/.no_restricted');
                		break;
            		}
			list($oct[0],$oct[1],$oct[2],$oct[3]) = sscanf($_SERVER['REMOTE_ADDR'], "%d.%d.%d.%d");
			$name = false;
			foreach($users as $user) 
			{
				if(empty($user['ip'])) continue;
				$uuser = explode(' ', $user['ip']);
				foreach($uuser as $ip)
				{
					$ip = trim($ip);
					if(!$ip) continue 2;
					$octs = explode('.', $ip);
					foreach($octs as $k=>$octet) {
						if(!$octet) continue;
						if(false !== strpos($octet, '[')) 
						{ // plage d'ip
							$o = explode('-', substr($octet, 1, strlen($octet)-2));
							if((int)$oct[$k] < (int)$o[0] || (int)$oct[$k] > (int)$o[1]) continue 2;
						} 
						elseif((int)$octet !== (int)$oct[$k]) continue 2;
					}
					
					$row = $db->getRow(lq("SELECT id, username, lang FROM #_TP_restricted_users WHERE id = '{$user['id']}'"));
					if(!$row) break 3; // strange ???????
					$lodeluser['rights'] = LEVEL_RESTRICTEDUSER;
					$lodeluser['lang'] = $row['lang'] ? $row['lang'] : "fr";
					$lodeluser['id'] = $row['id'];
					$lodeluser['groups'] = '';
					C::set('lodeluser', $lodeluser);
					C::set('login', $row['username']);
					$lodeluser['name'] = $row['username'];
					C::setUser($lodeluser);
					unset($lodeluser);
					function_exists('check_expiration') || include 'loginfunc.php';
					if(!check_expiration()) {
						break 3;
					}
					$name = open_session($row['username']);
					unset($row);
					// si on arrive là c'est qu'on est bon, on s'éjecte du foreach
					break 2;
				}
			}
			if(!$name || $name == 'error_opensession') break;
			else
			{
				C::trigger('postauth');
				return true;
			}
		}
		elseif(isset($_COOKIE[C::get('sessionname', 'cfg')]))
		{
			$name = addslashes($_COOKIE[C::get('sessionname', 'cfg')]);
		}
		else break;

        	defined('INC_CONNECT') || include 'connect.php';
		global $db;

		if (!($row = $db->getRow(lq("
            SELECT id,iduser,site,context,expire,expire2,currenturl 
                FROM #_MTP_session 
                WHERE name='$name'")))) 
        	{
        		setcookie(C::get('sessionname', 'cfg'), "", time()-1,C::get('urlroot', 'cfg'));
			break;
		}
		
		// passe les variables en global
		$lodeluser = unserialize($row['context']);

		// verifie que la session n'est pas expiree
		$time = time();
		if ($row['expire'] < $time || $row['expire2'] < $time) {
			$login = $lodeluser = "";
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

		if ($lodeluser['rights'] < $level) { //teste si l'utilisateur a les bons droits
			$lodeluser = '';
			header("location: login.php?error_privilege=1&". $retour);
			exit;
		}

		// verifie qu'on est dans le bon site
		if ($lodeluser['rights'] < LEVEL_ADMINLODEL && (!C::get('site', 'cfg') || $row['site'] != C::get('site', 'cfg'))) {
			break;
		}

		$lodeluser['restricted_user'] = $lodeluser['rights'] >= LEVEL_RESTRICTEDUSER;
		$lodeluser['adminlodel'] = $lodeluser['rights'] >= LEVEL_ADMINLODEL;
		$lodeluser['admin']      = $lodeluser['rights'] >= LEVEL_ADMIN;
		$lodeluser['editor']     = $lodeluser['rights'] >= LEVEL_EDITOR;
		$lodeluser['redactor']   = $lodeluser['rights'] >= LEVEL_REDACTOR;
		$lodeluser['visitor']    = $lodeluser['rights'] >= LEVEL_VISITOR;

		C::set('login', $lodeluser['name']);
		$lodeluser['idsession'] = $row['id'];
		$lodeluser['session'] = $name;
		C::setUser($lodeluser);
		unset($row);
        
		function_exists('open_session') || include 'loginfunc.php';
		if('error_opensession' === open_session($lodeluser['name'], $name))
			break;

		if($lodeluser['visitor'])
		{ // we set the lang only for logged users and not for restricted users, 
		// which ones can not change their lang in the interface but only in the site
			$lang = C::get('lang');
			if($lang && 'set' === C::get('do') && 'users' === C::get('lo'))
			{
				$GLOBALS['lang'] = $lang;
				C::set('sitelang', $lang);
			}
			elseif(isset($lodeluser['lang'])) 
			{
				$GLOBALS['lang'] = $lodeluser['lang'];
				C::set('sitelang', $lodeluser['lang']);
			}
		
			setLang(C::get('sitelang'));
		}
		
// 		usecurrentdb();
		unset($lodeluser);
        
		if(!C::get('adminlodel', 'lodeluser')) maintenance();
			
		if(C::get('redactor', 'lodeluser') && isset($_REQUEST['clearcache']))
		{
			clearcache(true);
		}
            
		C::trigger('postauth');
		return true; // ok !!!
	}	while (0);

// 	usecurrentdb();
    	C::setUser();
	// on est pas loggé : pour éviter des attaques par DOS on désactive le clearcache
	C::set('nocache', false);
	$lodeluser = '';
   	maintenance();

	setLang();
	C::trigger('postauth');
	// exception
	if ($level == 0) {
		return; // les variables ne sont pas mises... on retourne
	}
	elseif ($mode == 'HTTP') {
		include SITEROOT.'lodel/admin/loginHTTP.php';
		return;
	}
	else {
		if($return) return false;
		header("location: login.php?". $retour);
		exit;
	}
}

/**
 * Gestion de la langue de l'utilisateur
 *
 * Cette fonction gère la langue de l'utilisateur, loggé ou non
 *
 * @param string $lang La langue, si loggué
 */
function setLang($lang=null)
{
	// Langue ?
	if(!$lang) $lang = C::get('lang');
	// récupère langue dans le cookie (s'il existe et si la langue n'est pas passée en GET ou en POST)
	if(!empty($_COOKIE['language']) && !$lang) {
		if (preg_match("/^\w{2}(-\w{2})?$/", $_COOKIE['language'])) 
		{
			$lang = $_COOKIE['language'];
		}
		else
		{
			$lang = 'fr';
			setcookie('language', 'fr', 0, C::get('urlroot', 'cfg'));
		}
	}
	// langue passée en GET ou POST : initialise le cookie
	else 
	{
		if (!$lang || !preg_match("/^\w{2}(-\w{2})?$/", $lang)) 
		{
			// spécifique ME Revues.org : si langue du site renseigné, alors la langue par défaut prend cette valeur
			$lang = C::get('options.metadonneessite.langueprincipale');
			$lang = !$lang ? 'fr' : $lang;
		}
		setcookie('language', $lang, 0, C::get('urlroot', 'cfg'));
	}
	// do we have to set another locale ?
	if('fr' !== substr($lang, 0, 2))
	{
		$l = strtolower(substr($lang, 0,2));
		$lu = 'en' === $l ? 'US' : strtoupper($l);
		if(!@setlocale(LC_ALL, $l.'_'.$lu.'.UTF8'))
		{ // locale not found, reset it to default french
			@setlocale(LC_ALL, 'fr_FR.UTF8');
		}
		if('tr' === $l)
		{ // bug with locale tr_TR.UTF8
		// http://bugs.php.net/bug.php?id=18556
			@setlocale(LC_CTYPE, 'fr_FR.UTF8');
		}
		C::set('locale', $l.'_'.$lu.'.UTF8');
		unset($l, $lu);
	}
	
	C::set('sitelang', $lang);
}

/**
 * Enregistre l'url pour la session donnée
 *
 * Cette fonction enregistre l'url courante (url du script) pour une session donnée.
 * Cela est utile pour la navigation dans l'interface
 */
function recordurl()
{
	global $db;
	if (!C::get('norecordurl')) {
		$row = $db->GetRow(lq("SELECT id,currenturl FROM #_MTP_session WHERE id='". C::get('idsession', 'lodeluser')."' AND currenturl!=''"));
		if(!$row) return;
		$db->execute(lq("INSERT INTO #_MTP_urlstack (idsession,url,site) VALUES('{$row['id']}', '{$row['currenturl']}', '".C::get('site', 'cfg')."')")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
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
 * Test des jeux de caractéres supportés par un navigateur. Ce test est fait grâce aux
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
				return "utf-8";
			}
			// Sinon on analyse le HTTP_USER_AGENT retourn par le navigateur et si ca matche on vrifie 
			// que la version du navigateur est suprieure ou gale  la version dclare unicode
		}	elseif ((preg_match("/\b(\w+)\W(\d+)/i", $_SERVER["HTTP_USER_AGENT"], $matches)) 
								&& ($matches[2] >= $browserversion[strtolower($matches[1])]))	{
			return "utf-8";
		}
		else {
			return "utf-8"; // Si on a rien trouvé on renvoie de l'iso
		}
	}	else {
		return $charset;
	}
}

/**
 * Le site est-il en maintenance ?
 *
 * Vérifie le status d'un site. Si le fichier ./CACHE/.lock est présent c'est que le site est bloqué
 * Cette fonction est appellée uniquement si on n'est pas loggé en tant qu'admin lodel
 *
 * This function checks if the file ./CACHE/.lock exists and return the maintenance page if it is so 
 * This function is called only if we are not logged in as an adminlodel
 */
function maintenance()
{
	if('logout.php' === basename($_SERVER['SCRIPT_NAME']))
	{
		return false;
	}
	
	if(defined('backoffice-lodeladmin') || !file_exists(SITEROOT.'./CACHE/.lock')) return false;
	
	if(file_exists(C::get('home', 'cfg')."../../maintenance.html"))
		die(include(C::get('home', 'cfg')."../../maintenance.html"));
	elseif(file_exists("maintenance.html"))
		die(include("maintenance.html"));
	else
		die('Sorry, the site is under maintenance. Please come back later.');
}

// tres important d'initialiser le context.
C::setRequest();

// Tableau des options du site dans le $context
if (C::get('site', 'cfg')) 
{ // pas besoin quand on est dans l'admin générale (options définies pour un site)
	if(!($options = getFromCache('options')))
	{
		function_exists('cacheOptionsInFile') || include 'optionfunc.php';
		C::set('options', cacheOptionsInFile());
	}
	else
	{
		C::set('options', $options);
		unset($options);
	}
}
setLang();
// tableaux des langues disponibles
include 'lang.php';
C::set('defaultlang', $GLOBALS['languages']);
C::set('installlang', C::get('installlang', 'cfg'));
?>