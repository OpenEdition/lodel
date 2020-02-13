<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
* Fichier de gestion des filtres
*/

$cache_name = getCacheIdFromId('filterfunc');
if(!cache_exists($cache_name)) makefilterfunc();
cache_include($cache_name);

/**
* Filtre les champs qu'il faut filtrer et converti les filtres en fonction
*
*/
function makefilterfunc()
{
	global $db;
	defined('INC_CONNECT') || include 'connect.php';
	// cherche les champs a filtrer	
	$result = $db->Execute("
	SELECT class,name,filtering 
		FROM {$GLOBALS['tp']}tablefields 
		WHERE status > 0 AND filtering!=''") 
		or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

	$filterstr = '';
	while (!$result->EOF)	{
		$row = $result->fields;
		// convert filter into a function
 		$filters = explode("|", $row['filtering']);
		$filterfunc = '$x';
		foreach ($filters as $filter)	{
			if (preg_match("/^([A-Za-z][A-Za-z_0-9]*)(?:\((.*?)\))?$/", $filter, $result2)) {
				$funcname = $result2[1]; // name of the pipe function
				$arg = '';
				if(isset($result2[2]))
				{
					$arg = $result2[2]; // argument if any
	
					// process the variable. The processing is simple here. Need more ? Sould be associated with parser variable processing.
					//$arg = preg_replace("/\[\#([A-Z][A-Z_0-9]*)\]/e", ' "$"."context[".strtolower("\\1")."]" ', $arg);
					$arg = preg_replace_callback("/\[\#([A-Z][A-Z_0-9]*)\]/", function ($test) { return "$"."context[".strtolower($test[1])."]";} , $arg);
	
					if ($arg) {
						$arg = ','. $arg;
					}
				}
				$filterfunc = $funcname.'('.$filterfunc.$arg.')';
			}	elseif ($filter)	{
				trigger_error("invalid filter function: $filter", E_USER_ERROR);
			} // do nothing if $filter is empty
		}
		$filterfunc = "return ". $filterfunc. ";";
		$filterstr .= "'". $row['class'].'.'. $row['name']. "' => '". addcslashes($filterfunc, "'")."',";
			#echo "filterstr=$filterstr";
		$result->MoveNext();
	}
	$result->Close();

	$cache      = getCacheObject();
	$cache_name = getCacheIdFromId('filterfunc');

	// build the function with filtering
	$filterfunc = '
    function filtered_mysql_fetch_assoc($context, $result) {
        $row = $result->FetchRow();
        if (!$row) return array();
	$filters = array('.$filterstr.');
	if(empty($filters)) return $row;
	$count = $result->RecordCount();
	$ret = array();
        for($i = 0; $i < $count; $i++) {
	    $field = $result->FetchField($i);
            $fieldname[$i] = isset($field->orgname) ? $field->orgname : $field->name;
            $fullfieldname[$i] = (isset($field->orgtable) ? $field->orgtable : $field->table). ".". $fieldname[$i];
            $ret[$fieldname[$i]] = $row[$fieldname[$i]];
        }
        $localcontext=array_merge($context, $ret);
        for($i = 0; $i < $count; $i++) {
            if (!empty($filters[$fullfieldname[$i]])) {
                $filter = create_function(\'$x, $context\', $filters[$fullfieldname[$i]]);
                $ret[$fieldname[$i]] = $filter($ret[$fieldname[$i]], $localcontext);
            }
        }
        return $ret;
    }
    
    /**
    * Function to filter field of a single class.
    */
    function merge_and_filter_fields(&$context, $class, &$assoc)
    {
        $filters = array('. $filterstr. ');
        if(empty($filters)) {
            $context = array_merge($context, $assoc);
            return;
        }
        $localcontext = array_merge($context, $assoc);
        foreach($assoc as $k=>$v) {
            if (!empty($filters[$class. ".". $k])) {
                $filter = create_function(\'$x, $context\', $filters[$class. ".". $k]);
                $context[$k] = $filter($v, $localcontext);
            } else {
                $context[$k] = $v;
            }
        }
    }';
	$cache->set($cache_name, $filterfunc);
}
