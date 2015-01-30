<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier utilitaire de gestion des options
 */

/**
 * Mise en cache des options (table Option) dans un fichier
 *
 * En plus de créer le fichier de cache des options, un tableau de ces options est aussi créé
 * Tableau de la forme [groupname.optionname][value]
 *
 * Si la fonction est appelée sans argument, le fichier n'est pas écrit et le tableau est de la forme [groupname][optionname][value] : utilisé pour passer les options dans le $context
 * @param string $cache_name le nom du fichier cache des options
 * @return array le tableau des options
 */
	
function cacheOptionsInFile( $cache_name = null )
{
	$cache = getCacheObject();
	if(!isset( $cache_name ) && ($options = $cache->get('options')))
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
			$id       = $result->fields['id'];
			$name     = $result->fields['name'];
			$idparent = $result->fields['idparent'];
			$ids[]    = $id;
			if ($idparent) $name = $parent[$idparent].".".$name;
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

		if(!isset($cache_name))
			$sql .= 'AND type != "passwd" AND type != "username" ';
		$sql .= 'ORDER BY rank';

	$result = $db->Execute($sql) 
			or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

	if(isset($cache_name))
	{
		$options_cache_return = $options_cache = array();
		$txt = "<"."?php\n\$options_cache=array(\n";
		$txt2 = "\n\$options_cache_return=";
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
			$optname = $arr[$idgroup].".".$name;
			$txt .= "'".$optname."'=>'".addslashes($value)."',\n";
			$options_cache[$optname] = addslashes($value);
			$result->MoveNext();
		}
		$result->Close();
		$txt .= ");\n";
		$txt2 .= var_export($options_cache_return, true).";?".">";

		$cache->set($cache_name, $txt . $txt2 );

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
		if($options_cache_return){
			$cache = getCacheObject();
			$cache->set(getCacheIdFromId('options'), $options_cache_return);
		}
		return $options_cache_return;
	}
}