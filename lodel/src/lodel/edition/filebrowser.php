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


require("siteconfig.php");
require_once ($home."auth.php");
authenticate(LEVEL_REDACTOR,NORECORDURL);

define("UPLOADDIR",SITEROOT."CACHE/upload");

$context['caller']=$_REQUEST['caller'];
$deleteall=isset($_REQUEST['deleteall']);
$delete=$_REQUEST['delete'];

if (isset($_REQUEST['checkmail'])) {
  require_once($home."imapfunc.php");
  $context['nbattattachments']=checkmailforattachments();

} elseif ($deletall || $delete) {
  $dh=@opendir(UPLOADDIR);
  if (!$dh) die("ERROR: can't open upload directory");
  while( ($file=readdir($dh))!==false ) {
    if ($file[0]!="." || is_file(UPLOADDIR."/".$file)) {
      if ($deletall || $delete==$file) { // quite safe way to do for deleting only one file
	@unlink(UPLOADDIR."/".$file);
      }
    }
  }
}


require ($home."calcul-page.php");
calcul_page($context,"filebrowser");

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
    $localcontext['name']=$file;
    $localcontext['size']=nicefilesize(filesize(UPLOADDIR."/".$file));
    call_user_func("code_do_$funcname",$localcontext);
  }
}

?>
