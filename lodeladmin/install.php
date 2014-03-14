<?php
/**
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * Copyright (c) 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * Copyright (c) 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
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


error_reporting(E_ALL ^ E_NOTICE);

// import Posted variables for the Register Off case.
// this should be nicely/safely integrated inside the code, but that's
// a usefull little hack at the moment
if (!((bool)ini_get("register_globals"))) {
    extract($_REQUEST, EXTR_SKIP);
}

/************************************ !! VERSION !! **************************************/
$version = "1.0";
/************************************ !! VERSION !! **************************************/

require "class.Install.php";

$lodelconfig = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "lodelconfig-cfg.php";
// securise l'entree
if (file_exists("lodelconfig.php") && file_exists("../lodelconfig.php")) {
    $installing = true;
    $install = new Install($lodelconfig, $have_chmod, $plateformdir);
    if (!is_readable("lodelconfig.php")) $install->problem("reading_lodelconfig");
    require("lodelconfig.php");
    if (isset($_GET['installlang']) && $cfg['installlang'] != $_GET['installlang']) {
        require_once '../lodel/scripts/lang.php';
        if (in_array(strtoupper($_GET['installlang']), array_keys($GLOBALS['installlanguages']))) {
            $installlang = $_GET['installlang'];
        }
    }
    if (!isset($installlang)) $installlang = $cfg['installlang'];
    // Version of lodel to be installed.
    $install->set('versioninstall', $version);
    $install->testInstallDB();
} else {
    $install = new Install($lodelconfig, $have_chmod, $plateformdir);
    // Version of lodel to be installed.
    $install->set('versioninstall', $version);
}

header("Content-type: text/html; charset=utf-8");

$install->set('installlang', $installlang);

if (!defined("LODELROOT")) define("LODELROOT", "../"); // acces relatif vers la racine de LODEL. Il faut un / a la fin.

ini_set('include_path', LODELROOT . "lodel/scripts" . PATH_SEPARATOR . LODELROOT . "share" . PATH_SEPARATOR . ini_get("include_path"));

//
// option
//
if (!empty($option1)) {
    $installoption = 1;
} elseif (!empty($option2)) {
    $installoption = 2;
} elseif (!empty($erase_and_option1)) {
    $installoption = 1;
    unlink($install->get('lodelconfig'));
} elseif (!empty($erase_and_option2)) {
    $installoption = 2;
    unlink($install->get('lodelconfig'));
}

if (!empty($installoption))
    $install->set('installoption', $installoption);
elseif (!empty($cfg['installoption']))
    $install->set('installoption', $cfg['installoption']);

//
// Test the PHP version
//
if (!version_compare(PHP_VERSION, '5.2.0', '>=')) {
    $install->problem('version');
    exit;
}

//
// choix de la plateforme
// Copie le fichier lodelconfig choisi dans le CACHE
// Verifie qu'on peut ecrire dans le cache
//
$install->set('plateformdir', LODELROOT . "lodel/install/plateform");
$install->set('protecteddir', array("lodel",
    "CACHE",
    "tpl",
    "lodeladmin/CACHE",
    "lodeladmin/tpl"));

if (!isset($tache)) $tache = false;

switch ($tache) {
    case 'plateform':
        $install->set('plateform', $plateform);
        $install->installConf(__FILE__);
        break;
    case 'mysql':
        $install->majConfDB($newdbusername, $newdbpasswd, $newdbhost);
        break;
    case 'database':
        if ($continue) {
            $tache = "continue";
            // nothing to do
        } elseif (!$install->manageDB($erasetables, $singledatabase, $newdatabase, $newsingledatabase, $newtableprefix, $createdatabase, $existingdatabase)) {
            $GLOBALS['erreur_createdatabase'] = 1;
            $install->include_tpl("install-database.html");
        }
        break;
    case 'admin':
        if (($t = $install->manageAdmin($adminusername, $adminpasswd, $adminpasswd2, $lang, $site, $adminemail)) !== true) {
            if ($t === "error_user")
                $GLOBALS['erreur_empty_user_or_passwd'] = true;
            elseif ($t === "error_passwd")
                $GLOBALS['erreur_admin_passwd'] = true;
            elseif ($t === "error_confirmpasswd")
                $GLOBALS['erreur_confirm_passwd'] = true;
            elseif ($t === "error_create")
                $GLOBALS['erreur_create'] = 1;
            elseif ($t === "error_email")
                $GLOBALS['erreur_admin_email'] = true;

            $install->include_tpl("install-admin.html");
        } else {
            require($install->get('lodelconfig'));
            $GLOBALS['tableprefix'] = $cfg['tableprefix'];
            require_once("context.php");
            $cfg['home'] = LODELROOT . $cfg['home'];
            C::setCfg($cfg);
            // log this user in
            require_once("connect.php");
            require_once("loginfunc.php");
            require_once("auth.php");
            $site = "";
            if (check_auth($adminusername, $adminpasswd, $site)) {
                $adminpasswd = null;
                if ('error_opensession' === ($name = open_session($adminusername))) {
                    trigger_error('ERROR: cannot open a session ?', E_USER_ERROR);
                }
            } else {
                $adminpasswd = null;
                trigger_error('ERROR: invalid username or password. Strange, please contact lodel@lodel.org', E_USER_ERROR);
            }

            // on vire le MDP de la mémoire
            $adminpasswd = null;
        }
        break;
    case 'htaccess': // mise en place htaccess
        $GLOBALS['erreur_htaccess'] = $install->set_htaccess($verify, $write, $nohtaccess);
        if (!empty($GLOBALS['erreur_htaccess'])) {
            $install->include_tpl("install-htaccess.html");
        }
        break;
    case 'options': // maj des options lodel
        $install->maj_options($newurlroot, $permission, $pclzip, $newimportdir, $newextensionscripts, $newusesymlink, $newcontactbug, $newunzipcmd, $newzipcmd, $newuri);
        break;
    case 'downloadlodelconfig': // téléchargement du fichier de conf ?
        $install->downloadlodelconfig($log_version);
        exit();
        break;
    case 'showlodelconfig': // affichage du contenu du fichier lodelconfig ?
        $install->showlodelconfig();
        break;
    case 'droits': // les fonctions de tests existent, donc on peut faire des tests sur les droits
        $t = $install->testRights();
        if ($t !== true) {
            $GLOBALS['erreur']['functions'] = array();
            $GLOBALS['erreur']['functions'] = $t;
            $install->include_tpl("install-php.html");
        }
        break;
    case 'lodelconfig':
        //
        // Vérifie maintenant que les lodelconfig sont les meme que celui qu'on vient de produire
        //
        if ($install->verifyLodelConfig() === "error") {
            $GLOBALS['erreur_exists_but_not_readable'] = 1;
            $install->include_tpl('install-lodelconfig.html');
        }

        // enfin fini !
        $install->finish();
        break;
    default:
        if (!is_bool($installing))
            $GLOBALS['installing'] = $install->startInstall();
        $install->include_tpl("install-bienvenue.html");
        break;
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
$install->checkDB();

require($install->get('lodelconfig'));
// on cherche si on a une database
if (!$install->get('installoption')) {
    if (!$cfg['installoption']) trigger_error('Internal error. Missing installoption value from lodelconfig.php !', E_USER_ERROR);
    $install->set('installoption', $cfg['installoption']);
}
if (!$cfg['database']) {
    $GLOBALS['resultshowdatabases'] = $install->seekDB();
    $install->include_tpl("install-database.html");
}

$t = $install->installDB($erasetables, $tache);

if ($t !== true) {
    if ($t === "error_dbselect") {
        $GLOBALS['erreur_usedatabase'] = 1;
        $install->include_tpl("install-database.html");
    } elseif ($t === "error_tableexist") {
        $GLOBALS['erreur_tablesexist'] = 1;
        $install->include_tpl("install-database.html");
    } else {
        $GLOBALS['erreur_createtables'] = $t;
        $install->include_tpl("install-database.html");
    }
}

//
// Vérifie qu'il y a un administrateur Lodel, sinon demande la creation
//
if ($install->verifyAdmin() === false) {
    $install->include_tpl("install-admin.html");
}

//
// Vérifie la présence des htaccess
//
if ($cfg['htaccess'] != "non") {

    if (!$GLOBALS['erreur_htaccess'] = $install->checkHtaccess()) {
        $install->include_tpl("install-htaccess.html");
    }
}

//
// Demander des options generales
//
$t = $install->askOptions($newimportdir, $cfg['chooseoptions']);
if ($t === "error") {
    $GLOBALS['erreur_importdir'] = 1;
    $install->include_tpl("install-options.html");
} elseif ($t === false) {
    $install->include_tpl("install-options.html");
}
// install lodelconfig
$install->include_tpl('install-lodelconfig.html');
?>
