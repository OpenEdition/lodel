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


$GLOBALS['importdocument']=array(
				 0=>array("url"=>"document.php",
					  "title"=>"[@ADMIN.FORMS]"),

				 1=>array("url"=>"oochargement.php",
					  "title"=>"ServOO")

				 //				 100=>array("url"=>"biblioimport.php",
				 //					    "titre"=>"BibImport")
				 );


/**
 *  Logic Type
 */

class TypesLogic extends Logic {

  /** Constructor
   */
   function TypesLogic() {
     $this->Logic("types");
   }



   /**
    * Delete
    * Default implementation
    */

   function deleteAction(&$context,&$error)

   {     
     global $db,$home;

    // check the type has no entries
     $id=intval($context['id']);

     if ($this->isdeletelocked($id)) die("This object is locked for deletion. Please report the bug"); //  { return $error['reason']=$ret; return "back"; }

     $dao=$this->_getMainTableDAO();
     if (!$dao->deleteObject($id)) die("ERROR: you don't have the right to delete this type");

     require_once($home."typetypefunc.php"); 
     typetype_delete("entrytype","identitytype='".$id."'");
     typetype_delete("persontype","identitytype='".$id."'");
     typetype_delete("entitytype","identitytype='".$id."'");

     return "back";
   }


   function isdeletelocked($id,$status=0) 

   {
     global $db;
     $count=$db->getOne(lq("SELECT count(*) FROM #_TP_entities WHERE idtype='$id' AND status>-64"));
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
     global $db;

     $id=intval($context['id']);
     $dao=$this->_getMainTableDAO();
     $vo=$dao->getById($id,"class");
     $this->_changeRank($id,$context['dir'],"status>0 AND class='".$vo->class."'");
     return "back";
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
     require_once($GLOBALS['home']."typetypefunc.php");

     if ($context['id']) {
       typetype_delete("entrytype","identitytype='".$context['id']."'");
       typetype_delete("persontype","identitytype='".$context['id']."'");
       typetype_delete("entitytype","identitytype='".$context['id']."'");
     }
     typetype_insert($vo->id,$context['entrytype'],"entrytype");
     typetype_insert($vo->id,$context['persontype'],"persontype");
     typetype_insert($vo->id,$context['entitytype'],"entitytype2");
   }


   // begin{publicfields} automatic generation  //
   function _publicfields() {
     return array("type"=>array("type","+"),
                  "title"=>array("text","+"),
                  "class"=>array("class","+"),
                  "tpl"=>array("tplfile",""),
                  "tplcreation"=>array("tplfile",""),
                  "tpledition"=>array("tplfile",""),
                  "import"=>array("select","+"));
             }
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //

    function _uniqueFields() {  return array(array("type","class"),);  }
   // end{uniquefields} automatic generation  //


} // class 


/*-----------------------------------*/
/* loops                             */


function loop_persontypes($context,$funcname)
{ require_once($GLOBALS['home']."typetypefunc.php"); 
  loop_typetable ("persontype","entitytype",$context,$funcname,$_POST['edit'] ? $context['persontype'] : -1);}

function loop_entrytypes($context,$funcname)
{ require_once($GLOBALS['home']."typetypefunc.php"); 
  loop_typetable ("entrytype","entitytype",$context,$funcname,$_POST['edit'] ? $context['entrytype'] : -1);}

function loop_entitytypes($context,$funcname)
{ require_once($GLOBALS['home']."typetypefunc.php"); 
  loop_typetable ("entitytype2","entitytype",$context,$funcname,$_POST['edit'] ? $context['entitytype'] : -1);}





?>
