<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
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

// gere les types de entrees. L'acces est reserve au adminlodelistrateur.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
include_once($home."func.php");


// calcul le critere pour determiner ce qu'il faut editer, restorer, detruire...
$id=intval($id);
if ($id>0) {
  $critere="id='$id'";
} else $critere="";

if ($id>0 && $dir) {
  // search the parent
  chordre("typeentrees",$id,"statut>0",$dir);
  back();
}

if ($id && !$droitadminlodel) $critere.=" AND $GLOBALS[tp]typeentrees.statut<32";
//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  do { // block d'exception
    include_once ($home."connect.php");
    lock_write("typeentites_typeentrees","typeentrees","entrees");

    // check the type can be deleted.
    $result=mysql_query("SELECT count(*) FROM $GLOBALS[tp]entrees WHERE idtype='$id' AND statut>-64") or die (mysql_error());
    list($count)=mysql_fetch_row($result);
    if ($count) { $context[erreur_entrees_existent]=$count; unlock(); break; }

    // delete in the joined table
    mysql_query("DELETE FROM $GLOBALS[tp]typeentites_typeentrees WHERE idtypeentree='$id'") or die (mysql_error());

    $delete=2;
    include ($home."trash.php");
    treattrash("typeentrees",$critere,TRUE);
    return;
  } while(0);
}

require($home."typetypefunc.php");
require_once($home."validfunc.php");

$critere.=" AND statut>0";

//
// ajoute ou edit
//
if ($edit) {
  extract_post();
  // validation
  do {
    if (!$context[type] || !preg_match("/[\w-]/",$context[type])) $err=$context[erreur_type]=1;
    if (!$context[tpl]) $err=$context[erreur_tpl]=1;
    if (!$context[tplindex]) $err=$context[erreur_tplindex]=1;
    if (!$context[titre]) $err=$context[erreur_titre]=1;
    if (!$context[tri]) $context[tri]="nom";
    if ($context[style] &&
	!isvalidstyle($context[style]) && 
	!isvalidmlstyle($context[style])) $err=$context[erreur_style]=1;
    if ($err) break;

    include_once ($home."connect.php");

    if ($id>0) { // il faut rechercher le statut
      $result=mysql_query("SELECT statut,ordre FROM $GLOBALS[tp]typeentrees WHERE $critere") or die (mysql_error());
      if (!mysql_num_rows($result)) die("ERROR: The 'typeentree' does not exist or you are not allowed to modify it.");
      list($statut,$ordre)=mysql_fetch_array($result);
    } else {
      $statut=1;
      $ordre=get_ordre_max("typeentrees");
    }
    if ($droitadminlodel) {
      $newstatut=$protege ? 32 : 1;
      $statut=$statut>0 ? $newstatut : -$newstatut;    
    }

    mysql_query ("REPLACE INTO $GLOBALS[tp]typeentrees (id,type,titre,style,tpl,tplindex,statut,lineaire,nvimportable,utiliseabrev,tri,ordre) VALUES ('$id','$context[type]','$context[titre]','$context[style]','$context[tpl]','$context[tplindex]','$statut','$context[lineaire]','$context[nvimportable]','$context[utiliseabrev]','$context[tri]','$ordre')") or die (mysql_error());
    if ($id) {
      typetype_delete("typeentree","idtypeentree='$id'");
    } else {
      $id=mysql_insert_id();
    }
    typetype_insert($typeentite,$id,"typeentree");
    back();

  } while (0);
} elseif ($id>0) {
  $id=intval($id);
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]typeentrees WHERE $critere") or die (mysql_error());
  if (!mysql_num_rows($result)) die("ERROR: The 'typeentree' does not exist or you are not allowed to modify it.");
  $context=array_merge($context,mysql_fetch_assoc($result));
}

// post-traitement
posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"typeentree");

function loop_typeentites($context,$funcname)
{  loop_typetable ("typeentite","typeentree",$context,$funcname);}


?>
