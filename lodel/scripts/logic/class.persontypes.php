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
 *  Logic Persontypes
 */

class PersonTypesLogic extends Logic {

  /** Constructor
   */
   function PersonTypesLogic() {
     $this->Logic("persontypes");
   }


   function isdeletelocked($id,$status=0) 

   {
     global $db;
     $count=$db->getOne(lq("SELECT count(*) FROM #_TP_persons WHERE idtype='$id' AND status>-64"));
     if ($db->errorno())  dberror();
     if ($count==0) {
       return false;
     } else {
       return sprintf(getlodeltextcontents("cannot_delete_hasperson","admin"),$count);
     }
     //) { $error["error_has_entities"]=$count; return "_back"; }
   }


   /**
    * makeSelect
    */

   function makeSelect(&$context,$var)

   {
     switch($var) {
     case "g_type" :
       $g_typefields=array("DC.Creator","DC.Contributor");
       $dao=$this->_getMainTableDAO();
       $types=$dao->findMany("status>0","","g_type,title");     
       foreach($types as $type) { $arr[$type->g_type]=$type->title; }

       $arr2=array(""=>"--");
       foreach($g_typefields as $g_type) {
	 $lg_type=strtolower($g_type);
	 if ($arr[$lg_type]) {
	   $arr2[$lg_type]=$g_type." &rarr; ".$arr[$lg_type];
	 } else {
	   $arr2[$lg_type]=$g_type;
	 }
       }
       renderOptions($arr2,$context['g_type']);
     }
   }



   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */


   function _prepareEdit($dao,&$context)

   {
     // gather information for the following
     if ($context['id']) {
       $this->oldvo=$dao->getById($context['id']);
       if (!$this->oldvo) die("ERROR: internal error in PersonTypesLogic::_prepareEdit");
     }
   }


   /**
    * Used in editAction to do extra operation after the object has been saved
    */
     function _saveRelatedTables($vo,$context) 

   {
     #print_r($vo);
     #print_r($this->oldvo);

     if ($vo->type!=$this->oldvo->type) {
       // name has changed
       $GLOBALS['db']->execute(lq("UPDATE #_TP_tablefields SET name='".$vo->type."' WHERE name='".$this->oldvo->type."' AND type='persons'")) or dberror();
     }
   }

   function _prepareDelete($dao,&$context)

   {     
     // gather information for the following
     $this->vo=$dao->getById($context['id']);
     if (!$this->vo) die("ERROR: internal error in PersonTypesLogic::_prepareDelete");
   }

   function _deleteRelatedTables($id) {
     global $home;

     //require_once($home."typetypefunc.php"); 
     //typetype_delete("persontype","idpersontype='".$id."'");
      
     $dao=&getDAO("tablefields");
     $dao->delete("type='persons' AND name='".$this->vo->type."'");
   }


   // begin{publicfields} automatic generation  //
   function _publicfields() {
     return array("type"=>array("type","+"),
                  "class"=>array("class","+"),
                  "title"=>array("text","+"),
                  "style"=>array("style",""),
                  "g_type"=>array("select",""),
                  "tpl"=>array("tplfile",""),
                  "tplindex"=>array("tplfile",""));
             }
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //

    function _uniqueFields() {  return array(array("type"),);  }
   // end{uniquefields} automatic generation  //


} // class 


/*-----------------------------------*/
/* loops                             */

function loop_entitytypes($context,$funcname)
{ require_once($GLOBALS['home']."typetypefunc.php"); 
  loop_typetable ("entitytype","persontype",$context,$funcname,$_POST['edit'] ? $context['entitytype'] : -1);}




?>
