<?php
/**
 * Fichier racine de lodeladmin
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
 * @author Sophie Malafosse
 * @author Pierre-Alain MIGNOT
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodeladmin
 */

class Install {

	/**
	 * Suffixe de la version de Lodel
	 * @var string
	 */
	var $versionsuffix;

	/**
	 * Version à installer
	 * @var string
	 */	
	var $versioninstall;

	/**
	 * Fichier de configuration Lodel
	 * @var string
	 */
	var $lodelconfig;

	/**
	 * Plateforme Lodel
	 * @var string
	 */
	var $plateform;

	/**
	 * Répertoire de la plateforme
	 * @var string
	 */
	var $plateformdir;

	/**
	 * Plateforme lodelconfig
	 * @var string
	 */
	var $lodelconfigplatform;

	/**
	 * Tableau des répertoires à protéger avec un htaccess
	 * @var string
	 */
	var $protecteddir;

	/**
	 * Choix de la langue
	 * @var string
	 */
	var $langChoice;

	/**
	 * Chmod ?
	 * @var string
	 */
	var $have_chmod;

	/**
	 * installoption
	 * @var int
	 */
	var $installoption;


	/**
	 * Constructeur
	 *
	 * Instancie un objet de la classe
	 *
	 * @param string $lodelconfig chemin vers fichier lodelconfig temporaire
	 * @param array $context le contexte passé par référence
	 */
	function Install($lodelconfig, $have_chmod, $plateformdir)
	{
		$this->lodelconfig = $lodelconfig;
		$this->have_chmod = $have_chmod;
		$this->plateformdir = $plateformdir;
	}

	/**
	 * Accesseur
	 *
	 * Cette fonction renvoit la variable $var passée en paramètre
	 *
	 * @param var $var variable à renvoyer
	 */
	function get($var)
	{
		return $this->$var;
	}

	/**
	 * Accesseur
	 *
	 * Cette fonction alloue la valeur $valeur à la variable $var
	 *
	 * @param var $var variable à modifier
	 * @param var $valeur valeur à allouer
	 */
	function set($var, $valeur)
	{
		$this->$var = $valeur;
	}


	/**
	 * Test connexion à la base de données
	 *
	 * Cette fonction tente de se connecter à la base de données, met le charset par defaut et identifie l'utilisateur
	 * comme administrateur si jamais il existe un enregistrement dans la table username
	 */
	function testInstallDB()
	{
		require_once "../lodel".$this->versionsuffix."/scripts/auth.php";
		@include($this->lodelconfig);
		if (@mysql_connect($dbhost,$dbusername,$dbpasswd)) {
			@mysql_select_db($database);
			$this->set_mysql_charset();
		
			// test whether we access to a DB and whether the table users exists or not and whether it is empty or not.
		
			$result=mysql_query("SELECT username FROM `".$tableprefix."users` LIMIT 0,1");
			if ($result && mysql_num_rows($result)>0)
				authenticate(LEVEL_ADMINLODEL);
		} else {
			// well, no access to the DB but a lodelconfig ?
			// ask for erasing the lodelconfig.php ?
			$this->problem("lodelconfig_but_no_database");
		}
	}

	/**
	 * Installation de la configuration
	 *
	 * Cette fonction copie le fichier de configuration dans le CACHE, chmod et MAJ de celui-ci
	 *
	 * @param string $testfile fichier sur lequel deviner filemask
	 */
	function installConf($testfile)
	{
		$this->plateform=preg_replace("/[^A-Za-z_-]/","",$this->plateform);
		if (!$this->plateform) $this->plateform="default";
		
		$this->lodelconfigplatform=$this->plateformdir."/lodelconfig-".$this->plateform.".php";
		if (file_exists($this->lodelconfigplatform)) {
			// essai de copier ce fichier dans le CACHE
			if (!@copy($this->lodelconfigplatform,$this->lodelconfig)) { die ("probl&egrave;me de droits... &eacute;trange on a d&eacute;j&agrave; v&eacute;rifi&eacute;"); }
			if (file_exists(LODELROOT."lodelloader.php")) {
				// the installer has been use, let's chmod safely
				$chmod=fileperms(LODELROOT."lodel-".$this->versioninstall);
			} else {
				$chmod=0600;  // c'est plus sur, surtout a cause du mot de passe sur la DB qui apparaitra dans ce fichier.
			}
			@chmod($this->lodelconfig,$chmod);
			@include($this->lodelconfig);
			$this->maj_lodelconfig(array("home"=>'$pathroot/lodel'.$this->versionsuffix.'/scripts/'));
		} else {
			die("ERROR: ".$this->lodelconfigplatform." does not exist. Internal error, please report this bug.");
		}
		$arr=array();
		$needoptions=false;
		$arr['installoption']=$this->installoption;
		
		// guess the urlroot
		$me=$_SERVER['PHP_SELF'];
		if ($me) {
			// enleve moi
			$urlroot=preg_replace("/\/+lodeladmin".$this->versionsuffix."\/install.php$/","",$me);
			if ($urlroot==$me) die("ERROR: the install.php script is not at the right place, please report this bug.");
			if (LODELROOT!="../") die("ERROR: the lodeladmin directory has been moved, please report this bug.");
			
			$arr['urlroot']=$urlroot."/";
		}
		
		// is there a filemask ?
		
		if ($_REQUEST['filemask']) {
			// passed via the URL
			$arr['filemask']="0".$_REQUEST['filemask'];
		} elseif ($filemask) {
			// was in the previous lodelconfig.php
			$arr['filemask']=$filemask;
		} else {
			$arr['filemask']="0".decoct($this->guessfilemask($testfile));
		}
		
		$arr['installlang']=$this->installlang;
		
		if ($this->installoption==1) {
			// try to guess the options.
			// use pclzip ?
			if (function_exists("gzopen")) {
				$arr['unzipcmd']=$arr['zipcmd']="pclzip";
			} else {
				$arr['unzipcmd']=$arr['zipcmd']="";
				$needoptions=true;
			}
			$arr['extensionscripts']="php";
		}
		
		$arr['chooseoptions']=($needoptions && $this->installoption==1 ? "oui" : "non");
		return $this->maj_lodelconfig($arr);
	}

	/**
	 * Mise à jour configuration base de données
	 *
	 * Cette fonction met à jour les informations de connexion au serveur de base de données dans le fichier de configuration
	 *
	 * @param string $newdbusername identifiant
	 * @param string $newdbpasswd mot de passe
	 * @param string $newdbhost adresse du serveur
	 */
	function majConfDB($newdbusername, $newdbpasswd, $newdbhost)
	{
  		$this->maj_lodelconfig(array("dbusername"=>$newdbusername, "dbpasswd"=>$newdbpasswd, "dbhost"=>$newdbhost));
	}

	/**
	 * Installation base de données
	 *
	 * Cette fonction s'occupe de l'installation de la base de données
	 *
	 * @param string $erasetables on efface les tables d'une base de données existante ?
	 * @param string $singledatabase est-on en mode base de données unique ?
	 * @param string $newdatabase nom de la base de données
	 * @param string $newsingledatabase
	 * @param string $newtableprefix prefixe des tables pour installation multi site
	 * @param string $createdatabase on crée la base de données ou pas ?
	 * @param string $existingdatabase on utilise une base existante ?
	 */
	function manageDB($erasetables, $singledatabase, $newdatabase, $newsingledatabase, $newtableprefix, $createdatabase, $existingdatabase)
	{
		if($erasetables)
		{
			@include($this->lodelconfig);    // insert the lodelconfig. Should not be a problem.
			@mysql_connect($dbhost,$dbusername,$dbpasswd); // connect
			
			/*$version_mysql_num = explode(".", substr(mysql_get_server_info(), 0, 3));
			if ($version_mysql_num[0].$version_mysql_num[1] > 40)
					{ mysql_query('SET NAMES UTF8'); }*/
			@mysql_select_db($database); // selectionne la database

			$this->set_mysql_charset();
			// erase the table of each site
			
			$result=mysql_query("SELECT name FROM ".$tableprefix."sites") or die (mysql_error());

			if ($singledatabase) {
				// currently singledatabase implies single site ! That's shame but...
				// Let's destroyed everything in the database with the prefix !
				if (!$tableprefix) {
					// we can't destroy... too dangerous. Should find another solution.
					die("Sans tableprefix les tables ne peuvent pas etre efface en toute securite. Veuillez effacer vous-même les tables de Lodel. Merci.");
				} else {
					// get all table names.
					$result=mysql_list_tables($database);

					while ($row = mysql_fetch_row($result)) {
						if (preg_match("/^{$tableprefix}/",$row[0])) {
							// let's drop it
							mysql_query("DROP TABLE $row[0]");
						}
					}
				}
			} else {
				die(utf8_encode("<p>L'effacement des tables avec plusieurs bases de données n'est pas implementé. Veuillez effacer les bases de données vous même. Merci.</p>"));
			}
			// erase the main tables below.
		} else { // normal case
			$set=array();
			
			@include($this->lodelconfig);    // insert the lodelconfig. Should not be a problem.
			
			if ($installoption>1) {
				$set['singledatabase']=$newsingledatabase ? "on" : "";
				$set['tableprefix']=$newtableprefix;
			}

			if ($newdatabase==-1) $newdatabase=$existingdatabase;
			if ($newdatabase==-2) { 
				$newdatabase=$createdatabase;
			} else {
				$createdatabase="";
			}
			$set['database']=$newdatabase;

			$this->maj_lodelconfig($set);
			if ($createdatabase) { // il faut creer la database
				@mysql_connect($dbhost,$dbusername,$dbpasswd); // connect
					$version_mysql_num = explode(".", substr(mysql_get_server_info(), 0, 3));
					if ($version_mysql_num[0].$version_mysql_num[1] > 40) {
						mysql_query('SET NAMES UTF8');
						$db_charset = 'CHARACTER SET utf8 COLLATE utf8_general_ci';
					} else { 
						$db_charset = '';
					}
				if (!@mysql_query("CREATE DATABASE `$createdatabase` $db_charset")) {
					return false;
				}
			} else {
			// check whether the database contains something. If so, we just ask what to do
// 				@mysql_connect($dbhost,$dbusername,$dbpasswd); // connect
 //				if(@mysql_select_db($newdatabase)){
 //					return false;
 //				}
			}
		}
		return true;
	}

	/**
	 * Création administrateur
	 *
	 * Cette fonction permet de créer le premier utilisateur (administrateur)
	 *
	 * @param string $adminusername login
	 * @param string $adminpasswd mot de passe premier input du formulaire
	 * @param string $adminpasswd2 mot de passe deuxieme input du formulaire
	 * @param string $lang langue par défaut pour l'utilisateur créé
	 * @param string $site site lié à l'utilisation en cours de création
	 */
	function manageAdmin($adminusername, $adminpasswd, $adminpasswd2, $lang, $site, $adminemail)
	{
		@include($this->lodelconfig); // insere lodelconfig, normalement pas de probleme

		if(empty($adminusername) || empty($adminpasswd)) {
			return "error_user";
		}
		
		if (strlen($adminpasswd) < 3 || strlen($adminpasswd) > 255 || !preg_match("/^[0-9A-Za-z_;.?!@:,&]+$/", $adminpasswd)) {
			return "error_passwd";	
		}
		
		if ($adminpasswd2 != $adminpasswd) {
			return "error_confirmpasswd";
		}
		if(empty($adminemail)) return 'error_email';
		@mysql_connect($dbhost,$dbusername,$dbpasswd); // connect
		@mysql_select_db($database); // selectionne la database
		$this->set_mysql_charset();

		$adminusername=addslashes($adminusername);
		$pass=md5($adminpasswd.$adminusername);
		unset($adminpasswd);
		if (!preg_match("/^\w{2}(-\w{2})?/",$lang)) die("ERROR: invalid lang");
		
		if (!@mysql_query("REPLACE INTO ".$tableprefix."users (username,passwd,email,userrights,lang) VALUES ('$adminusername','$pass','$adminemail',128,'$lang')")) {
			return "error_create";
		}

		unset($pass);
		return true;
	}

	/**
	 * Installation htaccess
	 *
	 * Cette fonction installe les .htaccess dans les répertoires contenus dans la variable privée protecteddir
	 *
	 * @param string $verify 
	 * @param string $write Lodel installe lui même les htaccess ?
	 * @param string $nohtaccess pas de htaccess ?
	 */
	function set_htaccess($verify, $write, $nohtaccess)
	{	
		$currentLodelDir = "lodel".$this->versionsuffix;
		
		if ($verify || $write) $this->maj_lodelconfig("htaccess","on");
		if ($nohtaccess) $this->maj_lodelconfig("htaccess","off");
		if ($write) {
			$erreur_htaccesswrite=0;
			foreach ($this->protecteddir as $dir) {
				if (file_exists(LODELROOT.$dir) && !file_exists(LODELROOT.$dir."/.htaccess")) {
					$file=@fopen(LODELROOT.$dir."/.htaccess","w");
					if (!$file) {
						$erreur_htaccesswrite=1;
					} else {
						fputs($file,"deny from all\n");
						fclose($file);
					}
				}
			}
		}
		return $erreur_htaccesswrite;
	}

	/**
	 * Mise à jour des informations du site
	 *
	 * Cette fonction met à jour le fichier de configuration du site en cours d'installation
	 *
	 * @param string $newurlroot url vers répertoire racine de lodel
	 * @param string $permission permissions
	 * @param string $pclzip utilisation de pclzip ?
	 * @param string $newimportdir chemin vers répertoire 'import'
	 * @param string $newextensionscripts extension du script à afficher (.php ou .html ?)
	 * @param string $newusesymlink utilisation des liens symboliques ?
	 * @param string $newcontactbug adresse mail à contacter en cas de bug
	 * @param string $newunzipcmd commande unzip spécifiée par l'utilisateur
	 * @param string $newzipcmd commande zip spécifiée par l'utilisateur
	 * @param string $newuri type d'url affichée
	 */
	function maj_options($newurlroot, $permission, $pclzip, $newimportdir, $newextensionscripts, $newusesymlink, $newcontactbug, $newunzipcmd, $newzipcmd, $newuri)
	{
		$newurlroot = $newurlroot."/"; // ensure their is a / at the end
		$newurlroot = preg_replace("/\/\/+/","/",$newurlroot); // ensure there is no double slashes because it causes problem with the cookies
		$filemask = "07" . (5*($permission['group']['read']!="")+2*($permission['group']['write']!="")) . (5*($permission['all']['read']!="")+2*($permission['all']['write']!=""));
		
		if ($pclzip=="pclzip") { $newunzipcmd=$newzipcmd="pclzip"; }
		
		$this->maj_lodelconfig(array("chooseoptions"=>"oui",
					"urlroot"=>$newurlroot,
					"importdir"=>$newimportdir,
					"extensionscripts"=>$newextensionscripts,
					"usesymlink"=>$newusesymlink,
					"filemask"=>$filemask,
					"contactbug"=>$newcontactbug,
					"unzipcmd"=>$newunzipcmd,
					"zipcmd"=>$newzipcmd,
					"URI"=>$newuri
					));
	}

	/**
	 * Téléchargement de lodelconfig.php
	 *
	 * Cette fonction lance le téléchargement du fichier de configuration du site
	 *
	 * @param string $log_version version navigateur
	 */
	function downloadlodelconfig($log_version)
	{
		header("Content-type: application/force-download");
		header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		if (ereg('MSIE ([0-9].[0-9]{1,2})', $_SERVER['HTTP_USER_AGENT'], $log_version)) { // from phpMyAdmin
			header('Content-Disposition: inline; filename="lodelconfig.php"');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
		} else {
			header('Content-Disposition: attachment; filename="lodelconfig.php"');
			header('Pragma: no-cache');
		}
		readfile($this->lodelconfig);
		return true;
	}

	/**
	 * Affichage de la config
	 *
	 * Cette fonction affiche le contenu du fichier de configuration
	 *
	 */
	function showlodelconfig()
	{
		@include($this->lodelconfig); // insere lodelconfig, normalement pas de probleme
		$this->include_tpl("install-showlodelconfig.html");
		return true;
	}

	/**
	 * Test installation en cours
	 *
	 * Cette fonction teste si une installation a déjà été commencée
	 *
	 */
	function startInstall()
	{
		return file_exists($this->lodelconfig); // has an install been started
	}

	/**
	 * Test droits répertoire donné
	 *
	 * Cette fonction teste les droits (lecture/écriture) du répertoire dir
	 *
	 * @param string $dir répertoire à tester
	 * @param int $mode droits à tester
	 * @param bool $cheminAbsolu chemin absolu ?
	 */
	function testdirmode($dir, $mode, $cheminAbsolu=false)
	{
		if ($cheminAbsolu==false) {
			$dir = LODELROOT . $dir;
		}
		return ($mode == 7 ? (bool)is_writable($dir) : (bool)is_readable($dir));
	}

	/**
	 * Test droits répertoires
	 *
	 * Cette fonction teste les droits (lecture/écriture) des répertoires lodel
	 *
	 */
	function testRights()
	{
		$dirs=array("CACHE"=>7,
			"lodeladmin".$this->versionsuffix."/CACHE"=>7,
			"lodeladmin".$this->versionsuffix."/tpl"=>5,
			"lodel".$this->versionsuffix=>5,
			"lodel".$this->versionsuffix."/install"=>5,
			"lodel".$this->versionsuffix."/install/plateform"=>5,
			"lodel".$this->versionsuffix."/scripts"=>5,
			"lodel".$this->versionsuffix."/src"=>5,
			"share".$this->versionsuffix."/css"=>5,
			"share".$this->versionsuffix."/js"=>5,
			"share".$this->versionsuffix."/macros"=>5);
		
		foreach ($dirs as $dir => $mode) {
			if (!$this->testdirmode($dir,$mode)) { // vérifie les droits sur le répertoire
				if (!@file_exists(LODELROOT.$dir)){
					$missing_dirs[] = $dir; // le répertoire n'existe pas
				} else {
					if ($mode == 7) 
						$not_writable_dirs[] = $dir;
					else 
						$not_readable_dirs[] = $dir;
				}
			}
		}
		
		if (isset($missing_dirs) || isset($not_writable_dirs) || isset($not_readable_dirs)){
			$this->probleme_droits((array)$missing_dirs, (array)$not_writable_dirs, (array)$not_readable_dirs);
		}
		//
		// Check PHP has the needed function 
		//
		$erreur['functions']=array();
		foreach(array("utf8_encode","mysql_connect") as $fct) {
			if (!function_exists($fct)) array_push($erreur['functions'],$fct);
		}
		if (isset($erreur['functions'][0])) {
			return $erreur['functions'];
		}
		return true;
	}

	/**
	 * Installation plateforme
	 *
	 * Cette fonction vérifie qu'il existe un fichier de configuration. Si absent, on demande à installer la plateforme
	 *
	 */
	function checkConfig()
	{
		if (!file_exists($this->lodelconfig) || !(@include($this->lodelconfig))) {
			$this->include_tpl("install-plateform.html");
			return false;
		}
		return true;
	}

	/**
	 * Test inclusion du fichier func.php
	 *
	 * Cette fonction vérifie qu'il est possible d'accéder au fichier de fonctions func.php
	 *
	 */
	function checkFunc()
	{
		if ((@include("../lodel".$this->versionsuffix."/scripts/func.php"))!=568) { // on accede au fichier func.php
			die ("ERROR: unable to access the ../lodel".$this->versionsuffix."/scripts/func.php file. Check the file exists and the rights and/or report the bug.");
		}
	}

	/**
	 * Test accessibilité base de données
	 *
	 * Cette fonction teste l'identifiant, mot de passe et url du serveur de base de données. Si ceux-ci sont absent on les demande
	 *
	 */
	function checkDB()
	{
		@include($this->lodelconfig);
		if (!$dbusername || !$dbhost) {
			$this->include_tpl("install-mysql.html");
			return false;
		} elseif (!@mysql_connect($dbhost,$dbusername,$dbpasswd)) { // tente une connexion
			return "error_cnx";
		}
	}

	/**
	 * Liste des base de données
	 *
	 * Cette fonction retourne la liste des base de données existante sur le serveur
	 *
	 */
	function seekDB()
	{
		// retourne la liste des bdd
		return @mysql_query("SHOW DATABASES");
	}

	/**
	 * Installation tables
	 *
	 * Cette fonction installe les tables utilisées par Lodel
	 *
	 * @param string $erasetables on efface les tables si celles-ci sont existantes ?
	 * @param string $tache tâche a accomplir
	 */
	function installDB($erasetables, $tache)
	{
		@include($this->lodelconfig);
		$sitesexistsrequest="SELECT id,status FROM ".$tableprefix."sites LIMIT 1";

		if (!@mysql_select_db($database)) { // ok, database est defini, on tente la connection
			return "error_dbselect";
		} elseif ($erasetables || !@mysql_query($sitesexistsrequest)) {   // regarde si la table sites exists ?
			// non, alors on cree les tables

			// il faudrait tester ici que les tables sur la database sont bien les memes que celles dans le fichier
			// les IF NOT EXISTS sont necessaires dans le fichier init.sql sinon ca va produire une erreur.
			
			$erreur_createtables=$this->mysql_query_file(LODELROOT."lodel".$this->versionsuffix."/install/init.sql",$erasetables,$database);

			// no error, let's add the translations of the interface.
			if (!$erreur_createtables) 
				$erreur_createtables=$this->mysql_query_file(LODELROOT."lodel".$this->versionsuffix."/install/init-translations.sql",$erasetables, $database);

			if ($erreur_createtables) {
				// mince, ca marche pas... bon on detruit la table sites si elle existe pour pouvoir revenir ici
				if (@mysql_query($sitesexistsrequest)) {
					if (!@mysql_query("DROP TABLE IF EXISTS ".$tableprefix."sites")) { // ok, on n'arrive vraiment a rien faire
						$erreur_createtables.="<br /><br />La commande DROP TABLE IF EXISTS ".$tableprefix."sites n'a pas pu être executée. On ne peut vraiment rien faire !";
					}
				}
				preg_match("`<font COLOR=red>(.*?)</font>`is", $erreur_createtables, $res);
				return $res[1];
			}
			
			// let's deal with the problem of lock table.
			$ret=@mysql_query("LOCK TABLES {$tableprefix}sites WRITE");
			if (!$ret) { // does not support LOCK table
				$this->maj_lodelconfig("DONTUSELOCKTABLES",true);
			} else {
				mysql_query("UNLOCK TABLES") or die (mysql_error());
			}
		} elseif ($tache=="database") { // the table site already exists but we just have asked for which database... check what to do.
			// ask for erasing the table content or not.
			return "error_tableexist";
		}
		return true;
	}

	/**
	 * Vérification présence administrateur
	 *
	 * Cette fonction vérifie qu'un administrateur a été créé
	 *
	 */
	function verifyAdmin()
	{
		@include($this->lodelconfig);
		$result=mysql_query("SELECT id FROM ".$tableprefix."users LIMIT 1") or die (mysql_error());
		if (!mysql_num_rows($result)) { // il faut demander la creation d'un admin
			return false;
		}
		return true;
	}

	/**
	 * Vérification présence htaccess
	 *
	 * Cette fonction vérifie la présence des htaccess
	 *
	 */
	function checkHtaccess()
	{
		$erreur_htaccess=array();
		foreach ($this->protecteddir as $dir) {
			if (file_exists(LODELROOT.$dir) && !file_exists(LODELROOT.$dir."/.htaccess")) array_push($erreur_htaccess,$dir);
		}
		if (isset($erreur_htaccess[0])) {
			return $erreur_htaccess;
		}
		return true;
	}

	/**
	 * Test répertoire d'import
	 *
	 * Cette fonction vérifie que le serveur a la possibilité de lire dans le répertoire import
	 *
	 * @param string $importdir chemin absolu vers le répertoire d'import
	 * @param string $chooseoptions
	 */
	function askOptions($importdir, $chooseoptions)
	{
		if ($this->installoption != 1) {
			if ($importdir && !$this->testdirmode($importdir,5, true)) {
				return "error";
			} elseif ($chooseoptions!="oui") {
				return false;
			}
		}
		return true;
	}

	/**
	 * Vérification lodelconfig
	 *
	 * Cette fonction vérifie que le lodelconfig créé et celui placé dans le site sont identiques
	 *
	 */
	function verifyLodelConfig()
	{
		@include($this->lodelconfig);
		$textlc=file_get_contents($this->lodelconfig);
		$file="lodelconfig.php";
		
		// check $file is readable
		$rootlodelconfig_exists=file_exists(LODELROOT.$file);
		if ($rootlodelconfig_exists && !is_readable(LODELROOT.$file)) {
			return "error";
		}
		// compare the two config files
		if (!$rootlodelconfig_exists || $textlc!=file_get_contents(LODELROOT.$file)) { // are they different ?
			@unlink(LODELROOT.$file);
			if (@copy($this->lodelconfig,LODELROOT.$file)) { // let copy
				@chmod(LODELROOT.$file,0666 & octdec($filemask));
			} else { // error
				$this->include_tpl("install-lodelconfig.html");
				return "error";
			}  
		}
		return true;
	}

	/**
	 * Fin de l'installation
	 *
	 * Cette fonction lance la fin de l'installation
	 *
	 */
	function finish()
	{
		@include($this->lodelconfig);
		if (!defined("DATABASE")){
			define("DATABASE", $database);
			define("DBUSERNAME", $dbusername);
			define("DBPASSWD", $dbpasswd);
			define("DBHOST", $dbhost);
			define("DBDRIVER", 'mysql');
			define("SINGLEDATABASE", $singledatabase);
		}

		// finish !
		if ($this->installoption=='1') { // essaie de creer automatiquement le site
			header("location: site.php?maindefault=1");
		}
		$this->include_tpl("install-fin.html");
	}

	/**
	 * Mise à jour configuration
	 *
	 * Cette fonction met à jour le fichier de configuration lodelconfig en appellant la fonction maj_lodelconfig_var
	 *
	 * @param array $var variable à modifier
	 * @param string $val nouvelle valeur de la variable
	 */	
	function maj_lodelconfig($var,$val=-1)
	{
		// lit le fichier
		$text=$oldtext=file_get_contents($this->lodelconfig);
		//  if (!$text) die("ERROR: $lodelconfig can't be read. Internal error, please report this bug");
		
		if (is_array($var)) {
			foreach ($var as $v =>$val) {
				$this->maj_lodelconfig_var($v,$val,$text);
			}
		} else {
			$this->maj_lodelconfig_var($var,$val,$text);
		}
		
		if ($text==$oldtext) return false;
		// ecrit le fichier
		$f=@fopen($this->lodelconfig,"wb");
		if(!$f) die ("ERROR: ".$this->lodelconfig." is not writeable. Internal error, please report this bug.");
		fputs($f,$text);
		fclose($f);
		// 774 for windows users, bouh
  		return (chmod ($this->lodelconfig,0774) && $this->have_chmod);
	}

	/**
	 * Mise à jour configuration
	 *
	 * Cette fonction met à jour le fichier de configuration lodelconfig
	 *
	 * @param var $var variable à modifier
	 * @param string $val nouvelle valeur de la variable
	 * @param string $text contenu du fichier lodelconfig
	 */	
	function maj_lodelconfig_var($var,$val,&$text)
	{
		if (strtoupper($var)==$var) { // it's a constant
			if (!preg_match("/^\s*define\s*\(\"$var\",.*?\);/m",$text,$result)) {	die ("la constante $var est introuvable dans le fichier de config.");      }
			if (is_string($val)) $val='"'.$val.'"';
			if (is_bool($val)) $val=$val ? "true" : "false";
			
			$text=str_replace($result[0],"define(\"$var\",".$val.");",$text);
		} else { // no, it's a variable
			if (!preg_match("/^\s*\\\$$var\s*=\s*\".*?\";/m",$text,$result)) {	die ("la variable \$$var est introuvable dans le fichier de config.");      }
			$text=str_replace($result[0],"\$$var=\"$val\";",$text);
		}
	}
	
	/**
	 * Installation tables 
	 *
	 * Cette fonction execute les requêtes SQL contenues dans le fichier filename sur la base database
	 *
	 * @param string $filename fichier contenant la/les requêtes SQL
	 * @param bool $droptables on efface les tables existantes ?
	 * @param string $database nom de la base de données sur laquelle travailler
	 */		
	function mysql_query_file($filename,$droptables=false, $db)
	{
		@include($this->lodelconfig);
		$table_charset = $this->find_mysql_db_charset($db);
		// commenté par P.A. le 09/10/08, UTF8 uniquement à partir de Lodel > 0.8.7
// 		if (strpos($filename, 'init-translations.sql') && strpos($table_charset, 'utf8')) {
// 			$filename = str_replace('init-translations.sql', 'init-translations_utf8.sql', $filename);
// 		}
		$sqlfile=preg_replace('/#_M?TP_/',$tableprefix ,
				file_get_contents($filename));
		$sqlfile=str_replace('_CHARSET_', $table_charset , $sqlfile);
		if (!$sqlfile) return;
		
		$len=strlen($sqlfile);
		for ($i=0; $i<$len; $i++) {
			$c=$sqlfile{$i};
			
			if ($c=='\\') { $i++; continue; } // quoted char
			if ($c=='#') {
				for (; $i<$len; $i++) {
					if ($sqlfile{$i}=="\n") break;
					$sqlfile{$i}=" ";
				}      
			} elseif ($c=="'") {
				$i++;
				for (; $i<$len; $i++) {
					$c=$sqlfile{$i};
					if ($c=='\\') { $i++; continue; } // quoted char
					if ($c=="'") break;
				}
			} elseif ($c==";") { // end of SQL statment
				$cmd=trim(substr($sqlfile,$ilast,$i-$ilast));
				#echo $cmd,"<BR>\n";
				if ($cmd) {
					// should we drop tables before create them ?
					if ($droptables && preg_match('/^\s*CREATE\s+(?:TABLE\s+IF\s+NOT\s+EXISTS\s+)?'.$tableprefix.'(\w+)/',$cmd,$result)) {
						if (!mysql_query('DROP TABLE IF EXISTS '.$result[1])) {
							$err.="$cmd <font COLOR=red>".mysql_error().'</font><br>';
						}
					}
					// execute the command
					if (!mysql_query($cmd)) {
						$err.="$cmd <font COLOR=red>".mysql_error().'</font><br>';
					}
				}
				$ilast=$i+1;
			}
		}
		return $err;
	}

	/**
	 * Filemask
	 *
	 * Cette fonction permet de trouver le bon filemask à utiliser
	 *
	 * @param string $testfile fichier contenant la/les requêtes SQL
	 */	
	function guessfilemask($testfile) {
		//
		// Guess the correct filemask setting
		// (code from SPIP)
		
		$self = basename($_SERVER['PHP_SELF']);
		$uid_dir = @fileowner('.');
		$uid_self = @fileowner($self);
		$gid_dir = @filegroup('.');
		$gid_self = @filegroup($self);
		$perms_self = @fileperms($self);
		
		// Compare the ownership and groupship of the directory, the installer script and 
		// the file created by php.
		
		if ($uid_dir > 0 && $uid_dir == $uid_self && @fileowner($testfile) == $uid_dir)
			$chmod = 0700;
		else if ($gid_dir > 0 && $gid_dir == $gid2_self && @filegroup($testfile) == $gid_dir)
			$chmod = 0770;
		else
			$chmod = 0777;
		
		// Add the same read and executation rights as the installer script has.
		if ($perms_self > 0) {
			// add the execution right where there is read right
			$perms_self = ($perms_self & 0777) | (($perms_self & 0444) >> 2); 
			$chmod |= $perms_self;
		}
		return $chmod;
	}
	
	/**
	 * Inclusion du template
	 *
	 * Cette fonction inclue dans la page le template $file
	 *
	 * @param string $file fichier template à inclure
	 */	
	function include_tpl($file)
	{
		@include($this->lodelconfig);
		extract($GLOBALS,EXTR_SKIP);
		
		$plateformdir = $this->plateformdir;
		$installoption = $this->installoption;
		if (!$this->installlang) $this->installlang="fr";
		if($installlang != $this->installlang)
			$installlang = $this->installlang;
		if (!$langcache) {
			if (!(@include ("tpl/install-lang-".$this->installlang.".html"))) $this->problem_include("tpl/install-lang-".$this->installlang.".html");
		}
		$text=@file_get_contents("tpl/".$file);
		if ($text===false) $this->problem_include("tpl/".$file);
		$openphp='<'.'?php ';
		$closephp=' ?'.'>';
		// search for tags
		$text=preg_replace(array("/\[@(\w+\.\w+)\]/",
					"/\[@(\w+\.\w+)\|sprintf\(([^\]\)]+)\)\]/"),
				
				array($openphp.'echo stripslashes($GLOBALS[langcache][$installlang][strtolower(\'\\1\')]);'.$closephp,
					$openphp.'echo stripslashes(sprintf($GLOBALS[langcache][$installlang][strtolower(\'\\1\')],\\2));'.$closephp),
				$text);
		echo eval($closephp.$text.$openphp);
		exit();
	}
	
	/**
	 * Inclusion du template
	 *
	 * Cette fonction inclue dans la page le template $file
	 *
	 * @param string $file fichier template à inclure
	 */	
	function problem_include($filename)
	{
		?>
		<html>
		<body>
		Unable to access the file  <strong><?php echo $filename; ?></strong><br />
		Please check your directory  tpl and the file <?php echo $filename; ?> exist and are accessible by the web-serveur. Please report the bug if everything is alright.<br>
		<br />
		</body>
		</html>
		<?php
		trigger_error("Unable to access the file $filename",E_USER_ERROR);
		
		die();
	}

	/**
	 * Impression message d'erreur
	 *
	 * Cette fonction affiche un message d'erreur lorsqu'un problème survient
	 *
	 * @param string $msg message à afficher
	 */	
	function problem($msg)
	{
		@include($this->lodelconfig);
		extract($GLOBALS,EXTR_SKIP);

		//$installlang = $_REQUEST['installlang'];
		if (!$this->installlang) $this->installlang="fr";

		if (!$langcache) {
			if (!(@include ("tpl/install-lang-".$this->installlang.".html"))) $this->problem_include("tpl/install-lang-".$this->installlang.".html");
		}
		global $langcache;
		$messages=array(
				"version"=>sprintf($langcache[$this->installlang]['install.php_installation_or_configuration_error'],phpversion()),
		//'La version de php sur votre serveur ne permet pas un fonctionnement correct de Lodel.<br />Version de php sur votre serveur: '.phpversion().'<br />Versions recommandées : 5.X',
		
				"reading_lodelconfig"=>$langcache[$this->installlang]['install.reading_lodelconfig'].'<form method="post" action="install.php"><input type="hidden" name="tache" value="lodelconfig"><input type="submit" value="continuer"></form>',
				//'Le fichier lodelconfig.php n\'a pas pu être lu. Veuillez verifier que le serveur web à les droits de lecteur sur ce fichier.,
		
		"lodelconfig_but_no_database"=>$langcache[$this->installlang]['install.lodelconfig_but_no_database'],
				//=>'Un fichier de configuration lodelconfig.php a été trouvé dans le répertoire principale de Lodel mais ce fichier ne permet pas actuellement d\'acceder à une base de donnée valide. Si vous souhaitez poursuivre l\'installation, veuillez effacer manuellement. Ensuite, veuillez cliquer sur le bouton "Recharger" de votre navigateur.</form>'
		);
		
		?>
		<html>
		<head>
		<title><?php echo $langcache[$this->installlang]['install.install_lodel']; ?></title>
		</head>
		<body bgcolor="#FFFFFF"  text="Black" vlink="black" link="black" alink="blue" onLoad="" marginwidth="0" marginheight="0" rightmargin="0" leftmargin="0" topmargin="0" bottommargin="0"> 
		
		<h1><?php echo $langcache[$this->installlang]['install.install_lodel']; ?></h1>
		
		
		<p align="center">
		<table width="600">
		<tr>
		<td>
		<?php echo $messages[$msg]; ?>
		</td>
		</table>
		</body>
		<?php 
		die();
	}

	/**
	 * Affichage des problèmes de droits
	 *
	 * Cette fonction affiche un message d'erreur lorsqu'un problème de droits (lecture/écriture) survient sur un répertoire
	 *
	 * @param string $msg message à afficher
	 */		
	function probleme_droits($missing_dirs, $not_writable_dirs, $not_readable_dirs)
	{
		@include($this->lodelconfig);
		extract($GLOBALS,EXTR_SKIP);
		if (!$this->installlang) $this->installlang="fr";

		if (!$langcache) {
			if (!(@include ("tpl/install-lang-".$this->installlang.".html"))) $this->problem_include("tpl/install-lang-".$this->installlang.".html");
		}
		global $langcache;
		include 'tpl/install-openhtml.html';

		echo '<h2>' . $langcache[$this->installlang]['install.check_directories'] . '</h2>';
		echo '<p><strong>' . $langcache[$this->installlang]['install.directories_access_speech'] . '</strong></p>';
	
		if (!empty($missing_dirs)) {
			echo '<p><strong>' . $langcache[$this->installlang]['install.missing_directories'] . '</strong> :</p><ul>';
			foreach ($missing_dirs as $dir) {
				echo '<li>' . $dir . '</li>';
			}
			echo '</ul>';
		}
	
		if (!empty($not_writable_dirs)) {
			echo '<p><strong>' . $langcache[$this->installlang]['install.not_writable_directories'] . '</strong> :</p><ul>';
			foreach ($not_writable_dirs as $dir) {
				echo '<li>' . $dir  . '</li>';
			}
			echo '</ul>';
		}
	
		if (!empty($not_readable_dirs)) {
			echo '<p><strong>' . $langcache[$this->installlang]['install.not_readable_directories'] . '</strong> :</p><ul>';
			foreach ($not_readable_dirs as $dir) {
				echo '<li>' . $dir  . '</li>';
			}
			echo '</ul>';
		}
		
		echo '
		<p>
		<form method="post" action="install.php">
		<input type="hidden" name="tache" value="droits">
		<input type="hidden" name="installoption" value="' . $this->installoption . '">
		<input type="hidden" name="installlang" value="' . $this->installlang . '">
		<input type="submit" value="continuer">
		</form>
		</p>
		<p><strong>N.B. :&nbsp;</strong>' . $langcache[$this->installlang]['install.notice_security_directory_rights'] . '</p>';
		include 'tpl/install-closehtml.html';
		exit;
	}

	/**
	 * Configuration charset
	 *
	 * Cette fonction configure le charset de la base
	 *
	 */	
	function set_mysql_charset() 
	{
		$version_mysql = explode(".", substr(mysql_get_server_info(), 0, 3));
		$version_mysql_num = $version_mysql[0] . $version_mysql[1];
	
		if ($version_mysql_num > 40) {
			$result = mysql_query("SHOW VARIABLES LIKE 'character_set_database'");
				if ($db_charset = mysql_fetch_row($result)) {
					mysql_query('SET NAMES '. $db_charset[1]);
				} else {
					mysql_query('SET NAMES UTF8'); 
				}
		}
	}
	
	/**
	 * Connaitre le charset
	 *
	 * Cette fonction retourne le charset de la base database
	 *
	 * @param string $database nom de la base de données
	 */	
	function find_mysql_db_charset($database) 
	{
		@mysql_select_db($database);
		$result = mysql_query("SHOW VARIABLES LIKE '%_database'");
		while ($row = mysql_fetch_array($result)) {
			if ($row['Variable_name'] == 'character_set_database') { $db_charset =  $row['Value'];}
			if ($row['Variable_name'] == 'collation_database') { $db_collation =  $row['Value'];}
		}
		
		if (is_string($db_charset) && is_string($db_collation)) {
			$set_db_charset = " CHARACTER SET $db_charset COLLATE $db_collation";
		} else {
			$set_db_charset = '';
		}
		return $set_db_charset;
	}

	/**
	 * Affichage choix langue
	 *
	 * Cette fonction affiche une liste déroulante permettant de choisir sa langue par défaut
	 *
	 * @param string $tpr préfixe des tables
	 */	
	function makeSelectLang($tpr)
	{
		global $db;
		@include($this->lodelconfig);
		$text_lang = "";
		$GLOBALS['database'] = $database;
		$GLOBALS['dbusername'] = $dbusername;
		$GLOBALS['dbpasswd'] = $dbpasswd;
		$GLOBALS['dbhost'] = $dbhost;
 		@require_once("../lodel".$this->versionsuffix."/scripts/connect.php");
 		$result=$db->execute(lq("SELECT lang,title FROM ".$tpr."translations WHERE status>0 AND textgroups='interface'")) or die("ERROR : error during selecting lang");
 		$lang=$this->installlang;
 		while(!$result->EOF) {
 			$selected=$lang==$result->fields['lang'] ? "selected=\"selected\"" : "";
 			$text_lang .= '<option value="'.$result->fields['lang'].'" '.$selected.'>'.$result->fields['title'].'</option>';
 			$result->MoveNext();
 		}
		echo $text_lang;
	}
}
