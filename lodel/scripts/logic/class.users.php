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
 *  Logic User
 */

class UsersLogic extends Logic {

  /** Constructor
   */
   function UsersLogic() {
     $this->Logic("users");
   }



   /**
    * Delete
    * Default implementation
    */

   function deleteAction(&$context,&$error)

   {     
     global $db,$home;

    // check the user has no entries
     $id=intval($context['id']);

     if ($this->isdeletelocked($id)) die("This object is locked for deletion. Please report the bug");
     $dao=$this->_getMainTableDAO();
     if (!$dao->deleteObject($id)) die("ERROR: you don't have the right to delete this user");

     $db->execute(lq("DELETE FROM #_TP_users_usergroups WHERE iduser='$id'")) or die($db->errormsg());

     return "back";
   }


   function isdeletelocked($id,$status=0) 

   {
     global $db;
     $count=$db->getOne(lq("SELECT count(*) FROM #_TP_entities WHERE iduser='$id' AND status>-64"));
     if ($db->errorno)  die($db->errormsg());
     if ($count==0) {
       return false;
     } else {
       return sprintf(getlodeltextcontents("cannot_delete_hasentity","common"),$count);
     }
     //) { $error["error_has_entities"]=$count; return "back"; }
   }


   /**
    * Change rank action
    * Default implementation
    */
   function changeRankAction(&$context,&$error)

   {
   }


   /**
    *
    */

   function makeSelect(&$context,$var)

   {
     switch($var) {
     case "import" :
       foreach($GLOBALS['importdocument'] as $n=>$v) { $arr[]=getlodeltextcontents($v['title']); }
       renderOptions($arr,$context['import']);
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
   }


   // begin{publicfields} automatic generation  //
   function _publicfields() {
     return array("user"=>array("user","+"),
                  "title"=>array("text","+"),
                  "class"=>array("class","+"),
                  "tpl"=>array("tplfile",""),
                  "tplcreation"=>array("tplfile",""),
                  "tpledition"=>array("tplfile",""),
                  "import"=>array("select","+"));
             }
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //

    function _uniqueFields() {  return array(array("user","class"),);  }
   // end{uniquefields} automatic generation  //


} // class 


/*-----------------------------------*/
/* loops                             */





?>
