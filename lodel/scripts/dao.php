<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 *  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
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
   * Uniqueid. True if this table use unique id object
   */
  var $uniqueid;

  /**
   * Assoc array with the right level required to read, write, protect
   */
  var $rights;

  /**
   * Assoc array with the right level required to read, write, protect
   */
  var $idfield;


  /** Constructor
   */
  function DAO($table,$uniqueid=false,$idfield="id") {
    $this->table=$table;
    $this->sqltable=lq("#_TP_").$table;
    $this->uniqueid=$uniqueid;
    $this->idfield=$idfield;
  }

   
   /**
    * Main function to add/modify records
    */

  function save(&$vo,$forcecreate=false) // $set,$context=array())

   {
     global $db,$lodeluser;
     $idfield=$this->idfield;

     // check the user has the basic right for modifying/creating an object
     if ($lodeluser['rights']<$this->rights['write']) die("ERROR: you don't have the right to modify objects from the table ".$this->table);
     // check the user has the right to protect the object
     if ( ( (isset($vo->status) && ($vo->status>=32 || $vo->status<=-32)) || $vo->protect) 
	  && $lodeluser['rights'] < $this->rights['protect']) {
       die("ERROR: you don't have the right to protect objects from the table ".$this->table);
     }
     if (isset($vo->rank) && $vo->rank==0) {
       // initialise the rank
       $rank=$db->getOne("SELECT MAX(rank) FROM ".$this->sqltable." WHERE status>-64");
       if ($db->errorno()) dberror();
       $vo->rank=$rank+1;
     }
     //
     if ($vo->$idfield >0 && !$forcecreate) { // update
       $update="";
       if (isset($vo->protect)) { // special processing for the protection
	 $update="status=(2*(status>0)-1)".($vo->protect ? "*32" : "");
	 unset($vo->status);
	 unset($vo->protect);
       }
       foreach($vo as $k=>$v) {
	 if (!isset($v) || $k==$idfield) continue;
	 if ($update) $update.=",";
	 $update.="$k='".$v."'";
       }
       #echo "debug ghislain:",$update;
       if ($update) {
	 $db->execute("UPDATE ".$this->sqltable." SET  $update WHERE ".$idfield."='".$vo->$idfield."' ".$this->rightscriteria("write")) or dberror();
       }

     } else { // new !
       if (isset($vo->protect)) { // special processing for the protection
	 $vo->status=($vo->status > 0 ? 1 : -1)*($vo->protect ? 32 : 1);
	 unset($vo->protect);
       }

       $insert="";$values="";
       if ($this->uniqueid && !$vo->$idfield) $vo->$idfield=uniqueid($this->table);

       foreach($vo as $k=>$v) {
	 if (!isset($v)) continue;
	 if ($insert) { $insert.=","; $values.=","; }
	 $insert.=$k;
	 $values.="'".$v."'";
       }
       #echo "debug ghislain:",$values;
       if ($insert) {
	 $db->execute("REPLACE INTO ".$this->sqltable." (".$insert.") VALUES (".$values.")") or dberror();
	 if (!$vo->$idfield) $vo->$idfield=$db->insert_id();
       }
     }
     return $vo->$idfield;
   }

   /**
    * Quote the field in the object
    */

  function quote(&$vo)

   {
     foreach($vo as $k=>$v) {
       if (isset($v)) $vo->$k=addslashes($v);
     }
   }

   /**
    * Function to get a value object
    */
   function getById($id,$select="*") {
     return $this->find($this->idfield."='$id'",$select);
   }

  /**
   * Function to get many value object
   */
  function getByIds($ids,$select="*") {
    return $this->findMany($this->idfield.(is_array($ids) ? " IN ('".join("','",$ids)."')" : "='".$ids."'"),"",$select);
  }


  /**
   * Function to get a value object
   */
  function find($criteria,$select="*") {
     global $db;

     //execute select statement
     $GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_ASSOC;
     $row=$db->getRow("SELECT ".$select." FROM ".$this->sqltable." WHERE ($criteria) ".$this->rightscriteria("read"));
     #echo "SELECT ".$select." FROM ".$this->sqltable." WHERE ($criteria) ".$this->rightscriteria("read");
     #print_r($row);
     $GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_DEFAULT;
     if ($row===false) dberror();
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
   function findMany($criteria,$order="",$select="*") {
     global $db;


     //execute select statement
     $morecriteria=$this->rightscriteria("read");
     if ($order) $order="ORDER BY ".$order;
     $GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_ASSOC;
    # echo "SELECT ".$select." FROM ".$this->sqltable." WHERE ($criteria) ".$morecriteria." ".$order;
     $result=$db->execute("SELECT ".$select." FROM ".$this->sqltable." WHERE ($criteria) ".$morecriteria." ".$order) or dberror();
     $GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_DEFAULT;

     $i=0;
     $vos=array();
     while(!$result->EOF) {
       //create new vo and
       $this->instantiateObject($vos[$i]);
       // call getFromResult
       $this->_getFromResult($vos[$i],$result->fields);
       $i++;
       $result->MoveNext();
     }

     // return vo's
     return $vos;
   }

  /**
   * Return the number of element matching a criteria
   */
  function count($criteria)

  {
    global $db;
    $ret = $db->getOne("SELECT count(*) FROM ".$this->sqltable." WHERE ".$criteria);
    if ($db->errorno()) dberror();
    return $ret;
  }


   /**
    * Create a new Value Object
    */
   function &createObject()

   {
     $this->instantiateObject($vo);
     if (array_key_exists("status",$vo)) {
       $vo->status=1;
     }
     if (array_key_exists("rank",$vo)) {
       $vo->rank=0; // auto
     }
     return $vo;
   }

   /**
    * Instantiate a new object
    */
   function instantiateObject(&$vo) {
     $classname=$this->table."VO";
     $vo=new $classname; // the same name as the table. We don't use factory...
   }


   /**
    * Function to delete an object value.
    * @param mixed object or numeric id or an array of ids or criteria
    */
   function delete($mixed) { return $this->deleteObject($mixed); }

   function deleteObject(&$mixed) {
     global $db;

     if ($GLOBALS['lodeluser']['rights'] < $this->rights['write']) trigger_error("ERROR: you don't have the right to delete object from the table ".$this->table,E_USER_ERROR);

     $idfield=$this->idfield;
     if (is_object($mixed)) {
       $vo=&$mixed;
       $id=$vo->$idfield;
       $criteria=$idfield."='$id'";
       //set id on vo to 0
       $vo->$idfield=0;
       $nbid=1;
     } elseif (is_numeric($mixed) && $mixed>0) {
       $id=$mixed;
       $criteria=$idfield."='$id'";
       $nbid=1;
     } elseif (is_array($mixed)) {
       $id=$mixed;
       $criteria=$idfield." IN ('".join("','",$id)."')";
       $nbid=count($id);
     } elseif (is_string($mixed) && trim($mixed)) {
       $criteria=lq($mixed);
       if ($this->uniqueid) {
	 // select before deleting
	 $result=$db->execute("SELECT id FROM ".$this->sqltable."WHERE ($criteria) ".$this->rightscriteria("write")) or dberror();
	 // collect the ids
	 $id=array();
	 foreach ($result as $row) $id[]=$row['id'];
	 $nbid=count($id);
	 #print_r($id);
	 #$criteria=$idfield." IN ('".join("','",$id)."')";
       } else {
	 $nbid=0; // check we have delete at least one
       }

     } else {
       die("ERROR: DAO::deleteObject does not support the type of mixed");
     }
     //execute delete statement
     $db->execute("DELETE FROM ".$this->sqltable." WHERE ($criteria) ".$this->rightscriteria("write")) or dberror();
     if ($db->affected_Rows()<$nbid) trigger_error("ERROR: you don't have the right to delete some objects in table ".$this->table,E_USER_ERROR);
   // in theory, this is bad in the $mixed is an array because 
   // some but not all of the object may have been deleted
   // in practice, it is an error in the interface. The database may be corrupted (object in fact).

     //delete the uniqueid entry if required
     if ($this->uniqueid) {
       if ($nbid!=count($id)) die("ERROR: internal error in DAO::deleteObject. Please report the bug");
       deleteuniqueid($id);
     }
     return true;
   }

   /**
    * Function to delete many object value given a criteria
    */

   function deleteObjects($criteria) {
     global $db;

     // check the rights
     if ($GLOBALS['lodeluser']['rights']<$this->rights['write']) die("ERROR: you don't have the right to delete object from the table ".$this->table);
     $where=" WHERE (".$criteria.") ".$this->rightscriteria("write");

     // delete the uniqueid entry if required
     if ($this->uniqueid) {
       // select before deleting
       $result=$db->execute("SELECT id FROM ".$this->sqltable.$where) or dberror();
       // collect the ids
       $ids=array();
       foreach ($result as $row) $ids[]=$row['id'];
       // delete the uniqueid
       deleteuniqueid($ids);
     }
	//echo "sql="."DELETE FROM ".$this->sqltable.$where;
     //execute delete statement
     $db->execute("DELETE FROM ".$this->sqltable.$where) or dberror();
     if ($db->Affected_Rows()<=0) return false; // not the rights
     return true;
   }


   /**
    * Return the criteria depending on the write/read access
    *
    */
   function rightsCriteria($access) {
     if (!isset($this->cache_rightscriteria[$access])) {

       $classvars=get_class_vars($this->table."VO");
       if ($classvars && array_key_exists("status",$classvars)) {

	 $status=$this->sqltable.".status";
###	 $this->cache_rightscriteria[$access]=$GLOBALS['lodeluser']['visitor'] ? " AND $status>-64" : " AND $status>0";
	 $this->cache_rightscriteria[$access]=$GLOBALS['lodeluser']['visitor'] ? "" : " AND $status>0";

	 if ($access=="write" && $GLOBALS['lodeluser']['rights'] < $this->rights['protect'])
	   $this->cache_rightscriteria[$access].=" AND $status<32 AND $status>-32 ";
       }
     } else {
       $this->cache_rightscriteria[$access]="";
     }
     return $this->cache_rightscriteria[$access];
   }


   //! Private from this point

   var $cache_rightscriteria;


   /**
    * @private
    */

   function _getFromResult(&$vo, $row) 
   {
     //fill vo from the database result set
     foreach($row as $k=>$v) {
       $vo->$k=$v;
     }
   }
}



?>
