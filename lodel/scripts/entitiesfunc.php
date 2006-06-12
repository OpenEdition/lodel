<?php
/**
 * Fichier utilitaire pour les entités
 *
 * PHP version 4
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno Cénou, Jean Lamy
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
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno Cénou, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
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
	if ($id > 0) {
		$table = "#_TP_entitytypes_entitytypes INNER JOIN #_TP_entities as son ON identitytype=son.idtype";
		$criteria = "son.id='". $id. "'";
	}	elseif ($idtype > 0) {
		$table = "#_TP_entitytypes_entitytypes";
		$criteria = "identitytype='". $idtype. "'";
	}	else {
		die("ERROR: id=0 and idtype=0 in EntitiesLogic::_checkTypesCompatibility");
	}
	if ($idparent > 0) { // there is a parent
		$query = "SELECT cond FROM ". $table. " INNER JOIN #_TP_entities as parent ON identitytype2=parent.idtype  WHERE parent.id='". $idparent. "' AND ". $criteria;
	}	else { // no parent, the base.
		$query = "SELECT cond FROM ". $table. " WHERE identitytype2=0 AND ". $criteria;
	}
	#echo $query;
	$condition = $db->getOne(lq($query));
	if ($db->errorno()) {
		dberror();
	}
	return $condition;
}

/**
 * Test si l'entité pointée par $idcurrent est une descendante de $idref
 *
 * @param integer $idref Identifiant de l'entité de référence
 * @param integer $idcurrent Identifiant de l'entité courante
 * @return boolean true si $idcurrent est une descendante de $idref
 */
function isChild($idref, $idcurrent)
{
	global $db;
	if(!$idcurrent || !$idref) {
		return;
	}
	$sql = lq("SELECT idrelation FROM #_TP_relations where id2='$idcurrent' AND id1='$idref'");
	$idrelation = $db->getOne($sql);
	if ($db->errorno()) {
		dberror();
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
			require 'logic.php';
			require 'func.php';
			$logic = &getLogic('entities');
			foreach($ids as $id) {
				$context['id'] = $id;
				$logic->deleteAction($context, $error);
				}
		}
}


?>