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

class Entities_EditionLogic extends Logic {

  /**
   * generic equivalent assoc array
   */
  var $g_name;


  /** Constructor
   */
   function Entities_EditionLogic() {
     $this->Logic("entities");
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

     $daotype=getDAO("types");
     $votype=$daotype->getById($idtype);
     $this->_populateContext($votype,$context['type']);
     
     if ($id) {
       $daodatatable=getDAO("datatable",$votype->class);
       $vodatatable=$daodatatable->getById($id);
       if (!$vodatatable) die("ERROR: can't find object $id in the associated table. Please report this bug");
       $this->_populateContext($vodatatable,$context);
       $ret=$this->_populateContextRelatedTables($vo,$context);
     }

     /////

       require_once($GLOBALS['home']."langues.php");
     function loop_edition_fields($context,$funcname) 

     {
       global $db;

       if ($context['classtype']=="persons") {
	 $criteria="class='".$context['class']."' OR class='entities_".$context['class']."'";
       } else {
	 $criteria="idgroup='".$context['id']."'";
       }
       $result=$db->execute(lq("SELECT * FROM #_TP_tablefields WHERE ".$criteria." AND status>0 AND edition!='' AND edition!='none'  AND edition!='importable' ORDER BY rank")) or dberror();

       $haveresult=!empty($result->fields);
       if ($haveresult) call_user_func("code_before_$funcname",$context);

       while (!$result->EOF) {
	 $localcontext=array_merge($context,$result->fields);
	 $name=$result->fields['name'];
	 $localcontext['value']=htmlspecialchars($context[$name]);
	 $localcontext['error']=$context['error'][$name];

	 call_user_func("code_do_$funcname",$localcontext);
	 $result->MoveNext();
       }       
       if ($haveresult) call_user_func("code_after_$funcname",$context);
     }
     /////

     /////
       function loop_mltext($context,$funcname) 
       {
	 if (is_array($context['value'])) {
	   foreach($context['value'] as $lang=>$value) {
	     $localcontext=$context;
	     $localcontext['lang']=$lang;
	     $localcontext['value']=$value;
	     call_user_func("code_do_$funcname",$localcontext);
	   }
	   // pas super cette regexp... mais l argument a deja ete processe !
	 } elseif (preg_match_all("/&lt;r2r:ml lang\s*=&quot;(\w+)&quot;&gt;(.*?)&lt;\/r2r:ml&gt;/s",$context['value'],$results,PREG_SET_ORDER) ||
		   preg_match_all("/<r2r:ml lang\s*=\"(\w+)\">(.*?)<\/r2r:ml>/s",$context['value'],$results,PREG_SET_ORDER)    ) {
	   
	   foreach($results as $result) {
	     $localcontext=$context;
	     $localcontext['lang']=$result[1];
	     $localcontext['value']=$result[2];
	     call_user_func("code_do_$funcname",$localcontext);
	   }
	 }
	 
	 $lang=$context['addlanginmltext'][$context['name']];
	 if ($lang) {
	   $localcontext=$context;
	   $localcontext['lang']=$lang;
	   $localcontext['value']="";
	   call_user_func("code_do_$funcname",$localcontext);
	 }
       }
     /////
       function loop_persons_in_entities($context,$funcname)
       {
	 $varname=$context['varname'];
	 if (!$varname) return;

	 //search the type
	 $dao=getDAO("persontypes");
	 $vo=$dao->find("type='".$varname."'","class,id");
	 $class=$vo->class;

	 foreach($context['persons'][$vo->id] as $rank=>$arr) {
	   $localcontext=array_merge($context,$arr);
	   $localcontext['name']=$name;
	   $localcontext['class']=$class;
	   $localcontext['classtype']="persons";
	   $localcontext['rank']=$rank;
	   call_user_func("code_do_$funcname",$localcontext);
	 }
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

     if ($context['cancel']) return cancelAction($context,$error);

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

     if (!$this->_validateFields($context,$error)) {
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
     
     foreach (array("entries"=>"E","persons"=>"G") as $table=>$nature) {
       // put the id's from entrees and autresentrees into idtypes
       $idtypes=$context[$table] ? array_keys($context[$table]) : array();
       if (!$idtypes) continue;

       //if ($context[autresentries]) $idtypes=array_unique(array_merge($idtypes,array_keys($context[autresentries])));
       $logic=getLogic($table);
       foreach ($idtypes as $idtype) {
	 $itemscontext=$context[$table][$idtype];
	 if (!$itemscontext) continue;
	 $ids=array();
	 foreach ($itemscontext as $itemcontext) {
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
    * Validated the public fields and the unicity as usual and in addition the typescompatibility
    *
    */
   function _validateFields(&$context,&$error) {
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
	 default:
	   die("ERROR: unable to check the validity of the field ".$field->name." of type ".$field->type);
	 } // switch
       } // if valid
       } // foreach files
       return empty($error);
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



function lodel_strip_tags($text,$allowedtags,$k=-1) 

{
  if (is_array($text)) {
    array_walk($text,"lodel_strip_tags",$allowedtags);
    return $text;
  }
  if (is_numeric($allowedtags) && !is_numeric($k)) { $allowedtags=$k; } // for call via array_walk

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
