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


die("desuet");
require_once($home."func.php");

// calcul le critere pour determiner le periode a editer, restorer, detruire...
$id=intval($id);
if ($id>0) {
  $critere="id='$id'";
} else $critere="";

require($home."typetypefunc.php");

if ($id && !$rightadminlodel) $critere.=" AND $GLOBALS[tp]types.status<32";
//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 

  do { // block d'exception
    include_once ($home."connect.php");
    lock_write("types","typeentites_typeentites","typeentites_typeentrees","typeentites_typepersonnes","entites","objets");
    // check the type can be deleted.
    $result=mysql_query("SELECT 1 FROM $GLOBALS[tp]types WHERE status>-64 AND $critere") or dberror();
    if (!mysql_num_rows($result)) die("ERROR: The type does not exist or you are not allowed to modify it.");
    // check the type can be deleted.
    $result=mysql_query("SELECT count(*) FROM $GLOBALS[tp]entities WHERE idtype='$id' AND status>-64") or dberror();
    list($count)=mysql_fetch_row($result);
    if ($count) { $context[error_entites_existent]=$count; unlock(); break; }

    typetype_delete("typeentree","identitytype='$id'");
    typetype_delete("typepersonne","identitytype='$id'");
    typetype_delete("typeentite","identitytype='$id' OR idtypeentite2='$id'");

    $delete=2; // supprime pour de vrai
    include ($home."trash.php");
    deleteuniqueid($id);
    treattrash("types",$critere,TRUE);
    return;
  } while (0); // block d'exception
}

$critere.=" AND status>0";

//
// rank
//

if ($id>0 && $dir) {
  # cherche le parent
  chrank("types",$id,"status>0 AND class='$class'",$dir);
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
    if (!$context[type] || !isvalidtype($context[type])) $err=$context[error_type]=1;
    //    if (!$context[tpl]) $err=$context[error_tpl]=1;
    if ($err) break;

    include_once ($home."connect.php");
    lock_write("objets","types","typeentites_typeentites","typeentites_typeentrees","typeentites_typepersonnes");

    // verifie que ce type n'existe pas.
    $result=mysql_query("SELECT 1 FROM $GLOBALS[tp]types WHERE type='$context[type]' AND class='$context[class]' AND id!='$id'") or dberror();
    if (mysql_num_rows($result)) { unlock(); $context[error_type_existe]=1; break; }

    if ($id>0) { // il faut rechercher le status
      $result=mysql_query("SELECT status,rank FROM $GLOBALS[tp]types WHERE $critere") or dberror();
      if (!mysql_num_rows($result)) die("ERROR: The type does not exist or you are not allowed to modify it.");
      list($status,$rank)=mysql_fetch_array($result);

      typetype_delete("typeentree","identitytype='$id'");
      typetype_delete("typepersonne","identitytype='$id'");
      typetype_delete("typeentite","identitytype='$id'");
    } else {
      $status=1;
      $rank=get_rank_max("types");
      $id=uniqueid("types");
    }
    $context[import]=$context[import] ? 1 : 0;
    if ($rightadminlodel) {
      $newstatus=$protege ? 32 : 1;
      $status=$status>0 ? $newstatus : -$newstatus;    
    }
    if (!$context[tplcreation]) $context[tplcreation]=preg_replace("/s$/","",$class);

    mysql_query ("REPLACE INTO $GLOBALS[tp]types (id,type,title,class,tpl,tpledition,tplcreation,import,status,rank) VALUES ('$id','$context[type]','$context[title]','$class','$context[tpl]','$context[tpledition]','$context[tplcreation]','$context[import]','$status','$rank')") or dberror();

    typetype_insert($id,$typeentree,"typeentree");
    typetype_insert($id,$typepersonne,"typepersonne");
    typetype_insert($id,$typeentite,"typeentite2");

    unlock();
    back();

  } while (0);
} elseif ($id>0) {
  $id=intval($id);
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]types WHERE status>-64 AND $critere") or dberror();
  $context=array_merge($context,mysql_fetch_assoc($result));
} else {
  $context[import]=($class=="documents") && 
    $servoourl &&  
    $servoousername && 
    $servoopasswd ? 1 : 0;
}

// post-traitement
postprocessing($context);


function loop_typepersonnes($context,$funcname)
{  loop_typetable ("typepersonne","typeentite",$context,$funcname,$GLOBALS[edit] ? $context[typepersonne] : -1);}

function loop_typeentrees($context,$funcname)
{  loop_typetable ("typeentree","typeentite",$context,$funcname,$GLOBALS[edit] ? $context[typeentree] : -1);}

function loop_typeentites($context,$funcname)
{  loop_typetable ("typeentite2","typeentite",$context,$funcname,$GLOBALS[edit] ? $context[typeentite] : -1);}


?>
