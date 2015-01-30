<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier DAO de la table SQL oailogs.
 */

//
// Fichier généré automatiquement le 20-01-2009.
//


/**
 * Classe d'objet virtuel de la table SQL oailogs
 */
class oailogsVO 
{
	/**#@+
	 * @access public
	 */
	public $id;
	public $host;
	public $date;
	public $denied;
	/**#@-*/
}

/**
 * Classe d'abstraction de la base de données de la table oailogs
 */
class oailogsDAO extends DAO 
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
		parent::__construct("oailogs", false);
		$this->rights = array();
	}

}