<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier DAO de la table SQL session.
 */

//
// Fichier généré automatiquement le 20-01-2009.
//


/**
 * Classe d'objet virtuel de la table SQL session
 */
class sessionVO 
{
	/**#@+
	 * @access public
	 */
	public $id;
	public $name;
	public $iduser;
	public $site;
	public $currenturl;
	public $userrights;
	public $context;
	public $expire;
	public $expire2;
	/**#@-*/
}

/**
 * Classe d'abstraction de la base de données de la table session
 */
class sessionDAO extends DAO 
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
		parent::__construct("session", false);
		$this->rights = array();
	}
}