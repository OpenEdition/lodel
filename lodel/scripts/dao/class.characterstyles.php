<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier DAO de la table SQL characterstyles.
 */

//
// Fichier généré automatiquement le 20-01-2009.
//

/**
 * Classe d'objet virtuel de la table SQL characterstyles
 */
class characterstylesVO 
{
	/**#@+
	 * @access public
	 */
	public $id;
	public $style;
	public $conversion;
	public $rank;
	public $status;
	public $upd;
	public $otx;
	/**#@-*/
}

/**
 * Classe d'abstraction de la base de données de la table characterstyles
 */
class characterstylesDAO extends DAO 
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
		parent::__construct("characterstyles", false);
		$this->rights = array('write'=>LEVEL_ADMIN, 'protect'=>LEVEL_ADMINLODEL);
	}

}