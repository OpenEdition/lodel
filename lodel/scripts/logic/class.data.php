<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */


/**
 * Classe de logique permettant de gérer les backup et import de données et de ME
 *
 */
class DataLogic
{

	/**
	 * Prefix du fichier (pour l'import de ME et l'import de données)
	 * @var string
	 */
	private $filePrefix;

	/**
	 * Expression utilisée pour filtrer les fichiers pour un import
	 * @var string
	 */
	private $fileRegexp;

	/**
	 * Extension du fichier d'import
	 * @var string
	 */
	private $fileExtension;

	/* IMPORT ME XML */
	/**
	 * Tables correspondantes au ME
	 * @var array
	 */
	private $_tables;

	/**
	 * Tables enregistrées lors du parsage du fichier XML
	 * @var array
	 */
	private $_recordedTables;

	/**
	 * Structure de la base XML
	 * @var array
	 */
	private $_xmlStruct;

	/**
	 * Structure de la base SQL
	 * @var array
	 */
	private $_sqlStruct;

	/**
	 * Liste des tables différentes entre SQL/XML
	 * @var array
	 */
	private $_changedTables;

	/**
	 * Liste des champs différents entre chaque table SQL/XML
	 * @var array
	 */
	private $_changedFields;

	/**
	 * Donnés récupérées dans le XML
	 * @var array
	 */
 	private $_xmlDatas;

	/**
	 * Tables à créer (présentes dans le XML et non dans la base)
	 * @var array
	 */
	private $_tableToCreate;

	/**
	 * Tableau des requêtes à effectuer
	 * @var array
	 */
	private $_sql;

	/**
	 * Tableau des champs absent du XML mais présent dans la base
	 * @var array
	 */
	private $_fieldsToKeep;

	/**
	 * Tableau des types (entité ou entrée) n'ayant pas trouvé leur équivalent dans le XML
	 * @var array
	 */
	private $_changedTypes;

	/**
	 * Tableau utilisé pour comparer le contenu de certaines tables
	 * @var array
	 */
	private $_changedContent;

	/**
	 * Tableau des noms des classes du ME XML
	 */
	private $_classes;

	/**
	 * Tableau des types changés ayant une classe différente
	 */
	private $_typesClass;
	/* FIN IMPORT ME XML */

	/**
	 * Constructeur
	 *
	 * Interdit l'accès aux utilisateurs qui ne sont pas ADMIN
	 */
	public function __construct()
	{
		$do = C::get('do');
 		if ((!C::get('admin', 'lodeluser')
                || (!C::get('adminlodel', 'lodeluser') && ($do == 'import'
                || $do == 'backup'
                || $do == 'importmodel'
                || $do == 'importxmlmodel'
                || $do == 'globalbackup')))
                && !(C::get('redactor', 'lodeluser') && $do == 'import')
                ) {
                        trigger_error("ERROR: you don't have the right to access this feature", E_USER_ERROR);
                }

		function_exists('importFromZip') || include 'backupfunc.php';
		$this->fileExtension = 'zip';
	}

	/**
	 * Importation des données
	 *
	 * Cette fonction importe les données issus d'un backup de lodel : le dump SQL, les fichiers associés (si ils ont été sauvegardés).
	 *
	 * @param array $context le contexte passé par référence
	 * @param array $error les éventuelles erreur, passées par référence
	 */
	public function importAction(&$context, &$error)
	{
		if(!C::get('adminlodel', 'lodeluser')) trigger_error("ERROR: you don't have the right to access this feature", E_USER_ERROR);
		global $db;

		$context['importdir'] = C::get('importdir', 'cfg');
		$this->fileRegexp = $context['fileregexp'] = '(site|revue)-[a-z0-9\-]+-\d{6}.'. $this->fileExtension;

		// les répertoires d'import
		$context['importdirs'] = array();
		if ($context['importdir']) {
			$context['importdirs'][] = $context['importdir'];
		}

		$file = $this->_extractImport($context);

		if ($file) { // Si on a bien spécifié un fichier
			do { // control block

				set_time_limit(0); //pas d'effet si safe_mode on ; on met le temps à unlimited
				//nom du fichier SQL
				$sqlfile = tempnam(tmpdir(), 'lodelimport_');
				//noms des répertoires acceptés
				$accepteddirs = array('lodel/txt', 'lodel/rtf', 'lodel/sources', 'lodel/icons', 'docannexe/file', 'docannexe/image', 'docannexe/fichier'/*compat 0.7*/);
				if (!importFromZip($file, $accepteddirs, array(), $sqlfile)) {
					$err = $error['error_extract'] = 'extract';
					return 'import';
				}
				#require_once 'connect.php';
				// drop les tables existantes
				//$db->execute(lq('DROP TABLE IF EXISTS '. join(',', $GLOBALS['lodelsitetables']))) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				//execution du dump SQL
				if (!$this->_execute_dump($sqlfile)) {
					$error['error_execute_dump'] = $err = $db->errormsg();
				}
				@unlink($sqlfile);

				clearcache();

				// verifie les .htaccess dans le cache
				$this->_checkFiles($context);
			} while(0);
		} else {
			return 'import';
		}
		if(!$error) {
				$context['success'] = 1;
		}
		return 'import';
	}

	/**
	 * Sauvegarde des données
	 *
	 * Fait un dump de la base de données du site et si indiqué sauve aussi les fichiers annexes et source.
	 *
	 * @param array $context le contexte passé par référence
	 * @param array $error les éventuelles erreurs, passées par référence
	 */
	public function backupAction(&$context, &$error)
	{
		if(!C::get('adminlodel', 'lodeluser')) trigger_error("ERROR: you don't have the right to access this feature", E_USER_ERROR);

		$context['importdir'] = C::get('importdir', 'cfg');

		if (isset($context['backup'])) { // si on a demandé le backup
			set_time_limit(0);
			$site = C::get('site', 'cfg');
			$outfile = "site-$site.sql";

			$GLOBALS['tmpdir'] = $tmpdir = tmpdir(uniqid('sitebackup'));

			$errors = array();

            /* On fait le dump de la base de données */
			$this->_dump($site, $tmpdir . DIRECTORY_SEPARATOR. $outfile, $errors);

			if($errors) {
				$error = $errors;
				return 'backup';
			}

			// verifie que le fichier SQL n'est pas vide
			if (!file_exists($tmpdir. DIRECTORY_SEPARATOR . $outfile) || filesize($tmpdir. DIRECTORY_SEPARATOR . $outfile) <= 0) {
				$error['mysql'] = 'dump_failed';
				return 'backup';
			}

			// zip le site et ajoute la base
			$archivetmp = tempnam($tmpdir, 'lodeldump_'). '.zip';
            $archivefilename = "site-$site-". date("dmy"). '.zip';

			// fichiers à exclure de l'archive
			$GLOBALS['excludes'] = $excludes = array(
                '#\.htaccess$#',
                '#index.html$#',
                '#^docannexe/image/tmpdir-.*$#',
                '#^docannexe/tmp.*$#'
            );

			// répertoires à inclure
			$sitedirs = array(
                'lodel/icons',
                'lodel/sources',
                'docannexe/fichier',
                'docannexe/file',
                'docannexe/image',
            );

			// si sauvegarde des répertoires demandée (en + de la base)

            $files_to_zip = array();

			if (empty($context['sqlonly'])) {
                /* On créé la liste des fichiers à sauvegarder */
                $siteroot = realpath(SITEROOT) . DIRECTORY_SEPARATOR;

                foreach($sitedirs as $sitedir)
                {
                    $files_to_zip = array_merge(
                        $files_to_zip,
                        array_filter(glob_recursive( $siteroot . $sitedir . DIRECTORY_SEPARATOR . "*"), 'is_file')
                    );
                }

                /* Suppression des fichier à exclure */
                foreach($excludes as $exclude)
                {
                    $files_to_zip = preg_grep($exclude, $files_to_zip, PREG_GREP_INVERT);
                }

                /* On définit les chemin dans le zip et dans le filesystem */
                $files_and_names = array(
                    $tmpdir. DIRECTORY_SEPARATOR . $outfile => $outfile,
                );
                foreach($files_to_zip as $file)
                {
                    $files_and_names[$file] = str_replace($siteroot, '', $file);
                }
                unset($files_to_zip);
            }

			/* On créé le zip dans $archivetmp */
			$ok = create_zip_from_file_list($archivetmp, $files_and_names);

			@unlink($tmpdir. DIRECTORY_SEPARATOR . $outfile);
			if (!file_exists($archivetmp) || true !== $ok) {
				trigger_error("ERROR: the zip command reported an error: «{$ok}».\nArchive: $archivetmp", E_USER_ERROR);
			}

			if($error) { // Pour avoir accès aux erreurs dans les templates
				$context['error'] = $error;
            }

			if (operation($context['operation'], $archivetmp, $archivefilename, $context)) {
				$context['success'] = 1;
				return 'backup';
			}
			else {
				$context['success'] = 1;
				return 'backup';
			}

			return 'backup';
		}
		else {
			return 'backup';
		}
	}

	/**
	 * Backup global des données. Seulement autorisé pour un admin lodel
	 *
	 * Cela crée un backup de la base principale mais aussi de tous les sites
	 *
	 * @param array $context le contexte passé par référence
	 * @param array $error les éventuelles erreurs, passées par référence
	 * @return le nom du template utilisé pour cette action : backup
	 * @todo Trouver une alternative à la commande système tar
	 */
	public function globalbackupAction(&$context, &$error)
	{
		if(!C::get('adminlodel', 'lodeluser')) trigger_error("ERROR: you don't have the right to access this feature", E_USER_ERROR);
		global $db;
		$context['importdir'] = C::get('importdir', 'cfg');

		if (isset($context['backup'])) {
			if(empty($context['operation']))
				trigger_error("ERROR: unknonw operation", E_USER_ERROR);
            		$operation = $context['operation'];
			set_time_limit(0); // pas d'effet en safe mode
			// il faut locker la base parce que le dump ne doit pas se faire en meme temps que quelqu'un ecrit un fichier.
			$dirtotar  = array();
			$dirlocked = tempnam(tmpdir(), 'lodeldump_'). '.dir'; // this allow to be sure to have a unique dir.
			@mkdir($dirlocked, 0700);
			$outfile = 'lodel.sql';
			$fh = @fopen($dirlocked. '/'. $outfile, 'w');

			if (!$fh) {
				trigger_error("ERROR: unable to open a temporary file in write mode", E_USER_ERROR);
			}
			// save the main database
			if (fputs($fh, 'DROP DATABASE IF EXISTS '. DATABASE. ";\nCREATE DATABASE ". DATABASE. ";USE ". DATABASE. ";\n") === FALSE) {
				trigger_error("ERROR: unable to write in the temporary file", E_USER_ERROR);
			}

			$GLOBALS['currentprefix'] = '#_MTP_';

			mysql_dump(DATABASE, $GLOBALS['lodelbasetables'], '', $fh);

			// Trouve les sites a inclure au backup.
			//$errors = array();
			$result = $db->execute(lq('SELECT name, path FROM #_MTP_sites WHERE status > -32')) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			$GLOBALS['currentprefix'] = '#_TP_';
			while (!$result->EOF) {
				$name = $result->fields['name'];
				$sitepath = $result->fields['path'];
				if (fputs($fh, 'DROP DATABASE IF EXISTS '. $name. ";\nCREATE DATABASE ". $name. ";USE ". $name. ";\n") === FALSE) {
					trigger_error("ERROR: unable to write in the temporary file", E_USER_ERROR);
				}
				$this->_dump($name, $outfile, $errors, $fh);
				if (empty($context['sqlonly'])) {
					chdir(LODELROOT);
					if ($sitepath == '/') { $root = ''; } // site à la racine
					else { $root = $name . '/'; }
					// liste des répertoires du site à archiver
					$sitedirs = array('lodel/icons', 'lodel/sources', 'docannexe');
					$bad_dirs = array();
					//verifie que les repertoires sont accessibles en lecture
					foreach ($sitedirs as $sitedir) {
						if(is_readable($root . $sitedir)){ $dirtotar[] = $root . $sitedir;}
						else { $bad_dirs[] = $root . $sitedir;}
					}
					if (!empty($bad_dirs)) { isset($error['files']) || $error['files'] = ''; $error['files'] .= implode(', ', $bad_dirs); }
					chdir('lodeladmin'. (C::get('version', 'cfg') ? '-'. C::get('version', 'cfg') : ''));
				}
				$result->MoveNext();
			}
			fclose($fh);
			$db->selectDB(DATABASE); //selectionne la base principale.
			chdir(LODELROOT);
			// tar les sites et ajoute la base
			$archivetmp      = tempnam(tmpdir(), 'lodeldump_');
			$archivefilename = 'lodel-'. date('dmy'). '.tar.gz';
			// Attention ce qui suit ne fonctionnera que sous Linux
			system("tar czf $archivetmp ". join(' ', $dirtotar). " -C $dirlocked $outfile") !== false or trigger_error("impossible d'executer tar", E_USER_ERROR);
			unlink($dirlocked. '/'. $outfile);
			rmdir($dirlocked);
			chdir('lodeladmin'. (C::get('version', 'cfg') ? '-'. C::get('version', 'cfg') : ''));
			if (operation($operation, $archivetmp, $archivefilename, $context)) {
				return 'backup';
			}
		}

		//$context['error'] = $errors;
		return 'backup';
	}

	/**
	 * Importation du modèle éditorial
	 *
	 * Importe les données contenu dans un fichier ZIP de sauvegarde du ME
	 *
	 * @param array $context le contexte passé par référence
	 * @param array $error les éventuelles erreurs, passées par référence
	 * @return le nom du template utilisé pour cette action : importmodel
	 */
	public function importmodelAction(&$context, &$error)
	{
		if(!C::get('adminlodel', 'lodeluser')) trigger_error("ERROR: you don't have the right to access this feature", E_USER_ERROR);
		//Vérifie que l'on peut bien faire cet import
		$context['importdir'] = C::get('importdir', 'cfg'); //cherche le rep d'import défini dans la conf
		$GLOBALS['importdirs'] = array ( C::get('home', 'cfg'). '../install/plateform');
		if ($context['importdir']) {
			$GLOBALS['importdirs'][] = $context['importdir'];
		}
		$context['importdirs'] = $GLOBALS['importdirs'];
		$this->fileExtension = 'zip';
		$this->fileRegexp = $GLOBALS['fileregexp'] = '(model)-\w+(?:-\d+)?.'. $this->fileExtension; //restriction sur le nom du ZIP

		if (($context['error_table'] = $this->_isimportmodelallowed()) ) {
			return 'importmodel';
		}
		$this->filePrefix = 'model';
		$file = $this->_extractImport($context);

		if ($file) {
			$sqlfile = tempnam(tmpdir(), 'lodelimport_');
			$accepteddirs = array('tpl', 'css', 'images', 'js', 'lodel/icons');
			$acceptedexts = array('html', 'js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'tiff', 'js');
			if (!importFromZip($file, $accepteddirs, $acceptedexts, $sqlfile)) {
				$err = $context['error_extract'] = 1;
			}

			// execute the dump
			if (!$this->_execute_dump($sqlfile)) {
				$context['error_execute_dump'] = $err->errormsg();
			}
			@unlink($sqlfile);

			// change the id in order there are minimal and unique
			$this->_reinitobjetstable();

			//Vide le cache
			if(!function_exists('clearcache'))
				require 'cachefunc.php';
			clearcache();

			if (!isset($err)) {
				if (!empty($context['frominstall'])) { // si on vient de l'install redirige vers la page d'édition
					header ('location: ../edition/index.php');
					exit;
				} else {
					$context['success'] = 1;
					return 'importmodel';
				}
			}
		}
		#print_r($context);
		if (!empty($context['frominstall'])) {
			$GLOBALS['nodesk'] = true;
			return 'importmodel-frominstall';
		} else {

				return 'importmodel';
		}
	}


	/**
	 * Sauvegarde du modèle éditorial
	 *
	 * Sauve les tables du ME dans un dump SQL (table lodel + table créées). Si demandé inclut
	 * aussi les templates, les css, les images et les scripts javascript. Le fichier créé est
	 * de la forme <em>model-site-date.zip</em>.
	 *
	 * @param array $context le contexte passé par référence
	 * @param array $error les éventuelles erreurs, passées par référence
	 * @return le nom du template utilisé pour cette action : backupmodel
	 */
	public function backupmodelAction(&$context, &$error)
	{
		$context['importdir'] = C::get('importdir', 'cfg');
		if (isset($context['backup'])) {
			if(!$context['title']) {
				$error['title'] = 'title_required';
			}
			if(!$context['description']) {
				$error['description'] = 'description_required';
			}
			if(!$context['author']) {
				$error['author'] = 'author_required';
			}
			if(!$context['modelversion']) {
				$error['modelversion'] = 'modelversion_required';
			}
			if($error) { // Si on detecte des erreurs
				$context['error'] =& $error;
				return 'backupmodel';
			}

			$tmpfile        = tmpdir(). '/model.sql';
			$fh             = fopen($tmpfile, 'w');
			$description    = '<model>
			<lodelversion>'. C::get('version', 'cfg'). '</lodelversion>
			<date>'. date("Y-m-d"). '</date>
			<title>
			'. myhtmlentities(stripslashes($context['title'])). '
			</title>
			<description>
			'. myhtmlentities(stripslashes($context['description'])). '
			</description>
			<author>
			'. myhtmlentities(stripslashes($context['author'])). '
			</author>
			<modelversion>
			'. myhtmlentities(stripslashes($context['modelversion'])). '
			</modelversion>
			</model>
			';

			fputs($fh, '# '. str_replace("\n", "\n# ", $description). "\n#------------\n\n");

			$tables = array('#_TP_classes',
				'#_TP_tablefields',
				'#_TP_tablefieldgroups',
				'#_TP_types',
				'#_TP_persontypes',
				'#_TP_entrytypes',
				'#_TP_entitytypes_entitytypes',
				'#_TP_characterstyles',
				'#_TP_internalstyles'); //liste des tables de lodel à sauver.
			foreach ($tables as $table) {
				fputs($fh, 'DELETE FROM '. $table. ";\n");
			}
			$GLOBALS['currentprefix'] = $currentprefix = '#_TP_';
			$GLOBALS['showcolumns'] = true; // use by PMA to print the fields.
			//fait un DUMP de ces tables
			mysql_dump($GLOBALS['currentdb'], $tables, '', $fh, false, false, true); // get the content

			// select the optiongroups to export
			$vos = DAO::getDAO('optiongroups')->findMany('exportpolicy > 0 AND status > 0', '', 'name, id');
			$ids = array();
			foreach($vos as $vo) {
				$ids[] = $vo->id;
			}
			fputs($fh, "DELETE FROM #_TP_optiongroups;\n");
			mysql_dump($GLOBALS['currentdb'], array('#_TP_optiongroups'), '', $fh, false, false, true, '*', 'id '. sql_in_array($ids));
			fputs($fh, "DELETE FROM #_TP_options;\n");
			mysql_dump($GLOBALS['currentdb'],array('#_TP_options'), '', $fh, false, false, true, 'id, idgroup, name, title, type, defaultvalue, comment, userrights, rank, status, upd, edition, editionparams', 'idgroup '. sql_in_array($ids)); // select everything but not the value

			// Récupère la liste des tables de classe à sauver.
			$vos = DAO::getDAO('classes')->findMany('status > 0', '', 'id,class,classtype');
			$tables = array();
			foreach ($vos as $vo) {
				$tables[] = lq('#_TP_'. $vo->class);
				if ($vo->classtype == 'persons') {
					$tables[] = lq('#_TP_entities_'. $vo->class);
				}
				elseif($vo->classtype == 'entries')
				{
                    $external = DAO::getDAO('relations_ext')->find('nature="ET" AND id1='.$vo->id);
                    if(!$external)
                        $tables[] = lq('#_TP_entities_'. $vo->class);
				}
			}
            if ($tables) {
                mysql_dump($GLOBALS['currentdb'], $tables, '', $fh, true, true, false); // get the table create
            }
            // pour les types d'entrées externes
            $vos = DAO::getDAO('relations_ext')->findMany('nature IN ("ET", "EET")', '', 'idrelation');
            if($vos)
            {
                $ids = array();
                foreach($vos as $vo)
                {
                    $ids[] = $vo->idrelation;
                }
                mysql_dump($GLOBALS['currentdb'],array('#_TP_relations_ext'), '', $fh, false, false, true, 'id1, id2, nature, degree, site ', 'idrelation '. sql_in_array($ids));
            }
			// it may be better to recreate the field at the import rather
			// than using the created field. It may be more robust. Status quo at the moment.
			fclose($fh);

			if (filesize($tmpfile) <= 0) {
				trigger_error('ERROR: mysql_dump failed', E_USER_ERROR);
			}

			$dirs = array();
			$dirstest = array('tpl', 'css', 'images', 'js', 'lodel/icons');
			foreach($dirstest as $dir) {
				if (isset($context[$dir])) {
					$dirs[] = $dir;
				}
			}
			$zipfile = $this->_backupME($tmpfile, $dirs);
			$site = $context['site'];
			$filename  = "model-$site-". date("dmy"). ".zip";
			$operation = 'download';
			if (operation($operation, $zipfile, $filename, $context)) {
				$context['success'] = 1;
				return 'backupmodel';
			}
			@unlink($tmpfile);
			@unlink($zipfile);
			return 'backupmodel';
		}
		return 'backupmodel';
	}

	/**
	 * Dump SQL d'un site donné
	 * @access protected
	 * @param string $site le nom du site
	 * @param string $outfile le fichier dans lequel écrire le dump SQL
	 * @param resource $fh le descripteur de fichier (par défaut 0)
	 * @param array $error tableau des erreurs
	 */
	protected function _dump($site, $outfile, &$error, $fh = 0)
	{
		global $db;
        $closefh = false;

		if ($site && C::get('singledatabase', 'cfg') != 'on') {
			$dbname = DATABASE."_".$site;
			if (!$fh)	{
				$fh = @fopen($outfile, "w");
				$closefh = true;
			}
			if (!$fh)
				trigger_error("ERROR: unable to open file $outfile for writing", E_USER_ERROR);

		}	else	{
			$dbname = DATABASE;
		}

		if (!$db->selectDB($dbname)) {
			$error['database'] = 'error : '.$db->ErrorMsg().'<br />';
			return ;
		}
		$GLOBALS['currentprefix'] = "#_TP_";
		$tables = $GLOBALS['lodelsitetables'];
		$vos = DAO::getDAO('classes')->findMany('status > 0', '', 'class, classtype');
		foreach ($vos as $vo)	{
			$tables[] = lq("#_TP_". $vo->class);
			if (in_array($vo->classtype, array('persons', 'entries') ) )
				$tables[] = lq('#_TP_entities_'. $vo->class);
		}
		// dump structure + données
		mysql_dump($dbname, $tables, $outfile, $fh);
		// dump structure seulement
		$tables_nodatadump = $GLOBALS['lodelsitetables_nodatadump'];
		mysql_dump($dbname, $tables_nodatadump, $outfile, $fh, true, true,false);
		if ($closefh)
			@fclose($fh);
	}

	/**
	 * Execute un dump (fichier SQL) pointé par $url
	 *
	 * @todo vérifier que cette fonction ne prends pas trop de place en mémoire.
	 * @access protected
	 * @param string $url le fichier SQL
	 * @param boolean $ignoreerrors. false par défaut
	 * @return true si le dump a bien été executé	 */
	protected function _execute_dump($url, $ignoreerrors = false)
	{
		global $db;
		$file_content = file($url);
		//print_r($file_content);
		$query = '';
		foreach($file_content as $sql_line) {
			$tsl = trim($sql_line);
			if (($sql_line != "") && (substr($tsl, 0, 2) != "--") && (substr($tsl, 0, 1) != "#")) {
				$query .= $sql_line;
				if(preg_match("/;\s*$/", $sql_line)) {
					$query = preg_replace("/;\s*$/", '', $query);
					//echo "query : ".lq($query)."";
					$result = $db->execute(lq($query));
					//$result = mysql_query(lq($query));
					if (!$result && !$ignoreerrors) trigger_error($query.' - '.$db->ErrorMsg(), E_USER_ERROR);
					$query = '';
				}
			}
		}
		return true;
	}

	/**
	 * Vérifie les fichiers CACHE et .htaccess et recrée les .htaccess.
	 *
	 * @param array $context le contexte passé par référence.
	 */
	protected function _checkFiles(&$context)
	{
		$dirs = array( 'lodel/sources' );
		foreach ($dirs as $dir) {
			if (!file_exists(SITEROOT. $dir)) {
				continue;
			}
			$file = SITEROOT. $dir. '/.htaccess';
			if (file_exists($file)) {
				@unlink($file);
			}
			$f = @fopen ($file, 'w');
			if (!$f) {
				$context['error_htaccess'].= $dir. ' ';
				$err = 1;
			} else {
				fputs($f, "deny from all\n");
				fclose ($f);
			}
		}
	}

	/**
	 * Crée un fichier ZIP du ME contenant le fichier SQL/XML et éventuellement les répertoires
	 * images, css, js et tpl et lodel/icons
	 *
	 * @access private
	 * @param string $sqlfile le fichier dump SQL/XML
	 * @param array $dirs la liste des répertoires à inclure.
	 * @return le nom du fichier ZIP
	 */
	private function _backupME($sqlfile, $dirs = array())
	{
        $acceptedexts = array ('html', 'js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'tiff');
		$tmpdir = tmpdir(uniqid('backupME'));
		$archivetmp = tempnam($tmpdir, 'lodeldump_'). '.zip';

        $file_list = array();

		foreach ($dirs as $dir)	{
			if (!file_exists(SITEROOT . $dir))
				continue;

            $file_list = array_merge(
                $file_list,
                array_filter(glob_recursive( SITEROOT . $dir . DIRECTORY_SEPARATOR . '*'), 'is_file')
            );
		}

        $files_to_zip = array(
            $sqlfile => basename($sqlfile),
        );

        /* Filtrage des extentions de fichier autorisées */
        foreach($acceptedexts as $ext)
        {
            foreach(preg_grep("/$ext$/", $file_list) as $file)
            {
                $files_to_zip[$file] = str_replace(SITEROOT, '', $file);
            }
        }
        unset($file_list);

        create_zip_from_file_list($archivetmp, $files_to_zip);

		return $archivetmp;
	}


	/**
	 * Est-ce que l'on peut importer un ME ?
	 *
	 * Vérifie si le site est vide, pour permettre l'import d'un ME
	 *
	 * @access private
	 * @return un booleen false si impossible, le nom de la table sinon
	 */
	private function _isimportmodelallowed()
	{
		global $db;
		// verifie qu'on peut importer le modele.
		$tablestocheck = array('#_TP_entities', '#_TP_entries', '#_TP_persons');
		foreach($tablestocheck as $table) {
			$haveelements = $db->getOne(lq("SELECT id FROM $table WHERE status>-64"));
			if ($db->errorno()) {
				continue; // on fait comme si la table n'existait pas
			}
			if ($haveelements) {
				return $table;
			}
			$db->execute(lq("DELETE FROM $table WHERE status<=-64")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		}
		return false;
	}

	/**
	 * Extraction du fichier ZIP d'import du ME
	 *
	 *
	 * @param string $footprint le prefix qui doit être contenu dans le nom du fichier
	 * @param array $context le contexte passé par référence
	 * @param string $ext l'extension du fichier, par défaut .zip
	 * @return le nom du fichier d'import
	 */
	private function _extractImport(&$context)
	{
		$archive = empty($_FILES['archive']['tmp_name']) ? null : $_FILES['archive']['tmp_name'];
		if($archive && $_FILES['archive']['error'] !== UPLOAD_ERR_OK )
			$context['error_upload'] = $_FILES['archive']['error'];
		$file = '';
		if (!isset($context['error_upload']) && $archive && $archive != 'none' && is_uploaded_file($archive)) { // Le fichier a été uploadé
			$file = $_FILES['archive']['name'];
			if (!preg_match("/^".$this->fileRegexp."$/", $file)) {
				$context['error_regexp'] = 1;
				return;
				//$file = $this->filePrefix . '-import-'. date("dmy"). '.'. $this->fileExtension;
			}

			if (!move_uploaded_file( $archive, cache_get_path( null ) . DIRECTORY_SEPARATOR . $file )) {
				//trigger_error('ERROR: a problem occurs while moving the uploaded file.', E_USER_ERROR);
				$context['error_upload'] = 1;
				return;
			}else{
				$file = cache_get_path( null ) . DIRECTORY_SEPARATOR . $file;
			}
		} elseif (!empty($context['file'])) {
			if (preg_match("/^(?:". str_replace("/", "\/", join("|", $context['importdirs'])). ")\/". $this->fileRegexp. "$/", $context['file'], $result) &&
				file_exists($context['file']))	{ // fichier sur le disque
				$file = $context['file'];
				$prefix = $result[1];}
		}	else	{ // rien
			$file = '';
		}
		return $file;
	}


	/**
	 * Réinitialisation de la table des objets
	 */
	private function _reinitobjetstable()
	{
		global $db;
		$db->execute(lq('DELETE FROM #_TP_objects')) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

		// ajoute un grand nombre a tous les id.
		$offset = 2000000000;
		$tables = array(
			'classes' => array('id'),
			'types' => array('id'),
			'persontypes' => array('id'),
			'entrytypes' => array('id'),
			'entitytypes_entitytypes' => array('identitytype', 'identitytype2'),
			);
		foreach ($tables as $table => $idsname) {
			foreach ($idsname as $idname) {
				$db->execute(lq('UPDATE #_TP_'. $table. ' SET '. $idname. '='. $idname. '+'. $offset. ' WHERE '.$idname. '>0')) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
		}

		$conv = array('types' => array('entitytypes_entitytypes' => array('identitytype', 'identitytype2'), ),
									'persontypes' => array(), 'entrytypes' => array(), 'classes' => array());

		foreach ($conv as $maintable => $changes) {
			$result = $db->execute(lq("SELECT id FROM #_TP_$maintable")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			while ( ($id=$result->fields['id']) ) {
				$newid=uniqueid($maintable);
				$db->execute(lq('UPDATE #_TP_'.$maintable.' SET id='.$newid.' WHERE id='.$id)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				foreach ($changes as $table => $idsname) {
					if (!is_array($idsname)) {
						$idsname = array($idsname);
					}
					foreach ($idsname as $idname) {
						$db->execute(lq('UPDATE #_TP_'. $table. ' SET '. $idname. '='. $newid. ' WHERE '. $idname. '='. $id)) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
					}
				}
				$result->MoveNext();
			}
		}

		// check all the id have been converted
		$err = "";
		foreach ($tables as $table => $idsname) {
			foreach ($idsname as $idname) {
				$count = $db->getOne(lq("SELECT count(*) FROM #_TP_$table WHERE $idname>$offset"));
				if ($count === false) {
					trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				}
				if ($count) {
					trigger_error("<strong>warning</strong>: reste $count $idname non converti dans $table. si vous pensez que ce sont des restes de bug, vous pouvez les detruire avec la requete SQL suivante: DELETE FROM $GLOBALS[tp]$table WHERE $idname>$offset<br />\n", E_USER_ERROR);
				}
			}
		}
		if ($err) {
			return $err;
		}
		return false;
	}

	/**
	 * Backup du ME sous format XML
	 *
	 * @param array $context le contexte passé par référence
	 * @param array $error les éventuelles erreurs, passées par référence
	 */
	public function backupxmlmodelAction(&$context, &$error)
	{
		if (empty($context['backup'])) {
			return 'backupmodel';
		}
		if(empty($context['title'])) {
			$error['title'] = 'title_required';
		}
		if(empty($context['description'])) {
			$error['description'] = 'description_required';
		}
		if(empty($context['author'])) {
			$error['author'] = 'author_required';
		}
		if(empty($context['modelversion'])) {
			$error['modelversion'] = 'modelversion_required';
		}
		if($error) { // Si on detecte des erreurs
			$context['error'] = $error;
			return 'backupmodel';
		}
		$xml = $this->generateXML($context);

		$tmpfile = tmpdir(). '/model.xml';
		file_put_contents($tmpfile, $xml);
		$dirs = array();
		$dirstest = array('tpl', 'css', 'images', 'js', 'lodel/icons');
		foreach($dirstest as $dir) {
			if (!empty($context[$dir])) {
				$dirs[] = $dir;
			}
		}
		$zipfile = $this->_backupME($tmpfile, $dirs);
		$site = C::get('site', 'cfg');
		$filename  = "modelxml-$site-". date("dmy"). ".zip";
		if (operation('download', $zipfile, $filename, $context)) {
			$context['success'] = 1;
			return 'backupmodel';
		}
		@unlink($tmpfile);
		@unlink($zipfile);
		return 'backupmodel';
	}

	/**
	 * Met à jour le ME en fonction d'un fichier XML
	 *
	 * @param array $context le contexte passé par référence
	 * @param array $error les éventuelles erreurs, passées par référence
	 * @return string $tpl nom du template à afficher
	 */
	public function importxmlmodelAction(&$context, &$error)
	{
		$cache   = getCacheObject();
		$cacheid = getCacheIdFromId('ME.obj');

		if(!C::get('adminlodel', 'lodeluser')) trigger_error("ERROR: you don't have the right to access this feature", E_USER_ERROR);
		global $db;
		$err = '';
		$context['importdir'] = C::get('importdir', 'cfg'); // cherche le rep d'import défini dans la conf
		$GLOBALS['importdirs'] = array (C::get('home', 'cfg'). '../install/plateform');
		if ($context['importdir']) {
			$GLOBALS['importdirs'][] = $context['importdir'];
		}
		$context['importdirs'] = $GLOBALS['importdirs'];
		$context['xmlimport'] = true;
		$this->fileExtension = 'zip';
		$this->filePrefix = 'modelxml';
		$this->fileRegexp = $GLOBALS['fileregexp'] = "({$this->filePrefix})-\w+(?:-\d+)?.{$this->fileExtension}"; //restriction sur le nom du ZIP
		$context['delete'] = isset($context['delete']) ? $context['delete'] : false;
		$file = $this->_extractImport($context);

		if ($file) {
			$xmlfile = tempnam(tmpdir(), 'lodelimportxml_');
			$accepteddirs = array('tpl', 'css', 'images', 'js', 'lodel/icons');
			$acceptedexts = array('html', 'js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'tiff', 'js');
			if (!importFromZip($file, $accepteddirs, $acceptedexts, $xmlfile, true)) {
				$error = $context['error_extract'] = 1;
				return 'importxmlmodel';
			}
			$up = true;
		}

		if((isset($context['checktypes']) || isset($context['checkcontent']) || isset($context['checktables']) || isset($context['checkfields']) || isset($context['checktypesclass']))
			&& ($meObj = $cache->get($cacheid))) { // on a déjà parsé le XML
			$meObj = unserialize($meObj);
			$class = __CLASS__;
			if(!is_object($meObj) || !($meObj instanceof $class)) {
				$context['error'] = $error = 'Content in file "ME.obj" is not an object. Aborted.';
				return 'importxmlmodel';
			}
			$this->_xmlStruct = $meObj->_xmlStruct;
			$this->_xmlDatas = $meObj->_xmlDatas;
			$this->_recordedTables = $meObj->_recordedTables;
			$this->_changedContent = $meObj->_changedContent;
			$this->_classes = $meObj->_classes;
			// besoin de parser la base de nouveau pour prendre en compte les éventuelles modifications
			$this->_getEMTables();
			$this->_parseSQL();
			$this->_fieldsToKeep = $meObj->_fieldsToKeep;
			$this->_changedTables = $meObj->_changedTables;
			$meObj = null;
		} elseif(isset($xmlfile)) {
			// besoin des fonctions de bruno pour conversion entités
			function_exists('HTML2XML') || include 'textfunc.php';
			$this->_changedTables['added'] = $this->_changedTables['dropped'] = array();
			// on récupère les tables du ME
			$this->_getEMTables();
			// parse le XML
			$this->_parseXML($xmlfile, $error);
			@unlink($xmlfile);
			if($error) {
				$context['error'] = $error;
				return 'importxmlmodel';
			}
			// parse la base
			$this->_parseSQL();

			$cache->set($cacheid, serialize($this));
		}

		if(isset($context['checkcontent'])) {
			$this->_manageContent($context['changedcontent']);
		}
		if(isset($context['checktables'])) {
			$this->_manageTables($context, $error);
			if($error) {
				$context['error'] = $error;
				return 'importxml_checktables';
			}
			if(!empty($this->_changedFields)) {
				$cache->set($cacheid, serialize($this));

				unset($this->_changedFields[lq('#_TP_tablefields')]);
				$context['modifiedfields'] = $this->_changedFields;
				return 'importxml_checkfields';
			}
			$context['success'] = 1;
		} elseif(isset($context['checkfields'])) {
			$this->_manageFields($context, $error);
			if($error) {
				$context['error'] = $error;
				$context['modifiedfields'] = $this->_changedFields;
				return 'importxml_checkfields';
			}
			$context['success'] = 1;
		} elseif(isset($context['checktypes'])) {
			$this->_updateTypes($context['data'], $error);
			if(!$error) {
				if(!empty($this->_typesClass))
				{
					$i=0;
					$context['typesclass'] = array();
					$context['typesclass']['old'] = array();
					$context['typesclass']['new'] = array();
					foreach($this->_typesClass as $old=>$new)
					{
						$context['typesclass']['old'][$i] = array('class'=>$old, 'fields'=>$this->_sqlStruct[$old], 'idtype'=>$new['idtype']);

						$context['typesclass']['new'][$i] = array('class'=>$new['class'], 'fields'=>$this->_sqlStruct[$new['class']]);
						unset($context['typesclass']['old'][$i]['fields']['keys'], $context['typesclass']['old'][$i]['fields']['tableOptions'],
						$context['typesclass']['new'][$i]['fields']['keys'], $context['typesclass']['new'][$i]['fields']['tableOptions']);
						$i++;
					}
					return 'importxml_checktypes_class';
				}
				$context['success'] = 1;
			} else {
				$context['error'] = $error;
				return 'importxml_checktypes';
			}
		} elseif(isset($context['checktypesclass'])) {
			$this->_updateTypeClass($context['changedtypeclass'], $error);
			if($error)
			{
				$context['error'] = $error;
				return 'importxml_checktypes_class';
			}
			$context['success'] = 1;
		} elseif(isset($file)) {
			$this->_cleanDatabase();
			if(!isset($context['checkcontent']) && TRUE === $this->_checkContents()) {
				$cache->set($cacheid, serialize($this));

				$context['changedcontent'] = $this->_changedContent;
				return 'importxml_checkcontent';
			} elseif(count($this->_changedTables['dropped'])>0 || count($this->_changedTables['added'])>0) {
				$this->_changedTables['added'] += $this->_classes;
				$this->_changedTables['added'] = array_unique($this->_changedTables['added']);
				$context['modifiedtables'] = $this->_changedTables;
				return 'importxml_checktables';
			} elseif(!empty($this->_changedFields)) {
				unset($this->_changedFields[lq('#_TP_tablefields')]);
				$context['modifiedfields'] = $this->_changedFields;
				return 'importxml_checkfields';
			}
		}

		if(isset($context['success'])) {
			$this->_updateDatabase($context, $error);
			if($error) {
				$context['error'] = $error;
				return 'importxmlmodel';
			}
			if(!isset($context['checktypes']) && !isset($context['checktypesclass'])) {
				$this->_updateTypes(false, $error);
				if($error) {
					$context['error'] = $error;
					return 'importxmlmodel';
				}
				if(!empty($this->_changedTypes)) {

					$cache->set($cacheid, serialize($this));

					$context['modifiedoldtypes'] = $this->_changedTypes;
					$types = lq('#_TP_types');
					$entrytypes = lq('#_TP_entrytypes');
					$persontypes = lq('#_TP_persontypes');
					$this->_updateTypes(true, $error);
					$context['modifiednewtypes'][$types] = $db->getArray("SELECT id, type FROM `{$types}` ORDER BY id");
					$context['modifiednewtypes'][$entrytypes] = $db->getArray("SELECT id, type FROM `{$entrytypes}` ORDER BY id");
					$context['modifiednewtypes'][$persontypes] = $db->getArray("SELECT id, type FROM `{$persontypes}` ORDER BY id");
					return 'importxml_checktypes';
				}
				$this->_updateTypes(true, $error);
				if($error) {
					$context['error'] = $error;
					return 'importxmlmodel';
				}
				// suppression ancien types
				$this->_sql[] = lq("DELETE FROM `#_TP_entitytypes` WHERE identitytype NOT IN (SELECT id FROM `#_TP_types`) AND identitytype != '0';\n");
				$this->_sql[] = lq("DELETE FROM `#_TP_entitytypes` WHERE identitytype2 NOT IN (SELECT id FROM `#_TP_types`) AND identitytype2 != '0';\n");
				$this->_executeSQL();
			}
		}

		// Vide le cache
		if(isset($context['success'])) {
			clearcache();
		}

        if($cache->get($cacheid)) {
            $cache->delete($cacheid);
        }
		return 'importxmlmodel';
	}

	private function _updateTypeClass($datas, $error)
	{
		global $db;
		if(!is_array($datas)) return;
		foreach($datas as $table)
		{
			$idtype = $table['idtype'];
			unset($table['idtype']);
			$equiv = array();
			$keys = array_keys($table);

			$newclass = $keys[1];
			$oldclass = $keys[0];
			unset($keys);

			$new = array_pop($table);
			$old = array_pop($table);
			$old = array_flip($old);
			foreach($new as $key=>$val)
			{
				if(isset($old[$val]))
				{
					$equiv[$key] = $old[$val];
				}
			}

			if(!empty($equiv))
			{
				$fieldsFrom = join(',', array_keys($equiv));
				$fieldsTo = join(',', array_values($equiv));
				$entities = $db->GetArray("SELECT id FROM {$GLOBALS['tp']}entities__oldME where idtype = '{$idtype}'");
				if(!is_array($entities)) continue;
				foreach($entities as $entity)
				{
					$this->_sql[] = "INSERT INTO {$GLOBALS['tp']}{$newclass} (".join(',', array_keys($equiv)).") SELECT ".join(',', array_values($equiv))." FROM {$GLOBALS['tp']}{$oldclass} WHERE identity='{$entity['id']}'";
					$this->_sql[] = "DELETE FROM {$GLOBALS['tp']}{$oldclass} WHERE identity = '{$entity['id']}'";
				}
			}
		}
		$error = $this->_executeSQL();
	}

	/**
	 * Gère la mise à jour du contenu de certaines tables
	 */
	private function _manageContent(&$content) {
		foreach($content as $table=>$fields) {
			if(!isset($fields['oldcontent'])) continue;
			$maxId = 0;
			$ids = array();
			$childTable = ( (FALSE !== strpos($table, 'groups')) ? str_replace('group', '', $table) : false);
			foreach($this->_xmlDatas[$table] as $k=>$tbl) {
				if('fields' === (string)$k) continue;
				if($tbl[0] > $maxId) $maxId = $tbl[0];
				$ids[] = $tbl[0];
			}
			foreach($fields['oldcontent'] as $key=>$value) {
				if(false !== ($kk = array_search($value, $content[$table]['newcontent']))) { // correspondance
					foreach($this->_xmlDatas[$table] as $k=>&$tble) {
						if('fields' === (string)$k || !isset($this->_changedContent['newcontent'][$table])) continue;
						foreach($this->_changedContent['newcontent'][$table] as $ffield) {
							if((int)$tble[0] === (int)$ffield[0]) {
								if(in_array($ffield[0], $ids)) { // id déjà présent
									$oldId = $ffield[0];
									do { $maxId++; } while(in_array($maxId, $ids));
									$ffield[0] = $ids[] = $maxId;
									if(!$childTable) continue;
									foreach($this->_xmlDatas[$childTable] as $k=>&$field) {
										if('fields' === (string)$k) {
											foreach($field as $kk=>$fieldName) {
												if('idgroup' === (string)$fieldName) {
													$keyGroup = $kk;
													break;
												}
											}
										} elseif((int)$oldId === (int)$field[$keyGroup]) {
											$field[$keyGroup] = $ffield[0];
										}
									}
								}
								$tble = $ffield;
								break 2;
							}
						}
					}
				} else {
					if(in_array($this->_changedContent['oldcontent'][$table][$key][0], $ids)) { // id déjà présent
						$oldId = $this->_changedContent['oldcontent'][$table][$key][0];
						do { $maxId++; } while(in_array($maxId, $ids));
						$this->_changedContent['oldcontent'][$table][$key][0] = $ids[] = $maxId;
						if($childTable) {
							foreach($this->_xmlDatas[$childTable] as $k=>&$field) {
								if('fields' === (string)$k) {
									foreach($field as $kk=>$fieldName) {
										if('idgroup' === (string)$fieldName) {
											$keyGroup = $kk;
										}
										if('class' === (string)$fieldName) {
											$keyClass = $kk;
										}
									}
									continue;
								}
								if((int)$oldId === (int)$field[$keyGroup] && isset($this->_sqlStruct[$field[$keyClass]])) {
									$field[$keyGroup] = $this->_changedContent['oldcontent'][$table][$key][0];
								}
							}
						}
					}
					$this->_xmlDatas[$table][] = $this->_changedContent['oldcontent'][$table][$key];
				}
			}
		}
		// maj des groups de champs pour comparaison plus tard
		if(!empty($this->_changedFields)) {
			$tablefieldgroups = lq('#_TP_tablefieldgroups');
			foreach($this->_changedFields as $table=>&$value) {
				if(!isset($value['dropped']) || !is_array($value['dropped'])) continue;
				foreach($value['dropped'] as $k=>&$v) {
					if(!isset($v['tablefieldgroups'])) continue;
					$v['tablefieldgroups'] = $this->_xmlDatas[$tablefieldgroups];
				}
			}
		}

		$cache = getCacheObject();
		$cache->set(getCacheIdFromId('ME.obj'), serialize($this));
	}

	/**
	 * On va comparer le contenu des tables qui nous intéresse
	 */
	private function _checkContents() {
		global $db;
		foreach(array($GLOBALS['tp'].'tablefieldgroups', $GLOBALS['tp'].'options', $GLOBALS['tp'].'optiongroups', $GLOBALS['tp'].'internalstyles', $GLOBALS['tp'].'characterstyles') as $table) {
			$tmpXmlDatas = isset($this->_xmlDatas[$table]) ? $this->_xmlDatas[$table] : false;
			if(!$tmpXmlDatas) continue;
			$tmpSqlDatas = array();
			unset($tmpXmlDatas['fields']);
			$result = $db->execute("SELECT * FROM `{$table}`");
			if($result) {
				$i=0;
				while (!$result->EOF) {
					foreach($result->fields as $value) {
						$tmpSqlDatas[$i][] = $value;
					}
					$i++;
					$result->MoveNext();
				}
			} else continue;

			$diffs = array_diff_all($tmpXmlDatas, $tmpSqlDatas);
			if(!isset($diffs['dropped'])) continue;

			foreach($diffs['dropped'] as $k=>$diff) {
				$oldContent[$table][] = $tmpSqlDatas[$k];
			}
			$newContent[$table] = $tmpXmlDatas;
			$newContent[$table]['fields'] = $oldContent[$table]['fields'] = $this->_xmlDatas[$table]['fields'];
		}
		$this->_changedContent = array('newcontent'=>(isset($newContent) ? $newContent : ''), 'oldcontent'=>(isset($oldContent) ? $oldContent : array()));
		return (is_array($this->_changedContent['newcontent']) ? TRUE : FALSE);
	}

	/**
	 * On supprime les éventuelles anciennes tables de ME déjà mis à jour précédemment
	 * et ce afin d'éviter les conflits entre les données
	 */
	private function _cleanDatabase() {
		global $db;
		if(empty($this->_existingTables) || !is_array($this->_existingTables)) return;
		foreach($this->_existingTables as $table=>$k) {
			if(FALSE !== strpos($table, '__oldME')) {
				$db->execute("DROP TABLE `{$table}`");
				unset($this->_existingTables[$table]);
			}
		}
	}

	/**
	 * Récupération des tables du ME
	 *
	 * Cette fonction stock dans $this->_tables les noms des tables du ME (statiques ou dynamiques)
	 * et dans $this->_existingTables les noms de toutes les tables de la base
	 */
	private function _getEMTables() {
		global $db;
		// tables ME statiques
		$this->_tables = array($GLOBALS['tableprefix'].'classes'=>true, $GLOBALS['tableprefix'].'tablefields'=>true, $GLOBALS['tableprefix'].'tablefieldgroups'=>true, $GLOBALS['tableprefix'].'types'=>true, $GLOBALS['tableprefix'].'persontypes'=>true, $GLOBALS['tableprefix'].'entrytypes'=>true, $GLOBALS['tableprefix'].'entitytypes_entitytypes'=>true, $GLOBALS['tableprefix'].'internalstyles'=>true, $GLOBALS['tableprefix'].'characterstyles'=>true, $GLOBALS['tableprefix'].'optiongroups'=>true, $GLOBALS['tableprefix'].'options'=>true);
		// tables ME dynamiques
		$vos = DAO::getDAO('classes')->findMany('status > 0', '', 'class,classtype');
		foreach($vos as $vo) {
			$this->_tables[$GLOBALS['tableprefix'].$vo->class] = false;
			if ($vo->classtype == 'persons' || $vo->classtype == 'entries') {
				$this->_tables[$GLOBALS['tableprefix'].'entities_'. $vo->class] = false;
			}
		}
        // pour les types d'entrées externes
        $vos = DAO::getDAO('relations_ext')->findMany('nature="ET" OR nature="EET"', '', 'idrelation');
        if($vos)
        {
            $this->_tables[$GLOBALS['tableprefix'].'relations_ext'] = true;
        }
		// toutes les tables
		$tables = $db->getArray('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = "'.$GLOBALS['currentdb'].'"');
		foreach($tables as $k=>$tableName) {
			$this->_existingTables[$tableName['TABLE_NAME']] = $k;
		}
	}

	/**
	 * Parse le fichier XML à l'import du ME
	 *
	 * Stock la structure dans $this->_xmlStruct et les éventuelles données dans $this->_xmlDatas
	 *
	 * @param string $file lien vers le fichier XML
	 * @param string $error erreur passée en référence
	 */
	private function _parseXML($file, &$error) {
		global $db;
		// besoin de la dtd dans le meme répertoire pour valider
		$dtd = @copy(SITEROOT . '../share/lodelEM.dtd', tmpdir().'/lodelEM.dtd');
		if(false === $dtd) {
			$error = 'Unable to copy DTD into tmpdir. Aborted.';
			return;
		}
		// on récupère le ME
		$reader = new XMLReader();
		$validator = @$reader->open($file);
		if(FALSE === $validator) {
			$error = 'Unable to read XML file.';
			return;
		}
		$reader->setParserProperty(XMLReader::VALIDATE, TRUE);
		// on lit le doc jusqu'à la fin et on valide
		while (@$reader->read());
		if(!$reader->isValid()) {
			$error = 'XML document is not valid.';
			return;
		}
		$reader->close();
		$validator = $reader->open($file);
		if(FALSE === $validator) {
			$error = 'Unable to read XML file after validation.';
			return;
		}
		// on récupère les noms des tables sur la base
		if(!($tablefields = cache_get('tablefields')))
		{
			include 'tablefields.php';
		}

		// on récupère la structure de la base XML ainsi que les données
		$this->_xmlStruct = $this->_xmlDatas = $this->_recordedTables = array();
		while ($reader->read()) {
			if(XMLReader::ELEMENT == $reader->nodeType) {
				switch($reader->localName) {
					case 'table':
					// nom de la table en cours de traitement
					$table = lq($reader->getAttribute('name'));
					if(isset($this->_recordedTables[$table])) {
						$error = "duplicated table `{$table}`";
						return;
					}

					$dataFields = array();
					$this->_recordedTables[] = $table;
					// nouvelle table
					if(!isset($tablefields[$table]))
						$this->_changedTables['added'][] = $table;
					break;

					case 'structure':
					// début structure de la table
					$j=0;
					break;

					case 'field':
					// champs de la structure d'une table
					$localName = $reader->getAttribute('name');
					$key = $reader->getAttribute('key');
					if(in_array($localName, $dataFields)) {
						$error = "Duplicated field '{$localName}' in table `{$table}`.";
						return;
					}
					$dataFields[] = $localName;
					$reader->read();
					if(0 == strlen($reader->value)) {
						$error = "Missing definition type of field '{$localName}' in table `{$table}`";
						return;
					}
					if($key) {
						$this->_xmlStruct[$table]['keys'][$localName] = $reader->value;
						break;
					}
					if('tableOptions' === $localName) {
						$this->_xmlStruct[$table]['tableOptions'] = $reader->value;
						break;
					}
					$this->_xmlStruct[$table][] = array($localName=>$reader->value);
					break;

					case 'datas':
					// début contenu de la table
					$this->_xmlDatas[$table] = array();
					$this->_xmlDatas[$table]['fields'] = array();
					$i=-1;
					break;

					case 'row':
					$i++;
					$this->_xmlDatas[$table][$i] = array();
					break;

					case 'col':
					// champs du contenu de la table
					$localName = $reader->getAttribute('name');
					if(0 === $i) $this->_xmlDatas[$table]['fields'][] = $localName;
					$reader->read();
					// balise vide
					// ou champ 'value' de la table 'options'
					if('options' == $table && 'value' == $localName) {
						$value = $db->getOne(lq("SELECT value FROM #_TP_options WHERE name = '{$this->_xmlDatas[$table][$i][2]}'"));
						$this->_xmlDatas[$table][$i][] = $value ? $value : '';
						break;
					} elseif(XMLReader::END_ELEMENT == $reader->nodeType) {
						// http://fr2.php.net/manual/fr/class.xmlreader.php#xmlreader.props.hasvalue ?
						/*
							$reader->moveToElement();
							echo $reader->readInnerXML().'<br>';
						*/
						$this->_xmlDatas[$table][$i][] = '';
						break;
					}
					if('classes' == $table && 'class' == $localName) $this->_classes[] = $reader->value;
					$this->_xmlDatas[$table][$i][] = HTML2XML(strtr($reader->value, array('&amp;'=>'&', '&lt;'=>'<', '&gt;'=>'>')), true) ;
					break;
					default: break;
				}
			}
		}
		$reader->close();
		@unlink(tmpdir().'/lodelEM.dtd');
	}

	/**
	 * Récupère la structure des tables du ME
	 *
	 * Stock la structure dans $this->_sqlStruct
	 * @param array $tables on peut spécifier quelles tables en particulier parser
	 */
	private function _parseSQL() {
		global $db;
		// on analyse chaque tables du ME de la base SQL
		foreach($this->_tables as $table=>$content) {
			// on récupère la structure SQL de la base actuelle
			$result = $db->getRow("SHOW CREATE TABLE `{$table}`");
			preg_match("/^CREATE TABLE `{$table}`\s+\(\s*(.*)\s*\)\s*(.*)$/s", $result['Create Table'], $matches);
			$fields = explode("\n", $matches[1]);
			$i=0;
			foreach($fields as $kk=>$val) {
				if(!($field = trim($val)))
					continue;
				if(FALSE !== strpos($field, ',', strlen($field)-1))
					$field = substr($field, 0, strlen($field)-1);
				if(preg_match("/^`([^`]+)`\s+(.*)$/", $field, $m)) { // champ
					$this->_sqlStruct[$table][] = array($m[1]=>$m[2]);
				} else { // clé
					$field = explode('KEY', $field);
					$field[0] = trim($field[0]);
					$field[1] = trim($field[1]);
					if($field[0]) {
						$this->_sqlStruct[$table]['keys'][$field[0].'_'.$kk] = $field[1];
					} else {
						$this->_sqlStruct[$table]['keys']['KEY_'.$kk] = $field[1];
					}
				}
				$i++;
			}
			// charset, engine, auto_inc..
			$this->_sqlStruct[$table]['tableOptions'] = $matches[2];
			if(!$this->_tables[$table] && 0 !== strpos($table, 'entities_') && (!in_array($table, $this->_recordedTables) || (isset($this->_classes) && !in_array($table, $this->_classes)))) {
				// table du ME qu'on a changé de nom ou supprimée
				$this->_changedTables['dropped'][] = $table;
				continue;
			}
			// on compare
			$this->_getModifiedStruct($table);
		}
		// maj des groups de champs pour comparaison plus tard
		if(!empty($this->_changedFields)) {
			$tablefieldgroups = lq('#_TP_tablefieldgroups');
			foreach($this->_changedFields as $table=>&$value) {
				if(!isset($value['dropped']) || !is_array($value['dropped'])) continue;
				foreach($value['dropped'] as $k=>&$v) {
					if(isset($v['tablefieldgroups'])) $v['tablefieldgroups'] = $this->_xmlDatas[$tablefieldgroups];
				}
			}
		}
	}

	/**
	 * Compare la structure XML et SQL du ME
	 *
	 * Stock la comparaison dans $this->_changedFields pour chaque table
	 *
	 * @param string $table la table courante
	 */
	private function _getModifiedStruct($table) {
		global $db;
		$diff = array();
		if($diff = array_diff_all($this->_xmlStruct[$table], $this->_sqlStruct[$table]))
			$this->_changedFields[$table] = $diff;
		else return;
		unset($this->_changedFields[$table]['added']['keys'], $this->_changedFields[$table]['added']['tableOptions'], $this->_changedFields[$table]['dropped']['tableOptions'], $this->_changedFields[$table]['dropped']['keys']);
		if(empty($this->_changedFields[$table]['dropped']) && empty($this->_changedFields[$table]['added'])) {
			unset($this->_changedFields[$table]);
			return;
		}
		$escapeAdd = $escapeDrop = true;
		if(!empty($this->_changedFields[$table]['added']) && is_array($this->_changedFields[$table]['added'])) {
			$fields = array();
			$fields = $this->_changedFields[$table]['added'];
			unset($this->_changedFields[$table]['added']);
			foreach($fields as $k=>$field) {
				$this->_changedFields[$table]['added'][] = $fields[$k];
			}
			$escapeAdd = false;
		}
		if(!empty($this->_changedFields[$table]['dropped']) && is_array($this->_changedFields[$table]['dropped'])) {
			$fields = array();
			$fields = $this->_changedFields[$table]['dropped'];
			unset($this->_changedFields[$table]['dropped']);
			$tablefield = lq('#_TP_tablefields');
			$tablefieldgroups = lq('#_TP_tablefieldgroups');
			foreach($fields as $k=>$field) {
				$oldField = $idgroup = array();
				$arrKeys = array_keys($field);
				if(!empty($this->_changedFields[$table]['added']) && is_array($this->_changedFields[$table]['added']))
					$oldField = multidimArrayLocate($this->_changedFields[$table]['added'], $arrKeys[0]);
				if(!$oldField) {
					$row = $db->getRow("SELECT * FROM `{$tablefield}` where name='{$arrKeys[0]}' AND class='{$table}'");
					if(!$row) continue;
					unset($row['id']);
					$idgroup = $db->getRow("SELECT name FROM `{$tablefieldgroups}` where id='{$row['idgroup']}'");
					$this->_fieldsToKeep[$table][$arrKeys[0]] = $row;
				}
				$this->_changedFields[$table]['dropped'][] = empty($idgroup) ? $fields[$k] : array('value'=>$fields[$k], 'tablefieldgroups'=>$this->_xmlDatas[$tablefieldgroups]);
			}
			$escapeDrop = false;
		}
		if($escapeAdd || $escapeDrop) return;
		foreach($this->_changedFields[$table]['added'] as $k=>$field) {
			if(!is_array($field)) {
				unset($this->_changedFields[$table]['added'][$k]);
				continue;
			}
			if(!is_array($this->_changedFields[$table]['dropped'])) continue;
			foreach($field as $name=>$type) {
				$arr = $arrAdd = array();
				if($arr = multidimArrayLocate($this->_changedFields[$table]['dropped'], $name)) {
					$arrAdd = multidimArrayLocate($this->_changedFields[$table]['added'], $name);
					if(!$arrAdd) continue;
					$key = array_keys($arr);
					$ki = array_keys($arrAdd);
					if($ki[0] !== $key[0]) {
						if(isset($this->_changedFields[$table]['added'][$key[0]]))
							$this->_changedFields[$table]['added'][] = $this->_changedFields[$table]['added'][$key[0]];
						$this->_changedFields[$table]['added'][$key[0]] = array($name=>$type);
						unset($this->_changedFields[$table]['added'][$ki[0]]);
					}
				}
			}
		}
	}

	/**
	 * Execute une série de requêtes SQL
	 *
	 * @param bool $differed doit-on effectuer les requêtes différées ($this->_sql['differed']) ou les requêtes en cours ($this->_sql) ?
	 * @return false si aucune erreur ou la requête provoquant l'erreur et l'erreur retournée par le SGBD
	 */
	private function _executeSQL($differed=false) {
		global $db;
		if($differed) {
			if(empty($this->_sql['differed']))
				return false;
			if(is_array($this->_sql['differed'])) {
				foreach($this->_sql['differed'] as $sql) {
					if(!$db->execute($sql)) return "SQL Error with query '{$sql}' : {$db->ErrorMsg()}";
				}
			} else {
				if(!$db->execute($this->_sql['differed'])) return "SQL Error with query '{$this->_sql['differed']}' : {$db->ErrorMsg()}";;
			}
			return false;
		}
		if(empty($this->_sql)) return false;
		if(is_array($this->_sql)) {
			foreach($this->_sql as $k=>$sql) {
				if('differed' === $k) continue;
				if(!$db->execute($sql)) return "SQL Error with query '{$sql}' : {$db->ErrorMsg()}";
			}
		} else {
			if(!$db->execute($this->_sql)) return "SQL Error with query '{$this->_sql}' : {$db->ErrorMsg()}";
		}
		$this->_sql = isset($this->_sql['differed']) ? array('differed'=>$this->_sql['differed']) : array();
		return false;
	}

	/**
	 * Met à jour la base de données SQL
	 *
	 * Modifie ou crée les tables et insère les éventuelles données
	 *
	 * @param array $context le contexte passé par référence
	 * @param array $error les éventuelles erreurs, passées par référence
	 */
	private function _manageTables(&$context, &$error) {
		global $db;
		$classes = lq('#_TP_classes');
		if(!empty($context['data']['dropped']) && is_array($context['data']['dropped']) && empty($context['data']['added']) || !is_array($context['data']['added']))
		{
			$flipped = !empty($context['data']['added']) && is_array($context['data']['added']) ? array_flip($context['data']['added']) : array();
			foreach($context['data']['dropped'] as $table=>$equivalent)
			{
				if(isset($flipped[$table])) continue;
				$class = $db->getRow("SELECT * FROM {$classes} WHERE class='{$table}'");
				if(!$class) continue; // not a class
				$this->_xmlDatas['classes'][] = $class;
				$this->_classes[] = $class['class'];
			}
			$cache = getCacheObject();
			$cache->set(getCacheIdFromId('ME.obj'), serialize($this));
		}
		if(empty($context['data']['added']) || !is_array($context['data']['added'])) return;
		$tablefield = lq('#_TP_tablefields');
		$flipped = !empty($context['data']['dropped']) && is_array($context['data']['dropped']) ? array_flip($context['data']['dropped']) : array();
		foreach($context['data']['added'] as $table=>$equivalent) {
			if(isset($flipped[$equivalent])) {
				$classType = $db->getOne("SELECT classtype FROM `{$classes}` WHERE class = '{$flipped[$equivalent]}'")
					or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				switch($classType) {
					case 'entities': $typeTable = lq('#_TP_types'); break;
					case 'entries': $typeTable = lq('#_TP_entrytypes'); break;
					case 'persons': $typeTable = lq('#_TP_persontypes'); break;
				}
				$this->_sql[] = "RENAME TABLE `{$flipped[$equivalent]}` TO `{$table}`";
				$this->_tables[$table] = $this->_tables[$flipped[$equivalent]];
				unset($this->_tables[$flipped[$equivalent]]);
				$this->_sql[] = "UPDATE `{$tablefield}` SET class = '{$table}' WHERE class = '{$flipped[$equivalent]}'";
				$this->_sql[] = "UPDATE `{$typeTable}` SET class = '{$table}' WHERE class = '{$flipped[$equivalent]}'";
				$this->_sql[] = "DELETE FROM `{$classes}` WHERE class = '{$flipped[$equivalent]}'";
				if(isset($this->_fieldsToKeep[$flipped[$equivalent]])) {
					$this->_fieldsToKeep[$table] = $this->_fieldsToKeep[$flipped[$equivalent]];
					unset($this->_fieldsToKeep[$flipped[$equivalent]]);
					foreach($this->_fieldsToKeep[$table] as $name=>&$type) {
						$type['class'] = $table;
					}
				}
				$error = $this->_executeSQL();
				if($error) return false;
				unset($this->_sqlStruct[$table]);
				// on reparse la structure de la table
				$result = $db->getRow( "SHOW CREATE TABLE `{$table}`" );
				$row = $result['Create Table'];
				preg_match("/^CREATE TABLE `{$table}`\s+\(\s*(.*)\s*\)\s*(.*)$/s", $row, $matches);
				$fields = explode("\n", $matches[1]);
				$i=0;
				foreach($fields as $kk=>$val) {
					if(!($field = trim($val)))
						continue;
					if(FALSE !== strpos($field, ',', strlen($field)-1))
						$field = substr($field, 0, strlen($field)-1);
					if(preg_match("/^`([^`]+)`\s+(.*)$/", $field, $m)) { // champ
						$this->_sqlStruct[$table][] = array($m[1]=>$m[2]);
					} else { // clé
						$field = explode('KEY', $field);
						$field[0] = trim($field[0]);
						$field[1] = trim($field[1]);
						if($field[0]) {
							$this->_sqlStruct[$table]['keys'][$field[0].'_'.$kk] = $field[1];
						} else {
							$this->_sqlStruct[$table]['keys']['KEY_'.$kk] = $field[1];
						}
					}
					$i++;
				}
				// charset, engine, auto_inc..
				$this->_sqlStruct[$table]['tableOptions'] = $matches[2];
				// on compare
				$this->_getModifiedStruct($table);
				continue;
			}
			// nouvelle table
			if(!isset($this->_xmlStruct[$table])) {
				$error = "Structure missing for new table `{$table}`";
				return;
			}
			$tmpSql = "CREATE TABLE IF NOT EXISTS `{$table}` ( ";
			$keys = array();
			foreach($this->_xmlStruct[$table] as $k=>$v) {
				if('tableOptions' === $k) {
					$tableOptions = $v;
					continue;
				}
				if('keys' === $k) {
					foreach($v as $kk=>$vv) {
						$key = explode('_', $kk);
						$key = $key[0];
						if('KEY' != $key)
							$key .= ' KEY';
						preg_match("/^`([^`]+)`/", $vv, $m);
						if($m[1] && isset($keys[$m[1]])) {
							$error = "Duplicate key '{$m[1]}' in table `{$table}`";
							return;
						}
						$keys[$m[1]] = true;
						$tmpSql .= ' '.$key.' '.$vv.",";
					}
					continue;
				}
				$fieldName = array_keys($v);
				$fieldDefinition = array_values($v);
				$tmpSql .= ' `'.$fieldName[0].'` '.$fieldDefinition[0].', ';
			}
			$tmpSql = substr_replace($tmpSql, '', strlen($tmpSql)-1);
			$tmpSql .= " ) ".$tableOptions;
			$this->_sql[] = $tmpSql;
			if(isset($this->_xmlDatas[$table])) {
				$nbFields = count($this->_xmlDatas[$table]['fields']) - 1;
				$tmpSql = "INSERT INTO `{$table}` (".join(',', $this->_xmlDatas[$table]['fields']).") VALUES ";
				foreach($this->_xmlDatas[$table] as $i=>$fields) {
					if('fields' === $i) continue;
					$tmpSql .= ($i === 0) ? "\n(" : ",\n(";
					foreach($fields as $j=>$val) {
						$val = addcslashes($val, '"');
						$tmpSql .= ($j < $nbFields) ? "\"{$val}\"," : "\"{$val}\"";
					}
					$tmpSql .= ")";
				}
				$this->_sql[] = $tmpSql;
			}
		}
		$error = $this->_executeSQL();
	}

	/**
	 * Met à jour la base de données SQL
	 *
	 * Termine la mise à jour de la base (ids des objects, clés, options des tables et éventuelles données)
	 *
	 * @param array $context le contexte passé par référence
	 * @param array $error les éventuelles erreurs, passées par référence
	 */
	private function _updateDatabase(&$context, &$error) {
		global $db;
		$db->execute(lq("CREATE TABLE IF NOT EXISTS `#_TP_entities__oldME` SELECT * FROM `#_TP_entities`")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		$entitytypeTable = lq('#_TP_entitytypes_entitytypes');
		$entitiesTable = lq('#_TP_entities');
		$typesTable = lq('#_TP_types');
		foreach($this->_xmlStruct as $table=>$content) {
			// données à insérer
			if(isset($this->_xmlDatas[$table][0])) {
				// copie au cas où
				if(!isset($this->_existingTables[$table.'__oldME']))
					$this->_sql[] = "CREATE TABLE `{$table}__oldME` SELECT * FROM `{$table}`;\n";
				$this->_sql[] = "DELETE FROM `{$table}`;\n";
				$nbFields = count($this->_xmlDatas[$table]['fields']) - 1;
				$tmpSql = "INSERT INTO `{$table}` (".join(',', $this->_xmlDatas[$table]['fields']).") VALUES ";
				foreach($this->_xmlDatas[$table] as $i=>$fields) {
					if('fields' === $i || !is_array($fields)) continue;
					$f = array();
					$tmpSql .= ($i === 0) ? "\n(" : ",\n(";
					foreach($fields as $j=>&$val) {
						$val = str_replace('"', '\"', $val);
						$f[] = '"'.$val.'"';
					}
					$tmpSql .= join(',',$f).")";
				}
				$this->_sql[] = $tmpSql.";\n";
				$f = $this->_xmlDatas[$table]['fields'];
				unset($this->_xmlDatas[$table]);
				$this->_xmlDatas[$table]['fields'] = $f;
				unset($f);
			}
			// clés
			if((isset($this->_xmlStruct[$table]['keys']) && isset($this->_sqlStruct[$table]['keys']) && is_array($this->_xmlStruct[$table]['keys']) && is_array($this->_sqlStruct[$table]['keys']))
			&& (array_values($this->_xmlStruct[$table]['keys']) != array_values($this->_sqlStruct[$table]['keys']))) {
				// on efface toutes les clés présentes dans la table
				if(is_array($this->_sqlStruct[$table]['keys'])) {
					foreach($this->_sqlStruct[$table]['keys'] as $k=>$v) {
						$key = explode('_', $k);
						$key = $key[0];
						if('PRIMARY' == $key) {
							$drop = 'PRIMARY KEY';
						} else {
							preg_match("/^`([^`]+)`/", $v, $m);
							$drop = 'KEY `'.$m[1].'`';
						}
						$this->_sql[] = "ALTER TABLE `{$table}` DROP {$drop};\n";
					}
				}
				// on ajoute les clés
				foreach($this->_xmlStruct[$table]['keys'] as $k=>$v) {
					$key = explode('_', $k);
					$key = $key[0];
					if('KEY' != $key)
						$key .= ' KEY';
					$this->_sql[] = "ALTER TABLE `{$table}` ADD {$key} {$v};\n";
				}
			}
			unset($this->_xmlStruct[$table]['keys']);
			// table options
			if(isset($this->_xmlStruct[$table]['tableOptions']) && isset($this->_sqlStruct[$table]['tableOptions']) && $this->_xmlStruct[$table]['tableOptions'] != $this->_sqlStruct[$table]['tableOptions']) {
				$this->_sql[] = "ALTER TABLE `{$table}` {$this->_xmlStruct[$table]['tableOptions']};\n";
			}
		}
		// on execute les requetes en cours
		$error = $this->_executeSQL();
		if($error) return false;
		// requetes différées
		$error = $this->_executeSQL(true);
		if($error) return false;
	}

	/**
	 * Met à jour la base de données SQL
	 *
	 * Ajuste les idtypes après import XML du ME
	 * @param $datas tableau contenant les données à traiter ou bool
	 * @param $error erreur passée en référence
	 */
	private function _updateTypes($datas, &$error='') {
		global $db;
		$entriesTable = lq('#_TP_entries');
		$personsTable = lq('#_TP_persons');
		$entitiesTable = lq('#_TP_entities');
		$typesTable = lq('#_TP_types');
		$entrytypesTable = lq('#_TP_entrytypes');
		$persontypesTable = lq('#_TP_persontypes');
		$entitytypeTable = lq('#_TP_entitytypes_entitytypes');
		$tablefieldsTable = lq('#_TP_tablefields');
		$objectsTable = lq('#_TP_objects');
		$classesTable = lq('#_TP_classes');
		if(is_array($datas)) {
			foreach($datas as $table=>$content) {
				$typesFields = join(',', $this->_xmlDatas[$table]['fields']);
				$before = array_keys($content);
				$after = array_values($content);
				if($before === $after) continue;
				switch($table) {
					case $typesTable: $parentTable = $entitiesTable; break;
					case $entrytypesTable: $parentTable = $entriesTable; break;
					case $persontypesTable: $parentTable = $personsTable; break;
				}
				$objectType = ($parentTable == $entitiesTable) ? $typesTable : $parentTable;
				$oldMETable = ($parentTable == $entitiesTable) ? $entitiesTable.'__oldME' : $parentTable;
				foreach($before as $k=>$val) {
					$idtype=0;
					$ids = array();
					if((int)$val === (int)$after[$k]) continue;
					if(empty($after[$k])) { // ancien type sans correspondances avec nouveau ME il faut le recréer
						$id = uniqueid($objectType);
						if($parentTable == $entitiesTable) {
							$toUpdate[] = $val;
							$toUpNewId[] = $id;
						}
						$field = $db->GetRow("SELECT * FROM `{$table}__oldME` WHERE id = '{$val}'") or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
						unset($field['id']);
						array_walk($field, function(&$f) { $f = addcslashes($f, "\'");});
						$db->execute("INSERT INTO `{$table}` ({$typesFields}) VALUES ('{$id}','".join("','", $field)."')") or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
						$fieldName = $db->getOne("SELECT type FROM `{$table}__oldME` WHERE id = '{$val}'") or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
						$db->execute("INSERT INTO `$tablefieldsTable`(name, idgroup, class, title, altertitle, style, type, g_name, cond, defaultvalue, processing, allowedtags, gui_user_complexity, filtering, edition, editionparams, weight, comment, status, rank, upd, mask) (SELECT name, idgroup, class, title, altertitle, style, type, g_name, cond, defaultvalue, processing, allowedtags, gui_user_complexity, filtering, edition, editionparams, weight, comment, status, rank, upd, mask FROM `{$tablefieldsTable}__oldME` WHERE name = '{$fieldName}');\n") or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
					}
					elseif('types' === (string)$table)
					{
						$originalClass = $db->GetOne("SELECT class FROM `{$table}__oldME` WHERE id = '{$val}'");
						$newClass = $db->GetOne("SELECT class FROM `{$table}` WHERE id = '{$after[$k]}'");
						if($originalClass != $newClass)
						{ // we need to know what field from original class have to go to new class
							$this->_typesClass[$originalClass] = array('class'=>$newClass, 'idtype'=>$val);
						}
					}
					$idtype = isset($id) ? $id : $after[$k];
					$result = $db->execute("SELECT id FROM `{$oldMETable}` WHERE idtype = '{$val}'");
					if($result) {
						while(!$result->EOF) {
							$ids[] = $result->fields['id'];
							$result->MoveNext();
						}
						if(!empty($ids))
							$this->_sql[] = "UPDATE `{$parentTable}` SET idtype = '{$idtype}' WHERE id IN (".join(',', $ids).");\n";
					}
					unset($id, $fieldName);
				}
			}
			if(isset($toUpdate)) {// maj entitytypes_entitytypes
				$error = $this->_executeSQL();
				if($error) return false;
				$id=0;
				foreach($toUpdate as $k=>$id) {
					$etypes = $db->getArray("SELECT identitytype2 FROM `{$entitytypeTable}__oldME` WHERE identitytype = '{$id}'");
					if(is_array($etypes)) {
						foreach($etypes as $etype) {
							$id2=0;
							if(isset($datas[$typesTable][$etype['identitytype2']]) && '' != $datas[$typesTable][$etype['identitytype2']]) {
								$id2 = $datas[$typesTable][$etype['identitytype2']];
							} else {
								$id2 = $db->getOne("SELECT t.id FROM `{$typesTable}` as t JOIN `{$typesTable}__oldME` as tt ON (t.type=tt.type) WHERE tt.id = '{$etype['identitytype2']}'");
							}
							$this->_sql[] = "INSERT INTO `{$entitytypeTable}`(identitytype, identitytype2, cond) VALUES ('{$toUpNewId[$k]}', '{$id2}', '*');\n";
						}
					}
					$etypes = $db->getArray("SELECT identitytype FROM `{$entitytypeTable}__oldME` WHERE identitytype2 = '{$id}'");
					if(!$etypes) continue;
					foreach($etypes as $etype) {
						$id2=0;
						if(isset($datas[$typesTable][$etype['identitytype']]) && '' != $datas[$typesTable][$etype['identitytype']]) {
							$id2 = $datas[$typesTable][$etype['identitytype']];
						} else {
							$id2 = $db->getOne("SELECT t.id FROM `{$typesTable}` as t JOIN `{$typesTable}__oldME` as tt ON (t.type=tt.type) WHERE tt.id = '{$etype['identitytype']}'");
						}
						$this->_sql[] = "INSERT INTO `{$entitytypeTable}`(identitytype, identitytype2, cond) VALUES ('{$id2}', '{$toUpNewId[$k]}', '*');\n";
					}
				}
			}
			unset($toUpdate);
			unset($toUpNewId);
			$error = $this->_executeSQL();
			if($error) return false;
			foreach(array(0=>$typesTable, 1=>$entrytypesTable, 2=>$persontypesTable) as $key=>$typeName) {
				switch($key) {
					case 0: $table = $entitiesTable; break;
					case 1: $table = $entriesTable; break;
					case 2: $table = $personsTable; break;
				}
				$typeArr = $db->getArray("SELECT id, type FROM `{$typeName}` ORDER BY id");
				$objectTableName = ($table == $entitiesTable) ? 'types' : str_replace($GLOBALS['tp'], '', $typeName);
				foreach($typeArr as $k=>$type) {
					$ids = array();
					$class = $db->getOne("SELECT class FROM `{$objectsTable}` WHERE id = '{$type['id']}'");
					if($class != $objectTableName) {
						$newID = uniqueid($objectTableName);
						$this->_sql[] = "UPDATE `{$GLOBALS['tp']}{$objectTableName}` SET id = '{$newID}' WHERE id = '{$type['id']}'";
						if('types' == $objectTableName) {
							$toUpdate[] = $type['id'];
							$toUpNewId[] = $newID;
						}
						$result = $db->execute("SELECT id FROM `{$table}` WHERE idtype = '{$type['id']}'");
						if($result) {
							while(!$result->EOF) {
								$ids[] = $result->fields['id'];
								$result->MoveNext();
							}
							if(isset($ids[0]))
								$this->_sql[] = "UPDATE `{$table}` SET idtype = '{$newID}' WHERE id IN (".join(',', $ids).");\n";
						}
					}
				}
			}
			$error = $this->_executeSQL();
			if($error) return false;
			if(isset($toUpdate)) {// maj entitytypes_entitytypes
				$error = $this->_executeSQL();
				if($error) return false;
				$id=0;
				foreach($toUpdate as $k=>$id) {
					$this->_sql[] = "UPDATE `{$entitytypeTable}` SET identitytype = '{$toUpNewId[$k]}' WHERE identitytype = '{$id}';\n";
					$this->_sql[] = "UPDATE `{$entitytypeTable}` SET identitytype2 = '{$toUpNewId[$k]}' WHERE identitytype2 = '{$id}';\n";
				}
			}
			// suppression ancien types
			$this->_sql[] = "DELETE FROM `{$entitytypeTable}` WHERE identitytype NOT IN (SELECT id FROM `{$typesTable}`) AND identitytype != '0';\n";
			$this->_sql[] = "DELETE FROM `{$entitytypeTable}` WHERE identitytype2 NOT IN (SELECT id FROM `{$typesTable}`) AND identitytype2 != '0';\n";
			$error = $this->_executeSQL();
			if($error) return false;
			// suppression des doublons
			$result = $db->execute("SELECT identitytype, identitytype2, count(*) as nb FROM `{$entitytypeTable}` GROUP BY identitytype, identitytype2 HAVING nb > 1");
			if ($result) {
				while (!$result->EOF) {
					$nb = $result->fields['nb']-1;
					$this->_sql[] = "DELETE FROM `{$entitytypeTable}` WHERE identitytype = '{$result->fields['identitytype']}' AND identitytype2 = '{$result->fields['identitytype2']}' LIMIT {$nb};\n";
					$result->MoveNext();
				}
			}
			$error = $this->_executeSQL();
			if($error) return false;
			// class id unique check
			$classArr = $db->getArray("SELECT id FROM `{$GLOBALS['tp']}classes` ORDER BY id");
			foreach($classArr as $class) {
				$object = $db->getOne("SELECT class FROM `{$objectsTable}` WHERE id = '{$class['id']}'");
				if('classes' != $object) {
					$newID = uniqueid('classes');
					$this->_sql[] = "UPDATE `{$GLOBALS['tp']}classes` SET id = '{$newID}' WHERE id = '{$class['id']}'";
				}
			}
		} elseif(FALSE === $datas) {
			foreach(array(0=>$typesTable, 1=>$entrytypesTable, 2=>$persontypesTable) as $key=>$type) {
				switch($key) {
					case 0: $table = $entitiesTable; break;
					case 1: $table = $entriesTable; break;
					case 2: $table = $personsTable; break;
				}
				$oldTypeArr = $db->getArray("SELECT id, type FROM `{$type}__oldME` ORDER BY id");
				if(!$oldTypeArr) continue;
				$oldMETable = ($table == $entitiesTable) ? $entitiesTable.'__oldME' : $table;
				foreach($oldTypeArr as $k=>$oldType) {
					$ids = array();
					$typeArr = $db->getRow("SELECT id FROM `{$type}` WHERE type = '{$oldType['type']}'");
					if(!$typeArr) {
						$this->_changedTypes[$type][] = array($oldType['id'] => $oldType['type']);
						continue;
					}
					$result = $db->execute("SELECT id FROM `{$oldMETable}` WHERE idtype = '{$oldType['id']}'");
					if($result) {
						while(!$result->EOF) {
							$ids[] = $result->fields['id'];
							$result->MoveNext();
						}
						if(!empty($ids))
							$this->_sql[] = "UPDATE `{$table}` SET idtype = '{$typeArr['id']}' WHERE id IN (".join(',', $ids).");\n";
					}
				}
			}
		} elseif(TRUE === $datas) { // unique ID check
			foreach(array(0=>$typesTable, 1=>$entrytypesTable, 2=>$persontypesTable) as $key=>$typeName) {
				switch($key) {
					case 0: $table = $entitiesTable; break;
					case 1: $table = $entriesTable; break;
					case 2: $table = $personsTable; break;
				}
				$typeArr = $db->getArray("SELECT id, type FROM `{$typeName}` ORDER BY id");
				$objectTableName = ($table == $entitiesTable) ? 'types' : str_replace($GLOBALS['tp'], '', $typeName);
				foreach($typeArr as $k=>$type) {
					$ids = array();
					$class = $db->getOne("SELECT class FROM `{$objectsTable}` WHERE id = '{$type['id']}'");
					if($class != $objectTableName) {
						$newID = uniqueid($objectTableName);
						$this->_sql[] = "UPDATE `{$GLOBALS['tp']}{$objectTableName}` SET id = '{$newID}' WHERE id = '{$type['id']}'";
						if('types' == $objectTableName) {
							$toUpdate[] = $type['id'];
							$toUpNewId[] = $newID;
						}
						$result = $db->execute("SELECT id FROM `{$table}` WHERE idtype = '{$type['id']}'");
						if($result) {
							while(!$result->EOF) {
								$ids[] = $result->fields['id'];
								$result->MoveNext();
							}
							if(isset($ids[0]))
								$this->_sql[] = "UPDATE `{$table}` SET idtype = '{$newID}' WHERE id IN (".join(',', $ids).");\n";
						}
					}
				}
				$error = $this->_executeSQL();
				if($error) return false;
				if(isset($toUpdate)) {// maj entitytypes_entitytypes
					$error = $this->_executeSQL();
					if($error) return false;
					$id=0;
					foreach($toUpdate as $k=>$id) {
						$etypes = $db->getArray("SELECT identitytype2 FROM `{$entitytypeTable}` WHERE identitytype = '{$id}'");
						if(is_array($etypes)) {
							foreach($etypes as $etype) {
								$this->_sql[] = "INSERT INTO `{$entitytypeTable}`(identitytype, identitytype2, cond) VALUES ('{$toUpNewId[$k]}', '{$etype['identitytype2']}', '*');\n";
							}
						}
						$etypes = $db->getArray("SELECT identitytype FROM `{$entitytypeTable}` WHERE identitytype2 = '{$id}'");
						if(is_array($etypes)) {
							foreach($etypes as $etype) {
								$this->_sql[] = "INSERT INTO `{$entitytypeTable}`(identitytype, identitytype2, cond) VALUES ('{$etype['identitytype']}', '{$toUpNewId[$k]}', '*');\n";
							}
						}
					}
				}
				// suppression des doublons
				$result = $db->execute("SELECT identitytype, identitytype2, count(*) as nb FROM `{$entitytypeTable}` GROUP BY identitytype, identitytype2 HAVING nb > 1");
				if ($result) {
					while (!$result->EOF) {
						$nb = $result->fields['nb']-1;
						$this->_sql[] = "DELETE FROM `{$entitytypeTable}` WHERE identitytype = '{$result->fields['identitytype']}' AND identitytype2 = '{$result->fields['identitytype2']}' LIMIT {$nb};\n";
						$result->MoveNext();
					}
				}
			}
			// class id unique check
			$classArr = $db->getArray("SELECT id FROM `{$GLOBALS['tp']}classes` ORDER BY id");
			foreach($classArr as $class) {
				$object = $db->getOne("SELECT class FROM `{$objectsTable}` WHERE id = '{$class['id']}'");
				if('classes' != $object) {
					$newID = uniqueid('classes');
					$this->_sql[] = "UPDATE `{$GLOBALS['tp']}classes` SET id = '{$newID}' WHERE id = '{$class['id']}'";
				}
			}
		}
		$error = $this->_executeSQL();
		if($error) return false;
	}

	/**
	 * Met à jour la base de données SQL
	 *
	 * Modifie ou crée les champs des tables
	 *
	 * @param array $context le contexte passé par référence
	 * @param array $error les éventuelles erreurs, passées par référence
	 */
	private function _manageFields(&$context, &$error) {
		if(empty($context['data']) || !is_array($context['data'])) return;
		$tablefield = lq('#_TP_tablefields');
		foreach($context['data'] as $table=>$fields) {
			$tablefieldgroup = isset($fields['dropped']['tablefieldgroup']) ? $fields['dropped']['tablefieldgroup'] : array();
			unset($fields['dropped']['tablefieldgroup']);
			$flipped = !empty($fields['dropped']) && is_array($fields['dropped']) ? array_flip($fields['dropped']) : array();
			if(!empty($fields['added']) && is_array($fields['added'])) {
				foreach($fields['added'] as $field=>$equivalent) {
					$fieldType = multidimArrayLocate($this->_xmlStruct[$table], $field);
					$fieldType = array_pop($fieldType);
					if(isset($flipped[$equivalent])) {
						$action = "CHANGE `{$flipped[$equivalent]}` `{$field}`";
						if($field != $flipped[$equivalent])
							$this->_sql[] = "UPDATE `{$tablefield}` SET name = '{$field}' WHERE name = '{$flipped[$equivalent]}' AND class = '{$table}'";
						unset($this->_fieldsToKeep[$table][$flipped[$equivalent]]);
					} else {
						$action = "ADD `{$field}`";
					}
					$this->_sql[] = "ALTER TABLE `{$table}` {$action} {$fieldType[$field]};\n";
				}
			}
			if(!isset($this->_fieldsToKeep[$table])) {
				continue;
			}
			foreach($this->_fieldsToKeep[$table] as $name=>$row) {
				if(!isset($tablefieldgroup[$name])) {
					$error = "Please select a group for field '{$name}' in table '{$table}'";
					return;
				}
				$values = array_values($row);
				$nbTablefields = count($row)-1;
				$tmpSql = '';
				foreach($values as $k=>&$v) {
					if(1 === $k) { // idgroup
						$v = $tablefieldgroup[$name];
					}
					$v = addcslashes($v, '"');
					$tmpSql .= ($k < $nbTablefields) ? "\"{$v}\"," : "\"{$v}\"";
				}
				$this->_sql['differed'][] = "INSERT INTO `{$tablefield}` (".join(',', array_keys($row)).") VALUES ({$tmpSql});\n";
			}
		}
		$error = $this->_executeSQL();
	}

	/**
	 * Génération du ME XML
	 *
	 * @param array $context le contexte passé par référence
	 */
	public function generateXML(&$context) {
		global $db;

		// besoin des fonctions de bruno pour conversion entités
		defined('INC_TEXTFUNC') || include 'textfunc.php';

		// on récupère les tables du ME
		$this->_getEMTables();
		// on crée notre document XML avec sa DTD pour pouvoir valider par la suite
		$impl = new DomImplementation();
		$dtd = $impl->createDocumentType("lodelEM", "", "lodelEM.dtd");
		$document = $impl->createDocument("", "", $dtd);
		$document->encoding = $GLOBALS['db_charset'];
		// début création XML
		$schemaNode = $document->createElement("lodelEM");
		$document->appendChild($schemaNode);
		$model = $document->createElement("model");
		$schemaNode->appendChild($model);
		$descr = $document->createElement("lodelversion");
		$model->appendChild($descr);
		$descr->nodeValue = C::get('version', 'cfg');
		$descr = $document->createElement("date");
		$model->appendChild($descr);
		$descr->nodeValue = date("Y-m-d");
		$descr = $document->createElement("title");
		$model->appendChild($descr);
		$descr->nodeValue = myhtmlentities(stripslashes($context['title']));
		$descr = $document->createElement("description");
		$model->appendChild($descr);
		$descr->nodeValue = myhtmlentities(stripslashes($context['description']));
		$descr = $document->createElement("author");
		$model->appendChild($descr);
		$descr->nodeValue = myhtmlentities(stripslashes($context['author']));
		$descr = $document->createElement("modelversion");
		$model->appendChild($descr);
		$descr->nodeValue = myhtmlentities(stripslashes($context['modelversion']));

		foreach ($this->_tables as $table=>$content) {
			$tableNode = $document->createElement("table");
			$schemaNode->appendChild($tableNode);
			$tableName = $GLOBALS['tableprefix'] ? ( str_replace($GLOBALS['tableprefix'], '#_TP_', $table) ) : '#_TP_'.$table;
			$tableNode->setAttribute('name', $tableName);
			$structNode = $document->createElement("structure");
			$tableNode->appendChild($structNode);

			$result = $db->getRow( "SHOW CREATE TABLE ".$table );
			preg_match("/^CREATE TABLE `$table`\s+\(\s*(.*)\s*\)\s*(.*)$/s", $result['Create Table'], $matches);
			if($matches)
            {
                $fields = explode("\n", $matches[1]);

                foreach($fields as $kk=>$val) {
                    $field = trim($val);
                    if(!$field)
                        continue;
                    $fieldNode = $document->createElement("field");
                    $structNode->appendChild($fieldNode);
                    if(FALSE !== strpos($field, ',', strlen($field)-1))
                        $field = substr($field, 0, strlen($field)-1);

                    if(preg_match("/^`([^`]+)`\s+(.*)$/", $field, $m)) { // champ
                        $fieldNode->setAttribute('name', $m[1]);
                        $fieldNode->nodeValue = $m[2];
                    } else { // clé
                        $field = explode('KEY', $field);
                        $field[0] = trim($field[0]);
                        $field[1] = trim($field[1]);
                        // on peut avoir plusieurs clé : on concatène $kk
                        if($field[0]) {
                            $fieldNode->setAttribute('name', $field[0].'_'.$kk);
                            $fieldNode->nodeValue = $field[1];
                        } else {
                            $fieldNode->setAttribute('name', 'KEY_'.$kk);
                            $fieldNode->nodeValue = $field[1];
                        }
                        $fieldNode->setAttribute('key', '1');
                    }
                }

                $fieldNode = $document->createElement("field");
                $structNode->appendChild($fieldNode);
                $fieldNode->setAttribute('name', 'tableOptions');
                $fieldNode->nodeValue = $matches[2];
            }

			if($content) {
                if($table == lq('#_TP_relations_ext'))
                { // sauvegarde des types d'entrées externes
                    $where =  ' WHERE nature IN ("ET", "EET")';
                    $select = 'id1,id2,nature,degree,site';
                }
                else
                {
                    $where =  '';
                    $select = '*';
                }
				$result = $db->execute("SELECT {$select} FROM ".$table.$where);
				if($result->fields) {
					$datasNode = $document->createElement("datas");
					$tableNode->appendChild($datasNode);
				}
				while (!$result->EOF) {
					$rowNode = $document->createElement("row");
					$datasNode->appendChild($rowNode);
					foreach($result->fields as $key=>$value) {
						$dataNode = $document->createElement('col');
						$rowNode->appendChild($dataNode);
						$dataNode->setAttribute('name', $key);
						$dataNode->nodeValue = strtr(HTML2XML($value), array('&'=>'&amp;', '<'=>'&lt;', '>'=>'&gt;'));
					}
					$result->MoveNext();
				}
			}
		}
		// joli indentation
		$document->formatOutput = true;
		$xml = $document->saveXML();
		return $xml;
	}
}// end of DataLogic class


//Définition de la LOOP sur les fichiers d'import détectés
function loop_files(&$context, $funcname)
{
	#global $importdirs,$fileregexp;
	#$context['importdirs'][] = $GLOBALS['importdir'];
	foreach ($context['importdirs'] as $dir) {
		if ( $dh = @opendir($dir)) {
			while (($file = readdir($dh)) !== FALSE) {
				if (!preg_match("/^".$context['fileregexp']."$/i", $file)) {
					continue;
				}
				$localcontext = $context;
				$localcontext['filename']     = $file;
				$localcontext['fullfilename'] = "$dir/$file";
				call_user_func("code_do_$funcname", $localcontext);
			}
			closedir ($dh);
		}
	}
}

// Définition de la loop pour les fichier du ME
function loop_files_model(&$context, $funcname)
{
	global $fileregexp;
	$model = !empty($context['xmlimport']) ? 'model.xml' : 'model.sql';

	foreach ($context['importdirs'] as $dir) {
		if ( $dh = @opendir($dir)) {
			while (($file = readdir($dh)) !== FALSE) {
				if (!preg_match("/^$fileregexp$/i", $file)) {
					continue;
				}
				$localcontext = $context;
				$localcontext['filename']     = $file;
				$localcontext['fullfilename'] = $dir . DIRECTORY_SEPARATOR . $file;

                $tmpdir = tmpdir(uniqid('me'));
                extract_files_from_zip($localcontext['fullfilename'], $tmpdir, null, array($model) );

				$line = file_get_contents($tmpdir . DIRECTORY_SEPARATOR . $model);
                unlink($tmpdir . DIRECTORY_SEPARATOR . $model);
                rmdir($tmpdir);

				if (!$line) {
					continue;
				}

				$xml = "";
				if (preg_match("/<model>(.*?)<\/model>/s", $line, $result)) {
					$lines = preg_split("/\n/", $result[1]);
					$xml = "";
					foreach ($lines as $line) {
						$xml.= substr($line, 2). "\n";
					}
				}
				foreach (array('lodelversion', 'title', 'description', 'author', 'date', 'modelversion') as $tag) {
					if (preg_match("/<$tag>(.*?)<\/$tag>/s", $xml, $result)) {
						$localcontext[$tag] = str_replace(array("\r", "<",">", "\n"),
						array("", "&lt;", "&gt;", "<br />"),
						trim($result[1]));
					}
				}
				// check only the major version, sub-version are not checked
                		// with xml ME import, we don't need to check the version
				if (empty($context['xmlimport']) && (empty($localcontext['lodelversion']) ||
                			doubleval($localcontext['lodelversion']) != doubleval(C::get('version', 'cfg')))) {
					$localcontext['warning_version'] = 1;
				}
				call_user_func("code_do_$funcname", $localcontext);
			}
			closedir ($dh);
		}
	}
}
// FROM http://php.net/array_diff - http://php.net/array_diff_assoc - http://fr.php.net/manual/fr/function.array-search.php
// adjusted for Lodel. thanks to the authors !
function array_diff_assoc_recursive($array1, $array2)
{
	foreach($array1 as $key => $value)
	{
		if(is_array($value))
		{
			if(!isset($array2[$key]))
			{
				$difference[$key] = $value;
			}
			elseif(!is_array($array2[$key]))
			{
				$difference[$key] = $value;
			}
			else
			{
				$new_diff = array_diff_assoc_recursive($value, $array2[$key]);
				if($new_diff != FALSE)
				{
					$difference[$key] = $new_diff;
				}
			}
		} elseif(!isset($array2[$key]) || $array2[$key] != $value) {
			$difference[$key] = $value;
		}
	}
	return !isset($difference) ? 0 : $difference;
}
function array_diff_all($arr_new,$arr_old)
{
	$arr_del = array_diff_assoc_recursive($arr_old,$arr_new);
	$arr_add = array_diff_assoc_recursive($arr_new,$arr_old);
	$diff = array();
	if(!empty($arr_add))
		$diff += array('added'=>$arr_add);
	if(!empty($arr_del))
		$diff += array('dropped'=>$arr_del);
	return $diff;
}
function multidimArrayLocate($array, $text)
{
	foreach($array as $key => $arrayValue){
		if (is_array($arrayValue)){
			if ($key === $text) $arrayResult[$key] = $arrayValue;
			$temp[$key] = multidimArrayLocate($arrayValue, $text);
			if (isset($temp[$key])) $arrayResult[$key] = $temp[$key];
		}
		else{
			if ($key === $text) $arrayResult[$key] = $arrayValue;
		}
	}
	return isset($arrayResult) ? $arrayResult : null;
}
