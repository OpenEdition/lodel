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
 *  Logic TableField
 */

class ClassesLogic extends Logic {

  /** 
   * Constructor
   */
   function ClassesLogic() {
     $this->Logic("classes");
   }


   function isdeletelocked($id,$status=0) 

   {
     global $db;
     $count=$db->getOne(lq("SELECT count(*) FROM #_entitiestypesjoin_ INNER JOIN #_TP_classes ON #_TP_types.class=#_TP_classes.class WHERE #_TP_classes.id='$id' AND #_TP_entities.status>-64 AND #_TP_types.status>-64  AND #_TP_classes.status>-64"));
     if ($db->errorno)  die($db->errormsg());
     if ($count==0) {
       return false;
     } else {
       return sprintf(getlodeltextcontents("cannot_delete_hasentity","admin"),$count);
     }
     //) { $error["error_has_entities"]=$count; return "back"; }
   }

    

   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */


   function _prepareEdit($dao,&$context)

   {
     // gather information for the following
     if ($context['id']) {
       $this->oldvo=$dao->getById($context['id']);
       if (!$this->oldvo) die("ERROR: internal error in Classes::deleteAction");
     }
   }

   function _saveRelatedTables($vo,$context) 

   {
     global $db;


     //----------------new, create the table
     if (!$this->oldvo->class) {
       $db->execute(lq("CREATE TABLE IF NOT EXISTS #_TP_".$vo->class." ( identity	INTEGER UNSIGNED  UNIQUE, KEY index_identity (identity))")) or die($db->errormsg());
       $alter=true;

       //---------------- change class name ?
     } elseif ($this->oldvo->class!=$vo->class) {
       // change table name 
       $db->execute(lq("RENAME TABLE #_TP_".$this->oldvo->class." TO #_TP_".$vo->class)) or die($db->errormsg());
       // update tablefields, objects and types
       foreach(array("objects","types","tablefields") as $table) {
	 $db->execute(lq("UPDATE #_TP_".$table." SET class='".$vo->class."' WHERE class='".$this->oldvo->class."'")) or die($db->errormsg());
       }
       $alter=true;
     }

     if ($alter) {        // update the CACHE ?
       require_once($GLOBALS['home']."cachefunc.php");
       removefilesincache(SITEROOT,SITEROOT."lodel/edition",SITEROOT."lodel/admin");
     }
   }


   function _prepareDelete($dao,&$context)

   {     
     // gather information for the following
     $this->vo=$dao->getById($context['id']);
     if (!$this->vo) die("ERROR: internal error in Classes::deleteAction");
   }

   function _deleteRelatedTables($id)

   {
     global $db,$home;
     if (!$this->vo) die("ERROR: internal error in Classes::deleteAction");
     $db->execute(lq("DROP TABLE #_TP_".$this->vo->class)) or die($db->errormsg());

     // delete associated types
     // collect the type to delete
     $dao=getDAO("types");
     $types=$dao->findMany("idclass='".$id."'","id");
     $ids=array();
     foreach($types as $type) $ids[]=$type->id;

     $dao->deleteObject($ids);
     $logic=getLogic("types");
     $logic->_deleteRelatedTables($ids);
 
     unset($this->vo);

     // should be in the view....
     require_once($home."cachefunc.php");
     removefilesincache(SITEROOT,SITEROOT."lodel/edition",SITEROOT."lodel/admin");
     //

     return "back";
   }


   // begin{publicfields} automatic generation  //
   function _publicfields() {
     return array("class"=>array("class","+"),
                  "title"=>array("text","+"),
                  "comment"=>array("longtext",""));
             }
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //

    function _uniqueFields() {  return array(array("class"),);  }
   // end{uniquefields} automatic generation  //


} // class 


/*-----------------------------------*/
/* loops                             */


?>
