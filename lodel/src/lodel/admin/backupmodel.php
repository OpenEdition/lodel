<?php
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


require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN);
include ($home."func.php");

$context[importdir]=$importdir;

if ($backup) {
  require_once($home."func.php");
  extract_post();

  // il faut locker la base parce que le dump ne doit pas se faire en meme temps que quelqu'un ecrit un fichier.
  // lock les tables
  require($home."backupfunc.php");
  $uselodelprefix=true;


  lock_write_all($currentdb);

  $tmpfile=tempnam(tmpdir(),"lodelbackupmodel");
  $fh=fopen($tmpfile,"w");

  $description='<model>
<lodelversion>'.$version.'</lodelversion>
<date>'.date("Y-m-d").'</date>
<description>
'.myhtmlentities(stripslashes($context[description])).'
</description>
<author>
'.myhtmlentities(stripslashes($context[author])).'
</author>
</model>
 ';

  fputs($fh,"# ".str_replace("\n","\n# ",$description)."\n#------------\n\n");


  $tables=array("$GLOBALS[tp]champs",
		"$GLOBALS[tp]groupesdechamps",
		"$GLOBALS[tp]types",
		"$GLOBALS[tp]typepersonnes",
		"$GLOBALS[tp]typeentrees",
		"$GLOBALS[tp]typeentites_typeentites",
		"$GLOBALS[tp]typeentites_typeentrees",
		"$GLOBALS[tp]typeentites_typepersonnes");

  foreach ($tables as $table) {
    fputs($fh,"DELETE FROM ".lodelprefix($table).";\n");
  }
  $GLOBALS['showcolumns']=true; // use by PMA to print the fields.
  mysql_dump($currentdb,$tables,"",$fh,false,false,true); // get the content

  $tables=array("$GLOBALS[tp]documents",
		"$GLOBALS[tp]publications");
  mysql_dump($currentdb,$tables,"",$fh,true,true,false); // get the table create
  // it may be better to recreate the field at the import rather 
  // than using the created field. It may be more robust. Status quo at the moment.
	    
  fclose($fh);

  if (filesize($tmpfile)<=0) die ("ERROR: mysql_dump failed");

  unlock();
  $filename="model-$site-".date("dmy").".sql";
  $operation="download";
  if (operation($operation,$tmpfile,$filename,$context)) return;
  @unlink ($tmpfile);

  header ("location: index.php");
  return;
}


include ($home."calcul-page.php");
calcul_page($context,"backupmodel");


?>
