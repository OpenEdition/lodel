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


function calculateXML ($context)

{
  require_once("calcul-page.php");
  ob_start();
  calcul_page($context,"xml-class","",SITEROOT."lodel/edition/tpl/");
  $contents=ob_get_contents();
  ob_end_clean();

  return indentXML($contents);
}


function calculateXMLSchema ($context)

{

  require_once("calcul-page.php");
  ob_start();
  calcul_page($context,"schema-xsd","",SITEROOT."lodel/admin/tpl/");
  $contents=ob_get_contents();
  ob_end_clean();

  return indentXML($contents);
#  return $contents;
}



function indentXML ($contents,$output=false)

{
  $arr=preg_split("/\s*(<(\/?)(?:\w+:)?\w+(?:\s[^>]*)?>)\s*/",$contents,-1,PREG_SPLIT_DELIM_CAPTURE);

  $ret='<?xml version="1.0" encoding="utf-8" ?>
';
  if ($output) echo $ret;
  $tab="";
  for($i=1; $i<count($arr); $i+=3) {
    if ($arr[$i+1]) $tab=substr($tab,2); // closing tag
    if (substr($arr[$i],-2)=="/>") { // opening closing tag
      $out=$tab.$arr[$i].$arr[$i+2]."\n";
    } else 
    if (!$arr[$i+1] && $arr[$i+4]) { // opening follow by a closing tags
      $out=$tab.$arr[$i].$arr[$i+2].$arr[$i+3].$arr[$i+5]."\n";
      $i+=3;
    } else {
      $out=$tab.$arr[$i]."\n";
      if (!$arr[$i+1]) $tab.="  ";
      if (trim($arr[$i+2])) {
	$out.=$tab.$arr[$i+2]."\n";
      }
    }
    if ($output) { echo $out; } else { $ret.=$out; };
  }
  if (!$output) return $ret;
}




/**
 * decode le champs balises.
 */

function loop_xsdtypes(&$context,$funcname)

{
  $balises=preg_split("/;/",$context[balises],-1,PREG_SPLIT_NO_EMPTY);

  if ($balises) call_user_func("code_before_$funcname",$context);

  foreach($balises as $name) {
    if (is_numeric($name)) continue;
    $localcontext=$context;
    $localcontext['count']=$count;
    $count++;
    $localcontext['name']=preg_replace("/\s/","_",$name);
    call_user_func("code_do_$funcname",$localcontext);
  }
  if ($balises) call_user_func("code_after_$funcname",$context);
}





?>
