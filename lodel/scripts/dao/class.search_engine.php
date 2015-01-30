<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier DAO de la table SQL search_engine.
 */

//
// Fichier généré automatiquement le 20-01-2009.
//


/**
 * Classe d'objet virtuel de la table SQL search_engine
 */
class search_engineVO 
{
	/**#@+
	 * @access public
	 */
	public $identity;
	public $tablefield;
	public $word;
	public $weight;
	/**#@-*/
}

/**
 * Classe d'abstraction de la base de données de la table search_engine
 */
class search_engineDAO extends DAO 
{
	/**
	 * Constructeur
	 *
	 * <p>Appelle le constructeur de la classe mère DAO en lui passant le nom de la classe.
	 * Renseigne aussi le tableau rights des droits.
	 * </p>
	 */
	public function __construct()
	{
		parent::__construct("search_engine", false);
		$this->rights = array();
	}
}