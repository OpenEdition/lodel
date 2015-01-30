<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier DAO de la table SQL entities.
 */

 // begin{definitions} automatic generation  //

/**
 * Classe d'objet virtuel de la table SQL entities
 *
 */
class entitiesVO 
{
	/**#@+
	 * @access public
	 */
	public $id;
	public $idparent;
	public $idtype;
	public $identifier;
	public $entitytitle;
	public $usergroup;
	public $iduser;
	public $rank;
	public $status;
	public $upd;
	public $creationdate;
	public $modificationdate;
	public $creationmethod;
	public $creationinfo;
	/**#@-*/
}

/**
 * Classe d'abstraction de la base de données de la table entities
 *
 */
class entitiesDAO extends DAO 
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
		parent::__construct("entities", true);
		$this->rights = array('write'=>LEVEL_REDACTOR, 'protect'=>LEVEL_REDACTOR);
	}

 // end{definitions} automatic generation  //
	public function rightscriteria($access)
	{
		if (!isset($this->cache_rightscriteria[$access])) {
			parent::rightscriteria($access);
			if (!C::get('admin', 'lodeluser') && C::get('groups', 'lodeluser')) {
				$this->cache_rightscriteria[$access].=" AND usergroup IN (".C::get('groups', 'lodeluser').")";
			}
		}
		return $this->cache_rightscriteria[$access];
	}
}