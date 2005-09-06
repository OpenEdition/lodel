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

require_once("genericlogic.php");
/**
 *  Logic Persons
 */
class PersonsLogic extends GenericLogic {

	/** Constructor
	 */
	function PersonsLogic () {
		$this->GenericLogic ("persons");
	}


	function viewAction (&$context, &$error) {
		if (!$context['id']) $context['status']=32;
		$context['classtype']="persons";
		return GenericLogic::viewAction ($context, $error);
	}

	/**
	 * list action
	 */
	function listAction (&$context, &$error, $clean=false) {
		$daotype=&getDAO ("persontypes");
		$votype=$daotype->getById ($context['idtype']);
		if (!$votype) die ("ERROR: idtype must me known in GenericLogic::viewAction");
		$this->_populateContext ($votype, $context['type']);
		return "_ok";
	}

	/**
	 * add/edit Action
	 */
	function editAction (&$context, &$error, $clean=false) {
		global $lodeluser, $home;
		$id=$context['id'];
		$idtype=$context['idtype'];
		if (!$idtype) die ("ERROR: internal error in PersonsLogic::editAction");
		$status=$context['status'];
#echo "status=$status"; print_r ($context);
		// get the class 
		$daotype=&getDAO ("persontypes");
		$votype=$daotype->getById ($idtype, "class");
		$class=$context['class']=$votype->class;
		#print_r($context);
		if ($clean!=CLEAN) {
			if (!$this->validateFields ($context, $error)) {
			// error.
			// if the entity is imported and will be checked
			// that's fine, let's continue, if not return an error
				if ($status>-64) return "_error";
			}
		}
		foreach (array ("firstname", "familyname") as $g) {
			$equiv[$g]=$this->getGenericEquivalent($class,$g);
			if (!$equiv[$g]) trigger_error ("ERROR: The generic field $g is required. Please edit your editorial model.",E_USER_ERROR);
			$$g=trim($context['data'][$equiv[$g]]);
		}

		if (!$familyname && !$firstname) { $error[$equiv['familyname']]="+"; return "_error"; }
		// get the dao for working with the object
		$dao=$this->_getMainTableDAO();

		if (!$id && ($familyname || $firstname)) {
			// search if the person exists
			$vo=$dao->find ("g_familyname='". $familyname. "' AND g_firstname='". $firstname. "'  AND idtype='".$idtype."' AND status>-64","id,status");
			if( abs($vo->status) == 32) $context['protected'] = 1; //if protected
			$new=false;
		}

		if (!$vo) {
			if ($id) { //edit
				$new=false;echo "edit!";
				$dao->instantiateObject($vo);
				$vo->id=$id;
			} else { //create
				$new=true; echo "create!";
				$vo=$dao->createObject();
				$vo->status=$status ? $status : -1;
			}
		}
		// populate the persons table
		if ($dao->rights['protect']) $vo->protect=$context ['protected'] ? 1 : 0;
		if ($idtype) $vo->idtype=$idtype;
		$vo->g_firstname=$firstname;
		$vo->g_familyname=$familyname;
		$vo->sortkey=makeSortKey ($familyname." ".$firstname);
		$id=$context['id']=$dao->save($vo);
		// change the group recursively
		//if ($context['usergrouprec'] && $lodeluser['admin']) change_usergroup_rec($id,$usergroup);

		// save the class table
		$gdao=&getGenericDAO ($class,"idperson");
		$gdao->instantiateObject ($gvo);
		$context['data']['id']=$context['id'];
		$this->_populateObject ($gvo,$context['data']);
		$gvo->idperson=$id;
		$this->_moveFiles ($id, $this->files_to_move, $gvo);
		$gdao->save ($gvo,$new);  // save the related table
		// save the entities_class table
		if ($context['identity']) {
			$dao=&getDAO ("relations");
			$vo=$dao->find ("id1='".intval ($context['identity']). "' AND id2='". $id. "' AND nature='G' AND degree='".intval ($context['degree']). "'", "idrelation");
			if (!$vo) {
				$dao->instantiateObject ($vo);
				$vo->id1=intval ($context['identity']);
				$vo->id2=$id;
				$vo->degree=intval ($context['degree']);
				$vo->nature='G';
				$idrelation=$context['idrelation']=$dao->save($vo);
			} else {
				$idrelation=$context['idrelation']=$vo->idrelation;
			}

			$gdao=&getGenericDAO("entities_".$class,"idrelation");
			$gdao->instantiateObject($gvo);
			$this->_populateObject($gvo,$context['data']);
			$gvo->idrelation=$idrelation;
			$gdao->save($gvo,true);  // save the related table
		}

		update();
		return "_back";
	}

   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */


   /**
    * Used in deleteAction to do extra operation before the object is saved.
    * Usually it gather information used after in _deleteRelatedTables
    */
   function _prepareDelete($dao,&$context) 

  {
    global $db;

    // get the classes
    $this->classes=array();
    $result=$db->execute(lq("SELECT DISTINCT class FROM #_TP_persontypes INNER JOIN #_TP_persons ON idtype=#_TP_persontypes.id WHERE #_TP_persons.id ".sql_in_array($context['id']))) or dberror();		 
    while (!$result->EOF) {
      $this->classes[]=$result->fields['class'];
      $result->MoveNext();
    }

    if (isset($context['idrelation'])) {
      $this->idrelation=$context['idrelation'];
    } else {
      $dao=&getDAO("relations");
      $vos=$dao->findMany("id2 ".sql_in_array($context['id']));
      $this->idrelation=array();
      foreach ($vos as $vo) {
	$this->idrelation[]=$vo->idrelation;
      }
    }
  }


   /**
    * Used in deleteAction to do extra operation after the object has been deleted
    */
   function _deleteRelatedTables($id) 

  {
    global $db;

    foreach ($this->classes as $class) {
      $gdao=&getGenericDAO($class,"idperson");
      $gdao->deleteObject($id);

      if ($this->idrelation) {
	$gdao=&getGenericDAO("entities_".$class,"idrelation");
	$gdao->deleteObject($this->idrelation);
      }
    }
    if ($this->idrelation) {
      $dao=&getDAO("relations");
      $dao->delete("idrelation ".sql_in_array($this->idrelation));
    }

    // delete 
  }
}// class 

/*------------------------------------*/
/* special function                   */



/*-----------------------------------*/
/* loops                             */
?>
