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


function extract_import($footprint,&$context) {

  $context['importdir']=$importdir;
  $fileregexp='('.$footprint.')-\w+(?:-\d+)?.zip';

  $importdirs=array("CACHE",$GLOBALS['home']."../install/plateform");
  if ($importdir) $importdirs[]=$importdir;

  $archive=$_FILES['archive']['tmp_name'];
  $context['error_upload']=$_FILES['archive']['error'];
  if (!$context['error_upload'] && $archive && $archive!="none" && is_uploaded_file($archive)) { // Upload
    $file=$_FILES['archive']['name'];
    if (!preg_match("/^$fileregexp$/",$file)) $file=$footprint."-import-".date("dmy").".zip";

    if (!move_uploaded_file($archive,"CACHE/".$file)) die("ERROR: a problem occurs while moving the uploaded file.");

    $file=""; // on repropose la page

  } elseif ($file && preg_match("/^(?:".str_replace("/",'\/',join("|",$importdirs)).")\/$fileregexp$/",$file,$result) && file_exists($file)) { // file sur le disque
  $prefix=$result[1];

  } else { // rien
    $file="";
  }

  return $file;
}

?>