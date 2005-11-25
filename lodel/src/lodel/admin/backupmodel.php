<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�ou
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


require("siteconfig.php");
require("auth.php");
authenticate(LEVEL_ADMIN);
require_once("func.php");


$context['importdir']=$importdir;

if ($_POST['backup']) {
  extract_post();

  // il faut locker la base parce que le dump ne doit pas se faire en meme temps que quelqu'un ecrit un fichier.
  // lock les tables
  require("backupfunc.php");
  $uselodelprefix=true;


  #lock_write_all($currentdb);

  $tmpfile=tmpdir()."/model.sql";
  $fh=fopen($tmpfile,"w");

  $description='<model>
<lodelversion>'.$version.'</lodelversion>
<date>'.date("Y-m-d").'</date>
<title>
'.myhtmlentities(stripslashes($context['title'])).'
</title>
<description>
'.myhtmlentities(stripslashes($context['description'])).'
</description>
<author>
'.myhtmlentities(stripslashes($context['author'])).'
</author>
<modelversion>
'.myhtmlentities(stripslashes($context['modelversion'])).'
</modelversion>
</model>
 ';

  fputs($fh,"# ".str_replace("\n","\n# ",$description)."\n#------------\n\n");


  $tables=array("#_TP_classes",
		"#_TP_tablefields",
		"#_TP_tablefieldgroups",
		"#_TP_types",
		"#_TP_persontypes",
		"#_TP_entrytypes",
		"#_TP_entitytypes_entitytypes",
		"#_TP_characterstyles",
		"#_TP_internalstyles");

  foreach ($tables as $table) {
    fputs($fh,"DELETE FROM ".$table.";\n");
  }
  $currentprefix="#_TP_";
  $GLOBALS['showcolumns']=true; // use by PMA to print the fields.
  mysql_dump($currentdb,$tables,"",$fh,false,false,true); // get the content

  // select the optiongroups to export
  $dao=&getDAO("optiongroups");
  $vos=$dao->findMany("exportpolicy>0 AND status>0","","name,id");

  $ids=array();
  foreach($vos as $vo) { $ids[]=$vo->id; }
  fputs($fh,"DELETE FROM #_TP_optiongroups;\n");
  mysql_dump($currentdb,array("#_TP_optiongroups"),"",$fh,false,false,true,"*","id ".sql_in_array($ids));
  fputs($fh,"DELETE FROM #_TP_options;\n");
  mysql_dump($currentdb,array("#_TP_options"),"",$fh,false,false,true,"*","idgroup ".sql_in_array($ids)); // select everything but the value

  // get the classe table
  $dao=&getDAO("classes");
  $vos=$dao->findMany("status>0","","class,classtype");
  $tables=array();
  foreach ($vos as $vo) {
    $tables[]=lq("#_TP_".$vo->class);
    if ($vo->classtype=="persons") $tables[]=lq("#_TP_entities_".$vo->class);
  }
  if ($tables) mysql_dump($currentdb,$tables,"",$fh,true,true,false); // get the table create
  // it may be better to recreate the field at the import rather 
  // than using the created field. It may be more robust. Status quo at the moment.
	    
  fclose($fh);

  if (filesize($tmpfile)<=0) die ("ERROR: mysql_dump failed");

  #unlock();

  $dirs=array();
  foreach(array("tpl","css","images","js") as $dir) if ($context[$dir]) $dirs[]=$dir;
  $zipfile=backupME($tmpfile,$dirs);

  $filename="model-$site-".date("dmy").".zip";
  $operation="download";
  if (operation($operation,$zipfile,$filename,$context)) return;
  @unlink ($tmpfile);
  @unlink ($zipfile);

  header ("location: index.php");
  return;
}


require_once "view.php";
$view = &View::getView();
$view->render($context, "backupmodel");

?>
