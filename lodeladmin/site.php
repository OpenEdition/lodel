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
 * @package lodeladmin
 */
define('backoffice-lodeladmin', true);
// gere un site. L'acces est reserve au niveau lodeladmin.
require 'lodelconfig.php';
require 'class.errors.php';

try
{
	require 'auth.php';
	authenticate(LEVEL_ADMINLODEL, NORECORDURL);
	// pas de paramètres ? rien à faire ici. redirige vers la liste des sites
	if(empty($_GET) && empty($_POST))
		header('Location:index.php?do=list&lo=sites');
	
	extract_post();
	$context['installoption'] = (int)$installoption;
	$context['version']       = '0.8';

	require 'class.siteManage.php';
	$context['shareurl'] = $shareurl;
	$website = new siteManage($id, $context);
	$website->set('reinstall', $reinstall);
	$website->set('maindefault', $maindefault);
	$website->set('singledatabase',$singledatabase);
	$website->set('version',$context['version']);
	$website->set('downloadsiteconfig',$downloadsiteconfig);
	
	if($maintenance > 0)
		$website->maintenance($maintenance);
	
	// suppression et restauration
	if ($id>0 && ($delete || $restore)) {
		if($delete)
			$website->remove();
		elseif($restore)
			$website->restore();
	}

	// on récupère les infos du site (url, path, status, etc..)
	if ($id > 0 || $website->get('id') > 0) {
		$result = $db->GetRow("SELECT * FROM `$GLOBALS[tp]sites` WHERE ".$website->get('critere')." AND (status>0 || status=-32)") or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		$website->context = array_merge($website->context, $result);
	
	}
	
	// reinstall all the sites
	if ($reinstall == 'all') {
		$website->reinstall($dir);
	}
	
	// ajoute ou edit
	if ($edit || $maindefault) { 
		if($website->manageSite())
			$task = 'version';
	}
	
	if ($task === 'version') {
	
		// on verifie que versiondir match bien un repertoire local pour eviter un hack.
		// on verifie en meme temps qu'il est bien defini, ce qui correspond quand meme 
		// a la plupart des cas.
		if  (!$website->get('versiondir')) {
			$website->selectVersion();
		}
		$task = 'createdb';
	}   // on connait le repertoire dans lequel est la "bonne" version de lodel/site
	
	if ($task) {
		if  (!$website->get('versiondir')) {
			$website->selectVersion();
		}
		if (!preg_match($website->get('lodelhomere'),$website->get('versiondir'))) {
			trigger_error("ERROR: versiondir", E_USER_ERROR);
		}
	}
	
	// creation de la DataBase si besoin
	if (defined('DATABASE')) {
		$database = DATABASE;
	}
	$website->set('database', $database);
	
	if($website->context['name'] && !$website->context['dbname'])
		$website->context['dbname'] = ($website->get('singledatabase') == 'on') ? $database : $database. '_'. $website->context['name'];

	unset($t);
	if ($task === 'createdb') {
	
		$t = $website->createDB($lodeldo);
	
		if($t === true)
		{
			$task = 'createtables';
		}
		else
		{
			return;
		}
	}
	
	// creation des tables des sites
	if ($task === 'createtables') {
		if($website->createTables())
			$task = 'createdir';
		else
			return;
	}
	
	// Creer le repertoire principale du site
	if ($task === 'createdir'){
		if($website->createDir($lodeldo, $mano, $filemask))
			$task = 'file';
		else
			return;
	}
	
	// verifie la presence ou copie les fichiers necessaires
	// cherche dans le fichier install-file.dat les fichiers a copier
	if ($task === 'file') {
		if(!$website->manageFiles($lodeldo))
			return;
	}
	
	$context = $website->context;
	
	// post-traitement
	postprocessing($context);
	
	require 'view.php';
	$view = &View::getView();
	$view->render($context, 'site');
}
catch(Exception $e)
{
	echo $e->getContent();
	exit();
}
?>