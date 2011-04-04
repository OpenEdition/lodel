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
 * Copyright (c) 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * Copyright (c) 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
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
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
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
	
function cacheOptionsInFile($optionsfile=null)
{
	if(!isset($optionsfile) && ($options = getFromCache('options')))
	{
        	return $options;
	}
    
    	defined('INC_CONNECT') || include 'connect.php';
	global $db;
	$ids = $arr = array();
	do {
		$sql = 'SELECT id,idparent,name 
                    FROM '.$GLOBALS['tp'].'optiongroups 
                    WHERE status > 0 AND idparent '.(is_array($ids) ? "IN ('".join("','",$ids)."')" : "='".$ids."'").
                    " ORDER BY rank";
		$result = $db->Execute($sql) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
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
			++$i;
			$result->MoveNext();
		}
        	$result->Close();
	}	while ($ids);
    
	$sql = 'SELECT id, idgroup, name, value, defaultvalue, type 
               FROM '.$GLOBALS['tp'].'options 
               WHERE status > 0 ';
               
    	if(!isset($optionsfile))
        	$sql .= 'AND type != "passwd" AND type != "username" ';
        
    	$sql .= 'ORDER BY rank';

	$result = $db->Execute($sql) 
       		or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

	if(isset($optionsfile))
	{
		$options_cache_return = $options_cache = array();
		$txt = "<"."?php\n\$options_cache=array(\n";
		$txt2 = "\n\$options_cache_return=";
		while (!$result->EOF)   {
			$id = $result->fields['id'];
			$name = $result->fields['name'];
			$idgroup = $result->fields['idgroup'];
			if(!isset($arr[$idgroup])) continue;

			$value = $result->fields['value'] ? $result->fields['value'] : $result->fields['defaultvalue'];
			if('username' != $result->fields['type'] && 'passwd' !== $result->fields['type'])
			{
				if(!isset($options_cache_return[$arr[$idgroup]]))
					$options_cache_return[$arr[$idgroup]] = array();
				$options_cache_return[$arr[$idgroup]][$name] = $value;
			}
			$optname = $arr[$idgroup].".".$name;
			$txt .= "'".$optname."'=>'".addslashes($value)."',\n";
			$options_cache[$optname] = addslashes($value);
			$result->MoveNext();
		}
        	$result->Close();
        	$txt .= ");\n";
        	$txt2 .= var_export($options_cache_return, true).";?".">";
        
		if(FALSE === file_put_contents($optionsfile, $txt.$txt2)) 
			trigger_error("Cannot write $optionsfile.", E_USER_ERROR);
        
        	@chmod ($optionsfile,0666 & octdec(C::get('filemask', 'cfg'))); 
		
        	return $options_cache;
	}
	else
	{
		$options_cache_return = array();
		while (!$result->EOF)   {
			$id = $result->fields['id'];
			$name = $result->fields['name'];
			$idgroup = $result->fields['idgroup'];
			if(!isset($arr[$idgroup])){ 
                $result->MoveNext();
			    continue;
			}

			$value = $result->fields['value'] ? $result->fields['value'] : $result->fields['defaultvalue'];
			if('username' != $result->fields['type'] && 'passwd' !== $result->fields['type'])
			{
				if(!isset($options_cache_return[$arr[$idgroup]]))
					$options_cache_return[$arr[$idgroup]] = array();
				$options_cache_return[$arr[$idgroup]][$name] = $value;
			}
			$result->MoveNext();
		}
		$result->Close();
		if($options_cache_return)
			writeToCache('options', $options_cache_return);
		return $options_cache_return;
	}
}