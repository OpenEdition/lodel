<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier DAO de la table SQL sites.
 */

//
// Fichier généré automatiquement le 20-01-2009.
//


/**
 * Classe d'objet virtuel de la table SQL sites
 */
class sitesVO 
{
	/**#@+
	 * @access public
	 */
	public $id;
	public $name;
	public $title;
	public $subtitle;
	public $path;
	public $url;
	public $langdef;
	public $status;
	public $upd;
	public $creationdate;
	/**#@-*/
}

/**
 * Classe d'abstraction de la base de données de la table sites
 */
class sitesDAO extends DAO 
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
		parent::__construct("sites", true);
		$this->rights = array('write'=>LEVEL_ADMINLODEL, 'protect'=>LEVEL_ADMINLODEL);
	}
}