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
     if ($error) return; // nothing to do if it is an error.

     $id=intval($context['id']);
     if (!$id) return "_ok"; // just add a new Object

     $dao=$this->_getMainTableDAO();
     $vo=$dao->getById($id);
     if (!$vo) die("ERROR: can't find object $id in the table ".$this->maintable);
     $this->_populateContext($vo,$context);

     $ret=$this->_populateContextRelatedTables($vo,$context);

     return $ret ? $ret : "_ok";
   }

   /**
    * copy an object Action
    */
   function copyAction(&$context,&$error)

   {
     $ret=$this->viewAction($context,$error);

     $copyof=getlodeltextcontents("copyof","common");
     if (isset($context['name'])) {
       $context['name']=$copyof."_".$context['name'];
     } elseif (isset($context['type']) && !is_array($context['type'])) {
       $context['type']=$copyof."_".$context['type'];
     }
     if (isset($context['title'])) {
       $context['title']=$copyof." ".$context['title'];
     } elseif (isset($context['username'])) {
       $context['username']=$copyof."_".$context['username'];
     }
     unset($context['id']);
     return $ret;
   }



   /**
    * add/edit Action
    */

   function editAction(&$context,&$error,$clean=false)

   {
     if ($clean!=CLEAN) {      // validate the forms data
       if (!$this->validateFields($context,$error)) return "_error";
     }

     // get the dao for working with the object
     $dao=$this->_getMainTableDAO();
     $id=$context['id']=intval($context['id']);

     $this->_prepareEdit($dao,$context);
     // create or edit
     if ($id) {
       $dao->instantiateObject($vo);
       $vo->id=$id;
     } else {
       $vo=$dao->createObject();
     }
     if ($dao->rights['protect']) $vo->protect=$context['protected'] ? 1 : 0;

     // put the context into 
     $this->_populateObject($vo,$context);
     if (!$dao->save($vo)) trigger_error("You don't have the rights to modify or create this object",E_USER_ERROR);
     $ret=$this->_saveRelatedTables($vo,$context);

     update();

     return $ret ? $ret : "_back";
   }

   /**
    * Change rank action
    * Default implementation
    * $link the fields to make the define the group within which the rank is done.    
    */
   function changeRankAction(&$context,&$error,$groupfields="",$status="status>0")

   {
     $criterias=array();

     $id=$context['id'];
     if ($groupfields) {
       $dao=$this->_getMainTableDAO();
       $vo=$dao->getById($id,$groupfields);
       foreach(explode(",",$groupfields) as $field)  $criterias[]=$field."='".$vo->$field."'";
     }
     if ($status) $criterias[]=$status;

     $criteria=join(" AND ",$criterias);
     $this->_changeRank($id,$context['dir'],$criteria);

     update();
     
     return "_back";
   }

   /**
    * Delete
    * Default implementation
    */

   function deleteAction(&$context,&$error)

   {     
     global $db,$home;

     $id=$context['id'];
     if ($this->isdeletelocked($id)) trigger_error("This object is locked for deletion. Please report the bug",E_USER_ERROR);
     $dao=$this->_getMainTableDAO();
     $this->_prepareDelete($dao,$context);
     $dao->deleteObject($id);

     $ret=$this->_deleteRelatedTables($id);

     update();

     return $ret ? $ret : "_back";
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
     global $lodeluser;
     // basic
     $dao=$this->_getMainTableDAO();
     
     if (is_numeric($id)) {
       $criteria="id='".$id."'";
       $nbexpected=1;
     } else {
       $criteria="id IN ('".join("','",$id)."')";
       $nbexpected=count($id);
     }
     $nbreal=$dao->count($criteria." ".$dao->rightsCriteria("write"));

     return $nbexpected!=$nbreal;
   }

   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */


   function &_getMainTableDAO() {
     
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
     $vos=$dao->findMany($criteria,"rank $desc, id $desc","id,rank");

     $count=count($vos);
     $newrank=$dir>0 ? 1 : $count;

     #for($i=0; $i<$count; $i++) {
     #  echo $vos[$i]->class,"  ";
     #}
     #echo "<br>";

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
    * Validated the public fields and the unicity.
    * @return return an array containing the error and warning, null otherwise.
    */
   function validateFields(&$context,&$error) 

   {
     global $db;

     require_once("validfunc.php");

     $publicfields=$this->_publicfields();
     foreach($publicfields as $field => $fielddescr) {
       list($type,$condition)=$fielddescr;
       if ($condition=="+" && $type!="boolean" && // boolean false are not transfered by HTTP/POST
	   (
	    !isset($context[$field]) ||   // not set
	    $context[$field]===""  // or empty string
	    )) {
	 $error[$field]="+";
       } elseif ($type=="passwd" && !trim($context[$field]) && $context['id']>0) {
	   // passwd can be empty only if $context[id] exists... it is a little hack but.
	 unset($context[$field]); // remove it
       } else {
	 $valid=validfield($context[$field],$type,"");
	 if ($valid===false) die("ERROR: $type can not be validated in logic.php");
	 if (is_string($valid)) $error[$field]=$valid;      
       }
     }
     if ($error) return false;

     $conditions=array();
     foreach($this->_uniqueFields() as $fields) { // all the unique set of fields
       foreach($fields as $field) { // set of fields which has to be unique.
	 $conditions[]=$field."='".$context[$field]."'";
       }
       // check
       $ret=$db->getOne("SELECT 1 FROM ".lq("#_TP_".$this->maintable)." WHERE status>-64 AND id!='".$context['id']."' AND ".join(" AND ",$conditions));
       if ($db->errorno()) dberror();
       if ($ret) $error[$fields[0]]="1"; // report the error on the first field
     }

     return empty($error);
   }

   function _publicfields() {
     die("call to abstract publicfields");
     return array();
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
    * @protected
    */
   function _populateContext(&$vo,&$context) {
     foreach($vo as $k=>$v) {
       $context[$k]=$v;
     }
   }

   /**
    * Used in editAction to do extra operation before the object is saved.
    * Usually it gather information used after in _saveRelatedTables
    */
   function _prepareEdit($dao,&$context) {}

   /**
    * Used in deleteAction to do extra operation before the object is saved.
    * Usually it gather information used after in _deleteRelatedTables
    */
   function _prepareDelete($dao,&$context) {}

   /**
    * Used in editAction to do extra operation after the object has been saved
    */

   function _saveRelatedTables($vo,&$context) {}

   /**
    * Used in deleteAction to do extra operation after the object has been deleted
    */
   function _deleteRelatedTables($id) {}

   /**
    * Used in viewAction to do extra populate in the context 
    */
   function _populateContextRelatedTables(&$vo,&$context) {}


} // class Logic


/*------------------------------------------------*/

/**
 * Logic factory
 *
 */

function &getLogic($table) {
  static $factory; // cache

  if ($factory[$table]) return $factory[$table]; // cache

  require_once("logic/class.$table.php");
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
    $logic=&getLogic($table);
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
    $logic=&getLogic($table);
    $cache[$table][$id]=$logic->isdeletelocked($id,$status);
  }
  return $cache[$table][$id];
}

?>
