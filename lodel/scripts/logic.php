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
 * Define public field description constant
 */

define("F_REQUIRED",0x100);



/**
 * base class Logic
 */

class Logic {

  /**
   * Table and class name of the central table
   */
  var $maintable;

  //var $jointable;



  /** Constructor
   */
   function Logic($maintable) {
     $this->$maintable=$maintable;
   }


   /**
    * view Action
    */

   function viewAction($context)

   {
     $dao=getMainTableDAO();
     $vo=$dao->createObject();
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

     // get the dao for working with the object
     $dao=getMainTableDAO();

     // create or edit
     if ($context['id']) {
       $vo=$dao->getById(intval($context['id']),"id,status");
       if ($vo) die("ERROR: try to modify an object which does not exists");
     } else {
       $dao->createObject($vo);
     }

    if ($GLOBALS['rightadminlodel']) {
      $newstatus=$context['protected'] ? 32 : 1;
      $vo->status=$vo->status>0 ? $newstatus : -$newstatus;
    }

     // put the context into 
     $this->_populateObject($vo,$context);
     $dao->save($vo);

     return "back";
   }

   /**
    * Change rank action
    * Default implementation
    */
   function changeRankAction($id,$dir)

   {
     $this->_changeRank($id,$dir,"status>0");
   }

   /**
    * Delete
    * Default implementation
    */

   function deleteAction($id)

   {     
     die("Abstract logic deleteAction");
   }


   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */

   function &getMainTableDAO() {
     require_once($GLOBALS['home']."dao.php");
     return getDOA($this->maintable);
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

     $dao=getMainTableDAO();
     $vos=$dao->findMany($criteria,"rank $desc","id,rank");

     $count=count($vos);
     $newrank=$dir>0 ? 1 : $count;

     $i=0; 
     for($i=0; $i<$count; $i++) {
       if ($vos[$i]->id==$id) {
	 // exchange with the next if it exists
	 if (!($vos[$i+1])) break;
	 $vos[$i+1]->rank=$newrank;
	 $dao->save($vos[$i+1]);
	 $newrank+=$dir;
       }
       if ($vos[$i]->rank!=$newrank) { // rebuild the rank if necessary
	 $vos[$i]->rank=$newrank;
	 $dao->save($vos[$i]);
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
       list($type,$condition,$validfunc)=$fielddescr;
       if ($condition=="+" && !$context[$field]) {
	 $error[$field]="required";
       } else {
	 $valid=validfunc($context[$field],$type,"");
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
    * Populate the object from the context. Only the public fields are inputted
    */

   function _populateObject($context) {
     $publicfields=$this->_publicfields();
     foreach($publicfields as $field => $fielddescr) {
       $vo->$field=$context[$field];
     }
   }
}


/**
 * Logic factory
 *
 */

function &getLogic($table) {
  static $factory; // cache

  if ($factory[$table]) return $factory[$table]; // cache

  require_once($GLOBALS['home']."logic/class.$table.php");
  $logicclass="$tableDAO";
  return $factory[$table]=new $logicclass;
}

?>
