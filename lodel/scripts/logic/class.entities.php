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
    * view an object Action
    */
   function viewAction(&$context,&$error)

   {
     $id=$context['id'];
     if (!$id) return "ok"; // just add a new Object

     $dao=$this->_getMainTableDAO();
     $vo=$dao->getById($id);
     if (!$vo) die("ERROR: can't find object $id in the table ".$this->maintable);
     $this->_populateContext($vo,$context['entity']);

     $daotype=getDAO("types");
     $votype=$daotype->getById($vo->idtype);
     $this->_populateContext($vo,$context['type']);

     $daodatatable=getDAO("datatable",$votype->class);
     $vodatatable=$daodatatable->getById($vo->id);
     $this->_populateContext($vo,$context['entity']);

     $ret=$this->_populateContextRelatedTables($vo,$context);

     return $ret ? $ret : "ok";
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
    * add/edit Action
    */

   function editAction(&$context,&$error)

   {
     global $user;

     $entitycontext=& $context['entity'];
     $idparent=intval($entitycontext['idparent']);
     $idtype=intval($entitycontext['idtype']);
     $status=intval($entitycontext['status']);

     // iduser
     $entitycontext['iduser']=!SINGLESITE && $user['adminlodel'] ? 0 : $user['id'];

     if (!$this->_checkTypesCompatibility($id,$idparent,$idtype)) {
       $error['idtype']="types_compatibility";
       return "error";
     }

     // get the class and create the dao.
     $daotype=getDAO("types");
     $class=$context['class']=$daotype->getById($context['idtype'],"class");

     if (!$this->_validateFields($entitycontext,$error)) {
       // error.
       // if the entity is imported and will be checked
       // that's fine, let's continue, if not return an error
       if ($status>-64) return "error";
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
       $vo=$dao->createObject("idparent='".$idparent."'");
       $vo->idparent=$idparent;
       $vo->usergroup=$this->_getUserGroup($context,$idparent);
       $vo->iduser=$context['iduser'];
       $vo->status=$status;
       $vo->creationdate=$now;
     }
     $vo->modificationdate=$now;
     // populate the entity
     if ($idtype) $vo->idtype=$idtype;
     $vo->identifier=$entitycontext['identifier'];
     $vo->entitytitle=$entitycontext[dc('title')];

     $id=$context['id']=$dao->save($vo);

     // change the group recursively
     //if ($context['usergrouprec'] && $user['admin']) change_usergroup_rec($id,$usergroup);

     $daodatatable=getDAO("datatable",$class);
     $daodatatable->instantiateObject($vodatatable);
     $this->_populateObject($vodatatable,$entitycontext);
     $vodatatable->identity=$id;
  
     $this->_moveFiles($id,$this->files_to_move,$vodatatable);

     $daodatatable->save($vodatatable);  // save the related table
     if ($new) $this->_createRelationWithParents($id,$idparent,false);


     $this->_saveRelatedTables($vo,$context);

     if ($status>0) touch(SITEROOT."CACHE/maj");
     //unlock();

     return $id;
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
       $db->execute(lq("DELETE FROM #_TP_$class WHERE identity IN (".join(",",$ids).")")) or dberror();
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

     $db->execute(lq("UPDATE #_TP_entities SET status=$status WHERE ".$criteria)) or dberror();

     // changestatus for the relations
     $this->_publishSoftRelation($ids,$status,"entries","identry");
     $this->_publishSoftRelation($ids,$status,"persons","idperson");
   }

   /**
    * Move an entities in another entities
    *
    */

   function moveAction(&$context,&$error)

   {
     global $db;

     $id=$context['id']; // which entities
     $idparent=intval($context['idparent']); // where to move it


     ##lock_write("entites","relations","typeentites_typeentites","entites as parent","entites as fils");


     if (!$this->_checkTypesCompatibility($id,$idparent)) die("ERROR: Can move the entities $id into $idparent. Check the editorial model.");

     //
     // yes we have the right, move the entities
     //

     $dao=$this->_getMainTableDAO();
     $this->instantiateObject($vo);
     $vo->id=$id;
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
	   list($id1,$degree)=$result->fields;
	   $parents[$degree]=$id1;
	   if ($degree>$dmax) $dmax=$degree;
	   $values.="('".$id1."','.".$id."','P','".($degree+1)."'),";
	   $result->MoveNext();
	 }
	 $parents[0]=$idparent;
	 
	 //
	 // search for the children
	 //
	 $result=$db->execute(lq("SELECT id2,degree FROM #_TP_relations WHERE id1='$id' AND nature='P'")) or dberror();

	 $delete="";
	 while (!$result->EOF) {
	   list($id2,$degree)=$result->fields;
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
	 $db->execute(lq("INSERT INTO #_TP_relations (id1,id2,nature,degree) VALUES ".$values)) or dberror();
	 touch(SITEROOT."CACHE/maj");
       }
       //unlock();
    
     return "back";
   }
   

   /**
    * makeSelect
    */

   function makeSelect(&$context,$var)

   {
     switch($var) {
       case "entries" :
/*
function makeselectentries_rec($idparent,$rep,$entries,&$context,&$entriestrouvees)

{
  if (!$context[tri]) die ("ERROR: internal error in makeselectentries_rec");
  $result=$db->execute(lq("SELECT id, abrev, name FROM #_TP_entries WHERE idparent='$idparent' AND idtype='$context[id]' ORDER BY $context[sort]")) or dberror();

  while (!$result->EOF) {
    $row=$result->fields;
    $selected=$entries && (in_array($row['abrev'],$entries) || in_array($row['name'],$entries)) ? " selected" : "";
   if ($selected) array_push($entriestrouvees,$row['name'],$row['abrev']);
   $value=$context['useabrevation'] ? $row['abrev'] : $row['name'];
    echo "<option value=\"$value\"$selected>$rep$row[name]</option>\n";
    makeselectentries_rec($row[id],$rep.$row['name']."/",$entries,$context,$entriestrouvees);
    $result->MoveNext();
  }
}*/
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
     if (!$vo->status) {
       $dao=$this->_getMainTableDAO();
       $vo=$dao->getById($vo->id,"status");
     }
     if ($vo->status>-64 && $vo->status<-1) $status=-1;
     if ($vo->status>1) $status=1;

     //
     // Entries and Persons
     //
     
     foreach (array("entries","persons") as $table) {
       // put the id's from entrees and autresentrees into idtypes
       $idtypes=$context[$table] ? array_keys($context[$table]) : array();
       //if ($context[autresentries]) $idtypes=array_unique(array_merge($idtypes,array_keys($context[autresentries])));
       $logic=getLogic($table);
       foreach ($idtypes as $idtype) {
	 $itemscontext=$context[$table][$idtype];
	 if (!$itemscontext) continue;
	 $ids=array();
	 foreach ($itemscontext as $itemcontext) {
	   $ret=$logic->editAction($itemcontext,$error);
	   if ($ret!="error" && $itemcontext['id']) $ids[]=$itemcontext['id'];
	 }
	 if ($ids) {
	   $values=array();
	   if ($table=="entries") {
	     foreach ($ids as $id) $values[]="('".$id."','".$vo->$id."')";
	     $db->execute(lq("INSERT INTO #_TP_entities_".$table." (identry,identity) VALUES ".join(",",$values))) or dberror();
	   } else {
	     foreach ($ids as $id) $values[]="('".$id."','".$vo->$id."','".$idtype."')";
	     $db->execute(lq("INSERT INTO #_TP_entities_".$table." (identry,identity,idtype) VALUES ".join(",",$values))) or dberror();
	   }
	 }
       }
     } // foreach entries and persons
   }


   // most of this should be transfered in the entries and persons logic
   function _deleteSoftRelation($ids,$table,$relationfield) {
     global $db;

     $criteria="identity IN (".join(",",$ids).")";
     $db->execute(lq("DELETE FROM #_TP_entites_$table WHERE $criteria")) or dberror();

     // select all the items not in entities_$table
     // those with status<=1 must be deleted
     // thise with status> must be depublished

     $result=$db->execute(lq("SELECT id,status FROM #_TP_$table LEFT JOIN #_TP_entites_$table ON id=$relationfield WHERE $relationfield is NULL")) or dberror();
  
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
   }

   // most of this should be transfered in the entries and persons logic
   function _publishSoftRelation($ids,$status,$table,$relationfield) {
     global $db;

     $criteria="identity IN (".join(",",$ids).")";
     $status=$status>0 ? 1 : -1; // dans les tables le status est seulement a +1 ou -1

     $result=$db->execute(lq("SELECT $relationfield FROM #_TP_entities_$tables WHERE ".$criteria)) or dberror();

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
       $db->execute(lq("UPDATE #_TP_$tables SET status=abs(status) WHERE id IN ($idlist)")) or dberror();

     //------- UNPUBLISH ---------
     } else { // status<0
       // more difficult. Must check whether the items is attached to a publish entities. If yes, it must not be deleted
       
       $result=$db->execute(lq("SELECT $relationfield FROM #_TP_entites_$tables INNER JOIN #_TP_entities ON identity=id WHERE status>0 AND $relationfield IN ($idlist)")) or dberror();
       $ids=array();
       while (!$result->EOF) {
	 $ids[]=$result->fields[$relationfield];
	 $result->MoveNext();
       }
       if ($ids) $criteria="AND id NOT IN (".join(",",$ids).")";
       // depublish the items not having being published with another entities.
       $db->execute(lq("UPDATE #_TP_$tables SET status=-abs(status) WHERE id IN ($idlist) $criteria")) or dberror();
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
       $row=$db->getRow("SELECT #_TP_entities.id,#_TP_entities.status,$hasrights,class FROM #_entitiestypesjoin_ WHERE id='".$id."' ".$criteria) or dberror();
       if (!$row['hasrights']) die("This object is locked. Please report the bug");

       // list the entities to delete
       $ids=array($id);
       $classes=array($row['class']);
       $softprotectedids=array();
       if ($row['status']>=16) $softprotectedids[]=$id;

       // check the rights to delete the sons and get their ids
       // criteria to determin if on of the sons are locked
       $result=$db->execute("SELECT #_TP_entities.id,#_TP_entities.status,$hasrights,class FROM #_entitiestypesjoin_ INNER JOIN #_TP_relations ON id2=#_TP_entities.id WHERE id1='".$id."' AND nature='P'".$criteria) or dberror();


       while (!$result->EOF) {
	 $ids[]=$result->fields['id'];
	 $classes[$result->fields['class']];
	 if ($result->fields['status']>=16) $softprotectedids[]=$row['id'];
       }
   }


   /**
    * Validated the public fields and the unicity as usual and in addition the typescompatibility
    *
    */
   function _validateFields(&$context,&$error) {
     global $home;

     if (!$this->_checkTypesCompatibility($context['id'],$context['idparent'])) return false;

     // get the fields of class
     require_once($home."dao.php");

     $daotablefields=getDAO("tablefields");
     $fields=$daotablefields->findMany("class='".$context['class']."' AND status>0 ".$criteria,"",
				       "name,type,condition,default,allowedtags,persistence,edition");

     // file to move once the document id is know.
     $this->files_to_move=array();
     $this->_publicfields=array();
     require_once($home."fieldfunc.php");
     
     foreach ($fields as $field) {
       // check if the field is required or not, and rise an error if any problem.

       $value=&$entitycontext[$field->name];
       if (!is_array($value)) $value=trim($value);
       if ($value) $value=lodel_strip_tags($value,$field->allowedtags);

       // is empty ?
       $empty=$type!="boolean" && (             // boolean are always true or false
	    !isset($context[$field->name]) ||   // not set
	    $context[$field->name]==="");       // or empty string
	    

       if ( ($context['edit'] && ($field->edition=="importable" || 
				  $field->edition=="none" || 
				  $field->edition=="display")) ) {
	 // in edition interface and field is not editable in the interface

	 if ($field->condition!="+") { // the field is not required.
	   unset($value);
	   continue;
	 } else {
	   $value=lodel_strip_tags($field->default,$field->allowedtags); // default value
	   $empty=false;
	 }
       }
       if ($context['id']>0 && ( ( $field->condition=="permanent") ||
				 ($field->condition=="defaultnew" && $empty) ) ) {
	 // or a permanent field
	 // or field is empty and the default value must not be used
	 unset($value);
	 continue;
       }
       $this->_publicfields[]=$field->name; // this field is public

       if ($field->condition=="+" && $empty) {
	 $error[$field->name]="+"; // required
	 continue;
       }
       if ($field->edition=="none") unset($value);
       if ($empty) $value=lodel_strip_tags($field->default,$field->allowedtags); // default value

       // clean automatically the fields when required.
       if (!is_array($value) && $GLOBALS['lodelfieldtypes'][$type]['autostriptags']) $value=trim(strip_tags($value));

       // special processing depending on the type.

       $valid=validfield($value,$field->type,$field->default);
       if ($valid===true) {
	 // good, nothing to do.
       } elseif (is_string($valid)) {
	 $error[$name]=$valid; 	 // error
       } else {
	 // not validated... let's try other type
	 switch($type) {
	 case "mltext" :
	   if (is_array($value)) {
	     $str="";
	     foreach($value as $lang=>$value) {
	       $value=lodel_strip_tags(trim($value),$allowedtags);
	       if ($value) $str.="<r2r:ml lang=\"$lang\">$value</r2r:ml>";
	     }
	     $value=$str;
	   }
	   break;
	 case 'image' :
	 case 'fichier' :
	   if ($error[$name]) {  unset($value); break; } // error has been already detected
	   if (is_array($value)) unset($value);
	   if (!$value || $value=="none") { unset($value); break; }
	   // check for a hack or a bug
	   $lodelsource='lodel\/sources';
	   $docannexe='docannexe\/'.$type.'\/([^\.\/]+)';
	   if (!preg_match("/^(?:$lodelsource|$docannexe)\/[^\/]+$/",$value,$dirresult)) die("ERROR: bad filename in $name \"$value\"");
	   // if the filename is not "temporary", there is nothing to do
	   if (!preg_match("/^tmpdir-\d+$/",$dirresult[1])) { unset($value); break; }
	   // add this file to the file to move.
	   $this->files_to_move[$name]=array('filename'=>$value,'type'=>$type,'name'=>$name);           
	   break;
	 default:
	   die("ERROR: unable to check the validity of the field ".$field->name." of type ".$field->type);
	 } // switch
       } // if valid
       } // foreach files
       return empty($error);
     }
   
   /**
    * Check that the type of $id can be in the type of $idparant.
    * if $id=0 (creation of entites), use $idtype .
    */

   function _checkTypesCompatibility($id,$idparent,$idtype=0)
   {
     //
     // check whether we have the right or not to put an entitie $id in the $idparent
     //
     if ($id>0) {
       $table="#_TP_entitytypes_entitytypes INNER JOIN #_TP_entities as son ON identitytype=son.id";
       $criteria="son.id='".$id."'";
     } elseif ($idtype>0) {
       $table="#_TP_entitytypes_entitytypes";
       $criteria="identitytype2='".$idtype."'";
     } else {
       die("ERROR: id=0 and idtype=0 in EntitiesLogic::_checkTypesCompatibility");
     }
     
     if ($idparent>0) { // there is a parent
       $query="SELECT condition FROM ".$jointable." INNER JOIN #_TP_entities as parent ON identitytype2=parent.idtype  WHERE parent.id='".$idparent."' AND ".$criteria;
     } else { // no parent, the base.
       $query="SELECT condition FROM ".$jointable." WHERE identitytype2=0 AND ".$criteria;
     }
       
     $condition=$db->getOne(lq($query));
     if ($db->errorno) dberror();
     return $condition;
   }


   /**
    * return the usergroup for new entity
    */

    function _getUserGroup($context,$idparent)

    {
      global $user;

      if ($user['admin']) { // take it from the context. 
	$usergroup=intval($context['usergroup']);
	if ($usergroup>0) return $usergroup;
      }

      if ($idparent) { // take the group of the parent
	$usergroup=getone("SELECT usergroup FROM #_TP_entities WHERE id='".$idparent."' AND usergroup IN (".$user['groups'].")");
	if ($db->errorno()) dberror();
	if (!$usergroup) die("ERROR: You have not the rights: (2)");

      } else {
	//$usergroup=1;
	 die("ERROR: Only administrator have the rights to add an entity at this level");
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

      $db->execute(lq("INSERT INTO #_TP_relations (id1,id2,nature,degree) VALUES ".$values)) or dberror();
      //if ($lock) unlock();
    }


    function _moveFiles($id,$files_to_move,&$vo)

    {
      foreach ($files_to_move as $file) {
	$src=SITEROOT.$file['filename'];
	$dest=basename($file['filename']); // basename
	if (!$dest) die("ERROR: error in move_files");
	// new path to the file
	$dirdest="docannexe/".$file['type']."/".$id;
	if (!file_exists(SITEROOT.$dirdest)) {
	  if (!@mkdir(SITEROOT.$dirdest,0777 & octdec($GLOBALS['filemask']))) die("ERROR: impossible to create the directory \"$dir\"");
	}
	$dest=$dirdest."/".$dest;
	$vo->$file['name']="'".addslashes($dest)."'";
	if ($src==SITEROOT.$dest) continue;
	rename($src,SITEROOT.$dest);
	chmod (SITEROOT.$dest,0666 & octdec($GLOBALS['filemask']));
	@rmdir(dirname($src)); // do not complain, the directory may not be empty
      }
    }


   /**
    * Used in viewAction to do extra populate in the context 
    */
   function _populateContextRelatedTables(&$vo,&$context) 

   {
     global $db;
     foreach (array("entries"=>"E","persons"=>"G") as $table => $nature) {     
       $result=$db->execute(lq("SELECT * FROM #_TP_$table,#_TP_relations WHERE id2=#_TP_$table.id  AND id1='".$vo->id."' AND nature='".$nature."'")) or dberror();
       while (!$result->EOF) {
	 $rank=$result->fields['rank'] ? $result->fields['rank'] : (++$rank);
	 $context[$table][$result->fields['idtype']][$rank]=$result->fields;
	 $result->MoveNext();
       }
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

/*------------------------------------*/
/* special function                   */



function lodel_strip_tags($text,$allowedtags) 

{
  global $home;
  require_once($home."balises.php");
  static $accepted; // cache the accepted balise;
  global $multiplelevel,$xhtmlgroups;

  // simple case.
  if (!$allowedtags) return strip_tags($text);

  if (!$accepted[$allowedtags]) { // not cached ?
    $accepted[$allowedtags]=array();

    // split the groupe of balises
    $groups=preg_split("/\s*;\s*/",$allowedtags);
    array_push($groups,""); // balises speciales
    // feed the accepted string with accepted tags.
    foreach ($groups as $group) {
      // lodel groups
      if ($multiplelevel[$group]) {
	foreach($multiplelevel[$group] as $k=>$v) { $accepted[$allowedtags]["r2r:$k"]=true; }
      }
	// xhtml groups
      if ($xhtmlgroups[$group]) {
	foreach($xhtmlgroups[$group] as $k=>$v) {
	  if (is_numeric($k)) { 
	    $accepted[$allowedtags][$v]=true; // accept the tag with any attributs
	  } else {
	    // accept the tag with attributs matching unless it is already fully accepted
	    if (!$accepted[$allowedtags][$k]) $accepted[$allowedtags][$k][]=$v; // add a regexp
	  }
	}
      } // that was a xhtml group
    } // foreach group
  } // not cached.

#  print_r($accepted);

  $acceptedtags=$accepted[$allowedtags];

  // the simpliest case.
  if (!$accepted) return strip_tags($text);

  $arr=preg_split("/(<\/?)(\w*:?\w+)\b([^>]*>)/",stripslashes($text),-1,PREG_SPLIT_DELIM_CAPTURE);

  $stack=array(); $count=count($arr);
  for($i=1; $i<$count; $i+=4) {
    #echo htmlentities($arr[$i].$arr[$i+1].$arr[$i+2]),"<br/>";
    if ($arr[$i]=="</") { // closing tag
      if (!array_pop($stack)) $arr[$i]=$arr[$i+1]=$arr[$i+2]="";
    } else { // opening tag
      $tag=$arr[$i+1];
      $keep=false;

#      echo $tag,"<br/>";
      if (isset($acceptedtags[$tag])) {
	// simple case.
	if ($acceptedtags[$tag]===true) { // simple
	  $keep=true;
	} else { // must valid the regexp
	  foreach ($acceptedtags[$tag] as $re) {
	    #echo $re," ",$arr[$i+2]," ",preg_match("/(^|\s)$re(\s|>|$)/",$arr[$i+2]),"<br/>";

	    if (preg_match("/(^|\s)$re(\s|>|$)/",$arr[$i+2])) { $keep=true; break; }
	  }
	}
#	echo "keep:$keep<br/>";
      }
      #echo ":",$arr[$i],$arr[$i+1],$arr[$i+2]," ",htmlentities(substr($arr[$i+2],-2)),"<br/>";
      if (substr($arr[$i+2],-2)!="/>")  // not an opening closing.
	array_push($stack,$keep); // whether to keep the closing tag or not.
      if (!$keep) { $arr[$i]=$arr[$i+1]=$arr[$i+2]=""; }

    }
  }

  // now, we know the accepted tags
  return join("",$arr);
}




/*-----------------------------------*/
/* loops                             */



?>
