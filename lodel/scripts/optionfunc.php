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

function getOptionGroupsPath($idgroup)
{
	require_once("dao.php");
	$dao = &getDAO("optiongroups");
	while($idgroup) 
	{
		$vo=$dao->getById($idgroup,"id,idparent,name");
		$path[] = $vo->name;
		$idgroup = $vo->idparent;
	}
	if(is_array($path))
		return array_reverse($path);
	else
		return array();
}


function cacheOptionsInFile($optionsfile)
{
	global $db;
	//foreach group create a temp array containing the path.
	$result=$db->execute(lq("SELECT #_TP_options.name,value,defaultvalue , #_TP_optiongroups.id as grpid ,#_TP_optiongroups.name as grpname FROM #_TP_options INNER JOIN #_TP_optiongroups ON #_TP_optiongroups.id=idgroup WHERE #_TP_optiongroups.status > 0 AND #_TP_options.status > 0"));
  if ($result===false) 
  {
		if ($db->errorno()!=1146) dberror();// table does not exists... that can happen during the installation 	
			$options_cache=array();
  }
 // create the cache options file
  $txt="<"."?php\n\$options_cache=array(\n";
  $pathOptiongroups = array(); //array 
  while ($result && !$result->EOF) 
  {
		if(isset($pathOptiongroups[$result->fields['grpid']])) // if the path has already been calculated
			$path = $pathOptiongroups[$result->fields['grpid']];
		else
			$pathOptiongroups[$result->fields['grpid']] = $path = getOptionGroupsPath($result->fields['grpid']);

		$optname=implode(".",$path).".".$result->fields['name'];
		$value=$result->fields['value'] ? $result->fields['value'] : $result->fields['defaultvalue'];
		$txt.="'".$optname."'=>'".$value."',\n";
		$options_cache[$optname]=$value;
		$result->MoveNext();
  }
  reset($pathOptiongroups);
  $txt.=");?".">";
  writefile($optionsfile,$txt);
  return $options_cache;
}

?>