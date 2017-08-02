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

function moveToRank($db, $table, $tabIds) {
    $fromId = (int)$tabIds[0];
    $toRank = (int)$tabIds[1];

    include_once __DIR__ . "/../../lodel/scripts/Ranks.php";
    $from = Ranks::getRank($db, $table, $fromId);
    $to = $toRank;
    $parentId = Ranks::getParentId($db, $table, $fromId);

    include_once __DIR__ . "/../../lodel/scripts/RankShifter.php";
    $manager = new RankShifter($db, $table, $parentId);
    $manager->move($fromId, $from, $to);
    error_log($from);
    error_log($to);
}

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

    moveToRank($db, $table, $tabIds);

    clearcache();
    echo 'ok';
}
catch(Exception $e)
{
    header("HTTP/1.0 404 Not Found");
    echo 'error';
    exit();
}

