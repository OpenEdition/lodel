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
include ($home."auth.php");
authenticate(LEVEL_REDACTOR,NORECORDURL);
include ($home."func.php");
require_once("utf8.php"); // conversion des caracteres

$formats=array("sxw","doc","rtf","pdf");

if ($_FILES['file1'] && $_FILES['file1']['tmp_name'] && $_FILES['file1']['tmp_name']!="none") {
  do {
    $file1=$_FILES['file1']['tmp_name'];

    // verifie que la variable file1 n'a pas ete hackee
    if (!is_uploaded_file($file1)) die(utf8_encode("Le fichier n'est pas un fichier chargé"));

    $t=time();
    $tmpdir=tmpdir(); // use here and later.
    $source=$tmpdir."/".basename($file1)."-source";
    move_uploaded_file($file1,$source); // move first because some provider does not allow operation in the upload dir

    $fileconverted=$source.".converted";
    $sourceoriginale=$_FILES['file1']['name'];
    if (!in_array($formatconversion,$formats)) die("ERROR: unsupported format");

    list($ret,$convertretvar)=convert($source,$fileconverted,$formatconversion);

    // the ServOO should return nothing, if it return, it's an ERROR or a SAY comment.
    if ($ret) {
      $context[error_upload]=utf8_encode("Erreur renvoyée par le serveur OO: \"$ret\"");
      break;
    }

    $sourceoriginale=preg_replace("/\.\w+$/","",$sourceoriginale).".".$formatconversion;

    download($fileconverted,$sourceoriginale);
    return;
  } while (0); // exceptions
}


include ($home."calcul-page.php");
calcul_page($context,"conversion");



// schema 1 de conversion
function convert ($uploadedfile,$destfile,$formatconversion)

{
  global $home;

  require_once("serveurfunc.php");
  $err=contact_servoo("DWL file1; CVT OpenOffice.org $formatconversion; RTN convertedfile;"
		      ,$uploadedfile,$destfile);
  if ($err) return $err;

  return FALSE;
}


function makeselectformat()

{
  foreach ($GLOBALS['formats'] as $f) {
    echo "<option value=\"$f\">$f</option>\n";
  }
}

?>
