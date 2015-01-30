<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier DAO de la table SQL oai_token.
 */

//
// Fichier généré automatiquement le 20-01-2009.
//


/**
 * Classe d'objet virtuel de la table SQL oai_token
 */
class oai_tokenVO 
{
	/**#@+
	 * @access public
	 */
	public $token;
	public $query;
	public $metadataprefix;
	public $deliveredrecords;
	public $expirationdatetime;
	/**#@-*/
}

/**
 * Classe d'abstraction de la base de données de la table oai_token
 */
class oai_tokenDAO extends DAO 
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
		parent::__construct("oai_token", false);
		$this->rights = array();
	}

}