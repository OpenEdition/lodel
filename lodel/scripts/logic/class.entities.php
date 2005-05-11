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



/**
 *  Logic Entities
 */

class EntitiesLogic extends Logic {

  /**
   * generic equivalent assoc array
   */
  var $g_name;


  /** Constructor
   */
   function EntitiesLogic() {
     $this->Logic("entities");
   }


   /**
    * view an object Action
    */
   function viewAction(&$context,&$error) 
   { die("EntitiesLogic::viewAction"); }


   /**
    * Change rank action
    */
   function changeRankAction(&$context,&$error)

   {
     global $db;

     $id=intval($context['id']);
     $dao=$this->_getMainTableDAO();
     $vo=$dao->getById($id,"idparent");
     $this->_changeRank($id,$context['dir'],"status<64 AND idparent='".$vo->idparent."'");

     update();
     return "_back";
   }

   /**
    * add/edit Action
    */

   function editAction(&$context,&$error)

   {
     die("EntitiesLogic::editAction");
   }


   /**
    * Mass operation
    */
   function massAction(&$context,&$error)

   {
     if (!$context['entity']) return "_back";
     $context['id']=array();
     foreach(array_keys($context['entity']) as $id) $context['id'][]=intval($id);

     if ($context['delete']) {
       return $this->deleteAction($context,$error);
     } elseif ($context['publish']) {
       $context['status']=1;
       return $this->publishAction($context,$error);
     } elseif ($context['unpublish']) {
       $context['status']=-1;
       return $this->publishAction($context,$error);
     }
     trigger_error("unknow mass operation",E_USER_ERROR);
   }


   /**
    * Delete
    */

   function deleteAction(&$context,&$error)

   {     
     global $db;

     // get the entities to modify and ancillary information
     $this->_getEntityHierarchy($context['id'],"write","",$ids,$classes,$softprotectedids);
     if (!$ids) return "_back";
     // needs confirmation ?

     if (!$context['confirm'] && $softprotectedids) {
       $context['softprotectedentities']=$softprotectedids;
       $this->define_loop_protectedentities();
       return "delete_confirm";
     }

     // delete all the entities
     $dao=$this->_getMainTableDAO();
     $dao->deleteObject($ids);
     // delete in the joint table
     foreach(array_keys($classes) as $class) {
       $db->execute(lq("DELETE FROM #_TP_$class WHERE identity ".sql_in_array($ids))) or dberror();
     }
     // delete the relations
     $this->_deleteSoftRelation($ids);

     // delete other relations
     $db->execute(lq("DELETE FROM #_TP_relations WHERE id1 ".sql_in_array($ids)." OR id2 ".sql_in_array($ids))) or dberror();

     // delete the entity from the search_engine table
     $db->execute(lq("DELETE FROM #_TP_search_engine WHERE identity ".sql_in_array($ids))) or dberror();

     update();

     return "_back";
   }

   /**
    * Change the status of one entity. Only publish/unpublish is authorized.
    * Can protect recursively also but should not be used.
    * Do nothing on entites with status below or equal -8.
    */

   function publishAction(&$context,&$error)

   {
     global $db;
     $status=intval($context['status']);
     if ($status==0) die("error publishAction");

     // get the entities to modify and ancillary information
     $access=abs($status)>=32 ? "protect" : "write";
     $this->_getEntityHierarchy($context['id'],$access,"#_TP_entities.status>-8",
				$ids,$classes,$softprotectedids);
     if (!$ids) return "_back";

     // depublish protected entity ? need confirmation.
     if (!$context['confirm'] && $status<0 && $softprotectedids) {
       $context['softprotectedentities']=$softprotectedids;
       $this->define_loop_protectedentities();
       return "unpublish_confirm";
     }
     
     $criteria=" id IN (".join(",",$ids).")";

     // mais attention, il ne faut pas reduire le status quand on publie
     if ($status>0) $criteria.=" AND status<'$status'"; 

     $db->execute(lq("UPDATE #_TP_entities SET status=$status WHERE ".$criteria)) or dberror();

     // changestatus for the relations
     $this->_publishSoftRelation($ids,$status);

     update();
     return "_back";
   }


   /**
    * Change the status of one entity. Only lock/unlock is authorized.
    * Do nothing on entites with status below or equal -1.
    */

   function changeLockAction(&$context,&$error)

   {
     global $db;

     $statuschange= $context['lock']=="on" ? "+16" : "-16";

     // get the entities to modify and ancillary information
     $this->_getEntityHierarchy($context['id'],"write","#_TP_entities.status>-1",
				$ids,$classes,$softprotectedids);
     if (!$ids) return "_back";

     $criteria=" id IN (".join(",",$ids).")";
     if ($statuschange>0) {
       $criteria.=" AND status>=1 AND status<16";
     } else {
       $criteria.=" AND status>=16 AND status<32";
     }

     $db->execute(lq("UPDATE #_TP_entities SET status=status$statuschange WHERE ".$criteria)) or dberror();

     update();
     return "_back";
   }


   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */



   // most of this should be transfered in the entries and persons logic
   function _deleteSoftRelation($ids) {
     global $db;

     $criteria="id1 ".sql_in_array($ids);
     $result=$db->execute(lq("SELECT idrelation,nature FROM #_TP_relations WHERE $criteria AND nature IN ('G','E')")) or dberror();
     $idrelation=array();
     while(!$result->EOF) {
       $nature=$result->fields['nature'];
       $idrelation[$nature][]=$result->fields['idrelation'];
       $result->MoveNext();
     }

     // select all the items not in entities_$table
     // those with status<=1 must be deleted
     // thise with status> must be depublished

     foreach(array_keys($idrelation) as $nature) {
       $idlist=join(",",$idrelation[$nature]);
       $table=$nature=='G' ? "persons" : "entries";
       $db->execute(lq("DELETE FROM #_TP_relations WHERE idrelation IN (".$idlist.")")) or dberror();

       $result=$db->execute(lq("SELECT id,status FROM #_TP_$table LEFT JOIN #_TP_relations ON id2=id WHERE id1 is NULL")) or dberror();
  
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
	 $logic=&getLogic($table);
	 $localcontext=array("id"=>$idstodelete,"idrelation"=>$idrelation[$nature]);
	 $localerror=array();
	 $logic->deleteAction($localcontext,$localerror);
       }

       if ($idstounpublish) {
	 // should be in $table dao or logic
	 $db->execute(lq("UPDATE #_TP_$table SET status=-abs(status) WHERE id IN (".join(",",$idstounpublish).") AND status>=32")) or dberror();       
       }
     } // tables
   }

   // most of this should be transfered in the entries and persons logic
   function _publishSoftRelation($ids,$status) 
   
   {
     global $db;

     $criteria="id1 IN (".join(",",$ids).")";
     $status=$status>0 ? 1 : -1; // dans les tables le status est seulement a +1 ou -1

     $result=$db->execute(lq("SELECT id2,nature FROM #_TP_relations WHERE nature IN ('E','G') AND ".$criteria)) or dberror();

     $ids=array();
     while (!$result->EOF) {
       $ids[$result->fields['nature']][$result->fields['id2']]=true;
       $result->MoveNext();
     }
     if (!$ids) return; // get back, nothing to do
     
     foreach(array_keys($ids) as $nature) {
       $idlist=join(",",array_keys($ids[$nature]));
       $table=$nature=='G' ? "persons" : "entries";

     //------- PUBLISH ---------
       if ($status>0) {
	 // easy, simple case.
	 $db->execute(lq("UPDATE #_TP_$table SET status=abs(status) WHERE id IN ($idlist)")) or dberror();
	 
	 //------- UNPUBLISH ---------
       } else { // status<0
	 // more difficult. Must check whether the items is attached to a publish entities. If yes, it must not be deleted
	 
	 $result=$db->execute(lq("SELECT id1 FROM #_TP_relations INNER JOIN #_TP_entities ON id1=id WHERE #_TP_entities.status>0 AND id2 IN (".$idlist.") AND nature='".$nature."' GROUP BY id2")) or dberror();
	 while (!$result->EOF) {
	   unset($ids[$nature][$result->fields['id1']]); // remove the id from the list to unpublish
	   $result->MoveNext();
	 }
	 if ($ids[$nature]) {
	   $idlist=join(",",array_keys($ids[$nature]));
	   // depublish the items not having being published with another entities.
	   $db->execute(lq("UPDATE #_TP_$table SET status=-abs(status) WHERE id IN ($idlist)")) or dberror();
	 }
       } // status<0
     } // foreach
   }

   /**
      * Get one entitu and all its son for an operation given by access
      * Return the ids, the softprotected entities and the classes they belong
      */

     function _getEntityHierarchy($id,$access,$criteria,&$ids,&$classes,&$softprotectedids) 
     {
       global $db;
       // check the rights to $access the current entity
       $dao=$this->_getMainTableDAO();
       $hasrights="(1 ".$dao->rightsCriteria($access).") as hasrights";

       // get the central object
       if ($criteria) $criteria=" AND ".$criteria;
       $result=$db->execute(lq("SELECT #_TP_entities.id,#_TP_entities.status,$hasrights,class FROM #_entitiestypesjoin_ WHERE #_TP_entities.id ".sql_in_array($id).$criteria));

       // list the entities to delete
       $ids=array();
       $classes=array();
       $softprotectedids=array();

       while (!$result->EOF) {
	 if (!$result->fields['hasrights']) trigger_error("This object is locked. Please report the bug",E_USER_ERROR);
	 if ($result->fields['id']>0) $ids[]=$result->fields['id'];
	 $classes[$result->fields['class']]=true;
	 if ($result->fields['status']>=8) $softprotectedids[]=$result->fields['id'];      
	 $result->MoveNext();
       }

       // check the rights to delete the sons and get their ids
       // criteria to determin if one of the sons is locked
       $result=$db->execute(lq("SELECT #_TP_entities.id,#_TP_entities.status,$hasrights,class FROM #_entitiestypesjoin_ INNER JOIN #_TP_relations ON id2=#_TP_entities.id WHERE id1 ".sql_in_array($id)." AND nature='P' ".$criteria)) or dberror();

       while (!$result->EOF) {
	 if (!$result->fields['hasrights']) trigger_error("This object is locked. Please report the bug",E_USER_ERROR);
	 if ($result->fields['id']>0) $ids[]=$result->fields['id'];
	 $classes[$result->fields['class']]=true;
	 if ($result->fields['status']>=8) $softprotectedids[]=$result->fields['id'];
	 $result->MoveNext();
       }
   }

  function define_loop_protectedentities()
  {
       function loop_protectedentities($context,$funcname) {
	 global $db;
	 $result=$db->execute(lq("SELECT * FROM #_TP_entities WHERE id ".sql_in_array($context['softprotectedentities']))) or dberror();
	 while(!$result->EOF) {
	   $localcontext=array_merge($context,$result->fields);
	   call_user_func("code_do_$funcname",$localcontext);
	   $result->MoveNext();
	 }
       } // loop
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
