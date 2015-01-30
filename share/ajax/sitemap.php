<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier AJAX - Gère l'affichage d'un container
 */

$site = filter_input(INPUT_POST, 'site', FILTER_VALIDATE_REGEXP, array("options" => array("regexp"=>"/^[a-z0-9\-]+$/")));

if(!$site || in_array($site, array('lodel', 'share', 'lodeladmin')) || !is_dir("../../{$site}"))
{
    // tentative ?
    header("HTTP/1.0 404 Not Found");
    echo 'error';
    exit();
}

// chdir pour faciliter les include
chdir('../../' . ('principal' == $site ? '' : $site) . '/lodel/edition');

if(!file_exists('siteconfig.php'))
{
    header("HTTP/1.0 404 Not Found");
    echo 'error';
    exit();
}

require 'siteconfig.php';

try
{
    include 'auth.php';
    // pas de log de l'url dans la base
    C::set('norecordurl', true);
    // accès seulement aux personnes autorisées
    if(!authenticate(LEVEL_VISITOR, null, true) || !C::get('visitor', 'lodeluser'))
    {
        echo 'auth';
        return;
    }
    // if no childs, we just escape
    if(!$db->getOne("SELECT count(id) FROM {$GLOBALS['tableprefix']}entities WHERE idparent = '".C::get('id')."'")) exit();
    // set the parameters
    $GLOBALS['nodesk'] = true;
    $context['folder'] = true;
    $url = $db->GetOne(lq('SELECT url
                            FROM #_MTP_sites
                            WHERE name="'.C::get('site', 'cfg').'"'));
    $context['currenturl'] = $url.'/lodel/edition/';
    // get the block
    echo View::getView()->getIncTpl($context, 'edition', '', '', 1);
}
catch(Exception $e)
{
    header("HTTP/1.0 404 Not Found");
    echo 'error';
    exit();
}
