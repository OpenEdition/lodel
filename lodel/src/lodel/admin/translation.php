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


// manage translation

require("siteconfig.php");
require($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
require_once($home."func.php");
require_once($home."validfunc.php");


$id=intval($id);
$critere=$id>0 ? "id='$id'" : "";
$textscritere="textgroup!='site'";
$context['textgroups']="interface";

//
// delete
//
if ($id>0 && $delete) { 
  $delete=2; // destruction en -64;
  require($home."trash.php");

  $result=mysql_query("SELECT lang FROM $GLOBALS[tp]translations WHERE $critere") or die (mysql_error());
  list($lang)=mysql_fetch_row($result);
  if (!$lang) die("ERROR: invalid id or lang");
  mysql_query("DELETE FROM $GLOBALS[tp]textes WHERE lang='$lang' AND $textscritere") or die(mysql_error());
  treattrash("translations",$critere);
  return;
}

//
// ordre
//
if ($id>0 && $dir) {
  chordre("translations",$id,"statut>-64",$dir);
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
      $result=mysql_query("SELECT 1 FROM $GLOBALS[tp]translations WHERE lang='$lang' AND id!='$id'") or die(mysql_error());
      if (mysql_num_rows($result))  $err=$context['error_lang_exists']=1;
    }

    if ($err) break;

    lock_write("translations","textes");

    $set=array("title","translators","modificationdate");
    if (!$id) {
      $set['lang']=$context['lang'];
      $set['statut']=1;
      $set['creationdate']=$context['modificationdate']=date("Y-m-d");
      $set['ordre']=get_ordre_max("translations");
    }

    setrecord("translations",$id,$set,$context);

    //
    // create all the texts if needed
    // 
    // can't use insert select... so it not really funny to do
    //
    if (!$context['lang']) { // get the lang if we don't have it
      $result=mysql_query("SELECT lang FROM $GLOBALS[tp]translations WHERE $critere") or die(mysql_error());
      list($context['lang'])=mysql_fetch_row($result);
    }
    if ($context['lang']!=$userlang) { // normal case... should be different !
      // get all the text name, group, text in current langue for which the translation does not exists in the new lang
      $result=mysql_query("SELECT t1.nom,t1.textgroup,t1.texte FROM $GLOBALS[tp]textes as t1 LEFT OUTER JOIN $GLOBALS[tp]textes as t2 ON t1.nom=t2.nom AND t1.textgroup=t2.textgroup AND t2.lang='".$context['lang']."' WHERE t1.statut>-64 AND t1.lang='".$userlang."' AND t2.id IS NULL AND t1.$textscritere GROUP BY t1.nom,t1.textgroup") or die(mysql_error());
      do { // use multiple insert but not to much... to minimize the size of the query
	$inserts=array(); $count=0;
	while (($row=mysql_fetch_assoc($result)) && $count<20) {
	  $langs=explode(",",$row['langs']); // get the lang
	  if (in_array($lang,$langs)) continue; // the text already exists in the correct lang

#echo $row['nom']," ";
	  $inserts[]="('".$row['nom']."','".$row['textgroup']."','".mysql_escape_string($row['texte'])."','-1','".$context['lang']."')";
	  $count++;
	}
	if ($inserts) 
	  mysql_query("INSERT INTO $GLOBALS[tp]textes (nom,textgroup,texte,statut,lang) VALUES ".join(",",$inserts)) or die(mysql_error());
      } while ($row);
    }

    unlock();
    back();

  } while (0);
  // entre en edition
} elseif ($id>0) {
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]translations WHERE $critere") or die (mysql_error());
  $context=array_merge($context,mysql_fetch_assoc($result));
}


// post-traitement
posttraitement($context);

require($home."calcul-page.php");
calcul_page($context,"translation");



?>



