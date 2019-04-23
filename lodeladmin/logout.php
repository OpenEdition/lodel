<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier logout de lodeladmin - Ce fichier est quasiment identique au fichier logout.php dans
 * le package lodel/source
 */

require 'lodelconfig.php';
try
{
    require 'auth.php';
    C::set('env', 'lodeladmin');
    authenticate();
    
    if(isset($_COOKIE[C::get('sessionname', 'cfg')]))
    {
        $name = addslashes($_COOKIE[C::get('sessionname', 'cfg')]);
        $time = time()-1;
        usemaindb();
        $db->execute(lq("UPDATE #_MTP_session SET expire2='$time' WHERE name='$name'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
        $db->execute(lq("DELETE FROM #_MTP_urlstack WHERE idsession='".C::get('idsession', 'lodeluser')."'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
        setcookie(C::get('sessionname', 'cfg'), "", $time,C::get('urlroot', 'cfg'));
    }

    header ("Location: http".(C::get('https', 'cfg') ? 's' : '')."://". $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT'] ? ':'. $_SERVER['SERVER_PORT'] : ''). C::get('urlroot', 'cfg'));
    exit;
}
catch(LodelException $e)
{
    die($e->getMessage());
}
?>