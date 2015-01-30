<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier DAO de la table SQL relations.
 */

//
// Fichier généré automatiquement le 20-01-2009.
//


/**
 * Classe d'objet virtuel de la table SQL relations
 */
class relationsVO 
{
	/**#@+
	 * @access public
	 */
	public $idrelation;
	public $id1;
	public $id2;
	public $nature;
	public $degree;
	/**#@-*/
}

/**
 * Classe d'abstraction de la base de données de la table relations
 */
class relationsDAO extends DAO 
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
		parent::__construct("relations", false);
		$this->rights = array();
	}
}