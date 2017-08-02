<?php

class Ranks {
    public static function getParentId($db, $table, $id) {
        $sqlQuery =
            "SELECT idparent " .
            "FROM {$table} " .
            "WHERE id = {$id} ";

        return (int) $db->GetOne($sqlQuery);
    }

    public static function getRank($db, $table, $id) {
        $sqlQuery =
            "SELECT rank " .
            "FROM {$table} " .
            "WHERE id = {$id} ";

        return (int) $db->GetOne($sqlQuery);
    }
}