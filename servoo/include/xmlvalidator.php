<?
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003-2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
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

require_once($home."func.php");


function xmlvalidwithMSV($uploadedfile,$msg)

{
  global $javapath,$home;
  $errfile="$uploadedfile.err";

  // search for the files in the archive
  $f=escapeshellcmd($uploadedfile);
  $filesinarchive=preg_split("/\s*\r?\n\s*/",`$GLOBALS[unzipcmd] -Z -1 $f`,-1,PREG_SPLIT_NO_EMPTY);
  if (count($filesinarchive)!=2) { // two files... perfect.
    die("ERROR: the archive does not contain 2 files");
  }

  foreach ($filesinarchive as $file) {
    if (preg_match("/\.xsd$/",$file)) $schemafile=$file;
    if (preg_match("/\.xml$/",$file)) $xmlfile=$file;
  }

  $schemafile=escapeshellcmd($schemafile);
  $xmlfile=escapeshellcmd($xmlfile);

  if (!$schemafile) die("ERROR: no schema file found in the archive");
  if (!$xmlfile) die("ERROR: no xml file found in the archive");
  
  exec_unzip("-j -p $uploadedfile $schemafile >$uploadedfile.xsd",$errfile);
  exec_unzip("-j -p $uploadedfile $xmlfile >$uploadedfile.xml",$errfile);
  
  $outfile="$uploadedfile.out";
  myexec("$javapath/bin/java   -jar $home/xmlvalidator/msv/msv.jar -strict   $uploadedfile.xsd $uploadedfile.xml >$outfile",$errfile,"java script MSV failed");

  if (!file_exists($outfile)) {
    die("ERROR: the validator does not produce any output");
  }
  $content=file_get_contents($outfile);
  $content=preg_replace(array("/".str_replace("/","\/",$uploadedfile)."\.(xsd|xml)/",
			      "/warnings are found\./",
			      "/use -warning switch to see all warnings\./"),
			array("yourfile.\\1",
			      "",
			      ""),
			$content);
  writefile($outfile,$content);

#  die("ERROR: ".file_get_contents($outfile));

  return array($outfile);
}



?>
