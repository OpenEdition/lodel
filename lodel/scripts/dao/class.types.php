<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier DAO de la table SQL types.
 */

//
// Fichier généré automatiquement le 20-01-2009.
//


/**
 * Classe d'objet virtuel de la table SQL types
 */
class typesVO 
{
	/**#@+
	 * @access public
	 */
	public $id;
	public $type;
	public $title;
	public $altertitle;
	public $class;
	public $icon;
	public $gui_user_complexity;
	public $tpledition;
	public $display;
	public $tplcreation;
	public $import;
	public $creationstatus;
	public $search;
	public $oaireferenced;
	public $public;
	public $tpl;
	public $rank;
	public $status;
	public $upd;
	/**#@-*/
}

/**
 * Classe d'abstraction de la base de données de la table types
 */
class typesDAO extends DAO 
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
		parent::__construct("types", true);
		$this->rights = array('write'=>LEVEL_ADMIN, 'protect'=>LEVEL_ADMINLODEL);
	}
}