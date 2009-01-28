<?php
/**
 * Fichier utilitaire de gestion des options
 *
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 *
 * Home page: http://www.lodel.org
 *
 * E-Mail: lodel@lodel.org
 *
 * All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @author Ghislain Picard
 * @author Jean Lamy
 * @author Sophie Malafosse
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */

/**
 * Mise en cache des options (table Option) dans un fichier
 *
 * En plus de créer le fichier de cache des options, un tableau de ces options est aussi créé
 * Tableau de la forme [groupname.optionname][value]
 *
 * Si la fonction est appelée sans argument, le fichier n'est pas écrit et le tableau est de la forme [groupname][optionname][value] : utilisé pour passer les options dans le $context
 * @param string $optionsfile le nom du fichier cache des options
 * @return array le tableau des options
 */
if(!function_exists('clean_request_variable'))
	require 'func.php';
function cacheOptionsInFile($optionsfile='')
{
	global $db;
	$ids = array();
	do {
		$sql = lq('SELECT id,idparent,name FROM #_TP_optiongroups WHERE status > 0 AND idparent '.sql_in_array($ids)." ORDER BY rank");
		$result = $db->execute($sql) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
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

	if (!empty($optionsfile)) {
		$sql = lq('SELECT id, idgroup, name, value, defaultvalue FROM #_TP_options WHERE status > 0 ORDER BY rank');
	} else { // pas les username et passwd dans le context
		$sql = lq("SELECT id, idgroup, name, value, defaultvalue FROM #_TP_options WHERE status > 0 AND type !='passwd' AND type !='username' ORDER BY rank");
	}

	$result = $db->execute($sql) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
	$txt = "<"."?php\n\$options_cache=array(\n";
	while (!$result->EOF)	{
		$id = $result->fields['id'];
		$name = $result->fields['name'];
		$idgroup = $result->fields['idgroup'];
		$value = $result->fields['value'] ? $result->fields['value'] : $result->fields['defaultvalue'];
		if (!empty($optionsfile)) {
			$optname = $arr[$idgroup].".".$name;
			clean_request_variable($value);
			$txt .= "'".$optname."'=>'".addslashes($value)."',\n";
			$options_cache[$optname] = addslashes($value);
		} else {
			$optname = $name;
			clean_request_variable($value);
			//$txt .= "'".$optname."'=>'".$value."',\n";
			$papa = $arr[$idgroup];
			$options_cache[$papa][$optname] = $value;
		}
		$result->MoveNext();
	}
	$txt .= ");?".">";
	#echo "<textarea cols=100 rows=10>$txt</textarea>";
	if (!empty($optionsfile)) { $ret = writefile($optionsfile, $txt); }
	return $options_cache; 
}
?>