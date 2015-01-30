<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier contenant des fontions utilitaires pour le login
 */

defined('INC_CONNECT') || include 'connect.php';

/**
 * Ouverture d'une session
 *
 * @param string $login le nom d'utilisateur
 * @param string $name $nom de la session (optionnel)
 */
function open_session($login, $name = null)
{
	global $db;

	// timeout pour les cookies
	if(!$cookietimeout = C::get('cookietimeout', 'cfg'))
	{
		$cookietimeout = 4 * 3600;
	}

	if(!$timeout = C::get('cookietimeout', 'cfg'))
	{
		$timeout = 120*60;
	}

	// context
	C::setUser($login, 'name');
	// by default, we want the desk
	C::setUser(true, 'desk');
	$expire = $timeout + time();
	$expire2 = time() + $cookietimeout;
	// clean the url - nettoyage de l'url
	$url = preg_replace("/[\?&amp;]clearcache=\w+/", "", $_SERVER['REQUEST_URI']);
	if (get_magic_quotes_gpc()) {
		$url = stripslashes($url);
	}
	$myurl = C::get('norecordurl') ? "''" : $db->qstr($url);

	if(is_null($name))
	{
		for ($i = 0; $i < 5; $i ++)	{ // essaie cinq fois, au cas ou on ait le meme name de session
			// name de la session
			$name = md5($login.uniqid(mt_rand(), true));
			// enregistre la session, si ca marche sort de la boucle
			$result = $db->execute(lq("
        INSERT INTO #_MTP_session (name,iduser,site,context,expire,expire2,userrights,currenturl) 
            VALUES ('$name','".C::get('id', 'lodeluser')."','".C::get('site', 'cfg')."',
                '".addslashes(serialize(C::get(null, 'lodeluser')))."','$expire','$expire2', 
                '".C::get('rights', 'lodeluser')."', ".$myurl.")")) 
        			or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
	
			if ($result) break; // ok, it's working fine
		}
		if ($i == 5)
		{
			C::setUser();
			return "error_opensession";
		}
		if (!@setcookie(C::get('sessionname', 'cfg'), $name, time() + $cookietimeout, C::get('urlroot', 'cfg')))
			trigger_error("Cannot set cookie !", E_USER_ERROR);
	}
	else
	{
		$db->execute(lq("
        UPDATE #_MTP_session 
            SET expire='$expire',currenturl=$myurl 
            WHERE name='$name'")) 
        		or trigger_error($db->errormsg(), E_USER_ERROR);
	}

	C::set('clearcacheurl', mkurl($url, "clearcache=oui"));

	return $name;
}


/**
 * Vérifie que le login et le password sont bon pour le site concerné
 *
 * En plus de vérifier qu'un utilisateur peut se connecter, cette fonction met en variables
 * globales les informations de l'utilisateur
 *
 * @param string $login le nom d'utilisateur
 * @param string &$passwd le mot de passe
 * @param string &$site le site
 * @return boolean un booleen indiquant si l'authentification est valide
 */
function check_auth($login, $passwd)
{
	C::trigger('prelogin');
	global $db;
	do { // block de control
		if (!$login || !$passwd)
			break;

		$lodelusername = addslashes($login);
		$pass = md5($passwd. $login);

		if(C::get('site', 'cfg'))
		{
			$result = $db->execute(lq("
		SELECT * 
			FROM #_TP_users 
			WHERE username='$lodelusername' AND passwd='$pass' AND status>0")) 
				or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			
			$row = $result->fields;
			$result->Close();

			if(!$row)
			{
				$result = $db->execute(lq("
		SELECT * 
			FROM #_MTP_users 
			WHERE username='$lodelusername' AND passwd='$pass' AND status>0")) 
				or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

				$row = $result->fields;
				$result->Close();
				if (!$row) break;
			}
		}
		else
		{
			$result = $db->execute(lq("
		SELECT * 
			FROM #_MTP_users 
			WHERE username='$lodelusername' AND passwd='$pass' AND status>0")) 
				or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

			$row = $result->fields;
			$result->Close();
			if (!$row) break;
		}
        
		// pass les variables en global
		$lodeluser['rights'] = $row['userrights'];
		$lodeluser['lang'] = $row['lang'] ? $row['lang'] : "fr";
		$lodeluser['id'] = $row['id'];
		$lodeluser['gui_complexity'] = $row['gui_user_complexity'];
		$lodeluser['name'] = $row['username'];

		// cherche les groupes pour les non administrateurs
		if (defined("LEVEL_ADMIN") && $lodeluser['rights'] < LEVEL_ADMIN)	{ // defined is useful only for the install.php
			$result = $db->execute(lq("
            SELECT idgroup 
                FROM #_TP_users_usergroups 
                WHERE iduser='".$lodeluser['id']."'")) 
            		or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
            
			$lodeluser['groups'] = "1"; // sont tous dans le groupe "tous"
			while (($row = $result->fields)) {
				$lodeluser['groups'] .= ",".$row['idgroup'];
				$result->MoveNext();
			}
            		$result->Close();
		}	else {
			$lodeluser['groups'] = '';
		}

		C::setUser($lodeluser); // export info into the context
		// efface les donnees de la memoire et protege pour la suite
		$passwd = $pass = $lodeluser = 0;
        	C::set('passwd', null);

		// nettoyage des tables session et urlstack
		if(C::get('adminlodel', 'lodeluser')) {
			$db->execute(lq("
		DELETE FROM #_MTP_session 
			WHERE expire < UNIX_TIMESTAMP() AND expire2 < UNIX_TIMESTAMP()")) 
			or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		
			$db->execute(lq("
		DELETE FROM #_MTP_urlstack 
			WHERE idsession NOT IN (SELECT id FROM #_MTP_session)")) 
			or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}

		if (C::get('admin', 'lodeluser')) 
		{
			if(!function_exists('cleanEntities'))
				include ('entitiesfunc.php');
			cleanEntities(); // nettoyage de la table entities (supprime les entites à -64 modifiées il y a + de 12h)
		}

		C::trigger('postlogin');
		return true;
	}	while (0);
   	$passwd = $pass = 0;
    	C::set('passwd', null);
	C::trigger('postlogin');
	return false;
}

/**
 * Vérifie que le compte d'utilisateur n'a pas été suspendu
 *
 * Si le status de l'utilisateur est égale à 10 (utilisateur suspendu) ou à 11 (utilisateur protégé suspendu), on retourne false, sinon true
 *
 * @return boolean un booleen indiquant si le compte est suspendu (false) ou pas (true)
 */
function check_suspended()
{
	global $db;
	$status = 0;

	if(C::get('site', 'cfg')) {
		C::set('datab', $db->database);
		$status = $db->getOne(lq("
          SELECT status 
            FROM #_TP_users 
            where id = '".C::get('id', 'lodeluser')."' AND username = '".C::get('name', 'lodeluser')."'"));
	}
	
	//on a pas de status. Deux possibilités : soit cest pas la bonne base, soit l'utilisateur n'existe pas (deja vérifié avant, donc exclu)
	if(!$status) {
 		C::set('datab', DATABASE);
		$status = $db->getOne(lq("
          SELECT status 
            FROM #_MTP_users 
            WHERE id = '".C::get('id', 'lodeluser')."' AND username = '".C::get('name', 'lodeluser')."'"));
	}

	if($status == 10 || $status == 11)
		return false;

	return true;
}

/**
 * Modifie le mot de passe apres suspension d'un compte
 *
 * Permet à l'utilisateur ayant un compte suspendu de le réactiver en modifiant son mot de passe
 *
 * @param string $datab base de données à utiliser
 * @param string $login le nom d'utilisateur
 * @param string $old_passwd l'ancien mot de passe
 * @param string $passwd le mot de passe
 * @param string $passwd2 vérif même mot de passe
 * @return string 3 retours possibles : true (mot de passe changé et compte réactivé), false (pas d'utilisateur correspondant), 'error_passwd' (le mot de passe n'est pas au bon format)
 */
function change_passwd($datab, $login, $old_passwd, $passwd, $passwd2)
{
	global $db;

	$log = addslashes($login);
	$datab = addslashes($datab);
	$old_pass = md5($old_passwd . $login);
	$currentdb = $db->database;
	$db->SelectDB($datab);
	$res = $db->getRow("
        SELECT id, status 
            FROM ".$GLOBALS['tableprefix']."users 
            WHERE username = '".$log."' AND passwd = '".$old_pass."'");

	if(!$res)
	{
		$db->SelectDB($currentdb);
		return false;
	} else {
		if($passwd == $passwd2 && $passwd != $old_passwd && strlen($passwd) > 3 && strlen($passwd) < 256 && preg_match("/^[0-9A-Za-z_;.?!@:,&]+$/", $passwd)) {
			$passwd = md5($passwd . $login);
			if($res['status'] == 10)
				$status = 1;
			elseif($res['status'] == 11)
				$status = 32; 
			$db->execute("
            UPDATE ".$GLOBALS['tableprefix']."users 
                SET passwd = '".$passwd."', status = ".$status." 
                WHERE username = '".$log."' AND id = '".$res['id']."'");
			$db->SelectDB($currentdb);
			return true;
		}
		else
		{
			$db->SelectDB($currentdb);
			return "error_passwd";
		}
	}
}

/**
 * Vérifie que le login et le password sont bon pour le site concerné
 * Concerne uniquement les accès restreints côté site
 * En plus de vérifier qu'un utilisateur peut se connecter, cette fonction met en variables
 * globales les informations de l'utilisateur
 *
 * @param string $login le nom d'utilisateur
 * @param string &$passwd le mot de passe
 * @return boolean un booleen indiquant si l'authentification est valide
 */
function check_auth_restricted($login, $passwd)
{
	global $db;
	do { // block de control
		if (!$login || !$passwd)
			break;

		$lodelusername = addslashes($login);
		$pass = md5($passwd. $login);

		$row = $db->getRow(lq("
            SELECT * 
                FROM #_TP_restricted_users 
                WHERE username='$lodelusername' AND passwd='$pass' AND status>0"));
		if (!$row)	{
			break;
		}
		// pass les variables en global
		$lodeluser['rights'] = LEVEL_RESTRICTEDUSER;
		$lodeluser['lang'] = $row['lang'] ? $row['lang'] : "fr";
		$lodeluser['id'] = $row['id'];
 		$lodeluser['name'] = $row['username'];
		$lodeluser['groups'] = '';
		C::setUser($lodeluser);
		// efface les donnees de la memoire et protege pour la suite
		$passwd = $pass = $lodeluser = null;
        	C::set('passwd', null);
		return true;
	}	while (0);
	return false;
}

/**
 * Vérifie que le compte d'utilisateur restreint n'a pas expiré
 *
 * @return boolean un booleen indiquant si le compte est suspendu (false) ou pas (true)
 */
function check_expiration()
{
	global $db;

	C::set('datab', $db->database);
	$status = $db->getOne(lq("
       SELECT expiration 
            FROM #_TP_restricted_users 
            WHERE id = '".C::get('id', 'lodeluser')."' AND username = '".C::get('name', 'lodeluser')."'"));
    	if(!$status) return false;
    
	$status = explode('-', $status);

	if(mktime(23, 59, 0, $status[1], $status[2], $status[0]) <= time())
		return false;
	return true;
}

/**
 * Vérifie la messagerie de l'utilisateur. S'il a un message avec priorité alors on redirige vers la messagerie
 *
 */
function check_internal_messaging()
{
	global $db;
	
    	$url_retour = 'Location: ';
    
	if(defined('backoffice')) {
        	$url_retour .= $db->CacheGetOne(lq('
            SELECT url
                FROM #_MTP_sites
                WHERE name="'.C::get('site', 'cfg').'"')) or trigger_error($db->errormsg(), E_USER_ERROR);

		$url_retour .= '/lodel/admin/index.php?do=list&lo=internal_messaging';
	} elseif(defined('backoffice-lodeladmin')) {
		$url_retour .= 'index.php?do=list&lo=internal_messaging';
	} else {
		// où sommes-nous ??
		return false;
	}
    
	$lodeluserid = (int)C::get('rights', 'lodeluser') !== 128 ? 
			C::get('site','cfg').'-'.C::get('id', 'lodeluser') : 
			'lodelmain-'.C::get('id', 'lodeluser');
	$msg = $db->getOne(lq("
            SELECT count(id) as nbMsg 
                FROM #_MTP_internal_messaging 
                WHERE recipient = '{$lodeluserid}' AND status = '1' AND cond = '1'"));
    
	if($msg) {
		header($url_retour);
		exit();
	}
}

function updateDeskDisplayInSession()
{
	global $db;
	
	$user = C::get(null, 'lodeluser');
	if(!$user['visitor']) return 'error';
	$idsession = $user['idsession'];
	$row = $db->getRow(lq("SELECT context FROM #_MTP_session WHERE id='{$idsession}'"));
	if(!$row) return 'error';
	$localcontext = unserialize($row['context']);
	$localcontext['desk'] = (TRUE !== $localcontext['desk']) ? true : false;
	$localcontext = addslashes(serialize($localcontext));
	if(!$db->execute(lq("UPDATE #_MTP_session SET context = '{$localcontext}' WHERE id='{$idsession}'"))) return 'error';
	return 'ok';
}
