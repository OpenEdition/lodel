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


function isvalidtype($name) 
{return preg_match("/^[a-zA-Z0-9_-][a-zA-Z0-9_ -]*$/",$name);}

function isvalidfield($name) 
{return preg_match("/^[a-zA-Z0-9]+$/",$name);}

function isvalidstyle($name)
{ return preg_match("/^[a-zA-Z0-9]+$/",$name); }


function isvalidmlstyle($style)

{
  $stylesarr=preg_split("/([\n,:])/",$style,-1,PREG_SPLIT_DELIM_CAPTURE);
  if (!$stylesarr) return TRUE;
  $count=count($stylesarr);
  for($i=0; $i<$count; $i+=4) {
    if (!isvalidstyle(trim($stylesarr[$i]))) return FALSE; // le style 
    if ($stylesarr[$i+1]!=":") return FALSE; // le separateur
    if (!preg_match("/^\s*([a-z]{2}|--)\s*$/",$stylesarr[$i+2])) return FALSE; // la lang
    if ($stylesarr[$i+3]==":") return FALSE; // les autres separateurs

    $k=trim($stylesarr[$i+1]);
    $stylesassoc[$k]=trim($stylesarr[$i+1]);
  }
  return TRUE;
}

function isvalidlang($lang)

{
  return preg_match("/^[a-zA-Z]{2}(_[a-zA-Z]{2})?$/",$lang);
}


?>
