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


require_once("logic/class.tablefields.php");
/**
 *  Logic IndexTableField
 */

class IndexTableFieldsLogic extends TableFieldsLogic {

  /** Constructor
   */
   function IndexTableFieldsLogic() {
     $this->Logic("tablefields");
   }


   /**
    * edit/add an object Action
    */
   function editAction(&$context,&$error)

   {
     $context['condition']="*";
     return TableFieldsLogic::editAction($context,$error);
   }


   /**
    *
    */

   function makeSelect(&$context,$var)

   {
     global $home;

     switch($var) {
     case "name" :       
       $dao=&getDAO($context['type']=='entries' ? "entrytypes" : "persontypes");
       $vos=$dao->findMany("status>0","rank,title","type,title");
       foreach($vos as $vo) {
	 $arr[$vo->type]=$vo->title;
       }
       renderOptions($arr,$context['name']);
       break;
     case "edition" :
       $arr=array(
		  "editable"=>getlodeltextcontents("edit_in_the_interface","admin"),
		  "importable"=>getlodeltextcontents("no_edit_but_import","admin"),
		  "none"=>getlodeltextcontents("no_change","admin"),
		  "display"=>getlodeltextcontents("display_no_edit","admin"),
		  );
       renderOptions($arr,$context['edition']);
       break;
     default:
       TableFieldsLogic::makeSelect($context,$var);
       break;
     }
   }

   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */


   function _prepareEdit($dao,&$context)

   {
   }

   function _saveRelatedTables($vo,$context) 

   {
   }


   function _prepareDelete($dao,&$context)

   {     
   }

   function _deleteRelatedTables($id)

   {
   }


   // begin{publicfields} automatic generation  //   
    function _publicfields() {
     return array("name"=>array("select","+"),
                  "class"=>array("class","+"),
                  "title"=>array("text","+"),
                  "type"=>array("class","+"),
                  "edition"=>array("select",""),
                  "comment"=>array("longtext",""),
                  "idgroup"=>array("select","+"));
             }
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //
    function _uniqueFields() {  return array(array("name","class"),);  }
   // end{uniquefields} automatic generation  //


} // class 


/*-----------------------------------*/
/* loops                             */

?>
