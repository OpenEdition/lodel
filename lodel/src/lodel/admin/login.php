<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier de Login
 */

define('backoffice', true);
require 'siteconfig.php';

try
{
    include 'auth.php';
    C::set('env', 'admin');

    $login = C::get('login');
    
    if($login && C::get('passwd') && C::get('passwd2')) {
        include 'loginfunc.php';
        $retour = change_passwd(C::get('datab'), $login, C::get('old_passwd'), C::get('passwd'), C::get('passwd2'));
        switch($retour)
        {
            case true:
                // on relance la procédure d'identification
                if (!check_auth(C::get('login'), C::get('passwd'), C::get('site', 'cfg'))) {
                    C::set('error_login', 1);
                } else {
                    //Vérifie que le site est bloqué si l'utilisateur est pas lodeladmin
                    if(C::get('rights', 'lodeluser') < LEVEL_ADMINLODEL) {
                        usemaindb();
                        C::set('site_bloque', $db->getOne(lq("SELECT 1 FROM #_MTP_sites WHERE name='".C::get('site', 'cfg')."' AND status >= 32")));
                        usecurrentdb();
                        if(C::get('site_bloque') == 1) {
                            C::set('error_site_bloque', 1);
                            break;
                        }
                    }
                    // et on ouvre une session
                    $err = open_session(C::get('login'));
                    if ((string)$err === 'error_opensession') {
                        C::set($err, 1);
                        break;
                    } else {
                        check_internal_messaging();
                        header ("Location: http".(C::get('https', 'cfg') ? 's' : '')."://". $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT'] != 80 ? ':'. $_SERVER['SERVER_PORT'] : ''). C::get('url_retour'));
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
    } elseif ($login) {
        include 'loginfunc.php';
        do {
            if (!check_auth(C::get('login'), C::get('passwd'))) {
                C::set('error_login', 1);
                break;
            }
            //Vérifie que le site est bloqué si l'utilisateur est pas lodeladmin
            if(C::get('rights', 'lodeluser') < LEVEL_ADMINLODEL) {
                usemaindb();
                C::set('site_bloque', $db->getOne(lq("SELECT 1 FROM #_MTP_sites WHERE name='".C::get('site', 'cfg')."' AND status >= 32")));
                usecurrentdb();
                if(C::get('site_bloque') == 1) {
                    C::set('error_site_bloque', 1);
                    break;
                }
            }
            //vérifie que le compte n'est pas en suspend. Si c'est le cas, on amène l'utilisateur à modifier son mdp, sinon on l'identifie
            if(!check_suspended()) {
                C::set('suspended', 1);
                break;
            }
            else {
                // ouvre une session
                if ((string)open_session(C::get('login')) === 'error_opensession') {
                    C::set('error_opensession', 1);
                    break;
                }
            }
            check_internal_messaging();
            header ("Location: http".(C::get('https', 'cfg') ? 's' : '')."://". $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT'] != 80 ? ':'. $_SERVER['SERVER_PORT'] : ''). C::get('url_retour'));
        } while (0);
    }
    
    C::set('passwd', null);
    C::set('passwd2', null);
    C::set('old_passwd', null);
    // variable: sitebloque
    /*if ($context['error_sitebloque']) { // on a deja verifie que la site est bloque.
        $context['site_bloque'] = 1;
    } else { // test si le site est bloque dans la DB.
        
        usemaindb();
        $context['site_bloque'] = $db->getOne(lq("SELECT 1 FROM #_MTP_sites WHERE name='$site' AND status >= 32"));
        usecurrentdb();
    }*/
    
    View::getView()->render('login');
}
catch(LodelException $e)
{
	echo $e->getContent();
	exit();
}
?>