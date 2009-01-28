<?php
/**
 * Fichier racine de lodel/edition
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
 * @package lodel/source/lodel/edition
 */


define('backoffice', true);
require 'siteconfig.php';
require 'class.errors.php';
set_error_handler(array('LodelException', 'exception_error_handler'));

// les niveaux d'erreur à afficher
error_reporting(E_ALL);

try
{
require 'auth.php';

// Authentification HTTP pour les flux RSS coté édition (flux du tableau de bord) : Cf. auth.php
if ($_GET['page'] == 'backend' && $_GET['format'] ) {
	authenticate(LEVEL_VISITOR, 'HTTP');
	}
else {
	authenticate(LEVEL_VISITOR);
	}

if (!$_GET['do'] && !$_POST['do'] && !$_GET['lo'] && !$_POST['lo']) {
	if ($lodeluser['rights'] >= LEVEL_ADMIN) {
		if(!function_exists('cleanEntities'))
			require ('entitiesfunc.php');
		cleanEntities(); // nettoyage de la table entities (supprime les entites à -64 modifiées il y a + de 12h)
	}
	recordurl();
	$context['id'] = $id = (int)$_GET['id'];
	if(!class_exists('View', false))
		require 'view.php';
	$view = &View::getView();

	if ($id) {
		do {
			$row = $db->getRow(lq("SELECT tpledition,idparent,idtype FROM #_entitiestypesjoin_ WHERE #_TP_entities.id='$id'"));
			if ($row === false) {
				trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
			if (!$row) {
				header ("Location: not-found.html");
				return;
			}
			$base              = $row['tpledition'];
			$idparent          = $row['idparent'];
			$context['idtype'] =$row['idtype'];
			if (!$base) {
				$context['id'] = $id = $idparent;
			}
		} while (!$base && $idparent);
	} else {
		if ($_GET['page']) { // call a special page (and template)
			$base = $_GET['page'];
			if (strlen($base) > 64 || preg_match("/[^a-zA-Z0-9_\/-]/", $base)) {
				trigger_error("invalid page", E_USER_ERROR);
			}
		} else {
			$base = 'edition';
		}
	}
	$view->renderCached($context, $base);
	return;
} else {
	
	require 'controller.php';
	// automatic logic
	$do = $_GET['do'] ? $_GET['do'] : $_POST['do'];
	$lo = $_GET['lo'] ? $_GET['lo'] : $_POST['lo'];
	
	if(!$lo) {
		switch ($do) { // Detection automatique de la logique en fonction de l'action
			case 'move':
			case 'preparemove':
			case 'changestatus':
			case 'download':
				$lo = 'entities_advanced';
				break;
			case 'cleanIndex':
			case 'deleteIndex':
			case 'addIndex':
				$lo = 'entities_index';
				break;
			case 'view':
			case 'edit':
				$lo = 'entities_edition';
				break;
			case 'import':
				$lo = 'entities_import';
				break;
			default :
				$lo = 'entities';
				break;
		}
	}
	
	$Controler = new Controller(array('entities', 'entities_advanced', 'entities_edition', 'entities_import', 'entities_index', 'filebrowser',	'tasks', 'xml', 'users'), $lo);
}
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