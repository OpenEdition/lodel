<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier de gestion des types : tables liées (pas trop compris ce fichier là ... :()
 */

//
//
// Function to be rewritten in an Object framework: NN-relationship.
//
/**
 * Suppression des entrées dans les tables types liées
 *
 * @param string $typetable le nom du type
 * @param string $critere critères SQL
 */
function typetype_delete($typetable, $critere)
{
	global $db;
	$db->execute(lq("DELETE FROM #_TP_entitytypes_".$typetable."s WHERE $critere")) 
		or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
}

/**
 *
 * Ajout d'entrées dans les tables types liées
 *
 * @param array ou integer id du type de l'entité
 * @param array ou integer id du type
 * @param string nom de la table de type
 */
function typetype_insert($identitytype, $idtypetable, $typetable)
{
	global $db;
	// l'un ou l'autre des idtype doit etre un array, l'autre est un id fixe.
	//
	if (!$identitytype || !$idtypetable) {
		return;
	}
	$values = array ();
	if (is_array($idtypetable))	{
		$identitytype = (int)$identitytype;
		foreach ($idtypetable as $idtype => $cond) {
			array_push($values, "('$identitytype','$idtype','*')");
		}
	} else {
		$idtype = (int)$idtype;
		foreach ($identitytype as $idtype => $cond) {
			array_push($values, "('$idtype','$idtypetable','*')");
		}
	}
	$table = $typetable != 'entitytype2' ? $typetable : 'entitytype';

	$db->execute(lq("INSERT INTO #_TP_entitytypes_".$table."s (identitytype,id$typetable,cond) VALUES ".join(",", $values))) 
		or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
}


function loop_typetable($listtype, $criteretype, $context, $funcname, $checked = -1)
{
	global $db;
	if(empty($context['id'])) return;

	if ($listtype == 'entitytype' || $listtype == 'entitytype2') {
		$maintable = 'types';
		$rank = 'class, type';
		$relationtable = $criteretype;
	} else {
		$maintable = $listtype."s";
		$relationtable = $listtype;
		$rank = 'type';
	}

	$id = (int) $context['id'];
	$result = $db->execute(lq("SELECT * FROM #_TP_$maintable LEFT JOIN #_TP_entitytypes_".$relationtable."s ON id$listtype=#_TP_$maintable.id AND id$criteretype='$id' WHERE status>0 ORDER BY $rank")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

	while (!$result->EOF) {
		$localcontext = array_merge($context, $result->fields);
		if (is_array($checked)) {
			$localcontext['value'] = isset($checked[$result->fields['id']]) ? 'checked="checked"' : '';
		} else {
			$localcontext['value'] = $result->fields['cond'] ? 'checked="checked"' : '';
		}

		call_user_func("code_do_$funcname", $localcontext);
		$result->MoveNext();
	}
}