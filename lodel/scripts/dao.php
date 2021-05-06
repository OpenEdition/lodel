<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier de la classe DAO
 */

/**
 * Classe gérant la DAO (Database Abstraction Object)
 * 
 * <p>Cette classe définit un ensemble de méthodes permettant d'effectuer les opérations
 * courantes sur la base de données : sélection, insertion, mise à jour, suppression. Au lieu
 * d'effectuer soit même les requêtes SQL et de traiter les résultats SQL sous forme de tableau,
 * les méthodes de cette classe retourne leurs résultat sous forme d'objet : les Virtual Objet
 *  (VO).</p>
 * <p>Exemple d'utilisation (factice)
 * <code>
 * $dao = new DAO('personnes',true); //instantiation
 * $vos = $DAO->find("nom LIKE('robert')", "nom", "nom,prenom,mail");
 * print_r($vos); // affiche toutes les personnes dont le nom contient robert
 * 
 * $dao->deleteObject($vo[0]); //suppression du premier objet
 * </code>
 *
 */
class DAO
{
	/**#@+
	 * @access private
	 */
	/**
	 * Nom et classe de la table SQL
	 * @var string
	 */
	public $table;

	/**
	 * Nom de la table avec et préfixe et éventuellement la jointure pour le SELECT
	 * Table name with the prefix, and potential join for views.
	 * @var string
	 */
	public $sqltable;

	/**
	 * Uniqueid. Vrai si la table utilise une clé primaire (clé unique).
	 * @var integer
	 */
	public $uniqueid;

	/**
	 * Tableau associatif avec les droits requis pour lire, écrire et protéger
	 * Assoc array with the right level required to read, write, protect
	 * @var array
	 */
	public $rights;

	/**
	 * Champ identifiant
	 * @var string
	 */
	public $idfield;

	/**
	 * Tableau de cache stockant les critères SQL correspondants aux droit d'accès sur les objets
	 * @see rightsCriteria()
	 * @access private
	 */
	protected $cache_rightscriteria;

	/**
	 * Internal cache for DAO objects
	 * @var array
	 */
	static protected $_daos = array();

	/**
	 * Internal cache for GenericDAO objects
	 * @var array
	 */
	static protected $_gdaos = array();
	/**#@-*/

	/**
	 * Constructeur de classe
	 *
	 * Positionne les variables privées de la classe.
	 *
	 * @param string $table le nom de la table et de la classe.
	 * @param boolean $uniqueid Par défaut à 'false'. Indique si la table utilise une clé primaire.
	 * @param string $idfield Par défaut à 'id'. Indique le nom du champ identifiant
	 */
	public function __construct($table, $uniqueid = false, $idfield = "id")
	{
		$this->table = $table;
		$this->sqltable = lq("#_TP_"). $table;
		$this->uniqueid = $uniqueid;
		$this->idfield = $idfield;
	}

	/**
	* DAO factory
	*
	* @param string $table the dao name
	*/
	static public function getDAO($table)
	{
		if (isset(self::$_daos[$table])) {
			return self::$_daos[$table]; // cache
		}
		$daoclass = $table. 'DAO';
	
		if(!class_exists($daoclass))
		{
			$file = C::get('sharedir', 'cfg').'/plugins/custom/'.$table.'/dao.php';
			if(!file_exists($file))
				trigger_error('ERROR: unknown dao', E_USER_ERROR);
			
			include $file;
			if(!class_exists($daoclass, false) || !is_subclass_of($daoclass, 'DAO'))
				trigger_error('ERROR: the DAO plugin file MUST extends the DAO OR GenericDAO class', E_USER_ERROR);
		}
		
		self::$_daos[$table] = new $daoclass;
		return self::$_daos[$table];
	}

	/**
	* generic DAO factory
	*
	* @param string $table the dao name
	* @param int $idfield the identifier field
	*/
	static public function getGenericDAO($table, $idfield)
	{
		if (isset(self::$_gdaos[$table])) {
			return self::$_gdaos[$table]; // cache
		}
		self::$_gdaos[$table] = new genericDAO ($table,$idfield);
		return self::$_gdaos[$table];
	}

	/**
	 * Ajout/Modification d'enregistrement
	 * Main function to add/modify records
	 *
	 * @param object &$vo l'objet virtuel à sauvegarder.
	 * @param boolean $forcecreate Par défaut à false. Indique si on doit forcer la création.
	 * @return $idfield l'identifiant de l'enregistrement créé ou modifié.
	 */
	public function save(&$vo, $forcecreate = false) // $set,$context=array())
	{
		global $db;
		$idfield = $this->idfield;
		#print_r($vo);
		// check the user has the basic right for modifying/creating an object
		if (isset($this->rights['write']) && C::get('rights', 'lodeluser') < $this->rights['write']) {
			trigger_error('ERROR: you don\'t have the right to modify objects from the table '. $this->table, E_USER_ERROR);
		}
        
		// check the user has the right to protect the object
		if (((isset ($vo->status) && ($vo->status >= 32 || $vo->status <= -32)) || (isset($vo->protect) && $vo->protect)) && 
					C::get('rights', 'lodeluser') < $this->rights['protect']) {
			trigger_error('ERROR: you don\'t have the right to protect objects from the table '. $this->table, E_USER_ERROR);
		}

		if (isset ($vo->rank) && $vo->rank == 0) { // initialize the rank
			$where = '';
			if('entries' === $this->table)
			{
				$where = ' AND idtype='.$vo->idtype.' AND idparent='.$vo->idparent;
			}
			elseif('entities' === $this->table)
			{
				$where = ' AND idparent='.$vo->idparent;
			}
			$rank = $db->getOne('SELECT MAX(rank) FROM '.$this->sqltable.' WHERE status>-64'.$where);
			if ($db->errorno()) {
				trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
			$vo->rank = $rank +1;
		}
		$this->quote($vo);
		if (isset($vo->$idfield) && $vo->$idfield > 0 && !$forcecreate) { // Update - Mise à jour
			$update = ''; //critère de mise à jour
			if (isset ($vo->protect))	{ // special processing for the protection
				if ($vo->status != 21)
					$update = 'status=(2*(status>0)-1)'. ($vo->protect ? '*32' : ''); //reglage du status sauf si INDÉPUBLIALBLE
				unset ($vo->status);
				unset ($vo->protect);
			}
			foreach ($vo as $k => $v)	{ // ajout de chaque champ à la requete update
				if (!isset ($v) || $k == $idfield) {
					continue;
				}
				if ($update) {
					$update .= ',';
				}
				$update .= "$k='". $v. "'";
			}
			if ($update) {
				$update = 
				$db->execute('UPDATE '. $this->sqltable. " SET  $update WHERE ". $idfield. "='". (int)$vo->$idfield. "' ". $this->rightscriteria('write')) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
		}	else	{ // new  - Ajout
			if (isset ($vo->protect))	{ // special processing for the protection
				if ($vo->status != 21) // 21 == INDÉPUBLIALBLE
					$vo->status = ($vo->status > 0 ? 1 : -1) * ($vo->protect ? 32 : 1);
				unset ($vo->protect);
			}
			$insert = ''; //condition SQL pour INSERT
			$values = ''; // valeur des champs pour la requete SQL INSERT
			if ($this->uniqueid && !$vo->$idfield) {
				$vo->$idfield = uniqueid($this->table);
			}
			foreach ($vo as $k => $v)	{
				if (!isset ($v)) {
					continue;
				}
				if ($insert) {
					$insert .= ',';
					$values .= ',';
				}
				$insert .= $k;
				$values .= "'". $v. "'";
			}
			if ($insert) {
				$db->execute('REPLACE INTO '.$this->sqltable.' ('. $insert. ') VALUES ('. $values. ')') or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				if (!isset($vo->$idfield)) {
					$vo->$idfield = $db->insert_id();
				}
			}
		}
		return $vo->$idfield;
	}

	/**
	 * Ajout de slashes dans champ pour la protection des données dans la requête SQL
	 *
	 * Quote the field in the object
	 *
	 * @param object &$vo Objet virtuel passé par référence
	 */
	public function quote(&$vo)
	{
		foreach ($vo as $k => $v) {
			if (isset ($v) && is_string($v)){
				$vo->$k = addslashes($v);
			}
		}
	}

	/**
	 * Récuperer un objet par son identifiant
	 *
	 * Function to get a value object
	 *
	 * @param integer $id l'identifiant de l'objet
	 * @param string $select les champs à récuperer
	 * @return object un objet virtuel contenant les champs de l'objet
	 * @see fonction find()
	 */
	public function getById($id, $select = "*")
	{
		$id = (int)$id;
		return $this->find($this->idfield. "='$id'", $select);
	}

	/**
	 * Récuperer des objects grâce aux identifiants
	 *
	 * Function to get many value object
	 * @param array $ids le tableau des identifiant
	 * @param string $select les champs à récuperer
	 * @return array un tableau d'objet virtuels
	 * @see fonction find(), getById()
	 */
	public function getByIds($ids, $select = "*")
	{
		return $this->findMany($this->idfield. (is_array($ids) ? " IN ('". join("','", array_map('intval', $ids)). "')" : "='".(int)$ids."'"), '', $select);
	}

	/**
	 * Trouver un objet suivant certains critères et en sélectionnant certains champs
	 *
	 * Function to get a value object
	 *
	 * @param string $criteria les critères SQL de recherche
	 * @param string $select les critères SQL de sélection (par défaut : SELECT *)
	 * @return l'objet virtuel trouvé sinon null
	 */
	public function find($criteria, $select = "*")
	{
		global $db;

		//execute select statement
		$GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_ASSOC;
		$row = $db->getRow("SELECT ".$select." FROM ".$this->sqltable." WHERE ($criteria) ".$this->rightscriteria("read"));
		$GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_DEFAULT;
		if ($row === false) {
			trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}
		if (!$row) {
			return null;
		}

		// create new vo and call getFromResult
		$this->instantiateObject($vo);
		$this->_getFromResult($vo, $row);
		return $vo;
	}

	/**
	 * Trouver un ensemble d'objet correspondant à des critères
	 *
	 * Function to get many value object
	 *
	 * @param string $criteria les critères SQL de recherches
	 * @param string $order le critère SQL de tri des résultats. (par défaut vide)
	 * @param string $select les champs à sélectionner. (par défaut *).
	 * @return array Un tableau de VO correspondant aux résultats de la requête
	 */
	public function findMany($criteria, $order = '', $select = '*', $limit='')
	{
		global $db;

		//execute select statement
		$morecriteria = $this->rightscriteria("read");
		if ($order)
			$order = " ORDER BY ".$order;
		if ($limit)
			$limit = " LIMIT ".$limit;
		$GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_ASSOC;
		$sql = "SELECT ".$select." FROM ".$this->sqltable." WHERE ($criteria) ".$morecriteria.$order.$limit;
		$result = $db->execute($sql) 
			or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg()."<br/>$sql<br/>Base: ".$db->database, E_USER_ERROR);
		$GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_DEFAULT;

		$i = 0;
		$vos = array ();
		while (!$result->EOF) {
			//create new vo and
			$this->instantiateObject($vos[$i]);
			// call getFromResult
			$this->_getFromResult($vos[$i], $result->fields);
			++$i;
			$result->MoveNext();
		}
		$result->Close();
		// return vo's
		return $vos;
	}

	/**
	 * Compter le nombre d'éléments correspondant à tel critère
	 *
	 * Return the number of element matching a criteria
	 *
	 * @param string $criteria Les critères SQL de la requête.
	 */
	public function count($criteria)
	{
		global $db;
		$ret = $db->getOne('SELECT COUNT(*) FROM '.$this->sqltable.' WHERE '.$criteria);
		if ($db->errorno()) {
			trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}
		return $ret;
	}

	/**
	 * Crée un nouvel objet virtuel (VO)
	 * Create a new Value Object
	 *
	 * @return object Le VO instancié
	 */
	public function createObject()
	{
		$vo = null;
		$this->instantiateObject($vo);
		if (property_exists($vo, "status")) {
			$vo->status = 1;
		}
		if (property_exists($vo, "rank")) {
			$vo->rank = 0; // auto
		}
		return $vo;
	}

	/**
	 * Instanciation d'un nouvel objet virtuel (VO)
	 *
	 * Instantiate a new object
	 */
	public function instantiateObject(& $vo)
	{
		$classname = $this->table. 'VO';
		$vo = new $classname; // the same name as the table. We don't use factory...
	}

	/**
	 * Suppression d'un objet - fonction qui ne fait qu'appeller deleteObject
	 * Function to delete an object value.
	 * @param mixed object or numeric id or an array of ids or criteria
	 * @return boolean un booleen indiquant l'état de la suppression de l'objet
	 */
	public function delete($mixed)
	{
		return $this->deleteObject($mixed);
	}
	/**
	 * Suppression d'un objet ou d'un tableau d'objet (tableau d'identifiant)
	 * @param mixed object or numeric id or an array of ids or criteria
	 * @return boolean un booleen indiquant l'état de la suppression de l'objet
	 */
	public function deleteObject(&$mixed)
	{
		global $db;

		if (isset($this->rights['write']) && C::get('rights', 'lodeluser') < $this->rights['write']) {
			trigger_error('ERROR: you don\'t have the right to delete object from the table '. $this->table, E_USER_ERROR);
		}
		
		$idfield = $this->idfield;
		if (is_object($mixed)) {
			$vo = &$mixed;
			$id = (int)$vo->$idfield;
			$criteria = $idfield. "='$id'";
			//set id on vo to 0
			$vo->$idfield = 0;
			$nbid = 1;
		}	elseif (is_numeric($mixed) && $mixed > 0)	{
			$id = (int)$mixed;
			$criteria = $idfield. "='$id'";
			$nbid = 1;
		}	elseif (is_array($mixed))	{
			$id = array_map('intval', $mixed);
			$criteria = $idfield. " IN ('". join("','", $id). "')";
			$nbid = count($id);
		}	elseif (is_string($mixed) && trim($mixed)) {
			$criteria = lq($mixed);
			if ($this->uniqueid) {
				// select before deleting
				$result = $db->execute('SELECT id FROM '.$this->sqltable." WHERE ($criteria) ". $this->rightscriteria('write')) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				// collect the ids
				$id = array ();
				foreach ($result as $row) {
					$id[] = $row['id'];
				}
				$nbid = count($id);
			}	else {
				$nbid = 0; // check we have delete at least one
			}
		}	else {
			trigger_error('ERROR: DAO::deleteObject does not support the type of mixed variable', E_USER_ERROR);
		}

		//execute delete statement
		$db->execute('DELETE FROM '. $this->sqltable. " WHERE ($criteria) ". $this->rightscriteria("write")) 
			or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		if (($db->affected_Rows() < $nbid) && $this->rightscriteria("write")) {
			trigger_error("ERROR: you don't have the right to delete some objects in table ". $this->table, E_USER_ERROR);
		}
		// in theory, this is bad if the $mixed is an array because 
		// some but not all of the object may have been deleted
		// in practice, it is an error in the interface. The database may be corrupted (object in fact).

		//delete the uniqueid entry if required
		if ($this->uniqueid) {
			if ($nbid != count($id)) {
				trigger_error("ERROR: internal error in DAO::deleteObject. Please report the bug", E_USER_ERROR);
			}
			deleteuniqueid($id);
		}
		return true;
	}

	/**
	 * Suppression de plusieurs objets suivant un critère particulier
	 * Function to delete many object value given a criteria
	 *
	 * @param string critères SQL pour la suppression
	 * @return boolean un booleen indiquant l'état de la suppression de l'objet
	 */
	public function deleteObjects($criteria)
	{
		global $db;

		// check the rights
		if (isset($this->rights['write']) && C::get('rights', 'lodeluser') < $this->rights['write']) {
			trigger_error("ERROR: you don't have the right to delete object from the table ".$this->table, E_USER_ERROR);
		}
		$where = " WHERE (".$criteria.") ".$this->rightscriteria("write");

		// delete the uniqueid entry if required
		if ($this->uniqueid) {
			// select before deleting
			$result = $db->execute("SELECT id FROM ".$this->sqltable.$where) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			// collect the ids
			$ids = array ();
			foreach ($result as $row) {
				$ids[] = $row['id'];
			}
			// delete the uniqueid
			deleteuniqueid($ids);
		}
	
		//execute delete statement
		$db->execute("DELETE FROM ". $this->sqltable. $where) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		if ($db->Affected_Rows() <= 0) {
			return false; // not the rights
		}
		return true;
	}

	/**
	 * Récupère le critère SQL correspondant aux droits d'accès en lecture et en écriture
	 *
	 * Return the criteria depending on the write/read access
	 *
	 * @param string $access le niveau d'accès pour lequel on souhaite avoir le critère SQL
	 * @return string Le critère SQL correspond au droit d'accès
	 */
	public function rightscriteria($access)
	{
		if (!isset($this->cache_rightscriteria[$access])) {
			$classvars = get_class_vars($this->table. "VO");
			if ($classvars && array_key_exists("status", $classvars)) {
				$status = $this->sqltable. '.status';
				$this->cache_rightscriteria[$access] = C::get('visitor', 'lodeluser') ? '' : " AND $status > 0";

				if ($access == "write" && isset($this->rights['protect']) && C::get('rights', 'lodeluser') < $this->rights['protect']) {
					$this->cache_rightscriteria[$access] .= " AND $status<32 AND $status>-32 ";
				}
			}
		}	else	{
			$this->cache_rightscriteria[$access] = "";
		}
		return $this->cache_rightscriteria[$access];
	}

	/**
	 * Remplit un VO depuis une ligne d'un ResultSet SQL
	 *
	 * @param objet $vo Le VO à remplir passé par référence
	 * @param array $row La ligne du ResultSet SQL
	 * @access private
	 */
	protected function _getFromResult(&$vo, $row)
	{
		foreach ($row as $k => $v) {//fill vo from the database result set
			$vo->$k = $v;
		}
	}
}
?>
