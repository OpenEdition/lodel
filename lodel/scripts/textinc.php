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

// calcul le critere pour determiner le texte a editer, restorer, detruire...

require($GLOBALS['home']."func.php");

require($home."textgroupfunc.php");
$textgroupswhere=textgroupswhere($context['textgroups']);

$id=intval($id);
if ($id>0) {
  $critere="id='$id'";
} else 
  $critere="";

if ($id>0 && $delete) { 
  // delete in all lang
  $result=mysql_query("SELECT nom,textgroup FROM $GLOBALS[tp]textes WHERE id='$id' AND $textgroupswhere") or die(mysql_error());
  if (mysql_num_rows($result)) {
    list($nom,$textgroup)=mysql_fetch_row($result);
    mysql_query("DELETE FROM $GLOBALS[tp]textes WHERE nom='$nom' AND textgroup='$textgroup'");
  }
  back();
  return;
//
// ajoute ou edit
//
} elseif ($status && $id) { // modifie ou ajoute
  $status=intval($status);
  mysql_query("UPDATE $GLOBALS[tp]textes SET statut='$status' WHERE $critere AND $textgroupswhere");
  back();
//
// ajoute ou edit
//
} elseif ($edit) { // modifie ou ajoute
  extract_post();

  #print_r($context);
  if (is_array($context['texts'])) {
    foreach ($context['texts'] as $id=>$text) {
      $id=intval($id);
      $status=intval($context['status'][$id]);
      $text=preg_replace("/(\r\n\s*){2,}/","<br />",$text);
      mysql_query ("UPDATE $GLOBALS[tp]textes SET texte='$text',statut='$status' WHERE id='$id' AND $textgroupswhere") or die (mysql_error());
    }
    if (defined("SITEROOT")) {
      touch(SITEROOT."CACHE/maj");
    } else {
      touch("CACHE/maj");
    }
    back(); ///header("HTTP/1.1 204 No Response");
    return; 
  }

  // validation
  do {
    if (!$context['textgroup']) $context['textgroup']="site";
    if ( (!$context['nom'] && !$id) || ($context['nom'] && !preg_match("/^[\w\s]+$/",utf8_decode($context['nom'])) )) $err=$context['erreur_nom']=1;
    if ($err) break;

    if (!$context['lang']) $context['lang']=$GLOBALS['userlang'] ? $GLOBALS['userlang'] : "";

    if ($err) break;

    if ($id) {
      $result=mysql_query ("SELECT nom,textgroup,lang,statut FROM $GLOBALS[tp]textes WHERE id='$id' AND $textgroupswhere") or die (mysql_error());
      if (!mysql_num_rows($result)) die("ERROR: incompatible id and textgroups in textinc.php");
      $context=array_merge($context,mysql_fetch_assoc($result));

    } elseif ($context['nom'] && $context['textgroup'] && $context['lang']) {
      $result=mysql_query ("SELECT id,textgroup,lang,statut FROM $GLOBALS[tp]textes WHERE nom='$context[nom]' AND textgroup='$context[textgroup]' AND lang='$context[lang]'") or die (mysql_error());
      while($row=mysql_fetch_assoc($result)) {
	##if ($id && $id!=$row['id']) { $err=$context[erreur_nom_existe]=1; break; }
	if (!$id) { $id=$row['id'];  $context=array_merge($context,$row); break; }
      }
      if ($err) break;
    } else {
      $context['statut']=-1;
    }

    $context['texte']=preg_replace("/(\r\n\s*){2,}/","<br />",$context['texte']);

    mysql_query ("REPLACE INTO $GLOBALS[tp]textes (id,nom,texte,textgroup,lang,statut) VALUES ('$id','$context[nom]','$context[texte]','$context[textgroup]','$context[lang]','$context[statut]')") or die (mysql_error());
    back();

  } while (0);
  // entre en edition
} elseif ($id>0) {
  require_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]textes WHERE $critere AND $textgroupswhere") or die ("erreur SELECT");
  if (!mysql_num_rows($result)) die("ERROR: incompatible id and textgroups in textinc.php");
  $context=array_merge($context,mysql_fetch_assoc($result));

#    for($i=0; $i<strlen($context[texte]); $i++) {
#      echo ord($context[texte][$i])," ",$context[texte][$i],"<br>";
#    }


#    $context[texte]=preg_replace("/<br \/>/","\r\n\r\n",$context[texte]);
#    $context[texte]=preg_replace("/\n/","",$context[texte]);
} else {
  $context['textgroup']=$textgroup;
}

// post-traitement
$context['id']=$id;

?>