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


   function isdeletelocked($id,$status=0) 

   {
     if ($GLOBALS['iduser']==$id && 
	 ( ($GLOBALS['site'] && $GLOBALS['userrights']<LEVEL_ADMINLODEL) ||
	   (!$GLOBALS['site'] && $GLOBALS['userrights']==LEVEL_ADMINLODEL))) {
       return getlodeltextcontents("cannot_delete_current_user","common");
     } else {
       return false;
     }
     //) { $error["error_has_entities"]=$count; return "back"; }
   }


   /**
    * make the select for this logic
    */

   function makeSelect(&$context,$var)

   {
     switch($var) {
     case "groups" :
       $dao=getDAO("users_groups");
       $list=$dao->findMany("status>0","rank","id","title");
       foreach($list as $group) {
	 $arr[$group->id]=$group->title;
       }
       renderOptions($arr,$context['groups']);
       break;
     }
   }
 
   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */

   function _saveRelatedTables($vo,$context) 

   {
     if (!$context['groups']) $context['groups']=array(1);

     // change the groups     
     // first delete the group
     $this->_deleteRelatedTables($id);
     // now add the groups
      foreach ($context['groups'] as $group) {
	$group=intval($group);
	$db->execute(lq("INSERT INTO #_TP_users_usergroups (idgroup, iduser) VALUES  ('$groupe','$id')")) or die($db->errormsg());
      }
   }

   function _deleteRelatedTables($id) {
     global $db;
     $db->execute(lq("DELETE FROM #_TP_users_usergroups WHERE iduser='$id'")) or die($db->errormsg());
   }


   function _validateFields(&$context,&$error) {
     global $db;

     // check the user has the right equal or higher to the new user
     if ($GLOBALS['userrights']<$context['userrights']) die("ERROR: You don't have the right to create a user with rights higher than yours");

     // Check the user is not duplicated in the main table...
     if (!usemaindb()) return; // use the main db, return if it is the same as the current one.

     $ret=$db->getOne("SELECT 1 FROM ".lq("#_TP_".$this->maintable)." WHERE status>-64 AND id!='".$context['id']."' AND username='".$context['username']."'");
     if ($db->errorno) die($this->errormsg());
     usecurrentdb();

     if ($ret) {
       $error['username']="1"; // report the error on the first field
       return false;
     } else {
       return true;
     }
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
