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

include_once($home."func.php");

// calcul le critere pour determiner le periode a editer, restorer, detruire...
$id=intval($id);
if ($id>0) {
  $critere="id='$id'";
} else $critere="";

require($home."typetypefunc.php");

if ($id && !$droitadminlodel) $critere.=" AND $GLOBALS[tp]types.statut<32";
//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 

  do { // block d'exception
    include_once ($home."connect.php");
    lock_write("types","typeentites_typeentites","typeentites_typeentrees","typeentites_typepersonnes","entites");
    // check the type can be deleted.
    $result=mysql_query("SELECT 1 FROM $GLOBALS[tp]types WHERE statut>-64 AND $critere") or die (mysql_error());
    if (!mysql_num_rows($result)) die("ERROR: The type does not exist or you are not allowed to modify it.");
    // check the type can be deleted.
    $result=mysql_query("SELECT count(*) FROM $GLOBALS[tp]entites WHERE idtype='$id' AND statut>-64") or die (mysql_error());
    list($count)=mysql_fetch_row($result);
    if ($count) { $context[erreur_entites_existent]=$count; unlock(); break; }

    typetypes_delete("idtypeentite='$id'");

    $delete=2; // supprime pour de vrai
    include ($home."trash.php");
    treattrash("types",$critere,TRUE);
    return;
  } while (0); // block d'exception
}

$critere.=" AND statut>0";

//
// ordre
//

if ($id>0 && $dir) {
  # cherche le parent
  chordre("types",$id,"statut>0",$dir);
  back();
}
//
// ajoute ou edit
//
if ($edit) { // modifie ou ajoute
  extract_post();
  // validation
  do {
    require($home."validfunc.php");
    $context[type]=trim($context[type]);
    if (!$context[type] || !isvalidtype($context[type])) $err=$context[erreur_type]=1;
    //    if (!$context[tpl]) $err=$context[erreur_tpl]=1;
    if ($err) break;

    include_once ($home."connect.php");
    lock_write("types","typeentites_typeentites","typeentites_typeentrees","typeentites_typepersonnes");

    // verifie que ce type n'existe pas.
    $result=mysql_query("SELECT 1 FROM $GLOBALS[tp]types WHERE type='$context[type]' AND classe='$context[classe]' AND id!='$id'") or die (mysql_error());
    if (mysql_num_rows($result)) { unlock(); $context[erreur_type_existe]=1; break; }

    if ($id>0) { // il faut rechercher le statut
      $result=mysql_query("SELECT statut,ordre FROM $GLOBALS[tp]types WHERE $critere") or die (mysql_error());
      if (!mysql_num_rows($result)) die("ERROR: The type does not exist or you are not allowed to modify it.");
      list($statut,$ordre)=mysql_fetch_array($result);
    } else {
      $statut=1;
      $ordre=get_ordre_max("types");
    }
    $context[import]=$context[import] ? 1 : 0;
    if ($droitadminlodel) {
      $newstatut=$protege ? 32 : 1;
      $statut=$statut>0 ? $newstatut : -$newstatut;    
    }

    mysql_query ("REPLACE INTO $GLOBALS[tp]types (id,type,titre,classe,tpl,tpledition,tplcreation,import,statut,ordre) VALUES ('$id','$context[type]','$context[titre]','$classe','$context[tpl]','$context[tpledition]','$context[tplcreation]','$context[import]','$statut','$ordre')") or die (mysql_error());

    if ($id) {
      typetypes_delete("idtypeentite='$id'");
    } else {
      $id=mysql_insert_id();
    }
    typetype_insert($id,$typeentree,"typeentree");
    typetype_insert($id,$typepersonne,"typepersonne");
    typetype_insert($id,$typeentite,"typeentite2");

    unlock();
    back();

  } while (0);
} elseif ($id>0) {
  $id=intval($id);
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]types WHERE statut>-64 AND $critere") or die (mysql_error());
  $context=array_merge($context,mysql_fetch_assoc($result));
} else {
  $context[import]=($classe=="documents") && 
    $servoourl &&  
    $servoousername && 
    $servoopasswd ? 1 : 0;
}

// post-traitement
posttraitement($context);


function loop_typepersonnes($context,$funcname)
{  loop_typetable ("typepersonne","typeentite",$context,$funcname);}

function loop_typeentrees($context,$funcname)
{  loop_typetable ("typeentree","typeentite",$context,$funcname);}

function loop_typeentites($context,$funcname)
{  loop_typetable ("typeentite2","typeentite",$context,$funcname);}


?>
