<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 *
 *  Home page: http://www.lodel.org
 *
 *  E-Mail: lodel@lodel.org
 *
 *                            All Rights Reserved
 *
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.*/


class DAO {

  /**
   * Table and class name
   */
  var $table;

  /**
   * Table name with the prefix, and potential join for views.
   */
  var $sqltable;

  /**
   * Unique. True if this table use unique id object
   */
  var $unique;


  /** Constructor
   */
   function DAO($table) {
     $this->$table=$table;
     $this->$tableprefix=lq("#_TP_").$table;
   }

   
   /**
    * Main function to add/modify records
    */

   function save(&$vo) // $set,$context=array())

   {
     global $db;

     if ($vo->id>0) { // update
       foreach($vo as $k=>$v) {
	 if (!isset($v)) continue;
	 if ($update) $update.=",";
	 $update.="$k=".$db->qstr($v);
       }
       if ($update)
	 $db->execute("UPDATE ".$this->$sqltable." SET  $update WHERE id='".$vo->id."'") or die($db->errormsg());

     } else { // new !
       $insert="";$values="";
       if ($this->unique) {
	 $vo->id=uniqueid($table);
	 $insert="id";$values="'".$vo->id."'";
       }
       foreach($vo as $k=>$v) {
	 if (!isset($v)) continue;
	 if ($insert) { $insert.=","; $values.=","; }
	 $insert.=$k;
	 $values.=$db->qstr($v);
       }

       if ($insert) {
	 $db->execute("REPLACE INTO ".$this->$sqltable." (".$insert.") VALUES (".$values.")") or die($db->errormsg());
	 if (!$vo->id) $vo->id=$db->insert_id();
       }
     }
     return $vo->id;
   }


   /**
    * Function to get a value object
    */
   function getById($id,$select="*") {
     global $db;
     return $this->find("id='$id'",$select);
   }

   /**
    * Function to get a value object
    */
   function find($criteria,$select="*") {
     global $db;

     if (array_key_exists("status",get_class_vars($this->table."VO"))) $morecriteria=$GLOBALS['rightvisitor'] ? "AND status>-64" : "AND status>0";

     //execute select statement
     $row=$db->getRow("SELECT ".$select." FROM ".$this->$sqltable." WHERE ($criteria) $morecriteria");
     if ($row===false) die($db->errormsg());
     if (!$row) return null;

     // create new vo and call getFromResult
     $this->instantiateObject($vo);
     $this->_getFromResult($vo,$row);

     // return vo
     return $vo;
   }

   /**
    * Function to get many value object
    */
   function findMany($criteria,$order,$select="*") {
     global $db;


     //execute select statement
     $morecriteria=$this->_rightscriteria("select");
     if ($order) $order="ORDER ".$order;

     $result=$db->execute("SELECT ".$select." FROM ".$this->$sqltable." WHERE ($criteria) $morecriteria $order") or die($db->errormsg());

     $i=0;
     $vos=array();
     foreach ($result as $row) {
       //create new vo and
       $this->instantiateObject($vos[$i]);
       // call getFromResult
       $this->_getFromResult($vos[$i],$row);
       $i++;
     }

     // return vo's
     return $vos;
   }


   /**
    * Create a new Value Object
    */
   function &createObject($rankcriteria="")

   {
     $this->instantiateObject($vo);

     if (array_key_exists("rank",$vo)) {
       // initialise the rank
       if ($rankcriteria) $where=" WHERE ".$rankcriteria.$this->_rightscriteria("select");
       $rank=$db->getone("SELECT MAX(rank) FROM ".$this->$sqltable.$where);
       if ($db->errorno()) die($db->errormsg());
       $vo->rank=$rank+1;
     }
     if (array_key_exists("status",$vo)) {
       $vo->status=1;
     }
     return $vo;
   }

   /**
    * Instantiate a new object
    */
   function instantiateObject(&$vo) {
     $vo=new $this->$table."VO"; // the same name as the table. We don't use factory...
   }


   /**
    * Function to delete an object value.
    * @param mixed object or numeric id
    */

   function deleteObject(&$vo) {
     global $db;

     if (is_object($vo)) {
       $id=$vo->id;
       //set id on vo to 0
       $vo->id=0;
     } else {
       $id=$vo;
     }
     //execute delete statement
     $db->execute("DELETE FROM ".$this->$sqltable." WHERE id='$id'".$this->_rightscriteria("delete")) or die($db->errormsg());

     //delete the uniqueid entry if required
     if ($this->unique) {
       deleteuniqueid($id);
     }
   }

   /**
    * Function to delete many object value given a criteria
    */

   function deleteObjects($criteria) {
     global $db;

     $where=" WHERE (".$criteria.") ".$this->_rightscriteria("delete");

     // delete the uniqueid entry if required
     if ($this->unique) {
       // select before deleting
       $result=$db->execute("SELECT id FROM ".$this->$sqltable.$where) or die($db->errormsg());
       // collect the ids
       $ids=array();
       foreach ($result as $row) $ids[]=$row['id'];
       // delete the uniqueid
       deleteuniqueid($ids);
     }
     //execute delete statement
     $db->execute("DELETE FROM ".$this->$sqltable.$where) or die($db->errormsg());
   }




   //! Private from this point

     var $cache_rightscriteria;


   /**
    * @private
    */
   function _getFromResult(&vo, $row) {     
     //fill vo from the database result set
     foreach($row as $k=>$v) {
       $vo->$$k=$v;
     }
   }


   function _rightscriteria ($access) {
     if (!$this->cache_rightscriteria[$access]) {
       if (array_key_exists("status",get_class_vars($this->table."VO"))) {
	 $this->cache_rightscriteria[$access]=$GLOBALS['rightvisitor'] ? " AND status>-64" : " AND status>0";
	 if (($access=="modify" || $access=="delete") && 
	     !$GLOBALS['rightadminlodel']) $this->cache_rightscriteria[$access].=" AND status<32";
       }
     }
     return $this->cache_rightscriteria[$access];
   }
}


/**
 * DAO factory
 *
 */

function &getDAO($table) {
  static $factory; // cache

  if ($factory[$table]) return $factory[$table]; // cache

  require_once($GLOBALS['home']."dao/class.$table.php");
  $daoclass="$tableDAO";
  return $factory[$table]=new $daoclass;
}

?>
