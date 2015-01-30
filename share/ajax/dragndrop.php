<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier AJAX - Gère la mise à jour du status des entités - Appellé via le drag'n'drop
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
        exit();
    }

    $table = lq("#_TP_entities");
    $i=1;
    $tabIds = explode(',',C::get('tabids'));
    foreach($tabIds as $v)
    {
        $id = (int)str_replace('container_','',$v);
        if($id>0)
        {
            $db->execute("UPDATE {$table} SET rank = '{$i}' WHERE id='{$id}'") or trigger_error('error', E_USER_ERROR);
        }
        $i++;
    }

    clearcache();
    echo 'ok';
}
catch(Exception $e)
{
    header("HTTP/1.0 404 Not Found");
    echo 'error';
    exit();
}
