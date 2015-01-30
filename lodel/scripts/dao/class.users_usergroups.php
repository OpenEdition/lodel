<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier DAO de la table SQL users_usergroups.
 */

//
// Fichier généré automatiquement le 20-01-2009.
//


/**
 * Classe d'objet virtuel de la table SQL users_usergroups
 */
class users_usergroupsVO 
{
	/**#@+
	 * @access public
	 */
	public $idgroup;
	public $iduser;
	/**#@-*/
}

/**
 * Classe d'abstraction de la base de données de la table users_usergroups
 */
class users_usergroupsDAO extends DAO 
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
		parent::__construct("users_usergroups", false);
		$this->rights = array();
	}
}