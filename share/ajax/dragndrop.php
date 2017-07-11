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

function shiftRanks($db, $table, $parentId, $doNotShiftId, $from, $to, $offset) {
    $intervalSubQuery = "rank <= {$to} AND rank >= {$from}";
    if($from === $to) {
        $intervalSubQuery = "rank = {$from}";
    }

    $sqlQuery =
        "UPDATE {$table} " .
        "SET rank = (rank + ({$offset})) " .
        "WHERE " . $intervalSubQuery . " " .
        "AND idparent = {$parentId} " .
        "AND id != {$doNotShiftId}";

    $db->execute($sqlQuery);
}

function setRank($db, $table, $parentId, $oldRank, $newRank) {
    $sqlQuery =
        "UPDATE {$table} " .
        "SET rank = {$newRank} " .
        "WHERE rank = {$oldRank} " .
        "AND idparent = {$parentId} ";

    $db->execute($sqlQuery);
}

function getParentId($db, $table, $id) {
    $sqlQuery =
        "SELECT idparent " .
        "FROM {$table} " .
        "WHERE id = {$id} ";

    return (int) $db->GetOne($sqlQuery);
}

function getRank($db, $table, $id) {
    $sqlQuery =
        "SELECT rank " .
        "FROM {$table} " .
        "WHERE id = {$id} ";

    return (int) $db->GetOne($sqlQuery);
}

function getMaxRank($db, $table, $parentId) {
    $sqlQuery =
        "SELECT MAX(rank) " .
        "FROM {$table} " .
        "WHERE idparent = {$parentId} ";

    return (int) $db->GetOne($sqlQuery);
}

function move($db, $table, $parentId, $doNotShiftId, $from, $to) {
    if($from === $to) {
        return;
    }

    if($from < $to) {
        setRank($db, $table, $parentId, $from, $to);
        shiftRanks($db, $table, $parentId, $doNotShiftId, $from + 1, $to, -1);
        return;
    }

    if($from > $to) {
        setRank($db, $table, $parentId, $from, $to + 1);
        shiftRanks($db, $table, $parentId, $doNotShiftId, $to + 1, $from - 1, 1);
        return;
    }
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

    $fromId = (int)$tabIds[0];
    $toId = (int)$tabIds[1];
    $from = getRank($db, $table, $fromId);
    $to = -1;
    if($toId > -1) {
      $to = getRank($db, $table, $toId);
    }
    $parentId = getParentId($db, $table, $fromId);
    move($db, $table, $parentId, $fromId, $from, $to);

    clearcache();
    echo 'ok';
}
catch(Exception $e)
{
    header("HTTP/1.0 404 Not Found");
    echo 'error';
    exit();
}

