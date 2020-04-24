<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Logique des utilisateurs
 */


/**
 * Classe de logique des utilisateurs
 *
 */
class UsersLogic extends Logic 
{

	/** Constructor
	*/
	public function __construct() 
	{
		parent::__construct('users');
	}

	/**
	*  Indique si un objet est protégé en suppression
	*
	* Cette méthode indique si un objet, identifié par son identifiant numérique et
	* éventuellement son status, ne peut pas être supprimé. Dans le cas où un objet ne serait
	* pas supprimable un message est retourné indiquant la cause. Sinon la méthode renvoit le
	* booleen false.
	*
	* @param integer $id identifiant de l'objet
	* @param integer $status status de l'objet
	* @return false si l'objet n'est pas protégé en suppression, un message sinon
	*/
	public function isdeletelocked($id,$status=0) 
	{
		$id = (int)$id;
		$userid = (int)C::get('id', 'lodeluser');
		if ($userid===$id && 
		( (C::get('site', 'cfg') && !C::get('adminlodel', 'lodeluser')) ||
		(!C::get('site', 'cfg') && C::get('adminlodel', 'lodeluser')))) {
			return getlodeltextcontents("cannot_delete_current_user","common");
		} else {
			return false;
		}
	}


	/**
	 * Suppression du log des sessions d'un utilisateur
	 *
	 * Cette action permet de supprimer soit :
	 * - toutes les sessions d'un utilisateur, do=deletesession&lo=users&id=xx
	 * - une session particulière : do=deletesession&lo=users&session=xx
	 *
	 * @param array $context le contexte passé par référence
	 * @param array $error les erreur éventuelles par référence
	 */
	public function deletesessionAction(&$context, &$error)
	{
		global $db;
		
		$session     = (int)C::get('session', 'lodeluser');
		usemaindb(); // les sessions sont stockés dans la base principale
		$ids = array();
		$where = defined('backoffice-lodeladmin') ? ' AND userrights=128' : ' AND userrights<128';
		if (!empty($context['id'])) { //suppression de toutes les sessions
			$id = (int)@$context['id'];
			$result = $db->execute(lq("SELECT id FROM #_MTP_session WHERE iduser='".$id."' {$where}")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			while(!$result->EOF) {
				$ids[] = $result->fields['id'];
				$result->MoveNext();
			}
		} elseif ($session) { //suppression d'une session
			$ids[] = $session;
		} else {
			trigger_error('ERROR: unknown operation', E_USER_ERROR);
		}
		
		if (!empty($ids)) {
			$idstr = join(',', $ids);
			// remove the session
			$db->execute(lq("DELETE FROM #_MTP_session WHERE id IN ($idstr)")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			// remove the url related to the session
			$db->execute(lq("DELETE FROM #_MTP_urlstack WHERE idsession IN ($idstr)")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}

		usecurrentdb();
		update();
		return '_back';
	}

	/**
	 * Permet de régler la langue ou le mode traduction d'un utilisateur
	 *
	 * Pour changer la langue d'un utilisateur : lo=users&do=set&lang=fr
	 * Pour changer le mode traduction : lo=users&do=set&translationmode=off
	 * @param array $context le contexte passé par référence
	 * @param array $error les erreur éventuelles par référence
	 */
	public function setAction(&$context, &$error)
	{
		global $db;
		$lang = C::get('lang');
		$translationmode = C::get('translationmode');
		if ($lang) {
			if (!preg_match("/^\w\w(-\w\w)?$/",$lang)) {
				trigger_error("ERROR: invalid lang", E_USER_ERROR);
			}
			$prefix = C::get('adminlodel', 'lodeluser') ? '#_MTP_' : '#_TP_';
			$db->execute(lq("UPDATE {$prefix}users SET lang='$lang' WHERE id=".C::get('id', 'lodeluser'))) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			$this->_setcontext('lang', 'setvalue', $lang);
			setcookie('language', $lang, 0, C::get('urlroot', 'cfg'));
		}

		if ($translationmode) {
			switch($translationmode) {
			case 'off':
				$this->_setcontext('translationmode', 'clear');
				break;
			case 'site':
			case 'interface':
				$this->_setcontext('translationmode', 'setvalue', $translationmode);
				break;
			}
		}

		update();
		return '_back';
	}



	/**
		* make the select for this logic
		*/
	public function makeSelect(&$context,$var)
	{
		switch($var) {
		case 'usergroups' :
			$list=DAO::getDAO("usergroups")->findMany("status>0","rank,name","id,name");
			$arr=array();
			foreach($list as $group) {
				$arr[$group->id]=$group->name;
			}
			if (!$arr) $arr[1] = '--';
			renderOptions($arr,isset($context['usergroups']) ? $context['usergroups'] : '');
			break;
		case 'gui_user_complexity' :
			function_exists('makeSelectGuiUserComplexity') || include("commonselect.php");
			makeSelectGuiUserComplexity(isset($context['gui_user_complexity']) ? $context['gui_user_complexity'] : '');
			break;
		case 'userrights':
			function_exists('makeSelectUserRights') || include("commonselect.php");
			makeSelectUserRights(isset($context['userrights']) ? $context['userrights'] : '',!C::get('site', 'cfg') || SINGLESITE);
			break;
		case "lang" :
			// get the language available in the interface
			usemaindb();
			$list=DAO::getDAO("translations")->findMany("status>0 AND textgroups='interface'","rank,lang","lang,title");
			usecurrentdb();
			$arr=array();
            foreach($list as $lang) {
                $arr[$lang->lang]=$lang->title;
			}
			if (!$arr) $arr['fr']="Français";
			renderOptions($arr,isset($context['lang']) ? $context['lang'] : $context['sitelang']);
		}
	}

	/*---------------------------------------------------------------*/
	//! Private or protected from this point
	/**
		* @private
		*/
	/**
	 *
	 *
	 */
	protected function _setcontext($var, $operation, $value = '')
	{
		global $db;
		$where = "name='". addslashes($_COOKIE[C::get('sessionname', 'cfg')]). "' AND iduser='". C::get('id', 'lodeluser'). "' AND userrights=".C::get('rights', 'lodeluser');
		$context = $db->getOne(lq("SELECT context FROM #_MTP_session WHERE ".$where));
		if ($db->errorno()) {
			trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}
		$arr = unserialize($context);
		switch ($operation) {
		case 'toggle' :
			$arr[$var] = (isset($arr[$var]) && $arr[$var]) ? 0 : 1; // toggle
			break;
		case 'setvalue' :
			$arr[$var] = $value;  // set
			break;
		case 'clear' :
			unset($arr[$var]);  // clear
			break;
		}

		$db->execute(lq("UPDATE #_MTP_session SET context='". addslashes(serialize($arr)). "' WHERE ".$where)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
	}

	/**
	* Préparation de l'action Edit
	*
	* @access private
	* @param object $dao la DAO utilisée
	* @param array &$context le context passé par référence
	*/
	protected function _prepareEdit($dao,&$context) 
	{
		// encode the password
		if (!empty($context['passwd']) && !empty($context['username'])) {
			$context['tmppasswd'] = $context['passwd']; // backup it in memory just a few, for emailing
			$context['passwd']=md5($context['passwd'].$context['username']);
		}
	}



	protected function _populateContextRelatedTables($vo,&$context)
	{
		if ($vo->userrights<=LEVEL_EDITOR) {
			$list=DAO::getDAO("users_usergroups")->findMany("iduser='". $vo->id."'", "", "idgroup");
			$context['usergroups']=array();
			foreach($list as $relationobj) {
				$context['usergroups'][] = $relationobj->idgroup;
			}
		}
	}
	/**
	* Sauve des données dans des tables liées éventuellement
	*
	* Appelé par editAction pour effectuer des opérations supplémentaires de sauvegarde.
	*
	* @param object $vo l'objet qui a été créé
	* @param array $context le contexte
	*/
	protected function _saveRelatedTables($vo,&$context) 
	{
		global $db;
		if ($vo->userrights<=LEVEL_EDITOR) {
			if (empty($context['usergroups'])) $context['usergroups']=array(1);

			// change the usergroups     
			// first delete the group
			$this->_deleteRelatedTables($vo->id);
			if(!empty($context['id']))
			{
				$id = (int) $context['id'];
				// now add the usergroups
				foreach ($context['usergroups'] as $usergroup) {
					$usergroup = (int) $usergroup;
					$db->execute(lq("INSERT INTO #_TP_users_usergroups (idgroup, iduser) VALUES  ('$usergroup','$id')")) 
						or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				}
			}
		}
	}

	protected function _deleteRelatedTables($id) {
		global $db;
		if (C::get('site', 'cfg')) { // only in the site table
			$id = (int)$id;
			$db->execute(lq("DELETE FROM #_TP_users_usergroups WHERE iduser='$id'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}
	}


	public function validateFields(&$context,&$error) 
	{
		global $db;

		if (!parent::validateFields($context,$error)) return false;
		// check the user has the right equal or higher to the new user
		if (!isset($context['userrights']) || C::get('rights', 'lodeluser')<$context['userrights']) trigger_error("ERROR: You don't have the right to create a user with rights higher than yours", E_USER_ERROR);

		// Check the user is not duplicated in the main table...
//		if (!usemaindb()) return true; // use the main db, return if it is the same as the current one.

//		$ret=$db->getOne("SELECT 1 FROM ".lq("#_TP_".$this->maintable)." WHERE status>-64 AND id!='".$context['id']."' AND username='".$context['username']."'");
//		if ($db->errorno()) trigger_error($this->errormsg(), E_USER_ERROR);
//		usecurrentdb();

		// check the passwd is given for new user.
		if (empty($context['id']) && (empty($context['passwd']) || !trim($context['passwd']))) {
			$error['passwd']=1;
			return false;
		}

// 		if ($ret) {
// 			$error['username']="1"; // report the error on the first field
// 			return false;
// 		} else {
			return true;
// 		}
	}


	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	protected function _publicfields() 
	{
		return array('username' => array('username', '+'),
									'passwd' => array('passwd', ''),
									'lastname' => array('text', ''),
									'firstname' => array('text', ''),
									'email' => array('email', '+'),
									'lang' => array('lang', '+'),
									'userrights' => array('select', '+'),
									'gui_user_complexity' => array('select', '+'),
									'nickname' => array('text', ''),
									'biography' => array('longtext', ''),
									'photo' => array('image', ''),
									'professional_website' => array('text', ''),
									'url_professional_website' => array('url', ''),
									'rss_professional_website' => array('url', ''),
									'personal_website' => array('text', ''),
									'url_personal_website' => array('url', ''),
									'rss_personal_website' => array('url', ''),
									'pgp_key' => array('longtext', ''),
									'alternate_email' => array('email', ''),
									'phonenumber' => array('text', ''),
									'im_identifier' => array('text', ''),
									'im_name' => array('text', ''));
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	/**
	 * Retourne la liste des champs uniques
	 * @access private
	 */
	protected function _uniqueFields() 
	{ 
		return array(array('username'), );
	}
	// end{uniquefields} automatic generation  //

	/**
	 * Bloque un compte utilisateur tant que celui-ci n'a pas modifié son mot de passe
	 */

	public function suspendAction(&$context, &$error)
	{
 		global $db;
		if(empty($context['id']))
			trigger_error('ERROR: missing id in UsersLogic::suspendAction', E_USER_ERROR);

		$id = (int) $context['id'];
		$site = C::get('site', 'cfg');
		//on vérifie qu'on est bien administrateur
		if(C::get('admin', 'lodeluser')) {
			$prefixe = ($site != '' && $site != "tous les sites") ? "#_TP_" : "#_MTP_";
	
			$status = $db->getOne(lq("SELECT status FROM ".$prefixe."users WHERE id = '".$id."'"));
	
			if($status != -40 && $status != 32)
				$stat = 10;
			else
				$stat = 11;
			
			$db->execute(lq("UPDATE ".$prefixe."users SET status = ".$stat." WHERE id = '".$id."'"));
		} else
			trigger_error("ERROR : You don't have permissions to suspend this user. Contact your administrator.", E_USER_ERROR);
		
		update();
		return "_back";
	}

	/**
	 * Envoi un mail au nouvel utilisateur créé avec son login/mdp et diverses informations
	 */
	protected function _sendPrivateInformation(&$context) 
	{
		global $db;
		if(empty($context['tmppasswd'])) return;
		$site = C::get('site', 'cfg');
		if($site) {
			$row = $db->getRow(lq("SELECT url, title FROM #_MTP_sites WHERE name = '".$site."'"));
			if(!$row) trigger_error('Error while getting url and title of site for new user mailing', E_USER_ERROR);
			$context['siteurl'] = str_replace(":80", "", $row['url']);
			$context['sitetitle'] = $row['title'];
			$context['islodeladmin'] = false;
		} else { // lodeladmin
			$context['siteurl'] = 'http://'. $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT']!=80 ? ':'. $_SERVER['SERVER_PORT'] : '').dirname($_SERVER['REQUEST_URI']);
			$context['sitetitle'] = $context['siteurl'];
			$context['islodeladmin'] = true;
		}
		$prefix = C::get('adminlodel', 'lodeluser') ? lq("#_MTP_") : lq("#_TP_");
		$email = $db->getOne("SELECT email FROM {$prefix}users WHERE id = '".C::get('id', 'lodeluser')."'");
		//if(!$email) trigger_error('Error while getting your email for new user mailing', E_USER_ERROR);
		if(!$email) return;
		$GLOBALS['nodesk'] = true;
		$nocache = View::$nocache;
		View::$nocache = true;
        $context['adminemail'] = $email;
		if($context['lang'] != $context['sitelang'])
		{
			$sitelang = $context['sitelang'];
			$context['sitelang'] = $context['lang'];
		}
		ob_start();
		insert_template($context, 'users_mail', "", (!$context['islodeladmin'] ? SITEROOT."lodel/admin/tpl/" : ''));
		$body = ob_get_clean();
		View::$nocache = $nocache;
		$context['tmppasswd'] = null;
		if(isset($sitelang)) $context['sitelang'] = $sitelang;
		return send_mail($context['email'], $body, getlodeltextcontents("Your Lodel account", 'admin', $context['lang']).(!$context['islodeladmin'] ? ' '.getlodeltextcontents("on the website", 'admin', $context['lang'])." '{$context['sitetitle']}' ({$context['siteurl']})" : ''), $email, '');
	}
} // class 
