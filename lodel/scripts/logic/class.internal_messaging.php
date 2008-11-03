<?php
/**
 * Fichier de messagerie interne
 *
 * PHP 5
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
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 */
require_once 'logic.php';
/**
 * Classe de logique permettant de gérer la messagerie interne
 * 
 * @package lodel/logic
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajoutée depuis la version 0.8
 */
class Internal_MessagingLogic extends Logic 
{

	/**
	 * ID de l'utilisateur courant
	 * De la forme $site-$id ou lodelmain-$id (adminlodel)
	 */
	private $_iduser;

	/**
	 * Constructeur
	 */
	public function __construct()
	{
		global $lodeluser, $context;
		$this->_iduser = $lodeluser['adminlodel'] ? 'lodelmain-'.$lodeluser['id'] : $context['site'].'-'.$lodeluser['id'];
		parent::__construct('internal_messaging');
	}

	/**
	* make the select for this logic
	*/
	public function makeSelect(&$context,$var) 
	{
		if('addressees' != $var) return;
		global $db;
		$arr = array();
		if($context['idparent']) {
			$idparent = intval($context['idparent']);
			$sender = $db->getArray(lq("SELECT iduser, subject, body, addressees FROM #_MTP_internal_messaging WHERE id='{$idparent}'"));
			$sender = $sender[0];
			preg_match("/(\w+)-(\d+)/", $sender['iduser'], $m);
			$idUser = intval($m[2]);
			if('lodelmain' == $m[1]) {
				$user = $db->getArray(lq("SELECT username, firstname, lastname, userrights from #_MTP_users WHERE id='{$idUser}'"));
			} elseif(!SINGLESITE) {
				$dbname = '`'.DATABASE.'_'.$m[1].'`.'.$GLOBALS['tableprefix'];
				$user = $db->getArray("SELECT username, firstname, lastname, userrights from {$dbname}users WHERE id='{$idUser}'");
			}
			$user = $user[0];
			$arr[':'.$sender['iduser'].':'] = $user['username'] . ( ($user['firstname'] || $user['lastname']) ? " ({$user['firstname']} {$user['lastname']})" : ''). ($user['userrights'] == LEVEL_ADMINLODEL ? '(LodelAdmin)' : ($user['userrights'] == LEVEL_ADMIN ? '(Admin)' : ''));
			$addressees = explode(':', $sender['addressees']);
			foreach($addressees as $addr) {
				if(preg_match("/(\w+)-(\d+)/", $addr, $m) != 1) continue;
				$idUser = intval($m[2]);
				if('lodelmain' == $m[1]) {
					$user = $db->getArray(lq("SELECT username, firstname, lastname, userrights from #_MTP_users WHERE id='{$idUser}'"));
				} elseif(!SINGLESITE) {
					$dbname = '`'.DATABASE.'_'.$m[1].'`.'.$GLOBALS['tableprefix'];
					$user = $db->getArray("SELECT username, firstname, lastname, userrights from {$dbname}users WHERE id='{$idUser}'");
				}
				$user = $user[0];
				if(!in_array(':'.$addr.':', $arr))
					$arr[':'.$addr.':'] = $user['username'] . ( ($user['firstname'] || $user['lastname']) ? " ({$user['firstname']} {$user['lastname']}) " : ''). ($user['userrights'] == LEVEL_ADMINLODEL ? ' (LodelAdmin) ' : ($user['userrights'] == LEVEL_ADMIN ? ' (Admin) ' : ''));				
			}
			$context['addressees'] = $addressees;
			foreach($context['addressees'] as $k=>$v) {
				if($v == '') unset($context['addressees'][$k]);
				else {
					$context['addressees'][$k] = ':'.$v.':';
				}
			}
			$context['subject'] = 'RE: '.$sender['subject'];
		} else {
			if(!SINGLESITE) {
				$users = $db->getArray(lq("SELECT id, username, lastname, firstname, userrights FROM #_TP_users ORDER BY username"));
				if(is_array($users)) {
					foreach($users as $user) {
						$arr[':'.$context['site'].'-'.$user['id'].':'] = $user['username'] . ( ($user['firstname'] || $user['lastname']) ? " ({$user['firstname']} {$user['lastname']})" : ''). ($user['userrights'] == LEVEL_ADMINLODEL ? '(LodelAdmin)' : ($user['userrights'] == LEVEL_ADMIN ? '(Admin)' : ''));
						$ids[] = 'allsite'.$context['site'].'-'.$user['id'];
						$siteids[] = $context['site'].'-'.$user['id'];
						if($user['userrights'] == LEVEL_ADMIN) {
							$adminids[] = $context['site'].'-'.$user['id'];
						}
					}
					$arr[':'.join(':', $ids).':'] = getlodeltextcontents('internal_messaging_all_users_from_site', 'admin');
					if($adminids) {
						$arr[':'.join(':', $adminids).':'] = getlodeltextcontents('internal_messaging_all_admin_from_site', 'admin');
					}
					unset($ids, $adminids);
				}
			}
			$users = $db->getArray(lq("SELECT id, username, lastname, firstname, userrights FROM #_MTP_users ORDER BY username"));
			if(is_array($users)) {
				foreach($users as $user) {
					$arr[':lodelmain-'.$user['id'].':'] = $user['username'] . ( ($user['firstname'] || $user['lastname']) ? " ({$user['firstname']} {$user['lastname']}) " : '') . ($user['userrights'] == LEVEL_ADMINLODEL ? ' (LodelAdmin) ' : ($user['userrights'] == LEVEL_ADMIN ? ' (Admin) ' : ''));
					$ids[] = 'alllodelmain-'.$user['id'];
					$mainids[] = 'lodelmain-'.$user['id'];
				}
				$arr[':'.join(':', $ids).':'] = getlodeltextcontents('internal_messaging_all_adminlodel', 'admin');
				unset($ids);
			}
			if($context['lodeluser']['adminlodel']) {
				$sites = $db->getArray(lq("SELECT name FROM #_MTP_sites ORDER BY name"));
				$all = join(':', $mainids).':'.join(':', $siteids);
				foreach($sites as $site) {
					$dbname = '`'.DATABASE.'_'.$site['name'].'`.'.$GLOBALS['tableprefix'];
					$users = $db->getArray("SELECT id, username, lastname, firstname, userrights FROM {$dbname}users ORDER BY username");
					if(!is_array($users)) continue;
					foreach($users as $user) {
						$ids[] = $site['name'].'-'.$user['id'];
					}				
				}
				$arr[':'.$all.(is_array($ids) ? ':'.join(':', $ids) : '').':'] = getlodeltextcontents('internal_messaging_all_users_all_sites', 'admin');
			}
		}
		$selected = isset($context['addressees']) ? $context['addressees'] : '';
		renderOptions($arr, $selected);
	}

	public function deleteAction(&$context, &$error)
	{
		global $db, $lodeluser;
		if (isset($context['all'])) {
			$db->execute(lq("DELETE FROM #_MTP_internal_messaging WHERE addressee = '{$this->_iduser}'")) or dberror();
		} else {
			$id = intval($context['id']);
			$childIds = $db->getArray(lq("SELECT id, status FROM #_MTP_internal_messaging WHERE idparent = '{$id}' AND addressee = '{$this->_iduser}'"));
			if($childIds) {
				foreach($childIds as $childId) {
					$context['id'] = $childId['id'];
					$this->deleteAction($context, $error);
				}
			}
			$db->execute(lq("DELETE FROM #_MTP_internal_messaging WHERE id = '{$id}' AND addressee = '{$this->_iduser}'")) or dberror();
		}
		$context['deleted'] = true;
		unset($context['id']);
		require_once 'func.php';
		update();
		return "_location: index.php?do=list&lo=internal_messaging";
	}

	public function listAction(&$context, &$error)
	{
		global $db;
		$id = intval($context['id']);
		if($id) {
			$context['data'] = array();
			$datas = $db->getArray(lq("SELECT idparent, iduser, subject, body, cond, status FROM #_MTP_internal_messaging WHERE id='{$id}' AND addressee = '{$this->_iduser}'"));
			if(!$datas) {
				$error = getlodeltextcontents('internal_messaging_no_message_found', 'admin');
				return '_error';
			}
			$datas = $datas[0];
			preg_match("/(\w+)-(\d+)/", $datas['iduser'], $m);
			$idUser = intval($m[2]);
			if('lodelmain' == $m[1]) {
				$sender = $db->getArray(lq("SELECT username, firstname, lastname from #_MTP_users WHERE id='{$idUser}'"));
			} else {
				$dbname = '`'.DATABASE.'_'.$m[1].'`.'.$GLOBALS['tableprefix'];
				$sender = $db->getArray("SELECT username, firstname, lastname from {$dbname}users WHERE id='{$idUser}'");
			}
			$sender = $sender[0];
			$context['data'] = $datas;
			$context['data']['sender'] = $sender['username']. ( ($sender['firstname'] || $sender['lastname']) ? " ({$sender['firstname']} {$sender['lastname']})" : '');
			// nouveau message, on update son status à 0 == lu
			if(1 == $datas['status']) {
				$db->execute(lq("UPDATE #_MTP_internal_messaging SET status = 0 WHERE id = '{$id}' AND addressee = '{$this->_iduser}'")) or dberror();
			}
		}
		return '_ok';
	}

	public function viewAction(&$context, &$error)
	{
		usemaindb();
		return parent::viewAction($context, $error);
	}

	public function editAction(&$context, &$error)
	{
		global $db, $lodeluser;
		
		if (!$this->validateFields($context, $error)) {
			return '_error';
		}
		
		$addressees = array();
		foreach($context['addressees'] as $users) {
			$arr = explode(':', $users);
			foreach($arr as $user) {
				// on évite les doublons (ex: tous les admins + admin sélectionné)
				if($user != '' && !in_array($user, $addressees))
					$addressees[] = strtr($user, array('allsite'=>'', 'alllodelmain'=>'lodelmain'));
			}
		}

		usemaindb();
		// get the dao for working with the object
		$dao = $this->_getMainTableDAO();
		if ($lodeluser['rights'] < $dao->rights['write']) {
			die('ERROR: you don\'t have the right to send internal message');
		}
		$context['subject'] = addslashes($context['subject']);
		$context['body'] = addslashes($context['body']);
		$requetes = '';
		$context['addressees'] = array();
		$context['addressees'] = join(':', $addressees);
		foreach($addressees as $k=>$addressee) {
			if($context['idparent']) {
				$subject = addslashes($db->getOne(lq("SELECT subject FROM #_MTP_internal_messaging WHERE id='{$context['idparent']}' AND addressee = '{$this->_iduser}'")));
				if($subject)
					$idparent = $db->getOne(lq("SELECT id FROM #_MTP_internal_messaging WHERE subject='{$subject}' AND addressee='{$addressee}' AND addressee = '{$this->_iduser}'"));
				else $idparent=0;
			} else $idparent=0;
			$requetes .= "('{$idparent}', '{$this->_iduser}', '{$addressee}', ':{$context['addressees']}:', '{$context['subject']}', '{$context['body']}', '{$context['cond']}', '1'),";
		}
		$requetes = substr_replace($requetes, '', -1);
		$db->execute(lq("INSERT INTO #_MTP_internal_messaging (idparent, iduser, addressee, addressees, subject, body, cond, status) VALUES {$requetes}")) or dberror();
		$context['msgsended'] = true;
		unset($context['idparent']);
		require_once 'func.php';
		update();
		return 'internal_messaging';
	}

	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	function _publicfields() 
	{
		return array('addressees' => array('multipleselect', '+'),	
									'subject' => array('text', ''),
									'body' => array('longtext', '+'),
									'cond' => array('boolean', '+'),
									'status' => array('int', '+'));
	}

	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //


	// end{uniquefields} automatic generation  //

}// end of Internal_MessagingLogic class
?>