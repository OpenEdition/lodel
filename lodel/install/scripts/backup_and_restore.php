<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

if( php_sapi_name() != "cli") die();

if(!( isset($argv[1]) && isset($argv[2]) && isset($argv[3]) ) ) die("Usage php backup_and_restore.php [backup|import] [sitename] [file.zip]"."\n");
if(!preg_match("/^[a-z0-9\-]+$/", $argv[1])) die("Site name incorrect");

$action = $argv[1];
$site   = $argv[2];
$source = $argv[3];

define('backoffice-lodeladmin', true);
define("SITEROOT", $site . DIRECTORY_SEPARATOR);

require_once 'lodelconfig.php';
require_once 'lodel/scripts/context.php';
C::setCfg($cfg);

require_once 'lodel/scripts/connect.php';
require_once 'lodel/scripts/auth.php';

set_include_path( getcwd() . DIRECTORY_SEPARATOR . "lodel" . DIRECTORY_SEPARATOR . "scripts" . PATH_SEPARATOR . get_include_path() );

require_once 'logic/class.data.php';
require_once 'cache.php';

class DataCLI extends DataLogic {
	public function backupSite($site, $destination){
		$zipcmd = C::get('zipcmd', 'cfg');
		$outfile = "site-{$site}.sql";
		$tmpdir  = tmpdir();
		$fh = fopen($tmpdir. DIRECTORY_SEPARATOR . $outfile, 'w');
		$this->_dump($site, $tmpdir. DIRECTORY_SEPARATOR . $outfile, $errors, $fh);
		
		
		$excludes = array('lodel/sources/.htaccess',
				'docannexe/fichier/.htaccess',
				'docannexe/file/.htaccess',
				'docannexe/image/index.html',
				'docannexe/index.html',
				'docannexe/image/tmpdir-\*',
				'docannexe/tmp\*');
		$dirs = implode(" ",array("lodel/icons", "lodel/sources", "docannexe"));
		
		
		if ($zipcmd && $zipcmd != 'pclzip') {
			chdir($site);
			system("{$zipcmd} -q {$destination} -r $dirs " . join(" -x ", $excludes) . "\n");
			system("{$zipcmd} -q -g $destination -j $tmpdir/$outfile");
		}

		echo "Fichier backup enregistré dans $site/$destination"."\n";
	}
	
	public function importSite($site, $source){
		global $db;
		$db->SelectDB(DATABASE . "_{$site}");

		$sqlfile = tempnam(tmpdir(), 'lodelimport_');
		$accepteddirs = array('lodel/sources', 'lodel/icons', 'docannexe/file', 'docannexe/image', 'docannexe/fichier');
		chdir( $site . DIRECTORY_SEPARATOR . "lodel" . DIRECTORY_SEPARATOR . "admin" );
		if (!importFromZip($source, $accepteddirs, array(), $sqlfile)) {
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
		
	}
}

C::set('do','backup');
C::setUser('adminlodel');


$logic = new DataCLI();
switch($action){
	case "backup":
		$logic->backupSite($site, $source);
		break;
	case "import":
		$logic->importSite($site, $source);
		break;
}

