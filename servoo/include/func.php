<?
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
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



function exec_zip($cmd,$errfile)

{
  if (!$GLOBALS[zipcmd]) die ("ERROR: zip command not configured");
  myexec("$GLOBALS[zipcmd] $cmd",$errfile,"zip failed");
}

function exec_unzip($cmd,$errfile)

{
  if (!$GLOBALS[unzipcmd]) die ("ERROR: unzip command not configured");
  myexec("$GLOBALS[unzipcmd] $cmd",$errfile,"unzip failed");
}

function myexec($cmd,$errfile,$errormsg)

{
  system($cmd."  2>$errfile");
  if (filesize($errfile)>0) die("ERROR: $errormsg<br />".str_replace("\n","<br>",htmlentities(@join("",@file($errfile)))));
}



if (!function_exists("writefile")) {
function writefile ($filename,&$text)

{
 //echo "nom de fichier : $filename";
   if (file_exists($filename)) 
   { 
     if (! @unlink($filename) ) die ("ERROR: $filename can not be deleted. Please contact OO server administrator");
   }
   return ($f=fopen($filename,"w")) && fputs($f,$text) && fclose($f) && chmod ($filename,0644);
}
}

?>