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

require_once($GLOBALS['home']."genericlogic.php");

class Entities_EditionLogic extends GenericLogic {

  /**
   * generic equivalent assoc array
   */
  var $g_name;


  /** Constructor
   */
   function Entities_EditionLogic() {
     $this->GenericLogic("entities");
   }


   /**
    * view an object Action
    */
   function viewAction(&$context,&$error)

   {
     $ret = GenericLogic::viewAction($context,$error);
     
     /////
     function loop_persons_in_entities($context,$funcname)
       {
	 $varname=$context['varname'];
	 if (!$varname) return;

	 $idtype=$context['id'];

	 if (!$context['persons'][$idtype]) {
	   call_user_func("code_alter_$funcname",$context);
	   return;
	 }
	 //search the type
	 //$dao=getDAO("persontypes");
	 //$vo=$dao->find("type='".$varname."'","class,id");
	 //$class=$vo->class;

	 foreach($context['persons'][$idtype] as $degree=>$arr) {
	   $localcontext=array_merge($context,$arr);
	   $localcontext['name']=$name;
	   $localcontext['classtype']="persons";
	   $localcontext['degree']=$degree;
	   call_user_func("code_do_$funcname",$localcontext);
	 }
       }	  
     /////
     function loop_entries_in_entities($context,$funcname) 
       {
	 global $db;

	 $varname=$context['varname'];
	 if (!$varname) return;

	 $idtype=$context['id'];
	 $ref=&$context['entries'][$idtype];

	 // get the entries
	 $result=$db->execute(lq("SELECT * FROM #_TP_entries WHERE idtype='".$idtype."' AND status>-64")) or dberror();
	 while (!$result->EOF) {
	   $localcontext=array_merge($context,$result->fields);
	   $localcontext['checked']=$ref && in_array($result->fields['g_name'],$ref) ? "checked" : "";
	   call_user_func("code_do_$funcname",$localcontext);
	   $result->MoveNext();
	 }

	   /*
	 foreach($context['entries'][$idtype] as $i=>$arr) {
	   $localcontext=array_merge($context,$arr);
	   $localcontext['name']=$name;
	   $localcontext['class']=$class;
	   $localcontext['classtype']="entries";
	   $localcontext['degree']=$i;
	   call_user_func("code_do_$funcname",$localcontext);
	 }
	 */
       }
	 
     /////
     return $ret ? $ret : "_ok";
   }



   /**
    * add/edit Action
    */

   function editAction(&$context,&$error)

   {
     global $user,$home;

     if ($context['cancel']) return $this->cancelAction($context,$error);

     $id=$context['id'];
     $idparent=$context['idparent'];
     $idtype=$context['idtype'];
     $status=intval($context['status']);

     // iduser
     $context['iduser']=!SINGLESITE && $user['adminlodel'] ? 0 : $user['id'];

     require_once($home."entitiesfunc.php");
     if (!checkTypesCompatibility($id,$idparent,$idtype)) {
       $error['idtype']="types_compatibility";
       return "_error";
     }

     // get the class 
     $daotype=getDAO("types");
     $votype=$daotype->getById($context['idtype'],"class,creationstatus");
     $class=$context['class']=$votype->class;

     if (!$this->validateFields($context,$error)) {
       // error.
       // if the entity is imported and will be checked
       // that's fine, let's continue, if not return an error
       if ($status>-64) return "_error";
     }
    
     //lock_write($class,"objets","entity","relations",
     //"entity_personnes","personnes",
     //"entity_entrees","entrees","entrytypes","types");


     // get the dao for working with the object
     $dao=$this->_getMainTableDAO();
     $now=date("Y-m-d H:i:s");

     // create or edit the entity
     if ($id) {
       $new=false;
       $dao->instantiateObject($vo);
       $vo->id=$id;
       // change the usergroup of the entity ?
       if ($user['admin'] && $context['usergroup']) $vo->usergroup=intval($context['usergroup']);
     } else {
       $new=true;
       $vo=$dao->createObject();
       $vo->idparent=$idparent;
       $vo->usergroup=$this->_getUserGroup($context,$idparent);
       $vo->iduser=$context['iduser'];
       $vo->status=$status ? $status : $votype->creationstatus;
       $vo->creationdate=$now;
     }
     $vo->modificationdate=$now;
     // populate the entity
     if ($idtype) $vo->idtype=$idtype;
     $vo->identifier=$context['identifier'];
     if ($this->g_name['dc.title']) $vo->g_title=$context[$this->g_name['dc.title']];

     $id=$context['id']=$dao->save($vo);

     // change the group recursively
     //if ($context['usergrouprec'] && $user['admin']) change_usergroup_rec($id,$usergroup);

     $gdao=getGenericDAO($class,"identity");
     $gdao->instantiateObject($gvo);
     $this->_populateObject($gvo,$context);
     $gvo->identity=$id;
     $this->_moveFiles($id,$this->files_to_move,$gvo);
     $gdao->save($gvo,$new);  // save the related table
     if ($new) $this->_createRelationWithParents($id,$idparent,false);


     $this->_saveRelatedTables($vo,$context);

     if ($status>0) touch(SITEROOT."CACHE/maj");
     //unlock();

     return "_back";
   }

   /**
    *
    */

   function cancelAction($context,$error)
   {
     // detruit la tache en cours
     $context['idtask']=intval($context['idtask']);
     $dao=getDAO("task");
     $dao->deleteObject($context['idtask']);
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
     global $db;

     if (!$vo->status) {
       $dao=$this->_getMainTableDAO();
       $vo=$dao->getById($vo->id,"status");
     }
     if ($vo->status>-64 && $vo->status<-1) $status=-1;
     if ($vo->status>1) $status=1;

     //
     // Entries and Persons
     //
     
     foreach (array("entries"=>"E","persons"=>"G") as $table=>$nature) {
       // put the id's from entrees and autresentrees into idtypes
       $idtypes=$context[$table] ? array_keys($context[$table]) : array();
       if (!$idtypes) continue;

       //if ($context[autresentries]) $idtypes=array_unique(array_merge($idtypes,array_keys($context[autresentries])));
       $logic=getLogic($table);
       foreach ($idtypes as $idtype) {
	 if (!is_numeric($idtype)) continue;
	 $itemscontext=$context[$table][$idtype];
	 if (!$itemscontext) continue;
	 $ids=array();
	 foreach ($itemscontext as $itemcontext) {
	   $itemcontext['idtype']=$idtype;
	   $itemcontext['status']=$status;

	   $ret=$logic->editAction($itemcontext,$error);
	   if ($ret!="_error" && $itemcontext['id']) $ids[]=$itemcontext['id'];
	 }
	 if ($ids) {
	   $values=array();
	   $degree=1;
	   foreach ($ids as $id) $values[]="('".$id."','".$vo->$id."','".$nature."','".($degree++)."')";
	   $db->execute(lq("REPLACE INTO #_TP_relations (id2,id1,nature,degree) VALUES ".join(",",$values))) or dberror();
	 }
       }
     } // foreach entries and persons
   }


   // most of this should be transfered in the entries and persons logic
   function _deleteSoftRelation($ids) {
     global $db;

     $criteria="id1 IN (".join(",",$ids).")";
     $db->execute(lq("DELETE FROM #_TP_relations WHERE $criteria AND nature IN ('G','E')")) or dberror();

     // select all the items not in entities_$table
     // those with status<=1 must be deleted
     // thise with status> must be depublished

     foreach(array("entries","persons") as $table) {
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
	 $dao->getDAO($table);
	 $dao->deleteObject($idstodelete);
       }

       if ($idstounpublish) {
	 $db->execute(lq("UPDATE #_TP_$tables SET status=-abs(status) WHERE id IN (".join(",",$idstounpublish).") AND status>=32")) or dberror();       
       }
     } // tables
   }



   /**
    * return the usergroup for new entity
    */

    function _getUserGroup($context,$idparent)

    {
      global $user,$db;

      if ($user['admin']) { // take it from the context. 
	$usergroup=intval($context['usergroup']);
	if ($usergroup>0) return $usergroup;
      }

      if ($idparent) { // take the group of the parent
	$dao=$this->_getMainTableDAO();
	$vo=$dao->getById($idparent,"usergroup");
	$usergroup=$vo->usergroup;
	if ($db->errorno()) dberror();
	if (!$usergroup) die("ERROR: You have not the rights: (2)");
      } else {
	$usergroup=1;
	# die("ERROR: Only administrator have the rights to add an entity at this level");
      }
      return $usergroup;
    }

    /**
     * $id is the id of the new entity.
     * $idparent is its direct parent.
     */

    function _createRelationWithParents($id,$idparent,$lock=TRUE)

    {
      global $db;
      //if ($lock) lock_write("relations");
      // can't do INSERT SELECT because work on the same table... support for old MySQL version

      $result=$db->execute(lq("SELECT id1,degree FROM #_TP_relations WHERE id2='".$idparent."' AND nature='P'")) or dberror();
      while (!$result->EOF) {
	$values.="('".$result->fields['id1']."','$id','P','".($result->fields['degree']+1)."'),";
	$result->MoveNext();
      }
      $values.="('".$idparent."','".$id."','P',1)";

      $db->execute(lq("REPLACE INTO #_TP_relations (id1,id2,nature,degree) VALUES ".$values)) or dberror();
      //if ($lock) unlock();
    }



   /**
    * Used in viewAction to do extra populate in the context 
    */
   function _populateContextRelatedTables(&$vo,&$context) 

   {
     global $db,$ADODB_FETCH_MODE;
     $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

     foreach (array("entries"=>array("E","identry","entrytypes"),
		    "persons"=>array("G","idperson","persontypes")) as $table => $info) {
       list($nature,$idfield,$type)=$info;
       
       $result=$db->execute(lq("SELECT #_TP_$table.*,#_TP_relations.idrelation,#_TP_$type.class FROM #_TP_$table INNER JOIN #_TP_relations ON id2=#_TP_$table.id INNER JOIN #_TP_$type ON #_TP_$table.idtype=#_TP_$type.id WHERE  id1='".$vo->id."' AND nature='".$nature."'")) or dberror();
       while (!$result->EOF) {
	 $rank=$result->fields['rank'] ? $result->fields['rank'] : (++$rank);
	 $ref=$result->fields;
	 $class=$result->fields['class'];
	 $relatedtable[$class][$result->fields['id']]=&$ref;
	 if ($table=="persons") $relatedrelationtable[$class][$result->fields['idrelation']]=&$ref;

	 $context[$table][$result->fields['idtype']][$rank]=&$ref;
	 $result->MoveNext();
       }
       // load related table
       if ($relatedtable) {
	 foreach ($relatedtable as $class=>$ids) {
	   $result=$db->execute(lq("SELECT * FROM #_TP_".$class." WHERE ".$idfield." IN (".join(",",array_keys($ids)).")")) or dberror();
	   while (!$result->EOF) {
	     $id=$result->fields[$idfield];
	     $ids[$id]=array_merge($ids[$id],$result->fields);
	     $result->MoveNext();
	   }
	 }
       }
       // load relation related table
       if ($relatedrelationtable) {
	 foreach ($relatedrelationtable as $class=>$ids) {
	   $result=$db->execute(lq("SELECT * FROM #_TP_entities_".$class." WHERE idrelation IN (".join(",",array_keys($ids)).")")) or dberror();
	   while (!$result->EOF) {
	     $id=$result->fields['idrelation'];
	     $ids[$id]=array_merge($ids[$id],$result->fields);
	     $result->MoveNext();
	   }
	 }
       }
     } // foreach classtype
     $ADODB_FETCH_MODE = ADODB_FETCH_DEFAULT;
     #print_r($context['persons']);      

  }

   // begin{publicfields} automatic generation  //
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //
   // end{uniquefields} automatic generation  //


} // class 



?>
