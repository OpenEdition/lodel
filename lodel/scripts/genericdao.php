<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier de la classe GenericDAO
 */

/**
 * Classe GenericDAO
 *
 * <p>Cette classe permet de gérer les éléments génériques de l'interface</p>
 * 
 */

class genericDAO extends DAO
{
	/**
	 * Constructeur
	 *
	 * @param string $table la table SQL de la DAO
	 * @param string $idfield Indique le nom du champ identifiant
	 */
	public function __construct($table, $idfield)
	{
		parent::__construct($table, false, $idfield); // create the class
	}

	/**
	 * Instantie un nouvel objet virtuel (VO)
	 *
	 * Instantiate a new object
	 *
	 * @param object &$vo l'objet virtuel qui sera instancié
	 */
	public function instantiateObject(&$vo)
	{
		$classname = $this->table."VO";
		if (!class_exists($this->table."VO", false)) {
			eval ("class ". $classname. " { public $". $this->idfield. "; } ");
		}
		$vo = new $classname; // the same name as the table. We don't use factory...
	}
	/**
	 * Retourne les droits correspondant à un access particulier
	 *
	 * @access private
	 * @param string $access l'accès pour lequel on veut les droits
	 * @return Retourne une chaîne vide.
	 */
	protected function _rightscriteria($access)
	{
		return '';
	}
}