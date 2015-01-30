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
class relations_extVO 
{
	/**#@+
	 * @access public
	 */
	public $idrelation;
	public $id1;
	public $id2;
	public $nature;
	public $degree;
	public $site;
	/**#@-*/
}

/**
 * Classe d'abstraction de la base de données de la table relations_ext
 */
class relations_extDAO extends DAO 
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
		parent::__construct("relations_ext", false);
		$this->rights = array();
	}
}