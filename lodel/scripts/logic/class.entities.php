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
 *  Logic Entities
 */

class EntitiesLogic extends Logic {

  /** Constructor
   */
   function EntitiesLogic() {
     $this->Logic("entities");
   }


   /**
    * Change rank action
    */
   function changeRankAction(&$context,&$error)

   {
     global $db;

     $id=intval($context['id']);
     $dao=$this->_getMainTableDAO();
     $vo=$dao->getById($id,"idparent");
     $this->_changeRank($id,$context['dir'],"status>0 AND status<64 AND idparent='".$vo->idparent."'");
     return "back";
   }


   /**
    * Delete
    */

   function deleteAction(&$context,&$error)

   {     
     global $db,$user;

     // get the entities to modify and ancillary information
     $this->_getEntityHierarchy($context['id'],"write","",$ids,$classes,$softprotectedids);
     // needs confirmation ?

     if (!$context['confirm'] && $softprotectedids) {
       $context['softprotectedentities']="'".join("','",$softprotectedids)."'";
       return "confirm";
     }

     // delete all the entities
     $dao->deleteObject($ids);
     // delete in the joint table
     foreach(array_keys($classes) as $class) {
       $db->execute(lq("DELETE FROM #_TP_$class WHERE identity IN (".join(",",$ids).")")) or die($db->errormsg());
     }
     // delete the relations
     $this->_deleteSoftRelation($ids,"entries","identry");
     $this->_deleteSoftRelation($ids,"persons","idperson");

     return "back";
   }

   /**
    * Change the status of one entity. Changing the sign of status is not possible with this function.
    */

   function changeStatusAction(&$context,&$error)

   {
     //if (isset($context['rec'])) return changeStatusRecAction($context,$error);

     $status=intval($context['status']);
     $vo->find("id='".$context['id']."' AND status*$status>0","status,id");
     if (!$vo) return;
     $vo->status=$status;
     $this->save($vo);

     // changestatus for the relations
     //$this->_changeStatusSoftRelation($ids,$status,"entries","identry");
     //$this->_changeStatusSoftRelation($ids,$status,"persons","idperson");
   }


   /**
    * Change the status of one entity. Only publish/unpublish is authorized.
    * Can protect recursively also but should not be used.
    * Do nothing on entites with status below or equal -16.
    */

   function publishAction(&$context,&$error)

   {
     global $db;
     $status=intval($context['status']);
     // get the entities to modify and ancillary information
     $access=abs($status)>=32 ? "protect" : "write";
     $this->_getEntityHierarchy($context['id'],$access,"status>-16",
				$ids,$classes,$softprotectedids);

     // depublish protected entity ? need confirmation.
     if (!$context['confirm'] && $status<0 && $softprotectedids) {
       $context['softprotectedentities']="'".join("','",$softprotectedids)."'";
       return "confirm";
     }
     
     $criteria=" id IN (".join(",",$ids).")";

     // mais attention, il ne faut pas reduire le status quand on publie
     if ($status>0) $criteria.=" AND status<'$status'"; 

     $db->execute(lq("UPDATE #_TP_entities SET status=$status WHERE ".$criteria)) or die($db->errormsg());

     // changestatus for the relations
     $this->_changeStatusSoftRelation($ids,$status,"entries","identry");
     $this->_changeStatusSoftRelation($ids,$status,"persons","idperson");
   }


   /**
    * makeSelect
    */
/*
   function makeSelect(&$context,$var)

   {
     switch($var) {
     }
   }*/


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
       typetype_delete("entities","identities='".$context['id']."'");
     }
     typetype_insert($context['entitytype'],$vo->id,"entities");
   }

   function _deleteRelatedTables($id) {
     global $home;

     require_once($home."typetypefunc.php"); 
     typetype_delete("entities","identities='".$id."'");
   }


   function _deleteSoftRelation($ids,$table,$relationfield) {
     global $db;

     $criteria="identity IN (".join(",",$ids).")";
     $db->execute(lq("DELETE FROM #_TP_entites_$table WHERE $criteria")) or die($db->errormsg());

     // select all the items not in entities_$table
     // those with status<=1 must be deleted
     // thise with status> must be depublished

     $result=$db->execute(lq("SELECT id,status FROM #_TP_$table LEFT JOIN #_TP_entites_$table ON id=$relationfield WHERE $relationfield is NULL")) or die($db->errormsg());
  
     $idstodelete=array();
     $idstounpublish=array();
     while (!$result->EOF) {
       if (abs($result->fields['status'])==1) {
	 $idstodelete[]=$result->fields['id']; 
       } else {
	 $idstounpublish[]=$result->fields['id']; 
       }
       $result->MoveNext();
     }

     if ($idstodelete) {
       $dao->getDAO($table);
       $dao->deleteObject($idstodelete);
     }

     if ($idstounpublish) {
       $db->execute(lq("UPDATE #_TP_$tables SET status=-abs(status) WHERE id IN (".join(",",$idstounpublish).") AND status>=32")) or die($db->errormsg());       
     }
   }

   function _changeStatusSoftRelation($ids,$status,$table,$relationfield) {
     global $db;

     $criteria="identity IN (".join(",",$ids).")";
     $status=$status>0 ? 1 : -1; // dans les tables le status est seulement a +1 ou -1

     $result=$db->execute(lq("SELECT $relationfield FROM #_TP_entities_$tables WHERE ".$criteria)) or die($db->errormsg());

     $ids=array();
     while (!$result->EOF) {
       $ids[]=$result->fields['id'];
       $result->MoveNext();
     }
     if (!$ids) return; // get back, nothing to do
     $idlist=join(",",$ids);

     //------- PUBLISH ---------
     if ($status>0) {
       // easy, simple case.
       $db->execute(lq("UPDATE #_TP_$tables SET status=abs(status) WHERE id IN ($idlist)")) or die($db->errormsg());

     //------- UNPUBLISH ---------
     } else { // status<0
       // more difficult. Must check whether the items is attached to a publish entities. If yes, it must not be deleted
       
       $result=$db->execute(lq("SELECT $relationfield FROM #_TP_entites_$tables INNER JOIN #_TP_entities ON identity=id WHERE status>0 AND $relationfield IN ($idlist)")) or die($db->errormsg());
       $ids=array();
       while (!$result->EOF) {
	 $ids[]=$result->fields[$relationfield];
	 $result->MoveNext();
       }
       if ($ids) $criteria="AND id NOT IN (".join(",",$ids).")";
       // depublish the items not having being published with another entities.
       $db->execute(lq("UPDATE #_TP_$tables SET status=-abs(status) WHERE id IN ($idlist) $criteria")) or die($db->errormsg());
     } // status<0
   }

   /**
      * Get one entitu and all its son for an operation given by access
      * Return the ids, the softprotected entities and the classes they belong
      */

   function _getEntityHierarchy($id,$access,$criteria,&$ids,&$classes,&$softprotectedids) {
       // check the rights to delete the current entity
       $id=intval($context['id']);

       $dao=$this->_getMainTableDAO();
       $hasrights=",(1 ".$dao->rightsCriteria($access).") as hasrights";

       // get the central object
       if ($criteria) $criteria="AND ".$criteria;
       $row=$db->getRow("SELECT #_TP_entities.id,#_TP_entities.status,$hasrights,class FROM #_entitiestypesjoin_ WHERE id='".$id."' ".$criteria) or die($db->errormsg());
       if (!$row['hasrights']) die("This object is locked. Please report the bug");

       // list the entities to delete
       $ids=array($id);
       $classes=array($row['class']);
       $softprotectedids=array();
       if ($row['status']>=16) $softprotectedids[]=$id;

       // check the rights to delete the sons and get their ids
       // criteria to determin if on of the sons are locked
       $result=$db->execute("SELECT #_TP_entities.id,#_TP_entities.status,$hasrights,class FROM #_entitiestypesjoin_ INNER JOIN #_TP_relations ON id2=#_TP_entities.id WHERE id1='".$id."' AND nature='P'".$criteria) or die($db->errormsg());


       while (!$result->EOF) {
	 $ids[]=$result->fields['id'];
	 $classes[$result->fields['class']];
	 if ($result->fields['status']>=16) $softprotectedids[]=$row['id'];
       }
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
