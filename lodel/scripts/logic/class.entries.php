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
 *  Logic Entry
 */

require_once($GLOBALS['home']."genericlogic.php");

class EntriesLogic extends GenericLogic {

  /** Constructor
   */
   function EntriesLogic() {
     $this->GenericLogic("entries");
   }


  function viewAction(&$context,&$error)

  {
    if (!$context['id']) $context['status']=32;
    $context['classtype']="entries";
    return GenericLogic::viewAction($context,$error);
  }

   /**
    * list action
    */

  function listAction(&$context,&$error,$clean=false)

  {
    $daotype=&getDAO("entrytypes");
    $votype=$daotype->getById($context['idtype']);
    if (!$votype) die("ERROR: idtype must me known in GenericLogic::viewAction");
    $this->_populateContext($votype,$context['type']);
    return "_ok";
  }

   /**
    * add/edit Action
    */

   function editAction(&$context,&$error,$clean=false)

   {
     global $home;


     $id=$context['id'];
     $idtype=$context['idtype'];
     if (!$idtype) die("ERROR: internal error in EntriesLogic::editAction");
     $status=$context['status'];

     // get the class 
     $daotype=&getDAO("entrytypes");
     $votype=$daotype->getById($idtype,"class,newbyimportallowed,flat");
     $class=$context['class']=$votype->class;

     if ($clean!=CLEAN) {
       if (!$this->validateFields($context,$error)) {
	 // error.
	 // if the entity is imported and will be checked
	 // that's fine, let's continue, if not return an error
	 if ($status>-64) return "_error";
       }
     }
     if (!$this->g_name['index key']) die("ERROR: The generic field 'index key' is required. Please edit your editorial model.");

     // get the dao for working with the object
     $dao=$this->_getMainTableDAO();

     if (isset($context['g_name'])) {
       if (!$context['g_name']) return "_error"; // empty entry!
       // search if the entries exists
       $vo=$dao->find("g_name='".$context['g_name']."' AND idtype='".$idtype."' AND status>-64","id");
       if ($vo->id) {
	 $context['id']=$vo->id;
	 return; // nothing to do.
       } else {
	 $context[$this->g_name['index key']]=$context['g_name'];
       }
     }

     if (!$vo) {
       if ($id) { // create or edit the entity
	 $new=false;
	 $dao->instantiateObject($vo);
	 $vo->id=$id;
       } else {
	 if (!$votype->newbyimportallowed && !$context['forceaddition']) { return "error_"; }
	 $new=true;
	 $vo=$dao->createObject();
	 $vo->status=$status ? $status : -1;
       }
     }
     if ($dao->rights['protect']) $vo->protect=$context['protected'] ? 1 : 0;
     if ($votype->flat) {
       $vo->idparent=0; // force the entry to be at root
     } else {
       $vo->idparent=intval($context['idparent']);
     }
     // populate the entry table
     if ($idtype) $vo->idtype=$idtype;
     $vo->g_name=$context[$this->g_name['index key']];
     $vo->sortkey=makeSortKey($vo->g_name);
     #print_R($vo);
     $id=$context['id']=$dao->save($vo);
     #echo $id;
     #print_R($dao);
     // save the class table
     $gdao=&getGenericDAO($class,"identry");
     $gdao->instantiateObject($gvo);
     $this->_populateObject($gvo,$context);
     $gvo->identry=$id;

     $this->_moveFiles($id,$this->files_to_move,$gvo);
     $gdao->save($gvo,$new);  // save the related table
     
     update();
     //unlock();

     return "_back";
   }


   /**
    * Change rank action
    * Default implementation
    */
   function changeRankAction(&$context,&$error)

   {
     return Logic::changeRankAction(&$context,&$error,"idparent");
   }


  function makeSelect(&$context,$var)

  {
    global $db;

    switch($var) {
    case 'idparent':
      $arr=array();
      $rank=array();
      $parent=array();
      $ids=array(0);
      $l=1;
      do {
	$result=$db->execute(lq("SELECT * FROM #_TP_entries WHERE idtype='".$context['idtype']."' AND idparent ".sql_in_array($ids)." ORDER BY ".$context['type']['sort'])) or dberror();
	$ids=array();
	$i=1;
	while(!$result->EOF) {
	  $id=$result->fields['id'];
	  if ($id!=$context['id']) {
	    $ids[]=$id;	 
	    $fullname=$result->fields['g_name'];
	    $idparent=$result->fields['idparent'];
	    if ($idparent) $fullname=$parent[$idparent]." / ".$fullname;	   
	    $d=$rank[$id]=$rank[$idparent]+($i*1.0)/$l;
	    echo $d," ";
	    $arr["p$d"]=array($id,$fullname);
	    $parent[$id]=$fullname;
	    $i++;
	  }
	  $result->MoveNext();
	}
	$l*=100;
      } while ($ids);
      ksort($arr);
      $arr2=array("0"=>"--"); // reorganize the array $arr
      foreach($arr as $row) {
	$arr2[$row[0]]=$row[1];
      }
      renderOptions($arr2);
      break;
    }
  }



   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */


   /**
    * Used in deleteAction to do extra operation after the object has been deleted
    */
   function _deleteRelatedTables($id) 

  {
    global $db;
    $result=$db->execute(lq("SELECT DISTINCT class FROM #_TP_entrytypes INNER JOIN #_TP_entries ON idtype=#_TP_entrytypes.id WHERE #_TP_entries.id='".$id."'")) or dberror();
		 
    while (!$result->EOF) {
      $class=$result->fields['class'];

      $gdao=&getGenericDAO($class,"identry");
      $gdao->deleteObject($id);

      $result->MoveNext();
    }
    if ($this->idrelation) {
      $gdao=&getDAO("relations","idrelation");
      $gdao->delete("id2 IN ('".join("','",$this->idrelation)."')");
    }

    // delete 
  }
} // class 



?>
