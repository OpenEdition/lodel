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



$GLOBALS['translations_textgroups']=array("interface"=>array("common","edition","admin","lodeladmin","install","lodelloader"),
			     "site"=>array("site"),
			     );


/**
 *  Logic Translation
 */

class TranslationsLogic extends Logic {

  /** Constructor
   */
   function TranslationsLogic() {
     $this->Logic("translations");
   }

   /**
    * list Action
    */

   function listAction(&$context,&$errro) 
   {
     $this->_setTextGroups($context);

     function loop_textgroups(&$context,$funcname)

     {
       foreach($GLOBALS['translations_textgroups'][$context['textgroups']] as $textgroup) {
	 $localcontext=$context;
	 $localcontext['textgroup']=$textgroup;
	 call_user_func("code_do_".$funcname,$localcontext);
       }
     }

     function loop_alltexts(&$context,$funcname)

     {
       global $db,$distincttexts,$alltexts_cache;

       $result=$db->execute(lq("SELECT status,contents,name,id,lang FROM #_TP_texts WHERE status>=-1 AND textgroup='".$context['textgroup']."'")) or dberror();

       $distincttexts=array();
       while(!$result->EOF) {
	 $lang=$result->fields['lang'];
	 $name=$result->fields['name'];	
	 if ($name && $lang) {
	   $alltexts_cache[$lang][$name]=$result->fields;
	   if ($lang==$GLOBALS['la']) {
	     $distincttexts[$name]=$result->fields['contents'];
	   } elseif (!isset($distincttexts[$name])) {
	     $distincttexts[$name]=true;
	   }
	 } // valid name
	 $result->MoveNext();
       }
       foreach($distincttexts as $name=>$contents) {
	 $localcontext=$context;
	 $localcontext['name']=$name;
	 $localcontext['contents']=$contents;
	 call_user_func("code_do_".$funcname,$localcontext);
       }
     }

     function loop_lang_and_text(&$context,$funcname)
       
     {
       foreach(array_keys($GLOBALS['alltexts_cache']) as $lang) {
	 $localcontext=$context;
	 $row=$GLOBALS['alltexts_cache'][$lang][$context['name']];
	 $localcontext=$row ? array_merge($context,$row) : $context;
	 call_user_func("code_do_".$funcname,$localcontext);       
       }
     }

     return "_ok";
   }

   /**
    * add/edit Action
    */

   function editAction(&$context,&$error,$clean=false)

   {
     $this->_setTextGroups($context);
     if (!$context['id']) $context['modificationdate']=date("Y-m-d");

     return Logic::editAction($context,$error);
   }

   /**
    * export Action
    */
   function exportAction(&$context,&$error)

   {
     global $home;
     require_once("validfunc.php");

     $lang=$context['lang'];
     if ($lang!="all" && !validfield($lang,"lang")) die("ERROR: invalid lang");
     
     // lock the database
     //lock_write("translations","textes");

     $tmpfile=tempnam(tmpdir(),"lodeltranslation");
     require_once("translationfunc.php");

     $this->_setTextGroups($context);
     $xmldb=new XMLDB_Translations($context['textgroups'],$lang);

     #$ret=$xmldb->saveToString();
     #die($ret);

     $xmldb->saveToFile($tmpfile);

     $filename="translation-$lang-".date("dmy").".xml";
 
     download($tmpfile,$filename);
     @unlink ($tmpfile);
     exit();

     return "back";
   }


   function importAction(&$context,&$error)

   {
     global $home;

     $this->_setTextGroups($context);
     $lang="";

     require_once("importfunc.php");
     $file=extract_import("translation",$context,"xml");

     if ($file) {
       require_once("validfunc.php");
       require_once("translationfunc.php");
       $xmldb=new XMLDB_Translations($context['textgroups']);
       
       $xmldb->readFromFile($file);
       update();

       return "_back";
     }


     function loop_files(&$context,$funcname)

     {
       global $fileregexp,$importdirs,$home;
     
       foreach ($importdirs as $dir) {
	 if ( $dh= @opendir($dir)) {
	   while (($file=readdir($dh))!==FALSE) {
	     if (!preg_match("/^$fileregexp$/i",$file)) continue;
	     $localcontext=$context;
	     $localcontext['filename']=$file;
	     $localcontext['fullfilename']="$dir/$file";
	     if ($dir=="CACHE") $localcontext['maybedeleted']=1;
	     call_user_func("code_do_$funcname",$localcontext);	   
	   }
	   closedir ($dh);
	 }
       }
     }

     function loop_translation(&$context,$funcname)
     
     {
       $arr=preg_split("/<\/?row>/",file_get_contents($context['fullfilename']));

       $langs=array();
       for($i=1; $i<count($arr); $i+=2) {
	 $localcontext=$context;
	 foreach (array("lang","title","creationdate","modificationdate") as $tag) {
	   if (preg_match("/<$tag>(.*)<\/$tag>/",$arr[$i],$result)) 
	     $localcontext[$tag]=trim(strip_tags($result[1]));
	 }
	 if (!$localcontext['lang']) continue;
	 call_user_func("code_do_$funcname",$localcontext);
       }
     }
     return "import_translations";

   }
   

   /*---------------------------------------------------------------*/
   //! Private or protected from this point
   /**
    * @private
    */

   /**
    * Set the textgroups
    */

   function _setTextGroups(&$context) 

   {
     $context['textgroups']=$GLOBALS['site'] ? "site" : "interface";
   }

   /**
    * Used in editAction to do extra operation after the object has been saved
    */

   function _saveRelatedTables($vo,$context) 

   {
     global $db,$lodeluser;
     //
     // create all the texts if needed
     // 
     // can't use insert select... so it not really funny to do
     //

     
     if (!$vo->lang) { // get the lang if we don't have it
       $dao=$this->_getMainTableDAO();
       $vo=$dao->getById($vo->id);
     }
     if ($vo->lang==$lodeluser['lang']) {
       // get any lang... this should not happen anyway
       $dao=$this->_getMainTableDAO();
       $vo2=$dao->find("status>0","lang");
       $fromlang=$vo2->lang;
     } else {
       // normal case... should be different !
       $fromlang=$lodeluser['lang'];
     }
     $textscriteria=textgroupswhere( defined("SITEROOT") ? "site" : "interface" );

     // get all the text name, group, text in current lang for which the translation does not exists in the new lang
     $result=$db->execute(lq("SELECT t1.name,t1.textgroup,t1.contents FROM #_TP_texts as t1 LEFT OUTER JOIN #_TP_texts as t2 ON t1.name=t2.name AND t1.textgroup=t2.textgroup AND t2.lang='".$vo->lang."' WHERE t1.status>-64 AND t1.lang='".$fromlang."' AND t2.id IS NULL AND t1.".$textscriteria." GROUP BY t1.name,t1.textgroup")) or dberror();
     do { // use multiple insert but not to much... to minimize the size of the query
       $inserts=array(); $count=0;
       while (!$result->EOF && $count<20) {
	 $row=$result->fields;
	 #$langs=explode(",",$row['langs']); // get the lang
         #if (in_array($lang,$langs)) continue; // the text already exists in the correct lang
         #echo $row['name']," ";
	 
	 $inserts[]="('".$row['name']."','".$row['textgroup']."','".mysql_escape_string($row['contents'])."','-1','".$context['lang']."')";
	 $count++;
	 $result->MoveNext();
       }
       if ($inserts) 
	 $db->execute(lq("INSERT INTO #_TP_texts (name,textgroup,contents,status,lang) VALUES ".join(",",$inserts))) or dberror();
     } while (!$result->EOF);
   }

   function _deleteRelatedTables($id) {
     // reinitialise le cache surement.
   }



   // begin{publicfields} automatic generation  //
   function _publicfields() {
     return array("lang"=>array("text","+"),
                  "title"=>array("text",""),
                  "textgroups"=>array("text",""),
                  "translators"=>array("text",""),
                  "creationdate"=>array("date",""));
             }
   // end{publicfields} automatic generation  //

   // begin{uniquefields} automatic generation  //

    function _uniqueFields() {  return array(array("lang","textgroups"),);  }
   // end{uniquefields} automatic generation  //


} // class 


/*-----------------------------------*/
/* function pipe                     */

function textgroupswhere($textgroups)

{
  if (!$textgroups) die("ERROR: which textgroups ?");
  if ($GLOBALS['translations_textgroups'][$textgroups]) {
    return "textgroup IN ('".join("','",$GLOBALS['translations_textgroups'][$textgroups])."')";
  } else {
    die("ERROR: unkown textgroup");
  }
}


?>
