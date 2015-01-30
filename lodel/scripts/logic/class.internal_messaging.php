<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier de messagerie interne
 */

/**
 * Classe de logique permettant de gérer la messagerie interne
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
		$this->_iduser = C::get('adminlodel', 'lodeluser') ? 
              		'lodelmain-'.C::get('id', 'lodeluser') : 
              		C::get('site', 'cfg').'-'.C::get('id', 'lodeluser');
		parent::__construct('internal_messaging');
	}

	/**
	* make the select for this logic
	*/
	public function makeSelect(&$context,$var) 
	{
		if('recipients' != $var) return;
		global $db;
		$arr = array();
		if(!empty($context['idparent'])) {
			$idparent = (int)$context['idparent'];
			$sender = $db->getRow(lq("SELECT iduser, subject, body, recipients FROM #_MTP_internal_messaging WHERE id='{$idparent}'"));
			preg_match("/(.+)\-(\d+)/", $sender['iduser'], $m);
			$idUser = (int)$m[2];
			if('lodelmain' == $m[1]) {
				$user = $db->getRow(lq("SELECT username, firstname, lastname, userrights from #_MTP_users WHERE id='{$idUser}'"));
			} elseif(!SINGLESITE) {
				$dbname = '`'.DATABASE.'_'.$m[1].'`.'.$GLOBALS['tableprefix'];
				$user = $db->getRow("SELECT username, firstname, lastname, userrights from {$dbname}users WHERE id='{$idUser}'");
			}
			$arr[':'.$sender['iduser'].':'] = $user['username'] . ( ($user['firstname'] || $user['lastname']) ? " ({$user['firstname']} {$user['lastname']})" : ''). ($user['userrights'] == LEVEL_ADMINLODEL ? '(LodelAdmin)' : ($user['userrights'] == LEVEL_ADMIN ? '(Admin)' : ''));
			$recipients = explode(':', $sender['recipients']);
			foreach($recipients as $addr) {
				if($this->_iduser == $addr || preg_match("/(.+)\-(\d+)/", $addr, $m) != 1) continue;
				$idUser = (int)$m[2];
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
			$context['recipients'] = $recipients;
			foreach($context['recipients'] as $k=>$v) {
				if($v == '') unset($context['recipients'][$k]);
				else {
					$context['recipients'][$k] = ':'.$v.':';
				}
			}
			$context['subject'] = 'RE: '.$sender['subject'];
		} else {
			if(!SINGLESITE && !defined('backoffice-lodeladmin')) {
				$users = $db->getArray(lq("SELECT id, username, lastname, firstname, userrights FROM #_TP_users ORDER BY username"));
				if(is_array($users)) {
					foreach($users as $user) {
						$arr[':'.$context['site'].'-'.$user['id'].':'] = $user['username'] . ( ($user['firstname'] || $user['lastname']) ? " ({$user['firstname']} {$user['lastname']})" : ''). ($user['userrights'] == LEVEL_ADMIN ? '(Admin)' : '');
						$ids[] = 'allsite'.$context['site'].'-'.$user['id'];
						$siteids[] = $context['site'].'-'.$user['id'];
						if($user['userrights'] == LEVEL_ADMIN) {
							$adminids[] = $context['site'].'-'.$user['id'];
						}
					}
					$arr[':'.join(':', $ids).':'] = getlodeltextcontents('internal_messaging_all_users_from_site', 'admin');
					if(isset($adminids)) {
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
			if(C::get('adminlodel', 'lodeluser')) {
				$sites = $db->getArray(lq("SELECT name FROM #_MTP_sites ORDER BY name"));
				$all = join(':', $mainids).':'.(isset($siteids) ? join(':', $siteids) : '');
				foreach($sites as $site) {
					$dbname = '`'.DATABASE.'_'.$site['name'].'`.'.$GLOBALS['tableprefix'];
					$users = $db->getArray("SELECT id, username, lastname, firstname, userrights FROM {$dbname}users ORDER BY username");
					if(!is_array($users)) continue;
					foreach($users as $user) {
						$ids[] = $site['name'].'-'.$user['id'];
					}				
				}
				$arr[':'.$all.(!empty($ids) ? ':'.join(':', $ids) : '').':'] = getlodeltextcontents('internal_messaging_all_users_all_sites', 'admin');
			}
		}
		$selected = !empty($context['idparent']) ? array_keys($arr) : '';
		renderOptions($arr, $selected);
	}

	/**
	 * Suppression d'un message
	 *
	 * Liste des Status
	 * -32 : supprimé destinataire, dossier 'envoyés' de l'envoyeur
	 * -16 : corbeille destinataire, lu, supprimé du dossier 'envoyés' de l'envoyeur
	 * -8 : corbeille destinaire, lu, dossier 'envoyés' de l'envoyeur 
	 * -2 : corbeille destinaire, non lu, supprimé du dossier 'envoyés' de l'envoyeur
	 * -1 : corbeille destinaire, non lu, dossier 'envoyés' de l'envoyeur
	 * 0 : boite de réception destinataire, lu, dossier 'envoyés' de l'envoyeur
	 * 1 : boite de réception destinataire, non lu, dossier 'envoyés' de l'envoyeur
	 * 16 : boite de réception destinataire, non lu, supprimé du dossier 'envoyés' de l'envoyeur
	 * 32 : boite de réception destinataire, lu, supprimé du dossier 'envoyés' de l'envoyeur
	 * @param array &$context le context passé par référence
	 * @param array &$error le tableau des erreurs éventuelles passé par référence
	 */
	public function deleteAction(&$context, &$error)
	{
		global $db;
        
        	if(empty($context['directory'])) $context['directory'] = 'inbox';
        	else $context['directory'] = strtolower($context['directory']);
		if (isset($context['all'])) {
			unset($context['all']);
			$context['escape'] = true;
			if($context['directory'] == 'inbox') {
				$where = "status >= 0 AND recipient = '{$this->_iduser}'";
			} elseif($context['directory'] == 'sent') {
				$where = "status in (0,1,-1,-8,-32) AND iduser = '{$this->_iduser}'";
			} elseif($context['directory'] == 'trash') {
				$where = "status in (-1,-8,-2,-16) AND recipient = '{$this->_iduser}'";
			}
			if(isset($where)) {
				$ids = $db->execute(lq("SELECT id FROM #_MTP_internal_messaging WHERE {$where}"));
				if($ids) {
					while(!$ids->EOF) {
						$context['id'] = $ids->fields['id'];
						$this->deleteAction($context, $error);
						$ids->MoveNext();
					}
				}
			}
			unset($context['escape']);
		} elseif(isset($context['im_select'])) {
			$context['escape'] = true;
			$selects = $context['im_select'];
			unset($context['im_select']);
			foreach($selects as $id=>$checked) {
				$id = (int)$id;
				if('sent' === (string)$context['directory']) {
					$childIds = $db->execute(lq("
							SELECT distinct(id) 
							FROM	#_MTP_internal_messaging as i_m, 
								(  SELECT iduser, incom_date, subject, body, recipients 
								   FROM #_MTP_internal_messaging 
								   WHERE id = '{$id}') AS im 
							WHERE 	i_m.iduser=im.iduser 
								AND i_m.incom_date=im.incom_date 
								AND i_m.subject=im.subject 
								AND i_m.body=im.body 
								AND i_m.recipients=im.recipients
								AND id != '{$id}'"));
					if($childIds) {
						while(!$childIds->EOF) {
							$context['id'] = $childIds->fields['id'];
							$this->deleteAction($context, $error);
							$childIds->MoveNext();
						}
					}
				}
				$context['id'] = $id;
				$this->deleteAction($context, $error);
			}
			unset($context['escape']);
		} elseif(!empty($context['id'])) {
			$id = (int) $context['id'];
			$childIds = $db->execute(lq("SELECT id FROM #_MTP_internal_messaging WHERE idparent = '{$id}'"));
			if($childIds) {
				$oldEscape = isset($context['escape']) ? $context['escape'] : false;
				$context['escape'] = true;
				while(!$childIds->EOF) {
					$context['id'] = $childIds->fields['id'];
					$this->deleteAction($context, $error);
					$childIds->MoveNext();
				}
				$context['escape'] = $oldEscape;
				unset($oldEscape);
			}

			$row = $db->getRow(lq("SELECT status, iduser, recipient FROM #_MTP_internal_messaging WHERE id = '{$id}'"));
			
			if($row) {
				if((string)$row['recipient'] === $this->_iduser) 
				{
					if(empty($context['directory']) || $context['directory'] == 'inbox') 
					{
						switch((int)$row['status']) {
						case 0:
						$status = -8;
						break;
			
						case 1:
						$status = -1;
						break;
			
						case 16:
						$status = -2;
						break;
			
						case 32:
						$status = -16;
						break;
			
						default:break;
						}
						if(isset($status))
						$db->execute(lq("UPDATE #_MTP_internal_messaging SET status = '{$status}' WHERE id = '{$id}' AND recipient = '{$this->_iduser}'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
					} 
					elseif($context['directory'] == 'trash') 
					{
						if(!isset($context['restore'])) $context['restore'] = 0;
						switch((int)$row['status']) {
						case -16:
						$status = (1 === (int)$context['restore']) ? 32 : 'delete';
						break;
						case -2:
						$status = (1 === (int)$context['restore']) ? 16 : 'delete';
						break;
			
						case -8:
						$status = (1 === (int)$context['restore']) ? 0 : -32;
						break;
						case -1:
						$status = (1 === (int)$context['restore']) ? 1 : -32;
						break;
			
						default:break;
						}
						if(isset($status)) {
						if('delete' === $status)
							$db->execute(lq("DELETE FROM #_MTP_internal_messaging WHERE id = '{$id}' AND recipient = '{$this->_iduser}'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
						else
							$db->execute(lq("UPDATE #_MTP_internal_messaging SET status = '{$status}' WHERE id = '{$id}' AND recipient = '{$this->_iduser}'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
						}
					} 
					elseif($context['directory'] == 'sent') 
					{
						switch((int)$row['status']) {
						case 0:
						$status = 32;
						break;
			
						case 1:
						$status = 16;
						break;
			
						case -1:
						$status = -2;
						break;
			
						case -8:
						$status = -16;
						break;
			
						case -32:
						$status = 'delete';
						break;
						default:break;
						}
						if(isset($status)) {
						if('delete' === $status)
							$db->execute(lq("DELETE FROM #_MTP_internal_messaging WHERE id = '{$id}' AND recipient = '{$this->_iduser}'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
						else
							$db->execute(lq("UPDATE #_MTP_internal_messaging SET status = '{$status}' WHERE id = '{$id}' AND recipient = '{$this->_iduser}'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
						}
					}
				} 
				elseif((string)$row['iduser'] === $this->_iduser) 
				{
					if($context['directory'] == 'sent') {
						switch((int)$row['status']) {
						case 0:
						$status = 32;
						break;
			
						case 1:
						$status = 16;
						break;
			
						case -1:
						$status = -2;
						break;
			
						case -8:
						$status = -16;
						break;
			
						case -32:
						$status = 'delete';
						break;
						default:break;
						}
						if(isset($status)) {
						if('delete' === $status)
							$db->execute(lq("DELETE FROM #_MTP_internal_messaging WHERE id = '{$id}' AND iduser = '{$this->_iduser}'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
						else
							$db->execute(lq("
							UPDATE #_MTP_internal_messaging 
							SET status = '{$status}' 
							WHERE id = '{$id}' AND iduser = '{$this->_iduser}'")) 
							or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
						}
					}
				}
			}
		}
		unset($context['id'], $id);
		if(!isset($context['escape']) || true !== $context['escape']) {
			update();
            		if(!isset($context['restore'])) $context['restore'] = false;
			return "_location: index.php?do=list&lo=internal_messaging&".($context['restore'] ? "restored" : "deleted")."=1&directory=".$context['directory'];
		}
	}

	public function listAction(&$context, &$error)
	{
		global $db;
		
		if(!empty($context['id'])) {
			$id = (int) $context['id'];
			$context['data'] = array();
			$datas = $db->getRow(lq("SELECT idparent, iduser, recipient, subject, body, cond, status FROM #_MTP_internal_messaging WHERE id='{$id}' AND (recipient = '{$this->_iduser}' OR iduser = '{$this->_iduser}')"));
			if(!$datas) {
				$error = getlodeltextcontents('internal_messaging_no_message_found', 'admin');
				return '_error';
			}
			preg_match("/(.+)\-(\d+)/", $datas['iduser'], $m);
			$idUser = (int)$m[2];
			if('lodelmain' == $m[1]) {
				$sender = $db->getRow(lq("SELECT username, firstname, lastname from #_MTP_users WHERE id='{$idUser}'"));
			} else {
				$dbname = '`'.DATABASE.'_'.$m[1].'`.'.$GLOBALS['tableprefix'];
				$sender = $db->getRow("SELECT username, firstname, lastname from {$dbname}users WHERE id='{$idUser}'");
			}
			preg_match("/(.+)\-(\d+)/", $datas['recipient'], $m);
			$idUser = (int)$m[2];
			if('lodelmain' == $m[1]) {
				$recipient = $db->getRow(lq("SELECT username, firstname, lastname from #_MTP_users WHERE id='{$idUser}'"));
			} else {
				$dbname = '`'.DATABASE.'_'.$m[1].'`.'.$GLOBALS['tableprefix'];
				$recipient = $db->getRow("SELECT username, firstname, lastname from {$dbname}users WHERE id='{$idUser}'");
			}
			if(!$recipient)
			{ // deleted user
				$recipient = array('username'=>'', 'firstname'=>'', 'lastname'=>'');
			}
			if(!$sender)
			{ // deleted user
				$sender = array('username'=>'', 'firstname'=>'', 'lastname'=>'');
			}
			$context['data'] = $datas;
			$context['data']['sender'] = $sender['username']. ( ($sender['firstname'] || $sender['lastname']) ? " ({$sender['firstname']} {$sender['lastname']})" : '');
			$context['data']['recipient'] = $recipient['username']. ( ($recipient['firstname'] || $recipient['lastname']) ? " ({$recipient['firstname']} {$recipient['lastname']})" : '');
			// nouveau message, on update son status à 0 == lu
			if(1 === (int)$datas['status'] && $this->_iduser == $datas['recipient']) {
				$db->execute(lq("UPDATE #_MTP_internal_messaging SET status = 0 WHERE id = '{$id}' AND recipient = '{$this->_iduser}'")) 
					or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
		}
		return '_ok';
	}

	public function viewAction(&$context, &$error)
	{
		usemaindb();
		$ret = parent::viewAction($context, $error);
		usecurrentdb();
		return $ret;
	}

	public function editAction(&$context, &$error, $clean = false)
	{
		global $db;
		
		if (!$this->validateFields($context, $error)) {
			return '_error';
		}
		
		$recipients = array();
		foreach($context['recipients'] as $users) {
			$arr = explode(':', $users);
			foreach($arr as $user) {
				// on évite les doublons (ex: tous les admins + admin sélectionné)
				if($user != '' && !in_array($user, $recipients))
					$recipients[] = strtr($user, array('allsite'=>'', 'alllodelmain'=>'lodelmain'));
			}
		}

		usemaindb();
		// get the dao for working with the object
		$dao = $this->_getMainTableDAO();
		if (C::get('rights', 'lodeluser') < $dao->rights['write']) {
			trigger_error('ERROR: you don\'t have the right to send internal message', E_USER_ERROR);
		}
		$context['subject'] = !empty($context['subject']) ? addslashes($context['subject']) : '';
		$context['body'] = !empty($context['body']) ? addslashes($context['body']) : '';
		$requetes = '';
		$context['recipients'] = array();
		$context['recipients'] = join(':', $recipients);
		foreach($recipients as $k=>$recipient) {
			if(!empty($context['idparent'])) {
				$subject = addslashes($db->getOne(lq("SELECT subject FROM #_MTP_internal_messaging WHERE id='{$context['idparent']}' AND recipient = '{$this->_iduser}'")));
				if($subject)
					$idparent = $db->getOne(lq("SELECT id FROM #_MTP_internal_messaging WHERE subject='{$subject}' AND recipient='{$recipient}' AND recipient = '{$this->_iduser}'"));
				else $idparent=0;
			} else $idparent=0;
			$requetes .= "('{$idparent}', '{$this->_iduser}', '{$recipient}', ':{$context['recipients']}:', '{$context['subject']}', '{$context['body']}', '{$context['cond']}', NOW(), '1'),";
		}
		$requetes = substr_replace($requetes, '', -1);
		$db->execute(lq("INSERT INTO #_MTP_internal_messaging (idparent, iduser, recipient, recipients, subject, body, cond, incom_date, status) 
					VALUES {$requetes}")) 
			or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		unset($context['idparent']);
		update();
		usecurrentdb();
		return "_location: index.php?do=list&lo=internal_messaging&msgsended=1";
	}

	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	protected function _publicfields() 
	{
		return array(
				'recipients' => array('multipleselect', '+'),
				'subject' => array('text', ''),
				'body' => array('longtext', '+'),
				'cond' => array('boolean', '+'));
	}

	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //
	/**
	 * Retourne la liste des champs uniques
	 * @access private
	 */
	protected function _uniqueFields() 
	{ 
		return array();
	}

	// end{uniquefields} automatic generation  //

}// end of Internal_MessagingLogic class