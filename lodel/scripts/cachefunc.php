<?
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


function clearcache()

{
  if (defined("SITEROOT")) {
    removefilesincache( SITEROOT,SITEROOT."lodel/edition",SITEROOT."lodel/admin");
  } else {
    removefilesincache( "." );
  }
}

function removefilesincache()

{
  // cette fonction pourrait etre ecrite de facon bcp plus simple avec de la recurrence. Pour des raisons de securite/risque de bugs, elle est doublement proteger. 
  // On ajoute le repertoire CACHE dans le code, ce qui empeche de detruire le contenu d'un autre repertoire. On ne se propage pas de facon recurrente.
  // 
  foreach(func_get_args() as $rep) {
    if (!$rep) $rep=".";
    $rep.="/CACHE";
    $fd=opendir($rep) or die ("Impossible d'ouvrir $rep");
    
    while ( ($file=readdir($fd))!==false ) {
      #echo $rep," ",$file," ",(substr($file,0,1)==".") || ($file=="CVS"),"<br />";

      if (($file[0]==".") || ($file=="CVS") || ($file=="upload")) continue;
      $file=$rep."/".$file;
      if (is_dir($file)) {
	$rep2=$file;
	$fd2=opendir($rep2) or die ("Impossible d'ouvrir $file");
	while ( ($file=readdir($fd2))!==false ) {
	  if (substr($file,0,1)==".") continue;
	  $file=$rep2."/".$file;
	  if (is_file($file)) { unlink($file); }
	}
	closedir($fd2);
      } elseif (is_file($file)) { unlink($file); }
    }
    closedir($fd);
  }
}

?>
