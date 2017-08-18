<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier utilitaire pour gérer l'authentification de Lodel
 *
 * Ce script définit les méthodes de base pour l'authentification.
 * Il se charge aussi d'initialiser le context, ainsi que le jeu de caractère utilisé
 * *IMPORTANT* : ce script devrait être transformé en classe.
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
			if(cache_get('no_restricted')) break;
            
			defined('INC_CONNECT') || include 'connect.php';
			global $db;
			// check for restricted users by client IP address
			$users = $db->GetArray(lq("SELECT id, ip FROM #_TP_restricted_users WHERE status > 0"));
			if(!$users)
			{
				$cache = getCacheObject();
				$cache->set(getCacheIdFromId('no_restricted'), true);
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
					if (count($octs) != 4) continue;
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
				maintenance();
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
			clearcache();
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
 * Retourne la liste des langues disponibles du site par ordre de priorité
 *
 * @return array liste des codes langues disponibles
 */
function get_available_languages()
{
    global $db;

    $languages = array();

    $result = $db->execute(lq("SELECT lang, rank from #_TP_translations WHERE status > 0 ORDER BY rank"));
    foreach($result as $row)
    {
        $languages[$row['rank']] = $row['lang'];
    }
    return $languages;
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
    $available_languages = get_available_languages();
    $choosed_language = null;

    if( $lang && in_array($lang, $available_languages))
    {
        $choosed_language = $lang;
    }

    // Choix de modification de la langue par l'utilisateur
    if(!$choosed_language && !$lang && $lang = C::get('lang'))
    {
        // La langue doit être disponible dans les traductions
        if(in_array($lang, $available_languages)){
            setcookie('language', $lang, 0, C::get('urlroot', 'cfg'));
            $choosed_language = $lang;
        }
    }

    // Si un cookie est présent, on conserve la langue
    if( !$choosed_language && isset($_COOKIE['language']) && in_array($_COOKIE['language'], $available_languages) )
    {
        $choosed_language = $_COOKIE['language'];
    }

    /* On essaye de détecter la langue, si la détection de langue est activée */
    if( !$choosed_language && isset( $_SERVER['HTTP_ACCEPT_LANGUAGE']) && C::get('detectlanguage','cfg')){
        preg_match_all(
            '/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'],
            $acceptedlangs
        );
        foreach( $acceptedlangs[1] as $lang ) {
            $lang = substr($lang, 0, 2);
            if( in_array($lang, $available_languages) ){
                $choosed_language = $lang;
                break;
            }
        }
    }

    /* si une option définissant la langue par défaut est définie dans lodelconfig on l'utilise
     *
     */
    if( !$choosed_language && C::get('mainlanguageoption', 'cfg') )
    {
        $main_language = C::get(C::get('mainlanguageoption', 'cfg'));
        if($main_language && in_array($main_language, $available_languages))
        {
            $choosed_language = $main_language;
        }
    }

    if( !$choosed_language )
    {
        $choosed_language = array_shift($available_languages);
    }

    // Définition de la locale système
    if( $choosed_language !== substr(setlocale(LC_ALL, 0), 0, 2) )
    {
        $l = strtolower(substr($choosed_language, 0,2));
        $lu = 'en' === $l ? 'US' : strtoupper($l);
        @setlocale(LC_ALL, $l.'_'.$lu.'.UTF8');
        if('tr' === $l)
        { // bug with locale tr_TR.UTF8
        // http://bugs.php.net/bug.php?id=18556
            @setlocale(LC_CTYPE, 'fr_FR.UTF8');
        }
        C::set('locale', $l.'_'.$lu.'.UTF8');
        unset($l, $lu);
    }
    C::set('sitelang', $choosed_language);
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
		return $url. "&amp;". $extraarg;
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
	
	if(defined('backoffice-lodeladmion') || !cache_get('lock')) return false;
	
	if(file_exists(C::get('home', 'cfg')."../../maintenance.php"))
		die(include(C::get('home', 'cfg')."../../maintenance.php"));
	elseif(file_exists("maintenance.php"))
		die(include("maintenance.php"));
	else
		die('Sorry, the site is under maintenance. Please come back later.');
}

// tres important d'initialiser le context.
C::setRequest();

// Tableau des options du site dans le $context
if (C::get('site', 'cfg')) 
{ // pas besoin quand on est dans l'admin générale (options définies pour un site)
	if(!($options = cache_get('options')))
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
