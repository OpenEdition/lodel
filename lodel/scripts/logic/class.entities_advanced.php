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
 *  Logic Entities_Advanced
 */

class Entities_AdvancedLogic extends Logic {

  /**
   * generic equivalent assoc array
   */
  var $g_name;


  /** Constructor
   */
   function Entities_AdvancedLogic() {
     $this->Logic("entities");
   }


   /**
    * view an object Action
    */
   function viewAction(&$context,&$error)

   {
     recordurl();

     $id=$context['id'];
     if (!$id) die("ERROR: give the id ");

     $dao=$this->_getMainTableDAO();
     $vo=$dao->getById($id);
     if (!$vo) die("ERROR: can't find object $id in the table ".$this->maintable);
     $this->_populateContext($vo,$context);

     $daotype=&getDAO("types");
     $votype=$daotype->getById($vo->idtype);
     $this->_populateContext($votype,$context['type']);

     return "_ok";
   }


   function changeStatusAction(&$context,&$error)

   {
     //if (isset($context['rec'])) return changeStatusRecAction($context,$error);

     $status=intval($context['status']);
     $dao=$this->_getMainTableDAO();
     $vo=$dao->find("id='".$context['id']."' AND status*$status>0","status,id");
     if (!$vo) die("ERROR: interface error in Entities_AdvancedLogic::changeStatusAction ");
     $vo->status=$status;
     $dao->save($vo);

     update();
     return "_back";
   }

   /**
    * Move an entities in another entities
    *
    */

   function prepareMoveAction(&$context,&$error)

   {
     function loop_move_right(&$context,$funcname)
     {
       static $cache,$idtypes;
       global $db,$home;
       if (!isset($cache[$context['idtype']])) {
	 $idtype=$idtypes[$context['iddocument']];
	 if (!$idtype) { // get the type, we don't have it!
	   
	   $dao=&getDAO("entities");
	   $vo=$dao->getById($context['iddocument'],"idtype");
	   $idtype=$idtypes[$context['iddocument']]=$vo->idtype;
	 }
	 $condition=$db->getOne(lq("SELECT condition FROM #_TP_entitytypes_entitytypes WHERE identitytype='".$idtype."' AND identitytype2='".$context['idtype']."'"));
	 $cache[$context['idtype']]=(boolean)$condition;
	 if ($db->errorno()) dberror();
       } //

       if ($cache[$context['idtype']]) {
	 if (function_exists("code_do_$funcname")) 
	   call_user_func("code_do_$funcname",$context);
       } else {
	 if (function_exists("code_alter_$funcname")) 
	   call_user_func("code_alter_$funcname",$context);
       }
     }

     return "move";
   }

   /**
    * Move an entities in another entities
    *
    */

   function moveAction(&$context,&$error)

   {
     global $db,$home;

     $id=$context['id']; // which entities
     $idparent=intval($context['idparent']); // where to move it


     ##lock_write("entites","relations","typeentites_typeentites","entites as parent","entites as fils");

     require_once($home."entitiesfunc.php");
     if (!checkTypesCompatibility($id,$idparent)) die("ERROR: Can move the entities $id into $idparent. Check the editorial model.");

     //
     // yes we have the right, move the entities
     //

     $dao=$this->_getMainTableDAO();
 
     $dao->instantiateObject($vo);
     $vo->id=$id;
     $vo->rank=0; // recalculate
     $vo->idparent=$idparent;
     $dao->save($vo);

     if ($db->affected_Rows()>0) { // effective change
	 //
	 // get the new parent hierarchy
	 //
	 $result=$db->execute(lq("SELECT id1,degree FROM #_TP_relations WHERE id2='$idparent' AND nature='P'")) or dberror();
	 
	 $values="";
	 $dmax=0;
	 while (!$result->EOF) {
	   $id1=$result->fields['id1'];
	   $degree=$result->fields['degree'];

	   $parents[$degree]=$id1;
	   if ($degree>$dmax) $dmax=$degree;
	   $values.="('".$id1."','".$id."','P','".($degree+1)."'),";
	   $result->MoveNext();
	 }
	 $parents[0]=$idparent;
	 //
	 // search for the children
	 //
	 $result=$db->execute(lq("SELECT id2,degree FROM #_TP_relations WHERE id1='$id' AND nature='P'")) or dberror();

	 $delete="";
	 while (!$result->EOF) {
	   $id1=$result->fields['id2'];
	   $degree=$result->fields['degree'];

	   $delete.=" (id2='".$id2."' AND degree>".$degree.") OR "; // remove all the parent above $id.
	   for ($d=0; $d<=$dmax; $d++) { // fore each degree
	     $values.="('".$parents[$d]."','".$id2."','P','".($degree+$d+1)."'),"; // add all the parent
	   }
	   $result->MoveNext();
	 }
	 
	 $delete.=" id2='".$id."' ";
	 $values.="('".$idparent."','".$id."','P',1)";
	 
	 // delete the relation to the parent 
	 $db->execute(lq("DELETE FROM #_TP_relations WHERE (".$delete.") AND nature='P'")) or dberror();
	 $db->execute(lq("REPLACE INTO #_TP_relations (id1,id2,nature,degree) VALUES ".$values)) or dberror();
	 touch(SITEROOT."CACHE/maj");
       }
       //unlock();

     update();
     return "_back";
   }


   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */


   // begin{publicfields} automatic generation  //
   function _publicfields() {
     return array();
   }
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //

   // end{uniquefields} automatic generation  //


} // class 

/*------------------------------------*/
/* special function                   */

?>
