<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier DAO de la table SQL mainplugins.
 */

 // begin{definitions} automatic generation  //


/**
 * Classe d'objet virtuel de la table SQL mainplugins
 */
class mainpluginsVO 
{
	/**#@+
	 * @access public
	 */
	public $id;
	public $name;
	public $title;
	public $description;
	public $trigger_preedit;
	public $trigger_postedit;
	public $trigger_prelogin;
	public $trigger_postlogin;
	public $trigger_preauth;
	public $trigger_postauth;
	public $trigger_preview;
	public $trigger_postview;
	public $config;
	public $hooktype;
	public $status;
	public $upd;
	/**#@-*/
}

/**
 * Classe d'abstraction de la base de données de la table mainplugins
 */
class mainpluginsDAO extends DAO 
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
		parent::__construct("mainplugins");
		$this->rights = array('write'=>LEVEL_ADMINLODEL, 'protect'=>LEVEL_ADMINLODEL, 'read'=>LEVEL_ADMINLODEL);
	}

 // end{definitions} automatic generation  //
}