<?php
/**
 * Fichier site - Gère un site
 *
 * PHP version 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
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
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodeladmin
 */

// gere un site. L'acces est reserve au niveau lodeladmin.
require_once 'lodelconfig.php';
require_once 'auth.php';
authenticate(LEVEL_ADMINLODEL, NORECORDURL);
require_once 'func.php';

$context['installoption'] = intval($installoption);
$context['version']       = '0.8';

require_once 'class.siteManage.php';
$website = new siteManage($id, $context);
$website->set('reinstall', $reinstall);
$website->set('maindefault', $maindefault);
$website->set('singledatabase',$singledatabase);
$website->set('version',$context['version']);
$website->set('downloadsiteconfig',$downloadsiteconfig);
$website->set('mano',$mano);







// suppression et restauration
if ($website->get('id')>0 && ($delete || $restore)) {
	if($delete)
		$website->remove();
	else
		$website->restore();
}

// reinstall all the sites
if ($website->get('reinstall') == 'all') {
	$website->reinstall($dir);
}

// ajoute ou edit
if ($edit || $website->get('maindefault')) { 
	$website->manageSite();
	$task = 'version';
}

// on récupère les infos du site (url, path, status, etc..)
if ($website->get('id') > 0) {
	require_once 'connect.php';
	$result = mysql_query("SELECT * FROM $GLOBALS[tp]sites WHERE ".$website->get('critere')." AND (status>0 || status=-32)") or die (mysql_error());
	$res = mysql_fetch_assoc($result);
	settype($res, "array");
	$website->context = array_merge($website->context, $res);
}

if ($task == 'version') {

	// on verifie que versiondir match bien un repertoire local pour eviter un hack.
	// on verifie en meme temps qu'il est bien defini, ce qui correspond quand meme 
	// a la plupart des cas.
	if  (!$website->get('versiondir') && !$context['versiondir']) {
		$website->selectVersion();
	}
	$task = 'createdb';
}   // on connait le repertoire dans lequel est la "bonne" version de lodel/site

if ($task) {
	if  (!$website->get('versiondir') && !$context['versiondir']) {
		$website->selectVersion();
	}
	if (!preg_match($website->get('lodelhomere'),$website->get('versiondir'))) {
		die ("ERROR: versiondir");
	}
}

// creation de la DataBase si besoin
if (defined('DATABASE')) {
	$database = DATABASE;
}
$website->set('database', $database);
$website->context['dbname'] = $website->get('singledatabase') == 'on' ? $database : $database. '_'. $website->context['name'];

if ($task == 'createdb') {
	if($website->createDB())
		$task = 'createtables';
	else
		return;
}

// creation des tables des sites
if ($task == 'createtables') {
	
	if($website->createTables())
		$task = 'createdir';
	else
		return;
}

// Creer le repertoire principale du site
if ($task == 'createdir'){
	if($website->createDir())
		$task = 'file';
	else
		return;
}

// verifie la presence ou copie les fichiers necessaires
// cherche dans le fichier install-file.dat les fichiers a copier
if ($task == 'file') {
	if(!$website->manageFiles($lodeldo))
		return;
}

$context = $website->context;

// post-traitement
postprocessing($context);

require_once 'view.php';
$view = &View::getView();
$view->render($context, 'site');
?>
