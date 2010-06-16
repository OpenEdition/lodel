<?php
/**
 * Fichier de classe pour les backups
 *
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Home page: http://www.lodel.org
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
 * @package lodel/logic
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 * @version CVS:$Id$
 */

/**
 * Classe de logique permettant de gérer les backup et import de données et de ME
 * 
 * @package lodel/logic
 * @author Jean Lamy
 * @author Sophie Malafosse
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajoutée depuis la version 0.8
 * @see backupfunc.php, pma/sql-modified.php
 */
class DataLogic
{

	/**
	 * Prefix du fichier (pour l'import de ME et l'import de données)
	 * @var string
	 */
	var $filePrefix;

	/**
	 * Expression utilisée pour filtrer les fichiers pour un import
	 * @var string
	 */
	var $fileRegexp;

	/**
	 * Extension du fichier d'import
	 * @var string
	 */
	var $fileExtension;

	/**
	 * Constructeur
	 *
	 * Interdit l'accès aux utilisateurs qui ne sont pas ADMIN
	 */
	function DataLogic()
	{
		if ($GLOBALS['lodeluser']['rights'] < LEVEL_ADMIN 
		|| ($GLOBALS['lodeluser']['rights'] == LEVEL_ADMIN && ($GLOBALS['context']['do'] == 'import' 
		|| $GLOBALS['context']['do'] == 'backup' 
		|| $GLOBALS['context']['do'] == 'importmodel' 
		|| $GLOBALS['context']['do'] == 'importxmlmodel'))) {
			die("ERROR: you don't have the right to access this feature");
		}		
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
	function importAction(&$context, &$error)
	{
		global $db;
		require_once 'func.php';
		$context['importdir'] = $GLOBALS['importdir'];
		$this->fileRegexp = $context['fileregexp'] = '(site|revue)-[a-z0-9\-]+-\d{6}.'. $this->fileExtension;

		// les répertoires d'import
		$context['importdirs'] = array('CACHE');
		if ($context['importdir']) {
  		$context['importdirs'][] = $context['importdir'];
		}

		$file = $this->_extractImport($context);

		if ($file) { // Si on a bien spécifié un fichier
			do { // control block

				set_time_limit(120); //pas d'effet si safe_mode on ; on met le temps à unlimited
				//nom du fichier SQL
				$sqlfile = tempnam(tmpdir(), 'lodelimport_');
				//noms des répertoires acceptés
				$accepteddirs = array('lodel/txt', 'lodel/rtf', 'lodel/sources', 'lodel/icons', 'docannexe/file', 'docannexe/image', 'docannexe/fichier');
		
				require_once 'backupfunc.php';
				if (!importFromZip($file, $accepteddirs, array(), $sqlfile)) {
					
					$err = $error['error_extract'] = 'extract';
					return 'import';
				}
				#require_once 'connect.php';
				// drop les tables existantes
				//$db->execute(lq('DROP TABLE IF EXISTS '. join(',', $GLOBALS['lodelsitetables']))) or dberror();
				//execution du dump SQL
				if (!$this->_execute_dump($sqlfile)) {
					$error['error_execute_dump'] = $err = $db->errormsg();
				}
				@unlink($sqlfile);
		
				require_once 'cachefunc.php';
				removefilesincache(SITEROOT, SITEROOT. 'lodel/edition', SITEROOT. 'lodel/admin');
		
				// verifie les .htaccess dans le CACHE
				$this->_checkFiles($context);
			} while(0);
		} else {
			$error['file'] = 'unknown_file';
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
	function backupAction(&$context, &$error)
	{
		global $zipcmd;
		$context['importdir'] = $GLOBALS['importdir'];
		#print_r($context);
		if ($context['backup']) { // si on a demandé le backup
			require_once 'func.php';
			require_once 'backupfunc.php';
			$site = $context['site'];
			$outfile = "site-$site.sql";

			//$uselodelprefix = true; // ? NON UTILISE
			$GLOBALS['tmpdir'] = $tmpdir = tmpdir();
			$errors = array();
			$fh = fopen($tmpdir. '/'. $outfile, 'w');
			if (!$fh) {
				die ("ERROR: unable to open a temporary file in write mode");
			}
			$this->_dump($site, $tmpdir. '/'. $outfile, $errors, $fh);
			fclose($fh);
			if($errors) {
				$error = $errors;
				return 'backup';
			}
			// verifie que le fichier SQL n'est pas vide
			if (filesize($tmpdir. '/'. $outfile) <= 0) {
				$error['mysql'] = 'dump_failed';
				return 'backup';
			}
		
			// zip le site et ajoute la base
			$archivetmp      = tempnam($tmpdir, 'lodeldump_'). '.zip';
			$archivefilename = "site-$site-". date("dmy"). '.zip';
			// fichiers à exclure de l'archive
			$GLOBALS['excludes'] = $excludes = array('lodel/sources/.htaccess',
						'docannexe/fichier/.htaccess',
						'docannexe/image/index.html',
						'docannexe/index.html',
						'docannexe/image/tmpdir-\*',
						'docannexe/tmp\*');
			// répertoires à inclure
			$sitedirs = array('lodel/icons', 'lodel/sources', 'docannexe');

			// si sauvegarde des répertoires demandée (en + de la base)
			if (!$context['sqlonly']) {
					//verifie que les repertoires sont accessibles en lecture
					foreach ($sitedirs as $sitedir) {
						if(is_readable(SITEROOT . $sitedir)){
							$good_dirs[] = $sitedir;
						} else {
							$bad_dirs[] = $sitedir;
						}
					}
					// initialise $error pour affichage dans le template backup.html
					if (is_array($bad_dirs)) { $error['files'] = implode(', ', $bad_dirs); }
					
					// conversion en chaîne pour ligne de commande
					$dirs = implode(' ', $good_dirs);
				}
			else    { $dirs = ''; }

			if ($zipcmd && $zipcmd != 'pclzip') { //Commande ZIP

				if (!$context['sqlonly']) {
					if (!chdir(SITEROOT)) {
						die ("ERROR: can't chdir in SITEROOT");
					}
					$prefixdir    = $archivetmp[0] == "/" ? '' : 'lodel/admin/';
					$excludefiles = $excludes ? " -x ". join(" -x ", $excludes) : "";
					system($zipcmd. " -q $prefixdir$archivetmp -r $dirs $excludefiles");
					if (!chdir("lodel/admin")) {
						die ("ERROR: can't chdir in lodel/admin");
					}
					system($zipcmd. " -q -g $archivetmp -j $tmpdir/$outfile");
				} else {
					system($zipcmd. " -q $archivetmp -j $tmpdir/$outfile");
				}
			} else { // Comande PCLZIP

				require_once 'pclzip/pclzip.lib.php';
				$archive = new PclZip($archivetmp);
				if (!$context['sqlonly']) {
					// function to exclude files and rename directories
					function preadd($p_event, &$p_header) 
					{
						global $excludes, $tmpdir; // that's bad to depend on globals like that
						$p_header['stored_filename'] = preg_replace("/^". preg_quote($tmpdir, "/"). "\//", "", $p_header['stored_filename']);
						foreach ($excludes as $exclude) {
							if (preg_match ("/^". str_replace('\\\\\*', '.*', preg_quote($exclude, "/")). "$/", $p_header['stored_filename'])) {
								return 0;
							}
						}
						return 1;
					}
					// end of function to exclude files

					// ajout de la racine du site aux chemins des répertoires
					for($i=0 ; $i<count($good_dirs) ; $i++){
						$good_dirs[$i] = SITEROOT. $good_dirs[$i];
					}
					// ajout du fichier sql issu du dump de la base du site
					array_push($good_dirs, $tmpdir. '/'. $outfile);
					// création de l'archive
					$archive->create($good_dirs,
							PCLZIP_OPT_REMOVE_PATH,SITEROOT,
							PCLZIP_CB_PRE_ADD, 'preadd');
				} else {
					$archive->create($tmpdir. "/". $outfile, PCLZIP_OPT_REMOVE_ALL_PATH);
				}
			} // end of pclzip option
		
			if (!file_exists($archivetmp)) {
				die ("ERROR: the zip command or library does not produce any output");
			}
			@unlink($tmpdir. '/'. $outfile); // delete the sql file
			
			if($error) { // Pour avoir accès aux erreurs dans les templates
				$context['error'] = $error;}

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
	function globalbackupAction(&$context, &$error)
	{
		global $db;
		if($context['lodeluser']['rights'] < 128) {
			die('ERROR : You d\'ont have the rights to use this feature');
		}
		require_once 'backupfunc.php';
		$context['importdir'] = $GLOBALS['importdir'];
		$operation = $context['operation'];
		if ($context['backup']) {
			// il faut locker la base parce que le dump ne doit pas se faire en meme temps que quelqu'un ecrit un fichier.
			$dirtotar  = array();
			$dirlocked = tempnam('/tmp', 'lodeldump_'). '.dir'; // this allow to be sure to have a unique dir.
			mkdir($dirlocked, 0700);
			$outfile = 'lodel.sql';
			$fh = fopen($dirlocked. '/'. $outfile, 'w');
			
			if (!$fh) {
				die ("ERROR: unable to open a temporary file in write mode");
			}
			// save the main database
			if (fputs($fh, 'DROP DATABASE IF EXISTS '. DATABASE. ";\nCREATE DATABASE ". DATABASE. ";USE ". DATABASE. ";\n") === FALSE) {
				die("ERROR: unable to write in the temporary file");
			}

			$GLOBALS['currentprefix'] = '#_MTP_';
			set_time_limit(60); // pas d'effet en safe mode
			require_once 'backupfunc.php';
			mysql_dump(DATABASE, $GLOBALS['lodelbasetables'], '', $fh);
			
			// Trouve les sites a inclure au backup.
			//$errors = array();
			$result = $db->execute(lq('SELECT name, path FROM #_MTP_sites WHERE status > -32')) or dberror();
			chdir(LODELROOT); 
			set_time_limit(60); // pas d'effet en safe mode
			$GLOBALS['currentprefix'] = '#_TP_';
			while (!$result->EOF) {
				$name = $result->fields['name'];
				$sitepath = $result->fields['path'];
				if (fputs($fh, 'DROP DATABASE IF EXISTS '. $name. ";\nCREATE DATABASE ". $name. ";USE ". $name. ";\n") === FALSE) {
				die("ERROR: unable to write in the temporary file");
			}
				$this->_dump($name, $outfile, $errors, $fh);
				if (!$context['sqlonly']) { 
					if ($sitepath == '/') { $root = ''; } // site à la racine
					else { $root = $name . '/'; }
					// liste des répertoires du site à archiver
					$sitedirs = array('lodel/icons', 'lodel/sources', 'docannexe');

					//verifie que les repertoires sont accessibles en lecture
					foreach ($sitedirs as $sitedir) {
						if(is_readable($root . $sitedir)){ $dirtotar[] = $root . $sitedir;}
						else { $bad_dirs[] = $root . $sitedir;}
					}
					 if (is_array($bad_dirs)) { $error['files'] = implode(', ', $bad_dirs); }
				}
				$result->MoveNext();
			}
			fclose($fh);
			$db->selectDB(DATABASE); //selectionne la base principale.

			// tar les sites et ajoute la base
			$archivetmp      = tempnam('/tmp', 'lodeldump_');
			$archivefilename = 'lodel-'. date('dmy'). '.tar.gz';
			// Attention ce qui suit ne fonctionnera que sous Linux
			system("tar czf $archivetmp ". join(' ', $dirtotar). " -C $dirlocked $outfile") !== false or die ("impossible d'executer tar");
			unlink($dirlocked. '/'. $outfile);
			rmdir($dirlocked);
			chdir('lodeladmin'. ($GLOBALS['version'] ? '-'. $GLOBALS['version'] : ''));
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
	function importmodelAction(&$context, &$error)
	{
		//Vérifie que l'on peut bien faire cet import
		$context['importdir'] = $GLOBALS['importdir']; //cherche le rep d'import défini dans la conf
		$GLOBALS['importdirs'] = array ('CACHE', $GLOBALS['home']. '../install/plateform');
		if ($context['importdir']) {
			$GLOBALS['importdirs'][] = $importdir;
		}
		$context['importdirs'] = $GLOBALS['importdirs'];
		$this->fileExtension = 'zip';
		$this->fileRegexp = $GLOBALS['fileregexp'] = '(model)-\w+(?:-\d+)?.'. $this->fileExtension; //restriction sur le nom du ZIP
		
		if (($context['error_table'] = $this->_isimportmodelallowed()) ) {
			return 'importmodel';
		}
		$this->filePrefix = 'model';
		$file = $this->_extractImport($context);
		
		if ($file && $context['delete']) {// extra check. Need more ?
			if (dirname($file) == 'CACHE') {
				unlink($file);
			}
		} elseif ($file) {
			require_once 'backupfunc.php';
			require_once 'func.php';
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
			require_once 'cachefunc.php';
			clearcache();
			
			if (!$err) {
				if ($context['frominstall']) { // si on vient de l'install redirige vers la page d'édition
					header ('location: ../edition/index.php');
					exit;
				} else {
					$context['success'] = 1;
					return 'importmodel';
				}
			}
		}
		#print_r($context);
		if ($context['frominstall']) {
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
	function backupmodelAction(&$context, &$error)
	{
		$context['importdir'] = $importdir;
		if ($context['backup']) {
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
				$context['error'] = $error;
				return 'backupmodel';
			}
			require 'backupfunc.php';
			$tmpfile        = tmpdir(). '/model.sql';
			$fh             = fopen($tmpfile, 'w');
			$description    = '<model>
			<lodelversion>'. $version. '</lodelversion>
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
			mysql_dump($currentdb, $tables, '', $fh, false, false, true); // get the content
			
			// select the optiongroups to export
			$dao = &getDAO('optiongroups');
			$vos = $dao->findMany('exportpolicy > 0 AND status > 0', '', 'name, id');
			$ids = array();
			foreach($vos as $vo) {
				$ids[] = $vo->id;
			}
			fputs($fh, "DELETE FROM #_TP_optiongroups;\n");
			mysql_dump($currentdb, array('#_TP_optiongroups'), '', $fh, false, false, true, '*', 'id '. sql_in_array($ids));
			fputs($fh, "DELETE FROM #_TP_options;\n");
			mysql_dump($currentdb,array('#_TP_options'), '', $fh, false, false, true, 'id, idgroup, name, title, type, defaultvalue, comment, userrights, rank, status, upd, edition, editionparams', 'idgroup '. sql_in_array($ids)); // select everything but not the value
		
			// Récupère la liste des tables de classe à sauver.
			$dao = &getDAO('classes');
			$vos = $dao->findMany('status > 0', '', 'class,classtype');
			$tables = array();
			foreach ($vos as $vo) {
				$tables[] = lq('#_TP_'. $vo->class);
				if ($vo->classtype == 'persons') {
					$tables[] = lq('#_TP_entities_'. $vo->class);
				}
			}
			if ($tables) {
				mysql_dump($currentdb, $tables, '', $fh, true, true, false); // get the table create
			}
			// it may be better to recreate the field at the import rather 
			// than using the created field. It may be more robust. Status quo at the moment.
			fclose($fh);
			
			if (filesize($tmpfile) <= 0) {
				die ('ERROR: mysql_dump failed');
			}
		
			$dirs = array();
			$dirstest = array('tpl', 'css', 'images', 'js', 'lodel/icons');
			foreach($dirstest as $dir) {
				if ($context[$dir]) {
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
	 * @access private
	 * @param string $site le nom du site
	 * @param string $outfile le fichier dans lequel écrire le dump SQL
	 * @param resource $fh le descripteur de fichier (par défaut 0)
	 * @param array $error tableau des erreurs
	 */
	function _dump($site, $outfile, &$error, $fh = 0)
	{
		global $db;
		if ($site && $GLOBALS['singledatabase'] != 'on') {
			$dbname = DATABASE."_".$site;
			if (!$fh)	{
				$fh = fopen($outfile, "w");
				$closefh = true;
			}
			if (!$fh)
				die("ERROR: unable to open file $outfile for writing");
		}	else	{
			$dbname = DATABASE;
		}
	
		if (!$db->selectDB($dbname)) {
			$error['database'] = 'error : '.$db->ErrorMsg().'<br />';
			return ;
		}
		$GLOBALS['currentprefix'] = "#_TP_";
		$tables = $GLOBALS['lodelsitetables'];
		$dao = &getDAO('classes');
		$vos = $dao->findMany('status > 0', '', 'class, classtype');
		foreach ($vos as $vo)	{
			$tables[] = lq("#_TP_". $vo->class);
			if ($vo->classtype == 'persons')
				$tables[] = lq('#_TP_entities_'. $vo->class);
		}
		// dump structure + données
		mysql_dump($dbname, $tables, $outfile, $fh);
		// dump structure seulement
		$tables_nodatadump = $GLOBALS['lodelsitetables_nodatadump'];
		mysql_dump($dbname, $tables_nodatadump, $outfile, $fh, $create = true, $drop = true, $contents = false);
		if ($closefh)
			fclose($fh);
	}

	/**
	 * Execute un dump (fichier SQL) pointé par $url
	 *
	 * @todo vérifier que cette fonction ne prends pas trop de place en mémoire.
	 * @access private
	 * @param string $url le fichier SQL
	 * @param boolean $ignoreerrors. false par défaut
	 * @return true si le dump a bien été executé	 */
	function _execute_dump($url, $ignoreerrors = false) 
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
					if (!$result && !$ignoreerrors) die(mysql_error());
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
	function _checkFiles(&$context)
	{
		$dirs = array('CACHE', 'lodel/admin/CACHE', 'lodel/edition/CACHE', 'lodel/txt', 'lodel/rtf', 'lodel/sources');
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
	 * Crée un fichier ZIP du ME contenant le fichier SQL et éventuellement les répertoires
	 * images, css, js et tpl et lodel/icons
	 *
	 * @access private
	 * @param string $sqlfile le fichier dump SQL
	 * @param array $dirs la liste des répertoires à inclure.
	 * @return le nom du fichier ZIP
	 */
	function _backupME($sqlfile, $dirs = array())
	{
		global $zipcmd;
	
		$acceptedexts = array ('html', 'js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'tiff');
		$tmpdir = tmpdir();
		$archivetmp = tempnam($tmpdir, 'lodeldump_'). '.zip';
	
		// Cherche si les répertoires à zipper contiennent bien des fichiers
		$zipdirs = array ();
		foreach ($dirs as $dir)	{
			if (!file_exists(SITEROOT. $dir))
				continue;
			$dh = opendir(SITEROOT. $dir);
			while (($file = readdir($dh)) && !preg_match("/\.(".join("|", $acceptedexts).")$/", $file))	{
			}
			if ($file)
				$zipdirs[] = $dir;
			closedir($dh);
		}
		//
	
		if ($zipcmd && $zipcmd != 'pclzip')	{ //commande ZIP
			if ($zipdirs)	{
				foreach ($zipdirs as $dir) {
					foreach ($acceptedexts as $ext)	{
						$files .= " $dir/*.$ext";
					}
				}
				if (!chdir(SITEROOT))
					die("ERROR: can't chdir in SITEROOT");
				$prefixdir = $tmpdir[0] == '/' ? '' : 'lodel/admin/';
				system($zipcmd." -q $prefixdir$archivetmp $files");
				if (!chdir("lodel/admin"))
					die("ERROR: can't chdir in lodel/admin");
				system($zipcmd." -q -g $archivetmp -j $sqlfile");
			}	else {
				system($zipcmd." -q $archivetmp -j $sqlfile");
			}
		}	else	{ // commande PCLZIP
			//require_once "pclzip.lib.php";
			require_once 'pclzip/pclzip.lib.php';
			$archive = new PclZip($archivetmp);
			if ($zipdirs)	{
				// function to exclude files and rename directories
				function preadd($p_event, & $p_header, $user_vars)
				{
					$p_header['stored_filename'] = preg_replace("/^".preg_quote($user_vars['tmpdir'], "/")."\//", "", $p_header['stored_filename']);
	
					#echo $p_header['stored_filename'],"<br>";
					return preg_match("/\.(".join("|", $user_vars['acceptedexts'])."|sql)$/", $p_header['stored_filename']);
				}
				// end of function to exclude files
				foreach ($zipdirs as $dir) {
					$files[] = SITEROOT.$dir;
				}
				$files[] = $sqlfile;
				$archive->user_vars = array ('tmpdir' => $tmpdir, 'acceptedexts' => $acceptedexts);
				$res = $archive->create($files, PCLZIP_OPT_REMOVE_PATH, SITEROOT, PCLZIP_CB_PRE_ADD, 'preadd');
				if (!$res)
					die("ERROR: Error while creating zip archive: ".$archive->error_string);
			}	else {
				$archive->create($sqlfile, PCLZIP_OPT_REMOVE_ALL_PATH);
			}
		} // end of pclzip option
	
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
	function _isimportmodelallowed() 
	{
		global $db;
		// verifie qu'on peut importer le modele.
		$tablestocheck = array('#_TP_entities', '#_TP_entries', '#_TP_persons');
		foreach($tablestocheck as $table) {
			$haveelements = $db->getOne(lq("SELECT id FROM $table WHERE status>-64"));
			if ($db->errorno) {
				continue; // on fait comme si la table n'existait pas
			}
			if ($haveelements) {
				return $table;
			}
			$db->execute(lq("DELETE FROM $table WHERE status<=-64")) or dberror();
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
	function _extractImport(&$context)
	{
		$archive = $_FILES['archive']['tmp_name'];
		$context['error_upload'] = $_FILES['archive']['error'];
		if (!$context['error_upload'] && $archive && $archive != 'none' && is_uploaded_file($archive)) { // Le fichier a été uploadé			
			$file = $_FILES['archive']['name'];
			if (!preg_match("/^".$this->fileRegexp."$/", $file)) {
				$context['error_regexp'] = 1;
				return;
				//$file = $this->filePrefix . '-import-'. date("dmy"). '.'. $this->fileExtension;
			}
			
			if (!move_uploaded_file($archive, 'CACHE/'.$file)) {
				//die('ERROR: a problem occurs while moving the uploaded file.');
				$context['error_upload'] = 1;
				//return;
			}
			$file = ''; // on repropose la page
		} elseif ($context['file']) {
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
	function _reinitobjetstable()
	{
		global $db;
		$db->execute(lq('DELETE FROM #_TP_objects')) or dberror();
	
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
				$db->execute(lq('UPDATE #_TP_'. $table. ' SET '. $idname. '='. $idname. '+'. $offset. ' WHERE '.$idname. '>0')) or dberror();
			}
		}
	
		$conv = array('types' => array('entitytypes_entitytypes' => array('identitytype', 'identitytype2'), ),
									'persontypes' => array(), 'entrytypes' => array(), 'classes' => array());
	
		foreach ($conv as $maintable => $changes) {
			$result = $db->execute(lq("SELECT id FROM #_TP_$maintable")) or dberror();
			while ( ($id=$result->fields['id']) ) {
				$newid=uniqueid($maintable);
				$db->execute(lq('UPDATE #_TP_'.$maintable.' SET id='.$newid.' WHERE id='.$id)) or dberror();
				foreach ($changes as $table => $idsname) {
					if (!is_array($idsname)) {
						$idsname = array($idsname);
					}
					foreach ($idsname as $idname) {
						$db->execute(lq('UPDATE #_TP_'. $table. ' SET '. $idname. '='. $newid. ' WHERE '. $idname. '='. $id)) or dberror();
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
					dberror();
				}
				if ($count) {
					die("<strong>warning</strong>: reste $count $idname non converti dans $table. si vous pensez que ce sont des restes de bug, vous pouvez les detruire avec la requete SQL suivante: DELETE FROM $GLOBALS[tp]$table WHERE $idname>$offset<br />\n");
				}
			}
		}
		if ($err) {
			return $err;
		}
		return false;
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
	global $fileregexp, $home, $unzipcmd;
	#print_r($context);
	foreach ($context['importdirs'] as $dir) {
		if ( $dh = @opendir($dir)) {
			while (($file = readdir($dh)) !== FALSE) {
				if (!preg_match("/^$fileregexp$/i", $file)) {
					continue;
				}
				$localcontext = $context;
				$localcontext['filename']     = $file;
				$localcontext['fullfilename'] = "$dir/$file";
				if ($dir == "CACHE") {
					$localcontext['maybedeleted'] = 1;
				}
				// open ZIP archive and extract model.sql
				if ($unzipcmd && $unzipcmd != "pclzip") {
	  			$line = `$unzipcmd $dir/$file -c model.sql`;
				} else {
	  			require_once "pclzip/pclzip.lib.php";
	  			$archive = new PclZip("$dir/$file");
	  			$arr = $archive->extract(PCLZIP_OPT_BY_NAME, "model.sql",
									PCLZIP_OPT_EXTRACT_AS_STRING);
	  			$line = $arr[0]['content'];
				}
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
				if (doubleval($localcontext['lodelversion']) != doubleval($GLOBALS['version'])) {
					$localcontext['warning_version'] = 1;
				}
				call_user_func("code_do_$funcname", $localcontext);
			}
			closedir ($dh);
		}
	}
}

?>
