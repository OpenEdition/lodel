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
 *  Logic ServOOConf
 */

require("logic/class.useroptiongroups.php");

class ServOOConfLogic extends UserOptionGroupsLogic {

  /** Constructor
   */
   function ServOOConfLogic() {
     UserOptionGroupsLogic::UserOptionGroupsLogic();
   }


   /**
    * list Action
    */

   function listAction(&$context,&$error)
  { 
    $dao=&getDAO("optiongroups");
    $vo=$dao->find("name='servoo'");
    $context['id']=$vo->id;
    return Logic::listAction($context,$error);
  }

   /**
    * add/edit Action
    */

  function editAction(&$context,&$error)
  { 
    $dao=&getDAO("optiongroups");
    $vo=$dao->find("name='servoo'");
    $context['id']=$vo->id;
    $ret=UserOptionGroupsLogic::editAction($context,$error);

    if ($ret=="_error") return $ret;

    require("servoofunc.php");
    $client=new ServOO();
    if ($client->error_message) {
      if ($context['url']) $error['url']='+';
      if ($context['username'])$error['username']='+';
      if ($context['passwd']) $error['passwd']='+';
    }

    $servoover=$client->version();

    if (preg_match("/^ERROR:/i",$servoover) || $client->error_message) {
      $error['servoo']=$client->error_message;
      return "_error";
    }

    return $ret=="_ok" ? "edit_options" : $ret;
  }


   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */

   // begin{publicfields} automatic generation  //
   function _publicfields() {
     return array("name"=>array("text","+"),
                  "title"=>array("text","+"),
                  "idgroup"=>array("int","+"),
                  "type"=>array("select",""),
                  "userrights"=>array("select","+"),
                  "defaultvalue"=>array("text",""),
                  "comment"=>array("longtext",""));
             }
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //

    function _uniqueFields() {  return array(array("name","idgroup"),);  }
   // end{uniquefields} automatic generation  //


} // class 


/*-----------------------------------*/
/* loops                             */





?>
