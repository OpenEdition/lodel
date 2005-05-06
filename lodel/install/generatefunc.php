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



function replaceInFile($filename,$beginre,$endre,$contents)

{
  if (!file_exists($filename)) return false;
  $file=file_get_contents($filename);
  if (!$file) die("probleme avec le fichier $filename");
  
  if (!preg_match("/$beginre/",$file)) die("impossible de trouver les begin pour publicfields dans $filename");
  if (!preg_match("/$endre/",$file)) die("impossible de trouver les end pour publicfields dans $filename");

  $file=preg_replace("/($beginre\n?).*?(\n?$endre)/s","\\1".$contents."\\2",$file);

  $fp=fopen($filename,"w");
  fwrite($fp,$file);
  fclose($fp);

  return true;
}



?>