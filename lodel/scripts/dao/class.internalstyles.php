<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier DAO de la table SQL internalstyles.
 */

//
// Fichier généré automatiquement le 20-01-2009.
//


/**
 * Classe d'objet virtuel de la table SQL internalstyles
 */
class internalstylesVO 
{
	/**#@+
	 * @access public
	 */
	public $id;
	public $style;
	public $conversion;
	public $surrounding;
	public $greedy;
	public $rank;
	public $status;
	public $upd;
	public $otx;
	/**#@-*/
}

/**
 * Classe d'abstraction de la base de données de la table internalstyles
 */
class internalstylesDAO extends DAO 
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
		parent::__construct("internalstyles", false);
		$this->rights = array('write'=>LEVEL_ADMIN, 'protect'=>LEVEL_ADMINLODEL);
	}

}