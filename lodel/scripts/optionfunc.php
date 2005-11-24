<?php

/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�ou
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

function cacheOptionsInFile($optionsfile)
{
	global $db;
	do {
		$sql = lq("SELECT id,idparent,name FROM #_TP_optiongroups WHERE status > 0 AND idparent ".sql_in_array($ids)." ORDER BY rank");
		$result = $db->execute($sql);
		$ids = array ();
		$i = 1;
		$l = 1;
		while (!$result->EOF) {
			$id = $result->fields['id'];
			$name = $result->fields['name'];
			$idparent = $result->fields['idparent'];
			$ids[] = $id;
			if ($idparent)
				$name = $parent[$idparent].".".$name;
			#$d = $rank[$id] = $rank[$idparent]+($i*1.0)/$l;
			$arr[$id] = $name;
			$parent[$id] = $name;
			$l *= 100;
			$i ++;
			$result->moveNext();
		}
	}	while ($ids);

	$sql = lq("SELECT id, idgroup, name, value, defaultvalue FROM #_TP_options "." WHERE status > 0 ORDER BY rank");
	$result = $db->execute($sql);
	$txt = "<"."?php\n\$options_cache=array(\n";
	while (!$result->EOF)	{
		$id = $result->fields['id'];
		$name = $result->fields['name'];
		$idgroup = $result->fields['idgroup'];
		$value = $result->fields['value'] ? $result->fields['value'] : $result->fields['defaultvalue'];
		$optname = $arr[$idgroup].".".$name;
		clean_request_variable($value);
		$txt .= "'".$optname."'=>'".addslashes($value)."',\n";
		$options_cache[$optname] = addslashes($value);
		$result->MoveNext();
	}
	$txt .= ");?".">";
	#echo "<textarea cols=100 rows=10>$txt</textarea>";
	$ret = writefile($optionsfile, $txt);
	return $options_cache;
}
?>