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

class PersonsLogic extends Logic {

  /**
   * generic equivalent assoc array
   */
  var $g_name;


  /** Constructor
   */
   function PersonsLogic() {
     $this->Logic("persons");
   }


   /**
    * view an object Action
    */
   function viewAction(&$context,&$error)

   {
     
     $id=$context['id'];
     if ($id) {
       $dao=$this->_getMainTableDAO();
       $vo=$dao->getById($id);
       if (!$vo) die("ERROR: can't find object $id in the table ".$this->maintable);
       $this->_populateContext($vo,$context);
       $idtype=$vo->idtype;
     } else {
       $idtype=$context['idtype'];
     }

     $daotype=getDAO("persontypes");
     $votype=$daotype->getById($idtype);
     $this->_populateContext($votype,$context['type']);
     
     if ($id) {
       $daodatatable=getDAO("datatable",$votype->class,"idperson");
       $vodatatable=$daodatatable->getById($id);
       if (!$vodatatable) die("ERROR: can't find object $id in the associated table. Please report this bug");
       $this->_populateContext($vodatatable,$context);
     }

	 
     /////
     return "_ok";
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
     $status=intval($entitycontext['status']);

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

     $daodatatable=getDAO("datatable",$class);
     $daodatatable->instantiateObject($vodatatable);
     $this->_populateObject($vodatatable,$context);
     $vodatatable->identity=$id;
     $this->_moveFiles($id,$this->files_to_move,$vodatatable);
     $daodatatable->save($vodatatable,$new);  // save the related table
     if ($new) $this->_createRelationWithParents($id,$idparent,false);


     $this->_saveRelatedTables($vo,$context);

     if ($status>0) touch(SITEROOT."CACHE/maj");
     //unlock();

     return "_back";
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
     XXXX
   }


   // most of this should be transfered in the entries and persons logic
   function _deleteSoftRelation($ids) {
     global $db;

     $criteria="id2 IN (".join(",",$ids).")";
     $db->execute(lq("DELETE FROM #_TP_relations WHERE $criteria AND nature IN ('G')")) or dberror();

     XXXXX EFFACER LES RELATED XXXXX
   }


   /**
    * Validated the public fields and the unicity as usual and in addition the typescompatibility
    *
    */
   function validateFields(&$context,&$error) {
     global $home;

     // get the fields of class
     

     $daotablefields=getDAO("tablefields");
     $fields=$daotablefields->findMany("class='".$context['class']."' AND status>0 ","",
				       "name,type,condition,defaultvalue,allowedtags,edition,g_name");

     // file to move once the document id is know.
     $this->files_to_move=array();
     $this->_publicfields=array();
     require_once($home."fieldfunc.php");
     require_once($home."validfunc.php");
     
     foreach ($fields as $field) {
       if ($field->g_name) $this->g_name[$field->g_name]=$field->name; // save the generic field

       // check if the field is required or not, and rise an error if any problem.

       $value=&$context[$field->name];
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
       if ($field->type!="persons" && $field->type!="entries")
	 $this->_publicfields[$field->name]=true; // this field is public

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
	 $type=$field->type;
	 $name=$field->name;
	 // not validated... let's try other type
	 switch($type) {
	 case "mltext" :
	   #print_r($value);
	   #echo ":$value:";
	   if (is_array($value)) {
	     $str="";
	     foreach($value as $lang=>$v) {	       
	       if ($lang!="empty" && $v) $str.="<r2r:ml lang=\"$lang\">$v</r2r:ml>";
	     }
	     $value=$str;
	   }
	   break;
	 case 'image' :
	 case 'file' :
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
	 case 'persons':
	   // get the type
	   $dao=getDAO("persontype");
	   $vo=$dao->find("type='".$name."'","class,id");
	   $idtype=$vo->id;

	   $logic=getLogic("persons"); // the logic is used to validate
	   $localcontext=&$context['persons'][$idtype];
	   $count=count($localcontext);
	   for($i=0; $i<$count; $i++) {
	     $localcontext[$i]['class']=$vo->class;
	     $err=array();
	     $logic->validateFields($localcontext[$i],$err);
	     if ($err) $error['persons'][$idtype][$i]=$err;
	   }
	   break;
	 default:
	   die("ERROR: unable to check the validity of the field ".$field->name." of type ".$field->type);
	 } // switch
       } // if valid
       } // foreach files
       return empty($error);
     }



   // begin{publicfields} automatic generation  //
   function _publicfields() {
     if (!isset($this->_publicfields)) die("ERROR: publicfield has not be created");
     return $this->_publicfields;
   }
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //

   // end{uniquefields} automatic generation  //


} // class 

/*------------------------------------*/
/* special function                   */



/*-----------------------------------*/
/* loops                             */



?>
