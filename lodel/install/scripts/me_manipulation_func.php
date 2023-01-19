<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 *  API de manipulation du ME

Créer rapidement un script en cli:
<?php
	require_once('lodel/install/scripts/me_manipulation_func.php');
// 	define('DO_NOT_DIE', true); // Ne mourir qu'en cas d'erreur grave
// 	define('QUIET', true); // Pas de sortie du tout
	$sites = new ME_sites_iterator($argv, 'errors', 0); // 'errors' ne montre que les erreurs de la fonction ->m(), 0 est le statut minimal du site
	while ($siteName = $sites->fetch()) {
		// script de manipulation du ME du site
	}

Mettre le script à la racine du lodel et l'utiliser: php script.php nom_site | all (pour impacter tous les sites)

Modifier le ME:

TableField alias TF
	::get($class, $fieldname) 
	::create($class, $fieldname, $groupname, $type, $infos=array())
	->delete()
	->name($name, $title=false) change le nom du champ
	->mask($mask, $type=false) rajoute un masque de saisie, $type possible: 'reasonable' ou 'regexp'
	->hook($hook, $clobber=false) rajoute un hook au champ, true pour effacer les anciens
	->group($groupname) Change le group du champ
	->type($newtype) Change le type du champ
	->addStyle($style) rajout d'un style
	->delStyle($style) enlever un style
	->set($fields, $value=null) Change les propriétés du champ: $fields = array('fieldname'=>'value') OU ('fieldname', 'value')
	->migrate($class, $fieldname, $overwrite = true) Copie les valeurs du champ dans un autre (niveau des entités)
	->value($value='') Modifie la valeur d'un champ dans les entités
	->copy($name, $title, $class=false) Créer un champ identique. $class pour copier dans une autre class (indexes)
	->toEntry($type) transforme un champ texte en un index, l'index doit exister, le champ est effacé 
	->toField($deleteEntry = false, $tablefield = false) transforme un champ index en champ texte, si on donne un $tablefield c'est celui-ci qui est utilisé pour la conversion
	->field($key) Recevoir une propriété du champ

TableFieldGroup alias TFG
	::get($class, $name)
	::create($class, $name, $title="", $infos=array())
	->delete()
	->field($key) Recevoir une propriété du TableFieldGroup

Type alias T
	::get($class, $type)
	::create($class, $type, $title, $infos=array())
	->delete()
	->set($fields, $value=null) Change les propriétés du type: $fields = array('fieldname'=>'value') OU ('fieldname', 'value')
	->name($type, $title=false) change le nom du type
	->relation($class, $type, $clobber=false) rajoute une relation, true pour effacer les autres relations
	->relations($ids, $clobber=false) rajoute des relations selon un tableau d'id
	->copy($type, $title) Créer un type identique
	->field($key) Recevoir une propriété du type

EntryType alias ET
	::get($type)
	::create($class, $type, $title, $infos=array())
	->delete()
	->field($key) Recevoir une propriété de l'EntryType
	->set($fields, $value=null) Change les propriétés du champ: $fields = array('fieldname'=>'value') OU ('fieldname', 'value')
	->migrate($class) migrer le type et les données vers une autre classe (la classe doit exister et comporter les mêmes champs)
	->addStyle($style) rajout d'un style
	->delStyle($style) enlever un style

PersonType alias PT
	::get($type)
	::create($class, $type, $title, $infos=array())
	->delete()
	->field($key) Recevoir une propriété du PersonType
	->set($fields, $value=null) Change les propriétés du champ: $fields = array('fieldname'=>'value') OU ('fieldname', 'value')
	->addStyle($style) rajout d'un style
	->delStyle($style) enlever un style

Classe alias Cl
	::get($class)
	::create($class, $classtype, $title, $infos=array()) $classtype = entities | entries | persons (ou entities_CLASSE)
	->delete()
	->external($ext, $clobber=false) Rajoute un lien à un index externe
	->field($key)

Option alias O
	::get($group, $name)
	::create($group, $name, $type, $title="", $infos=array())
	->delete()
	->field($key)

OptionGroup alias OG
	::get($name)
	::create($name, $title="", $infos=array())
	->delete()
	->field($key)

InternalStyle alias IS
	::get($name) nom du style du début !
	::create($style, $infos=array())
	->set($fields, $value=null) Change les propriétés du type: $fields = array('fieldname'=>'value') OU ('fieldname', 'value')
	->addStyle($style) rajout d'un style
	->delStyle($style) enlever un style
	->delete()
	->field($key)
*/

/* Pas en CLI on doit être adminlodel */
if (php_sapi_name() != "cli") {
	require_once 'lodelconfig.php';
	define("SITEROOT","");
	define('backoffice-lodeladmin', true);
	ini_set('include_path', SITEROOT. $cfg['home'] . PATH_SEPARATOR . ini_get('include_path'));
	require 'context.php';
	C::setCfg($cfg);
	$GLOBALS['nodesk'] = true;
	C::set('nocache', true);
	require_once 'auth.php';
	authenticate(LEVEL_ADMINLODEL);
} else {
	// en CLI on s'authentifie comme adminlodel
	define('backoffice-lodeladmin', true);
	require_once 'lodelconfig.php';

	define("SITEROOT","");
	$cfg['home'] = "lodel/scripts/";
	$cfg['sharedir'] = SITEROOT . $cfg['sharedir'];
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

// quick and dirty error handling, au cas où lodel plante
function ME_errors($errno, $errstr='', $errfile='', $errline=0) {
	if ($errno<E_STRICT) {
		if (!defined('QUIET'))
			echo "GRAVE ERREUR: $errno, $errstr, $errfile, $errline"."\n";
		if (!defined('DO_NOT_DIE'))
			die();
	} else {
		if (!defined('QUIET'))
			echo "ATTENTION ERREUR: $errno, $errstr, $errfile, $errline"."\n";
	}
}
error_reporting(-1);
set_error_handler('ME_errors'); // errors
set_exception_handler('ME_errors'); // exceptions not catched

class TableField extends MEobject {
	protected $fields = array();

	static function get($class, $fieldname) {
		return new TableField($class, $fieldname);
	}

	static function create($class, $fieldname, $groupname, $type, $infos=array()) {
		$ME = new MEobject();
		$messages = array();
		$errors = array();
		$classe = $ME->Class_get($class);
		if (!$classe) {
			$errors[] = "Création du champ. La classe '$class' n'existe pas.";
		} else {
			$idgroup = 0;
			if ($classe['classtype'] == 'entities') { // le groupe ne vaut que pour les entitées
				$group = $ME->TableFieldGroup_get($class, $groupname);
				if (!$group) {
					$errors[] = "Création du champ. Le groupe '$groupname' n'existe pas.";
					$idgroup = false;
				} else $idgroup = $group['id'];
			}
			if ($idgroup >= 0) {
				$fields = $ME->TableField_get($class, $fieldname);
				if (!$fields) {
					$fields = array ('id'=>'0', 'name'=>$fieldname, 'class'=>$class, 'title'=>$fieldname, 'altertitle'=>'', 'gui_user_complexity'=>'64', 'idgroup'=>$idgroup, 'type'=>$type, 'g_name'=>'', 'style'=>'', 'mask'=>'', 'cond'=>'*', 'defaultvalue'=>'', 'processing'=>'', 'allowedtags'=>'', 'edition'=>'editable', 'editionparams'=>'', 'editionhooks'=>'', 'weight'=>'0', 'filtering'=>'', 'comment'=>'', 'status'=>'1', 'rank'=>'', 'otx'=>'', );
					$fields = array_merge($fields, $infos);
					$ok = $ME->TableField_Save($fields);
					if ($ok === true)
						$messages[] = "Création du champ.";
					else {
						$errors[] = "Création du champ: problème avec la base de donnée: ".var_export($ok, true);
					}
				} else {
					$messages[] =  "Création du champ: Il existe déjà.";
				}
			}
		}

		$obj = new TableField($class, $fieldname);
		$obj->messages = $messages;
		$obj->errors = $errors;
		$obj->error = !empty($errors);
		return $obj;
	}

	function __construct($class, $fieldname) {
		$this->fields = $this->TableField_get($class, $fieldname);
		if (!is_array($this->fields)) {
			$this->fields = array('class'=>$class, 'name'=>$fieldname);
			$this->error = true;
			$this->err("N'existe pas.");
		}
		return $this;
	}

	function __toString() {
		return "TableField '".$this->fields['name']."', classe '".$this->fields['class']."'";
	}

	protected function save($message) {
		if ($this->error) return $this;
		$ok = $this->TableField_save($this->fields);
		if ($ok === true)
			$this->messages[] = "$message.";
		else 
			$this->err("$message non effectué: ".$ok);
		return $this;
	}

	// efface le champ
	public function delete() {
		if ($this->error) return $this;
		$ok = $this->TableField_delete($this->fields);
		if ($ok === true)
			$this->messages[] = "Effacement.";
		else
			$this->errors[] = $ok;

		$this->error = true;
		return $this;
	}

	// rajoute un mask
	public function mask($mask, $type=false) {
		if ($this->error) return $this;
		if (empty($mask) && isset($this->fields['mask']['user'])) {
			unset($this->fields['mask']['user']);
		} else {
			$this->fields['mask']['user'] = $mask;
			if ($type && in_array($type, array('reasonable', 'regexp')))
				$this->fields['mask_'.$type] = true;
			else
				$type = false;
		}
		return $this->save("Changement du masque pour '$mask'".($type?", type:'$type'":""));
	}

	// change le nom du champ
	public function name($name, $title=false) {
		if ($this->error) return $this;
		$oldname = $this->fields['name'];
		$this->fields['name'] = $name;
		if ($title)
			$this->fields['title'] = $title;
		return $this->save("Changement de nom: de '$oldname' vers '$name'");
	}

	// rajoute un hook au champ
	public function hook($hook, $clobber=false) {
		if ($this->error) return $this;
		if ($clobber) {
			$this->fields['editionhooks'] = $hook;
			$message = "Remplacement des hooks par le";
		} else {
			$this->fields['editionhooks'] = $this->list_merge($this->fields['editionhooks'], $hook);
			$message = "Ajout du";
		}
		return $this->save("$message hook '$hook'");
	}

	// change les hooks du champ
	public function hook_create($hook) {
		$this->hook($hook, true);
		return $this;
	}

	// Change le group du champ
	public function group($groupname) {
		if ($this->error) return $this;
		$group = $this->TableFieldGroup_get($this->fields['class'], $groupname);;
		if (!$group) {
			$this->errors[] = "Changement de groupe. Le groupe '$groupname' n'existe pas.";
		} else {
			$this->fields['idgroup'] = $group['id'];
			$this->save("Changement de groupe '$groupname'");
		}
		return $this;
	}

	// Change le type du champ
	public function type($newtype) {
		if ($this->error) return $this;
		isset($GLOBALS['lodelfieldtypes']) || include "fieldfunc.php";
		global $lodelfieldtypes;
		if (!isset($lodelfieldtypes[$newtype]))
			return $this->err("Type '$newtype'. Le type est invalide");

		$this->fields['type'] = $newtype;
		return $this->save("Type: '$newtype'");
	}

	// change les propriétés du champ
	public function set($fields, $value=null) {
		if ($this->error) return $this;
		$autorised_field = array ('title','altertitle','gui_user_complexity'=>'64','g_name','style','cond','defaultvalue','processing', 'allowedtags','edition','editionparams','weight','filtering','comment','status','rank','otx',);
		if ($value !== null)
			$fields = array($fields=>$value);
		$done = array();
		foreach ($autorised_field as $f) {
			if (isset($fields[$f])) {
				$this->fields[$f] = $fields[$f];
				$done[] = "'$f' => '".$fields[$f]."'";
			}
		}
		return $this->save("Changements de propriétés: ".implode(", ", $done));
	}

	// Copie les valeurs du champ dans un autre
	public function migrate($class, $fieldname, $overwrite = true) {
		if ($this->error) return $this;
		global $db;
		$migrate = TableField::get($class, $fieldname);
		if ($migrate->error)
			return $this->err("Migration: $migrate n'existe pas.");

		if ($migrate->fields['type'] != $this->fields['type'] || $migrate->fields['class'] != $this->fields['class'])
			return $this->err("Migration vers $migrate: types ou classes ne correspondent pas.");

		$classDAO = DAO::getDAO('classes')->find("class=".$db->Quote($this->fields['class']),"classtype");
		$idname = "id".$this->pluralChange($classDAO->classtype);
		$fields = $db->Execute(lq("SELECT ".$this->fields['name']." as old, ".$migrate->fields['name']." as new, $idname as id FROM #_TP_".$this->fields['class'].""));
		foreach ($fields as $field) {
			$sql = lq("UPDATE #_TP_".$this->fields['class']." SET ".$migrate->fields['name']."=".$db->Quote($field['old'])." WHERE $idname={$field['id']}");
			if ($overwrite || !$field['new'])
				$db->Execute($sql);
		}
		$this->messages[] = "Migration vers $migrate";
		return $this;
	}

	// Modifie la valeur d'un champ dans les entités
	public function value($value='') {
		if ($this->error) return $this;
		global $db;
		$sql = lq("UPDATE #_TP_".$this->fields['class']." SET ".$this->fields['name']."=" . $db->Quote($value));
		$db->Execute($sql);
		$this->messages[] = "Valeurs passées à '$value'.";
		return $this;
	}

	// Créer un champ identique
	public function copy($name, $title, $class=false) {
		if ($this->error) return $this;
		$fields = $this->fields;
		if (!$class) {
			$ME = new MEobject();
			$new_classe = $ME->Class_get($class);
			if (!$new_classe)
				return $this->err("Copie non effectuée. La classe '$class' n'existe pas.");
			if ($new_classe->fields['classtype'] == 'entities') // Pour entities il faudrait un tablefieldgroup
				return $this->err("Copie non effectuée. La classe '$class' est de type 'entity'. Ce n'est pas supporté.");
			$class = $this->fields['class'];
		}
		$changes = array('id'=>0, 'class'=>$class, 'name'=>$name, 'title'=>$title);
		$fields = array_merge($fields, $changes);

		$ok = $this->TableField_save($fields);
		if ($ok === true)
			$this->messages[] = "Copie vers '$name' de la classe '$class'.";
		else 
			return $this->err("Copie non effectuée: ".$ok);

		return $this;
	}

	// transforme un champ texte en un index
	// l'index doit exister
	// le champ est effacé !
	public function toEntry($type) {
		if ($this->error) return $this;
		global $db;
		$entrytype = $this->EntryType_get($type);
		if (!is_array($entrytype))
			return $this->err("toEntry: ".$this->fields);

		$g_name = $this->gname_get($entrytype['class'], "index key");

		// cherche les entitées qui ont ce champ
		$entryLo = Logic::getLogic("entries");
		$entitiesWithField = lq("select identity as id, ".$this->fields['name']." as field FROM #_TP_".$this->fields['class']." WHERE ".$this->fields['name']." != ''");
		$entities = $db->Execute($entitiesWithField);
		foreach ($entities as $entity) {
			// créer une entré d'index pour chaque mot du champ (mot séparé par des ,)
			foreach(explode(',', $entity['field']) as $entry) {
				$entry = array('g_name'=>$entry, 'idtype'=>$entrytype['id'], 'identity'=>$entity['id'], 'lo'=>'entities_edition', 'degree'=>0);
				$entryLo->editAction($entry, $error);
				if ($error) {
					return $this->err("toEntry non effectué: ".var_export($error, true).".");
				}
			}
		}

		$fields = $this->fields;
		// efface le champ texte
		$this->delete();

		// création du champ index
		$fieldgroup = $this->TableFieldGroup_get($fields['idgroup']);
		$tf = TableField::create($fields['class'], $type, $fieldgroup['name'], 'entries', array('style'=>'', 'cond'=>''));
		if ($tf->error)
			return $this->err("Migration en index. Champ '$tf' non créé: ".var_export($tf->errors, true).".");

		$this->messages[] = "Migration en index '$type'.";
		return $this;
	}

	// transforme un champ index en champ texte
	// si on donne un $tablefield c'est celui-ci qui est utilisé pour la conversion
	public function toField($deleteEntry = false, $tablefield = false) {
		global $db;
		if ($this->fields['type'] != 'entries')
			return $this->err("toField: Le champ n'est pas un index");

		$entrytype = $this->EntryType_get($this->fields['name']);
		$g_name = $this->gname_get($entrytype['class'], "index key");
		$gnameField = $this->TableField_get($entrytype['class'], $g_name); // le champ gname de l'index
		if (!$tablefield) {
			// création du champ avec un nom temporaire
			$fieldgroup = $this->TableFieldGroup_get($this->fields['idgroup']);
			$tablefield = TableField::create($this->fields['class'], $this->fields['name']."copy", $fieldgroup['name'], $gnameField['type'], array('style'=>'', 'cond'=>$gnameField['cond']));
			if ($tablefield->error)
				return $this->err("toField: Champ '$tablefield' non créé: ".var_export($tablefield->errors, true).".");
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
				if (!$deleteEntry) { // si on efface pas l'index, on efface quand même les relations
					$deleteRelation = lq("DELETE FROM #_TP_relations WHERE idrelation={$entryRow['idrelation']}");
					$deleteRelationAttribut = lq("DELETE FROM #_TP_entities_{$entrytype['class']} WHERE idrelation={$entryRow['idrelation']}");
					$db->Execute($deleteRelation);
					$db->Execute($deleteRelationAttribut);
				}
			}
			$entryName = implode( ',', $entryName);
			$addField = lq("UPDATE #_TP_".$this->fields['class']." SET ".$tablefield->fields['name']." = ".$db->Quote($entryName)." WHERE identity={$entityRow['id']}");
			$db->Execute($addField);
		}

		// effacer les entrées de l'index et son type
		if ($deleteEntry) {
			$this->Entries_delete($entrytype['id']);
			$this->EntryType_delete($entrytype);
		} else
			$this->delete();

		// nommer définitivement le nouveau champ
		$tablefield->name($this->fields['name']);

		$this->messages[] = "toField: l'index est un champ";
		return $this;

	}

}
class TF extends TableField { // alias
}

class TableFieldGroup extends MEobject {
	protected $fields = array();

	static function get($class, $name) {
		return new TableFieldGroup($class, $name);
	}

	static function create($class, $name, $title="", $infos=array()) {
		$ME = new MEobject();
		$messages = array();
		$errors = array();

		$fields = $ME->TableFieldGroup_get($class, $name);
		if (!$fields) {
			$fields = array ( 'id' => '0', 'name'=> $name, 'class' => $class, 'title' => $title, 'altertitle' => '', 'comment' => '', 'rank' => '1', 'status' => '1' );
			$fields = array_merge($fields, $infos);
			$ok = $ME->TableFieldGroup_save($fields);
			if ($ok === true)
				$messages[] = "Création du TableFieldGroup.";
			else {
				$errors[] = "Création du TableFieldGroup: problème avec la base de donnée: ".var_export($ok, true);
			}
		} else {
			$messages[] =  "Création du TableFieldGroup: Il existe déjà.";
		}

		$obj = new TableFieldGroup($class, $name);
		$obj->messages = $messages;
		$obj->errors = $errors;
		$obj->error = !empty($errors);
		return $obj;
	}

	function __construct($class, $name) {
		$this->fields = $this->TableFieldGroup_get($class, $name);
		if (!is_array($this->fields)) {
			$this->fields = array('class'=>$class, 'name'=>$name);
			$this->err("N'existe pas.");
		}
		return $this;
	}

	function __toString() {
		return "TableFieldGroup, name: '".$this->fields['name']."', class: '".$this->fields['class']."'";
	}

	protected function save($message) {
		if ($this->error) return $this;
		$ok = $this->TableFieldGroup_save($this->fields);
		if ($ok === true)
			$this->messages[] = "$message.";
		else 
			$this->err("$message non effectué: ".$ok);
		return $this;
	}

	// efface le champ
	public function delete() {
		if ($this->error) return $this;
		$ok = $this->TableFieldGroup_delete($this->fields);
		if ($ok === true)
			$this->messages[] = "Effacement.";
		else
			$this->errors[] = $ok;

		$this->error = true;
		return $this;
	}
}
class TFG extends TableFieldGroup { // alias
}

class Type extends MEobject {
	protected $fields = array();

	static function get($class, $type) {
		return new Type($class, $type);
	}

	static function create($class, $type, $title, $infos=array()) {
		$ME = new MEobject();
		$messages = array();
		$errors = array();

		$fields = $ME->Type_get($class, $type);
		if (!$fields) {
			$fields = array ( 'id' => '0', 'type' => $type, 'title' => $title, 'altertitle' => '', 'class' => $class, 'icon' => '', 'gui_user_complexity' => '16', 'tpledition' => 'edition', 'display' => '', 'tplcreation' => 'entities', 'import' => '1', 'creationstatus' => '-1', 'search' => '1', 'oaireferenced' => '0', 'public' => '0', 'tpl' => 'article', 'rank' => '1', 'status' => '1', );
			$fields = array_merge($fields, $infos);
			$ok = $ME->Type_save($fields);
			if ($ok === true)
				$messages[] = "Création du type.";
			else {
				$errors[] = "Création du type: problème avec la base de donnée: ".var_export($ok, true);
			}
		} else {
			$messages[] =  "Création du type: Il existe déjà.";
		}

		$obj = new Type($class, $type);
		$obj->messages = $messages;
		$obj->errors = $errors;
		$obj->error = !empty($errors);
		return $obj;
	}

	function __construct($class, $type) {
		$this->fields = $this->Type_get($class, $type);
		if (!is_array($this->fields)) {
			$this->fields = array('class'=>$class, 'type'=>$type);
			$this->err("N'existe pas.");
		}
		if (!$this->error)
			$this->set_relations();
		return $this;
	}

	function __toString() {
		return "Type: '".$this->fields['type']."', class: '".$this->fields['class']."'";
	}

	protected function save($message) {
		if ($this->error) return $this;
		$ok = $this->Type_save($this->fields);
		if ($ok === true)
			$this->messages[] = "$message.";
		else 
			$this->err("$message non effectué: ".$ok);
		return $this;
	}

	// efface le type
	public function delete() {
		if ($this->error) return $this;
		$ok = $this->Type_delete($this->fields);
		if ($ok === true)
			$this->messages[] = "Effacement.";
		else
			$this->errors[] = $ok;

		$this->error = true;
		return $this;
	}

	// change les propriétés du type
	public function set($fields, $value=null) {
		if ($this->error) return $this;
		$autorised_field = array ('gui_user_complexity', 'tpledition', 'display', 'tplcreation', 'import', 'creationstatus', 'search', 'oaireferenced', 'public', 'tpl', 'rank', 'status',);
		if ($value !== null)
			$fields = array($fields=>$value);
		$done = array();
		foreach ($autorised_field as $f) {
			if (isset($fields[$f])) {
				$this->fields[$f] = $fields[$f];
				$done[] = "'$f' => '".$fields[$f]."'";
			}
		}
		return $this->save("Changements de propriétés: ".implode(", ", $done));
	}

	// change le nom du type
	public function name($type, $title=false) {
		if ($this->error) return $this;
		$oldname = $this->fields['type'];
		$this->fields['type'] = $type;
		if ($title)
			$this->fields['title'] = $title;
		return $this->save("Changement de nom de '$oldname' pour '$type'");
	}

	private function set_relations() {
		if (!isset($this->fields['entitytype']) || !is_array($this->fields['entitytype']))
			$this->fields['entitytype'] = array();

		$relations = DAO::getDAO('entitytypes_entitytypes')->findMany("identitytype=".$this->fields['id']);
		foreach ($relations as $vo) {
			$this->fields['entitytype'][$vo->identitytype2] = "on";
		}
	}

	// rajoute des relations selon un tableau d'id
	public function relations($ids, $clobber=false) {
		if ($this->error) return $this;
		if (!is_array($ids)) // si $ids = "4,25"
			$ids = explode(",", $ids);
		if ($clobber===true)
			$this->fields['entitytype'] = array();
		foreach ($ids as $id) {
			$this->fields['entitytype'][$id] = "on";
		}
		return $this->save("Relations mises à jours");
	}

	// rajoute une relation
	public function relation($class, $type, $clobber=false) {
		if ($this->error) return $this;
		$t = Type::get($class, $type);
		$id = $t->field('id');
		if (!$id)
			return $this->err("Relations non mises à jour: $t n'existe pas");
		return $this->relations($id, $clobber);
	}

	// Créer un type identique
	public function copy($type, $title) {
		if ($this->error) return $this;
		$fields = $this->fields;
		$changes = array('id'=>0, 'type'=>$type, 'title'=>$title, 'altertitle'=> '', 'g_name'=>'', 'rank'=>'');
		$fields = array_merge($fields, $changes);

		$ok = $this->Type_save($fields);
		if ($ok === true)
			$this->messages[] = "Copie vers '$type'.";
		else 
			return $this->err("Copie non effectuée: ".$ok);

		return $this;
	}

}
class T extends Type { // alias
}

class EntryType extends MEobject {
	protected $fields = array();

	static function get($type) {
		return new EntryType($type);
	}

	static function create($class, $type, $title, $infos=array()) {
		$ME = new MEobject();
		$messages = array();
		$errors = array();

		$fields = $ME->EntryType_get($type);
		if (!$fields) {
			$fields = array ( 'id' => '0', 'type' => $type, 'class' => $class, 'title' => $title, 'altertitle' => '', 'lang' => 'fr', 'icon' => '', 'gui_user_complexity' => '32', 'edition' => 'pool', 'flat' => '1', 'g_type' => '', 'newbyimportallowed' => '1', 'style' => $type, 'tpl' => 'entree', 'tplindex' => 'entrees', 'sort' => 'sortkey', 'rank' => '1', 'status' => '1', 'otx' => '', 'externalallowed' => '0', );
			$fields = array_merge($fields, $infos);
			$ok = $ME->EntryType_save($fields);
			if ($ok === true)
				$messages[] = "Création du entrytype.";
			else {
				$errors[] = "Création du entrytype: problème avec la base de donnée: ".var_export($ok, true);
			}
		} else {
			$messages[] =  "Pas de création du entrytype: Il existe déjà.";
		}

		$obj = new EntryType($type);
		$obj->messages = $messages;
		$obj->errors = $errors;
		$obj->error = !empty($errors);
		return $obj;
	}

	function __construct($type) {
		$this->fields = $this->EntryType_get($type);
		if (!is_array($this->fields)) {
			$this->fields = array('type'=>$type);
			$this->err("N'existe pas.");
		}
		return $this;
	}

	function __toString() {
		return "EntryType '".$this->fields['type']."'";
	}

	protected function save($message) {
		if ($this->error) return $this;
		$ok = $this->EntryType_save($this->fields);
		if ($ok === true)
			$this->messages[] = "$message.";
		else 
			$this->err("$message non effectué: ".$ok);
		return $this;
	}

	// efface l'EntryType
	public function delete() {
		if ($this->error) return $this;
		$this->Entries_delete($this->fields['id']); // efface les index de ce type
		$ok = $this->EntryType_delete($this->fields);
		if ($ok === true)
			$this->messages[] = "Effacement.";
		else
			$this->errors[] = $ok;

		$this->error = true;
		return $this;
	}

	// change les propriétés du champ
	public function set($fields, $value=null) {
		if ($this->error) return $this;
		$autorised_field = array ('title', 'altertitle', 'lang', 'icon', 'gui_user_complexity', 'edition', 'flat', 'g_type', 'newbyimportallowed', 'tpl', 'tplindex', 'sort', 'rank', 'status', 'upd','otx');
		if ($value !== null)
			$fields = array($fields=>$value);
		$done = array();
		foreach ($autorised_field as $f) {
			if (isset($fields[$f])) {
				$this->fields[$f] = $fields[$f];
				$done[] = "'$f' => '".$fields[$f]."'";
			}
		}
		return $this->save("Changements de propriétés: ".implode(", ", $done));
	}

	// migrer le type et les données vers une autre classe (la classe doit exister et comporter les mêmes champs)
	public function migrate($class) {
		if ($this->error) return $this;

		// changer la classe
		$oldclass = 'indexes';//$this->fields['class'];
		$idtype = $this->fields['id'];
		$this->fields['class'] = $class;
		$this->save("Changement de classe de '$oldclass' vers '$class'");
		if ($this->error) return $this;

		global $db;
		// migrer les données des tables classeA à classeB
		$oldentries = lq("SELECT oldclass.* FROM #_TP_$oldclass as oldclass, #_TP_entries as e WHERE e.id = oldclass.identry AND e.idtype=$idtype");
		$oldentries = $db->Execute($oldentries);
		while ($row = $oldentries->FetchRow()) {
			$keys = implode( ',', array_keys($row));
			$values = array();
			foreach ($row as $v) {
				$values[] = $db->Quote($v);
			}
			$values = implode(',', $values);
			$migrate_entry = lq("INSERT INTO #_TP_$class ($keys) VALUES ($values)");
			$db->Execute($migrate_entry);
		}

		// migrer les données de entities_classeA à entities_classeB
		$oldrelations = lq("SELECT oldclass.* FROM #_TP_entities_$oldclass as oldclass, #_TP_entries as e, #_TP_relations as r WHERE oldclass.idrelation=r.idrelation AND e.id=r.id2 AND e.idtype=$idtype");
		$oldrelations = $db->Execute($oldrelations);
		while ($row = $oldrelations->FetchRow()) {
			$keys = implode(',', array_keys($row));
			$values = array();
			foreach ($row as $v) {
				$values[] = $db->Quote($v);
			}
			$values = implode(',', $values);
			$migrate_relation = lq("INSERT INTO #_TP_entities_$class ($keys) VALUES ($values)");
			$db->Execute($migrate_relation); // OK 
		}

		// efface les vieilles liaisons
		$delete_oldentries = lq("DELETE FROM oldclass USING #_TP_$oldclass as oldclass, #_TP_entries as e WHERE e.id = oldclass.identry AND e.idtype=$idtype");
		$db->Execute($delete_oldentries);
		$delete_oldrelations = lq("DELETE FROM oldclass USING #_TP_entities_$oldclass as oldclass, #_TP_entries as e, #_TP_relations as r WHERE oldclass.idrelation=r.idrelation AND e.id=r.id2 AND e.idtype=$idtype");
		$db->Execute($delete_oldrelations);
		$this->messages[] = "Migration effectuée.";
		return $this;
	}

}
class ET extends EntryType { // alias
}

class PersonType extends MEobject {
	protected $fields = array();

	static function get($type) {
		return new PersonType($type);
	}

	static function create($class, $type, $title, $infos=array()) {
		$ME = new MEobject();
		$messages = array();
		$errors = array();

		$fields = $ME->PersonType_get($type);
		if (!$fields) {
			$fields = array ('id' => '0', 'type' => $type, 'class' => $class, 'title' => $title, 'altertitle' => '', 'icon' => 'lodel/icons/auteur.gif', 'gui_user_complexity' => '32', 'g_type' => '', 'style' => $type, 'tpl' => 'personne', 'tplindex' => 'personnes', 'rank' => '1', 'status' => '1', 'otx' => '',);
			$fields = array_merge($fields, $infos);
			$ok = $ME->PersonType_Save($fields);
			if ($ok === true)
				$messages[] = "Création du persontype.";
			else {
				$errors[] = "Création du persontype: problème avec la base de donnée: ".var_export($ok, true);
			}
		} else {
			$messages[] =  "Pas de création du persontype: Il existe déjà.";
		}

		$obj = new PersonType($type);
		$obj->messages = $messages;
		$obj->errors = $errors;
		$obj->error = !empty($errors);
		return $obj;
	}

	function __construct($type) {
		$this->fields = $this->PersonType_get($type);
		if (!is_array($this->fields)) {
			$this->fields = array('type'=>$type);
			$this->err("N'existe pas.");
		}
		return $this;
	}

	function __toString() {
		return "PersonType '".$this->fields['type']."'";
	}

	// change les propriétés du champ
	public function set($fields, $value=null) {
		if ($this->error) return $this;
		$autorised_field = array ('title', 'altertitle', 'icon', 'gui_user_complexity', 'g_type', 'tpl', 'tplindex', 'rank', 'status', 'upd', 'otx');
		if ($value !== null)
			$fields = array($fields=>$value);
		$done = array();
		foreach ($autorised_field as $f) {
			if (isset($fields[$f])) {
				$this->fields[$f] = $fields[$f];
				$done[] = "'$f' => '".$fields[$f]."'";
			}
		}
		return $this->save("Changements de propriétés: ".implode(", ", $done));
	}

	protected function save($message) {
		if ($this->error) return $this;
		$ok = $this->PersonType_save($this->fields);
		if ($ok === true)
			$this->messages[] = "$message.";
		else 
			$this->err("$message non effectué: ".$ok);
		return $this;
	}

	// efface le PersonType
	public function delete() {
		if ($this->error) return $this;
		$ok = $this->PersonType_delete($this->fields);
		if ($ok === true)
			$this->messages[] = "Effacement.";
		else
			$this->errors[] = $ok;

		$this->error = true;
		return $this;
	}
}
class PT extends PersonType { // alias
}

class Classe extends MEobject {
	protected $fields = array();

	static function get($class) {
		return new Classe($class);
	}

	static function create($class, $classtype, $title, $infos=array()) {
		$ME = new MEobject();
		$messages = array();
		$errors = array();

		$fields = $ME->Class_get($class);
		if (!$fields) {
			$fields = array ('id' => '0', 'icon' => '', 'class' => $class, 'title' => $title, 'altertitle' => '', 'classtype' => $classtype, 'comment' => '', 'rank' => '1', 'status' => '1',);
			$fields = array_merge($fields, $infos);
			$ok = $ME->Class_save($fields);
			if ($ok === true)
				$messages[] = "Création de la class.";
			else {
				$errors[] = "Création de la class: problème avec la base de donnée: ".var_export($ok, true);
			}
		} else {
			$messages[] =  "Création de la class: Il existe déjà.";
		}

		$obj = new Classe($class);
		$obj->messages = $messages;
		$obj->errors = $errors;
		$obj->error = !empty($errors);
		return $obj;
	}

	function __construct($class) {
		$this->fields = $this->Class_get($class);
		if (!is_array($this->fields)) {
			$this->fields = array('class'=>$class, 'classtype'=>'inconnu');
			$this->err("N'existe pas.");
		}
		if ($this->fields['classtype'] == 'entries') {
			$this->set_external();
		}
		return $this;
	}

	function __toString() {
		return "Class '".$this->fields['class']."', type: '".$this->fields['classtype']."'";
	}

	protected function save($message) {
		if ($this->error) return $this;
		$ok = $this->Class_save($this->fields);
		if ($ok === true)
			$this->messages[] = "$message.";
		else 
			$this->err("$message non effectué: ".$ok);
		return $this;
	}

	// efface la class
	public function delete() {
		if ($this->error) return $this;
		if ($this->fields['classtype'] == 'entries') {
			$types = $this->get_types();
			foreach ($types as $type) {
				ET::get($type)->delete();
			}
		} else {
			return $this->err("Ne sais pas effacer une classe de ce type");
		}
		$ok = $this->Class_delete($this->fields);
		if ($ok === true)
			$this->messages[] = "Effacement.";
		else
			$this->errors[] = $ok;

		$this->error = true;
		return $this;
	}

	// Rajoute un lien à un index externe
	public function external($ext, $clobber=false) {
		if ($this->error) return $this;
		if ($this->fields['classtype'] != 'entries')
			return $this->err("Pas d\'external sur une classe de type '".$this->fields['classtype']."'");

		// le site externe existe-t-il ?
		preg_match('/^([a-z0-9\-]+)\.(\d+)$/', $ext, $result);
		if(!C::get('db_no_intrusion.'.$result[1], 'cfg')) {
			global $db;
			$ok = $db->SelectDB(DATABASE.'_'.$result[1]);
			usecurrentdb();
			if (!$ok)
				return $this->err("External: le site distant n'exite pas");
		}
		if (!isset($this->fields['externalentrytypes']))
			$this->fields['externalentrytypes'] = '';
		$this->fields['externalentrytypes'] = $this->list_merge($this->fields['externalentrytypes'], $ext);
		return $this->save("Tables externes modifiées");
	}

	private function set_external() {
		$externals = DAO::getDAO('relations_ext')->findMany("id1=".$this->fields['id'],'','site, id2');
		$external = array();
		foreach ($externals as $ext) {
			$external[] = $ext->site . "." . $ext->id2;
		}
		if ($external)
			$this->fields['externalentrytypes'] = implode(",", $external);
	}

	private function get_types() {
		global $db;
		$typetable = $this->typetable($this->fields['classtype']);
		$dao = DAO::getDAO($typetable);
		$types = $dao->findMany("class=".$db->Quote($this->fields['class']));
		$t = array();
		foreach ($types as $type) {
			$t[] = $type->type;
		}
		return $t;
	}
}
class Cl extends Classe { // alias
}

class Option extends MEobject {
	protected $fields = array();
	private $group="";

	static function get($group, $name) {
		return new Option($group, $name);
	}

	static function create($group, $name, $type, $title="", $infos=array()) {
		$ME = new MEobject();
		$messages = array();
		$errors = array();

		$og = $ME->OptionGroup_get($group);
		if (!$og) {
			return $ME->err("Création, l'optiongroup '$group' n'existe pas");
		}
		$idgroup = $og['id'];

		$fields = $ME->Option_get($name, $idgroup);
		if (!$fields) {
			$fields = array ( 'id' => '0', 'name' => $name, 'title' => $title, 'altertitle' => '', 'idgroup' => $idgroup, 'type' => $type, 'edition' => '', 'editionparams' => '', 'value' => '', 'userrights' => '40', 'defaultvalue' => '', 'comment' => '', 'rank' => '', 'status' => '1' );
			$fields = array_merge($fields, $infos);
			$ok = $ME->Option_save($fields);
			if ($ok === true)
				$messages[] = "Création de l'Option.";
			else {
				$errors[] = "Création de l'Option: problème avec la base de donnée: ".var_export($ok, true);
			}
		} else {
			$messages[] =  "Pas de création de l'Option: elle existe déjà.";
		}

		$obj = new Option($group, $name);
		$obj->messages = $messages;
		$obj->errors = $errors;
		$obj->error = !empty($errors);
		return $obj;
	}

	function __construct($group, $name) {
		$og = $this->OptionGroup_get($group);
		if (!$og) {
			$this->fields = array('name'=>$name);
			$this->err("L'OptionGroup '$group' n'existe pas");
		} else {
			$idgroup = $og['id'];
			$this->fields = $this->Option_get($name, $idgroup);
		
			if (!is_array($this->fields)) {
				$this->fields = array('name'=>$name);
				$this->err("N'existe pas.");
			}
		}
		$this->group = $group;
		return $this;
	}

	function __toString() {
		return "Option, name: '".$this->fields['name']."', group: '".$this->group."'";
	}

	protected function save($message) {
		if ($this->error) return $this;
		$ok = $this->Option_save($this->fields);
		if ($ok === true)
			$this->messages[] = "$message.";
		else 
			$this->err("$message non effectué: ".$ok);
		return $this;
	}

	// efface le champ
	public function delete() {
		if ($this->error) return $this;
		$ok = $this->Option_delete($this->fields);
		if ($ok === true)
			$this->messages[] = "Effacement.";
		else
			$this->errors[] = $ok;

		$this->error = true;
		return $this;
	}
}
class O extends Option { // alias
}

class OptionGroup extends MEobject {
	protected $fields = array();

	static function get($name) {
		return new OptionGroup($name);
	}

	static function create($name, $title="", $infos=array()) {
		$ME = new MEobject();
		$messages = array();
		$errors = array();

		$fields = $ME->OptionGroup_get($name);
		if (!$fields) {
			$fields = array ( 'id' => '0', 'idparent' => '0', 'name' => $name, 'title' => $title, 'altertitle' => '', 'logic' => '', 'exportpolicy' => '1', 'rank' => '', 'status' => '1', 'comment' => '' );
			$fields = array_merge($fields, $infos);
			$ok = $ME->OptionGroup_save($fields);
			if ($ok === true)
				$messages[] = "Création de l'OptionGroup.";
			else {
				$errors[] = "Création de l'OptionGroup: problème avec la base de donnée: ".var_export($ok, true);
			}
		} else {
			$messages[] =  "Pas de création de l'OptionGroup: il existe déjà.";
		}

		$obj = new OptionGroup($name);
		$obj->messages = $messages;
		$obj->errors = $errors;
		$obj->error = !empty($errors);
		return $obj;
	}

	function __construct($name) {
		$this->fields = $this->OptionGroup_get($name);
		if (!is_array($this->fields)) {
			$this->fields = array('name'=>$name);
			$this->err("N'existe pas.");
		}

		return $this;
	}

	function __toString() {
		return "OptionGroup, '".$this->fields['name']."";
	}

	protected function save($message) {
		if ($this->error) return $this;
		$ok = $this->OptionGroup_save($this->fields);
		if ($ok === true)
			$this->messages[] = "$message.";
		else 
			$this->err("$message non effectué: ".$ok);
		return $this;
	}

	// efface l'OptionGroup
	public function delete() {
		if ($this->error) return $this;
		$ok = $this->OptionGroup_delete($this->fields);
		if ($ok === true)
			$this->messages[] = "Effacement.";
		else
			$this->errors[] = $ok;

		$this->error = true;
		return $this;
	}
}
class OG extends OptionGroup { // alias
}

class InternalStyle extends MEobject {
	protected $fields = array();

	static function get($style) {
		return new InternalStyle($style);
	}

	static function create($style, $infos=array()) {
		$ME = new MEobject();
		$messages = array();
		$errors = array();

		$fields = $ME->InternalStyle_get($style);
		if (!$fields) {
			$fields = array ( 'id' => '0', 'style' => $style, 'surrounding' => '-*', 'conversion' => '', 'greedy' => '1', 'rank' => '', 'otx' => '', 'status' => '1',);
			$fields = array_merge($fields, $infos);
			$ok = $ME->InternalStyle_save($fields);
			if ($ok === true)
				$messages[] = "Création de l'InternalStyle.";
			else {
				$errors[] = "Création de l'InternalStyle: problème avec la base de donnée: ".var_export($ok, true);
			}
		} else {
			$messages[] =  "Pas de création de l'InternalStyle: il existe déjà.";
		}

		$obj = new InternalStyle($style);
		$obj->messages = $messages;
		$obj->errors = $errors;
		$obj->error = !empty($errors);
		return $obj;
	}

	function __construct($style) {
		$this->fields = $this->InternalStyle_get($style);
		if (!is_array($this->fields)) {
			$this->fields = array('style'=>$style);
			$this->err("N'existe pas.");
		}

		return $this;
	}

	function __toString() {
		return "InternalStyle, '".$this->fields['style']."'";
	}

	// change les propriétés du champ
	public function set($fields, $value=null) {
		if ($this->error) return $this;
		$autorised_field = array ('surrounding','conversion','greedy','rank','otx');
		if ($value !== null)
			$fields = array($fields=>$value);
		$done = array();
		foreach ($autorised_field as $f) {
			if (isset($fields[$f])) {
				$this->fields[$f] = $fields[$f];
				$done[] = "'$f' => '".$fields[$f]."'";
			}
		}
		return $this->save("Changements de propriétés: ".implode(", ", $done));
	}

	protected function save($message) {
		if ($this->error) return $this;
		$ok = $this->InternalStyle_save($this->fields);
		if ($ok === true)
			$this->messages[] = "$message.";
		else 
			$this->err("$message non effectué: ".$ok);
		return $this;
	}

	// efface l'InternalStyle
	public function delete() {
		if ($this->error) return $this;
		$ok = $this->InternalStyle_delete($this->fields);
		if ($ok === true)
			$this->messages[] = "Effacement.";
		else
			$this->errors[] = $ok;

		$this->error = true;
		return $this;
	}
}
class IS extends InternalStyle { // alias
}

//
// ME OBJECTS *****************************************************************************************************************************************
//
class MEobject {
	public $messages = array();
	public $errors = array();
	public $error = false;

	function __toString() {
		return "MEObject: ";
	}

	// recevoir la valeur d'un champ
	public function field($key) {
		if (isset($this->fields) && is_array($this->fields) && isset($this->fields[$key]))
			return $this->fields[$key];
		return false;
	}

	public function m() {
		$this->messages();
	}

	public function messages() {
		$onlyerror = (isset($GLOBALS['ME_messages']) && $GLOBALS['ME_messages'] == 'errors') ? true : false;
		$sep = ((php_sapi_name() != "cli") ? "<br/>" : "") . "\n";
		$space = ((php_sapi_name() != "cli") ? "&nbsp;" : "") . " ";
		if ((!$onlyerror && $this->messages) || $this->errors)
			echo "MESSAGES: $this" . $sep;
		if (!$onlyerror && $this->messages || ($this->errors && $this->messages)) {
			echo "${space}OK:" . $sep;
			echo "${space}${space}" . implode($sep."${space}${space}", $this->messages) . $sep;
		}
		if ($this->errors) {
			echo "${space}ERREUR:" . $sep;
			echo "${space}${space}" . implode($sep."${space}${space}", $this->errors) . $sep;
		}
		if ((!$onlyerror && $this->messages) || $this->errors)
			echo $sep;
	}

	protected function err($message, $invalidate=true) {
		$this->errors[] = $message;
		if ($invalidate) $this->error = true;
		return $this;
	}

	// rajoute un style
	public function addStyle($style) {
		if ($this->error) return $this;
		if (!isset($this->fields['style'])) {
			$this->messages[] = "Cet élément ne supporte pas les styles !";
			return $this;
		}
			
		$styles = explode(",", $this->fields['style']);
		if (!in_array($style, $styles)) {
			$styles[] = $style;
			$this->fields['style'] = implode(',', array_filter($styles));
			return $this->save("Ajout du style: $style");
		}
		$this->messages[] = "Ajout du style: $style. Existe déjà !";
		return $this;
	}

	// enleve un style
	public function delStyle($style) {
		if ($this->error) return $this;
		if (!isset($this->fields['style'])) {
			$this->messages[] = "Cet élément ne supporte pas les styles !";
			return $this;
		}
			
		$styles = explode(",", $this->fields['style']);
		if (($key = array_search($style, $styles)) !== false) {
			unset($styles[$key]);
			$this->fields['style'] = implode(',', array_filter($styles));
			return $this->save("Effacement du style: $style");
		}
		$this->messages[] = "Effacement du style: $style. N'existe pas !";
		return $this;
	}

/*
	TableField
*/
	protected function TableField_save($fields) {
		$logic = (($fields['type'] == 'entries' || $fields['type'] == 'persons') ? "index" : "");
		if (!empty($fields['allowedtags']) && is_string($fields['allowedtags'])) // grrrr, pourquoi lodel ne fait pas ça ???
			$fields['allowedtags'] = explode(';', $fields['allowedtags']);
		return $this->logic_save($logic . "tablefields", $fields);
	}
	protected function TableField_delete($fields) {
		$logic = (($fields['type'] == 'entries' || $fields['type'] == 'persons') ? "index" : "");
		return $this->logic_delete($logic . "tablefields", $fields);
	}
	protected function TableField_get($class, $fieldname) {
		global $db;
		$tf = $this->Object_get("tablefields", "class=".$db->Quote($class)." AND name=".$db->Quote($fieldname));
		if ($tf) {
			if (!empty($tf['mask']))  // grrrr, pourquoi lodel ne fait pas ça ???
				$tf['mask'] = @unserialize(html_entity_decode(stripslashes($tf['mask'])));
			else
				$tf['mask'] = array();
		}
		return $tf;
	}

/*
	TableFieldGroup
*/
	public function TableFieldGroup_save($fields) {
		return $this->logic_save("tablefieldgroups", $fields);
	}
	public function TableFieldGroup_delete($fields) {
		return $this->logic_delete("tablefieldgroups", $fields);
	}
	public function TableFieldGroup_get($class, $name=false) {
		global $db;
		if (intval($class)>0 && !$name)
			$cond = "id=".intval($class);
		else
			$cond = "class=".$db->Quote($class)." AND name=".$db->Quote($name);
		return $this->Object_get("tablefieldgroups", $cond);
	}

/*
	Type
*/
	protected function Type_save($fields) {
		return $this->logic_save("types", $fields);
	}
	protected function Type_delete($fields) {
		return $this->logic_delete("types", $fields);
	}
	protected function Type_get($class, $type=false) {
		global $db;
		if (intval($class)>0)
			$cond = "id=".intval($class);
		else
			$cond = "type=".$db->Quote($type)." AND class=".$db->Quote($class);
		return $this->Object_get('types', $cond);
	}

/*
	PersonType
*/
	protected function PersonType_save($fields) {
		return $this->logic_save("persontypes", $fields);
	}
	protected function PersonType_delete($fields) {
		return $this->logic_save("persontypes", $fields);
	}
	protected function PersonType_get($type) {
		global $db;
		if (intval($type)>0)
			$cond = "id=".intval($type);
		else
			$cond = "type=".$db->Quote($type);
		return $this->Object_get("persontypes", $cond);
	}

/*
	EntryType
*/
	protected function EntryType_save($fields) {
		return $this->logic_save("entrytypes", $fields);
	}
	protected function EntryType_delete($fields) {
		return $this->logic_delete("entrytypes", $fields);
	}
	protected function EntryType_get($type) {
		global $db;
		if (intval($type)>0)
			$cond = "id=".intval($type);
		else
			$cond = "type=".$db->Quote($type);
		return $this->Object_get("entrytypes", $cond);
	}

/*
	Class
*/
	protected function Class_save($fields) {
		return $this->logic_save("classes", $fields);
	}
	protected function Class_delete($fields) {
		return $this->logic_delete("classes", $fields);
	}
	protected function Class_get($class) {
		global $db;
		return $this->Object_get("classes", "class=" . $db->Quote($class));
	}

/*
	Option
*/
	protected function Option_save($fields) {
		return $this->logic_save("options", $fields);
	}
	protected function Option_delete($fields) {
		return $this->logic_delete("options", $fields);
	}
	protected function Option_get($name, $idgroup) {
		global $db;
		return $this->Object_get("options", "name=" . $db->Quote($name) . " AND idgroup=" . intval($idgroup));
	}

/*
	OptionGroup
*/
	protected function OptionGroup_save($fields) {
		return $this->logic_save("optiongroups", $fields);
	}
	protected function OptionGroup_delete($fields) {
		return $this->logic_delete("optiongroups", $fields);
	}
	protected function OptionGroup_get($name) {
		global $db;
		return $this->Object_get("optiongroups", "name=".$db->Quote($name));
	}

/*
	InternalStyle
*/
	protected function InternalStyle_save($fields) {
		return $this->logic_save("internalstyles", $fields);
	}
	protected function InternalStyle_delete($fields) {
		return $this->logic_delete("internalstyles", $fields);
	}
	protected function InternalStyle_get($name) {
		global $db;
		return $this->Object_get("internalstyles", "style like ".$db->Quote($name."%"));
	}

/*
	Entries
*/
	// effacer les entrées d'un index
	protected function Entries_delete($idtype) {
		global $db;
		$logic = Logic::getLogic('entries');

		// efface les entrées
		$tous_racine_tous_depublie = lq("UPDATE #_TP_entries SET idparent=0, status=1 WHERE idtype=$idtype");
		$db->Execute($tous_racine_tous_depublie); // hack, on publie et applati la hiérarchie !

		$dao = DAO::getDAO('entries');
		$entries = $dao->findMany("idtype=$idtype","","id");
		foreach ($entries as $entry) {
			$entry = array('id' => $entry->id);
			$logic->deleteAction($entry, $error);
			if ($error) {
				$this->errors[] = "Efface les entrées de type $idtype non effectué: ".var_export($error, true)."<br>\n";
				return false;
			}
		}

		$this->messages[] = "Entrées de type $idtype effacé";
		return true;
	}

/*
	UTILS
*/
	// recevoir un objet quelconque
	private function Object_get($table, $cond) {
		$vo = DAO::getDAO($table)->find($cond);
		if ($vo) {
			$class = "ME_".$table;
			$lo = new $class;
			$fields = $lo->ME_object_get($vo);
			return $fields;
		}
		return false;
	}

	// enregistrer un objet
	private function logic_save($logic, $fields) {
		$Lo = Logic::getLogic($logic);
		$Lo->editAction($fields, $error);
		if ($error)
			return "$logic: Sauvegarde non effectuée: ".var_export($error, true).".";
		return true;
	}

	// effacer un objet
	private function logic_delete($logic, $fields) {
		$Lo = Logic::getLogic($logic);
		$Lo->deleteAction($fields, $error);
		if ($error)
			return "$logic: Effacement non effectué: ".var_export($error, true).".";
		return true;
	}

	// recevoir le nom canonique (g_name) d'une entry
	protected function gname_get($class, $name="screen name") {
		$ret = false;
		$dao = DAO::getDAO("tablefields");
		$g_names = $dao->findMany("class='$class' AND status>0 AND g_name!=''", "", "name ,g_name");
		foreach ($g_names as $g_name) {
			if ($g_name->g_name == $name) {
				return $g_name->name;
			} elseif ($g_name->g_name == 'index key')
				$ret = $g_name->name;
		}
		return $ret;
	}

	// merger deux listes "jlk,sfjkl" "lkjnk,lj"
	protected function list_merge($list_one, $list_two, $sep=",") {
		$list_one = explode($sep, $list_one);
		if ($list_one[0] === "") $list_one = array();
		$list_two  = explode($sep, $list_two);
		if ($list_two[0] === "") $list_two = array();

		$newlist = array_unique(array_merge($list_one, $list_two));
		$newlist = implode($sep, $newlist);
		return $newlist;
	}

	// Return the plural or singular of lodel objects name
	protected function pluralChange($name) {
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

	// Return the type table associated with the classtype
	protected function typetable($type) {
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

}

// Class warper autour des logics de chaque table !
class ME_tablefields extends TableFieldsLogic {
	public function ME_object_get($vo) {
		$this->_populateContext($vo, $fields);
		return $fields;
	}
}
class ME_indextablefield extends IndexTableFieldsLogic {
	public function ME_object_get($vo) {
		$this->_populateContext($vo, $fields);
		return $fields;
	}
}
class ME_tablefieldgroups extends TablefieldgroupsLogic {
	public function ME_object_get($vo) {
		$this->_populateContext($vo, $fields);
		return $fields;
	}
}
class ME_types extends TypesLogic {
	public function ME_object_get($vo) {
		$this->_populateContext($vo, $fields);
		return $fields;
	}
}
class ME_persontypes extends PersonTypesLogic {
	public function ME_object_get($vo) {
		$this->_populateContext($vo, $fields);
		return $fields;
	}
}
class ME_entrytypes extends EntryTypesLogic {
	public function ME_object_get($vo) {
		$this->_populateContext($vo, $fields);
		return $fields;
	}
}
class ME_classes extends ClassesLogic {
	public function ME_object_get($vo) {
		$this->_populateContext($vo, $fields);
		return $fields;
	}
}
class ME_options extends OptionsLogic {
	public function ME_object_get($vo) {
		$this->_populateContext($vo, $fields);
		return $fields;
	}
}
class ME_optiongroups extends OptiongroupsLogic {
	public function ME_object_get($vo) {
		$this->_populateContext($vo, $fields);
		return $fields;
	}
}
class ME_internalstyles extends InternalstylesLogic {
	public function ME_object_get($vo) {
		$this->_populateContext($vo, $fields);
		return $fields;
	}
}

// Itérateur pour tourner sur les sites en CLI
class ME_sites_iterator implements Iterator {
	private $position = -1;
	private $sites = array();
	private $current_db;

	public function __construct($argv, $error_level = '', $status=0) {
// 		if( php_sapi_name() != "cli" ) // Pas besoin car l'authentification est faite plus haut…
// 			die("PHP-cli only !!!");
		$this->position = -1;
		$sites = $argv;
		if(!(isset($sites[1]))) {
			echo "USAGE:\n";
			echo " php ".$argv[0]." site1 [site2] […]\n";
			echo " Utiliser 'all' comme nom de site pour impacter tous les sites de l'installation.\n";
			die();
		}
		array_shift($sites);
		if ($sites[0] == 'all')
			$sites = $this->findAllSites((int) $status);
		$this->sites = $sites;
		$GLOBALS['ME_messages'] = $error_level;
	}

	public function fetch() {
		$this->next();
		if (!$this->valid())
			return false;
		$site = $this->current();
		if(!preg_match("/^[a-z0-9\-]+$/", $site) || !is_file($site."/siteconfig.php")) {
			if (!defined('QUIET'))
				echo "*** Site name incorrect '$site' is not a lodel site ***\n";
			return $this->fetch();
		} else {
			if (!defined('QUIET'))
				echo "*** Travail sur '$site' ***\n";
		}
		$this->current_db = c::Get('database','cfg') . "_" . $site;
		$this->connect();
		return $site;
	}

	function rewind() {
		return;
	}

	function current() {
		return $this->sites[$this->position];
	}

	function key() {
		return $this->position;
	}

	function next() {
		++$this->position;
	}

	function valid() {
		return isset($this->sites[$this->position]);
	}

	public function connect() {
		$this->setdb($this->current_db);
	}
	
	private function findAllSites($status) {
		$base_lodel = c::Get('database','cfg');
		$this->setdb($base_lodel);
		global $db;
		$les_sites = $db->execute(lq("SELECT name FROM #_MTP_sites WHERE status>$status"));
		$sites = array();
		while ($site = $les_sites->FetchRow()) {
			$sites[] = $site['name'];
		}
		return $sites;
	}

	private function setdb($db_name) {
		$GLOBALS['currentdb'] = $db_name;
		usecurrentdb();
	}
}
