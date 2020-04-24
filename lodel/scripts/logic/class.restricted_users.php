<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Logique des utilisateurs restreints
 */


/**
 * Classe de logique des utilisateurs restreints
 */
class Restricted_UsersLogic extends Logic 
{
	/** Constructor
	*/
	public function __construct() 
	{
		parent::__construct('restricted_users');
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
		//) { $error["error_has_entities"]=$count; return "_back"; }
	}

	/**
	 * Implémenation de l'action d'ajout ou d'édition d'un objet.
	 *
	 * add/edit Action
	 * @param array $context le tableau des données passé par référence.
	 * @param array $error le tableau des erreurs rencontrées passé par référence.
	 * @param boolean $clean false si on ne doit pas nettoyer les données (par défaut à false).
	 * @return string les différentes valeurs possibles de retour d'une action (_ok, _back, _error ou xxxx).
	 */
	public function editAction(&$context, &$error, $clean=false)
	{
		$ret   = parent::editAction($context, $error);
		cache_delete('no_restricted');
		return $ret;
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
		
		$session = (int) C::get('session', 'lodeluser');

		$ids = array();
		if (!empty($context['id'])) { //suppression de toutes les sessions
			$id = (int) $context['id'];
			$result = $db->execute(lq("SELECT id FROM #_MTP_session WHERE iduser='".$id."'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			while(!$result->EOF) {
				$ids[] = $result->fields['id'];
				$result->MoveNext();
			}
		} elseif (!empty($session)) { //suppression d'une session
			$ids[] = $session;
		} else {
			trigger_error('ERROR: unknown operation', E_USER_ERROR);
		}
		
		if ($ids) {
			$idstr = join(',', $ids);
			// remove the session
			$db->execute(lq("DELETE FROM #_MTP_session WHERE id IN ($idstr)")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			// remove the url related to the session
			$db->execute(lq("DELETE FROM #_MTP_urlstack WHERE idsession IN ($idstr)")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}

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
		
		if (!empty($context['lang'])) {
			$lang = $context['lang'];
			if (!preg_match("/^\w\w(-\w\w)?$/",$lang)) {
				trigger_error("ERROR: invalid lang", E_USER_ERROR);
			}
			$db->execute(lq("UPDATE #_TP_restricted_users SET lang='$lang'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			$this->_setcontext('lang', 'setvalue', $lang);
		}

		if (!empty($context['translationmode'])) {
			$translationmode = $context['translationmode'];
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
			case 'userrights':
				break;
			case "lang" :
			// get the language available in the interface
			$dao=DAO::getDAO("translations");
			$list=$dao->findMany("status>0 AND textgroups='interface'","rank,lang","lang,title");
			$arr=array();
			foreach($list as $lang) {
				$arr[$lang->lang]=$lang->title;
			}
			if (!$arr) $arr['fr']="Français";
			renderOptions($arr,$context['sitelang']);
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
		$where = "name='". addslashes($_COOKIE[C::get('sessionname', 'cfg')]). "' AND iduser='". (int)C::get('id', 'lodeluser'). "'";
		$context = $db->getOne(lq("SELECT context FROM $GLOBALS[tp]session WHERE ".$where));
		if ($db->errorno()) {
			trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}
		$arr = unserialize($context);
		switch ($operation) {
		case 'toggle' :
			$arr[$var] = $arr[$var] ? 0 : 1; // toggle
			break;
		case 'setvalue' :
			$arr[$var] = $value;  // set
			break;
		case 'clear' :
			unset($arr[$var]);  // clear
			break;
		}
	
		$db->execute(lq("UPDATE #_MTP_session SET context='". addslashes(serialize($arr)). "' WHERE ".$where)) 
			or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
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
			$context['tmppasswd'] = $context['passwd'];
			$context['passwd'] = md5($context['passwd'].$context['username']);
		}
	}



	protected function _populateContextRelatedTables($vo,&$context)
	{
	/*	if ($vo->userrights<=LEVEL_EDITOR) {
			$dao=&getDAO("users_usergroups");
			$list=$dao->findMany("iduser='". $vo->id."'", "", "idgroup");
			$context['usergroups']=array();
			foreach($list as $relationobj) {
				$context['usergroups'][] = $relationobj->idgroup;
			}
		}
	*/
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
	{/*
		global $db;
		if ($vo->userrights<=LEVEL_EDITOR) {
			if (!$context['usergroups']) $context['usergroups']=array(1);

			// change the usergroups     
			// first delete the group
			$this->_deleteRelatedTables($vo->id);
			// now add the usergroups
			foreach ($context['usergroups'] as $usergroup) {
	$usergroup=intval($usergroup);
	$db->execute(lq("INSERT INTO #_TP_users_usergroups (idgroup, iduser) VALUES  ('$usergroup','$id')")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
		}*/
	}

	protected function _deleteRelatedTables($id) {/*
		global $db;
		if ($GLOBALS['site']) { // only in the site table
			$db->execute(lq("DELETE FROM #_TP_users_usergroups WHERE iduser='$id'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}*/
	}


	public function validateFields(&$context,&$error) 
	{
		global $db;

		if (!parent::validateFields($context,$error)) return false;
		// check the user has the right equal or higher to the new user
		if (C::get('rights', 'lodeluser') < $context['userrights'])
			trigger_error("ERROR: You don't have the right to create a user with rights higher than yours", E_USER_ERROR);

		usecurrentdb();
		// check the passwd is given for new user.
		if (empty($context['id']) && (empty($context['passwd']) || !trim($context['passwd']))) {
			$error['passwd']=1;
			return false;
		}

		return true;
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
									'email' => array('email', ''),
									'expiration' => array('date', '+'),
									'ip' => array('text', ''),
									'lang' => array('lang', '+'));
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
	 * Envoi un mail au nouvel utilisateur créé avec son login/mdp et diverses informations
	 */
	protected function _sendPrivateInformation(&$context) 
	{
		global $db;
		if(empty($context['tmppasswd'])) return;
		$row = $db->getRow(lq("SELECT url, title FROM #_MTP_sites WHERE name = '{$context['site']}'"));
		if(!$row) trigger_error('Error while getting url and title of site for new user mailing', E_USER_ERROR);
		$context['siteurl'] = str_replace(":80", "", $row['url']);
		$context['sitetitle'] = $row['title'];
		$prefix = C::get('adminlodel', 'lodeluser') ? lq("#_MTP_") : lq("#_TP_");
		$email = $db->getOne("SELECT email FROM {$prefix}users WHERE id = '{$context['lodeluser']['id']}'");
		if(!$email) trigger_error('Error while getting your email for new user mailing', E_USER_ERROR);
		$GLOBALS['nodesk'] = true;
		$context['restricted'] = true;
		$nocache = View::$nocache;
		View::$nocache = true;
        $context['adminemail'] = $email;
		ob_start();
		insert_template($context, 'users_mail', "", SITEROOT."lodel/admin/tpl/");
		$body = ob_get_clean();
		View::$nocache = $nocache;
		unset($context['tmppasswd']);
		return send_mail($context['email'], $body, "Votre compte abonné sur le site ". $context['sitetitle'] ." ({$context['siteurl']})", $email, '');
	}
} // class 
