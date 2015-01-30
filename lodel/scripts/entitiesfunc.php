<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier utilitaire pour les entités
 */

/**
 * Vérifie que le type de $id peut être associé au type du parent $idparent
 *
 * Check that the type of $id can be in the type of $idparent.
 * if $id=0 (creation of entites), use $idtype
 *
 * @param integer $id l'identifiant
 * @param integer $idparent l'identifant du parent
 * @param integer $idtype le type que l'on souhaite tester (utile si $id=0). Par défaut = 0
 * @return la condition de compatibilité entre les deux types.
 */
function checkTypesCompatibility($id, $idparent, $idtype = 0)
{
	global $db;
	#echo "id=$id;idparent=$idparent;idtype=$idtype";
	// check whether we have the right or not to put an entitie $id in the $idparent
	$id = (int)$id;
	$idparent = (int)$idparent;
	$idtype = (int)$idtype;
	if ($id > 0) {
		$table = "#_TP_entitytypes_entitytypes INNER JOIN #_TP_entities as son ON identitytype=son.idtype";
		$criteria = "son.id='". $id. "'";
	}	elseif ($idtype > 0) {
		$table = "#_TP_entitytypes_entitytypes";
		$criteria = "identitytype='". $idtype. "'";
	}	else {
		trigger_error("ERROR: id=0 and idtype=0 in EntitiesLogic::_checkTypesCompatibility", E_USER_ERROR);
	}
	if ($idparent > 0) { // there is a parent
		$query = "SELECT cond FROM ". $table. " INNER JOIN #_TP_entities as parent ON identitytype2=parent.idtype  WHERE parent.id='". $idparent. "' AND ". $criteria;
	}	else { // no parent, the base.
		$query = "SELECT cond FROM ". $table. " WHERE identitytype2=0 AND ". $criteria;
	}
// 	echo lq($query).'<br>';
	$condition = $db->getOne(lq($query));
	if ($db->errorno()) {
		trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
	}
	return $condition;
}

/**
 * Teste si l'entité pointée par $idcurrent n'est pas une descendante de $idref
 *
 * @param integer $idref Identifiant de l'entité de référence
 * @param integer $idcurrent Identifiant de l'entité courante
 * @return boolean false si $idcurrent est une descendante de $idref
 */
function isChild($idref, $idcurrent)
{
	global $db;
	if(!isset($idcurrent) || !isset($idref)) {
		return;
	}
	$idcurrent = (int)$idcurrent;
	$idref = (int)$idref;
	$sql = lq("SELECT idrelation FROM #_TP_relations where id2='$idcurrent' AND id1='$idref'");
	$idrelation = $db->getOne($sql);
	if ($db->errorno()) {
		trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
	}
	return $idrelation ? false : true; // si on a une relation (descendance) retourne false
}


/**
 * Suppression des entités à -64 dont la dernière modification remonte à + de 12 h. Cette fonction est  appelée dans index.php (côté édition), lorsqu'il n'y a ni $do, ni $lo dans la requete (lorsque le controler n'est pas appelé).
 *
 * Cette fonction appelle l'action delete de la logique des entités.
 *
 * @see class.entities.php
 */

function cleanEntities ()
{
	global $db;
	$mysql = lq('SELECT id FROM #_TP_entities WHERE status=-64 AND upd < DATE_SUB(NOW(), INTERVAL 12 HOUR)');
	$result = $db->execute($mysql);
	$ids = array();
	while(!$result->EOF) {
		$ids[] = $result->fields['id'];
		$result->MoveNext();
	}
	
	if (is_array($ids)) {
		$logic = Logic::getLogic('entities');
		foreach($ids as $id) {
			$context['id'] = $id;
			$logic->deleteAction($context, $error);
		}
	}
}