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

require_once($GLOBALS['home']."logic/class.entities_edition.php");

class Entities_ImportLogic extends Entities_EditionLogic {

  /**
   * generic equivalent assoc array
   */
  var $g_name;
  var $prefixregexp="Pr\.|Dr\.|Mr\.|Ms\.";
  var $context; // save the current context
  var $task;

  /** Constructor
   */
   function Entities_ImportLogic() {
     Entities_EditionLogic::Entities_EditionLogic();
   }

   /**
    * Import edition
    */

   function importAction(&$context,&$error)

   {
     $this->context=&$context;

     $idtask=intval($context['idtask']);
     $this->task=$task=gettask($idtask);
     require_once($GLOBALS['home']."xmlimport.php");

     $idtype=$task['idtype'];
     if (!$idtype) die("ERROR: idtype must be given by task in importAction");
     // get the type 
     $dao=&getDAO("types");
     $votype=$dao->getById($idtype,"class");

     //$this->_init($votype->class);

     $parser=new XMLImportParser();
     $parser->init($votype->class);
     $parser->parse(file_get_contents($task['fichier']),$this);

     // save the file
     if (!$this->id) die("ERROR: internal error in Entities_ImportLogic::importAction");
     $sourcefile=SITEROOT."lodel/sources/entite-".$this->id.".source";
     @unlink($sourcefile);
     copy($task['fichier'],$sourcefile);
     @chmod($sourcefile,0666 & octdec($GLOBALS['filemask']));
     // ok

     #die("la");
     // close the task     
     if ($idtask) {
       $dao=&getDAO("tasks");
       $dao->deleteObject($idtask);
     }

     if ($this->return!="_error" && $context['finish']) {
       return "_back";
     } else {
       header("location: index.php?do=view&id=".$this->id);
       exit();
     }
   }


   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */

   /**
    * method to move img link when the new id is known
    *
    */
   function _moveImages(&$context) { 
     $count=1;
     $dir="";
     $this->_moveImages_rec($context,$dir,$count); 
   }

   function _moveImages_rec(&$context,&$dir,&$count) 

   {
     foreach (array_keys($context) as $k) {
       if (is_array($context[$k])) {
	 $this->_moveImages_rec($context[$k],$dir,$count);
	 continue;
       }
       $text=&$context[$k];
       
       preg_match_all('/<img\b[^>]+src=\\\?"([^"]+\.([^"\.]+?))\\\?"([^>]*>)/i',$text,$results,PREG_SET_ORDER);
#       if ($results) {
#	 print_R($results);
#	 print_R($context);
#	 die();
#       }
       foreach ($results as $result) {
	 $imgfile=$result[1];	   $ext=$result[2];
	 if (substr($imgfile,0,5)=="http:") continue; // external image

	 // local.
	 // is it in the cache ?
	 if ($imglist[$imgfile]) { 
	   $text=str_replace($result[0],"<img src=\\\"$imglist[$imgfile]\\\"",$text);
	   
	 } else {
	   // not in the cache let's move it
	   if (!$dir) {
	     $dir="docannexe/image/".$context['id'];
	     $this->_checkdir($dir);
	   }

	   $imglist[$imgfile]=$newimgfile="$dir"."/img-".$count.".".$ext;
	   copy($imgfile,SITEROOT.$newimgfile);
	   @unlink($imgfile);

	   if ($newimgfile) { // ok, the image has been correctly copied
#	echo "images: $imgfile $newimgfile <br>";
	     $text=str_replace($result[0],'<img src="'.$newimgfile.'"'.$result[3],$text);
	     @chmod(SITEROOT.$newimgfile, 0666  & octdec($GLOBALS['filemask']));
	     $count++;
	   } else { // no, problem copying the image
	     $text=str_replace($result[0],"<span class=\"image_error\">[Image non convertie]</span>",$text);
	   }
	 }
       }
     }
   }
   function _checkdir($dir)

   {
     if (!is_dir(SITEROOT.$dir)) {
       mkdir(SITEROOT.$dir,0777 & octdec($GLOBALS['filemask']));
       @chmod(SITEROOT.$dir,0777 & octdec($GLOBALS['filemask']));
     } else {
       // clear the directory the first time.
       $fd=opendir(SITEROOT.$dir);
       if (!$fd) die("ERROR: cannot open the directory $dir");
       while($file=readdir($fd)) {
	 if ($file{0}==".") continue;
	 $file=SITEROOT.$dir."/".$file;
	 if (is_file($file)) @unlink($file);
       }
     }
   }
   
   //--------------------------------------------------//
   // definition of the handler to create the context

//   /**
//    * Get internal and characters styles
//    */
//   function _init($class) 
//
//   {
//#     $dao=&getDAO("internalstyles");
//#     $iss=$dao->findMany("status>0","","style,conversion");
//#     foreach ($iss as $is) {
//#       $style=preg_replace("/[:,;].*$/","",$is->style); // take the first one only
//#       if ($style) $this->internalstyles[$style]=$is;
//#     }
//     $this->characterstyles=array();
//     $this->_init_characterstyles("class='".$class."'");
//   }
//
//   /**
//    * Get characters styles for given classes
//    */
//   function _init_characterstyles($classcriteria)
//
//   {
//     $dao=&getDAO("tablefields");
//     $tfs=$dao->findMany("(".$classcriteria.")  AND status>0","","name,class,style,type");
//
//     foreach ($tfs as $tf) {
//       if ($tf->type=="persons") {
//	 $dao=&getDAO("persontypes");
//	 $votype=$dao->find("type='".$tf->name."'");
//	 $this->_init_characterstyles("class='".$votype->class."' OR class='entities_".$votype->class."'");
//	 continue;
//       } elseif ($tf->type=="entries") {
//	 $dao=&getDAO("entrytypes");
//	 $votype=$dao->find("type='".$tf->name."'");
//
//	 echo $tf->name;
//	 echo "la"; print_R($votype);
//
//	 $style=$votype->style;
//	 $class=$votype->class;
//       } else {
//	 $style=$tf->style;
//	 $class=$tf->class;
//       }
//
//       foreach(preg_split("/[,;]/",$style) as $style) {
//	 $style=trim($style);
//	 if ($style[0]==".")  // look for character styles only
//	   $this->characterstyles[$class][substr($style,1)]=$tf;
//       }
//     }
//   }
//

   var $_localcontext;

   function openClass($class,$obj=null)
   {
     switch($class[1]) { // classtype
     case 'entries':
       break;
     case 'persons':
       $this->_localcontext['persons'][$obj->id][]=array(); // add a person
       $this->_currentcontext=&$this->_localcontext['persons'][$obj->id][count($this->_localcontext['persons'][$obj->id])-1];
       break;
     case 'entities':
       $this->_localcontext=array();
       $this->_currentcontext=&$this->_localcontext;
       break;
     }
   }


   function closeClass($class) 

   {
     switch($class[1]) { // classtype
     case 'entries':
       break;
     case 'persons': // come back to the main context
       $this->_currentcontext=&$this->_localcontext;
       break;
     case 'entities':
       // let's import now.
       $localcontext=array_merge($this->context,$this->_localcontext);

       #print_R($localcontext);

       if ($this->task['idparent']) $localcontext['idparent']=$this->task['idparent'];
       if ($this->task['idtype']) $localcontext['idtype']=$this->task['idtype'];
       if ($this->task['identity']) $localcontext['id']=$this->task['identity'];

       $localcontext['creationmethod']="servoo";
       $localcontext['creationinfo']=$this->task['sourceoriginale'];
       
#print_r($localcontext);
       if (!$context['finish']) $localcontext['status']=-64;

       $error=array();
       $this->return=$this->editAction($localcontext,$error,FORCE);#
       $this->id=$localcontext['id'];
       
#     if ($ret=="_error") {}
#     print
     // move the source file and the files
     }
   }


   function processData($data) {
     return $data; #echo $data;
   }

   function processTableFields($obj,$data) 
   {
     global $db;
     if ($obj->type=="file" || $obj->type=="image") {
       // nothing...
     } elseif ($obj->type=="mltext") {
       $lang=$obj->lang ? $obj->lang : $GLOBALS['lodeluser']['lang'];
       $this->_currentcontext[$obj->name][$lang].=addslashes($data);
     } else {
       $this->_currentcontext[$obj->name].=addslashes($data);
     }
   }
   
   function processEntryTypes($obj,$data) 
   {
     foreach(preg_split("/,/",strip_tags($data)) as $entry) {
       $this->_localcontext['entries'][$obj->id][]=array("g_name"=>trim(addslashes($entry)));
     }
   }
   
   function processPersonTypes($obj,$data)

   {
     static $g_name_cache;

     // get the generic type     
     if (!$g_name_cache[$obj->class]) {
       $dao=&getDAO("tablefields");
       $vos=$dao->findMany("class='".$obj->class."' or class='entites_".$obj->class."' and g_name IN ('familyname','firstname','prefix')","","name,g_name");
       foreach ($vos as $vo) {
	 $g_name_cache[$obj->class][$vo->g_name]=$vo->name;
       }
     }
     $g_name=$g_name_cache[$obj->class];
     // ok, we have the generic type

     $data=strip_tags($data);
     if (preg_match("/^\s*(".$this->prefixregexp.")\s/",$data,$result)) {
       $this->_currentcontext[$g_name['prefix']]=$result[1];
       $data=str_replace($result[0],"",$data);
     }
     // ok, we have the prefix

     // try to guess
     if (!$have_firstname && !$have_familyname) {
       // ok, on cherche maintenant a separer le name et le firstname
       $name=$data;
       while ($name && strtoupper($name)!=$name) { $name=substr(strstr($name," "),1);}
       if ($name) {
	 $firstname=str_replace($name,"",$data);
       } else { // sinon coupe apres le premiere espace
	 if (preg_match("/^(.*?)\s+([^\s]+)$/i",trim($data),$result)) {
	   $firstname=$result[1]; $name=$result[2];
	 } else $name=$data;
       }
     }
     $this->_currentcontext[$g_name['firstname']]=addslashes(trim($firstname));
     $this->_currentcontext[$g_name['familyname']]=addslashes(trim($name));
   }

   
   function processCharacterStyles($obj,$data) 
     
   {
     return $obj->conversion.$data.closetags($obj->conversion);
   }

   function processInternalStyles($obj,$data) 
     
   {
     return $obj->conversion.$data.closetags($obj->conversion);
   }

   function unknownParagraphStyle($style,$data) {
     //
   }

   function unknownCharacterStyle($style,$data) {
     // nothing... let's clean it.
     return $data;
   }

   // begin{publicfields} automatic generation  //
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //
   // end{uniquefields} automatic generation  //
} // class 



?>
