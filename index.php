<?php
/**
 * Fichier racine
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
 * @package lodel
 */


if (!file_exists("lodelconfig.php")) {
	header('location: lodeladmin-0.9/install.php');
	exit;
}


if (file_exists("siteconfig.php")) {
	require 'siteconfig.php';
} else {
	require 'lodelconfig.php';
	ini_set('include_path', $home. PATH_SEPARATOR. ini_get('include_path'));
}
if('path' != URI && preg_match("/^".preg_quote($urlroot.$site, '/')."\/index(\d*).$extensionscripts(\?[^\/]*)?(\/.+)$/", $_SERVER['REQUEST_URI'])>0) {
	header("HTTP/1.0 403 Bad Request");
	header("Status: 403 Bad Request");
	header("Connection: Close");
	include "../missing.html";
	exit;
}

require 'class.errors.php';
set_error_handler(array('LodelException', 'exception_error_handler'));

// les niveaux d'erreur à afficher
error_reporting(E_ALL);

try
{
require 'auth.php';
authenticate();
//on indique qu'on veut pas le desk
$GLOBALS['nodesk'] = true;
require 'view.php';
$view = &View::getView();
$view->renderCached($context, 'index');
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