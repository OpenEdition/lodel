<?php
/**
 * Fichier de classe pour les backups
 *
 * PHP version 4
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
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 * @version CVS:$Id$
 */

/**
 * Classe de logique permettant de gérer les backup et import de données et de ME
 * 
 * @package lodel/logic
 * @author Jean Lamy
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Classe ajouté depuis la version 0.8
 * @see logic.php
 */
class DataLogic
{
	/**
	 * Constructeur
	 *
	 * Interdit l'accès aux utilisateurs qui ne sont pas ADMIN
	 */
	function DataLogic()
	{
		if ($GLOBALS['lodeluser']['rights'] < LEVEL_ADMIN) {
			die("ERROR: you don't have the right to access this feature");
		}
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
		$context['importdir'] = $GLOBALS['$importdir'];
		$context['fileregexp'] = '(site|revue)-\w+-\d+.zip';

		// les répertoires d'import
		$context['importdirs'] = array('CACHE');
		if ($context['importdir']) {
  		$context['importdirs'][] = $context['importdir'];
		}

		// Upload du fichier
		if (!$context['error_upload'] && $archive && $archive != 'none' && is_uploaded_file($archive)) {
			$prefixre   = '(site|revue)';
			$prefixunix = '{site,revue}';
			$file       = $archive;

		// Ficher déjà sur le disque
		} elseif ($context['file'] && preg_match("/^(?:". str_replace('/', '\/', join('|', $context['importdirs'])). ")\/".$context['fileregexp']."$/", $context['file'], $result) && file_exists($context['file'])) {
			$prefixre = $prefixunix = $result[1];
		} else { // rien
			$file = '';
		}

		if ($context['file']) { // Si on a bien spécifié un fichier
			do { // control block

				set_time_limit(120); //pas d'effet si safe_mode on ; on met le temps à unlimited
				//nom du fichier SQL
				$sqlfile = tempnam(tmpdir(), 'lodelimport_');
				//noms des répertoires acceptés
				$accepteddirs = array('lodel/txt', 'lodel/rtf', 'lodel/sources', 'docannexe/file', 'docannexe/image');
		
				require_once 'backupfunc.php';
				if (!importFromZip($context['file'], $accepteddirs, array(), $sqlfile)) {
					
					$err = $error['error_extract'] = 'extract';
					return 'import';
				}
				#require_once 'connect.php';
				// drop les tables existantes
				$db->execute(lq('DROP TABLE IF EXISTS '. join(',', $GLOBALS['lodelsitetables']))) or dberror();
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
	 * @param array $error les éventuelles erreur, passées par référence
	 */
	function backupAction(&$context, &$error)
	{
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
			$this->_dump($site, $tmpdir. '/'. $outfile, $errors);
			if($errors) {
				$error = $errors;
				return 'backup';
			}
			// verifie que le fichier SQL n'est pas vide
			if (filesize($tmpdir. '/'. $outfile) <= 0) {
				$error['mysql'] = 'dump_failed';
				return 'backup';
			}
		
			// tar le site et ajoute la base
			$archivetmp      = tempnam($tmpdir, 'lodeldump_'). '.zip';
			$archivefilename = "site-$site-". date("dmy"). '.zip';
			$GLOBALS['excludes'] = $excludes        = array('lodel/sources/.htaccess',
															'docannexe/fichier/.htaccess',
															'docannexe/image/index.html',
															'docannexe/index.html',
															'docannexe/image/tmpdir-\*',
															'docannexe/tmp\*'
															);
			$dirs            = $context['sqlonly'] ? '' : 'lodel/sources docannexe';
		
			if ($zipcmd && $zipcmd != 'pclzip') { //Commande ZIP
				if (!$context['sqlonly']) {
					if (!chdir(SITEROOT)) {
						die ("ERROR: can't chdir in SITEROOT");
					}
					$prefixdir    = $tmpdir[0] == "/" ? '' : 'lodel/admin/';
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

				//require_once "pclzip.lib.php";
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
					$archive->create(array(SITEROOT. 'lodel/sources',
							SITEROOT. 'docannexe',
							$tmpdir. '/'. $outfile
							),
							PCLZIP_OPT_REMOVE_PATH,SITEROOT,
							PCLZIP_CB_PRE_ADD, 'preadd'
							);
				} else {
					$archive->create($tmpdir. "/". $outfile, PCLZIP_OPT_REMOVE_ALL_PATH);
				}
			} // end of pclzip option
		
			if (!file_exists($archivetmp)) {
				die ("ERROR: the zip command or library does not produce any output");
			}
			@unlink($tmpdir. '/'. $outfile); // delete the sql file
			#echo "toto";exit;
			if (operation($context['operation'], $archivetmp, $archivefilename, $context)) {
				$context['success'] = 1;
				return 'backup';
			}
			else {
				$context['success'] = 1;
				return 'backup';
				//???
			}
			return 'backup';
		}
		else {
			return 'backup';
		}
	}

	/**
	 * Importation du modèle éditorial
	 *
	 */
	function importmodelAction(&$context, &$error)
	{

	}


	/**
	 * Sauvegarde du modèle éditorial
	 *
	 *
	 */
	function backupmodelAction(&$context, &$error)
	{
		
	}


	/**
	 * Dump SQL d'un site donné
	 *
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
	
		mysql_dump($dbname, $tables, $outfile, $fh);
	
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
	 * @return true si le dump a bien été executé
	 */
	function _execute_dump($url, $ignoreerrors = false) 
	{
		$file_content = file($url);
		$query = '';
		foreach($file_content as $sql_line) {
			$tsl = trim($sql_line);
			if (($sql_line != "") && (substr($tsl, 0, 2) != "--") && (substr($tsl, 0, 1) != "#")) {
				$query .= $sql_line;
				if(preg_match("/;\s*$/", $sql_line)) {
					#echo "query:".lq($query)."<br />";
					$result = mysql_query(lq($query));
					if (!$result && !$ignoreerrors) die(mysql_error());
					$query = '';
				}
			}
		}
		return true;
	}

	/**
	 * Vérifie les fichiers CACHE et .htaccess et recré les .htaccess.
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

}// end of DataLogic class


//Définition de la LOOP sur les fichiers d'import détectés
function loop_files(&$context, $funcname)
{
	#global $importdirs,$fileregexp;
	$context['importdirs'][] = $GLOBALS['importdir'];
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

?>