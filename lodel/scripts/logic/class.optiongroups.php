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
 *  Logic Optiongroup
 */

class OptiongroupsLogic extends Logic {

  /** Constructor
   */
   function OptiongroupsLogic() {
     $this->Logic("optiongroups");
   }


   function isdeletelocked($id,$status=0) 

   {
     global $db;
     $count=$db->getOne(lq("SELECT count(*) FROM #_TP_options WHERE idgroup='$id' AND status>-64"));
     if ($db->errorno)  dberror();
     if ($count==0) {
       return false;
     } else {
       return sprintf(getlodeltextcontents("cannot_delete_hasoptions","admin"),$count);
     }
     //) { $error["error_has_entities"]=$count; return "back"; }
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
                  "editscript"=>array("text",""),
                  "comment"=>array("longtext",""));
             }
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //

    function _uniqueFields() {  return array(array("name"),);  }
   // end{uniquefields} automatic generation  //


} // class 


/*-----------------------------------*/
/* loops                             */





?>
