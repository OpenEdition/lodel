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
 *  Logic Tasks
 */

class TasksLogic extends Logic {

  /**
   * generic equivalent assoc array
   */
  var $g_name;


  /** Constructor
   */
   function TasksLogic() {
     $this->Logic("tasks");
   }





   /**
    * view an object Action
    */
   function viewAction(&$context,&$error) 
   { die("TasksLogic::viewAction"); }


   /**
    * Change rank action
    */
   function changeRankAction(&$context,&$error)
   { die("TasksLogic::changeRankAction"); }

   /**
    * add/edit Action
    */

   function editAction(&$context,&$error)
   { die("TasksLogic::editAction"); }

   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */


   /**
    * Say whether an object (given by its id and status if possible) is deletable by the current user or not
    */
   function isdeletelocked($id,$status=0)

   {
     global $lodeluser;

     // basic check. Should be more advanced because of the potential conflict between 
     // adminlodel adn othe rusers
     $dao=$this->_getMainTableDAO();
     $vo=$dao->find("id='".$id."' AND user='".$lodeluser['id']."'","id");
     return $vo->id ? false : true ;
   }


   // begin{publicfields} automatic generation  //   
    function _publicfields() {
     return array();
             }
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //

   // end{uniquefields} automatic generation  //


} // class 



/*-----------------------------------*/
/* loops                             */



?>
