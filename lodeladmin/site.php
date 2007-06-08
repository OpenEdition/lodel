<?php
/**
 * Fichier site - Gère un site
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
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodeladmin
 */

//# L'ensemble de ce fichier sera à transferer dans une logique (> 0.8)
//# !!!!!!!!!!!!!!!!!

// gere un site. L'acces est reserve au niveau lodeladmin.
require 'lodelconfig.php';
require 'auth.php';
authenticate(LEVEL_ADMINLODEL, NORECORDURL);
require_once 'func.php';

// calcul le critere pour determiner le user a editer, restorer, detruire...
$id      = intval($id);
$critere = "id='$id'";
$context['installoption'] = intval($installoption);
$context['version']       = '0.8';

// suppression et restauration
if ($id>0 && ($delete || $restore)) {
	if ($delete) {
		mysql_query(lq("UPDATE #_TP_sites SET status=-abs(status) WHERE $critere")) or dberror();
	} else {
		mysql_query(lq("UPDATE #_TP_sites SET status=abs(status) WHERE $critere")) or dberror();
	}
	update();
	require 'view.php';
	$view = &View::getView();
	$view->back(); // on revient
}

// reinstall all the sites
if ($reinstall == 'all') {
	require_once 'connect.php';

	// function to get the version of the site
	function getsiteversion($dir)
	{ 
		if (!file_exists($dir. 'siteconfig.php')) {
			die("ERROR: internal error while reinstalling every site. dir is $dir");
		}
		include ($dir. 'siteconfig.php');
		return $version;
	}

	$result = $db->execute(lq("SELECT path,name FROM #_MTP_sites WHERE status>0")) or dberror();
	while(!$result->EOF) {
		$row = $result->fields;
		// on peut installer les fichiers
		if (!$row['path']) {
			$row['path'] = '/'. $row['name'];
		}
		$root    = str_replace('//', '/', LODELROOT. $row['path']). '/';
		$version = getsiteversion($root);
		if ($row['path'] == '/') { // c'est un peu sale ca.
			install_file($root, "lodel-$version/src", '');
		} else {
			install_file($root, "../lodel-$version/src", LODELROOT);
		}

		// clear the CACHEs
		require_once 'cachefunc.php';
		removefilesincache(LODELROOT, $root, $root. 'lodel/edition', $root. 'lodel/admin');

		$result->MoveNext();
	}

	header('location: '. LODELROOT. 'index.php');
	exit;
}



// ajoute ou edit
if ($edit || $maindefault) { // modifie ou ajoute
	extract_post();
	if ($maindefault) { // site par defaut ?
		$context['title']  = 'Site principal';
		$context['name']   = 'principal';
		$context['atroot'] = true;
	}

	// validation
	do {
		if (!$context['title']) {
			$context['error_title'] = $err = 1;
		}
		if (!$id && (!$context['name'] || !preg_match("/^[a-z0-9\-]+$/",$context['name']))) { $context['error_name'] = $err = 1;
		}
		if ($err) {
			break;
		}
		require_once 'connect.php';

		// verifie qu'on a qu'un site si on est en singledatabase
		if (!$id && $singledatabase == 'on') {
			$result = mysql_query ("SELECT COUNT(*) FROM $GLOBALS[tp]sites WHERE status>-32 AND name!='". $context['name']. "'") or die (mysql_error());
			list($numsite) = mysql_fetch_row($result);
			if ($numsite >= 1) {
				die("ERROR<br />\nIl n'est pas possible actuellement d'avoir plusieurs sites sur une unique base de données : il faut utiliser plusieurs bases de données.");
			}
		}

		// édition d'un site : lit les informations options, status, etc.
		if ($id) {
			$result = mysql_query ("SELECT status,name,path FROM $GLOBALS[tp]sites WHERE id='$id'") or die (mysql_error());
			list($status,$name,$context['path']) = mysql_fetch_row($result);
			$context['name'] = $name;
		} else { // création d'un site
			// vérifie que le nom (base de données + répertoire du site) n'est pas déjà utilisé
			$result = mysql_query ("SELECT name FROM $GLOBALS[tp]sites") or die (mysql_error());
			while ($row = mysql_fetch_array($result)) {
				$sites[] = $row['name'];
			}
			if(is_array($sites)) {
 				if(in_array($context['name'], $sites)) {
					$context['error_unique_name'] = $err = 1;
					break;
				}
			}

			$options = '';
			$status  = -32; // -32 signifie en creation
			if ($context[atroot]) {
				$context['path'] = '/';
			}
			if (!$context['path']) {
				$context['path'] = '/'. $context['name'];
			}
		}
		if (!$context['url']) {
			$context['url'] = 'http://'. $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT'] ? ':'. $_SERVER['SERVER_PORT'] : ""). preg_replace("/\blodeladmin-?\d*(\.\d*)?\/.*/", '', $_SERVER['REQUEST_URI']). substr($context['path'], 1);
		}

		if ($reinstall) {
			$status = -32;
		}

		//suppression de l'eventuel / a la fin de l'url
		$context['url'] = preg_replace("/\/$/", '', $context[url]);

		// Ajout de slashes pour autoriser les guillemets dans le titre et le sous-titre du site
		$context['title'] = magic_addslashes($context['title']);
		$context['subtitle'] = magic_addslashes($context['subtitle']);

		mysql_query("REPLACE INTO $GLOBALS[tp]sites (id,title,name,path,url,subtitle,status) VALUES ('$id','$context[title]','$context[name]','$context[path]','$context[url]','$context[subtitle]','$status')") or die (mysql_error());

		update();
		if ($status>-32) {
			require 'view.php';
			$view = &View::getView();
			$view->back(); // on revient, le site n'est pas en creation
		}

		if (!$id) {
			$context['id'] = $id = mysql_insert_id();
		}
		$task = 'version';
	} while (0);
}

if ($id > 0) {
	require_once 'connect.php';
	$result = mysql_query("SELECT * FROM $GLOBALS[tp]sites WHERE $critere AND (status>0 || status=-32)") or die (mysql_error());
	$res = mysql_fetch_assoc($result);
	settype($res, "array");
	$context = array_merge($context, $res);
}


// regexp pour reconnaitre un repertoire de version
$lodelhomere = "/^lodel(-[\w.]+)$/";

if ($task == 'version') {

	// on verifie que versiondir match bien un repertoire local pour eviter un hack.
	// on verifie en meme temps qu'il est bien defini, ce qui correspond quand meme 
	// a la plupart des cas.

	// cherche les differentes versions de lodel
	function cherche_version () // on encapsule a cause du include de sites config
	{
		global $lodelhomere;
		$dir = opendir(LODELROOT);
		if (!$dir) {
			die ("impossible d'acceder en ecriture le repertoire racine");
		}
		$versions = array();
		while ($file = readdir($dir)) {
			if ($file[0] === '.') {
				continue;
			}
			if (is_dir(LODELROOT.$file) && preg_match($lodelhomere,$file) && is_dir(LODELROOT. $file. '/src')) {
				if (!(@include(LODELROOT. "$file/src/siteconfig.php"))) {
					echo "ERROR: Unable to open the file: $file/src/siteconfig.php<br>";
				} else {
					$versions[$file]=$version ? $version : "devel";
				}
			}
		}
		return $versions;
	}
	if  (!$versiondir) {
		$versions = cherche_version();
	
		// ok, maintenant on connait les versions
		$context['countversions'] = count($versions);
		if ($context['countversions'] == 1) {// ok, une seule version, on la choisit
			list($versiondir) = array_keys($versions);
		} elseif ($context['countversions'] == 0) { // aie, aucune version on crach
			die ("Verifiez le package que vous avez, il manque le repertoire lodel/src. L'installation ne peut etre poursuivie !");
		} else { // il y en a plusieurs, faut choisir
			$context['count'] = count($versions);
			function makeselectversion()
			{
				global $versiondir,$versions;
				foreach ($versions as $dir => $ver) {
					$selected = $versiondir == $dir ? "selected=\"selected\"" : '';
					echo "<option value=\"$dir\"$selected>$dir  ($ver)</option>\n";
				}
			}
			
			require 'view.php';
			$view = &View::getView();
			$view->render($context, 'site-version');
			return;
		}
	}
	$task = 'createdb';
}   // on connait le repertoire dans lequel est la "bonne" version de lodel/site

if ($task) {
	if (!preg_match($lodelhomere,$versiondir)) {
		die ("ERROR: versiondir");
	}
	$context['versiondir'] = $versiondir;
}

// creation de la DataBase si besoin
if (defined('DATABASE')) {
	$database = DATABASE;
}
$context['dbname'] = $singledatabase == 'on' ? $database : $database. '_'. $context['name'];

if ($task == 'createdb') {
	if (!$context['name']) {
		die ('probleme interne');
	}
	do { // bloc de controle
		if ($singledatabase == 'on') {
			break;
		}

		// check if the database existe
		require_once 'connect.php';
		$db_list = mysql_list_dbs();
		$i = 0;
		$cnt = mysql_num_rows($db_list);
		while ($i < $cnt) {
			if ($context['dbname'] == mysql_db_name($db_list, $i)) {
				break 2; // la database existe
			}
			$i++;
		}
		// well, it does not exist, let's create it.
		if (defined('DBUSERNAME')) {
			$dbusername = DBUSERNAME;
		}
		if (defined('DBHOST')) {
			$dbhost     = DBHOST;
		}
		if (defined('DBPASSWD')) {
			$dbpasswd   = DBPASSWD;
		}

		if ($GLOBALS['version_mysql'] > 40) {
			$db_charset = find_mysql_db_charset($GLOBALS['currentdb']);
		} else { 
			$db_charset = '';
		}
    		$context[command1]="CREATE DATABASE `$context[dbname]`$db_charset";
		$context['command2'] = "GRANT ALL ON `$context[dbname]`.* TO $dbusername@$dbhost";
		$pass = $dbpasswd ? " IDENTIFIED BY '$dbpasswd'" : '';

		if ($installoption == '2' && !$lodeldo) {
			$context['dbusername'] = $dbusername;
			$context['dbhost']     = $dbhost;

			require 'view.php';
			$view = &View::getView();
			$view->render($context, 'site-createdb');
			return;
		}
		if (!@mysql_query($context['command1']) || !@mysql_query($context['command2']. $pass)) {
			$context['error']      = mysql_error();
			$context['dbusername'] = $dbusername;
			$context['dbhost']     =$dbhost;
			require 'view.php';
			$view = &View::getView();
			$view->render($context, 'site-createdb');
			return;
		}
	} while (0);

	$task = 'createtables';
}

// creation des tables des sites
if ($task == 'createtables') {
	if (!$context['name']) {
		die ("probleme interne");
	}

	require_once 'connect.php';
	mysql_select_db($context['dbname']); //selectionne la base de donnée du site
	if (!file_exists(LODELROOT. "$versiondir/install/init-site.sql")) {
		die ("impossible de faire l'installation, le fichier init-site.sql est absent");
	}

	$text = join('', file(LODELROOT. "$versiondir/install/init-site.sql"));
	$text.= "\n";
	
	if ($GLOBALS['version_mysql'] > 40) {
		$db_charset = find_mysql_db_charset($context['dbname']);
	} else { 
		$db_charset = '';
	}
	
	$text = str_replace("_CHARSET_",$db_charset,$text);
	$sqlfile = lq($text);
	$sqlcmds = preg_split ("/;\s*\n/", preg_replace("/#.*?$/m", '', $sqlfile));
	if (!$sqlcmds) {
		die("le fichier init-site.sql ne contient pas de commande. Probleme!");
	}
	$error = array();
	foreach ($sqlcmds as $cmd) {
		$cmd = trim($cmd);
		if ($cmd && !mysql_query($cmd)) {
			array_push($error, $cmd, mysql_error());
		}
	}

	if ($error) {
		$context['error_createtables'] = $error;
		function loop_errors_createtables(&$context, $funcname)
		{
			$error = $context['error_createtables'];
			do {
				$localcontext['command'] = array_shift($error);
				$localcontext['error']   = array_shift($error);
				call_user_func("code_do_$funcname", array_merge($context, $localcontext));
			} while ($error);
		}
		require 'view.php';
		$view = &View::getView();
		$view->render($context, 'site-createtables');
		return;
	}
	mysql_select_db($database);
	$task = 'createdir';
}

// Creer le repertoire principale du site
if ($task == 'createdir') {
	if (!$context['path']) {
		$context['path'] = '/'. $context['name'];
	}

	$dir = LODELROOT. $context['path'];
	if (!file_exists($dir) || !@opendir($dir)) {
		// il faut creer le repertoire rep
		if ($installoption == '2' && !$lodeldo) {
			if ($mano) {
				$context['error_nonexists'] = !file_exists($dir);
				$context['error_nonaccess'] = !@opendir($dir);
			}
			require 'view.php';
			$view = &View::getView();
			$view->render($context, 'site-createdir');
			return;
		}

		// on essaie
		if (!file_exists($dir) && !@mkdir($dir, 0777 & octdec($filemask))) {
			// on y arrive pas... pas les droits surement
			$context['error_mkdir'] = 1;
			require 'view.php';
			$view = &View::getView();
			$view->render($context, 'site-createdir');
			return;
		}
		@chmod($dir, 0777 & octdec($filemask));
	}

	// on essaie d'ecrire dans tpl si root
	if ($context['path'] == '/') {
		if (!@writefile(LODELROOT. 'tpl/testecriture', '')) {
			$context['error_tplaccess'] = 1;
			require 'view.php';
			$view = &View::getView();
			$view->render($context, 'site-createdir');
			return;
		} else {
			unlink(LODELROOT. 'tpl/testecriture');
		}
	}
	$task = 'file';
}

// verifie la presence ou copie les fichiers necessaires
// cherche dans le fichier install-file.dat les fichiers a copier
if ($task == 'file') {
	// on peut installer les fichiers
	if (!$context['path']) {
		$context['path'] = '/'. $context['name'];
	}
	$root = str_replace('//', '/', LODELROOT. $context['path']). '/';
	$siteconfigcache = 'CACHE/siteconfig.php';
	if ($downloadsiteconfig) { // download the siteconfig
		download($siteconfigcache, 'siteconfig.php');
		return;
	}
	if (file_exists($siteconfigcache)) {
		unlink($siteconfigcache);
	}

	$atroot = $context['path'] == '/' ? 'root' : '';
	if (!copy(LODELROOT. "$versiondir/src/siteconfig$atroot.php", $siteconfigcache)) {
		die("ERROR: unable to write in CACHE.");
	}
	maj_siteconfig($siteconfigcache, array('site' => $context['name']));

	$siteconfigdest = $root. 'siteconfig.php';
	// cherche si le fichier n'existe pas ou s'il est different de l'original
	if (!file_exists($siteconfigdest) || file_get_contents($siteconfigcache) != file_get_contents($siteconfigdest)) {
		if ($installoption == '2' && !$lodeldo) {
			require 'view.php';
			
			$view = &View::getView();
			$view->render($context, 'site-file');
			return;
		}
		@unlink($siteconfigdest); // try to delete before copying.
		// try to copy now.
		if (!@copy($siteconfigcache,$siteconfigdest)) {
			$context['siteconfigsrc']  = $siteconfigcache;
			$context['siteconfigdest'] = $siteconfigdest;
			$context['error_writing']    = 1;
			require 'view.php';
			$view = &View::getView();
			$view->render($context, 'site-file');
			return;
		}
		@chmod ($siteconfigdest, 0666 & octdec($GLOBALS['filemask']));
	}
	// ok siteconfig est copie.
	if ($context['path'] == '/') { // c'est un peu sale ca.
		install_file($root, "$versiondir/src", '');
	} else {
		install_file($root, "../$versiondir/src", LODELROOT);
	}

	// clear the CACHEs
	require_once 'cachefunc.php';
	removefilesincache(LODELROOT, $root, $root. 'lodel/edition', $root. 'lodel/admin');

	// ok on a fini, on change le status du site
	mysql_select_db($GLOBALS[database]);
	mysql_query ("UPDATE $GLOBALS[tp]sites SET status=1 WHERE id='$id'") or die (mysql_error());


	// ajouter le modele editorial ?
	if ($GLOBALS[singledatabase]!="on") {
		mysql_select_db($GLOBALS['database']. '_'. $context['name']);
	}
	$import = true;
	// verifie qu'on peut importer le modele.
	foreach(array('types', 'tablefields', 'persontypes', 'entrytypes') as $table) {
		$result = mysql_query("SELECT 1 FROM $GLOBALS[tp]$table WHERE status>-64 LIMIT 0,1") or die(mysql_error());
		if (mysql_num_rows($result)) {
			$import = false;
			break;
		}
	}

	if (!$context['path']) {
		$context['path'] = '/'. $context['rep'];
	}
	if ($import) {
		$go = $context['url']. "/lodel/admin/index.php?do=importmodel&lo=data";
	} else {
		$go = $context['url']. '/lodel/edition';
	}

	if (!headers_sent()) {
		header("location: $go");
		exit;
	} else {
		echo "<h2>Warnings seem to appear on this page. Since Lodel may be correctly  installed anyway, you may go on by following <a href=\"$go\">this link</a>. Please report the problem to help us to improve Lodel.</h2>";
		exit;
	}
	return;
}

// post-traitement
postprocessing($context);
require_once 'view.php';
$view = &View::getView();
$view->render($context, 'site');

function install_file($root, $homesite, $homelodel)
{
	global $extensionscripts, $usesymlink, $context;
	$file = "$root$homesite/../install/install-fichier.dat"; // homelodel est necessaire pour choper le bon fichier d'install
	if (!file_exists($file)) {
		die("Fichier $file introuvable. Verifiez votre pactage");
	}
	$lines = file($file);
	$dirsource = '.';
	$dirdest   = '.';

	$search = array("/\#.*$/", '/\$homesite/', '/\$homelodel/');
	$rpl    = array ('', $homesite, $homelodel);

	foreach ($lines as $line) {
		$line = rtrim(preg_replace($search, $rpl, $line));
		if (!$line) {
			continue;
		}
		list ($cmd, $arg1, $arg2) = preg_split ("/\s+/", $line);
		$dest1 = "$root$dirdest/$arg1";
		# quelle commande ?
		if ($cmd == 'dirsource') {
			$dirsource = $arg1;
		} elseif ($cmd == 'dirdestination') {
			$dirdest = $arg1;
		} elseif ($cmd == 'mkdir') {
			$arg1 = $root. $arg1;
			if (!file_exists($arg1)) {
				if(!@mkdir($arg1, 0777 & octdec($GLOBALS['filemask']))) {
					$context['error_mkdir'] = $arg1;
					require 'view.php';
					$view = &View::getView();
					$view->render($context, 'site-createdir');
					exit;	
				 }
			}
			@chmod($arg1, 0777 & octdec($GLOBALS['filemask']));
		} elseif ($cmd == 'ln' && $usesymlink && $usesymlink != 'non') {
			if ($dirdest == '.' && 	$extensionscripts == 'html' && $arg1 != 'lodelconfig.php') {
				$dest1 = preg_replace("/\.php$/", '.html', $dest1);
			}
			if (!file_exists($dest1)) {
				$toroot = preg_replace(array("/^\.\//", "/([^\/]+)\//", "/[^\/]+$/"),
					array('', '../', ''), "$dirdest/$arg1");
				slink("$toroot$dirsource/$arg1", $dest1);
			}
		} elseif ($cmd == 'cp' || ($cmd == 'ln' && (!$usesymlink || $usesymlink == 'non'))) {
			if ($dirdest == '.' && 	$extensionscripts == 'html' &&	$arg1 != 'lodelconfig.php') {
				$dest1 = preg_replace("/\.php$/", '.html', $dest1);
			}
			mycopyrec("$root$dirsource/$arg1", $dest1);
		} elseif ($cmd == 'touch') {
			if (!file_exists($dest1)) {
				writefile($dest1, '');
			}
			@chmod($dest1, 0666 & octdec($GLOBALS['filemask']));
		} elseif ($cmd == 'htaccess') {
			if (!file_exists("$dest1/.htaccess")) {
				htaccess($dest1);
			}
		} else {
			die ("command inconnue: \"$cmd\"");
		}
	}
	return TRUE;
}

function htaccess ($dir)
{
	$text = "deny from all\n";
	if (file_exists("$dir/.htaccess") && file_get_contents("$dir/.htaccess") == $text) {
		return;
	}
	writefile ("$dir/.htaccess", $text);
	@chmod ("$dir/.htaccess", 0666 & octdec($GLOBALS['filemask']));
}

function slink($src, $dest)
{
	if (file_exists($dest) && file_get_contents($dest)==file_get_contents($src)) {
		return;
	}

	// le lien n'existe pas ou on n'y accede pas.
	@unlink($dest); // detruit le lien s'il existe
	if (!(@symlink($src,$dest))) {
		@chmod(basename($dest), 0777 & octdec($GLOBALS['filemask']));
		symlink($src, $dest);
	}
	if (!file_exists($dest)) {
		echo ("Warning: impossible d'acceder au fichier $src via le lien symbolique $dest<br>");
	}
}

function mycopyrec($src, $dest)
{
	if (is_dir($src)) {
		if (file_exists($dest) && !is_dir($dest)) {
			unlink($dest);
		}
		if (!file_exists($dest)) {
			mkdir($dest, 0777 & octdec($GLOBALS['filemask']));
		}
		@chmod($dest, 0777 & octdec($GLOBALS['filemask']));
		$dir = opendir($src);
		while ($file = readdir($dir)) {
			if ($file == '.' || $file == '..') {
				continue;
			}
			$srcfile  = $src. '/'. $file;
			$destfile = $dest. '/'. $file;
			// pour le moment on ne copie pas les repertoires, que les fichiers
			if (is_file($srcfile)) {
				mycopy($srcfile,$destfile);
			}
		}
		closedir($dir);
	} else {
		mycopy($src,$dest);
	}
}

function mycopy($src,$dest) 
{
	if (file_exists ($dest) && file_get_contents($dest) == file_get_contents($src)) {
		return;
	}
	if (file_exists ($dest)) {
		unlink($dest);
	}
	if (!(@copy($src,$dest))) {
		@chmod(basename($dest), 0777 & octdec($GLOBALS['filemask']));
		copy($src, $dest);
	}
	@chmod($dest, 0666 & octdec($GLOBALS['filemask']));
}

function maj_siteconfig($siteconfig, $var, $val = -1)
{
	// lit le fichier
	$text   = join('', file($siteconfig));
	$search = array(); 
	$rpl = array();
	if (is_array($var)) {
		foreach ($var as $v => $val) {
			if (!preg_match("/^\s*\\\$$v\s*=\s*\".*?\"/m", $text)) {
				die ("la variable \$$v est introuvable dans le fichier de config.");
			}
			array_push($search, "/^(\s*\\\$$v\s*=\s*)\".*?\"/m");
			array_push($rpl, '\\1"'. $val. '"');
		}
	} else {
			if (!preg_match("/^\s*\\\$$var\s*=\s*\".*?\"/m", $text)) {
				die ("la variable \$$var est introuvable dans le fichier de config.");
			}
			array_push($search, "/^(\s*\\\$$var\s*=\s*)\".*?\"/m");
			array_push($rpl, '\\1"'. $val. '"');
	}
	$newtext = preg_replace($search, $rpl, $text);
	if ($newtext == $text) {
		return false;
	}
	// ecrit le fichier
	if (!(unlink($siteconfig)) ) {
		return $newtext;
	}
	if (($f = fopen($siteconfig, 'w')) && fputs($f,$newtext) && fclose($f)) {
		@chmod ($siteconfig, 0666 & octdec($GLOBALS['filemask']));
		return false;
	} else {
		return $newtext;
	}
}

function find_mysql_db_charset($database) {
	$db_collation = mysql_find_db_variable($database, 'collation_database');
	if (is_string($GLOBALS['db_charset']) && is_string($db_collation)) {
				$db_charset = ' CHARACTER SET ' . $GLOBALS['db_charset'] . ' COLLATE ' . $db_collation;
			} else {
				$db_charset = '';
			}
	return $db_charset;
}
?>
