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



/**
 * base class Logic
 */

class Logic {

  /**
   * Table and class name of the central table
   */
  var $maintable;

  /**
   * Give the SQL criteria which make a group from the ranking point of view.
   */
  var $rankcriteria;



  /** Constructor
   */
   function Logic($maintable) {
     $this->maintable=$maintable;
   }


   /**
    * view an object Action
    */
   function viewAction(&$context,&$error)

   {
     $id=intval($context['id']);
     if (!$id) return "ok"; // just add a new Object

     $dao=$this->_getMainTableDAO();
     $vo=$dao->getById($id);
     if (!$vo) die("ERROR: can't find object $id in the table ".$this->maintable);
     $this->_populateContext($vo,$context);

     return "ok";
   }



   /**
    * add/edit Action
    */

   function editAction(&$context,&$error)

   {
     // validate the forms data
     if (!$this->_validatePublicFields($context,$error)) {
       return "error";
     }
     // check for unicity
     if (!$this->_validateUniqueFields($context,$error)) {
       return "error";
     }

     // get the dao for working with the object
     $dao=$this->_getMainTableDAO();

     // create or edit
     if ($context['id']) {
       $vo=$dao->getById(intval($context['id']),"id,status");
       if (!$vo) die("ERROR: try to modify an object which does not exists");
     } else {
       $vo=$dao->createObject();
     }
     $newstatus=$context['protected'] ? 32 : 1; // check later if we have the rights
     $vo->status=$vo->status>0 ? $newstatus : -$newstatus;

     // put the context into 
     $this->_populateObject($vo,$context);
     if (!$dao->save($vo)) die("You don't have the rights to modify or create this object");
     $this->_saveRelatedTables($vo,$context);

     return "back";
   }

   /**
    * Change rank action
    * Default implementation
    */
   function changeRankAction(&$context,&$error)

   {
     $id=intval($context['id']);
     $this->_changeRank($id,$context['dir'],"status>0");
     return "back";
   }

   /**
    * Delete
    * Default implementation
    */

   function deleteAction(&$context,&$error)

   {     
     die("Abstract logic deleteAction");
   }


   /**
    * Return the right for a given kind of access
    */
   function rights($access) 

   {
     $dao=$this->_getMainTableDAO();
     return $dao->rights[$access];
   }

   /**
    * Say whether an object (given by its id and status if possible) is deletable by the current user or not
    */
   function isdeletelocked($id,$status=0)

   {
     // basic
     $dao=$this->_getMainTableDAO();
     if (!$status) { // heavy... caching would be better but...
       $vo=$dao->getById(intval($id),"status");
       $status=$vo->status;
     }
     return ($GLOBALS['userrights'] < $dao->rights['write']) ||
       (abs($status)>=32 && $GLOBALS['userrights']< $dao->rights['protect']);
   }

   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */


   function &_getMainTableDAO() {
     require_once($GLOBALS['home']."dao.php");
     return getDAO($this->maintable);
   }
   

   /**
    * Change the rank of an Object
    */


   function _changeRank($id,$dir,$criteria)

   {
     global $db;

     if (is_numeric($dir)) {
       $dir=$dir>0 ? 1 : -1;
     } else {
       $dir=$dir=="up" ? -1 : 1;
     }

     $desc=$dir>0 ? "" : "DESC";

     $dao=$this->_getMainTableDAO();
     $vos=$dao->findMany($criteria,"rank $desc","id,rank");

     $count=count($vos);
     $newrank=$dir>0 ? 1 : $count;
     
     $i=0; 
     for($i=0; $i<$count; $i++) {
       if ($vos[$i]->id==$id) {
	 // exchange with the next if it exists
	 if (!$vos[$i+1]) break;
	 $vos[$i+1]->rank=$newrank;
	 $dao->save($vos[$i+1]);
	 $newrank+=$dir;
       }
       if ($vos[$i]->rank!=$newrank) { // rebuild the rank if necessary
	 $vos[$i]->rank=$newrank;
	 $dao->save($vos[$i]);
       }
       if ($vos[$i]->id==$id) {
	 $i++;
       }
       $newrank+=$dir;
     }
   }
   
   /**
    * Validated the public fields
    * @return return an array containing the error and warning, null otherwise.
    */
   function _validatePublicFields(&$context,&$error) {

     require_once($GLOBALS['home']."validfunc.php");

     $publicfields=$this->_publicfields();
     foreach($publicfields as $field => $fielddescr) {
       list($type,$condition)=$fielddescr;
       if ($condition=="+" && $type!="boolean" && // boolean false are not transfered by HTTP/POST
	   (
	    !isset($context[$field]) ||   // not set
	    $context[$field]===""  // or empty string
	    )) {
	 $error[$field]="+";
       } else {
	 $valid=validfield($context[$field],$type,"");
	 if ($valid===false) die("ERROR: $type can not be validated in logic.php");
	 if (is_string($valid)) $error[$field]=$valid;
       }
     }
     return !isset($error);
   }

   function _publicfields() {
     die("call to abstract publicfields");
     return array();
   }


   /**
    * Validate fields which need unicity
    * rmq: this method is limit between the logic and the dao.
    */

   function _validateUniqueFields(&$context,&$error) {
     global $db;
     // check the unique fields
     
     foreach($this->_uniqueFields() as $fields) { // all the unique set of fields
       foreach($fields as $field) { // set of fields which has to be unique.
	 $conditions[]=$field."='".$context[$field]."'";
       }
       // check
       $ret=$db->getOne("SELECT 1 FROM ".lq("#_TP_".$this->maintable)." WHERE status>-64 AND id!='".$context['id']."' AND ".join(" AND ",$conditions));
       if ($db->errorno) die($this->errormsg());
       if ($ret) $error[$fields[0]]="1"; // report the error on the first field
     }
     return !isset($error);
   }

   function _uniqueFields() {
     return array();
   }


   /**
    * Populate the object from the context. Only the public fields are inputted.
    * @private
    */
   function _populateObject(&$vo,&$context) {
     $publicfields=$this->_publicfields();
     foreach($publicfields as $field => $fielddescr) {
       $vo->$field=$context[$field];
     }
   }

   /**
    * Populate the context from the object. All fields are outputted.
    * @private
    */
   function _populateContext(&$vo,&$context) {
     foreach($vo as $k=>$v) {
       $context[$k]=$v;
     }
   }

   /**
    * Used in editAction to do extra operation after the object has been saved
    */

   function _saveRelatedTables($vo,$context) {}
   } // class Logic


/*------------------------------------------------*/

/**
 * Logic factory
 *
 */

function &getLogic($table) {
  static $factory; // cache

  if ($factory[$table]) return $factory[$table]; // cache

  require_once($GLOBALS['home']."logic/class.$table.php");
  $logicclass=$table."Logic";
  return $factory[$table]=new $logicclass;
}


/**
 * function returning the right for $access in the table $table
 */

function rights($table,$access) 

{
  static $cache;
  if (!isset($cache[$table][$access])) {
    $logic=getLogic($table);
    $cache[$table][$access]=$logic->rights($access);
  }
  return $cache[$table][$access];
}

/**
 * Pipe function to test if an object can be deleted or not
 * (with cache)
 */

function isdeletelocked($table,$id,$status=0)

{
  static $cache;
  if (!isset($cache[$table][$id])) {
    $logic=getLogic($table);
    $cache[$table][$id]=$logic->isdeletelocked($id,$status);
  }
  return $cache[$table][$id];
}

?>
