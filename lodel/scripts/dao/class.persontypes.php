<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier DAO de la table SQL persontypes.
 */

//
// Fichier généré automatiquement le 20-01-2009.
//


/**
 * Classe d'objet virtuel de la table SQL persontypes
 */
class persontypesVO 
{
	/**#@+
	 * @access public
	 */
	public $id;
	public $type;
	public $class;
	public $title;
	public $altertitle;
	public $icon;
	public $gui_user_complexity;
	public $g_type;
	public $style;
	public $tpl;
	public $tplindex;
	public $rank;
	public $status;
	public $upd;
	public $otx;
	/**#@-*/
}

/**
 * Classe d'abstraction de la base de données de la table persontypes
 */
class persontypesDAO extends DAO 
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
		parent::__construct("persontypes", true);
		$this->rights = array('write'=>LEVEL_ADMIN, 'protect'=>LEVEL_ADMINLODEL);
	}
}