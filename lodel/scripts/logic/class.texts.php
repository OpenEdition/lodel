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



require_once("translationfunc.php");

/**
 *  Logic Text
 */

class TextsLogic extends Logic {

  /** Constructor
   */
   function TextsLogic() {
     $this->Logic("texts");
   }


   /**
    * add/edit Action
    */

   function editAction(&$context,&$error,$clean=false)

   {
     if ($context['id']) {
     // normal edit
     return Logic::editAction($context,$error);
     }
     // mass edit

     if (is_array($context['contents'])) {
       $dao=$this->_getMainTableDAO();
       foreach ($context['contents'] as $id=>$contents) {
	 if (!is_numeric($id)) continue;
	 $dao->instantiateObject($vo);
	 $vo->contents=preg_replace("/(\r\n\s*){2,}/","<br />",$contents);
	 $vo->id=$id;
	 $vo->status=intval($context['status'][$id]);
	 if (!$vo->status) $vo->status=-1;
	 $dao->save($vo);
       }
       update();
     }


     return "_back";
   }


   /**
    * Function to create the text entry for all the languages
    */

   function createTexts($name,$textgroup)

   {
     global $db;
     $result=$db->execute(lq("SELECT #_TP_translations.lang FROM #_TP_translations LEFT JOIN #_TP_texts ON #_TP_translations.lang=#_TP_texts.lang AND name='".$name."' AND textgroup='".$textgroup."' WHERE #_TP_texts.lang is NULL")) or dberror();
     $dao=$this->_getMainTableDAO();

     while (!$result->EOF) {
       $dao->instantiateObject($vo);
       $vo->name=$name;
       $vo->textgroup=$textgroup;
       $vo->status=1;
       $vo->lang=$result->fields['lang'];
       $dao->save($vo);
       $result->MoveNext();
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
     $this->createTexts($vo->name,$vo->textgroup);   }

   function _deleteRelatedTables($id) {
     // reinitialise le cache surement.
   }



   // begin{publicfields} automatic generation  //   
    function _publicfields() {
     return array("contents"=>array("longtext",""),
                  "lang"=>array("lang","+"),
                  "textgroup"=>array("text","+"));
             }
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //
    function _uniqueFields() {  return array(array("name","lang","textgroup"),);  }
   // end{uniquefields} automatic generation  //


} // class 




?>
