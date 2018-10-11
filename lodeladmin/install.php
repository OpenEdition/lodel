<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

class Install {

        const CRITICAL = 'CRITICAL';
        const LODELROOT = '../';
	const MIN_VERSION = '5.4.0';
	static private $php_exts = array(
				'mbstring',
				'xml',
				'gd',
				'curl',
				'mysqlnd',
				'zip',
				);
	static private $error = NULL;
	static private $lodelCfgLoc = self::LODELROOT.'lodelconfig.php';	
	static private $initFile = self::LODELROOT.'lodel/install/init.sql';
	static private $initTranslationsFile = self::LODELROOT.'lodel/install/init-translations.sql';
	static private $username = 'admin';

	static private function checkError() {	
		if( self::CRITICAL === self::$error ) {
			echo "<h3 class=\"error\">Interruption, check error messages above</h3>\n";
			self::closeHtml();
			exit(); 
		} else {
			echo "<h3 class=\"ok\">Completed</h3>";
		}
	}

	static private function checkLodelCfgExists() {
		return is_file(self::$lodelCfgLoc) && is_readable(self::$lodelCfgLoc);
	}

	static private function checkLodelCfgLoaded() {
		return defined('INC_LODELCONFIG');
	}

	static private function checkLodelCfg() {
		return self::checkLodelCfgExists() && self::checkLodelCfgLoaded();
	}

        static private function includeConnect() {
                defined('INC_CONNECT') || include 'connect.php';
        }

        static private function mysql_query_file($file, $droptables = FALSE) {
                self::includeConnect();
                global $db;
                $sqlfile = preg_replace('/#_M?TP_/', C::get('tableprefix', 'cfg'), file_get_contents($file));
                $sqlfile = str_replace('_CHARSET_', ' CHARACTER SET utf8 COLLATE utf8_general_ci' , $sqlfile);
                if (!$sqlfile) return;
                $len = strlen($sqlfile);
                $ilast = 0;
                for ($i = 0; $i < $len; $i++) {
                        $c = $sqlfile{$i};
                        if ($c == '\\') { $i++; continue; } // quoted char
                        if ($c == '#') {
                                for (; $i < $len; $i++) {
                                        if ($sqlfile{$i} == "\n") break;
                                        $sqlfile{$i} = " ";
                                }
                        } elseif ($c == "'") {
                                $i++;
                                for (; $i < $len; $i++) {
                                        $c = $sqlfile{$i};
                                        if ($c == '\\') { $i++; continue; } // quoted char
                                        if ($c == "'") break;
                                }
                        } elseif ($c == ";") { // end of SQL statment
                                $cmd = trim(substr($sqlfile, $ilast, $i - $ilast));
                               // echo $cmd,"\n";
                                if ($cmd) {
                                        // should we drop tables before create them ?
                                        if ($droptables && preg_match('/^\s*CREATE\s+(?:TABLE\s+IF\s+NOT\s+EXISTS\s+)?'.C::get('tableprefix', 'cfg').'(\w+)/', $cmd, $result)) {
                                                if (!$db->query('DROP TABLE IF EXISTS '.$result[1])) {
                                                        echo "<strong>SQL ERROR: ".$db->ErrorMsg()."</strong>\n";
                                                        self::$error = self::CRITICAL;
                                                }
                                        }
                                        // execute the command
                                        if (!$db->query($cmd)) {
                                                echo "<strong>SQL ERROR: ".$db->ErrorMsg()."</strong>\n";
                                                self::$error = self::CRITICAL;
                                        }
                                }
                                $ilast = $i+1;
                        }
                }
                self::checkError();
        }

	static private function createPass() {
		class_exists('PWGen') || include '../lodel/scripts/vendor/autoload.php';
		$pwgen = new PWGen\PWGen(15);
		return $pwgen->generate();
	}

        static public function checkPHPExts() {
                $msg = '';
		if(version_compare(PHP_VERSION, self::MIN_VERSION) >= 0) {
			$msg .= "PHP Version ".PHP_VERSION.": <strong class=\"ok\">OK</strong>\n";
		} else {
			$msg .= "PHP Version ".PHP_VERSION.": <strong class=\"error\">Too low, you need at least ".self::MIN_VERSION."</strong>\n";
			self::$error = self::CRITICAL;
		}
                foreach(self::$php_exts as $ext) {
                        if(TRUE ===  extension_loaded($ext)) {
                                $msg .= "{$ext}: <strong class=\"ok\">OK</strong>\n";
                        } else {
                                $msg .= "{$ext}: <strong class=\"error\">Missing<strong>\n";
                                self::$error = self::CRITICAL;
                        }
                }
                echo $msg;
                self::checkError();
        }

	static public function includeCfg() {
		if(self::checkLodelCfgLoaded()) return;
		if(TRUE === self::checkLodelCfgExists()) {
			require 'lodelconfig.php';
		} else {
			echo "<strong class=\"error\">Fail include lodelconfig</strong>\n";
			self::$error = self::CRITICAL;
		}
		if(TRUE !== self::checkLodelCfgLoaded()) {
                        echo "<strong class=\"error\">Fail load lodelconfig</strong>\n";
                        self::$error = self::CRITICAL;
		}
		self::checkError();
	}

	static public function checkInstallKey() {
		$ok = C::get('install_key', 'cfg') && file_exists(self::LODELROOT.C::get('install_key', 'cfg'));
		if(TRUE !== $ok) {
			echo "<strong class=\"error\">Key file doesn't exist</strong>\n";
			self::$error = self::CRITICAL;
		}
		self::checkError();
        }


	static public function checkDB(){
		function_exists('ADONewConnection') || require "vendor/autoload.php";
		$db = ADONewConnection(C::get('dbDriver', 'cfg'));
		$conn = $db->connect(C::get('dbhost', 'cfg'), C::get('dbusername', 'cfg'), C::get('dbpasswd', 'cfg'), C::get('database', 'cfg'));
		if(TRUE !== $conn){
			echo "<strong>SQL ERROR: ".$db->ErrorMsg()."</strong>\n";
			self::$error = self::CRITICAL;
		}
		self::checkError();
	}

	static public function createTables(){
		self::mysql_query_file(self::$initFile);
	}

        static public function insertTexts(){
                self::mysql_query_file(self::$initTranslationsFile);
        }

	static public function createAdmin(){
		self::includeConnect();
		global $db;
		$clearPasswd = self::createPass();
		$passwd = md5($clearPasswd.self::$username);
		$q = lq("INSERT INTO #_MTP_users (username, passwd, userrights, gui_user_complexity) VALUES ('".self::$username."','{$passwd}', 128, 64)");
		if(!$db->query($q)){
			echo "<strong>SQL ERROR: ".$db->ErrorMsg()."</strong>\n";
			self::$error = self::CRITICAL;
		}
		self::checkError();
		echo "Username: <strong>".self::$username."</strong>\n";
		echo "Password: <strong>{$clearPasswd}</strong>\n";
        }

	static public function finish(){
		$msg = '<p class="finish">Lodel is now installed, copy the SuperAdmin account parameters above somewhere and log here: <a target="_blank" href="index.php?do=view&lo=users">User Creation</a> to create a permanent Lodel Admin.</p>';
		$msg .= "<p class=\"finish\">After that you should:\n
		1. Log in with your new account and delete the automatically created one;\n
		2. delete the key file in order to prevent this installation script to be run;\n
		3. create your first site via the general admin interface.\n
		</p>";
		echo $msg;
	}

	static public function openHtml(){
		$open = <<<EOD
<!DOCTYPE html>
<html>
<head>
<title>Lodel Installation</title>
<meta charset="UTF-8">
<style>
.ok{color:green}
.error{color:red}
.finish{font-size:120%;font-weight:bold}
</style>
</head>
<body>
<pre>
EOD;
		echo $open;
	}

        static public function closeHtml(){
                $close = <<<EOD
</pre>
</body>  
</html>
EOD;
                echo $close;
        }

	
}



Install::openHtml();
echo <<<EOD
<h1>Lodel Installation</h1>
EOD;
echo <<<EOD
<h2>PHP Extensions Check</h2>
EOD;
Install::checkPHPExts();
echo <<<EOD
<h2>Config File</h2>
EOD;
Install::includeCfg();
echo <<<EOD
<h2>Install key file</h2>
EOD;
Install::checkInstallKey();
echo <<<EOD
<h2>DB Connection</h2>
EOD;
Install::checkDB();
echo <<<EOD
<h2>Tables Creation</h2>
EOD;
Install::createTables();
echo <<<EOD
<h2>Texts Insertion</h2>
EOD;
Install::insertTexts();
echo <<<EOD
<h2>SuperAdmin Creation</h2>
EOD;
Install::createAdmin();
Install::finish();
Install::closeHtml();	
