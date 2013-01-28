<?php

/* Pas en CLI on doit être adminlodel */
if (php_sapi_name() != "cli") {
	authenticate(LEVEL_ADMINLODEL);
} else {
	// en CLI on s'authentifie comme adminlodel
	define('backoffice-lodeladmin', true);
	require_once 'lodelconfig.php';

	define("SITEROOT","");
	$cfg['versionsuffix'] = "-".$cfg['version'];   # versioning
	$cfg['home'] = "lodel{$cfg['versionsuffix']}/scripts/";
	$cfg['sharedir'] = SITEROOT . $cfg['sharedir'].$cfg['versionsuffix'];
	$cfg['shareurl'] .= $cfg['versionsuffix'];
	ini_set('include_path', SITEROOT. $cfg['home'] . PATH_SEPARATOR . ini_get('include_path'));
	require 'context.php';
	C::setCfg($cfg);

	require_once 'auth.php';
	$lodeluser = array('rights'=>LEVEL_ADMINLODEL, 'adminlodel'=>1, 'id'=>1, 'groups'=>'');
	C::set('lodeluser', $lodeluser);
	C::set('login', 'admin');
	C::setUser($lodeluser);
	unset($lodeluser);

	$GLOBALS['nodesk'] = true;
	C::set('nocache', true);
	require_once 'connect.php';
}

class upgradeME {

	// 	FONCTIONS DE HAUT NIVEAU (sic)

	// Rajouter un hook au champ $field de la $class
	public function add_editionhook($class, $field, $hook, $clobber=false) {
		$tf = $this->getTableField($class, $field);
		if ($tf) {
			if ($clobber) {
				$tf['editionhooks'] = $hook;
			} else {
				$tf['editionhooks'] = $this->update_editionhooks($tf['editionhooks'], $hook);
			}
			$ok = $this->updateTableField($tf);
			if ($ok)
				echo "Ajout du hook '$hook' sur le champ '$field' de la classe '$class' effectué."."<br>\n";
			else
				echo "ERREUR: Ajout du hook '$hook' sur le champ '$field' de la classe '$class' non effectué."."<br>\n";
		} else {
			echo "ERREUR: Ajout du hook '$hook' : pas de champ '$field' ou pas de classe '$class'"."<br>\n";
		}
	}

	// Modifier les hooks du champ $field de la $class
	public function change_editionhooks($class, $field, $hooks) {
		$this->add_editionhook($class, $field, $hooks, true);
	}

	// Modifier les champs $changes du champ $field de la $class
	public function change_tablefield($class, $fieldname, $changes) {
		$field = $this->getTableField($class, $fieldname);
		if (!$field) {
			echo "ERREUR: modification du champ '$fieldname' de la classe '$class', le champ ou la classe sont inexistants"."<br>\n";
			return false;
		}
		$field = array_merge($field, $changes);
		$ok = $this->updateTableField($field);
		if ($ok)
			echo "Modification du champ '$fieldname' de la classe '$class' effectué."."<br>\n";
		else {
			echo "ERREUR: Modification du champ '$fieldname' de la classe '$class' non effectué."."<br>\n";
			return false;
		}
		return true;
	}

	// Créer un champ $fieldname de type $type de la $class dans le groupe $groupname
	public function add_tablefield($class, $groupname, $fieldname, $type, $infos) {
		$group = $this->getObject('tablefieldgroups',"name='$groupname' AND class='$class'");
		if (!$group) {
			echo "ERREUR: Création du champ '$fieldname' de la classe '$class' non effectué. Le groupe '$groupname' n'existe pas'"."<br>\n";
			return false;
		}
		$id_group = $group['id'];

		$field = $this->getTableField($class, $fieldname);
		if (!$field) {
			$field = array ('id'=>'0', 'name'=>$fieldname, 'class'=>'publications', 'title'=>$fieldname, 'altertitle'=>'', 'gui_user_complexity'=>'64', 'idgroup'=>$id_group, 'type'=>$type, 'g_name'=>'', 'style'=>'', 'cond'=>'*', 'defaultvalue'=>'', 'processing'=>'', 'mask'=>'', 'allowedtags'=>'', 'edition'=>'editable', 'editionparams'=>'', 'editionhooks'=>'', 'weight'=>'0', 'filtering'=>'', 'comment'=>'', 'status'=>'1', 'rank'=>'', 'otx'=>'', );
			$field = array_merge($field, $infos);
			$ok = $this->updateTableField($field);
			if ($ok)
				echo "Création du champ '$fieldname' de la classe '$class' effectué."."<br>\n";
			else {
				echo "ERREUR: Création du champ '$fieldname' de la classe '$class' non effectué."."<br>\n";
				return false;
			}
		} else {
			echo "ERREUR: Création du champ '$fieldname' de la classe '$class' non effectué. Il existe déjà"."<br>\n";
			return false;
		}
		return true;
	}

	// Le reste

	public function setdb($db_name) {
		$GLOBALS['currentdb'] = $db_name;
		usecurrentdb();
	}

	// change la valeur d'un champ par la valeur d'un autre champ
	public function migrateField($tableFieldFrom, $tableFieldTo, $overwrite = false) {
		global $db;
		if (($tableFieldFrom['class'] != $tableFieldTo['class']) || ($tableFieldFrom['type'] != $tableFieldTo['type'])) {
			echo "ERREUR: Changement sur la table {$tableFieldFrom['class']} non effectué. class et type ne correspondent pas"."<br>\n";
			return false;
		}
		$class = DAO::getDAO('classes')->find("class=".$db->Quote($tableFieldFrom['class']),"classtype");
		$idname = "id".pluralChange($class->classtype);
		$fields = $db->Execute(lq("SELECT {$tableFieldFrom['name']} as old, {$tableFieldTo['name']} as new, $idname as id FROM #_TP_{$tableFieldFrom['class']}"));
		foreach ($fields as $field) {
			$sql = lq("UPDATE #_TP_{$tableFieldFrom['class']} SET {$tableFieldTo['name']}=".$db->Quote($field['old'])." WHERE $idname={$field['id']}");
			if ($overwrite || !$field['new'])
				$db->Execute($sql);
		}
		echo "Changement sur la table {$tableFieldFrom['class']} valeurs de {$tableFieldFrom['name']} vers {$tableFieldTo['name']}"."<br>\n";
		return true;
	}

	// transforme un champ texte en un index
	// l'index doit exister
	public function fieldToIndex($field, $entrytype) {
		global $db;
		$g_name = $this->getGname($entrytype['class'], "index key");

		// cherche les entitées avec ce champ
		$entryLo = Logic::getLogic("entries");
		$entitiesWithField = lq("select identity as id, {$field['name']} as field FROM #_TP_{$field['class']} WHERE {$field['name']} != ''");
		$entities = $db->Execute($entitiesWithField);
		foreach ($entities as $entity) {
			// créer une entré d'index pour chaque mot du champ (mot séparé par des ,)
			foreach(explode(',', $entity['field']) as $entry) {
				$entry = array('g_name'=>$entry, 'idtype'=>$entrytype['id'], 'identity'=>$entity['id'], 'lo'=>'entities_edition', 'degree'=>0);
				$entryLo->editAction($entry, $error);
				if ($error) {
					echo "ERREUR: Passage du champ {$field['name']} en index non effectué: ".var_export($error, true)."<br>\n";
					return false;
				}
			}
		}

		// effacer le champ texte
		if (! $this->deleteTableField($field)) {
			echo "ERREUR: Passage du champ {$field['name']} en index non effectué ({$field['name']} non effacé): ".var_export($error, true)."<br>\n";
			return false;
		}

		// création du champ index
		$indexField = $field;
		$indexField['id'] = 0;
		$indexField['type'] = 'entries';
		$indexField['name'] = $entrytype['type'];
		$indexField['style'] = '';
		$indexField['cond'] = '';
		if (! $this->updateTableField($indexField)) {
			echo "ERREUR: Passage du champ {$field['name']} en index non effectué (TableField {$indexField['name']} non créé): ".var_export($error, true)."<br>\n";
			return false;
		}

		echo "Passage du champ {$field['name']} en index"."<br>\n";
		return true;
	}

	// transforme un champ index en champ texte
	// si on donne un $field c'est celui-ci qui est utilisé pour la conversion
	public function indexToField($indexField, $deleteEntry = true, $field = false) {
		global $db;
		$entrytype = $this->getTypeFromField($indexField);
		if (!$entrytype) {
			echo "ERREUR: le champ index de {$indexField['name']} n'en est pas un."."<br>\n";
			return false;
		}
		$g_name = $this->getGname($entrytype['class']);
		$gnameField = $this->getTableField($entrytype['class'], $g_name);
		if (!$field) {
			// création du champ avec un nom temporaire
			$field = $indexField;
			$field['id'] = 0;
			$field['name'] = $indexField['name']."copy";
			$field['g_name'] = '';
			$field['cond'] = $gnameField['cond'];
			$field['type'] = $gnameField['type'];
			if (! $this->updateTableField($field)) {
				echo "ERREUR: Passage de l'index {$indexField['name']} en champ non effectué: ".var_export($error, true)."<br>\n";
				return false;
			}
			$id = $db->GetOne(lq("SELECT id FROM #_TP_tablefields WHERE name=".$db->Quote($field['name'])." AND class=".$db->Quote($field['class']).""));
			$field = $this->getObject('tablefields', "id=$id");
		}

		// Chercher les entités liées à l'index
		$relatedEntities = lq("SELECT r.id1 as id FROM #_TP_relations as r, #_TP_entries as e WHERE r.id2 = e.id AND e.idtype={$entrytype['id']} GROUP BY r.id1");
		$entities = $db->Execute($relatedEntities);
		foreach($entities as $entityRow) {
			$entryName = array();
			// renseigner le champ
			$relatedEntries = lq("SELECT i.$g_name as g_name, r.idrelation as idrelation FROM #_TP_relations as r, #_TP_entries as e LEFT JOIN #_TP_{$entrytype['class']} as i ON i.identry = e.id WHERE r.id2 = e.id AND e.idtype={$entrytype['id']} AND r.id1={$entityRow['id']}");
			$entries = $db->Execute($relatedEntries);
			foreach ($entries as $entryRow) {
				$entryName[] = $entryRow['g_name'];
				if (!$deleteEntry) {
					$deleteRelation = lq("DELETE FROM #_TP_relations WHERE idrelation={$entryRow['idrelation']}");
					$deleteRelationAttribut = lq("DELETE FROM #_TP_entities_{$entrytype['class']} WHERE idrelation={$entryRow['idrelation']}");
					$db->Execute($deleteRelation);
					$db->Execute($deleteRelationAttribut);
				}
			}
			$entryName = implode(',', $entryName);
			$addField = lq("UPDATE #_TP_{$indexField['class']} SET {$field['name']} = ".$db->Quote($entryName)." WHERE identity={$entityRow['id']}");
			$db->Execute($addField);
		}

		// effacer les entrées de l'index et son type
		if ($deleteEntry)
			$this->deleteEntries($entrytype['id'], true);
		else
			$this->deleteTableField($indexField);

		// renommer le nouveau champ
		$field['name'] = $indexField['name'];
		$this->updateTableField($field);

		echo "Passage de l'index {$indexField['name']} en champ"."<br>\n";
		return true;

	}

	// Change la valeur d'un champ d'une table
	public function updateField($tableField, $value='') {
		global $db;
		$sql = lq("UPDATE #_TP_{$tableField['class']} SET {$tableField['name']}=" . $db->Quote($value));
		$db->Execute($sql);
		echo "Changement sur la table {$tableField['class']} valeurs de {$tableField['name']} passé à '$value'"."<br>\n";
		return true;
	}

	// Change le type d'un tableField
	public function updateFieldType($tableField, $newtype) {
		isset($GLOBALS['lodelfieldtypes']) || include "fieldfunc.php";
		global $lodelfieldtypes;
		if (!isset($lodelfieldtypes[$newtype])) {
			echo "ERREUR: Changement de type sur {$tableField['name']} non effectué. Le type est invalide"."<br>\n";
			return false;
		}
		$tableField['type'] = $newtype;
		if (! $this->updateTableField($tableField)) {
			echo "ERREUR: Changement de type sur {$tableField['name']} en type '$newtype' non effectué: ".var_export($error, true)."<br>\n";
			return false;
		}

		echo "Changement de type sur {$tableField['name']} en type '$newtype'"."<br>\n";
		return true;
	}

	// effacer les entrèes d'un index
	// optionnelement son type : efface alors aussi les tablefiels associés
	public function deleteEntries($idtype, $deleteType = false) {
		$logic = Logic::getLogic('entries');

		// efface les entrèes
		$dao = DAO::getDAO('entries');
		$entries = $dao->findMany("idtype=$idtype","","id");
		foreach ($entries as $entry) {
			$entry = array('id' => $entry->id);
			$logic->$this->deleteAction($entry, $error);
			if ($error) {
				echo "ERREUR: Efface les entrées de type $idtype non effectué: ".var_export($error, true)."<br>\n";
				return false;
			}
		}

		// efface le type
		if ($deleteType) {
			if (! $this->deleteEntryType($idtype)) {
				echo "ERREUR: Efface le type $idtype non effectué: ".var_export($error, true)."<br>\n";
				return false;
			}
		}

		echo "Efface les entrées de type $idtype".($deleteType?" et du type lui même":"")."<br>\n";
		return true;
	}

	/*
		EntryType
	*/

	// création ou modification d'un type d'index
	public function updateEntryType($type) {
		$entrytypeLo = Logic::getLogic('entrytypes');
		$entrytypeLo->editAction($type, $error);
		$maj = $type['id']==0 ? "Création" : "Mise à jour";
		if ($error) {
			echo "ERREUR: $maj du type d'entrée {$type['type']} non effectué: ".var_export($error, true)."<br>\n";
			return false;
		}
		echo "$maj du type d'entrée {$type['type']}"."<br>\n";
		return true;
	}

	// effacer un type d'index
	public function deleteEntryType($idtype) {
		$type = array('id' => $idtype);
		$typeLo = Logic::getLogic('entrytypes');
		$typeLo->deleteAction($type, $error);
		if ($error) {
			echo "ERREUR: Efface le type d'entrée $idtype non effectué: ".var_export($error, true)."<br>\n";
			return false;
		}
		return true;
	}

	/*
		PersonType
	*/

	// création ou modification d'un type de person
	public function updatePersonType($type) {
		$entrytypeLo = Logic::getLogic('persontypes');
		$entrytypeLo->editAction($type, $error);
		$maj = $type['id']==0 ? "Création" : "Mise à jour";
		if ($error) {
			echo "ERREUR: $maj du type de person {$type['type']} non effectué: ".var_export($error, true)."<br>\n";
			return false;
		}
		echo "$maj du type de person {$type['type']}"."<br>\n";
		return true;
	}

	// effacer un type de person
	public function deletePersonType($idtype) {
		$type = array('id' => $idtype);
		$typeLo = Logic::getLogic('persontypes');
		$typeLo->deleteAction($type, $error);
		if ($error) {
			echo "ERREUR: Efface le type de person $idtype non effectué: ".var_export($error, true)."<br>\n";
			return false;
		}
		return true;
	}

	/*
		Type
	*/

	// création ou modification d'un type
	public function updateType($type) {
		$entrytypeLo = Logic::getLogic('types');
		$entrytypeLo->editAction($type, $error);
		$maj = $type['id']==0 ? "Création" : "Mise à jour";
		if ($error) {
			echo "ERREUR: $maj du type {$type['type']} (class {$type['class']}) non effectué: ".var_export($error, true)."<br>\n";
			return false;
		}
		echo "$maj du type {$type['type']} (class {$type['class']})"."<br>\n";
		return true;
	}

	// effacer un type
	public function deleteType($idtype) {
		$type = array('id' => $idtype);
		$typeLo = Logic::getLogic('types');
		$typeLo->deleteAction($type, $error);
		if ($error) {
			echo "ERREUR: Efface le type $idtype non effectué: ".var_export($error, true)."<br>\n";
			return false;
		}
		return true;
	}

	// recevoir le type d'index pointé par le tablefield du ME
	public function getTypeFromField($indexField) {
		$typetable = $this->typetable($indexField['type']);
		if (!$typetable)
			return false;
		$index = DAO::getDAO($typetable)->find("type='{$indexField['name']}'"); // TODO il faut donner la classe aussi !
		if ($index)
			return get_object_vars($index);
		return false;
	}

	// recevoir le type d'index pointé par le tablefield du ME
	public function get_type($class, $type) {
		global $db;
		return $this->getObject('types', "type=".$db->Quote($type)." AND class=".$db->Quote($class));
	}

	/*
		TableFieldGroup
	*/

	// modifie ou crée un groupe de champ
	public function updateTableFieldGroup($field) {
		$tablefieldsLo = Logic::getLogic("tablefieldgroups");
		$tablefieldsLo->editAction($field, $error);
		$maj = $field['id']==0 ? "Création" : "Mise à jour";
		if ($error) {
			echo "ERREUR: $maj du TableFieldGroup sur {$field['name']} non effectué: ".var_export($error, true)."<br>\n";
			return false;
		}

		echo "$maj du TableFieldGroup sur {$field['name']}"."<br>\n";
		return true;
	}

	// effacer un groupe de champ
	public function deleteTableFieldGroup($field) {
		$tablefieldsLo = Logic::getLogic("tablefieldgroups");
		$tablefieldsLo->deleteAction($field, $error);
		if ($error) {
			echo "ERREUR: Effacement du TableFieldGroup {$field['name']} non effectué: ".var_export($error, true)."<br>\n";
			return false;
		}

		echo "Effacement du TableFieldGroup {$field['name']}"."<br>\n";
		return true;
	}

	// recevoir un tablefieldgroup par sa class et son nom
	public function getTableFieldGroup($class, $name) {
		global $db;
		$field = DAO::getDAO("tablefieldgroups")->find("class=".$db->Quote($class)." AND name=".$db->Quote($name));
		if ($field)
			return get_object_vars($field);
		return false;
	}

	/*
		TableField
	*/

	// modifie ou crée un champ
	public function updateTableField($field) {
		$logic = (($field['type'] == 'entries' || $field['type'] == 'persons') ? "index" : "");
		$tablefieldsLo = Logic::getLogic($logic . "tablefields");
		$tablefieldsLo->editAction($field, $error);
		$maj = $field['id']==0 ? "Création" : "Mise à jour";
		if ($error) {
			echo "ERREUR: $maj du TableField sur {$field['name']} non effectué: ".var_export($error, true)."<br>\n";
			return false;
		}

		echo "$maj du TableField sur {$field['name']}"."<br>\n";
		return true;
	}

	// effacer le champ d'un ME
	public function deleteTableField($field) {
		$logic = (($field['type'] == 'entries' || $field['type'] == 'persons') ? "index" : "");
		$tablefieldsLo = Logic::getLogic($logic . "tablefields");
		$tablefieldsLo->deleteAction($field, $error);
		if ($error) {
			echo "ERREUR: Effacement du tableField {$field['name']} non effectué: ".var_export($error, true)."<br>\n";
			return false;
		}

		echo "Effacement du tableField {$field['name']}"."<br>\n";
		return true;
	}

	// recevoir un tablefield par sa class et son nom
	public function getTableField($class, $name) {
		global $db;
		$field = DAO::getDAO("tablefields")->find("class=".$db->Quote($class)." AND name=".$db->Quote($name));
		if ($field)
			return get_object_vars($field);
		return false;
	}

	/*
		OptionGroup
	*/

	// modifie ou crée un champ
	public function updateOptionGroup($field) {
		$optiongroupsLo = Logic::getLogic("optiongroups");
		$optiongroupsLo->editAction($field, $error);
		$maj = $field['id']==0 ? "Création" : "Mise à jour";
		if ($error) {
			echo "ERREUR: $maj de l'OptionGroup «{$field['name']}» non effectué: ".var_export($error, true)."<br>\n";
			return false;
		}

		echo "$maj de l'OptionGroup «{$field['name']}»"."<br>\n";
		return true;
	}

	// effacer le champ d'un ME
	public function deleteOptionGroup($field) {
		$optiongroupsLo = Logic::getLogic("optiongroups");
		$optiongroupsLo->deleteAction($field, $error);
		if ($error) {
			echo "ERREUR: Effacement de l'OptionGroup {$field['name']} non effectué: ".var_export($error, true)."<br>\n";
			return false;
		}

		echo "Effacement de l'OptionGroup {$field['name']}"."<br>\n";
		return true;
	}

	// recevoir un tablefield par sa class et son nom
	public function getOptionGroup($name) {
		global $db;
		$cond = "name=".$db->Quote($name);
		return $this->getObject("optiongroups", $cond);
	}

	/*
		Option
	*/

	// modifie ou crée une option
	public function updateOption($field) {
		$optionsLo = Logic::getLogic("options");
		$optionsLo->editAction($field, $error);
		$maj = $field['id']==0 ? "Création" : "Mise à jour";
		if ($error) {
			echo "ERREUR: $maj de l'Option «{$field['name']}» non effectué: ".var_export($error, true)."<br>\n";
			return false;
		}

		echo "$maj de l'Option «{$field['name']}»"."<br>\n";
		return true;
	}

	// effacer une option
	public function deleteOption($field) {
		$optionsLo = Logic::getLogic("options");
		$optionsLo->deleteAction($field, $error);
		if ($error) {
			echo "ERREUR: Effacement de l'Option {$field['name']} non effectué: ".var_export($error, true)."<br>\n";
			return false;
		}

		echo "Effacement de l'Option {$field['name']}"."<br>\n";
		return true;
	}

	// recevoir une option son nom et son group
	public function get_option($name, $idgroup) {
		global $db;
		$cond = "name=" . $db->Quote($name) . " AND idgroup=" . intval($idgroup);
		return $this->getObject("options", $cond);
	}

	/*
		Class
	*/

	// Créer ou modifier une classe
	public function updateClass($classArray) {
		$lo = Logic::getLogic("classes");
		$lo->editAction($classArray, $error);
		$maj = $classArray['id']==0 ? "Création" : "Mise à jour";
		if ($error) {
			echo "ERREUR: $maj de la Class sur {$classArray['class']} non effectué: ".var_export($error, true)."<br>\n";
			return false;
		}

		echo "$maj de la Class sur {$classArray['class']}"."<br>\n";
		return true;
	}

	// effacer une classe
	public function deleteClass($classArray) {
		$lo = Logic::getLogic("classes");
		$lo->deleteAction($classArray, $error);
		if ($error) {
			echo "ERREUR: Effacement de la classe {$classArray['class']} non effectué: ".var_export($error, true)."<br>\n";
			return false;
		}

		echo "Effacement de la classe {$classArray['class']}"."<br>\n";
		return true;
	}

	// recevoir une classe
	public function getClass($class) {
		$object = DAO::getDAO('classes')->find("class='$class'");
		if ($object)
			return get_object_vars($object);
		return false;
	}

	/*
		Relations entitytype_entitytype
	*/

	public function getEntityTypeRelations($class, $type_name) {
		global $db;
		$type = get_type($class, $type_name);
		$relations = DAO::getDAO('entitytypes_entitytypes')->findMany("identitytype={$type['id']}");
		$rel = array();
		foreach ($relations as $vo) {
			$rel[] = $vo->identitytype2;
		}
		return  $rel;
	}

	public function updateEntityTypeRelations($class, $type_name, $relations) {
		global $db;
		$type = get_type($class, $type_name);
		$rel = array();
		foreach ($relations as $idtype) {
			$type['entitytype'][$idtype] = "on";
		}
		$ok = $this->updateType($type);
		if (!$ok) {
			echo "ERREUR: Mise à jour de la relation entre le type $type_name et d'autres types non effectué: "."<br>\n";
			return false;
		}
		
		echo "Mise à jour de la relation entre le type $type_name et d'autres types c'est bien passé"."<br>\n";
		return true;
	}

	/*
		Utilitaires
	*/

	// recevoir la définition d'un objet lodel à partir de sa table et une condition
	public function getObject($table, $cond) {
		$object = DAO::getDAO($table)->find($cond);
		if ($object)
			return get_object_vars($object);
		return false;
	}

	// recevoir un objet lodel vide
	public function getEmptyObject($table) {
		$dao = DAO::getDAO($table)->instantiateObject($vo);
		$vo = get_object_vars($vo);
		return $vo;
	}

	// recevoir le nom canonique (g_name) d'une entry
	public function getGname($class, $name = "screen name") {
		$ret = false;
		$dao = DAO::getDAO("tablefields");
		$g_names = $dao->findMany("class='$class' AND status>0 AND g_name!=''", "", "name ,g_name");
		foreach ($g_names as $g_name) {
			if ($g_name->g_name == $name)
				return $g_name->name;
			elseif ($g_name->g_name == 'index key')
				$ret = $g_name->name;
		}
		return $ret;
	}

	// rajoute un hook à une liste de hook
	public function update_editionhooks($hooks, $hook) {
		$hooks = explode(",",$hooks);
		if ($hooks[0] === "") $hooks=array();
		if (!in_array($hook, $hooks)) {
			$hooks[] = $hook;
		}
		$hooks = implode($hooks, ",");
		return $hooks;
	}

	// Return the type table associated with the classtype
	private function typetable($type) {
		switch ($type) {
			case 'entities':
				return 'types';
			case 'entries':
				return 'entrytypes';
			case 'persons' :
				return 'persontypes';
		}
		return false;
	}

	// Return the plural or singular of object name
	private function pluralChange($name) {
		switch ($name) {
			case 'persons':
				return 'person';
			case 'person':
				return 'persons';
			case 'entity':
				return 'entities';
			case 'entities':
				return 'entity';
			case 'entry':
				return 'entries';
			case 'entries':
				return 'entry';
		}
		return false;
	}

	/*
		ME
	*/

	public function getME() {
		global $db;
		$me = array();

		$dao = DAO::getDAO('classes');
		$classes = $dao->findMany("1=1", "classtype");
		foreach ($classes as $class) {
			$class = get_object_vars($class);
			$classesById = array('class'=> $class);
			$classesByName = array('class'=> $class);

			// classesTypes
			$classesById['types'] = array();
			$typetable = $this->typetable($class['classtype']);
			$dao = DAO::getDAO($typetable);
			$types = $dao->findMany("class=".$db->Quote($class['class']));
			foreach ($types as $type){
				$type = get_object_vars($type);
				$classesById['types'][$type['id']] = $type;
				$classesByName['types'][$type['type']] = $type;
				$me[$typetable][$type['type']] = $type;
			}

			if ($class['classtype'] == 'entities') {
				// tablefieldgroups
				$classesById['tablefieldgroups'] = array();
				$dao = DAO::getDAO('tablefieldgroups');
				$tablefieldgroups = $dao->findMany("class=".$db->Quote($class['class']), "rank");
				foreach ($tablefieldgroups as $tablefieldgroup) {
					$tablefieldgroup = get_object_vars($tablefieldgroup);
					$group = array('tablefieldgroup' => $tablefieldgroup);
					// tablefields
					$group['tablefields'] = array();
					$dao = DAO::getDAO('tablefields');
					$tablefields = $dao->findMany("idgroup={$tablefieldgroup['id']}", "rank");
					foreach ($tablefields as $tablefield) {
						$tablefield = get_object_vars($tablefield);
						$group['tablefields'][$tablefield['id']] = $tablefield;
						$classesByName['tablefields'][$tablefield['name']] = $tablefield;
					}
					$classesById['tablefieldgroups'][$tablefield['id']] = $group;
				}
			} else {
				$classesById['tablefields'] = array();
				$dao = DAO::getDAO('tablefields');
				$tablefields = $dao->findMany("class=".$db->Quote($class['class']), "rank");
				foreach ($tablefields as $tablefield) {
					$tablefield = get_object_vars($tablefield);
					$classesById['tablefields'][$tablefield['id']] = $tablefield;
					$classesByName['tablefields'][$tablefield['name']] = $tablefield;
				}

				$classesById['relationfields'] = array();
				$relationfields = $dao->findMany("class=".$db->Quote("entities_".$class['class']), "rank");
				foreach ($relationfields as $relationfield) {
					$relationfield = get_object_vars($relationfield);
					$classesById['relationfields'][$relationfield['id']] = $relationfield;
					$classesByName['relationfields'][$relationfield['name']] = $relationfield;
				}
			}
			$me['ids'][$class['id']] = $classesById;
			$me['names'][$class['class']] = $classesByName;
		}
		return $me;
	}

	public function showMe($me) {
		foreach ($me['ids'] as $id => $classes) {
			$class = $classes['class'];
			echo "<ul>";
			echo "<li>";
			echo "{$class['class']} ({$class['classtype']}) °$id";
			echo "<ul>";

			// classesTypes
			if (!empty($classes['types'])) {
				echo "<li>Types";
				echo $this->makeTable($classes['types']);
				echo "</li>";
			}

			if ($class['classtype'] == 'entities') {
				// tablefieldgroups
				if (!empty($classes['tablefieldgroups'])) {
					echo "<li>tablefields<ul>";
					foreach ($classes['tablefieldgroups'] as $id => $tablefieldgroups) {
						$tablefieldgroup = $tablefieldgroups['tablefieldgroup'];
						echo "<li>{$tablefieldgroup['title']} ({$tablefieldgroup['name']}) °$id";

						// tablefields
						if (!empty($tablefieldgroups['tablefields'])) {
							echo $this->makeTable($tablefieldgroups['tablefields']);
						}

						echo "</li>";
					}
					echo "</ul></li>";
				}
			} else {
				// tablefields
				if (!empty($classes['tablefields'])) {
					echo "<li>tablefields";
					echo $this->makeTable($classes['tablefields']);
					echo "</li>";
				}
				// relationfields
				if (!empty($classes['relationfields'])) {
					echo "<li>relationfields";
					echo $this->makeTable($classes['relationfields']);
					echo "</li>";
				}
			}
			echo "</ul></li>";
			echo "</ul>";
		}
	}

	private function makeTable($arrays) {
		$ret = "<table><tr>";
		foreach(array_keys(current($arrays)) as $v)
			$ret .= "<th>$v</th>";
		$ret .= "</tr>";
		foreach($arrays as $array) {
			$ret .= "<tr>";
			foreach($array as $v)
				$ret .= "<td>$v</td>";
			$ret .= "</tr>";
		}
		$ret .= "</table>";
		return $ret;
	}

}

// EXEMPLES
/*

// $indextofield = true;
// $fieldtoindex = true;
// $updatefield = true;
// $migratefield = true;
// $updateFieldType = true;
// $createExternalType = true

// passer d'un index à un champ
if (isset($indextofield)) {
	$index = getTableField('textes', 'motsclesfr');
	indexToField($index);
}

// passer d'un champ à un index
if (isset($fieldtoindex)) {
	$type = array ('id' => 0, 'type' => 'motsclesfr', 'class' => 'indexes', 'title' => 'Index de mots-clés', 'altertitle' => '', 'lang' => 'fr', 'icon' => '', 'gui_user_complexity' => '32', 'edition' => 'pool', 'flat' => '1', 'g_type' => 'dc.subject', 'newbyimportallowed' => '1', 'style' => 'motscles, .motcles,motscls,motsclesfr', 'tpl' => 'entree', 'tplindex' => 'entrees', 'sort' => 'sortkey', 'rank' => '1', 'status' => '1', 'upd' => '2012-09-03 13:47:55', 'otx' => '/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme=\'keyword\']', 'externalallowed' => '1', );
	updateEntryType($type);
	$me = getME();
	$field = $me['names']['textes']['tablefields']['motsclesfr'];
	$entrytype = $me['names']['indexes']['types']['motsclesfr'];
	fieldToIndex($field, $entrytype);
	echo "type d'entrée: " . var_export($entrytype,true)."<hr>";
}

// changer la valeur d'un champ
if (isset($updatefield)) {
	updateField(getTableField('textes', 'resume'));
	updateField(getTableField('indexes', 'definition'), 'jolie');
	updateField(getTableField('entities_auteurs', 'description'), 'jolie');
}

// changer la valeur d'un champ par un autre
if (isset($migratefield)) {
	migrateField(getTableField('textes', 'titre'), getTableField('textes', 'soustitre'), true);
}

// changer le type d'un champ
if (isset($updateFieldType)) {
	$blabla = getTableField('textes','blabla');
	updateField($blabla, '45.4');
	updateFieldType($blabla, 'number');
}

// Créer une classe d'index externe
if (isset($createExternalType)) {
	$calendule = getClass('calendule');
	if ($calendule)
		deleteClass($calendule);
	$calendule = array('id' => 0, 'class' => 'calendule', 'title' => 'Calendule', 'classtype' => 'entries', 'status' => 1, 'externalentrytypes' => 'calenda.7');
	updateClass($calendule);
}

// echo "champ index: " .var_export(getTableField('textes', 'motsclesen'),true)."<hr>";
// echo "champ texte d'une entité: " .var_export(getTableField('textes', 'resume'),true)."<hr>";
// echo "champ texte d'un index: " .var_export(getTableField('indexes', 'definition'),true)."<hr>";
// echo "champ attribut d'un index: " .var_export(getTableField('entities_auteurs', 'description'),true)."<hr>";

*/

?>