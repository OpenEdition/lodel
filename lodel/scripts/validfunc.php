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




function validfield(&$text,$type,$default="")

{
  switch ($type) {
  case "text" :
    if (!$text) $text=$default;
    return true; // always true
    break;
  case "type" :
    if (!preg_match("/^[a-zA-Z0-9_][a-zA-Z0-9_ -]*$/",$text)) return $type;
    break;
  case "class" :
    if (!preg_match("/^[a-zA-Z][a-zA-Z0-9_]*$/",$text)) return $type;
    require_once($GLOBALS['home']."champfunc.php");
    if (reservedword($name)) return "reservedsql";
    break;
  case "field" :
    if (!preg_match("/^[a-zA-Z0-9]+$/",$text)) return $type;
    require_once($GLOBALS['home']."champfunc.php");
    if (reservedword($name)) return "reservedsql";
    break;
  case "style" :
    if (!preg_match("/^[a-zA-Z0-9]+$/",$text)) return $type;
    break;
  case "passwd" :
  case "username" :
    $len=strlen($text);
    if ($len<3 || $len>12 || !preg_match("/^[0-9A-Za-z_;.?!@:,]+$/",$text)) return $type;
    break;
  case "lang" :
    if (!preg_match("/^[a-zA-Z]{2}(_[a-zA-Z]{2})?$/",$text)) return $type;
    break;
  case "date" :
  case "datetime" :
  case "time" :
    require_once($GLOBALS['home']."date.php");
    if ($text) {
      $text=mysqldatetime($text,$type);
      if (!$text) return $type;
    } elseif ($default) {
      $dt=mysqldatetime($default,$type);
      if ($dt) {
	$text=$dt;
      } else {
	die("ERROR: default value not a date or time: \"$default\"");
      }
    }
    break;
  case "int" :
    if ((!isset($text) || $text==="") && $default!=="") $text=intval($default);
    if (isset($text) && (!is_numeric($text) || intval($text)!=$text)) return "int";
    break;
  case "number" : 
    if ((!isset($text) || $text==="") && $default!=="") $text=doubleval($default);
    if (isset($text) && !is_numeric($text)) return "numeric";
    break;
  case "email" : 
    if (!$text && $default) $text=$default;
    if ($text) {
      $validchar='-0-9A-Z_a-z';
      if (!preg_match("/^[$validchar]+@([$validchar]+\.)+[$validchar]+$/",$text)) return "email";
    }
    break;
  case "url" : 
    if (!$text && $default) $text=$default;
    if ($text) {
      $validchar='-0-9A-Z_a-z';
      if (!preg_match("/^(http|ftp):\/\/([$validchar]+\.)+[$validchar]+/",$text)) return "url";
    }
    break;
  case "boolean" :
    $text=$text ? 1 : 0;
    break;
  case "tplfile" :
    $text=trim($text); // should be done elsewhere but to be sure...
    if (strpos($text,"/")!==false || $text[0]==".") return "tplfile";
    break;

  case "textgroups" :
    return $text=="site" || $text=="interface";
    break;
  case "select" :
    return true; // cannot validate
  default:
    return false; // pas de validation
  }

  return true; // validated
}




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





?>
