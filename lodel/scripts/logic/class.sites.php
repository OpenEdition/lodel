<?php
/**	
 * Logique des sites
 *
 * PHP versions 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Home page: http://www.lodel.org
 * E-Mail: lodel@lodel.org
 *
 * All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @package lodel/logic
 * @author Ghislain Picard
 * @author Jean Lamy
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 * @version CVS:$Id$
 */



/**
 * Classe de logique des sites
 * 
 * @package lodel/logic
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajouté depuis la version 0.8
 * @see logic.php
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
		$context['id'] = @$context['id'];
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
		$context['id'] = @$context['id'];
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
		$id = (int)$id;
		if(!$id) {
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
			$vo = $dao->find($critere, 'path');
			if (!$vo->path) {
				$context['error'] = getlodeltextcontent('error_during_lock_site_the_site_may_have_been_deleted', 'lodeladmin');
				return '_error';
			}
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
?>