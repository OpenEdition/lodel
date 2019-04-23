<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier login de lodeladmin - Ce fichier est quasiment identique au fichier login.php dans
 * le package lodel/source
 */
define('backoffice-lodeladmin', true);
require 'lodelconfig.php';

try
{
	include 'auth.php';
    C::set('env', 'lodeladmin');
	$login = C::get('login');
    
	if($login && C::get('passwd') && C::get('passwd2')) {
		include 'loginfunc.php';
		
        $retour = change_passwd(C::get('datab'), $login, C::get('old_passwd'), C::get('passwd'), C::get('passwd2'));
        switch($retour)
        {
            case true:
                // on relance la procédure d'identification
                if (!check_auth($login, C::get('passwd'), C::get('site', 'cfg'))) {
                    C::set('error_login', 1);
                } else {
                    // et on ouvre une session
                    $err = open_session($login);
                    if ((string)$err === 'error_opensession') {
                        C::set($err, 1);
                        break;
                    } else {
                        check_internal_messaging();
                        header ("Location: http".(C::get('https', 'cfg') ? 's' : '')."://". $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT'] != 80 ? ':'. $_SERVER['SERVER_PORT'] : ''). C::get('url_retour'));
                        die();
                    }
                }
                break;
            case 'error_passwd':
                C::set('suspended', 1);
                break;
            case false: // bad login/passwd
            default: 
                C::set('error_login', 1);
                break;
        }
	} elseif (C::get('login')) {
		do {
			include 'loginfunc.php';
			if (!check_auth($login, C::get('passwd'), C::get('site', 'cfg'))) {
                		C::set('error_login', 1);
				break;
			}
			
			//vérifie que le compte n'est pas en suspend. Si c'est le cas, on amène l'utilisateur à modifier son mdp, sinon on l'identifie
			if(!check_suspended()) {
                		C::set('suspended', 1);
				break;
			}
			else {
				// ouvre une session
				$err = open_session(C::get('login'));
				if ((string)$err === 'error_opensession') {
                    			C::set($err, 1);
					break;
				}
			}
			check_internal_messaging();
			header ("Location: http".(C::get('https', 'cfg') ? 's' : '')."://". $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT'] ? ':'. $_SERVER['SERVER_PORT'] : ''). C::get('url_retour'));
			die ();
		} while (0);
	}
	C::set('passwd', null);
    	C::set('passwd2', null);
    	C::set('old_passwd', null);
	// commenté le 13/11/08 par pierre-alain, aucune utilité trouvée ?
	// variable: sitebloque
	// if ($context['error_sitebloque']) { // on a deja verifie que la site est bloque.
	// 	$context['sitebloque'] = 1;
	// } else { // test si la site est bloque dans la DB.
	// 	require_once 'connect.php';
	// 	usemaindb();
	// 	$context['sitebloque'] = $db->getOne(lq("SELECT 1 FROM #_MTP_sites WHERE name='$site' AND status>=32"));
	// 	usecurrentdb();
	// }
	View::getView()->render('login');
}
catch(Exception $e)
{
	echo $e->getContent();
	exit();
}
?>