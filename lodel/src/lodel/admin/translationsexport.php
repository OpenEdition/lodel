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
require ($home."auth.php");
authenticate(LEVEL_EDITOR);
require_once($home."func.php");
require_once($home."validfunc.php");
require_once($home."translationfunc.php");


//$context[importdir]=$importdir;
if ($lang!="all" && !isvalidlang($lang)) die("ERROR: invalid lang");

// lock the database
lock_write("translations","textes");

$tmpfile=tempnam(tmpdir(),"lodeltranslation");

require_once($home."xmldbfunc.php");

$result=mysql_query("SELECT textgroups FROM $GLOBALS[tp]translations WHERE lang='$lang'") or dberror();
list($textgroups)=mysql_fetch_row($result); // get the first one
// assume all have the same group

$xmldb=new XMLDB_Translations($textgroups,$lang);

$ret=$xmldb->saveToString();
die($ret);

#$xmldb->saveToFile($tmpfile);

$filename="translation-$lang-".date("dmy").".sql";



download($tmpfile,$filename);
@unlink ($tmpfile);
return;

header ("location: index.php");
return;


?>
