<?php
/**
 * Classe siteManage - Gère un site
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
 * @version CVS:$this->id:
 * @package lodeladmin
 */

require_once 'cachefunc.php';

class siteManage {
	/**
	 * Version lodel du site en cours de traitement
	 * @var int
	 */
	var $version;

	/**
	 * Répertoire de la version lodel utilisée
	 * @var string
	 */	
	var $versiondir;

	/**
	 * Variable contenant les différentes versions de lodel installées
	 * @var string
	 */		
	var $versions;

	/**
	 * Identifiant du site
	 * @var int
	 */
	var $id;

	/**
	 * Critere de sélection du site requete SQL ("id=$id")
	 * @var string
	 */
	var $critere;

	/**
	 * Regex permettant de trouver s'il existe plusieurs versions de lodel installées
	 * @var string
	 */	
	var $lodelhomere;

	/**
	 * Réinstallation ?
	 * @var string
	 */
	var $reinstall;

	/**
	 * Base de donnée unique ?
	 * @var bool
	 */
	var $singledatabase;

	/**
	 * Nom de la base de données principale
	 * @var string
	 */
	var $database;

	/**
	 * Un seul site ?
	 * @var bool
	 */
	var $maindefault;

	/**
	 * Informations du site
	 * @var array
	 */
	var $context;

	/**
	 * Téléchargement du fichier siteconfig.php ?
	 * @var int
	 */
	var $downloadsiteconfig;


	/**
	 * Constructeur
	 *
	 * Instancie un objet de la classe
	 *
	 * @param int $id identifiant du site
	 * @param array $context le contexte passé par référence
	 */
	function siteManage($id, &$context)
	{
		$this->context['id'] = $context['id'] ? $context['id'] : $id;
		$this->id = intval($id);
		$this->critere = "id='$id'";
		$this->lodelhomere = "/^lodel(-[\w.]+)$/";
		$this->context = $context;
	}

	/**
	 * Accesseur
	 *
	 * Cette fonction renvoit la variable $_v passée en paramètre
	 *
	 * @param var $_v variable à renvoyer
	 */
        function get( $_v )
	{
		return $this->$_v;
	}

	/**
	 * Accesseur
	 *
	 * Cette fonction alloue la valeur $_a à la variable $_v
	 *
	 * @param var $_v variable à modifier
	 * @param var $_a valeur à allouer
	 */
        function set( $_v, $_a )
	{
		$this->$_v = $_a;
        }

	/**
	 * Restoration d'un site supprimé
	 *
	 * Cette fonction restaure un site préalablement supprimé
	 */
	function restore()
	{
		mysql_query(lq("UPDATE #_TP_sites SET status=abs(status) WHERE ".$this->critere)) or dberror();
		update();
		require_once 'view.php';
		$view = &View::getView();
		$view->back(); // on revient
	}

	/**
	 * Suppression d'un site
	 *
	 * Cette fonction supprime un site
	 */
	function remove()
	{
		mysql_query(lq("UPDATE #_TP_sites SET status=-abs(status) WHERE ".$this->critere)) or dberror();
		update();
		require_once 'view.php';
		$view = &View::getView();
		$view->back(); // on revient
	}

	/**
	 * Version du site
	 *
	 * Cette fonction retourne la version de lodel du site en cours de traitement
	 *
	 * @param var $dir répertoire à traiter
	 */	
	function getsiteversion($dir)
	{ 
		if (!file_exists($dir. 'siteconfig.php')) {
			die("ERROR: internal error while reinstalling every site. dir is $dir");
		}
		include ($dir. 'siteconfig.php');
		return $version;
	}

	/**
	 * Réinstallation d'un site
	 *
	 * Cette fonction lance la procédure de réinstallation d'un site
	 *
	 * @param var $dir répertoire à traiter
	 */	
	function reinstall($dir)
	{
		require_once 'connect.php';
	
		$result = $db->execute(lq("SELECT path,name FROM #_MTP_sites WHERE status>0")) or dberror();
		
		while(!$result->EOF) {
			$row = $result->fields;
			// on peut installer les fichiers
			if (!$row['path']) {
				$row['path'] = '/'. $row['name'];
			}
			$root    = str_replace('//', '/', LODELROOT. $row['path']). '/';
			$this->version = $this->getsiteversion($root);
			if ($row['path'] == '/') { // c'est un peu sale ca.
				$this->install_file($root, "lodel-".$this->version."/src", '');
			} else {
				$this->install_file($root, "../lodel-".$this->version."/src", LODELROOT);
			}
	
			// clear the CACHEs
			require_once 'cachefunc.php';
			removefilesincache(getCachePath(), getCachePath('edition') , getCachePath('admin'));
	
			$result->MoveNext();
		}
	
		header('location: '. LODELROOT. 'index.php');
		exit;
	}

	/**
	 * Edition d'un site
	 *
	 * Cette fonction permet d'éditer les informations d'un site
	 */	
	function manageSite()
	{
		//on extrait les variables contenues dans $_POST
		extract_post();
		//on les alloue à notre contexte
		$this->context = $GLOBALS['context'];
		if ($this->maindefault) { // site par defaut ?
			$this->context['title']  = 'Site principal';
			$this->context['name']   = 'principal';
			$this->context['atroot'] = true;
		}
		
		// validation
		do {

			if (!$this->context['title']) {
				$this->context['error_title'] = $err = 1;
			}
			if (!$this->id && (!$this->context['name'] || !preg_match("/^[a-z0-9\-]+$/",$this->context['name']))) { $this->context['error_name'] = $err = 1;
			}
			if ($err) {
				break;
			}
			require_once 'connect.php';
	
			// verifie qu'on a qu'un site si on est en singledatabase
			if (!$this->id && $this->singledatabase == 'on') {
				$result = mysql_query ("SELECT COUNT(*) FROM `$GLOBALS[tp]sites` WHERE status>-32 AND name!='". $this->context['name']. "'") or die (mysql_error());
				list($numsite) = mysql_fetch_row($result);
				if ($numsite >= 1) {
					die("ERROR<br />\nIl n'est pas possible actuellement d'avoir plusieurs sites sur une unique base de données : il faut utiliser plusieurs bases de données.");
				}
			}
	
			// édition d'un site : lit les informations options, status, etc.
			if ($this->id) {
				$result = mysql_query ("SELECT status,name,path FROM `$GLOBALS[tp]sites` WHERE id='".$this->id."'") or die (mysql_error());
				list($status,$name,$this->context['path']) = mysql_fetch_row($result);
				$this->context['name'] = $name;
			} else { // création d'un site
				// vérifie que le nom (base de données + répertoire du site) n'est pas déjà utilisé
				$result = mysql_query ("SELECT name FROM `$GLOBALS[tp]sites`") or die (mysql_error());
				while ($row = mysql_fetch_array($result)) {
					$sites[] = $row['name'];
				}
				if(is_array($sites)) {
					if(in_array($this->context['name'], $sites)) {
						$this->context['error_unique_name'] = $err = 1;
						break;
					}
				}
	
				$options = '';
				$status  = -32; // -32 signifie en creation
				if ($this->context['atroot']) {
					$this->context['path'] = '/';
				}
				if (!$this->context['path']) {
					$this->context['path'] = '/'. $this->context['name'];
				}
			}
			if (!$this->context['url']) {
				$this->context['url'] = 'http://'. $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT'] ? ':'. $_SERVER['SERVER_PORT'] : ""). preg_replace("/\blodeladmin-?\d*(\.\d*)?\/.*/", '', $_SERVER['REQUEST_URI']). substr($this->context['path'], 1);
			}
			
			if ($this->reinstall) {
				$status = -32;
			}
	
			//suppression de l'eventuel / a la fin de l'url
			$this->context['url'] = preg_replace("/\/$/", '', $this->context[url]);

			// Ajout de slashes pour autoriser les guillemets dans le titre et le sous-titre du site
			$this->context['title'] = magic_addslashes($this->context['title']);
			$this->context['subtitle'] = magic_addslashes($this->context['subtitle']);
	
			mysql_query("REPLACE INTO `$GLOBALS[tp]sites` (id,title,name,path,url,subtitle,status) VALUES ('".$this->id."','".$this->context['title']."','".$this->context['name']."','".$this->context['path']."','".$this->context['url']."','".$this->context['subtitle']."','".$status."')") or die (mysql_error());
	
			update();

			if ($status>-32) {
				require_once 'view.php';
				$view = &View::getView();
				$view->back(); // on revient, le site n'est pas en creation
			}
	
			if (!$this->id) {
				$this->context['id'] = $this->id = mysql_insert_id();
			}
			return true;
		} while (0);		
	}

	/**
	 * Versions installées sur le serveur web
	 *
	 * Cette fonction cherche et alloue à la variable $versions les différentes versions installées sur le serveur web
	 */	
	function cherche_version () 
	{
		$dir = opendir(LODELROOT);
		if (!$dir) {
			die ("impossible d'acceder en ecriture sur le repertoire racine");
		}
		$this->versions = array();
		while ($file = readdir($dir)) {
			if ($file[0] === '.') {
				continue;
			}
			if (is_dir(LODELROOT.$file) && preg_match($this->lodelhomere,$file) && is_dir(LODELROOT. $file. '/src')) {
				if (!(@include(LODELROOT. "$file/src/siteconfig.php"))) {
					echo "ERROR: Unable to open the file: $file/src/siteconfig.php<br />";
				} else {
					$this->versions[$file]=$this->version ? $this->version : "devel";
				}
			}
		}
	}

	/**
	 * Sélection de la version de lodel à installer
	 *
	 * Cette fonction affiche les différentes versions installées sur le serveur web
	 * Et permet de choisir celle que l'on veut installer
	 */	
	function makeselectversion()
	{
		foreach ($this->versions as $dir => $ver) {
			$selected = $this->versiondir == $dir ? "selected=\"selected\"" : '';
			echo "<option value=\"$dir\"$selected>$dir  ($ver)</option>\n";
		}
	}

	/**
	 * Sélection de notre version de lodel
	 *
	 * Cette fonction sélectionne la version de lodel du site que l'on veut installer
	 */	
	function selectVersion()
	{
		if  (!$this->versiondir) {
			$this->cherche_version();
			
			// ok, maintenant on connait les versions
			$this->context['countversions'] = count($this->versions);
			if ($this->context['countversions'] == 1) {// ok, une seule version, on la choisit
				list($this->versiondir) = array_keys($this->versions);
			} elseif ($this->context['countversions'] == 0) { // aie, aucune version on crach
				die ("Verifiez le package que vous avez, il manque le repertoire lodel/src. L'installation ne peut etre poursuivie !");
			} else { // il y en a plusieurs, faut choisir
				$this->context['count'] = count($this->versions);
				$this->makeselectversion();
				require_once 'view.php';
				$view = &View::getView();
				$view->render($this->context, 'site-version');
				return false;
			}
		}
		$this->context['versiondir'] =  $this->versiondir;
		return true;
	}

	/**
	 * Installation de lodel
	 *
	 * Cette fonction installe lodel
	 *
	 * @param var $root chemin de la racine du serveur web
	 * @param var $homesite chemin du répertoire du site
	 * @param var $homelodel chemin du répertoire de lodel
	 */	
	function install_file($root, $homesite, $homelodel)
	{
		@include 'lodelconfig.php';
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
						$this->context['error_mkdir'] = $arg1;
						require_once 'view.php';
						$view = &View::getView();
						$view->render($this->context, 'site-createdir');
						exit;	
					}
				}
				@chmod($arg1, 0777 & octdec($GLOBALS['filemask']));
			} elseif ($cmd == 'ln' && $usesymlink && $usesymlink != 'non') {
				if ($dirdest == '.' && $extensionscripts == 'html' && $arg1 != 'lodelconfig.php') {
					$dest1 = preg_replace("/\.php$/", '.html', $dest1);
				}
				if (!file_exists($dest1)) {
					$toroot = preg_replace(array("/^\.\//", "/([^\/]+)\//", "/[^\/]+$/"),
						array('', '../', ''), "$dirdest/$arg1");
					$this->slink("$toroot$dirsource/$arg1", $dest1);
				}
			} elseif ($cmd == 'cp' || ($cmd == 'ln' && (!$usesymlink || $usesymlink == 'non'))) {
				if ($dirdest == '.' && $extensionscripts == 'html' && $arg1 != 'lodelconfig.php') {
					$dest1 = preg_replace("/\.php$/", '.html', $dest1);
				}
				$this->mycopyrec("$root$dirsource/$arg1", $dest1);
			} elseif ($cmd == 'touch') {
				if (!file_exists($dest1)) {
					writefile($dest1, '');
				}
				@chmod($dest1, 0666 & octdec($GLOBALS['filemask']));
			} elseif ($cmd == 'htaccess') {
				if (!file_exists("$dest1/.htaccess")) {
					$this->htaccess($dest1);
				}
			} else {
				die ("command inconnue: \"$cmd\"");
			}
		}
		return TRUE;
	}

	/**
	 * Protection du répertoire par htaccess
	 *
	 * Cette fonction crée un htaccess contenant 'deny from all' dans le répertoire '$dir'
	 *
	 * @param var $dir répertoire dans lequel sera créé le htaccess
	 */	
	function htaccess ($dir)
	{
		$text = "deny from all\n";
		if (file_exists("$dir/.htaccess") && file_get_contents("$dir/.htaccess") == $text) {
			return;
		}
		writefile ("$dir/.htaccess", $text);
		@chmod ("$dir/.htaccess", 0666 & octdec($GLOBALS['filemask']));
	}

	/**
	 * Création des liens symboliques
	 *
	 * Cette fonction crée ou modifie les liens symboliques
	 *
	 * @param var $src source du lien
	 * @param var $dest destination du lien
	 */	
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
			echo ("Warning: impossible d'acceder au fichier $src via le lien symbolique $dest<br />");
		}
	}

	/**
	 * Copie des fichiers
	 *
	 * Cette fonction copie les fichiers de lodel
	 *
	 * @param var $src source du fichier
	 * @param var $dest destination du fichier
	 */	
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
					$this->mycopy($srcfile,$destfile);
				}
			}
			closedir($dir);
		} else {
			$this->mycopy($src,$dest);
		}
	}

	/**
	 * Copie des répertoires
	 *
	 * Cette fonction copie les répertoires de lodel
	 *
	 * @param var $src source du répertoire
	 * @param var $dest destination du répertoire
	 */	
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
	
	/**
	 * Charset de la base de données
	 *
	 * Cette fonction retourne le charset utilisé par la base de données '$database'
	 *
	 * @param var $database nom de la base de donnée à traiter
	 */		
	function find_mysql_db_charset($database) {
		$db_collation = mysql_find_db_variable($this->database, 'collation_database');
		if (is_string($GLOBALS['db_charset']) && is_string($db_collation)) {
					$db_charset = ' CHARACTER SET ' . $GLOBALS['db_charset'] . ' COLLATE ' . $db_collation;
				} else {
					$db_charset = '';
				}
		return $db_charset;
	}

	/**
	 * Création de la base de données
	 *
	 * Cette fonction crée la base de données si celle-ci n'existe pas déjà
	 *
	 */	
	function createDB($lodeldo)
	{
		// creation de la DataBase si besoin
		if (!$this->context['name']) {
			die ('probleme interne 1');
		}
		
		do { // bloc de controle
			if ($this->singledatabase == 'on') {
				break;
			}
	
			// check if the database existe
			require_once 'connect.php';
			$db_list = mysql_list_dbs();
			$i = 0;
			$cnt = mysql_num_rows($db_list);
			while ($i < $cnt) {
				if ($this->context['dbname'] == mysql_db_name($db_list, $i)) {
					return true; // la database existe
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
				$db_charset = $this->find_mysql_db_charset($GLOBALS['currentdb']);
			} else { 
				$db_charset = '';
			}
			$this->context['command1']="CREATE DATABASE `".$this->context['dbname']."`$db_charset";
			$this->context['command2'] = "GRANT ALL ON `".$this->context['dbname']."`.* TO $dbusername@$dbhost";
			$pass = $dbpasswd ? " IDENTIFIED BY '$dbpasswd'" : '';
			
			if ($this->context['installoption'] == '2' && !$lodeldo) {
				$this->context['dbusername'] = $dbusername;
				$this->context['dbhost']     = $dbhost;
				require_once 'view.php';
				$view = &View::getView();
				$view->render($this->context, 'site-createdb');
				return false;
			}
			if (!@mysql_query($this->context['command1']) || !@mysql_query($this->context['command2']. $pass)) {
				$this->context['error']      = mysql_error();
				$this->context['dbusername'] = $dbusername;
				$this->context['dbhost']     =$dbhost;
				require_once 'view.php';
				$view = &View::getView();
				$view->render($this->context, 'site-createdb');
				return false;
			}

		} while (0);
		return true;
	}

	/**
	 * Gestion des erreurs de création des tables
	 *
	 * Cette fonction gère les erreurs retournées lors de la création des tables
	 *
	 * @param var &$context contexte du site
	 * @param var $funcname nom de la fonction à appeller (nom = code_do_$funcname)
	 */	
	function loop_errors_createtables(&$context, $funcname)
	{
		$error = $this->context['error_createtables'];
		do {
			$localcontext['command'] = array_shift($error);
			$localcontext['error']   = array_shift($error);
			call_user_func("code_do_$funcname", array_merge($this->context, $localcontext));
		} while ($error);
	}

	/**
	 * Création des tables
	 *
	 * Cette fonction crée les tables lors de l'installation
	 *
	 */	
	function createTables()
	{
		if (!$this->context['name']) {
				die ("probleme interne 2");
		}

		require_once 'connect.php';
		mysql_select_db($this->context['dbname']); //selectionne la base de donnée du site
		if (!file_exists(LODELROOT. $this->versiondir."/install/init-site.sql")) {
			die ("impossible de faire l'installation, le fichier init-site.sql est absent");
		}
		
		$text = join('', file(LODELROOT. $this->versiondir."/install/init-site.sql"));
		$text.= "\n";
			
		if ($GLOBALS['version_mysql'] > 40) {
			$db_charset = $this->find_mysql_db_charset($this->context['dbname']);
			mysql_select_db($this->context['dbname']); //selectionne la base de donnée du site
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
			$this->context['error_createtables'] = $error;
			require_once 'view.php';
			$view = &View::getView();
			$view->render($this->context, 'site-createtables');
			return false;
			}
		mysql_select_db($this->database);
		return true;
	}	

	/**
	 * Procédure de création des répertoires
	 *
	 * Cette fonction gère la création des répertoires de lodel
	 *
	 */
	function createDir($lodeldo, $mano, $filemask)
	{

		if(!$this->versiondir)
			$this->selectVersion();
		if (!$this->context['path']) {
			$this->context['path'] = '/'. $this->context['name'];
		}
		$dir = LODELROOT. $this->context['path'];
		if (!file_exists($dir) || !@opendir($dir)) {

			// il faut creer le repertoire rep
			if ($this->context['installoption'] == '2' && !$lodeldo) {
				if ($mano) {
					$this->context['error_nonexists'] = !file_exists($dir);
					$this->context['error_nonaccess'] = !@opendir($dir);
				
				}
				require_once 'view.php';
				$view = &View::getView();
				$view->render($this->context, 'site-createdir');
				return false;
			}
			// on essaie
			if (!file_exists($dir) && !@mkdir($dir, 0777 & octdec($filemask))) {
				// on y arrive pas... pas les droits surement
				$this->context['error_mkdir'] = 1;
				require_once 'view.php';
				$view = &View::getView();
				$view->render($this->context, 'site-createdir');
				return false;
			}
			@chmod($dir, 0777 & octdec($filemask));
		}
		
		// on essaie d'ecrire dans tpl si root
		if ($this->context['path'] == '/') {
			if (!@writefile(LODELROOT. 'tpl/testecriture', '')) {
				$this->context['error_tplaccess'] = 1;

				require_once 'view.php';
				$view = &View::getView();
				$view->render($this->context, 'site-createdir');
				return false;
			} else {
				unlink(LODELROOT. 'tpl/testecriture');
			}
		}
		return true;
	}

	/**
	 * MAJ du fichier de configuration du site
	 *
	 * Cette fonction met à jour le fichier siteconfig.php
	 *
	 * @param var $siteconfig nom du fichier
	 * @param var $var nom des sites
	 * @param var $val variable de travail pour la boucle foreach
	 */
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
			return true;
		}
		// ecrit le fichier
		if (!(unlink($siteconfig)) ) {
			return false;
		}
		if (($f = fopen($siteconfig, 'w')) && fputs($f,$newtext) && fclose($f)) {
			@chmod ($siteconfig, 0666 & octdec($GLOBALS['filemask']));
			return true;
		} else {
			return false;
		}
	}	

	/**
	 * Gestion des fichiers
	 *
	 * Cette fonction gère l'installation des fichiers de lodel
	 *
	 * @param string $lodeldo 
	 */
	function manageFiles($lodeldo)
	{

		// verifie la presence ou copie les fichiers necessaires
		// cherche dans le fichier install-file.dat les fichiers a copier
		// on peut installer les fichiers
		if (!$this->context['path']) {
			$this->context['path'] = '/'. $this->context['name'];
		}
		$root = str_replace('//', '/', LODELROOT. $this->context['path']). '/';
		$siteconfigcache = getCachePath('siteconfig.php');
		if ($this->downloadsiteconfig) { // download the siteconfig
			download($siteconfigcache, 'siteconfig.php');
			return false;
		}
		if (file_exists($siteconfigcache)) {
			unlink($siteconfigcache);
		}
		$atroot = $this->context['path'] == '/' ? 'root' : '';
		if (!copy(LODELROOT. $this->versiondir."/src/siteconfig$atroot.php", $siteconfigcache)) {
			die("ERROR: unable to write in CACHE.");
		}
		if(!$this->maj_siteconfig($siteconfigcache, array('site' => $this->context['name'])))
			return false;
		$siteconfigdest = $root. 'siteconfig.php';

		// cherche si le fichier n'existe pas ou s'il est different de l'original
		if (!file_exists($siteconfigdest) || file_get_contents($siteconfigcache) != file_get_contents($siteconfigdest)) {
			if ($this->context['installoption'] == '2' && !$lodeldo) {
				require_once 'view.php';
				$view = &View::getView();
				$view->render($this->context, 'site-file');
				return false;
			}
			@unlink($siteconfigdest); // try to delete before copying.
			// try to copy now.
			if (!@copy($siteconfigcache,$siteconfigdest)) {
				$this->context['siteconfigsrc']  = $siteconfigcache;
				$this->context['siteconfigdest'] = $siteconfigdest;
				$this->context['error_writing']    = 1;
				require_once 'view.php';
				$view = &View::getView();
				$view->render($this->context, 'site-file');
				return false;
			}
			@chmod ($siteconfigdest, 0666 & octdec($GLOBALS['filemask']));
		}
		// ok siteconfig est copie.
		if ($this->context['path'] == '/') { // c'est un peu sale ca.
			$this->install_file($root, $this->versiondir."/src", '');
		} else {
			$this->install_file($root, "../".$this->versiondir."/src", LODELROOT);
		}
		
		// clear the CACHEs
		require_once 'cachefunc.php';
		removefilesincache(getCachePath(), getCachePath('edition'), getCachePath('admin'));
	
		// ok on a fini, on change le status du site
		mysql_select_db($GLOBALS[database]);
		mysql_query ("UPDATE `$GLOBALS[tp]sites` SET status=1 WHERE id='".$this->id."'") or die (mysql_error());

		
		// ajouter le modele editorial ?
		if ($GLOBALS[singledatabase]!="on") {
			if($this->context['name'] != '')
				$pattern = $this->context['name'];
			elseif($GLOBALS['name'] != '')
				$pattern = $GLOBALS['name'];

			if(!preg_match("`".$pattern."`", $this->context['dbname']))
			{
				$this->context['dbname'] .= $this->context['name'];
			}
			mysql_select_db($this->context['dbname']);
		}

		$import = true;
		// verifie qu'on peut importer le modele.
		foreach(array('types', 'tablefields', 'persontypes', 'entrytypes') as $table) {
			$result = mysql_query("SELECT 1 FROM `$GLOBALS[tp]$table` WHERE status>-64 LIMIT 0,1") or die(mysql_error());
			if (mysql_num_rows($result)) {
				$import = false;
				break;
			}
		}
		
		if (!$this->context['path']) {
			$this->context['path'] = '/'. $this->context['rep'];
		}
		if ($import) {
			$go = $this->context['url']. "/lodel/admin/index.php?do=importmodel&lo=data";
		} else {
			$go = $this->context['url']. '/lodel/edition';
		}
		if (!headers_sent()) {
			header("location: $go");
			exit;
		} else {
			echo "<h2>Warnings seem to appear on this page. Since Lodel may be correctly installed anyway, you may go on by following <a href=\"$go\">this link</a>. Please report the problem to help us to improve Lodel.</h2>";
			exit;
		}
		
		return true;
	}

	/**
	 * Maintenance des sites
	 *
	 * Cette fonction gère la mise en maintenance des sites
	 *
	 * @param int type application de la maintenance : 1 = tous en ligne, 2 = tous en maintenance
	 * @author Pierre-Alain Mignot
	 */
	function maintenance($type)
	{
 		if($this->id > 0) {
			$res = mysql_query(lq("SELECT status FROM #_TP_sites WHERE ".$this->critere.""));
			$row = mysql_fetch_row($res);
			if($row[0] == 32) {
				$status = -65;
			} elseif($row[0] == -65) {
				$status = 32;
			} else {
				$status = $row[0] == -64 ? 1 : -64;
			}
			mysql_query(lq("UPDATE #_TP_sites SET status = ".$status." WHERE ".$this->critere.""));
		}
		elseif($this->id == 0) {
			if(intval($type) === 1) {
				mysql_query(lq("UPDATE #_TP_sites SET status = 1 WHERE status = -64"));
				mysql_query(lq("UPDATE #_TP_sites SET status = 32 WHERE status = -65"));
			} elseif(intval($type) === 2) {
				mysql_query(lq("UPDATE #_TP_sites SET status = -64 WHERE status = 1"));
				mysql_query(lq("UPDATE #_TP_sites SET status = -65 WHERE status = 32"));
			}
		}
		if (!headers_sent()) {
			header("location: index.php?do=list&lo=sites&clearcache=oui");
			exit;
		} else {
			echo "<h2>Warnings seem to appear on this page. You may go on by following <a href=\"index.php?do=list&lo=sites&clearcache=oui\">this link</a>. Please report the problem to help us to improve Lodel.</h2>";
			exit;
		}
	}
}
