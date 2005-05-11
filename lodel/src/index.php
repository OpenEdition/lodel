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

require("siteconfig.php");
require_once("auth.php");
authenticate();

require_once("view.php");
//
// record the url if logged
//
if ($lodeluser['rights']>=LEVEL_VISITOR) recordurl();
//
// get the view and checked the cache.
//
$view=&getView();
if ($view->renderIfCacheIsValid()) return;

require_once("textfunc.php");

$id=intval($_GET['id']);
$identifier=$_GET['identifier'];
$page=$_GET['page']; // get only
$tpl="index"; // template by default.
$do=$_POST['do'] ? $_POST['do'] : $_GET['do'];

  //------------------------------ ID ou IDENTIFIER -------------------
if ($id || $identifier) {
  require_once("connect.php");
  do { // exception block
    require_once("func.php");  
    if ($id) {
      $class=$db->getOne(lq("SELECT class FROM #_TP_objects WHERE id='".$id."'"));
      if ($db->errorno() && $lodeluser['rights']>LEVEL_VISITOR) dberror();
      if (!$class) { header ("Location: not-found.html"); return; }
    } elseif ($identifier) {
      $class="entities";
    } else {
      die("?? strange");
    }
    switch($class) {
    case 'entities':
      printEntities($id,$identifier,$context);
      break;
    case 'entrytypes':
    case 'persontypes':
      $result=$db->execute(lq("SELECT * FROM #_TP_".$class." WHERE id='".$id."' AND status>0")) or dberror();
      $context['type']=$result->fields;
      $view=&getView();
      $view->renderCached($context,$result->fields['tplindex']);
      exit();
    case 'persons':
    case 'entries':
      printIndex($id,$class,$context);
      break;
    } // switch class
  } while(0);

  //------------------------------ PAGE -------------------
 } elseif ($page) { // call a special page (and template)
   if (strlen($page)>64 || preg_match("/[^a-zA-Z0-9_\/-]/",$page)) die("invalid page");
   $view->renderCached($context,$page);
   exit();

  //------------------------------ DO -------------------
 } elseif ($do) {
   if ($do=="edit" || $do=="view") {
     $_GET['id']=$_POST['id']=0; // to be sure nobody is going to modify something wrong
     // check for the right to change this document
     $idtype=$_POST['idtype'] ? intval($_POST['idtype']) : intval($_GET['idtype']) ;
     if (!$idtype) die("ERROR: idtype must be given");
     require_once("dao.php");
     $dao=&getDAO("types");
     $vo=$dao->find("id='$idtype' and public>0 and status>0");
     if (!$vo) die("ERROR: you are not allow to add this kind of document");
     $lodeluser['rights']=LEVEL_REDACTOR; // grant temporary
     require_once("controler.php");
     Controler::controler(array("entities_edition"),"entities_edition");
     exit();
   } else {
     die("ERROR: unknown action");
   }
 } else {
  require_once("connect.php");
  $query=preg_replace("/[&?](format|clearcache)=\w+/","",$_SERVER['QUERY_STRING']);
  if($query && !preg_match("/[^a-zA-Z0-9_\/-]/",$query)) {
    // maybe a path to the document
    $path=preg_split("#/#",$query,-1,PREG_SPLIT_NO_EMPTY);
    $id=0;
    $i=0;
    while ($path[$i]) {
      $join="#_TP_entities as e0";
      $where="AND e0.identifier='".$path[$i]."'";
      $j=1;

      $i++;
      while($path[$i] && ($i % 4) ) { // 4 join max
	$join.=" INNER JOIN #_TP_entities as e$i ON e$i.idparent=e".($i-1).".id";
	$where.=" AND e$i.identifier='".$path[$i]."'";
	$i++;$j++;
      }
      #echo lq("SELECT e".($j-1).".id FROM ".$join." WHERE e0.idparent='".$id."' ".$where);
      $id=$db->getOne(lq("SELECT e".($j-1).".id FROM ".$join." WHERE e0.idparent='".$id."' ".$where));
      if ($db->errorno()) dberror();    
    }
    if ($id) {
      printEntities($id,"",$context);
    }
  } else {
    // nohting to do...
  }
}


$view->renderCached($context,"index");


function printEntities($id,$identifier,&$context)

{

  global $lodeluser,$home,$db;

  $critere=$lodeluser['visitor'] ? "AND #_TP_entities.status>-64" : "AND #_TP_entities.status>0 AND #_TP_types.status>0";


  //
  // cherche le document, et le template
  //
  if (!(@include_once("CACHE/filterfunc.php"))) require_once("filterfunc.php");
  
  do {
    if ($identifier) {
      $identifier=addslashes(stripslashes(substr($identifier,0,255)));
      $where="#_TP_entities.identifier='".$identifier."' ".$critere;
    } else {
      $where="#_TP_entities.id='".$id."' ".$critere;
    }
    $row=$db->getRow(lq("SELECT #_TP_entities.*,tpl,type,class FROM #_entitiestypesjoin_ WHERE ".$where));
    if ($row===false) dberror();
    if (!$row) { header ("Location: not-found.html"); return; }
    $base=$row['tpl'];
    if (!$base) { $id=$row['idparent']; $relocation=TRUE; }
  } while (!$base && !$identifier && $id);    

  if ($relocation) { 
    header("location: ".makeurlwithid("index",$row['id']));
    exit;
  }
  $context=array_merge($context,$row);
  $row=$db->getRow(lq("SELECT * FROM #_TP_".$row['class']." WHERE identity='".$row['id']."'"));
  if ($row===false) dberror();
  if (!$row) die("ERROR: internal error");
  merge_and_filter_fields($context,$row['class'],$row);

#    print_R($context);
  $view=&getView();
  $view->renderCached($context,$base);
  exit();
}

function printIndex($id,$classtype,&$context)

{
  global $lodeluser,$home,$db;

  switch($classtype) {
  case 'persons':
    $typetable="#_TP_persontypes";
    $table="#_TP_persons";
    $longid="idperson";
    break;
  case 'entries':
    $typetable="#_TP_entrytypes";
    $table="#_TP_entries";
    $longid="identry";
    break;
  default: 
    die("ERROR: internal error in printIndex");
  }

  // get the index
  $critere=$lodeluser['visitor'] ? "AND status>-64" : "AND status>0";
  $row=$db->getRow(lq("SELECT * FROM ".$table." WHERE id='".$id."' ".$critere));
  if ($row===false) dberror();
  if (!$row) { header ("Location: not-found.html"); return; }
  $context=array_merge($context,$row);

  // get the type
  $row=$db->getRow(lq("SELECT * FROM ".$typetable." WHERE id='".$row['idtype']."'".$critere));
  if ($row===false) dberror();
  if (!$row) { header ("Location: not-found.html"); return; }
  $base=$row['tpl'];
  $context['type']=$row;

  // get the associated table
  $row=$db->getRow(lq("SELECT * FROM #_TP_".$row['class']." WHERE ".$longid."='".$id."'"));
  if ($row===false) dberror();
  if (!$row) die("ERROR: internal error");
  if (!(@include_once("CACHE/filterfunc.php"))) require_once("filterfunc.php");
  merge_and_filter_fields($context,$row['class'],$row);

  $view=&getView();
  $view->renderCached($context,$base);
  exit();
}



function loop_alphabet($context,$funcname)

{
  for($l="A"; $l!="AA"; $l++) {
    $context['lettre']=$l;
    call_user_func("code_do_$funcname",$context);
  }
}


?>
