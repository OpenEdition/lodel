<?php
/**
 * Fichier racine - porte d'entrée principale du site
 *
 * Ce fichier permet de faire appel aux différentes entités (documents), via leur id, leur
 * identifier (lien permanent). Il permet aussi d'appeler un template particulier (via l'argument
 * page=)
 * Voici des exemples d'utilisations
 * <code>
 * index.php?/histoire/france/charlemagne-le-pieux
 * index.php?id=48
 * index.php?page=rss20
 * index.php?do=view&idtype=2
 * </code>
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
 * @author Pierre-Alain Mignot
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel/source
 */

require 'siteconfig.php';
// vérifie l'intégrité de l'url demandée
if('path' != URI && preg_match("/^".preg_quote($urlroot.$site, '/')."\/(index|signaler|backend|logout|oai|search)(\d*)\.$extensionscripts(\?[^\/]*)?(\/.*)$/", $_SERVER['REQUEST_URI'])>0) {
	header("HTTP/1.0 403 Bad Request");
	header("Status: 403 Bad Request");
	header("Connection: Close");
	include "../missing.html";
	exit;
}
//gestion de l'authentification
require 'auth.php';
authenticate();
// record the url if logged
if ($lodeluser['rights'] >= LEVEL_VISITOR) {
	recordurl();
}

if ($_POST) {
	$request = &$_POST;
} else {
	$request = &$_GET;
}

$request['idtype'] = (isset($_POST['idtype']) ? intval($_POST['idtype']) : intval($_GET['idtype']));
$request['do'] = ($_POST['do'] ? $_POST['do'] : $_GET['do']);
$request['tpl'] = 'index'; // template by default.
$request['url_retour'] = isset($url_retour) ? strip_tags($url_retour) : null;
// GET ONLY
$request['id'] = intval($_GET['id']);
$request['identifier'] = isset($_GET['identifier']) ? $_GET['identifier'] : null;
$request['file'] = isset($_GET['file']) ? true : false;
$request['page'] = isset($_GET['page']) ? $_GET['page'] : null;
// POST ONLY
$request['login'] = isset($_POST['login']) ? $_POST['login'] : null;
$request['passwd'] = isset($_POST['passwd']) ? $_POST['passwd'] : null;

if (isset($request['do'])) {
	if ($request['do'] == 'edit' || $request['do'] == 'view') {
		// check for the right to change this document
		if (!$request['idtype']) {
			die('ERROR: idtype must be given');
		}

		require 'dao.php';
		$dao = &getDAO('types');
		$vo = $dao->find("id='{$request['idtype']}' and public>0 and status>0");
		if (!$vo) {
			die("ERROR: you are not allowed to add this kind of document");
		}

		$lodeluser['rights']  = LEVEL_EDITOR; // grant temporary
		$lodeluser['editor']  = 1;
		$context['lodeluser'] = $lodeluser;
		$accepted_logic = array('entities_edition');
		$called_logic = 'entities_edition';
	} else {
		die('ERROR: unknown action');
	}
}

require 'controler.php';
$controler = new controler(isset($accepted_logic) ? $accepted_logic : array(), isset($called_logic) ? $called_logic : '', $request);
exit();
?>