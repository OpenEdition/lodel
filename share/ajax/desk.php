<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier AJAX - Gère la mise à jour dans la DB du status du desk côté édition
 */

$site = filter_input(INPUT_POST, 'site', FILTER_VALIDATE_REGEXP, array("options" => array("regexp"=>"/^[a-z0-9\-]+$/")));

if(!$site || in_array($site, array('lodel', 'share', 'lodeladmin')) || !is_dir("../../{$site}"))
{
    // tentative ?
    header("HTTP/1.0 404 Not Found");
    echo "error";
    exit();
}

// chdir pour faciliter les include
chdir('../../' . ('principal' == $site ? '' : $site) . '/lodel/edition');

if(!file_exists('siteconfig.php'))
{
    // tentative ?
    header("HTTP/1.0 404 Not Found");
    echo "error";
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

    echo updateDeskDisplayInSession();
    return;
}
catch(Exception $e)
{
    header("HTTP/1.0 404 Not Found");
    echo 'error';
    exit();
}
