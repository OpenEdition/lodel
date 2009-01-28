<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 *
 *  Home page: http://www.lodel.org
 *
 *  E-Mail: lodel@lodel.org
 *
 *                            All Rights Reserved
 *
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.*/


/************************************ !! VERSION !! **************************************/
$version = "0.9";
/************************************ !! VERSION !! **************************************/


require "class.Install.php";
$test = false;
$lodelconfig = "CACHE/lodelconfig-cfg.php";
// securise l'entree
if (file_exists("lodelconfig.php") && file_exists("../lodelconfig.php")) 
{
	// import Posted variables for the Register Off case.
	// this should be nicely/safely integrated inside the code, but that's
	// a usefull little hack at the moment
	if (!((bool) ini_get("register_globals"))) { 
		extract($_REQUEST,EXTR_SKIP);
	}
	$test = $installing = true;
	$install = new Install($lodelconfig, $have_chmod, $plateformdir);
	if (!is_readable("lodelconfig.php")) $install->problem("reading_lodelconfig");
	require("lodelconfig.php");
	if($installlang != $_GET['installlang']) {
		require_once '../lodel-'.$version.'/scripts/lang.php';
		if(in_array(strtoupper($_GET['installlang']), array_keys($GLOBALS['installlanguages']))) {
			$installlang = $_GET['installlang'];
		}
	}
}

error_reporting(E_ERROR | E_WARNING | E_PARSE);

if($test) {
	// Version of lodel to be installed.
	$install->set('versioninstall', $version);
	$install->set('versionsuffix', "-".$install->get('versioninstall'));   # versioning
	$install->testInstallDB();
} else
{
	// import Posted variables for the Register Off case.
	// this should be nicely/safely integrated inside the code, but that's
	// a usefull little hack at the moment
	if (!((bool) ini_get("register_globals"))) { 
		extract($_REQUEST,EXTR_SKIP);
	}
	
	$install = new Install($lodelconfig, $have_chmod, $plateformdir);
	// Version of lodel to be installed.
	$install->set('versioninstall', $version);
	$install->set('versionsuffix', "-".$install->get('versioninstall'));   # versioning
}

header("Content-type: text/html; charset=utf-8");

$install->set('installlang', $installlang);

if (!defined("LODELROOT")) define("LODELROOT","../"); // acces relatif vers la racine de LODEL. Il faut un / a la fin.

ini_set('include_path',LODELROOT. "lodel".$install->get('versionsuffix')."/scripts" .PATH_SEPARATOR . LODELROOT . "share".$install->get('versionsuffix'). PATH_SEPARATOR . ini_get("include_path"));


//
// option
//
if($option1) { $installoption=1; }
elseif($option2){ $installoption=2; }
elseif ($erase_and_option1) { $installoption=1; @unlink($install->get('lodelconfig')); }
elseif ($erase_and_option2) { $installoption=2; @unlink($install->get('lodelconfig')); }
if(isset($installoption))
	$install->set('installoption', $installoption);

//
// Test the PHP version
//
if (!version_compare(PHP_VERSION,'5.0.0','>=')) {
  $install->problem('version');
  exit;
}

//
// choix de la plateforme
// Copie le fichier lodelconfig choisi dans le CACHE
// Verifie qu'on peut ecrire dans le cache
//
$install->set('plateformdir', LODELROOT."lodel".$install->get('versionsuffix')."/install/plateform");

if ($tache=="plateform") {
	$install->set('plateform', $plateform);
	$install->installConf($testfile);
}

//
// gestion de mysql / maj configuration
//

if ($tache=="mysql") {
	$install->majConfDB($newdbusername, $newdbpasswd, $newdbhost);
}

//
// gestion de la database
//

if ($tache=="database") {
	if ($continue) {
		$tache="continue";
		// nothing to do
	}
	else
	{
		if(!$install->manageDB($erasetables, $singledatabase, $newdatabase, $newsingledatabase, $newtableprefix, $createdatabase, $existingdatabase))
		{
			$erreur_createdatabase=1;
			$install->include_tpl("install-database.html");
			return;
		}
	}
}

// création de l'admin
if ($tache=="admin") {

	unset($t);
	$t = $install->manageAdmin($adminusername, $adminpasswd, $adminpasswd2, $lang, $site, $adminemail);

	if($t !== true)
	{
		if($t === "error_user")
			$erreur_empty_user_or_passwd=true;
		elseif($t === "error_passwd")
			$erreur_admin_passwd=true;
		elseif($t === "error_confirmpasswd")
			$erreur_confirm_passwd=true;
		elseif($t === "error_create")
			$erreur_create=1;
		elseif($t === "error_email")
			$erreur_admin_email=true;

		$install->include_tpl("install-admin.html");
	}
	else
	{
		@include($install->get('lodelconfig'));	
		$GLOBALS['tableprefix'] = $tableprefix;
		// log this user in 
		require_once("connect.php");
		require_once("loginfunc.php");

		$site="";
		if (check_auth($adminusername,$adminpasswd,$site)) {
			open_session($adminusername);
		}

		// on vire le MDP de la mémoire
		unset($adminpasswd);
	}

 }

$install->set('protecteddir', array("lodel".$install->get('versionsuffix'),
		    "CACHE",
		    "tpl",
		    "lodeladmin".$install->get('versionsuffix')."/CACHE",
		    "lodeladmin".$install->get('versionsuffix')."/tpl"));

// mise en place htaccess
if ($tache=="htaccess") {
	$erreur_htaccesswrite = $install->set_htaccess($verify, $write, $nohtaccess);
}

// maj des options lodel
if ($tache=="options") {
	$install->maj_options($newurlroot, $permission, $pclzip, $newimportdir, $newextensionscripts, $newusesymlink, $newcontactbug, $newunzipcmd, $newzipcmd, $newuri);
}

// téléchargement du fichier de conf ?
if ($tache=="downloadlodelconfig") {
	if($install->downloadlodelconfig($log_version))
		return;
}

// affichage du contenu du fichier lodelconfig ?
if ($tache=="showlodelconfig") {
	if($install->showlodelconfig())
		return;
}


/////////////////////////////////////////////////////////////////
//                              TESTS                          //
/////////////////////////////////////////////////////////////////

//
// Vérifie les droits sur les fichiers, (verifie juste les droits d'apache, pas les droits des autres users, et verifie les droits minimum, pas de verification de la securite) dans la zone admin
//

// les fonctions de tests existent, donc on peut faire des tests sur les droits
if($tache == 'droits') {
	$t = $install->testRights();
	if($t !== true)
	{
		$erreur['functions'] = array();	
		$erreur['functions'] = $t;
		$install->include_tpl("install-php.html");
		return;
	}
}

// pas de tache ? ok on est donc au début de l'installation
if (!$tache) {
	if(!is_bool($installing))
		$installing = $install->startInstall();
	$install->include_tpl("install-bienvenue.html");
	return;
}

// include: lodelconfig
//
// essai de trouver une configuration
//
$install->checkConfig();

//
// essaie d'etablir si on accede au script func.php
//
$install->checkFunc();


//
// essaie la connection a la base de donnée
//
if($install->checkDB() === "error_cnx")
{
	$erreur_connect=1;
	$install->include_tpl("install-mysql.html");
}

@include($install->get('lodelconfig'));
// on cherche si on a une database
if(!$install->get('installoption')) {
	if(!$installoption) trigger_error('Internal error. Missing installoption value from lodelconfig.php !', E_USER_ERROR);
	$install->set('installoption', $installoption);
}
if (!$database) {
	$resultshowdatabases = $install->seekDB();
	$install->include_tpl("install-database.html");
}

$t = $install->installDB($erasetables, $tache);

if($t !== true)
{
	if($t === "error_dbselect")
	{
		$erreur_usedatabase=1;
		$install->include_tpl("install-database.html");
		return;
	}
	elseif($t === "error_tableexist")
	{
		$erreur_tablesexist=1;
		$install->include_tpl("install-database.html");
		return;
	}
	else
	{
		$erreur_createtables = $t;
		$install->include_tpl("install-database.html");
		return;
	}
}

//
// Vérifie qu'il y a un administrateur Lodel, sinon demande la creation
//
unset($t);
$t = $install->verifyAdmin();

if($t === false)
{
	$install->include_tpl("install-admin.html");
	return;
}

//
// Vérifie la présence des htaccess
//

if ($htaccess!="non") {

	if(!$install->checkHtaccess())
	{
		$erreur_htaccess = $install->checkHtaccess();
		$install->include_tpl("install-htaccess.html");
		return;
	}
}

//
// Demander des options generales
//
unset($t);
$t = $install->askOptions($importdir, $chooseoptions);

if($t === "error")
{
	$erreur_importdir=1;
	$install->include_tpl("install-options.html");
	return;
}
elseif($t === false)
{
	$install->include_tpl("install-options.html");
	return;
}

//
// Vérifie maintenant que les lodelconfig sont les meme que celui qu'on vient de produire
//
unset($t);
$t = $install->verifyLodelConfig();

if($t === "error")
{
	$erreur_exists_but_not_readable=1;
	$install->include_tpl('install-lodelconfig.html');
	return;
}

// enfin fini !
$install->finish();
?>