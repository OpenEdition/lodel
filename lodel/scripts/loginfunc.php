<?php
/**
 * Fichier contenant des fontions utilitaires pour le login
 *
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 *
 * Home page: http://www.lodel.org
 *
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
 * @author Ghislain Picard
 * @author Jean Lamy
 * @author Pierre-Alain MIGNOT
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */

/**
 * Ouverture d'une session
 *
 * @param string $login le nom d'utilisateur
 */
function open_session($login)
{
	global $lodeluser, $sessionname, $timeout, $cookietimeout;
	global $db, $urlroot, $site;

	// timeout pour les cookies
	if (!$cookietimeout)
		$cookietimeout = 4 * 3600; // to ensure compatibility

	// context
	$lodeluser['name'] = $login;

	$contextstr = addslashes(serialize($lodeluser));
	$expire = time() + $timeout;
	$expire2 = time() + $cookietimeout;

	usemaindb();

	for ($i = 0; $i < 5; $i ++)	{ // essaie cinq fois, au cas ou on ait le meme name de session
		// name de la session
		$name = md5($login.microtime());
		// enregistre la session, si ca marche sort de la boucle
		$result = $db->execute(lq("INSERT INTO #_MTP_session (name,iduser,site,context,expire,expire2) VALUES ('$name','".$lodeluser['id']."','$site','$contextstr','$expire','$expire2')")) or dberror();
		if ($result)
			break; // ok, it's working fine
	}

	if ($i == 5)
		return "error_opensession";
	if (!setcookie($sessionname, $name, time() + $cookietimeout, $urlroot))
		die("Probleme avec setcookie... probablement du texte avant");

	usecurrentdb();
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
function check_auth($login, & $passwd, & $site)
{
	global $db, $context, $lodeluser, $home;
	do { // block de control
		if (!$login || !$passwd)
			break;

		$lodelusername = addslashes($login);
		$pass = md5($passwd. $login);
		// cherche d'abord dans la base generale.

		usemaindb();
		$result = $db->execute(lq("SELECT * FROM #_MTP_users WHERE username='$lodelusername' AND passwd='$pass' AND status>0")) or dberror();
		usecurrentdb();

		if (($row = $result->fields))	{

			// le user est dans la base generale
			$site = "";
		}	elseif ($GLOBALS['currentdb'] && $GLOBALS['currentdb'] != DATABASE)	{ // le user n'est pas dans la base generale
			if (!$site)
				break; // si $site n'est pas definie on s'ejecte
			// cherche ensuite dans la base du site
			$result = $db->execute(lq("SELECT * FROM #_TP_users WHERE username='$lodelusername' AND passwd='$pass' AND status>0")) or dberror();
			if (!($row = $result->fields))
				break;
		}	else {
			break; // on s'ejecte
		}
		
		// pass les variables en global
		$lodeluser['rights'] = $row['userrights'];
		$lodeluser['lang'] = $row['lang'] ? $row['lang'] : "fr";
		$lodeluser['id'] = $row['id'];
		$lodeluser['gui_complexity'] = $row['gui_user_complexity'];

		// cherche les groupes pour les non administrateurs
		if (defined("LEVEL_ADMIN") && $lodeluser['rights'] < LEVEL_ADMIN)	{ // defined is useful only for the install.php
			$result = $db->execute(lq("SELECT idgroup FROM #_TP_users_usergroups WHERE iduser='".$lodeluser['id']."'")) or dberror();
			$lodeluser['groups'] = "1"; // sont tous dans le groupe "tous"
			while (($row = $result->fields)) {
				$lodeluser['groups'] .= ",".$row['idgroup'];
				$result->MoveNext();
			}
		}	else {
			$lodeluser['groups'] = '';
		}

		$context['lodeluser'] = $lodeluser; // export info into the context

		// efface les donnees de la memoire et protege pour la suite
		$passwd = 0;
		return true;
	}	while (0);
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
	global $context, $db;
	if($context['site'] != '') {
		$prefixe = "#_TP_";
		usecurrentdb();
		$context['datab'] = $db->database;
	}
	else {
		$prefixe = "#_MTP_";
		usemaindb();
	}
	$status = $db->getOne(lq("SELECT status FROM ".$prefixe."users where id = '".$context['lodeluser']['id']."' AND username = '".$context['login']."'"));
	//on a pas de status. Deux possibilités : soit cest pas la bonne base, soit l'utilisateur n'existe pas (deja vérifié avant, donc exclu)
	if(!$status) {
		usemaindb();
 		$context['datab'] = $db->database;
		$status = $db->getOne(lq("SELECT status FROM #_MTP_users where id = '".$context['lodeluser']['id']."' AND username = '".$context['login']."'"));
	}
	usecurrentdb();
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
	global $db, $context;

	$log = addslashes($login);
	$datab = addslashes($datab);
	$old_pass = md5($old_passwd . $login);
	mysql_select_db($datab);
	$res = $db->getall("SELECT id, status FROM ".$GLOBALS['tableprefix']."users WHERE username = '".$log."' AND passwd = '".$old_pass."'");

	if(!$res[0])
		return false;
	else {
		if($passwd == $passwd2 && $passwd != $old_passwd && strlen($passwd) > 3 && strlen($passwd) < 256 && preg_match("/^[0-9A-Za-z_;.?!@:,&]+$/", $passwd)) {
			$passwd = md5($passwd . $login);
			if($res[0]['status'] == 10)
				$status = 1;
			elseif($res[0]['status'] == 11)
				$status = 32; 
			$db->execute("UPDATE ".$GLOBALS['tableprefix']."users SET passwd = '".$passwd."', status = ".$status." WHERE username = '".$log."' AND id = '".$res[0]['id']."'");
			return true;
		}
		else
			return "error_passwd";
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
 * @param string &$site le site
 * @return boolean un booleen indiquant si l'authentification est valide
 */
function check_auth_restricted($login, & $passwd)
{
	global $db, $context, $lodeluser, $home;
	do { // block de control
		if (!$login || !$passwd)
			break;

		$lodelusername = addslashes($login);
		$pass = md5($passwd. $login);

		usecurrentdb();
		$result = $db->execute(lq("SELECT * FROM #_TP_restricted_users WHERE username='$lodelusername' AND passwd='$pass' AND status>0")) or dberror();
		if (!($row = $result->fields))	{
			break;
		}
		// pass les variables en global
		$lodeluser['rights'] = $row['userrights'];
		$lodeluser['lang'] = $row['lang'] ? $row['lang'] : "fr";
		$lodeluser['id'] = $row['id'];
 		$lodeluser['name'] = $row['username'];
		$lodeluser['groups'] = '';
		$context['lodeluser'] = $lodeluser; // export info into the context

		// efface les donnees de la memoire et protege pour la suite
		$passwd = 0;
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
	global $context, $db;

	usecurrentdb();
	$context['datab'] = $db->database;
	$status = $db->getOne(lq("SELECT expiration FROM #_TP_restricted_users where id = '".$context['lodeluser']['id']."' AND username = '".$context['login']."'"));
	$status = explode('-', $status);
	$time = time();
	$status = mktime(23, 59, 0, $status[1], $status[2], $status[0]);

	if($status <= $time)
		return false;
	return true;
}

?>
