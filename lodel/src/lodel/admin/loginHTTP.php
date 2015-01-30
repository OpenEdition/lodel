<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier de Login HTTP (basic)
 */

require_once 'siteconfig.php';

try
{
    include_once 'auth.php';
    include 'class.authHTTP.php';
    
    $httpAuth = new AuthHTTP();
    if ($httpAuth->getHeader())
    {
        // récupère les identifiants (login/password) du header
        $identifiers = $httpAuth->getIdentifiers();
	C::clean($identifiers);
    
        function_exists('check_auth') || include 'loginfunc.php';
        
        // les identifiants ne correspondent pas à un utilisateur Lodel
        if (!check_auth($identifiers['login'],$identifiers['password'],C::get('site', 'cfg')))
        {
            $httpAuth->errorLogin();	
        }
    
    // OK
        else
        {
            open_session($identifiers['login']);
            $httpAuth->reset();
            return;
        }
    }
    
    else $httpAuth->errorLogin();
}
catch(LodelException $e)
{
	echo $e->getContent();
	exit();
}
?>