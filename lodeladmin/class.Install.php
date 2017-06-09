<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier de la classe d'installation
 */

class Install {

	/**
	 * Suffixe de la version de Lodel
	 * @var string
	 */
	private $versionsuffix;

	/**
	 * Version à installer
	 * @var string
	 */	
	private $versioninstall;

	/**
	 * Fichier de configuration Lodel
	 * @var string
	 */
	private $lodelconfig;

	/**
	 * Plateforme Lodel
	 * @var string
	 */
	private $plateform;

	/**
	 * Répertoire de la plateforme
	 * @var string
	 */
	private $plateformdir;

	/**
	 * Plateforme lodelconfig
	 * @var string
	 */
	private $lodelconfigplatform;

	/**
	 * Tableau des répertoires à protéger avec un htaccess
	 * @var string
	 */
	private $protecteddir;

	/**
	 * Choix de la langue
	 * @var string
	 */
	private $langChoice;

	/**
	 * Chmod ?
	 * @var string
	 */
	private $have_chmod;

	/**
	 * installoption
	 * @var int
	 */
	private $installoption;


	/**
	 * Constructeur
	 *
	 * Instancie un objet de la classe
	 *
	 * @param string $lodelconfig chemin vers fichier lodelconfig temporaire
	 * @param array $context le contexte passé par référence
	 */
	public function __construct($lodelconfig, $have_chmod, $plateformdir)
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
	public function get($var)
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
	public function set($var, $valeur)
	{
		$this->$var = $valeur;
	}


	/**
	 * Test connexion à la base de données
	 *
	 * Cette fonction tente de se connecter à la base de données, met le charset par defaut et identifie l'utilisateur
	 * comme administrateur si jamais il existe un enregistrement dans la table username
	 */
	public function testInstallDB()
	{
		@include($this->lodelconfig);
		require "../lodel/scripts/auth.php";
		if (@mysql_connect(C::get('dbhost','cfg'),C::get('dbusername','cfg'),C::get('dbpasswd','cfg'))) {
			@mysql_select_db(C::get('database','cfg'));
			mysql_query("SET SESSION sql_mode = '';");
			$this->set_mysql_charset();
		
			// test whether we access to a DB and whether the table users exists or not and whether it is empty or not.
		
			$result=@mysql_query("SELECT username FROM `".C::get('tableprefix','cfg')."users` LIMIT 0,1");
			if ($result && @mysql_num_rows($result)>0)
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
	public function installConf($testfile)
	{
		$this->plateform=preg_replace("/[^A-Za-z_-]/","",$this->plateform);
		if (!$this->plateform) $this->plateform="default";

		$this->lodelconfigplatform=$this->plateformdir."/lodelconfig-".$this->plateform.".php";

		if (file_exists($this->lodelconfigplatform)) {
			// essai de copier ce fichier dans le CACHE

			if (!copy($this->lodelconfigplatform,$this->lodelconfig)) { trigger_error("probl&egrave;me de droits... &eacute;trange on a d&eacute;j&agrave; v&eacute;rifi&eacute;", E_USER_ERROR); }
			if (file_exists(LODELROOT."lodelloader.php")) {
				// the installer has been use, let's chmod safely
				$chmod=fileperms(LODELROOT."lodel");
			} else {
				$chmod=0600;  // c'est plus sur, surtout a cause du mot de passe sur la DB qui apparaitra dans ce fichier.
			}
			chmod($this->lodelconfig,$chmod);
			include($this->lodelconfig);
			$this->maj_lodelconfig(array("home"=>$cfg['pathroot'].'/lodel/scripts/'));
		} else {
			trigger_error("ERROR: ".$this->lodelconfigplatform." does not exist. Internal error, please report this bug.", E_USER_ERROR);
		}
		$arr=array();
		$needoptions=false;
		$arr['installoption']=$this->installoption;
		// guess the urlroot
		$me=$_SERVER['PHP_SELF'];
		if ($me) {
			// enleve moi
			$urlroot=preg_replace("/\/+lodeladmin\/install.php$/","",$me);
			if ($urlroot==$me) trigger_error("ERROR: the install.php script is not at the right place, please report this bug.", E_USER_ERROR);
			if (LODELROOT!="../") trigger_error("ERROR: the lodeladmin directory has been moved, please report this bug.", E_USER_ERROR);
			
			$arr['urlroot']=$urlroot."/";
		}
		
		// is there a filemask ?

		if ($_REQUEST['filemask']) {
			// passed via the URL
			$arr['filemask']="0".$_REQUEST['filemask'];
		} elseif ($cfg['filemask']) {
			// was in the previous lodelconfig.php
			$arr['filemask']=$cfg['filemask'];
		} else {
			$arr['filemask']="0".decoct($this->guessfilemask($testfile));
		}

		$arr['installlang']=$this->installlang;

		if ($this->installoption==1) {
			// try to guess the options.
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
	public function majConfDB($newdbusername, $newdbpasswd, $newdbhost)
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
	 * @param string $newtableprefix prefixe des tables pour installation multi site // Deprecated
	 * @param string $createdatabase on crée la base de données ou pas ?
	 * @param string $existingdatabase on utilise une base existante ?
	 */
	public function manageDB($erasetables, $singledatabase, $newdatabase, $newsingledatabase, $newtableprefix='', $createdatabase, $existingdatabase)
	{
		@include($this->lodelconfig);    // insert the lodelconfig. Should not be a problem.
		if($erasetables)
		{
			@mysql_connect($cfg['dbhost'],$cfg['dbusername'],$cfg['dbpasswd']); // connect
			@mysql_select_db($cfg['database']); // selectionne la database

			$this->set_mysql_charset();
			// erase the table of each site
			
			$result=@mysql_query("SELECT name FROM ".$cfg['tableprefix']."sites") or trigger_error(mysql_error(), E_USER_ERROR);

			if ($singledatabase) {
				// currently singledatabase implies single site ! That's shame but...
				// Let's destroyed everything in the database with the prefix !
				if (!$cfg['tableprefix']) {
					// we can't destroy... too dangerous. Should find another solution.
					trigger_error("Sans tableprefix les tables ne peuvent pas etre efface en toute securite. Veuillez effacer vous-même les tables de Lodel. Merci.", E_USER_ERROR);
				} else {
					// get all table names.
					$result=@mysql_list_tables($cfg['database']);

					while ($row = @mysql_fetch_row($result)) {
						if (preg_match("/^{$cfg['tableprefix']}/",$row[0])) {
							// let's drop it
							@mysql_query("DROP TABLE $row[0]");
						}
					}
				}
			} else {
				trigger_error("<p>L'effacement des tables avec plusieurs bases de données n'est pas implementé. Veuillez effacer les bases de données vous même. Merci.</p>", E_USER_ERROR);
			}
			// erase the main tables below.
		} else { // normal case
			$set=array();

			if ($cfg['installoption'] > 1) {
				$set['singledatabase']=$newsingledatabase ? "on" : "";
				$set['tableprefix']='';
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
				@mysql_connect($cfg['dbhost'],$cfg['dbusername'],$cfg['dbpasswd']); // connect
				$this->set_mysql_charset();
				$db_charset = 'CHARACTER SET utf8 COLLATE utf8_general_ci';
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
	public function manageAdmin($adminusername, &$adminpasswd, &$adminpasswd2, $lang, $site, $adminemail)
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
		@mysql_connect($cfg['dbhost'],$cfg['dbusername'],$cfg['dbpasswd']); // connect
		@mysql_select_db($cfg['database']); // selectionne la database
		$this->set_mysql_charset();

		$adminusername=addslashes($adminusername);
		$pass=md5($adminpasswd.$adminusername);
		$adminpasswd2 = null;
		$adminemail = addslashes($adminemail);
		if (!preg_match("/^\w{2}(-\w{2})?/",$lang)) trigger_error("ERROR: invalid lang", E_USER_ERROR);
		$lang = addslashes($lang);
		if (!@mysql_query("REPLACE INTO ".$cfg['tableprefix']."users (username,passwd,email,userrights,lang) VALUES ('$adminusername','$pass','$adminemail',128,'$lang')")) {
			unset($pass);
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
	public function set_htaccess($verify, $write, $nohtaccess)
	{	
		$currentLodelDir = "lodel";
		
		if ($verify || $write) $this->maj_lodelconfig("htaccess","on");
		if ($nohtaccess) $this->maj_lodelconfig("htaccess","off");
		if ($write) {
			$erreur_htaccesswrite=array();
			foreach ($this->protecteddir as $dir) {
				if (file_exists(LODELROOT.$dir) && !file_exists(LODELROOT.$dir."/.htaccess")) {
					$file=@fopen(LODELROOT.$dir."/.htaccess","w");
					if (!$file) {
						array_push($erreur_htaccesswrite,$dir);
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
	 * @param string $newuri type d'url affichée
	 */
	public function maj_options($newurlroot, $permission, $newimportdir, $newextensionscripts, $newusesymlink, $newcontactbug, $newuri)
	{
		$newurlroot = $newurlroot."/"; // ensure their is a / at the end
		$newurlroot = preg_replace("/\/\/+/","/",$newurlroot); // ensure there is no double slashes because it causes problem with the cookies
		$filemask = "07" . (5*($permission['group']['read']!="")+2*($permission['group']['write']!="")) . (5*($permission['all']['read']!="")+2*($permission['all']['write']!=""));
		

		$this->maj_lodelconfig(array("chooseoptions"=>"oui",
					"urlroot"=>$newurlroot,
					"importdir"=>$newimportdir,
					"extensionscripts"=>$newextensionscripts,
					"usesymlink"=>$newusesymlink,
					"filemask"=>$filemask,
					"contactbug"=>$newcontactbug,
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
	public function downloadlodelconfig($log_version)
	{
		header("Content-type: application/force-download");
		header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Content-Disposition: attachment; filename="lodelconfig.php"');
        header('Pragma: no-cache');
		readfile($this->lodelconfig);
		return true;
	}

	/**
	 * Affichage de la config
	 *
	 * Cette fonction affiche le contenu du fichier de configuration
	 *
	 */
	public function showlodelconfig()
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
	public function startInstall()
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
	private function testdirmode($dir, $mode, $cheminAbsolu=false)
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
	public function testRights()
	{
		$dirs=array(
			"lodeladmin/tpl"=>5,
			"lodel"=>5,
			"lodel/install"=>5,
			"lodel/install/plateform"=>5,
			"lodel/scripts"=>5,
			"lodel/src"=>5,
			"share/css"=>5,
			"share/js"=>5,
			"share/macros"=>5
        );
		
		if((int)$this->installoption === 1)
		{
			$dirs['tpl'] = 7;
			$dirs['tpl/index.html'] = 7;
			$dirs['tpl/macros_accueil.html'] = 7;
		}
	
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
	public function checkConfig()
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
	public function checkFunc()
	{
		require_once LODELROOT . 'lodel/scripts/context.php';
		if(defined('INC_FUNC')) return;
		if ((include(LODELROOT."lodel/scripts/func.php"))!=568) { // on accede au fichier func.php
			trigger_error("ERROR: unable to access the ../lodel/scripts/func.php file. Check the file exists and the rights and/or report the bug.", E_USER_ERROR);
		}
	}

	/**
	 * Test accessibilité base de données
	 *
	 * Cette fonction teste l'identifiant, mot de passe et url du serveur de base de données. Si ceux-ci sont absent on les demande
	 *
	 */
	public function checkDB()
	{
		@include($this->lodelconfig);
		if (!$cfg['dbusername'] || !$cfg['dbhost']) {
			$this->include_tpl("install-mysql.html");
			return false;
		} elseif (!@mysql_connect($cfg['dbhost'],$cfg['dbusername'],$cfg['dbpasswd'])) { // tente une connexion
			$GLOBALS['erreur_connect']=1;
			$this->include_tpl("install-mysql.html");
//			return "error_cnx";
		}
	}

	/**
	 * Liste des base de données
	 *
	 * Cette fonction retourne la liste des base de données existante sur le serveur
	 *
	 */
	public function seekDB()
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
	public function installDB($erasetables, $tache)
	{
		@include($this->lodelconfig);
		$sitesexistsrequest="SELECT id,status FROM ".$cfg['tableprefix']."sites LIMIT 1";

		if (!@mysql_select_db($cfg['database'])) { // ok, database est defini, on tente la connection
			return "error_dbselect";
		} elseif($this->find_mysql_db_charset($cfg['database'], $charset) && 'utf8' !== $charset) {
			$GLOBALS['erreur_utf8'] = true;
			$this->include_tpl("install-database.html");
		} elseif ($erasetables || !@mysql_query($sitesexistsrequest)) {   // regarde si la table sites exists ?
			// non, alors on cree les tables

			// il faudrait tester ici que les tables sur la database sont bien les memes que celles dans le fichier
			// les IF NOT EXISTS sont necessaires dans le fichier init.sql sinon ca va produire une erreur.
			
			$erreur_createtables=$this->mysql_query_file(LODELROOT."lodel/install/init.sql",$erasetables,$database);

			// no error, let's add the translations of the interface.
			if (!$erreur_createtables) 
				$erreur_createtables=$this->mysql_query_file(LODELROOT."lodel/install/init-translations.sql",$erasetables, $cfg['database']);

			if ($erreur_createtables) {
				// mince, ca marche pas... bon on detruit la table sites si elle existe pour pouvoir revenir ici
				if (@mysql_query($sitesexistsrequest)) {
					if (!@mysql_query("DROP TABLE IF EXISTS ".$cfg['tableprefix']."sites")) { // ok, on n'arrive vraiment a rien faire
						$erreur_createtables.="<br /><br />La commande DROP TABLE IF EXISTS ".$cfg['tableprefix']."sites n'a pas pu être executée. On ne peut vraiment rien faire !";
					}
				}
				preg_match("`<font COLOR=red>(.*?)</font>`is", $erreur_createtables, $res);
				return $res[1];
			}
			
			// let's deal with the problem of lock table.
			$ret=@mysql_query("LOCK TABLES {$cfg['tableprefix']}sites WRITE");
			if (!$ret) { // does not support LOCK table
				$this->maj_lodelconfig("DONTUSELOCKTABLES",true);
			} else {
				@mysql_query("UNLOCK TABLES") or trigger_error(mysql_error(), E_USER_ERROR);
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
	public function verifyAdmin()
	{
		@include($this->lodelconfig);
		$result=@mysql_query("SELECT id FROM ".$cfg['tableprefix']."users LIMIT 1") or trigger_error(mysql_error(), E_USER_ERROR);
		if (!@mysql_num_rows($result)) { // il faut demander la creation d'un admin
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
	public function checkHtaccess()
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
	public function askOptions($importdir, $chooseoptions)
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
	public function verifyLodelConfig()
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
			if(!is_writeable(LODELROOT.$file)) $this->include_tpl("install-lodelconfig.html");
			unlink(LODELROOT.$file);
			if (@copy($this->lodelconfig,LODELROOT.$file)) { // let copy
				@chmod(LODELROOT.$file,0666 & octdec($cfg['filemask']));
			} else { // error
				$this->include_tpl("install-lodelconfig.html");
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
	public function finish()
	{
		@include($this->lodelconfig);
		if (!defined("DATABASE")){
			define("DATABASE", $cfg['database']);
			define("DBUSERNAME", $cfg['dbusername']);
			define("DBPASSWD", $cfg['dbpasswd']);
			define("DBHOST", $cfg['dbhost']);
			define("DBDRIVER", 'mysql');
			define("SINGLEDATABASE", $cfg['singledatabase']);
		}
		unlink($this->lodelconfig);
		// finish !
		if ((int)$cfg['installoption']===1) { // essaie de creer automatiquement le site
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
	private function maj_lodelconfig($var,$val=-1)
	{
		// lit le fichier
		$text=$oldtext=file_get_contents($this->lodelconfig);
		//  if (!$text) trigger_error("ERROR: $lodelconfig can't be read. Internal error, please report this bug", E_USER_ERROR);
		
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
		if(!$f) trigger_error("ERROR: ".$this->lodelconfig." is not writeable. Internal error, please report this bug.", E_USER_ERROR);
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
	private function maj_lodelconfig_var($var,$val,&$text)
	{
		if (strtoupper($var)==$var) { // it's a constant
			if (!preg_match("/^\s*define\s*\(\"$var\",.*?\);/m",$text,$result)) {	trigger_error("la constante $var est introuvable dans le fichier de config.", E_USER_ERROR);      }
			if (is_string($val)) $val='"'.$val.'"';
			if (is_bool($val)) $val=$val ? "true" : "false";
			
			$text=str_replace($result[0],"define(\"$var\",".$val.");",$text);
		} else { // no, it's a variable
			if (!preg_match("/^\s*\\\$cfg\['$var'\]\s*=\s*\".*?\";/m",$text,$result)) {	trigger_error("la variable \$$var est introuvable dans le fichier de config.", E_USER_ERROR);      }
			$text=str_replace($result[0],"\$cfg['$var']=\"$val\";",$text);
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
	private function mysql_query_file($filename,$droptables=false, $db)
	{
		@include($this->lodelconfig);
//		$table_charset = $this->find_mysql_db_charset($db);
		// commenté par P.A. le 09/10/08, UTF8 uniquement à partir de Lodel > 0.8.7
// 		if (strpos($filename, 'init-translations.sql') && strpos($table_charset, 'utf8')) {
// 			$filename = str_replace('init-translations.sql', 'init-translations_utf8.sql', $filename);
// 		}
		$sqlfile=preg_replace('/#_M?TP_/',$cfg['tableprefix'], file_get_contents($filename));
		$sqlfile=str_replace('_CHARSET_', ' CHARACTER SET utf8 COLLATE utf8_general_ci' , $sqlfile);
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
					if ($droptables && preg_match('/^\s*CREATE\s+(?:TABLE\s+IF\s+NOT\s+EXISTS\s+)?'.$cfg['tableprefix'].'(\w+)/',$cmd,$result)) {
						if (!@mysql_query('DROP TABLE IF EXISTS '.$result[1])) {
							$err.="$cmd <font COLOR=red>".mysql_error().'</font><br>';
						}
					}
					// execute the command
					if (!@mysql_query($cmd)) {
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
	private function guessfilemask($testfile) {
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
	public function include_tpl($file)
	{
		@include($this->lodelconfig);
		extract($GLOBALS,EXTR_SKIP);
		@extract($cfg, EXTR_SKIP);
		$plateformdir = $this->plateformdir;
		$installoption = $this->installoption;
		if (!$this->installlang) $this->installlang="fr";
		if($installlang != $this->installlang)
			$installlang = $this->installlang;
		if (!$langcache) {
			if (!(include_once ("tpl/install-lang-".$this->installlang.".html"))) $this->problem_include("tpl/install-lang-".$this->installlang.".html");
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
	public function problem_include($filename)
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
	}

	/**
	 * Impression message d'erreur
	 *
	 * Cette fonction affiche un message d'erreur lorsqu'un problème survient
	 *
	 * @param string $msg message à afficher
	 */	
	public function problem($msg)
	{
		@include($this->lodelconfig);
		extract($GLOBALS,EXTR_SKIP);

		//$installlang = $_REQUEST['installlang'];
		if (!$this->installlang) $this->installlang="fr";
		global $langcache;
		if (!$langcache) {
			if (!(require_once ("tpl/install-lang-".$this->installlang.".html"))) $this->problem_include("tpl/install-lang-".$this->installlang.".html");
		}
		
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
	 */		
	private function probleme_droits($missing_dirs, $not_writable_dirs, $not_readable_dirs)
	{
		@include($this->lodelconfig);
		if (!$this->installlang) $this->installlang="fr";
		global $langcache;
		if (!$langcache) {
			if (!(require_once ("tpl/install-lang-".$this->installlang.".html"))) $this->problem_include("tpl/install-lang-".$this->installlang.".html");
		}
		
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
	private function set_mysql_charset() 
	{
		return @mysql_query('SET NAMES utf8');
	}
	
	/**
	 * Connaitre le charset
	 *
	 * Cette fonction retourne le charset de la base database
	 *
	 * @param string $database nom de la base de données
	 */	
	private function find_mysql_db_charset($database, &$charset = null, &$collation = null) 
	{
		@mysql_select_db($database);
		$result = @mysql_query("SHOW VARIABLES LIKE '%_database'");
		while ($row = @mysql_fetch_array($result)) {
			if ($row['Variable_name'] == 'character_set_database') { $charset =  $row['Value'];}
			if ($row['Variable_name'] == 'collation_database') { $collation =  $row['Value'];}
		}
		
		if (!empty($charset) && !empty($collation)) {
			$set_db_charset = " CHARACTER SET $charset COLLATE $collation";
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
	public function makeSelectLang()
	{
		@include($this->lodelconfig);
		$text_lang = "";
		$GLOBALS['database'] = $cfg['database'];
		$GLOBALS['dbusername'] = $cfg['dbusername'];
		$GLOBALS['dbpasswd'] = $cfg['dbpasswd'];
		$GLOBALS['dbhost'] = $cfg['dbhost'];
		require_once(LODELROOT . "lodel/scripts/context.php");
		C::setCfg($cfg);
 		require_once(LODELROOT . "lodel/scripts/connect.php");
		global $db;
 		$result=$db->execute(lq("SELECT lang,title FROM #_MTP_translations WHERE status>0 AND textgroups='interface'")) 
			or trigger_error("ERROR : error during selecting lang", E_USER_ERROR);
 		$lang=$this->installlang;
 		while(!$result->EOF) {
 			$selected=$lang==$result->fields['lang'] ? "selected=\"selected\"" : "";
 			$text_lang .= '<option value="'.$result->fields['lang'].'" '.$selected.'>'.$result->fields['title'].'</option>';
 			$result->MoveNext();
 		}
		echo $text_lang;
	}
}
?>
