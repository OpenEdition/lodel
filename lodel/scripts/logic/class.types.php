<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 *  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
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
					  "title"=>"[@COMMON.FORM]"),

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
    * view an object Action
    */
   function viewAction(&$context,&$error)

   {
     if ($error) return;
     if (!$context['id']) {
       // creation
       $context['creationstatus']=-1;
       $context['search']=1;
       $context['tpledition']="edition";
       $context['tplcreation']="entities";
       return "_ok";
     }
     return Logic::viewAction($context,$error);
   }


   function isdeletelocked($id,$status=0) 

   {
     global $db;
     $count=$db->getOne(lq("SELECT count(*) FROM #_TP_entities WHERE idtype='$id' AND status>-64"));
     if ($db->errorno())  dberror();
     if ($count==0) {
       return false;
     } else {
       return sprintf(getlodeltextcontents("cannot_delete_hasentity","admin"),$count);
     }
     //) { $error["error_has_entities"]=$count; return "_back"; }
   }


   /**
    * Change rank action
    * Default implementation
    */
   function changeRankAction(&$context,&$error)

   {
     return Logic::changeRankAction(&$context,&$error,"class");
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
     case "display" :
       $arr=array(""=>getlodeltextcontents("folded","admin"),
		  "unfolded"=>getlodeltextcontents("unfolded","admin"),
		  "advanced"=>getlodeltextcontents("advanced_functions","admin")
	      );
       renderOptions($arr,$context['display']);
       break;
     case "creationstatus" :
       $arr=array("-16"=>getlodeltextcontents("draft","common"),
		  "-1"=>getlodeltextcontents("ready_for_publication","common"),
		  "1"=>getlodeltextcontents("published","common"),
		  "16"=>getlodeltextcontents("protected","common"));
       renderOptions($arr,$context['creationstatus']);
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
     require_once("typetypefunc.php");

     if ($context['id']) {
       //typetype_delete("entrytype","identitytype='".$context['id']."'");
       //typetype_delete("persontype","identitytype='".$context['id']."'");
       typetype_delete("entitytype","identitytype='".$context['id']."'");
     }
     //typetype_insert($vo->id,$context['entrytype'],"entrytype");
     //typetype_insert($vo->id,$context['persontype'],"persontype");
     typetype_insert($vo->id,$context['entitytype'],"entitytype2");
   }



   function _deleteRelatedTables($id) {
     global $home;

     require_once("typetypefunc.php"); 

     if (is_array($id)) {
       $criteria="identitytype IN ('".join("','",$id)."')";
     } else {
       $criteria="identitytype='$id'";
     }

     //typetype_delete("entrytype",$criteria);
     //typetype_delete("persontype",$criteria);
     typetype_delete("entitytype",$criteria);
   }



   // begin{publicfields} automatic generation  //   
    function _publicfields() {
     return array("type"=>array("type","+"),
                  "title"=>array("text","+"),
                  "class"=>array("class","+"),
                  "tpl"=>array("tplfile",""),
                  "tplcreation"=>array("tplfile",""),
                  "tpledition"=>array("tplfile",""),
                  "import"=>array("select","+"),
                  "creationstatus"=>array("select","+"),
                  "search"=>array("boolean","+"),
                  "display"=>array("select",""));
             }
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //

    function _uniqueFields() {  return array(array("type","class"),);  }
   // end{uniquefields} automatic generation  //


} // class 


/*-----------------------------------*/
/* loops                             */


//function loop_persontypes($context,$funcname)
//{ require_once("typetypefunc.php"); 
//  loop_typetable ("persontype","entitytype",$context,$funcname,$_POST['edit'] ? $context['persontype'] : -1);}
//
//function loop_entrytypes($context,$funcname)
//{ require_once("typetypefunc.php"); 
//  loop_typetable ("entrytype","entitytype",$context,$funcname,$_POST['edit'] ? $context['entrytype'] : -1);}


function loop_entitytypes($context,$funcname)
{ require_once("typetypefunc.php"); 
  #loop_typetable ("entitytype2","entitytype",$context,$funcname,$_POST['edit'] ? $context['entitytype'] : -1);

loop_typetable ("entitytype2","entitytype",$context,$funcname,$context['entitytype']);
}






?>
