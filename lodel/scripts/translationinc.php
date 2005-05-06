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


 die("desuet");if (!function_exists("authenticate")) die("ERROR: invalid include of translationinc.php");

require_once("func.php");
require_once("validfunc.php");
require_once("textgroupfunc.php");

$textscritere=textgroupswhere($context['textgroups']);

$id=intval($id);
$critere=$id>0 ? "id='$id'" : "";

//
// delete
//
if ($id>0 && $delete) { 
  $delete=2; // destruction en -64;
  require("trash.php");

  $result=mysql_query("SELECT lang FROM $GLOBALS[tp]translations WHERE $critere") or dberror();
  list($lang)=mysql_fetch_row($result);
  if (!$lang) die("ERROR: invalid id or lang");
  mysql_query("DELETE FROM $GLOBALS[tp]texts WHERE lang='$lang' AND $textscritere") or dberror();
  treattrash("translations",$critere);
  return;
}

//
// rank
//
if ($id>0 && $dir) {
  chrank("translations",$id,"status>-64",$dir);
  back();
}


//
// ajoute ou edit
//
if ($edit) { // modifie ou ajoute
  extract_post();
  // validation
  do {
    if (!isvalidlang($context['lang']))  { $err=$context['error_lang']=1; break; }

    if (!$id) { // check the lang does not exists
      $result=mysql_query("SELECT 1 FROM $GLOBALS[tp]translations WHERE lang='$lang' AND textgroups='$context[textgroups]' AND id!='$id'") or dberror();
      if (mysql_num_rows($result))  $err=$context['error_lang_exists']=1;
    }

    if ($err) break;

    lock_write("translations","texts","texts as t1","texts as t2");

    $set=array("title","translators","modificationdate","textgroups");
    if (!$id) {
      $set['lang']=$context['lang'];
      $set['status']=1;
      $set['creationdate']=$context['modificationdate']=date("Y-m-d");
      $set['rank']=get_max_rank("translations");
    }

    setrecord("translations",$id,$set,$context);

    //
    // create all the texts if needed
    // 
    // can't use insert select... so it not really funny to do
    //
    if (!$context['lang']) { // get the lang if we don't have it
      $result=mysql_query("SELECT lang FROM $GLOBALS[tp]translations WHERE $critere") or dberror();
      list($context['lang'])=mysql_fetch_row($result);
    }
    if ($context['lang']!=$user['lang']) { // normal case... should be different !
      // get all the text name, group, text in current lang for which the translation does not exists in the new lang
      $result=mysql_query("SELECT t1.name,t1.textgroup,t1.texte FROM $GLOBALS[tp]texts as t1 LEFT OUTER JOIN $GLOBALS[tp]texts as t2 ON t1.name=t2.name AND t1.textgroup=t2.textgroup AND t2.lang='".$context['lang']."' WHERE t1.status>-64 AND t1.lang='".$user['lang']."' AND t2.id IS NULL AND t1.$textscritere GROUP BY t1.name,t1.textgroup") or dberror();
      do { // use multiple insert but not to much... to minimize the size of the query
	$inserts=array(); $count=0;
	while (($row=mysql_fetch_assoc($result)) && $count<20) {
	  $langs=explode(",",$row['langs']); // get the lang
	  if (in_array($lang,$langs)) continue; // the text already exists in the correct lang

#echo $row['name']," ";
	  $inserts[]="('".$row['name']."','".$row['textgroup']."','".mysql_escape_string($row['texte'])."','-1','".$context['lang']."')";
	  $count++;
	}
	if ($inserts) 
	  mysql_query("INSERT INTO $GLOBALS[tp]texts (name,textgroup,texte,status,lang) VALUES ".join(",",$inserts)) or dberror();
      } while ($row);
    }

    unlock();
    back();

  } while (0);
  // entre en edition
} elseif ($id>0) {
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]translations WHERE $critere") or dberror();
  $context=array_merge($context,mysql_fetch_assoc($result));
}

?>
