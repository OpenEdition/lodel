<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Appel du navigateur d'entité
 */

define('backoffice', true);
define('backoffice-edition', true);
require 'siteconfig.php';

try
{
    function getchecked($id)
    {
        return in_array($GLOBALS['ids'], $id) ? "checked=\"checked\"" : '';
    }
    
    include 'auth.php';
    C::set('env', 'edition');
    authenticate(LEVEL_REDACTOR);
    
    define('UPLOADDIR', SITEROOT. 'upload');
    
    $context['caller'] = $_REQUEST['caller'];
    $context['single'] = isset($_REQUEST['single']);
    $GLOBALS['ids'] = array();
    if(isset($_REQUEST['value']))
    	$GLOBALS['ids'] = explode(',', $_REQUEST['value']);
    $GLOBALS['nodesk'] = true;
    View::getView()->renderCached('entitybrowser');
    

}
catch(LodelException $e)
{
	echo $e->getContent();
	exit();
}
?>