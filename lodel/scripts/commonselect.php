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


function makeSelectFieldTypes($value)

{
  require_once("fieldfunc.php");
  $arr2=array();
  foreach($GLOBALS['lodelfieldtypes'] as $k=>$v) if ($v) $arr2[$k]=getlodeltextcontents($k,"common");
  renderOptions($arr2,$value);
}


function makeSelectUserRights($value,$adminlodel)

{
  $arr=array(LEVEL_VISITOR=>getlodeltextcontents("user_visitor","common"),
	     LEVEL_REDACTOR=>getlodeltextcontents("user_redactor","common"),
	     LEVEL_EDITOR=>getlodeltextcontents("user_editor","common"),
	     LEVEL_ADMIN=>getlodeltextcontents("user_admin","common"),
	     ); 
  if (!$GLOBALS['site'] && !SINGLESITE) $arr=array();
  if ($adminlodel) 
    $arr[LEVEL_ADMINLODEL]=getlodeltextcontents("user_adminlodel","common");

  // take only the rights below the current user rights
  // in pratice only the ADMIN and ADMINLODEL should be authorized to add/remove users...
  $arr2=array();
  foreach($arr as $k=>$v) if ($GLOBALS['lodeluser']['rights']>=$k) $arr2[$k]=$v;
  renderOptions($arr2,$value);
}
