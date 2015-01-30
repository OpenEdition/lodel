<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Logique des sites
 */


/**
 * Classe de logique des sites
 *
 */
class SitesLogic extends Logic 
{

	/**
	 * Constructeur
	 */
	public function __construct()
	{
		parent::__construct('sites');
	}

	/**
	 * Construction des balises select HTML pour cet objet
	 *
	 * @param array &$context le contexte, tableau passé par référence
	 * @param string $var le nom de la variable du select
	 */
	public function makeSelect(&$context, $var)
	{
		switch($var) {
		}
	}

	/**
	 * Bloque un site
	 *
	 * Met le status d'un site à 32
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function lockAction(&$context, &$error)
	{
		if(empty($context['id']))
			return '_error';
		$ret = $this->_lockOrUnlock($context['id'], 'lock');
		return $ret;
	}

	/**
	 * Débloque un site
	 *
	 * Met le status d'un site à 1
	 *
	 * @param array &$context le contexte passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function unlockAction(&$context, &$error)
	{
		if(empty($context['id']))
			return '_error';
		$ret = $this->_lockOrUnlock($context['id'],'unlock');
		return $ret;
	}


	/**
	 * Bloque ou débloque
	 *
	 * @access private
	 * @param integer $id identifiant du site
	 * @param string $action lock ou unlock
	 */
	protected function _lockOrUnlock($id, $action = 'lock')
	{
		global $db;
		$id = (int) $id;
		if(empty($id)) {
			return '_error';
		}
		$critere = " id = '$id' AND status > 0";
		usemaindb();

		if($action == 'lock') // Verouillage du site
		{
			$iduser = (int)C::get('id', 'lodeluser');
			//bloque les tables
			lock_write ('session', 'sites');
			// cherche le nom du site
			$dao = DAO::getDAO('sites');
			$vo = $dao->find($critere, 'path, name');
			if (!$vo->path) {
				$context['error'] = getlodeltextcontent('error_during_lock_site_the_site_may_have_been_deleted', 'lodeladmin');
				return '_error';
			}
			$site = $vo->name;
			// delogue tout le monde sauf l'utilisateur courant
			$db->execute(lq("DELETE FROM #_MTP_session WHERE site='$site' AND iduser!='$iduser'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			// change le statut du site
			$db->execute(lq("UPDATE #_MTP_sites SET status = 32 WHERE $critere")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			unlock();

		} else if ($action == 'unlock') { // Déverouillage du site

			$db->execute(lq("UPDATE #_MTP_sites SET status = 1 WHERE $critere")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			//deverouille les tables
			unlock();

		} else {
			trigger_error('Invalid action', E_USER_ERROR);
		}

		usecurrentdb();
		clearcache();
		return '_back';
	}

	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	protected function _publicfields() 
	{
		return array('title' => array('text', '+'),
									'subtitle' => array('text', '+'),
									'path' => array('text', '+'),
									'url' => array('text', '+'),
									'langdef' => array('select', ''));
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	// end{uniquefields} automatic generation  //
} // class 