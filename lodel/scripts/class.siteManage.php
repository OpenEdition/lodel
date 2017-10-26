<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Classe siteManage - Gère un site
 */

class siteManage {
	/**
	 * Version lodel du site en cours de traitement
	 * @var int
	 */
	private $version;

	/**
	 * Répertoire de la version lodel utilisée
	 * @var string
	 */	
	private $versiondir;

	/**
	 * Variable contenant les différentes versions de lodel installées
	 * @var string
	 */		
	private $versions;

	/**
	 * Identifiant du site
	 * @var int
	 */
	private $id;

	/**
	 * Critere de sélection du site requete SQL ("id=$id")
	 * @var string
	 */
	private $critere;

	/**
	 * Regex permettant de trouver s'il existe plusieurs versions de lodel installées
	 * @var string
	 */	
	private $lodelhomere;

	/**
	 * Réinstallation ?
	 * @var string
	 */
	private $reinstall;

	/**
	 * Base de donnée unique ?
	 * @var bool
	 */
	private $singledatabase;

	/**
	 * Nom de la base de données principale
	 * @var string
	 */
	private $database;

	/**
	 * Un seul site ?
	 * @var bool
	 */
	private $maindefault;

	/**
	 * Informations du site
	 * @var array
	 */
	public $context;

	/**
	 * Téléchargement du fichier siteconfig.php ?
	 * @var int
	 */
	private $downloadsiteconfig;


	/**
	 * Constructeur
	 *
	 * Instancie un objet de la classe
	 *
	 * @param int $id identifiant du site
	 * @param array $context le contexte passé par référence
	 */
	public function __construct()
	{
		$this->critere = "id='".C::get('id')."'";
		$this->lodelhomere = "/^lodel$/";
        $this->versiondir = 'lodel';
		defined('INC_FUNC') || include 'func.php';
	}

	/**
	 * Accesseur
	 *
	 * Cette fonction renvoit la variable $_v passée en paramètre
	 *
	 * @param var $_v variable à renvoyer
	 */
    	public function get( $_v )
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
    	public function set( $_v, $_a )
	{
		$this->$_v = $_a;
    	}

	/**
	 * Restoration d'un site supprimé
	 *
	 * Cette fonction restaure un site préalablement supprimé
	 */
	public function restore()
	{
		global $db;
		$db->Execute(lq("
            UPDATE #_MTP_sites 
                SET status=abs(status) 
                WHERE ".$this->critere)) 
            		or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		update();
		View::getView()->back();// on revient
	}

	/**
	 * Suppression d'un site
	 *
	 * Cette fonction supprime un site
	 */
	function remove()
	{
		global $db;
		$db->Execute(lq("
            UPDATE #_MTP_sites 
                SET status=-abs(status) 
                WHERE ".$this->critere)) 
            		or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		update();
        	View::getView()->back();// on revient
	}

	/**
	 * Réinstallation d'un site
	 *
	 * Cette fonction lance la procédure de réinstallation d'un site
	 *
	 * @param var $dir répertoire à traiter
	 */	
	function reinstall()
	{
		global $db;
	
		$result = $db->execute(lq("
            SELECT path,name 
                FROM #_MTP_sites 
                WHERE status>0")) 
            		or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		
		while(!$result->EOF) {
			$row = $result->fields;
			// on peut installer les fichiers
			if (!$row['path']) {
				$row['path'] = '/'. $row['name'];
			}
			$root = str_replace('//', '/', LODELROOT. $row['path']). '/'; 
			if ($row['path'] == '/') { // c'est un peu sale ca.
				$this->install_file($root, "lodel/src", '');
			} else {
				$this->install_file($root, "../lodel/src", LODELROOT);
			}
	
			// clear the CACHEs
			function_exists('clearcache') || include 'cachefunc.php';
			clearcache();
	
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
		global $db;
		//on extrait les variables contenues dans $_POST
		if (C::get('maindefault')) { // site par defaut ?
			C::set('title', 'Site principal');
			C::set('name', 'principal');
			C::set('atroot', true); 
		}
		
		// validation
		do {
            		$title = C::get('title');
			if (!$title) {
                		C::set('error_title', 1);
				break;
			}
            
            		$id = C::get('id');
            		$name = C::get('name');
			if (!$id && (!$name || !preg_match("/^[a-z0-9\-]+$/",$name))) { 
                		C::set('error_name', 1);
                		break;
			}
	
			// verifie qu'on a qu'un site si on est en singledatabase
			if (!$name && C::get('singledatabase', 'cfg') == 'on') {
				$numsite = $db->GetOne(lq("
                    SELECT COUNT(*) 
                        FROM #_MTP_sites 
                        WHERE status>-32 AND name!='". magic_addslashes($name). "'"));
                    
				if ($numsite >= 1) {
					trigger_error("ERROR<br />\nIl n'est pas possible actuellement d'avoir plusieurs sites sur une unique base de données : il faut utiliser plusieurs bases de données.", E_USER_ERROR);
				}
			}
            	
            		$status = C::get('status');
                
			// édition d'un site : lit les informations options, status, etc.
			if (!$id) { // création d'un site
				// vérifie que le nom (base de données + répertoire du site) n'est pas déjà utilisé
				$result = $db->GetOne(lq("
                    SELECT id
                        FROM #_MTP_sites
                        WHERE name='".magic_addslashes($name)."'"));
                    
				if($result>0)
				{
					C::set('error_unique_name', 1);
					break;
				}
	
				$options = '';
				$status  = -32; // -32 signifie en creation
				if (C::get('atroot')) {
                    			C::set('path', '/'); 
				}elseif (!C::get('path')) {
                    			C::set('path', '/'. $name);
				}
			}
            
			if (!C::get('url')) {
				C::set('url', 'http://'. $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT'] && $_SERVER['SERVER_PORT'] != "80" ? ':'. $_SERVER['SERVER_PORT'] : ""). preg_replace("/\blodeladmin\/.*/", '', $_SERVER['REQUEST_URI']). substr(C::get('path'), 1));
			}
			
			if (C::get('reinstall')) {
				$status = -32;
			}
	
			//suppression de l'eventuel / a la fin de l'url
			C::set('url', preg_replace("/\/$/", '', C::get('url')));

			// Ajout de slashes pour autoriser les guillemets dans le titre et le sous-titre du site
            		$title = magic_addslashes($title);
            		$subtitle = magic_addslashes(C::get('subtitle'));
	        
			$db->Execute("REPLACE INTO `{$GLOBALS['tp']}sites` (id,title,name,path,url,subtitle,status) VALUES ('".C::get('id')."','".$title."','".C::get('name')."','".C::get('path')."','".C::get('url')."','".$subtitle."','".$status."')") or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
	
			update();

			if ($status>-32) {
				View::getView()->back(); // on revient, le site n'est pas en creation
			}
	
			if (!C::get('id')) {
                		C::set('id', (int)$db->insert_id()); 
			}
			return true;
		} while (0);
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
		$file = "{$root}{$homesite}/../install/install-fichier.dat"; // homelodel est necessaire pour choper le bon fichier d'install
		if (!file_exists($file)) {
			trigger_error("Fichier $file introuvable. Verifiez votre pactage", E_USER_ERROR);
		}
		$lines = file($file);
		$dirsource = '.';
		$dirdest   = '.';
    		$filemask = C::get('filemask', 'cfg');
		$search = array("/\#.*$/", '/\$homesite/', '/\$homelodel/');
		$rpl    = array ('', $homesite, $homelodel);
		$usesymlink = C::get('usesymlink', 'cfg');
		$extensionscripts = C::get('extensionscripts', 'cfg');
		foreach ($lines as $line) {
			$line = rtrim(preg_replace($search, $rpl, $line));
			if (!$line) {
				continue;
			}
			@list ($cmd, $arg1) = preg_split ("/\s+/", $line);
			$dest1 = "$root$dirdest/$arg1";
			# quelle commande ?
			if ($cmd == 'dirsource') {
				$dirsource = $arg1;
			} elseif ($cmd == 'dirdestination') {
				$dirdest = $arg1;
			} elseif ($cmd == 'mkdir') {
				$arg1 = $root. $arg1;
				if (!file_exists($arg1) || !is_writeable($arg1)) {
					if(!@mkdir($arg1, 0777 & octdec($filemask))) {
						C::set('error_mkdir', $arg1);
						View::getView()->render('site-createdir');
						exit;	
					}
				}
				@chmod($arg1, 0777 & octdec($filemask));
			} elseif ($cmd == 'ln' && $usesymlink && $usesymlink != 'non') {
				if ($dirdest == '.' && $extensionscripts == 'html' && $arg1 != 'lodelconfig.php') {
					$dest1 = preg_replace("/\.php$/", '.html', $dest1);
				}
				$toroot = preg_replace(array("/^\.\//", "/([^\/]+)\//", "/[^\/]+$/"),
						array('', '../', ''), "$dirdest/$arg1");
				$this->slink("$toroot$dirsource/$arg1", $dest1);
			} elseif ($cmd == 'cp' || ($cmd == 'ln' && (!$usesymlink || $usesymlink == 'non'))) {
				if ($dirdest == '.' && $extensionscripts == 'html' && $arg1 != 'lodelconfig.php') {
					$dest1 = preg_replace("/\.php$/", '.html', $dest1);
				}
				$this->mycopyrec("$root$dirsource/$arg1", $dest1);
			} elseif ($cmd == 'touch') {
				if (!file_exists($dest1)) {
					writefile($dest1, '');
				}
				@chmod($dest1, 0666 & octdec($filemask));
			} elseif ($cmd == 'htaccess') {
				if (!file_exists("$dest1/.htaccess")) {
					$this->htaccess($dest1);
				}
			} else {
				trigger_error("command inconnue: \"$cmd\"", E_USER_ERROR);
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
		@chmod ("$dir/.htaccess", 0666 & octdec(C::get('filemask', 'cfg')));
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
		@unlink($dest); // detruit le lien s'il existe
		if (!(@symlink($src,$dest))) {
			@chmod(basename($dest), 0777 & octdec(C::get('filemask', 'cfg')));
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
				mkdir($dest, 0777 & octdec(C::get('filemask', 'cfg')));
			}
			@chmod($dest, 0777 & octdec(C::get('filemask', 'cfg')));
			$dir = @opendir($src) or trigger_error('Cannot open dir '.$src, E_USER_ERROR);
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
		if (file_exists ($dest) && md5_file($dest) == md5_file($src)) {
			return;
		}
		if (file_exists ($dest)) {
			if(!@unlink($dest))
			{
				View::getView()->render('site-createdir');
				exit;
			}
		}
		if (!(@copy($src,$dest))) {
			@chmod(basename($dest), 0777 & octdec(C::get('filemask', 'cfg')));
			if(!@copy($src, $dest))
			{
				View::getView()->render('site-createdir');
				exit;
			}
		}
		@chmod($dest, 0666 & octdec(C::get('filemask', 'cfg')));
	}
	
	/**
	 * Charset de la base de données
	 *
	 * Cette fonction retourne le charset utilisé par la base de données '$database'
	 *
	 * @param var $database nom de la base de donnée à traiter
	 */		
	function find_mysql_db_charset($database) {
		$db_collation = mysql_find_db_variable($database, 'collation_database');
		if (isset($GLOBALS['db_charset']) && is_string($db_collation)) {
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
	function createDB()
	{
		global $db;
		// creation de la DataBase si besoin
		if (!C::get('id') && !C::get('name')) {
			trigger_error('probleme interne 1' . C::get('id') . " " . C::get('name'), E_USER_ERROR);
		}
		
		do { // bloc de controle
			if (C::get('singledatabase', 'cfg') == 'on') {
				break;
			}
            		$dbname = C::get('dbname');
			// check if the database existe
			$db_list = $db->MetaDatabases();
			$i = 0;
			$cnt = count($db_list);
			while ($i < $cnt) {
				if ($dbname == $db_list[$i]) {
					return true; // la database existe
				}
				$i++;
			}
			// well, it does not exist, let's create it.
			$dbusername = C::get('dbusername', 'cfg');
			$dbhost = C::get('dbhost', 'cfg');
			$dbpasswd = C::get('dbpasswd', 'cfg');
			
			$db_charset = $this->find_mysql_db_charset($GLOBALS['currentdb']);
			
            		C::set('command1', "CREATE DATABASE `".$dbname."` $db_charset");
			C::set('command2', "GRANT ALL ON `".$dbname."`.* TO \"$dbusername\"@\"$dbhost\"");
			$installoption = C::get('installoption', 'cfg');
			if(false === $installoption)
				$installoption = C::get('installoption');
			if ($installoption == '2' && !C::get('lodeldo')) {
                		C::set('dbusername', $dbusername);
                		C::set('dbhost', $dbhost);
				View::getView()->render('site-createdb');
				exit();
			}
			if (!$db->Execute(C::get('command1')) || !$db->Execute(C::get('command2'))) {
				C::set('error', $db->ErrorMsg());
                		C::set('dbusername', $dbusername);
                		C::set('dbhost', $dbhost);
				View::getView()->render('site-createdb');
				exit();
			}

		} while (0);
		return true;
	}

	/**
	 * Création des tables
	 *
	 * Cette fonction crée les tables lors de l'installation
	 *
	 */	
	function createTables()
	{
		global $db;
        
        	$name = C::get('name');
		if (!$name) {
				trigger_error("probleme interne 2", E_USER_ERROR);
		}

        	$dbname = C::get('dbname');

		$db->SelectDB($dbname) //selectionne la base de donnée du site
            		or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR); 
            
		if (!file_exists(LODELROOT. $this->versiondir."/install/init-site.sql")) {
			trigger_error("impossible de faire l'installation, le fichier init-site.sql est absent", E_USER_ERROR);
		}
		
		$text = file_get_contents(LODELROOT. $this->versiondir."/install/init-site.sql");
		$text.= "\n";
			
		$db_charset = $this->find_mysql_db_charset($dbname);

		$text = str_replace("_CHARSET_",$db_charset,$text);
		$sqlfile = lq($text);
		$sqlfile = preg_split ("/;\s*\n/", preg_replace("/#.*?$/m", '', $sqlfile));
		if (!$sqlfile) {
			trigger_error("le fichier init-site.sql ne contient pas de commande. Probleme!", E_USER_ERROR);
		}
		$error = array();
		foreach ($sqlfile as $cmd) {
			$cmd = trim($cmd);
			if ($cmd && !$db->Execute($cmd)) {
				array_push($error, $cmd, $db->ErrorMsg());
			}
		}
		
		if ($error) {
            		C::set('error_createtables', $error);
			View::getView()->render('site-createtables');
			exit();
		}
		$db->SelectDB(C::get('database', 'cfg')) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		return true;
	}	

	/**
	 * Procédure de création des répertoires
	 *
	 * Cette fonction gère la création des répertoires de lodel
	 *
	 */
	function createDir()
	{
		$installoption = C::get('installoption', 'cfg');
		if(false === $installoption)
			$installoption = C::get('installoption');

        	$path = C::get('path');
		if (!$path) {
            		C::set('path', '/'. C::get('name'));
			$path = '/'. C::get('name');
		}
		if(C::get('path') != '/')
		{
			$dir = LODELROOT. $path;
			if (!file_exists($dir) || !@opendir($dir)) {
				// il faut creer le repertoire rep
				if ((int)$installoption === 2 && !C::get('lodeldo')) {
					C::set('error_nonexists', !file_exists($dir));
					C::set('error_nonaccess', !@opendir($dir));
					View::getView()->render('site-createdir');
					exit();
				}

				// on essaie
				if (!file_exists($dir) && !@mkdir($dir, 0777 & octdec(C::get('filemask', 'cfg')))) {
					// on y arrive pas... pas les droits surement
					C::set('error_mkdir', $dir);
					View::getView()->render('site-createdir');
					exit();
				}
				@chmod($dir, 0777 & octdec(C::get('filemask', 'cfg')));
			}
		}
		
		// on essaie d'ecrire dans tpl si root
		if (C::get('path') == '/') {
			if (!is_writable(LODELROOT.'/tpl')) {
                		C::set('error_tplaccess', 1);
				View::getView()->render('site-createdir');
				exit();
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
	function maj_siteconfig($text, $var, $val = -1)
	{
		// lit le fichier
		$search = array(); 
		$rpl = array();
		if (is_array($var)) {
			foreach ($var as $v => $val) {
				if (!preg_match("/^\s*\\\$cfg\['$v'\]\s*=\s*\".*?\"/m", $text)) {
					trigger_error("la variable \$$v est introuvable dans le fichier de config.", E_USER_ERROR);
				}
				array_push($search, "/^(\s*\\\$cfg\['$v'\]\s*=\s*)\".*?\"/m");
				array_push($rpl, '\\1"'. $val. '"');
			}
		} else {
				if (!preg_match("/^\s*\\\$cfg\['$var'\]\s*=\s*\".*?\"/m", $text)) {
					trigger_error("la variable \$$var est introuvable dans le fichier de config.", E_USER_ERROR);
				}
				array_push($search, "/^(\s*\\\$cfg\['$var'\]\s*=\s*)\".*?\"/m");
				array_push($rpl, '\\1"'. $val. '"');
		}
		$newtext = preg_replace($search, $rpl, $text);
		if ($newtext == $text) {
			return true;
		}
		unset($text);
		// ecrit le fichier
		$cache = getCacheObject();
		return $cache->set(getCacheIdFromId('siteconfig.php'), $newtext);
	}	

	/**
	 * Gestion des fichiers
	 *
	 * Cette fonction gère l'installation des fichiers de lodel
	 *
	 */
	function manageFiles()
	{
		global $db;
		// verifie la presence ou copie les fichiers necessaires
		// cherche dans le fichier install-file.dat les fichiers a copier
		// on peut installer les fichiers
		if (!C::get('path')) {
			C::set('path', '/'. C::get('name'));
		}
		$root = str_replace('//', '/', LODELROOT. C::get('path')). '/';
		$siteconfigcache = cache_get('siteconfig.php');
		if (C::get('downloadsiteconfig')) { // download the siteconfig
			download('', 'siteconfig.php', $siteconfigcache);
			exit();
		}

		$atroot = C::get('path') == '/' ? 'root' : '';

		$siteconfigcache = file_get_contents(LODELROOT. $this->versiondir."/src/siteconfig$atroot.php");

		if(!$this->maj_siteconfig($siteconfigcache, array('site' => C::get('name'))))
		{
			View::getView()->render('site-file');
			exit();
		}
		$siteconfigdest = $root. 'siteconfig.php';

		// Si le fichier de conf n'existe pas on le crée ou propose de le créer
		if (!file_exists($siteconfigdest)) {
			$installoption = C::get('installoption', 'cfg');
			if(false === $installoption)
				$installoption = C::get('installoption');
			if ($installoption == '2' && !C::get('lodeldo')) {
				View::getView()->render('site-file');
				exit();
			}
			@unlink($siteconfigdest); // try to delete before copying. (?)
			// try to copy now.

			if (!@file_put_contents($siteconfigdest, cache_get('siteconfig.php'))) {
				C::set('siteconfigdest', $siteconfigdest);
				C::set('error_writing', 1);
				View::getView()->render('site-file');
				exit();
			}
			@chmod ($siteconfigdest, 0666 & octdec(C::get('filemask', 'cfg')));
		}
		// ok siteconfig est copie.
		if (C::get('path') == '/') { // c'est un peu sale ca.
			$this->install_file($root, $this->versiondir."/src", '');
		} else {
			$this->install_file($root, "../".$this->versiondir."/src", LODELROOT);
		}
		
		// clear the cache
		if(!function_exists('clearcache'))
			include 'cachefunc.php';
		clearcache();
	
		// ok on a fini, on change le status du site
		$db->SelectDB(C::get('database', 'cfg'));
		$db->Execute (lq("
            UPDATE #_MTP_sites 
                SET status=1 
                WHERE id='".C::get('id')."'")) 
            		or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

		
		// ajouter le modele editorial ?
		if (C::get('singledatabase', 'cfg')!="on") {
			if(C::get('name') != '')
				$pattern = C::get('name');

			if(!preg_match("`".$pattern."`", C::get('dbname')))
			{
				C::set('dbname', C::get('dbname').C::get('name'));
			}
			$db->SelectDB(C::get('dbname'));
		}

		$import = true;
		// verifie qu'on peut importer le modele.
		foreach(array('types', 'tablefields', 'persontypes', 'entrytypes') as $table) {
			$result = $db->Execute(lq("
            SELECT 1 
                FROM #_TP_$table 
                WHERE status>-64 
                LIMIT 0,1")) 
				or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

			if ($result->RecordCount()) {
				$import = false;
				break;
			}
		}

		if ($import) {
			$go = C::get('url'). "/lodel/admin/index.php?do=importmodel&lo=data";
		} else {
			$go = C::get('url'). '/lodel/edition';
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
	function maintenance()
	{
		global $db;
        	$id = C::get('id');
 		if($id > 0) 
        	{
			$site = $db->GetRow(lq("
            SELECT name, status 
                FROM #_MTP_sites 
                WHERE ".$this->critere."")) 
            		or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
            
            		$status = $site['status'];
            
			if($status == 32) 
            		{
				$status = -65;
			} 
			elseif($status == -65) 
			{
				$status = 32;
			} 
			else 
			{
				$status = $status == -64 ? 1 : -64;
			}
    		$lock = cache_get('lock');
			if($status > 0)
			{
				cache_delete($lock);
			}
			else
			{
				$cache = getCacheObject();
				$cache->set(getCacheIdFromId('lock'), true);
			}
			$db->Execute(lq("
        UPDATE #_MTP_sites 
            SET status = ".$status." 
            WHERE ".$this->critere."")) 
                	or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}
		elseif($id === 0) 
        	{
			$maintenance = (int)C::get('maintenance');
			$sites = $db->Execute(lq('SELECT id, status, name FROM #_MTP_sites'));
			$single = C::get('singledatabase', 'cfg') != "on";
			$lock = C::get('home', 'cfg').'../../';
			if($maintenance === 1) 
			{
				while($site = $sites->FetchRow())
				{
				if($site['status'] == -64)
				{
					$db->Execute(lq("UPDATE #_MTP_sites SET status = 1 WHERE id=".$site['id'])) 
					or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				}
				elseif($site['status'] == -65)
				{
					$db->Execute(lq("UPDATE #_MTP_sites SET status = 32 WHERE id=".$site['id'])) 
					or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				}
				$cache = getCacheObject();
				$cache->set(getCacheIdFromId('lock'), true);
				}
			} 
			elseif($maintenance === 2) 
			{
				while($site = $sites->FetchRow())
				{
					if($site['status'] == -1)
					{
						$db->Execute(lq("UPDATE #_MTP_sites SET status = -64 WHERE id=".$site['id'])) 
							or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
					}
					elseif($site['status'] == 32)
					{
						$db->Execute(lq("UPDATE #_MTP_sites SET status = -65 WHERE id=".$site['id'])) 
							or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
					}
					cache_delete('lock');
				}
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
	$error = C::get('error_createtables');
	do {
				$localcontext = array();
		$localcontext['command'] = array_shift($error);
		$localcontext['error']   = array_shift($error);
		call_user_func("code_do_$funcname", array_merge(C::getC(), $localcontext));
	} while ($error);
}
