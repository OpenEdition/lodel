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



function humanfieldtype($text)

{
  return $GLOBALS[fieldtypes][$text];
}

/***/



/**
 *  Logic Option
 */

class OptionsLogic extends Logic {

  /** Constructor
   */
   function OptionsLogic() {
     $this->Logic("options");
   }


   /**
    * Change rank action
    * Default implementation
    */
   function changeRankAction(&$context,&$error)

   {
     global $db;

     $id=intval($context['id']);
     $dao=$this->_getMainTableDAO();
     $vo=$dao->getById($id,"idgroup");
     $this->_changeRank($id,$context['dir'],"status>0 AND idgroup='".$vo->idgroup."'");
     return "back";
   }


   /**
    *
    */

   function makeSelect(&$context,$var)

   {

     switch($var) {
     case "userrights":
       require_once($GLOBALS['home']."commonselect.php");
       makeSelectUserRights($context['userrights']);
       break;
     case "type" :
       require_once($GLOBALS['home']."commonselect.php");
       makeSelectFieldTypes($context['type']);
       break;
     }
   }
     

   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */

   /**
    * Used in editAction to do extra operation after the object has been saved
    */

   function _saveRelatedTables($vo,$context) 

   {
     // reinitialise le cache surement.
   }



   function _deleteRelatedTables($id) {
     // reinitialise le cache surement.
   }



   // begin{publicfields} automatic generation  //
   function _publicfields() {
     return array("name"=>array("text","+"),
                  "idgroup"=>array("int","+"),
                  "type"=>array("select",""),
                  "userrights"=>array("select","+"),
                  "defaultvalue"=>array("text",""),
                  "comment"=>array("longtext",""),
                  "exportpolicy"=>array("boolean","+"));
             }
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //

    function _uniqueFields() {  return array(array("name"),);  }
   // end{uniquefields} automatic generation  //


} // class 


/*-----------------------------------*/
/* loops                             */





?>
