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

require("siteconfig.php");
require_once($home."auth.php");
authenticate();

require_once($home."view.php");
//
// record the url if logged
//
if ($lodeluser['rights']>=LEVEL_VISITOR) recordurl();
//
// get the view and checked the cache.
//
$view=&getView();
if ($view->renderIfCacheIsValid()) return;

require_once($home."textfunc.php");

$id=intval($_GET['id']);
$tpl="index"; // template by default.

if ($id) {
  require_once($home."connect.php");
  do { // exception block
    require_once($home."func.php");  
    $class=$db->getOne(lq("SELECT class FROM #_TP_objects WHERE id='".$id."'"));
    if ($db->errorno() && $lodeluser['rights']>LEVEL_VISITOR) dberror();
    if (!$class) break;

    switch($class) {
    case 'entities':
      printEntities($id,$_GET['identifier'],$context);
      break;
    } // switch class
  } while(0);
} else{
  require_once($home."connect.php");
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
  if (!(@include_once("CACHE/filterfunc.php"))) require_once($home."filterfunc.php");
  
  if ($id || $identifier) {
    do {
      if ($identifier) {
	$identifier=addslashes(stripslashes(substr($identifier,0,255)));
	$identifier=addslashes(stripslashes($identifier));
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
}

?>
