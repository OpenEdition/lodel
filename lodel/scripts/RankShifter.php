<?php

class RankShifter {
    private $db;
    private $table;
    private $parentId;

    public function __construct($db, $table, $parentId)
    {
        $this->db = $db;
        $this->table = $table;
        $this->parentId = $parentId;
    }

    public function move($fixedId, $from, $to) {
        if($from === $to) {
            return;
        }

        if($from < $to) {
            $this->setRank($from, $to);
            $this->shift($fixedId, $from + 1, $to, -1);
            return;
        }

        if($from > $to) {
            $this->setRank($from, $to + 1);
            $this->shift($fixedId, $to + 1, $from - 1, 1);
            return;
        }
    }

    private function shift($fixedId, $fromRank, $toRank, $offset) {
        $intervalSubQuery = "rank <= {$toRank} AND rank >= {$fromRank}";
        if($fromRank === $toRank) {
            $intervalSubQuery = "rank = {$fromRank}";
        }

        $sqlQuery =
            "UPDATE {$this->table} " .
            "SET rank = (rank + ({$offset})) " .
            "WHERE " . $intervalSubQuery . " " .
            "AND idparent = {$this->parentId} " .
            "AND id != {$fixedId}";

        $this->db->execute($sqlQuery);
    }

    private function setRank($oldRank, $newRank) {
        $sqlQuery =
            "UPDATE {$this->table} " .
            "SET rank = {$newRank} " .
            "WHERE rank = {$oldRank} " .
            "AND idparent = {$this->parentId} ";

        $this->db->execute($sqlQuery);
    }
}