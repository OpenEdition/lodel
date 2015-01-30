<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier DAO de la table SQL users.
 */

//
// Fichier généré automatiquement le 20-01-2009.
//


/**
 * Classe d'objet virtuel de la table SQL users
 */
class usersVO 
{
	/**#@+
	 * @access public
	 */
	public $id;
	public $username;
	public $passwd;
	public $lastname;
	public $firstname;
	public $email;
	public $lang;
	public $userrights;
	public $gui_user_complexity;
	public $nickname;
	public $biography;
	public $photo;
	public $professional_website;
	public $url_professional_website;
	public $rss_professional_website;
	public $personal_website;
	public $url_personal_website;
	public $rss_personal_website;
	public $pgp_key;
	public $alternate_email;
	public $phonenumber;
	public $im_identifier;
	public $im_name;
	public $status;
	public $rank;
	public $upd;
	/**#@-*/
}

/**
 * Classe d'abstraction de la base de données de la table users
 */
class usersDAO extends DAO 
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
		parent::__construct("users", false);
		$this->rights = array('write'=>LEVEL_ADMIN, 'protect'=>LEVEL_ADMINLODEL);
	}
}