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


define("UPLOADDIR",SITEROOT."CACHE/upload");

$GLOBALS['nodesk']=true;

/**
 *  Logic FileBrowse
 */

class FileBrowserLogic {

  /** Constructor
   */
   function FileBrowserLogic() {
     if (!$GLOBALS['lodeluser']['redactor']) die("ERROR: you don't have the right to access this feature");
   }


   function viewAction(&$context,&$error)

   {
     return "filebrowser";
   }

   /**
    * dispatcher Action
    */

   function submitAction(&$context,&$error)

   {
     if ($_POST['checkmail']) return $this->checkMailAction($context,$error);
     if ($_POST['resize'] && $_POST['newsize']) return $this->resizeAction($context,$error);
     if ($_POST['delete']) return $this->deleteAction($context,$error);
   }

   /**
    * check Mail and store files in attachments
    */

   function checkMailAction(&$context,&$error)

   {
     require_once("imapfunc.php");
     $context['nbattachments']=checkmailforattachments();
     update();
     return "filebrowser";
   }

   /**
    * delete files
    */

   function deleteAction(&$context,&$error)

   {
     $selectedfiles=is_array($context['file']) ? array_keys($context['file']) : false;
     $dh=@opendir(UPLOADDIR);
     if (!$dh) die("ERROR: can't open upload directory");

     while( ($file=readdir($dh))!==false ) {
       if ($file[0]!="." || is_file(UPLOADDIR."/".$file)) {
	 if (in_array($file,$selectedfiles)) { // quite safe way, not efficient !
	   @unlink(UPLOADDIR."/".$file);
	 }
       }
     }
     update();
     return "filebrowser";
   }

   /**
    * 
    */

   function resizeAction(&$context,&$error)

   {
     $selectedfiles=is_array($context['file']) ? array_keys($context['file']) : false;
     require_once("images.php");
     $dh=@opendir(UPLOADDIR);
     if (!$dh) die("ERROR: can't open upload directory");
     while( ($file=readdir($dh))!==false ) {
       if ($file[0]!="." || is_file(UPLOADDIR."/".$file)) {
	 if (in_array($file,$selectedfiles)) { // quite safe way, not efficient !
	   $file=UPLOADDIR."/".$file;
	   resize_image($context['newsize'],$file,$file);
	 }
       }
     }
     update();
     return "filebrowser";
   }

} // logic FileBrowserLogic


function loop_filelist($context,$funcname)

{
  $dh=@opendir(UPLOADDIR);
  if (!$dh) { // create the dir if needed
    if (!@mkdir(UPLOADDIR,0777 & octdec($GLOBALS['filemask']))) die("ERROR: unable to create the directory \"UPLOADDIR\"");
    @chmod(UPLOADDIR,0777 & octdec($GLOBALS['filemask']));
    $dh=@opendir(UPLOADDIR);
    if (!$dh) die("ERROR: can't open CACHE/upload dir");
  }

  while( ($file=readdir($dh))!==false ) {
    if ($file[0]=="." || !is_file(UPLOADDIR."/".$file)) continue;
    $localcontext=$context;
    // is it an image ?
    list($w,$h)=getimagesize(UPLOADDIR."/".$file);
    if ($w && $h) {
      $localcontext['imagesize']="$w x $h";
    }
    //
    $localcontext['name']=$file;
    $localcontext['size']=nicefilesize(filesize(UPLOADDIR."/".$file));
    $localcontext['checked']=$context['file'][$file] ? "checked=\"checked\"" : "";
    call_user_func("code_do_$funcname",$localcontext);
  }
}

?>
