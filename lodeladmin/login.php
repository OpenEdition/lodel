<?php
/**
 * Fichier login de lodeladmin - Ce fichier est quasiment identique au fichier login.php dans
 * le package lodel/source
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
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodeladmin
 */
define('backoffice-lodeladmin', true);
require 'lodelconfig.php';

require 'class.errors.php';
set_error_handler(array('LodelException', 'exception_error_handler'));

// les niveaux d'erreur à afficher
error_reporting(E_ALL);

try
{
	require 'auth.php';
	
	$url_retour = strip_tags($url_retour);
	
	if($_POST['passwd'] && $_POST['passwd2'] && $_POST['login']) {
		require 'func.php';
		extract_post();
		require 'loginfunc.php';
		unset($retour);
		$retour = change_passwd($_POST['datab'], $_POST['login'], $_POST['old_passwd'], $_POST['passwd'], $_POST['passwd2']);
		if($retour === "error_passwd") {
			$context['suspended'] = 1;
		} elseif($retour === false) {	
			$context['error_login'] = 1;
		} elseif($retour === true) {
			// on relance la procédure d'identification
			if (!check_auth($_POST['login'], $_POST['passwd'], $site)) {
				$context['error_login'] = 1;
			} else {
				// et on ouvre une session
				$err = open_session($_POST['login']);
				if ((string)$err === 'error_opensession') {
					$context[$err] = 1;
					break;
				} else {
					check_internal_messaging();
					header ("Location: http://". $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT'] != 80 ? ':'. $_SERVER['SERVER_PORT'] : ''). $context['url_retour']);
				}
			}
		}
	} elseif ($_POST['login']) {
		require 'func.php';
		extract_post();
		do {
			require 'loginfunc.php';
			if (!check_auth($context['login'], $context['passwd'], $site)) {
				$context['error_login'] = 1; 
				break;
			}
			
			//vérifie que le compte n'est pas en suspend. Si c'est le cas, on amène l'utilisateur à modifier son mdp, sinon on l'identifie
			if(!check_suspended()) {
				$context['suspended'] = 1;
				break;
			}
			else {
				// ouvre une session
				$err = open_session($context['login']);
				if ((string)$err === 'error_opensession') {
					$context[$err] = 1;
					break;
				}
			}
			check_internal_messaging();
			header ('Location: http://'. $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT'] ? ':'. $_SERVER['SERVER_PORT'] : ''). $context['url_retour']);
			die ();
		} while (0);
	}
	
	$context['passwd'] = $passwd = 0;
	// commenté le 13/11/08 par pierre-alain, aucune utilité trouvée ?
	// variable: sitebloque
	// if ($context['error_sitebloque']) { // on a deja verifie que la site est bloque.
	// 	$context['sitebloque'] = 1;
	// } else { // test si la site est bloque dans la DB.
	// 	require_once 'connect.php';
	// 	usemaindb();
	// 	$context['sitebloque'] = $db->getOne(lq("SELECT 1 FROM #_MTP_sites WHERE name='$site' AND status>=32"));
	// 	usecurrentdb();
	// }
	
	
	$context['url_retour']      = $url_retour;
	$context['error_timeout']   = $error_timeout;
	$context['error_privilege'] = $error_privilege;
	
	require 'view.php';
	$view = &View::getView();
	$view->render($context, 'login');
}
catch(Exception $e)
{
	if(!headers_sent())
	{
		header("HTTP/1.0 403 Internal Error");
		header("Status: 403 Internal Error");
		header("Connection: Close");
	}
	echo $e->getContent();
	exit();
}
?>