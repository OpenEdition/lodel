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
     global $user;
     if ($user['id']==$id && 
	 ( ($GLOBALS['site'] && $user['rights']<LEVEL_ADMINLODEL) ||
	   (!$GLOBALS['site'] && $user['rights']==LEVEL_ADMINLODEL))) {
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
     case "usergroups" :
       require_once($GLOBALS['home']."dao.php");
       $dao=getDAO("usergroups");
       $list=$dao->findMany("status>0","rank,name","id,name");
       $arr=array();
       foreach($list as $group) {
	 $arr[$group->id]=$group->name;
       }
       if (!$arr) $arr[1]="--";
       renderOptions($arr,$context['usergroups']);
       break;
     case "userrights":
       require_once($GLOBALS['home']."commonselect.php");
       makeSelectUserRights($context['userrights'],SINGLESITE);
       break;
     case "lang" :
       // get the language available in the interface
       require_once($GLOBALS['home']."dao.php");
       $dao=getDAO("translations");
       $list=$dao->findMany("status>0 AND textgroups='interface'","rank,lang","lang,title");
       $arr=array();
       foreach($list as $lang) {
	 $arr[$lang->lang]=$lang->title;
       }
       if (!$arr) $arr['fr']="Francais";
       renderOptions($arr,$context['lang']);
     }
   }
 
   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */

   function _populateContextRelatedTables(&$vo,&$context)

   {
     $dao=getDAO("users_usergroups");
     $list=$dao->findMany("iduser='".$vo->id."'","","idgroup");
     $context['usergroups']=array();
     foreach($list as $relationobj) {
       $context['usergroups'][]=$relationobj->idgroup;
     }
   }

   function _saveRelatedTables($vo,$context) 

   {
     global $db;
     if (!$context['usergroups']) $context['usergroups']=array(1);

     // change the usergroups     
     // first delete the group
     $this->_deleteRelatedTables($vo->id);
     // now add the usergroups
      foreach ($context['usergroups'] as $usergroup) {
	$usergroup=intval($usergroup);
	$db->execute(lq("INSERT INTO #_TP_users_usergroups (idgroup, iduser) VALUES  ('$usergroup','$id')")) or dberror();
      }
   }

   function _deleteRelatedTables($id) {
     global $db;
     $db->execute(lq("DELETE FROM #_TP_users_usergroups WHERE iduser='$id'")) or dberror();
   }


   function _validateFields(&$context,&$error) {
     global $db,$user;

     if (!Logic::_validateFields($context,$error)) return false;

     // check the user has the right equal or higher to the new user
     if ($user['rights']<$context['userrights']) die("ERROR: You don't have the right to create a user with rights higher than yours");

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
     return array("username"=>array("username","+"),
                  "passwd"=>array("passwd",""),
                  "name"=>array("text",""),
                  "email"=>array("email",""),
                  "userrights"=>array("select","+"),
                  "lang"=>array("lang","+"));
             }
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //

    function _uniqueFields() {  return array(array("username"),);  }
   // end{uniquefields} automatic generation  //

} // class 


/*-----------------------------------*/
/* loops                             */





?>
